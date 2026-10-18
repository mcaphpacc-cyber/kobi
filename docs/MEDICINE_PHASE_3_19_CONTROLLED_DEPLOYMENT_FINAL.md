# Medicine Phase 3.19 controlled deployment

**PHASE 3.19 CONTROLLED DEPLOYMENT — BLOCKED**

The exact migration completed on normal KOBI, but the post-deployment exact trigger-metadata comparison failed. No further deployment, repair, restoration, or regression execution was attempted after that failure. The schema objects remain installed. This is not a completed acceptance pass.

## Target and execution

- Database: `medical1`, configured host `localhost`, server hostname `LAPTOP-PTFFHOMV`, port `3306`.
- MariaDB: `10.4.27-MariaDB`.
- Preflight: `2026-10-10 20:48:57.801579 UTC` / `2026-10-11 02:18:57.801579 Asia/Calcutta`.
- Migration start: `2026-10-10 20:54:15.233551 UTC` / `2026-10-11 02:24:15.233551 Asia/Calcutta`.
- Migration end: `2026-10-10 20:54:15.309496 UTC` / `2026-10-11 02:24:15.309496 Asia/Calcutta`.
- Final read-only diagnostic: `2026-10-10 20:55:04.921967 UTC`.
- Inventory before: **54 tables / 8 triggers**. Inventory after: **55 tables / 11 triggers**.
- Migration artifact: `D:\xampp\htdocs\kobi\database\medicine-phase319-provisional.sql`.
- Migration SHA-256: `84166a59636a8eefcf580bc8bc71b09c19951255a865d2b8a8415ba62a41be48`, matching the previously validated artifact and the authoritative structure block.
- All **4** executable migration statements succeeded; **0 MariaDB warnings / 0 MariaDB errors**. The deployment command subsequently exited `1` because its metadata verification failed.
- The normal execution journal retains every attempted SQL statement verbatim and its immediate diagnostics: `storage/logs/medicine321a-owner-recovery/phase319-final-normal-ddl.json`.

## Fresh backup and recovery

Fresh full pre-deployment backup:

- Path: `D:\xampp\htdocs\kobi\storage\logs\medicine321a-owner-recovery\phase319-final-before.sql`.
- Size: **5,353,453 bytes**.
- SHA-256: `6d9d3edd66ed5f4c55456befb59e67d675600ba518762e998bc49f94a2e21a75`.
- Dump exit status `0`, empty stderr, nonempty/readable file, full live baseline unchanged across the dump. Identity was checked again after deployment.
- Earlier authoritative backups and recovery certificates were retained unchanged.

The previously certified schema-aware generated-column DERIVED recovery procedure remains available. Its two independent restore certificates, generated-value comparisons, privilege/session comparisons, and existing recovery tests were checked; they were not rerun or redesigned. No backup was restored during this task.

A DERIVED copy of the fresh checkpoint was prepared without executing it:

- Path: `D:\xampp\htdocs\kobi\storage\logs\medicine321a-owner-recovery\phase319-final-DERIVED.sql`.
- Size: **5,353,662 bytes**.
- SHA-256: `6fb80dd667dd591f2bf427de166e6b6a6c59ee0cc54940ddab7eec51ed632cf6`.
- Exactly one INSERT transformation omits explicit generated `active_slot` inputs; the manifest records unchanged remaining bytes. The authoritative fresh backup is unchanged.

No ad-hoc rollback was attempted. Any recovery or corrective action requires resolving this recorded stop under the approved recovery/deployment policy.

## Executed normal schema changes

Only the unchanged Phase 3.19 artifact was executed against normal KOBI:

1. `CREATE TABLE medicine_provisional_identity_revisions`.
2. `CREATE TRIGGER provisional_identity_before_insert`.
3. `CREATE TRIGGER provisional_identity_no_update`.
4. `CREATE TRIGGER provisional_identity_no_delete`.

No normal data writes, seeds, ALTER, DROP, grants, unrelated schema statements, or SQL-mode directives were executed. Phase 3.22 was not deployed. The migration file and `database/structure.sql` were not modified.

The normal table's complete `SHOW CREATE TABLE` matches the exact-artifact rehearsal: all 11 non-null columns, types/defaults, composite primary key `(substance_id, revision)`, two secondary indexes, three foreign keys, four CHECK constraints, engine and collations. There are no generated columns in this new table. Its row count is **0**.

