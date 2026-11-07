import { apiGet } from './core/api.js?v=4';
import { debounce, escapeHtml, formatDateTime, qs, qsa } from './core/dom.js?v=4';

const openSidebar = () => {
    qs('#dashSidebar')?.classList.add('open');
    const backdrop = qs('#sidebarBackdrop');

    if (backdrop) {
        backdrop.hidden = false;
    }
};

const closeSidebar = () => {
    qs('#dashSidebar')?.classList.remove('open');
    const backdrop = qs('#sidebarBackdrop');

    if (backdrop) {
        backdrop.hidden = true;
    }
};

const initSidebar = () => {
    qsa('[data-sidebar-open]').forEach((button) => {
        button.addEventListener('click', openSidebar);
    });

    qsa('[data-sidebar-close]').forEach((button) => {
        button.addEventListener('click', closeSidebar);
    });

    qs('#sidebarBackdrop')?.addEventListener('click', closeSidebar);

    qsa('.dash-nav-link').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.matchMedia('(max-width: 900px)').matches) {
                closeSidebar();
            }
        });
    });
};

const initTheme = () => {
    const key = 'dashboard-theme';
    const root = document.documentElement;
    const stored = localStorage.getItem(key);
    const preferred = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';

    const apply = (theme) => {
        root.dataset.theme = theme;
        localStorage.setItem(key, theme);
        qsa('[data-theme-icon="moon"]').forEach((element) => {
            element.hidden = theme === 'dark';
        });
        qsa('[data-theme-icon="sun"]').forEach((element) => {
            element.hidden = theme !== 'dark';
        });

        window.dispatchEvent(new CustomEvent('dashboard-theme-changed', { detail: { theme } }));
    };

    apply(stored || preferred);

    qsa('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            apply(root.dataset.theme === 'dark' ? 'light' : 'dark');
        });
    });
};

const initUserMenu = () => {
    const toggle = qs('[data-user-menu-toggle]');
    const menu = qs('#userMenu');

    if (!toggle || !menu) {
        return;
    }

    const close = () => {
        menu.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    };

    toggle.addEventListener('click', (event) => {
        event.stopPropagation();
        const isOpen = !menu.hidden;
        menu.hidden = isOpen;
        toggle.setAttribute('aria-expanded', String(!isOpen));

        if (!isOpen) {
            menu.querySelector('a, button')?.focus();
        }
    });

    document.addEventListener('click', (event) => {
        if (!menu.hidden && !menu.contains(event.target) && !toggle.contains(event.target)) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
        }
    });
};

const initNotifications = () => {
    const drawer = qs('#notificationDrawer');
    const backdrop = qs('#notificationBackdrop');
    const list = qs('#notificationList');
    const dot = qs('.dash-notification-dot');

    if (!drawer || !list) {
        return;
    }

    const seen = sessionStorage.getItem('dashboard_notifications_seen');

    if (dot && !seen) {
        dot.hidden = false;
    }

    const render = (logs) => {
        if (!logs.length) {
            list.innerHTML = '<div class="dash-drawer-empty"><strong>No recent activity</strong><span>Platform events will appear here.</span></div>';

            return;
        }

        list.innerHTML = logs.map((log) => {
            const subjectId = log.subject?.id;
            const subjectType = log.subject?.type;
            const href = subjectId
                ? `/dashboard/activity-logs?subject_id=${encodeURIComponent(subjectId)}&subject_type=${encodeURIComponent(subjectType || '')}`
                : '/dashboard/activity-logs';

            return `
                <a class="dash-notification-item" href="${href}">
                    <span class="dash-notification-icon dash-notification-${escapeHtml(log.log_type || 'admin')}">
                        ${escapeHtml(String(log.action || 'event').charAt(0).toUpperCase())}
                    </span>
                    <span class="dash-notification-copy">
                        <strong>${escapeHtml(log.description || log.action || 'Activity')}</strong>
                        <small>${escapeHtml(log.causer?.name || 'System')} · ${escapeHtml(formatDateTime(log.created_at))}</small>
                    </span>
                </a>
            `;
        }).join('');
    };

    const load = async () => {
        list.innerHTML = '<div class="dash-drawer-loading"><span class="dash-spinner"></span><span>Loading activity...</span></div>';

        try {
            const response = await apiGet('/dashboard/activity-logs', { row_in_page: 8 });
            render(Array.isArray(response.data) ? response.data : []);
        } catch (error) {
            list.innerHTML = `<div class="dash-drawer-empty"><strong>Unable to load activity</strong><span>${escapeHtml(error.message || 'Try again later.')}</span></div>`;
        }
    };

    const open = () => {
        drawer.hidden = false;
        backdrop.hidden = false;
        sessionStorage.setItem('dashboard_notifications_seen', '1');

        if (dot) {
            dot.hidden = true;
        }

        load();
    };

    const close = () => {
        drawer.hidden = true;
        backdrop.hidden = true;
    };

    qsa('[data-notifications-open]').forEach((button) => {
        button.addEventListener('click', open);
    });

    qsa('[data-notifications-close]').forEach((button) => {
        button.addEventListener('click', close);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !drawer.hidden) {
            close();
        }
    });
};

