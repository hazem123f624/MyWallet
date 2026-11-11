# Phase 3 Tasks

Status legend: `[x]` implemented, `[~]` partial, `[ ]` not started.

## 1. Parent Wallet Domain

- [x] Create the Wallets module and Parent Wallet model.
- [x] Add one Parent Wallet per user through a unique user constraint.
- [x] Add `Unlinked`, `Active`, and `Suspended` wallet statuses.
- [x] Store currency, currency symbol, total allocated, available, and reserved balances.
- [x] Add a repository and service with ownership and locking rules.

## 2. Automatic Creation

- [x] Dispatch a user-verified event after account verification.
- [x] Create the Parent Wallet through an event listener.
- [x] Make creation idempotent and safe against concurrent requests.
- [x] Add a lazy creation fallback for legacy users.
- [x] Add a backfill command for existing users.
- [x] Backfill existing users during the wallet migration.

## 3. Funding Source

- [x] Add a bank-account lookup contract and DTO for cross-module access.
- [x] Link the Parent Wallet only to a verified primary bank account.
- [x] Reject pending, non-primary, unknown, or another user's bank account.
- [x] Make linking the same account idempotent.
- [x] Auto-link the funding source after bank-account verification.
- [x] Update the funding source when the primary account changes.
- [x] Clear or replace the funding source when the linked account is unlinked.

## 4. API

- [x] Add `GET /mobile/parent-wallet`.
- [x] Add `PUT /mobile/parent-wallet/funding-source`.
- [x] Expose wallet status, currency, balances, funding source summary, and timestamps.
- [x] Enforce authentication, active-user status, and ownership.
- [x] Add localized response messages.

## 5. Documentation and Verification

- [x] Add Phase 3 documentation files.
- [x] Add API documentation cases and a documentation data builder.
- [x] Add focused feature tests for creation, linking, synchronization, ownership, and backfill.
- [x] Run Phase 3 feature tests.
- [x] Export the updated API collection.

## Definition of Done

Phase 3 is complete when:

1. Every user has exactly one Parent Wallet.
2. The wallet links only to the user's verified primary bank account.
3. Wallet status reflects the funding source.
4. Users can view only their own wallet and balances.
5. Bank-account changes keep the funding source consistent.
6. Phase 3 tests and documentation pass.
