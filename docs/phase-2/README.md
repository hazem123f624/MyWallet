# Phase 2 — Bank Account Linking

This folder tracks the implementation and verification work for Phase 2 of the MyWallet MVP.

**Goal:** a user can see the supported banks, link their own bank account, verify ownership, see all linked accounts, keep exactly one primary account, and unlink an account.

**Source of truth:** [`../business.md`](../business.md) — Phase 2.

## Files

| File | Purpose |
|---|---|
| [`tasks.md`](tasks.md) | Implementation checklist with current status and remaining gaps. |
| [`test-cases.md`](test-cases.md) | QA and automation test-case specification for Phase 2. |

## Current Status

| Area | Status | Notes |
|---|---|---|
| Supported bank list | Implemented | Only active banks with an available linking integration are returned. |
| Link bank account | Implemented | The account starts as pending and is not usable until ownership is verified. |
| Ownership verification | Implemented | A four-digit OTP is sent to the user's registered phone; the OTP has expiry, cooldown, and attempt limits. |
| Linked account list | Implemented | Each account shows its bank, masked account number, verification state, and primary designation. |
| Primary account | Implemented | The first linked account is primary; only a verified account can be selected as primary. |
| Unlink account | Implemented | Unlinking the primary account promotes another linked account when one remains. |

## Phase 2 Endpoints

Base path: `{{base_url}}/api/{{api_prefix}}`

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/mobile/banks` | List supported banks. |
| `GET` | `/mobile/bank-accounts` | List the authenticated user's linked bank accounts. |
| `POST` | `/mobile/bank-accounts` | Link a bank account and send the ownership-verification OTP. |
| `POST` | `/mobile/bank-accounts/{bankAccount}/resend-otp` | Resend the verification OTP after the cooldown. |
| `POST` | `/mobile/bank-accounts/{bankAccount}/verify` | Verify ownership with the OTP. |
| `PATCH` | `/mobile/bank-accounts/{bankAccount}/primary` | Mark a verified account as the primary account. |
| `DELETE` | `/mobile/bank-accounts/{bankAccount}` | Unlink a bank account. |

## Test Data

- Country code: `20` (Egypt)
- Phone input: `01011112222`
- Stored phone format: E.164, for example `+201011112222`
- OTP in local/testing: `1234`
- Header for protected routes: `X-API-Token`
- Header for authenticated routes: `Authorization: Bearer {{token}}`

## Known Gaps

1. Live bank or Banking Aggregator integration is not connected yet. Ownership verification currently uses an OTP sent to the user's registered phone; the linking availability flag on each bank controls which banks can be selected.
2. The Parent Wallet funding link is implemented in Phase 3, after a verified primary account exists.
