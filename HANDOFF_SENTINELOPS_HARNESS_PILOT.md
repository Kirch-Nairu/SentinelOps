# HANDOFF — SENTINELOPS ENGINEERING HARNESS PILOT

## Mission

Build SentinelOps as a real greenfield software system and use the Project Second Brain engineering cognition mesh as an external planning/decision/security/failure/verification harness.

This is not a mockup exercise. The output must be a runnable application on the code-writer agent's VM, with automated tests and a draft PR.

This is also not a test of whether the harness can generate lots of documents. The test is whether it materially improves the architecture, threat model, invariants, failure handling, implementation constraints, verification strategy, release discipline, and recovery posture of the software.

---

## Repositories

### Application repository

`https://github.com/Kirch-Nairu/SentinelOps.git`

Default branch: `main`

Implementation branch to create:

`KIRCH-HARNESS-PILOT-SENTINELOPS-V1`

Do not implement directly on `main`.

### Cognition harness repository

Repository:

`Kirch-Nairu/project-second.brain`

Harness branch:

`KIRCH-ENGINEERING-COGNITION-MESH-V3`

Pinned evaluation baseline:

`d213ad56cea6a9a1fb3a56c159c448343d0d0777`

The branch may move later. For this pilot, checkout the pinned SHA above so the build can be reproduced against a known harness state.

If the harness repository or pinned commit cannot be accessed, stop and report exactly:

`HARNESS SOURCE UNAVAILABLE`

Do not reconstruct or invent missing harness guidance.

Current measured harness scale at handoff time:

- 849 scoped graph nodes
- 4,030 semantic edges
- 722 `mesh/**/*.md` knowledge notes
- 7 macro families
- 23 semantic lanes
- 7 explicit bridge corridors
- 17 Salryn packet/route nodes
- 18 Talibon packet/route nodes
- 134 unresolved links globally
- 1 unresolved zoning/lane/integration link

The unresolved-link debt is known. Do not silently treat unresolved references as evidence.

---

# Product to build

## SentinelOps

A secure, offline-first field operations, asset, incident, evidence, maintenance, approval, audit, and recovery platform for organizations whose field users may work with unreliable connectivity.

The system must be complex enough to exercise architecture, data authority, authorization, offline synchronization, failure handling, testing, deployment, and recovery decisions, but bounded enough to produce a working V1 pilot.

## Core actors

At minimum:

- Administrator
- Supervisor
- Technician / field operator
- Security or incident officer
- Auditor / read-only reviewer

The agent may refine these roles if the harness and implementation evidence justify it.

## Core modules

V1 should contain a coherent vertical slice across these areas:

1. Organizations / workspace boundary
2. Users and role-based access
3. Asset registry
4. Asset custody / assignment
5. Locations
6. Incidents
7. Evidence attachments
8. Maintenance records
9. Approval-controlled actions where appropriate
10. Audit history
11. Offline mutation queue + synchronization
12. Operational notifications/events
13. Basic reporting/dashboard
14. Backup/recovery capability or a reproducible recovery procedure
15. Administrative configuration

Avoid building shallow CRUD screens for every possible feature. Prefer fewer workflows implemented deeply and correctly.

---

# Required engineering pressure cases

The pilot must deliberately handle or explicitly reject these cases with documented reasoning and tests where feasible:

1. Two offline users attempt to assign the same asset.
2. The same offline mutation is retried multiple times.
3. Evidence upload succeeds but the related incident mutation fails, or vice versa.
4. A user's permissions change while that user's client is offline.
5. A client submits a stale mutation after newer server state exists.
6. A malicious user changes an asset/incident identifier in an authorized request.
7. An administrator session/token is stolen or reused.
8. A database migration fails partway through deployment.
9. A restore succeeds for the database but associated evidence/object files are incomplete.
10. A deployment partially succeeds and must be rolled back.
11. An incident is closed and later evidence/history must remain trustworthy.
12. A vulnerability is discovered after release and requires a controlled remediation path.

The system does not need magical conflict resolution. It does need explicit conflict semantics, idempotency semantics, authority rules, and failure behavior.

---

# Initial technical hypothesis — NOT automatic authority

Start by evaluating, not blindly accepting, this stack and architecture:

- Laravel 13 / PHP 8.3+
- React + TypeScript + Inertia
- PostgreSQL as authoritative server database
- IndexedDB for browser-side offline queue/cache
- Redis for queue/cache if justified; core pilot must still be reproducible if Redis is unavailable
- S3-compatible object storage abstraction for evidence; local filesystem adapter is acceptable for VM development
- Docker for reproducible runtime if practical in the VM
- Linux-targeted deployment model
- Pest/PHPUnit for backend tests
- Vitest where frontend unit testing is useful
- Playwright for critical end-to-end flows if practical
- GitHub Actions for CI

