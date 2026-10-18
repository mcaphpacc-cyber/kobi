# Medicine Phase 3.19 — Warning 1906 generated-column recovery correction

Final recovery status: **PASS**. Two independent MariaDB 10.4.27 restores using the same unchanged source checkpoint and a deterministic, labelled DERIVED import copy produced **zero warnings**, exact data/schema/generated-column/trigger fidelity, unchanged privilege context, and matching final SQL/session state. **Phase 3.19 and Phase 3.22 remain undeployed.** This report supersedes the warning-related recovery blocker only; it does not authorize deployment.

## Source identity and investigation boundary

The source path was located in the existing recovery diagnostic report, not inferred. This is the unchanged original checkpoint from the blocked fresh-backup restore:

- Path: `D:\xampp\htdocs\kobi\storage\logs\medicine321a-owner-recovery\phase319-before.sql`
- Size: **5,353,453 bytes**
- SHA-256: **f4587999dc9e6f3f7dcd50bca9517bbce70ba40217f244434e7891ec4d52b984**

The file matched its documented identity and retained manifest before investigation, derivation, each restore and final verification. It was never edited, overwritten, normalized, filtered in place or regenerated. Both corrected restores were independently reproduced from this exact file through the same correction.

The older protected original backup remains unchanged too:

- Path: `D:\xampp\htdocs\kobi\storage\logs\medicine321a-owner-recovery\before.sql`
- Size: **5,352,130 bytes**
- SHA-256: **f303f482ba49931db370a33b833bf95e5106e3c20de21d47ce3bfc4c78108cf2**

Its existing independent certificates were checked and preserved. It predates the owner grant; substituting it would not investigate Warning 1906 in the current checkpoint.

The Phase 3.10 lifecycle, Phase 3.12 policy and Phase 3.19 model reports, current structure, existing owner correction and recovery tooling were inspected. Authority/lifecycle semantics and migrations were unchanged. Normal KOBI was accessed with SELECT/SHOW only. Every state-changing test/import ran in guarded disposable databases.

## Exact active_slot definition and governance meaning

Normal SHOW CREATE TABLE contains:

```sql
`active_slot` tinyint(4) GENERATED ALWAYS AS
  (case when `revoked_at` is null then 1 else NULL end) STORED,
UNIQUE KEY `uq_medicine_active_grant`
  (`user_id`,`capability`,`scope`,`active_slot`)
```

Normal information_schema.COLUMNS records:

| Attribute | Actual value |
| --- | --- |
| Ordinal position | 15 |
| COLUMN_TYPE | tinyint(4) |
| IS_NULLABLE | YES |
| COLUMN_DEFAULT | NULL |
| EXTRA | STORED GENERATED |
| GENERATION_EXPRESSION | case when `revoked_at` is null then 1 else NULL end |

This is a stored/persistent GENERATED ALWAYS column, derived from **revoked_at**, not an ordinary insertable authority field and not a virtual column. Non-revoked records generate 1; revoked records generate NULL. The unique key retains the existing one-current-slot enforcement per user/capability/scope, while historical revoked grants remain representable. Neither the expression nor this unique key was changed.

All relevant CHECKs remain exactly as in the live baseline: grant capability/scope vocabulary, active/expiry including the non-expiring designated-owner exception, qualification tuple, reviewer qualification and complete revocation tuple. All existing indexes/FKs/defaults remain exact. Complete SHOW CREATE and column metadata are retained in `phase319-generated-preflight.json` and both restore proofs.

## Actual backup representation and root cause

The source CREATE TABLE retains the generated definition correctly. There is exactly **one** INSERT targeting medicine_authority_grants, with no column list:

```sql
INSERT INTO `medicine_authority_grants` VALUES (1,1,'administrator','catalog',1,NULL,NULL,NULL,1,'2026-10-10 19:30:25.594320',NULL,NULL,NULL,NULL,1);
```

Its 15 positional values follow the authoritative ordinal metadata. The final supplied **1** corresponds to generated `active_slot`; all preceding 14 ordinary values are the existing owner grant. Thus the generated column is not explicitly named in a column list, but is explicitly supplied positionally. The existing audit event is in a separate unchanged INSERT.

The backup header and protected operator helper establish its producer: **mysqldump 10.19, Distrib 10.4.27-MariaDB, Win64 (AMD64)**. The helper calls that executable with single-transaction/no-lock/no-DROP-TABLE, routines/events/triggers and hex-blob options. It does not build INSERT columns or export SELECT * itself. The unchanged dump already contains the generated value, before the restore parser sees it.

