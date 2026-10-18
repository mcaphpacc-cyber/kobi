# Medicine Phase 3.16 — Controlled schema deployment

Result: **PASS**. The normal-database additive deployment, backup restore rehearsal, exact-source metadata comparison, existing-data integrity checks and relevant regressions passed.

**Phase 3.16 deployed the Medicine governance schema only. No Medicine candidate was enrolled, approved, activated, or published.** No evidence, credentials, personnel, grants, governed revisions, approvals, enrollment content or seed data were created in normal KOBI.

## 1. Deployment objective and authorization

The Phase 3.16 request explicitly authorized normal-database schema deployment after backup and safety gates. Root AGENTS, authoritative schema, current Medicine repository/services/policy/configuration, pilot/foundation/governance tests and Phases 3.12–3.15 documentation were inspected. The twelve-table/eight-trigger fresh-deployment inventory follows Phase 3.13; the schema was neither regenerated nor redesigned. Application code and configuration were not changed.

## 2. Target database/version

Actual application connection: `medical1`, normal port **3306**, **10.4.27-MariaDB**. Preflight observed UTC **2026-10-09 21:28:08.257423** (India 2026-10-10). The normal connection used the existing Database configuration internally; no credentials were printed or changed.

Both normal session and global SQL mode: `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`. The target/version/mode guards passed before deployment. Restore and mutation regressions used separate guarded localhost test instances, ports 3328 and 3325, with project-local test datadirs; none of those mutation tests targeted normal KOBI.

## 3. Pre-deployment state and safety gate

There were **42 existing base tables** and none of the Medicine foundation/governance/pilot/private-link tables or Medicine triggers. No partial/conflicting Medicine table, trigger, routine or event was discovered. Existing normal schema/view definitions, trigger inventory, account-grant digest and all base-table full-row data digests were captured in protected local storage. Data was rechecked after backup and immediately before normal DDL.

`database/structure.sql` SHA-256 remained `b7829ac3e1463d959386c714fdbd364a09ede97bb3c9c90549d0f1036d85f7f1`. The Phase 3.12 pilot-table definition matches this source. The earlier Phase 3.12 action CHECK is intentionally superseded by Phase 3.13's documented `pilot_enroll`/`pilot_disable` extension; the Phase 3.13 CHECK was normalized and reconciled exactly against the current authoritative constraint. No old-state ALTER upgrade was applicable to this fresh installation.

## 4. Backup status and verification

Preserved local backup: `D:\xampp\htdocs\kobi\storage\logs\medicine316-recovery\medical1-before.sql`.

- Full logical pre-deployment `medical1` schema/data backup, including triggers and available routines/events, using bundled `mysqldump`, single-transaction mode, no table locks, no data-changing statements on the source connection.
- **5,306,544 bytes**, SHA-256 `f157061b27c6b335c962dffdfd46aa5ac6fcf8e857b4415c26ba46cd65b700ca`; process exit 0, empty protected stderr, nonempty file verified.
- Stored beneath the existing web-denied, Git-ignored storage/logs tree, with an additional `Require all denied` directory rule. No upload, public preview or backup-content logging was performed.
- A temporary client option file carried existing credentials only to the local backup process; it was removed in `finally` and absence was verified. The SQL backup itself contains sensitive application data and must remain restricted.
- **Full logical restore rehearsal performed** on isolated MariaDB 10.4.27 database `kobi_medicine_phase316_restore_20261010`: **321 import statements**, no import SQL errors. All 42 restored full-row table counts/digests matched the source preflight. The exact twenty additive statements were then rehearsed there without warnings; resulting metadata became the independently instantiated comparison baseline.

Only these SQL-mode directives were omitted from the **disposable backup restore stream**, leaving the stored backup unchanged:

```sql
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
```

They would otherwise replace the matching validation session mode. No other backup statement was intentionally omitted. Normal deployment never imported the backup/dump. Existing isolated regression harnesses likewise omitted their three documented structure-dump SQL-mode directives, without changing `structure.sql`.

