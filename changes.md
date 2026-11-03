# Changes Log

## Session 2026-11-03 — @Hazemwalid290

- Created the MyWallet backend from the reusable modular starter kit and removed all previous valuation-platform business modules.
- Kept the shared core: documentation generation, phone verification codes, translation, repository request handling, media storage, activity logging, and the security layer.
- Retained the admin, user, and settings modules and removed everything tied to the old multi-company model.
- Added the MyWallet business context covering accounts, bank linking, the Parent Wallet, Sub Wallets, transfers, refunds and reversals, and transaction history.
- Updated the project guide, naming conventions, README, and API documentation introduction for MyWallet.
- Removed the old deployment workflows and refreshed the seeded API documentation.
- Reorganised the API documentation into the user app (authentication and account) and the dashboard (administration and user management).
- Removed the settings, country, city, and nationality documentation that is not needed by the product.
- Added a command that exports the documented endpoints into a Postman collection JSON file at the project root, with a configurable API prefix variable.
- Verified the application boots, all migrations and seeds run successfully, the documentation page loads, and the existing tests pass.
