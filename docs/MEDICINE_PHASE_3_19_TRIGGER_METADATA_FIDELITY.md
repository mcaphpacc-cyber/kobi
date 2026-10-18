# Phase 3.19 trigger metadata fidelity

**PHASE 3.19 TRIGGER METADATA — BLOCKED** for full task acceptance. The original trigger metadata blocker is resolved and certified without normal database DDL. Two unchanged legacy MariaDB regression suites fail stale inventory assertions, and the legacy readiness audit rejects the current persisted schema. These remaining validation blockers are explained below; no tests were changed to make them pass.

Investigation date: **2026-10-11 Asia/Calcutta**. Final normal read-only verification: **2026-10-10 21:05:14.204328 UTC / 2026-10-11 02:35:14.204328 Asia/Calcutta**.

## Initial state and authoritative materials

Normal KOBI was already at **55 tables / 11 triggers**, after the unchanged Phase 3.19 artifact executed four statements with zero warnings/errors. Acceptance had stopped at six trigger creation-context field differences. No automatic rollback or restore was performed.

Inspected materials: `database/medicine-phase319-provisional.sql`, `database/structure.sql`, the Phase 3.19 provisional identity model, final deployment report, Warning 1906 certified recovery report, application PDO connection implementation, deployment/rehearsal tooling, immediate execution journals and existing regression suites.

Migration SHA-256 remains:

`84166a59636a8eefcf580bc8bc71b09c19951255a865d2b8a8415ba62a41be48`

The authoritative migration, structure, application, configuration, authentication, services, repositories, controllers and policies were not modified. Both the original failed rehearsal reference and its execution journal remain retained.

## Actual normal trigger inventory

Target: configured host `localhost`, server `LAPTOP-PTFFHOMV`, database `medical1`, port `3306`, **MariaDB 10.4.27**.

| Trigger | Origin | Timing / event | Target table |
| --- | --- | --- | --- |
| `medicine_events_no_delete` | Existing | BEFORE DELETE | `medicine_governance_events` |
| `medicine_events_no_update` | Existing | BEFORE UPDATE | `medicine_governance_events` |
| `medicine_form_activity_history` | Existing | AFTER UPDATE | `kobi_substance_forms` |
| `medicine_lifecycle_no_delete` | Existing | BEFORE DELETE | `medicine_registry_lifecycle_events` |
| `medicine_lifecycle_no_update` | Existing | BEFORE UPDATE | `medicine_registry_lifecycle_events` |
| `medicine_pilot_no_delete` | Existing | BEFORE DELETE | `medicine_pilot_records` |
| `medicine_pilot_no_update` | Existing | BEFORE UPDATE | `medicine_pilot_records` |
| `medicine_substance_activity_history` | Existing | AFTER UPDATE | `kobi_substances` |
| `provisional_identity_before_insert` | Phase 3.19 | BEFORE INSERT | `medicine_provisional_identity_revisions` |
| `provisional_identity_no_delete` | Phase 3.19 | BEFORE DELETE | `medicine_provisional_identity_revisions` |
| `provisional_identity_no_update` | Phase 3.19 | BEFORE UPDATE | `medicine_provisional_identity_revisions` |

**All 11** have `CHARACTER_SET_CLIENT=utf8mb4`, `COLLATION_CONNECTION=utf8mb4_general_ci`, `DATABASE_COLLATION=utf8mb4_general_ci`, `DEFINER=root@localhost` and `ACTION_ORDER=1`. SQL mode is the approved unchanged `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`. Normal KOBI has a consistent observed creation convention, not mixed metadata.

Captured every row of `information_schema.TRIGGERS` and every `SHOW CREATE TRIGGER`, including exact `ACTION_STATEMENT`, orientation, condition, ordering, definer, SQL mode and character-set/collation fields. MariaDB 10.4.27 exposes **`ACTION_ORDER`**, not `ACTION_ORDERING`; the actual available columns were captured rather than querying a nonexistent field. `ACTION_ORIENTATION` is `ROW`; `ACTION_CONDITION` is NULL. Creation timestamps are retained in the information-schema inventory but excluded from equality between independent creations. Database schema names necessarily differ between isolated and normal databases and are explicitly normalized only for that cross-environment comparison.

