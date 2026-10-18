# Medicine Phase 3.18 — Governed identity revision preparation

Result: **STOP — existing composition creation requires prior chemical approval. DRAFTS NOT PREPARED.**

This is a stop-condition report and executable database-free reproduction, not a completed draft implementation. Phase 3.18 sections 23.2 and 23.8 require stopping when the existing lifecycle cannot safely represent the requested draft or approval would be required. No lifecycle checks were weakened and no parallel persistence path was introduced.

## Objective and inspected implementation

Prepare the five fixed registry candidates as evidence-linked, revision-bound, unapproved Medicine Identity drafts for later independent professional appraisal. Inspected the authoritative `database/structure.sql`, MedicineGovernanceRepository, MedicineGovernancePolicy, MedicineAuthorizationService, MedicineIdentityService, MedicineGovernanceService, MedicineEligibilityService, MedicinePersistenceException, configuration, Phase 3.17 report/manifest, and existing Medicine tests.

## Exact blocker

1. `MedicineIdentityService::createMedicine()` calls `resolve()` before inserting an identity. `resolve()` calls `state($row, true)` and `MedicineAuthorizationService::requireCurrent('chemical', ...)` for each ingredient. A candidate chemical reference fails with safe application code **409**, `Medicine reference is unavailable or unverified.` Current verification also requires an independent reviewer decision, accepted/appraised evidence and matching current facts.
2. `loadIdentity(..., false)` also calls the same `resolve()`. Merely skipping its first invocation would not provide a safe draft workflow.
3. `MedicineGovernanceService::inspect()`, `checkedSnapshot()`, `submit()` and composition edits use the default dependency-validating `MedicineEligibilityService::snapshot()`. Composition snapshots require current verified chemical dependencies. The existing `snapshot(..., false)` is used for intake registration, but is not an authorized replacement for the default governance/current-use checks.
4. The normal configuration has `pilot_enabled=false`, `pilot_internal_enabled=false`, an empty representative list and no configured real authorities/evidence sources. Existing catalog creation and target governance call the pilot intake gate and fail with **403** while intake is disabled. `pilot_enabled` controls intake; it is distinct from internal activation. This report does not conflate intake registration with enrollment: `medicine_pilot_records` is a creation marker, while actual enrollment is an approval-bound `pilot_enroll` event. Neither was created in the normal database.

The schema can store `candidate`, null verification fields, revision metadata and immutable events. No fundamental SQL CHECK/ENUM incompatibility or requirement for a new `draft` state was established. The demonstrated limitation is the **existing service lifecycle for a composition draft with unreviewed ingredients**. Creating an identity by direct repository writes would bypass the required service boundary. Approving the five chemical references to make existing composition creation succeed would violate this phase.

## Five-candidate readiness

Registry state below is from the retained Phase 3.17 SELECT-only observation; no fresh normal-database census was performed in Phase 3.18.

| ID | Name | Retained registry state | Identity draft | Evidence | Reviewer / approval / enrollment | Chemical-form mapping |
| --- | --- | --- | --- | --- | --- | --- |
| 15 | Acetazolamide | CANDIDATE / UNKNOWN | NOT CREATED — blocked | NOT_APPRAISED; unbound | None / none / none | Pending professional review |
| 54 | Allopurinol | CANDIDATE / UNKNOWN | NOT CREATED — blocked | NOT_APPRAISED; unbound | None / none / none | Pending professional review |
| 319 | Carbamazepine | CANDIDATE / UNKNOWN | NOT CREATED — blocked | NOT_APPRAISED; unbound | None / none / none | Pending review; crystalline wording does not resolve form equivalence |
| 481 | Colchicine | CANDIDATE / UNKNOWN | NOT CREATED — blocked | NOT_APPRAISED; unbound | None / none / none | Stereochemical mapping blocker retained |
| 2064 | Topiramate | CANDIDATE / UNKNOWN | NOT CREATED — blocked | NOT_APPRAISED; unbound | None / none / none | Stereochemical mapping blocker retained |

No target IDs, revision tokens, target snapshot hashes, reviewer IDs, scientific mappings or credentials were invented. The source registry references are exactly S:15, S:54, S:319, S:481 and S:2064. No normal governed target yet exists to bind the evidence to.

## Evidence provenance and unresolved questions

Reused `docs/MEDICINE_PHASE_3_17_EVIDENCE_PACKAGE.json` and `docs/MEDICINE_PHASE_3_17_EVIDENCE_REVIEWER_READINESS.md`, unchanged. All ten retained raw-file SHA-256 digests match the manifest and proposed six-field evidence envelopes. No new evidence was fetched. Source/version/locator/retrieval date and existing digests remain in the Phase 3.17 ledger; this report does not duplicate their authority.

PubChem remains proposed Tier 1 pending qualified acceptance of applicability/sufficiency. DailyMed remains Tier 4 manufacturer/packager-origin corroboration. No source policy was changed. Captured bytes do not establish scientific approval, therapeutic effectiveness or equivalence to the UNKNOWN registry referent.

