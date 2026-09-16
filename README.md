# ACES Next Generation

Financial Operations Platform for Cooperatives

## Local setup

1. Copy `.env.example` to `.env`.
2. Set `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` for the local database.
3. Set a unique `SEED_ADMIN_PASSWORD` of at least 12 characters before running the user seeder.
4. Run the project's migration command before starting the application.

`.env` is intentionally ignored by Git and must never be committed.

## Security requirements

- Production runs with `APP_ENV=production` and `APP_DEBUG=false`.
- Production database credentials must come from environment configuration.
- All POST requests require the application's CSRF token.
- Financial/member mutations are role-protected; loan approval/rejection is admin-only, while accounting mutations require an accounting or admin role.
- Login attempts are rate-limited and recorded for abuse protection.
- Member-registration drafts are kept in the authenticated server-side session rather than browser `localStorage` because they contain personal information.
