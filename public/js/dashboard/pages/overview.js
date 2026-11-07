import { apiGet } from '../core/api.js?v=4';
import { renderLineChart, renderStatusDoughnut } from '../core/charts.js?v=1';
import { escapeHtml, formatDate, formatDateTime, initials, qs } from '../core/dom.js?v=4';
import { toast } from '../core/toast.js?v=4';

let lastChartData = null;

const renderRecentUsers = (users) => {
    const body = qs('#overviewRecentUsers');

    if (!body) {
        return;
    }

    body.innerHTML = users.map((user) => `
        <tr>
            <td>
                <div class="dash-table-user">
                    ${user.avatar
                        ? `<img class="dash-table-avatar dash-table-avatar-image" src="${escapeHtml(user.avatar)}" alt="">`
                        : `<span class="dash-table-avatar">${escapeHtml(initials(user.name))}</span>`}
                    <span>
                        <span class="dash-table-name">${escapeHtml(user.name || 'Unnamed user')}</span>
                        <span class="dash-table-subtext">${escapeHtml(user.email || user.phone || '')}</span>
                    </span>
                </div>
            </td>
            <td>
                ${user.is_active
                    ? '<span class="dash-badge dash-badge-success">Active</span>'
                    : '<span class="dash-badge dash-badge-warning">Suspended</span>'}
            </td>
            <td class="dash-col-date">${escapeHtml(formatDate(user.created_at))}</td>
        </tr>
    `).join('');

    qs('#overviewEmpty').hidden = users.length > 0;
};

const renderRecentActivity = (logs) => {
    const host = qs('#overviewRecentActivity');

    if (!host) {
        return;
    }

    if (!logs.length) {
        host.innerHTML = '<div class="dash-drawer-empty"><strong>No recent activity</strong><span>Platform events will appear here.</span></div>';

        return;
    }

    host.innerHTML = logs.map((log) => {
        const subjectId = log.subject?.id;
        const subjectType = log.subject?.type;
        const href = subjectId
            ? `/dashboard/activity-logs?subject_id=${encodeURIComponent(subjectId)}&subject_type=${encodeURIComponent(subjectType || '')}`
            : '/dashboard/activity-logs';

        return `
            <a class="dash-overview-activity-item" href="${href}">
                <span class="dash-notification-icon dash-notification-${escapeHtml(log.log_type || 'admin')}">
                    ${escapeHtml(String(log.action || 'event').charAt(0).toUpperCase())}
                </span>
                <span class="dash-notification-copy">
                    <strong>${escapeHtml(log.description || log.action || 'Activity')}</strong>
                    <small>
                        <span>${escapeHtml(log.causer?.name || 'System')}</span>
                        <time>${escapeHtml(formatDateTime(log.created_at))}</time>
                    </small>
                </span>
            </a>
        `;
    }).join('');
};

const setStatusState = (state) => {
    const loading = qs('#overviewStatusLoading');
    const content = qs('#overviewStatusContent');
    const empty = qs('#overviewStatusEmpty');

    if (loading) {
        loading.hidden = state !== 'loading';
    }

    if (content) {
        content.hidden = state !== 'ready';
    }

    if (empty) {
        empty.hidden = state !== 'empty';
    }
};

const showStatusEmpty = (title, message) => {
    const titleElement = qs('#overviewStatusEmptyTitle');
    const textElement = qs('#overviewStatusEmptyText');

    if (titleElement) {
        titleElement.textContent = title;
    }

    if (textElement) {
        textElement.textContent = message;
    }

    setStatusState('empty');
};

const renderStatusCard = (analytics) => {
    const active = Number(analytics.users?.status?.active ?? 0);
    const suspended = Number(analytics.users?.status?.suspended ?? 0);
    const total = active + suspended;
    const totalBadge = qs('#overviewStatusTotalBadge');

    if (totalBadge) {
        totalBadge.textContent = `${total.toLocaleString()} total`;
    }

    if (total <= 0) {
        showStatusEmpty(
            'No status data available',
            'User status statistics will appear here once accounts are created.'
        );

        return;
    }

    const activePercent = Math.round((active / total) * 100);
    const suspendedPercent = total > 0 ? 100 - activePercent : 0;

    qs('#overviewStatusTotal').textContent = total.toLocaleString();
    qs('#overviewStatusActiveCount').textContent = active.toLocaleString();
    qs('#overviewStatusSuspendedCount').textContent = suspended.toLocaleString();
    qs('#overviewStatusActivePercent').textContent = `${activePercent}% of users`;
    qs('#overviewStatusSuspendedPercent').textContent = `${suspendedPercent}% of users`;

    setStatusState('ready');

    renderStatusDoughnut(
        'overviewStatusChart',
        ['Active', 'Suspended'],
        [active, suspended]
    );
};

const renderCharts = (analytics) => {
    lastChartData = analytics;

    const userChart = qs('#overviewUserChart');

    const lineRendered = renderLineChart(
        'overviewUserChart',
        analytics.users?.series?.labels ?? [],
        analytics.users?.series?.values ?? [],
        'New users'
    );

    if (userChart) {
        userChart.hidden = !lineRendered;
    }
};

export const initOverviewPage = () => {
    const loading = qs('#overviewLoading');
    const refreshedAt = qs('#overviewRefreshedAt');

    const load = async () => {
        if (loading) {
            loading.hidden = false;
        }

        setStatusState('loading');

        try {
            const [analytics, recentUsers, recentActivity] = await Promise.all([
                apiGet('/dashboard/analytics/overview', { days: 30 }),
                apiGet('/dashboard/users', { page: 1, per_page: 5, sort_by: 'created_at', sort_dir: 'desc' }),
                apiGet('/dashboard/activity-logs', { row_in_page: 6 }),
            ]);

            const data = analytics.data ?? {};

            qs('#overviewTotalUsers').textContent = String(data.users?.total ?? 0);
            qs('#overviewActiveUsers').textContent = String(data.users?.active ?? 0);
            qs('#overviewSuspendedUsers').textContent = String(data.users?.suspended ?? 0);
            qs('#overviewTotalAdmins').textContent = String(data.admins?.total ?? 0);
            qs('#overviewNewUsers').textContent = `${data.users?.new ?? 0} new`;

            renderCharts(data);
            renderStatusCard(data);
            renderRecentUsers(Array.isArray(recentUsers.data) ? recentUsers.data : []);
            renderRecentActivity(Array.isArray(recentActivity.data) ? recentActivity.data : []);

            if (refreshedAt) {
                refreshedAt.textContent = formatDateTime(data.generated_at ?? new Date().toISOString());
            }
        } catch (error) {
            showStatusEmpty('Unable to load status data', 'Refresh the page to try again.');
            toast(error.message || 'Unable to load dashboard overview.', 'error');
        } finally {
            if (loading) {
                loading.hidden = true;
            }
        }
    };

    qs('[data-overview-refresh]')?.addEventListener('click', load);

    window.addEventListener('dashboard-theme-changed', () => {
        if (lastChartData) {
            renderCharts(lastChartData);
            renderStatusCard(lastChartData);
        }
    });

    load();
};
