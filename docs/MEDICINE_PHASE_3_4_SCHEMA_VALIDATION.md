# Medicine Knowledge Foundation — Phase 3.4 Schema Implementation & Validation

## 1. Scope

**PASS WITH LIMITATIONS.** Implemented exactly the seven additive Phase 3.3 tables in `database/structure.sql`. No existing table definition, application/test source, route, configuration, SQL mode, Treatment governance/public reader or HOLD setting changed. No migration, seed, canonical catalog, medicine UI/API or public exposure was created. Only disposable synthetic fixtures were used and rolled back. Phase 3.5 was not started.

Task-start SHA-256 inventory covered 270 files across app/public/tests/config/bootstrap/database/routes/docs. Pre-existing Git modifications, untracked files and deletions were preserved. Persistent output: modified structure.sql and this new document only. Temporary harness/data/report files were confined to tests/.medicine34-validation and removed after evidence was summarized here.

## 2. Source Design

Authoritative: `docs/MEDICINE_PHASE_3_3_SCHEMA_DESIGN.md`; conceptual input: `docs/MEDICINE_PHASE_3_2_IDENTITY_SPEC.md`. Current schema, HealthMedicineRepository/Service, SubstanceRepository/Service, relevant AccountController/routes/views/JS and existing test architecture were inspected. The existing registry and private text records remain the owners described in those documents.

The new block is after users and all existing referenced registry/private parents, before the substance-search view. Existing dump FOREIGN_KEY_CHECKS handling remains intact; child-table creation order is chemical refs → identities → ingredients → presentations → strengths → links → selections. No existing statement was rewritten.

## 3. Implemented Seven Tables

| Table | Purpose | Implemented | Notes |
| --- | --- | --- | --- |
| health_medicine_identity_links | Optional private medication association | YES | 9 columns; empty foundation, no application writer. |
| health_medicine_selected_ingredients | Private explicit chemical selections | YES | 6 columns; empty foundation, no application writer. |
| kobi_medicine_chemical_refs | Typed existing substance/form reference | YES | 14 columns; empty foundation, no application writer. |
| kobi_medicine_identities | Canonical ingredient composition | YES | 16 columns; empty foundation, no application writer. |
| kobi_medicine_ingredients | Composition membership | YES | 5 columns; empty foundation, no application writer. |
| kobi_medicine_presentations | Strength/form/route/release variant | YES | 20 columns; empty foundation, no application writer. |
| kobi_medicine_strengths | Exact per-member rational strength | YES | 9 columns; empty foundation, no application writer. |

## 4. Schema Compatibility

MariaDB 10.4.27 accepted the exact source definitions: InnoDB, utf8mb4_unicode_ci human text, utf8mb4_bin code/key columns, explicit case/space-sensitive BINARY comparisons, DATETIME(6), DECIMAL(38,0), BINARY(32) digest keys, JSON_VALID and byte-bound CHECKs, explicit composite FKs and ordinary indexes. No generated column, expression index, new ENUM, trigger, eighth table or newer-only feature was added.

Shared auto-increment PKs/registry references are BIGINT unsigned. Existing users/health_medicines references are signed INT. All 26 FK column pairs matched actual parent type/charset/collation. No CHECK references an AUTO_INCREMENT row ID. Required UTC DATETIME(6) values are supplied explicitly by future callers; no server-clock default or SQL timezone change is introduced. Ingredient ordering depends on the future canonicalization service, with no invented constant default.

MariaDB-specific enforcement detail: binary comparisons in CHECKs reject code case and trailing-space variants even though ordinary text collation equality can pad spaces. This implements the approved exact controlled-string contract. No conceptual adaptation or weakened approved constraint was needed.

## 5. MariaDB 10.4.27 Environment

- Version: `10.4.27-MariaDB`.
- Host/port: `127.0.0.1:3324`, separate temporary server process.
- Disposable database: `kobi_medicine_phase34_validation_20261009`.
- Data directory: `D:/xampp/htdocs/kobi/tests/.medicine34-validation/data/`; InnoDB data/log, PID and temporary paths were also confined there.
- Validation: 2026-10-09T11:08:52+05:30; database cleanup: 2026-10-09T11:16:59+05:30; server clean shutdown completed at 2026-10-09 11:17:18 local time.
- Session/global SQL mode throughout: `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`.

The temporary instance read the existing XAMPP settings with isolated path/port overrides; neither configuration file nor SQL-mode setting was changed. Connection guards required the exact version, port, data directory, database name and modes before writes. No normal application bootstrap/credentials/database connection was used. The normal medical1 database did not exist on this separate instance. All DDL/DML targeted the disposable schema or its server's system initialization; no normal KOBI database writes occurred.

