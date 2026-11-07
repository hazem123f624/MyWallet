# MyWallet Business Design

MyWallet is a personal money-management app built around one funding source and many virtual wallets. A user links a bank account, receives a single Parent Wallet, and splits that balance across Sub Wallets that represent how the money is intended to be used.

This document combines the original MVP business design with the confirmed business rules and workflows. It is a living document, updated only when a task is fully completed and a new or changed rule is confirmed. It contains no technical implementation details, code, or database schemas.

Product user stories and acceptance criteria for every phase are in [`user-stories.md`](user-stories.md).

---

## Platform Overview

**Actors:**

1. **Super Admin** — platform oversight; manages admin accounts and user account status.
2. **User (Mobile)** — owns the wallet, links a bank account, creates Sub Wallets, and sends money.
3. **Banking Provider** — licensed bank or aggregator that executes external transfers.

**Core model:** One bank account funds one Parent Wallet. The Parent Wallet is divided internally into Sub Wallets. Only external transfers leave the user's wallets; moving money between the user's own wallets is private.

---

## Phase 1: User Registration & Account Management

**Goal:** The user can create an account and start using the app.

**Business requirements and rules:**

- Create a new account.
- Log in and log out.
- Verify the phone number with an OTP before using the app.
- Manage user data and change the password.
- Set a transaction PIN to confirm transfers.
- Transaction PIN verification is locked after repeated incorrect attempts and can be reset using a verified phone OTP.
- An account is either Active or Suspended; a Suspended account cannot use the app.
- Only a Super Admin can change an account's status.

**Result:** The user has an account ready to use the app.

---

## Phase 2: Bank Account Linking

**Goal:** Link the user's own bank account to the app as the source of funds.

**Business requirements and rules:**

- Display the list of supported banks.
- Show only banks that are currently available and permit the required integration.
- Select a bank and link the user's own bank account.
- The same bank account cannot be linked twice by the same user.
- Verify ownership of the bank account before it becomes usable; the user proves ownership with a one-time code sent to their registered phone.
- Display all linked accounts, clearly showing which are verified and which is primary.
- Exactly one linked account is the primary account while the user has linked accounts.
- Only a verified account can be chosen as the primary account.
- Allow the user to unlink a bank account.
- Unlinking the primary account promotes another linked account when one remains, so the funding source stays unambiguous.
- Bank linking depends on the availability of a bank or Banking Aggregator that permits the required integration.

**Result:** The user can link their bank account as the source of funds.

---

## Phase 3: Parent Wallet Management

**Goal:** Create and manage the user's main wallet.

**Business requirements and rules:**

- Create the Parent Wallet automatically for every user.
- Link the Parent Wallet to the user's bank account.
- Display the total allocated balance.
- Display the available balance.
- Display the balance reserved for pending operations.
- Display the wallet status.
- The Parent Wallet is the only real source of funds; Sub Wallets are internal allocations of it.

**Result:** The user has a main wallet through which they manage their money.

---

## Phase 4: Sub Wallet Management

**Goal:** Allow the user to divide their money across virtual wallets.

**Business requirements and rules:**

- Create Sub Wallets to divide money by purpose.
- Rename a Sub Wallet.
- Delete a Sub Wallet.
- Choose a color and icon for each Sub Wallet.
- A user can have at most 12 Sub Wallets.
- Allocate an amount to each Sub Wallet from the Parent Wallet.
- Redistribute funds between Sub Wallets.
- Display each Sub Wallet's balance.
- Prevent spending more than the available balance of a Sub Wallet.

**Example:**

```text
Wallet            Balance
----------------  -----------
Parent Wallet     20,000 EGP
Electricity        3,000 EGP
Rent               7,000 EGP
Food               5,000 EGP
Personal           5,000 EGP
```

**Result:** The user can divide their balance according to their needs.

---

## Phase 5: Money Transfer

**Goal:** Allow the user to send money from a specific Sub Wallet.

**Business requirements and rules:**

- Select the Parent Wallet.
- Select the Sub Wallet.
- Enter the beneficiary details.
- Enter the transfer amount.
- Validate the amount against the Sub Wallet's available balance before anything is sent.
- Respect the configured transfer limits.
- Confirm the operation using the transaction PIN.
- Create the transfer request only after validation succeeds.
- Execute the transfer through the Banking Provider.
- Update the transaction status as the provider responds.
- If the balance is insufficient, reject the transfer before a request is sent.

**Example:** The user wants to transfer 2,000 EGP from the Electricity Sub Wallet.

```text
Parent Wallet
      |
      v
Electricity Sub Wallet
      |
      v
Available Balance: 3,000 EGP
      |
      v
Transfer: 2,000 EGP
      |
      v
Validate Balance
      |
      v
Execute Transfer
```

**Result:** The user can transfer money from a specific Sub Wallet.

---

## Phase 6: Transaction Lifecycle & Refund Management

