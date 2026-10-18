# Medicine Phase 3.13 — Controlled internal pilot

Validation date: 2026-10-10. **Result: PASS WITH LIMITATIONS. Pilot status: Awaiting prerequisites; application settings remain Disabled.**

**No real pilot identities activated; pilot mechanism validated only.** All positive evidence, professional credentials, users, operational confirmations and subjects in the test suites are explicitly synthetic. Passing these tests establishes software behavior, not medical correctness or professional credential verification.

## 1. Purpose

Validate the small internal Medicine governance workflow using the existing services, repositories, shared transactions and immutable audit events. Phase 3.12 recorded candidate intake in `medicine_pilot_records`; that intake marker alone was not an operator's approval-bound enrollment. Phase 3.13 retains the marker and adds `pilot_enroll` / `pilot_disable` events to the same governance journal. It introduces no second pilot table/system, public publication, HTTP endpoint or UI.

## 2. Scope

The first real pilot requires five to ten explicitly selected, individually appraised substances/Medicine identities. No real IDs were supplied or selected. The real configuration retains empty targets, personnel and evidence sources, `pilot_enabled=false`, `pilot_internal_enabled=false`, and `pilot_activation=[]`.

Phase 3.12 intake quota and explicit substance scope remain unchanged. Actual internal availability requires a unique manifest of five to ten approved enrollments, with at least five selected substance representatives, a maximum of ten, and the matching pilot code. Chemical and composition scopes are supported. Presentations are excluded from enrollment in this phase; no chemical forms were created. Candidate intake is separate from active membership: an intake marker or an enrollment outside the activation manifest confers no internal availability.

No census or normal database connection was made. The previously documented 2,224 registry substances, 18 formatting-collision groups, 2,327 legacy combinations, 6,671 members and three private medication records were not read, rewritten, reconciled or linked in this phase.

## 3. Governance workflow

Candidate → attached identity evidence → explicit submission → qualified independent review → approval/rejection → deployment-authorized operator enrollment → explicitly gated internal availability.

Reuse `MedicineIdentityService` for explicitly allowed creation. Use existing `assessChemical`, `submit`, `verify` and `reject` commands for governance. All revision/hash, evidence appraisal, professional qualification, historical independence, dependencies and durable lifecycle checks remain enforced. A rejection remains immutable and requires a fresh permissible revision/submission/review. Invalidation requires explicit reassessment, fresh submission and fresh independent review. Withdrawn/superseded history cannot be bypassed; retirement remains terminal.

New service commands on `MedicineGovernanceService`:

- `enrollPilot(scope, targetId, input)`: requires the actual authenticated active deployment-authorized actor, existing matching intake marker, current exact approved revision/evidence/dependencies, expected revision/hash, rationale and `expected_enrollment_event_id` (string `0` for no previous operation). Locks the target and history in the shared transaction. Appends a same-revision immutable operational event referencing the actual approval event, its snapshot hash and pilot code. Records actor, UTC time, rationale, revision/state and, when delegated authority is used, the original deployment grant. No clinical revision is incremented by an operational enrollment.
- `disablePilot(scope, targetId, input)`: requires deployment authority, current target revision and expected enrollment event. Appends revocation history without deleting intake/approval. It remains available while intake/internal access is disabled or a review becomes invalid. Reenrollment requires the latest operation token; an already enrolled same revision is rejected until explicitly disabled.
- `internalPilot(scope, targetId)`: requires an actual current scoped contributor/editor/qualified reviewer. Administrator or deployment authority alone does not permit this read. Checks activation attestations, named active administrator/deployment operator/qualified reviewer and every current entry in the activation manifest. Rechecks original operator authority and its original grant when grant-based. Revoking and reissuing a different grant cannot revive the old enrollment. Returns only scope, target ID, revision, display name, `internal=true` and `published=false`.

