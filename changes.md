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
- Made the API version configurable from one environment key instead of hard-coding it in route files, documentation URLs, and the exported collection.
- Added a configurable dashboard path prefix alongside the mobile prefix, and updated routes, documentation cases, grouping, and docs to use it.
- Added a documentation index, a full command reference, a generated list of all Artisan commands, and the MVP business design under the documentation folder.
- Merged the separate business context and MVP design into a single `docs/business.md` source of truth.
- Added a `docs/phase-1` folder with the Phase 1 implementation checklist, test-case specification, current status, and known gaps.
- Redesigned the `/docs` page with a simpler light layout, clearer endpoint cards, searchable navigation, copy buttons, mobile navigation, and a configurable base URL.
- Added a night mode to the documentation page that follows the system preference by default and remembers the reader's choice.
- Polished the documentation tables so field names wrap cleanly on smaller screens.
- Added method filters to the documentation page so readers can narrow endpoints by HTTP method.
- Corrected the descriptions of the custom module and documentation commands shown by `php artisan list`.
- Verified the application boots, all migrations and seeds run successfully, the documentation page loads, and the existing tests pass.
