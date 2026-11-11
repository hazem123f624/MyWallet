import { apiDelete, apiGet, apiPatch, apiPost } from '../../dashboard/core/api.js?v=5';
import { escapeHtml, qs, qsa } from '../../dashboard/core/dom.js?v=5';
import { closeModal, confirmAction, openModal } from '../../dashboard/core/modal.js?v=5';
import { toast } from '../../dashboard/core/toast.js?v=5';

const state = { beneficiaries: [] };

const errorMessage = (error) => {
    const first = error.payload?.errors ? Object.values(error.payload.errors)[0] : null;

    return Array.isArray(first) ? first[0] : (first || error.message || 'The action could not be completed.');
};

const render = () => {
    const grid = qs('#beneficiaryGrid');
    const empty = qs('#beneficiariesEmpty');

    empty.hidden = state.beneficiaries.length > 0;

    grid.innerHTML = state.beneficiaries.map((beneficiary) => `
        <article class="user-bank-card">
            <div class="user-bank-head">
                <span class="user-funding-icon"><svg viewBox="0 0 24 24" aria-hidden="true" class="dash-icon"><circle cx="12" cy="8" r="4" /><path d="M4 21a8 8 0 0 1 16 0" /></svg></span>
                <span>
                    <strong>${escapeHtml(beneficiary.name)}</strong>
                    <small>${escapeHtml(beneficiary.bank_name)}</small>
                </span>
            </div>

            <div class="user-bank-meta">
                <span class="dash-table-subtext">${escapeHtml(beneficiary.account_number)}</span>
                <span class="dash-badge ${beneficiary.is_active ? 'dash-badge-success' : 'dash-badge-warning'}">${beneficiary.is_active ? 'Active' : 'Inactive'}</span>
            </div>

            <div class="user-bank-actions">
                <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-beneficiary-action="edit" data-id="${beneficiary.id}">Edit</button>
                <button class="dash-button dash-button-ghost dash-button-xs dash-button-danger-text" type="button" data-beneficiary-action="delete" data-id="${beneficiary.id}">Delete</button>
            </div>
        </article>
    `).join('');
};

const load = async () => {
    try {
        const response = await apiGet('/mobile/beneficiaries');
        state.beneficiaries = Array.isArray(response.data) ? response.data : [];
        render();
    } catch (error) {
        toast(error.message || 'Unable to load beneficiaries.', 'error');
    }
};

const openForm = (beneficiary = null) => {
    qs('#beneficiaryForm').reset();
    qs('#beneficiaryId').value = beneficiary?.id ?? '';
    qs('#beneficiaryName').value = beneficiary?.name ?? '';
    qs('#beneficiaryBankName').value = beneficiary?.bank_name ?? '';
    qs('#beneficiaryBankCode').value = beneficiary?.bank_code ?? '';
    qs('#beneficiaryAccountNumber').value = beneficiary?.account_number ?? '';
    qs('#beneficiaryIban').value = beneficiary?.iban ?? '';
    qs('#beneficiaryError').hidden = true;
    qs('#beneficiaryFormModal').querySelector('h2').textContent = beneficiary ? 'Edit beneficiary' : 'Add beneficiary';
    qs('#beneficiarySubmit').textContent = beneficiary ? 'Save changes' : 'Save beneficiary';
    openModal('beneficiaryFormModal');
};

const submit = async (event) => {
    event.preventDefault();
    const id = qs('#beneficiaryId').value;
    const errorBox = qs('#beneficiaryError');
    const button = qs('#beneficiarySubmit');
    errorBox.hidden = true;
    button.disabled = true;

    const payload = {
        name: qs('#beneficiaryName').value.trim(),
        bank_name: qs('#beneficiaryBankName').value.trim(),
        bank_code: qs('#beneficiaryBankCode').value.trim() || null,
        account_number: qs('#beneficiaryAccountNumber').value.trim(),
        iban: qs('#beneficiaryIban').value.trim() || null,
    };

    try {
        const response = id
            ? await apiPatch(`/mobile/beneficiaries/${id}`, payload)
            : await apiPost('/mobile/beneficiaries', payload);

        closeModal('beneficiaryFormModal');
        toast(response.message || 'Beneficiary saved.');
        await load();
    } catch (error) {
        errorBox.textContent = errorMessage(error);
        errorBox.hidden = false;
    } finally {
        button.disabled = false;
    }
};

const remove = async (beneficiary) => {
    const confirmed = await confirmAction({
        title: 'Delete beneficiary',
        message: `Delete ${beneficiary.name}?`,
        confirmLabel: 'Delete',
    });

    if (!confirmed) {
        return;
    }

    try {
        const response = await apiDelete(`/mobile/beneficiaries/${beneficiary.id}`);
        toast(response.message || 'Beneficiary deleted.');
        await load();
    } catch (error) {
        toast(errorMessage(error), 'error');
    }
};

export const initBeneficiariesPage = () => {
    qs('[data-beneficiary-create]')?.addEventListener('click', () => openForm());
    qs('[data-beneficiary-refresh]')?.addEventListener('click', load);
    qs('#beneficiaryForm')?.addEventListener('submit', submit);

    qs('#beneficiaryGrid')?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-beneficiary-action]');

        if (!button) {
            return;
        }

        const beneficiary = state.beneficiaries.find((item) => String(item.id) === button.dataset.id);

        if (!beneficiary) {
            return;
        }

        if (button.dataset.beneficiaryAction === 'edit') {
            openForm(beneficiary);
        } else if (button.dataset.beneficiaryAction === 'delete') {
            remove(beneficiary);
        }
    });

    qsa('#beneficiaryFormModal [data-close-modal]').forEach((button) => {
        button.addEventListener('click', () => closeModal('beneficiaryFormModal'));
    });

    load();
};