`MedicineEligibilityService::decision` distinguishes clinical decisions from the two operational event types. It excludes only `pilot_enroll` and `pilot_disable`; adverse decisions, submissions, edits and rejection continue to displace old approvals. The existing `latest` repository method still returns the actual latest audit event. Clinical eligibility and pilot availability remain separate predicates.

Disposable validation exercised both chemical and composition creation, evidence, submission, review, operator enrollment and internal read. Negative paths preserved all old decisions and required new approval and enrollment after recovery. No real workflow was executed.

## 4. Roles

| Role | Responsibility | Real personnel available |
| --- | --- | --- |
| Medicine Administrator | Owner-authorized issuance, professional attestation, ordinary grant management | Not supplied |
| Contributor / Editor | Scoped creation, reassessment, metadata and submission | Not supplied |
| Qualified Reviewer | Actual professionally verified Doctor/Pharmacist; independent revision decision | Not supplied |
| Registry Operator | Separate explicitly authorized registry authority | Not supplied; no registry mutation operation used/added |
| Deployment Operator | Reviewed schema/code deployment, backup verification and explicit pilot enrollment/activation configuration | Not supplied |

Authority uses actual `AuthService::id()`, current active accounts and persisted scoped grants or existing owner-approved operator settings. Running a PHP test script grants no production authority. Roles retain their separate capabilities; administrator authority does not permit self-review. No real credentials/grants were created.

## 5. Evidence policy

The Phase 3.12 model remains authoritative: Tier 1 primary authoritative sources; Tier 2 recognized standards; Tier 3 established professional/clinical/pharmacological references; Tier 4 manufacturer documentation; Tier 5 secondary/general sources. Initially only appraised tiers 1–2 qualify; tiers 3–4 require explicit owner-controlled policy approval, and Tier 5 never independently qualifies.

Appraisal binds exact source record/version/locator/content digest to scope, target and current fact snapshot with a current appraisal expiry. Identity/composition evidence cannot be replaced by therapeutic/effectiveness claims. Submission is not evidence verification. No external evidence, source URL or real appraisal was supplied, fetched or verified. No website was hardcoded into application logic. Each real item must stop before authoritative approval until its actual source and record are appraised by appropriate personnel.

## 6. Pilot activation gate and deployment sequence

`pilot_enabled` authorizes bounded candidate preparation only. It does not authorize internal availability. `pilot_internal_enabled` remains false until an authorized, documented deployment action confirms all of:

- Named active Medicine Administrator and deployment-authorized activation operator.
- Named active qualified independent reviewer with current professionally verified registration/licence and scoped authority.
- Five to ten explicitly approved and unambiguous substance representatives, plus a unique five-to-ten-entry approved enrollment manifest.
- Actual appraised identity evidence and current independent approvals for each enrolled revision and its dependencies.
- Verified completed backup and verified compatible deployment, represented by nonblank accountable operational record references.
- Public Medicine publication remains disabled (`public_disabled=true` attestation plus route/search/SEO inspection).

Trusted deployment configuration shape (field names only; **no actual values supplied**):

```text
pilot_internal_enabled: false until approved activation
pilot_activation: {
  pilot_code,
  administrator_id, deployment_operator_id, reviewer_id,
  backup_reference, deployment_reference, public_disabled: true,
  enrollments: [{scope, target_id, revision, approval_event_id, enrollment_event_id}]
}
```

Role IDs are positive integers; manifest IDs/revisions are canonical positive decimal strings. The manifest is bounded and rejects duplicate targets. These settings are deployment-controlled, never request body fields. Record references are operator attestations, not automatic backup validation or a licence lookup. Every read rechecks current persisted authority, evidence, lifecycle, exact revision and enrollment, and fails closed if any manifest item is no longer current. Tests never enable the application configuration.

Concrete deployment procedure, for the real deployment operator to perform separately:

1. **Backup:** capture schema/data, code and restricted configuration under the operator's policy; verify a restore into a separate environment and record the backup reference. Confirm existing private medication/registry counts without exposing records. Confirm application/DB writer privileges and normal instance identity.
2. **Schema deployment:** inspect actual metadata first. For an installation already at Phase 3.12, apply the single reviewed CHECK extension below. For a fresh Medicine foundation, use the twelve Medicine CREATE TABLE and eight CREATE TRIGGER definitions in authoritative dependency order. For Phase 3.10, use the Phase 3.12 five-statement upgrade with the current action vocabulary. Do not import the complete dump over normal KOBI. Partial/drifted installations require operator review. MariaDB DDL has implicit commits; do not assume transactional DDL rollback.
3. **Application deployment:** deploy the matching reviewed service/policy/configuration code while both pilot flags stay disabled; preserve credentials, SQL mode, private medication behavior and Treatment HOLD.
4. **Compatibility:** compare actual columns, constraints, indexes, restrictive FKs and immutable trigger definitions with authoritative schema; confirm supported PHP/MariaDB and the existing SQL mode. Record the deployment reference.
5. **Smoke:** confirm ordinary authentication/private records and Treatment HOLD, absence of Medicine public routes/search/SEO integration, and denial of unknown/inactive/unauthorized Medicine actors. Use separate disposable negative fixtures.
6. **Governance:** manually approve the named roles, real professional credential, precise five-to-ten representatives and actual evidence. Enable only bounded intake after that review. Complete candidate/submission/independent decisions through the existing internal services; retain rejection history and stop items with unmet prerequisites.
7. **Enrollment:** actual authorized operator invokes `enrollPilot` against each exact current approved revision/snapshot and records resulting enrollment and approval IDs. No automatic registry adoption or public publication.
8. **Activation:** operator reviews the entire manifest, backup/deployment attestations and public-disabled inspection, records the configuration change, then explicitly enables internal availability. Invoke the authenticated internal service path only; no new browser/public endpoint exists in this phase. Recheck unauthorized access and minimal projection.
9. **Rollback:** immediately disable internal availability and intake; append `disablePilot` events as needed, revoke delegated grants and use steward invalidation/withdrawal when medically required. Preserve all audit/intake rows. See section 8 for code/schema rollback constraints.

### Exact Phase 3.12 → Phase 3.13 schema upgrade

Only the existing controlled action vocabulary was extended. No columns, tables, indexes, FKs or triggers changed; no other CHECK was removed or relaxed. This SQL was validated on disposable MariaDB 10.4.27, **not applied to normal KOBI**:

```sql
ALTER TABLE medicine_governance_events
 DROP CONSTRAINT chk_medicine_event_action,
 ADD CONSTRAINT chk_medicine_event_action CHECK (
  BINARY action IN (BINARY 'grant',BINARY 'revoke',BINARY 'assess',BINARY 'edit',
   BINARY 'verify',BINARY 'invalidate',BINARY 'retire',BINARY 'create',
   BINARY 'submit',BINARY 'reject',BINARY 'pilot_enroll',BINARY 'pilot_disable'));
```

Source SHA-256: `b7829ac3e1463d959386c714fdbd364a09ede97bb3c9c90549d0f1036d85f7f1`. Removing only the two new action literals reconstructs the exact Phase 3.12 hash `b5fbc3dea77772e88fbfcff58baa8ebc601a31977cea4a3d6d55a931096a7eed`, confirming all other schema text is unchanged.

## 7. Security boundaries and validation

Server-side commands enforce authentication, active user, explicit scoped authority, original reviewer qualification/independence, expected revisions/snapshot/operation tokens and current pilot scope. No caller-provided role, actor or tier overrides are accepted. Fixed scope whitelists/canonical IDs and existing parameterized repository methods remain in use. No new database-access pattern was introduced.

No controller, route, public API/page/search/sitemap/SEO change was made. Reference inspection found no controller/router use of the canonical Medicine governance/identity services. Existing substance autocomplete uses the old registry repository, not pilot enrollment, governance events or reviewer qualifications; it was not changed. Internal projections expose no credential, evidence, grant or audit-history payload. Publication actions remain denied and successful governance/enrollment returns `published=false`.

