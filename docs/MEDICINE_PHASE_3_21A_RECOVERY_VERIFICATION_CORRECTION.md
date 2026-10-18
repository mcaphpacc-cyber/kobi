# Medicine Phase 3.21A recovery verification correction

Date: 2026-10-11, Asia/Calcutta. **Final readiness: PASS — recovery verification is trustworthy and repeatable for the protected KOBI database backup and recorded recovery-operator privileges.** No owner-authority migration or bootstrap was executed. Deployment remains a separate task; this PASS authorizes no deployment or authority creation.

## Final trigger-fidelity investigation and two independent restores

This section supersedes the earlier BLOCKED attempt preserved below. The final byte-preserving parser had previously passed offline checks but had not yet been exercised in a new live restore. There was no demonstrated remaining MariaDB conversion after that final correction; this continuation reproduced the retired tokenizer defect, traced it across the full path, and verified the corrected parser twice.

Authoritative backup: `D:\xampp\htdocs\kobi\storage\logs\medicine321a-owner-recovery\before.sql`, **5,352,130 bytes**, SHA-256 **f303f482ba49931db370a33b833bf95e5106e3c20de21d47ce3bfc4c78108cf2**. Existence/readability, size and hash matched the original manifest before each run and at final verification. The backup remained byte-for-byte unchanged; no replacement, normalized artifact or new backup was used.

### Exact cause and boundary evidence

The two historical defects were the SQL-mode whitelist rejecting valid trigger save/restore comments, and the tokenizer splitting with `/\r?\n/` then reconstructing lines using LF. The latter operation was the exact component responsible for CRLF→LF. DELIMITER processing, PDO MySQL and MariaDB storage/retrieval did not cause the conversion.

A separate disposable forensic restore reproduced the retired tokenizer's in-memory LF reconstruction. This deliberately faulty forensic path is explicitly named `fidelity-reproduce`; it is not the recovery procedure and never edits the protected backup. For all eight triggers:

1. Original backup contains the baseline CRLF body verbatim.
2. Retired tokenizer submitted an LF-only body to PDO, with a different byte length/hash.
3. INFORMATION_SCHEMA returned exactly those submitted LF bytes.
4. SHOW CREATE TRIGGER returned the same LF bytes.
5. Strict comparison against baseline CRLF rejected them.

The corrected procedure uses `preg_split('/(?<=\n)/', $source)`, retaining each original chunk, then `$buffer .= $line` without newline reconstruction. Trigger delimiter markers are excluded from the SQL sent to PDO; body bytes are unchanged. All executable mode comments are submitted intact. The comparison remains strict; no LF/CRLF normalization is used to obtain PASS.

An optional pre-execution observer records each parsed/submitted trigger body's byte length, SHA-256, CRLF count, LF-only count, CR-only count, statement hash and actual creation mode/character context immediately before `PDO::exec()`. It never rewrites SQL. After execution the verifier separately captures INFORMATION_SCHEMA bodies and complete SHOW CREATE TRIGGER output. All those boundaries matched exactly in both corrected restores. **No unavoidable MariaDB canonicalization was observed or used as an exception.**

### Trigger-by-trigger result

All rows below passed in both independent restores. Baseline/backup body, parsed/submitted body, stored body and SHOW CREATE body were identical. LF-only and CR-only counts were **zero for every trigger**; byte hashes also matched, detecting transformations beyond line endings.

| Trigger | Timing/event | Target | Body bytes | CRLF pairs | Exact across all boundaries |
|---|---|---|---:|---:|---|
| medicine_events_no_delete | BEFORE DELETE | medicine_governance_events | 86 | 2 | PASS |
| medicine_events_no_update | BEFORE UPDATE | medicine_governance_events | 86 | 2 | PASS |
| medicine_form_activity_history | AFTER UPDATE | kobi_substance_forms | 256 | 5 | PASS |
| medicine_lifecycle_no_delete | BEFORE DELETE | medicine_registry_lifecycle_events | 96 | 2 | PASS |
| medicine_lifecycle_no_update | BEFORE UPDATE | medicine_registry_lifecycle_events | 96 | 2 | PASS |
| medicine_pilot_no_delete | BEFORE DELETE | medicine_pilot_records | 95 | 2 | PASS |
| medicine_pilot_no_update | BEFORE UPDATE | medicine_pilot_records | 95 | 2 | PASS |
| medicine_substance_activity_history | AFTER UPDATE | kobi_substances | 266 | 5 | PASS |

