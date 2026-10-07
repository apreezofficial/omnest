# Omnest API

Vanilla PHP 8.2+ and MySQL 8. No framework: a small router, middleware pipeline and PDO wrapper.

## Run

```bash
cp .env.example .env      # set DB_* values
composer install
composer migrate          # runs migrations/*.sql in order
composer serve            # http://localhost:8000/health
composer test
```

Feature tests need a MySQL/MariaDB database called `omnest_test` (they're skipped without one).
Defaults: `127.0.0.1:3306`, user `root`, no password. Override with `TEST_DB_HOST`, `TEST_DB_PORT`,
`TEST_DB_DATABASE`, `TEST_DB_USERNAME`, `TEST_DB_PASSWORD`. The suite drops and re-migrates that database.

## Endpoints (`/api/v1`)

| Method | Path | Auth | What |
|---|---|---|---|
| POST | `/auth/register` | - | name, email, password → user + parent token, sends verify email |
| POST | `/auth/login` | - | email, password → user + parent token |
| POST | `/auth/logout` | parent | revokes this token |
| GET | `/auth/me` | parent | current user |
| POST | `/auth/email/verify` | - | token from email link |
| POST | `/auth/email/resend` | parent | new verify link |
| POST | `/auth/password/forgot` | - | email → reset link (always 202) |
| POST | `/auth/password/reset` | - | token, password → signs out all sessions |
| GET/POST | `/children` | parent | list / create (name, age_tier, birth_date?, avatar?) |
| GET/PATCH/DELETE | `/children/{id}` | parent | own children only (404 otherwise) |
| GET | `/children/{id}/devices` | parent | paired phones |
| POST | `/children/{id}/pairing-codes` | parent | 6-digit code, 10 min, + `omnest://pair?code=` QR payload |
| DELETE | `/devices/{id}` | parent | unpair: device token stops working |
| POST | `/device/pair` | - | code + phone info → per-device token (10 tries / 10 min / IP) |
| GET | `/device/me` | device | this phone + its child |
| PUT | `/device/fcm-token` | device | push token |

Tokens: parent `omp_…` (30-day sliding expiry), device `omd_…` (until unpaired), email links `omx_…`.

## Layout

```
public/index.php      front controller
routes/api.php        route table (/api/v1/...)
config/app.php        config read from .env
migrations/           numbered SQL files: 0001_name.sql, 0002_name.sql, ...
bin/                  CLI scripts (migrate; later: worker, scheduler)
src/
  App.php             boot, service wiring, global middleware
  Http/               Request, Response, Router, Pipeline, Middleware/
  Database/           Database (PDO wrapper), Migrator
  Auth/               token resolvers (parent, device)
  Controllers/        thin: validate -> call service -> respond
  Services/           business logic
  Repositories/       SQL lives here
  Support/            Container, Validator, Env, logging, rate limiting
```

## Conventions

**Responses.** Success: `{"data": ..., "meta": {...}}`. Error: `{"error": {"code": "validation_failed", "message": "...", "details": {...}}}`.

**Validation.** Every endpoint validates input with `Validator::validate($request->all(), [...rules])` and uses only the returned array.

**Middleware aliases.** `auth.parent` (attaches `user`), `auth.device` (attaches `device`), `throttle.auth` (10/min per IP), `throttle.api` (120/min per IP). Global: CORS, error handler, JSON body parsing.

**SQL.** Prepared statements only, through `Database`. No string-built values.

**Tokens.** API and device tokens are random 32+ bytes; only the SHA-256 hash is stored.