Operational interruptions occurred **before normal DDL**: a tooling keyword guard initially mistook the literal `grant` action for a GRANT statement; the disposable server was initially unavailable, then had its default startup mode rather than the required production-matching mode; one restart overlapped disposable shutdown; and the restore parser initially treated the trailing dump completion comment as incomplete SQL. These tooling/startup conditions were corrected without altering authoritative schema or normal SQL mode. The partial disposable restore was removed and a complete restore repeated successfully. No normal deployment statement failed.

Disposable instances were initialized with production-matching startup mode for reproducibility; no normal global/server/session mode was changed and no compatibility workaround was applied to any DDL. Both disposable instances/datadirs and all five validation/restore databases were subsequently removed. The verified original backup and operator records remain available locally.

## 5. Schema deployed: exact objects

Twelve created tables:

1. `kobi_medicine_chemical_refs`
2. `kobi_medicine_identities`
3. `kobi_medicine_ingredients`
4. `kobi_medicine_presentations`
5. `kobi_medicine_strengths`
6. `health_medicine_identity_links`
7. `health_medicine_selected_ingredients`
8. `medicine_authority_grants`
9. `medicine_governance_events`
10. `medicine_registry_assessments`
11. `medicine_registry_lifecycle_events`
12. `medicine_pilot_records`

Eight created triggers:

- `medicine_events_no_update`, `medicine_events_no_delete`
- `medicine_substance_activity_history` (AFTER UPDATE on existing `kobi_substances`)
- `medicine_form_activity_history` (AFTER UPDATE on existing `kobi_substance_forms`)
- `medicine_lifecycle_no_update`, `medicine_lifecycle_no_delete`
- `medicine_pilot_no_update`, `medicine_pilot_no_delete`

The two activity triggers attach new history enforcement to existing registry tables; no existing column/table definition or existing trigger was modified. No existing row was updated to exercise them. All twelve new tables are **empty**, including private-link tables, authority, assessments, journals and pilot records.

## 6. Exact deployment SQL/source and execution

`database/medicine-phase316-deployment.sql` contains the exact extracted twelve CREATE TABLE/eight CREATE TRIGGER definitions in their original source order, with client delimiter framing for triggers. SHA-256: `19ae515199dfa5ca4bebfdcd7a1f5fccae69a522e2ee038a90c14854e6406cf0`.

No DROP, ALTER, TRUNCATE, seed/population, privilege, SQL-mode or constraint-disabling directives are in this deployment stream. The trigger bodies contain their already-approved future lifecycle journal INSERTs; creating a trigger does not execute those bodies against existing rows.

The CLI `tests/medicine-schema-deployment.php` extracts from the pinned authoritative source, checks the documented definitions, verifies the recovery proof and unchanged target baseline, then executes only those twenty statements. It refuses existing/partial objects and refuses automatically retrying a normal deployment once an execution log exists. It stops at any warning/error. It is explicitly CLI-only and returns 404 under web execution.

**20 executed, 0 warnings, 0 errors**. Sum of individual DDL execution time: approximately **0.451 seconds**. Every statement name, kind, source-statement digest, elapsed time and warnings/result is preserved in `storage/logs/medicine316-recovery/normal-ddl.json`; the exact SQL is reviewable in the deployment file. Client `DELIMITER` commands are framing, not server-executed statements.

## 7. Post-deployment metadata and security verification

Normal metadata matched the exact-source rehearsal for all expected tables, ordered columns/types/defaults/nullability/character sets/collations, indexes, constraints, FK columns/actions and full trigger bodies/timing/event/creation SQL mode. Verified **134 columns**, **66 distinct indexes** (97 indexed-column metadata entries), **12 primary keys**, **15 unique constraints**, **43 foreign keys**, **54 CHECK constraints**, **8 Medicine triggers**.

Both pilot actions exist in the validated governance action CHECK. All restrictive FKs, controlled code/target/lifecycle constraints and immutable event/lifecycle/intake triggers remain present. Isolated tests exercised tampering/lifecycle/pilot integrity rather than inserting synthetic governance into normal KOBI.

The normal table/view inventory increased by exactly the twelve expected tables; trigger inventory increased by exactly the eight expected triggers. Existing schema/view hashes and complete pre-existing trigger-definition hashes matched. Account grants were unchanged by digest comparison; no database privilege was granted. No unexpected tables, columns, triggers or application routes were introduced.

