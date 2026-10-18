# Medicine Phase 3.19 — Recovery diagnostic correction

Final recovery status: **BLOCKED**. Warning capture is corrected and proven, but both independent restores produce the same Warning 1906. No warning acceptance threshold or recovery policy was changed. No Phase 3.19 or Phase 3.22 deployment occurred.

## Scope and backup identity

This work was authorized only to diagnose the previous restore, retain exact MariaDB diagnostics, run disposable self-tests and two independent restores, and verify normal KOBI preservation. Application semantics, migrations, schema, authority and exposure were outside scope.

The unchanged checkpoint that produced the previous warning, as identified in `MEDICINE_PHASE_3_19_CONTROLLED_DEPLOYMENT.md`, was used for both reproductions:

- Path: `D:\xampp\htdocs\kobi\storage\logs\medicine321a-owner-recovery\phase319-before.sql`
- Size: **5,353,453 bytes**
- SHA-256: **f4587999dc9e6f3f7dcd50bca9517bbce70ba40217f244434e7891ec4d52b984**

Its path/existence/size/hash matched the report and retained manifest before and after diagnostics. It was read verbatim: no normalization, filtering, regeneration or replacement occurred. This is the original file from the blocked *fresh-checkpoint* restore, including the already-established owner grant and event.

The older original authoritative recovery backup was also independently verified and left unchanged:

- Path: `D:\xampp\htdocs\kobi\storage\logs\medicine321a-owner-recovery\before.sql`
- Size: **5,352,130 bytes**
- SHA-256: **f303f482ba49931db370a33b833bf95e5106e3c20de21d47ce3bfc4c78108cf2**

Its two existing independent recovery certificates were verified, not overwritten. It predates owner establishment and is not the file that produced the current warning. Substituting it for the failed fresh checkpoint would not reproduce that failure or validate current owner recovery.

## Root cause and exact correction

The prior importer fetched SHOW WARNINGS after statements and returned a warning collection. The caller asserted that the collection was empty **before saving the import result**. When that assertion threw, the collected array was lost at process exit. Exact warnings were unavailable to subsequent connections. This was a persistence/order defect in the caller, not stderr redirection or a changed backup. No evidence established that PDO suppressed the warning in the original restore.

The shared lossless importer now supports an optional per-statement retention callback. For each statement:

1. Execute the unchanged SQL; close/drain its PDO cursor without issuing additional SQL.
2. Immediately retrieve **SHOW WARNINGS**, including after a PDO SQL exception.
3. Retrieve **SHOW COUNT(*) WARNINGS** and require the retained list to be complete. A server-truncated or incomplete list cannot pass.
4. Retain statement order, source ending line, SQL SHA-256, capture UTC timestamp, SQL-mode-context indicator, server condition count, and every exact Level/Code/Message. Failed executions additionally retain SQLSTATE/error code/message.
5. Persist through the callback before another statement, SQL-mode observation or exception escapes. Then rethrow any SQL exception; never convert it into success.

The existing Phase 3.19 restore caller now supplies this callback and still requires an empty warnings collection for RESTORED_VERIFIED. It saves both immediate statement diagnostics and the completed import result before its warning assertion. Its original warning gate is unchanged.

A library dispatch guard allows the new **diagnostics-only** driver to reuse capture/identity/safety functions without invoking deployment modes. The driver journals after every statement, including failures, and distinguishes diagnostic completion from recovery acceptance. WARNINGS_PRESENT remains BLOCKED even when metadata and data match. Full private backup SQL is not copied to console/report; warning-producing operations are identified by table/type, statement/source line and exact SQL hash, resolving back to the protected unchanged dump.

During focused test development, using PDO exec with a result-producing SELECT exposed an undrained-result error (2014). The importer now uses a PDO statement and closes its cursor before diagnostic SQL. Initial fixture assumptions that table-free SELECT clears prior warning state and that trigger-body warning signals necessarily surface to the caller were incorrect on this server. The final fixtures use a table-reading SELECT that demonstrably clears warnings and MariaDB's exposed duplicate-trigger creation Note. These test corrections changed neither backup nor database semantics. Failed development runs are not counted as additional passing cases.

## Environment and importer

- MariaDB server: **10.4.27-MariaDB**, Windows x64.
- PHP: **8.2.0**; PDO MySQL client: **mysqlnd 8.2.0**.
- Dump producer: **mysqldump 10.19, Distrib 10.4.27-MariaDB, Win64 (AMD64)**.
- Importer: corrected `tests/medicine-owner-schema-deployment.php`, reused by `tests/medicine-phase319-recovery-diagnostics.php`.
- Disposable target: **kobi_phase319_recovery_20261011**, port **3342**, fresh project-local `.medicine-owner-recovery/data` with version/port/datadir guards.
- Initial/final session and global SQL mode: **NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION**.
- Client/connection/results/server charset: utf8mb4; connection/server/database-default collation: utf8mb4_general_ci.

