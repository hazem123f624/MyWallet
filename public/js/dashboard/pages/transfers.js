import { apiGet, apiPost } from '../core/api.js?v=4';
import { debounce, escapeHtml, formatDate, formatDateTime, qs, qsa } from '../core/dom.js?v=4';
import { closeModal, openModal } from '../core/modal.js?v=4';
import { toast } from '../core/toast.js?v=4';

const state = {
    page: 1,
    lastPage: 1,
    perPage: 15,
    rows: [],
    status: '',
    search: '',
    reference: '',
    dateFrom: '',
    dateTo: '',
    detailId: null,
    detailStatus: '',
};

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

const money = (amount, currency) => {
    const symbol = currency === 'EGP' ? 'E£' : currency;

    return `${symbol || ''}${Number(amount || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
};

const renderRows = () => {
    const body = qs('#transfersTableBody');
    const empty = qs('#transfersEmpty');

    empty.hidden = state.rows.length > 0;

    body.innerHTML = state.rows.map((transfer) => `
        <tr>
            <td>
                <span class="dash-table-name">${escapeHtml(transfer.reference)}</span>
                <span class="dash-table-subtext">${escapeHtml(transfer.provider_reference || 'No provider reference')}</span>
            </td>
            <td>${escapeHtml(transfer.user?.name || `User #${transfer.user_id}`)}</td>
            <td>${escapeHtml(money(transfer.amount, transfer.currency))}</td>
            <td>${escapeHtml(transfer.sub_wallet?.name || '—')}</td>
            <td>
                <span class="dash-table-name">${escapeHtml(transfer.beneficiary?.name || '—')}</span>
                <span class="dash-table-subtext">${escapeHtml(transfer.beneficiary?.bank_name || '')}</span>
            </td>
            <td><span class="dash-badge ${statusClass(transfer.status)}">${escapeHtml(transfer.status)}</span></td>
            <td class="dash-col-date">${escapeHtml(formatDateTime(transfer.requested_at))}</td>
            <td class="dash-table-actions">
                <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-transfer-action="view" data-id="${transfer.id}">View</button>
            </td>
        </tr>
    `).join('');
};

const updatePagination = (pagination = {}) => {
    const currentPage = Number(pagination.current_page ?? 1);
    const lastPage = Math.max(1, Number(pagination.last_page ?? 1));
    const total = Number(pagination.total ?? 0);

    state.page = currentPage;
    state.lastPage = lastPage;
    qs('#transfersPageInfo').textContent = `Page ${currentPage} of ${lastPage} · ${total} transfer${total === 1 ? '' : 's'}`;
    qs('#transfersPrevPage').disabled = currentPage <= 1;
    qs('#transfersNextPage').disabled = currentPage >= lastPage;
};

const loadStatistics = async () => {
    try {
        const response = await apiGet('/dashboard/transfers/statistics');
        const data = response.data ?? {};

        qs('#transferStatTotal').textContent = String(data.total ?? 0);
        qs('#transferStatPending').textContent = String(data.pending ?? 0);
        qs('#transferStatSuccessful').textContent = String(data.successful ?? 0);
        qs('#transferStatFailed').textContent = String(data.failed ?? 0);
        qs('#transferStatReversed').textContent = String(data.reversed ?? 0);
        qs('#transferStatRefunded').textContent = String(data.refunded ?? 0);
    } catch (error) {
        toast(error.message || 'Unable to load transfer statistics.', 'error');
    }
};

const load = async () => {
    const loading = qs('#transfersLoading');
    loading.hidden = false;

    try {
        const response = await apiGet('/dashboard/transfers', {
            page: state.page,
            per_page: state.perPage,
            status: state.status,
            search: state.search,
            reference: state.reference,
            date_from: state.dateFrom,
            date_to: state.dateTo,
        });

        state.rows = Array.isArray(response.data) ? response.data : [];
        renderRows();
        updatePagination(response.pagination);
    } catch (error) {
        state.rows = [];
        renderRows();
        toast(error.message || 'Unable to load transfers.', 'error');
    } finally {
        loading.hidden = true;
    }
};

const renderTimeline = (events = []) => {
    const host = qs('#transferDetailTimeline');

    if (!events.length) {
        host.innerHTML = '<span class="dash-muted-text">No lifecycle events recorded.</span>';

        return;
    }

    host.innerHTML = events.map((event) => `
        <div class="user-timeline-item">
            <span class="user-timeline-dot"></span>
            <span>
                <strong>${escapeHtml(event.type)}</strong>
                <small>${escapeHtml(formatDateTime(event.created_at))}</small>
                <p>${escapeHtml(event.message || '')}</p>
            </span>
        </div>
    `).join('');
};

const renderReversal = (reversal) => {
    const host = qs('#transferDetailReversal');

    if (!reversal) {
        host.textContent = 'No reversal or refund recorded.';

        return;
    }

    host.innerHTML = `
        <strong>${escapeHtml(reversal.type)}</strong>
        <span>${escapeHtml(money(reversal.amount, reversal.currency))} · ${escapeHtml(reversal.reason || 'Returned to Sub Wallet')}</span>
    `;
};

const openDetails = async (id) => {
    try {
        const response = await apiGet(`/dashboard/transfers/${id}`);
        const transfer = response.data ?? {};
        const failure = qs('#transferDetailFailure');
        state.detailId = transfer.id ?? id;
        state.detailStatus = transfer.status || '';

        qs('#transferDetailReference').textContent = transfer.reference || '—';
        qs('#transferDetailUser').textContent = transfer.user?.name || `User #${transfer.user_id}`;
        qs('#transferDetailStatus').textContent = transfer.status || '—';
        qs('#transferDetailAmount').textContent = money(transfer.amount, transfer.currency);
        qs('#transferDetailSubWallet').textContent = transfer.sub_wallet?.name || '—';
        qs('#transferDetailBeneficiary').textContent = `${transfer.beneficiary?.name || ''} · ${transfer.beneficiary?.bank_name || ''} · ${transfer.beneficiary?.account_number || ''}`;
        qs('#transferDetailProviderReference').textContent = transfer.provider_reference || '—';
        qs('#transferDetailRequested').textContent = formatDateTime(transfer.requested_at);
        qs('#transferDetailCompleted').textContent = formatDateTime(transfer.completed_at);

        if (transfer.failure_reason) {
            failure.textContent = transfer.failure_reason;
            failure.hidden = false;
        } else {
            failure.hidden = true;
        }

        renderReversal(transfer.reversal);
        renderTimeline(Array.isArray(transfer.events) ? transfer.events : []);
        qs('#transferDetailSync').disabled = !['pending', 'processing'].includes(state.detailStatus);
        openModal('transferDetailsModal');
    } catch (error) {
        toast(error.message || 'Unable to load transfer details.', 'error');
    }
};

