# Medicine Phase 3.19 — Controlled prerequisite deployment

Result: **BLOCKED — fresh recovery verification reported warnings. No normal migration was attempted.**

## Authorization and boundary

The user explicitly authorized the exact `database/medicine-phase319-provisional.sql` prerequisite migration only, conditional on verified recovery and preservation checks. Phase 3.22 deployment, provisional creation, reviewer preparation/grants, approvals, enrollment, activation and Treatment changes were excluded. The recovery warning stop condition was enforced before normal deployment. No normal repair or automatic retry was performed.

## Pre-deployment verification

Normal target: **medical1**, port **3306**, **10.4.27-MariaDB**. Session and global mode:

```text
NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION
```

Client/connection/results/server character sets were utf8mb4; connection/server and database default collations were utf8mb4_general_ci. The complete preflight matched the retained Phase 3.22 read-only normal baseline: 54 base-table row counts/full-row digests, complete table/view definitions, eight trigger definitions, routines/events and operator privileges. Exact SHOW CREATE TRIGGER definitions and character contexts were captured separately. Trigger creation timestamps are excluded because they are not reproducible dump properties.

Account 1 was active and had the sole administrator/catalog grant: user/grantor 1, active, no revocation, **NULL expiry**, no qualification fields. Exactly one immutable owner establishment event existed. Its actor, grant subject, revision/state, snapshot hash, grant field bindings and configured establishment decision were checked. Owner snapshot SHA-256:

`41ad91d0341eb8a147429876a43a3b8573b3602cb280cf8439077024b860778d`

Contributor/editor, reviewer, steward, deployment and delegated administrator grants were zero. Canonical chemical references, Medicine identities and pilot records were zero. No provisional table or Phase 3.22 qualification/appraisal tables existed.

Five active registry rows remained CANDIDATE / UNKNOWN with exact names: 15 Acetazolamide; 54 Allopurinol; 319 Carbamazepine; 481 Colchicine; 2064 Topiramate. No candidate approval/reviewer event/enrollment/publication was present. Medicine pilot/internal pilot were OFF, activation/evidence/reviewer configuration empty, and Treatment publication HOLD. Public Medicine remained unavailable under the existing policy; no new browser/public exposure test was performed in this stopped operation.

Protected evidence: `storage/logs/medicine321a-owner-recovery/phase319-preflight.json`.

## Original backup preserved

The authoritative path was read from the successful Phase 3.21A owner deployment report:

- Path: `D:\xampp\htdocs\kobi\storage\logs\medicine321a-owner-recovery\before.sql`
- Bytes: **5,352,130**
- SHA-256: **f303f482ba49931db370a33b833bf95e5106e3c20de21d47ce3bfc4c78108cf2**

The retained backup manifest and both independent RESTORED_VERIFIED certificates matched this identity; the second certificate retained its independent repeatability PASS. The original file was not modified or replaced. It predates the owner migration/bootstrap, so it is insufficient by itself to restore the current owner authority.

## Separate fresh checkpoint and recovery stop

A fresh current logical backup was created with the established MariaDB mysqldump procedure: single transaction; no locks, added locks or DROP TABLE statements; routines/events/triggers; hex blobs. Exit status was 0, stderr empty, and complete live baseline unchanged after capture. The temporary protected client credential file was removed in finally; credentials were never printed.

- Path: `D:\xampp\htdocs\kobi\storage\logs\medicine321a-owner-recovery\phase319-before.sql`
- Bytes: **5,353,453**
- SHA-256: **f4587999dc9e6f3f7dcd50bca9517bbce70ba40217f244434e7891ec4d52b984**

This checkpoint was restored into **kobi_phase319_recovery_20261011**, on a fresh project-local MariaDB 10.4.27 instance, port **3342**, with matching database defaults. The lossless importer preserved original CRLF trigger bytes. It executed the complete dump, including all **26 session SQL-mode save/set/restore directives**, without intentionally omitting any statement. Static accounting after the completed importer found **501 statements**; these include isolated schema and restored data writes, not normal database writes. No global or normal/application SQL mode was changed.

