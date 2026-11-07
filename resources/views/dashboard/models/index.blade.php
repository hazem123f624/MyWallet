@extends('dashboard.layouts.app')

@section('page', 'models')

@section('content')
    <x-dashboard.page-header
        title="Permission Models"
        description="The modules and permissions that can be granted to a role."
    >
        <x-slot:actions>
            <button class="dash-button dash-button-ghost" type="button" data-model-refresh>
                Refresh
            </button>
        </x-slot:actions>
    </x-dashboard.page-header>

    <section class="dash-panel">
        <div class="dash-panel-toolbar">
            <label class="dash-search">
                <span class="sr-only">Search models</span>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                </svg>
                <input type="search" id="modelSearch" placeholder="Search models..." autocomplete="off">
            </label>
        </div>

        <div class="dash-table-wrap">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Model</th>
                        <th>Permissions</th>
                    </tr>
                </thead>
                <tbody id="modelsTableBody"></tbody>
            </table>

            <div class="dash-table-loading" id="modelsLoading" hidden>
                <span class="dash-spinner"></span>
                <span>Loading models...</span>
            </div>

            <x-dashboard.empty-state
                id="modelsEmpty"
                title="No permission models found"
                message="Permission models are seeded by the platform configuration."
            />
        </div>

        <footer class="dash-table-footer">
            <span id="modelsPageInfo">Page 1 of 1</span>

            <div class="dash-pagination">
                <button class="dash-button dash-button-ghost" type="button" id="modelsPrevPage" disabled>
                    Previous
                </button>
                <button class="dash-button dash-button-ghost" type="button" id="modelsNextPage" disabled>
                    Next
                </button>
            </div>
        </footer>
    </section>
@endsection

