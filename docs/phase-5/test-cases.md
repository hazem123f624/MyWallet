# Phase 5 Test Cases

Test specification for beneficiaries, money transfer validation, transaction PIN confirmation, provider execution, and dashboard monitoring.

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

> Automated feature tests cover beneficiary CRUD, ownership, transfer validation, limits, PIN enforcement, reservation settlement/release, idempotency, and admin monitoring.

---

## 1. Beneficiaries

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| BEN-01 | Create a beneficiary | Authenticated user | `200`; beneficiary is saved with masked account number | Ready |
| BEN-02 | List beneficiaries | User has saved beneficiaries | `200`; only the user's beneficiaries are returned | Ready |
| BEN-03 | View a beneficiary | User owns the beneficiary | `200`; masked details are returned | Ready |
| BEN-04 | Update a beneficiary | User owns the beneficiary | `200`; name/bank/account may change | Ready |
| BEN-05 | Delete a beneficiary | User owns the beneficiary | `200`; beneficiary is soft deleted | Ready |
| BEN-06 | Duplicate beneficiary is rejected | Same account is already saved | `422`; already exists | Ready |
| BEN-07 | Another user's beneficiary is inaccessible | Beneficiary belongs to another user | `404` on view/update/delete | Ready |
| BEN-08 | Full account number is never exposed | Any beneficiary response | Only the last four digits are visible | Ready |
| BEN-09 | Admin can list beneficiaries | Admin is authenticated | `200`; includes owning user summary | Ready |
| BEN-10 | Admin can view a beneficiary | Admin is authenticated | `200`; detail is returned | Ready |
| BEN-11 | Beneficiary routes require auth | No token | `401` | Ready |

---

## 2. Transfer Validation

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| TRF-01 | Create a successful transfer | Verified Sub Wallet has enough balance; PIN is correct | `200`; transfer is successful | Ready |
| TRF-02 | Reject insufficient balance | Amount exceeds Sub Wallet available | `422`; no provider call; no reservation | Ready |
| TRF-03 | Reject amount below minimum | Amount is below configured minimum | `422`; validation error | Ready |
| TRF-04 | Reject amount above maximum | Amount is above configured maximum | `422`; validation error | Ready |
| TRF-05 | Reject daily limit violation | Amount exceeds remaining daily limit | `422`; no transfer created | Ready |
| TRF-06 | Reject monthly limit violation | Amount exceeds remaining monthly limit | `422`; no transfer created | Ready |
| TRF-07 | Reject another user's Sub Wallet | Sub Wallet belongs to another user | `422`; Sub Wallet not found | Ready |
| TRF-08 | Reject another user's beneficiary | Beneficiary belongs to another user | `422`; beneficiary not found | Ready |
| TRF-09 | Reject zero or negative amount | Amount is `0` or negative | `422`; validation error | Ready |
| TRF-10 | Reject invalid amount precision | Amount has more than two decimals | `422`; decimal validation error | Ready |

---

## 3. Transaction PIN

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| PIN-10 | Require the PIN for a transfer | PIN is missing | `422`; transfer is rejected before provider call | Ready |
| PIN-11 | Reject a wrong PIN | User has a PIN set; submitted PIN is wrong | `422`; failed-attempt counter increases | Ready |
| PIN-12 | Reject a locked PIN | PIN verification is locked | `422`; transfer is not created | Ready |
| PIN-13 | No money is reserved for a failed PIN | Wrong PIN is submitted | Sub Wallet available/reserved balances are unchanged | Ready |

---

## 4. Reservation and Provider Result

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| TRF-11 | Successful provider result | Provider returns success | Reservation settles; reserved balance drops to zero; transfer is successful | Ready |
| TRF-12 | Failed provider result | Provider returns failure | Reservation releases; available balance returns to original; transfer is failed | Ready |
| TRF-13 | Pending/processing provider result | Provider returns processing | Reservation remains; available decreased and reserved increased | Ready |
| TRF-14 | Provider exception is handled | Provider throws an error | Reservation is released; transfer is failed with a safe reason | Ready |
| TRF-15 | Transfer amount is recorded correctly | Transfer succeeds | Transfer amount matches the reserved/settled amount | Ready |

---

## 5. Idempotency and Status

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| TRF-16 | Duplicate client reference | Same client reference is submitted twice | Same transfer is returned; no second reservation | Ready |
| TRF-17 | View recent transfers | Transfers exist | `200`; recent transfers are returned for the user | Ready |
| TRF-18 | View one transfer | User owns the transfer | `200`; status and provider details are returned | Ready |
| TRF-19 | Another user's transfer is inaccessible | Transfer belongs to another user | `404` | Ready |
| TRF-20 | Transfer limits endpoint | Authenticated user | `200`; min/max, daily/monthly used and remaining are returned | Ready |
| TRF-21 | Transfer routes require auth | No token | `401` | Ready |