Each trigger's exact name, timing, event, table, complete body, definer and SQL mode matched baseline. Complete SHOW CREATE definition text, client charset `utf8mb4`, connection collation `utf8mb4_general_ci` and database collation `utf8mb4_general_ci` also matched actual normal metadata exactly. Only SHOW CREATE's `Created` timestamp is excluded from definition comparison: the dump does not preserve creation timestamps, and recreation necessarily creates a new timestamp. No schema/SQL/body/character field is excluded.

### Independent restore results

Three completely disposable instances were used, all MariaDB **10.4.27**, port 3342, guarded datadir `tests/.medicine-owner-recovery/data`:

- Forensic retired-tokenizer reproduction: instance `a698a48a-47c3-4712-957e-a38a6ca09b27`, PID 2304. Expected eight body mismatches reproduced; disposed before corrected recovery.
- First corrected restore: instance `17293e23-093c-4af2-925c-8e265b9d5f48`, PID 5392. **PASS**, destroyed completely before the next instance.
- Second independent corrected restore: instance `bbaad328-8f13-43c9-948c-006a4e0daf24`, PID 13480. **PASS**, independently matched first restore and baseline, then destroyed.

Each corrected restore executed **499 dump statements**, including **26 SQL-mode directives**, with **zero omissions, SQL errors or warnings**. Exit code was **0**. Each restored exactly **54 base tables / 26,207 total rows**, with every table's row count and full sorted row SHA-256 digest matching the original preflight. No missing or additional rows appeared. Complete table/view SHOW CREATE definitions matched byte-for-byte, covering columns/types/defaults, indexes, foreign keys, CHECK constraints, engines/collations and AUTO_INCREMENT metadata. Trigger inventory was exactly eight; routines/events and other object inventories matched baseline with no additions.

The second run compared exact data, schema, trigger metadata, complete SHOW CREATE definitions, byte diagnostics, mode transitions/warnings, privilege digest and initial/final session/server settings to the first. Separate fresh datadir initialization and distinct instance IDs established independence; the first restored database/server was not reused.

### Privileges, SQL mode and environment fidelity

The actual isolated recovery operator's SHOW GRANTS digest matched protected normal preflight exactly and was unchanged by both imports: `ed56afa71d6afce3f87a6a27aa9ff47ad3060e29119e9adab128fbf7d04025a4`. No GRANT/REVOKE or authority mutation was executed. Application authority grants, owner grants and Medicine governance events remained zero in both restorations.

Session/global SQL mode before and after each restore was `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`. During import the unaltered dump temporarily sets session `NO_AUTO_VALUE_ON_ZERO`, saves/sets/restores the correct mode around every trigger, and restores the initial session mode at completion. Every trigger's persisted creation mode equals baseline. No global SQL-mode assignment or persistent normal configuration change occurred.

The disposable startup explicitly matched normal server charset/collation (`utf8mb4` / `utf8mb4_general_ci`), and restored database defaults matched normal metadata. Before/after client/connection/results charsets were `utf8mb4`, connection collation `utf8mb4_general_ci`. The earlier disposable default `latin1` was not used for these final runs.

Isolated startup retained validated Windows buffered I/O: `--innodb-flush-method=normal` (MariaDB reports `fsync`) and `--innodb-use-native-aio=0`. These affect only disposable instances and avoid the previously recorded isolated unbuffered-write failure. No server error was suppressed; server logs were retained. Versions remain PHP **8.2.0**, PDO MySQL; mysql client **15.1 / Distrib 10.4.27-MariaDB**; mysqldump **10.19 / Distrib 10.4.27-MariaDB**. No client SQL newline canonicalization occurred in the successful runs.

### Reproducible procedure and retained proof

From the project directory, with the original protected backup/manifests present:

1. Run `php tests/medicine-owner-recovery-verification.php normalcheck` for strictly read-only preservation, complete SHOW CREATE trigger character metadata and database default verification. This reads no credentials into output and requests no Account 1 authentication.
2. Run `tests/medicine-owner-recovery-environment.ps1 start` to initialize a fresh disposable datadir/server, with explicit port/datadir/SQL mode/character settings and a new instance identity.
3. Run `php tests/medicine-owner-recovery-verification.php restore1`. Stop on any failure; no migration or bootstrap is invoked.
4. Run the driver's `cleanup` mode, then the environment script's `stop` action. Confirm actual isolated process exit and datadir removal.
5. Start a completely fresh environment and run `restore2`, which checks its independent identity and compares all results against `restore1`.
6. Clean up the second environment; repeat read-only normal preservation, backup hash and configuration checks.

The forensic `fidelity-reproduce` mode exists only to demonstrate the retired parser defect on a disposable instance; it is not a PASS path. No normal deployment command is part of this recovery procedure.

