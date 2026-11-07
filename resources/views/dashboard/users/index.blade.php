@extends('dashboard.layouts.app')

@section('page', 'users')

@section('content')
    <x-dashboard.page-header
        title="Users"
        description="Review accounts, control access, and manage the user recycle bin."
    >
        <x-slot:actions>
            <button class="dash-button dash-button-ghost" type="button" data-users-refresh>
                Refresh
            </button>
        </x-slot:actions>
    </x-dashboard.page-header>

    @unless($canManageStatus)
        <p class="dash-inline-note">You can review accounts here, but only a Super Admin can suspend or activate users.</p>
    @endunless

    <section class="dash-stats" aria-label="User statistics">
        <x-dashboard.stat-card label="Total users" value-id="statTotal" tone="brand" hint="Accounts in the system">
        </x-dashboard.stat-card>

        <x-dashboard.stat-card label="Active users" value-id="statActive" tone="success" hint="Can access the app">
        </x-dashboard.stat-card>

        <x-dashboard.stat-card label="Suspended users" value-id="statSuspended" tone="warning" hint="Access is blocked">
        </x-dashboard.stat-card>

        <x-dashboard.stat-card label="Deleted users" value-id="statTrashed" tone="danger" hint="In the recycle bin">
        </x-dashboard.stat-card>
    </section>

    <section class="dash-panel">
        <div class="dash-panel-toolbar">
            <div class="dash-tabs" role="tablist" aria-label="User lists">
                <button class="dash-tab active" type="button" data-users-mode="active" role="tab" aria-selected="true">
                    Active users
                </button>
                <button class="dash-tab" type="button" data-users-mode="trash" role="tab" aria-selected="false">
                    Recycle bin
                </button>
            </div>

            <div class="dash-toolbar-actions">
                <label class="dash-select-wrap">
                    <span class="sr-only">Filter users</span>
                    <select id="userStatusFilter" class="dash-select">
                        <option value="">All statuses</option>
                        <option value="1">Active</option>
                        <option value="0">Suspended</option>
                    </select>
                </label>

                <label class="dash-select-wrap">
                    <span class="sr-only">Rows per page</span>
                    <select id="userPageSize" class="dash-select">
                        <option value="10">10 rows</option>
                        <option value="25">25 rows</option>
                        <option value="50">50 rows</option>
                    </select>
                </label>

                <label class="dash-select-wrap">
                    <span class="sr-only">Sort users</span>
                    <select id="userSort" class="dash-select">
                        <option value="created_at:desc">Newest first</option>
                        <option value="created_at:asc">Oldest first</option>
                        <option value="name:asc">Name A–Z</option>
                        <option value="name:desc">Name Z–A</option>
                        <option value="is_active:desc">Active first</option>
                        <option value="is_active:asc">Suspended first</option>
                    </select>
                </label>

                <label class="dash-search">
                    <span class="sr-only">Search users</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                    </svg>
                    <input type="search" id="userSearch" placeholder="Search name, email, or phone..." autocomplete="off">
                </label>
            </div>
        </div>

        <div class="dash-table-wrap">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="dash-table-actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody"></tbody>
            </table>

            <div class="dash-table-loading" id="usersLoading" hidden>
                <span class="dash-spinner"></span>
                <span>Loading users...</span>
            </div>

            <x-dashboard.empty-state
                id="usersEmpty"
                title="No users found"
                message="Try another filter or switch to the recycle bin."
            />
        </div>

        <footer class="dash-table-footer">
            <span id="usersPageInfo">Page 1 of 1</span>

            <div class="dash-pagination">
                <button class="dash-button dash-button-ghost" type="button" id="usersPrevPage" disabled>
                    Previous
                </button>
                <button class="dash-button dash-button-ghost" type="button" id="usersNextPage" disabled>
                    Next
                </button>
            </div>
        </footer>
    </section>

    <x-dashboard.modal id="userDetailsModal" title="User details">
        <dl class="dash-detail-list">
            <div><dt>Name</dt><dd id="detailName">—</dd></div>
            <div><dt>Email</dt><dd id="detailEmail">—</dd></div>
            <div><dt>Phone</dt><dd id="detailPhone">—</dd></div>
            <div><dt>Country code</dt><dd id="detailCountry">—</dd></div>
            <div><dt>Status</dt><dd id="detailStatus">—</dd></div>
            <div><dt>Created</dt><dd id="detailCreated">—</dd></div>
            <div><dt>Last login</dt><dd id="detailLastLogin">—</dd></div>
        </dl>

        <x-slot:footer>
            <a class="dash-button dash-button-ghost" id="userDetailActivity" href="{{ route('dashboard.activity-logs') }}">
                View activity
            </a>
            <button class="dash-button dash-button-ghost" type="button" data-close-modal>Close</button>
        </x-slot>
    </x-dashboard.modal>
@endsection

@push('scripts')
    <script>
        window.setTimeout(function () {
            const loading = document.getElementById('usersLoading');
            const body = document.getElementById('usersTableBody');
            const empty = document.getElementById('usersEmpty');

            if (!loading || loading.hidden || body?.children.length) {
                return;
            }

            loading.hidden = true;

            if (empty) {
                empty.hidden = false;
                empty.querySelector('h3').textContent = 'Unable to load users';
                empty.querySelector('p').textContent = 'Refresh the page to try again.';
            }
        }, 8000);
    </script>
@endpush
