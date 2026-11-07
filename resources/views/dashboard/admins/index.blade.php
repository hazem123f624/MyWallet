@extends('dashboard.layouts.app')

@section('page', 'admins')

@section('content')
    <x-dashboard.page-header
        title="Admin Management"
        description="Manage dashboard administrators, their roles, and account access."
    >
        <x-slot:actions>
            <button class="dash-button dash-button-primary" type="button" data-admin-create>
                Add admin
            </button>
        </x-slot:actions>
    </x-dashboard.page-header>

    <section class="dash-panel">
        <div class="dash-panel-toolbar">
            <div class="dash-tabs" role="tablist" aria-label="Administrator lists">
                <button class="dash-tab active" type="button" data-admins-mode="active" role="tab" aria-selected="true">
                    Active administrators
                </button>
                <button class="dash-tab" type="button" data-admins-mode="trash" role="tab" aria-selected="false">
                    Recycle bin
                </button>
            </div>

            <div class="dash-toolbar-actions">
                <label class="dash-select-wrap">
                    <span class="sr-only">Filter admins</span>
                    <select id="adminStatusFilter" class="dash-select">
                        <option value="">All statuses</option>
                        <option value="1">Active</option>
                        <option value="0">Suspended</option>
                    </select>
                </label>

                <label class="dash-select-wrap">
                    <span class="sr-only">Filter by role</span>
                    <select id="adminRoleFilter" class="dash-select">
                        <option value="">All roles</option>
                    </select>
                </label>

                <label class="dash-select-wrap">
                    <span class="sr-only">Rows per page</span>
                    <select id="adminPageSize" class="dash-select">
                        <option value="10">10 rows</option>
                        <option value="25">25 rows</option>
                        <option value="50">50 rows</option>
                    </select>
                </label>

                <label class="dash-select-wrap">
                    <span class="sr-only">Sort admins</span>
                    <select id="adminSort" class="dash-select">
                        <option value="created_at:desc">Newest first</option>
                        <option value="created_at:asc">Oldest first</option>
                        <option value="name:asc">Name A–Z</option>
                        <option value="name:desc">Name Z–A</option>
                        <option value="is_active:desc">Active first</option>
                        <option value="is_active:asc">Suspended first</option>
                    </select>
                </label>
            </div>

            <div class="dash-toolbar-actions">
                <label class="dash-search">
                    <span class="sr-only">Search admins</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                    </svg>
                    <input type="search" id="adminSearch" placeholder="Search name, email, or phone..." autocomplete="off">
                </label>

                <button class="dash-button dash-button-ghost" type="button" data-admin-refresh>
                    Refresh
                </button>
            </div>
        </div>

        <div class="dash-table-wrap">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Admin</th>
                        <th>Contact</th>
                        <th>Roles</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="dash-table-actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="adminsTableBody"></tbody>
            </table>

            <div class="dash-table-loading" id="adminsLoading" hidden>
                <span class="dash-spinner"></span>
                <span>Loading admins...</span>
            </div>

            <x-dashboard.empty-state
                id="adminsEmpty"
                title="No administrators found"
                message="Try another search or create a new administrator."
            />
        </div>

        <footer class="dash-table-footer">
            <span id="adminsPageInfo">Page 1 of 1</span>

            <div class="dash-pagination">
                <button class="dash-button dash-button-ghost" type="button" id="adminsPrevPage" disabled>
                    Previous
                </button>
                <button class="dash-button dash-button-ghost" type="button" id="adminsNextPage" disabled>
                    Next
                </button>
            </div>
        </footer>
    </section>

    <x-dashboard.modal id="adminFormModal" title="Add administrator">
        <form id="adminForm" class="dash-form">
            <input type="hidden" id="adminId" name="id">

            <div class="dash-form-grid">
                <div class="dash-field">
                    <label for="adminName">Name</label>
                    <input id="adminName" name="name" type="text" required>
                </div>

                <div class="dash-field">
                    <label for="adminEmail">Email</label>
                    <input id="adminEmail" name="email" type="email" required>
                </div>

                <div class="dash-field">
                    <label for="adminPhone">Phone</label>
                    <input id="adminPhone" name="phone" type="text" required>
                </div>

                <div class="dash-field">
                    <label for="adminJobTitle">Job title</label>
                    <input id="adminJobTitle" name="job_title" type="text" required>
                </div>

                <div class="dash-field">
                    <label for="adminAvatar">Avatar</label>
                    <input id="adminAvatar" name="avatar" type="file" accept="image/jpeg,image/png,image/jpg,image/gif">
                    <p class="dash-table-subtext">JPG, PNG, or GIF. Maximum 2 MB.</p>
                </div>

                <div class="dash-field">
                    <label for="adminPassword">Password</label>
                    <input id="adminPassword" name="password" type="password" autocomplete="new-password">
                </div>

                <div class="dash-field">
                    <label for="adminPasswordConfirmation">Confirm password</label>
                    <input id="adminPasswordConfirmation" name="password_confirmation" type="password" autocomplete="new-password">
                </div>
            </div>

            <div class="dash-field">
                <label>Roles</label>
                <div class="dash-checkbox-grid" id="adminRolesOptions"></div>
            </div>

            <label class="dash-checkbox">
                <input id="adminIsActive" name="is_active" type="checkbox" checked>
                <span>Account is active</span>
            </label>

            <p class="dash-form-error" id="adminFormError" hidden></p>

            <x-slot:footer>
                <button class="dash-button dash-button-ghost" type="button" data-close-modal>Cancel</button>
                <button class="dash-button dash-button-primary" type="submit" form="adminForm" id="adminSubmit">Save admin</button>
            </x-slot:footer>
        </form>
    </x-dashboard.modal>

    <x-dashboard.modal id="adminDetailsModal" title="Administrator details">
        <dl class="dash-detail-list">
            <div><dt>Name</dt><dd id="adminDetailName">—</dd></div>
            <div><dt>Email</dt><dd id="adminDetailEmail">—</dd></div>
            <div><dt>Phone</dt><dd id="adminDetailPhone">—</dd></div>
            <div><dt>Job title</dt><dd id="adminDetailJobTitle">—</dd></div>
            <div><dt>Status</dt><dd id="adminDetailStatus">—</dd></div>
            <div><dt>Last login</dt><dd id="adminDetailLastLogin">—</dd></div>
            <div><dt>Created</dt><dd id="adminDetailCreated">—</dd></div>
        </dl>

        <div class="dash-section">
            <h3>Roles</h3>
            <div class="dash-chip-list" id="adminDetailRoles"></div>
        </div>

        <p class="dash-form-error" id="adminDetailError" hidden></p>

        <x-slot:footer>
            <a class="dash-button dash-button-ghost" id="adminDetailActivity" href="{{ route('dashboard.activity-logs') }}">
                View activity
            </a>
            <button class="dash-button dash-button-ghost" type="button" data-close-modal>Close</button>
        </x-slot>
    </x-dashboard.modal>
@endsection

@push('scripts')
    <script>
        window.setTimeout(function () {
            const loading = document.getElementById('adminsLoading');
            const body = document.getElementById('adminsTableBody');
            const empty = document.getElementById('adminsEmpty');

            if (!loading || loading.hidden || body?.children.length) {
                return;
            }

            loading.hidden = true;

            if (empty) {
                empty.hidden = false;
                empty.querySelector('h3').textContent = 'Unable to load administrators';
                empty.querySelector('p').textContent = 'Refresh the page to try again.';
            }
        }, 8000);
    </script>
@endpush
