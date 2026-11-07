# Changes Log

## Session 2026-11-07 — @Hazemwalid290

- Completed dashboard coverage for the administrator management APIs: active and recycle-bin views, delete/restore/permanent delete, role filtering, detailed record loading, and role removal.
- Added a Roles & Permissions area with role creation and editing, status control, permission assignment, member assignment and removal, recycle-bin recovery, and permission display.
- Added a Permission Models area that lists the modules and permissions available to roles.
- Added an Activity Logs area with filters, subject-specific history, CSV export and authenticated download, plus activity shortcuts from user and administrator details.
- Completed user details loading from the dedicated details API.
- Added an administrator password-recovery flow to the login page.
- Added a shared dashboard foundation: icon system, skeletons, accessible modals, upgraded toasts, inline form errors, password visibility, skip link, focus states, and reduced-motion support.
- Added the new dashboard shell: sidebar icons, breadcrumbs, user menu, notification drawer, global search palette, keyboard shortcuts, and dark mode.
- Added aggregate dashboard analytics and charts for user growth, account status, and activity volume.
- Added an improved overview with recent activity, quick actions, refresh state, and chart panels.
- Redesigned the Account Status card with a proportional rounded donut, centered user total, readable active/suspended statistics, percentage tooltips, loading state, and a proper no-data state.
- Refined the dashboard visuals: smooth gradient user-growth chart with legible axes, dark glass metric cards, glowing active sidebar state with section dividers, zebra tables, glowing status badges, and right-aligned dates.
- Added profile avatar upload/removal, administrator avatar upload, user and administrator page-size controls, user status filtering, role permission search/select-all, and activity-log quick ranges/diff view/copy.
- Added automated coverage for dashboard analytics and the full dashboard flow.
- Settings and location management pages remain pending.
- Added Phase 3 Parent Wallet management: one wallet per user, automatic creation after verification, legacy backfill, verified-primary funding source linking, status and balance views, and automatic synchronization when bank accounts are verified, changed, or unlinked.
- Added Phase 3 feature tests and documentation.
- Added Phase 4 Sub Wallet management: create/list/view/rename/restyle/delete, 12-wallet limit, funds allocation from the Parent Wallet, redistribution, reserved-fund deletion guard, internal operation ledger, and Parent Wallet Sub Wallet summary.
- Added Phase 4 feature tests and documentation.
- Added the `/user` web dashboard with session login, password recovery, wallet overview, Parent/Sub Wallet management, bank-account linking and verification, profile/avatar/phone updates, account deletion, password change, transaction PIN controls, and dark-mode responsive styling.
- Added user dashboard registration with OTP verification.
- Fixed user dashboard asset paths so login controls, password visibility, dark mode, and page actions work correctly.
- Added visual color swatches and icon previews to Sub Wallet creation/editing and wallet cards.
- Added user dashboard access tests; the full suite now passes with 153 tests and 691 assertions.