---

## 6. Admin Monitoring

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| ADM-TRF-01 | Admin lists transfers | Admin authenticated | `200`; paginated transfers with user details | Ready |
| ADM-TRF-02 | Filter transfers by status | Admin authenticated | Only the selected status is returned | Ready |
| ADM-TRF-03 | Filter transfers by reference | Admin authenticated | Matching transfer is returned | Ready |
| ADM-TRF-04 | Filter transfers by date | Admin authenticated | Only transfers in range are returned | Ready |
| ADM-TRF-05 | View transfer statistics | Admin authenticated | Total, pending, successful, failed, volume returned | Ready |
| ADM-TRF-06 | View transfer detail | Admin authenticated | Full transfer detail returned | Ready |
| ADM-TRF-07 | Admin beneficiary list | Admin authenticated | Paginated beneficiaries with user summaries | Ready |
| ADM-TRF-08 | Admin beneficiary detail | Admin authenticated | Beneficiary detail returned | Ready |
| ADM-TRF-09 | User token cannot access admin transfer APIs | User token is used | `403` | Ready |

---

## Execution Checklist

### 1. Beneficiaries
- [ ] **BEN-01** — Create a beneficiary
- [ ] **BEN-02** — List beneficiaries
- [ ] **BEN-03** — View a beneficiary
- [ ] **BEN-04** — Update a beneficiary
- [ ] **BEN-05** — Delete a beneficiary
- [ ] **BEN-06** — Duplicate beneficiary is rejected
- [ ] **BEN-07** — Another user's beneficiary is inaccessible
- [ ] **BEN-08** — Full account number is never exposed
- [ ] **BEN-09** — Admin can list beneficiaries
- [ ] **BEN-10** — Admin can view a beneficiary
- [ ] **BEN-11** — Beneficiary routes require auth

### 2. Transfer Validation
- [ ] **TRF-01** — Create a successful transfer
- [ ] **TRF-02** — Reject insufficient balance
- [ ] **TRF-03** — Reject amount below minimum
- [ ] **TRF-04** — Reject amount above maximum
- [ ] **TRF-05** — Reject daily limit violation
- [ ] **TRF-06** — Reject monthly limit violation
- [ ] **TRF-07** — Reject another user's Sub Wallet
- [ ] **TRF-08** — Reject another user's beneficiary
- [ ] **TRF-09** — Reject zero or negative amount
- [ ] **TRF-10** — Reject invalid amount precision

### 3. Transaction PIN
- [ ] **PIN-10** — Require the PIN for a transfer
- [ ] **PIN-11** — Reject a wrong PIN
- [ ] **PIN-12** — Reject a locked PIN
- [ ] **PIN-13** — No money is reserved for a failed PIN

### 4. Reservation and Provider Result
- [ ] **TRF-11** — Successful provider result
- [ ] **TRF-12** — Failed provider result
- [ ] **TRF-13** — Pending/processing provider result
- [ ] **TRF-14** — Provider exception is handled
- [ ] **TRF-15** — Transfer amount is recorded correctly

### 5. Idempotency and Status
- [ ] **TRF-16** — Duplicate client reference
- [ ] **TRF-17** — View recent transfers
- [ ] **TRF-18** — View one transfer
- [ ] **TRF-19** — Another user's transfer is inaccessible
- [ ] **TRF-20** — Transfer limits endpoint
- [ ] **TRF-21** — Transfer routes require auth

### 6. Admin Monitoring
- [ ] **ADM-TRF-01** — Admin lists transfers
- [ ] **ADM-TRF-02** — Filter transfers by status
- [ ] **ADM-TRF-03** — Filter transfers by reference
- [ ] **ADM-TRF-04** — Filter transfers by date
- [ ] **ADM-TRF-05** — View transfer statistics
- [ ] **ADM-TRF-06** — View transfer detail
- [ ] **ADM-TRF-07** — Admin beneficiary list
- [ ] **ADM-TRF-08** — Admin beneficiary detail
- [ ] **ADM-TRF-09** — User token cannot access admin transfer APIs

---

## Suggested Automation Layout

```text
Modules/Beneficiaries/tests/Feature/
└── BeneficiaryManagementTest.php

Modules/Transfers/tests/Feature/
└── MoneyTransferTest.php
```

Run the suite with:

```bash
php artisan test Modules/Beneficiaries/tests/Feature Modules/Transfers/tests/Feature
```
