import { apiPost } from '../core/api.js?v=4';
import { qs } from '../core/dom.js?v=4';

const errorMessage = (error) => {
    const first = error.payload?.errors ? Object.values(error.payload.errors)[0] : null;

    return Array.isArray(first) ? first[0] : (first || error.message || 'The request could not be completed.');
};

export const initLoginPage = () => {
    const loginPanel = qs('#loginPanel');
    const recoveryPanel = qs('#recoveryPanel');
    const requestForm = qs('#recoveryRequestForm');
    const verifyForm = qs('#recoveryVerifyForm');
    const resetForm = qs('#recoveryResetForm');
    const errorBox = qs('#recoveryError');
    const messageBox = qs('#recoveryMessage');
    const tokenInput = qs('#recoveryToken');
    const root = document.documentElement;
    let resendTimer = null;

    if (!loginPanel || !recoveryPanel || !requestForm || !verifyForm || !resetForm) {
        return;
    }

    initTheme();

    const initTheme = () => {
        const apply = (theme) => {
            root.dataset.theme = theme;
            localStorage.setItem('dashboard-theme', theme);
            document.querySelectorAll('[data-theme-icon="moon"]').forEach((element) => {
                element.hidden = theme === 'dark';
            });
            document.querySelectorAll('[data-theme-icon="sun"]').forEach((element) => {
                element.hidden = theme !== 'dark';
            });
        };

        apply(localStorage.getItem('dashboard-theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));

        qs('[data-login-theme-toggle]')?.addEventListener('click', () => {
            apply(root.dataset.theme === 'dark' ? 'light' : 'dark');
        });
    };

    const initPasswordToggles = () => {
        document.querySelectorAll('[data-password-toggle]').forEach((button) => {
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
    };

    const initCapsWarning = () => {
        const password = qs('#password');
        const warning = qs('[data-caps-warning]');

        if (!password || !warning) {
            return;
        }

        const update = (event) => {
            if (typeof event.getModifierState !== 'function') {
                return;
            }

            warning.hidden = !event.getModifierState('CapsLock');
        };

        password.addEventListener('keydown', update);
        password.addEventListener('keyup', update);
        password.addEventListener('blur', () => {
            warning.hidden = true;
        });
    };

    const startResendCooldown = (seconds = 60) => {
        const button = qs('#recoveryResend');

        if (!button) {
            return;
        }

        window.clearInterval(resendTimer);
        let remaining = seconds;
        button.disabled = true;
        button.textContent = `Resend in ${remaining}s`;

        resendTimer = window.setInterval(() => {
            remaining -= 1;

            if (remaining <= 0) {
                window.clearInterval(resendTimer);
                button.disabled = false;
                button.textContent = 'Resend code';

                return;
            }

            button.textContent = `Resend in ${remaining}s`;
        }, 1000);
    };

    const showError = (message) => {
        errorBox.textContent = message;
        errorBox.hidden = false;
        messageBox.hidden = true;
    };

    const showMessage = (message) => {
        messageBox.textContent = message;
        messageBox.hidden = false;
        errorBox.hidden = true;
    };

    const resetSteps = () => {
        requestForm.hidden = false;
        verifyForm.hidden = true;
        resetForm.hidden = true;
        errorBox.hidden = true;
        messageBox.hidden = true;
        tokenInput.value = '';
    };

    const openRecovery = () => {
        loginPanel.hidden = true;
        recoveryPanel.hidden = false;
        resetSteps();
        qs('#recoveryEmail').value = qs('#email')?.value.trim() ?? '';
        qs('#recoveryRequestSubmit').disabled = false;
        qs('#recoveryEmail').focus();
    };

    const closeRecovery = () => {
        recoveryPanel.hidden = true;
        loginPanel.hidden = false;
        resetSteps();
    };

    const requestCode = async (email) => {
        const response = await apiPost('/forget-password', { email });
        const token = response.data?.change_password_token;

        if (!token) {
            throw new Error('The reset token was not returned.');
        }

        tokenInput.value = token;
        qs('#recoveryVerifyEmail').value = email;
        requestForm.hidden = true;
        verifyForm.hidden = false;
        qs('#recoveryOtp').value = '';
        qs('#recoveryOtp').focus();
        showMessage('A verification code has been sent to your email address.');
        startResendCooldown();
    };

    initPasswordToggles();
    initCapsWarning();

    qs('#loginRecoveryLink')?.addEventListener('click', openRecovery);
    qs('#recoveryBack')?.addEventListener('click', closeRecovery);

    requestForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorBox.hidden = true;
        qs('#recoveryRequestSubmit').disabled = true;

        try {
            await requestCode(qs('#recoveryEmail').value.trim());
        } catch (error) {
            showError(errorMessage(error));
        } finally {
            qs('#recoveryRequestSubmit').disabled = false;
        }
    });

    qs('#recoveryResend')?.addEventListener('click', async () => {
        const email = qs('#recoveryVerifyEmail').value.trim();
        const button = qs('#recoveryResend');

        button.disabled = true;

        try {
            const response = await apiPost('/resend-forgot-password-otp', { email });
            const token = response.data?.change_password_token;

            if (token) {
                tokenInput.value = token;
            }

            showMessage('A new verification code has been sent.');
        } catch (error) {
            showError(errorMessage(error));
        } finally {
            button.disabled = false;
        }
    });

    verifyForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorBox.hidden = true;
        qs('#recoveryVerifySubmit').disabled = true;

        try {
            const response = await apiPost('/verify-forget-password-otp', {
                email: qs('#recoveryVerifyEmail').value.trim(),
                otp: qs('#recoveryOtp').value.trim(),
                change_password_token: tokenInput.value,
            });

            if (response.data?.change_password_token) {
                tokenInput.value = response.data.change_password_token;
            }

            verifyForm.hidden = true;
            resetForm.hidden = false;
            resetForm.reset();
            qs('#recoveryPassword').focus();
            showMessage('Code verified. Choose a new password.');
        } catch (error) {
            showError(errorMessage(error));
        } finally {
            qs('#recoveryVerifySubmit').disabled = false;
        }
    });

    resetForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorBox.hidden = true;
        qs('#recoveryResetSubmit').disabled = true;

        try {
            const response = await apiPost('/reset-password', {
                email: qs('#recoveryVerifyEmail').value.trim(),
                password: qs('#recoveryPassword').value,
                new_password_confirmation: qs('#recoveryPasswordConfirmation').value,
                change_password_token: tokenInput.value,
            });

            showMessage(response.message || 'Password reset successfully. You can sign in now.');
            resetForm.hidden = true;
            qs('#email').value = qs('#recoveryVerifyEmail').value.trim();

            window.setTimeout(() => {
                closeRecovery();
            }, 1600);
        } catch (error) {
            showError(errorMessage(error));
        } finally {
            qs('#recoveryResetSubmit').disabled = false;
        }
    });
};

initLoginPage();