Protected full inventory: `storage/logs/medicine321a-owner-recovery/phase319-metadata-normal.json`. All eight existing triggers are byte-for-byte unchanged against the original pre-deployment reference through the established complete verifier.

## Reproduction and exact cause

The old disposable instance had been removed. A fresh guarded **3342** instance recreated two empty isolated databases using the exact authoritative schema prefix and then the exact unchanged Phase 3.19 migration:

- `kobi_phase319_metadata_utf8`: exact original rehearsal creation context.
- `kobi_phase319_metadata_utf8mb4`: same schema, explicitly restored normal deployment creation context before the four migration statements.

Both arms use MariaDB 10.4.27, database defaults `utf8mb4` / `utf8mb4_general_ci`, and the unchanged approved SQL mode. Each base import executes **135** statements; each Phase 3.19 import executes **4** statements with **zero warnings/errors**.

The schema prefix's two SQL-mode directives were omitted exactly as in the approved prior rehearsal, preserving the existing mode:

```sql
/*!40101 SET SQL_MODE=''*/;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
```

The later SQL-mode restore statement is outside this prefix. No other prefix statement was omitted. In particular, the prefix executes:

```sql
/*!40101 SET NAMES utf8 */;
```

| Creation state | Original reproduced rehearsal | Normal deployment / corrected rehearsal |
| --- | --- | --- |
| `character_set_client` | `utf8` | `utf8mb4` |
| `character_set_connection` | `utf8` | `utf8mb4` |
| `character_set_results` | `utf8` | `utf8mb4` |
| `collation_connection` | `utf8_general_ci` | `utf8mb4_general_ci` |
| `character_set_database` | `utf8mb4` | `utf8mb4` |
| `collation_database` | `utf8mb4_general_ci` | `utf8mb4_general_ci` |
| Server charset / collation | `utf8mb4` / `utf8mb4_general_ci` | Same |
| Global and session SQL mode | Approved unchanged mode | Same |

The normal migration execution journal captured the right-hand session state before execution. `app/Core/Database.php` constructs a PDO DSN with `charset`, defaulting to `utf8mb4`; the actual observed state confirms this setting. The wrapper executed the migration directly on that PDO connection and did not import the schema prefix or issue `SET NAMES`/`SET CHARACTER SET` against normal KOBI.

The migration itself contains **no** SET NAMES, SET CHARACTER SET, client charset, connection collation, database-default or SQL-mode directives. Its table default is explicit and separate from the trigger creation context. Exact attempted migration statements in the original normal journal, original successful rehearsal and new reproduction use the same artifact bytes. Reproducing the utf8 arm matches the original rehearsal reference; reproducing the utf8mb4 arm matches the actual normal table and triggers exactly. The bodies and SQL text did not cause the difference.

Exactly **two fields per new trigger** differed in the old SHOW CREATE comparison: `character_set_client` and `collation_connection`. All other compared fields, including SQL mode, database collation and original CREATE statement text, matched. The same two differences appear in `information_schema.TRIGGERS`.

**Root cause:** the rehearsal schema-prefix wrapper changed its session to utf8 and failed to restore the normal PDO creation context before executing Phase 3.19. Normal KOBI followed its existing utf8mb4 convention. This is not a differing migration, database default, trigger body, normal SQL-mode change, or unsupported MariaDB DDL.

Evidence: `phase319-metadata-rehearsals.json` retains complete before/creation/after state, both metadata representations, exact SQL and immediate diagnostics for both arms.

## Semantic analysis

