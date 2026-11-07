# MyWallet Admin Dashboard Frontend Tasks

Admin-only dashboard used by the Super Admin to oversee the platform, manage administrators, and control user account status.

**Status:** `[x]` implemented, `[~]` partial, `[ ]` pending.

## Architecture

- **Routes:** `/dashboard/login` is public; every dashboard page requires an authenticated `admins` session.
- **Authorization:** `EnsureDashboardAdmin` checks the session and active status. `EnsureDashboardSuperAdmin` protects Administrator Management and admin-only controls.
- **API access:** a scoped dashboard token is created server-side after login and injected only into authenticated dashboard pages.
- **Frontend:** Blade layouts/components plus browser-native ES modules. No framework or build step is required.
- **Styles:** `public/css/dashboard.css`.
- **Server-updated state:** user/admin statistics, listings, filters, forms, and status changes use the dashboard API.

## Phase 1: Admin Dashboard Foundation

- [x] Initialize the admin dashboard frontend.
- [x] Set up the frontend folder structure.
- [x] Configure API base URL and HTTP client.
- [x] Implement API error handling.
- [x] Implement authentication/session handling.
- [x] Create the main dashboard layout.
- [x] Create a responsive sidebar.
- [x] Create the top navigation bar.
- [~] Add an admin profile dropdown; profile and logout are shown directly in the topbar.
- [x] Add logout functionality.
- [x] Implement loading states.
- [x] Implement reusable error and empty states.
- [x] Implement reusable confirmation dialogs.
- [~] Implement reusable data-table wrappers; listing tables share styles but use page-specific markup.
- [x] Implement pagination controls.

## Phase 2: Admin Authentication

- [x] Create the Admin Login page.
- [x] Create the login form.
- [x] Validate email and password according to the backend contract.
- [x] Integrate the login flow.
- [x] Handle invalid credentials.
- [x] Handle unauthorized/inactive admins.
- [x] Redirect authenticated admins to the dashboard.
- [x] Protect dashboard routes.
- [x] Handle expired authentication sessions.
- [x] Implement server-side logout and token revocation.

## Phase 3: Dashboard Overview

- [x] Create the Dashboard Overview page.
- [x] Create reusable statistic cards.
- [x] Display total registered users.
- [x] Display active users.
- [x] Display suspended users.
- [x] Display total administrators.
- [x] Integrate dashboard statistics APIs.
- [~] Use loading indicators; full skeleton placeholders are still pending.
- [x] Implement dashboard API error handling.
- [x] Show recent users.
- [x] Add quick links to management pages.

## Phase 4: Admin Management

### Listing

- [x] Create the Admin Management page.
- [x] Display administrators in a data table.
- [x] Display admin name.
- [x] Display email.
- [x] Display account status.
- [x] Display creation date.
- [x] Display assigned roles.
- [x] Implement pagination.
- [x] Implement admin search.
- [x] Implement status filtering.
- [x] Implement sorting.

### Create Admin

- [x] Create the Add Admin form.
- [x] Implement form validation.
- [x] Integrate the Create Admin API.
- [x] Assign roles during creation.
- [x] Display success and error messages.

### Edit Admin

- [x] Create the Edit Admin form.
- [x] Load existing admin information.
- [x] Implement form validation.
- [x] Integrate the Update Admin API.
- [x] Allow password changes from the edit form.
- [x] Allow role changes.

### Admin Details

- [~] Display admin details in a modal rather than a separate page.
- [x] Display administrator information.
- [x] Display assigned roles.
- [x] Display account status.
- [x] Display account creation date.
- [x] Add suspend/activate admin actions.

## Phase 5: User Management

### User Listing

- [x] Create the User Management page.
- [x] Display registered users in a data table.
- [x] Display user name.
- [x] Display phone number.
- [x] Display email.
- [x] Display account status.
- [x] Display registration date.
- [x] Implement pagination.
- [x] Implement user search.
- [ ] Implement status filtering.
- [ ] Implement date filtering.
- [x] Implement sorting.
- [x] Add recycle-bin view.

### User Details

- [~] Display user details in a modal rather than a separate page.
- [x] Display personal information.
- [ ] Display phone verification status; not currently exposed by the user resource.
- [x] Display account status.
- [x] Display registration date.
- [ ] Display linked bank accounts; Phase 2 backend support is not implemented.
- [ ] Display Parent Wallet information; Phase 3 backend support is not implemented.

### User Account Status Management

- [x] Add Activate User action.
- [x] Add Suspend User action.
- [x] Create a confirmation dialog before changing status.
- [x] Integrate the Change User Status API.
- [x] Update the displayed status after successful requests.
- [x] Prevent unauthorized status changes in the UI.
- [x] Reject unauthorized status changes at the API layer.
- [x] Display API validation errors.

## Phase 6: UI/UX and Finalization

