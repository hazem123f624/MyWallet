# Changes Log

## Session 2026-11-04 — @Hazemwalid290

- Blocked suspended users at login with a dedicated `users::messages.account_suspended` response.
- Added `EnsureUserIsActive` middleware so suspended accounts cannot use existing tokens on protected user routes.
- Revoked all user tokens inside a transaction when an administrator suspends an account.
- Normalised login, registration, forgot-password, reset-password, and profile updates to use the full international phone number consistently.
- Deferred phone and country changes until the new number is verified, then resolved the new country from the full phone.
- Fixed avatar replacement so the previous physical file is removed from storage.
- Added feature tests for registration/OTP, login/lockout/session, profile/avatar/phone change/deletion, password reset, and account suspension.
- Switched the PHPUnit database configuration to a dedicated MySQL `mywallet_test` database because the local PHP build does not include the SQLite PDO driver.
- Added a hashed transaction PIN with first-time setup, change, OTP-based reset, verification, and lockout after repeated failures.
- Added transaction PIN feature tests and exposed the endpoints in the API documentation cases and Postman export.
- Fixed OTP resend for stored full phone numbers and included the transaction PIN documentation cases in fresh database seeding.
- Restricted account-status changes to administrators holding the Super Admin role.
- Added administrator user-management and authorization tests, expanded Phase 1 coverage across all Ready cases, and recorded the execution results.
- Re-verified fresh migration and seeding, regenerated the Postman collection, and confirmed 68 documented endpoints.
- Added product user stories and acceptance criteria for all seven MVP phases.
- Added an execution checklist with per-case checkboxes to the Phase 1 test-case specification.
- Moved the transfer PIN confirmation test case from Phase 1 to the new Phase 5 test-case specification.
- Added the remaining cooldown time to OTP resend responses.
- Prevented locked OTPs from being resent immediately, closing the repeated-attempt bypass.
- Ensured the base admin account is `admin@base.com` with every role and every permission, and made admin seeding repeatable.
- Fixed the admin password documentation case to use a disposable fixture instead of changing the base admin password, and added missing admin response translations.
- Standardised user phone storage to E.164 format; fresh seeding writes the new format directly, so no phone backfill migration is needed.
- Defaulted the profile `delete_avatar` flag to `0` when the client omits it.
- Grouped large API documentation modules into expandable feature folders, ordered the documentation navigation predictably, gave parent folders and feature folders distinct colors, made every sidebar folder collapsible, and added a draggable resizable sidebar.
- Standardised the OTP cooldown message and added assertions for the exact response.
- Verified the full test suite passes: 70 tests, 295 assertions.
