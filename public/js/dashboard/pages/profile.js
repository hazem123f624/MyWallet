import { apiGet, apiPost, apiUpload } from '../core/api.js?v=4';
import { escapeHtml, initials, qs, qsa } from '../core/dom.js?v=4';
import { toast } from '../core/toast.js?v=4';

const messageFor = (error) => {
    const first = error.payload?.errors ? Object.values(error.payload.errors)[0] : null;

    return Array.isArray(first) ? first[0] : (first || error.message || 'The action could not be completed.');
};

const renderAvatar = (url, name) => {
    const preview = qs('#profileAvatarPreview');

    if (!preview) {
        return;
    }

    preview.innerHTML = url
        ? `<img src="${escapeHtml(url)}" alt="">`
        : `<span>${escapeHtml(initials(name || 'Administrator'))}</span>`;
};

export const initProfilePage = () => {
    const profileForm = qs('#profileForm');
    const passwordForm = qs('#passwordForm');
    const profileError = qs('#profileFormError');
    const passwordError = qs('#passwordFormError');
    const avatarInput = qs('#profileAvatar');
    const deleteAvatarFlag = qs('#profileDeleteAvatarFlag');

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

    const loadProfile = async () => {
        try {
            const response = await apiGet('/dashboard/profile');
            const admin = response.data ?? {};

            qs('#profileName').value = admin.name ?? '';
            qs('#profileEmail').value = admin.email ?? '';
            qs('#profilePhone').value = admin.phone ?? '';
            qs('#profileRoles').value = (admin.roles ?? [])
                .map((role) => role.name)
                .filter(Boolean)
                .join(', ');

            renderAvatar(admin.avatar, admin.name);
        } catch (error) {
            toast(error.message || 'Unable to load profile.', 'error');
        }
    };

    avatarInput?.addEventListener('change', () => {
        const file = avatarInput.files?.[0];

        if (!file) {
            return;
        }

        deleteAvatarFlag.value = '0';
        renderAvatar(URL.createObjectURL(file), qs('#profileName').value);
    });

    qs('#profileDeleteAvatar')?.addEventListener('click', () => {
        deleteAvatarFlag.value = '1';

        if (avatarInput) {
            avatarInput.value = '';
        }

        renderAvatar(null, qs('#profileName').value);
    });

    profileForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        profileError.hidden = true;
        const button = qs('#profileSubmit');
        button.disabled = true;

        try {
            const formData = new FormData();
            formData.append('name', qs('#profileName').value.trim());
            formData.append('phone', qs('#profilePhone').value.trim());
            formData.append('delete_avatar', deleteAvatarFlag.value);

            const file = avatarInput?.files?.[0];

            if (file) {
                formData.append('avatar', file);
            }

            const response = await apiUpload('/dashboard/profile', formData);
            const admin = response.data ?? {};

            qsa('.dash-user-meta strong').forEach((element) => {
                element.textContent = admin.name ?? qs('#profileName').value.trim();
            });

            renderAvatar(admin.avatar ?? null, admin.name ?? qs('#profileName').value.trim());
            deleteAvatarFlag.value = '0';

            if (avatarInput) {
                avatarInput.value = '';
            }

            toast(response.message || 'Profile updated.');
        } catch (error) {
            profileError.textContent = messageFor(error);
            profileError.hidden = false;
        } finally {
            button.disabled = false;
        }
    });

    passwordForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        passwordError.hidden = true;
        const button = qs('#passwordSubmit');
        button.disabled = true;

        try {
            const response = await apiPost('/dashboard/change-password', {
                old_password: qs('#oldPassword').value,
                new_password: qs('#newPassword').value,
                new_password_confirmation: qs('#newPasswordConfirmation').value,
            });

            passwordForm.reset();
            toast(response.message || 'Password updated.');
        } catch (error) {
            passwordError.textContent = messageFor(error);
            passwordError.hidden = false;
        } finally {
            button.disabled = false;
        }
    });

    loadProfile();
};