Architectural starting hypothesis:

**Modular monolith with explicit module boundaries.**

The harness must either support that choice with evidence or cause the implementation agent to change it. Record the decision.

Do not split into microservices merely to appear advanced.

---

# Harness-use contract

The code-writer agent must actually consult the cognition harness before major design/implementation decisions.

At minimum, route the project through these concern families:

- Product / Delivery
- Application / Architecture
- Security / Trust
- Quality / Assurance
- Resilience / Systems
- Platform / Runtime
- Governance / Intelligence

Use the harness lanes, district/system notes, bridge corridors, standards, project patterns, and decision/failure/security concepts that are genuinely relevant.

Do not create fake references to make the harness look useful.

## Required evidence directory

Create:

`docs/harness-evidence/`

At minimum produce:

### `00_HARNESS_BASELINE.md`

Record:

- harness repository
- branch
- exact SHA used
- date/time
- tools/notes consulted
- unresolved-link limitations encountered

### `01_PROBLEM_AND_SCOPE.md`

Problem framing, actors, goals, non-goals, constraints, V1 boundary, acceptance criteria.

### `02_ROUTE_TRACE.md`

A concise trace of how the work moved through the harness.

For each major engineering concern, record:

- input question
- family/lane/district consulted
- relevant notes used
- conclusion or constraint produced
- whether the harness added value, confirmed an existing idea, produced noise, or exposed a gap

This file is central to evaluating the harness.

### `03_ARCHITECTURE.md`

Include:

- context
- module decomposition
- dependency direction
- authoritative stores
- transaction/mutation boundaries
- offline/online topology
- synchronization model
- evidence storage model
- deployment topology

### `04_DECISIONS.md` or `decisions/`

At minimum record decisions for:

- modular monolith vs other architecture
- offline operation-log vs entity/snapshot synchronization
- idempotency model
- conflict-resolution model
- tenant/organization isolation
- evidence immutability/integrity
- privileged reauthentication/MFA policy
- object storage and database consistency model
- backup/recovery model

Each decision should contain:

- question
- alternatives
- chosen answer
- evidence/support
- assumptions
- failure impact
- security impact
- reversibility
- verification plan
- revisit trigger

### `05_THREAT_MODEL.md`

Include assets, actors, trust boundaries, attack surfaces, abuse cases, authorization risks, evidence/upload risks, offline-client risks, admin risks, and mitigations.

### `06_FAILURE_AND_RECOVERY.md`

Failure matrix covering sync, database, object/evidence storage, authorization drift, migrations, partial deployment, queues, backups, and restore.

### `07_INVARIANTS_AND_TEST_PLAN.md`

Define system invariants first, then map tests to them.

Examples that may be refined:

- an exclusive asset cannot have two active custodians unless explicitly modeled otherwise;
- replaying the same accepted operation does not duplicate the mutation;
- authorization is re-evaluated server-side when offline operations are synchronized;
- closed incident history is not silently rewritten;
- audit history cannot be bypassed by normal application mutation paths;
- restoring a backup is not considered successful until database and evidence consistency are verified.

### `08_DEPLOYMENT_AND_OPERATIONS.md`

Environment model, config/secrets, migrations, health checks, logging, CI/release gates, rollback, backup, restore verification, and operational runbook basics.

### `09_HARNESS_EVALUATION.md`

This is mandatory.

At the end of the pilot classify harness contributions:

- **Changed decision** — harness materially changed what would have been built.
- **Strengthened decision** — same direction, but constraints/testing/security became better.
- **Confirmed only** — useful confirmation, little design change.
- **Noise/overhead** — added process without useful engineering value.
- **Missing knowledge** — real problem not adequately represented by the harness.

List specific notes/lanes that were repeatedly useful and ones that were never useful.

Do not protect the harness from criticism.

---

# Required application behaviors

## Asset workflow

A user can register/view an asset with at least:

- stable identifier
- human-readable code
- description/type
- status
- current location
- custody/assignment state
- maintenance state

QR generation/lookup is desirable for the pilot. Physical camera scanning is optional if it would distract from the core engineering test.

## Incident workflow

A field user can create an incident tied to an asset/location, including severity, narrative, evidence metadata/files, and workflow state.

Incident transitions must be explicit and authorization-aware.

## Offline workflow

A supported workflow must work while disconnected, at minimum:

- capture an incident or asset-related field mutation locally;
- assign a client-generated operation ID;
- queue it in IndexedDB or equivalent;
- synchronize when connectivity returns;
- perform server-side authentication/authorization validation;
- enforce idempotency;
- detect stale/conflicting state;
- return a deterministic reconciliation result to the client.

