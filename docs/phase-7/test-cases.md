# Phase 7 Test Cases

Test specification for external transfer history, search/filter behavior, transfer details, and private internal wallet activity.

**Status legend:**

- `Ready` — the feature exists and the case can be executed now.
- `Gap` — the current implementation does not meet the expected result yet.
- `Planned` — the feature does not exist yet.

**Environment:**

- Base URL: `http://127.0.0.1:8000/api/v1`
- Local/testing OTP: `1234`
- Transaction PIN used in examples: `2468`
- Header for protected routes: `X-API-Token: {{api_key}}`
- Header for authenticated routes: `Authorization: Bearer {{token}}`

> Automated feature tests cover pagination, reference/status/date filters, validation, cross-user isolation, private operation history, and the internal/external separation.

---

## 1. External Transfer History

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| HIS-01 | List transfer history | User has transfers | `200`; paginated external transfers are returned | Ready |
| HIS-02 | History is newest first | Multiple transfers exist | Most recent transfer appears first | Ready |
| HIS-03 | Pagination metadata | More transfers than `per_page` | `pagination.total`, `last_page`, and `current_page` are correct | Ready |
| HIS-04 | Page navigation | Multiple pages exist | Requesting page 2 returns the next set | Ready |
| HIS-05 | Only the user's transfers | Another user has transfers | Other users' transfers are excluded | Ready |
| HIS-06 | Search by reference | A matching reference exists | Only matching transfers are returned | Ready |
| HIS-07 | Partial reference search | Part of a reference is submitted | Matching transfers are returned | Ready |
| HIS-08 | Filter by status | Transfers with different statuses exist | Only the selected status is returned | Ready |
| HIS-09 | Filter by date range | Transfers exist in and outside the range | Only transfers inside the inclusive range are returned | Ready |
| HIS-10 | Invalid status filter | Unknown status is submitted | `422`; validation error | Ready |
| HIS-11 | Invalid date range | `date_to` is before `date_from` | `422`; validation error | Ready |
| HIS-12 | Invalid page size | `per_page` exceeds 50 | `422`; validation error | Ready |
| HIS-13 | History requires authentication | No token | `401` | Ready |

## 2. Transfer Details

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| HIS-14 | Open transfer details from history | User owns the transfer | Detail modal/API returns reference, amount, beneficiary, source, status, dates, events, and reversal | Ready |
| HIS-15 | Another user's transfer detail | Transfer belongs to another user | `404` | Ready |
| HIS-16 | Latest lifecycle state | Transfer was reversed or refunded | Detail reflects the latest status and reversal information | Ready |

## 3. Privacy Separation

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| PRIV-01 | Internal operation is excluded from transfer history | Allocation/redistribution operation exists | Transfer history total is unchanged | Ready |
| PRIV-02 | External transfer is excluded from private activity | A transfer exists | Private activity does not include it | Ready |
| PRIV-03 | Private flag is returned | Wallet operations are listed | Every entry has `is_private: true` | Ready |
| PRIV-04 | Private activity requires authentication | No token | `401` | Ready |
| PRIV-05 | Another user's private activity is hidden | Another user has operations | Only the authenticated user's operations are returned | Ready |

## 4. Private Wallet Activity

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| PRIV-06 | List private activity | Operations exist | `200`; paginated operations are returned | Ready |
| PRIV-07 | Filter by operation type | Allocated and redistributed operations exist | Only the selected type is returned | Ready |
| PRIV-08 | Filter by date range | Operations exist | Only operations inside the range are returned | Ready |
| PRIV-09 | Invalid operation type | Unknown type is submitted | `422`; validation error | Ready |
| PRIV-10 | Invalid page size | `per_page` exceeds 50 | `422`; validation error | Ready |

## 5. Dashboard

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| UI-HIS-01 | Open Transactions page | Authenticated user | Page loads with the transfer history table | Ready |
| UI-HIS-02 | Search and filter | User enters filters | Table refreshes and pagination resets to page 1 | Ready |
| UI-HIS-03 | Clear filters | Filters are active | All filters reset and the full history returns | Ready |
| UI-HIS-04 | Open details | User clicks View | Detail modal opens with lifecycle information | Ready |
| UI-HIS-05 | Pagination controls | Multiple pages exist | Previous/Next behave correctly and disable at bounds | Ready |
| UI-HIS-06 | Send Money link | User is on Send Money | “View all transactions” opens the Transactions page | Ready |
| UI-HIS-07 | Private activity panel | User opens Wallets | Paginated private activity appears with a Private badge | Ready |
| UI-HIS-08 | Overview privacy marker | User opens Overview | Internal activity entries show a Private badge | Ready |

---

## Execution Checklist

### 1. External Transfer History
- [ ] **HIS-01** — List transfer history
- [ ] **HIS-02** — History is newest first
- [ ] **HIS-03** — Pagination metadata
- [ ] **HIS-04** — Page navigation
- [ ] **HIS-05** — Only the user's transfers
- [ ] **HIS-06** — Search by reference
- [ ] **HIS-07** — Partial reference search
- [ ] **HIS-08** — Filter by status
- [ ] **HIS-09** — Filter by date range
- [ ] **HIS-10** — Invalid status filter
- [ ] **HIS-11** — Invalid date range
- [ ] **HIS-12** — Invalid page size
- [ ] **HIS-13** — History requires authentication

### 2. Transfer Details
- [ ] **HIS-14** — Open transfer details from history
- [ ] **HIS-15** — Another user's transfer detail
- [ ] **HIS-16** — Latest lifecycle state

### 3. Privacy Separation
- [ ] **PRIV-01** — Internal operation is excluded from transfer history
- [ ] **PRIV-02** — External transfer is excluded from private activity
- [ ] **PRIV-03** — Private flag is returned
- [ ] **PRIV-04** — Private activity requires authentication
- [ ] **PRIV-05** — Another user's private activity is hidden

### 4. Private Wallet Activity
- [ ] **PRIV-06** — List private activity
- [ ] **PRIV-07** — Filter by operation type
- [ ] **PRIV-08** — Filter by date range
- [ ] **PRIV-09** — Invalid operation type
- [ ] **PRIV-10** — Invalid page size

### 5. Dashboard
- [ ] **UI-HIS-01** — Open Transactions page
- [ ] **UI-HIS-02** — Search and filter
- [ ] **UI-HIS-03** — Clear filters
- [ ] **UI-HIS-04** — Open details
- [ ] **UI-HIS-05** — Pagination controls
- [ ] **UI-HIS-06** — Send Money link
- [ ] **UI-HIS-07** — Private activity panel
- [ ] **UI-HIS-08** — Overview privacy marker

---

## Suggested Automation Layout

```text
Modules/Transfers/tests/Feature/
├── MoneyTransferTest.php
├── TransferLifecycleTest.php
└── TransferHistoryTest.php

Modules/Wallets/tests/Feature/
├── ParentWalletCreationTest.php
├── ParentWalletFundingSourceTest.php
├── ParentWalletSyncTest.php
├── SubWalletManagementTest.php
├── SubWalletTransferTest.php
└── WalletOperationHistoryTest.php
```

Run the suite with:

```bash
php artisan test Modules/Transfers/tests/Feature Modules/Wallets/tests/Feature
```
