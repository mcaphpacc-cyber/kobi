# Medicine Phase 3.22 — Reviewer and evidence governance preparation

**Final result: PASS WITH LIMITATIONS.** Implementation and isolated validation completed 2026-10-11. Controlled normal schema deployment, a genuine professional verification attestation, and a separately authorized reviewer grant remain required. There is no reviewer-management HTTP UI in this phase; commands use the existing authenticated service boundary.

**NO REAL REVIEWER WAS CREATED**  
**NO REAL REVIEWER AUTHORITY WAS GRANTED**  
**NO MEDICINE CANDIDATE WAS APPROVED**  
**NO PILOT ENROLLMENT OCCURRED**  
**NO PUBLIC MEDICINE ACTIVATION OCCURRED**

## Current governance and normal database preservation

Normal target remains medical1 / port 3306 / MariaDB 10.4.27-MariaDB. Read-only before/after verification compared complete full-row counts/digests for all **54** tables, complete table/view/trigger/routine/event definitions, all **eight** existing triggers, recorded operator privileges and session/global SQL modes. Everything matched exactly. Only the existing Account 1 administrator/catalog grant with NULL expiry and its single owner-establishment event remain. No reviewer, contributor/editor, deployment or other-account authority exists.

Pilot intake OFF; internal pilot OFF; activation list empty; public Medicine OFF/unchanged. Effective Treatment publication mode remains HOLD. No normal DDL or data-write command was issued. Protected baseline: `storage/logs/medicine321a-owner-recovery/phase322-normal-baseline.json`; final comparison returned unchanged=true. No normal authentication, grant or audit mutation was simulated.

## Qualification model and privacy

The existing authority model previously validated credential JSON on a grant. A private append-only qualification journal now supplies independent state and version binding:

`medicine_reviewer_qualifications`: subject user, monotonic revision, controlled lifecycle state, private canonical JSON snapshot and SHA-256, authenticated actor, UTC timestamp. Unique (user_id, revision), subject/actor foreign keys, JSON/hash/shape/state/binding CHECKs and immutable UPDATE/DELETE triggers protect history.

Minimum credential fields: profession (doctor/pharmacist), registration identifier, issuing jurisdiction and authority, renewal_due (nullable where genuinely inapplicable), status, verified_at and verifier. Verification provenance records an attestation reference, SHA-256 and validity boundary. No uploaded credential document store was introduced.

Credential text is bounded and rejects blank values, controls, HTML delimiters and quote/semicolon injection payloads. All repository statements are parameterized. Service failures use generic governance messages, with no credential value returned in errors. Qualification lifecycle responses expose event ID, revision, state and digest only. Appraisal snapshots contain qualification event ID/hash and reviewer identity, never licence values. There are no public credential routes or audit listings. `prepareReviewerAuthority()` is a private administrator service operation; its qualification_reference contains sensitive credential JSON and must remain restricted in any future UI.

## Verification lifecycle and authorization

`recordReviewerQualification()` requires an active authenticated Medicine Administrator, locks the subject user and checks the expected latest revision. Recording or replacing credentials appends **pending** and grants no authority. Replacing an already verified credential immediately makes the old qualification pointer stale.

`decideReviewerQualification()` requires the same administrator boundary, exact expected revision and a different actor from the subject. Allowed decisions:

- pending → verified or rejected;
- verified → suspended or expired;
- rejected/suspended/expired require a fresh pending recording and fresh verification;
- there is no automatic restoration.

Verification additionally requires a **deployment-controlled** `medicine_governance.reviewer_verifications` entry matching subject, scope, exact pending snapshot SHA-256, verifier ID, valid verification date, provenance SHA-256 and current attestation validity. The normal configuration is **empty**. An administrator's request alone cannot create a matching attestation or fabricate verification. This mechanism records a completed genuine external professional verification; it does not contact licensing registries or establish professional validity automatically. The trusted configuration must be populated only after separately documented real verification and authorization.

Natural expiry is enforced at authorization time. An administrator can append the explicit expired lifecycle event once credential renewal or verification validity has elapsed. Expiry does not erase older qualification/appraisal rows. A renewed credential requires a new verified event and separately authorized new grant; the old grant never silently acquires the new context.

Existing `grantAuthority()` requires a current journal-bound qualification for reviewer grants. Legacy unbound credential JSON fails closed in production. Grant expiry remains required for delegated reviewer authority. The existing administrator, contributor/editor and deployment domains remain separate. Account 1's permanent administrator grant is unchanged and provides no reviewer qualification. A qualified reviewer receives no administrator/contributor/deployment capability through qualification.

## Exact review and evidence binding

`requireQualificationBinding()` is shared by reviewer authorization, grant issuance and current Medicine eligibility. It validates the immutable journal hash, exact event ID/hash in qualification_reference, current latest verified state, subject/verifier, credential equality, scope, renewal and attestation validity. Existing canonical verify/reject decisions now retain the redacted qualification event/hash in their immutable evidence metadata. Current eligibility compares that binding; an expired/replaced qualification cannot support new current use. Historical decisions remain stored.

