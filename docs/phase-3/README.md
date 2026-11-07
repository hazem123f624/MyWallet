# Phase 3 — Parent Wallet Management

This folder tracks the implementation and verification work for Phase 3 of the MyWallet MVP.

**Goal:** every user has one Parent Wallet, linked to their verified primary bank account, with clear wallet status and balance fields.

**Source of truth:** [`../business.md`](../business.md) — Phase 3.

## Files

| File | Purpose |
|---|---|
| [`tasks.md`](tasks.md) | Implementation checklist with current status. |
| [`test-cases.md`](test-cases.md) | QA and automation test-case specification for Phase 3. |

## Current Status

| Area | Status | Notes |
|---|---|---|
| Automatic Parent Wallet creation | Implemented | Created after account verification and available through a legacy fallback and backfill command. |
| Funding source | Implemented | Links only to the user's verified primary bank account. |
| Wallet status | Implemented | `Unlinked`, `Active`, or `Suspended`. |
| Balances | Implemented | Total allocated, available, and reserved balances are exposed; Phase 3 keeps them read-only. |
| Bank account synchronization | Implemented | Verification, primary-account changes, and unlinking update the wallet automatically. |
| Ownership enforcement | Implemented | Users can only view and update their own Parent Wallet. |

## Phase 3 Endpoints

Base path: `{{base_url}}/api/{{api_prefix}}`

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/mobile/parent-wallet` | View the authenticated user's Parent Wallet. |
| `PUT` | `/mobile/parent-wallet/funding-source` | Link the wallet to the verified primary bank account. |

## Test Data

- Local/testing OTP: `1234`
- Stored phone format: E.164, for example `+201011112222`
- Header for protected routes: `X-API-Token`
- Header for authenticated routes: `Authorization: Bearer {{token}}`

## Known Gaps

1. Live bank or Banking Aggregator balance synchronization is not connected yet.
2. Phase 3 does not add manual deposits or top-ups; balances stay read-only until funding flows are implemented.
3. Wallet suspension has a status value but no administrator control endpoint yet.
