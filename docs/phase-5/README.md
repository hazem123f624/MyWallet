# Phase 5 — Money Transfer

This folder tracks the implementation and verification work for Phase 5 of the MyWallet MVP.

**Goal:** a user can send money from a Sub Wallet to a saved beneficiary after validating balance, limits, and the transaction PIN.

**Source of truth:** [`../business.md`](../business.md) — Phase 5.

## Files

| File | Purpose |
|---|---|
| [`tasks.md`](tasks.md) | Implementation checklist with current status. |
| [`test-cases.md`](test-cases.md) | QA and automation test-case specification for Phase 5. |

## Current Status

| Area | Status | Notes |
|---|---|---|
| Beneficiary management | Implemented | User CRUD with masked account details and ownership isolation. |
| Transfer validation | Implemented | Balance, configured limits, beneficiary ownership, Sub Wallet ownership, and transaction PIN. |
| Reservation | Implemented | Amount is reserved before provider execution. |
| Provider execution | Implemented | Sandbox provider by default; live integration remains later. |
| Success/failure handling | Implemented | Successful transfers settle reservations; failed transfers release them. |
| Idempotency | Implemented | Optional `client_reference` prevents duplicate submissions. |
| User dashboard | Implemented | Send Money, Beneficiaries, limits, recent transfers. |
| Admin dashboard | Implemented | Read-only transfer and beneficiary monitoring. |

## Phase 5 Endpoints

Base path: `{{base_url}}/api/{{api_prefix}}`

### User App

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/mobile/beneficiaries` | List saved beneficiaries. |
| `POST` | `/mobile/beneficiaries` | Create a beneficiary. |
| `GET` | `/mobile/beneficiaries/{beneficiary}` | View a beneficiary. |
| `PATCH` | `/mobile/beneficiaries/{beneficiary}` | Update a beneficiary. |
| `DELETE` | `/mobile/beneficiaries/{beneficiary}` | Delete a beneficiary. |
| `GET` | `/mobile/transfers/limits` | View transfer limits and remaining allowance. |
| `GET` | `/mobile/transfers` | List recent transfers. |
| `POST` | `/mobile/transfers` | Validate, reserve, and execute a transfer. |
| `GET` | `/mobile/transfers/{transfer}` | View a transfer status. |

### Dashboard

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/dashboard/transfers` | List transfers with filters. |
| `GET` | `/dashboard/transfers/statistics` | Transfer statistics. |
| `GET` | `/dashboard/transfers/{transfer}` | Transfer details. |
| `GET` | `/dashboard/beneficiaries` | List saved beneficiaries. |
| `GET` | `/dashboard/beneficiaries/{beneficiary}` | Beneficiary details. |

## Test Data

- Transaction PIN used in local examples: `2468`
- Local/testing OTP: `1234`
- Header for protected routes: `X-API-Token`
- Header for authenticated routes: `Authorization: Bearer {{token}}`

## Known Gaps

1. Live Banking Provider integration is not connected; the sandbox provider executes local transfers successfully.
2. Full lifecycle reconciliation, reversal, and refund processing are Phase 6.
3. Full searchable transfer history is implemented in Phase 7; Phase 5 continues to expose the recent-transfer preview and limits used by the Send Money page.
