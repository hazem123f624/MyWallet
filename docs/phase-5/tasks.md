# Phase 5 Tasks

Status legend: `[x]` implemented, `[~]` partial, `[ ]` not started.

## 1. Beneficiaries

- [x] Create the Beneficiaries module.
- [x] Add beneficiary model, encryption, masking, and soft deletes.
- [x] Add user CRUD endpoints.
- [x] Prevent duplicate beneficiaries per user.
- [x] Enforce ownership isolation.
- [x] Add admin read-only list and detail.
- [x] Add localized messages and tests.

## 2. Wallets Transfer Contract

- [x] Add Sub Wallet transfer summary DTO.
- [x] Add reserve, settle, and release operations.
- [x] Lock balances during every mutation.
- [x] Reject invalid or insufficient reservations.

## 3. Transaction PIN

- [x] Add a Users contract for transaction PIN verification.
- [x] Reuse the existing PIN lockout and failure-count policy.
- [x] Block transfer creation when the PIN is missing, wrong, or locked.

## 4. Transfers

- [x] Create the Transfers module and transfer model.
- [x] Add statuses: pending, processing, successful, failed, reversed, refunded.
- [x] Add provider contract, result DTO, and sandbox provider.
- [x] Validate beneficiary ownership.
- [x] Validate Sub Wallet ownership.
- [x] Validate min/max and daily/monthly limits.
- [x] Reserve the amount before provider execution.
- [x] Settle reservations on success.
- [x] Release reservations on failure.
- [x] Keep reservations for pending/processing outcomes.
- [x] Support `client_reference` idempotency.
- [x] Expose recent transfers, transfer detail, and limits.

## 5. User Dashboard

- [x] Add Send Money page.
- [x] Add Beneficiaries page.
- [x] Add beneficiary CRUD UI.
- [x] Add Sub Wallet and beneficiary selectors.
- [x] Add transaction PIN confirmation.
- [x] Add transfer result and failure states.
- [x] Add transfer-limit display.
- [x] Add recent transfers table.

## 6. Admin Dashboard

- [x] Add Transfers page.
- [x] Add Beneficiaries page.
- [x] Add transfer statistics.
- [x] Add status, reference, search, and date filters.
- [x] Add transfer detail modal.
- [x] Add beneficiary detail modal.
- [x] Add read-only authorization.

## 7. Documentation and Verification

- [x] Add Phase 5 README, tasks, and test cases.
- [x] Add documentation cases and data builders.
- [x] Add feature tests for beneficiaries, transfers, limits, PIN, reservation, idempotency, and admin monitoring.
- [x] Run tests and static analysis.
- [x] Export the updated API collection.

## Definition of Done

Phase 5 is complete when:

1. Users can manage beneficiaries.
2. Transfers validate balance, limits, ownership, and PIN.
3. No provider call occurs after failed validation.
4. Funds are reserved and settled or released correctly.
5. Retries with the same client reference do not duplicate transfers.
6. User and admin dashboards expose the implemented transfer features.
7. Phase 5 tests and documentation pass.
