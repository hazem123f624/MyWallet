import { apiGet } from '../core/api.js?v=4';
import { debounce, escapeHtml, qs } from '../core/dom.js?v=4';
import { toast } from '../core/toast.js?v=4';

const state = {
    page: 1,
    lastPage: 1,
    perPage: 10,
    rows: [],
    search: '',
};

export const initModelsPage = () => {
    const tableBody = qs('#modelsTableBody');
    const emptyState = qs('#modelsEmpty');
    const loadingState = qs('#modelsLoading');
    const pageInfo = qs('#modelsPageInfo');
    const prevButton = qs('#modelsPrevPage');
    const nextButton = qs('#modelsNextPage');
    const searchInput = qs('#modelSearch');

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

        title.textContent = state.search !== '' ? 'No matching models' : 'No permission models found';
        message.textContent = state.search !== ''
            ? 'Try a different search.'
            : 'Permission models are seeded by the platform configuration.';
    };

    const renderRows = () => {
        tableBody.innerHTML = state.rows.map((model) => `
            <tr>
                <td>
                    <span class="dash-table-name">${escapeHtml(model.title || `Model #${model.id}`)}</span>
                    <span class="dash-table-subtext">#${escapeHtml(model.id)}</span>
                </td>
                <td>
                    <div class="dash-chip-list">
                        ${(model.permissions ?? []).map((permission) => `
                            <span class="dash-chip">${escapeHtml(permission.title || permission.name || 'Permission')}</span>
                        `).join('') || '<span class="dash-muted-text">No permissions</span>'}
                    </div>
                </td>
            </tr>
        `).join('');

        updateEmptyState();
    };

    const updatePagination = (pagination = {}) => {
        const currentPage = Number(pagination.current_page ?? 1);
        const lastPage = Math.max(1, Number(pagination.last_page ?? 1));
        const total = Number(pagination.total ?? 0);

        state.page = currentPage;
        state.lastPage = lastPage;

        if (pageInfo) {
            pageInfo.textContent = `Page ${currentPage} of ${lastPage} · ${total} model${total === 1 ? '' : 's'}`;
        }

        if (prevButton) {
            prevButton.disabled = currentPage <= 1;
        }

        if (nextButton) {
            nextButton.disabled = currentPage >= lastPage;
        }
    };

    const loadModels = async () => {
        setLoading(true);

        try {
            const response = await apiGet('/dashboard/models', {
                page: state.page,
                per_page: state.perPage,
                search: state.search,
            });

            state.rows = Array.isArray(response.data) ? response.data : [];
            renderRows();
            updatePagination(response.pagination);
        } catch (error) {
            state.rows = [];
            renderRows();
            toast(error.message || 'Unable to load permission models.', 'error');
        } finally {
            setLoading(false);
        }
    };

    const applySearch = debounce(() => {
        state.search = searchInput?.value.trim() ?? '';
        state.page = 1;
        loadModels();
    }, 350);

    searchInput?.addEventListener('input', applySearch);

    prevButton?.addEventListener('click', () => {
        if (state.page > 1) {
            state.page -= 1;
            loadModels();
        }
    });

    nextButton?.addEventListener('click', () => {
        if (state.page < state.lastPage) {
            state.page += 1;
            loadModels();
        }
    });

    qs('[data-model-refresh]')?.addEventListener('click', loadModels);

    loadModels();
};
