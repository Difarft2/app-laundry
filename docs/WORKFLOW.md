# Team workflow — app_laundry

## Branches
- `main`: stable/release branch.
- `develop`: integration branch.
- `feature/<module>`: individual feature work.
- `fix/<module>`: bug fixes.

## Daily workflow
1. Start from the latest `develop`.
2. Create a branch for one focused task.
3. Commit small, meaningful changes.
4. Push the branch and open a Pull Request to `develop`.
5. Ask the other developer to review.
6. Merge only after relevant checks pass.

## Commit convention
- `feat(scope): ...` — new feature
- `fix(scope): ...` — bug fix
- `refactor(scope): ...` — code restructuring
- `docs(scope): ...` — documentation
- `chore(scope): ...` — tooling/configuration
- `test(scope): ...` — tests

Examples:
- `feat(order): add order creation endpoint`
- `feat(services): add package management page`
- `fix(auth): handle expired token`

## Collaboration rules
- Do not commit `.env` files, credentials, API keys, or database dumps.
- Keep `.env.example` updated when adding environment variables.
- Coordinate before editing the same migration or shared configuration.
- Agree on API request/response shapes before frontend integration.
- Do not force-push shared branches.
- Keep PRs focused and explain how to test them.

## Local setup
1. Install the required PHP version/extensions, Composer, Node.js and pnpm.
2. Copy `backend/.env.example` to `backend/.env` and configure local MariaDB credentials.
3. Run `composer install` inside `backend`.
4. Run `php artisan key:generate` inside `backend`.
5. Run `php artisan migrate` after the database is created.
6. Run `pnpm install` inside each frontend project that has a `package.json`.
7. Start each project using its own documented development command.

Do not assume dashboard and landingpage use the same framework or scripts; check each `package.json`.