Each complete restore executed **501 statements**, including all **26 original dump session SQL-mode save/set/restore directives**, with **zero omissions** and **zero SQL execution errors**. These temporary session transitions were part of replaying the unchanged established backup procedure, not a new SQL-mode setting or workaround. Both restored the exact initial final session state. Normal/global/persistent application SQL mode was never changed. Neither schema migration artifact was executed anywhere in this work.

## Exact warning reproduced in both restores

Each restore exposed exactly **one diagnostic condition**, and the complete server count matched the fetched list:

| Field | Exact result |
| --- | --- |
| Level | Warning |
| Code | 1906 |
| Message | The value specified for generated column 'active_slot' in table 'medicine_authority_grants' has been ignored |
| Responsible statement | 315 |
| Source ending line | 1475 |
| Operation | Data import: INSERT INTO `medicine_authority_grants` |
| Executed SQL SHA-256 | `76b2b47ab8dbfcb21425296e9427a58e75dbab9e17b9d1fa87e71b2f91370470` |
| SQL-mode directive? | No |

No warning arose during table creation, view creation, creation of any of the eight existing triggers, or replay of SQL-mode directives. The logical database dump contains no account GRANT/privilege-restoration operation. Operator privilege context was separately verified; full server account credentials are not part of this database-specific backup.

The normal/current grant table defines `active_slot` as a **STORED GENERATED ALWAYS** expression: `CASE WHEN revoked_at IS NULL THEN 1 ELSE NULL END`. The backup INSERT supplies a value for this generated column. MariaDB explicitly reports ignoring that supplied value. The same dump includes the current owner grant, whereas the older original pre-bootstrap backup had no grant row to insert. This explains the data-import warning; there was no parser/filter alteration, privilege restoration or trigger-creation warning involved.

The restored grant row's complete digest—including the generated value—and all other row/schema digests match. Thus these two reproductions show no observable data, schema, constraint, trigger, privilege or final-mode mismatch from the ignored supplied generated-column value. **This evidence is not an acceptance of the warning.** The unchanged certification gate requires zero unexpected warnings. Accepting this nonzero result would require a separate policy decision; it was not made here. The dump was not repaired to use DEFAULT or omit the column. No subsequent schema or authority operation was attempted.

## First complete restore

- Independent instance: **05da881d-298c-466f-95d9-2d72538320aa**.
- Start/end: **2026-10-10 20:13:17–20:13:29 UTC**.
- Warning capture: **2026-10-10 20:13:24 UTC**, statement 315.
- Result: **BLOCKED_WARNINGS_PRESENT**; one Warning 1906, no SQL errors.
- All **54 tables / 26,209 rows**: exact row counts and established full-row sorted digests match the fresh preflight.
- Complete table/view definitions match: columns/types/nullability/defaults, indexes, foreign keys, CHECKs, engines/collations and AUTO_INCREMENT metadata.
- All **eight existing triggers** match: names, timing/event/table, complete body bytes/CRLF, SQL mode, definer and SHOW CREATE character context. Only regenerated trigger creation timestamps are excluded from reproducible comparison.
- Routines/events inventories and definitions match.
- Operator privileges digest, database defaults, character settings, and initial/final session/server state match.

Evidence: `storage/logs/medicine321a-owner-recovery/phase319-diagnostic-restore1.json`. This contains all 501 immediate statement entries, exact diagnostics, complete actual metadata/digests, mode transitions and client/instance identity.

The first server was shut down after process/datadir ownership checks and its datadir was removed. Its log and proof were retained. No restored database from this instance was reused for the second restore.

## Second independent complete restore

- Fresh instance: **2d0f0792-f4f5-410d-b303-b8a04812c888**.
- Start/end: **2026-10-10 20:14:11–20:14:22 UTC**.
- Same unchanged checkpoint and corrected importer.
- Result: **BLOCKED_WARNINGS_PRESENT**; the identical one Warning 1906 at statement 315/line 1475, no SQL errors.
- All 54 table counts/full-row digests, complete schema, eight exact triggers/character contexts, routines/events, privilege context, defaults and SQL/character state match both the current baseline and first restore.
- Statement counts and every mode transition match the first restore.
- Warning count, levels/codes/messages, statement/line/context and SQL hash match exactly. Capture timestamps are retained individually and naturally differ between executions.

