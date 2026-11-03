# GitHub Commit Structure

Use one simple format for both branch name and commit subject.

## Format

prefix/task_id/task_title_snake_case

Example:

- feat/142/add_budget_summary_endpoint
- fix/201/resolve_null_pointer_in_otp_verification

## Rules

- Commit subject must match branch name exactly.
- `task_id` is required and must not include project code/name.
- `task_title_snake_case` must be lowercase, clear, and use underscores.
- Avoid vague titles like `update`, `changes`, `fix_stuff`.

## Allowed Prefixes

- **feat**: new feature.
- **fix**: bug fix.
- **refactor**: code restructure without feature/bug change.
- **chore**: non-feature/non-fix housekeeping.
- **perf**: performance improvement.
- **ci**: CI related changes.
- **ops**: infrastructure/deployment/operations changes.
- **build**: build tools, dependencies, versioning.
- **docs**: documentation changes.
- **style**: formatting-only changes.
- **revert**: revert previous commit.
- **test**: add/fix tests.

## Quick Examples

- docs/1/add_agents_md_file
- refactor/188/simplify_security_middleware_pipeline
- test/220/add_register_request_validation_tests

## Team Practice

- One commit = one intent.
- Do not mix unrelated changes in one commit.
