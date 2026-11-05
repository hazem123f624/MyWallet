# MyWallet User Stories

Product-level user stories for every MVP phase described in [`business.md`](business.md).

**Actors:**

- **User** — the mobile app user who owns the account and wallets.
- **Super Admin** — the platform administrator who manages accounts and user status.

**Story format:** each story states the user goal and the acceptance criteria required for the story to be considered complete.

---

## Phase 1: User Registration & Account Management

### US-P1-01 — Create an account

**As a** new user, **I want to** create a MyWallet account with my personal details **so that** I can start using the app.

**Acceptance criteria:**

- I can register with my name, country code, phone number, optional email address, and a password.
- My phone number and email address must be unique.
- My password must meet the required strength rules.
- My phone number must match the selected country's format and length.
- I receive a verification OTP before the account is created.
- My account is not created until the OTP is successfully verified.
- The OTP is not returned in production responses.

### US-P1-02 — Verify my phone number

**As a** new user, **I want to** verify my phone number with an OTP **so that** my account is trusted and ready to use.

**Acceptance criteria:**

- A correct OTP activates my account and records that my phone is verified.
- An incorrect OTP is rejected and increases the failed-attempt count.
- An expired OTP is rejected.
- An OTP cannot be reused after successful verification.
- I can request a new OTP after the resend cooldown.
- Repeated incorrect attempts lock the OTP until a new one is requested.

### US-P1-03 — Log in securely

**As a** registered user, **I want to** log in with my phone number and password **so that** I can access my wallet.

**Acceptance criteria:**

- I can log in only after my phone number is verified.
- A suspended account cannot log in.
- A successful login returns an access token and records the login time.
- A successful login resets my failed-login counter.
- Repeated incorrect passwords lock my account according to the configured lock policy.
- An incorrect phone number or password does not reveal which credential was wrong.

### US-P1-04 — End or refresh my session

**As an** authenticated user, **I want to** log out or refresh my session token **so that** I stay in control of my account access.

**Acceptance criteria:**

- Logging out revokes my current token.
- A revoked token cannot access protected resources.
- Refreshing my session revokes the old token and returns a new token.
- A suspended account cannot continue using an existing token.

### US-P1-05 — Manage my profile

**As an** authenticated user, **I want to** view and update my profile **so that** my personal information stays accurate.

**Acceptance criteria:**

- I can view my name, email, phone, country, postal code, address, and avatar.
- I can update my personal details.
- My email and phone must remain unique.
- I can upload, replace, or remove my avatar.
- Changing my phone number requires OTP verification.
- My old phone number remains active until the new number is verified.
- After verification, my phone number and stored country remain consistent.
- I cannot access or modify another user's profile using a supplied user ID.

### US-P1-06 — Change or reset my password

**As an** authenticated user, **I want to** change my password or reset it when forgotten **so that** I can keep my account secure.

**Acceptance criteria:**

- Changing my password requires my current password.
- My new password must be strong, confirmed, and different from my current password.
- I can request a password-reset OTP for my registered phone number.
- The reset OTP and temporary reset token must both be valid before I can set a new password.
- The reset token expires after its configured window and cannot be reused.
- A successful reset clears the temporary token.

### US-P1-07 — Set my transaction PIN

**As an** authenticated user, **I want to** set a transaction PIN **so that** I can confirm sensitive transfers.

**Acceptance criteria:**

- I can set a PIN only when I do not already have one.
- My PIN must be exactly four digits and cannot be a repeated or simple sequential value.
- I must confirm the PIN when setting it.
- The PIN is stored securely as a hash and is never returned by the API.

### US-P1-08 — Change, verify, or reset my transaction PIN

**As an** authenticated user, **I want to** change, verify, or recover my transaction PIN **so that** I can continue confirming transfers safely.

**Acceptance criteria:**

- Changing my PIN requires my current PIN and a new confirmed PIN.
- A correct PIN verification succeeds.
- An incorrect PIN verification fails and increases the failed-attempt count.
- Repeated incorrect attempts lock PIN verification until the PIN is reset.
- I can reset my PIN using a verified OTP sent to my registered phone number.
- A successful reset clears the lock and failed-attempt count.
- My PIN and its hash are never returned by the API.

### US-P1-09 — Delete my account

**As an** authenticated user, **I want to** delete my account **so that** I can stop using MyWallet and remove my access.

**Acceptance criteria:**

- I can delete my own account while authenticated.
- My account is soft deleted and all of my tokens are revoked.
- I cannot sign in or access protected resources after deletion.
- A Super Admin can restore my account from the recycle bin.

### US-P1-10 — Control user account status

**As a** Super Admin, **I want to** suspend or reactivate a user account **so that** I can enforce platform rules.

**Acceptance criteria:**