An initial sandboxed listener could not be reached (2002/access/timeout); it was stopped and replaced with the authorized isolated process outside the network sandbox. A Windows slash-normalization bug in the temporary precheck stopped before DDL and was corrected. One automatic approval review timed out; its permitted retry succeeded. None was a schema compatibility failure.

## 6. Import Results

The original task-start schema was first imported into the disposable database to capture actual baseline definitions, then that guarded disposable database was dropped/recreated and the complete resulting structure.sql imported.

| Import | Schema statements actually executed | SQL-mode directives omitted | Failures | Import warnings |
| --- | ---: | ---: | ---: | ---: |
| Original baseline | 111 | 3 | 0 | 0 |
| Implemented full schema | 125 | 3 | 0 | 0 |

Database CREATE/USE/DROP administration and client DELIMITER commands are not counted as schema statements. The additional 14 statements are seven DROP IF EXISTS and seven CREATE TABLE statements. All non-SQL-mode source statements executed in source order, including existing indexes/FKs/CHECKs, both Treatment source triggers and view definitions. DELIMITER commands were parsed as client syntax without changing trigger SQL. No other statement was omitted.

Exactly these SQL-mode directives were omitted from the implemented import stream:
- Source line 9: `/*!40101 SET SQL_MODE=''*/;`
- Source line 13: `/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;`
- Source line 1319: `/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;`

The baseline omitted the same three directives at its original source locations (9, 13, 1089). This follows the previously approved isolated-validation practice and validates schema objects under the inherited existing mode. The directives remain unchanged in structure.sql. No SET SQL_MODE was executed.

After import, FOREIGN_KEY_CHECKS=1 and UNIQUE_CHECKS=1; session/global modes remained equal to their initial values. New/total actual objects: 7 / 55 (54 base tables, one view).

Source hashes:
- Task-start/baseline structure.sql: `D3C73231D08047A413F19CF99B76EE769DFE4CBDD3F380BAB8B85D8B8D3DED5B`.
- Implemented structure.sql: `5A7EC86AEF99200052731E5169026E9A7E22CBA5CD71E122A3B9CE23C3496365`.

## 7. Actual Schema Introspection

SHOW CREATE TABLE/VIEW and information_schema columns/statistics/key usage/referential constraints/check constraints/table metadata were inspected. All seven tables exist. A source-to-metadata comparison performed **637 assertions**, with no mismatch; these are assertions, not 637 distinct runtime cases.

Actual columns below: km_ abbreviates kobi_medicine_; hm_ abbreviates health_medicine_. “none” means no declared default; NULL means nullable SQL default. Code/key text has utf8mb4_bin; remaining human text has utf8mb4_unicode_ci. Nontext columns have no text charset/collation.

