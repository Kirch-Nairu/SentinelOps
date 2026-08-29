# SentinelOps

SentinelOps is a runnable engineering-harness pilot for secure, offline-first field operations: organization-scoped users and roles, asset/custody authority, incident/evidence handling, maintenance follow-up, append-only audit history, and idempotent offline synchronization.

The pilot evaluates Project Second Brain at pinned SHA `d213ad56cea6a9a1fb3a56c159c448343d0d0777`. Harness evidence and the critical evaluation are under `docs/harness-evidence/`.

## Stack

- Laravel 13 / PHP 8.3+
- React + TypeScript + Inertia
- PostgreSQL
- IndexedDB offline queue/cache
- private local evidence adapter for development; S3-compatible abstraction for production

Redis is optional and not required for core correctness.

## Local setup

Create PostgreSQL databases named `sentinelops` and `sentinelops_test`, then configure `.env` from `.env.example` with local credentials.

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm run build
php artisan serve
```

The demo seeder creates Administrator, Supervisor, Technician, Security Officer, and Auditor accounts for local evaluation. The demo seeder refuses to run in `production`.

## Verification

```bash
php artisan test
npm run typecheck
npm run build
php artisan sentinelops:verify-recovery
```

`sentinelops:verify-recovery` verifies PostgreSQL connectivity, core relational invariants, audit/evidence immutability triggers, and the presence/SHA-256 integrity of attached evidence bytes. A database-only restore with missing evidence must fail this gate.

## Engineering evidence

See:

- `docs/harness-evidence/03_ARCHITECTURE.md`
- `docs/harness-evidence/05_SECURITY.md`
- `docs/harness-evidence/06_FAILURE_AND_RECOVERY.md`
- `docs/harness-evidence/07_VERIFICATION.md`
- `docs/harness-evidence/08_RUNTIME_AND_DEPLOYMENT.md`
- `docs/harness-evidence/09_HARNESS_EVALUATION.md`

The primary build handoff remains in `HANDOFF_SENTINELOPS_HARNESS_PILOT.md`.