- Only an administrator with the Super Admin role can change a user's status.
- Suspending a user makes the account inactive and revokes all of that user's tokens.
- Reactivating a user makes the account active again.
- The status change is recorded in the activity log.
- An administrator without the Super Admin role receives a forbidden response.

### US-P1-11 — Keep suspended users out of the app

**As the** platform, **I want** suspended users to be blocked from the app **so that** account status is enforced consistently.

**Acceptance criteria:**

- A suspended user cannot log in.
- A suspended user cannot use an existing token on protected routes.
- The user receives a clear suspended-account message.

---

## Phase 2: Bank Account Linking

### US-P2-01 — View supported banks

**As a** user, **I want to** see the list of supported banks **so that** I can choose where my funds come from.

**Acceptance criteria:**

- I can view the available supported banks.
- The list reflects whether a bank or Banking Aggregator currently permits the required integration.
- A bank that cannot be used for linking is not presented as available for linking.

### US-P2-02 — Link my bank account

**As a** user, **I want to** select a bank and link my own bank account **so that** MyWallet has a funding source.

**Acceptance criteria:**

- I can select an available bank.
- I can provide the details required to link my own bank account.
- The account must belong to me.
- A linked account is not usable until ownership is verified.
- A failed or unsupported integration does not create a usable funding source.

### US-P2-03 — Verify ownership of my bank account

**As a** user, **I want to** verify that the linked bank account belongs to me **so that** my wallet uses a trusted source of funds.

**Acceptance criteria:**

- Ownership verification is required before the account can fund my wallet.
- An unverified account cannot be used as a funding source.
- A successful verification makes the account usable.
- A failed verification keeps the account unusable and provides a clear outcome.

### US-P2-04 — View my linked accounts

**As a** user, **I want to** see all bank accounts I have linked **so that** I know which sources are available.

**Acceptance criteria:**

- I can view all linked bank accounts.
- Each account shows its verification and usability status.
- The primary account is clearly identified.

### US-P2-05 — Choose my primary bank account

**As a** user, **I want to** have exactly one primary linked account **so that** the funding source is unambiguous.

**Acceptance criteria:**

- Exactly one linked account is marked as primary.
- Changing the primary account updates the designation for the other accounts.
- The platform never operates with multiple primary accounts.

### US-P2-06 — Unlink a bank account

**As a** user, **I want to** unlink a bank account **so that** I can stop using it with MyWallet.

**Acceptance criteria:**

- I can unlink a linked bank account.
- An unlinked account can no longer be used as a funding source.
- Unlinking does not create or corrupt wallet balances.
- The platform prevents leaving the wallet in an invalid funding state.

---

## Phase 3: Parent Wallet Management

### US-P3-01 — Receive my Parent Wallet automatically

**As a** user, **I want** a Parent Wallet created automatically for my account **so that** I can manage my money without a separate setup step.

**Acceptance criteria:**

- Every user receives one Parent Wallet.
- The wallet is created automatically as part of the account experience.
- I cannot create multiple Parent Wallets for the same account.

### US-P3-02 — Link my Parent Wallet to my bank account

**As a** user, **I want** my Parent Wallet linked to my verified bank account **so that** I know where the money comes from.

**Acceptance criteria:**

- The Parent Wallet is linked to my verified bank account.
- An unverified or unavailable bank account cannot be used as the funding source.
- The wallet clearly presents its current funding status.

### US-P3-03 — View my wallet balances

**As a** user, **I want to** see my total allocated balance, available balance, and reserved balance **so that** I understand how much money I can use.

**Acceptance criteria:**

- I can view the total amount allocated to my Parent Wallet.
- I can view the amount currently available for use.
- I can view the amount reserved for pending operations.
- The balances are presented without exposing another user's wallet data.

### US-P3-04 — View my wallet status

**As a** user, **I want to** see the status of my Parent Wallet **so that** I know whether it is ready for use.

**Acceptance criteria:**

- The wallet displays its current status.
- A wallet that is not ready for use is clearly marked.
- The status does not imply funds are available when they are restricted or unverified.

### US-P3-05 — Understand the Parent Wallet as the source of funds

**As a** user, **I want** all Sub Wallets to draw from my Parent Wallet **so that** I understand where my real money is held.

**Acceptance criteria:**

- The Parent Wallet is presented as the only real source of funds.
- Sub Wallets are presented as internal allocations of the Parent Wallet.
- Moving money between my own wallets is treated as an internal operation.

---

## Phase 4: Sub Wallet Management

### US-P4-01 — Create Sub Wallets

**As a** user, **I want to** create Sub Wallets for different purposes **so that** I can divide my money clearly.

**Acceptance criteria:**

- I can create a Sub Wallet with a name, color, and icon.
- I can create Sub Wallets for purposes such as electricity, rent, food, or personal spending.
- I can have at most 12 Sub Wallets.
- I cannot create a Sub Wallet beyond the maximum limit.
- A newly created Sub Wallet starts without funds until I allocate money to it.

