# Verification Evidence

## Invariants enforced

1. A user cannot read or mutate a resource outside an organization in which current server authority permits the action.
2. An exclusive asset has at most one active custody assignment.
3. Replaying an identical accepted sync operation never repeats the business effect.
4. Reusing an operation ID for a different canonical payload is rejected.
5. A queued offline command has no guaranteed authority until server execution.
6. Authorization is evaluated using current server membership/role at synchronization time.
7. A mutation with a stale base revision cannot silently overwrite newer protected state.
8. Closing an incident preserves incident history, evidence metadata, evidence bytes, and audit trail.
9. Normal application/database paths cannot silently update or delete audit rows.
10. Attached evidence refers only to durable private bytes with recorded integrity metadata.
11. A failed domain transaction cannot leave evidence represented as authoritative attached incident evidence.
12. A database restore is not a successful recovery while required attached evidence is absent or hash-invalid.
13. Significant authoritative mutation and its audit event succeed/fail together.
14. Offline queue entries and local evidence are scoped to the authenticated user/workspace identity; a later login does not flush another actor's pending commands.
15. Automated tests use a dedicated PostgreSQL database (`sentinelops_test`) rather than the development authority database.

## Final backend suite

Executed after the final queue-scope, recovery-verifier, and test-database isolation changes against PostgreSQL:

- tests: 13
- assertions: 68
- failures: 0
- skipped: 0

Coverage includes authentication/workspace establishment, auditor read-only enforcement, cross-organization substitution, five identical offline replays producing one business mutation, competing custody assignments, permission revocation before synchronization, stale conflicts, incident/evidence lifecycle and closure, idempotency-key payload substitution, privileged reauthentication, recovery verification, append-only audit enforcement, and immutable attached evidence metadata.

## Frontend verification

The preserved artifact had broken `node_modules/.bin` shims because artifact transport dereferenced package symlinks. Local ignored shims were repaired only inside `node_modules`; repository dependencies were not modified to hide this condition. After repair, the actual project scripts executed successfully:

- `npm run typecheck` — PASS
- `npm run build` — PASS
- Vite 8.2.2 transformed 576 modules and emitted the production bundle.

CI uses a fresh `npm ci`, so the transported-shim issue is not part of the committed runtime.

## Final HTTP vertical-slice smoke

Application process: PHP 8.4 + Laravel 13.29.0 on `127.0.0.1:8000`, PostgreSQL 16.15 on the preserved VM PostgreSQL runtime.

Flow executed after final application hardening:

1. `/up` returned HTTP 200.
2. Technician authenticated; `/dashboard` returned HTTP 200 with authenticated actor/workspace metadata.
3. `GENERATOR-0041` was read at revision 2, status `deployed`.
4. A valid private PNG evidence object was staged; endpoint returned HTTP 201.
5. An offline-origin HIGH incident command synchronized successfully.
6. The exact same operation was replayed five additional times; all returned the stored accepted result.
7. PostgreSQL contained exactly one sync-operation row, one incident row, and one evidence row for the operation; asset state became `damaged`, revision 3.
8. Technician attempt to close the incident returned HTTP 403.
9. Security Officer authenticated; the incident was visible on the dashboard and closure returned HTTP 200.
10. Incident status became `closed`; incident audit history remained present.

Final smoke identifiers:

- client operation: `fde60003-572a-4a91-92b9-37445c3cfaec`
- incident: `8bc80245-995e-44bb-aa1f-48ef21866b7e`
- asset: `e47224f6-789a-4a00-afaf-72060fac535f`

## Recovery rehearsal

See `06_FAILURE_AND_RECOVERY.md`. A real `pg_dump` → fresh PostgreSQL database → `pg_restore` cycle reproduced all critical counts, preserved immutability triggers, passed evidence hash verification, and intentionally failed when the restored database referenced missing evidence bytes.

## Playwright

**PLAYWRIGHT DEFERRED — VM BROWSER ENVIRONMENT LIMITATION.** Chromium exists in the VM, but `@playwright/test` is not part of the preserved dependency lock and this VM has no npm registry/DNS access. Adding an unverified new test dependency at delivery time would reduce reproducibility. The executed HTTP vertical slice remains the end-to-end runtime evidence for this pilot.

## Post-hardening final runtime smoke

After the final test-database isolation, recovery-verifier, CI, documentation, and production-seeder safety changes, the running application was exercised once more without restarting or rebuilding the candidate:

- `/up`: HTTP 200
- technician login: HTTP 302 to authenticated application
- dashboard: HTTP 200
- offline-origin `incident.create`: accepted
- exact replay: returned byte-equivalent reconciliation payload
- authoritative sync rows for the client operation: 1
- asset state after acceptance: `damaged`, revision 4
- technician close attempt: HTTP 403

Final post-hardening identifiers:

- client operation: `2a4fffb5-15af-4cef-ad09-da3baf021706`
- incident: `e4b61b51-dc30-4057-b6e8-82b1ec5e9f4d`