A disposable two-generated-column fixture was exported with the same installed executable and positional/hex-blob/no-DROP options. The resulting unmodified dump again included the stored and virtual generated values in positional INSERTs. The retained `phase319-generated-export-fixture.sql` proves this producer behavior independently of the real data. The installed help exposes complete-insert but no generated-column exclusion option; no unsupported option or producer replacement was introduced.

Previous raw restores and a new uncorrected fixture explicitly exposed MariaDB diagnostics. The real raw restore warning was:

```text
Level: Warning
Code: 1906
The value specified for generated column 'active_slot' in table 'medicine_authority_grants' has been ignored
```

It arose at statement **315**, source ending line **1475**, during the grant-row INSERT, not CREATE TABLE, trigger creation, privileges or SQL-mode restoration. The fixture produced four captured 1906 conditions for two rows with two generated columns. Warning handling is intact.

MariaDB is correctly refusing the supplied generated value and deriving it from the declared expression. An explicit generated value is not authoritative input. The old dump's generated value happened to match the expression, explaining previous full-row matches, but that did not satisfy the warning-free policy. This correction prevents submitting that value; it does not accept or suppress the warning.

## Correction: schema-aware DERIVED import only

The authoritative checkpoint cannot be regenerated or replaced under this authorization. Therefore the approved second correction option was used: a deterministic transformation of an explicitly labelled separate copy.

- Path: `D:\xampp\htdocs\kobi\storage\logs\medicine321a-owner-recovery\phase319-generated-DERIVED.sql`
- Size: **5,353,662 bytes**
- SHA-256: **f063d88a4e1308abeb9ba024dcd1802335a9ebcfd3ec41601c35efa62f330bff**

The copy is labelled **DERIVED — NEVER REPLACE AUTHORITATIVE BACKUP** in its protected manifest and filename. Its manifest records the original path/size/hash, derived path/size/hash, authoritative schema metadata and each changed statement's source/derived offsets, lengths and hashes. Private backup content remains protected by the existing `Require all denied` directory.

Generated columns are identified from the actual **information_schema.COLUMNS** EXTRA and GENERATION_EXPRESSION, in ordinal order, with the complete live SHOW CREATE definition. STORED and VIRTUAL generated columns are supported. Normal baseline validation ensures these are the authoritative definitions. Classification is cross-checked against CREATE attributes; unknown/duplicate columns, missing ordinary metadata, forged classification, changed CREATE definitions, malformed tuple counts and unsupported value expressions are rejected. The transformer has no configurable arbitrary list of columns to omit.

For positional INSERTs, it constructs the explicit ordinary-column list from metadata and removes only values at proven generated-column positions. For explicit/reordered column lists it maps names, not assumed last positions. Multiple rows and multiple generated columns are supported. Every retained ordinary SQL token remains byte-identical, including NULL, numeric spelling and quoted/escaped strings. It does not decode/re-encode user data.

For this exact checkpoint there was **one edit only**, statement 315, one row, omission of `active_slot` only. The corrected INSERT is:

```sql
INSERT INTO `medicine_authority_grants` (`id`,`user_id`,`capability`,`scope`,`is_active`,`qualification_reference`,`qualification_assessed_by`,`qualification_scope`,`granted_by`,`granted_at`,`expires_at`,`revoked_by`,`revoked_at`,`revocation_reason`) VALUES (1,1,'administrator','catalog',1,NULL,NULL,NULL,1,'2026-10-10 19:30:25.594320',NULL,NULL,NULL,NULL);
```

Trace: source offset **4,501,812**, source bytes **150** including terminator/CRLF; derived offset **4,501,812**, derived bytes **359**. Source/derived statement-region hashes, including terminator/line ending:

- Source: `8149c27fb8929739307b4692b488bb0caca1a0e6c6f00ea21c6d764629b9f3a6`
- Derived: `b8a8e1fc6eedbb73a4e576fcc5fb145ccb61bd4b19c623ab839a5fa1992395af`

These region hashes include different surrounding bytes than the earlier PDO-executed statement hash; the manifest defines their exact offsets/lengths. Retained ordinary-token and omitted-token hashes provide additional traceability.

