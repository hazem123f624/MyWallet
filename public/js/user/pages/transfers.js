import { renderTransferDetails } from '../core/transfer-details.js?v=1';
import { apiGet, apiPost } from '../../dashboard/core/api.js?v=5';
import { escapeHtml, formatDateTime, qs, qsa } from '../../dashboard/core/dom.js?v=5';
import { closeModal, openModal } from '../../dashboard/core/modal.js?v=5';
import { toast } from '../../dashboard/core/toast.js?v=5';

const state = {
    subWallets: [],
    beneficiaries: [],
    currency: '',
    detailId: null,
    detailStatus: '',
};

const errorMessage = (error) => {
    const first = error.payload?.errors ? Object.values(error.payload.errors)[0] : null;

    return Array.isArray(first) ? first[0] : (first || error.message || 'The action could not be completed.');
};

const money = (value) => `${state.currency || ''}${Number(value || 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
})}`;

const statusClass = (status) => {
    if (status === 'successful') {
        return 'dash-badge-success';
    }

    if (status === 'failed') {
        return 'dash-badge-danger';
    }

    if (status === 'refunded') {
        return 'dash-badge-neutral';
    }

    return 'dash-badge-warning';
};

const renderTransfers = (transfers) => {
    const body = qs('#recentTransfersBody');
    const empty = qs('#recentTransfersEmpty');

    empty.hidden = transfers.length > 0;

    body.innerHTML = transfers.map((transfer) => `
        <tr>
            <td>
                <span class="dash-table-name">${escapeHtml(transfer.reference)}</span>
                <span class="dash-table-subtext">${escapeHtml(transfer.provider_reference || '')}</span>
            </td>
            <td>
                <span class="dash-table-name">${escapeHtml(transfer.beneficiary?.name || '—')}</span>
                <span class="dash-table-subtext">${escapeHtml(transfer.beneficiary?.bank_name || '')}</span>
            </td>
            <td>${escapeHtml(transfer.sub_wallet?.name || '—')}</td>
            <td>${escapeHtml(money(transfer.amount))}</td>
            <td><span class="dash-badge ${statusClass(transfer.status)}">${escapeHtml(transfer.status)}</span></td>
            <td class="dash-col-date">${escapeHtml(formatDateTime(transfer.requested_at))}</td>
            <td class="dash-table-actions">
                <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-transfer-action="view" data-id="${transfer.id}">View</button>
            </td>
        </tr>
    `).join('');
};

const openDetails = async (id) => {
    try {
        const response = await apiGet(`/mobile/transfers/${id}`);
        const transfer = response.data ?? {};
        state.detailId = transfer.id ?? id;
        state.detailStatus = transfer.status || '';

        renderTransferDetails(transfer, money);
        openModal('transferDetailsModal');
    } catch (error) {
        toast(error.message || 'Unable to load transfer details.', 'error');
    }
};

const fillSelects = () => {
    qs('#transferSubWallet').innerHTML = state.subWallets.map((wallet) => `
        <option value="${wallet.id}">${escapeHtml(wallet.name)} · ${escapeHtml(money(wallet.available_balance))} available</option>
    `).join('');

    qs('#transferBeneficiary').innerHTML = state.beneficiaries.map((beneficiary) => `
        <option value="${beneficiary.id}">${escapeHtml(beneficiary.name)} · ${escapeHtml(beneficiary.bank_name)} · ${escapeHtml(beneficiary.account_number)}</option>
    `).join('');

    const disabled = state.subWallets.length === 0 || state.beneficiaries.length === 0;
    qs('#transferSubmit').disabled = disabled;

    if (state.subWallets.length === 0) {
        qs('#transferLimits').textContent = 'Create a Sub Wallet before sending money.';
    } else if (state.beneficiaries.length === 0) {
        qs('#transferLimits').textContent = 'Add a beneficiary before sending money.';
    }
};