The offline client is not an authority simply because it captured a mutation first.

## Audit workflow

Security-sensitive and business-significant mutations must create queryable audit events containing sufficient actor/action/target/time/context information for later investigation.

Do not rely solely on generic application logs as the audit ledger.

## Evidence workflow

Evidence storage must have explicit ownership and integrity semantics. Record metadata in the authoritative database and make partial database/object-storage failure behavior explicit.

---

# Testing expectations

Do not chase arbitrary coverage percentages.

Tests must prove the important invariants and dangerous boundaries.

Minimum expected categories:

- authorization matrix tests
- organization/tenant isolation tests
- asset assignment conflict tests
- idempotent replay tests
- stale offline mutation/conflict tests
- incident state-transition tests
- evidence consistency/failure tests
- audit generation tests
- migration/schema tests where useful
- backup/restore verification or recovery rehearsal where feasible in the VM
- at least one end-to-end critical workflow if tooling permits

Security tests should include negative cases, not just successful authorized paths.

---

# Build phases

## Phase 0 — Harness retrieval and planning

Before application implementation:

- clone both repos;
- pin the harness SHA;
- inspect relevant harness lanes/notes;
- create initial `docs/harness-evidence/` planning artifacts;
- record major architecture/security/data decisions.

Do not spend the entire task polishing documentation. Phase 0 exists to constrain implementation.

## Phase 1 — Foundation

Create the application, development environment, CI baseline, auth, organization boundary, module structure, migrations, and test infrastructure.

## Phase 2 — Vertical operational slice

Implement deeply:

- assets
- custody/assignment
- incidents
- evidence
- audit

with authorization and tests.

## Phase 3 — Offline synchronization

Implement the operation queue, idempotency, authorization re-check, conflict detection/reconciliation, and failure tests.

## Phase 4 — Maintenance / approvals / reporting

Add enough adjacent workflow capability to demonstrate the modular architecture without turning V1 into an ERP.

## Phase 5 — Runtime and recovery

Container/runtime setup if practical, CI gates, health checks, structured logging, backup procedure, restore verification, rollback instructions.

## Phase 6 — Adversarial review and harness evaluation

Attempt the required pressure cases. Fix material defects. Finish `09_HARNESS_EVALUATION.md`.

---

# VM execution expectations

The implementation agent is explicitly authorized to build and test inside its own VM/workspace.

It may:

- install PHP/Composer/Node/PostgreSQL/Docker and ordinary development dependencies;
- create local development databases;
- run migrations, seeds, tests, linters, static checks, builds, and local servers;
- generate disposable test evidence files;
- run local security/negative-path tests that do not target external systems.

It must not:

- publish secrets;
- target third-party systems;
- claim production readiness from local tests alone;
- modify the Project Second Brain harness repository;
- write application implementation directly to `main`.

---

# Git / delivery protocol

1. Initialize VM workspace from `main`.
2. Create `KIRCH-HARNESS-PILOT-SENTINELOPS-V1`.
3. Commit coherent milestones, not one giant dump.
4. Push only the implementation branch.
5. Open a **draft PR** into `main`.
6. PR body must include:
   - exact harness SHA used;
   - architecture summary;
   - security model summary;
   - offline sync model;
   - test commands and results;
   - known defects/limitations;
   - harness-evaluation summary;
   - explicit statement of anything not runtime-verified.
7. Do not merge the PR unless separately authorized.

---

# Stop conditions

Stop and report rather than guessing if any of these occur:

- `HARNESS SOURCE UNAVAILABLE`
- framework/runtime requirements cannot be installed in the VM after reasonable troubleshooting;
- the repository state differs materially from this handoff before implementation begins;
- a required architectural decision has unresolved contradictory evidence that materially changes data/security authority;
- implementation would require credentials or external infrastructure that were not provided and cannot be substituted locally.

A stop report must include what was verified, what failed, and the narrowest next action.

---

# Success criteria

The pilot is successful when all of the following are true:

1. A fresh VM can follow documented setup instructions and run the application.
2. The critical asset → incident → evidence → audit flow works.
3. At least one meaningful offline workflow works and reconciles safely.
4. Authorization and organization isolation are server-enforced and tested.
5. Idempotency/conflict behavior is explicit and tested.
6. Failure/recovery behavior is documented and at least partially rehearsed.
7. A draft PR contains a runnable candidate and honest verification evidence.
8. `09_HARNESS_EVALUATION.md` provides concrete evidence about whether the cognition harness improved the build.

The purpose of SentinelOps is not to prove the harness is good. The purpose is to find out whether it is.
