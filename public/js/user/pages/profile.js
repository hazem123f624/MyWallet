import { apiDelete, apiGet, apiPost, apiUpload } from '../../dashboard/core/api.js?v=5';
import { escapeHtml, initials, qs, qsa } from '../../dashboard/core/dom.js?v=5';
import { closeModal, confirmAction, openModal } from '../../dashboard/core/modal.js?v=5';
import { toast } from '../../dashboard/core/toast.js?v=5';

const state = {
    profile: null,
    pendingCountryCode: '',
    pendingPhone: '',
};

const errorMessage = (error) => {
    const first = error.payload?.errors ? Object.values(error.payload.errors)[0] : null;

    return Array.isArray(first) ? first[0] : (first || error.message || 'The action could not be completed.');
};

const renderAvatar = (url, name) => {
    const preview = qs('#userAvatarPreview');

    if (!preview) {
        return;
    }

    preview.innerHTML = url
        ? `<img src="${escapeHtml(url)}" alt="">`
        : `<span>${escapeHtml(initials(name || 'User'))}</span>`;
};

const fillProfile = (profile) => {
    state.profile = profile;

    qs('#userName').value = profile.name ?? '';
    qs('#userEmail').value = profile.email ?? '';
    qs('#userPostalCode').value = profile.postal_code ?? '';
    qs('#userAddress').value = profile.main_address ?? '';
    qs('#userPhoneReadonly').textContent = profile.phone ?? '—';
    qs('#userPhoneStatus').textContent = profile.phone ? 'Verified' : 'Not verified';
    qs('#userPhoneStatus').className = `dash-badge ${profile.phone ? 'dash-badge-success' : 'dash-badge-warning'}`;

    renderAvatar(profile.avatar, profile.name);
};

const loadProfile = async () => {
    try {
        const response = await apiGet('/mobile/users/profile');
        fillProfile(response.data ?? {});
    } catch (error) {
        toast(error.message || 'Unable to load your profile.', 'error');
    }
};

const submitProfile = async (event) => {
    event.preventDefault();
    const errorBox = qs('#userProfileError');
    const button = qs('#userProfileSubmit');
    errorBox.hidden = true;
    button.disabled = true;

    try {
        const formData = new FormData();
        formData.append('name', qs('#userName').value.trim());
        formData.append('email', qs('#userEmail').value.trim());
        formData.append('postal_code', qs('#userPostalCode').value.trim());
        formData.append('main_address', qs('#userAddress').value.trim());
        formData.append('delete_avatar', qs('#userDeleteAvatarFlag').value);

        const avatar = qs('#userAvatar')?.files?.[0];

        if (avatar) {
            formData.append('avatar', avatar);
        }

        const response = await apiUpload('/mobile/users/profile', formData);
        fillProfile(response.data ?? state.profile);
        qs('#userDeleteAvatarFlag').value = '0';
        qs('#userAvatar').value = '';
        toast(response.message || 'Profile updated.');
    } catch (error) {
        errorBox.textContent = errorMessage(error);
        errorBox.hidden = false;
    } finally {
        button.disabled = false;
    }
};

const submitPhone = async (event) => {
    event.preventDefault();
    const errorBox = qs('#userPhoneError');
    const button = qs('#userPhoneSubmit');
    errorBox.hidden = true;
    button.disabled = true;

    try {
        const countryCode = qs('#userCountryCode').value;
        const phone = qs('#userNewPhone').value.trim();
        const response = await apiPost('/mobile/users/profile', {
            country_code: countryCode,
            phone,
        });

        state.pendingCountryCode = countryCode;
        state.pendingPhone = phone;

        const hint = qs('#verifyPhoneHint');
        const otp = response.data?.otp_code;

        hint.textContent = otp ? `Local verification code: ${otp}` : 'A verification code has been sent to your new phone number.';
        hint.hidden = false;
        qs('#verifyPhoneForm').reset();
        qs('#verifyPhoneError').hidden = true;
        openModal('verifyPhoneModal');
    } catch (error) {
        errorBox.textContent = errorMessage(error);
        errorBox.hidden = false;
    } finally {
        button.disabled = false;
    }
};

const submitPhoneVerification = async (event) => {
    event.preventDefault();
    const errorBox = qs('#verifyPhoneError');
    const button = qs('#verifyPhoneSubmit');
    errorBox.hidden = true;
    button.disabled = true;

    try {
        const response = await apiPost('/mobile/users/verify', {
            country_code: state.pendingCountryCode,
            phone: state.pendingPhone,
            otp: qs('#verifyPhoneOtp').value.trim(),
        });

        closeModal('verifyPhoneModal');
        toast(response.message || 'Phone number updated.');
        qs('#userNewPhone').value = '';
        await loadProfile();
    } catch (error) {
        errorBox.textContent = errorMessage(error);
        errorBox.hidden = false;
    } finally {
        button.disabled = false;
    }
};

const deleteAccount = async () => {
    const confirmed = await confirmAction({
        title: 'Delete account',
        message: 'Delete your account? You will be signed out and your access will be revoked.',
        confirmLabel: 'Delete account',
        tone: 'danger',
    });

    if (!confirmed) {
        return;
    }

    try {
        const response = await apiDelete('/mobile/users/profile');
        toast(response.message || 'Account deleted.');
        window.setTimeout(() => {
            window.location.href = '/user/login?expired=1';
        }, 600);
    } catch (error) {
        toast(errorMessage(error), 'error');
    }
};

export const initProfilePage = () => {
    qs('#userProfileForm')?.addEventListener('submit', submitProfile);
    qs('#userPhoneForm')?.addEventListener('submit', submitPhone);
    qs('#verifyPhoneForm')?.addEventListener('submit', submitPhoneVerification);
    qs('#userDeleteAccount')?.addEventListener('click', deleteAccount);

    qs('#userAvatar')?.addEventListener('change', () => {
        const file = qs('#userAvatar').files?.[0];

        if (file) {
            qs('#userDeleteAvatarFlag').value = '0';
            renderAvatar(URL.createObjectURL(file), qs('#userName').value);
        }
    });

    qs('#userDeleteAvatar')?.addEventListener('click', () => {
        qs('#userDeleteAvatarFlag').value = '1';
        qs('#userAvatar').value = '';
        renderAvatar(null, qs('#userName').value);
    });

    qsa('#verifyPhoneModal [data-close-modal]').forEach((button) => {
        button.addEventListener('click', () => closeModal('verifyPhoneModal'));
    });

    loadProfile();
};
