# User Dashboard

The user-facing web dashboard for MyWallet.

**Route prefix:** `/user`

**Purpose:** let an existing mobile user sign in and manage their profile, security, bank accounts, Parent Wallet, and Sub Wallets from the browser.

## Routes

| Route | Page |
|---|---|
| `GET /user/login` | Login, registration, and password recovery |
| `POST /user/login` | Session login |
| `POST /user/logout` | Logout and token revocation |
| `GET /user` | Wallet overview |
| `GET /user/wallets` | Parent Wallet and Sub Wallets |
| `GET /user/bank-accounts` | Linked bank accounts |
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
- Dark mode and responsive layout using the existing dashboard design system.

## Architecture

- Controllers: `App\Http\Controllers\User\UserAuthController`, `UserPageController`.
- Middleware: `EnsureUserDashboard`.
- Views: `resources/views/user/`.
- JS: `public/js/user/`.
- CSS: `public/css/user.css`.
- API access uses the existing `/api/v1/mobile/*` endpoints and a session-held Sanctum token.

## Current Limitations

- No transfers, beneficiaries, or transaction history until later phases.
- The browser receives the scoped user Sanctum token, matching the existing admin dashboard pattern.
