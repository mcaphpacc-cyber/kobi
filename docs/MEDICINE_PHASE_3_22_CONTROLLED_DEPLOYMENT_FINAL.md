# Medicine Phase 3.22 Controlled Deployment — Final Report

**PHASE 3.22 CONTROLLED DEPLOYMENT — PASS**

Phase 3.22 governance schema is deployed and verified. Deployment was completed once; the credit-reset resume performed only read-only confirmation and final reporting. No deployment or regression suite was restarted. Normal KOBI received only the six authorized schema statements; no governance data was created.

## Artifact and previous validation

The authoritative artifact was identified from the existing implementation, schema block, MariaDB suite and `docs/MEDICINE_PHASE_3_22_REVIEWER_EVIDENCE_GOVERNANCE.md`. The previously validated implementation and original `phase322-isolated.json` evidence were retained. No schema redesign or regeneration occurred.

- Artifact: `database/medicine-phase322-reviewer-evidence.sql`.
- SHA-256: `c7796b079be60b19931969641907e45ce9e6b4156bf0f825c99ef89696bf5dc9`.
- The artifact and matching `database/structure.sql` block remained unchanged.
- Existing qualified reviewer history, credential/reviewer binding, immutable appraisal and exact provisional-revision protections passed the completed unit and MariaDB suites.

## Target, preflight and recoverable backup

Target: `medical1`, host `localhost`, hostname `LAPTOP-PTFFHOMV`, port `3306`, MariaDB `10.4.27-MariaDB`. Preflight inventory was **55 tables / 11 triggers**. SQL mode remained `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`; global/server mode and persistent application configuration were not changed. Normal trigger creation context remained `utf8mb4` / `utf8mb4_general_ci`.

Fresh pre-deployment checkpoint:

- Path: `D:/xampp/htdocs/kobi/storage/logs/medicine321a-owner-recovery/phase322-final-before.sql`.
- Size: **5,364,373 bytes**.
- SHA-256: `03a59492fd1af25f1cd930973c557a66d4293d0a44b40ea02e2e3794bde2cc39`.
- Dump exit status: **0**; stderr empty; readable and nonempty. Before/after-dump complete inventory matched.
- Preflight observation: `2026-10-10 21:49:07.992718 UTC` / `2026-10-11 03:19:07.992718 Asia/Calcutta`.

The already-certified recovery procedure was verified, including two independent corrected MariaDB restores with zero warnings, data/generated-value fidelity and trigger metadata fidelity. Recovery was not required or executed for this deployment. Earlier protected backups remained unchanged.

Fresh recovery derivative: `storage/logs/medicine321a-owner-recovery/phase322-final-DERIVED.sql`, **5,364,582 bytes**, SHA-256 `92c4d7636b3663ed23861e3df2a9a7a94246d8da3843c8923dbb0c4e9561a572`. Its 546-statement manifest records the single certified generated-column correction: statement 315 omits `active_slot` inputs from the one-row authority-grant insert. All other source bytes are preserved; the original backup is immutable. Backup and derivative identities were rechecked at final resume.

An initial operator preflight compared differently shaped generated-column metadata representations and stopped before backup or DDL. The representation was corrected to the existing certified format; actual schema metadata matched. This was not a migration warning or schema change.

## Execution and exact schema verification

Deployment ran `2026-10-10 21:50:17.226833–21:50:17.352677 UTC` / `2026-10-11 03:20:17.226833–03:20:17.352677 Asia/Calcutta`.

**Six statements executed, zero warnings, zero errors, exit status 0:**

1. `CREATE TABLE medicine_reviewer_qualifications`.
2. `CREATE TABLE medicine_evidence_appraisals`.
3. `CREATE TRIGGER medicine_qualification_no_update`.
4. `CREATE TRIGGER medicine_qualification_no_delete`.
5. `CREATE TRIGGER medicine_appraisal_no_update`.
6. `CREATE TRIGGER medicine_appraisal_no_delete`.

Exact executed SQL and immediate per-statement `SHOW WARNINGS` results are recorded in `phase322-final-normal-ddl.json`; the immutable SQL artifact is the SQL applied. **No SQL remains to be applied and the migration must not be rerun.** No corrective DDL was performed.