const loadLimits = async () => {
    try {
        const response = await apiGet('/mobile/transfers/limits');
        const data = response.data ?? {};

        qs('#limitMin').textContent = money(data.min_amount);
        qs('#limitMax').textContent = money(data.max_amount);
        qs('#limitDaily').textContent = money(data.daily_remaining);
        qs('#limitMonthly').textContent = money(data.monthly_remaining);

        if (state.subWallets.length > 0 && state.beneficiaries.length > 0) {
            qs('#transferLimits').textContent = `Daily remaining ${money(data.daily_remaining)} · Monthly remaining ${money(data.monthly_remaining)}`;
        }
    } catch (error) {
        toast(error.message || 'Unable to load transfer limits.', 'error');
    }
};

const load = async () => {
    try {
        const [walletResponse, subWalletsResponse, beneficiariesResponse, transfersResponse] = await Promise.all([
            apiGet('/mobile/parent-wallet'),
            apiGet('/mobile/sub-wallets'),
            apiGet('/mobile/beneficiaries'),
            apiGet('/mobile/transfers'),
        ]);

        state.currency = walletResponse.data?.currency_symbol ?? '';
        state.subWallets = Array.isArray(subWalletsResponse.data) ? subWalletsResponse.data : [];
        state.beneficiaries = Array.isArray(beneficiariesResponse.data) ? beneficiariesResponse.data : [];

        fillSelects();
        renderTransfers(Array.isArray(transfersResponse.data) ? transfersResponse.data : []);
        await loadLimits();
    } catch (error) {
        toast(error.message || 'Unable to load transfer data.', 'error');
    }
};

const showResult = (transfer) => {
    const panel = qs('#transferResult');
    const status = qs('#transferResultStatus');
    const failure = qs('#transferResultFailure');

    panel.hidden = false;
    qs('#transferResultReference').textContent = transfer.reference;
    qs('#transferResultAmount').textContent = money(transfer.amount);
    qs('#transferResultBeneficiary').textContent = `${transfer.beneficiary?.name || ''} · ${transfer.beneficiary?.bank_name || ''}`;
    status.textContent = transfer.status;
    status.className = `dash-badge ${statusClass(transfer.status)}`;

    if (transfer.failure_reason) {
        failure.textContent = transfer.failure_reason;
        failure.hidden = false;
    } else {
        failure.hidden = true;
    }
};

const submit = async (event) => {
    event.preventDefault();
    const errorBox = qs('#transferError');
    const button = qs('#transferSubmit');
    errorBox.hidden = true;
    button.disabled = true;

    try {
        const response = await apiPost('/mobile/transfers', {
            sub_wallet_id: Number(qs('#transferSubWallet').value),
            beneficiary_id: Number(qs('#transferBeneficiary').value),
            amount: qs('#transferAmount').value,
            transaction_pin: qs('#transferPin').value,
            note: qs('#transferNote').value.trim() || null,
            client_reference: `web-${Date.now()}`,
        });

        qs('#transferForm').reset();
        showResult(response.data ?? {});
        toast(response.message || 'Transfer processed.');
        await load();
    } catch (error) {
        errorBox.textContent = errorMessage(error);
        errorBox.hidden = false;
    } finally {
        button.disabled = false;
    }
};

export const initTransfersPage = () => {
    qs('#transferForm')?.addEventListener('submit', submit);
    qs('[data-transfers-refresh]')?.addEventListener('click', load);

    qs('#recentTransfersBody')?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-transfer-action]');

        if (button) {
            openDetails(button.dataset.id);
        }
    });

    qsa('#transferDetailsModal [data-close-modal]').forEach((button) => {
        button.addEventListener('click', () => closeModal('transferDetailsModal'));
    });

    qs('#userTransferDetailRefresh')?.addEventListener('click', async () => {
        if (!state.detailId) {
            return;
        }

        const button = qs('#userTransferDetailRefresh');
        button.disabled = true;

        try {
            const response = await apiPost(`/mobile/transfers/${state.detailId}/refresh`);
            toast(response.message || 'Transfer status checked.');
            await openDetails(state.detailId);
            await load();
        } catch (error) {
            toast(error.message || 'Unable to refresh transfer status.', 'error');
        } finally {
            button.disabled = !['pending', 'processing'].includes(state.detailStatus);
        }
    });

    load();
};