### US-P4-02 — Rename a Sub Wallet

**As a** user, **I want to** rename a Sub Wallet **so that** its purpose stays clear as my needs change.

**Acceptance criteria:**

- I can change the name of an existing Sub Wallet.
- The new name is reflected wherever the Sub Wallet appears.
- Renaming does not change its balance or its relationship to the Parent Wallet.

### US-P4-03 — Delete a Sub Wallet

**As a** user, **I want to** delete a Sub Wallet I no longer need **so that** my wallet structure stays relevant.

**Acceptance criteria:**

- I can delete an existing Sub Wallet.
- Deleting a Sub Wallet does not lose track of its funds.
- Funds that must be returned to the Parent Wallet are handled according to the wallet rules.
- A deleted Sub Wallet no longer appears as an active allocation.

### US-P4-04 — Allocate money to a Sub Wallet

**As a** user, **I want to** allocate money from my Parent Wallet to a Sub Wallet **so that** each purpose has a clear budget.

**Acceptance criteria:**

- I can allocate an amount from the Parent Wallet to a Sub Wallet.
- The allocation cannot exceed the Parent Wallet's available balance.
- The Sub Wallet balance increases by the allocated amount.
- The Parent Wallet's available balance reflects the allocation.
- The operation is recorded as an internal wallet operation.

### US-P4-05 — Redistribute funds between Sub Wallets

**As a** user, **I want to** move money between my Sub Wallets **so that** I can adjust my budgets without external transfers.

**Acceptance criteria:**

- I can redistribute funds between my own Sub Wallets.
- The source Sub Wallet has enough available balance for the move.
- The source balance decreases and the destination balance increases by the same amount.
- The operation is treated as internal and does not create an external transfer.

### US-P4-06 — View each Sub Wallet balance

**As a** user, **I want to** see the balance of every Sub Wallet **so that** I know how much is available for each purpose.

**Acceptance criteria:**

- Each Sub Wallet displays its current balance.
- The balance reflects allocations, redistributions, transfers, refunds, and reversals.
- Reserved amounts are reflected so I cannot mistake reserved money for available money.

### US-P4-07 — Prevent overspending a Sub Wallet

**As a** user, **I want** the platform to stop me from spending more than a Sub Wallet has available **so that** my budget remains accurate.

**Acceptance criteria:**

- A transfer or internal move cannot exceed the Sub Wallet's available balance.
- The platform rejects the operation before sending any external transfer request.
- No balance is deducted when the operation is rejected.

---

## Phase 5: Money Transfer

### US-P5-01 — Start a transfer from a Sub Wallet

**As a** user, **I want to** choose my Parent Wallet and a specific Sub Wallet **so that** I send money from the correct budget.

**Acceptance criteria:**

- I can select the Parent Wallet.
- I can select one of my Sub Wallets.
- The selected Sub Wallet belongs to my Parent Wallet.
- The transfer cannot use another user's wallet or Sub Wallet.

### US-P5-02 — Enter beneficiary and amount

**As a** user, **I want to** enter the beneficiary details and transfer amount **so that** I can send money to the right person.

**Acceptance criteria:**

- I can enter the beneficiary details required for the transfer.
- I can enter the transfer amount.
- The amount is positive and valid.
- The beneficiary and amount are clearly shown before confirmation.

### US-P5-03 — Validate the transfer before sending it

**As a** user, **I want** the platform to validate my balance and transfer limits before sending anything **so that** I do not create a failed or duplicate transfer.

**Acceptance criteria:**

- The transfer amount is checked against the Sub Wallet's available balance.
- The transfer respects the configured transfer limits.
- An insufficient balance is rejected before any request is sent to the Banking Provider.
- A transfer request is created only after validation succeeds.
- A rejected validation does not reserve or deduct money.

### US-P5-04 — Confirm the transfer with my transaction PIN

**As a** user, **I want to** confirm the transfer with my transaction PIN **so that** a sensitive operation requires my explicit approval.

**Acceptance criteria:**

- A valid transaction PIN is required before the transfer is submitted.
- A missing or incorrect PIN prevents the transfer from being submitted.
- A locked transaction PIN prevents confirmation until it is reset.
- The transfer is not sent to the Banking Provider without PIN confirmation.

### US-P5-05 — Execute the transfer through the Banking Provider

**As a** user, **I want** the validated transfer sent to the Banking Provider **so that** the beneficiary receives the money.

**Acceptance criteria:**

- The transfer is executed through the supported Banking Provider.
- The transaction status is updated as the provider responds.
- The user can see whether the transfer is pending, processing, successful, or failed.
- A provider failure does not silently lose the user's money.

---

