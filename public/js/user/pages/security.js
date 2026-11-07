import { apiPost } from '../../dashboard/core/api.js?v=5';
import { qs, qsa } from '../../dashboard/core/dom.js?v=5';
import { toast } from '../../dashboard/core/toast.js?v=5';

const errorMessage = (error) => {
    const first = error.payload?.errors ? Object.values(error.payload.errors)[0] : null;

    return Array.isArray(first) ? first[0] : (first || error.message || 'The action could not be completed.');
};

export const initSecurityPage = () => {
    qsa('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.target);

            if (!input) {
                return;
            }

            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    });

    qs('#userPasswordForm')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const errorBox = qs('#userPasswordError');
        const button = qs('#userPasswordSubmit');
        errorBox.hidden = true;
        button.disabled = true;

        try {
            const response = await apiPost('/mobile/users/change-password', {
                old_password: qs('#userOldPassword').value,
                new_password: qs('#userNewPassword').value,
                new_password_confirmation: qs('#userNewPasswordConfirmation').value,
            });

            qs('#userPasswordForm').reset();
            toast(response.message || 'Password updated.');
        } catch (error) {
            errorBox.textContent = errorMessage(error);
            errorBox.hidden = false;
        } finally {
            button.disabled = false;
        }
    });

    qs('#setPinForm')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const errorBox = qs('#setPinError');
        const button = qs('#setPinSubmit');
        errorBox.hidden = true;
        button.disabled = true;

        try {
            const response = await apiPost('/mobile/users/transaction-pin', {
                transaction_pin: qs('#setPin').value,
                transaction_pin_confirmation: qs('#setPinConfirmation').value,
            });

            qs('#setPinForm').reset();
            toast(response.message || 'Transaction PIN set.');
        } catch (error) {
            errorBox.textContent = errorMessage(error);
            errorBox.hidden = false;
        } finally {
            button.disabled = false;
        }
    });

    qs('#changePinForm')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const errorBox = qs('#changePinError');
        const button = qs('#changePinSubmit');
        errorBox.hidden = true;
        button.disabled = true;

        try {
            const response = await apiPost('/mobile/users/transaction-pin/change', {
                current_transaction_pin: qs('#currentPin').value,
                transaction_pin: qs('#changePin').value,
                transaction_pin_confirmation: qs('#changePinConfirmation').value,
            });

            qs('#changePinForm').reset();
            toast(response.message || 'Transaction PIN changed.');
        } catch (error) {
            errorBox.textContent = errorMessage(error);
            errorBox.hidden = false;
        } finally {
            button.disabled = false;
        }
    });

    qs('#sendPinResetOtp')?.addEventListener('click', async () => {
        const button = qs('#sendPinResetOtp');
        const hint = qs('#pinResetHint');
        button.disabled = true;

        try {
            const response = await apiPost('/mobile/users/transaction-pin/forget');
            const otp = response.data?.otp;
            hint.textContent = otp ? `Local reset code: ${otp}` : 'A reset code has been sent to your phone.';
            hint.hidden = false;
            toast(response.message || 'Reset code sent.');
        } catch (error) {
            toast(errorMessage(error), 'error');
        } finally {
            button.disabled = false;
        }
    });

    qs('#resetPinForm')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const errorBox = qs('#resetPinError');
        const button = qs('#resetPinSubmit');
        errorBox.hidden = true;
        button.disabled = true;

        try {
            const response = await apiPost('/mobile/users/transaction-pin/reset', {
                otp: qs('#pinResetOtp').value.trim(),
                transaction_pin: qs('#pinResetNew').value,
                transaction_pin_confirmation: qs('#pinResetConfirmation').value,
            });

            qs('#resetPinForm').reset();
            qs('#pinResetHint').hidden = true;
            toast(response.message || 'Transaction PIN reset.');
        } catch (error) {
            errorBox.textContent = errorMessage(error);
            errorBox.hidden = false;
        } finally {
            button.disabled = false;
        }
    });
};