Separate registry/deployment gate failures were tested for contributors/reviewers/ordinary users. No registry mutation API exists in this phase, so a positive authorized registry write and production SQL privilege review remain deferred; application authorization does not constrain arbitrary privileged DBA SQL. Synthetic disposable registry rows remained active and unchanged by the pilot workflow. Existing private records were never connected to/read/modified; disposable private-table emptiness was checked.

CSRF/browser mutation testing is **not applicable to the new service-only commands**: no HTTP pilot endpoint was added. Any future HTTP adapter must explicitly enforce authentication, scoped authorization and CSRF; it is not implicitly approved by this validation. Treatment/authentication suites retain their existing HTTP/CSRF checks.

### Runtime suites

| Suite | Cases / checks | Assertions | Failures |
| --- | ---: | ---: | ---: |
| medicine-pilot.php | 40 | 143 | 0 |
| medicine-pilot-mariadb.php | 15 | 131 | 0 |
| medicine-policy.php | 33 | 92 | 0 |
| medicine-policy-mariadb.php | 11 | 43 | 0 |
| medicine-governance.php | 56 | 249 | 0 |
| medicine-governance-mariadb.php | 38 | 144 | 0 |
| medicine-foundation.php | 105 | 216 | 0 |
| medicine-foundation-mariadb.php | 29 | 96 | 0 |
| treatment-operations.php | 570 (127 focused + 443 retained) | Not separately counted by suite | 0 |
| auth-phase1.php | 45 | Not separately counted by suite | 0 |

Total: **942 cases/checks; 1,114 counted Medicine assertions; zero final failures.** Existing assertions were retained. The only existing test-file edit exposes Phase 3.12 disposable helpers in library mode for the new harness. Seven new/modified PHP files passed syntax checks; `git diff --check` passed. Source/reference review covered all affected services, policy/configuration and schema; repository/identity/authorization conventions were preserved.

Focused synthetic results: candidate intake denied as approval; unauthorized enrollment/read/evidence/review denied; independent qualified review accepted; qualified contributor/administrator self-review denied; rejection retained with fresh reconsideration; stale review/enrollment denied; invalidation required fresh review and operator enrollment; withdrawal remained protected; revoked reviewer/operator authority denied; new operator grant could not reactivate old enrollment; missing/retracted evidence denied; unauthorized registry/deployment/publication denied; injected IDs/client roles rejected; internal projection omitted credentials/history; journal/intake UPDATE/DELETE tampering rejected; actual audit storage fault rolled back the command.

Four two-worker independent-PDO races used a start barrier and fixed expected revision/operation tokens: two submissions, two qualified reviewer decisions, two operator enrollments, and two lifecycle transitions. Each produced exactly one success and one denial, with current final state/audit alignment. The lifecycle race concluded with protected withdrawal. Existing creation/quota, review/edit/revocation and registry-activity races also passed their retained suites.

### Disposable database execution and compatibility

All connections used guarded disposable MariaDB **10.4.27-MariaDB** instances: port 3328 with project datadir `tests/.medicine38-validation/data`, and port 3325 with `tests/.medicine35-validation/data`. The new dedicated database was `kobi_medicine_phase313_validation_20261010`; retained suites used their existing distinct fixed disposable names. Guards verify version, port, exact datadir and SQL mode before any database action and refuse to overwrite existing databases.

Each fresh validation import executed **138 statements** from authoritative `database/structure.sql`, including every non-SQL-mode schema statement. DELIMITER directives were parsed as client syntax. Actual metadata covered all twelve Medicine tables' named constraints and explicit indexes, only explained InnoDB-generated FK backing indexes, and all eight Medicine triggers. The schema object definitions were not silently filtered or repaired. The one-statement Phase 3.12 upgrade was exercised by restoring the earlier action CHECK and applying the exact current CHECK. Unknown action insertion still raised CHECK error 4025; immutable audit/intake mutations raised SIGNAL error 1644. No non-mode schema statement failed; no final MariaDB compatibility issue remains.

