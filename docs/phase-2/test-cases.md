# Phase 2 Test Cases

Test specification for supported banks, bank account linking, ownership verification, primary selection, and unlinking.

**Status legend:**

- `Ready` — the feature exists and the case can be executed now.
- `Gap` — the current implementation does not meet the expected result yet.
- `Planned` — the feature does not exist yet.

**Environment:**

- Base URL: `http://127.0.0.1:8000/api/v1`
- Local/testing OTP: `1234`
- Password used in examples: `P@ssw0rd123`
- Stored phone format: E.164, for example `+201011112222`
- Header for protected routes: `X-API-Token: {{api_key}}`
- Header for authenticated routes: `Authorization: Bearer {{token}}`

> Automated feature tests cover the supported-bank list, linking, duplicate rejection, ownership isolation, OTP verification, cooldown, primary selection, and unlinking. The manual execution checklist below remains for QA sign-off.

---

## 1. Supported Banks

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| BANK-01 | List supported banks | User is authenticated; active linked banks exist | `200`; only active banks that support linking are returned | Ready |
| BANK-02 | Inactive bank is excluded | A bank is inactive | The inactive bank is absent from the list | Ready |
| BANK-03 | Bank without integration is excluded | A bank does not support linking | The bank is absent from the list | Ready |
| BANK-04 | Bank list requires authentication | No authorization header | `401` | Ready |
| BANK-05 | Suspended user cannot list banks | User is suspended and has a token | `403`; suspended-account message | Ready |
| BANK-06 | Bank list is ordered | Multiple supported banks exist | Banks are returned by the configured sort order, then name | Ready |

---

## 2. Link a Bank Account

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| BACC-01 | Link a valid bank account | Authenticated; bank supports linking; account is unused | `200`; account is pending; OTP is sent; OTP is returned outside production | Ready |
| BACC-02 | First linked account becomes primary | No linked accounts exist | The newly linked account is marked primary | Ready |
| BACC-03 | Additional linked account is not primary | A primary account already exists | The new account is not marked primary | Ready |
| BACC-04 | Duplicate account is rejected | The same user already linked the same account at the same bank | `422`; account already linked | Ready |
| BACC-05 | Different user can link the same account details | Another user linked the same account number | The link succeeds for the current user | Ready |
| BACC-06 | Inactive bank cannot be linked | Selected bank is inactive | `422`; bank validation error | Ready |
| BACC-07 | Bank without integration cannot be linked | Selected bank does not support linking | `422`; bank validation error | Ready |
| BACC-08 | Invalid account number is rejected | Account number contains letters or is too short | `422`; account number validation error | Ready |
| BACC-09 | Invalid IBAN is rejected | IBAN format is invalid | `422`; IBAN validation error | Ready |
| BACC-10 | Missing account holder name is rejected | Account holder name is absent | `422`; required-field error | Ready |
| BACC-11 | Account number is masked | A linked account is returned | Only the last four digits are visible; the full number never appears | Ready |
| BACC-12 | Linking requires authentication | No authorization header | `401` | Ready |
| BACC-13 | OTP is hidden in production | `APP_ENV=production` | The response contains `otp: null` | Ready |

---

## 3. Ownership Verification

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| BACC-14 | Verify with the correct OTP | A pending account exists; OTP is `1234` | `200`; account becomes verified; verification time is recorded | Ready |
| BACC-15 | Verification makes the account primary when none exists | No primary account exists | The verified account becomes primary | Ready |
| BACC-16 | Verification keeps an existing primary | Another primary account exists | The verified account is not made primary | Ready |
| BACC-17 | Verify with a wrong OTP | A pending account exists | `422`; OTP failure; account remains pending | Ready |
| BACC-18 | Verify an already verified account | Account is verified | `422`; account already verified | Ready |
| BACC-19 | Verify another user's account | Account belongs to a different user | `404` | Ready |
| BACC-20 | Resend OTP inside the cooldown | OTP was sent less than the cooldown ago | `422`; cooldown message | Ready |
| BACC-21 | Resend OTP after the cooldown | Cooldown has passed | `200`; a new OTP is generated | Ready |
| BACC-22 | Resend OTP for a verified account | Account is verified | `422`; account already verified | Ready |
| BACC-23 | Expired OTP is rejected | OTP expiry window has passed | `422`; expired OTP error | Ready |
| BACC-24 | OTP locks after repeated wrong attempts | Maximum wrong attempts were made | Further attempts are rejected until a new OTP is requested | Ready |

---