| Table abbreviation | Column | Actual type | Nullable | Default | Collation / extra |
| --- | --- | --- | --- | --- | --- |
| hm_identity_links | health_medicine_id | int(11) | NO | none |  |
| hm_identity_links | association_mode | varchar(16) | NO | none | utf8mb4_bin |
| hm_identity_links | medicine_identity_id | bigint(20) unsigned | YES | NULL |  |
| hm_identity_links | medicine_presentation_id | bigint(20) unsigned | YES | NULL |  |
| hm_identity_links | link_revision | int(10) unsigned | NO | 1 |  |
| hm_identity_links | created_by | int(11) | NO | none |  |
| hm_identity_links | updated_by | int(11) | NO | none |  |
| hm_identity_links | created_at | datetime(6) | NO | none |  |
| hm_identity_links | updated_at | datetime(6) | NO | none |  |
| hm_selected_ingredients | health_medicine_id | int(11) | NO | none |  |
| hm_selected_ingredients | chemical_ref_id | bigint(20) unsigned | NO | none |  |
| hm_selected_ingredients | association_mode | varchar(16) | NO | none | utf8mb4_bin |
| hm_selected_ingredients | display_order | smallint(5) unsigned | NO | none |  |
| hm_selected_ingredients | observed_name | varchar(500) | YES | NULL | utf8mb4_unicode_ci |
| hm_selected_ingredients | created_at | datetime(6) | NO | none |  |
| km_chemical_refs | chemical_ref_id | bigint(20) unsigned | NO | none | auto_increment |
| km_chemical_refs | substance_id | bigint(20) unsigned | YES | NULL |  |
| km_chemical_refs | form_id | bigint(20) unsigned | YES | NULL |  |
| km_chemical_refs | identity_status | varchar(16) | NO | 'candidate' | utf8mb4_bin |
| km_chemical_refs | metadata_revision | int(10) unsigned | NO | 1 |  |
| km_chemical_refs | created_by | int(11) | NO | none |  |
| km_chemical_refs | created_at | datetime(6) | NO | none |  |
| km_chemical_refs | updated_at | datetime(6) | NO | none |  |
| km_chemical_refs | verified_by | int(11) | YES | NULL |  |
| km_chemical_refs | verified_at | datetime(6) | YES | NULL |  |
| km_chemical_refs | verification_reference | text | YES | NULL | utf8mb4_unicode_ci |
| km_chemical_refs | retired_by | int(11) | YES | NULL |  |
| km_chemical_refs | retired_at | datetime(6) | YES | NULL |  |
| km_chemical_refs | retirement_reason | text | YES | NULL | utf8mb4_unicode_ci |
| km_identities | medicine_identity_id | bigint(20) unsigned | NO | none | auto_increment |
| km_identities | key_version | smallint(5) unsigned | NO | 1 |  |
| km_identities | composition_hash | binary(32) | NO | none |  |
| km_identities | composition_key | text | NO | none | utf8mb4_bin |
| km_identities | display_name | varchar(500) | YES | NULL | utf8mb4_unicode_ci |
| km_identities | identity_status | varchar(16) | NO | 'candidate' | utf8mb4_bin |
| km_identities | metadata_revision | int(10) unsigned | NO | 1 |  |
| km_identities | created_by | int(11) | NO | none |  |
| km_identities | created_at | datetime(6) | NO | none |  |
| km_identities | updated_at | datetime(6) | NO | none |  |
| km_identities | verified_by | int(11) | YES | NULL |  |
| km_identities | verified_at | datetime(6) | YES | NULL |  |
| km_identities | verification_reference | text | YES | NULL | utf8mb4_unicode_ci |
| km_identities | retired_by | int(11) | YES | NULL |  |
| km_identities | retired_at | datetime(6) | YES | NULL |  |
| km_identities | retirement_reason | text | YES | NULL | utf8mb4_unicode_ci |
| km_ingredients | medicine_ingredient_id | bigint(20) unsigned | NO | none | auto_increment |
| km_ingredients | medicine_identity_id | bigint(20) unsigned | NO | none |  |
| km_ingredients | chemical_ref_id | bigint(20) unsigned | NO | none |  |
| km_ingredients | display_order | smallint(5) unsigned | NO | none |  |
| km_ingredients | created_at | datetime(6) | NO | none |  |
| km_presentations | medicine_presentation_id | bigint(20) unsigned | NO | none | auto_increment |
| km_presentations | medicine_identity_id | bigint(20) unsigned | NO | none |  |
| km_presentations | key_version | smallint(5) unsigned | NO | 1 |  |
| km_presentations | presentation_hash | binary(32) | NO | none |  |
| km_presentations | presentation_key | text | NO | none | utf8mb4_bin |
| km_presentations | dosage_form_code | varchar(32) | NO | none | utf8mb4_bin |
| km_presentations | route_code | varchar(32) | NO | none | utf8mb4_bin |
| km_presentations | release_code | varchar(32) | NO | none | utf8mb4_bin |
| km_presentations | display_name | varchar(500) | YES | NULL | utf8mb4_unicode_ci |
| km_presentations | identity_status | varchar(16) | NO | 'candidate' | utf8mb4_bin |
| km_presentations | metadata_revision | int(10) unsigned | NO | 1 |  |
| km_presentations | created_by | int(11) | NO | none |  |
| km_presentations | created_at | datetime(6) | NO | none |  |
| km_presentations | updated_at | datetime(6) | NO | none |  |
| km_presentations | verified_by | int(11) | YES | NULL |  |
| km_presentations | verified_at | datetime(6) | YES | NULL |  |
| km_presentations | verification_reference | text | YES | NULL | utf8mb4_unicode_ci |
| km_presentations | retired_by | int(11) | YES | NULL |  |
| km_presentations | retired_at | datetime(6) | YES | NULL |  |
| km_presentations | retirement_reason | text | YES | NULL | utf8mb4_unicode_ci |
| km_strengths | medicine_presentation_id | bigint(20) unsigned | NO | none |  |
| km_strengths | medicine_identity_id | bigint(20) unsigned | NO | none |  |
| km_strengths | medicine_ingredient_id | bigint(20) unsigned | NO | none |  |
| km_strengths | ratio_numerator | decimal(38,0) | NO | none |  |
| km_strengths | ratio_denominator | decimal(38,0) | NO | none |  |
| km_strengths | numerator_unit_code | varchar(8) | NO | none | utf8mb4_bin |
| km_strengths | denominator_unit_code | varchar(8) | NO | none | utf8mb4_bin |
| km_strengths | original_strength_text | varchar(512) | YES | NULL | utf8mb4_unicode_ci |
| km_strengths | created_at | datetime(6) | NO | none |  |

Metadata checks also covered exact column order/count, required/nullable shape, defaults, auto-increment, table engine/collation, datetime precision, all index column order/uniqueness and all FK column order/targets/actions. Each DECIMAL coefficient has precision 38, scale 0.

## 8. Primary Keys / Indexes / Unique Constraints

