---
paths:
  - 'tests/**,phpunit.xml'
---

# Testing Environment (Docker)

## Never run `php artisan test` / `pest` via plain `docker exec laravel_app ...` without forcing the environment

The app runs in Docker (`laravel_app`, `laravel_mysql`, `laravel_nginx`, `laravel_node`). The `laravel_app` container has a real OS-level env var `APP_ENV=local` baked in, and pointing at the live dev database (`DB_CONNECTION=mysql`, `DB_HOST=mysql`, `DB_DATABASE=personal_finance_management`).

`phpunit.xml` declares `force="true"` overrides (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`), but those overrides do **not** take effect when running tests through a plain `docker exec laravel_app php artisan test`. `app()->environment()` still resolves to `local` and the DB connection still resolves to the live `mysql`/`personal_finance_management` connection inside that shell.

`tests/Pest.php` applies `RefreshDatabase` to every Feature test. `RefreshDatabase` runs `migrate:fresh` (drop + recreate every table) once per process before wrapping each test in a transaction. Combined with the above, running the suite via a bare `docker exec` **drops and recreates every table in the real dev database**, permanently destroying whatever data was in it (the transaction rollback only undoes rows inserted *during* the test run, not the `migrate:fresh` itself).

**This has already happened once** (2026-09-06): a bare `docker exec laravel_app php artisan test` wiped every row from every application table in the dev database. Recovery was attempted via the MySQL binary logs (`log_bin=ON` on `laravel_mysql`), since no other backup existed.

### The fix

Always pass explicit env overrides on the `docker exec` command itself — these take precedence over both the container's baked env and `phpunit.xml`:

```bash
docker exec -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: laravel_app php artisan test --compact
```

Before trusting a test run against this container, verify with a throwaway assertion (`dump(app()->environment()); dump(config('database.default'));`) that it actually reports `testing` / `sqlite` — don't assume `phpunit.xml`'s forced env applied.

### Known unrelated failure under sqlite

`tests/Feature/Settings/BackupTest.php` fails under the sqlite connection (`SHOW CREATE TABLE` is MySQL-only syntax used by `DatabaseBackupService`). This is a pre-existing gap in that feature, not something introduced by other changes — don't try to "fix" it as a side effect of unrelated work.
