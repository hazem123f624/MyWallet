import { apiDelete, apiGet, apiPatch } from '../core/api.js?v=4';
import { debounce, escapeHtml, formatDate, formatDateTime, initials, qs, qsa } from '../core/dom.js?v=4';
import { closeModal, confirmAction, openModal } from '../core/modal.js?v=4';
import { toast } from '../core/toast.js?v=4';

const canManageStatus = document.body.dataset.canManageStatus === '1';

const state = {
    mode: 'active',
    page: 1,
    lastPage: 1,
    perPage: 10,
    rows: [],
    query: '',
    status: '',
    sortBy: 'created_at',
    sortDir: 'desc',
};

export const initUsersPage = () => {
    const tableBody = qs('#usersTableBody');
    const emptyState = qs('#usersEmpty');
    const loadingState = qs('#usersLoading');
    const searchInput = qs('#userSearch');
    const statusFilter = qs('#userStatusFilter');
    const pageSizeSelect = qs('#userPageSize');
    const sortSelect = qs('#userSort');
    const pageInfo = qs('#usersPageInfo');
    const prevButton = qs('#usersPrevPage');
    const nextButton = qs('#usersNextPage');

    if (!tableBody || !emptyState || !loadingState) {
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

        if (state.query !== '') {
            title.textContent = 'No matching users';
            message.textContent = 'Try a different name, email, or phone number.';

            return;
        }

        title.textContent = state.mode === 'trash' ? 'Recycle bin is empty' : 'No users found';
        message.textContent = state.mode === 'trash'
            ? 'Deleted users will appear here.'
            : 'New user accounts will appear here.';
    };

    const renderStatus = (user) => {
        if (state.mode === 'trash') {
            return '<span class="dash-badge dash-badge-danger">Deleted</span>';
        }

        return user.is_active
            ? '<span class="dash-badge dash-badge-success">Active</span>'
            : '<span class="dash-badge dash-badge-warning">Suspended</span>';
    };

    const renderActions = (user) => {
        if (state.mode === 'trash') {
            return `
                <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-action="restore" data-id="${user.id}">Restore</button>
                <button class="dash-button dash-button-danger dash-button-xs" type="button" data-action="force-delete" data-id="${user.id}">Delete forever</button>
            `;
        }

        const toggleLabel = user.is_active ? 'Suspend' : 'Activate';

        const toggle = canManageStatus
            ? `<button class="dash-button dash-button-ghost dash-button-xs" type="button" data-action="toggle" data-id="${user.id}">${toggleLabel}</button>`
            : '';

        return `
            <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-action="view" data-id="${user.id}">View</button>
            ${toggle}
            <button class="dash-button dash-button-ghost dash-button-xs dash-button-danger-text" type="button" data-action="delete" data-id="${user.id}">Delete</button>
        `;
    };

    const renderRows = () => {
        tableBody.innerHTML = state.rows.map((user) => {
            return `
                <tr>
                    <td>
                        <div class="dash-table-user">
                            ${user.avatar
                                ? `<img class="dash-table-avatar dash-table-avatar-image" src="${escapeHtml(user.avatar)}" alt="">`
                                : `<span class="dash-table-avatar">${escapeHtml(initials(user.name))}</span>`}
                            <span>
                                <span class="dash-table-name">${escapeHtml(user.name || 'Unnamed user')}</span>
                                <span class="dash-table-subtext">#${escapeHtml(user.id)}</span>
                            </span>
                        </div>
                    </td>
                    <td>
                        <span class="dash-table-contact">${escapeHtml(user.email || 'No email')}</span>
                        <span class="dash-table-subtext">${escapeHtml(user.phone || 'No phone')}</span>
                    </td>
                    <td>${renderStatus(user)}</td>
                    <td>${escapeHtml(formatDate(user.created_at))}</td>
                    <td class="dash-table-actions">
                        <div class="dash-row-actions">${renderActions(user)}</div>
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
            pageInfo.textContent = `Page ${currentPage} of ${lastPage} · ${total} user${total === 1 ? '' : 's'}`;
        }

        if (prevButton) {
            prevButton.disabled = currentPage <= 1;
        }

        if (nextButton) {
            nextButton.disabled = currentPage >= lastPage;
        }
    };

    const loadSummary = async () => {
        try {
            const response = await apiGet('/dashboard/users/statistics');
            const stats = response.data ?? {};

            qs('#statTotal').textContent = String(stats.total ?? 0);
            qs('#statActive').textContent = String(stats.active ?? 0);
            qs('#statSuspended').textContent = String(stats.suspended ?? 0);
            qs('#statTrashed').textContent = String(stats.trashed ?? 0);
        } catch (error) {
            toast(error.message || 'Unable to load user statistics.', 'error');
        }
    };

    const loadUsers = async () => {
        setLoading(true);

        try {
            const path = state.mode === 'trash' ? '/dashboard/users/trash' : '/dashboard/users';
            const response = await apiGet(path, {
                page: state.page,
                per_page: state.perPage,
                search: state.query,
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
            toast(error.message || 'Unable to load users.', 'error');
        } finally {
            setLoading(false);
        }
    };

    const reload = async () => {
        await Promise.all([loadUsers(), loadSummary()]);
    };

    const openDetails = async (user) => {
        let details = user;

        try {
            const response = await apiGet(`/dashboard/users/${user.id}`);
            details = response.data ?? user;
        } catch (error) {
            toast(error.message || 'Unable to load user details.', 'error');
        }

        qs('#detailName').textContent = details.name || '—';
        qs('#detailEmail').textContent = details.email || '—';
        qs('#detailPhone').textContent = details.phone || '—';
        qs('#detailCountry').textContent = details.country_code || '—';
        qs('#detailStatus').textContent = state.mode === 'trash'
            ? 'Deleted'
            : (details.is_active ? 'Active' : 'Suspended');
        qs('#detailCreated').textContent = formatDate(details.created_at);
        qs('#detailLastLogin').textContent = formatDateTime(details.last_login_at);

        const activityLink = qs('#userDetailActivity');

        if (activityLink) {
            activityLink.href = `/dashboard/activity-logs?subject_id=${encodeURIComponent(details.id)}&subject_type=${encodeURIComponent('Modules\\Users\\Models\\User')}`;
        }

        openModal('userDetailsModal');
    };

    const runAction = async (button, action) => {
        const user = state.rows.find((item) => String(item.id) === button.dataset.id);

        if (!user) {
            return;
        }

        button.disabled = true;

        try {
            if (action === 'view') {
                await openDetails(user);

                return;
            }

            if (action === 'toggle') {
                const confirmed = await confirmAction({
                    title: user.is_active ? 'Suspend user' : 'Activate user',
                    message: user.is_active
                        ? `Suspend ${user.name}? Their active tokens will be revoked.`
                        : `Activate ${user.name} and allow them to sign in again?`,
                    confirmLabel: user.is_active ? 'Suspend' : 'Activate',
                    tone: user.is_active ? 'danger' : 'primary',
                });

                if (!confirmed) {
                    return;
                }

                const response = await apiPatch(`/dashboard/users/${user.id}/toggle-active`);
                toast(response.message || 'User status updated.');
            }

            if (action === 'delete') {
                const confirmed = await confirmAction({
                    title: 'Delete user',
                    message: `Move ${user.name} to the recycle bin?`,
                    confirmLabel: 'Delete',
                });

                if (!confirmed) {
                    return;
                }

                const response = await apiDelete(`/dashboard/users/${user.id}`);
                toast(response.message || 'User deleted.');
            }

            if (action === 'restore') {
                const confirmed = await confirmAction({
                    title: 'Restore user',
                    message: `Restore ${user.name} and make the account active again?`,
                    confirmLabel: 'Restore',
                    tone: 'primary',
                });

                if (!confirmed) {
                    return;
                }

                const response = await apiPatch(`/dashboard/users/${user.id}/restore`);
                toast(response.message || 'User restored.');
            }

            if (action === 'force-delete') {
                const confirmed = await confirmAction({
                    title: 'Delete permanently',
                    message: `Permanently delete ${user.name}? This cannot be undone.`,
                    confirmLabel: 'Delete forever',
                });

                if (!confirmed) {
                    return;
                }

                const response = await apiDelete(`/dashboard/users/${user.id}/force-delete`);
                toast(response.message || 'User permanently deleted.');
            }

            await reload();
        } catch (error) {
            toast(error.message || 'The action could not be completed.', 'error');
        } finally {
            button.disabled = false;
        }
    };

    qsa('[data-users-mode]').forEach((button) => {
        button.addEventListener('click', () => {
            const mode = button.dataset.usersMode;

            if (mode === state.mode) {
                return;
            }

            state.mode = mode;
            state.page = 1;

            qsa('[data-users-mode]').forEach((item) => {
                const active = item === button;
                item.classList.toggle('active', active);
                item.setAttribute('aria-selected', String(active));
            });

            loadUsers();
        });
    });

    const applySearch = debounce(() => {
        state.query = searchInput?.value.trim() ?? '';
        state.page = 1;
        loadUsers();
    }, 350);

    searchInput?.addEventListener('input', applySearch);

    statusFilter?.addEventListener('change', () => {
        state.status = statusFilter.value;
        state.page = 1;
        loadUsers();
    });

    pageSizeSelect?.addEventListener('change', () => {
        state.perPage = Number(pageSizeSelect.value) || 10;
        state.page = 1;
        loadUsers();
    });

    sortSelect?.addEventListener('change', () => {
        [state.sortBy, state.sortDir] = sortSelect.value.split(':');
        state.page = 1;
        loadUsers();
    });

    prevButton?.addEventListener('click', () => {
        if (state.page > 1) {
            state.page -= 1;
            loadUsers();
        }
    });

    nextButton?.addEventListener('click', () => {
        if (state.page < state.lastPage) {
            state.page += 1;
            loadUsers();
        }
    });

    qs('[data-users-refresh]')?.addEventListener('click', reload);

    tableBody.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-action]');

        if (button) {
            runAction(button, button.dataset.action);
        }
    });

    qsa('#userDetailsModal [data-close-modal]').forEach((button) => {
        button.addEventListener('click', () => closeModal('userDetailsModal'));
    });

    reload();
};
