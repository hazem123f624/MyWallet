# MyWallet Backend

Laravel backend for **MyWallet**, a personal money-management app: one linked bank account,
one Parent Wallet, and up to 12 Sub Wallets used to divide and transfer money.

The project is a modular Laravel application built on a reusable starter kit. It currently
ships the **core infrastructure** only — business wallet modules are added on top.

---

## Architecture Overview

- **Modular Architecture**: Powered by `nwidart/laravel-modules`. Each feature lives inside `Modules/`.
- **Service Layer**: Business logic stays out of controllers and lives in dedicated services.
- **DTO Pattern**: Strict data handling between layers.
- **Repository & Interface Pattern**: Data access is decoupled from the rest of the application.
- **API Resources**: Standardized API response structures.

### Current Modules

- `Modules/Admins` — back-office admin accounts, roles, and settings.
- `Modules/Users` — mobile user accounts.
- `Modules/Settings` — platform settings and locations.

---

## Core Infrastructure

- **Documentation system** — generated `/docs` page backed by real executed API responses.
- **OTP service** — phone verification codes with attempt limits and resend support.
- **Translation system** — dual-locale (English/Arabic) storage and API translation helpers.
- **Repository request layer** — `handelRequests` for consistent database operations.
- **Media handling** — uploads, storage paths, and file responses.
- **Activity log** — audit trail for model changes.
- **Security layer** — API keys, request signatures, anti-replay, IP filtering, rate limiting, security headers, and payload limits.
- **Authentication** — Laravel Sanctum with a driver factory for JWT/Passport.

---

## Security System

### Middlewares
1. **ApiKeyMiddleware** — authorized clients only.
2. **SignatureMiddleware** — request signing to prevent tampering.
3. **TimestampAntiReplayMiddleware** — replay-attack protection.
4. **IpFilterMiddleware** — whitelist/blacklist access control.
5. **RateLimitMiddleware** — brute-force and DDoS protection.
6. **SecurityHeadersMiddleware** — CSP and other HTTP security headers.
7. **PayloadSizeLimitMiddleware** — large-payload protection.

---

## Developer Workflow

### Initialize a new module
```bash
php artisan app:init-module {ModuleName}
```

### Define documentation cases
Edit `Modules/{ModuleName}/tests/Documentation/DocumentationCases.php` using the
`TestCaseBuilder` fluent API.

### Generate live documentation
```bash
php artisan app:init-test {ModuleName} {Type}
```

### Export the API collection
```bash
php artisan app:export-api-json
```
This writes `api.json` at the project root as a Postman collection.

### Change the API version and prefixes
Set these keys in `.env`, then clear config and regenerate the collection:
```bash
API_VERSION=v2
API_MOBILE_PREFIX=mobile
API_DASHBOARD_PREFIX=dashboard
```

```bash
php artisan config:clear
php artisan app:export-api-json
```

- `API_VERSION` controls the version segment (`v1` by default).
- `API_MOBILE_PREFIX` controls the user app segment (`mobile` by default).
- `API_DASHBOARD_PREFIX` controls the dashboard segment (`dashboard` by default).

Set the `api_prefix` collection variable to change the version segment across every request. After changing a path prefix, rerun the relevant documentation cases before exporting so stored URLs use the new segment.

### Run the app locally
```bash
php artisan migrate --seed
php artisan serve
```

The API documentation is available at `/docs`.

---

## Documentation

Project documentation lives in [`docs/`](docs/README.md):

- [`docs/business.md`](docs/business.md) — the complete business design, rules, and workflows.
- [`docs/commands.md`](docs/commands.md) — every project, development, module, and security command.
- [`docs/artisan-commands.md`](docs/artisan-commands.md) — the complete generated list of all Artisan commands.
- [`docs/documentation-data-builders.md`](docs/documentation-data-builders.md) — documentation data standard.
- [`docs/github-commit-structure.md`](docs/github-commit-structure.md) — commit and pull-request conventions.
- [`docs/phase-1/`](docs/phase-1/README.md) — Phase 1 tasks and test cases.

---

## Requirements

- PHP `^8.2`
- Laravel `^12.0`
- MySQL
- Composer and Node.js for asset tooling

---

## Key Folder Structure

```text
├── app/
│   ├── Services/          # Auth, Security, File, OTP, Documentation, Translation
│   ├── Console/Commands/  # Custom automation commands
│   └── Http/Traits/       # Shared API response and file traits
├── Modules/               # Feature-based modules
│   ├── Admins/
│   ├── Users/
│   └── Settings/
├── docs/                  # Business context and project standards
└── config/                # Security, auth, OTP, documentation settings
```