Protected evidence in `storage/logs/medicine321a-owner-recovery/`: `fidelity-fidelity-reproduce.json`, `fidelity-restore1.json`, `fidelity-restore2.json`, `fidelity-normal-read-only.json` and three `fidelity-server-<instance-id>.err` logs. These include full per-table counts/digests, schema and trigger definitions, byte hashes/counts, before/after SQL modes, character settings and privileges. Earlier stopped-attempt evidence remains separate and is not relabeled as success.

### Final safety checks, files and limitations

Final SELECT/metadata-only normal verification passed against original preflight: all 54 table data digests, all table/view/trigger/routine/event metadata and recorded privileges remain unchanged. Account 1 is active with **zero authority grants**; Medicine audit events remain **zero**; expiry remains NOT NULL. No Phase 3.21A migration, bootstrap, new authority, Medicine provisional/identity record, pilot/public activation or Treatment change occurred. Both pilot flags remain false, activation is empty, and actual Treatment publication mode is HOLD. No application/schema/config file was modified, and no Git mutation occurred. Original backup hash is unchanged.

All three isolated databases/servers/datadirs were removed after process identity/exit and path/reparsepoint checks; protected backup/proofs/logs remain. Shutdown cleanup is scoped to the exact disposable datadir and port; normal server was never stopped.

Modified this continuation: `tests/medicine-owner-schema-deployment.php` (passive pre-execution observer), `tests/medicine-owner-recovery-verification.php` (forensic byte diagnostics, strict SHOW CREATE comparison, independent-instance verification), and this report. Created `tests/medicine-owner-recovery-environment.ps1` (guarded fresh startup/cleanup). The final line-preserving tokenizer correction itself was already implemented in the preceding attempt and has now passed live validation.

Focused testing: existing **19-assertion** offline protected-dump parser regression passed, PHP lint passed for the importer/driver/offline test, PowerShell syntax passed, forensic reproduction and both independent live restores passed, final read-only normal/backup/configuration checks passed, and `git diff --check` passed. No unrelated suites were rerun. One standalone configuration-check command initially used an incorrect key, then was corrected to the existing `treatment_governance` key; no configuration file changed.

Scope limitations are non-material to faithful recovery of this `medical1` backup: server-wide accounts/credentials are outside a single-database dump and were not recovered; the recorded recovery operator privileges were verified exactly. Creation timestamps are regenerated as explained above. Application/browser/clinical validation and genuine Account 1 bootstrap remain separate future work. The prior physical I/O failure's underlying OS cause is not proven; the documented isolated buffered-I/O procedure passed all three runs. This report certifies only the tested recovery procedure, not a normal deployment or bootstrap.

## Historical stopped investigation (superseded)

The following sections preserve the preceding attempt and its then-BLOCKED status. Its deferred live checks and repeat restore are now completed above; they are not current blockers.

## Previous failure and protected backup identity

The previous attempt rejected `/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;` after 257 isolated import statements. It had omitted the initial dump SQL-mode directive. The normal database was unchanged.

The authoritative path was obtained from `MEDICINE_PHASE_3_21A_OWNER_DEPLOYMENT_ATTEMPT.md`:

- `D:\xampp\htdocs\kobi\storage\logs\medicine321a-owner-recovery\before.sql`
- Size: **5,352,130 bytes**.
- SHA-256: **f303f482ba49931db370a33b833bf95e5106e3c20de21d47ce3bfc4c78108cf2**.

Existence/readability, exact byte size and SHA-256 were checked against the retained backup manifest before use and again afterward. The same backup was used throughout; it was not edited, replaced, regenerated or normalized. All eight baseline trigger bodies appear verbatim in its original bytes. Protected `preflight.json` supplies all 54 table row counts/digests, complete schema definitions, trigger/routine/event metadata and the original operator privilege digest.

## Versions and isolated environment

- Server: MariaDB **10.4.27-MariaDB**, dedicated port **3342**.
- MariaDB client: **15.1 / Distrib 10.4.27-MariaDB**, Win64, revision `0946c99e7d6f7ac9dfcf3e60dae6ae85161d5ef2`.
- Backup generator: mysqldump **10.19 / Distrib 10.4.27-MariaDB**.
- Importer: PHP **8.2.0**, PDO MySQL, `oImport()` in `tests/medicine-owner-schema-deployment.php`.
- Disposable datadir: `tests/.medicine-owner-recovery/data`.
- Disposable database: `kobi_owner_recovery_20261010`.
- Initial session/global mode: `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`.

The recovery driver checks version, port, resolved datadir, both modes and fresh database absence before restore writes. It never dispatches normal deployment, the migration ALTER or bootstrap. Only the explicitly separate `normalcheck` mode connects to the normal database, using SELECT/metadata only.

