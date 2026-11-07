import { apiDownload, apiGet } from '../core/api.js?v=4';
import { debounce, escapeHtml, formatDateTime, qs, qsa } from '../core/dom.js?v=4';
import { closeModal, openModal } from '../core/modal.js?v=4';
import { toast } from '../core/toast.js?v=4';

const state = {
    page: 1,
    lastPage: 1,
    perPage: 15,
    rows: [],
    filters: {},
    subjectId: null,
    subjectType: '',
    detailLog: null,
};

const shortType = (type) => String(type ?? '').split('\\').filter(Boolean).pop() || 'Subject';

const actionTone = (action) => {
    if (['created', 'login'].includes(action)) {
        return 'success';
    }

    if (['deleted', 'failed'].includes(action)) {
        return 'danger';
    }

    if (['updated', 'restored'].includes(action)) {
        return 'warning';
    }

    return 'neutral';
};

export const initActivityLogsPage = () => {
    const tableBody = qs('#logsTableBody');
    const emptyState = qs('#logsEmpty');
    const loadingState = qs('#logsLoading');
    const pageInfo = qs('#logsPageInfo');
    const prevButton = qs('#logsPrevPage');
    const nextButton = qs('#logsNextPage');
    const filtersForm = qs('#logFilters');
    const banner = qs('#logSubjectBanner');
    const bannerText = qs('#logSubjectText');
    const pageSizeSelect = qs('#logPageSize');
    const copyButton = qs('#logCopy');

    if (!tableBody || !emptyState || !loadingState) {
        return;
    }

    const filterFields = {
        type: qs('#logType'),
        action: qs('#logAction'),
        subject_type: qs('#logSubjectType'),
        causer_id: qs('#logCauserId'),
        from: qs('#logFrom'),
        to: qs('#logTo'),
        search: qs('#logSearch'),
    };

    const setLoading = (loading) => {
        loadingState.hidden = !loading;
        tableBody.setAttribute('aria-busy', String(loading));
    };

    const updateEmptyState = () => {
        emptyState.hidden = state.rows.length > 0;

        const title = emptyState.querySelector('h3');
        const message = emptyState.querySelector('p');

        title.textContent = state.subjectId ? 'No activity found for this subject' : 'No activity logs found';
        message.textContent = state.subjectId
            ? 'Actions recorded against this record will appear here.'
            : 'Actions performed in the platform will appear here.';
    };

    const renderRows = () => {
        tableBody.innerHTML = state.rows.map((log) => {
            const subjectId = log.subject?.id;
            const subjectType = log.subject?.type;

            return `
                <tr>
                    <td class="dash-col-date">${escapeHtml(formatDateTime(log.created_at))}</td>
                    <td>
                        <span class="dash-badge dash-badge-${actionTone(log.action)}">${escapeHtml(log.action || 'event')}</span>
                        <span class="dash-table-subtext">${escapeHtml(log.log_type || '')}</span>
                    </td>
                    <td>${escapeHtml(log.description || '—')}</td>
                    <td>${escapeHtml(log.causer?.name || 'System')}</td>
                    <td>
                        <span class="dash-table-name">${escapeHtml(shortType(subjectType))}</span>
                        <span class="dash-table-subtext">${subjectId ? `#${escapeHtml(subjectId)}` : '—'}</span>
                    </td>
                    <td>${escapeHtml(log.ip_address || '—')}</td>
                    <td class="dash-table-actions">
                        <div class="dash-row-actions">
                            <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-log-action="view" data-id="${log.id}">View</button>
                            ${subjectId
                                ? `<button class="dash-button dash-button-ghost dash-button-xs" type="button" data-log-action="export" data-id="${log.id}">Export CSV</button>`
                                : ''}
                        </div>
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
            pageInfo.textContent = `Page ${currentPage} of ${lastPage} · ${total} log${total === 1 ? '' : 's'}`;
        }

        if (prevButton) {
            prevButton.disabled = currentPage <= 1;
        }

        if (nextButton) {
            nextButton.disabled = currentPage >= lastPage;
        }
    };

    const updateBanner = () => {
        if (!banner || !bannerText) {
            return;
        }

        banner.hidden = !state.subjectId;
        bannerText.textContent = state.subjectId
            ? `Showing logs for ${state.subjectType ? shortType(state.subjectType) : 'subject'} #${state.subjectId}.`
            : '';
    };

    const syncUrl = () => {
        const params = new URLSearchParams();

        if (state.subjectId) {
            params.set('subject_id', String(state.subjectId));
            if (state.subjectType) {
                params.set('subject_type', state.subjectType);
            }
        } else {
            Object.entries(state.filters).forEach(([key, value]) => {
                if (value !== null && value !== undefined && value !== '') {
                    params.set(key, String(value));
                }
            });
        }

        const query = params.toString();
        window.history.replaceState({}, '', `${window.location.pathname}${query ? `?${query}` : ''}`);
    };

    const readFilters = () => ({
        type: filterFields.type?.value || '',
        action: filterFields.action?.value.trim() || '',
        subject_type: filterFields.subject_type?.value || '',
        causer_id: filterFields.causer_id?.value.trim() || '',
        from: filterFields.from?.value || '',
        to: filterFields.to?.value || '',
        search: filterFields.search?.value.trim() || '',
    });

    const loadLogs = async () => {
        setLoading(true);

        try {
            const query = {
                page: state.page,
                row_in_page: state.perPage,
            };

            let response;

            if (state.subjectId) {
                response = await apiGet(`/dashboard/activity-logs/${state.subjectId}`, {
                    ...query,
                    subject_type: state.subjectType,
                });
            } else {
                response = await apiGet('/dashboard/activity-logs', { ...query, ...state.filters });
            }

            state.rows = Array.isArray(response.data) ? response.data : [];
            renderRows();
            updatePagination(response.pagination);
            updateBanner();
        } catch (error) {
            state.rows = [];
            renderRows();
            toast(error.message || 'Unable to load activity logs.', 'error');
        } finally {
            setLoading(false);
        }
    };

    const renderProperties = (properties) => {
        const host = qs('#logDetailProperties');

        if (!host) {
            return;
        }

        if (!properties || typeof properties !== 'object') {
            host.textContent = '—';

            return;
        }

        const oldValues = properties.old && typeof properties.old === 'object' ? properties.old : null;
        const newValues = properties.new && typeof properties.new === 'object' ? properties.new : null;

        if (!oldValues && !newValues) {
            host.innerHTML = `<pre class="dash-code">${escapeHtml(JSON.stringify(properties, null, 2))}</pre>`;

            return;
        }

        const keys = Array.from(new Set([
            ...Object.keys(oldValues ?? {}),
            ...Object.keys(newValues ?? {}),
        ]));

        host.innerHTML = `
            <table class="dash-diff-table">
                <thead><tr><th>Field</th><th>Old value</th><th>New value</th></tr></thead>
                <tbody>
                    ${keys.map((key) => `
                        <tr>
                            <td>${escapeHtml(key)}</td>
                            <td>${escapeHtml(String(oldValues?.[key] ?? '—'))}</td>
                            <td>${escapeHtml(String(newValues?.[key] ?? '—'))}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    };

    const openDetails = (log) => {
        state.detailLog = log;
        qs('#logDetailTime').textContent = formatDateTime(log.created_at);
        qs('#logDetailType').textContent = log.log_type || '—';
        qs('#logDetailAction').textContent = log.action || '—';
        qs('#logDetailDescription').textContent = log.description || '—';
        qs('#logDetailCauser').textContent = log.causer?.name || 'System';
        qs('#logDetailSubject').textContent = log.subject?.id
            ? `${shortType(log.subject.type)} #${log.subject.id}`
            : '—';
        qs('#logDetailIp').textContent = log.ip_address || '—';
        renderProperties(log.properties);

        openModal('logDetailsModal');
    };

    const exportSubject = async (log) => {
        if (!log.subject?.id) {
            return;
        }

        try {
            const response = await apiGet(`/dashboard/activity-logs/${log.subject.id}/export`);
            const fileName = response.data?.file_name;

            if (!fileName) {
                toast('The export file could not be created.', 'error');

                return;
            }

            await apiDownload(`/dashboard/activity-logs/export/${fileName}/download`, fileName);
            toast(`Exported ${fileName}.`);
        } catch (error) {
            toast(error.message || 'Unable to export activity logs.', 'error');
        }
    };

    const runAction = async (button) => {
        const log = state.rows.find((item) => String(item.id) === button.dataset.id);

        if (!log) {
            return;
        }

        if (button.dataset.logAction === 'view') {
            openDetails(log);

            return;
        }

        if (button.dataset.logAction === 'export') {
            button.disabled = true;

            try {
                await exportSubject(log);
            } finally {
                button.disabled = false;
            }
        }
    };

    const applySubjectFromUrl = () => {
        const params = new URLSearchParams(window.location.search);

        state.subjectId = params.get('subject_id');
        state.subjectType = params.get('subject_type') || '';
        state.filters = {
            type: params.get('type') || '',
            action: params.get('action') || '',
            subject_type: params.get('subject_type') || '',
            causer_id: params.get('causer_id') || '',
            from: params.get('from') || '',
            to: params.get('to') || '',
            search: params.get('search') || '',
        };

        Object.entries(filterFields).forEach(([key, input]) => {
            if (input && state.filters[key] !== undefined) {
                input.value = state.filters[key];
            }
        });
    };

    const resetFilters = () => {
        state.subjectId = null;
        state.subjectType = '';
        state.filters = {};
        state.page = 1;
        state.perPage = 15;
        filtersForm?.reset();
        syncUrl();
        loadLogs();
    };

    filtersForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        state.subjectId = null;
        state.subjectType = '';
        state.filters = readFilters();
        state.page = 1;
        syncUrl();
        loadLogs();
    });

    qs('[data-log-reset]')?.addEventListener('click', resetFilters);
    qs('[data-log-refresh]')?.addEventListener('click', loadLogs);

    pageSizeSelect?.addEventListener('change', () => {
        state.perPage = Number(pageSizeSelect.value) || 15;
        state.page = 1;
        loadLogs();
    });

    qsa('[data-log-range]').forEach((button) => {
        button.addEventListener('click', () => {
            const range = button.dataset.logRange;
            const today = new Date();
            const from = new Date(today);

            if (range === '7') {
                from.setDate(from.getDate() - 6);
            } else if (range === '30') {
                from.setDate(from.getDate() - 29);
            }

            const toInput = filterFields.to;
            const fromInput = filterFields.from;

            if (fromInput) {
                fromInput.value = from.toISOString().slice(0, 10);
            }

            if (toInput) {
                toInput.value = today.toISOString().slice(0, 10);
            }

            state.subjectId = null;
            state.subjectType = '';
            state.filters = readFilters();
            state.page = 1;
            syncUrl();
            loadLogs();
        });
    });

    copyButton?.addEventListener('click', async () => {
        if (!state.detailLog) {
            return;
        }

        try {
            await navigator.clipboard.writeText(JSON.stringify(state.detailLog, null, 2));
            toast('Activity log copied.');
        } catch (error) {
            toast('Unable to copy the activity log.', 'error');
        }
    });
    qs('[data-log-clear-subject]')?.addEventListener('click', () => {
        state.subjectId = null;
        state.subjectType = '';
        state.page = 1;
        syncUrl();
        loadLogs();
    });

    prevButton?.addEventListener('click', () => {
        if (state.page > 1) {
            state.page -= 1;
            loadLogs();
        }
    });

    nextButton?.addEventListener('click', () => {
        if (state.page < state.lastPage) {
            state.page += 1;
            loadLogs();
        }
    });

    tableBody.addEventListener('click', (event) => {
        const button = event.target.closest('[data-log-action]');

        if (button) {
            runAction(button);
        }
    });

    qsa('#logDetailsModal [data-close-modal]').forEach((button) => {
        button.addEventListener('click', () => closeModal('logDetailsModal'));
    });

    applySubjectFromUrl();
    loadLogs();
};
