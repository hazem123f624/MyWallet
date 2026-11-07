import { apiPost } from '../../dashboard/core/api.js?v=5';
import { qs } from '../../dashboard/core/dom.js?v=5';

const errorMessage = (error) => {
    const first = error.payload?.errors ? Object.values(error.payload.errors)[0] : null;

    return Array.isArray(first) ? first[0] : (first || error.message || 'The request could not be completed.');
};

const initTheme = () => {
    const root = document.documentElement;
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

export const initLoginPage = () => {
    const loginPanel = qs('#loginPanel');
    const recoveryPanel = qs('#recoveryPanel');
    const requestForm = qs('#recoveryRequestForm');
    const verifyForm = qs('#recoveryVerifyForm');
    const resetForm = qs('#recoveryResetForm');
    const errorBox = qs('#recoveryError');
    const messageBox = qs('#recoveryMessage');
    const resendButton = qs('#recoveryResend');
    const registerPanel = qs('#registerPanel');
    const registerForm = qs('#registerForm');
    const registerVerifyForm = qs('#registerVerifyForm');
    const registerError = qs('#registerError');
    const registerMessage = qs('#registerMessage');
    const registerResend = qs('#registerResend');
    const loginMessage = qs('#loginMessage');

    if (!loginPanel || !recoveryPanel || !requestForm || !verifyForm || !resetForm || !registerPanel || !registerForm || !registerVerifyForm) {
        return;
    }

    let resetToken = '';
    let resendTimer = null;
    let registerResendTimer = null;
    let registration = { country_code: '', phone: '' };

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

    const credentials = () => ({
        country_code: qs('#recoveryCountry').value,
        phone: qs('#recoveryPhone').value.trim(),
    });

    const startCooldown = (seconds = 60) => {
        window.clearInterval(resendTimer);
        let remaining = seconds;
        resendButton.disabled = true;
        resendButton.textContent = `Resend in ${remaining}s`;

        resendTimer = window.setInterval(() => {
            remaining -= 1;

            if (remaining <= 0) {
                window.clearInterval(resendTimer);
                resendButton.disabled = false;
                resendButton.textContent = 'Resend code';

                return;
            }

            resendButton.textContent = `Resend in ${remaining}s`;
        }, 1000);
    };

    const resetSteps = () => {
        requestForm.hidden = false;
        verifyForm.hidden = true;
        resetForm.hidden = true;
        errorBox.hidden = true;
        messageBox.hidden = true;
        resetToken = '';
    };

    const openRecovery = () => {
        loginPanel.hidden = true;
        recoveryPanel.hidden = false;
        resetSteps();
        qs('#recoveryCountry').value = qs('#country_code').value;
        qs('#recoveryPhone').value = qs('#phone').value.trim();
        qs('#recoveryCountry').focus();
    };

    const closeRecovery = () => {
        recoveryPanel.hidden = true;
        loginPanel.hidden = false;
        resetSteps();
    };

    const openRegister = () => {
        loginPanel.hidden = true;
        recoveryPanel.hidden = true;
        registerPanel.hidden = false;
        registerForm.hidden = false;
        registerVerifyForm.hidden = true;
        registerError.hidden = true;
        registerMessage.hidden = true;
        qs('#registerName').focus();
    };

    const closeRegister = () => {
        registerPanel.hidden = true;
        loginPanel.hidden = false;
        registerForm.hidden = false;
        registerVerifyForm.hidden = true;
        registerError.hidden = true;
        registerMessage.hidden = true;
    };

    const startRegisterCooldown = (seconds = 60) => {
        window.clearInterval(registerResendTimer);
        let remaining = seconds;
        registerResend.disabled = true;
        registerResend.textContent = `Resend in ${remaining}s`;

        registerResendTimer = window.setInterval(() => {
            remaining -= 1;

            if (remaining <= 0) {
                window.clearInterval(registerResendTimer);
                registerResend.disabled = false;
                registerResend.textContent = 'Resend code';

                return;
            }

            registerResend.textContent = `Resend in ${remaining}s`;
        }, 1000);
    };

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

    const password = qs('#password');
    const capsWarning = qs('[data-caps-warning]');

    if (password && capsWarning) {
        const update = (event) => {
            if (typeof event.getModifierState === 'function') {
                capsWarning.hidden = !event.getModifierState('CapsLock');
            }
        };

        password.addEventListener('keydown', update);
        password.addEventListener('keyup', update);
        password.addEventListener('blur', () => {
            capsWarning.hidden = true;
        });
    }

    qs('#loginRecoveryLink')?.addEventListener('click', openRecovery);
    qs('#recoveryBack')?.addEventListener('click', closeRecovery);
    qs('#loginRegisterLink')?.addEventListener('click', openRegister);
    qs('#registerBack')?.addEventListener('click', closeRegister);

    requestForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorBox.hidden = true;
        qs('#recoveryRequestSubmit').disabled = true;

        try {
            const response = await apiPost('/mobile/users/forget-password', credentials());
            resetToken = response.data?.token ?? response.data?.change_password_token ?? '';

            if (!resetToken) {
                throw new Error('The reset token was not returned.');
            }

            requestForm.hidden = true;
            verifyForm.hidden = false;
            qs('#recoveryOtp').value = '';
            qs('#recoveryOtp').focus();
            showMessage(response.data?.otp ? `A verification code was sent. Local code: ${response.data.otp}` : 'A verification code has been sent.');
            startCooldown();
        } catch (error) {
            showError(errorMessage(error));
        } finally {
            qs('#recoveryRequestSubmit').disabled = false;
        }
    });

    resendButton?.addEventListener('click', async () => {
        resendButton.disabled = true;

        try {
            const response = await apiPost('/mobile/users/resend-otp', {
                ...credentials(),
                type: 'forgot_password',
            });

            showMessage(response.data?.otp ? `A new code was sent. Local code: ${response.data.otp}` : 'A new verification code has been sent.');
            startCooldown();
        } catch (error) {
            resendButton.disabled = false;
            showError(errorMessage(error));
        }
    });

    verifyForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorBox.hidden = true;
        qs('#recoveryVerifySubmit').disabled = true;

        try {
            const response = await apiPost('/mobile/users/verify-forget-password', {
                ...credentials(),
                otp: qs('#recoveryOtp').value.trim(),
                change_password_token: resetToken,
            });

            resetToken = response.data?.change_password_token ?? resetToken;
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

    registerForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        registerError.hidden = true;
        qs('#registerSubmit').disabled = true;

        try {
            const payload = {
                name: qs('#registerName').value.trim(),
                country_code: qs('#registerCountry').value,
                phone: qs('#registerPhone').value.trim(),
                email: qs('#registerEmail').value.trim() || null,
                password: qs('#registerPassword').value,
                password_confirmation: qs('#registerPasswordConfirmation').value,
            };

            const response = await apiPost('/mobile/users/register', payload);
            registration = { country_code: payload.country_code, phone: payload.phone };

            registerForm.hidden = true;
            registerVerifyForm.hidden = false;
            qs('#registerOtp').value = '';
            qs('#registerOtp').focus();
            registerMessage.textContent = response.data?.otp
                ? `Verification code sent. Local code: ${response.data.otp}`
                : 'Verification code sent to your phone number.';
            registerMessage.hidden = false;
            startRegisterCooldown();
        } catch (error) {
            registerError.textContent = errorMessage(error);
            registerError.hidden = false;
        } finally {
            qs('#registerSubmit').disabled = false;
        }
    });

    registerResend?.addEventListener('click', async () => {
        registerResend.disabled = true;

        try {
            const response = await apiPost('/mobile/users/resend-otp', registration);
            registerMessage.textContent = response.data?.otp
                ? `A new code was sent. Local code: ${response.data.otp}`
                : 'A new verification code has been sent.';
            registerMessage.hidden = false;
            startRegisterCooldown();
        } catch (error) {
            registerResend.disabled = false;
            registerError.textContent = errorMessage(error);
            registerError.hidden = false;
        }
    });

    registerVerifyForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        registerError.hidden = true;
        qs('#registerVerifySubmit').disabled = true;

        try {
            const response = await apiPost('/mobile/users/verify', {
                country_code: registration.country_code,
                phone: registration.phone,
                otp: qs('#registerOtp').value.trim(),
            });

            registerPanel.hidden = true;
            loginPanel.hidden = false;
            registerForm.hidden = false;
            registerVerifyForm.hidden = true;
            qs('#country_code').value = registration.country_code;
            qs('#phone').value = registration.phone;
            loginMessage.textContent = response.message || 'Account created successfully. Sign in to continue.';
            loginMessage.hidden = false;
        } catch (error) {
            registerError.textContent = errorMessage(error);
            registerError.hidden = false;
        } finally {
            qs('#registerVerifySubmit').disabled = false;
        }
    });

    resetForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorBox.hidden = true;
        qs('#recoveryResetSubmit').disabled = true;

        try {
            const response = await apiPost('/mobile/users/reset-password', {
                ...credentials(),
                password: qs('#recoveryPassword').value,
                password_confirmation: qs('#recoveryPasswordConfirmation').value,
                change_password_token: resetToken,
            });

            showMessage(response.message || 'Password reset successfully. You can sign in now.');
            resetForm.hidden = true;
            qs('#phone').value = qs('#recoveryPhone').value.trim();
            qs('#country_code').value = qs('#recoveryCountry').value;

            window.setTimeout(() => closeRecovery(), 1600);
        } catch (error) {
            showError(errorMessage(error));
        } finally {
            qs('#recoveryResetSubmit').disabled = false;
        }
    });
};

initTheme();
initLoginPage();