- [x] Implement responsive layouts.
- [x] Ensure the sidebar works on mobile screens.
- [x] Standardize typography and spacing.
- [x] Standardize buttons and form components.
- [x] Standardize status badges.
- [x] Implement toast notifications.
- [x] Implement consistent confirmation dialogs.
- [~] Use loading overlays/spinners; skeleton loading for every listing is still pending.
- [x] Add empty states to listing pages.
- [x] Handle network errors.
- [x] Handle expired sessions.
- [~] Test the login, admin creation, search, sorting, and navigation flows; complete form/API QA remains.
- [~] Test all API integrations; core listing/statistics/status flows are verified.
- [x] Test dashboard navigation.
- [x] Test authorization restrictions.
- [x] Perform a browser-based UI review.

## Phase 7: Complete Admin API Coverage

### Administrator Management

- [x] Add active/recycle-bin tabs.
- [x] Load administrator details from the show endpoint.
- [x] Add soft delete, restore, and force-delete actions.
- [x] Add a role filter.
- [x] Add role removal from the administrator details modal.
- [x] Refresh details and listings after mutations.

### Roles and Permissions

- [x] Create the Roles & Permissions page.
- [x] List, search, filter, sort, and paginate roles.
- [x] Create, edit, activate, deactivate, delete, restore, and permanently delete roles.
- [x] Add a role recycle bin.
- [x] Build the permission matrix from the permission-models endpoint.
- [x] Show granted permissions and assigned administrators.
- [x] Search available administrators and assign them to a role.
- [x] Remove administrators from a role.
- [x] Display role details from the show endpoint.

### Permission Models

- [x] Create the Permission Models page.
- [x] List modules and their permissions.
- [x] Reuse the permission-models endpoint in the role form.

### Activity Logs

- [x] Create the Activity Logs page.
- [x] Add filters for type, action, subject type, causer, date range, and search.
- [x] Show activity for a specific subject.
- [x] Export subject activity to CSV.
- [x] Download exported CSV files through the authenticated API.
- [x] Add activity shortcuts from user and administrator details.

### User Details and Authentication Recovery

- [x] Load user details from the show endpoint.
- [x] Add the administrator forgot-password flow to the login page.
- [x] Support requesting a reset code, resending it, verifying it, and setting a new password.
- [x] Keep API login/logout as external-client endpoints; dashboard sessions remain server-side.

## Phase 8: Dashboard Product Polish

### Foundation and Shell

- [x] Add shared icons, skeleton loaders, inline form errors, and password visibility.
- [x] Improve modal accessibility with Escape close, focus trapping, and scroll locking.
- [x] Upgrade toast notifications with icons, dismissal, and progress indicators.
- [x] Add skip link, focus-visible styles, and reduced-motion support.
- [x] Add sidebar icons, linked breadcrumbs, topbar user menu, and notification drawer.
- [x] Add global search palette and keyboard shortcuts.
- [x] Add persisted dark mode with system preference support.

### Overview and Analytics

- [x] Add aggregate analytics for user growth, account status, and activity volume.
- [x] Add overview charts with theme-aware styling.
- [x] Add recent activity, quick actions, refresh state, and stat-card icons.

### Page Enhancements

- [x] Add profile avatar upload/removal.
- [x] Add administrator avatar upload.
- [x] Add user status filter and user/admin page-size controls.
- [x] Add role permission search, select-all/clear, and selected counts.
- [x] Add activity-log quick date ranges, page-size control, readable diff view, and copy action.
- [x] Add login expired-session notice, password visibility, caps-lock warning, and resend countdown.

### Still Pending

- [ ] Settings and location management.
- [ ] Bulk user/admin actions.
- [ ] Full filtered CSV exports.
- [ ] User date filtering and extended wallet/bank details.
- [ ] Finance modules: bank accounts, wallets, transactions, beneficiaries, reports.
- [ ] Persistent notifications, sessions, and two-factor authentication.
- [ ] API proxy so the browser never receives the shared API key.

## Recommended Sidebar Structure

```text
MyWallet Admin
│
├── Dashboard
│
├── Administration
│   ├── Admin Management
│   ├── Roles & Permissions
│   └── Permission Models
│
├── Users
│   └── User Management
│
├── Monitoring
│   └── Activity Logs
│
└── Account
    ├── Profile
    └── Logout
```

## Frontend Priority

| Phase | Feature | Priority | Status |
|---|---|---|---|
| 1 | Dashboard Foundation | Critical | Implemented |
| 2 | Admin Authentication | Critical | Implemented |
| 3 | Dashboard Overview | High | Implemented |
| 4 | Admin Management | High | Implemented |
| 5 | User Management | Critical | Mostly implemented |
| 6 | UI/UX & Finalization | High | Mostly implemented |
| 7 | Complete Admin API Coverage | High | Implemented |
| 8 | Dashboard Product Polish | High | In progress |

## Outside MVP Scope

Do not add bill payments, electricity and gas services, mobile recharge, savings goals, financial reports, budget recommendations, recurring payments, cashback, or loyalty points.

## Remaining Gaps

- User status and date filtering.
- Phone verification and wallet/bank details in user details.
- Separating Admin/User details into dedicated pages if required.
- Skeleton loading for all listings.
- Complete manual browser QA of every form and API integration.
- Automated browser end-to-end tests.
- Server-side API proxy so the browser never receives the shared API key.
- Settings/locations APIs remain outside the dashboard scope until a settings page is requested.
