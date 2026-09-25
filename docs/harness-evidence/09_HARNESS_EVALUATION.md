# Harness Evaluation

Harness baseline: `Kirch-Nairu/project-second.brain` at pinned SHA `d213ad56cea6a9a1fb3a56c159c448343d0d0777`.

## Contribution counts

These counts refer only to contribution events supported by notes actually retrieved during this pilot; they are not counts of the entire graph.

| Classification | Count | Concrete effect |
| --- | ---: | --- |
| `CHANGED_DECISION` | 1 | Recovery acceptance was implemented as DB + evidence integrity proof rather than treating successful relational restore as sufficient. |
| `STRENGTHENED_DECISION` | 2 | Modular-monolith choice was evaluated through authority/failure/complexity criteria; privileged authority changes received explicit step-up reauthentication rather than UI-only role gating. |
| `CONFIRMED_EXISTING_REASONING` | 2 | Server-side resource authorization and append-oriented/auditable state transitions were already expected and were reinforced rather than newly invented. |
| `EXPOSED_FAILURE` | 1 | Offline availability was separated from authority: local queued state cannot become authoritative merely because the UI can operate without a network. |
| `EXPOSED_VULNERABILITY` | 1 | Authorization drift/identifier substitution was treated as a current server-side actor + action + target decision at synchronization time. |
| `IMPROVED_VERIFICATION` | 2 | Replay/concurrency verification became a layered operation-ledger + revision + locking + DB-constraint test; recovery verification became executable rather than documentary. |
| `IMPROVED_RECOVERY` | 1 | Missing evidence after a valid DB restore is a tested failure state. |
| `NOISE` | 2 | Atlas/mesh overlap often restated the same concept at different abstraction levels; unresolved-link debt made some graph edges unusable as evidence. |
| `HARNESS_GAP` | 3 | No retrieved route directly solved offline queue ownership across login changes, test-database isolation, or the implementation details of staged object-storage cleanup/claim semantics. |

## What materially helped

The planning hubs were more useful than broad graph traversal. `ARCHITECTURE_PLANNING_HUB`, `SECURITY_PLANNING_HUB`, and `DATA_PLANNING_HUB` reduced the initial decision surface to authority, trust boundaries, mutation boundaries, failure, recovery, and verification. The short atomic notes on idempotent mutation, transaction boundaries, concurrency control, and authorization boundaries mapped cleanly to executable SentinelOps constraints.

The strongest practical benefit was not a novel framework choice. It was forcing explicit questions at state boundaries: who owns truth, when an offline command obtains authority, what a duplicate operation means, which invariants must survive races, and what evidence is required before claiming recovery.

## What was mostly confirmatory

A substantial part of the harness repeated engineering knowledge already present in the implementation mission or already standard for a security-conscious build. The handoff itself explicitly required PostgreSQL authority, current authorization at synchronization, idempotency, stale-state handling, evidence consistency, append-oriented audit history, and recovery proof. Therefore causal attribution is limited: the harness organized and reinforced many of these decisions, but the experiment cannot honestly claim that SentinelOps would otherwise have omitted them.

The modular-monolith note also confirmed a direction already favored by the initial stack hypothesis. It improved the justification, not the originality of the choice.

## Noise and overhead

Routing through a mesh advertised as hundreds of nodes is not automatically efficient. Reading all notes would have been counterproductive. The pilot was workable only because traversal was aggressively narrowed through planning hubs and a small set of relevant concepts. Several atomic notes are definition-sized and overlap semantically with atlas concepts, doctrines, or neighboring mesh notes. That redundancy can help graph discoverability, but it increases retrieval and attribution overhead for an implementation agent.

The pilot did not measure wall-clock harness overhead separately, so no fabricated time figure is reported. Qualitatively, overhead was moderate-to-high: useful when a hub routed directly to a state/security/recovery boundary, low-value when multiple notes merely restated a concept.

Known unresolved links also reduced confidence in traversing arbitrary graph relationships. Those links were excluded rather than inferred.

## Gaps revealed by implementation

1. **Offline queue ownership across identity changes.** The final client review found a confused-deputy risk: a browser queue must be scoped to both workspace and authenticated actor so a later login cannot flush another user's pending operations. No directly retrieved harness note supplied this implementation constraint.
2. **Test-environment destructive isolation.** Final verification found PHPUnit was pointed at the development database name. Fixing this required ordinary runtime/test discipline rather than harness guidance.
3. **Evidence staging lifecycle details.** The harness correctly emphasized transaction and recovery boundaries, but the exact object-store pattern—durable staging, DB claim, TTL cleanup, hash verification, and DB-only restore failure—still required project-specific design work.

Useful future additions would be focused concepts/patterns for identity-scoped offline queues, object-store/database consistency without pretending to have cross-store ACID, destructive test-environment safeguards, and explicit browser-cache threat models.

## What should be simplified

- Prefer canonical concepts with aliases/links rather than multiple near-duplicate short notes where the distinction has no operational consequence.
- Make planning hubs expose a smaller recommended route for common application classes before presenting the full mesh.
- Treat unresolved graph links as health failures with visible remediation priority.
- Promote worked implementation patterns only when they contain failure semantics and verification hooks, not merely definitions.

## Final verdict

**MOSTLY_CONFIRMATORY**

Project Second Brain was useful as a structured engineering checklist and as a forcing function for explicit authority/failure/recovery reasoning. It improved traceability and some verification design. However, most of SentinelOps' strongest controls were already required by the adversarial handoff or are standard engineering reasoning, and several implementation-specific defects were found outside harness guidance. The experiment therefore does not support a stronger claim that the harness independently caused most of the software's quality.
