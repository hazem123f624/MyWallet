# Documentation Data Builders — Project Standard

Every module's `/docs` is generated from **real executed API responses** (via its
`DocumentationCases`). To make those responses production-shaped instead of
null-heavy minimal rows, every module MUST provide a **Documentation Data Builder**
that seeds a realistic business scenario before the endpoints run.

This is the standard for **every Mobile and Dashboard module**, existing and future.

## The contract

- Base class: `App\Support\Documentation\DocumentationDataBuilder`.
- One builder per module: `Modules\<Module>\tests\Documentation\<Module>DataBuilder`,
  extending the base and implementing `build(array $context = []): array`.
- The module's `DocumentationCases` seeds its scenario with
  `<Module>DataBuilder::run([...])` and references the returned records.

Every builder MUST be:

| Rule | Meaning |
|---|---|
| **Deterministic** | Same records and values on every run. No faker / randomness. |
| **Idempotent** | Safe to call once per DocumentationCase group during the seed (use `firstOrCreate` + backfill). |
| **Realistic** | Values represent a genuine production use case (real dates, amounts, currencies, descriptions, media URLs, related records, counters, nested resources). |
| **Complete** | Populate every field that would normally hold a value. Leave a field null/empty ONLY when that is itself a real case — and document why in a comment. |

## Shared helpers (on the base class)

- `adminId()` — the seeded back-office admin id (default owner for admin-driven scenarios).
- `demoUserId($email)` — a seeded demo mobile-app user id.
- `mediaPath($bucket, $file)` — deterministic, realistic media path → stable response URL.

## Reference implementation

When the first wallet module is added, its builder becomes the reference implementation.
It should seed a fully-populated scenario — for example a verified user with a linked bank
account, a Parent Wallet, several colored Sub Wallets with real balances, and a mix of
pending, successful, failed, and reversed transfers — plus one intentionally-null case to
demonstrate a genuine optional scenario.

## Validation exposure (every endpoint)

The `/docs` page must show a Frontend developer everything needed to integrate
without reading backend code: required/optional, data types, min/max, enum values,
accepted file types, and max upload size.

Rule: **every body-validated endpoint (POST/PUT/PATCH) MUST pass its FormRequest**
to the DocumentationCase as `notes: <Request>::class`. The framework
(`DocumentationService::generateValidationDocs`) turns the request's `rules()` into
a readable table automatically — e.g.:

```
amount           : required, number, min 0.01, max 100000
sub_wallet_id    : required, exists
transaction_pin  : required, digits:4
reference        : optional, string, max 255
```

Never handwrite these — they are generated from the real FormRequest rules.

## Rules recap

1. Build meaningful business data **before** executing the API.
2. Never hand-edit JSON responses — improve the **seed data**, not the output.
3. Avoid nulls/empty arrays unless the null itself is a documented real case.
4. If several modules need similar data, add helpers to the base class rather than duplicating.
5. Pass every body-validated endpoint's FormRequest as `notes:` so validation is documented.
