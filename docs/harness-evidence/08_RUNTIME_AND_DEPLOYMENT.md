# Runtime and Deployment

## Runtime baseline

- Linux target
- PHP 8.3+; VM verification used PHP 8.4
- Laravel 13
- PostgreSQL authoritative database; VM verification used PostgreSQL 16.15
- React + TypeScript + Inertia frontend
- private local filesystem adapter for VM evidence; S3-compatible storage is the production abstraction
- Node required for asset build only, not production request handling
- Redis optional and not required for core mutation correctness

## Configuration and secrets

Environment variables supply `APP_KEY`, database credentials, session/security settings, evidence storage configuration, and optional Redis settings. `.env`, backups, private evidence, `vendor`, `node_modules`, generated frontend assets, logs, and local database artifacts are excluded from Git. Production must enable HTTPS-only secure cookies and configure trusted proxies according to deployment topology.

The demo database seeder is explicitly disabled when `APP_ENV=production`; its fixed local demonstration credential cannot be created by accidentally running the demo seeder in production.

## CI baseline

`.github/workflows/ci.yml` uses Ubuntu 24.04, PHP 8.4, Node 22, and a PostgreSQL 16 service. It performs locked Composer and npm installs, prepares a test environment, runs migrations, executes backend tests, runs `npm run typecheck`, and builds production frontend assets. It requires no production secrets.

At the time this document is committed, the workflow configuration has been parsed locally but runner verification can only be established after push. The final delivery report must use the actual GitHub Actions result rather than this design claim.

## Release sequence

1. Build an immutable application artifact from lock files.
2. Run backend tests, TypeScript check, production frontend build, and dependency/security gates available to the release environment.
3. Before destructive or irreversible schema change, produce a proven PostgreSQL + evidence backup set.
4. Prefer expand/migrate/contract and backward-compatible migrations.
5. Run migrations as a release gate; a failed migration stops promotion.
6. Deploy the application artifact.
7. Execute health, database, storage, and authenticated smoke checks.
8. Promote only after all required gates pass.

## Migration / partial deployment failure

A failed migration is not retried blindly. If the schema remains backward-compatible, the prior application artifact can be restored. If an irreversible data boundary was crossed, recovery uses the proven backup set or a reviewed forward repair. A partially deployed release remains unpromoted until application, schema, and storage checks agree on the same release state.

## Observability

Generic runtime logs and the authoritative audit ledger are separate concerns. Runtime diagnostics should carry request/sync correlation identifiers. Health checks cover application boot and database reachability; evidence consistency is verified explicitly by `sentinelops:verify-recovery`, not inferred from a superficial HTTP 200.

## Backup / restore

Use PostgreSQL-native logical backup/restore plus a synchronized evidence snapshot/manifest. Recovery is incomplete until attached evidence bytes are present and SHA-256 verified. The executed rehearsal is recorded in `06_FAILURE_AND_RECOVERY.md`.
