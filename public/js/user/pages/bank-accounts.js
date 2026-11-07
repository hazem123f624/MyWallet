import { apiDelete, apiGet, apiPatch, apiPost } from '../../dashboard/core/api.js?v=5';
import { escapeHtml, qs, qsa } from '../../dashboard/core/dom.js?v=5';
import { closeModal, confirmAction, openModal } from '../../dashboard/core/modal.js?v=5';
import { toast } from '../../dashboard/core/toast.js?v=5';

const state = {
    banks: [],
    accounts: [],
};

const errorMessage = (error) => {
    const first = error.payload?.errors ? Object.values(error.payload.errors)[0] : null;

    return Array.isArray(first) ? first[0] : (first || error.message || 'The action could not be completed.');
};

const renderAccounts = () => {
    const grid = qs('#bankAccountsGrid');
    const empty = qs('#bankAccountsEmpty');

    empty.hidden = state.accounts.length > 0;

    grid.innerHTML = state.accounts.map((account) => {
        const verified = account.status === 'verified';
        const actions = [];

        if (!verified) {
            actions.push(`<button class="dash-button dash-button-ghost dash-button-xs" type="button" data-bank-action="verify" data-id="${account.id}">Verify</button>`);
        }

        if (verified && !account.is_primary) {
            actions.push(`<button class="dash-button dash-button-ghost dash-button-xs" type="button" data-bank-action="primary" data-id="${account.id}">Set primary</button>`);
        }

        actions.push(`<button class="dash-button dash-button-ghost dash-button-xs dash-button-danger-text" type="button" data-bank-action="unlink" data-id="${account.id}">Unlink</button>`);

        return `
            <article class="user-bank-card">
                <div class="user-bank-head">
                    <span class="user-funding-icon"><svg viewBox="0 0 24 24" aria-hidden="true" class="dash-icon"><path d="M3 9l9-5 9 5M5 9v10M9 9v10M15 9v10M19 9v10M3 21h18" /></svg></span>
                    <span>
                        <strong>${escapeHtml(account.bank?.name || 'Bank account')}</strong>
                        <small>${escapeHtml(account.account_number || '')}</small>
                    </span>
                    ${account.is_primary ? '<span class="dash-badge dash-badge-success">Primary</span>' : ''}
                </div>

                <div class="user-bank-meta">
                    <span class="dash-badge ${verified ? 'dash-badge-success' : 'dash-badge-warning'}">${verified ? 'Verified' : 'Pending verification'}</span>
                    <span class="dash-table-subtext">${escapeHtml(account.account_holder_name || '')}</span>
                </div>

                <div class="user-bank-actions">${actions.join('')}</div>
            </article>
        `;
    }).join('');
};

const load = async () => {
    try {
        const [banksResponse, accountsResponse] = await Promise.all([
            apiGet('/mobile/banks'),
            apiGet('/mobile/bank-accounts'),
        ]);

        state.banks = Array.isArray(banksResponse.data) ? banksResponse.data : [];
        state.accounts = Array.isArray(accountsResponse.data) ? accountsResponse.data : [];

        qs('#linkBankId').innerHTML = state.banks.map((bank) => `
            <option value="${bank.id}">${escapeHtml(bank.name)}</option>
        `).join('');

        renderAccounts();
    } catch (error) {
        toast(error.message || 'Unable to load bank accounts.', 'error');
    }
};

const openLink = () => {
    qs('#linkBankForm').reset();
    qs('#linkBankError').hidden = true;
    openModal('linkBankModal');
};

const openVerify = (accountId, otp = null) => {
    qs('#verifyBankForm').reset();
    qs('#verifyBankAccountId').value = accountId;
    qs('#verifyBankError').hidden = true;
    const hint = qs('#verifyBankHint');

    if (otp) {
        hint.textContent = `Local verification code: ${otp}`;
        hint.hidden = false;
    } else {
        hint.hidden = true;
    }

    openModal('verifyBankModal');
};

