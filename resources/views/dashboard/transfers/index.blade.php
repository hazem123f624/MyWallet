@extends('dashboard.layouts.app')

@section('page', 'transfers')

@section('content')
    <x-dashboard.page-header
        title="Transfers"
        description="Monitor money transfers, provider responses, and failures."
    >
        <x-slot:actions>
            <button class="dash-button dash-button-ghost" type="button" data-transfer-refresh>Refresh</button>
        </x-slot:actions>
    </x-dashboard.page-header>

    <section class="dash-stats" aria-label="Transfer statistics">
        <x-dashboard.stat-card label="Total transfers" value-id="transferStatTotal" tone="brand" hint="All requests" icon="send" />
        <x-dashboard.stat-card label="Pending" value-id="transferStatPending" tone="warning" hint="Awaiting provider" icon="activity" />
        <x-dashboard.stat-card label="Successful" value-id="transferStatSuccessful" tone="success" hint="Completed transfers" icon="check" />
        <x-dashboard.stat-card label="Failed" value-id="transferStatFailed" tone="danger" hint="Failed transfers" icon="warning" />
        <x-dashboard.stat-card label="Reversed" value-id="transferStatReversed" tone="warning" hint="Provider reversals" icon="restore" />
        <x-dashboard.stat-card label="Refunded" value-id="transferStatRefunded" tone="brand" hint="Returned to wallets" icon="refresh" />
    </section>

    <section class="dash-panel">
        <div class="dash-panel-toolbar">
            <div class="dash-toolbar-actions">
                <label class="dash-select-wrap">
                    <span class="sr-only">Filter status</span>
                    <select id="transferStatusFilter" class="dash-select">
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="successful">Successful</option>
                        <option value="failed">Failed</option>
                        <option value="reversed">Reversed</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </label>

                <label class="dash-search">
                    <span class="sr-only">Search transfers</span>
                    <x-dashboard.icon name="search" />
                    <input type="search" id="transferSearch" placeholder="Reference or beneficiary..." autocomplete="off">
                </label>
            </div>

            <div class="dash-toolbar-actions">
                <label class="dash-search">
                    <span class="sr-only">Reference</span>
                    <x-dashboard.icon name="search" />
                    <input type="search" id="transferReference" placeholder="Exact reference..." autocomplete="off">
                </label>
                <input class="dash-select" type="date" id="transferDateFrom" aria-label="From date">
                <input class="dash-select" type="date" id="transferDateTo" aria-label="To date">
            </div>
        </div>

        <div class="dash-table-wrap">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Sub Wallet</th>
                        <th>Beneficiary</th>
                        <th>Status</th>
                        <th class="dash-col-date">Date</th>
                        <th class="dash-table-actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="transfersTableBody"></tbody>
            </table>

            <div class="dash-table-loading" id="transfersLoading" hidden>
                <span class="dash-spinner"></span>
                <span>Loading transfers...</span>
            </div>

            <x-dashboard.empty-state
                id="transfersEmpty"
                title="No transfers found"
                message="Transfer requests will appear here."
            />
        </div>

        <footer class="dash-table-footer">
            <span id="transfersPageInfo">Page 1 of 1</span>
            <div class="dash-pagination">
                <button class="dash-button dash-button-ghost" type="button" id="transfersPrevPage" disabled>Previous</button>
                <button class="dash-button dash-button-ghost" type="button" id="transfersNextPage" disabled>Next</button>
            </div>
        </footer>
    </section>

    <x-dashboard.modal id="transferDetailsModal" title="Transfer details">
        <dl class="dash-detail-list">
            <div><dt>Reference</dt><dd id="transferDetailReference">—</dd></div>
            <div><dt>User</dt><dd id="transferDetailUser">—</dd></div>
            <div><dt>Status</dt><dd id="transferDetailStatus">—</dd></div>
            <div><dt>Amount</dt><dd id="transferDetailAmount">—</dd></div>
            <div><dt>Sub Wallet</dt><dd id="transferDetailSubWallet">—</dd></div>
            <div><dt>Beneficiary</dt><dd id="transferDetailBeneficiary">—</dd></div>
            <div><dt>Provider reference</dt><dd id="transferDetailProviderReference">—</dd></div>
            <div><dt>Requested</dt><dd id="transferDetailRequested">—</dd></div>
            <div><dt>Completed</dt><dd id="transferDetailCompleted">—</dd></div>
        </dl>

        <p class="dash-form-error" id="transferDetailFailure" hidden></p>

        <div class="dash-section">
            <h3>Reversal / refund</h3>
            <div id="transferDetailReversal" class="dash-muted-text">No reversal or refund recorded.</div>
        </div>

        <div class="dash-section">
            <h3>Lifecycle timeline</h3>
            <div class="user-timeline" id="transferDetailTimeline"></div>
        </div>

        <x-slot:footer>
            <button class="dash-button dash-button-ghost" type="button" id="transferDetailSync">Sync status</button>
            <button class="dash-button dash-button-ghost" type="button" data-close-modal>Close</button>
        </x-slot>
    </x-dashboard.modal>
@endsection
