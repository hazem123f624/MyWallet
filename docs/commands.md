# MyWallet Commands

All commands run from the project root (`/home/masarat/Desktop/MyWallet`).

> Run `php artisan list` to see every Laravel and package command available in the app.
> The complete generated snapshot is in [`artisan-commands.md`](artisan-commands.md).

---

## Project Commands

These commands are specific to this project.

| Command | What it does |
|---|---|
| `php artisan app:init-module {name}` | Creates a complete module scaffold: module folder, main and translation migrations, model, interface, repository, create/update requests, API resource, DTO, controller, policy, and documentation test case. It prompts before creating each optional file. `name` is the module name in PascalCase, for example `Wallet`. |
| `php artisan app:init-sub-module {name} {module}` | Adds a sub-resource scaffold inside an existing module. It creates the migration, model, interface, repository, requests, resource, DTO, controller, and policy for the sub-resource. `module` is the existing module name. |
| `php artisan app:init-test {name} {type}` | Runs the module's documentation cases and stores the real API response in the documentation database. `name` is the module name, `type` is the case group such as `index`, `show`, `recycle-bin`, `authentication`, `mobile-auth`, or `mobile-profile`. |
| `php artisan app:export-api-json` | Exports every documented endpoint to a Postman collection JSON file. By default it writes `api.json` at the project root. |
| `php artisan app:export-api-json --path=docs/api.json` | Exports the collection to a custom path instead of `api.json`. |
| `php artisan make:dto {name} {module}` | Creates a DTO class inside `Modules/{module}/app/DTOs`. The generated class is named `{name}DTO`. |
| `php artisan make:test-case {module}` | Creates `Modules/{module}/tests/Documentation/DocumentationCases.php` from the documentation stub if it does not already exist. |
| `php artisan security:api-key create "Client Name"` | Creates a new API client and prints its API key and secret. Use `--days=90` for an expiry or `--no-expiry` for a key that never expires. |
| `php artisan security:api-key rotate {id}` | Generates new credentials for an existing API client. The old credentials stop working immediately. |
| `php artisan security:api-key renew {id} --days=60` | Extends the expiry date of an API client. |
| `php artisan security:api-key list` | Lists all API clients and their status. |
| `php artisan security:api-key revoke {id}` | Deactivates an API client. |

### Documentation Workflow

```bash
# 1. Run a module's documentation cases
php artisan app:init-test Users mobile-auth

# 2. Export the collected endpoints to Postman
php artisan app:export-api-json
```

### API Version & Prefixes

The API version and named path segments are controlled from `.env`:

```bash
API_VERSION=v2
API_MOBILE_PREFIX=mobile
API_DASHBOARD_PREFIX=dashboard
```

- `API_VERSION` controls the version segment, for example `/api/v2/...`.
- `API_MOBILE_PREFIX` controls the user app segment, for example `/api/v2/mobile/...`.
- `API_DASHBOARD_PREFIX` controls the dashboard segment, for example `/api/v2/dashboard/...`.

After changing them, clear the config cache and regenerate the collection:

```bash
php artisan config:clear
php artisan app:export-api-json
```

Route files, documentation URLs, and the exported collection all read the same keys. After changing a prefix, rerun the relevant documentation cases before exporting so stored URLs use the new segment.

---

## Common Development Commands

| Command | What it does |
|---|---|
| `composer install` | Installs PHP dependencies. |
| `composer setup` | Runs the project setup script: install dependencies, create `.env`, generate the app key, run migrations, install Node packages, and build assets. |
| `composer dev` | Starts the local development stack: Laravel server, queue listener, log viewer, and Vite. |
| `composer test` | Clears the config cache and runs the test suite. |
| `php artisan key:generate` | Generates the application encryption key. |
| `php artisan serve` | Starts the Laravel development server, by default on `http://127.0.0.1:8000`. |
| `php artisan serve --host=0.0.0.0 --port=8000` | Starts the server on all network interfaces. |
| `php artisan migrate` | Runs pending database migrations. |
| `php artisan migrate:fresh --seed` | Drops all tables, runs every migration, and seeds the database. Use this for a clean local reset. |
| `php artisan db:seed` | Runs the database seeders. |
| `php artisan db:seed --class=DocumentSeeder` | Runs only the documentation seeder. |
| `php artisan test` | Runs the PHPUnit test suite. |
| `php artisan route:list` | Lists every registered route. |
| `php artisan route:list --except-vendor` | Lists application routes without vendor routes. |
| `php artisan config:clear` | Removes the cached configuration file. |
| `php artisan cache:clear` | Clears the application cache. |
| `php artisan view:clear` | Clears compiled Blade views. |
| `php artisan optimize:clear` | Clears config, route, view, event, and other cached files. |
| `php artisan storage:link` | Creates the public storage symlink. |
| `php artisan queue:work` | Starts a queue worker for background jobs. |
| `php artisan schedule:run` | Runs due scheduled tasks once. |
| `php artisan schedule:work` | Runs the scheduler continuously for local development. |
| `php artisan pail` | Streams application logs in real time. |
| `php artisan tinker` | Opens an interactive PHP shell with the application booted. |
| `php artisan scribe:generate` | Generates Scribe API documentation from routes when Scribe is configured. |