Final inventory: **57 tables / 15 triggers**. The original 55 table definitions, indexes, unique constraints, foreign keys, CHECK constraints and 11 trigger definitions/context remain unchanged. No unexpected schema objects exist in the verified complete inventory.

The two new InnoDB tables match the exact artifact and independent rehearsal, including every column, type, nullability, default, index, FK and CHECK:

- `medicine_reviewer_qualifications`: eight non-null columns; primary key `id`; unique `(user_id, revision)` and `(id, user_id)`; two RESTRICT foreign keys to users; three state/shape/binding CHECK constraints.
- `medicine_evidence_appraisals`: eleven non-null columns; primary key `id`; unique `(substance_id, provisional_revision, decision_revision)`; qualification/reviewer index; three RESTRICT foreign keys binding provisional revision, qualification/reviewer and authority grant; three state/shape/binding CHECK constraints.
- Both table defaults are `utf8mb4_unicode_ci`, with explicit binary comparison for controlled state/JSON values and binary snapshot hashes. Neither introduces a generated column or seed row. Both contain **zero rows**.

All four new BEFORE UPDATE/DELETE triggers match exact `SHOW CREATE TRIGGER` and complete normalized `information_schema.TRIGGERS` metadata: immutable-history SIGNAL behavior, `root@localhost` definer, action order 1, ROW orientation, NULL condition, established SQL mode, client `utf8mb4`, connection/database collation `utf8mb4_general_ci`. Only environment schema names and creation timestamps are excluded from cross-environment comparison; bodies and all operative metadata are compared.

The isolated rehearsal executed all 142 base schema statements plus the exact six additions. Only these SQL-mode directives were intentionally omitted from the isolated base import:

```sql
/*!40101 SET SQL_MODE=''*/;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
```

This preserved the existing server mode. No other schema statement was silently omitted. Isolated connection character-set setup reproduced normal KOBI's `utf8mb4` creation context; it did not change SQL mode.

## Data, authority and exposure

All **26,209 pre-existing rows** across the complete original inventory retain identical counts/digests. Established counts:

| Data | Rows |
| --- | ---: |
| Substance registry | 2,224 |
| Personal medications | 3 |
| Combinations | 2,327 |
| Combination members | 6,671 |
| Treatment protocols | 2,150 |
| Treatment items | 3,190 |
| Diseases | 400 |

Account 1 remains the owner and sole Medicine Administrator, with **NULL expiry**. There is exactly one administrator grant and one unchanged bootstrap audit/history event. Owner snapshot digest: `41ad91d0341eb8a147429876a43a3b8573b3602cb280cf8439077024b860778d`. No reviewer, contributor/editor, delegated administrator, deployment operator or other authority was granted.

No reviewer registration, professional credential verification, reviewer qualification, evidence appraisal/decision, provisional identity, canonical Medicine Identity, chemical reference, candidate approval or pilot enrollment was created. The five selected candidates remain active registry `CANDIDATE` / `UNKNOWN` records, unreviewed and unapproved.

**Medicine Pilot OFF; Public Medicine OFF; Treatment HOLD.** Internal pilot activation and reviewer/evidence operational configuration remain empty. Treatment schema/data and public exposure are unchanged. No production application, authorization, policy, authentication, route or configuration change occurred; source hash inventories match preflight.

## Completed regressions

All required suites completed successfully before the resume; results were preserved, not rerun. No assertions were weakened, deleted or skipped. Synthetic fixture writes occurred only in guarded disposable MariaDB environments, never normal KOBI.

| Suite | Cases/checks | Assertions | Result |
| --- | ---: | ---: | --- |
| Reviewer/evidence unit | 57 | 170 | PASS |
| Phase 3.22 MariaDB | 7 | 26 | PASS |
| Provisional identity unit / MariaDB | 43 / 15 | 240 / 65 | PASS |
| Medicine governance unit / MariaDB | 56 / 38 | 249 / 144 | PASS |
| Medicine policy unit / MariaDB | 33 / 11 | 92 / 43 | PASS |
| Medicine foundation unit / MariaDB | 105 / 29 | 216 / 96 | PASS |
| Medicine pilot unit / MariaDB | 40 / 15 | 143 / 141 | PASS |
| Candidate selection | 55 checks | — | PASS |
| Pilot readiness | 43 checks | — | PASS |
| Evidence readiness | 11 | 134 | PASS |
| Authentication | 45 scenarios | — | PASS |
| Certified-addition integrity negatives | 8 | — | PASS |