Creation metadata is not universally cosmetic. MariaDB retains stored-program literal character sets/collations from creation time; changing these can affect expressions. Identical source text alone would therefore be insufficient. This documented behavior informed the actual expression analysis and isolated differential tests. [MariaDB character sets and stored programs](https://mariadb.com/docs/server/reference/data-types/string-data-types/character-sets/setting-character-sets-and-collations), [SHOW CREATE TRIGGER](https://mariadb.com/docs/server/reference/sql-statements/administrative-sql-statements/show/show-create-trigger).

For this **specific unchanged artifact and actual schema**, the analyzed paths are equivalent:

1. The entire migration is ASCII. All string literals, SQLSTATEs, SIGNAL messages, JSON paths, lifecycle/capability/scope tokens, package digest, tier values and target prefixes are ASCII. Their byte representations are the same in utf8 and utf8mb4.
2. Local `prior_state`, `source_path` and `facts_path` declarations inherit the same database default in both arms. Paths contain only fixed ASCII plus loop indices 0/1. Numeric casts and revision/ID concatenations produce ASCII decimal tokens.
3. Stored JSON columns explicitly use utf8mb4 with binary collation; hashes are binary(32). Creation metadata does not change their column storage, values or checksum bytes. `BINARY` comparisons enforce exact bytes for lifecycle, scope/capability, domain/target/status and source binding expressions. Both equality and rejection were tested with multibyte and supplementary characters.
4. Nonbinary comparisons are numeric/time tests, JSON type names such as `NULL`/`ARRAY`, or the existing `users.status='active'` column comparison. JSON type names are fixed ASCII. The unchanged column type/collation controls the status comparison, with the same ASCII literal and compatible coercion. No locale-sensitive sorting, Unicode case-folding of user text, or literal containing a supplementary character occurs in these triggers.
5. `JSON_COMPACT` source-binding equality operates on stored JSON and compares binary results. Unicode in valid source content is retained; different source bytes are rejected in both arms. Hash CHECKs and binary hashes are table constraints, unchanged between creations.
6. These triggers do not create governance audit events, generate identifiers, assign authority, or modify existing registry/private data. The insert trigger validates/locks/reads, while update/delete triggers always signal the same ASCII immutable-history error. Table PK, indexes, FKs and checks remain the same; there is no generated column in the provisional table.

Paired executions on identical isolated schemas used runtime utf8mb4 connections after trigger creation. **13 cases per arm** produced identical accepted values or exact rejected MariaDB code/message:

- Valid service draft with `café`, Hindi and a supplementary-plane test-tube emoji in registry description/rationale; Unicode stored without truncation.
- Equal Unicode source binding accepted.
- Unequal Unicode source binding rejected.
- Supplementary character in target rejected.
- Supplementary character in domain rejected.
- Full-width approval status rejected.
- Wrong domain case rejected.
- Wrong hash rejected.
- Skipped revision rejected.
- Forged authority rejected.
- UPDATE rejected.
- DELETE rejected.
- Restoration after withdrawal rejected.

The accepted draft hashes were checked by the existing schema/service logic; per-arm snapshot timestamp facts were excluded from cross-arm outcome equality. The differing creation metadata was retained throughout the tests. No warning accompanied an accepted operation. Expected rejection errors were captured and compared rather than suppressed. All fixture users/grants/revisions were synthetic and existed only in the disposable databases.

Result: **SEMANTICS_CONFIRMED**, `phase319-metadata-semantics.json`. This conclusion is limited to the exact current artifact/schema; future changed literals, expressions, defaults or column collations require fresh validation. It does not assert that utf8 and utf8mb4 are universally interchangeable.

## Smallest correction and certification

**Normal corrective DDL is unnecessary. No trigger was dropped, recreated or altered.** There was no interval with absent triggers, no need for a maintenance window, and no definer/privilege change. No fresh pre-correction backup was needed because no corrective normal DDL occurred. Existing protected backups and the certified DERIVED recovery procedure remain retained unchanged; no restore was performed or redesigned.

The minimal change is in **operator rehearsal/verifier tooling**, not application code:

```sql
SET NAMES utf8mb4 COLLATE utf8mb4_general_ci;
```

This executes only on the isolated rehearsal connection after the authoritative base import and before the unchanged migration. The helper immediately checks for warnings and requires the complete creation state to equal the normal preflight reference. The corrected wrapper was actually exercised on a third fresh empty schema within the disposable instance: **four exact migration statements, zero warnings/errors, empty new table**.

The verifier reads a separate, immutable, hash-bound `phase319-metadata-certified.json`, built only after the causal reproduction and semantics tests passed. It retains full SHOW CREATE comparisons, including charset, collation, SQL mode, definer and body, and also compares complete normalized information-schema rows including ACTION_ORDER/orientation/condition. It does not ignore the differing fields, rewrite the old reference, or accept an unexplained discrepancy. Only independent creation timestamps and necessarily different isolated schema names are excluded from the cross-environment identity comparison.

The observed normal convention, unchanged PDO implementation, direct execution evidence and exact isolated reproduction establish the authoritative intended **utf8mb4 / utf8mb4_general_ci** context. No migration change or normal session/global SQL-mode change was required.

The Phase 3.19 deployment verifier now reports **SCHEMA_DEPLOYMENT_VERIFIED**, including every certified trigger field. This certifies the metadata portion, not full regression acceptance.

## Post-investigation integrity

Final read-only checks confirm **55 intact tables / 11 triggers**, all three Phase 3.19 bodies/definitions and creation metadata exact, and all eight pre-existing triggers unchanged. Complete pre-deployment table row counts/digests, definitions, FKs/CHECKs/indexes, routines/events, defaults, privileges and session state match the protected pre-deployment reference after removing only the already-authorized new empty table and three triggers. The original fresh backup's size/hash remains verified.

| Existing table | Unchanged rows |
| --- | ---: |
| `kobi_substances` | 2,224 |
| `health_medicines` | 3 |
| `kobi_combinations` | 2,327 |
| `kobi_combination_items` | 6,671 |
| `treatment_protocols` | 2,150 |
| `treatment_protocol_items` | 3,190 |
| `diseases` | 400 |

All existing 26,209 rows retain their digests. `medicine_provisional_identity_revisions` remains empty. No canonical Medicine identities, chemical references, reviewer/evidence records, approvals or enrollment were created in normal KOBI.

Account 1 remains the sole active Medicine Administrator, NULL expiry, exactly one unchanged owner-establishment event, no other authority. Medicine pilot OFF, public Medicine OFF, Treatment HOLD. Registry, personal medications, Treatment data/schema/code/exposure and application hashes are unchanged. No Phase 3.22 objects were deployed to normal KOBI.

## Deferred regressions now executed

All existing tests were run unchanged after metadata certification. Outputs/stderr are retained under `storage/logs/medicine321a-owner-recovery/metadata-regression-*`.

| Suite | Cases / assertions or checks | Result |
| --- | --- | --- |
| Provisional identity unit | 43 / 240 | PASS |
| Provisional identity MariaDB | 15 / 65 | PASS |
| Governance unit | 56 / 249 | PASS |
| Governance MariaDB | 38 / 141 | PASS |
| Policy unit | 33 / 92 | PASS |
| Policy MariaDB | 11 / 43 | **1 metadata-inventory failure** |
| Foundation unit | 105 / 216 | PASS |
| Foundation MariaDB | 29 / 96 | PASS |
| Pilot unit | 40 / 143 | PASS |
| Pilot MariaDB | 15 / 94 | **1 metadata-inventory failure** |
| Candidate selection | 55 checks | PASS after documented SELECT-only snapshot generation |
| Evidence readiness | 11 / 134 | PASS |
| Legacy pilot readiness | Audit prerequisite rejected | **Unavailable for current schema** |
| Authentication | 45 scenarios | PASS |
| Phase 3.19 deployment verifier | Complete certified schema/data/state checks | PASS |

Treatment suites passed: prerequisites (29 risk / 7 lifecycle / 11 comparison focused checks), governance 91, editorial 73, review 130, publication 105, publication correction 17 new, creation 110 focused, operations 127 focused, and administration hardening 39 focused. Embedded earlier checks also ran; these figures are not combined into a misleading unique total.

MariaDB suites used separate guarded disposable ports **3325**, **3328**, **3339**. Each full-schema import executes **148** schema statements and explicitly omits only the three legacy SQL-mode-changing directives. The full authoritative structure includes Phase 3.22 objects **inside those disposable regression databases only**, which explains the old inventory expectations below. No synthetic regression writes targeted normal KOBI. All owned disposable processes/datadirs were safely stopped/removed after retaining logs.

### Remaining acceptance blockers, not repaired

1. Policy MariaDB `pilot actual metadata FKs checks indexes triggers` expects `medicine_%` trigger count **8**, but its current full structure import contains **12** (the four Phase 3.22 immutable qualification/appraisal triggers are included). Its named pilot table constraint/index comparisons match; the count assertion fails.
2. Pilot MariaDB `fresh schema all Medicine constraints indexes FKs and immutable triggers` expects **13** matching Medicine tables, but the source now has **15** (Phase 3.22 qualifications/appraisals). It fails at that early count. Its later expected Medicine trigger count **8** would also be **12**. Independent read-only diagnostic comparisons of all 15 actual imported tables show required constraint/index names match the current artifact in both failing disposable databases, including legitimate implicit FK indexes. These findings explain the failures; they do not convert failed suites into passing suites.
3. `medicine-pilot-readiness.php audit` explicitly rejects persisted Medicine schema; its Phase 3.15 fixture was not retained. Normal KOBI now legitimately contains persisted governance. The unchanged audit stops with `Medicine deployment differs from Phase 3.14; inspect persisted scoped revisions before classifying readiness.` The offline readiness test was not run with a fabricated schema-absence snapshot.

Candidate-selection initially failed because its documented temporary snapshot was absent. The existing guarded `snapshot` mode was then used SELECT-only on normal KOBI; the unchanged test passed all 55 checks. The failed first transcript remains retained. No test code was modified.

The remaining failed/inapplicable inventory/readiness expectations need separately scoped regression-fixture maintenance before an all-regressions PASS can be claimed. They do not establish a trigger semantic defect, and none justifies changing normal triggers, application code, authority or data in this task.

Security regressions that did execute successfully cover forged/unauthorized authority, stale revisions, wrong evidence/hash binding, immutable history, terminal withdrawal/invalidation, protected canonical recovery, self-approval rejection, pilot gates and public denial. Every policy/pilot case other than the named inventory case reports PASS; overall acceptance remains blocked because the required suites are not all green and legacy readiness is unavailable.

## Files and retained evidence

Modified:

- `tests/medicine-phase319-final-deployment.php`: isolated deterministic creation-context guard and exact certified metadata reference/comparison; no normal DDL retry or acceptance weakening.
- `docs/MEDICINE_PHASE_3_19_CONTROLLED_DEPLOYMENT_FINAL.md`: additive historical follow-up note only.

Created:

- `tests/medicine-phase319-metadata-fidelity.php`: normal read-only inventory, isolated causal reproduction/semantic probes and certification.
- `tests/medicine-phase319-regression-metadata.php`: guarded read-only diagnosis of disposable stale inventory assertions.
- `tests/medicine-phase319-foundation-environment.ps1`, `tests/medicine-phase319-governance-environment.ps1`: existing guarded hidden-process environment procedure adapted to required disposable regression ports/datadirs.
- `docs/MEDICINE_PHASE_3_19_TRIGGER_METADATA_FIDELITY.md`: this report.

Protected evidence created: `phase319-metadata-normal.json`, `phase319-metadata-rehearsals.json`, `phase319-metadata-semantics.json`, `phase319-metadata-certified.json`, `phase319-metadata-regression-diagnostic.json`, `phase319-metadata-final-verifier.out/.stderr`, `phase319-deterministic-rehearsal-ddl.json`, `phase319-deterministic-expected.json`, regression transcripts/stderr, candidate snapshot/diagnostic output, readiness audit output/stderr and owned server-log archives. `phase319-final-post.json` now records successful metadata/schema verification; historic blocked journals were retained.

PHP lint passed for all new/modified PHP tooling. Tracked `git diff --check` passed; new/untracked helper sources were inspected separately. No existing regression test, migration, schema, application/configuration file or Git state was modified. The temporary candidate fixture was removed after preserving its output in protected storage.

## Final disposition

**Metadata certification: resolved**, authoritative/deterministic utf8mb4 creation context and confirmed correct current trigger behavior. **Normal corrective DDL: 0. Normal data/authority writes: 0. Restorations: 0.**

**Full task status: PHASE 3.19 TRIGGER METADATA — BLOCKED**, because the required unchanged regression set is not completely passing/applicable. No normal trigger repair is needed. No unrelated tests/schema were changed to force acceptance. No Medicine activation, governed record creation, Phase 3.22 deployment, Treatment change or Git mutation occurred. Work ends at this report.

## Exact migration CREATE TRIGGER statements

The following appendix is copied verbatim from the unchanged authoritative artifact, including its client delimiter directives. The execution evidence retains the exact submitted SQL separately.

```sql
DELIMITER $$
CREATE TRIGGER `provisional_identity_before_insert` BEFORE INSERT ON `medicine_provisional_identity_revisions` FOR EACH ROW
BEGIN
 DECLARE locked_substance bigint unsigned;
 DECLARE prior_revision int unsigned DEFAULT 0;
 DECLARE prior_state varchar(16);
 DECLARE valid_grant int DEFAULT 0;
 DECLARE source_index int DEFAULT 0;
 DECLARE source_path varchar(16);
 DECLARE facts_path varchar(32);
 /* Serialize even first insertion on the parent registry row. */
 SELECT substance_id INTO locked_substance FROM kobi_substances WHERE substance_id=NEW.substance_id FOR UPDATE;
 SELECT COALESCE(MAX(revision),0) INTO prior_revision FROM medicine_provisional_identity_revisions WHERE substance_id=NEW.substance_id;
 IF NEW.revision<>prior_revision+1 THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine provisional revision is stale';
 END IF;
 IF prior_revision>0 THEN
  SELECT state INTO prior_state FROM medicine_provisional_identity_revisions WHERE substance_id=NEW.substance_id AND revision=prior_revision;
  IF BINARY prior_state<>BINARY 'draft' THEN
   SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine provisional history cannot be restored';
  END IF;
 ELSEIF BINARY NEW.state<>BINARY 'draft' THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine provisional history must begin as draft';
 END IF;
 SELECT COUNT(*) INTO valid_grant FROM medicine_authority_grants g JOIN users u ON u.id=g.user_id
 WHERE g.id=NEW.authority_grant_id AND g.user_id=NEW.actor_id AND u.status='active'
 AND g.is_active=1 AND g.revoked_at IS NULL AND g.revoked_by IS NULL AND g.revocation_reason IS NULL
 AND g.granted_at<=UTC_TIMESTAMP(6) AND g.expires_at>UTC_TIMESTAMP(6)
 AND BINARY g.scope IN (BINARY 'composition',BINARY 'catalog')
 AND ((BINARY NEW.state=BINARY 'draft' AND BINARY g.capability IN (BINARY 'contributor',BINARY 'editor'))
 OR (BINARY NEW.state IN (BINARY 'withdrawn',BINARY 'invalidated') AND BINARY g.capability=BINARY 'steward'));
 IF valid_grant<>1 THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine provisional authority is unavailable';
 END IF;
 IF BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot_json,'$.domain')),'')<>'provisional_identity'
 OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot_json,'$.target')),'')<>CONCAT('provisional:S:',NEW.substance_id)
 OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot_json,'$.registry.id')),'')<>CAST(NEW.substance_id AS CHAR)
 OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot_json,'$.package_sha256')),'')<>'3fc38ca47fa9ace4bd8a9963854e120425909565b2a91e2c8c4ac763da454086'
 OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot_json,'$.evidence_status')),'')<>'NOT_APPRAISED'
 OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot_json,'$.review_status')),'')<>'NOT_ASSIGNED'
 OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot_json,'$.approval_status')),'')<>'NONE'
 OR COALESCE(JSON_TYPE(JSON_EXTRACT(NEW.snapshot_json,'$.reviewer_id')),'')<>'NULL'
 OR COALESCE(JSON_TYPE(JSON_EXTRACT(NEW.snapshot_json,'$.canonical_identity_id')),'')<>'NULL'
 OR COALESCE(JSON_TYPE(JSON_EXTRACT(NEW.snapshot_json,'$.chemical_ref_id')),'')<>'NULL'
 OR COALESCE(JSON_TYPE(JSON_EXTRACT(NEW.snapshot_json,'$.proposal.chemical_form')),'')<>'NULL'
 OR COALESCE(JSON_TYPE(JSON_EXTRACT(NEW.snapshot_json,'$.proposal.stereochemistry')),'')<>'NULL'
 OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot_json,'$.proposal.mapping_status')),'')<>'PENDING_REVIEW'
 OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot_json,'$.proposal.chemical_form_status')),'')<>'PENDING_REVIEW'
 OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot_json,'$.proposal.stereochemistry_status')),'')<>'PENDING_REVIEW'
 OR COALESCE(JSON_TYPE(NEW.evidence_json),'')<>'ARRAY'
 OR COALESCE(JSON_LENGTH(NEW.evidence_json),0)<>2 THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine provisional facts must remain unapproved';
 END IF;
 WHILE source_index<2 DO
  SET source_path=CONCAT('$[',source_index,']');
  SET facts_path=CONCAT('$.sources[',source_index,']');
  IF BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.evidence_json,CONCAT(source_path,'.appraisal_status'))),'')<>'NOT_APPRAISED'
  OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.evidence_json,CONCAT(source_path,'.scope'))),'')<>'provisional_identity'
  OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.evidence_json,CONCAT(source_path,'.target'))),'')<>CONCAT('provisional:S:',NEW.substance_id)
  OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.evidence_json,CONCAT(source_path,'.revision'))),'')<>CAST(NEW.revision AS CHAR)
  OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.evidence_json,CONCAT(source_path,'.snapshot_sha256'))),'')<>LOWER(HEX(NEW.snapshot_hash))
  OR BINARY COALESCE(JSON_COMPACT(JSON_EXTRACT(NEW.evidence_json,CONCAT(source_path,'.source'))),'')<>BINARY COALESCE(JSON_COMPACT(JSON_EXTRACT(NEW.snapshot_json,CONCAT(facts_path,'.proposed_evidence'))),'MISSING')
  OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.evidence_json,CONCAT(source_path,'.proposed_tier'))),'')<>IF(source_index=0,'1','4')
  OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.evidence_json,CONCAT(source_path,'.retrieved_at'))),'')<>BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot_json,CONCAT(facts_path,'.accessed_at_utc'))),'MISSING') THEN
   SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine provisional evidence binding is invalid';
  END IF;
  SET source_index=source_index+1;
 END WHILE;
END$$
CREATE TRIGGER `provisional_identity_no_update` BEFORE UPDATE ON `medicine_provisional_identity_revisions` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine provisional history is immutable';
END$$
CREATE TRIGGER `provisional_identity_no_delete` BEFORE DELETE ON `medicine_provisional_identity_revisions` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine provisional history is immutable';
END$$
DELIMITER ;
```
