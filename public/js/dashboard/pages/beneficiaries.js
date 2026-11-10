import { apiGet } from '../core/api.js?v=4';
import { debounce, escapeHtml, formatDate, qs, qsa } from '../core/dom.js?v=4';
import { closeModal, openModal } from '../core/modal.js?v=4';
import { toast } from '../core/toast.js?v=4';

const state = {
    page: 1,
    lastPage: 1,
    perPage: 15,
    rows: [],
    search: '',
    userId: '',
};

const renderRows = () => {
    const body = qs('#adminBeneficiariesTableBody');
    const empty = qs('#adminBeneficiariesEmpty');

    empty.hidden = state.rows.length > 0;

    body.innerHTML = state.rows.map((beneficiary) => `
        <tr>
            <td>
                <span class="dash-table-name">${escapeHtml(beneficiary.name)}</span>
                <span class="dash-table-subtext">#${escapeHtml(beneficiary.id)}</span>
            </td>
            <td>
                <span class="dash-table-name">${escapeHtml(beneficiary.user?.name || `User #${beneficiary.user_id}`)}</span>
                <span class="dash-table-subtext">${escapeHtml(beneficiary.user?.phone || '')}</span>
            </td>
            <td>${escapeHtml(beneficiary.bank_name)}</td>
            <td>${escapeHtml(beneficiary.account_number)}</td>
            <td><span class="dash-badge ${beneficiary.is_active ? 'dash-badge-success' : 'dash-badge-warning'}">${beneficiary.is_active ? 'Active' : 'Inactive'}</span></td>
            <td class="dash-col-date">${escapeHtml(formatDate(beneficiary.created_at))}</td>
            <td class="dash-table-actions">
                <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-admin-beneficiary-action="view" data-id="${beneficiary.id}">View</button>
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
    qs('#adminBeneficiariesPageInfo').textContent = `Page ${currentPage} of ${lastPage} · ${total} beneficiar${total === 1 ? 'y' : 'ies'}`;
    qs('#adminBeneficiariesPrevPage').disabled = currentPage <= 1;
    qs('#adminBeneficiariesNextPage').disabled = currentPage >= lastPage;
};

const load = async () => {
    const loading = qs('#adminBeneficiariesLoading');
    loading.hidden = false;

    try {
        const response = await apiGet('/dashboard/beneficiaries', {
            page: state.page,
            per_page: state.perPage,
            search: state.search,
            user_id: state.userId,
        });

        state.rows = Array.isArray(response.data) ? response.data : [];
        renderRows();
        updatePagination(response.pagination);
    } catch (error) {
        state.rows = [];
        renderRows();
        toast(error.message || 'Unable to load beneficiaries.', 'error');
    } finally {
        loading.hidden = true;
    }
};

const openDetails = async (id) => {
    try {
        const response = await apiGet(`/dashboard/beneficiaries/${id}`);
        const beneficiary = response.data ?? {};

        qs('#adminBeneficiaryName').textContent = beneficiary.name || '—';
        qs('#adminBeneficiaryUser').textContent = beneficiary.user?.name || `User #${beneficiary.user_id}`;
        qs('#adminBeneficiaryBank').textContent = `${beneficiary.bank_name || ''} ${beneficiary.bank_code ? `(${beneficiary.bank_code})` : ''}`;
        qs('#adminBeneficiaryAccount').textContent = beneficiary.account_number || '—';
        qs('#adminBeneficiaryIban').textContent = beneficiary.iban || '—';
        qs('#adminBeneficiaryStatus').textContent = beneficiary.is_active ? 'Active' : 'Inactive';
        qs('#adminBeneficiaryCreated').textContent = formatDate(beneficiary.created_at);

        openModal('adminBeneficiaryDetailsModal');
    } catch (error) {
        toast(error.message || 'Unable to load beneficiary details.', 'error');
    }
};

export const initBeneficiariesPage = () => {
    const search = debounce(() => {
        state.search = qs('#adminBeneficiarySearch').value.trim();
        state.page = 1;
        load();
    }, 300);

    const user = debounce(() => {
        state.userId = qs('#adminBeneficiaryUserId').value.trim();
        state.page = 1;
        load();
    }, 300);

    qs('#adminBeneficiarySearch')?.addEventListener('input', search);
    qs('#adminBeneficiaryUserId')?.addEventListener('input', user);
    qs('[data-admin-beneficiary-refresh]')?.addEventListener('click', load);

    qs('#adminBeneficiariesPrevPage')?.addEventListener('click', () => {
        if (state.page > 1) {
            state.page -= 1;
            load();
        }
    });

    qs('#adminBeneficiariesNextPage')?.addEventListener('click', () => {
        if (state.page < state.lastPage) {
            state.page += 1;
            load();
        }
    });

    qs('#adminBeneficiariesTableBody')?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-admin-beneficiary-action]');

        if (button) {
            openDetails(button.dataset.id);
        }
    });

    qsa('#adminBeneficiaryDetailsModal [data-close-modal]').forEach((button) => {
        button.addEventListener('click', () => closeModal('adminBeneficiaryDetailsModal'));
    });

    load();
};
