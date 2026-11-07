# Phase 4 Tasks

Status legend: `[x]` implemented, `[~]` partial, `[ ]` not started.

## 1. Sub Wallet Domain

- [x] Add the Sub Wallet model and table.
- [x] Add name, color, icon, available balance, and reserved balance.
- [x] Add soft deletes for future transaction history.
- [x] Add the maximum of 12 active Sub Wallets.
- [x] Add configured color and icon allowlists.
- [x] Add the internal wallet operation ledger.

## 2. Sub Wallet CRUD

- [x] Add `GET /mobile/sub-wallets`.
- [x] Add `POST /mobile/sub-wallets`.
- [x] Add `GET /mobile/sub-wallets/{subWallet}`.
- [x] Add `PATCH /mobile/sub-wallets/{subWallet}`.
- [x] Add `DELETE /mobile/sub-wallets/{subWallet}`.
- [x] Enforce ownership through the authenticated user's Parent Wallet.
- [x] Keep rename and restyle operations balance-neutral.

## 3. Allocation and Redistribution

- [x] Add `POST /mobile/sub-wallets/{subWallet}/allocate`.
- [x] Add `POST /mobile/sub-wallets/{subWallet}/redistribute`.
- [x] Validate positive amounts with two decimals.
- [x] Prevent allocation above the Parent Wallet available balance.
- [x] Prevent redistribution above the source Sub Wallet available balance.
- [x] Prevent redistribution to the same Sub Wallet.
- [x] Prevent redistribution to another user's Sub Wallet.
- [x] Use row locks and database transactions for every balance mutation.
- [x] Preserve Parent Wallet total allocated balance during internal moves.

## 4. Deletion Behaviour

- [x] Return available funds to the Parent Wallet.
- [x] Block deletion when reserved funds exist.
- [x] Soft delete the Sub Wallet.
- [x] Record the returned-funds operation.

## 5. Parent Wallet Integration

- [x] Add Sub Wallet count to the Parent Wallet response.
- [x] Add total allocated to Sub Wallets.
- [x] Add recent internal operations.
- [x] Keep Phase 3 endpoints backward compatible.

## 6. Documentation and Verification

- [x] Add Phase 4 documentation files.
- [x] Add API documentation cases and data builder updates.
- [x] Add feature tests for CRUD, limits, allocation, redistribution, deletion, ownership, and ledger writes.
- [x] Run the Phase 4 feature suite.
- [x] Export the updated API collection.

## Definition of Done

Phase 4 is complete when:

1. Users can create, rename, restyle, list, and delete Sub Wallets.
2. No user can exceed 12 active Sub Wallets.
3. Allocations and redistributions preserve total money and cannot overspend.
4. Deletion returns available funds and is blocked when reserved funds exist.
5. Internal operations are recorded.
6. Phase 4 tests and documentation pass.
