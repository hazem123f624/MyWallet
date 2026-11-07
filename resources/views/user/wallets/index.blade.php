@extends('user.layouts.app')

@section('page', 'wallets')

@section('content')
    <x-dashboard.page-header
        title="Wallets"
        description="Manage your Parent Wallet budget and divide it across Sub Wallets."
    >
        <x-slot:actions>
            <button class="dash-button dash-button-ghost" type="button" data-wallets-refresh>Refresh</button>
            <button class="dash-button dash-button-primary" type="button" data-sub-wallet-create>Create Sub Wallet</button>
        </x-slot:actions>
    </x-dashboard.page-header>

    <section class="dash-panel user-parent-wallet-panel">
        <div class="dash-panel-heading">
            <div>
                <h2>Parent Wallet</h2>
                <p>Your only real source of funds.</p>
            </div>
            <span class="dash-badge dash-badge-neutral" id="walletStatusBadge">—</span>
        </div>

        <div class="dash-panel-body">
            <div class="user-balance-grid">
                <div class="user-balance-item">
                    <span>Total allocated</span>
                    <strong id="walletTotalBalance">—</strong>
                </div>
                <div class="user-balance-item user-balance-available">
                    <span>Available</span>
                    <strong id="walletAvailableBalance">—</strong>
                </div>
                <div class="user-balance-item user-balance-reserved">
                    <span>Reserved</span>
                    <strong id="walletReservedBalance">—</strong>
                </div>
                <div class="user-balance-item">
                    <span>Allocated to Sub Wallets</span>
                    <strong id="walletAllocatedBalance">—</strong>
                </div>
            </div>

            <div class="user-funding-inline" id="walletFundingSource">
                <span class="user-funding-icon"><x-dashboard.icon name="bank" /></span>
                <span>
                    <strong>No funding source linked</strong>
                    <small>Link a verified primary bank account to fund your wallet.</small>
                </span>
                <a class="dash-button dash-button-ghost" href="{{ route('user.bank-accounts') }}">Manage</a>
            </div>
        </div>
    </section>

    <section class="dash-panel" style="margin-top: 18px;">
        <div class="dash-panel-heading">
            <div>
                <h2>Sub Wallets</h2>
                <p id="walletCountLabel">Loading...</p>
            </div>
        </div>

        <div class="user-subwallet-grid user-subwallet-grid-actions" id="subWalletGrid"></div>

        <x-dashboard.empty-state
            id="subWalletsEmpty"
            title="No Sub Wallets yet"
            message="Create your first Sub Wallet to divide your balance by purpose."
        />
    </section>

    <x-dashboard.modal id="subWalletFormModal" title="Create Sub Wallet">
        <form id="subWalletForm" class="dash-form">
            <input type="hidden" id="subWalletId">
            <input type="hidden" id="subWalletFormMethod" value="POST">

            <div class="dash-field">
                <label for="subWalletName">Name</label>
                <input id="subWalletName" type="text" maxlength="100" required placeholder="Electricity">
            </div>

            <div class="dash-field">
                <label>Color</label>
                <div class="user-choice-grid user-color-grid" id="subWalletColorOptions">
                    @foreach($subWalletColors as $color)
                        <label class="user-choice-option" title="{{ $color }}" aria-label="Color {{ $color }}">
                            <input type="radio" name="sub_wallet_color" value="{{ $color }}" @checked($loop->first)>
                            <span class="user-color-swatch" style="--swatch-color: {{ $color }}"></span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="dash-field">
                <label>Icon</label>
                <div class="user-choice-grid user-icon-grid" id="subWalletIconOptions">
                    @foreach($subWalletIcons as $icon)
                        <label class="user-choice-option" title="{{ $icon }}" aria-label="Icon {{ $icon }}">
                            <input type="radio" name="sub_wallet_icon" value="{{ $icon }}" @checked($loop->first)>
                            <span class="user-icon-choice">
                                <svg class="user-wallet-icon" aria-hidden="true"><use href="#user-icon-{{ $icon }}"></use></svg>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <p class="dash-form-error" id="subWalletFormError" hidden></p>

            <x-slot:footer>
                <button class="dash-button dash-button-ghost" type="button" data-close-modal>Cancel</button>
                <button class="dash-button dash-button-primary" type="submit" id="subWalletSubmit">Save Sub Wallet</button>
            </x-slot:footer>
        </form>
    </x-dashboard.modal>

    <x-dashboard.modal id="allocateModal" title="Allocate funds">
        <form id="allocateForm" class="dash-form">
            <input type="hidden" id="allocateSubWalletId">
            <p class="dash-muted-text" id="allocateHint"></p>

            <div class="dash-field">
                <label for="allocateAmount">Amount</label>
                <input id="allocateAmount" type="number" min="0.01" step="0.01" required>
            </div>

            <p class="dash-form-error" id="allocateError" hidden></p>

            <x-slot:footer>
                <button class="dash-button dash-button-ghost" type="button" data-close-modal>Cancel</button>
                <button class="dash-button dash-button-primary" type="submit" id="allocateSubmit">Allocate</button>
            </x-slot:footer>
        </form>
    </x-dashboard.modal>

    <x-dashboard.modal id="redistributeModal" title="Move funds">
        <form id="redistributeForm" class="dash-form">
            <input type="hidden" id="redistributeSourceId">
            <p class="dash-muted-text" id="redistributeHint"></p>

            <div class="dash-field">
                <label for="redistributeDestination">Destination Sub Wallet</label>
                <select id="redistributeDestination" required></select>
            </div>

            <div class="dash-field">
                <label for="redistributeAmount">Amount</label>
                <input id="redistributeAmount" type="number" min="0.01" step="0.01" required>
            </div>

            <p class="dash-form-error" id="redistributeError" hidden></p>

            <x-slot:footer>
                <button class="dash-button dash-button-ghost" type="button" data-close-modal>Cancel</button>
                <button class="dash-button dash-button-primary" type="submit" id="redistributeSubmit">Move funds</button>
            </x-slot:footer>
        </form>
    </x-dashboard.modal>
@endsection