export const initTransfersPage = () => {
    const search = debounce(() => {
        state.search = qs('#transferSearch').value.trim();
        state.page = 1;
        load();
    }, 300);

    const reference = debounce(() => {
        state.reference = qs('#transferReference').value.trim();
        state.page = 1;
        load();
    }, 300);

    qs('#transferSearch')?.addEventListener('input', search);
    qs('#transferReference')?.addEventListener('input', reference);
    qs('#transferStatusFilter')?.addEventListener('change', () => {
        state.status = qs('#transferStatusFilter').value;
        state.page = 1;
        load();
    });
    qs('#transferDateFrom')?.addEventListener('change', () => {
        state.dateFrom = qs('#transferDateFrom').value;
        state.page = 1;
        load();
    });
    qs('#transferDateTo')?.addEventListener('change', () => {
        state.dateTo = qs('#transferDateTo').value;
        state.page = 1;
        load();
    });

    qs('[data-transfer-refresh]')?.addEventListener('click', () => {
        loadStatistics();
        load();
    });

    qs('#transfersPrevPage')?.addEventListener('click', () => {
        if (state.page > 1) {
            state.page -= 1;
            load();
        }
    });

    qs('#transfersNextPage')?.addEventListener('click', () => {
        if (state.page < state.lastPage) {
            state.page += 1;
            load();
        }
    });

    qs('#transfersTableBody')?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-transfer-action]');

        if (button) {
            openDetails(button.dataset.id);
        }
    });

    qsa('#transferDetailsModal [data-close-modal]').forEach((button) => {
        button.addEventListener('click', () => closeModal('transferDetailsModal'));
    });

    qs('#transferDetailSync')?.addEventListener('click', async () => {
        if (!state.detailId) {
            return;
        }

        const button = qs('#transferDetailSync');
        button.disabled = true;

        try {
            const response = await apiPost(`/dashboard/transfers/${state.detailId}/sync`);
            toast(response.message || 'Transfer status checked.');
            await openDetails(state.detailId);
            await load();
            await loadStatistics();
        } catch (error) {
            toast(error.message || 'Unable to sync transfer status.', 'error');
        } finally {
            button.disabled = !['pending', 'processing'].includes(state.detailStatus);
        }
    });

    loadStatistics();
    load();
};
