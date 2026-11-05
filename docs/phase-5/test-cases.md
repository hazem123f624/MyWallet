# Phase 5 Test Cases

Test specification for money transfer and transfer confirmation.

**Status legend:**

- `Ready` — the feature exists and the case can be executed now.
- `Gap` — the current implementation does not meet the expected result yet.
- `Planned` — the feature does not exist yet.

**Environment:**

- Base URL: `http://127.0.0.1:8000/api/v1`
- Local/testing OTP: `1234`
- Password used in examples: `P@ssw0rd123`
- Header for protected routes: `X-API-Token: {{api_key}}`
- Header for authenticated routes: `Authorization: Bearer {{token}}`

---

## 1. Transfer Confirmation

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| PIN-10 | Require the PIN for a transfer | User attempts a transfer without a PIN or with a wrong PIN | Transfer is rejected before the banking provider is called | Planned |

> This case moved from Phase 1 because it exercises the Phase 5 transfer flow.

---

## Execution Checklist

> Check each case after executing it locally.

### 1. Transfer Confirmation
- [ ] **PIN-10** — Require the PIN for a transfer

---

## Suggested Automation Layout

```text
Modules/Transfers/tests/Feature/
└── TransferPinConfirmationTest.php
```
