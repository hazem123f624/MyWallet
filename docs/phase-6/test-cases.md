# Phase 6 Test Cases

Test specification for the transfer lifecycle, provider status refresh, signed callbacks, reconciliation, reversals, and refunds.

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
- Provider callback secret: `TRANSFERS_CALLBACK_SECRET` in `.env`

> Automated feature tests cover lifecycle events, user refresh, terminal-state protection, signed callbacks, amount checks, admin sync, reconciliation, provider failure backoff, reversal/refund idempotency, and statistics.

---

## 1. Lifecycle Events

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| LC-01 | Record creation event | A transfer is created | A `created` event is stored with `to_status = pending` | Ready |
| LC-02 | Record success event | Provider returns success | A `successful` event is stored with the status transition | Ready |
| LC-03 | Record failure event | Provider returns failure | A `failed` event is stored with the safe failure reason | Ready |
| LC-04 | Record processing event | Provider returns processing | A `processing` event is stored | Ready |
| LC-05 | Record callback event | A valid callback is received | A `callback_received` event is stored | Ready |
| LC-06 | Record status-check event | A provider query fails | A `status_checked` event is stored | Ready |
| LC-07 | Return events in detail | Transfer detail is requested | Events are returned newest first | Ready |
| LC-08 | Preserve audit trail | Transfer reaches a terminal state | Previous events remain available | Ready |

---

## 2. User Status Refresh

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| REF-01 | Refresh a processing transfer | Provider now returns success | `200`; status becomes successful; reservation settles | Ready |
| REF-02 | Refresh a pending transfer | Provider now returns failure | `200`; status becomes failed; reservation releases | Ready |
| REF-03 | Refresh does not change a successful transfer | Transfer is successful; provider returns reversed | `200`; transfer remains successful; no credit | Ready |
| REF-04 | Refresh does not change a failed transfer | Transfer is failed | `200`; transfer remains failed | Ready |
| REF-05 | Refresh rejects another user's transfer | Transfer belongs to another user | `404` | Ready |
| REF-06 | Refresh is rate limited | More than six refreshes in a minute | `429` | Ready |
| REF-07 | Refresh requires authentication | No token | `401` | Ready |

---

## 3. Provider Callback

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| CB-01 | Valid successful callback | Processing transfer; correct signature | `200`; transfer becomes successful | Ready |
| CB-02 | Invalid signature | Signature does not match the raw body | `403`; transfer unchanged | Ready |
| CB-03 | Missing callback secret | `TRANSFERS_CALLBACK_SECRET` is empty | `403`; transfer unchanged | Ready |
| CB-04 | Unknown reference | No matching transfer | `404` | Ready |
| CB-05 | Invalid status | Status is not allowed | `422` | Ready |
| CB-06 | Amount mismatch | Callback amount differs from the transfer | `422`; transfer unchanged | Ready |
| CB-07 | Duplicate successful callback | Callback is delivered twice | Second callback is idempotent; no duplicate settlement | Ready |
| CB-08 | Duplicate reversal callback | Reversal callback is delivered twice | Only one reversal row and one wallet credit | Ready |
| CB-09 | Duplicate refund callback | Refund callback is delivered twice | Only one refund row and one wallet credit | Ready |
| CB-10 | Callback reference matches provider reference | Reference uses the stored provider reference | Transfer is found and updated | Ready |
| CB-11 | Callback failure releases the reservation | Processing transfer; callback status is failed | Transfer becomes failed; amount returns to the Sub Wallet | Ready |

---

## 4. Reversal and Refund

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| REV-01 | Reverse a successful transfer | Provider sends `reversed` | `200`; transfer is reversed; amount returns to the Sub Wallet | Ready |
| REV-02 | Refund a successful transfer | Provider sends `refunded` | `200`; transfer is refunded; amount returns to the Sub Wallet | Ready |
| REV-03 | Reversal amount is recorded | Reversal is applied | Amount and currency match the original transfer | Ready |
| REV-04 | Reversal reason is recorded | Callback includes a reason | Reason is visible on the reversal detail | Ready |
| REV-05 | Reversal timestamp is recorded | Reversal is applied | `reversed_at`/`refunded_at` is populated | Ready |
| REV-06 | Reversal is terminal | Reversal callback arrives again | No second credit and no status change | Ready |
| REV-07 | Failed transfer cannot be reversed | Transfer is failed | Transition is ignored; no credit | Ready |
| REV-08 | Reversal preserves reservation accounting | Successful transfer is reversed | Available increases once; reserved remains zero | Ready |

---

## 5. Admin Status Sync

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| ADM-SYNC-01 | Sync a processing transfer | Admin authenticated; provider returns success | `200`; status becomes successful | Ready |
| ADM-SYNC-02 | Sync returns lifecycle events | Admin sync succeeds | Response includes updated events | Ready |
| ADM-SYNC-03 | Sync a terminal transfer | Transfer is already successful | `200`; status remains unchanged | Ready |
| ADM-SYNC-04 | No manual refund action | Admin inspects transfer endpoints | No refund endpoint exists | Ready |
| ADM-SYNC-05 | Admin statistics include reversals | Reversed transfer exists | `reversed` count and volume are returned | Ready |
| ADM-SYNC-06 | Admin statistics include refunds | Refunded transfer exists | `refunded` count and volume are returned | Ready |
| ADM-SYNC-07 | User cannot sync as admin | User token is used | `403` | Ready |

---