Actual metadata contains **39 indexes**, including **7 PKs and 10 additional UNIQUE indexes**. All 17 PK/UNIQUE definitions had duplicate-insert cases rejected with native 1062; valid distinct fixture rows coexist.

| Table | Index | Ordered columns | Kind |
| --- | --- | --- | --- |
| health_medicine_identity_links | idx_health_medicine_link_created_by | created_by | INDEX |
| health_medicine_identity_links | idx_health_medicine_link_medicine_identity_id | medicine_identity_id | INDEX |
| health_medicine_identity_links | idx_health_medicine_link_medicine_presentation_id | medicine_presentation_id | INDEX |
| health_medicine_identity_links | idx_health_medicine_link_updated_by | updated_by | INDEX |
| health_medicine_identity_links | PRIMARY | health_medicine_id | UNIQUE |
| health_medicine_identity_links | uq_health_medicine_link_mode | health_medicine_id, association_mode | UNIQUE |
| health_medicine_selected_ingredients | idx_health_medicine_selection_mode | health_medicine_id, association_mode | INDEX |
| health_medicine_selected_ingredients | idx_health_medicine_selection_ref | chemical_ref_id | INDEX |
| health_medicine_selected_ingredients | PRIMARY | health_medicine_id, chemical_ref_id | UNIQUE |
| health_medicine_selected_ingredients | uq_health_medicine_selection_order | health_medicine_id, display_order | UNIQUE |
| kobi_medicine_chemical_refs | idx_medicine_ref_created_by | created_by | INDEX |
| kobi_medicine_chemical_refs | idx_medicine_ref_retired_by | retired_by | INDEX |
| kobi_medicine_chemical_refs | idx_medicine_ref_status | identity_status, chemical_ref_id | INDEX |
| kobi_medicine_chemical_refs | idx_medicine_ref_verified_by | verified_by | INDEX |
| kobi_medicine_chemical_refs | PRIMARY | chemical_ref_id | UNIQUE |
| kobi_medicine_chemical_refs | uq_medicine_ref_form | form_id | UNIQUE |
| kobi_medicine_chemical_refs | uq_medicine_ref_substance | substance_id | UNIQUE |
| kobi_medicine_identities | idx_medicine_identity_created_by | created_by | INDEX |
| kobi_medicine_identities | idx_medicine_identity_retired_by | retired_by | INDEX |
| kobi_medicine_identities | idx_medicine_identity_status | identity_status, medicine_identity_id | INDEX |
| kobi_medicine_identities | idx_medicine_identity_verified_by | verified_by | INDEX |
| kobi_medicine_identities | PRIMARY | medicine_identity_id | UNIQUE |
| kobi_medicine_identities | uq_medicine_composition_hash | composition_hash | UNIQUE |
| kobi_medicine_ingredients | idx_medicine_ingredient_ref | chemical_ref_id, medicine_identity_id | INDEX |
| kobi_medicine_ingredients | PRIMARY | medicine_ingredient_id | UNIQUE |
| kobi_medicine_ingredients | uq_medicine_ingredient_order | medicine_identity_id, display_order | UNIQUE |
| kobi_medicine_ingredients | uq_medicine_ingredient_ref | medicine_identity_id, chemical_ref_id | UNIQUE |
| kobi_medicine_ingredients | uq_medicine_ingredient_scope | medicine_identity_id, medicine_ingredient_id | UNIQUE |
| kobi_medicine_presentations | idx_medicine_presentation_created_by | created_by | INDEX |
| kobi_medicine_presentations | idx_medicine_presentation_identity | medicine_identity_id, identity_status, medicine_presentation_id | INDEX |
| kobi_medicine_presentations | idx_medicine_presentation_retired_by | retired_by | INDEX |
| kobi_medicine_presentations | idx_medicine_presentation_status | identity_status, medicine_presentation_id | INDEX |
| kobi_medicine_presentations | idx_medicine_presentation_verified_by | verified_by | INDEX |
| kobi_medicine_presentations | PRIMARY | medicine_presentation_id | UNIQUE |
| kobi_medicine_presentations | uq_medicine_presentation_hash | presentation_hash | UNIQUE |
| kobi_medicine_presentations | uq_medicine_presentation_scope | medicine_identity_id, medicine_presentation_id | UNIQUE |
| kobi_medicine_strengths | idx_medicine_strength_ingredient | medicine_identity_id, medicine_ingredient_id | INDEX |
| kobi_medicine_strengths | idx_medicine_strength_presentation | medicine_identity_id, medicine_presentation_id | INDEX |
| kobi_medicine_strengths | PRIMARY | medicine_presentation_id, medicine_ingredient_id | UNIQUE |

Scoped identity+ingredient-ID, identity+presentation-ID and medication+mode UNIQUEs intentionally overlap the PK. Their duplicate inserts can be rejected by the PK first; the separate scoped index is verified in metadata and exercised as the referenced composite FK target. This does not imply 17 independently distinguishable duplicate algorithms.

