# Harness Route Trace

## 1. Architecture shape

**QUESTION**  
Should SentinelOps begin as a modular monolith or distributed services?

**HARNESS ROUTE USED**  
Application / Architecture → Architecture Planning Hub → Modular / Domain-Oriented Monolith → Data Authority → Cost/failure questions.

**NOTES / CONCEPTS CONSULTED**  
`planning/ARCHITECTURE_PLANNING_HUB.md`, `atlas/concepts/MODULAR_MONOLITH.md`, `atlas/concepts/DATABASE_AUTHORITY.md`.

**WHAT THE HARNESS CONTRIBUTED**  
It made authority, reversibility, dependency loss, and cost of complexity explicit decision criteria instead of treating deployment topology as aesthetic choice.

**OUTCOME**  
One deployable Laravel application with cohesive domain boundaries. PostgreSQL remains one authoritative transactional boundary. No microservices in V1.

**CLASSIFICATION**  
`STRENGTHENED_DECISION`

---

## 2. Offline state authority

**QUESTION**  
Can offline-created local state be considered current or authoritative until synchronization?

**HARNESS ROUTE USED**  
Application / Architecture → Offline-First → Data / State → Database Authority → Mutation Boundary.

**NOTES / CONCEPTS CONSULTED**  
`atlas/concepts/OFFLINE_FIRST.md`, `atlas/concepts/DATABASE_AUTHORITY.md`, `mesh/data/Mutation Boundary.md`.

**WHAT THE HARNESS CONTRIBUTED**  
It exposed the failure in a naive local-first interpretation: availability does not grant authority. It forced a distinction between locally captured commands and accepted server state.

**OUTCOME**  
IndexedDB stores pending commands, cached snapshots, and reconciliation results. A queued command has no guaranteed business authority until the server executes it.

**CLASSIFICATION**  
`EXPOSED_FAILURE`

---

## 3. Duplicate replay and concurrent custody

**QUESTION**  
How do five replays of the same mutation and two offline assignments preserve invariants?

**HARNESS ROUTE USED**  
Data / State → Concurrency Control → Idempotent Mutation → Transaction Boundary → Quality invariant enforcement.

**NOTES / CONCEPTS CONSULTED**  
`mesh/data/Concurrency Control.md`, `mesh/data/Idempotent Mutation.md`, `mesh/data/Transaction Boundary.md`.

**WHAT THE HARNESS CONTRIBUTED**  
It strengthened the model from request-level retry handling to a data-authority contract: stable operation identity, unique server record, transaction/locking, optimistic revision, and database uniqueness all cooperate.

**OUTCOME**  
`(organization_id, client_operation_id)` is unique. Accepted/rejected operations persist deterministic reconciliation. Asset custody uses a locked asset row, revision compare, and a PostgreSQL partial unique index for one active exclusive assignment. First valid server acceptance wins; stale competing commands are rejected, never last-write-wins.

**CLASSIFICATION**  
`IMPROVED_VERIFICATION`

---

## 4. Authorization drift and identifier substitution

**QUESTION**  
Does a command captured while authorized retain that authority offline, and can a caller substitute resource identifiers?

**HARNESS ROUTE USED**  
Security / Trust → Security Planning Hub → Authorization Boundary → Mutation Boundary.

**NOTES / CONCEPTS CONSULTED**  
`planning/SECURITY_PLANNING_HUB.md`, `atlas/concepts/AUTHORIZATION_RBAC.md`, `mesh/security/Authorization Boundary.md`.

**WHAT THE HARNESS CONTRIBUTED**  
It made authorization contextual to actor + action + target + current server state rather than a property of the client operation itself.

**OUTCOME**  
Every synchronized command re-runs server policy checks. Organization identity is derived from the authenticated membership/context; payload organization IDs cannot grant access. Target resources are loaded and organization-scoped before mutation.

**CLASSIFICATION**  
`EXPOSED_VULNERABILITY`

---

## 5. Recovery claim

**QUESTION**  
When can SentinelOps say a restore succeeded?

**HARNESS ROUTE USED**  
Resilience / Systems → Recovery Proof → Data Planning → Restore Verification.

**NOTES / CONCEPTS CONSULTED**  
`assurance/RECOVERY_PROOF.md`, `planning/DATA_PLANNING_HUB.md`.

**WHAT THE HARNESS CONTRIBUTED**  
It changed the acceptance condition from “database restored” to verified recovery across relational state and evidence bytes/integrity.

**OUTCOME**  
Restore procedure must validate schema/migrations, critical records, evidence object presence, recorded SHA-256 integrity, and application health before declaring recovery.

**CLASSIFICATION**  
`CHANGED_DECISION`

---

## 6. Audit history integrity

**QUESTION**  
Should audit history be ordinary mutable application data or a stronger historical boundary?

**HARNESS ROUTE USED**  
Security / Trust → Auditability → Data Authority → Mutation Boundary.

**NOTES / CONCEPTS CONSULTED**  
`atlas/concepts/AUDITABILITY.md`, `atlas/concepts/DATABASE_AUTHORITY.md`, `mesh/data/Mutation Boundary.md`.

**WHAT THE HARNESS CONTRIBUTED**  
Mostly confirmation. The project handoff already required trustworthy history, but the route reinforced that important state transitions should be attributable and resistant to silent mutation rather than treated as ordinary CRUD records.

**OUTCOME**  
Significant domain transitions write audit events in their transaction. PostgreSQL triggers reject ordinary audit UPDATE/DELETE. Restore verification checks that those protections survived backup/restore.

**CLASSIFICATION**  
`CONFIRMED_EXISTING_REASONING`

---

## 7. Privileged authority changes

**QUESTION**  
Should an already-authenticated administrator session be sufficient for role/authority changes?

**HARNESS ROUTE USED**  
Security / Trust → Security Planning Hub → Privileged Operation → Authorization Boundary.

**NOTES / CONCEPTS CONSULTED**  
`planning/SECURITY_PLANNING_HUB.md`, `mesh/security/Authorization Boundary.md`.

**WHAT THE HARNESS CONTRIBUTED**  
It strengthened the control from ordinary authenticated RBAC to explicit recent reauthentication for authority-changing operations while leaving phishing-resistant MFA as production hardening debt.

**OUTCOME**  
Membership/authority changes require recent password reauthentication in V1, with current server-side administrator authorization still required at the target resource boundary.

**CLASSIFICATION**  
`STRENGTHENED_DECISION`
