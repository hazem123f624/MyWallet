import { qs, qsa } from '../dashboard/core/dom.js?v=5';

const openSidebar = () => {
    qs('#userSidebar')?.classList.add('open');
    const backdrop = qs('#userSidebarBackdrop');

    if (backdrop) {
        backdrop.hidden = false;
    }
};

const closeSidebar = () => {
    qs('#userSidebar')?.classList.remove('open');
    const backdrop = qs('#userSidebarBackdrop');

    if (backdrop) {
        backdrop.hidden = true;
    }
};

const initTheme = () => {
    const key = 'dashboard-theme';
    const root = document.documentElement;

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

    apply(localStorage.getItem(key) || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));

    qsa('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            apply(root.dataset.theme === 'dark' ? 'light' : 'dark');
        });
    });
};

const initSidebar = () => {
    qsa('[data-user-sidebar-open]').forEach((button) => {
        button.addEventListener('click', openSidebar);
    });

    qsa('[data-user-sidebar-close]').forEach((button) => {
        button.addEventListener('click', closeSidebar);
    });

    qs('#userSidebarBackdrop')?.addEventListener('click', closeSidebar);
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

const boot = async () => {
    const version = document.body.dataset.assetVersion || '1';
    initTheme();
    initSidebar();
    initUserMenu();

    const page = document.body.dataset.page;

    if (page === 'overview') {
        const module = await import(`./pages/overview.js?v=${version}`);
        module.initOverviewPage();
    }

    if (page === 'wallets') {
        const module = await import(`./pages/wallets.js?v=${version}`);
        module.initWalletsPage();
    }

    if (page === 'bank-accounts') {
        const module = await import(`./pages/bank-accounts.js?v=${version}`);
        module.initBankAccountsPage();
    }

    if (page === 'profile') {
        const module = await import(`./pages/profile.js?v=${version}`);
        module.initProfilePage();
    }

    if (page === 'security') {
        const module = await import(`./pages/security.js?v=${version}`);
        module.initSecurityPage();
    }
};

boot();
