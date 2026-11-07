@extends('dashboard.layouts.app')

@section('page', 'roles')

@section('content')
    <x-dashboard.page-header
        title="Roles & Permissions"
        description="Create roles, assign module permissions, and manage role membership."
    >
        <x-slot:actions>
            <button class="dash-button dash-button-primary" type="button" data-role-create>
                Add role
            </button>
        </x-slot:actions>
    </x-dashboard.page-header>

    <section class="dash-panel">
        <div class="dash-panel-toolbar">
            <div class="dash-tabs" role="tablist" aria-label="Role lists">
                <button class="dash-tab active" type="button" data-roles-mode="active" role="tab" aria-selected="true">
                    Active roles
                </button>
                <button class="dash-tab" type="button" data-roles-mode="trash" role="tab" aria-selected="false">
                    Recycle bin
                </button>
            </div>

            <div class="dash-toolbar-actions">
                <label class="dash-select-wrap">
                    <span class="sr-only">Filter roles</span>
                    <select id="roleStatusFilter" class="dash-select">
                        <option value="">All statuses</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </label>

                <label class="dash-select-wrap">
                    <span class="sr-only">Sort roles</span>
                    <select id="roleSort" class="dash-select">
                        <option value="created_at:desc">Newest first</option>
                        <option value="created_at:asc">Oldest first</option>
                    </select>
                </label>

                <label class="dash-search">
                    <span class="sr-only">Search roles</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                    </svg>
                    <input type="search" id="roleSearch" placeholder="Search roles..." autocomplete="off">
                </label>

                <button class="dash-button dash-button-ghost" type="button" data-role-refresh>
                    Refresh
                </button>
            </div>
        </div>

        <div class="dash-table-wrap">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Description</th>
                        <th>Permissions</th>
                        <th>Admins</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="dash-table-actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="rolesTableBody"></tbody>
            </table>

            <div class="dash-table-loading" id="rolesLoading" hidden>
                <span class="dash-spinner"></span>
                <span>Loading roles...</span>
            </div>

            <x-dashboard.empty-state
                id="rolesEmpty"
                title="No roles found"
                message="Create the first role to group permissions and administrators."
            />
        </div>

        <footer class="dash-table-footer">
            <span id="rolesPageInfo">Page 1 of 1</span>

            <div class="dash-pagination">
                <button class="dash-button dash-button-ghost" type="button" id="rolesPrevPage" disabled>
                    Previous
                </button>
                <button class="dash-button dash-button-ghost" type="button" id="rolesNextPage" disabled>
                    Next
                </button>
            </div>
        </footer>
    </section>

    <x-dashboard.modal id="roleFormModal" title="Add role">
        <form id="roleForm" class="dash-form">
            <input type="hidden" id="roleId" name="id">

            <div class="dash-form-grid">
                <div class="dash-field">
                    <label for="roleNameEn">English name</label>
                    <input id="roleNameEn" name="name_en" type="text" required>
                </div>

                <div class="dash-field">
                    <label for="roleNameAr">Arabic name</label>
                    <input id="roleNameAr" name="name_ar" type="text" dir="rtl" required>
                </div>
            </div>

            <div class="dash-form-grid">
                <div class="dash-field">
                    <label for="roleDescriptionEn">English description</label>
                    <input id="roleDescriptionEn" name="description_en" type="text">
                </div>

                <div class="dash-field">
                    <label for="roleDescriptionAr">Arabic description</label>
                    <input id="roleDescriptionAr" name="description_ar" type="text" dir="rtl">
                </div>
            </div>

            <div class="dash-field">
                <label>Permissions</label>
                <div class="dash-permission-tools">
                    <label class="dash-search">
                        <span class="sr-only">Search permissions</span>
                        <x-dashboard.icon name="search" />
                        <input type="search" id="rolePermissionSearch" placeholder="Search permissions...">
                    </label>
                    <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-permission-all>Select all</button>
                    <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-permission-none>Clear</button>
                    <span class="dash-badge dash-badge-neutral" id="rolePermissionCount">0 selected</span>
                </div>
                <div class="dash-permission-matrix" id="rolePermissionsOptions"></div>
            </div>

            <p class="dash-form-error" id="roleFormError" hidden></p>

            <x-slot:footer>
                <button class="dash-button dash-button-ghost" type="button" data-close-modal>Cancel</button>
                <button class="dash-button dash-button-primary" type="submit" form="roleForm" id="roleSubmit">Save role</button>
            </x-slot:footer>
        </form>
    </x-dashboard.modal>

    <x-dashboard.modal id="roleDetailsModal" title="Role details">
        <dl class="dash-detail-list">
            <div><dt>Name</dt><dd id="roleDetailName">—</dd></div>
            <div><dt>Description</dt><dd id="roleDetailDescription">—</dd></div>
            <div><dt>Status</dt><dd id="roleDetailStatus">—</dd></div>
            <div><dt>Permissions</dt><dd id="roleDetailPermissionCount">—</dd></div>
            <div><dt>Administrators</dt><dd id="roleDetailAdminCount">—</dd></div>
            <div><dt>Created</dt><dd id="roleDetailCreated">—</dd></div>
        </dl>

        <div class="dash-section">
            <h3>Granted permissions</h3>
            <div id="roleDetailPermissions" class="dash-chip-list"></div>
        </div>

        <div class="dash-section">
            <h3>Assigned administrators</h3>
            <div id="roleDetailMembers" class="dash-member-list"></div>
        </div>

        <div class="dash-section">
            <h3>Assign administrators</h3>
            <div class="dash-toolbar-actions">
                <label class="dash-search">
                    <span class="sr-only">Search available administrators</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                    </svg>
                    <input type="search" id="roleAvailableAdminSearch" placeholder="Search administrators..." autocomplete="off">
                </label>

                <button class="dash-button dash-button-primary dash-button-xs" type="button" id="roleAssignAdmins">
                    Assign selected
                </button>
            </div>

            <div id="roleAvailableAdmins" class="dash-member-list"></div>
        </div>

        <p class="dash-form-error" id="roleDetailError" hidden></p>

        <x-slot:footer>
            <button class="dash-button dash-button-ghost" type="button" data-close-modal>Close</button>
        </x-slot>
    </x-dashboard.modal>
@endsection

