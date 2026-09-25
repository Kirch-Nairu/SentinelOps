# Invariants and Test Plan

## Invariants

1. A user cannot read or mutate a resource outside an organization in which current server authority permits the action.
2. An exclusive asset has at most one active custody assignment.
3. Replaying an identical accepted sync operation never repeats the business effect.
4. Reusing an operation ID for a different canonical payload is rejected.
5. A queued offline command has no guaranteed authority until server execution.
6. Authorization is evaluated using current server membership/role at synchronization time.
7. A mutation with a stale base revision cannot silently overwrite newer protected state.
8. Closing an incident preserves incident history, evidence metadata, evidence bytes, and audit trail.
9. Normal application paths cannot update/delete audit rows.
10. Attached evidence refers only to durable private bytes with recorded integrity metadata.
11. A failed domain transaction cannot leave evidence represented as attached authoritative incident evidence.
12. A database restore is not a successful recovery while required attached evidence is absent or hash-invalid.
13. Significant authoritative mutation and its audit event succeed/fail together.

## Automated test map

- Authentication success/failure/session rotation behavior.
- Role matrix and read-only auditor negative paths.
- Cross-organization asset/incident/evidence rejection.
- Asset create/update policy.
- Concurrent/existing active custody rejection and database uniqueness.
- Sync replay x5 -> one assignment/incident.
- Same idempotency ID + changed payload -> conflict.
- Stale base revision -> deterministic `STALE_CONFLICT`.
- Permission changed between queue capture and sync -> `AUTHORITY_REVOKED`/403 result, no mutation.
- Identifier substitution across organization -> rejection.
- Incident open/investigating/closed transitions and closure restrictions.
- Evidence staging/attach authorization and transaction rollback path.
- Audit emitted for accepted significant mutations and protected from mutation.
- Recovery verifier detects missing/hash-mismatched evidence.

## Critical end-to-end target

Login as technician → open asset → go offline → stage incident command/evidence → queue visible as pending → restore connectivity → sync → accepted incident and asset status returned → supervisor/security sees incident → audit timeline contains authoritative transition.
