import { escapeHtml, formatDateTime, qs } from '../../dashboard/core/dom.js?v=5';

const renderTimeline = (events = []) => {
    const host = qs('#userTransferDetailTimeline');

    if (!host) {
        return;
    }

    if (!events.length) {
        host.innerHTML = '<span class="dash-muted-text">No lifecycle events recorded.</span>';

        return;
    }

    host.innerHTML = events.map((event) => `
        <div class="user-timeline-item">
            <span class="user-timeline-dot"></span>
            <span>
                <strong>${escapeHtml(event.type)}</strong>
                <small>${escapeHtml(formatDateTime(event.created_at))}</small>
                <p>${escapeHtml(event.message || '')}</p>
            </span>
        </div>
    `).join('');
};

const renderReversal = (reversal, money) => {
    const host = qs('#userTransferDetailReversal');

    if (!host) {
        return;
    }

    if (!reversal) {
        host.textContent = 'No reversal or refund recorded.';

        return;
    }

    host.innerHTML = `
        <strong>${escapeHtml(reversal.type)}</strong>
        <span>${escapeHtml(money(reversal.amount))} · ${escapeHtml(reversal.reason || 'Returned to Sub Wallet')}</span>
    `;
};

export const renderTransferDetails = (transfer = {}, money = (value) => String(value ?? '')) => {
    const failure = qs('#userTransferDetailFailure');

    if (!failure) {
        return;
    }

    qs('#userTransferDetailReference').textContent = transfer.reference || '—';
    qs('#userTransferDetailStatus').textContent = transfer.status || '—';
    qs('#userTransferDetailAmount').textContent = money(transfer.amount);
    qs('#userTransferDetailSubWallet').textContent = transfer.sub_wallet?.name || '—';
    qs('#userTransferDetailBeneficiary').textContent = `${transfer.beneficiary?.name || ''} · ${transfer.beneficiary?.bank_name || ''} · ${transfer.beneficiary?.account_number || ''}`;
    qs('#userTransferDetailProviderReference').textContent = transfer.provider_reference || '—';
    qs('#userTransferDetailRequested').textContent = formatDateTime(transfer.requested_at);
    qs('#userTransferDetailCompleted').textContent = formatDateTime(transfer.completed_at);

    if (transfer.failure_reason) {
        failure.textContent = transfer.failure_reason;
        failure.hidden = false;
    } else {
        failure.hidden = true;
    }

    renderReversal(transfer.reversal, money);
    renderTimeline(Array.isArray(transfer.events) ? transfer.events : []);

    const refresh = qs('#userTransferDetailRefresh');

    if (refresh) {
        refresh.disabled = !['pending', 'processing'].includes(transfer.status || '');
    }
};