The importer returned a nonempty SHOW WARNINGS collection and the command exited **1** with `STOP: Restore warning; stop.` The initial helper checked warnings before saving the import result, so **the exact warning count, codes, text and affected statements were not retained**. Subsequent queries cannot recover that connection's historical warning list. This diagnostic loss is a tooling error, not a MariaDB incompatibility diagnosis. The helper now saves the import evidence before that assertion for any separately authorized future recovery investigation; **no second restore was attempted here**.

A follow-up read-only comparison showed that recovered data, complete metadata, all eight exact trigger definitions/character contexts, final SQL/character state, database defaults and operator privilege context matched the fresh normal baseline. This does **not** override the warning failure or certify recovery. No recovery certificate was issued. The exact recovery procedure remains a complete lossless restore into a clean guarded database, followed by all baseline/trigger/context/authority comparisons and zero unexpected warnings. Replacing a normal database with an unverified checkpoint is not authorized by this operation.

The queued rehearsal command failed its missing-recovery-certificate guard before any migration statement. Its missing-file PHP diagnostics are separate from the unidentified restore warnings. A second disposable server on port 3339 was initialized for the existing unchanged behavioral harness, but no test database, fixture or behavioral test was run; it was subsequently shut down.

Fresh backup manifest: `phase319-backup.json`. Stop/preservation proof: `phase319-stopped.json`. Disposable actual metadata/digest evidence is retained inside the stop proof. Both project-local disposable servers were stopped with process/path identity checks, logs archived, and datadirs removed. Original and fresh backups remain protected by `Require all denied`.

## Artifact and expected schema

The artifact SHA-256 matched the retained validated identity:

`84166a59636a8eefcf580bc8bc71b09c19951255a865d2b8a8415ba62a41be48`

Its complete block matched `database/structure.sql` exactly after trimming outer whitespace and was reviewed against Phase 3.19 documentation/tests. The migration itself was not edited. Full structure SHA-256 remained:

`52f2d6c6ee8832a2f3114845e18fa8ce4359ebb023a23f614a5d59eb948f473c`

Expected additions, **not executed**:

- One InnoDB `medicine_provisional_identity_revisions` table, 11 non-null columns, utf8mb4_unicode_ci default, binary JSON collations/hash columns as defined.
- Composite primary key `(substance_id, revision)`; indexes `idx_medicine_provisional_actor` and `idx_medicine_provisional_grant`.
- Three RESTRICT foreign keys: registry substance, users actor, authority grant.
- Four CHECKs: five-substance scope, binary draft/withdrawn/invalidated vocabulary, revision/rationale/JSON/size shape, SHA-256 hashes.
- `provisional_identity_before_insert`: registry serialization, sequential revisions, initial draft/terminal restrictions, active scoped contributor/editor or steward binding, unapproved facts and exact evidence linkage.
- `provisional_identity_no_update` and `provisional_identity_no_delete`: unconditional immutable-history rejection.

The expected migration is four executable statements: CREATE TABLE and three CREATE TRIGGER statements. No mode directives or seeds occur in it. The three trigger definitions are available verbatim in the unchanged artifact; they were **not installed or rehearsed during this operation**. No new-object runtime metadata verification can be claimed.

## Actual normal result and preservation

Read-only final check at **2026-10-10 20:05:53.749550 UTC** (**2026-10-11 01:35:53.749550 Asia/Calcutta**) verified:

