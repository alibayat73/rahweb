# AGENTS

## Quick Commands
- Full local setup: `composer setup`
- Dev stack (server, queue, pail, Vite): `composer dev`
- PHP lint/format check: `composer lint:check`
- PHP format fix: `composer lint`
- Frontend format: `npm run format`
- Frontend lint: `npm run lint`
- Frontend typecheck: `npm run types:check`
- Tests (minimal): `php artisan test --compact`

## CI Parity
- Lint workflow runs: `composer lint`, `npm run format`, `npm run lint`.
- Test workflow builds assets (`npm run build`) before running PHPUnit.

## Repo Gotchas
- `npm` is configured with `ignore-scripts=true` in `.npmrc`.
- PHPUnit uses sqlite in-memory DB (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`) per `phpunit.xml`.

## Frontend Wiring
- Vite plugins: Laravel + Inertia + Tailwind + Vue + Wayfinder (see `vite.config.ts`).
- Wayfinder-generated paths are ESLint-ignored: `resources/js/actions/**`, `resources/js/routes/**`, `resources/js/wayfinder/**`.
- shadcn-vue setup and aliases live in `components.json`.