Treatment prerequisites, governance, editorial, review, publication, publication correction, creation, operations and administration hardening suites all passed. Focused checks respectively included prerequisite/risk/lifecycle 29/7/11, governance 91, editorial 73, review 130, publication 105, correction 17, creation 110, operations 127 and administration hardening 39. Embedded predecessor suites also passed; duplicated embedded counts are not summed as unique tests.

Phase 3.19 deployment verifier and final readiness verifier passed with the exact certified Phase 3.22 additions. Clinical candidate readiness correctly remains **BLOCKED** by missing evidence, qualified review, identity revision and pilot gates; this is expected and not deployment failure.

Security negative paths passed: unauthorized/unqualified reviewer, self-approval, forged/revoked authority, stale revisions, evidence mutation, immutable reviewer/appraisal history, protected withdrawal, invalidation and explicit recovery, unauthorized pilot enrollment and public Medicine denial.

Seven new/modified operator PHP files passed syntax checks. Final tracked diff whitespace verification passed. No Git mutation occurred.

## Verifiers, cleanup and resume

The Phase 3.22 final deployment verifier completed with `SCHEMA_DEPLOYMENT_VERIFIED` at `2026-10-10 21:52:55.660565 UTC`. Its full final snapshot is preserved in `phase322-final-after-regressions.json`.

The resumed focused read-only check passed at **2026-10-10 23:08:02.675332 UTC** / **2026-10-11 04:38:02.675332 Asia/Calcutta**. It compared the complete current normal database to that completed verifier, validated exact certified additions, original schema/data, authority/exposure gates, application/schema source hashes and backup identities. Evidence: `phase322-resume-final-confirmation.json`. No normal DDL or data write occurred during resume.

All four owned disposable instances were shut down and their validated workspace data directories removed. Server logs/proofs were retained. Final filesystem checks confirmed absence of:

- `tests/.medicine-owner-recovery` (port 3342).
- `tests/.medicine35-validation` (port 3325).
- `tests/.medicine38-validation` (port 3328).
- `tests/.medicine319-validation` (port 3339).

## Files and evidence

Operator/test harness files modified during the existing deployment task:

- `tests/medicine-phase319-deployment.php`.
- `tests/medicine-phase319-final-deployment.php`.
- `tests/medicine-pilot-readiness.php`.
- `tests/medicine-reviewer-evidence-mariadb.php`.

Files created:

- `tests/medicine-phase322-final-deployment.php`.
- `tests/medicine-phase322-deployed-prerequisite.php`.
- `tests/medicine-phase322-prerequisite-tests.php`.
- `docs/MEDICINE_PHASE_3_22_CONTROLLED_DEPLOYMENT_FINAL.md`.

These operator changes permit only exact certified schema additions before enforcing the full original inventory. Eight negative tests reject unexpected objects, new records, changed definitions, new trigger body/context changes and existing data/trigger changes. The MariaDB harness change only provides a separate guarded output destination, preserving original validation evidence. Production application and schema artifacts were not edited.

Protected evidence root: `D:/xampp/htdocs/kobi/storage/logs/medicine321a-owner-recovery/`. Key files are `phase322-final-preflight.json`, `phase322-final-backup.json`, `phase322-final-rehearsal-ddl.json`, `phase322-final-expected.json`, `phase322-final-normal-ddl.json`, `phase322-final-certified.json`, `phase322-final-after-regressions.json`, `phase322-final-regression-isolated.json`, `phase322-final-regression-summary.json`, `phase322-final-prerequisite-tests.json`, `phase322-final-readiness.json` and `phase322-resume-final-confirmation.json`. Individual `phase322-regression-*.out` logs retain suite output. Protected backups contain sensitive data and should remain protected.

## Limitations and final acceptance

Recovery was certified and the fresh recovery derivative verified, but no restore was necessary. This schema acceptance does not constitute real-world professional credential verification, clinical evidence approval, candidate approval or pilot/public authorization. Those operational steps remain deferred and require separate authorization.

All required deployment acceptance conditions are satisfied. No new compatibility issue or failure appeared. No additional corrective DDL, application change, deployment beyond Phase 3.22 or Git mutation occurred.

**PHASE 3.22 CONTROLLED DEPLOYMENT — PASS**
