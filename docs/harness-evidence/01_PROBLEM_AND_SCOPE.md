# Problem and Scope

## Problem

Field personnel must register and act on operational assets and incidents even when connectivity is unreliable, without allowing an offline browser to become an authority over organization data, custody, incident history, or permissions.

## Actors

- Administrator
- Supervisor
- Technician / Field Operator
- Security / Incident Officer
- Auditor / Read-Only Reviewer

## V1 goals

1. Server-enforced organization isolation and RBAC.
2. Asset registry with location, operational state, revision, and exclusive custody state.
3. Incident creation tied to an asset, with evidence and explicit lifecycle.
4. Append-oriented audit records for authoritative business and security-sensitive mutations.
5. Offline operation queue with stable operation identity, idempotent replay, current-time authorization, optimistic conflict detection, deterministic reconciliation, and visible rejection.
6. A maintenance follow-up path and basic operational dashboard.
7. Reproducible PostgreSQL-backed runtime and recovery procedure with database/evidence consistency verification.

## Non-goals

- Microservices or distributed service decomposition.
- Full ERP breadth.
- Generic workflow-engine construction.
- Physical QR camera support if it compromises the core synchronization/security work; stable QR-compatible asset codes are sufficient for V1.
- Magical automatic conflict merging.
- Treating IndexedDB as authoritative state.
- Claiming production readiness from a local pilot.

## Constraints

- PostgreSQL is the server authority for relational/domain state.
- Browser IndexedDB is a cache and pending-command journal only.
- Evidence bytes use a storage abstraction; VM development may use a private local disk.
- Redis is optional and cannot be required for the correctness of the core path.
- Authorization is evaluated against current server-side authority at command execution/synchronization time.
- No force push, no main-branch implementation, no harness mutation.

## V1 acceptance criteria

A technician can capture an asset incident offline, including evidence metadata/file staging; the operation receives a stable client operation ID; a later synchronization is authenticated and authorized by the server; one accepted replay produces one domain effect; stale or unauthorized operations are rejected with a reconciliation result; the accepted incident affects asset operational status; authorized supervisors/security users can observe and follow up; and all authoritative transitions are auditable.
