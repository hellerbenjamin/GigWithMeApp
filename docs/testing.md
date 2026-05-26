# Testing

## The suite runs on Postgres

Tests run against **Postgres**, the same engine as dev and production — not
SQLite. This matters because the two differ in ways that silently pass on SQLite
and then break in prod: case-insensitive `LIKE` vs. `ILIKE`, JSON operators,
`::text` casts, stricter typing, etc. (The venue search caught exactly this — a
Postgres `ilike` 500s on SQLite.)

### How it's wired

- **Isolated database.** Tests use a dedicated `testing` Postgres database so
  `RefreshDatabase` never touches the dev `db` database. `phpunit.xml` sets
  `DB_CONNECTION=pgsql` and `DB_DATABASE=testing`; host/port/credentials fall
  through from `.env` (the ddev `db` service).
- **Auto-created on start.** `.ddev/config.testdb.yaml` adds a `post-start` hook
  that creates the `testing` database if it doesn't exist (idempotent), so a
  fresh `ddev start` — or a teammate's first clone — just works. `RefreshDatabase`
  then migrates it on the first test run.

### Running

```sh
ddev exec "php artisan test"          # whole suite
ddev exec "php artisan test --filter SomeTest"
```

If the `testing` database ever gets into a bad state, drop it and let the hook
recreate it:

```sh
ddev exec "PGPASSWORD=db psql -h db -U db -d db -c 'DROP DATABASE IF EXISTS testing;'"
ddev restart
```

### CI note

Any CI runner needs a Postgres service and the `testing` database (or point
`DB_DATABASE` at whatever Postgres DB the runner provides). The ddev post-start
hook only covers local; CI must provision Postgres itself.
