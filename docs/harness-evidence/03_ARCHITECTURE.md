# Architecture

## Selected shape

SentinelOps V1 is a modular monolith: one Laravel deployment and one PostgreSQL authority boundary, with domain-oriented application code for Identity/Organizations, Assets/Custody, Incidents/Evidence, Audit, Sync, Maintenance, and Reporting.

## Dependency direction

HTTP/Inertia/API adapters → application actions/commands → domain models/policies → persistence/storage adapters. Cross-module state changes occur through explicit application actions rather than controllers directly mutating unrelated models.

## Authoritative stores

| Store | Authority |
| --- | --- |
| PostgreSQL | Users/memberships/roles, assets, revisions, custody, incidents, evidence metadata and hashes, maintenance, sync operation ledger, audit records |
| Private evidence storage | Authoritative evidence bytes; DB metadata binds bytes by storage key + SHA-256 + size + MIME |
| IndexedDB | Non-authoritative cached read models, pending client commands/evidence staging state, reconciliation results |
| Redis | Optional acceleration/queueing only; never required for core correctness |

## Core transaction boundaries

- Asset custody assignment: operation ledger claim + authorization + target lock + revision compare + active-custody invariant + audit + asset revision update.
- Incident creation: operation ledger claim + authorization + target/revision check + incident insert + asset operational-state update + audit. Evidence association is finalized only for durable staged evidence owned by the same organization/user context.
- Incident close: policy + current-state check + closure metadata + audit; closed incident/evidence history is not rewritten.

## Offline synchronization model

1. Client creates UUID `client_operation_id` and monotonic client sequence.
2. Command and base resource revision are stored in IndexedDB before optimistic UI presentation.
3. Client sends a batch when online. Server treats entries independently to permit partial reconciliation.
4. Server derives current actor/membership, validates schema, and computes a canonical payload hash.
5. Unique `(organization_id, client_operation_id)` establishes idempotency. Reuse with different payload hash is a conflict/security error; exact replay returns the stored result.
6. Mutation action runs inside a PostgreSQL transaction. Target rows are organization-scoped and locked where contention threatens invariants.
7. Base revision mismatch returns `STALE_CONFLICT` and authoritative current summary; no business mutation occurs.
8. Current authorization failure returns `AUTHORITY_REVOKED`; an offline capture timestamp never grants authority.
9. Accepted mutation commits domain changes, audit, and operation result atomically.
10. Client stores reconciliation, replaces cached state with server-returned authority, and removes only terminally reconciled queue entries.

## Custody conflict semantics

There is one active exclusive custody assignment per asset. The server serializes assignment against the asset row and PostgreSQL additionally enforces a partial unique index on active assignment. When two offline commands race, the first command that is valid against the current revision commits. The second receives a stale/conflict result describing the newer revision/current assignee. Its rejected operation remains evidence in the sync ledger/audit trail.

## Evidence consistency model

Evidence upload is staged before attachment. Staged bytes receive a server-generated storage key and SHA-256 metadata, but are not incident evidence until a domain transaction claims them. A failed incident transaction leaves a non-authoritative staged object eligible for TTL cleanup. The reverse case is prevented by requiring durable staged bytes before the DB transaction can mark evidence attached. Restore verification rehashes attached evidence; missing/corrupt objects make recovery incomplete.

## Rejected alternatives

- Microservices: unnecessary distributed authority/failure modes for V1.
- Browser/entity last-write-wins replication: violates custody, permission-drift, and historical-integrity requirements.
- Redis-required mutation correctness: adds an avoidable correctness dependency.
- Public evidence URLs: weakens access-control and incident confidentiality boundaries.