const initCommandPalette = () => {
    const palette = qs('#commandPalette');
    const input = qs('#commandInput');
    const resultsHost = qs('#commandResults');

    if (!palette || !input || !resultsHost) {
        return;
    }

    const navigation = [
        { label: 'Dashboard', href: '/dashboard', hint: 'Overview and recent users' },
        { label: 'User Management', href: '/dashboard/users', hint: 'Search and manage users' },
        { label: 'Admin Management', href: '/dashboard/admins', hint: 'Manage administrators' },
        { label: 'Roles & Permissions', href: '/dashboard/roles', hint: 'Roles and permission matrix' },
        { label: 'Permission Models', href: '/dashboard/models', hint: 'Modules and permissions' },
        { label: 'Activity Logs', href: '/dashboard/activity-logs', hint: 'Audit trail' },
        { label: 'Profile', href: '/dashboard/profile', hint: 'Your account and password' },
    ];

    let items = [];
    let selected = 0;

    const render = (groups) => {
        items = groups.flatMap((group) => group.items);
        selected = 0;

        if (items.length === 0) {
            resultsHost.innerHTML = '<div class="dash-command-empty">No results found.</div>';

            return;
        }

        resultsHost.innerHTML = groups
            .filter((group) => group.items.length > 0)
            .map((group) => `
                <div class="dash-command-group">
                    <span class="dash-command-group-title">${escapeHtml(group.title)}</span>
                    ${group.items.map((item, index) => `
                        <a class="dash-command-item ${index === 0 && group === groups.find((entry) => entry.items.length > 0) ? 'active' : ''}" href="${escapeHtml(item.href)}" data-command-item>
                            <span>
                                <strong>${escapeHtml(item.label)}</strong>
                                <small>${escapeHtml(item.hint || '')}</small>
                            </span>
                            <span aria-hidden="true">↗</span>
                        </a>
                    `).join('')}
                </div>
            `).join('');

        markActive();
    };

    const markActive = () => {
        qsa('[data-command-item]', resultsHost).forEach((element, index) => {
            element.classList.toggle('active', index === selected);
        });
    };

    const localResults = (query) => navigation.filter((item) => {
        const haystack = `${item.label} ${item.hint}`.toLowerCase();

        return query === '' || haystack.includes(query.toLowerCase());
    });

    const search = debounce(async (rawQuery) => {
        const query = rawQuery.trim();
        const groups = [{
            title: 'Navigate',
            items: localResults(query),
        }];

        if (query.length >= 2) {
            try {
                const [users, admins, roles] = await Promise.all([
                    apiGet('/dashboard/users', { search: query, per_page: 5 }),
                    apiGet('/dashboard/admins', { search: query, per_page: 5 }),
                    apiGet('/dashboard/roles', { search: query, per_page: 5 }),
                ]);

                groups.push(
                    {
                        title: 'Users',
                        items: (users.data ?? []).map((user) => ({
                            label: user.name || `User #${user.id}`,
                            hint: user.email || user.phone || '',
                            href: `/dashboard/activity-logs?subject_id=${encodeURIComponent(user.id)}&subject_type=${encodeURIComponent('Modules\\Users\\Models\\User')}`,
                        })),
                    },
                    {
                        title: 'Administrators',
                        items: (admins.data ?? []).map((admin) => ({
                            label: admin.name || `Admin #${admin.id}`,
                            hint: admin.email || '',
                            href: '/dashboard/admins',
                        })),
                    },
                    {
                        title: 'Roles',
                        items: (roles.data ?? []).map((role) => ({
                            label: role.name || `Role #${role.id}`,
                            hint: role.description || '',
                            href: '/dashboard/roles',
                        })),
                    },
                );
            } catch (error) {
                groups.push({ title: 'Search', items: [] });
            }
        }

        render(groups);
    }, 240);

    const open = () => {
        palette.hidden = false;
        input.value = '';
        input.focus();
        render([{ title: 'Navigate', items: navigation }]);
        document.body.classList.add('dash-modal-open');
    };

    const close = () => {
        palette.hidden = true;
        document.body.classList.remove('dash-modal-open');
    };

    qsa('[data-command-open]').forEach((button) => {
        button.addEventListener('click', open);
    });

    qsa('[data-command-close]').forEach((button) => {
        button.addEventListener('click', close);
    });

    input.addEventListener('input', () => search(input.value));

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();

            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            selected = Math.min(selected + 1, items.length - 1);
            markActive();
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            selected = Math.max(selected - 1, 0);
            markActive();
        }

        if (event.key === 'Enter') {
            event.preventDefault();
            const active = qsa('[data-command-item]', resultsHost)[selected];

            if (active) {
                window.location.href = active.getAttribute('href');
            }
        }
    });

    resultsHost.addEventListener('click', (event) => {
        if (event.target.closest('[data-command-item]')) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName);

        if (((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') || (event.key === '/' && !typing)) {
            event.preventDefault();
            open();
        }
    });
};

const boot = async () => {
    initTheme();
    initSidebar();
    initUserMenu();
    initNotifications();
    initCommandPalette();

    const page = document.body.dataset.page;

    if (page === 'overview') {
        const module = await import('./pages/overview.js?v=4');
        module.initOverviewPage();
    }

    if (page === 'users') {
        const module = await import('./pages/users.js?v=4');
        module.initUsersPage();
    }

    if (page === 'admins') {
        const module = await import('./pages/admins.js?v=4');
        module.initAdminsPage();
    }

    if (page === 'roles') {
        const module = await import('./pages/roles.js?v=1');
        module.initRolesPage();
    }

    if (page === 'models') {
        const module = await import('./pages/models.js?v=1');
        module.initModelsPage();
    }

    if (page === 'activity-logs') {
        const module = await import('./pages/activity-logs.js?v=1');
        module.initActivityLogsPage();
    }

    if (page === 'profile') {
        const module = await import('./pages/profile.js?v=4');
        module.initProfilePage();
    }
};

boot();