## Exact parser root cause and dump context

The relevant original dump context is at lines 1235–1241:

```sql
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `medicine_form_activity_history` AFTER UPDATE ON `kobi_substance_forms` FOR EACH ROW
```

The old importer matched every line containing `SQL_MODE\s*=` and required it to match a narrow whitelist. That whitelist allowed only `@OLD_SQL_MODE`, literal mode assignments, and restoration from `@OLD_SQL_MODE`, with the delimiter directly adjacent to `*/`. The valid trigger-specific `@saved_sql_mode` save directive matched neither the permitted variable form nor that spacing rule. The importer threw **`Unrecognized mode directive; stop.`**, exit code **1**, before submitting it to MariaDB. This was a tooling/SQL-mode handling defect, not malformed backup syntax or a trigger DELIMITER error.

MariaDB supports these versioned executable comments; their delimiters belong outside the comment. Session mode assignments affect the current connection. See [MariaDB comment syntax](https://mariadb.com/docs/server/reference/sql-statements/comment-syntax) and [SQL_MODE](https://mariadb.com/docs/server/server-management/variables-and-modes/sql_mode). The complete isolated import later executed the save/set/restore statements successfully on the actual 10.4.27 server with no SQL errors or warnings, confirming applicability to this version.

## Reproduction and separate environment issue

An initial isolated attempt encountered an InnoDB file-write assertion before reaching the parser failure: `Retry attempts for writing partial data failed`, `os0file.cc` line 6636, `err == DB_SUCCESS`; client error **2006 / MySQL server has gone away**, exit **1**. This error was retained and not ignored. Its underlying OS/storage cause is not proven; disk-sector inspection was denied by Windows, and adequate free space was observed.

The failed disposable datadir was discarded after its process exited. A fresh isolated instance using Windows buffered I/O (`--innodb-flush-method=normal`, actual `fsync`; `--innodb-use-native-aio=0`) allowed the original parser failure to be reproduced exactly. This changed only disposable startup I/O behavior, not schema, data, constraints or the normal server. [MariaDB documents these flushing options](https://mariadb.com/docs/server/server-usage/storage-engines/innodb/innodb-system-variables). The physical I/O cause remains an environment limitation, not an established backup defect.

The successful reproduction captured initial/final state and unchanged privileges in `correction-reproduce-failure.json`. Message was exactly `Unrecognized mode directive; stop.`, exit **1**, stderr contained that message, stdout was empty. Initial/final modes remained the expected modes.

## Surgical correction and safety

The SQL-mode line filter/omission branch was removed from the recovery importer. Each original executable comment is now sent intact to MariaDB using the existing delimiter-aware statement path. No SQL-mode directive is suppressed, and no backup normalization is performed. Each executed mode directive and its before/after session value is recorded. SQL exceptions propagate; warnings are captured.

The original dump's initial `NO_AUTO_VALUE_ON_ZERO` assignment is now executed in the isolated restore connection, followed by the dump's trigger-specific save/set/restore blocks and final restoration. This reproduces dump semantics, including literal zero AUTO_INCREMENT values, instead of relying on a whitelist or stripping meaningful session state. Global mode is never assigned by the importer.

After this change, a fresh restore completed all **499 statements**, including **26 mode directives**, with **0 omissions and 0 warnings**. However, strict trigger metadata comparison failed, so the required stop condition was enforced and no further live restore was attempted.

Offline investigation identified a second tooling defect: `preg_split('/\r?\n/', ...)` followed by appending `"\n"` converted stored trigger-body CRLF bytes to LF. All eight `ACTION_STATEMENT` values differed only in those bytes; every other trigger field matched. Comparing with normalized line endings was used only to diagnose the cause, never to pass recovery verification.

The final parser correction splits at `/(?<=\n)/` and appends each original chunk unchanged, preserving original line endings. This is a lossless in-memory statement boundary operation, not a modified recovery artifact. The original backup remains authoritative. **This final correction was validated offline only; a live restore with it remains deferred after the explicit trigger-difference stop.**

The recovery result is marked `BLOCKED_TRIGGER_BODY_LINE_ENDINGS`, not verified. The driver now records pending comparison first and writes a verified status only after all assertions succeed. No deployment recovery certificate or expected migrated metadata was created.

## Data/schema/trigger/privilege comparison

Results of the one complete SQL import, before the final line-ending correction:

| Check | Actual result |
|---|---|
| Base tables | Exactly 54, matching baseline inventory |
| Data | Every table's row count and full sorted row SHA-256 digest matched; no missing/additional rows |
| Table/view schema | All complete SHOW CREATE definitions matched byte-for-byte |
| Columns/defaults/indexes/FKs/CHECKs/engines/collations | Covered by exact complete definition comparison; all matched |
| Routines/events | Exact baseline metadata match; no unexpected objects |
| Triggers | Exactly 8; names, event, timing, table, mode and definer matched; all 8 bodies differed in CRLF→LF only |
| Authority grants | 0 total, 0 for Account 1 |
| Governance audit events | 0 |
| Operator privileges | Actual isolated SHOW GRANTS digest matched original protected normal operator baseline, unchanged before/after import |
| Treatment data/schema | Included in full-table data and schema comparisons, unchanged |

The full per-table counts/digests, full schema definitions, exact trigger metadata, privilege comparison and every mode transition are retained in protected `correction-restore1.json`; baseline is protected `preflight.json`. No personal rows or credentials were printed or copied into this report.

Privilege scope limitation: mysqldump of `medical1` does not contain all server accounts or all server privilege tables. This test verified the recorded recovery operator's actual privileges and application authority data, and executed no GRANT/REVOKE. It does not certify disaster recovery of every server principal/credential.

## SQL mode and character settings

The complete restore temporarily set session mode to `NO_AUTO_VALUE_ON_ZERO` as the unaltered dump specifies. Each trigger creation used exactly `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`, matching all eight baseline trigger modes. The dump restored the session mode at completion; global mode was unchanged throughout.

Before and after the complete restore:

- Session/global mode: `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`.
- Client/connection/results character sets: `utf8mb4`.
- Connection collation: `utf8mb4_general_ci`.
- Disposable server defaults: `latin1` / `latin1_swedish_ci` from `--no-defaults`.

Normal read-only inspection reports server defaults `utf8mb4` / `utf8mb4_general_ci`. The dump explicitly restored table definitions and trigger character context, and these compared as described. The default-server difference is disclosed; a subsequent certification run should explicitly reproduce normal server character defaults on its disposable instance as well. No normal character settings were changed.

## Repeatability and stop result

No second independent restore was performed. The complete import stopped certification at the exact trigger comparison, as instructed. The final line-preserving parser passes an offline regression against the same protected dump: **19 assertions**, **499 statements**, **54 CREATE TABLE statements**, **8 byte-preserved original trigger bodies**, and **26 executed mode directives**. This is not a substitute for MariaDB metadata verification.

Recovery certification and Phase 3.21A deployment remain **BLOCKED** pending a fresh isolated restore using the final parser, exact trigger comparison, and an independent repeat. The parser defect is explained and corrected in code, but no live success with the final correction is claimed. Account-1 authentication remains outside this recovery-only scope and was neither required nor fabricated.

## Normal preservation, cleanup and testing

A final strictly read-only normal inspection was necessary to substantiate the requested preservation/privilege confirmation. All 54 data digests, all schema/trigger/routine/event metadata and operator privileges still match the retained baseline. Account 1 remains active; authority grants and audit events remain **zero**; expiry remains NOT NULL because the migration was not applied. Normal session/global SQL mode is unchanged. Config/public/pilot/Treatment governance and the migration artifact were not changed.

The isolated database was dropped and its absence verified. Graceful server shutdown did not promptly release the process/datadir lock. An automatic approval review rejected a recursive cleanup retry because a stale PID check did not establish that the lock holder had exited. Cleanup then used fresh elevated process identity checks (exact isolated datadir and port), terminated only that isolated process, confirmed its eventual disappearance, and removed the remaining empty directories nonrecursively. Normal process 4944 was not stopped. Protected backup/evidence/logs remain; no cleanup permission is outstanding.

Testing performed: lint for the three changed/new PHP tooling files, the offline 19-assertion parser regression, original-failure reproduction, one full isolated import with strict comparison, read-only normal preservation/privilege checks, original backup hash verification and `git diff --check`. No unrelated regression suites were rerun. No commit/push/reset/checkout/merge/rebase or other Git operation mutating state was executed.

## Files changed

Modified: `tests/medicine-owner-schema-deployment.php` — library dispatch guard, verbatim mode handling, original line-ending preservation, mode/warning evidence.

Created: `tests/medicine-owner-recovery-verification.php` — guarded recovery-only driver; `tests/medicine-owner-recovery-parser.php` — offline protected-backup regression; this report.

Protected evidence created: `correction-reproduce-failure.json`, `correction-restore1.json`, `correction-normal-read-only.json`, `correction-first-server.err`, `correction-buffered-server.err`. Original backup/manifests and earlier stop evidence were retained. No production application or schema file was modified.
