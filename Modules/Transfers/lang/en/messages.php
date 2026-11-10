<?php

return [
    'retrieved' => 'Transfers retrieved successfully.',
    'retrieved_one' => 'Transfer retrieved successfully.',
    'processed' => 'Transfer request processed.',
    'limits_retrieved' => 'Transfer limits retrieved successfully.',
    'statistics_retrieved' => 'Transfer statistics retrieved successfully.',
    'amount_must_be_positive' => 'The transfer amount must be greater than zero.',
    'amount_below_minimum' => 'The transfer amount must be at least :min.',
    'amount_above_maximum' => 'The transfer amount cannot exceed :max.',
    'daily_limit_exceeded' => 'This transfer exceeds your daily transfer limit.',
    'monthly_limit_exceeded' => 'This transfer exceeds your monthly transfer limit.',
    'beneficiary_not_found' => 'The selected beneficiary could not be found.',
    'sub_wallet_not_found' => 'The selected Sub Wallet could not be found.',
    'provider_failed' => 'The banking provider could not process the transfer.',
    'status_query_failed' => 'The provider status could not be checked. It will be retried.',
    'status_checked' => 'Transfer status checked.',
    'callback_processed' => 'Provider callback processed.',
    'callback_not_configured' => 'Provider callback is not configured.',
    'invalid_callback_signature' => 'Invalid provider callback signature.',
    'transfer_not_found' => 'Transfer not found.',
    'callback_amount_mismatch' => 'Callback amount does not match the transfer amount.',
];