## 9. Foreign-Key Validation

All **23 FKs / 26 column pairs** verified against actual metadata. Each had a valid-reference update and a nonexistent-reference update rejected with **1452 naming that exact FK**. The tuple was made CHECK-valid first, and dependent strengths removed within the case where needed, so parent protection did not mask the outgoing FK test.

| Constraint | Child columns | Parent columns | DELETE / UPDATE | Missing parent |
| --- | --- | --- | --- | --- |
| fk_health_medicine_link_created_by | health_medicine_identity_links (created_by) | users (id) | RESTRICT / RESTRICT | 1452; PASS |
| fk_health_medicine_link_identity | health_medicine_identity_links (medicine_identity_id) | kobi_medicine_identities (medicine_identity_id) | RESTRICT / RESTRICT | 1452; PASS |
| fk_health_medicine_link_presentation | health_medicine_identity_links (medicine_presentation_id) | kobi_medicine_presentations (medicine_presentation_id) | RESTRICT / RESTRICT | 1452; PASS |
| fk_health_medicine_link_record | health_medicine_identity_links (health_medicine_id) | health_medicines (id) | CASCADE / RESTRICT | 1452; PASS |
| fk_health_medicine_link_updated_by | health_medicine_identity_links (updated_by) | users (id) | RESTRICT / RESTRICT | 1452; PASS |
| fk_health_medicine_selection_link | health_medicine_selected_ingredients (health_medicine_id, association_mode) | health_medicine_identity_links (health_medicine_id, association_mode) | CASCADE / RESTRICT | 1452; PASS |
| fk_health_medicine_selection_ref | health_medicine_selected_ingredients (chemical_ref_id) | kobi_medicine_chemical_refs (chemical_ref_id) | RESTRICT / RESTRICT | 1452; PASS |
| fk_medicine_ref_created_by | kobi_medicine_chemical_refs (created_by) | users (id) | RESTRICT / RESTRICT | 1452; PASS |
| fk_medicine_ref_form | kobi_medicine_chemical_refs (form_id) | kobi_substance_forms (form_id) | RESTRICT / RESTRICT | 1452; PASS |
| fk_medicine_ref_retired_by | kobi_medicine_chemical_refs (retired_by) | users (id) | RESTRICT / RESTRICT | 1452; PASS |
| fk_medicine_ref_substance | kobi_medicine_chemical_refs (substance_id) | kobi_substances (substance_id) | RESTRICT / RESTRICT | 1452; PASS |
| fk_medicine_ref_verified_by | kobi_medicine_chemical_refs (verified_by) | users (id) | RESTRICT / RESTRICT | 1452; PASS |
| fk_medicine_identity_created_by | kobi_medicine_identities (created_by) | users (id) | RESTRICT / RESTRICT | 1452; PASS |
| fk_medicine_identity_retired_by | kobi_medicine_identities (retired_by) | users (id) | RESTRICT / RESTRICT | 1452; PASS |
| fk_medicine_identity_verified_by | kobi_medicine_identities (verified_by) | users (id) | RESTRICT / RESTRICT | 1452; PASS |
| fk_medicine_ingredient_identity | kobi_medicine_ingredients (medicine_identity_id) | kobi_medicine_identities (medicine_identity_id) | RESTRICT / RESTRICT | 1452; PASS |
| fk_medicine_ingredient_ref | kobi_medicine_ingredients (chemical_ref_id) | kobi_medicine_chemical_refs (chemical_ref_id) | RESTRICT / RESTRICT | 1452; PASS |
| fk_medicine_presentation_created_by | kobi_medicine_presentations (created_by) | users (id) | RESTRICT / RESTRICT | 1452; PASS |
| fk_medicine_presentation_identity | kobi_medicine_presentations (medicine_identity_id) | kobi_medicine_identities (medicine_identity_id) | RESTRICT / RESTRICT | 1452; PASS |
| fk_medicine_presentation_retired_by | kobi_medicine_presentations (retired_by) | users (id) | RESTRICT / RESTRICT | 1452; PASS |
| fk_medicine_presentation_verified_by | kobi_medicine_presentations (verified_by) | users (id) | RESTRICT / RESTRICT | 1452; PASS |
| fk_medicine_strength_ingredient | kobi_medicine_strengths (medicine_identity_id, medicine_ingredient_id) | kobi_medicine_ingredients (medicine_identity_id, medicine_ingredient_id) | RESTRICT / RESTRICT | 1452; PASS |
| fk_medicine_strength_presentation | kobi_medicine_strengths (medicine_identity_id, medicine_presentation_id) | kobi_medicine_presentations (medicine_identity_id, medicine_presentation_id) | RESTRICT / RESTRICT | 1452; PASS |

