# Medicine Phase 3.23 — Controlled reviewer/evidence schema deployment

**Final result: BLOCKED — stopped before normal DDL.** Read-only preflight observed 2026-10-10 19:57:49.674661 UTC (2026-10-11 01:27:49.674661 Asia/Calcutta).

## Exact blocker and authorization boundary

The identified and unchanged Phase 3.22 artifact is `database/medicine-phase322-reviewer-evidence.sql`, SHA-256 **c7796b079be60b19931969641907e45ce9e6b4156bf0f825c99ef89696bf5dc9**. It matches the tested additive suffix of structure.sql and the retained Phase 3.22 validated schema proof.

Its `medicine_evidence_appraisals` table includes:

```sql
CONSTRAINT `fk_medicine_appraisal_target`
 FOREIGN KEY (`substance_id`,`provisional_revision`)
 REFERENCES `medicine_provisional_identity_revisions` (`substance_id`,`revision`)
 ON DELETE RESTRICT ON UPDATE RESTRICT
```

Fresh normal metadata confirms **medicine_provisional_identity_revisions is absent**. The Phase 3.22 report already documents this dependency and the need for its separate controlled deployment. Phase 3.22 disposable validation included that prerequisite through the complete base structure import; the present normal database does not contain it.

The user authorizes **ONLY Phase 3.22 additive reviewer/evidence schema**. Establishing the Phase 3.19 predecessor is outside that scope. Applying the six-statement artifact now would risk a partial deployment before the dependent appraisal table can be created. No statement was attempted, no foreign key was removed, no schema was edited to reconcile the mismatch and no production repair was improvised. This is a missing deployment prerequisite, not a newly observed syntax incompatibility or an executed MariaDB error.

The existing prerequisite artifact is `database/medicine-phase319-provisional.sql`, SHA-256 **84166a59636a8eefcf580bc8bc71b09c19951255a865d2b8a8415ba62a41be48**. It was located and its table declaration reviewed; it was not applied or revalidated in this stopped attempt. The next step requires separate explicit authorization for a controlled prerequisite deployment, followed by a fresh authorized migration preflight/recovery checkpoint and exact-artifact verification. No draft or real reviewer should be created by that schema-only work.

## Pre-deployment checks actually performed

- Normal database: **medical1**, port **3306**.
- MariaDB: **10.4.27-MariaDB**.
- Session/global SQL mode: **NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION**.
- Client, connection, results and server character set: **utf8mb4**.
- Connection/server collation: **utf8mb4_general_ci**.
- All **54** existing table inventories, full row counts/digests and complete table/view/trigger/routine/event metadata exactly match the retained Phase 3.22 normal baseline.
- All **eight** existing trigger definitions remain identical.
- Recorded operator privileges SHA-256 unchanged: **ed56afa71d6afce3f87a6a27aa9ff47ad3060e29119e9adab128fbf7d04025a4**.
- Account 1 exists and is active. Exactly one grant remains: user/grantor **1**, active **administrator/catalog**, **NULL expiry**, no revocation.
- The sole owner-establishment event and grant data match the preserved baseline; owner designation is intact.
- Reviewer, contributor/editor, deployment, registry, other-account and delegated administrator authority: **zero**.
- Canonical Medicine chemical references/identities and pilot records: **zero**.
- Reviewer qualifications and appraisals: no tables/records deployed; no credentials or reviewer decisions created.
- Pilot/internal-pilot flags OFF; activation list empty; evidence-source and professional-verification configurations empty; effective Treatment publication mode **hold**.

Fresh read-only candidate query found the exact five active CANDIDATE/UNKNOWN registry entries:

| substance_id | Candidate |
|---:|---|
| 15 | Acetazolamide |
| 54 | Allopurinol |
| 319 | Carbamazepine |
| 481 | Colchicine |
| 2064 | Topiramate |

Their full table data remain identical to baseline. No final identity, approval, review, enrollment or publication was introduced. Personal medication/combination, disease, Treatment and governance data are covered by the full 54-table digest comparison.

