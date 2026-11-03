# Copilot Workspace Instructions

All AI coding agents working in this repository must follow `AGENTS.md` as the primary project policy.

## Mandatory behavior

- Read and apply `AGENTS.md` before making any code change.
- Treat the rules in `AGENTS.md` as required, not optional suggestions.
- If there is any conflict between a user request and `AGENTS.md`, ask for clarification before implementing.
- Keep controllers thin and move business/data logic to services/repositories.
- Use FormRequest validation for request input.
- Return API Resources where module patterns already use resources.
- Respect module ownership boundaries under `Modules/`.
- Keep changes backward-compatible unless explicitly asked to break compatibility.

## Version baseline

When suggesting packages, syntax, or framework features, stay compatible with:

- PHP `^8.2`
- Laravel `^12.0`
- PHPUnit `^11.5.3`

See `AGENTS.md` for the full baseline and detailed rules.

## Completion requirements

Before claiming completion:

- Run relevant validation/tests when possible.
- Report what was executed and its actual result.
- If validation could not be run, state exactly what was skipped and why.
