# Medicine Phase 3.21A controlled normal deployment

**PHASE 3.21A — PASS.** Completed 2026-10-11 Asia/Calcutta. Normal migration and genuine Account 1 protected owner bootstrap succeeded. Only Account 1's permanent Medicine Administrator authority was established. Earlier authentication-blocked attempts did not execute the migration or bootstrap.

## Pre-deployment baseline and recovery

Target: **medical1**, port **3306**, MariaDB **10.4.27-MariaDB**. Session/global SQL mode before and after: `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`. Client/connection/results/server character sets remain utf8mb4; connection/server collations remain utf8mb4_general_ci.

Account 1 was active, authority grants **0**, Medicine governance events **0**, and expires_at was NOT NULL. The complete fresh baseline records all **54** table counts and order-independent full-row SHA-256 digests; table/view DDL; eight trigger definitions and exact SHOW CREATE character contexts; routines/events; operator privileges SHA-256; version/mode/character state. Evidence: `storage/logs/medicine321a-owner-recovery/final-preflight.json`.

The initial historical-preservation check found a data digest difference from the original backup and stopped that check. A read-only comparison against a fresh isolated restoration established that only Account 1's **last_login_at** and **updated_at** had changed through genuine login. Every other user field and all other tables matched. These expected login timestamps were incorporated into the fresh baseline, preserved by migration and bootstrap, and were not restored or overwritten.

Original authoritative backup, reverified before migration:

- Path: `D:\xampp\htdocs\kobi\storage\logs\medicine321a-owner-recovery\before.sql`.
- Size: **5,352,130 bytes**.
- SHA-256: **f303f482ba49931db370a33b833bf95e5106e3c20de21d47ce3bfc4c78108cf2**.

The backup was neither replaced nor altered. Both retained independent recovery certificates were checked as RESTORED_VERIFIED with matching backup identity and repeatability. See [recovery verification](MEDICINE_PHASE_3_21A_RECOVERY_VERIFICATION_CORRECTION.md) for the confirmed recovery procedure and scope.

An additional fresh guarded disposable server on port 3342 restored the original dump through the lossless importer: **499 statements**, zero omissions and zero warnings. All 54 table digests, schema objects and eight exact trigger definitions matched the original backup baseline. The exact approved ALTER was rehearsed there with zero warnings. Only the expected expiry column/CHECK changed; data, other schema and triggers remained identical. This produced `final-rehearsal.json`, including the observed login-field names without account credentials or personal values.

Recovery remains a database-specific logical restore. Recorded operator privileges were compared; the backup does not represent all server account credentials. Trigger creation timestamps are not a reproducible dump property; trigger DDL/body/character context were compared exactly.

## Exact normal migration

Unchanged artifact: `database/medicine-phase321a-owner-authority.sql`.

SHA-256: **2c96acc2e198b9838c9388c45abedb6c355c7b8ae1cd750bbad7c1365b97ebd1**.

The sole normal DDL executed was:

```sql
ALTER TABLE `medicine_authority_grants`
 MODIFY COLUMN `expires_at` datetime(6) DEFAULT NULL,
 DROP CONSTRAINT `chk_medicine_grant_active`,
 ADD CONSTRAINT `chk_medicine_grant_active` CHECK (is_active IN (0,1) AND ((expires_at IS NOT NULL AND expires_at > granted_at) OR (expires_at IS NULL AND BINARY capability=BINARY 'administrator' AND BINARY scope=BINARY 'catalog' AND user_id=granted_by AND qualification_reference IS NULL AND qualification_assessed_by IS NULL AND qualification_scope IS NULL)));
```

Start UTC: **2026-10-10 19:30:05.793970**. End UTC: **2026-10-10 19:30:05.899402**. One statement executed; warnings **0**, errors **0**, exit status **0**. Exact artifact bytes and execution metadata are recorded in `final-normal-ddl.json`. No other normal DDL, SQL-mode directive or manual authority write was executed.

Immediately before execution, all fresh baseline values were rechecked. Immediately afterward, all 54 table counts/digests were identical. Actual complete schema metadata matched the isolated rehearsal, including all columns, indexes, foreign keys and CHECKs. The only schema differences were the approved expiry column and CHECK. All eight original triggers, exact SHOW CREATE contexts, recorded privileges and SQL/character state remained identical. Account 1 remained active and grants/events remained zero. Evidence: `final-post-migration.json`.

## Genuine protected bootstrap and exact authority

The user's existing Codex browser tab was used through the normal application. GET `/admin/medicine/owner-bootstrap` displayed the fixed Account 1 owner page instead of redirecting to Login. This endpoint validates actual AuthService::id() = 1, an active account and the explicit owner decision. Authentication was rechecked before migration and after migration. No CLI/test actor, cookie copying, session-file access or authentication bypass was used.

After migration verification, the displayed CSRF-protected **Confirm owner administrator setup** button was submitted once. No account, role or expiry parameter was added or altered. The normal application reported establishment success and closed setup.

Read-only verification afterward found exactly:

- Grant **1**: user **1**, granted_by **1**, capability **administrator**, scope **catalog**, active **1**, expires_at **NULL**; all qualification fields NULL.
- Grant timestamp UTC: **2026-10-10 19:30:25.594320**.
- No contributor/editor, reviewer, deployment, registry, delegated administrator, other-account or other Medicine authority.
- Governance event **1**: actor **1**, grant_subject_id **1**, action **grant**, revision **1**, previous_state NULL, new_state **active**, authority_grant_id NULL, independent 0, no other content subject.
- Event timestamp UTC: **2026-10-10 19:30:25.595733**.
- Snapshot SHA-256: **41ad91d0341eb8a147429876a43a3b8573b3602cb280cf8439077024b860778d**.
- Establishment mechanism **initial_owner_bootstrap**, owner_user_id **1**, policy_version **initial-owner-v1**, decision_reference **owner-account-1-choice-a-2026-10-10**.