Protected complete evidence: `storage/logs/medicine321a-owner-recovery/phase323-preflight.json`. It contains the fresh inventory/digests/schema/privileges/character state, candidate observations, artifact/backup identities and explicit missing dependency. The verifier exits **1** with BLOCKED_MISSING_SCHEMA_DEPENDENCY. SELECT/metadata statements were executed; **migration statements executed = 0**, normal data writes = 0.

## Backup and recovery identity

The authoritative path was located from the Phase 3.21A deployment documentation rather than assumed:

`D:\xampp\htdocs\kobi\storage\logs\medicine321a-owner-recovery\before.sql`.

Fresh existence/readability/size/SHA verification passed:

- **5,352,130 bytes**.
- SHA-256 **f303f482ba49931db370a33b833bf95e5106e3c20de21d47ce3bfc4c78108cf2**.

The original backup was not modified, replaced, normalized or rewritten. See [the established exact recovery procedure](MEDICINE_PHASE_3_21A_RECOVERY_VERIFICATION_CORRECTION.md) and [completed owner deployment](MEDICINE_PHASE_3_21A_OWNER_DEPLOYMENT.md). Two independent restores are retained historical evidence; they were not rerun here. The original backup predates the owner migration/bootstrap. A resumed deployment needs a separately verified fresh checkpoint covering current owner authority; the original remains the authoritative historical recovery baseline, not a substitute for that current checkpoint. No additional backup was created after the missing-dependency stop.

## Migration and post-deployment results

Migration: **NOT ATTEMPTED**. No migration start/end or migration warnings/errors apply. The six intended statements remain two CREATE TABLE and four immutable-journal CREATE TRIGGER statements in the exact artifact. Zero were executed. No other artifact was applied and no partial schema was created.

Post-deployment new-object/schema verification and deployed-schema regression are **deferred**, because deployment did not occur. Account 1 authority, all existing data/schema/triggers, privileges, SQL modes and feature state were verified read-only as described above. Public Medicine remains OFF/unchanged; no route, configuration or exposure change occurred. No new public projections or credential listings were introduced.

## Tests and files

Performed this phase: artifact/structure/test-proof inspection, exact artifact hash check, documented backup identity verification, guarded live read-only preflight, full preservation comparison, authority/feature/candidate checks, PHP lint for the new verifier and diff whitespace check. Preflight correctly stopped on the absent dependency. No behavioral/security test was represented as newly passed after that stop.

Focused, MariaDB, Medicine, Authentication, Treatment and broader post-deployment regression execution is **deferred** under the critical stop instruction. Retained Phase 3.22 results (57 cases/170 assertions; seven MariaDB cases/26 assertions and recorded regressions) are historical evidence only and are not inflated as this phase's test counts. No synthetic fixture or state-changing suite was run on the normal database. No isolated server was started in this phase.

Created:

- `tests/medicine-phase322-deployment.php` — CLI-only **read-only preflight**, reusing existing guarded connection/digest/metadata/evidence functions. It has no DDL/data-write path, grant mechanism or actor substitution. It currently refuses the missing prerequisite and compares against the retained 54-table pre-deployment baseline; it must be extended/revalidated for any later separately authorized prerequisite/deployment sequence.
- `docs/MEDICINE_PHASE_3_23_REVIEWER_EVIDENCE_SCHEMA_DEPLOYMENT.md`.
- Protected `storage/logs/medicine321a-owner-recovery/phase323-preflight.json`.

No existing application code, configuration, schema artifact, source package or database object was modified. No Git-state mutation occurred.

## Explicit final state

**NO REAL REVIEWER CREATED**  
**NO REVIEWER AUTHORITY GRANTED**  
**NO PROFESSIONAL CREDENTIAL VERIFIED**  
**NO MEDICINE CANDIDATE APPROVED**  
**NO PILOT ENROLLMENT**  
**NO PUBLIC MEDICINE ACTIVATION**  
**NO TREATMENT CHANGE**

**BLOCKED:** the exact Phase 3.22 schema cannot be deployed against the present normal schema within the authorized Phase 3.22-only scope. Resolve the separately authorized Phase 3.19 schema dependency before resuming controlled Phase 3.22 deployment.
