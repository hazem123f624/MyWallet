# Phase 1 Test Cases

Test specification for user registration, account management, and administrator controls.

**Status legend:**

- `Ready` — the feature exists and the case can be executed now.
- `Gap` — the current implementation does not meet the expected result yet.
- `Planned` — the feature does not exist yet.

**Environment:**

- Base URL: `http://127.0.0.1:8000/api/v1`
- Local/testing OTP: `1234`
- Password used in examples: `P@ssw0rd123`
- Header for protected routes: `X-API-Token: {{api_key}}`
- Header for authenticated routes: `Authorization: Bearer {{token}}`

> This is a test-case specification. Phase 1 does not yet have automated feature tests.

---

## 1. Registration and Phone Verification

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| REG-01 | Register with valid name, country code, phone, email, and confirmed strong password | Phone and email are unused | `200`; registration OTP is sent; no user row is created yet; OTP is returned outside production | Ready |
| REG-02 | Register with an already registered phone | A user exists with the same full phone | `422`; phone uniqueness error | Ready |
| REG-03 | Register with an already registered email | A user exists with the same email | `422`; email uniqueness validation error | Ready |
| REG-04 | Register with an invalid country code | Request has an unknown `country_code` | `422`; invalid country-code error | Ready |
| REG-05 | Register with an invalid phone length or format | Country code is valid; phone is too short or has letters | `422`; phone validation error | Ready |
| REG-06 | Register with a weak password | Password does not satisfy the strong-password rule | `422`; password validation error | Ready |
| REG-07 | Register with mismatched password confirmation | `password_confirmation` differs from `password` | `422`; confirmation error | Ready |
| REG-08 | Register with missing required fields | Name, country code, phone, or password is absent | `422`; required-field errors | Ready |
| REG-09 | Request registration OTP twice within the cooldown | First registration request succeeded less than 60 seconds ago | `422`; message says an OTP was already sent and to wait | Ready |
| REG-10 | Verify registration with the correct OTP | A pending registration exists; OTP is `1234` | `200`; user is persisted; `phone_verified_at` is set; bearer token is returned | Ready |
| REG-11 | Verify registration with a wrong OTP | A pending registration exists | `422`; invalid OTP; attempt counter increases | Ready |
| REG-12 | Verify after the maximum wrong attempts | Three wrong OTP attempts were made | `422`; OTP is locked; a resend is required | Ready |
| REG-13 | Verify with an expired OTP | The OTP expiry window has passed | `422`; expired OTP error | Ready |
| REG-14 | Reuse an already verified registration OTP | Registration was already verified | `422`; OTP cannot be reused | Ready |
| REG-15 | Verify an unknown phone | No pending registration exists for the phone | `422`; invalid or expired OTP | Ready |
| REG-16 | Resend the registration OTP after the cooldown | A pending registration exists; cooldown has passed | `200`; a new OTP is generated | Ready |
| REG-17 | Confirm OTP is hidden in production | `APP_ENV=production` | Response contains `otp: null` | Ready |

---

## 2. Login, Logout, and Session

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| LOG-01 | Login with valid credentials | User is verified and active | `200`; bearer token is returned; `last_login_at` is updated; failed-attempt counters reset | Ready |
| LOG-02 | Login with a wrong password | User exists and is active | `422`; invalid credentials; failed-attempt counter increases | Ready |
| LOG-03 | Login with an unverified account | User exists but `phone_verified_at` is null | `403`; message asks the user to verify the account first | Ready |
| LOG-04 | Login with a suspended account | User exists but `is_active` is false | `422`; no token is issued; message is currently the generic invalid-credentials message | Gap |
| LOG-05 | Login with an unknown phone | No user exists with the phone | `422`; phone does not exist | Ready |
| LOG-06 | Lock after five failed attempts | User exists; five wrong passwords were submitted | `422`; account is locked for 15 minutes; correct password is also rejected until expiry | Ready |
| LOG-07 | Lock after ten failed attempts | User exists; ten wrong passwords were submitted | `422`; account is locked for 1 hour | Ready |
| LOG-08 | Lock after fifteen failed attempts | User exists; fifteen wrong passwords were submitted | `422`; account is locked for 1 day | Ready |
| LOG-09 | Login while locked | Account has a future `locked_until` | `422`; message includes the remaining lock time | Ready |
| LOG-10 | Logout | User has a valid token | `200`; current token is revoked; the same token cannot access protected routes | Ready |
| LOG-11 | Refresh token | User has a valid token | `200`; new token is returned; old token is revoked | Ready |
| LOG-12 | Access a protected route without a token | No authorization header | `401` | Ready |
| LOG-13 | Access a protected route with a suspended user's existing token | User was suspended after receiving a token | `401` or `403`; token must not be accepted | Gap |