const submitLink = async (event) => {
    event.preventDefault();
    const errorBox = qs('#linkBankError');
    const button = qs('#linkBankSubmit');
    errorBox.hidden = true;
    button.disabled = true;

    try {
        const response = await apiPost('/mobile/bank-accounts', {
            bank_id: Number(qs('#linkBankId').value),
            account_holder_name: qs('#linkHolderName').value.trim(),
            account_number: qs('#linkAccountNumber').value.trim(),
            iban: qs('#linkIban').value.trim() || null,
        });

        closeModal('linkBankModal');
        toast(response.message || 'Bank account linked.');
        await load();

        const accountId = response.data?.account?.id;
        const otp = response.data?.otp ?? null;

        if (accountId) {
            openVerify(accountId, otp);
        }
    } catch (error) {
        errorBox.textContent = errorMessage(error);
        errorBox.hidden = false;
    } finally {
        button.disabled = false;
    }
};

const submitVerify = async (event) => {
    event.preventDefault();
    const id = qs('#verifyBankAccountId').value;
    const errorBox = qs('#verifyBankError');
    const button = qs('#verifyBankSubmit');
    errorBox.hidden = true;
    button.disabled = true;

    try {
        const response = await apiPost(`/mobile/bank-accounts/${id}/verify`, {
            otp: qs('#verifyBankOtp').value.trim(),
        });

        closeModal('verifyBankModal');
        toast(response.message || 'Bank account verified.');
        await load();
    } catch (error) {
        errorBox.textContent = errorMessage(error);
        errorBox.hidden = false;
    } finally {
        button.disabled = false;
    }
};

const resendOtp = async () => {
    const id = qs('#verifyBankAccountId').value;
    const hint = qs('#verifyBankHint');

    try {
        const response = await apiPost(`/mobile/bank-accounts/${id}/resend-otp`);
        const otp = response.data?.otp ?? null;
        hint.textContent = otp ? `Local verification code: ${otp}` : 'A new verification code has been sent.';
        hint.hidden = false;
        toast(response.message || 'Verification code resent.');
    } catch (error) {
        toast(errorMessage(error), 'error');
    }
};

const runAction = async (button, account) => {
    if (button.dataset.bankAction === 'verify') {
        openVerify(account.id);

        return;
    }

    if (button.dataset.bankAction === 'primary') {
        try {
            const response = await apiPatch(`/mobile/bank-accounts/${account.id}/primary`);
            toast(response.message || 'Primary bank account updated.');
            await load();
        } catch (error) {
            toast(errorMessage(error), 'error');
        }

        return;
    }

    if (button.dataset.bankAction === 'unlink') {
        const confirmed = await confirmAction({
            title: 'Unlink bank account',
            message: `Unlink ${account.account_number}? It will no longer be available as a funding source.`,
            confirmLabel: 'Unlink',
        });

        if (!confirmed) {
            return;
        }

        try {
            const response = await apiDelete(`/mobile/bank-accounts/${account.id}`);
            toast(response.message || 'Bank account unlinked.');
            await load();
        } catch (error) {
            toast(errorMessage(error), 'error');
        }
    }
};

export const initBankAccountsPage = () => {
    qs('[data-bank-link]')?.addEventListener('click', openLink);
    qs('[data-banks-refresh]')?.addEventListener('click', load);
    qs('#linkBankForm')?.addEventListener('submit', submitLink);
    qs('#verifyBankForm')?.addEventListener('submit', submitVerify);
    qs('#resendBankOtp')?.addEventListener('click', resendOtp);

    qs('#bankAccountsGrid')?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-bank-action]');

        if (!button) {
            return;
        }

        const account = state.accounts.find((item) => String(item.id) === button.dataset.id);

        if (account) {
            runAction(button, account);
        }
    });

    qsa('#linkBankModal [data-close-modal], #verifyBankModal [data-close-modal]').forEach((button) => {
        button.addEventListener('click', () => closeModal(button.closest('.dash-modal')?.id ?? 'linkBankModal'));
    });

    load();
};