Every source byte **outside that INSERT region** is checked equal against its corresponding derived span. CREATE TABLE/generated expression, unique/CHECK/FK definitions, trigger bodies/CRLF, delimiter lines, dump ordering, all other data INSERTs, executable SQL-mode directives and view/routine/event/privilege content are untouched. Table-definition comparison tolerates SHOW CREATE's CRLF/LF presentation difference solely for validation; no table/source/derived SQL bytes are normalized. Repeated derivation from the same source/metadata produces identical SQL and edit metadata; each restore regenerates and checks that exact output before execution.

The correction introduces no INSERT IGNORE, warning filter, SQL-mode workaround, changed constraints or special active_slot schema rule. Warning capture and zero-warning acceptance are unchanged. The old raw-import recovery path continues to reject the raw warning; the certified corrected process is the generated-recovery driver and manifest described here. No old deployment certificate or deployment readiness flag was manufactured.

## First corrected restore

Independent server instance **da760aa6-0e8f-4de0-befe-7f9dd8022da3**; fresh project-local MariaDB 10.4.27 datadir, port **3342**, guarded target **kobi_phase319_generated_recovery**.

- Start/end: **2026-10-10 20:24:25–20:24:37 UTC**.
- Executed: **501 statements**, all **26 original dump session mode save/set/restore directives**, zero omissions.
- Warning/Note conditions: **0**, captured immediately for every statement and checked against SHOW COUNT(*) WARNINGS.
- SQL errors: **0**; shell exit **0**; recovery status **PASS**.
- All **54 tables / 26,209 rows**: complete sorted row digests and counts equal the original fresh baseline, including generated values and all ordinary data.
- Complete schema: columns, defaults/nullability, indexes, unique/FK/CHECK constraints, generated definitions, engines/collations, AUTO_INCREMENT metadata, views/routines/events equal baseline.
- All **eight triggers**: names, timing/event/table, exact bodies and line-ending bytes, definer, SQL mode and SHOW CREATE character context equal baseline. Regenerated creation timestamps are excluded from dump-stable comparison.
- Operator privilege digest, database defaults and initial/final SQL/character state match.
- Actual generated metadata exactly matches normal metadata. Explicit read-only comparison confirms owner grant id 1 has **active_slot = 1** in both normal and restored databases.

Evidence: `phase319-generated-restore1.json` (all 501 immediate diagnostic entries, actual metadata/digests and checks) and `phase319-generated-values1.json` (explicit generated results/instance).

The first server was stopped after process/datadir ownership checks, log archived and datadir removed. The second did not reuse it.

## Second independent corrected restore

Independent fresh instance **ee514170-c668-4173-8eac-b192e810006b**, new server/datadir initialization, same port/target/version guards.

- Start/end: **2026-10-10 20:25:58–20:26:13 UTC**.
- Same original source identity and independently re-derived byte-identical corrected stream.
- **501 statements / 26 mode directives / zero omissions / zero warnings / zero SQL errors**; exit **0**; recovery status **PASS**.
- Complete data/schema/generated metadata, exact eight triggers/contexts, privileges/defaults and SQL/session state match baseline and first restore.
- Explicit normal/first/second generated-value comparisons match: grant id 1, **active_slot = 1**.
- Independent proof compares source/derived identity, distinct instances, actual objects/data/state, generated metadata, empty warning lists and every mode transition; all true.

Evidence: `phase319-generated-restore2.json` and `phase319-generated-values2.json`. Server/datadir were removed after verified shutdown; retained logs/proofs and both backups were preserved.

Server version: **10.4.27-MariaDB**; PHP **8.2.0**; PDO client **mysqlnd 8.2.0**. Initial/final global/session mode remains **NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION**. Client/connection/results/server charset utf8mb4, connection/server/database-default collation utf8mb4_general_ci. Temporary session transitions were only the unchanged dump's existing save/set/restore procedure; no global, normal or persistent application SQL-mode change occurred.

This is database-specific logical recovery. The dump does not recreate server account credentials; operator SHOW GRANTS context/digest is compared exactly. Trigger creation timestamps are not reproducible dump properties. These established limitations do not affect the demonstrated database/trigger/constraint fidelity.

## Regression and security tests

| Suite | Cases | Assertions | Failures |
| --- | ---: | ---: | ---: |
| Generated-column correction/security fixtures | 21 | 28 | 0 |
| Existing immediate warning-capture regression | 10 | 24 | 0 |

Two complete recovery operations are reported separately, not inflated into unit case/assertion counts.

