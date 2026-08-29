# Failure and Recovery Model

| Failure | Required behavior |
| --- | --- |
| Duplicate sync replay | Return stored reconciliation; no duplicate business effect. |
| Same operation ID, changed payload | Reject as idempotency-key reuse/conflict. |
| Two offline custody assignments | Serialize on server; first valid commit wins; later stale command receives authoritative reconciliation. |
| Permission revoked while offline | Reject under current policy; preserve rejected operation evidence. |
| Stale client | Reject by revision unless an operation is explicitly commutative. |
| Evidence staged, incident fails | Staging remains unattached and cleanup-eligible; no authoritative attached evidence row is created. |
| DB commit attempted without durable staged evidence | Reject before incident/evidence finalization. |
| Evidence missing after DB restore | Recovery verification fails; system is not declared recovered. |
| Migration failure | Stop deployment; retain/restore prior release where schema compatibility permits; otherwise use reviewed forward repair or proven restore. |
| Partial deployment | Health/migration gates prevent promotion; rollback the application artifact if compatible, otherwise execute the documented recovery path. |
| Audit write failure | Significant authoritative mutation fails rather than committing without its audit evidence. |
| Redis unavailable | Core synchronous mutation path continues; optional asynchronous work may degrade. |
| Object store unavailable | Evidence staging fails closed; an incident without evidence is allowed only when the user explicitly submits no evidence. |

## Backup model

A recoverable SentinelOps backup is a set, not a single database file:

1. PostgreSQL logical backup plus checksum/manifest.
2. Evidence storage snapshot/copy plus a manifest of attached keys, sizes, and SHA-256 values.
3. Application release and migration metadata sufficient to identify the compatible runtime.
4. Required configuration/secrets restored through the deployment secret channel, never embedded in backup documentation.

Backups contain sensitive operational information and require access control equivalent to the source data.

## Restore acceptance rule

A restore is successful only after PostgreSQL loads, expected schema protections exist, critical record counts/sentinels are coherent, custody/orphan invariants hold, every attached evidence object exists and matches its recorded SHA-256, and the application can boot/read the restored authority. A database-only restore with missing evidence is explicitly **INCOMPLETE**, even if `pg_restore` exits successfully.

## Executed recovery rehearsal — 2026-08-29

Source database: `sentinelops` on PostgreSQL 16.15. A custom-format `pg_dump` was created without owner/ACL coupling, then restored into a separately created `sentinelops_recovery_test` database. The primary database was not dropped or overwritten.

Authoritative counts before backup and after fresh restore matched exactly:

- organizations: 1
- users: 5
- assets: 1
- incidents: 1
- audit events: 2
- sync operations: 1
- attached evidence rows: 1

Post-restore checks found zero duplicate active custody assignments. PostgreSQL restored both audit immutability triggers (`audit_events_no_update`, `audit_events_no_delete`) and both evidence metadata immutability triggers (`evidence_no_update`, `evidence_no_delete`). `sentinelops:verify-recovery` passed against the restored database while the evidence bytes were present.

To test the required negative path, the evidence storage directory was temporarily removed while keeping the restored PostgreSQL database intact. Recovery verification returned exit code `1`, reported the attached evidence object as `MISSING`, and emitted `RECOVERY INCOMPLETE`. The evidence directory was then restored and the same verifier returned `RECOVERY VERIFIED` again.

Recovery dump evidence for this rehearsal:

- custom-format dump size: 76,624 bytes
- dump SHA-256: `019f5dfdbfc60395689221b9f3eda35f2c26e1430246874ee727083f962685f8`

The dump itself is an ephemeral VM artifact and is intentionally not committed.