All three new triggers match the rehearsal's `information_schema.TRIGGERS` definitions, including body, timing, event, definer and SQL mode. Their `SHOW CREATE TRIGGER` statement text also matches. The comparison failure is in the creation character-set context described below.

## Exact stop reason

For **each** of the three new triggers, `SHOW CREATE TRIGGER` differs in exactly these fields:

| Field | Disposable rehearsal | Normal KOBI |
| --- | --- | --- |
| `character_set_client` | `utf8` | `utf8mb4` |
| `collation_connection` | `utf8_general_ci` | `utf8mb4_general_ci` |

All other compared new trigger fields match, apart from intentionally excluded creation timestamps. Database collation and SQL mode match.

The rehearsal imported the authoritative schema prefix, which contains `/*!40101 SET NAMES utf8 */;`. That statement changed the rehearsal session's character-set context before the Phase 3.19 artifact was executed. Normal KOBI's application connection remained `utf8mb4` / `utf8mb4_general_ci`. The operator helper should have compared these contexts before normal execution; it did not. This is a rehearsal/verifier preparation defect, not a MariaDB migration execution error. It has not been corrected or waived after the required stop.

Detailed read-only evidence: `storage/logs/medicine321a-owner-recovery/phase319-final-blocked.json`, including the six exact field differences and full protected actual inventory.

## Existing data, authority and application integrity

Read-only verification after the failure confirms:

- All **54 pre-existing tables** retain their complete definitions and full row counts/digests. All **8 pre-existing triggers** retain exact metadata and `SHOW CREATE TRIGGER` definitions.
- Existing routines/events, database defaults, privileges and connection/server state remain unchanged.
- Existing total rows remain **26,209**; the new table contributes zero rows.

| Existing table | Unchanged rows |
| --- | ---: |
| `kobi_substances` | 2,224 |
| `health_medicines` | 3 |
| `kobi_combinations` | 2,327 |
| `kobi_combination_items` | 6,671 |
| `treatment_protocols` | 2,150 |
| `treatment_protocol_items` | 3,190 |
| `diseases` | 400 |

Account 1 remains the sole active Medicine Administrator, with **NULL expiry**, unchanged grant and exactly one unchanged owner-establishment audit event. No contributor/editor, reviewer, deployment operator, delegated administrator or other authority was added or changed. Owner audit snapshot SHA-256 remains `41ad91d0341eb8a147429876a43a3b8573b3602cb280cf8439077024b860778d`.

The five registry candidates retain their existing active CANDIDATE/UNKNOWN states. No provisional records, canonical Medicine identities, chemical references, reviewer records, evidence appraisals, approvals or pilot enrollments were created. Phase 3.22 reviewer/evidence tables remain absent in normal KOBI.

Medicine pilot and public Medicine remain **OFF**. Treatment remains **HOLD**, with existing data, schema and exposure gates unchanged. Hashes of the preflight application inventory (`app`, `routes`, PHP/JS/CSS under `public`) and the recorded schema artifacts match after deployment. No application, authentication, policy or configuration code was modified.

Global/server SQL mode, application SQL-mode configuration and normal session mode were not changed:

`NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`

No Git mutation was performed.

## Disposable rehearsal and operator tooling

All disposable work used the guarded MariaDB 10.4.27 instance on port **3342**, separate from normal KOBI. No normal backup restoration occurred.

The successful empty-schema rehearsal executed **135** base-schema statements and then the exact **4** Phase 3.19 statements, with zero warnings/errors. Only these two SQL-mode directives were omitted from the imported schema prefix to preserve the existing mode:

```sql
/*!40101 SET SQL_MODE=''*/;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
```

The later `/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;` lies outside that prefix and was not part of this import. No other prefix schema statement was omitted. In particular, `SET NAMES utf8` was executed, producing the metadata context mismatch. Phase 3.22 was not imported by this prefix rehearsal.

Two earlier disposable attempts exposed tooling issues before any normal migration SQL:

1. The prefix accounting incorrectly expected three SQL-mode directives; it contains two. That exact accounting was corrected without changing schema SQL.
2. All four artifact statements succeeded, but the importer rejected the trailing ordinary block comment as incomplete SQL. Its EOF check was corrected to accept complete non-executable comments while still rejecting incomplete SQL, incomplete comments and unexecuted executable comments. The failed rehearsal journal was retained as `phase319-final-rehearsal-ddl.json`; the subsequent successful journal is `phase319-final-rehearsal2-ddl.json`.