Existing tiered, claim-specific `evidence_sources`, `authoritative_tiers`, source validity and appraised-record policy remain authoritative. Identity evidence still cannot satisfy therapeutic effectiveness. No real source policy was approved or reclassified. The five Phase 3.17 packages and captured files remain unchanged and provisional. Package SHA-256 remains:

`3fc38ca47fa9ace4bd8a9963854e120425909565b2a91e2c8c4ac763da454086`.

`appraiseProvisionalEvidence()` uses only the retained five registry candidates: 15 Acetazolamide, 54 Allopurinol, 319 Carbamazepine, 481 Colchicine, 2064 Topiramate. It requires an active qualified reviewer, exact draft revision, target/evidence hashes, current registry facts and the digest-verified existing capture package. Source record/version/locator/capture digest/retrieval time and deployment-configured tier/purpose/scope/configuration digest are recorded for each source. Client tier/source substitutions are not accepted.

The private `medicine_evidence_appraisals` journal binds a provisional substance/revision and monotonic decision revision to reviewer, qualification event, authority grant, immutable snapshot/hash and UTC timestamp. Foreign keys bind the exact provisional revision and qualification/subject pair. States are pending, accepted, rejected, withdrawn. Accepted requires an explicit confirmed mapping assertion and rationale from the independent qualified reviewer; rejection requires an explicit rejected mapping. The journal records the assertion and supporting source context. It does not create a canonical form/stereochemical mapping or a final Medicine identity.

`inspectProvisionalAppraisal()` exposes private redacted metadata to administrators. It reevaluates target revision/hashes, registry facts, captured sources, source policy and qualification. Changed facts/policy/qualification fail currentness. A new provisional revision returns pending until separately appraised. Accepted history never floats into another revision. It always returns candidate_approved=false, pilot_eligible=false, published=false. Appraisal is not a canonical approval bridge; the existing canonical review/submission/evidence/independence gates still apply.

Provisional withdrawn/invalidated state and protected canonical lifecycle history remain barriers. The proposal journal is not rewritten by appraisal. All authors of current or prior provisional revisions are excluded from appraisal; administrator authority does not allow reviewer impersonation. Withdrawal appends a new decision from the original currently qualified reviewer and preserves prior attribution.

The Acetazolamide/Allopurinol exact identity/form blockers, Carbamazepine material-form equivalence blocker, and Colchicine/Topiramate stereochemical blockers remain unresolved in normal KOBI. No credential, mapping or professional appraisal was invented. Synthetic tests exercised mechanics using explicitly marked synthetic identities, credentials and attestations; they produced no real scientific conclusions or changes to package classification.

## Audit and concurrency

Qualification and appraisal operations append immutable records in the existing repository transaction convention. Four new triggers reject journal UPDATE/DELETE with SQLSTATE 45000. Qualification payload hash/subject/status/profession and appraisal target/revision/reviewer/qualification/purpose bindings are CHECK-protected. No existing FK, CHECK, trigger or index was removed or weakened.

Locking the subject user serializes qualification creation/replacement and reviewer decisions, including an absent first entry. Expected revision and unique keys reject stale duplicates. Appraisal locks reviewer/qualification before registry/provisional journal. Administrator currentness inspection obtains an unlocked reviewer hint, locks that reviewer first, then locks and revalidates the target/journal; this avoids reversing the appraisal lock order. Time-based reviewer expiry is rechecked immediately before recording.

Disposable two-process races proved:

- duplicate qualification recording: one success, one 409; exactly one first journal row;
- replacement versus appraisal: the appraisal either committed before replacement with the historical qualification or was denied 403 afterward; new use of the replaced qualification was denied. No stale qualification decision or lost update occurred.

## Schema and disposable MariaDB validation

Created `database/medicine-phase322-reviewer-evidence.sql`; the same additive suffix is in authoritative `database/structure.sql`. Artifact SHA-256:

`c7796b079be60b19931969641907e45ce9e6b4156bf0f825c99ef89696bf5dc9`.

Exactly **six statements**: two CREATE TABLE and four CREATE TRIGGER. No seeds, global/session mode directives or normal deployment action are included. Exact DDL was validated on MariaDB 10.4.27 in `kobi_medicine_phase322_validation_20261011`, guarded by port 3342 and the project-local disposable datadir. Existing schema objects matched before/after; the only additions were the two journals and four triggers.

The normal database currently lacks the Phase 3.19 `medicine_provisional_identity_revisions` dependency. A later separately authorized controlled deployment must establish that approved prerequisite before applying Phase 3.22. Do not run this artifact against the present normal schema or improvise away its foreign key. Neither predecessor nor Phase 3.22 was deployed here.

The isolated base import executed **142** statements; the additive artifact executed **six**, with zero warnings and zero omissions from the additive artifact. Only these three existing structure.sql mode directives were intentionally excluded from the isolated base import:

```sql
/*!40101 SET SQL_MODE=''*/;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
```

This tests all schema under `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`. No other schema statement was omitted. The retained protected-bootstrap suite imported all **148** schema statements under the same mode. Normal and global/persistent KOBI SQL mode were not changed.

