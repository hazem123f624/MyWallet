import { renderTransferDetails } from '../core/transfer-details.js?v=1';
import { apiGet, apiPost } from '../../dashboard/core/api.js?v=5';
import { debounce, escapeHtml, formatDateTime, qs, qsa } from '../../dashboard/core/dom.js?v=5';
import { closeModal, openModal } from '../../dashboard/core/modal.js?v=5';
import { toast } from '../../dashboard/core/toast.js?v=5';

const state = {
    page: 1,
    lastPage: 1,
    perPage: 15,
    rows: [],
    reference: '',
    status: '',
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
    const body = qs('#transactionsTableBody');
    const empty = qs('#transactionsEmpty');

    empty.hidden = state.rows.length > 0;

    body.innerHTML = state.rows.map((transfer) => `
        <tr>
            <td>
                <span class="dash-table-name">${escapeHtml(transfer.reference)}</span>
                <span class="dash-table-subtext">${escapeHtml(transfer.provider_reference || 'No provider reference')}</span>
            </td>
            <td>
                <span class="dash-table-name">${escapeHtml(transfer.beneficiary?.name || '—')}</span>
                <span class="dash-table-subtext">${escapeHtml(transfer.beneficiary?.bank_name || '')}</span>
            </td>
            <td>${escapeHtml(transfer.sub_wallet?.name || '—')}</td>
            <td>${escapeHtml(money(transfer.amount, transfer.currency))}</td>
            <td><span class="dash-badge ${statusClass(transfer.status)}">${escapeHtml(transfer.status)}</span></td>
            <td class="dash-col-date">${escapeHtml(formatDateTime(transfer.requested_at))}</td>
            <td class="dash-table-actions">
                <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-transaction-action="view" data-id="${transfer.id}">View</button>
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
    qs('#transactionsPageInfo').textContent = `Page ${currentPage} of ${lastPage} · ${total} transaction${total === 1 ? '' : 's'}`;
    qs('#transactionsPrevPage').disabled = currentPage <= 1;
    qs('#transactionsNextPage').disabled = currentPage >= lastPage;
};

const load = async () => {
    const loading = qs('#transactionsLoading');
    loading.hidden = false;

    try {
        const response = await apiGet('/mobile/transfers/history', {
            page: state.page,
            per_page: state.perPage,
            reference: state.reference,
            status: state.status,
            date_from: state.dateFrom,
            date_to: state.dateTo,
        });

        state.rows = Array.isArray(response.data) ? response.data : [];
        renderRows();
        updatePagination(response.pagination);
    } catch (error) {
        state.rows = [];
        renderRows();
        toast(error.message || 'Unable to load transactions.', 'error');
    } finally {
        loading.hidden = true;
    }
};

const openDetails = async (id) => {
    try {
        const response = await apiGet(`/mobile/transfers/${id}`);
        const transfer = response.data ?? {};
        state.detailId = transfer.id ?? id;
        state.detailStatus = transfer.status || '';

        renderTransferDetails(transfer, (amount) => money(amount, transfer.currency));
        openModal('transferDetailsModal');
    } catch (error) {
        toast(error.message || 'Unable to load transfer details.', 'error');
    }
};

export const initTransactionsPage = () => {
    const reference = debounce(() => {
        state.reference = qs('#transactionReference').value.trim();
        state.page = 1;
        load();
    }, 300);

    qs('#transactionReference')?.addEventListener('input', reference);
    qs('#transactionStatus')?.addEventListener('change', () => {
        state.status = qs('#transactionStatus').value;
        state.page = 1;
        load();
    });
    qs('#transactionDateFrom')?.addEventListener('change', () => {
        state.dateFrom = qs('#transactionDateFrom').value;
        state.page = 1;
        load();
    });
    qs('#transactionDateTo')?.addEventListener('change', () => {
        state.dateTo = qs('#transactionDateTo').value;
        state.page = 1;
        load();
    });

    qs('[data-transactions-clear]')?.addEventListener('click', () => {
        qs('#transactionReference').value = '';
        qs('#transactionStatus').value = '';
        qs('#transactionDateFrom').value = '';
        qs('#transactionDateTo').value = '';
        state.reference = '';
        state.status = '';
        state.dateFrom = '';
        state.dateTo = '';
        state.page = 1;
        load();
    });

    qs('[data-transactions-refresh]')?.addEventListener('click', load);

    qs('#transactionsPrevPage')?.addEventListener('click', () => {
        if (state.page > 1) {
            state.page -= 1;
            load();
        }
    });

    qs('#transactionsNextPage')?.addEventListener('click', () => {
        if (state.page < state.lastPage) {
            state.page += 1;
            load();
        }
    });

    qs('#transactionsTableBody')?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-transaction-action]');

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
