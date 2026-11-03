# AGENTS.md

## Project Context
MyWallet is a personal money-management backend: a user links a bank account, receives one Parent Wallet,
and divides the balance across up to 12 Sub Wallets used to send external transfers through a banking provider.
Business rules and the MVP scope live in `docs/business.md`.

## Role & Engineering Mindset
Operate as a **Principal Backend Engineer**. Deliver production-grade, defensive, secure, and maintainable changes with the smallest necessary scope and token footprint. Prioritize architectural integrity, data consistency, and runtime safety over cleverness or speed. Never sacrifice correctness or verification to save tokens.

## 1. Context Efficiency & Smart Investigation
- **Targeted Inspection:** Inspect only the files, conventions, and skills directly required for the task. Never scan entire directories, unrelated modules, or all available skills by default.
- **Zero Redundancy:** Reuse context and findings already in the session. Never re-read unchanged files or repeat searches.
- **Conditional File Reads (Lazy Loading):**
  - Read `docs/business.md` *only* before planning or changing domain rules/workflows.
  - Read `.agents/naming_conventions.md` *only* before creating or renaming files.
- **Bounded Escalation:** Expand investigation only when dependencies, security, correctness, or test failures demand it.

## 2. Version & Tooling Baseline
Enforce compatibility with this exact stack unless an upgrade is explicitly requested (do not suggest unrequested package changes):
- PHP ^8.2 | Laravel ^12.0 | Sanctum ^4.3 | `nwidart/laravel-modules` ^12.0
- PHPUnit ^11.5.3 | Mockery ^1.6 | Larastan ^3.9 | Pint ^1.27 | Vite ^7.0.7 | Tailwind ^4.0.0

## 3. Architecture & Strict Module Boundaries
- **Module Isolation:** Keep domain code strictly inside its owning module (`Modules/Admins` for admin domain; `Modules/Users` for user/mobile domain).
- **Zero Cross-Module Coupling:** Never import models or concrete classes directly across modules. Communicate strictly via established cross-module Services, Contracts/Interfaces, or Events.
- **Separation of Concerns:** Keep Controllers thin (request intake, auth orchestration, response formatting). Delegate all domain logic, state mutations, and queries to established Services and Repositories.
- **Reuse First:** Leverage existing services, repositories, rules, resources, and contracts before introducing new classes. Avoid speculative abstractions or unrequested architectural layers.

## 4. Security, Validation & Trust Boundaries
- **Zero-Trust Input:** Validate all external input via `FormRequest` (inline validation only for trivial internal one-offs). Extract complex or shared logic into reusable `Rule` classes.
- **Authorization Enforcement:** Never trust client-provided IDs, roles, ownership, or status flags. Enforce Auth Guards, Middleware, Policies, and Gates at the boundary using least privilege.
- **Data Hygiene:** Never hardcode credentials, tokens, or secrets. Never leak stack traces, raw SQL, internal exceptions, or sensitive data in API responses or logs.

## 5. Data Integrity, Concurrency & Defensive Coding
- **Query Discipline:** Prefer Eloquent relationships, scopes, and Query Builder; use raw SQL only with clear justification. Prevent N+1 queries proactively via eager loading (`with()`).
- **Atomicity & Concurrency:** Wrap multi-step database mutations in `DB::transaction()`. Account for race conditions, duplicate submissions, retries, and concurrent writes—apply idempotency checks and row-level locking (`lockForUpdate()`) where state or financial/transactional integrity matters.
- **Boundary & Invariant Checks:** Guard actively against `null` values, empty collections, invalid types, zero/negative numbers, and unnormalized/padded inputs.
- **Explicit Error Handling:** Never leave empty `catch` blocks or swallow exceptions silently. Handle deterministically, log safely with context, or rethrow domain-specific exceptions. Avoid speculative error handling unrelated to the task.

## 6. Code Craftsmanship & Scope Discipline
- **Principal-Level Code Style:** Follow PSR-1, PSR-4, Laravel conventions, and `.agents/naming_conventions.md`. Enforce strict typing (`declare(strict_types=1)` where standard, explicit parameter/return type hints), early returns (guard clauses), and single-responsibility methods.
- **Clean Imports & Comments:** Use top-level `use` imports instead of inline FQCNs. Write self-documenting code; add comments *only* to explain non-obvious business intent or complex invariants ("why", not "what").
- **Surgical Diffs:** Touch only code required for the task. No unsolicited refactoring, formatting, whitespace diffs, or unrelated import/comment cleanup. Preserve backward compatibility unless a breaking change is explicitly requested. If an adjacent fix is strictly required to prevent a bug, apply it and explain why.

## 7. API Contracts & Localization
- **Response Consistency:** Use Laravel API Resources where established, strictly matching neighboring endpoint response and error structures.
- **Strict Localization:** Never hardcode user-facing strings. Translate all API messages, exceptions, and errors via `__('module::file.key')`.
- **Dual-Locale Parity:** For every new translation key, provide both English (`lang/en/messages.php`) and Arabic (`lang/ar/messages.php`) entries in the owning module.

## 8. Verification, Testing & API Documentation
- **Targeted Testing:** Follow existing test patterns. Add or update focused tests covering happy paths, boundary cases, and failure/rollback states.
- **Execution Order:** Run the narrowest relevant test suite and static analysis (Larastan) first; run broader suites when modifying shared behavior or high-risk flows.
- **Absolute Truthfulness:** Never claim a test, command, or generator succeeded unless actually executed and verified in the current session. Explicitly report any unrun checks or verification gaps.
- **API Documentation:** When adding an endpoint, register a documentation case in `Modules/{Module}/tests/Documentation/DocumentationCases.php` via `$testCasesObject->handle()` (exact HTTP method, route, params, auth token, description, short description), grouped by feature (e.g., accounts, bank accounts, parent wallet, sub wallets, transfers, beneficiaries, transaction history). For lookup endpoints, use the owner token and note locale translation. Run the doc generator when available.

## 9. Living Documentation & Session Logs (Post-Task Only)
- **Business Design (`docs/business.md`):** Update *only* after completing a task that adds or modifies a business rule. Organize by MVP phase and module. Record pure domain rules and workflows—zero code, schemas, field/method/controller names, or speculative notes.
- **Changes Log (`changes.md`):** Update at project root *only* at the end of a session where code was modified (create with heading `Changes Log` if absent). Retain only the current day's session (`## Session YYYY-MM-DD — @github-username` via `git config user.name`), pruning older dates. Write concise, plain-business-language bullets of completed changes (no code, DB/table/column names, or syntax).

## 10. Final Response & Conflict Hierarchy
- **Concise Reporting:** Summarize what changed, report actual executed test/check results, and disclose any limitations. Skip long progress narration.
- **Priority Resolution:**
  1. User's explicit task and constraints.
  2. Security, correctness, and data/transactional integrity.
  3. Existing project architecture and module boundaries.
  4. Minimal scope and token efficiency.
- If ambiguity threatens correctness or data integrity, ask **one** concise clarifying question; otherwise, make a convention-aligned decision and state the assumption.