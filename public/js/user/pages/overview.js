import { apiGet } from '../../dashboard/core/api.js?v=5';
import { renderDistributionDoughnut } from '../../dashboard/core/charts.js?v=2';
import { escapeHtml, formatDateTime, qs } from '../../dashboard/core/dom.js?v=5';
import { toast } from '../../dashboard/core/toast.js?v=5';

let lastChart = null;

const iconMarkup = (name) => {
    const safe = String(name || 'wallet').replace(/[^a-z0-9-]/gi, '') || 'wallet';

    return `<svg class="user-wallet-icon" aria-hidden="true"><use href="#user-icon-${safe}"></use></svg>`;
};

const money = (value, symbol = '') => `${symbol ?? ''}${Number(value || 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
})}`;

const renderFunding = (wallet) => {
    const status = qs('#userWalletStatus');
    const bank = qs('#userFundingBank');
    const account = qs('#userFundingAccount');

    status.textContent = wallet.status === 'active' ? 'Active' : (wallet.status === 'suspended' ? 'Suspended' : 'Unlinked');
    status.className = `dash-badge ${wallet.status === 'active' ? 'dash-badge-success' : 'dash-badge-warning'}`;

    if (wallet.funding_source) {
        bank.textContent = wallet.funding_source.bank?.name || 'Linked bank account';
        account.textContent = `${wallet.funding_source.account_number} · Primary`;
    } else {
        bank.textContent = 'No funding source linked';
        account.textContent = 'Link a verified primary bank account to fund your wallet.';
    }
};

const renderSubWallets = (subWallets, symbol) => {
    const host = qs('#userSubWalletCards');
    const empty = qs('#userSubWalletsEmpty');

    if (!host) {
        return;
    }

    empty.hidden = subWallets.length > 0;

    host.innerHTML = subWallets.map((wallet) => `
        <a class="user-subwallet-card" href="/user/wallets" style="--wallet-color: ${escapeHtml(wallet.color)}">
            <span class="user-subwallet-mark">${iconMarkup(wallet.icon)}</span>
            <span class="user-subwallet-copy">
                <strong>${escapeHtml(wallet.name || 'Sub Wallet')}</strong>
            </span>
            <span class="user-subwallet-amount">${escapeHtml(money(wallet.available_balance, symbol))}</span>
        </a>
    `).join('');
};

const renderOperations = (operations, symbol) => {
    const host = qs('#userRecentOperations');

    if (!host) {
        return;
    }

    if (!operations.length) {
        host.innerHTML = '<div class="dash-drawer-empty"><strong>No activity yet</strong><span>Internal wallet moves will appear here.</span></div>';

        return;
    }

    host.innerHTML = operations.map((operation) => `
        <div class="dash-overview-activity-item">
            <span class="dash-notification-icon">${escapeHtml(String(operation.type || 'op').charAt(0).toUpperCase())}</span>
            <span class="dash-notification-copy">
                <strong>${escapeHtml(operation.description || operation.type)}</strong>
                <small>
                    <span>${escapeHtml(money(operation.amount, symbol))}</span>
                    <time>${escapeHtml(formatDateTime(operation.created_at))}</time>
                </small>
            </span>
        </div>
    `).join('');
};

const renderChart = (wallet, subWallets, symbol) => {
    const labels = subWallets.map((item) => item.name);
    const values = subWallets.map((item) => Number(item.available_balance) + Number(item.reserved_balance));
    const colors = subWallets.map((item) => item.color);
    const unallocated = Number(wallet.available_balance || 0);

    if (unallocated > 0) {
        labels.push('Unallocated');
        values.push(unallocated);
        colors.push('#94a3b8');
    }

    lastChart = { labels, values, colors, symbol };

    const canvas = qs('#userWalletChart');
    const empty = qs('#userWalletChartEmpty');
    const hasValue = values.some((value) => value > 0);
    const rendered = hasValue && renderDistributionDoughnut('userWalletChart', labels, values, colors);

    if (canvas) {
        canvas.hidden = !rendered;
    }

    if (empty) {
        empty.hidden = rendered;
    }
};

export const initOverviewPage = () => {
    const load = async () => {
        try {
            const [walletResponse, subWalletsResponse] = await Promise.all([
                apiGet('/mobile/parent-wallet'),
                apiGet('/mobile/sub-wallets'),
            ]);

            const wallet = walletResponse.data ?? {};
            const subWallets = Array.isArray(subWalletsResponse.data) ? subWalletsResponse.data : [];
            const symbol = wallet.currency_symbol ?? '';

            qs('#userTotalBalance').textContent = money(wallet.total_allocated_balance, symbol);
            qs('#userAvailableBalance').textContent = money(wallet.available_balance, symbol);
            qs('#userReservedBalance').textContent = money(wallet.reserved_balance, symbol);
            qs('#userSubWalletCount').textContent = String(wallet.sub_wallets_count ?? subWallets.length);

            renderFunding(wallet);
            renderSubWallets(subWallets, symbol);
            renderOperations(Array.isArray(wallet.recent_operations) ? wallet.recent_operations : [], symbol);
            renderChart(wallet, subWallets, symbol);
        } catch (error) {
            toast(error.message || 'Unable to load your wallet overview.', 'error');
        }
    };

    qs('[data-user-overview-refresh]')?.addEventListener('click', load);

    window.addEventListener('dashboard-theme-changed', () => {
        if (lastChart) {
            renderDistributionDoughnut('userWalletChart', lastChart.labels, lastChart.values, lastChart.colors);
        }
    });

    load();
};
