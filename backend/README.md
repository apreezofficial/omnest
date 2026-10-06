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
