@extends('dashboard.layouts.app')

@section('page', 'beneficiaries')

@section('content')
    <x-dashboard.page-header
        title="Beneficiaries"
        description="Review the external beneficiaries users have saved."
    >
        <x-slot:actions>
            <button class="dash-button dash-button-ghost" type="button" data-admin-beneficiary-refresh>Refresh</button>
        </x-slot:actions>
    </x-dashboard.page-header>

    <section class="dash-panel">
        <div class="dash-panel-toolbar">
            <label class="dash-search">
                <span class="sr-only">Search beneficiaries</span>
                <x-dashboard.icon name="search" />
                <input type="search" id="adminBeneficiarySearch" placeholder="Name, bank, or last four..." autocomplete="off">
            </label>

            <label class="dash-search">
                <span class="sr-only">Filter by user</span>
                <x-dashboard.icon name="user" />
                <input type="number" id="adminBeneficiaryUserId" placeholder="User ID" min="1">
            </label>
        </div>

        <div class="dash-table-wrap">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Beneficiary</th>
                        <th>User</th>
                        <th>Bank</th>
                        <th>Account</th>
                        <th>Status</th>
                        <th class="dash-col-date">Created</th>
                        <th class="dash-table-actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="adminBeneficiariesTableBody"></tbody>
            </table>

            <div class="dash-table-loading" id="adminBeneficiariesLoading" hidden>
                <span class="dash-spinner"></span>
                <span>Loading beneficiaries...</span>
            </div>

            <x-dashboard.empty-state
                id="adminBeneficiariesEmpty"
                title="No beneficiaries found"
                message="Saved beneficiaries will appear here."
            />
        </div>

        <footer class="dash-table-footer">
            <span id="adminBeneficiariesPageInfo">Page 1 of 1</span>
            <div class="dash-pagination">
                <button class="dash-button dash-button-ghost" type="button" id="adminBeneficiariesPrevPage" disabled>Previous</button>
                <button class="dash-button dash-button-ghost" type="button" id="adminBeneficiariesNextPage" disabled>Next</button>
            </div>
        </footer>
    </section>

    <x-dashboard.modal id="adminBeneficiaryDetailsModal" title="Beneficiary details">
        <dl class="dash-detail-list">
            <div><dt>Name</dt><dd id="adminBeneficiaryName">—</dd></div>
            <div><dt>User</dt><dd id="adminBeneficiaryUser">—</dd></div>
            <div><dt>Bank</dt><dd id="adminBeneficiaryBank">—</dd></div>
            <div><dt>Account</dt><dd id="adminBeneficiaryAccount">—</dd></div>
            <div><dt>IBAN</dt><dd id="adminBeneficiaryIban">—</dd></div>
            <div><dt>Status</dt><dd id="adminBeneficiaryStatus">—</dd></div>
            <div><dt>Created</dt><dd id="adminBeneficiaryCreated">—</dd></div>
        </dl>

        <x-slot:footer>
            <button class="dash-button dash-button-ghost" type="button" data-close-modal>Close</button>
        </x-slot>
    </x-dashboard.modal>
@endsection