- **54 tables**, not 55; all established counts/full-row digests unchanged.
- Complete existing table/view definitions, indexes, foreign keys, CHECKs, routines/events unchanged.
- **Eight existing triggers**, not 11; exact definitions/character contexts unchanged.
- Privileges SHA-256 unchanged: `ed56afa71d6afce3f87a6a27aa9ff47ad3060e29119e9adab128fbf7d04025a4`.
- SQL modes, character state and database defaults unchanged.
- Owner grant and audit/history unchanged; Account 1 remains sole administrator with NULL expiry.
- Registry, personal medications, combinations/members, Treatment protocols/items, diseases and all other existing data unchanged.
- Five candidates remain active CANDIDATE / UNKNOWN, unapproved and unenrolled.
- Pilot/public availability unchanged; Treatment HOLD.

**Normal migration statements executed: 0. Normal row writes: 0.** Normal SELECT/SHOW statements were executed for verification, and a read-only logical dump was taken. No normal DDL execution start/end or MariaDB DDL errors exist because deployment never began. Recovery command exit status was 1; the guarded rehearsal command also exited 1 without DDL. Cleanup and final preservation verification exited 0.

The provisional table remains absent, so a SQL COUNT on it is impossible. **No provisional identity record exists or was created**, rather than a claimed successful COUNT on a nonexistent table.

## Phase 3.22 dependency and deferred testing

**Phase 3.22 prerequisite: UNAVAILABLE. Phase 3.22 deployment: NOT EXECUTED.** Its composite evidence/appraisal FK prerequisite could not become available because the Phase 3.19 deployment was stopped. No Phase 3.22 DDL was applied, and no reviewer/evidence record was created.

Performed this operation: artifact/full-structure identity and block comparison; original backup/certificates verification; fresh backup creation/identity; isolated lossless recovery attempt; read-only restored-versus-normal comparison; complete normal preservation and owner audit binding verification; isolated cleanup; PHP lint for the new operator helper (PASS); `git diff --check` (exit 0).

**New unit/MariaDB behavioral cases run: 0. Assertions: 0.** Required Phase 3.19 unit/security/concurrency, Medicine governance, Authentication and Treatment regression suites were deferred because recovery verification reached a critical stop before migration readiness. Previous phase counts are not presented as current results. In particular, no new successful proof is claimed for forged authority/evidence, stale snapshots, duplicate preparation, immutable/terminal/restoration protections, injection or concurrency. Existing suites and documentation remain unchanged.

## Files and limitations

Created:

- `tests/medicine-phase319-deployment.php` — guarded schema-only operator workflow and read-only stopped verification; reuses the established lossless recovery library. No automatic retry or row-writing authorization path.
- `tests/medicine-phase319-environment.ps1` — existing isolated environment procedure adapted to the unchanged Phase 3.19 harness's port 3339/datadir guard.
- `docs/MEDICINE_PHASE_3_19_CONTROLLED_DEPLOYMENT.md` — this report.
- Protected `phase319-preflight.json`, `phase319-backup.json`, `phase319-before.sql`, `phase319-dump.stdout`, `phase319-dump.stderr`, `phase319-stopped.json`, and archived isolated server logs.

No previously existing application, schema, configuration or test file was modified. The new helper's warning-evidence retention was corrected after the stop, without repeating restoration or deploying anything. Existing user changes were retained. No commit, push, reset, checkout, merge, rebase, amend or other Git mutation occurred.

Remaining blocker: identify the restore warnings through a separately controlled diagnostic restore with retained per-statement warnings, then establish a clean recoverable current checkpoint before any normal deployment. Their exact cause is unknown; this report does not label them harmless or infer a schema incompatibility.

## Explicit safety confirmation

NO PROVISIONAL IDENTITY RECORD CREATED  
NO REAL REVIEWER CREATED  
NO REVIEWER AUTHORITY GRANTED  
NO EVIDENCE APPRAISAL CREATED  
NO MEDICINE CANDIDATE APPROVED  
NO PILOT ENROLLMENT  
NO PUBLIC MEDICINE ACTIVATION  
NO TREATMENT CHANGE

Final result: **BLOCKED, normal KOBI unchanged.** No Phase 3.19 or Phase 3.22 deployment took place.
