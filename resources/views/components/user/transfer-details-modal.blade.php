@props(['refreshLabel' => 'Refresh status'])

<x-dashboard.modal id="transferDetailsModal" title="Transfer details">
    <dl class="dash-detail-list">
        <div><dt>Reference</dt><dd id="userTransferDetailReference">—</dd></div>
        <div><dt>Status</dt><dd id="userTransferDetailStatus">—</dd></div>
        <div><dt>Amount</dt><dd id="userTransferDetailAmount">—</dd></div>
        <div><dt>Sub Wallet</dt><dd id="userTransferDetailSubWallet">—</dd></div>
        <div><dt>Beneficiary</dt><dd id="userTransferDetailBeneficiary">—</dd></div>
        <div><dt>Provider reference</dt><dd id="userTransferDetailProviderReference">—</dd></div>
        <div><dt>Requested</dt><dd id="userTransferDetailRequested">—</dd></div>
        <div><dt>Completed</dt><dd id="userTransferDetailCompleted">—</dd></div>
    </dl>

    <p class="dash-form-error" id="userTransferDetailFailure" hidden></p>

    <div class="dash-section">
        <h3>Reversal / refund</h3>
        <div id="userTransferDetailReversal" class="dash-muted-text">No reversal or refund recorded.</div>
    </div>

    <div class="dash-section">
        <h3>Lifecycle timeline</h3>
        <div class="user-timeline" id="userTransferDetailTimeline"></div>
    </div>

    <x-slot:footer>
        <button class="dash-button dash-button-ghost" type="button" id="userTransferDetailRefresh">{{ $refreshLabel }}</button>
        <button class="dash-button dash-button-ghost" type="button" data-close-modal>Close</button>
    </x-slot:footer>
</x-dashboard.modal>