## 8. Existing-data integrity

Every one of the **42 existing base tables** retained its count and deterministic full-row digest. Digests cover every stored column, not only labels/statuses; sorted row-hash multisets retain duplicate multiplicity without exporting row contents. This includes substance IDs/names/normalized names/flags/governance fields, private medication contents, combination membership and all Treatment/Disease fields.

| Existing dataset/table | Before | After | Full-row digest |
| --- | ---: | ---: | --- |
| `kobi_substances` | 2,224 | 2,224 | MATCH |
| `health_medicines` | 3 | 3 | MATCH |
| `kobi_combinations` | 2,327 | 2,327 | MATCH |
| `kobi_combination_items` | 6,671 | 6,671 | MATCH |
| `treatment_protocols` | 2,150 | 2,150 | MATCH |
| `treatment_protocol_items` | 3,190 | 3,190 | MATCH |
| `treatment_systems` | 7 | 7 | MATCH |
| `user_treatment_preferences` | 14 | 14 | MATCH |
| `diseases` | 400 | 400 | MATCH |
| `disease_causes` | 553 | 553 | MATCH |
| `disease_content` | 400 | 400 | MATCH |
| `disease_diets` | 2,248 | 2,248 | MATCH |
| `disease_faqs` | 2,130 | 2,130 | MATCH |
| `disease_preventions` | 30 | 30 | MATCH |
| `disease_seo` | 0 | 0 | MATCH |
| `disease_symptoms` | 1,113 | 1,113 | MATCH |
| `featured_diseases` | 10 | 10 | MATCH |
| `user_saved_diseases` | 3 | 3 | MATCH |

Remaining existing tables also matched; their counts/digests are retained in protected preflight/post-deployment records. No normal INSERT/UPDATE/DELETE was performed. Verification was repeated after regressions at UTC **2026-10-09 21:32:29.482160**; all checks still passed.

## 9. SQL mode before/after

| Normal setting | Before | After |
| --- | --- | --- |
| Session SQL mode | NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION | NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION |
| Global/server SQL mode | NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION | NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION |

No normal session/global SQL-mode SET was executed; no project/server configuration or credentials were changed. `database/structure.sql` remains byte-for-byte unchanged.

## 10. Application compatibility and regressions

The existing application Database connected successfully to the newly deployed schema. Read-only repository smoke calls (`grants` without locking and `pilotRecords`) returned the expected empty state. No normal service mutation was invoked.

| Suite | Cases/checks | Assertions where counted | Failures |
| --- | ---: | ---: | ---: |
| Medicine policy | 33 | 92 | 0 |
| Medicine governance | 56 | 249 | 0 |
| Medicine foundation | 105 | 216 | 0 |
| Medicine pilot | 40 | 143 | 0 |
| Medicine policy MariaDB | 11 | 43 | 0 |
| Medicine governance MariaDB | 38 | 142 | 0 |
| Medicine foundation MariaDB | 29 | 96 | 0 |
| Medicine pilot MariaDB | 15 | 131 | 0 |
| Candidate selection | 55 | — | 0 |
| Pilot readiness | 41 | — | 0 |
| Treatment operations, including retained checks | 570 | — | 0 |
| Authentication | 45 | — | 0 |

Medicine service suites: **327 cases / 1,112 assertions**, zero failures. Live assertions reflect the permitted concurrency winner; 142 is this governance run's actual count. Mutation suites used only their guarded disposable databases, importing the unchanged full authoritative schema under the same production mode with their documented SQL-mode omissions. They verified authorization, lifecycle, immutability, enrollment and concurrency.

Candidate/readiness tests passed on a SELECT-only pre-deployment snapshot and were repeated offline after deployment; the normal full-row digests prove those registry facts did not change. The unchanged Phase 3.15 `audit` intentionally stops on deployed Medicine tables rather than applying its old undeployed-state assessment. It was not changed or misrepresented as a post-deployment live audit. Phase 3.16's live metadata/empty-state verification covers the deployed schema. Temporary census snapshots were removed after testing.

