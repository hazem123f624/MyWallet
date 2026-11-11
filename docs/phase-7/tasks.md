# Phase 7 Tasks

Status legend: `[x]` implemented, `[~]` partial, `[ ]` not started.

## 1. External Transfer History

- [x] Add a paginated user transfer history endpoint.
- [x] Scope every history query to the authenticated user.
- [x] Search by reference.
- [x] Filter by status.
- [x] Filter by inclusive date range.
- [x] Validate filters and pagination inputs.
- [x] Return pagination metadata with the history.
- [x] Reuse the Phase 6 transfer detail modal.

## 2. Internal Wallet History

- [x] Add a paginated private wallet-operation endpoint.
- [x] Scope every operation query to the authenticated user.
- [x] Filter by operation type.
- [x] Filter by inclusive date range.
- [x] Mark every operation as private in the API response.
- [x] Add the private activity table to the Wallets page.
- [x] Mark overview activity entries as private.

## 3. User Dashboard

- [x] Add the Transactions page route and sidebar item.
- [x] Add reference, status, and date filters.
- [x] Add pagination and empty/loading states.
- [x] Add the View action and transfer detail modal.
- [x] Add a “View all transactions” link to Send Money.
- [x] Limit the Send Money preview to the latest ten transfers.

## 4. Privacy Separation

- [x] External history uses only the `transfers` table.
- [x] Internal activity uses only the `wallet_operations` table.
- [x] Internal operations never appear in external transfer history.
- [x] External transfers never appear in private wallet activity.
- [x] Cross-user history access is blocked.

## 5. Tests

- [x] Add transfer history pagination tests.
- [x] Add reference, status, and date filter tests.
- [x] Add filter validation tests.
- [x] Add internal-operation exclusion tests.
- [x] Add wallet-operation history tests.
- [x] Add private flag, type filter, date filter, and isolation tests.
- [x] Add dashboard access coverage for the Transactions page.

## 6. Documentation and Verification

- [x] Add Phase 7 README, tasks, and test cases.
- [x] Add documentation cases for transfer history and private wallet activity.
- [x] Update the documentation index and business rules.
- [x] Update Phase 4 and Phase 5 known gaps.
- [x] Run the full test suite.
- [x] Regenerate `api.json` after the final documentation run.

## Definition of Done

Phase 7 is complete when:

1. Users can view a paginated external transfer history.
2. Reference, status, and date filters return only the user's transfers.
3. Transfer details remain available from history.
4. Internal wallet movements are private and never appear as transfers.
5. Internal activity has its own paginated view on the Wallets page.
6. User dashboard access, tests, and documentation pass.
