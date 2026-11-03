# Business Context

This file documents business rules, domain logic, and user workflows for the MyWallet platform.
It is a living document — updated only when a task is fully completed and a new or changed rule is confirmed.

Do NOT add technical implementation details, code snippets, or database schemas here.

---

## Platform Overview

MyWallet is a personal money-management app built around one funding source and many virtual wallets.
A user links a bank account, receives a single Parent Wallet, and splits that balance across Sub Wallets
that represent how the money is intended to be used.

**Actors:**
1. **Super Admin** — platform oversight; manages admin accounts and user account status.
2. **User (Mobile)** — owns the wallet, links a bank account, creates Sub Wallets, and sends money.
3. **Banking Provider** — licensed bank or aggregator that executes external transfers.

---

## Module: User Account

### Business Rules

- A user can create an account, sign in, and sign out.
- A user must verify their phone number with an OTP before using the app.
- A user can manage their profile and change their password.
- A transaction PIN is required to confirm transfers.
- An account is either Active or Suspended; a Suspended account cannot use the app.
- Only a Super Admin can change an account's status.

---

## Module: Bank Account Linking

### Business Rules

- The app shows the list of supported banks.
- A user selects a bank and links their own bank account as the source of funds.
- Ownership of the bank account must be verified before it becomes usable.
- A user can view all linked accounts.
- Exactly one linked account is the primary account.
- A user can unlink a bank account.
- Bank linking depends on the availability of a bank or Banking Aggregator that permits the integration.

---

## Module: Parent Wallet

### Business Rules

- A Parent Wallet is created automatically for every user.
- The Parent Wallet is linked to the user's bank account.
- The Parent Wallet reports the total allocated balance, the available balance, the balance reserved for pending operations, and its status.
- The Parent Wallet is the only real source of funds; Sub Wallets are internal allocations of it.

---

## Module: Sub Wallets

### Business Rules

- A user can create Sub Wallets to divide their money by purpose.
- A user can rename, recolor, re-icon, and delete a Sub Wallet.
- A user can have at most 12 Sub Wallets.
- A user allocates an amount to each Sub Wallet from the Parent Wallet.
- A user can redistribute funds between Sub Wallets.
- Each Sub Wallet shows its own balance.
- A Sub Wallet can never spend more than its available balance.

---

## Module: Money Transfers

### Business Rules

- A transfer starts from a chosen Parent Wallet and a chosen Sub Wallet.
- The user enters beneficiary details and the transfer amount.
- The amount is validated against the Sub Wallet's available balance before anything is sent.
- The transfer must respect the configured transfer limits.
- The user confirms the transfer with the transaction PIN.
- A transfer request is created only after validation succeeds.
- The request is executed through the Banking Provider and its status is updated as the provider responds.
- If the balance is insufficient, the transfer is rejected before a request is sent.

---

## Module: Transaction Lifecycle & Refund Management

### Transaction Statuses

- **Pending** — the transfer request has been created.
- **Processing** — the bank has started processing the transfer.
- **Successful** — the transfer succeeded.
- **Failed** — the transfer failed.
- **Reversed** — the bank reversed a previously successful operation.
- **Refunded** — the amount was returned to the wallet.

### Business Rules

- The amount is reserved from the Sub Wallet as soon as a transfer request is created.
- On success, the reserved amount is permanently deducted and the transfer is recorded as Successful.
- On failure, the reservation is cancelled and the amount returns to the Sub Wallet's available balance.
- While the bank has not determined a result, the amount stays reserved and the status is queried later.
- When a successful transfer is reversed, a reversal entry is recorded, the amount returns to the original Sub Wallet, and the transfer becomes Reversed.
- No money may be lost because of a failed transfer or a duplicate request.

---

## Module: Transaction History

### Business Rules

- A user can view the transfers they have made.
- A user can open the details of each transfer.
- Transfers can be searched by Reference Number.
- Transfers can be filtered by date and by status.
- Each entry shows the amount, the beneficiary, and the source of funds.
- Moving money between the Parent Wallet and a Sub Wallet, or between Sub Wallets, is a private internal operation.
- Internal operations appear in the wallet details only; they never appear in the external transfer history.
- Only transfers that leave the user's wallets toward an external beneficiary appear in the transaction history.

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

---

## Glossary

- **Parent Wallet** — the user's main wallet and the only real source of funds.
- **Sub Wallet** — a virtual allocation of the Parent Wallet's balance for a specific purpose.
- **Available Balance** — the amount a wallet can currently spend.
- **Reserved Balance** — the amount held for transfers that are still pending or processing.
- **Internal Operation** — a move of funds between the user's own wallets; not an external transfer.
- **External Transfer** — money sent from a Sub Wallet to a beneficiary through the Banking Provider.
- **Transaction PIN** — the user's confirmation secret for transfers.
