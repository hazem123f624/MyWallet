@extends('user.layouts.app')

@section('page', 'transactions')

@section('content')
    <x-dashboard.page-header
        title="Transaction History"
        description="Every transfer you have sent to an external beneficiary."
    >
        <x-slot:actions>
            <button class="dash-button dash-button-ghost" type="button" data-transactions-refresh>Refresh</button>
        </x-slot:actions>
    </x-dashboard.page-header>

    <section class="dash-panel">
        <div class="dash-panel-heading">
            <div>
                <h2>External transfers</h2>
                <p>Internal wallet moves are private and appear on the Wallets page only.</p>
            </div>
        </div>

        <div class="dash-panel-toolbar">
            <div class="dash-toolbar-actions">
                <label class="dash-search">
                    <span class="sr-only">Search by reference</span>
                    <x-dashboard.icon name="search" />
                    <input type="search" id="transactionReference" placeholder="Search reference..." autocomplete="off">
                </label>

                <label class="dash-select-wrap">
                    <span class="sr-only">Filter status</span>
                    <select id="transactionStatus" class="dash-select">
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="successful">Successful</option>
                        <option value="failed">Failed</option>
                        <option value="reversed">Reversed</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </label>
            </div>

            <div class="dash-toolbar-actions">
                <input class="dash-select" type="date" id="transactionDateFrom" aria-label="From date">
                <input class="dash-select" type="date" id="transactionDateTo" aria-label="To date">
                <button class="dash-button dash-button-ghost" type="button" data-transactions-clear>Clear</button>
            </div>
        </div>

        <div class="dash-table-wrap">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Beneficiary</th>
                        <th>Source</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th class="dash-col-date">Date</th>
                        <th class="dash-table-actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="transactionsTableBody"></tbody>
            </table>

            <div class="dash-table-loading" id="transactionsLoading" hidden>
                <span class="dash-spinner"></span>
                <span>Loading transactions...</span>
            </div>

            <x-dashboard.empty-state
                id="transactionsEmpty"
                title="No transactions found"
                message="Your external transfers will appear here."
            />
        </div>

        <footer class="dash-table-footer">
            <span id="transactionsPageInfo">Page 1 of 1</span>
            <div class="dash-pagination">
                <button class="dash-button dash-button-ghost" type="button" id="transactionsPrevPage" disabled>Previous</button>
                <button class="dash-button dash-button-ghost" type="button" id="transactionsNextPage" disabled>Next</button>
            </div>
        </footer>
    </section>

    <x-user.transfer-details-modal />
@endsection
