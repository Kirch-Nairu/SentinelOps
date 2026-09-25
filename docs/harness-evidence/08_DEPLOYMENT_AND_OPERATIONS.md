# Deployment and Operations Baseline

## Runtime hypothesis

- Linux
- PHP 8.3+ / Laravel 13
- PostgreSQL authoritative database
- Web server/PHP runtime
- private local or S3-compatible evidence storage
- Node only for asset build, not production request handling
- Redis optional

## Configuration and secrets

Environment variables supply APP_KEY, DB credentials, session/security settings, evidence disk/bucket, and optional Redis values. Secrets are never committed. Production enables HTTPS-only secure cookies and trusted proxy configuration appropriate to topology.

## Release sequence

1. Build immutable application artifact from locked dependencies.
2. Run backend tests, frontend tests/build, lint/static gates, and dependency audits where network is available.
3. Back up authoritative DB and evidence according to change risk before destructive/irreversible migration.
4. Run backward-compatible migrations first where feasible.
5. Deploy application artifact.
6. Run health + authenticated smoke checks.
7. Promote only if gates pass.

## Migration failure

Migrations must be designed for safe retry and favor expand/migrate/contract sequencing. A failed migration stops promotion. If schema remains compatible, rollback application artifact. If migration crossed an irreversible boundary, restore from proven backup or perform reviewed forward repair; never repeatedly run destructive migration commands blindly.

## Observability

Structured application logs include request correlation IDs and sync operation IDs, but the audit ledger remains separate from generic logs. Health checks cover app boot and database reachability; storage/recovery checks are explicit rather than hidden in a superficial HTTP 200.

## Backup/restore

Use `pg_dump`/`pg_restore` (or equivalent PostgreSQL-native tools) plus evidence snapshot and manifest. The supplied recovery verifier checks attached evidence presence/hash after restore. Restore rehearsal evidence belongs in `07_VERIFICATION.md`.
