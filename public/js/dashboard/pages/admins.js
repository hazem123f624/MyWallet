import { apiDelete, apiGet, apiPatch, apiPost, apiPut, apiUpload } from '../core/api.js?v=4';
import { debounce, escapeHtml, formatDate, formatDateTime, initials, qs, qsa } from '../core/dom.js?v=4';
import { closeModal, confirmAction, openModal } from '../core/modal.js?v=4';
import { toast } from '../core/toast.js?v=4';

const state = {
    mode: 'active',
    page: 1,
    lastPage: 1,
    perPage: 10,
    rows: [],
    search: '',
    status: '',
    role: '',
    sortBy: 'created_at',
    sortDir: 'desc',
    roles: [],
    detailId: null,
};

const errorMessage = (error) => {
    const first = error.payload?.errors ? Object.values(error.payload.errors)[0] : null;

    return Array.isArray(first) ? first[0] : (first || error.message || 'The action could not be completed.');
};

export const initAdminsPage = () => {
    const tableBody = qs('#adminsTableBody');
    const emptyState = qs('#adminsEmpty');
    const loadingState = qs('#adminsLoading');
    const pageInfo = qs('#adminsPageInfo');
    const prevButton = qs('#adminsPrevPage');
    const nextButton = qs('#adminsNextPage');
    const searchInput = qs('#adminSearch');
    const statusFilter = qs('#adminStatusFilter');
    const roleFilter = qs('#adminRoleFilter');
    const pageSizeSelect = qs('#adminPageSize');
    const sortSelect = qs('#adminSort');
    const form = qs('#adminForm');
    const formModal = qs('#adminFormModal');
    const formError = qs('#adminFormError');
    const submitButton = qs('#adminSubmit');
    const rolesContainer = qs('#adminRolesOptions');
    const detailRoles = qs('#adminDetailRoles');
    const detailError = qs('#adminDetailError');
    const detailActivity = qs('#adminDetailActivity');

    if (!tableBody || !emptyState || !loadingState || !form) {
        return;
    }

    const setLoading = (loading) => {
        loadingState.hidden = !loading;
        tableBody.setAttribute('aria-busy', String(loading));
    };

    const updateEmptyState = () => {
        emptyState.hidden = state.rows.length > 0;

        const title = emptyState.querySelector('h3');
        const message = emptyState.querySelector('p');

        if (state.search !== '' || state.status !== '' || state.role !== '') {
            title.textContent = 'No matching administrators';
            message.textContent = 'Try another search, role, or status filter.';

            return;
        }

        if (state.mode === 'trash') {
            title.textContent = 'Recycle bin is empty';
            message.textContent = 'Deleted administrators will appear here.';

            return;
        }

        title.textContent = 'No administrators found';
        message.textContent = 'Create the first administrator to get started.';
    };

    const renderRoles = (roles) => {
        return roles
            .map((adminRole) => adminRole.name || adminRole.role?.name || 'Role')
            .filter(Boolean)
            .join(', ') || 'No roles';
    };

    const renderRoleChips = (admin) => {
        if (!detailRoles) {
            return;
        }

        const roles = admin.roles ?? [];

        detailRoles.innerHTML = roles.length
            ? roles.map((adminRole) => {
                const role = adminRole.role ?? adminRole;
                const id = role.id ?? adminRole.id;
                const name = role.name ?? adminRole.name ?? `Role #${id}`;

                return `
                    <span class="dash-chip">
                        ${escapeHtml(name)}
                        <button type="button" class="dash-chip-remove" data-remove-role="${id}" aria-label="Remove ${escapeHtml(name)}">×</button>
                    </span>
                `;
            }).join('')
            : '<span class="dash-muted-text">No roles assigned.</span>';
    };

    const renderRows = () => {
        tableBody.innerHTML = state.rows.map((admin) => {
            const actions = state.mode === 'trash'
                ? `
                    <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-admin-action="restore" data-id="${admin.id}">Restore</button>
                    <button class="dash-button dash-button-danger dash-button-xs" type="button" data-admin-action="force-delete" data-id="${admin.id}">Delete forever</button>
                `
                : `
                    <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-admin-action="view" data-id="${admin.id}">View</button>
                    <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-admin-action="edit" data-id="${admin.id}">Edit</button>
                    <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-admin-action="toggle" data-id="${admin.id}">${admin.is_active ? 'Suspend' : 'Activate'}</button>
                    <button class="dash-button dash-button-ghost dash-button-xs dash-button-danger-text" type="button" data-admin-action="delete" data-id="${admin.id}">Delete</button>
                `;

            return `
                <tr>
                    <td>
                        <div class="dash-table-user">
                            ${admin.avatar
                                ? `<img class="dash-table-avatar dash-table-avatar-image" src="${escapeHtml(admin.avatar)}" alt="">`
                                : `<span class="dash-table-avatar">${escapeHtml(initials(admin.name))}</span>`}
                            <span>
                                <span class="dash-table-name">${escapeHtml(admin.name || 'Unnamed admin')}</span>
                                <span class="dash-table-subtext">${escapeHtml(admin.job_title || 'Administrator')}</span>
                            </span>
                        </div>
                    </td>
                    <td>
                        <span class="dash-table-contact">${escapeHtml(admin.email || 'No email')}</span>
                        <span class="dash-table-subtext">${escapeHtml(admin.phone || 'No phone')}</span>
                    </td>
                    <td>${escapeHtml(renderRoles(admin.roles || []))}</td>
                    <td>
                        ${state.mode === 'trash'
                            ? '<span class="dash-badge dash-badge-danger">Deleted</span>'
                            : admin.is_active
                                ? '<span class="dash-badge dash-badge-success">Active</span>'
                                : '<span class="dash-badge dash-badge-warning">Suspended</span>'}
                    </td>
                    <td>${escapeHtml(formatDate(admin.created_at))}</td>
                    <td class="dash-table-actions">
                        <div class="dash-row-actions">${actions}</div>
                    </td>
                </tr>
            `;
        }).join('');

        updateEmptyState();
    };

    const updatePagination = (pagination = {}) => {
        const currentPage = Number(pagination.current_page ?? 1);
        const lastPage = Math.max(1, Number(pagination.last_page ?? 1));
        const total = Number(pagination.total ?? 0);

        state.page = currentPage;
        state.lastPage = lastPage;

        if (pageInfo) {
            pageInfo.textContent = `Page ${currentPage} of ${lastPage} · ${total} admin${total === 1 ? '' : 's'}`;
        }

        if (prevButton) {
            prevButton.disabled = currentPage <= 1;
        }

        if (nextButton) {
            nextButton.disabled = currentPage >= lastPage;
        }
    };

    const loadAdmins = async () => {
        setLoading(true);

        try {
            const path = state.mode === 'trash' ? '/dashboard/admins/trash' : '/dashboard/admins';
            const response = await apiGet(path, {
                page: state.page,
                per_page: state.perPage,
                search: state.search,
                is_active: state.mode === 'trash' ? '' : state.status,
                role: state.role,
                sort_by: state.sortBy,
                sort_dir: state.sortDir,
            });

            state.rows = Array.isArray(response.data) ? response.data : [];

            renderRows();
            updatePagination(response.pagination);
        } catch (error) {
            state.rows = [];
            renderRows();
            toast(error.message || 'Unable to load administrators.', 'error');
        } finally {
            setLoading(false);
        }
    };

    const loadRoles = async () => {
        try {
            const response = await apiGet('/dashboard/roles', { per_page: 100 });
            state.roles = Array.isArray(response.data) ? response.data : [];

            if (rolesContainer) {
                rolesContainer.innerHTML = state.roles.map((role) => `
                    <label class="dash-checkbox">
                        <input type="checkbox" name="roles[]" value="${role.id}">
                        <span>${escapeHtml(role.name || `Role #${role.id}`)}</span>
                    </label>
                `).join('');
            }

            if (roleFilter) {
                const current = roleFilter.value;
                roleFilter.innerHTML = '<option value="">All roles</option>' + state.roles.map((role) => `
                    <option value="${role.id}">${escapeHtml(role.name || `Role #${role.id}`)}</option>
                `).join('');
                roleFilter.value = current;
            }
        } catch (error) {
            toast(error.message || 'Unable to load roles.', 'error');
        }
    };

    const resetForm = (admin = null) => {
        form.reset();
        qs('#adminId').value = admin?.id ?? '';
        qs('#adminName').value = admin?.name ?? '';
        qs('#adminEmail').value = admin?.email ?? '';
        qs('#adminPhone').value = admin?.phone ?? '';
        qs('#adminJobTitle').value = admin?.job_title ?? '';
        qs('#adminIsActive').checked = admin ? Boolean(admin.is_active) : true;
        qs('#adminPassword').required = !admin;
        qs('#adminPasswordConfirmation').required = !admin;
        formError.hidden = true;
        formError.textContent = '';

        const selectedRoleIds = new Set((admin?.roles ?? []).map((role) => Number(role.role?.id ?? role.id)));

        qsa('input[name="roles[]"]', rolesContainer).forEach((checkbox) => {
            checkbox.checked = selectedRoleIds.has(Number(checkbox.value));
        });

        const avatarInput = qs('#adminAvatar');

        if (avatarInput) {
            avatarInput.value = '';
        }

        formModal.querySelector('h2').textContent = admin ? 'Edit administrator' : 'Add administrator';
        submitButton.textContent = admin ? 'Update admin' : 'Create admin';
        openModal('adminFormModal');
    };

    const openDetails = async (id) => {
        try {
            const response = await apiGet(`/dashboard/admins/${id}`);
            const admin = response.data ?? {};
            state.detailId = admin.id ?? id;

            qs('#adminDetailName').textContent = admin.name || '—';
            qs('#adminDetailEmail').textContent = admin.email || '—';
            qs('#adminDetailPhone').textContent = admin.phone || '—';
            qs('#adminDetailJobTitle').textContent = admin.job_title || '—';
            qs('#adminDetailStatus').textContent = admin.is_active ? 'Active' : 'Suspended';
            qs('#adminDetailLastLogin').textContent = formatDateTime(admin.last_login_at);
            qs('#adminDetailCreated').textContent = formatDate(admin.created_at);
            detailError.hidden = true;
            detailError.textContent = '';

            if (detailActivity) {
                detailActivity.href = `/dashboard/activity-logs?subject_id=${encodeURIComponent(state.detailId)}&subject_type=${encodeURIComponent('Modules\\Admins\\Models\\Admins')}`;
            }

            renderRoleChips(admin);
            openModal('adminDetailsModal');
        } catch (error) {
            toast(errorMessage(error), 'error');
        }
    };

    const submitForm = async (event) => {
        event.preventDefault();

        formError.hidden = true;
        submitButton.disabled = true;
        const id = qs('#adminId').value;
        const selectedRoles = qsa('input[name="roles[]"]:checked', rolesContainer).map((checkbox) => ({
            role_id: Number(checkbox.value),
        }));
        const payload = {
            name: qs('#adminName').value.trim(),
            email: qs('#adminEmail').value.trim(),
            phone: qs('#adminPhone').value.trim(),
            job_title: qs('#adminJobTitle').value.trim(),
            is_active: qs('#adminIsActive').checked,
            roles: selectedRoles,
        };
        const password = qs('#adminPassword').value;
        const confirmation = qs('#adminPasswordConfirmation').value;

        if (password !== '') {
            payload.password = password;
            payload.password_confirmation = confirmation;
        }

        try {
            const avatarInput = qs('#adminAvatar');
            const avatar = avatarInput?.files?.[0];
            let response;

            if (avatar) {
                const formData = new FormData();
                Object.entries(payload).forEach(([key, value]) => {
                    if (key === 'roles') {
                        value.forEach((role, index) => {
                            formData.append(`roles[${index}][role_id]`, role.role_id);
                        });
                    } else {
                        formData.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : value);
                    }
                });
                formData.append('avatar', avatar);
                response = await apiUpload(`/dashboard/admins${id ? `/${id}` : ''}`, formData, id ? 'PUT' : 'POST');
            } else {
                response = id
                    ? await apiPut(`/dashboard/admins/${id}`, payload)
                    : await apiPost('/dashboard/admins', payload);
            }

            closeModal('adminFormModal');
            toast(response.message || (id ? 'Administrator updated.' : 'Administrator created.'));
            await loadAdmins();
        } catch (error) {
            formError.textContent = errorMessage(error);
            formError.hidden = false;
        } finally {
            submitButton.disabled = false;
        }
    };

    const removeRole = async (roleId) => {
        if (!state.detailId) {
            return;
        }

        const confirmed = await confirmAction({
            title: 'Remove role',
            message: 'Remove this role from the administrator?',
            confirmLabel: 'Remove',
        });

        if (!confirmed) {
            return;
        }

        try {
            const response = await apiDelete(`/dashboard/admins/${state.detailId}/roles/${roleId}`);
            toast(response.message || 'Role removed.');
            await openDetails(state.detailId);
            await loadAdmins();
        } catch (error) {
            detailError.textContent = errorMessage(error);
            detailError.hidden = false;
        }
    };

    const runAction = async (button, action) => {
        const admin = state.rows.find((item) => String(item.id) === button.dataset.id);

        if (!admin) {
            return;
        }

        if (action === 'view') {
            await openDetails(admin.id);

            return;
        }

        if (action === 'edit') {
            resetForm(admin);

            return;
        }

        button.disabled = true;

        try {
            if (action === 'toggle') {
                const confirmed = await confirmAction({
                    title: admin.is_active ? 'Suspend administrator' : 'Activate administrator',
                    message: admin.is_active
                        ? `Suspend ${admin.name}? They will no longer be able to use the dashboard.`
                        : `Activate ${admin.name} and allow dashboard access again?`,
                    confirmLabel: admin.is_active ? 'Suspend' : 'Activate',
                    tone: admin.is_active ? 'danger' : 'primary',
                });

                if (!confirmed) {
                    return;
                }

                const response = await apiPatch(`/dashboard/admins/${admin.id}/toggle-active`);
                toast(response.message || 'Administrator status updated.');
            }

            if (action === 'delete') {
                const confirmed = await confirmAction({
                    title: 'Delete administrator',
                    message: `Move ${admin.name} to the recycle bin?`,
                    confirmLabel: 'Delete',
                });

                if (!confirmed) {
                    return;
                }

                const response = await apiDelete(`/dashboard/admins/${admin.id}`);
                toast(response.message || 'Administrator deleted.');
            }

            if (action === 'restore') {
                const confirmed = await confirmAction({
                    title: 'Restore administrator',
                    message: `Restore ${admin.name} and allow dashboard access again?`,
                    confirmLabel: 'Restore',
                    tone: 'primary',
                });

                if (!confirmed) {
                    return;
                }

                const response = await apiPatch(`/dashboard/admins/${admin.id}/restore`);
                toast(response.message || 'Administrator restored.');
            }

            if (action === 'force-delete') {
                const confirmed = await confirmAction({
                    title: 'Delete administrator permanently',
                    message: `Permanently delete ${admin.name}? This cannot be undone.`,
                    confirmLabel: 'Delete forever',
                });

                if (!confirmed) {
                    return;
                }

                const response = await apiDelete(`/dashboard/admins/${admin.id}/force-delete`);
                toast(response.message || 'Administrator permanently deleted.');
            }

            await loadAdmins();
        } catch (error) {
            toast(errorMessage(error), 'error');
        } finally {
            button.disabled = false;
        }
    };

    const applySearch = debounce(() => {
        state.search = searchInput?.value.trim() ?? '';
        state.page = 1;
        loadAdmins();
    }, 350);

    searchInput?.addEventListener('input', applySearch);

    statusFilter?.addEventListener('change', () => {
        state.status = statusFilter.value;
        state.page = 1;
        loadAdmins();
    });

    roleFilter?.addEventListener('change', () => {
        state.role = roleFilter.value;
        state.page = 1;
        loadAdmins();
    });

    pageSizeSelect?.addEventListener('change', () => {
        state.perPage = Number(pageSizeSelect.value) || 10;
        state.page = 1;
        loadAdmins();
    });

    sortSelect?.addEventListener('change', () => {
        [state.sortBy, state.sortDir] = sortSelect.value.split(':');
        state.page = 1;
        loadAdmins();
    });

    qsa('[data-admins-mode]').forEach((button) => {
        button.addEventListener('click', () => {
            const mode = button.dataset.adminsMode;

            if (mode === state.mode) {
                return;
            }

            state.mode = mode;
            state.page = 1;

            qsa('[data-admins-mode]').forEach((item) => {
                const active = item === button;
                item.classList.toggle('active', active);
                item.setAttribute('aria-selected', String(active));
            });

            loadAdmins();
        });
    });

    prevButton?.addEventListener('click', () => {
        if (state.page > 1) {
            state.page -= 1;
            loadAdmins();
        }
    });

    nextButton?.addEventListener('click', () => {
        if (state.page < state.lastPage) {
            state.page += 1;
            loadAdmins();
        }
    });

    qs('[data-admin-refresh]')?.addEventListener('click', loadAdmins);
    qs('[data-admin-create]')?.addEventListener('click', () => resetForm());
    tableBody.addEventListener('click', (event) => {
        const button = event.target.closest('[data-admin-action]');

        if (button) {
            runAction(button, button.dataset.adminAction);
        }
    });

    detailRoles?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-role]');

        if (button) {
            removeRole(button.dataset.removeRole);
        }
    });

    qsa('#adminFormModal [data-close-modal], #adminDetailsModal [data-close-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            closeModal(button.closest('.dash-modal')?.id ?? 'adminFormModal');
        });
    });

    form.addEventListener('submit', submitForm);

    loadRoles();
    loadAdmins();
};