Disposable databases were removed. The port-3342 process identity and exit were verified; its diagnostic log was archived and its checked project-local datadir removed. Evidence: `storage/logs/medicine321a-owner-recovery/phase322-isolated.json` and `fidelity-server-9c069187-2f94-42b4-a4d8-91fc72b1c615.err`.

## Actual tests

| Suite | Cases | Assertions | Failures |
|---|---:|---:|---:|
| New reviewer/evidence DB-free | 57 | 170 | 0 |
| New reviewer/evidence MariaDB | 7 | 26 | 0 |
| Retained Medicine governance | 56 | 249 | 0 |
| Retained Medicine policy | 33 | 92 | 0 |
| Retained Medicine foundation | 105 | 216 | 0 |
| Retained provisional identity | 43 | 240 | 0 |
| Retained evidence readiness (`test`) | 11 | 134 | 0 |
| Retained owner authority bootstrap | 27 | 116 | 0 |
| Retained protected owner entry | 40 | 82 | 0 |
| Retained protected entry MariaDB | 6 | 44 | 0 |
| Treatment administration hardening | 39 focused | Not reported | 0 |

No totals are inflated with overlapping embedded suites. The evidence-readiness command initially lacked its required `test` argument and exited before testing; it was rerun correctly and passed. No focused behavioral test failed.

New negatives cover absent/inactive authority, ordinary/contributor/reviewer management denial, self-verification, another-person qualification, reviewer administrator/deployment grants, incomplete/unsupported/malicious credentials, invalid state/date/attestation/hash/subject/verifier, licence expiry, replacement/mutation, stale revision/target/source-policy/digests, unknown or disallowed source/tier, identity-only purpose, unqualified/admin/self appraisal, terminal provisional states, no floating decision, private outputs and journal rollback. SQL tests cover exact metadata, immutable UPDATE/DELETE, non-strict CHECK/FK rejection, qualification duplicates, replacement/review race, historical retention and absence of canonical/pilot effects.

PHP lint passed for all **11** changed/created PHP files. Diff whitespace check passed; repository/service/reference changes were reviewed. Existing synthetic fixture policies explicitly override the new binding only in legacy tests so their original policy-specific scenarios remain isolated; new Phase 3.22 tests exercise production qualification/binding policy without that override. The protected-bootstrap schema pin was updated to the exact new additive structure hash.

Deferred: real licence verification; normal schema deployment; real reviewer creation/grant; real source-policy approval/appraisal; real multi-account browser workflow; broader unrelated MariaDB suites. Shared authentication/controller code was not changed, so no new Authentication suite run was required or claimed.

## Files

Modified:

- `app/Config/Config.php` — empty protected verification configuration.
- `app/Repositories/MedicineGovernanceRepository.php` — journal persistence and lock/read primitives.
- `app/Services/MedicineGovernancePolicy.php` — provenance, current qualification and source configuration binding.
- `app/Services/MedicineGovernanceService.php` — qualification lifecycle, private preparation/currentness and provisional appraisal.
- `app/Services/MedicineAuthorizationService.php` — journal-bound reviewer gate.
- `app/Services/MedicineEligibilityService.php` — exact historical decision qualification binding.
- `database/structure.sql` — exact additive schema suffix.
- `tests/medicine-governance.php`, `tests/medicine-policy.php` — explicit legacy synthetic fixture isolation.
- `tests/medicine-owner-bootstrap-web-mariadb.php` — current authoritative schema hash pin.

Created:

- `database/medicine-phase322-reviewer-evidence.sql`.
- `tests/medicine-reviewer-evidence.php`.
- `tests/medicine-reviewer-evidence-mariadb.php`.
- `docs/MEDICINE_PHASE_3_22_REVIEWER_EVIDENCE_GOVERNANCE.md`.
- Protected normal baseline, isolated proof and archived diagnostic log named above; disposable temporary worker files were removed.

## Next legitimate prerequisite and limitations

Obtain a genuine Doctor/Pharmacist identity and verify an actual licence/registration with its issuing authority. Record only the minimum private governance facts, real verification provenance/date and applicable renewal boundary. After separate deployment authorization and backup/recovery checks establish the required schema, an authenticated administrator can record pending credentials. An independently documented and explicitly authorized deployment attestation must bind that exact pending digest and verifier. Verification alone grants nothing; a reviewer authority grant is a separate explicitly authorized action with expiry. No grant should be inferred from Account 1, an administrator or any KOBI role.

The implementation is service-based; no credential browser form, upload handler or public/private HTTP audit listing was added. Any later UI must preserve CSRF, authentication/administrator checks, private output and error conventions. Captured scientific evidence and five candidate blockers still require actual professional appraisal. This phase supplies auditable preparation mechanics and does not settle scientific correctness.

**PASS WITH LIMITATIONS:** safe mechanism implemented and validated; later controlled schema deployment and genuine professional/evidence decisions remain mandatory. No Git state was mutated: no commit, push, reset, checkout, merge or rebase. Existing unrelated workspace changes were preserved.
