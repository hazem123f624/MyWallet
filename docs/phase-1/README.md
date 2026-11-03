# Phase 1 — User Registration & Account Management

This folder tracks the implementation and verification work for Phase 1 of the MyWallet MVP.

**Goal:** a user can create an account, verify their phone number, sign in, manage their profile, change their password, and have their account status controlled by an administrator.

**Source of truth:** [`../business.md`](../business.md) — Phase 1.

## Files

| File | Purpose |
|---|---|
| [`tasks.md`](tasks.md) | Implementation checklist with current status and remaining gaps. |
| [`test-cases.md`](test-cases.md) | QA and automation test-case specification for Phase 1. |

## Current Status

| Area | Status | Notes |
|---|---|---|
| Registration and OTP verification | Implemented | Registration is cached until the OTP is verified. |
| Login, logout, token refresh | Implemented | Unverified and inactive users are blocked at login. |
| Profile management | Implemented | Includes avatar upload and phone-change verification. |
| Password change and reset | Implemented | Forgot-password flow uses OTP plus a temporary token. |
| Account deletion and admin controls | Implemented | Soft delete, recycle bin, restore, force delete, and status toggle. |
| Transaction PIN | Missing | Required by the Phase 1 business scope; not implemented yet. |
| Super Admin-only status control | Partial | The dashboard route is admin-only, but not restricted to a Super Admin role. |
| Suspended-account enforcement | Partial | Login is blocked, but existing tokens are not explicitly revoked or checked per request. |

## Phase 1 Endpoints

Base path: `{{base_url}}/api/{{api_prefix}}`

### User App

| Method | Path | Purpose |
|---|---|---|
| `POST` | `/mobile/users/register` | Start registration and send an OTP. |
| `POST` | `/mobile/users/verify` | Verify the registration OTP and create the account. |
| `POST` | `/mobile/users/resend-otp` | Resend the registration or login OTP. |
| `POST` | `/mobile/users/login` | Sign in with phone and password. |
| `POST` | `/mobile/users/logout` | Revoke the current token. |
| `POST` | `/mobile/users/refresh-token` | Replace the current token. |
| `GET` | `/mobile/users/profile` | Get the authenticated user's profile. |
| `POST` | `/mobile/users/profile` | Update the authenticated user's profile. |
| `DELETE` | `/mobile/users/profile` | Delete the authenticated user's account. |
| `POST` | `/mobile/users/change-password` | Change the password using the current password. |
| `POST` | `/mobile/users/forget-password` | Start the password-reset flow. |
| `POST` | `/mobile/users/verify-forget-password` | Verify the reset OTP and temporary token. |
| `POST` | `/mobile/users/reset-password` | Set the new password. |

### Dashboard

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/dashboard/users` | List users. |
| `GET` | `/dashboard/users/{user}` | View a user. |
| `PATCH` | `/dashboard/users/{user}/toggle-active` | Activate or suspend a user. |
| `DELETE` | `/dashboard/users/{user}` | Soft delete a user. |
| `GET` | `/dashboard/users/trash` | List deleted users. |
| `PATCH` | `/dashboard/users/{user}/restore` | Restore a deleted user. |
| `DELETE` | `/dashboard/users/{user}/force-delete` | Permanently delete a user. |

## Test Data

- Country code: `20` (Egypt)
- Phone: `01011112222`
- OTP in local/testing: `1234`
- Password: `P@ssw0rd123`
- Required security header: `X-API-Token`
- Authenticated requests: `Authorization: Bearer {{token}}`

## Known Gaps

1. Transaction PIN is not implemented.
2. Only a Super Admin should change account status; the current route allows any authenticated admin.
3. Suspended users are blocked at login, but existing tokens are not revoked.
4. Phase 1 does not yet have automated feature tests; `test-cases.md` is the specification to implement them from.
