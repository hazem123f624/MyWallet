# Phase 4 — Sub Wallet Management

This folder tracks the implementation and verification work for Phase 4 of the MyWallet MVP.

**Goal:** a user can divide their Parent Wallet balance across up to 12 Sub Wallets, allocate funds, redistribute funds between them, and delete unused Sub Wallets without losing money.

**Source of truth:** [`../business.md`](../business.md) — Phase 4.

## Files

| File | Purpose |
|---|---|
| [`tasks.md`](tasks.md) | Implementation checklist with current status. |
| [`test-cases.md`](test-cases.md) | QA and automation test-case specification for Phase 4. |

## Current Status

| Area | Status | Notes |
|---|---|---|
| Sub Wallet CRUD | Implemented | Create, list, view, rename/restyle, and delete. |
| Maximum limit | Implemented | At most 12 active Sub Wallets per user. |
| Allocation | Implemented | Moves funds from the Parent Wallet available balance. |
| Redistribution | Implemented | Moves funds between the user's own Sub Wallets. |
| Deletion | Implemented | Returns available funds to the Parent Wallet and blocks deletion when funds are reserved. |
| Internal operations | Implemented | Allocation, redistribution, and delete-return movements are recorded. |
| Balances | Implemented | Available and reserved balances are exposed per Sub Wallet. |

## Phase 4 Endpoints

Base path: `{{base_url}}/api/{{api_prefix}}`

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/mobile/sub-wallets` | List active Sub Wallets. |
| `POST` | `/mobile/sub-wallets` | Create a Sub Wallet. |
| `GET` | `/mobile/sub-wallets/{subWallet}` | View a Sub Wallet. |
| `PATCH` | `/mobile/sub-wallets/{subWallet}` | Rename or change color/icon. |
| `DELETE` | `/mobile/sub-wallets/{subWallet}` | Delete and return available funds. |
| `POST` | `/mobile/sub-wallets/{subWallet}/allocate` | Allocate funds from the Parent Wallet. |
| `POST` | `/mobile/sub-wallets/{subWallet}/redistribute` | Move funds to another Sub Wallet. |

## Test Data

- Local/testing OTP: `1234`
- Stored phone format: E.164, for example `+201011112222`
- Header for protected routes: `X-API-Token`
- Header for authenticated routes: `Authorization: Bearer {{token}}`

## Known Gaps

1. Manual Parent Wallet deposits are not implemented, so live balances start at zero until funding flows exist.
2. Reserved Sub Wallet funds are supported by the schema but are only used by Phase 5 transfers.
3. Full internal operation history remains Phase 7 work; Phase 4 exposes the latest operations on the Parent Wallet response.
