# Engineering Decisions

## D-001 — Modular monolith
- **Question:** single deployable system or distributed services?
- **Alternatives:** modular monolith; microservices; separate sync service.
- **Selected:** modular monolith.
- **Evidence:** small bounded V1, shared transactional invariants, harness guidance to prefer coherent deployable boundaries before distributed complexity.
- **Failure modes:** internal coupling can grow; one deployment has larger blast radius.
- **Mitigations:** explicit application-action boundaries, policy boundaries, architecture tests/static conventions, module dependency documentation.
- **Verification:** tests target module authority/invariants; no controller performs cross-domain hidden mutation.
- **Revisit trigger:** independent scaling/failure/SLA requirements proven by runtime evidence.

## D-002 — PostgreSQL owns domain truth; IndexedDB owns pending intent only
- **Alternatives:** local-first replicated entities; last-write-wins; server-authoritative command log.
- **Selected:** server-authoritative command execution with client operation queue/cache.
- **Failure impact:** local users may see pending state later rejected.
- **Mitigation:** explicit pending/rejected UI and deterministic reconciliation.
- **Verification:** stale and permission-drift tests.

## D-003 — Idempotency is persisted domain infrastructure
- **Selected:** unique organization + client operation ID, canonical payload hash, persisted terminal result.
- **Failure mode addressed:** retry/replay produces duplicate assignments/incidents.
- **Security:** same key with different payload is rejected, not silently replayed.
- **Verification:** five identical submissions create one authoritative effect; mismatched payload reuse returns conflict.

## D-004 — Optimistic revision plus row lock/database invariant
- **Selected:** client supplies base revision; server scopes + locks target row, compares current revision, then mutates. PostgreSQL constraints remain the last line of defense.
- **Failure mode addressed:** two offline assignments and stale clients.
- **Operational consequence:** losing command requires human-visible reconciliation, not automatic overwrite.

## D-005 — Current authorization at synchronization time
- **Selected:** queued operations carry intent, not delegated authority. Policies execute under current server membership/role.
- **Failure mode addressed:** revoked users retaining offline power indefinitely.
- **Verification:** queue while authorized, revoke permission, then sync -> rejection/no business mutation.

## D-006 — Staged private evidence + integrity binding
- **Selected:** upload to private staging, compute SHA-256, attach only inside authorized domain transaction, TTL-clean abandoned staging.
- **Failure mode addressed:** object/DB partial failures and public evidence leakage.
- **Verification:** failed incident mutation does not create attached evidence; missing/corrupt evidence fails recovery verification.

## D-007 — Privileged step-up for destructive/authority-changing admin actions
- **Selected:** normal authenticated session is insufficient for role/authority changes and recovery-sensitive actions; recent password reauthentication is required in V1. TOTP MFA remains a production hardening path rather than a fake local-only control.
- **Threat:** stolen admin session.
- **Residual risk:** password-confirmation step-up is weaker than phishing-resistant MFA.
- **Revisit trigger:** any internet-facing production deployment.

## D-008 — Recovery requires composite proof
- **Selected:** DB restore + evidence verification + application/schema checks.
- **Alternative rejected:** database restore alone.
- **Verification:** recovery command/report enumerates missing/hash-mismatched attached evidence and exits non-zero.
