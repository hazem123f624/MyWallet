@extends('dashboard.layouts.app')

@section('page', 'activity-logs')

@section('content')
    <x-dashboard.page-header
        title="Activity Logs"
        description="Review the audit trail for administrators, users, roles, and platform events."
    >
        <x-slot:actions>
            <button class="dash-button dash-button-ghost" type="button" data-log-refresh>
                Refresh
            </button>
        </x-slot:actions>
    </x-dashboard.page-header>

    <section class="dash-panel">
        <form class="dash-panel-body dash-form" id="logFilters">
            <div class="dash-form-grid dash-log-filters">
                <div class="dash-field">
                    <label for="logType">Log type</label>
                    <select id="logType" name="type">
                        <option value="">All types</option>
                        <option value="admin">Admin</option>
                        <option value="user">User</option>
                    </select>
                </div>

                <div class="dash-field">
                    <label for="logAction">Action</label>
                    <select id="logAction" name="action">
                        <option value="">All actions</option>
                        <option value="created">Created</option>
                        <option value="updated">Updated</option>
                        <option value="deleted">Deleted</option>
                        <option value="restored">Restored</option>
                        <option value="login">Login</option>
                        <option value="logout">Logout</option>
                        <option value="exported_data">Exported data</option>
                    </select>
                </div>

                <div class="dash-field">
                    <label for="logSubjectType">Subject type</label>
                    <select id="logSubjectType" name="subject_type">
                        <option value="">All subjects</option>
                        <option value="Modules\Users\Models\User">User</option>
                        <option value="Modules\Admins\Models\Admins">Administrator</option>
                        <option value="Modules\Admins\Models\Roles">Role</option>
                    </select>
                </div>

                <div class="dash-field">
                    <label for="logCauserId">Causer ID</label>
                    <input id="logCauserId" name="causer_id" type="number" min="1">
                </div>

                <div class="dash-field">
                    <label for="logFrom">From</label>
                    <input id="logFrom" name="from" type="date">
                </div>

                <div class="dash-field">
                    <label for="logTo">To</label>
                    <input id="logTo" name="to" type="date">
                </div>

                <div class="dash-field">
                    <label for="logSearch">Search</label>
                    <input id="logSearch" name="search" type="search" placeholder="Search descriptions...">
                </div>

                <div class="dash-field">
                    <label for="logPageSize">Rows per page</label>
                    <select id="logPageSize" name="row_in_page">
                        <option value="15">15 rows</option>
                        <option value="30">30 rows</option>
                        <option value="50">50 rows</option>
                    </select>
                </div>

                <div class="dash-field dash-field-actions">
                    <label>&nbsp;</label>
                    <div class="dash-toolbar-actions">
                        <button class="dash-button dash-button-primary" type="submit">Apply filters</button>
                        <button class="dash-button dash-button-ghost" type="button" data-log-reset>Reset</button>
                    </div>
                </div>
            </div>

            <div class="dash-quick-ranges">
                <span>Quick range:</span>
                <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-log-range="today">Today</button>
                <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-log-range="7">Last 7 days</button>
                <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-log-range="30">Last 30 days</button>
            </div>
        </form>

        <div class="dash-subject-banner" id="logSubjectBanner" hidden>
            <span id="logSubjectText"></span>
            <button class="dash-button dash-button-ghost dash-button-xs" type="button" data-log-clear-subject>
                Clear subject
            </button>
        </div>

        <div class="dash-table-wrap">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th class="dash-col-date">Time</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>Causer</th>
                        <th>Subject</th>
                        <th>IP Address</th>
                        <th class="dash-table-actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="logsTableBody"></tbody>
            </table>

            <div class="dash-table-loading" id="logsLoading" hidden>
                <span class="dash-spinner"></span>
                <span>Loading activity logs...</span>
            </div>

            <x-dashboard.empty-state
                id="logsEmpty"
                title="No activity logs found"
                message="Actions performed in the platform will appear here."
            />
        </div>

        <footer class="dash-table-footer">
            <span id="logsPageInfo">Page 1 of 1</span>

            <div class="dash-pagination">
                <button class="dash-button dash-button-ghost" type="button" id="logsPrevPage" disabled>
                    Previous
                </button>
                <button class="dash-button dash-button-ghost" type="button" id="logsNextPage" disabled>
                    Next
                </button>
            </div>
        </footer>
    </section>

    <x-dashboard.modal id="logDetailsModal" title="Activity log details">
        <dl class="dash-detail-list">
            <div><dt>Time</dt><dd id="logDetailTime">—</dd></div>
            <div><dt>Type</dt><dd id="logDetailType">—</dd></div>
            <div><dt>Action</dt><dd id="logDetailAction">—</dd></div>
            <div><dt>Description</dt><dd id="logDetailDescription">—</dd></div>
            <div><dt>Causer</dt><dd id="logDetailCauser">—</dd></div>
            <div><dt>Subject</dt><dd id="logDetailSubject">—</dd></div>
            <div><dt>IP address</dt><dd id="logDetailIp">—</dd></div>
        </dl>

        <div class="dash-section">
            <h3>Changed properties</h3>
            <div class="dash-diff" id="logDetailProperties">—</div>
        </div>

        <x-slot:footer>
            <button class="dash-button dash-button-ghost" type="button" id="logCopy">Copy log</button>
            <button class="dash-button dash-button-ghost" type="button" data-close-modal>Close</button>
        </x-slot>
    </x-dashboard.modal>
@endsection