These three directives alone were intentionally omitted from each disposable import stream to retain the existing non-strict server mode:

```sql
/*!40101 SET SQL_MODE=''*/;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
```

The authoritative file retains those directives unchanged. Server/global, application/persistent and validation-session SQL mode were not changed. Actual mode before/after: `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`. All controlled values continue to be validated explicitly by application policy/services and database CHECKs.

Two initial focused metadata runs failed because the new test omitted the two private-link foundation tables from its inventory and then assumed MariaDB would create no implicit FK indexes. The test was corrected to inventory all twelve tables, require every declared constraint/index, and permit only extra indexes explicitly attributable to declared FKs. The complete final focused run passed; no schema/business protection or earlier assertion was weakened. A positive unit test also caught an enrollment snapshot hash encoding error during implementation; the application was corrected to use the existing binary-to-hex snapshot convention and all final tests passed.

Cleanup completed: all four disposable validation databases were dropped with absence verified; both isolated servers shut down normally; their PIDs, listening ports and project-contained datadirs were verified absent. The existing normal MariaDB listener on port 3306 remained running; it was never connected to. A final elevated read-only process/listener check confirmed cleanup after sandboxed CIM inspection was unavailable.

## 8. Rollback

Disable `pilot_internal_enabled` immediately; disable `pilot_enabled` to stop preparation. This denies internal use without deleting history. `disablePilot` appends a revocation even while configuration is paused or a subject is adversely reviewed. Revoking a grant or invalidating/withdrawing a subject blocks future use. Reenablement must satisfy current reviewer/evidence/revision conditions and an explicit current operator enrollment plus reviewed manifest. Withdrawn/superseded subjects have no restoration command in this phase.

Keep the extended action CHECK and immutable events when rolling back application code. Do not narrow the CHECK once operational events exist or delete them to make rollback pass. Pre-3.13 code does not understand operational events and conservatively denies their use; keep availability off until compatible code/manifest is revalidated. A backup restoration is a separately reviewed operator procedure requiring writer shutdown, full data/audit consistency and verified recovery; no production restoration was executed.

## 9. Current pilot state

**Awaiting prerequisites / Disabled.** Mechanism prepared and validated only. No real pilot subjects, grants, professional credential, evidence records, backup/deployment attestations or production activation were created. No connection or write to the normal KOBI database occurred. Treatment HOLD, existing registry/private records and public exposure paths remain unchanged. No commit, push, reset, checkout, merge or rebase was performed.

Files modified in this phase: `app/Config/Config.php`, `app/Services/MedicineGovernancePolicy.php`, `app/Services/MedicineGovernanceService.php`, `app/Services/MedicineEligibilityService.php`, `database/structure.sql`, `tests/medicine-policy-mariadb.php`.

Files created: `tests/medicine-pilot.php`, `tests/medicine-pilot-mariadb.php`, this document. Existing unrelated work was preserved.

## 10. Remaining prerequisites and deferred work

Actual activation requires owner-approved named administrator, contributor/editor, deployment operator and independent qualified reviewer; professionally verified registration; explicit real five-to-ten-item selection; actual source and record appraisals for every required current identity/dependency; independent approved revisions; verified backup/restore and reviewed normal-instance schema/code deployment; operator enrollment and reviewed activation manifest; confirmation that public publication remains disabled. No real facts for those prerequisites were supplied or independently verified here.

Real medical appraisal, professional credential checking, real governance/enrollment, normal-instance metadata/privilege review, backup/restore, deployment, operational smoke tests and real activation are deferred to authorized personnel. A positive registry mutation test is deferred because this phase adds/uses no registry writer. No new HTTP/browser pilot surface was requested/implemented, so new browser/CSRF execution is deferred rather than claimed as passed. There is no observed remaining technical incompatibility or failed final software test.
