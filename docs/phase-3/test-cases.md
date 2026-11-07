# Phase 3 Test Cases

Test specification for Parent Wallet creation, funding-source linking, balances, and synchronization.

**Status legend:**

- `Ready` — the feature exists and can be executed now.
- `Gap` — the current implementation does not meet the expected result yet.
- `Planned` — the feature does not exist yet.

**Environment:**

- Base URL: `http://127.0.0.1:8000/api/v1`
- Local/testing OTP: `1234`
- Header for protected routes: `X-API-Token: {{api_key}}`
- Header for authenticated routes: `Authorization: Bearer {{token}}`

> Automated feature tests cover creation, linking, synchronization, ownership, and backfill.

---

## 1. Parent Wallet Creation

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| WAL-01 | Create a wallet after account verification | User completes OTP verification | One Parent Wallet is created for the user | Ready |
| WAL-02 | Wallet creation is idempotent | Verification event fires twice | Only one wallet exists | Ready |
| WAL-03 | Legacy user receives a wallet | User exists without a wallet | `GET /parent-wallet` creates and returns it | Ready |
| WAL-04 | Backfill existing users | Users exist without wallets | `wallets:backfill` creates one wallet per user | Ready |
| WAL-05 | New wallet has zero balances | Wallet was just created | Total, available, and reserved balances are `0.00` | Ready |
| WAL-06 | New wallet is unlinked | No funding source is linked | Status is `unlinked` and funding source is `null` | Ready |

## 2. View Parent Wallet

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| WAL-07 | View own Parent Wallet | User is authenticated | `200`; wallet status, currency, balances, and funding source are returned | Ready |
| WAL-08 | View without authentication | No authorization header | `401` | Ready |
| WAL-09 | Suspended user cannot view the wallet | Suspended user has a token | `403`; suspended-account message | Ready |
| WAL-10 | Wallet is scoped to the authenticated user | User A requests the wallet | Only User A's wallet is returned | Ready |
| WAL-11 | Currency follows the user's country | Country has currency configured | Wallet uses the country currency and symbol | Ready |
| WAL-12 | Currency falls back to EGP | No country currency is configured | Wallet uses `EGP` and `E£` | Ready |

## 3. Link Funding Source

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| WAL-13 | Link verified primary bank account | User has a verified primary account | `200`; wallet becomes `active`; funding source is returned | Ready |
| WAL-14 | Pending bank account is rejected | Account is not verified | `422`; verification required | Ready |
| WAL-15 | Non-primary account is rejected | Account is verified but not primary | `422`; only the primary account can fund the wallet | Ready |
| WAL-16 | Another user's account is rejected | Account belongs to a different user | `422`; account not found | Ready |
| WAL-17 | Unknown account is rejected | Bank account ID does not exist | `422`; validation error | Ready |
| WAL-18 | Linking is idempotent | Same account is linked twice | Both requests succeed without duplicate state | Ready |
| WAL-19 | Funding source masks the account number | Wallet is linked | Only the last four digits are exposed | Ready |

## 4. Synchronization

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| WAL-20 | Verification links the wallet | Account becomes verified and primary | Wallet funding source is updated automatically | Ready |
| WAL-21 | Primary account change updates the wallet | Another verified account becomes primary | Wallet points to the new primary account | Ready |
| WAL-22 | Unlinking the funded account clears the wallet | Linked account is removed and no primary remains | Wallet becomes `unlinked`; funding source is `null` | Ready |
| WAL-23 | Unlinking promotes the next primary | Another verified account remains | Wallet points to the promoted primary account | Ready |
| WAL-24 | View repairs missed synchronization | Bank changes occurred without listener completion | Viewing the wallet synchronizes the funding source | Ready |

---

## Execution Checklist

### 1. Parent Wallet Creation
- [ ] **WAL-01** — Create a wallet after account verification
- [ ] **WAL-02** — Wallet creation is idempotent
- [ ] **WAL-03** — Legacy user receives a wallet
- [ ] **WAL-04** — Backfill existing users
- [ ] **WAL-05** — New wallet has zero balances
- [ ] **WAL-06** — New wallet is unlinked

### 2. View Parent Wallet
- [ ] **WAL-07** — View own Parent Wallet
- [ ] **WAL-08** — View without authentication
- [ ] **WAL-09** — Suspended user cannot view the wallet
- [ ] **WAL-10** — Wallet is scoped to the authenticated user
- [ ] **WAL-11** — Currency follows the user's country
- [ ] **WAL-12** — Currency falls back to EGP

### 3. Link Funding Source
- [ ] **WAL-13** — Link verified primary bank account
- [ ] **WAL-14** — Pending bank account is rejected
- [ ] **WAL-15** — Non-primary account is rejected
- [ ] **WAL-16** — Another user's account is rejected
- [ ] **WAL-17** — Unknown account is rejected
- [ ] **WAL-18** — Linking is idempotent
- [ ] **WAL-19** — Funding source masks the account number

### 4. Synchronization
- [ ] **WAL-20** — Verification links the wallet
- [ ] **WAL-21** — Primary account change updates the wallet
- [ ] **WAL-22** — Unlinking the funded account clears the wallet
- [ ] **WAL-23** — Unlinking promotes the next primary
- [ ] **WAL-24** — View repairs missed synchronization

---

## Suggested Automation Layout

```text
Modules/Wallets/tests/Feature/
├── ParentWalletCreationTest.php
├── ParentWalletFundingSourceTest.php
└── ParentWalletSyncTest.php
```

Run the suite with:

```bash
php artisan test Modules/Wallets/tests/Feature
```