---

## Module Commands

The app uses `nwidart/laravel-modules`. These are the commands used most often.

| Command | What it does |
|---|---|
| `php artisan module:list` | Lists all modules and whether they are enabled. |
| `php artisan module:make {name}` | Creates a new module. |
| `php artisan module:make-controller --api {name} {module}` | Creates an API controller inside a module. |
| `php artisan module:make-model {name} {module}` | Creates a model inside a module. |
| `php artisan module:make-migration create_{table}_table {module}` | Creates a migration inside a module. |
| `php artisan module:make-request {name} {module}` | Creates a form request inside a module. |
| `php artisan module:make-resource {name} {module}` | Creates an API resource inside a module. |
| `php artisan module:make-repository {name} {module}` | Creates a repository inside a module. |
| `php artisan module:make-service {name} {module}` | Creates a service class inside a module. |
| `php artisan module:make-interface {name} {module}` | Creates an interface inside a module. |
| `php artisan module:make-policy {name} {module}` | Creates a policy inside a module. |
| `php artisan module:make-test {name} {module}` | Creates a test class inside a module. |
| `php artisan module:make-seed {name} {module}` | Creates a seeder inside a module. |
| `php artisan module:make-factory {name} {module}` | Creates a model factory inside a module. |
| `php artisan module:migrate {module}` | Runs migrations for one module. |
| `php artisan module:migrate` | Runs migrations for all modules. |
| `php artisan module:migrate-fresh` | Resets and re-runs all module migrations. |
| `php artisan module:seed {module}` | Runs a module's seeders. |
| `php artisan module:enable {module}` | Enables a module. |
| `php artisan module:disable {module}` | Disables a module. |
| `php artisan module:route-provider {module}` | Creates a route service provider for a module. |
| `php artisan module:publish-config {module}` | Publishes a module's config file to the application. |
| `php artisan module:publish-migration {module}` | Publishes a module's migrations to the application. |
| `php artisan module:publish-translation {module}` | Publishes a module's translations to the application. |

Run `php artisan list module` to see the full set of module commands, including generators for actions, casts, channels, components, enums, events, exceptions, helpers, jobs, listeners, mail, middleware, notifications, observers, providers, rules, scopes, and traits.

---

## Security Commands

| Command | What it does |
|---|---|
| `php artisan security:api-key create "Client Name"` | Creates an API client and prints its credentials. |
| `php artisan security:api-key create "Client Name" --days=30` | Creates an API client that expires after 30 days. |
| `php artisan security:api-key create "Client Name" --no-expiry` | Creates an API client that never expires. |
| `php artisan security:api-key rotate {id}` | Replaces an API client's credentials. |
| `php artisan security:api-key renew {id} --days=60` | Extends an API client's expiry by 60 days. |
| `php artisan security:api-key list` | Lists API clients. |
| `php artisan security:api-key revoke {id}` | Deactivates an API client. |

---

## Useful One-Liners

```bash
# Clean local reset with documentation and demo data
php artisan migrate:fresh --seed

# Check registered modules
php artisan module:list

# Check routes for the user app
php artisan route:list --except-vendor | grep mobile

# Check routes for the dashboard
php artisan route:list --except-vendor | grep dashboard

# Verify the exported collection is valid JSON
python3 -m json.tool api.json > /dev/null

# Run the test suite
php artisan test
```