---

## 3. Password Management

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| PWD-01 | Change password with the correct current password | User is authenticated | `200`; new password works; old password no longer works | Ready |
| PWD-02 | Change password with an incorrect current password | User is authenticated | `422`; current password is incorrect | Ready |
| PWD-03 | Change to the same password | New password equals the current password | `422`; new password must differ | Ready |
| PWD-04 | Change to a weak password | New password fails the strong-password rule | `422`; password validation error | Ready |
| PWD-05 | Change with mismatched confirmation | Confirmation differs from the new password | `422`; confirmation error | Ready |
| PWD-06 | Request a password reset for an existing phone | User exists | `200`; reset OTP is sent; temporary token is returned | Ready |
| PWD-07 | Request a password reset for an unknown phone | No user exists with the phone | `404`; user not found | Ready |
| PWD-08 | Verify the reset OTP with the correct token | Forgot-password request succeeded | `200`; temporary token remains valid for the reset step | Ready |
| PWD-09 | Verify the reset OTP with a wrong OTP | Forgot-password request succeeded | `422`; invalid OTP | Ready |
| PWD-10 | Verify with an expired or invalid reset token | Token is wrong or past `change_password_expired_at` | `422`; invalid or expired token | Ready |
| PWD-11 | Reset the password with valid token and OTP | Reset OTP was verified | `200`; password changes; temporary token is cleared | Ready |
| PWD-12 | Reuse a reset token | Password was already reset | `422`; token is no longer valid | Ready |
| PWD-13 | Reset to a weak password | New password fails the strong-password rule | `422`; password validation error | Ready |
| PWD-14 | Reset to the same password | New password matches the current password | `422`; new password must differ | Ready |
| PWD-15 | Reset with mismatched confirmation | Confirmation differs from the new password | `422`; confirmation error | Ready |
| PWD-16 | Request reset OTP repeatedly | Multiple requests are sent inside the cooldown window | `422`; cooldown message; no new OTP | Ready |

---

## 4. Profile Management

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| PRO-01 | Get the current profile | User is authenticated | `200`; profile fields match the user | Ready |
| PRO-02 | Get the profile without a token | No authorization header | `401` | Ready |
| PRO-03 | Update name, email, postal code, and address | User is authenticated; values are valid and unique | `200`; fields are updated | Ready |
| PRO-04 | Update to an email owned by another user | Another user has the email | `422`; email uniqueness error | Ready |
| PRO-05 | Update to a phone owned by another user | Another user has the phone | `422`; phone uniqueness error | Ready |
| PRO-06 | Change the phone number | New phone is valid and unused | `200`; phone is not changed yet; OTP is sent to the new phone; response says verification is pending | Ready |
| PRO-07 | Verify the new phone with the correct OTP | Phone-change OTP exists | `200`; phone is updated; user receives a token | Ready |
| PRO-08 | Verify the new phone with a wrong OTP | Phone-change OTP exists | `422`; invalid OTP; attempt counter increases | Ready |
| PRO-09 | Upload a valid avatar | Authenticated; valid image within the size limit | `200`; avatar is stored and the URL is returned | Ready |
| PRO-10 | Upload an invalid avatar | File is not an image or exceeds 2 MB | `422`; image validation error | Ready |
| PRO-11 | Delete the avatar | User has an avatar; request sends `delete_avatar=true` | `200`; avatar is removed from storage and response | Ready |
| PRO-12 | Delete the account | User is authenticated | `200`; account is soft deleted; all tokens are revoked; user cannot sign in | Ready |
| PRO-13 | Restore a deleted account from the dashboard | Admin restores the soft-deleted user | `200`; user is restored and can sign in again | Ready |
| PRO-14 | Access the profile after deletion | Account was soft deleted | `401`; token is no longer valid | Ready |
| PRO-15 | Change phone and confirm stored country | Phone change uses a different country code | `200`; phone and country data remain consistent | Gap |

---

