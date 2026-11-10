# Phase 6 — Transaction Lifecycle & Refund Management

This folder tracks the implementation and verification work for Phase 6 of the MyWallet MVP.

**Goal:** every transfer has an auditable lifecycle, provider status can be refreshed safely, and a successful transfer that is reversed or refunded returns the money to the original Sub Wallet exactly once.

**Source of truth:** [`../business.md`](../business.md) — Phase 6.

## Files

| File | Purpose |
|---|---|
| [`tasks.md`](tasks.md) | Implementation checklist with current status. |
| [`test-cases.md`](test-cases.md) | QA and automation test-case specification for Phase 6. |

## Current Status

| Area | Status | Notes |
|---|---|---|
| Lifecycle events | Implemented | Every state change and provider callback is recorded in `transfer_events`. |
| Provider status refresh | Implemented | Users may refresh pending/processing transfers; the endpoint is throttled. |
| Admin status sync | Implemented | Administrators can sync provider status; manual refunds are not exposed. |
| Provider callback | Implemented | HMAC-SHA256 signature in `X-Provider-Signature` using `transfers.callback_secret`. |
| Reversal | Implemented | A provider reversal of a successful transfer credits the Sub Wallet once and marks the transfer Reversed. |
| Refund | Implemented | An explicit refund credits the Sub Wallet once and marks the transfer Refunded. |
| Reconciliation | Implemented | `transfers:reconcile` runs every five minutes with per-transfer backoff. |
| Idempotency | Implemented | Terminal states and the unique reversal record prevent duplicate credits. |
| User dashboard | Implemented | Transfer detail modal, lifecycle timeline, reversal/refund summary, and status refresh. |
| Admin dashboard | Implemented | Lifecycle timeline, reversal/refund summary, status sync, and reversed/refunded statistics. |

## Endpoints

Base path: `{{base_url}}/api/{{api_prefix}}`

### User App

| Method | Path | Purpose |
|---|---|---|
| `POST` | `/mobile/transfers/{transfer}/refresh` | Query the provider for a pending or processing transfer. Throttled to six requests per minute. |
| `GET` | `/mobile/transfers/{transfer}` | Includes `events` and `reversal` in the transfer detail. |

### Dashboard

| Method | Path | Purpose |
|---|---|---|
| `POST` | `/dashboard/transfers/{transfer}/sync` | Sync provider status. Status only; no manual refund. |
| `GET` | `/dashboard/transfers/{transfer}` | Includes `events` and `reversal` in the transfer detail. |
| `GET` | `/dashboard/transfers/statistics` | Includes `reversed`, `refunded`, and `refunded_volume`. |

### Provider

| Method | Path | Purpose |
|---|---|---|
| `POST` | `/provider/transfers/callback` | Signed provider callback for processing, successful, failed, reversed, or refunded results. |

## Provider Callback Contract

```http
POST /api/v1/provider/transfers/callback
Content-Type: application/json
X-Provider-Signature: <hex HMAC-SHA256 of the raw request body>
```

```json
{
  "reference": "TRX-...",
  "status": "reversed",
  "provider_reference": "BANK-REF-123",
  "amount": "200.00",
  "reason": "Recipient bank returned the funds"
}
```

Rules:

- `TRANSFERS_CALLBACK_SECRET` must be configured. Callbacks are rejected with `403` when it is not.
- The signature is compared with `hash_equals`.
- When `amount` is present it must equal the transfer amount.
- Callbacks are idempotent. A terminal transfer ignores later transitions, and a reversal/refund can only be credited once.
- Allowed statuses are `processing`, `successful`, `failed`, `reversed`, and `refunded`.

## Reconciliation

```bash
php artisan transfers:reconcile
```

- Scheduled every five minutes with `withoutOverlapping()`.
- Processes pending and processing transfers whose `next_check_at` is due.
- Uses exponential-ish retry spacing controlled by `transfers.reconcile_interval_minutes`, `transfers.max_backoff_minutes`, and `transfers.max_check_attempts`.
- Provider errors keep the transfer pending/processing and schedule another attempt.

## Lifecycle Rules

- `Pending` and `Processing` may move to `Processing`, `Successful`, or `Failed`.
- `Successful` may move to `Reversed` or `Refunded`.
- `Reversed`, `Refunded`, and `Failed` are terminal.
- A reversal/refund creates one `transfer_reversals` row and credits the original Sub Wallet available balance exactly once.
- Failed transfers release the reservation; successful transfers settle it.
- Events are read-only audit records and are returned newest first.

## Test Data

- Transaction PIN used in local examples: `2468`
- Local/testing OTP: `1234`
- Header for protected routes: `X-API-Token`
- Header for authenticated routes: `Authorization: Bearer {{token}}`
- Callback secret for local callback examples: set `TRANSFERS_CALLBACK_SECRET` in `.env`

## Known Gaps

1. Live Banking Provider integration remains a later integration; the sandbox provider supports execution and status queries.
2. Full searchable transaction history is implemented in Phase 7.
3. Provider callbacks are received only through the signed HTTP endpoint; there is no queued retry of failed callback deliveries.
