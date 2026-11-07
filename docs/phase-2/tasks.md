# Phase 2 Tasks

Status legend: `[x]` implemented, `[~]` partial, `[ ]` not started.

## 1. Supported Banks

- [x] Add a supported-bank catalogue with an English and Arabic name, code, and logo.
- [x] Mark each bank as active or inactive.
- [x] Mark whether each bank currently supports account linking.
- [x] Return only active banks that support linking.
- [x] Keep the bank list deterministic and ordered.

## 2. Link a Bank Account

- [x] Add an authenticated endpoint to link a bank account.
- [x] Validate the selected bank, account holder name, account number, and optional IBAN.
- [x] Reject a bank that is inactive or does not support linking.
- [x] Reject the same account being linked twice by the same user.
- [x] Store the account number securely and expose only the last four digits.
- [x] Create the account in a pending state.
- [x] Make the first linked account the primary account.
- [x] Send an ownership-verification OTP to the registered phone.

## 3. Verify Ownership

- [x] Add an endpoint to verify ownership with the OTP.
- [x] Keep the account unusable until verification succeeds.
- [x] Mark the account verified and record the verification time.
- [x] Promote the account to primary when no primary exists.
- [x] Enforce OTP expiry, resend cooldown, and maximum failed attempts.
- [x] Add an endpoint to resend the verification OTP.
- [x] Prevent an already verified account from being verified again.
- [x] Never return the OTP in production responses.

## 4. Linked Accounts

- [x] Add an endpoint to list the authenticated user's linked accounts.
- [x] Show the bank, masked account number, verification state, and primary state.
- [x] Enforce that users only see and act on their own accounts.
- [x] Eager-load bank data to avoid additional queries per account.

## 5. Primary Account

- [x] Ensure exactly one linked account is primary while accounts exist.
- [x] Allow a verified account to become the primary account.
- [x] Reject a pending account as the primary account.
- [x] Clear the previous primary designation atomically when the primary changes.
- [x] Make setting the current primary account idempotent.

## 6. Unlink a Bank Account

- [x] Add an endpoint to unlink a bank account.
- [x] Remove the account so it can no longer be used as a funding source.
- [x] Promote another linked account when the primary account is unlinked.
- [x] Leave no primary account when the last account is unlinked.
- [x] Prevent users from unlinking another user's account.

## 7. Documentation and Verification

- [x] Document all Phase 2 mobile endpoints in the API documentation.
- [x] Add Phase 2 cases to the Postman export.
- [x] Add the Phase 2 test-case specification to the project documentation.
- [x] Add focused feature tests for linking, verification, primary selection, unlinking, ownership isolation, and the supported-bank list.
- [x] Run the Phase 2 feature tests and static analysis.

## Definition of Done

Phase 2 is complete when:

1. A user can only link a bank that currently supports linking.
2. A linked account cannot fund the wallet until ownership is verified.
3. Exactly one linked account is primary while linked accounts exist, and only a verified account can become primary.
4. Unlinking a primary account leaves the remaining accounts in a valid state.
5. All test cases in `test-cases.md` pass.
6. The API documentation and Postman collection include the final Phase 2 endpoints.
