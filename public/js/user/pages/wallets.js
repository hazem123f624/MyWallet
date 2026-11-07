import { apiDelete, apiGet, apiPatch, apiPost } from '../../dashboard/core/api.js?v=5';
import { escapeHtml, qs, qsa } from '../../dashboard/core/dom.js?v=5';
import { closeModal, confirmAction, openModal } from '../../dashboard/core/modal.js?v=5';
import { toast } from '../../dashboard/core/toast.js?v=5';

const state = {
    wallet: null,
    subWallets: [],
    maxSubWallets: 12,
};

const iconMarkup = (name) => {
    const safe = String(name || 'wallet').replace(/[^a-z0-9-]/gi, '') || 'wallet';

    return `<svg class="user-wallet-icon" aria-hidden="true"><use href="#user-icon-${safe}"></use></svg>`;
};

const getChoice = (name) => qs(`input[name="${name}"]:checked`)?.value ?? '';

const setChoice = (name, value) => {
    const inputs = qsa(`input[name="${name}"]`);
    const match = inputs.find((input) => input.value === value) ?? inputs[0];

    if (match) {
        match.checked = true;
    }
};

const money = (value) => {
    const symbol = state.wallet?.currency_symbol ?? '';

    return `${symbol}${Number(value || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
};

const errorMessage = (error) => {
    const first = error.payload?.errors ? Object.values(error.payload.errors)[0] : null;

    return Array.isArray(first) ? first[0] : (first || error.message || 'The action could not be completed.');
};

const renderSummary = () => {
    const wallet = state.wallet ?? {};
    const status = qs('#walletStatusBadge');

    status.textContent = wallet.status === 'active' ? 'Active' : (wallet.status === 'suspended' ? 'Suspended' : 'Unlinked');
    status.className = `dash-badge ${wallet.status === 'active' ? 'dash-badge-success' : 'dash-badge-warning'}`;

    qs('#walletTotalBalance').textContent = money(wallet.total_allocated_balance);
    qs('#walletAvailableBalance').textContent = money(wallet.available_balance);
    qs('#walletReservedBalance').textContent = money(wallet.reserved_balance);
    qs('#walletAllocatedBalance').textContent = money(wallet.allocated_to_sub_wallets);

    const funding = qs('#walletFundingSource');
    const strong = funding?.querySelector('strong');
    const small = funding?.querySelector('small');

    if (wallet.funding_source) {
        strong.textContent = wallet.funding_source.bank?.name || 'Linked bank account';
        small.textContent = `${wallet.funding_source.account_number} · Primary`;
    } else {
        strong.textContent = 'No funding source linked';
        small.textContent = 'Link a verified primary bank account to fund your wallet.';
    }

    const count = wallet.sub_wallets_count ?? state.subWallets.length;
    qs('#walletCountLabel').textContent = `${count} of ${state.maxSubWallets} active Sub Wallets`;
};

const renderSubWallets = () => {
    const grid = qs('#subWalletGrid');
    const empty = qs('#subWalletsEmpty');

    empty.hidden = state.subWallets.length > 0;

    grid.innerHTML = state.subWallets.map((wallet) => `
        <article class="user-subwallet-card user-subwallet-card-full" data-sub-wallet-id="${wallet.id}" style="--wallet-color: ${escapeHtml(wallet.color)}">
            <div class="user-subwallet-head">
                <span class="user-subwallet-mark">${iconMarkup(wallet.icon)}</span>
                <span class="user-subwallet-copy">
                    <strong>${escapeHtml(wallet.name || 'Sub Wallet')}</strong>
                </span>
                <span class="user-subwallet-amount">${escapeHtml(money(wallet.available_balance))}</span>
            </div>

            <div class="user-subwallet-meta">
                <span>Available <strong>${escapeHtml(money(wallet.available_balance))}</strong></span>
                <span>Reserved <strong>${escapeHtml(money(wallet.reserved_balance))}</strong></span>
            </div>

            <div class="user-subwallet-actions">
                <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-action="allocate" data-id="${wallet.id}">Allocate</button>
                <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-action="redistribute" data-id="${wallet.id}">Move</button>
                <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-action="edit" data-id="${wallet.id}">Edit</button>
                <button class="dash-button dash-button-ghost dash-button-xs dash-button-danger-text" type="button" data-action="delete" data-id="${wallet.id}">Delete</button>
            </div>
        </article>
    `).join('');

    const createButton = qs('[data-sub-wallet-create]');

    if (createButton) {
        createButton.disabled = state.subWallets.length >= state.maxSubWallets;
    }
};

const load = async () => {
    try {
        const [walletResponse, subWalletsResponse] = await Promise.all([
            apiGet('/mobile/parent-wallet'),
            apiGet('/mobile/sub-wallets'),
        ]);

        state.wallet = walletResponse.data ?? {};
        state.subWallets = Array.isArray(subWalletsResponse.data) ? subWalletsResponse.data : [];

        renderSummary();
        renderSubWallets();
    } catch (error) {
        toast(error.message || 'Unable to load your wallets.', 'error');
    }
};

const openSubWalletForm = (wallet = null) => {
    qs('#subWalletForm').reset();
    qs('#subWalletId').value = wallet?.id ?? '';
    qs('#subWalletName').value = wallet?.name ?? '';
    setChoice('sub_wallet_color', wallet?.color ?? '');
    setChoice('sub_wallet_icon', wallet?.icon ?? '');
    qs('#subWalletFormError').hidden = true;
    qs('#subWalletFormModal').querySelector('h2').textContent = wallet ? 'Edit Sub Wallet' : 'Create Sub Wallet';
    qs('#subWalletSubmit').textContent = wallet ? 'Save changes' : 'Create Sub Wallet';
    openModal('subWalletFormModal');
};

const openAllocate = (wallet) => {
    qs('#allocateForm').reset();
    qs('#allocateSubWalletId').value = wallet.id;
    qs('#allocateHint').textContent = `Allocate from ${money(state.wallet.available_balance)} available in your Parent Wallet to ${wallet.name}.`;
    qs('#allocateError').hidden = true;
    qs('#allocateModal').querySelector('h2').textContent = `Allocate to ${wallet.name}`;
    openModal('allocateModal');
};

const openRedistribute = (wallet) => {
    const select = qs('#redistributeDestination');
    const destinations = state.subWallets.filter((item) => item.id !== wallet.id);

    select.innerHTML = destinations.map((item) => `
        <option value="${item.id}">${escapeHtml(item.name)} · ${escapeHtml(money(item.available_balance))}</option>
    `).join('');

    if (destinations.length === 0) {
        toast('Create another Sub Wallet before moving funds.', 'warning');

        return;
    }

    qs('#redistributeForm').reset();
    qs('#redistributeSourceId').value = wallet.id;
    qs('#redistributeHint').textContent = `Move funds from ${wallet.name} (${money(wallet.available_balance)} available).`;
    qs('#redistributeError').hidden = true;
    qs('#redistributeModal').querySelector('h2').textContent = `Move from ${wallet.name}`;
    openModal('redistributeModal');
};

const submitSubWallet = async (event) => {
    event.preventDefault();
    const id = qs('#subWalletId').value;
    const errorBox = qs('#subWalletFormError');
    const button = qs('#subWalletSubmit');
    errorBox.hidden = true;
    button.disabled = true;

    const payload = {
        name: qs('#subWalletName').value.trim(),
        color: getChoice('sub_wallet_color'),
        icon: getChoice('sub_wallet_icon'),
    };

    try {
        const response = id
            ? await apiPatch(`/mobile/sub-wallets/${id}`, payload)
            : await apiPost('/mobile/sub-wallets', payload);

        closeModal('subWalletFormModal');
        toast(response.message || 'Sub Wallet saved.');
        await load();
    } catch (error) {
        errorBox.textContent = errorMessage(error);
        errorBox.hidden = false;
    } finally {
        button.disabled = false;
    }
};

const submitAllocate = async (event) => {
    event.preventDefault();
    const id = qs('#allocateSubWalletId').value;
    const errorBox = qs('#allocateError');
    const button = qs('#allocateSubmit');
    errorBox.hidden = true;
    button.disabled = true;

    try {
        const response = await apiPost(`/mobile/sub-wallets/${id}/allocate`, {
            amount: qs('#allocateAmount').value,
        });

        closeModal('allocateModal');
        toast(response.message || 'Funds allocated.');
        await load();
    } catch (error) {
        errorBox.textContent = errorMessage(error);
        errorBox.hidden = false;
    } finally {
        button.disabled = false;
    }
};

const submitRedistribute = async (event) => {
    event.preventDefault();
    const id = qs('#redistributeSourceId').value;
    const errorBox = qs('#redistributeError');
    const button = qs('#redistributeSubmit');
    errorBox.hidden = true;
    button.disabled = true;

    try {
        const response = await apiPost(`/mobile/sub-wallets/${id}/redistribute`, {
            destination_sub_wallet_id: Number(qs('#redistributeDestination').value),
            amount: qs('#redistributeAmount').value,
        });

        closeModal('redistributeModal');
        toast(response.message || 'Funds moved.');
        await load();
    } catch (error) {
        errorBox.textContent = errorMessage(error);
        errorBox.hidden = false;
    } finally {
        button.disabled = false;
    }
};

const deleteSubWallet = async (wallet) => {
    const confirmed = await confirmAction({
        title: 'Delete Sub Wallet',
        message: `Delete ${wallet.name}? Its available balance will return to your Parent Wallet.`,
        confirmLabel: 'Delete',
        tone: 'danger',
    });

    if (!confirmed) {
        return;
    }

    try {
        const response = await apiDelete(`/mobile/sub-wallets/${wallet.id}`);
        toast(response.message || 'Sub Wallet deleted.');
        await load();
    } catch (error) {
        toast(errorMessage(error), 'error');
    }
};

export const initWalletsPage = () => {
    const max = Number(document.body.dataset.maxSubWallets || 12);
    state.maxSubWallets = max;

    qs('[data-sub-wallet-create]')?.addEventListener('click', () => openSubWalletForm());
    qs('[data-wallets-refresh]')?.addEventListener('click', load);
    qs('#subWalletForm')?.addEventListener('submit', submitSubWallet);
    qs('#allocateForm')?.addEventListener('submit', submitAllocate);
    qs('#redistributeForm')?.addEventListener('submit', submitRedistribute);

    qs('#subWalletGrid')?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-action]');

        if (!button) {
            return;
        }

        const wallet = state.subWallets.find((item) => String(item.id) === button.dataset.id);

        if (!wallet) {
            return;
        }

        if (button.dataset.action === 'allocate') {
            openAllocate(wallet);
        } else if (button.dataset.action === 'redistribute') {
            openRedistribute(wallet);
        } else if (button.dataset.action === 'edit') {
            openSubWalletForm(wallet);
        } else if (button.dataset.action === 'delete') {
            deleteSubWallet(wallet);
        }
    });

    qsa('#subWalletFormModal [data-close-modal], #allocateModal [data-close-modal], #redistributeModal [data-close-modal]').forEach((button) => {
        button.addEventListener('click', () => closeModal(button.closest('.dash-modal')?.id ?? 'subWalletFormModal'));
    });

    load();
};
