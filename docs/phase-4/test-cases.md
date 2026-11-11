# Phase 4 Test Cases

Test specification for Sub Wallet CRUD, allocation, redistribution, deletion, and internal operations.

**Status legend:**

- `Ready` — the feature exists and can be executed now.
- `Gap` — the current implementation does not meet the expected result yet.
- `Planned` — the feature does not exist yet.

**Environment:**

- Base URL: `http://127.0.0.1:8000/api/v1`
- Local/testing OTP: `1234`
- Header for protected routes: `X-API-Token: {{api_key}}`
- Header for authenticated routes: `Authorization: Bearer {{token}}`

> Automated feature tests cover CRUD, limits, balance movements, deletion, ownership, and ledger writes.

---

## 1. Sub Wallet CRUD

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| SW-01 | Create a Sub Wallet | Authenticated user has a Parent Wallet | `200`; Sub Wallet is created with zero balances | Ready |
| SW-02 | Create with an invalid color | Color is outside the allowed palette | `422`; color validation error | Ready |
| SW-03 | Create with an invalid icon | Icon is outside the allowed list | `422`; icon validation error | Ready |
| SW-04 | Create with a missing name | Name is absent | `422`; required-field error | Ready |
| SW-05 | Create 12 Sub Wallets | 11 active Sub Wallets exist | The 12th is created | Ready |
| SW-06 | Create the 13th Sub Wallet | 12 active Sub Wallets exist | `422`; maximum reached | Ready |
| SW-07 | List Sub Wallets | User owns Sub Wallets | `200`; only the user's active Sub Wallets are returned | Ready |
| SW-08 | View a Sub Wallet | User owns the Sub Wallet | `200`; balances and appearance are returned | Ready |
| SW-09 | View another user's Sub Wallet | Sub Wallet belongs to another user | `404` | Ready |
| SW-10 | Rename a Sub Wallet | User owns the Sub Wallet | `200`; name changes and balances stay the same | Ready |
| SW-11 | Restyle a Sub Wallet | User owns the Sub Wallet | `200`; color and icon change and balances stay the same | Ready |
| SW-12 | Update another user's Sub Wallet | Sub Wallet belongs to another user | `404` | Ready |
| SW-13 | Access Sub Wallets without authentication | No authorization header | `401` | Ready |

## 2. Allocation

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| SW-14 | Allocate funds to a Sub Wallet | Parent available balance is sufficient | `200`; Parent available decreases; Sub Wallet available increases | Ready |
| SW-15 | Allocate above Parent available balance | Amount exceeds Parent available | `422`; no balances change | Ready |
| SW-16 | Allocate zero or negative | Amount is `0` or negative | `422`; validation error | Ready |
| SW-17 | Allocate more than two decimals | Amount has three decimals | `422`; decimal validation error | Ready |
| SW-18 | Allocate to another user's Sub Wallet | Sub Wallet belongs to another user | `404` | Ready |
| SW-19 | Allocation preserves total money | Allocation succeeds | Parent total allocated stays unchanged | Ready |
| SW-20 | Allocation creates an internal operation | Allocation succeeds | Operation type is `allocated` with the correct amount | Ready |

## 3. Redistribution

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| SW-21 | Redistribute between owned Sub Wallets | Source has enough available balance | `200`; source decreases; destination increases by the same amount | Ready |
| SW-22 | Redistribute above source available | Amount exceeds source available | `422`; no balances change | Ready |
| SW-23 | Redistribute to the same Sub Wallet | Destination equals source | `422`; same-wallet error | Ready |
| SW-24 | Redistribute to another user's wallet | Destination belongs to another user | `422`; Sub Wallet not found | Ready |
| SW-25 | Redistribution leaves Parent balance unchanged | Redistribution succeeds | Parent available and total balances stay unchanged | Ready |
| SW-26 | Redistribution creates an internal operation | Redistribution succeeds | Operation type is `redistributed` with source and destination | Ready |

## 4. Deletion

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| SW-27 | Delete an unused Sub Wallet | Sub Wallet has available funds | `200`; funds return to Parent; Sub Wallet is soft deleted | Ready |
| SW-28 | Deleted Sub Wallet disappears | Deletion succeeds | It no longer appears in the list or view | Ready |
| SW-29 | Delete with reserved funds | Reserved balance is greater than zero | `422`; Sub Wallet remains active | Ready |
| SW-30 | Delete another user's Sub Wallet | Sub Wallet belongs to another user | `404` | Ready |
| SW-31 | Deletion creates a return operation | Sub Wallet with available funds is deleted | Operation type is `sub_wallet_returned` | Ready |

## 5. Parent Wallet Integration

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| SW-32 | Parent response includes Sub Wallet count | Sub Wallets exist | `sub_wallets_count` matches active Sub Wallets | Ready |
| SW-33 | Parent response includes allocated total | Funds were allocated | `allocated_to_sub_wallets` matches Sub Wallet totals | Ready |
| SW-34 | Parent response includes recent operations | Money movement occurred | Latest internal operations are returned | Ready |

---

## Execution Checklist

### 1. Sub Wallet CRUD
- [ ] **SW-01** — Create a Sub Wallet
- [ ] **SW-02** — Create with an invalid color
- [ ] **SW-03** — Create with an invalid icon
- [ ] **SW-04** — Create with a missing name
- [ ] **SW-05** — Create 12 Sub Wallets
- [ ] **SW-06** — Create the 13th Sub Wallet
- [ ] **SW-07** — List Sub Wallets
- [ ] **SW-08** — View a Sub Wallet
- [ ] **SW-09** — View another user's Sub Wallet
- [ ] **SW-10** — Rename a Sub Wallet
- [ ] **SW-11** — Restyle a Sub Wallet
- [ ] **SW-12** — Update another user's Sub Wallet
- [ ] **SW-13** — Access Sub Wallets without authentication

### 2. Allocation
- [ ] **SW-14** — Allocate funds to a Sub Wallet
- [ ] **SW-15** — Allocate above Parent available balance
- [ ] **SW-16** — Allocate zero or negative
- [ ] **SW-17** — Allocate more than two decimals
- [ ] **SW-18** — Allocate to another user's Sub Wallet
- [ ] **SW-19** — Allocation preserves total money
- [ ] **SW-20** — Allocation creates an internal operation

### 3. Redistribution
- [ ] **SW-21** — Redistribute between owned Sub Wallets
- [ ] **SW-22** — Redistribute above source available
- [ ] **SW-23** — Redistribute to the same Sub Wallet
- [ ] **SW-24** — Redistribute to another user's wallet
- [ ] **SW-25** — Redistribution leaves Parent balance unchanged
- [ ] **SW-26** — Redistribution creates an internal operation

### 4. Deletion
- [ ] **SW-27** — Delete an unused Sub Wallet
- [ ] **SW-28** — Deleted Sub Wallet disappears
- [ ] **SW-29** — Delete with reserved funds
- [ ] **SW-30** — Delete another user's Sub Wallet
- [ ] **SW-31** — Deletion creates a return operation

### 5. Parent Wallet Integration
- [ ] **SW-32** — Parent response includes Sub Wallet count
- [ ] **SW-33** — Parent response includes allocated total
- [ ] **SW-34** — Parent response includes recent operations

---

## Suggested Automation Layout

```text
Modules/Wallets/tests/Feature/
├── ParentWalletCreationTest.php
├── ParentWalletFundingSourceTest.php
├── ParentWalletSyncTest.php
├── SubWalletManagementTest.php
└── SubWalletTransferTest.php
```

Run the suite with:

```bash
php artisan test Modules/Wallets/tests/Feature
```