All five require qualified appraisal of exact entity mapping, proposed single-ingredient composition, active moiety, salt/free form, hydrate/solvate and other material qualifiers. Colchicine and Topiramate retain the specific stereochemical mapping blockers. No form, presentation, product, brand, user dose or combination was created. The absence of a canonical form mapping was not replaced with a guessed value.

## Implementation files

Created:

- `tests/medicine-draft-preflight.php`: CLI-only stop-condition reproducer. Reuses existing in-memory governance fixtures and actual services/policy, checks the exact five packages/digests, reproduces disabled-intake denial, and reproduces composition denial with candidate chemical references. It is not the proposed Phase 3.18 helper and creates no authoritative draft data.
- `docs/MEDICINE_PHASE_3_18_GOVERNED_IDENTITY_REVISION_PREPARATION.md`: this report.

Existing files modified: **none**. Application, routes, configuration, schema, prior tests, source packages and raw captures were left unchanged. No machine-readable draft manifest was generated because it would have no actual governed target/revision binding.

Synthetic fixture role grants/credentials and intake configuration are confined to memory, explicitly marked test-only, and reuse the existing tests. They do not establish real personnel, reviewer qualification, evidence acceptance or scientific approval. The reproducer deliberately never calls `verify()` or `enrollPilot()`.

## Validation actually executed

Runtime: **PHP 8.2.0 CLI**. No MariaDB instance was used or connected during this phase; the previous Phase 3.17 report records MariaDB 10.4.27, but this is not a fresh version verification.

| Suite | Cases/checks | Assertions | Failures |
| --- | ---: | ---: | ---: |
| New Phase 3.18 stop-condition reproducer | 11 | 116 | 0 |
| Medicine foundation | 105 | 216 | 0 |
| Medicine governance | 56 | 249 | 0 |
| Medicine policy | 33 | 92 | 0 |
| Medicine pilot | 40 | 143 | 0 |
| Phase 3.17 evidence readiness | 11 | 134 | 0 |
| Treatment operations | 570 checks (127 focused + 443 retained) | Not separately reported | 0 |
| Authentication Phase 1 | 45 database-free scenarios | Not separately reported | 0 |

The five retained Medicine suites total **245 cases / 834 assertions**. With the new reproducer: **256 cases / 950 assertions**. PHP syntax validation of the new test passed. Diff/whitespace review was performed. Passing these tests proves retained behavior and the reported stop condition, not successful Phase 3.18 draft implementation.

## Security results and deferred validation

New focused checks show both creation commands deny each exact selected ID when intake is disabled, despite synthetic contributor grants; candidate chemical references do not satisfy current review or composition creation; failed commands preserve complete in-memory registry/medication/identity/history state; source digests remain exact and target bindings remain null. No verify/enrollment event was added by the reproducer.

Passing retained suites cover authority/qualification denial, independent-review/self-approval checks, revision and registry drift, evidence target/digest mismatch, protected withdrawn/superseded history, and invalidation recovery through explicit reassessment plus fresh independent review. Existing pilot checks retain approval-bound enrollment and disabled internal/public availability. These are tests of existing services, not of a new draft helper.

The full Phase 3.18 A–X matrix and security-negative matrix are **deferred** because the requested helper/lifecycle was not implemented after the mandatory stop. Specifically, successful five-candidate composition creation, helper-specific out-of-scope enforcement/idempotency, real target/revision evidence binding, structured pending-form/reviewer status and helper-specific injection/forgery/tampering attempts were not validated. No disposable MariaDB import/mutation/concurrency test or live/browser/professional appraisal was run in this phase. No claim of SQL injection resistance through a new nonexistent helper is made.

## Database and availability safety

- Database connections: **zero** in the focused reproducer and all suites run here; no normal database SELECT, DDL, INSERT, UPDATE or DELETE was executed.
- Normal production data/schema changes: **none**. No `kobi_substances`, forms, health medication, Treatment or Disease row was touched.
- No grants, credentials, approvals, reviewer decisions, evidence submissions, enrollment or activation were persisted.
- SQL mode/server/application database configuration was not changed.
- Application configuration and routes remain unchanged: pilot intake OFF, internal activation OFF, public Medicine unavailable, Treatment HOLD unchanged. This statement is based on inspected source configuration and database-free regression tests, not a fresh live/browser observation.
- No Git commit, push, reset, checkout, merge or rebase.

## Required decision before continuing

The requested phase needs an explicitly defined **unapproved composition preparation lifecycle over unreviewed chemical candidates**, with exact provisional fact snapshots and uncertainty/evidence metadata, separate from verified dependency/current-use checks and pilot enrollment/activation. Whether that can reuse candidate/event fields without schema additions needs a follow-on design decision; no schema redesign or gate bypass was implemented here. A narrower chemical-reference-only package would not fulfill the requested five Medicine Identity composition drafts and was not substituted for the deliverable.

Final state: **DRAFTS NOT PREPARED; APPROVAL NONE; REVIEWER DECISIONS NONE; PILOT ENROLLMENT NONE; PILOT ACTIVATION OFF; PUBLIC MEDICINE OFF; TREATMENT HOLD UNCHANGED.**