The final disposable instance was shut down after the stop. PID, executable, port, resolved project datadir and absence of reparse points were verified before deleting only its owned temporary datadir. Protected server logs and all existing backups/certificates were retained.

## Tests and deferred validation

Actually performed in this task:

- Importer footer/warning checks: **7 cases / 0 failures**. Complete block/line/empty footers accepted; incomplete SQL, executable-comment residue and unterminated comments rejected; warning 1292 still captured without suppression.
- Exact-artifact disposable schema rehearsal: **4 statements / 0 warnings / 0 errors**.
- Normal migration diagnostics: **4 statements / 0 warnings / 0 errors**.
- Full read-only existing-data/schema/authority/state preservation checks: pass.
- New complete table definition and trigger bodies: match; exact new trigger creation metadata: **FAIL**, as recorded above.
- PHP syntax checks passed for the modified operator helper and all three new PHP helpers.
- `git diff --check` passed for tracked changes. The operator helpers are untracked; their relevant source and new-file content were inspected separately.

**Deferred after the mandatory stop:** Medicine provisional identity unit/MariaDB, governance unit/MariaDB, policy unit/MariaDB, foundation unit/MariaDB, pilot unit/MariaDB, candidate-selection/readiness, Treatment regression and authentication regression suites. The deployment verifier did run and failed the exact creation-context comparison. No complete regression or security-negative acceptance is claimed.

Unauthorized/forged authority, stale revisions, evidence mutation, immutable history, protected withdrawal/invalidation, self-approval and enrollment protections were not dynamically retested in this stopped run. Existing code and all existing database definitions remain unchanged; that preservation does not replace the required deferred security tests.

## Files changed and evidence retained

Modified existing operator tooling (already untracked before this task):

- `tests/medicine-owner-schema-deployment.php`: strict EOF comment handling only.

New files:

- `tests/medicine-phase319-final-deployment.php`: guarded backup/preflight/rehearsal/deployment/verifier; its current exact comparison remains BLOCKED.
- `tests/medicine-phase319-import-footer.php`: focused disposable importer checks.
- `tests/medicine-phase319-final-diagnostic.php`: read-only post-stop metadata/preservation diagnosis.
- `docs/MEDICINE_PHASE_3_19_CONTROLLED_DEPLOYMENT_FINAL.md`: this report.

New protected operational outputs under `storage/logs/medicine321a-owner-recovery/`:

- `phase319-final-before.sql`, `phase319-final-DERIVED.sql`.
- `phase319-final-backup.json`, `phase319-final-preflight.json`.
- `phase319-final-dump.stdout`, `phase319-final-dump.stderr`.
- `phase319-final-rehearsal-ddl.json`, `phase319-final-rehearsal2-ddl.json`, `phase319-final-expected.json`.
- `phase319-final-normal-ddl.json`, `phase319-final-footer-tests.json`, `phase319-final-blocked.json`.
- Archived disposable server logs for the fresh owned rehearsal instances; earlier protected logs/artifacts were preserved.

No application code, migration artifact or authoritative schema file changed. No SQL should be reapplied: the four normal migration statements have already executed and the guarded helper refuses an automatic retry.

## Final disposition

**DEPLOYED:** Phase 3.19 table and three triggers, schema only; acceptance remains BLOCKED.

**NOT CREATED:** provisional identities, canonical Medicine identities, chemical references, reviewer records, evidence appraisals, approvals or pilot enrollment.

**NOT ACTIVATED:** Medicine pilot or public Medicine. Treatment remains HOLD.

**NOT DEPLOYED:** Phase 3.22.

**Remaining blocker:** resolve the recorded rehearsal-versus-normal trigger creation character-set metadata mismatch under a separately reviewed continuation, then complete the deferred regression/security validation. No schema repair, acceptance weakening, retry or restoration was performed. Work stopped after documenting the result.

## Later metadata investigation (2026-10-11)

The historical metadata stop above was subsequently resolved by exact-context isolated reproduction and a rehearsal/verifier tooling correction, without normal corrective DDL. Full acceptance remains BLOCKED by legacy regression inventory assertions and an inapplicable readiness audit. See [trigger metadata fidelity report](MEDICINE_PHASE_3_19_TRIGGER_METADATA_FIDELITY.md) for evidence and final disposition. The historical failed journals and original rehearsal reference remain unchanged.