Evidence: `phase319-diagnostic-restore2.json`, including all immediate entries and independent comparison fields. The second server was also stopped with ownership checks, log archived and datadir removed.

Both diagnostic commands completed with shell exit 0 because capture/reproduction/comparison completed; their explicit **recovery status remained BLOCKED_WARNINGS_PRESENT**, not PASS. Generic process success is not used as a recovery certificate. Neither result created or updated `phase319-restored.json` or any deployment readiness certificate.

## Tests and checks actually run

| Test/check | Cases | Assertions | Result |
| --- | ---: | ---: | --- |
| Final recovery diagnostic self-tests | 10 | 24 | 0 failures |
| Phase 3.19 provisional identity unit regression | 43 | 240 | 0 failures; database-free |
| Medicine governance regression | 56 distinct | 249 | 0 failures; synthetic |
| Medicine policy regression | 33 distinct | 92 | 0 failures; synthetic |
| Complete independent checkpoint restores | 2 operations | Not counted as unit assertions | Identical warning; all fidelity checks match |

Focused fixtures cover exact single warning, multiple warnings/order, retention before a later SQL clears state, a zero-warning schema/data import, exact level/code/message, missing-trigger Note and duplicate-trigger creation Note, SQL-mode context without altering its value, immediate error and earlier-warning persistence on failure, unsuppressed list/count agreement, and refusal to accept warnings automatically. All state-changing fixtures ran only inside the disposable MariaDB. The synthetic fixture database was dropped; the independently restored real snapshot was never used for synthetic actor/grant tests.

PHP lint passed for all three changed/created PHP helpers. `git diff --check` passed. Focused whitespace/source review was performed because these phase files are untracked in the pre-existing working tree. Full Phase 3.19 lifecycle/behavioral MariaDB tests and normal deployment tests were not run: they are outside the requested diagnostic correction, and no deployment readiness is claimed.

## Normal database safety

Read-only preflight, pre/post-restore and final checks compared normal **medical1/3306** against the retained fresh baseline. Final check: **2026-10-10 20:15:23.827282 UTC**. All 54 table counts/digests, complete schema, eight triggers/character contexts, privileges, defaults and SQL/character state remain unchanged.

Account 1 remains active, sole administrator/catalog authority, non-expiring **NULL expiry**, with the same single owner establishment event/snapshot. Other authority remains zero. No new audit event was caused by this work. All five active CANDIDATE / UNKNOWN registry rows and existing data remain unchanged, no canonical approval/reviewer/enrollment exists, and the provisional table remains absent. Medicine pilot/public exposure stays OFF and Treatment HOLD. Phase 3.22 tables remain absent.

Normal DDL: **0**. Normal data/authority writes: **0**. Normal SELECT/SHOW verification was performed. No genuine browser login/bootstrap POST or authentication substitution was used. Original and fresh backups, schema/migration hashes, configuration and application code remain unchanged. No Git-state mutation occurred.

Normal preservation evidence: `phase319-diagnostic-preflight.json` and `phase319-diagnostic-finalcheck.json`.

## Files changed and created

Modified only diagnostic tooling:

- `tests/medicine-owner-schema-deployment.php`: immediate successful/failed statement diagnostics, cursor closure, complete condition count/list check and optional retention callback; no parser/SQL semantics or acceptance weakening.
- `tests/medicine-phase319-deployment.php`: immediate restore journal retention and library-only dispatch guard; existing zero-warning recovery gate preserved.

Created:

- `tests/medicine-phase319-recovery-diagnostics.php`: guarded diagnostics-only driver, synthetic self-tests, two independent recovery comparisons and normal read-only safety checks.
- `docs/MEDICINE_PHASE_3_19_RECOVERY_DIAGNOSTIC_CORRECTION.md`: this report.
- Protected `phase319-diagnostic-{preflight,selftest,restore1,restore2,finalcheck}.json`, synthetic `phase319-diagnostic-failure-fixture.json` and `phase319-diagnostic-last-fixture.json`, and archived disposable server logs.

No application/controller/service/repository/configuration/migration/backup change; no normal database schema change. Existing unrelated user changes were preserved.

## Remaining limitation and final result

The warning is fully captured, explained and independently repeatable. Exact fidelity is demonstrated for both complete restores, but **nonzero Warning 1906 has not been accepted under the existing recovery policy**. No backup modification, warning suppression, filtering, normalization, migration change or relaxed threshold was used to turn it into PASS.

Recovery status: **BLOCKED**. Warning-capture correction and tests are complete. Phase 3.19 and Phase 3.22 remain undeployed. This operation stops here.