PHP syntax and `git diff --check` passed. Hash comparison of all 300 pre-existing files under app/public/tests/bootstrap/database/routes/docs found no changes; exactly the three reviewable additions listed below were created. No existing test assertion was changed/weakened. No new HTTP governance surface exists, so no real browser enrollment/approval or credential verification is claimed. A final read-only verification after disposable cleanup at UTC **2026-10-09 21:36:10.471852** passed again.

## 11. Rollback/recovery procedure

MariaDB CREATE TABLE/CREATE TRIGGER DDL can implicitly commit. **This deployment is not transactionally rollback-safe.** No normal destructive rollback or restore was performed.

The safest application rollback is to keep the compatible empty additive schema and both pilot flags off while reverting only reviewed application changes if needed. No application change occurred here. Do not narrow the action CHECK, delete governance history or automatically drop these objects, especially once later legitimate governance data exists.

For a separately authorized full recovery: stop application writers; preserve a new current-state backup and all legitimate post-deployment changes/audit history; inspect the saved pre-deployment backup digest and matching code/configuration state; restore into a new isolated database first; compare metadata, all table counts/digests and triggers; have the deployment operator approve the precise target/restore/cutover procedure. The preserved backup restores the **pre-Phase-3.16 database**, and therefore omits the twelve new tables/eight triggers. Restoring it over a later populated installation could lose later data; no blind inverse DROP script is supplied or authorized.

The logical recovery rehearsal described above passed, but an actual normal-instance outage/cutover and operator disaster-recovery process were not rehearsed. This does not claim transactional rollback or automatic server/user-grant recovery; existing server privileges were left unchanged. Keep the backup and protected records until the developer applies the appropriate retention policy.

## 12. Public exposure and security boundaries

**Public Medicine publication OFF.** Source/routing inspection found no connection from controllers/public routes/views to these governance/enrollment/internal services; existing medicine routes remain private health-record CRUD. No publication method/action was enabled, no public sitemap/search/SEO integration was introduced, and no unauthenticated Medicine projection was added. Existing routes/public/controller/configuration source hashes remained unchanged.

The schema does not itself establish application authorization or publish content. All new credential/grant/evidence/approval/enrollment storage is empty. Regression tests confirm actual active/scoped authority, professional qualifications/independence, exact current facts, separate deployment authority and minimal credential-free `published=false` projection. Registry authority remains separate; no privilege change or registry mutation API was added. Treatment publication configuration was read and remains **hold**.

## 13. Pilot status, files and records

**Pilot OFF:** `pilot_enabled=false`, `pilot_internal_enabled=false`, `pilot_substances=[]`, `pilot_activation=[]`; role/evidence configuration remains unpopulated. The fixed preparation IDs 15/54/319/481/2064 were not enrolled, converted into governed revisions, reviewed or altered.

Created reviewable files:

- `database/medicine-phase316-deployment.sql` — exact additive schema extraction.
- `tests/medicine-schema-deployment.php` — guarded CLI backup/rehearsal/deployment/verification tooling.
- This document.

Protected local recovery records created under `storage/logs/medicine316-recovery/`: `.htaccess`, `medical1-before.sql`, `preflight.json`, `backup-verification.json`, `expected-metadata.json`, `rehearsal-ddl.json`, `normal-ddl.json`, `post-deployment.json`, empty `dump.stdout`/`dump.stderr`. These files remain Git-ignored and must not be published. Temporary option file, real-data disposable restore datadir, test servers/datadirs and census snapshots were removed. No pre-existing application/configuration/schema/test/document file was modified. No commit, push, reset, checkout, merge or rebase was performed.

## 14. Remaining prerequisites before Phase 3.17

The undeployed-schema blocker is resolved. Real governance remains closed pending separately authorized operational work: named active administrator/deployment operator/contributors/editor and independent qualified reviewer; verified current professional credentials and legitimate scoped grants; appraised representative references and authoritative identity/composition/form-applicability evidence for each fixed candidate; current target-specific source/fact snapshots and appraisal bindings; explicit candidate creation, submission and independent approval; separately authorized operator enrollment and activation manifest/backup/deployment/public-disabled attestations. No role, evidence or approval can be inferred from this successful schema deployment.

Phase 3.16 ends here. Do not activate the pilot until the five candidates have current evidence, qualified independent review, explicit approval, and all Phase 3.13 activation prerequisites are satisfied.