Generated fixtures test stored and virtual detection, unchanged generated CREATE definitions, omission of columns and corresponding values, ordinary bytes, multiple generated positions/rows, NULL, escaped/doubled strings, decimal/negative numbers, order, exact triggers/mode directives, unchanged source identity, deterministic repeat, source/derived traceability, explicit reordered lists and unchanged ordinary-only lists. Integrity cases reject forged generated flags, missing ordinary metadata, changed CREATE/expression/constraints, malformed value counts, duplicate/unknown columns and unsupported SQL expressions. Uncorrected insertion still produces exact 1906 diagnostics; corrected insertion yields zero warnings and expected regenerated values. A fresh mysqldump fixture demonstrates the producer defect.

An initial test construction over-escaped a synthetic quote and was rejected as an unterminated token before any backup restore. The fixture was corrected, its disposable environment removed, and the final 21/28 result is reported without counting that failed development attempt as a pass. No real source or derived SQL was changed to make a test succeed.

All state-changing fixtures used separate disposable fixture databases, which were dropped. Real restored owner/audit data was not used for synthetic actor/grant tests. Transformation does not edit grant/audit values, generate events, change expressions, modify triggers or bypass constraints. Full non-generated AND generated row digests and byte-span equality provide additional real-backup proof.

PHP syntax and focused whitespace/source checks passed for the two new PHP files; `git diff --check` passed. No application-code change or unrelated test/implementation was made. No synthetic normal-database test was performed.

## Normal KOBI preservation and deployment boundary

Preflight, derivation, each corrected restore and final read-only verification matched the retained normal **medical1/3306** fresh baseline. All 54 tables, full row digests/counts, complete schema, eight exact triggers/contexts, operator privileges, defaults and SQL/character state remain unchanged.

Account 1 remains active and is the **sole administrator/catalog grant**, expiry **NULL**, with its same single owner establishment event and snapshot hash. Contributor/editor/reviewer/steward/deployment/delegated administrator authority remains zero. No authority/audit write or new normal login/bootstrap occurred.

The five active candidates (15 Acetazolamide, 54 Allopurinol, 319 Carbamazepine, 481 Colchicine, 2064 Topiramate) remain CANDIDATE / UNKNOWN, unapproved and unenrolled. Canonical chemical references, identities and pilot records remain zero. The provisional and Phase 3.22 reviewer/evidence tables remain absent. No provisional, real reviewer, appraisal, approval or pilot record was created in normal KOBI. Medicine pilot/public exposure remains OFF; Treatment remains HOLD and all Treatment data unchanged.

Normal DDL **0**; normal data/authority/audit writes **0**. Existing original/fresh backup and migration/structure hashes remain unchanged. No commit, push, reset, checkout, merge, rebase, amend or other Git mutation occurred.

## Files created and retained artifacts

Created source/report files:

- `tests/medicine-generated-recovery-library.php` — pure schema-aware, byte-traceable derived INSERT transformation.
- `tests/medicine-phase319-generated-recovery.php` — guarded read-only normal checks, derived provenance, fixture tests and corrected isolated restores; no deployment mode.
- `docs/MEDICINE_PHASE_3_19_RECOVERY_WARNING_1906.md` — this report.

No previously existing application/schema/configuration/migration/test source file was modified in this operation.

Protected artifacts: `phase319-generated-{preflight,manifest,tests,restore1,restore2,values1,values2,finalcheck}.json`, `phase319-generated-DERIVED.sql`, `phase319-generated-export-fixture.sql`, exporter stderr, and archived disposable logs. Existing warning-regression result/fixture JSON was refreshed by its passing rerun. Original backup files and historical recovery/deployment reports were preserved.

## Scope limits and final status

The transformation intentionally supports the established trusted mysqldump INSERT VALUES format with backtick identifiers and literal SQL tokens; it is not a general SQL rewrite engine. It fails on malformed/unsupported generated-table INSERT forms or mismatching CREATE/metadata. Source identity and normal baseline are pinned before use. Future changed schema, source dump dialect or SQL-mode/escaping conventions require their own verification; no public/request-supplied transformation interface exists.

Recovery for this exact authoritative checkpoint is **PASS — ZERO WARNINGS**, independently repeatable. Authority semantics, constraints, source backup and recovery acceptance were preserved. No Phase 3.19 or Phase 3.22 migration was applied. Stop after this recovery report; any deployment remains a separate authorized operation.
