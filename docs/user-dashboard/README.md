# User Dashboard

The user-facing web dashboard for MyWallet.

**Route prefix:** `/user`

**Purpose:** let an existing mobile user sign in and manage their profile, security, bank accounts, Parent Wallet, Sub Wallets, beneficiaries, transfers, and transaction history from the browser.

## Routes

| Route | Page |
|---|---|
| `GET /user/login` | Login, registration, and password recovery |
| `POST /user/login` | Session login |
| `POST /user/logout` | Logout and token revocation |
| `GET /user` | Wallet overview |
| `GET /user/wallets` | Parent Wallet, Sub Wallets, and private wallet activity |
| `GET /user/bank-accounts` | Linked bank accounts |
| `GET /user/transfers` | Send Money and recent transfers |
| `GET /user/transactions` | Full external transfer history |
| `GET /user/beneficiaries` | Saved beneficiaries |
| `GET /user/profile` | Profile, avatar, phone change, account deletion |
| `GET /user/security` | Password and transaction PIN |

## Features

- Phone/country-code/password session login.
- Account registration with OTP verification.
- Forgot-password OTP flow.
- Suspended and unverified account blocking.
- Wallet overview with balances and distribution chart.
- Parent Wallet funding source status.
- Sub Wallet create, rename, restyle, delete, allocate, and redistribute.
- Bank account linking, OTP verification, primary selection, and unlinking.
- Profile updates, avatar handling, phone-change OTP, and account deletion.
- Password change and transaction PIN set/change/reset.
- Beneficiary create, edit, and delete.
- Send Money with Sub Wallet and beneficiary selection, limits, and transaction-PIN confirmation.
- Transfer detail modal with lifecycle events and reversal/refund information.
- Paginated external transfer history with reference, status, and date filters.
- Paginated private wallet activity with operation-type filtering.
- Internal operations are marked Private and never appear in external transfer history.
- Dark mode and responsive layout using the existing dashboard design system.

## Architecture

- Controllers: `App\Http\Controllers\User\UserAuthController`, `UserPageController`.
- Middleware: `EnsureUserDashboard`.
- Views: `resources/views/user/`.
- JS: `public/js/user/`.
- CSS: `public/css/user.css` plus the shared `public/css/dashboard.css`.
- API access uses the existing `/api/v1/mobile/*` endpoints and a session-held Sanctum token.
- Expired user sessions return to `/user/login?expired=1`; dashboard sessions return to `/dashboard/login?expired=1`.

## Current Limitations

- External transfers use the sandbox Banking Provider until a licensed bank or aggregator is connected.
- Manual Parent Wallet deposits/top-ups are not implemented.
- The browser receives the scoped user Sanctum token, matching the admin dashboard pattern.
- CSV/PDF transaction export is not included.