## 5. Administrator Controls

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| ADM-01 | List users | Authenticated admin | `200`; paginated user list | Ready |
| ADM-02 | View a user | Authenticated admin; user exists | `200`; user details | Ready |
| ADM-03 | Suspend a user | Authenticated admin; user is active | `200`; `is_active` becomes false; user cannot log in | Ready |
| ADM-04 | Activate a user | Authenticated admin; user is suspended | `200`; `is_active` becomes true; user can log in | Ready |
| ADM-05 | Change status as a non-Super-Admin | Authenticated admin without Super Admin role | `403`; only a Super Admin may change status | Gap |
| ADM-06 | Soft delete a user | Authenticated admin; user exists | `200`; user appears in the recycle bin and cannot sign in | Ready |
| ADM-07 | Restore a soft-deleted user | Authenticated admin; user is trashed | `200`; user is restored | Ready |
| ADM-08 | Force delete a user | Authenticated admin; user is trashed | `200`; user is permanently removed | Ready |
| ADM-09 | Access dashboard user routes with a user token | Token belongs to a mobile user | `403`; user ability is not accepted | Ready |
| ADM-10 | Access mobile user routes with an admin token | Token belongs to an admin | `403`; admin ability is not accepted | Ready |
| ADM-11 | Record a status-change activity log | Authenticated admin toggles a user's status | Activity log entry is created with actor, target, and old/new status | Ready |

---

## 6. Transaction PIN

Required by Phase 1 and consumed by transfers in Phase 5.

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| PIN-01 | Set the first transaction PIN | Authenticated user; no PIN exists | `200`; PIN is stored hashed and never returned | Planned |
| PIN-02 | Set a PIN with mismatched confirmation | Confirmation differs | `422`; confirmation error | Planned |
| PIN-03 | Set an invalid PIN format | PIN is too short, too long, or non-numeric | `422`; PIN format error | Planned |
| PIN-04 | Change the PIN with the correct current PIN | Authenticated user; PIN exists | `200`; new PIN works; old PIN is rejected | Planned |
| PIN-05 | Change the PIN with a wrong current PIN | Authenticated user; PIN exists | `422`; current PIN is incorrect | Planned |
| PIN-06 | Reset the PIN using an OTP | Authenticated user; OTP is verified | `200`; PIN is replaced | Planned |
| PIN-07 | Verify the correct PIN | Authenticated user; PIN exists | `200`; verification succeeds | Planned |
| PIN-08 | Verify a wrong PIN | Authenticated user; PIN exists | `422`; verification fails; attempt counter increases | Planned |
| PIN-09 | Lock PIN verification after repeated failures | Several wrong PIN attempts were made | `422` or `423`; further attempts are blocked until reset | Planned |
| PIN-10 | Require the PIN for a transfer | User attempts a transfer without a PIN or with a wrong PIN | Transfer is rejected before the banking provider is called | Planned |

---

## 7. Security and Validation

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| SEC-01 | Request without the API key header | `SECURITY_API_KEY=true` | `401` or `403`; request is rejected before the controller | Ready |
| SEC-02 | Exceed the global rate limit | More than the configured requests per minute from one IP | `429`; `Retry-After` header is present | Ready |
| SEC-03 | Submit an oversized payload | Payload exceeds the configured size limit | `413` or `422`; request is rejected | Ready |
| SEC-04 | Confirm passwords are never returned | Any auth or profile response | Response and logs contain no password or hash | Ready |
| SEC-05 | Confirm OTPs are never returned in production | `APP_ENV=production` | OTP fields are null or absent | Ready |
| SEC-06 | Confirm soft-deleted users cannot authenticate | User was soft deleted | Login fails; existing tokens are revoked | Ready |
| SEC-07 | Confirm a user cannot access another user's profile | Authenticated as user A; request uses user B's id | The API ignores client ids and returns only user A's data, or rejects the request | Ready |

---

## Suggested Automation Layout

```text
Modules/Users/tests/Feature/
├── Auth/
│   ├── RegistrationTest.php
│   ├── LoginTest.php
│   ├── PasswordResetTest.php
│   └── SessionTest.php
├── Account/
│   ├── ProfileTest.php
│   ├── PhoneChangeTest.php
│   └── DeleteAccountTest.php
├── Security/
│   ├── AccountStatusTest.php
│   └── TransactionPinTest.php
└── Admin/
    └── ManageUsersTest.php
```

Run the suite with:

```bash
php artisan test --filter=Users
```
