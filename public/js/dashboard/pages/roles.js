import { apiDelete, apiGet, apiPatch, apiPost, apiPut } from '../core/api.js?v=4';
import { debounce, escapeHtml, formatDate, initials, qs, qsa } from '../core/dom.js?v=4';
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
    sortBy: 'created_at',
    sortDir: 'desc',
    models: [],
    permissionSearch: '',
    detailId: null,
    availableAdminsLoaded: false,
};

const errorMessage = (error) => {
    const first = error.payload?.errors ? Object.values(error.payload.errors)[0] : null;

    return Array.isArray(first) ? first[0] : (first || error.message || 'The action could not be completed.');
};

const roleName = (role) => role?.name || 'Role';
const roleDescription = (role) => role?.description || '—';

export const initRolesPage = () => {
    const tableBody = qs('#rolesTableBody');
    const emptyState = qs('#rolesEmpty');
    const loadingState = qs('#rolesLoading');
    const pageInfo = qs('#rolesPageInfo');
    const prevButton = qs('#rolesPrevPage');
    const nextButton = qs('#rolesNextPage');
    const searchInput = qs('#roleSearch');
    const statusFilter = qs('#roleStatusFilter');
    const sortSelect = qs('#roleSort');
    const form = qs('#roleForm');
    const formError = qs('#roleFormError');
    const submitButton = qs('#roleSubmit');
    const permissionsContainer = qs('#rolePermissionsOptions');
    const permissionSearch = qs('#rolePermissionSearch');
    const permissionCount = qs('#rolePermissionCount');
    const detailError = qs('#roleDetailError');
    const membersContainer = qs('#roleDetailMembers');
    const availableContainer = qs('#roleAvailableAdmins');
    const availableSearch = qs('#roleAvailableAdminSearch');

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

        if (state.search !== '' || state.status !== '') {
            title.textContent = 'No matching roles';
            message.textContent = 'Try another search or status filter.';

            return;
        }

        if (state.mode === 'trash') {
            title.textContent = 'Recycle bin is empty';
            message.textContent = 'Deleted roles will appear here.';

            return;
        }

        title.textContent = 'No roles found';
        message.textContent = 'Create the first role to group permissions and administrators.';
    };

    const renderRows = () => {
        tableBody.innerHTML = state.rows.map((role) => {
            const actions = state.mode === 'trash'
                ? `
                    <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-role-action="restore" data-id="${role.id}">Restore</button>
                    <button class="dash-button dash-button-danger dash-button-xs" type="button" data-role-action="force-delete" data-id="${role.id}">Delete forever</button>
                `
                : `
                    <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-role-action="view" data-id="${role.id}">View</button>
                    <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-role-action="edit" data-id="${role.id}">Edit</button>
                    <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-role-action="toggle" data-id="${role.id}">${role.is_active ? 'Deactivate' : 'Activate'}</button>
                    <button class="dash-button dash-button-ghost dash-button-xs dash-button-danger-text" type="button" data-role-action="delete" data-id="${role.id}">Delete</button>
                `;

            return `
                <tr>
                    <td>
                        <div class="dash-table-user">
                            <span class="dash-table-avatar">${escapeHtml(initials(roleName(role)))}</span>
                            <span>
                                <span class="dash-table-name">${escapeHtml(roleName(role))}</span>
                                <span class="dash-table-subtext">#${escapeHtml(role.id)}</span>
                            </span>
                        </div>
                    </td>
                    <td>${escapeHtml(roleDescription(role))}</td>
                    <td>${escapeHtml(role.permissions_count ?? 0)}</td>
                    <td>${escapeHtml(role.admins_count ?? 0)}</td>
                    <td>
                        ${state.mode === 'trash'
                            ? '<span class="dash-badge dash-badge-danger">Deleted</span>'
                            : role.is_active
                                ? '<span class="dash-badge dash-badge-success">Active</span>'
                                : '<span class="dash-badge dash-badge-warning">Inactive</span>'}
                    </td>
                    <td>${escapeHtml(formatDate(role.created_at))}</td>
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
            pageInfo.textContent = `Page ${currentPage} of ${lastPage} · ${total} role${total === 1 ? '' : 's'}`;
        }

        if (prevButton) {
            prevButton.disabled = currentPage <= 1;
        }

        if (nextButton) {
            nextButton.disabled = currentPage >= lastPage;
        }
    };

    const loadRoles = async () => {
        setLoading(true);

        try {
            const path = state.mode === 'trash' ? '/dashboard/roles/trash' : '/dashboard/roles';
            const response = await apiGet(path, {
                page: state.page,
                per_page: state.perPage,
                search: state.search,
                is_active: state.mode === 'trash' ? '' : state.status,
                sort_by: state.sortBy,
                sort_dir: state.sortDir,
            });

            state.rows = Array.isArray(response.data) ? response.data : [];

            renderRows();
            updatePagination(response.pagination);
        } catch (error) {
            state.rows = [];
            renderRows();
            toast(error.message || 'Unable to load roles.', 'error');
        } finally {
            setLoading(false);
        }
    };

    const loadModels = async () => {
        try {
            const response = await apiGet('/dashboard/models', { per_page: 100 });
            state.models = Array.isArray(response.data) ? response.data : [];
        } catch (error) {
            toast(error.message || 'Unable to load permission models.', 'error');
        }
    };

    const checkedPermissionKeys = () => new Set(
        qsa('input[name="permissions[]"]:checked', permissionsContainer).map((checkbox) => checkbox.value)
    );

    const updatePermissionCount = () => {
        if (permissionCount) {
            const total = qsa('input[name="permissions[]"]:checked', permissionsContainer).length;
            permissionCount.textContent = `${total} selected`;
        }
    };

    const renderPermissionMatrix = (selected = new Set()) => {
        if (!permissionsContainer) {
            return;
        }

        if (state.models.length === 0) {
            permissionsContainer.innerHTML = '<p class="dash-muted-text">No permission models are available.</p>';
            updatePermissionCount();

            return;
        }

        const query = state.permissionSearch.toLowerCase();

        const models = state.models
            .map((model) => {
                const title = model.title || `Model #${model.id}`;
                const permissions = (model.permissions ?? []).filter((permission) => {
                    if (query === '') {
                        return true;
                    }

                    return `${title} ${permission.title || ''} ${permission.name || ''}`.toLowerCase().includes(query);
                });

                return { ...model, title, permissions };
            })
            .filter((model) => model.permissions.length > 0);

        if (models.length === 0) {
            permissionsContainer.innerHTML = '<p class="dash-muted-text">No permissions match your search.</p>';
            updatePermissionCount();

            return;
        }

        permissionsContainer.innerHTML = models.map((model) => `
            <section class="dash-permission-model">
                <div class="dash-permission-model-head">
                    <h4>${escapeHtml(model.title)}</h4>
                    <button class="dash-text-link" type="button" data-permission-model="${model.id}">Toggle all</button>
                </div>
                <div class="dash-permission-list">
                    ${model.permissions.map((permission) => {
                        const key = `${model.id}:${permission.id}`;

                        return `
                            <label class="dash-checkbox">
                                <input type="checkbox" name="permissions[]" value="${key}" ${selected.has(key) ? 'checked' : ''}>
                                <span>${escapeHtml(permission.title || permission.name || `Permission #${permission.id}`)}</span>
                            </label>
                        `;
                    }).join('')}
                </div>
            </section>
        `).join('');

        updatePermissionCount();
    };

    const selectedPermissionKeys = (role) => new Set(
        (role?.permissions ?? [])
            .filter((permission) => permission.model_id && permission.permission_id)
            .map((permission) => `${permission.model_id}:${permission.permission_id}`)
    );

    const resetForm = async (role = null) => {
        form.reset();
        qs('#roleId').value = role?.id ?? '';
        qs('#roleNameEn').value = role?.translate?.name?.en ?? role?.name ?? '';
        qs('#roleNameAr').value = role?.translate?.name?.ar ?? role?.name ?? '';
        qs('#roleDescriptionEn').value = role?.translate?.description?.en ?? role?.description ?? '';
        qs('#roleDescriptionAr').value = role?.translate?.description?.ar ?? role?.description ?? '';
        formError.hidden = true;
        formError.textContent = '';
        state.permissionSearch = '';

        if (permissionSearch) {
            permissionSearch.value = '';
        }

        if (state.models.length === 0) {
            await loadModels();
        }

        let selected = selectedPermissionKeys(role);

        if (role && selected.size === 0) {
            try {
                const response = await apiGet(`/dashboard/roles/${role.id}`);
                selected = selectedPermissionKeys(response.data ?? {});
            } catch (error) {
                toast(error.message || 'Unable to load role permissions.', 'error');
            }
        }

        renderPermissionMatrix(selected);

        qs('#roleFormModal').querySelector('h2').textContent = role ? 'Edit role' : 'Add role';
        submitButton.textContent = role ? 'Update role' : 'Create role';
        openModal('roleFormModal');
    };

    const renderMembers = (members) => {
        if (!membersContainer) {
            return;
        }

        membersContainer.innerHTML = members.length
            ? members.map((member) => `
                <div class="dash-member-row">
                    <div class="dash-table-user">
                        <span class="dash-table-avatar">${escapeHtml(initials(member.name))}</span>
                        <span>
                            <span class="dash-table-name">${escapeHtml(member.name || 'Administrator')}</span>
                            <span class="dash-table-subtext">${escapeHtml(member.email || '')}</span>
                        </span>
                    </div>
                    <button class="dash-button dash-button-ghost dash-button-xs dash-button-danger-text" type="button" data-remove-member="${member.id}">
                        Remove
                    </button>
                </div>
            `).join('')
            : '<p class="dash-muted-text">No administrators are assigned to this role.</p>';
    };

    const renderAvailableAdmins = (admins) => {
        if (!availableContainer) {
            return;
        }

        availableContainer.innerHTML = admins.length
            ? admins.map((admin) => `
                <label class="dash-checkbox">
                    <input type="checkbox" name="available_admins[]" value="${admin.id}">
                    <span>${escapeHtml(admin.name || 'Administrator')}${admin.job_title ? ` · ${escapeHtml(admin.job_title)}` : ''}</span>
                </label>
            `).join('')
            : '<p class="dash-muted-text">No available administrators found.</p>';
    };

    const loadAvailableAdmins = async (search = '') => {
        if (!state.detailId) {
            return;
        }

        try {
            const response = await apiGet(`/dashboard/roles/${state.detailId}/available-admins`, { search });
            renderAvailableAdmins(Array.isArray(response.data) ? response.data : []);
            state.availableAdminsLoaded = true;
        } catch (error) {
            detailError.textContent = errorMessage(error);
            detailError.hidden = false;
        }
    };

    const renderDetailPermissions = (groups) => {
        const container = qs('#roleDetailPermissions');

        if (!container) {
            return;
        }

        if (!groups.length) {
            container.innerHTML = '<p class="dash-muted-text">No permissions granted.</p>';

            return;
        }

        container.innerHTML = groups.map((group) => `
            <div class="dash-chip-group">
                <span class="dash-chip-group-title">${escapeHtml(group.title || `Model #${group.id}`)}</span>
                <div class="dash-chip-list">
                    ${(group.permissions ?? []).map((permission) => `
                        <span class="dash-chip">${escapeHtml(permission.title || permission.name || 'Permission')}</span>
                    `).join('')}
                </div>
            </div>
        `).join('');
    };

    const openDetails = async (id) => {
        try {
            const [roleResponse, permissionsResponse, adminsResponse] = await Promise.all([
                apiGet(`/dashboard/roles/${id}`),
                apiGet(`/dashboard/roles/${id}/permissions`),
                apiGet(`/dashboard/roles/${id}/admins`, { per_page: 100 }),
            ]);

            const role = roleResponse.data ?? {};
            state.detailId = role.id ?? id;
            detailError.hidden = true;
            detailError.textContent = '';

            qs('#roleDetailName').textContent = roleName(role);
            qs('#roleDetailDescription').textContent = roleDescription(role);
            qs('#roleDetailStatus').textContent = role.is_active ? 'Active' : 'Inactive';
            qs('#roleDetailPermissionCount').textContent = String(role.permissions_count ?? 0);
            qs('#roleDetailAdminCount').textContent = String(role.admins_count ?? 0);
            qs('#roleDetailCreated').textContent = formatDate(role.created_at);

            renderDetailPermissions(Array.isArray(permissionsResponse.data) ? permissionsResponse.data : []);
            renderMembers(Array.isArray(adminsResponse.data) ? adminsResponse.data : []);
            await loadAvailableAdmins(availableSearch?.value.trim() ?? '');

            openModal('roleDetailsModal');
        } catch (error) {
            toast(errorMessage(error), 'error');
        }
    };

    const submitForm = async (event) => {
        event.preventDefault();

        formError.hidden = true;
        submitButton.disabled = true;
        const id = qs('#roleId').value;
        const permissions = qsa('input[name="permissions[]"]:checked', permissionsContainer).map((checkbox) => {
            const [modelId, permissionId] = checkbox.value.split(':');

            return {
                model_id: Number(modelId),
                permission_id: Number(permissionId),
            };
        });
        const payload = {
            name: {
                en: qs('#roleNameEn').value.trim(),
                ar: qs('#roleNameAr').value.trim(),
            },
            description: {
                en: qs('#roleDescriptionEn').value.trim(),
                ar: qs('#roleDescriptionAr').value.trim(),
            },
            permissions,
        };

        try {
            const response = id
                ? await apiPut(`/dashboard/roles/${id}`, payload)
                : await apiPost('/dashboard/roles', payload);

            closeModal('roleFormModal');
            toast(response.message || (id ? 'Role updated.' : 'Role created.'));
            await loadRoles();
        } catch (error) {
            formError.textContent = errorMessage(error);
            formError.hidden = false;
        } finally {
            submitButton.disabled = false;
        }
    };

    const assignAdmins = async () => {
        if (!state.detailId) {
            return;
        }

        const adminIds = qsa('input[name="available_admins[]"]:checked', availableContainer)
            .map((checkbox) => Number(checkbox.value));

        if (adminIds.length === 0) {
            detailError.textContent = 'Select at least one administrator to assign.';
            detailError.hidden = false;

            return;
        }

        try {
            const response = await apiPost(`/dashboard/roles/${state.detailId}/admins`, { admin_ids: adminIds });
            toast(response.message || 'Administrators assigned.');
            await openDetails(state.detailId);
            await loadRoles();
        } catch (error) {
            detailError.textContent = errorMessage(error);
            detailError.hidden = false;
        }
    };

    const removeMember = async (adminId) => {
        if (!state.detailId) {
            return;
        }

        const confirmed = await confirmAction({
            title: 'Remove administrator',
            message: 'Remove this administrator from the role?',
            confirmLabel: 'Remove',
        });

        if (!confirmed) {
            return;
        }

        try {
            const response = await apiDelete(`/dashboard/admins/${adminId}/roles/${state.detailId}`);
            toast(response.message || 'Administrator removed.');
            await openDetails(state.detailId);
            await loadRoles();
        } catch (error) {
            detailError.textContent = errorMessage(error);
            detailError.hidden = false;
        }
    };

    const runAction = async (button, action) => {
        const role = state.rows.find((item) => String(item.id) === button.dataset.id);

        if (!role) {
            return;
        }

        if (action === 'view') {
            await openDetails(role.id);

            return;
        }

        if (action === 'edit') {
            await resetForm(role);

            return;
        }

        button.disabled = true;

        try {
            if (action === 'toggle') {
                const confirmed = await confirmAction({
                    title: role.is_active ? 'Deactivate role' : 'Activate role',
                    message: role.is_active
                        ? `Deactivate ${roleName(role)}? Administrators keep the role but it stops granting access.`
                        : `Activate ${roleName(role)} and let it grant access again?`,
                    confirmLabel: role.is_active ? 'Deactivate' : 'Activate',
                    tone: role.is_active ? 'danger' : 'primary',
                });

                if (!confirmed) {
                    return;
                }

                const response = await apiPatch(`/dashboard/roles/${role.id}/toggle-active`);
                toast(response.message || 'Role status updated.');
            }

            if (action === 'delete') {
                const confirmed = await confirmAction({
                    title: 'Delete role',
                    message: `Move ${roleName(role)} to the recycle bin?`,
                    confirmLabel: 'Delete',
                });

                if (!confirmed) {
                    return;
                }

                const response = await apiDelete(`/dashboard/roles/${role.id}`);
                toast(response.message || 'Role deleted.');
            }

            if (action === 'restore') {
                const confirmed = await confirmAction({
                    title: 'Restore role',
                    message: `Restore ${roleName(role)}?`,
                    confirmLabel: 'Restore',
                    tone: 'primary',
                });

                if (!confirmed) {
                    return;
                }

                const response = await apiPatch(`/dashboard/roles/${role.id}/restore`);
                toast(response.message || 'Role restored.');
            }

            if (action === 'force-delete') {
                const confirmed = await confirmAction({
                    title: 'Delete role permanently',
                    message: `Permanently delete ${roleName(role)}? This cannot be undone.`,
                    confirmLabel: 'Delete forever',
                });

                if (!confirmed) {
                    return;
                }

                const response = await apiDelete(`/dashboard/roles/${role.id}/force-delete`);
                toast(response.message || 'Role permanently deleted.');
            }

            await loadRoles();
        } catch (error) {
            toast(errorMessage(error), 'error');
        } finally {
            button.disabled = false;
        }
    };

    const applySearch = debounce(() => {
        state.search = searchInput?.value.trim() ?? '';
        state.page = 1;
        loadRoles();
    }, 350);

    const applyAvailableSearch = debounce(() => {
        loadAvailableAdmins(availableSearch?.value.trim() ?? '');
    }, 350);

    searchInput?.addEventListener('input', applySearch);
    availableSearch?.addEventListener('input', applyAvailableSearch);

    statusFilter?.addEventListener('change', () => {
        state.status = statusFilter.value;
        state.page = 1;
        loadRoles();
    });

    sortSelect?.addEventListener('change', () => {
        [state.sortBy, state.sortDir] = sortSelect.value.split(':');
        state.page = 1;
        loadRoles();
    });

    qsa('[data-roles-mode]').forEach((button) => {
        button.addEventListener('click', () => {
            const mode = button.dataset.rolesMode;

            if (mode === state.mode) {
                return;
            }

            state.mode = mode;
            state.page = 1;

            qsa('[data-roles-mode]').forEach((item) => {
                const active = item === button;
                item.classList.toggle('active', active);
                item.setAttribute('aria-selected', String(active));
            });

            loadRoles();
        });
    });

    prevButton?.addEventListener('click', () => {
        if (state.page > 1) {
            state.page -= 1;
            loadRoles();
        }
    });

    nextButton?.addEventListener('click', () => {
        if (state.page < state.lastPage) {
            state.page += 1;
            loadRoles();
        }
    });

    qs('[data-role-refresh]')?.addEventListener('click', loadRoles);
    qs('[data-role-create]')?.addEventListener('click', () => resetForm());
    qs('#roleAssignAdmins')?.addEventListener('click', assignAdmins);

    tableBody.addEventListener('click', (event) => {
        const button = event.target.closest('[data-role-action]');

        if (button) {
            runAction(button, button.dataset.roleAction);
        }
    });

    membersContainer?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-member]');

        if (button) {
            removeMember(button.dataset.removeMember);
        }
    });

    qsa('#roleFormModal [data-close-modal], #roleDetailsModal [data-close-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            closeModal(button.closest('.dash-modal')?.id ?? 'roleFormModal');
        });
    });

    permissionSearch?.addEventListener('input', () => {
        state.permissionSearch = permissionSearch.value.trim();
        renderPermissionMatrix(checkedPermissionKeys());
    });

    permissionsContainer?.addEventListener('change', updatePermissionCount);

    permissionsContainer?.addEventListener('click', (event) => {
        const modelButton = event.target.closest('[data-permission-model]');

        if (!modelButton) {
            return;
        }

        const section = modelButton.closest('.dash-permission-model');
        const checkboxes = qsa('input[type="checkbox"]', section);
        const shouldCheck = checkboxes.some((checkbox) => !checkbox.checked);
        checkboxes.forEach((checkbox) => {
            checkbox.checked = shouldCheck;
        });
        updatePermissionCount();
    });

    qs('[data-permission-all]')?.addEventListener('click', () => {
        qsa('input[name="permissions[]"]', permissionsContainer).forEach((checkbox) => {
            checkbox.checked = true;
        });
        updatePermissionCount();
    });

    qs('[data-permission-none]')?.addEventListener('click', () => {
        qsa('input[name="permissions[]"]', permissionsContainer).forEach((checkbox) => {
            checkbox.checked = false;
        });
        updatePermissionCount();
    });

    form.addEventListener('submit', submitForm);

    loadModels();
    loadRoles();
};