All new FK updates are RESTRICT; all shared-reference deletions are RESTRICT. Per-edge action cases checked declared actions and observed protected parent deletes/updates (1451) in the populated fixture graph. Where several inbound references coexist, another inbound FK can be the first rejecting constraint; these shared-graph cases prove parent protection, while exact per-edge rules are established by metadata plus the named outgoing-reference cases. This limitation is explicit.

Private delete cascades were verified both transitively (health_medicines → sidecar → selected rows) and directly (delete sidecar, retain medication). Selection-mode updates are rejected while children exist; removal of selections then atomic mode change succeeds. Wrong-mode selection and wrong-composition presentation/member strengths are rejected.

## 10. Constraint Validation

The final schema harness ran **140 distinct test cases, 515 assertions, zero failures**. It covered all 37 named CHECKs, all 17 PK/UNIQUE definitions and all 23 FKs, plus structural/default/precision/boundary/action cases. These categories overlap the same 140-case run and must not be summed as extra cases.

| CHECK | Rejected input | Result |
| --- | --- | --- |
| chk_health_medicine_link_mode | unknown mode | 4025, named constraint; PASS |
| chk_health_medicine_link_revision | Revision 0 | 4025, named constraint; PASS |
| chk_health_medicine_link_shape | ingredients mode with composition FK | 4025, named constraint; PASS |
| chk_health_medicine_selection_mode | composition as selection mode | 4025, named constraint; PASS |
| chk_health_medicine_selection_order | Order 0; 16/17 bounds separately checked | 4025, named constraint; PASS |
| chk_medicine_ref_retired | retired without retirement tuple | 4025, named constraint; PASS |
| chk_medicine_ref_retirement | Partial retirement tuple | 4025, named constraint; PASS |
| chk_medicine_ref_revision | Revision 0 | 4025, named constraint; PASS |
| chk_medicine_ref_status | unknown status | 4025, named constraint; PASS |
| chk_medicine_ref_target | Neither target; both targets separately checked | 4025, named constraint; PASS |
| chk_medicine_ref_verification | Partial verification tuple | 4025, named constraint; PASS |
| chk_medicine_ref_verified | verified without verification tuple | 4025, named constraint; PASS |
| chk_medicine_composition_key | Malformed JSON; byte bound separately checked | 4025, named constraint; PASS |
| chk_medicine_composition_version | Version 2 | 4025, named constraint; PASS |
| chk_medicine_identity_retired | retired without retirement tuple | 4025, named constraint; PASS |
| chk_medicine_identity_retirement | Partial retirement tuple | 4025, named constraint; PASS |
| chk_medicine_identity_revision | Revision 0 | 4025, named constraint; PASS |
| chk_medicine_identity_status | unknown status | 4025, named constraint; PASS |
| chk_medicine_identity_verification | Partial verification tuple | 4025, named constraint; PASS |
| chk_medicine_identity_verified | verified without verification tuple | 4025, named constraint; PASS |
| chk_medicine_ingredient_order | Order 0; 16/17 bounds separately checked | 4025, named constraint; PASS |
| chk_medicine_presentation_form | unknown form | 4025, named constraint; PASS |
| chk_medicine_presentation_form_release | tablet + not_applicable | 4025, named constraint; PASS |
| chk_medicine_presentation_form_route | tablet + cutaneous | 4025, named constraint; PASS |
| chk_medicine_presentation_key | Malformed JSON; byte bound separately checked | 4025, named constraint; PASS |
| chk_medicine_presentation_release | unknown release | 4025, named constraint; PASS |
| chk_medicine_presentation_retired | retired without retirement tuple | 4025, named constraint; PASS |
| chk_medicine_presentation_retirement | Partial retirement tuple | 4025, named constraint; PASS |
| chk_medicine_presentation_revision | Revision 0 | 4025, named constraint; PASS |
| chk_medicine_presentation_route | unknown route | 4025, named constraint; PASS |
| chk_medicine_presentation_status | unknown status | 4025, named constraint; PASS |
| chk_medicine_presentation_verification | Partial verification tuple | 4025, named constraint; PASS |
| chk_medicine_presentation_verified | verified without verification tuple | 4025, named constraint; PASS |
| chk_medicine_presentation_version | Version 2 | 4025, named constraint; PASS |
| chk_medicine_strength_denominator_unit | ML instead of mL | 4025, named constraint; PASS |
| chk_medicine_strength_numerator_unit | mg as canonical numerator unit | 4025, named constraint; PASS |
| chk_medicine_strength_ratio | Zero numerator; both zero/negative coefficients separately checked | 4025, named constraint; PASS |

Additional cases: both adapter targets; verified/retired complete tuples; blank attribution references/reasons; wrong code case/trailing spaces; valid supported form/route/release combinations; JSON byte limits at 8192/16384 and one byte over; display order 16 accepted/17 rejected; all canonical denominator codes; NULL controlled fields; default candidate/version/revision and nullable display/provenance; scoped strength and private-mode errors; direct/transitive cascades and safe mode transition. Nullable substance/form targets behave exclusively; multiple NULLs in their separate UNIQUE indexes do not prevent distinct valid adapters.