**Goal:** Manage the full lifecycle of a transfer and handle failure cases. This is a critical phase, not an optional extra feature.

**Transaction statuses:**

| Status | Description |
|---|---|
| Pending | The transfer request has been created. |
| Processing | The bank has started processing the transfer. |
| Successful | The transfer succeeded. |
| Failed | The transfer failed. |
| Reversed | The bank reversed a previously successful operation. |
| Refunded | The amount was returned to the wallet. |

**Business rules:**

- Reserve the amount from the Sub Wallet as soon as a transfer request is created.
- On success, permanently deduct the reserved amount and record the transfer as Successful.
- On failure, cancel the reservation and return the amount to the Sub Wallet's available balance.
- While the bank has not determined a result, keep the amount reserved and query the status later.
- When a successful transfer is reversed, record a reversal entry, return the amount to the original Sub Wallet, and mark the transfer as Reversed.
- No money may be lost because of a failed transfer or a duplicate request.

### Scenario 1: Successful Transfer

- Reserve the amount from the Sub Wallet.
- Send the transfer request.
- The bank confirms success.
- Permanently deduct the amount from the budget.
- Record the transaction as Successful.

### Scenario 2: Failed Transfer

- Reserve the amount.
- Send the transfer request.
- The bank returns Failed.
- Cancel the reservation.
- Return the amount to the Sub Wallet's available balance.
- Record the transaction as Failed.

### Scenario 3: Pending Transfer

- Reserve the amount.
- The bank has not yet determined the result.
- Keep the amount reserved.
- Query the transfer status later.

### Scenario 4: Successful Then Reversed

- The transfer succeeded.
- The bank sends a notification reversing the operation.
- Create a reversal ledger entry.
- Return the amount to the original wallet.
- Record the transaction as Reversed.

**Result:** No money is lost due to failed transfers or duplicate requests.

---

## Phase 7: Transaction History

**Goal:** The user can view the transfers they have made.

**Business requirements and rules:**

- Display the transfer history.
- Show the details of each transfer.
- Search by Reference Number.
- Filter by date.
- Filter by status.
- Display the amount and the beneficiary.
- Display the source of funds.
- Moving money between the Parent Wallet and a Sub Wallet, or between Sub Wallets, is a private internal operation.
- Internal operations appear in the wallet details only; they never appear in the external transfer history.
- Only transfers that leave the user's wallets toward an external beneficiary appear in the transaction history.

**Example:**

```text
Internal operation:
Parent Wallet -> Electricity
Appears in wallet details as Private. Does NOT appear in External Transfer History.

External transfer:
Electricity -> Beneficiary
Appears in Transaction History.
```

**Result:** A clear transaction history that does not present internal operations as bank transfers.

---

## Final MVP Structure

```text
                 USER
                   |
                   v
             User Account
                   |
                   v
              Parent Wallet
                   |
        +----------+----------+
        |          |          |
        v          v          v
    Electricity   Rent      Personal
        |          |          |
        +----------+----------+
                   |
                   v
             Money Transfer
                   |
                   v
             Banking Provider
                   |
          +--------+--------+
          |        |        |
          v        v        v
       Success   Failed   Pending
          |        |        |
          v        v        v
       Complete  Release   Verify
                 Reserve   Status
```

---

## Final MVP Scope

| Phase | Business Feature | Priority |
|---|---|---|
| 1 | User Account | Essential |
| 2 | Bank Account Linking | Essential |
| 3 | Parent Wallet | Essential |
| 4 | Sub Wallets | Essential |
| 5 | Money Transfer | Essential |
| 6 | Refund & Reversal Management | Essential |
| 7 | Transaction History | Essential |

---

## Out of Scope for the MVP

- Bill payments.
- Electricity and gas services.
- Mobile recharge.
- Savings goals.
- Financial reports.
- Budget recommendations.
- Recurring payments.
- Cashback.
- Loyalty points.

These can be considered later if the product succeeds.

---

## Glossary

- **Parent Wallet** — the user's main wallet and the only real source of funds.
- **Sub Wallet** — a virtual allocation of the Parent Wallet's balance for a specific purpose.
- **Available Balance** — the amount a wallet can currently spend.
- **Reserved Balance** — the amount held for transfers that are still pending or processing.
- **Internal Operation** — a move of funds between the user's own wallets; not an external transfer.
- **External Transfer** — money sent from a Sub Wallet to a beneficiary through the Banking Provider.
- **Transaction PIN** — the user's confirmation secret for transfers.

---

## Final Business Design Principle

Every Sub Wallet is treated as an internal allocation of the Parent Wallet's balance, not as an independent bank account. Any external transfer must go through a reserve, confirm, and financial settlement cycle, with an actual funding source and the authority to execute the transfer through a licensed banking partner or service provider.

This keeps the MVP clear and limited, and avoids spending time on features that are not essential to the product at launch.