## 6. Reconciliation

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| REC-01 | Process a due processing transfer | `next_check_at` is in the past; provider returns success | Transfer becomes successful | Ready |
| REC-02 | Skip a transfer that is not due | `next_check_at` is in the future | No provider query occurs | Ready |
| REC-03 | Skip terminal transfers | Transfer is successful/reversed/refunded/failed | No provider query occurs | Ready |
| REC-04 | Provider error schedules a retry | Provider query throws | Status remains processing; `check_attempts` increases; `next_check_at` is future | Ready |
| REC-05 | Maximum attempts are respected | `check_attempts` reaches the configured maximum | Transfer is skipped | Ready |
| REC-06 | Command reports the checked count | Due transfers exist | Command exits successfully and prints the count | Ready |
| REC-07 | Schedule is five minutes | Scheduler is inspected | `transfers:reconcile` runs every five minutes without overlap | Ready |

---

## 7. Dashboard

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| UI-LC-01 | User opens transfer details | User owns a transfer | Modal shows reference, status, amount, beneficiary, provider reference, and dates | Ready |
| UI-LC-02 | User views lifecycle timeline | Transfer has events | Events render newest first with dates and messages | Ready |
| UI-LC-03 | User views a reversal | Transfer is reversed | Reversal type, amount, and reason are shown | Ready |
| UI-LC-04 | User refreshes status | Transfer is pending/processing | Button calls the refresh endpoint and updates the modal | Ready |
| UI-LC-05 | Admin opens transfer details | Admin authenticated | Modal shows timeline and reversal/refund summary | Ready |
| UI-LC-06 | Admin syncs status | Transfer is pending/processing | Button calls the admin sync endpoint and refreshes the list | Ready |
| UI-LC-07 | Admin sees lifecycle statistics | Reversed/refunded transfers exist | Reversed and refunded cards show counts | Ready |
| UI-LC-08 | Admin filters lifecycle statuses | Reversed/refunded selected | List shows only the selected status | Ready |

---

## Execution Checklist

### 1. Lifecycle Events
- [ ] **LC-01** — Record creation event
- [ ] **LC-02** — Record success event
- [ ] **LC-03** — Record failure event
- [ ] **LC-04** — Record processing event
- [ ] **LC-05** — Record callback event
- [ ] **LC-06** — Record status-check event
- [ ] **LC-07** — Return events in detail
- [ ] **LC-08** — Preserve audit trail

### 2. User Status Refresh
- [ ] **REF-01** — Refresh a processing transfer
- [ ] **REF-02** — Refresh a pending transfer
- [ ] **REF-03** — Refresh does not change a successful transfer
- [ ] **REF-04** — Refresh does not change a failed transfer
- [ ] **REF-05** — Refresh rejects another user's transfer
- [ ] **REF-06** — Refresh is rate limited
- [ ] **REF-07** — Refresh requires authentication

### 3. Provider Callback
- [ ] **CB-01** — Valid successful callback
- [ ] **CB-02** — Invalid signature
- [ ] **CB-03** — Missing callback secret
- [ ] **CB-04** — Unknown reference
- [ ] **CB-05** — Invalid status
- [ ] **CB-06** — Amount mismatch
- [ ] **CB-07** — Duplicate successful callback
- [ ] **CB-08** — Duplicate reversal callback
- [ ] **CB-09** — Duplicate refund callback
- [ ] **CB-10** — Callback reference matches provider reference
- [ ] **CB-11** — Callback failure releases the reservation

### 4. Reversal and Refund
- [ ] **REV-01** — Reverse a successful transfer
- [ ] **REV-02** — Refund a successful transfer
- [ ] **REV-03** — Reversal amount is recorded
- [ ] **REV-04** — Reversal reason is recorded
- [ ] **REV-05** — Reversal timestamp is recorded
- [ ] **REV-06** — Reversal is terminal
- [ ] **REV-07** — Failed transfer cannot be reversed
- [ ] **REV-08** — Reversal preserves reservation accounting

### 5. Admin Status Sync
- [ ] **ADM-SYNC-01** — Sync a processing transfer
- [ ] **ADM-SYNC-02** — Sync returns lifecycle events
- [ ] **ADM-SYNC-03** — Sync a terminal transfer
- [ ] **ADM-SYNC-04** — No manual refund action
- [ ] **ADM-SYNC-05** — Admin statistics include reversals
- [ ] **ADM-SYNC-06** — Admin statistics include refunds
- [ ] **ADM-SYNC-07** — User cannot sync as admin

### 6. Reconciliation
- [ ] **REC-01** — Process a due processing transfer
- [ ] **REC-02** — Skip a transfer that is not due
- [ ] **REC-03** — Skip terminal transfers
- [ ] **REC-04** — Provider error schedules a retry
- [ ] **REC-05** — Maximum attempts are respected
- [ ] **REC-06** — Command reports the checked count
- [ ] **REC-07** — Schedule is five minutes

### 7. Dashboard
- [ ] **UI-LC-01** — User opens transfer details
- [ ] **UI-LC-02** — User views lifecycle timeline
- [ ] **UI-LC-03** — User views a reversal
- [ ] **UI-LC-04** — User refreshes status
- [ ] **UI-LC-05** — Admin opens transfer details
- [ ] **UI-LC-06** — Admin syncs status
- [ ] **UI-LC-07** — Admin sees lifecycle statistics
- [ ] **UI-LC-08** — Admin filters lifecycle statuses

---

## Suggested Automation Layout

```text
Modules/Transfers/tests/Feature/
├── MoneyTransferTest.php
└── TransferLifecycleTest.php
```

Run the suite with:

```bash
php artisan test Modules/Transfers/tests/Feature
```
