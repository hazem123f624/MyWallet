@extends('user.layouts.app')

@section('page', 'beneficiaries')

@section('content')
    <x-dashboard.page-header
        title="Beneficiaries"
        description="Save the people and accounts you send money to."
    >
        <x-slot:actions>
            <button class="dash-button dash-button-ghost" type="button" data-beneficiary-refresh>Refresh</button>
            <button class="dash-button dash-button-primary" type="button" data-beneficiary-create>Add beneficiary</button>
        </x-slot:actions>
    </x-dashboard.page-header>

    <section class="user-bank-grid" id="beneficiaryGrid"></section>

    <x-dashboard.empty-state
        id="beneficiariesEmpty"
        title="No beneficiaries yet"
        message="Add a beneficiary to send money from your Sub Wallets."
    />

    <x-dashboard.modal id="beneficiaryFormModal" title="Add beneficiary">
        <form id="beneficiaryForm" class="dash-form">
            <input type="hidden" id="beneficiaryId">

            <div class="dash-field">
                <label for="beneficiaryName">Beneficiary name</label>
                <input id="beneficiaryName" type="text" required maxlength="100">
            </div>

            <div class="dash-form-grid">
                <div class="dash-field">
                    <label for="beneficiaryBankName">Bank name</label>
                    <input id="beneficiaryBankName" type="text" required maxlength="100">
                </div>

                <div class="dash-field">
                    <label for="beneficiaryBankCode">Bank code <span class="dash-muted-text">(optional)</span></label>
                    <input id="beneficiaryBankCode" type="text" maxlength="20">
                </div>
            </div>

            <div class="dash-field">
                <label for="beneficiaryAccountNumber">Account number</label>
                <input id="beneficiaryAccountNumber" type="text" inputmode="numeric" required>
            </div>

            <div class="dash-field">
                <label for="beneficiaryIban">IBAN <span class="dash-muted-text">(optional)</span></label>
                <input id="beneficiaryIban" type="text" placeholder="EG380019000500000000263180002">
            </div>

            <p class="dash-form-error" id="beneficiaryError" hidden></p>

            <x-slot:footer>
                <button class="dash-button dash-button-ghost" type="button" data-close-modal>Cancel</button>
                <button class="dash-button dash-button-primary" type="submit" form="beneficiaryForm" id="beneficiarySubmit">Save beneficiary</button>
            </x-slot:footer>
        </form>
    </x-dashboard.modal>
@endsection