## Phase 6: Transaction Lifecycle & Refund Management

### US-P6-01 — Reserve funds when a transfer request is created

**As a** user, **I want** the transfer amount reserved immediately **so that** I cannot spend the same money twice while the transfer is in progress.

**Acceptance criteria:**

- The amount is reserved from the Sub Wallet when the transfer request is created.
- The reserved amount is no longer available for another transfer.
- The reservation is visible in the Sub Wallet's available and reserved balances.

### US-P6-02 — Complete a successful transfer

**As a** user, **I want** a successful transfer to permanently deduct the reserved amount **so that** my balance matches reality.

**Acceptance criteria:**

- A successful provider response permanently deducts the reserved amount.
- The transfer is recorded as Successful.
- The amount is not deducted twice.
- The final Sub Wallet balance reflects the completed transfer.

### US-P6-03 — Release funds after a failed transfer

**As a** user, **I want** a failed transfer to return the reserved amount **so that** I do not lose money because of a failed operation.

**Acceptance criteria:**

- A failed provider result cancels the reservation.
- The amount returns to the Sub Wallet's available balance.
- The transfer is recorded as Failed.
- The user's final balance is the same as before the failed transfer.

### US-P6-04 — Handle a pending transfer safely

**As a** user, **I want** a pending transfer to keep its amount reserved **so that** the outcome can be resolved without double spending.

**Acceptance criteria:**

- While the bank has not determined a result, the amount remains reserved.
- The reserved amount is not available for another transfer.
- The platform can query the transfer status later.
- The transfer is not marked successful or failed until the provider confirms the result.

### US-P6-05 — Reverse a successful transfer

**As a** user, **I want** a reversed transfer to return the money to the original Sub Wallet **so that** the reversal is reflected correctly.

**Acceptance criteria:**

- A provider reversal creates a reversal record.
- The amount returns to the original Sub Wallet.
- The transfer is recorded as Reversed.
- The reversal cannot be applied twice.

### US-P6-06 — Prevent duplicate or lost-money outcomes

**As a** user, **I want** the platform to protect my balance from duplicate requests and lifecycle errors **so that** my money is always accounted for.

**Acceptance criteria:**

- A duplicate submission does not create a second charge.
- Every transfer ends in a clear lifecycle state.
- Failed, pending, successful, and reversed transfers each leave the wallet balances consistent.
- No money is lost or created because of retries or repeated provider responses.

---

## Phase 7: Transaction History

### US-P7-01 — View my external transfer history

**As a** user, **I want to** view my transfers **so that** I can review the money I have sent to external beneficiaries.

**Acceptance criteria:**

- I can view my external transfer history.
- Each entry shows the amount, beneficiary, source Sub Wallet, date, and status.
- Only transfers that leave my wallets appear in the external transfer history.
- The history does not expose another user's transfers.

### US-P7-02 — View transfer details

**As a** user, **I want to** open a transfer's details **so that** I can verify exactly what happened.

**Acceptance criteria:**

- I can view the details of a transfer in my history.
- The details include the amount, beneficiary, source of funds, reference, date, and status.
- The details reflect the latest lifecycle state of the transfer.
- I cannot access another user's transfer details.

### US-P7-03 — Search and filter my transfer history

**As a** user, **I want to** search by reference number and filter by date or status **so that** I can find a specific transfer quickly.

**Acceptance criteria:**

- I can search my transfer history by reference number.
- I can filter my transfer history by date.
- I can filter my transfer history by status.
- Search and filters return only transfers that belong to me.

### US-P7-04 — Separate internal operations from external transfers

**As a** user, **I want** internal wallet movements to stay out of my external transfer history **so that** I can distinguish my own allocations from money sent to beneficiaries.

**Acceptance criteria:**

- Moving money between the Parent Wallet and a Sub Wallet is treated as an internal operation.
- Moving money between Sub Wallets is treated as an internal operation.
- Internal operations appear in wallet details and are marked as private.
- Internal operations never appear in the external transfer history.

---

## Phase Coverage

| Phase | Stories |
|---|---|
| 1 — User Registration & Account Management | US-P1-01 to US-P1-11 |
| 2 — Bank Account Linking | US-P2-01 to US-P2-06 |
| 3 — Parent Wallet Management | US-P3-01 to US-P3-05 |
| 4 — Sub Wallet Management | US-P4-01 to US-P4-07 |
| 5 — Money Transfer | US-P5-01 to US-P5-05 |
| 6 — Transaction Lifecycle & Refund Management | US-P6-01 to US-P6-06 |
| 7 — Transaction History | US-P7-01 to US-P7-04 |

## Out of Scope for the MVP

The business design excludes bill payments, electricity and gas services, mobile recharge, savings goals, financial reports, budget recommendations, recurring payments, cashback, and loyalty points from the MVP.
