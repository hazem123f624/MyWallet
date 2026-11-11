# MyWallet Documentation

This folder holds the product and engineering documentation for the MyWallet backend.

> **Current status — 2026-11-10:** all seven MVP phases are implemented. The automated suite
> passes with 202 tests / 987 assertions, and the exported Postman collection contains
> 104 requests. Live Egyptian bank/aggregator integration, manual deposits, and the admin
> wallet-suspension control remain documented gaps rather than missing MVP tasks.

| File | What it covers |
|---|---|
| [`business.md`](business.md) | The complete MyWallet business design: platform overview, MVP phases, business rules, transaction lifecycle, scope, and glossary. |
| [`user-stories.md`](user-stories.md) | Product user stories and acceptance criteria for every MVP phase. |
| [`commands.md`](commands.md) | Every project command, common development command, module command, and security command with what it does. |
| [`artisan-commands.md`](artisan-commands.md) | The complete generated list of all Artisan commands available in this installation. |
| [`documentation-data-builders.md`](documentation-data-builders.md) | The standard for seeding realistic data before generating API documentation. |
| [`github-commit-structure.md`](github-commit-structure.md) | Commit message and pull-request conventions. |
| [`phase-1/`](phase-1/README.md) | Phase 1 implementation tasks, test-case specification, execution report, and admin frontend tasks. |
| [`phase-2/`](phase-2/README.md) | Phase 2 bank account linking tasks, endpoints, and test-case specification. |
| [`phase-3/`](phase-3/README.md) | Phase 3 Parent Wallet tasks, endpoints, and test-case specification. |
| [`phase-4/`](phase-4/README.md) | Phase 4 Sub Wallet tasks, endpoints, and test-case specification. |
| [`user-dashboard/`](user-dashboard/README.md) | User-facing web dashboard routes, features, and limitations. |
| [`phase-5/`](phase-5/README.md) | Phase 5 transfer tasks, endpoints, and test-case specification. |
| [`phase-6/`](phase-6/README.md) | Phase 6 lifecycle, callbacks, reconciliation, and reversal/refund tasks and test cases. |
| [`phase-7/`](phase-7/README.md) | Phase 7 external transfer history, search/filter behavior, and private wallet activity. |

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
