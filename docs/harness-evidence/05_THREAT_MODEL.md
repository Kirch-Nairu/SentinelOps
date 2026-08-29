# Security Model

## Assets and trust boundaries

Protected assets include credentials/sessions, organization membership and role authority, asset/custody state, incident narratives, evidence bytes, audit history, sync operation identities/results, backups, and deployment secrets.

Trust boundaries exist between browser and server, authenticated actor and target organization/resource, application and PostgreSQL, application and evidence storage, CI and deployment runtime, and backup media and restored runtime.

## Primary threats and controls

- **BOLA/IDOR and tenant crossover:** every target is organization-scoped server-side; payload organization IDs are never permission evidence; policies enforce action + resource + current context.
- **Privilege escalation / stale offline authority:** synchronized commands are authorized when executed, not when captured.
- **CSRF/session theft:** same-origin session auth, framework CSRF for web mutations, HttpOnly/Secure/SameSite cookie policy in production, session rotation at login, privileged reauthentication for authority-changing/admin recovery operations.
- **Mass assignment:** validated request DTO/FormRequest fields and explicit action parameters; identifiers loaded and authorized before mutation.
- **Evidence abuse:** private disk/object storage, allowlisted image MIME/types for V1, size limits, content-derived MIME validation, SHA-256 integrity, authorization on download, no public storage URL.
- **Replay:** persisted idempotency identity and payload hash.
- **Concurrency/state substitution:** resource locks + revisions + constraints; server does not trust client snapshots.
- **Audit tampering:** application audit model is append-only; PostgreSQL trigger rejects ordinary UPDATE/DELETE; significant domain changes write audit in the same transaction.
- **Secret leakage:** `.env`, backups, evidence, vendor/node_modules, runtime credentials are excluded from Git.
- **Supply chain:** Composer/npm locks committed; CI uses locked installs; dependency audit is a release gate where network is available.
- **Abuse/rate pressure:** auth and sync endpoints receive rate limits; individual batch size/payload/evidence sizes are bounded.

## Stolen administrator session

V1 requires recent password reauthentication for role/authority changes and recovery-sensitive actions. Session rotation and logout invalidation reduce reuse. This does not claim to equal phishing-resistant MFA; production should require MFA/step-up appropriate to deployment risk. Security logging records privileged attempts and outcomes.

## Residual risks

- Offline clients necessarily retain cached operational data; device compromise can expose that cache unless platform/browser storage protections are sufficient.
- V1 local evidence adapter is not an immutable WORM store.
- Password step-up is a transitional control, not final strong MFA.
- Malware-content scanning is not guaranteed by MIME/hash validation and should be added for broader attachment types/production exposure.