The first 136-case run had four fixture-setup failures (NULL merge into links, attempted invalidation of the constrained mode instead of medication ID, and outgoing-parent update masked by inbound strengths). Only the temporary harness was corrected, with no schema alteration. The corrected 136-case run passed; four extra cases brought the final run to 140. The intermediate runs are not counted again. No schema failure was concealed.

## 11. Strength / Quantity Precision Validation

- Positive exact integer coefficient strings round-trip without float conversion.
- The synthetic repeating ratio 1/3 remains separate exact coefficients.
- Largest 38-digit coefficient `99999999999999999999999999999999999999` accepted exactly in numerator and denominator.
- Zero and negative numerator/denominator rejected by named ratio CHECK, native 4025.
- NULL numerator rejected after coercion by the ratio CHECK, native 4025.
- Canonical units g and unit/mL/g accepted; mg numerator, wrong case/trailing-space/unknown denominator rejected.
- Per-member coefficients and microsecond timestamps retrieved exactly.

**Expected non-strict coercion limitation, observed directly:**

| Direct SQL input | Stored value | Native diagnostic |
| --- | --- | --- |
| Numerator `1.5` | `2` | Note 1265, data truncated |
| Numerator `100000000000000000000000000000000000000` (39 digits) | `99999999999999999999999999999999999999` | Warning 1264, out of range |

These two direct invalid inputs were accepted after coercion; a row CHECK sees the resulting positive DECIMAL value. Phase 3.3 explicitly requires service prevalidation and does not promise raw SQL fractional/overflow rejection. No SQL-mode change or new trigger was invented. The characterization cases pass because the observed limitation matches that approved enforcement split; this is not a claim that those original inputs are valid.

Input grammar/12+12 decimal bounds, exact conversion/GCD reduction, denominator integer-count semantics, correct measured basis, full vector/form-denominator compatibility and canonical hashing remain future service tests. No real pharmaceutical/patient dosing data or prescribing rules were used.

## 12. Combination Medicine Structural Validation

Disposable placeholders represented composition A+B via two distinct chemical references/memberships. A separate one-member composition used a form adapter. Duplicate identity+chemical membership and duplicate display ordering are rejected. Actual two-member retrieval preserved references/order. No imported combination, live registry semantics or real medicine was reclassified.

Reordered-set canonicalization and scientific substance/form equivalence require the future service/reconciliation process; PK/FK/UNIQUE tests do not implement those algorithms.

## 13. Presentation Structural Validation

One composition had two distinct presentations with complete synthetic two-member strength vectors: supported tablet/unit and solution/mL variants. A second composition had its own presentation/member. Both same-parent variants coexist; wrong-parent strengths fail the scoped FKs. No brand, product, dose or active-moiety conversion was introduced.

The schema allows parent rows before children within a transaction. Nonempty composition/full-vector completeness, aggregate/key consistency and semantic immutability cannot be established by ordinary row constraints and are deliberately deferred service invariants.

## 14. Legacy Compatibility

The task-start original.sql reconstructed by removing only the new block had the exact task-start structure.sql SHA-256. Actual SHOW CREATE definitions for all **48 original table/view objects** were byte-for-byte equal before and after the additive import, with **zero drift**. This includes kobi_substances/forms, health_medicines/profiles and every Treatment table/view definition. Existing source triggers were imported unchanged.

A raw-text personal medication without a sidecar retained its original synthetic strength/dose text and remained unlinked. No migration, registry import, snapshot rewrite or change to existing ownership/CRUD behavior was performed.

## 15. Existing Application Compatibility

Safe existing database-free suites reported:
- auth-phase1.php: 45 scenarios passed.
- treatment-operations.php: 127 focused tests plus 443 retained earlier checks passed.
- treatment-publication-correction.php: 17 new plus 105 existing publication tests passed.
- treatment-prerequisites.php: 29 risk, 7 lifecycle and 11 comparison checks passed, with its inherited suites also passing.

Inherited reruns are not counted as additional distinct tests. These suites use memory/fakes and do not mutate the normal database; their HOLD checks passed. This is bounded static/regression evidence, not a browser or live personal-medication application rollout test.

PHP application files touched: 0; PHP application syntax checks therefore not applicable. Temporary PHP harnesses executed successfully. git diff --check passed. No medicine runtime functionality exists to test against the new tables yet.

## 16. Approved Design vs Implemented Schema

No conceptual/schema drift identified. The seven names, required/nullable column contracts, BIGINT/signed-INT FK types, composition/member and presentation/strength split, private XOR modes, exact ratios, code sets, hashes/JSON bounds, actor/status attribution, uniqueness and RESTRICT/private CASCADE semantics match Phase 3.3.

