@extends('user.layouts.app')

@section('page', 'bank-accounts')

@section('content')
    <x-dashboard.page-header
        title="Bank Accounts"
        description="Link and verify the bank account that funds your Parent Wallet."
    >
        <x-slot:actions>
            <button class="dash-button dash-button-ghost" type="button" data-banks-refresh>Refresh</button>
            <button class="dash-button dash-button-primary" type="button" data-bank-link>Link bank account</button>
        </x-slot:actions>
    </x-dashboard.page-header>

    <section class="user-bank-grid" id="bankAccountsGrid"></section>

    <x-dashboard.empty-state
        id="bankAccountsEmpty"
        title="No bank accounts linked"
        message="Link a supported bank account to provide a funding source for your wallet."
    />

    <x-dashboard.modal id="linkBankModal" title="Link bank account">
        <form id="linkBankForm" class="dash-form">
            <div class="dash-field">
                <label for="linkBankId">Bank</label>
                <select id="linkBankId" required></select>
            </div>

            <div class="dash-field">
                <label for="linkHolderName">Account holder name</label>
                <input id="linkHolderName" type="text" required>
            </div>

            <div class="dash-field">
                <label for="linkAccountNumber">Account number</label>
                <input id="linkAccountNumber" type="text" inputmode="numeric" required>
            </div>

            <div class="dash-field">
                <label for="linkIban">IBAN <span class="dash-muted-text">(optional)</span></label>
                <input id="linkIban" type="text" placeholder="EG380019000500000000263180002">
            </div>

            <p class="dash-form-error" id="linkBankError" hidden></p>

            <x-slot:footer>
                <button class="dash-button dash-button-ghost" type="button" data-close-modal>Cancel</button>
                <button class="dash-button dash-button-primary" type="submit" id="linkBankSubmit">Link account</button>
            </x-slot:footer>
        </form>
    </x-dashboard.modal>

    <x-dashboard.modal id="verifyBankModal" title="Verify bank account">
        <form id="verifyBankForm" class="dash-form">
            <input type="hidden" id="verifyBankAccountId">
            <p class="dash-muted-text">Enter the verification code sent to your registered phone number.</p>
            <p class="dash-form-success" id="verifyBankHint" hidden></p>

            <div class="dash-field">
                <label for="verifyBankOtp">Verification code</label>
                <input id="verifyBankOtp" type="text" inputmode="numeric" maxlength="4" required>
            </div>

            <p class="dash-form-error" id="verifyBankError" hidden></p>

            <x-slot:footer>
                <button class="dash-button dash-button-ghost" type="button" id="resendBankOtp">Resend code</button>
                <button class="dash-button dash-button-primary" type="submit" id="verifyBankSubmit">Verify</button>
            </x-slot:footer>
        </form>
    </x-dashboard.modal>
@endsection
