# Phase 7 — Transaction History

This folder tracks the implementation and verification work for Phase 7 of the MyWallet MVP.

**Goal:** the user can review every external transfer they have made, search and filter it, and open a transfer's details without exposing internal wallet movements as bank transfers.

**Source of truth:** [`../business.md`](../business.md) — Phase 7.

## Files

| File | Purpose |
|---|---|
| [`tasks.md`](tasks.md) | Implementation checklist with current status. |
| [`test-cases.md`](test-cases.md) | QA and automation test-case specification for Phase 7. |

## Current Status

| Area | Status | Notes |
|---|---|---|
| External transfer history | Implemented | Paginated history of the user's own transfers. |
| Search by reference | Implemented | Partial reference search scoped to the authenticated user. |
| Status filter | Implemented | Pending, processing, successful, failed, reversed, and refunded. |
| Date filter | Implemented | Inclusive `date_from` / `date_to` range. |
| Transfer details | Implemented | Reuses the lifecycle/reversal detail modal from Phase 6. |
| Internal operation history | Implemented | Paginated private activity on the Wallets page. |
| Privacy separation | Implemented | Internal operations carry `is_private` and never appear in transfer history. |
| User dashboard | Implemented | Dedicated Transactions page, recent-transfer link, and private activity panel. |

## Endpoints

Base path: `{{base_url}}/api/{{api_prefix}}`

### User App

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/mobile/transfers/history` | Paginated external transfer history with `reference`, `status`, `date_from`, `date_to`, `page`, and `per_page` filters. |
| `GET` | `/mobile/parent-wallet/operations` | Paginated private wallet operations with `type`, `date_from`, `date_to`, `page`, and `per_page` filters. |
| `GET` | `/mobile/transfers/{transfer}` | Transfer detail with lifecycle events and reversal/refund information. |

### Example

```http
GET /api/v1/mobile/transfers/history?status=successful&reference=TRX-&date_from=2026-11-01&date_to=2026-11-30&page=1&per_page=15
Authorization: Bearer {{token}}
```

```json
{
  "data": [
    {
      "id": 7,
      "reference": "TRX-...",
      "status": "successful",
      "amount": "150.00",
      "currency": "EGP",
      "sub_wallet": { "id": 3, "name": "Electricity" },
      "beneficiary": {
        "id": 2,
        "name": "Transfer Demo Beneficiary",
        "bank_name": "Commercial International Bank",
        "account_number": "****3322"
      },
      "requested_at": "2026-11-10T06:30:00+00:00"
    }
  ],
  "pagination": {
    "per_page": 15,
    "current_page": 1,
    "total": 1,
    "last_page": 1
  },
  "message": "Transfers retrieved successfully.",
  "success": true
}
```

## Privacy Rules

- Only rows from the `transfers` table appear in external transfer history.
- Internal wallet movements remain in `wallet_operations` and appear only in the Wallets page private activity section.
- Every private activity response includes `is_private: true`.
- Transfer history queries are always scoped to the authenticated user.

## Dashboard

- `Transactions` page: reference search, status filter, date range, pagination, and transfer detail modal.
- `Send Money` page: latest ten transfers with a link to the full history.
- `Wallets` page: private wallet activity with type filter and pagination.
- `Overview` page: private wallet moves carry a `Private` badge.

## Test Data

- Transaction PIN used in local examples: `2468`
- Local/testing OTP: `1234`
- Header for protected routes: `X-API-Token`
- Header for authenticated routes: `Authorization: Bearer {{token}}`

## Known Gaps

1. CSV/PDF export is not included in the MVP.
2. Search is limited to reference, status, and date range; amount and beneficiary search are not included.
3. Admin transfer history already supports its own filters and is unchanged in Phase 7.
