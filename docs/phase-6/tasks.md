# Phase 6 Tasks

Status legend: `[x]` implemented, `[~]` partial, `[ ]` not started.

## 1. Lifecycle Data

- [x] Add `last_checked_at`, `next_check_at`, `check_attempts`, `reversed_at`, and `refunded_at` to transfers.
- [x] Create `transfer_events` with type, status transition, provider reference, message, and metadata.
- [x] Create `transfer_reversals` with a unique transfer constraint.
- [x] Add `TransferEvent` and `TransferReversal` models and relations.
- [x] Add lifecycle and reversal event types.

## 2. State Machine

- [x] Centralize transitions in `TransferLifecycleService`.
- [x] Record creation, processing, success, failure, reversal, refund, callback, and status-check events.
- [x] Settle reservations on success.
- [x] Release reservations on failure.
- [x] Keep reservations for pending/processing outcomes.
- [x] Prevent transitions from terminal states.
- [x] Credit the Sub Wallet on reversal/refund exactly once.
- [x] Store reversal/refund amount, currency, provider reference, and reason.

## 3. Provider Status

- [x] Extend the banking provider contract with `queryStatus()`.
- [x] Implement `queryStatus()` in the sandbox provider and test fake.
- [x] Add retry backoff and maximum check attempts.
- [x] Expose user refresh for pending/processing transfers.
- [x] Throttle user refresh to six requests per minute.
- [x] Expose admin status sync without manual refunds.
- [x] Return lifecycle events and reversal details in transfer resources.

## 4. Provider Callback

- [x] Add the provider callback endpoint.
- [x] Verify the `X-Provider-Signature` HMAC-SHA256 signature.
- [x] Reject callbacks when no callback secret is configured.
- [x] Validate reference, status, provider reference, amount, and reason.
- [x] Verify the callback amount when supplied.
- [x] Record `callback_received` before applying a result.
- [x] Keep duplicate callbacks idempotent.

## 5. Reconciliation

- [x] Add `transfers:reconcile`.
- [x] Register the command in the Transfers service provider.
- [x] Schedule it every five minutes with `withoutOverlapping()`.
- [x] Query due pending/processing transfers.
- [x] Apply provider results and record events.
- [x] Schedule a retry when the provider query fails.

## 6. User Dashboard

- [x] Add a transfer detail modal with lifecycle events.
- [x] Add reversal/refund summary.
- [x] Add a refresh-status action for pending/processing transfers.
- [x] Keep terminal transfers unchanged when refreshed.
- [x] Add shared timeline styling.

## 7. Admin Dashboard

- [x] Add lifecycle events and reversal/refund summary to transfer details.
- [x] Add a status sync action.
- [x] Add reversed and refunded statistic cards.
- [x] Add reversed and refunded status filters.
- [x] Show refunded/reversed volume in statistics.
- [x] Do not add a manual admin refund action.

## 8. Documentation and Verification

- [x] Add Phase 6 README, tasks, and test cases.
- [x] Add user refresh and admin sync documentation cases.
- [x] Add feature tests for callbacks, signatures, amount checks, refresh, sync, reconciliation, backoff, reversals, refunds, and idempotency.
- [x] Run the transfer and beneficiary suites.
- [x] Update `changes.md`, `business.md`, and the documentation index.
- [x] Regenerate `api.json` after the final documentation run.

## Definition of Done

Phase 6 is complete when:

1. Every transfer state change is auditable.
2. Pending and processing transfers can be refreshed without changing terminal transfers.
3. Provider callbacks are authenticated and idempotent.
4. Reversal and refund credit the original Sub Wallet exactly once.
5. Reconciliation runs every five minutes and retries safely.
6. User and admin dashboards expose lifecycle detail and status refresh.
7. Phase 6 tests and documentation pass.