Implementation choices within the permitted conventions: explicit datetime(6) inputs for UTC attribution; version/revision defaults 1 and candidate default; utf8mb4_unicode_ci table text with binary code/key columns; named supporting/scoped indexes; binary CHECK comparisons. No transition trigger: terminal retirement, evidence/authority, current active eligibility and preservation of prior verification tuple across transitions require the approved future services. No other constraint was weakened.

Developer deployment plan, requiring separate normal-database authorization: first verify the existing parent key types/engine and that all seven new names are absent. Review and apply only the seven CREATE TABLE definitions from the new block, in the listed dependency order; exclude all dump DROP statements, existing-object statements and SQL-mode directives. Keep foreign-key/unique checks enabled and the existing SQL mode unchanged. Stop on an unexpected existing name, warning or compatibility failure. MariaDB DDL is not one rollbackable transaction, so review backup/recovery and partial-application handling before deployment; do not silently drop objects to retry. Afterwards compare all new metadata to this report, confirm new tables are empty and verify existing definitions/behavior. No normal-database application was performed here.

## 17. Findings

| ID | Severity | Finding | Impact | Blocking? | Recommendation |
| --- | --- | --- | --- | --- | --- |
| M34-F1 | MEDIUM | Non-strict DECIMAL rounds/clamps direct fractional/overflow input, with 1265/1264 diagnostics. | Raw SQL writers can persist a different positive coefficient. | No for empty schema; yes before canonical data acceptance without validation. | Enforce Phase 3.3 exact input/string bounds in future services; keep SQL mode unchanged. |
| M34-F2 | INFORMATIONAL | Aggregate completeness, hashes, exact arithmetic, chemical reconciliation and lifecycle transitions are service/governance rules. | Row constraints alone do not make data verified or publicly eligible. | No for schema; prerequisite for persistence rollout. | Implement and test separately; no catalog/public writer now. |
| M34-F3 | INFORMATIONAL | Some UNIQUEs overlap PKs; shared FK action fixtures can have multiple inbound blockers. | A native duplicate/delete error may name the overlapping/first constraint. | No. | Use actual index/FK metadata plus named outgoing-reference tests; do not overstate isolation of every inbound action. |
| M34-F4 | INFORMATIONAL | Existing Phase 3.1 exception/date defects remain outside this task. | Affected handlers must not be extended before separately authorized correction. | No for empty schema. | Address prerequisites in separate scope before private integration. |

No required constraint compatibility failure, existing-table alteration, eighth-table need or conceptual redesign blocker occurred.

## 18. Known Limitations

This is schema validation, not medicine application implementation or clinical approval. Trusted direct writers can bypass semantic service rules; candidate/inactive association denial, canonical order/hash collision logic, scientific S/F reconciliation, GCD/conversion/bounds, retirement transition enforcement, stale revisions and concurrent service transaction/retry behavior are deferred. JSON_VALID proves valid JSON, not the approved aggregate payload.

No normal database was queried to compare live patient/catalog data. No browser, HTTP medicine UI, populated-registry migration, clinical governance/public-read or real-world formulation acceptance testing was performed. Synthetic FK/type/structural fixtures establish relational capability only.

Baseline import and final import used a fresh separate data directory, not a normal database upgrade. Full structure.sql remains a dump with DROP statements and SQL-mode directives; applying additive changes to the normal database remains a separately reviewed developer action, not full-dump reimport.

## 19. Final Validation Decision

**PASS WITH LIMITATIONS.** Import, actual metadata, all implemented constraints/uniqueness/FKs, exact coefficient representation, combination/presentation structures, legacy definition preservation and safe application/static checks passed. Documented non-strict coercion behavior is already accounted for by the approved service enforcement contract.

Cleanup: every one of the 54 base tables had zero fixture rows after rollback. The disposable database was dropped and absence verified. The temporary MariaDB process shut down normally with exit 0. Port/process checks and temporary-file removal were performed; no validation listener or temporary application/harness files remain. Normal KOBI database: no selection/writes; SQL mode/config/HOLD unchanged.

Files modified: database/structure.sql. Files created: this validation document. No pre-existing file deleted or other baseline file changed. No Git commit, push, reset, checkout, merge, rebase or history mutation.

## 20. Phase 3.5 Recommendation

A separately authorized, narrowly scoped persistence/canonicalization task: implement exact bounded arithmetic, fixed composition/presentation serializers, aggregate transactions and duplicate/current-read handling; test service invariants on a disposable database. Establish identity-verifier authority and resolve registry semantics before verified data acceptance. Keep private UI/data migration, medicine clinical content/public exposure and Treatment linkage out of that task. Existing Phase 3.1 safety prerequisites require separate authorization before affected handlers are extended. Do not populate or apply this schema to the normal database without separate approval.

