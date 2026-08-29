# SentinelOps Agent Operating Contract

This repository is a controlled greenfield build used to evaluate an external engineering cognition harness.

## Role

You are the implementation/code-writer agent. You own a disposable VM/workspace and may install required development dependencies there. You do not own the cognition-harness repository and must not mutate it.

## Required workflow

1. Clone this repository into your VM.
2. Clone `Kirch-Nairu/project-second.brain` separately and checkout the exact harness baseline named in `HANDOFF_SENTINELOPS_HARNESS_PILOT.md`.
3. Read the handoff before writing application code.
4. Inspect the harness notes and tooling that are relevant to the task. Do not invent harness outputs that were not retrieved.
5. Create a dedicated implementation branch: `KIRCH-HARNESS-PILOT-SENTINELOPS-V1`.
6. Build and test in the VM. Do not commit generated secrets, local databases, `.env`, vendor directories, `node_modules`, or transient test artifacts.
7. Keep architecture, security, failure, verification, and deployment evidence under `docs/harness-evidence/` as specified by the handoff.
8. Commit in coherent milestones. Never force-push `main`.
9. Open a draft pull request against `main` when a runnable candidate exists.

## Authority and truth

Repository/runtime evidence outranks assumptions. The harness provides engineering guidance and decision-support, not automatic authority. If the harness conflicts with actual framework/runtime behavior, record the conflict and use verified runtime behavior.

Do not claim something is verified unless you actually ran the relevant test, command, migration, build, or runtime check.

## Safety and scope

- Prefer a modular monolith unless the decision packet justifies another architecture.
- Treat offline synchronization, authorization, evidence handling, audit history, backups, and recovery as first-class engineering concerns.
- No decorative security controls. Every security control must correspond to a threat, abuse case, trust boundary, or invariant.
- No destructive production actions are required for this pilot.
- No external paid SaaS is required to complete the core pilot.
- If the harness repository or exact baseline is unavailable, stop and report `HARNESS SOURCE UNAVAILABLE`; do not synthesize its contents from this file.

## Definition of a useful pilot

The pilot succeeds only if it produces both working software and evidence that the harness changed or strengthened concrete engineering decisions. A large amount of documentation without a runnable system is not success.