## 4. Linked Accounts

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| BACC-25 | List linked accounts | User has linked accounts | `200`; all of the user's accounts are returned | Ready |
| BACC-26 | List shows verification and primary state | User has pending and verified accounts | Each account shows its status and whether it is primary | Ready |
| BACC-27 | User does not see another user's accounts | Another user has linked accounts | Only the authenticated user's accounts are returned | Ready |
| BACC-28 | List without a token | No authorization header | `401` | Ready |

---

## 5. Primary Account

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| BACC-29 | Set a verified account as primary | Two verified accounts exist; one is primary | `200`; the selected account is primary and the previous primary is cleared | Ready |
| BACC-30 | Set the current primary again | Selected account is already primary | `200`; the state is unchanged | Ready |
| BACC-31 | Pending account cannot become primary | Selected account is pending | `422`; verification required | Ready |
| BACC-32 | Only one primary account exists | Primary designation changes | Exactly one account is marked primary | Ready |
| BACC-33 | Another user's account cannot become primary | Account belongs to a different user | `404` | Ready |

---

## 6. Unlink a Bank Account

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| BACC-34 | Unlink a non-primary account | Account exists and is not primary | `200`; account is removed; primary is unchanged | Ready |
| BACC-35 | Unlink the primary account | Another linked account exists | `200`; the remaining account becomes primary | Ready |
| BACC-36 | Unlink the only account | Account exists and is primary | `200`; account is removed; no primary remains | Ready |
| BACC-37 | Unlink another user's account | Account belongs to a different user | `404`; account is not removed | Ready |
| BACC-38 | Unlinked account cannot be used again until re-linked | Account was unlinked | It no longer appears in the list and cannot be verified or made primary | Ready |

---

## Execution Checklist

> Check each case after executing it locally against the API.

### 1. Supported Banks
- [ ] **BANK-01** — List supported banks
- [ ] **BANK-02** — Inactive bank is excluded
- [ ] **BANK-03** — Bank without integration is excluded
- [ ] **BANK-04** — Bank list requires authentication
- [ ] **BANK-05** — Suspended user cannot list banks
- [ ] **BANK-06** — Bank list is ordered

### 2. Link a Bank Account
- [ ] **BACC-01** — Link a valid bank account
- [ ] **BACC-02** — First linked account becomes primary
- [ ] **BACC-03** — Additional linked account is not primary
- [ ] **BACC-04** — Duplicate account is rejected
- [ ] **BACC-05** — Different user can link the same account details
- [ ] **BACC-06** — Inactive bank cannot be linked
- [ ] **BACC-07** — Bank without integration cannot be linked
- [ ] **BACC-08** — Invalid account number is rejected
- [ ] **BACC-09** — Invalid IBAN is rejected
- [ ] **BACC-10** — Missing account holder name is rejected
- [ ] **BACC-11** — Account number is masked
- [ ] **BACC-12** — Linking requires authentication
- [ ] **BACC-13** — OTP is hidden in production

### 3. Ownership Verification
- [ ] **BACC-14** — Verify with the correct OTP
- [ ] **BACC-15** — Verification makes the account primary when none exists
- [ ] **BACC-16** — Verification keeps an existing primary
- [ ] **BACC-17** — Verify with a wrong OTP
- [ ] **BACC-18** — Verify an already verified account
- [ ] **BACC-19** — Verify another user's account
- [ ] **BACC-20** — Resend OTP inside the cooldown
- [ ] **BACC-21** — Resend OTP after the cooldown
- [ ] **BACC-22** — Resend OTP for a verified account
- [ ] **BACC-23** — Expired OTP is rejected
- [ ] **BACC-24** — OTP locks after repeated wrong attempts

### 4. Linked Accounts
- [ ] **BACC-25** — List linked accounts
- [ ] **BACC-26** — List shows verification and primary state
- [ ] **BACC-27** — User does not see another user's accounts
- [ ] **BACC-28** — List without a token

### 5. Primary Account
- [ ] **BACC-29** — Set a verified account as primary
- [ ] **BACC-30** — Set the current primary again
- [ ] **BACC-31** — Pending account cannot become primary
- [ ] **BACC-32** — Only one primary account exists
- [ ] **BACC-33** — Another user's account cannot become primary

### 6. Unlink a Bank Account
- [ ] **BACC-34** — Unlink a non-primary account
- [ ] **BACC-35** — Unlink the primary account
- [ ] **BACC-36** — Unlink the only account
- [ ] **BACC-37** — Unlink another user's account
- [ ] **BACC-38** — Unlinked account cannot be used again until re-linked

---

## Suggested Automation Layout

```text
Modules/BankAccounts/tests/Feature/
├── BankListTest.php
├── BankAccountLinkingTest.php
├── BankAccountVerificationTest.php
└── BankAccountPrimaryAndUnlinkTest.php
```

Run the suite with:

```bash
php artisan test Modules/BankAccounts/tests/Feature
```
