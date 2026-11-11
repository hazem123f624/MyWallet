@extends('user.layouts.app')

@section('page', 'overview')

@section('content')
    <x-dashboard.page-header
        title="Overview"
        description="Your balance, Sub Wallets, and funding source at a glance."
    >
        <x-slot:actions>
            <button class="dash-button dash-button-ghost" type="button" data-user-overview-refresh>Refresh</button>
            <a class="dash-button dash-button-primary" href="{{ route('user.wallets') }}">Manage wallets</a>
        </x-slot:actions>
    </x-dashboard.page-header>

    <section class="dash-stats" aria-label="Wallet statistics">
        <x-dashboard.stat-card label="Total balance" value-id="userTotalBalance" tone="brand" hint="Allocated to your wallet" icon="wallet" />
        <x-dashboard.stat-card label="Available" value-id="userAvailableBalance" tone="success" hint="Ready to allocate" icon="check" />
        <x-dashboard.stat-card label="Reserved" value-id="userReservedBalance" tone="warning" hint="Held for pending operations" icon="lock" />
        <x-dashboard.stat-card label="Sub Wallets" value-id="userSubWalletCount" tone="danger" hint="Active allocations" icon="models" />
    </section>

    <div class="dash-chart-grid user-overview-grid">
        <section class="dash-panel dash-chart-panel">
            <div class="dash-panel-heading">
                <div>
                    <h2>Balance distribution</h2>
                    <p>How your money is divided across Sub Wallets.</p>
                </div>
            </div>

            <div class="dash-chart-wrap user-chart-wrap">
                <canvas id="userWalletChart" aria-label="Sub Wallet distribution chart" role="img"></canvas>
                <div class="dash-chart-empty" id="userWalletChartEmpty" hidden>No Sub Wallet balances yet.</div>
            </div>
        </section>

        <section class="dash-panel user-status-panel">
            <div class="dash-panel-heading">
                <div>
                    <h2>Funding source</h2>
                    <p>The bank account behind your wallet.</p>
                </div>
                <span class="dash-badge dash-badge-neutral" id="userWalletStatus">—</span>
            </div>

            <div class="dash-panel-body user-funding-body">
                <div class="user-funding-icon">
                    <x-dashboard.icon name="bank" />
                </div>

                <div class="user-funding-copy">
                    <strong id="userFundingBank">No funding source linked</strong>
                    <span id="userFundingAccount">Link a verified primary bank account to fund your wallet.</span>
                </div>

                <a class="dash-button dash-button-ghost" href="{{ route('user.bank-accounts') }}">Manage bank accounts</a>
            </div>
        </section>
    </div>

    <section class="dash-panel" style="margin-top: 18px;">
        <div class="dash-panel-heading">
            <div>
                <h2>Your Sub Wallets</h2>
                <p>A quick look at every active budget.</p>
            </div>
            <a class="dash-text-link" href="{{ route('user.wallets') }}">Manage all</a>
        </div>

        <div class="user-subwallet-grid" id="userSubWalletCards"></div>
        <x-dashboard.empty-state
            id="userSubWalletsEmpty"
            title="No Sub Wallets yet"
            message="Create a Sub Wallet to start dividing your balance by purpose."
        />
    </section>

    <section class="dash-panel" style="margin-top: 18px;">
        <div class="dash-panel-heading">
            <div>
                <h2>Recent activity</h2>
                <p>Internal moves between your own wallets.</p>
            </div>
        </div>

        <div class="dash-overview-activity" id="userRecentOperations"></div>
    </section>
@endsection
