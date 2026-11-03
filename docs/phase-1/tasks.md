# Phase 1 Tasks

Status legend: `[x]` implemented, `[~]` partial, `[ ]` not started.

## 1. Registration and Phone Verification

- [x] Add a registration endpoint that accepts name, country code, phone, optional email, and password.
- [x] Validate the name, country code, phone format and length, email uniqueness, password strength, and password confirmation.
- [x] Reject a phone number that is already registered.
- [x] Cache the pending registration instead of creating the user immediately.
- [x] Generate and send a registration OTP.
- [x] Return the OTP in the response only outside production.
- [x] Add an OTP verification endpoint that creates the user and sets `phone_verified_at`.
- [x] Issue a Sanctum token after successful verification.
- [x] Add a resend-OTP endpoint for pending registrations.
- [x] Enforce the OTP resend cooldown.
- [x] Enforce OTP expiry and maximum failed attempts.
- [x] Prevent reuse of an already verified OTP.
- [ ] Add dedicated feature tests for the registration and OTP flows.
- [ ] Add a registration rate-limit test that confirms the security middleware returns `429`.

## 2. Login, Logout, and Session

- [x] Add phone/password login for verified users.
- [x] Return a Sanctum bearer token on successful login.
- [x] Update `last_login_at` and reset failed-login counters on success.
- [x] Block users who have not verified their phone number.
- [x] Block inactive or suspended users at login.
- [x] Lock an account after repeated failed login attempts.
- [x] Apply the configured lock durations for 5, 10, and 15 failed attempts.
- [x] Add logout that revokes the current token.
- [x] Add token refresh that revokes the old token and issues a new one.
- [ ] Return a specific suspended-account message instead of the generic invalid-credentials message.
- [ ] Revoke all tokens when an administrator suspends a user.
- [ ] Enforce `is_active` on every authenticated request, not only at login.
- [ ] Add dedicated feature tests for login, lockout, logout, and refresh.

## 3. Profile Management

- [x] Add an authenticated endpoint to get the current user's profile.
- [x] Add an authenticated endpoint to update name, email, phone, country, postal code, main address, and avatar.
- [x] Validate unique email and phone values against the current user.
- [x] Support avatar upload, replacement, and deletion.
- [x] Require OTP verification when the user changes their phone number.
- [x] Keep the old phone number until the new number is verified.
- [x] Add an endpoint to delete the authenticated user's account.
- [x] Soft delete the account and revoke its tokens on deletion.
- [ ] Confirm that a phone change also updates the stored country consistently.
- [ ] Add dedicated feature tests for profile read, update, avatar handling, phone change, and account deletion.

## 4. Password Management

- [x] Add change-password for authenticated users using the current password.
- [x] Reject an incorrect current password.
- [x] Reject a new password that matches the old password.
- [x] Enforce the strong-password rule and confirmation.
- [x] Add forgot-password that sends an OTP and creates a temporary reset token.
- [x] Add verify-forgot-password that validates the OTP and temporary token.
- [x] Add reset-password that updates the password and clears the temporary token.
- [x] Expire the temporary reset token after the configured window.
- [ ] Add dedicated feature tests for change, forgot, verify, and reset password.
- [ ] Add a rate-limit test for repeated forgot-password requests.

## 5. Transaction PIN

Required by the Phase 1 business scope and needed by transfers in Phase 5.

- [ ] Add a hashed transaction PIN field to the user account.
- [ ] Add an endpoint to set the first transaction PIN.
- [ ] Require PIN confirmation and enforce a safe PIN format.
- [ ] Add an endpoint to change the PIN using the current PIN.
- [ ] Add an OTP-based PIN reset flow.
- [ ] Add a PIN verification service for transfer confirmation.
- [ ] Lock PIN verification after repeated wrong attempts.
- [ ] Never return the PIN or its hash in API responses.
- [ ] Add feature tests for set, change, reset, verify, wrong PIN, and lockout.

## 6. Account Status and Administration

- [x] Add dashboard endpoints to list and view users.
- [x] Add a dashboard endpoint to activate or suspend a user.
- [x] Add soft delete, recycle bin, restore, and force delete for users.
- [x] Protect dashboard routes with the admin guard and admin ability.
- [~] Restrict account-status changes to a Super Admin.
- [ ] Add a Super Admin authorization check or dedicated permission for status changes.
- [ ] Revoke the user's tokens when the account is suspended.
- [x] Record an activity log entry when an administrator changes a user's status (automatic via `LogsActivity`).
- [ ] Add feature tests for admin list, show, status toggle, soft delete, restore, and force delete.
- [ ] Add an authorization test proving a non-Super-Admin cannot change account status.

## 7. Documentation and Verification

- [x] Document all Phase 1 mobile and dashboard endpoints in the API documentation.
- [x] Add Phase 1 cases to the Postman export.
- [x] Add the Phase 1 test-case specification to the project documentation.
- [ ] Execute the test cases in `test-cases.md` against the local environment.
- [ ] Record the execution result and any defects found.

## Definition of Done

Phase 1 is complete when:

1. Every business requirement in Phase 1 is implemented, including the transaction PIN.
2. A suspended user cannot use the app through an existing token.
3. Only a Super Admin can change an account's status.
4. All test cases in `test-cases.md` pass.
5. The API documentation and Postman collection include the final Phase 1 endpoints.