The event's SHA-256 matches its stored snapshot bytes. Every grant snapshot field matches the actual grant, including NULL expiry. Subject binding, actor, state, timestamps, rationale/evidence shape and protected decision metadata passed verification. The existing immutable-event trigger definitions are preserved exactly; their UPDATE/DELETE rejection was tested on the isolated database rather than attempting writes to the normal audit.

The first post-bootstrap checker used an incorrect field name (`grant_id` instead of the schema's `grant_subject_id`) and stopped with an undefined-key warning. This was an operator-checker defect, not an observed audit integrity failure. The checker alone was corrected; complete read-only verification then passed. Migration and bootstrap were not repeated.

## Preservation, replay, security and activation

Safe GET refresh showed **Owner setup is closed**, with no bootstrap form. Subsequent read-only verification still found exactly one grant/event. No repeated normal POST or deliberate audit noise was generated. Genuine Account 2 login was not attempted; cross-account rejection and tamper resistance were tested with synthetic actors only in DB-free and isolated tests.

After bootstrap, all **52 unrelated tables** retain their exact baseline counts/digests; the two authority tables each have only the expected new row. All **54 tables** remain present. Complete schema still matches rehearsal apart from the expected AUTO_INCREMENT counter advances on those two inserted tables. All eight triggers, privileges, modes and character contexts remain identical. Evidence: `final-post-bootstrap.json`.

Pilot intake **OFF**, internal pilot **OFF**, activation list empty; public Medicine exposure remains **OFF/unchanged**. No Medicine Identity revision, candidate approval, evidence/reviewer assignment, pilot enrollment or publication was created. The full preserved data/configuration and scoped writes support these results. No public activation setting, route or application code changed. Treatment records/schema remain identical and effective publication mode remains **hold**.

## Actual tests and checks in this run

| Suite | Cases | Assertions | Result |
|---|---:|---:|---|
| Medicine owner authority bootstrap | 27 | 116 | PASS |
| Protected owner entry, DB-free | 40 | 82 | PASS |
| Protected owner entry, isolated MariaDB | 6 | 44 | PASS |
| Medicine governance | 56 | 249 | PASS |
| Medicine policy | 33 | 92 | PASS |
| Medicine foundation | 105 | 216 | PASS |
| Authentication Phase 1 | 45 scenarios | Not reported | PASS |
| Treatment administration hardening | 39 focused | Not reported | PASS; retained embedded suites also ran |

The protected-entry total is **46 focused cases / 126 assertions**. Embedded retained suites overlap; totals are not added across suites. Tests include unauthorized/inactive/cross-account denial, CSRF/method/input tampering, fixed target/role/NULL expiry, ordinary self-grant denial, delegated expiry requirements, rollback on audit failure, actual two-process bootstrap race (one success, one 409), repeated POST protection and immutable audit UPDATE/DELETE rejection.

The isolated protected-entry suite executed **142 schema statements** from unchanged structure.sql and intentionally omitted only:

```sql
/*!40101 SET SQL_MODE=''*/;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
```

This retained the test server's existing non-strict mode. It made zero normal database connections. In contrast, the separate original-backup restoration executed all 26 dump session-mode save/set/restore directives verbatim on the disposable connection and restored its original final session state. Neither workflow changed the normal/global server mode or persistent application configuration.

PHP syntax passed for **11** relevant PHP files: final deployment helper, deployment/recovery helpers, both protected-entry tests, controller, service, repository, both views and routes. Relevant changed references and final report/helper were reviewed; diff whitespace check passed. No separate port-3341 authority suite or broad live multi-account browser testing is claimed. The exact migration rehearsal, focused bootstrap suite and isolated protected-controller suite cover the requested migration/bootstrap security paths.

Both disposable databases were removed. The exact port-3342 process/datadir identity was verified, the process was shut down and confirmed exited, its diagnostic log archived, and its checked project-local datadir removed. Normal KOBI remained running.

## Files and safety confirmations

Modified: `docs/MEDICINE_PHASE_3_21A_OWNER_DEPLOYMENT.md`.

Created: `tests/medicine-owner-final-deployment.php`, a CLI-only guarded operator helper reusing the existing deployment-library connection/import/digest/metadata functions. It has no grant-write or actor-authentication path and refuses repeated migration execution.

Protected evidence created under `storage/logs/medicine321a-owner-recovery/`: `final-preflight.json`, `final-rehearsal.json`, `final-normal-ddl.json`, `final-post-migration.json`, `final-post-bootstrap.json`, and `fidelity-server-92e05e8c-d2ab-431f-935f-7f23dd22c231.err`. Existing `fidelity-normal-read-only.json` was refreshed by the initial historical comparison and records that historical data mismatch; the final-* proofs are the authoritative fresh deployment evidence. Disposable temporary test files were removed.

Database schema changed only by the one approved ALTER. Normal data writes consisted only of the genuine protected application's intended owner grant and audit event. Existing login-timestamp changes predated the fresh baseline. No direct SQL authority insertion, unrelated application/configuration/schema edit, Treatment change, pilot/public activation, credential disclosure or Git-state mutation occurred. The original backup and migration artifact remain unchanged.

**Final acceptance: PASS.** Account 1 is the designated owner and sole permanent Medicine Administrator, expiry NULL, with the correct immutable establishment event and all required preservation/security checks passed within the explicitly documented testing scope.
