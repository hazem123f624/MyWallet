# MyWallet Documentation

This folder holds the product and engineering documentation for the MyWallet backend.

| File | What it covers |
|---|---|
| [`business.md`](business.md) | The complete MyWallet business design: platform overview, MVP phases, business rules, transaction lifecycle, scope, and glossary. |
| [`user-stories.md`](user-stories.md) | Product user stories and acceptance criteria for every MVP phase. |
| [`commands.md`](commands.md) | Every project command, common development command, module command, and security command with what it does. |
| [`artisan-commands.md`](artisan-commands.md) | The complete generated list of all Artisan commands available in this installation. |
| [`documentation-data-builders.md`](documentation-data-builders.md) | The standard for seeding realistic data before generating API documentation. |
| [`github-commit-structure.md`](github-commit-structure.md) | Commit message and pull-request conventions. |
| [`phase-1/`](phase-1/README.md) | Phase 1 implementation tasks, test-case specification, and execution report. |
| [`phase-5/`](phase-5/test-cases.md) | Phase 5 money transfer test cases, including transfer PIN confirmation. |

## Quick Links

- API documentation page: `/docs`
- Exported Postman collection: `api.json` at the project root
- Business source: `MyWallet.txt` at the project root

## Regenerate the API Collection

```bash
php artisan app:export-api-json
```

## Reset the Local Database

```bash
php artisan migrate:fresh --seed
```
