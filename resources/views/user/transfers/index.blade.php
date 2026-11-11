@extends('user.layouts.app')

@section('page', 'transfers')

@section('content')
    <x-dashboard.page-header
        title="Send Money"
        description="Send money from one of your Sub Wallets to a saved beneficiary."
    >
        <x-slot:actions>
            <a class="dash-button dash-button-ghost" href="{{ route('user.beneficiaries') }}">Manage beneficiaries</a>
            <button class="dash-button dash-button-ghost" type="button" data-transfers-refresh>Refresh</button>
        </x-slot:actions>
    </x-dashboard.page-header>

    <div class="user-transfer-layout">
        <section class="dash-panel">
            <div class="dash-panel-heading">
                <div>
                    <h2>New transfer</h2>
                    <p>Enter the amount and confirm with your transaction PIN.</p>
                </div>
            </div>

            <form id="transferForm" class="dash-form dash-panel-body">
                <div class="dash-form-grid">
                    <div class="dash-field">
                        <label for="transferSubWallet">From Sub Wallet</label>
                        <select id="transferSubWallet" required></select>
                    </div>

                    <div class="dash-field">
                        <label for="transferBeneficiary">Beneficiary</label>
                        <select id="transferBeneficiary" required></select>
                    </div>
                </div>

                <div class="dash-form-grid">
                    <div class="dash-field">
                        <label for="transferAmount">Amount</label>
                        <input id="transferAmount" type="number" min="0.01" step="0.01" required>
                    </div>

                    <div class="dash-field">
                        <label for="transferPin">Transaction PIN</label>
                        <input id="transferPin" type="password" inputmode="numeric" maxlength="4" required>
                    </div>
                </div>

                <div class="dash-field">
                    <label for="transferNote">Note <span class="dash-muted-text">(optional)</span></label>
                    <input id="transferNote" type="text" maxlength="255">
                </div>

                <p class="dash-muted-text" id="transferLimits">Loading limits...</p>
                <p class="dash-form-error" id="transferError" hidden></p>

                <div>
                    <button class="dash-button dash-button-primary" type="submit" id="transferSubmit">Send money</button>
                </div>
            </form>
        </section>

        <aside class="user-side-stack">
            <section class="dash-panel">
                <div class="dash-panel-heading">
                    <div>
                        <h2>Transfer limits</h2>
                        <p>Configured by the platform.</p>
                    </div>
                </div>

                <div class="dash-panel-body user-limit-list">
                    <div><span>Minimum</span><strong id="limitMin">—</strong></div>
                    <div><span>Maximum</span><strong id="limitMax">—</strong></div>
                    <div><span>Daily remaining</span><strong id="limitDaily">—</strong></div>
                    <div><span>Monthly remaining</span><strong id="limitMonthly">—</strong></div>
                </div>
            </section>

            <section class="dash-panel" id="transferResult" hidden>
                <div class="dash-panel-heading">
                    <div>
                        <h2>Last transfer</h2>
                        <p id="transferResultReference">—</p>
                    </div>
                    <span class="dash-badge dash-badge-neutral" id="transferResultStatus">—</span>
                </div>

                <div class="dash-panel-body user-result-body">
                    <strong id="transferResultAmount">—</strong>
                    <span id="transferResultBeneficiary">—</span>
                    <p class="dash-form-error" id="transferResultFailure" hidden></p>
                </div>
            </section>
        </aside>
    </div>

    <section class="dash-panel" style="margin-top: 18px;">
        <div class="dash-panel-heading">
            <div>
                <h2>Recent transfers</h2>
                <p>The latest transfers you have requested.</p>
            </div>
            <a class="dash-text-link" href="{{ route('user.transactions') }}">View all transactions</a>
        </div>

        <div class="dash-table-wrap">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Beneficiary</th>
                        <th>Sub Wallet</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th class="dash-col-date">Date</th>
                        <th class="dash-table-actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="recentTransfersBody"></tbody>
            </table>
        </div>

        <x-dashboard.empty-state
            id="recentTransfersEmpty"
            title="No transfers yet"
            message="Your transfer requests will appear here."
        />
    </section>

    <x-user.transfer-details-modal />
@endsection
