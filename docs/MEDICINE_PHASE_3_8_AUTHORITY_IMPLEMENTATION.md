# Medicine Phase 3.8 — Persisted authority implementation

Date: 2026-10-09. Result: **PASS WITH LIMITATIONS**. Implementation readiness: **READY WITH OPEN DESIGN QUESTIONS**.

Production shared Medicine creation/publication remains disabled. Synthetic runtime validation does not establish actual registry facts or approve production activation.

## 1. Phase objective

Implement persisted scoped authority, qualified independent review, immutable revision-bound history, explicit reconciliation and current-use invalidation justified by Phase 3.7. Preserve the exact canonicalizer, existing repository transactions, seven foundation tables, private medication boundary and Treatment HOLD.

## 2. Phase 3.7 carry-forward findings

Reviewed Phase 3.1–3.7 documents and actual schema/services before implementation. Profile/Treatment grants cannot represent Medicine authority; verification tuples cannot reconstruct decisions/all contributors; registry activity/name flags cannot establish equivalence. Before schema edits this document recorded the missing grant/history/current-assessment persistence and why existing fields were insufficient. Bootstrap administration, credential authority, sources and real registry representatives remain human policy questions.

## 3. Implemented authority model

`MedicineAuthorizationService::requireGovernance()` reads current persisted grants/users through `MedicineGovernanceRepository`. It requires exact capability/scope, active unrevoked grant and canonical current UTC validity dates. `MedicineGovernanceService` takes the actual actor from `AuthService::id()`; submitted IDs cannot impersonate that actor. `grantAuthority()`/`revokeAuthority()` atomically persist attribution/history; self-granting is rejected. Concrete DI resolves the new services with the existing shared Database. No new permission framework or container configuration.

## 4. Authority roles/scopes

| Capability | Controlled operation |
| --- | --- |
| contributor | Explicit chemical candidate/unresolved assessment; internal inspection |
| editor | Composition/presentation display metadata edit, invalidating verification |
| reviewer | Independent evidence-supported chemical/composition/presentation review |
| steward | Adverse decision or retirement with rationale |
| publisher | Persisted separately; no publication permission or operation activated |

Scopes: `chemical`, `composition`, `presentation`, explicit `catalog`. Catalog scope covers controlled domain actions, never shared creation/publication. Unknown actions/scopes deny. Grants are expiring/revocable; nullable generated uniqueness retains revoked rows while allowing later separately audited issuance.

## 5. Reviewer qualification

Reviewer grants require qualification reference, assessing actor and competence scope. Current eligibility rechecks the original reviewer's user/grant, dates, revocation and qualification. `MedicineGovernancePolicy::requireAdministrator()`, `requireQualification()` and `requireEvidence()` deliberately deny in production until respective policies are approved. Ordinary admin/profile/Treatment status is insufficient. No real personnel, credentials or evidence sources were invented or seeded. Tests inject `MedicineSyntheticPolicy` accepting explicitly synthetic credentials/evidence; production DI uses the closed policy.

## 6. Independence rules

Reviewers cannot review their own created target or a target they assessed/edited anywhere in its recorded history. `verify()` checks all recorded contributors; `MedicineEligibilityService::requireCurrent()` rechecks independence against history. Recorded `independent=1` alone is insufficient. Qualification/administration cannot bypass these checks.

## 7. Review lifecycle

An explicit chemical reference receives contributor candidate/unresolved assessment. Candidate or existing verified assessment can receive a fresh independent evidence-backed decision; unresolved/adverse assessment cannot be reviewed directly. Verification increments revision, appends history, updates verification tuple and leaves `published=false`. Fresh review may supersede current evidence with a new event; old proof remains and dependent revision snapshots become stale.

Composition review requires all current chemical dependencies. Presentation additionally requires current composition and exact presentation/strength consistency. Metadata edit moves the target to candidate and clears verification. Invalidated chemical refs need explicit reassessment before review. Withdrawn/superseded targets cannot be restored through assessment/edit/review without approved policy; retirement is terminal. Identity-defining ingredient/coefficient/form/route/release/strength/key mutations are not exposed; replacement policy remains deferred.

## 8. Revision binding

Commands require matching `expected_revision` and, except adverse decisions, `expected_snapshot_hash` against locked state. Review binds full key, display metadata, registry facts, chemical assessment and dependency revisions. Current reads require latest verify event, matching revision/actor/reference and exact digest/serialized snapshot. Edits, changed evidence/dependency revisions and registry drift invalidate current approval. Existing new-use `resolve()`, `loadIdentity()` and `loadPresentation()` consume this gate; canonical normalization/hash/ratio protections remain intact.

## 9. Audit/history model

`medicine_governance_events` records exactly one typed subject, actor/grant, action, revision, prior/new state, rationale, evidence, bounded deterministic snapshot/digest, independence and UTC microsecond timestamp. Restrictive FKs retain referenced subjects. Repository exposes append/read only; UPDATE/DELETE database triggers reject direct SQL mutations. Every grant/revoke/assess/edit/verify/invalidate/retire command writes history and current state in one transaction. Historical proof remains when current use is denied. Privileged database administrators with DDL rights remain outside normal application immutability guarantees.

## 10. Registry reconciliation model

`assessChemical()` consumes an existing explicit S/F adapter, never name matching. Optional `candidate_identity_id` must contain that adapter. `medicine_registry_assessments` stores the current reference/candidate/state/revision, registry digest/snapshot, decision event and actor/time. Events retain immutable decisions. Production governance only reads/locks underlying registry/form rows; complete observed facts are conservatively fingerprinted. No registry row is automatically promoted, rewritten, merged or linked.

## 11. Duplicate/ambiguity handling

Insufficient evidence can be explicitly recorded `unresolved`, blocking review/current use. Exact/normalized names, salt/base, stereo variants, combinations and historical labels are not equivalence algorithms. Tests show unresolved exact/normalized/salt-base cases cannot verify themselves and do not mutate registry rows. These tests do not discover actual duplicates. Representative selection/S-F overlap policy remains unresolved; closed production evidence/qualification policies prevent unsupported real approvals. No automatic classifier/population job.

## 12. Chemical-form handling

S and F IDs remain distinct. F snapshots include explicit form and active parent. Each form requires its own reviewed adapter; parent approval cannot substitute. Parent inactivity/drift blocks current form use. Names never establish salt/base, hydrate/solvate, stereochemical or molecular equivalence. Real relationship evidence acceptance is deferred policy.

## 13. Invalidation model

`invalidate()` requires current steward authority, expected revision and rationale. Exact allowed states: `invalidated`, `withdrawn`, `superseded`, `retired`. It still works after registry/dependency loss. It appends history/increments revision, preserves retirement attribution for retired targets and leaves other targets noneligible candidates with adverse history/assessment. Dependent new-use reads recursively reject lost approval, inactive registry/parent, revoked reviewer or changed facts. Existing links/decisions are retained, with no destructive cascade or silent replacement. Supersession records an adverse state, not an invented replacement ID. Restoration/replacement policy remains closed.

## 14. Private medication boundary

Existing profile accessibility/actual actor checks remain. Medicine grants confer no profile access. Historical association reads retain original IDs without asserting current validity; legacy text/selected IDs are not migrated or auto-linked. Isolated selection/invalidation tests preserve raw medication and prior link while blocking withdrawn new use. No private UI/controller/routes or health-table definitions changed. No normal private data was accessed by validation.

## 15. Public exposure boundary

`requireCatalog()` still always denies shared creation/publication even with persisted controlled grants. Governance has no publish method and successful review returns `published=false`. No endpoints/public projections/UI/SEO added. Existing public substance search remains its preexisting registry output and receives no governed Medicine projection. Treatment code/routes/configuration and HOLD were untouched.

## 16. Transaction/concurrency model

The governance repository extends `MedicineRepository`, reusing shared PDO/BaseRepository binding and bounded whole-transaction retry for duplicate/deadlock errors. Current actors/grants/targets/registry/assessment/latest decisions are locked as appropriate; no partial events survive rollback. Five live races used two independent PHP/PDO processes and a barrier:

1. Two editors, same composition revision: one winner, stale competitor denied.
2. Review versus content edit: accepted review matches final revision, or edit wins and eligibility denies.
3. Grant revocation versus edit: lawful before/after ordering; subsequent authority denied.
4. Invalidation versus fresh chemical review: one revision winner; withdrawal denies current use if it wins.
5. Duplicate unresolved reconciliation: one winner, stale competitor denied, registry row count unchanged.

Stale approval submission is also tested sequentially. Assertions vary with lawful race winners; fixed live case count is 29. These scenarios are not a general load/performance certification.

## 17. Security/fail-closed behavior

Negative tests deny missing/wrong/unknown/revoked/inactive/expired grants, inactive user, missing qualification, self/all-contributor review, missing/therapeutic evidence, unresolved/inactive/withdrawn targets, stale revisions/digests and unsupported state values. Service codes are strict allowlists independent of non-strict coercion; binary CHECKs provide backstops. Command PDO failures return safe 503; unexpected failures safe 500; genuine denial/staleness 403/409; malformed input 422. Injected audit faults roll back grant/review/current assessment/history. No new HTTP handlers were added; future separately authorized controllers must supply auth/CSRF. No internal diagnostics reach normal users.

## 18. Schema changes

Added three InnoDB tables and two triggers. Existing seven Medicine tables, registry definitions and all other constraints/mode directives remain preserved. No normal DDL applied. Required deployment SQL is the **exact additive block** from the `Medicine Phase 3.8` comment through `DELIMITER ;` in [structure.sql](D:/xampp/htdocs/kobi/database/structure.sql:1319): three CREATE TABLE and two CREATE TRIGGER statements. Apply that block separately only after human deployment approval; do not import the complete dump over an existing database.

| Table | CHECKs | FKs | Required indexes |
| --- | ---: | ---: | --- |
| medicine_authority_grants | 5 | 4 | PK, active-grant unique, actor/activity/expiry |
| medicine_governance_events | 5 | 6 | PK, four subject/history indexes |
| medicine_registry_assessments | 2 | 4 | Reference PK, state/reference index |

Actual metadata matched all named constraints/required indexes. All FK columns have leading supporting indexes; MariaDB's additional implicit FK indexes are legitimate. Invalid code/boolean/qualification/XOR/assessment writes failed CHECKs (4025), invalid actor/event references failed FKs (1452). Exact trigger DDL:

```sql
DELIMITER $$
CREATE TRIGGER `medicine_events_no_update` BEFORE UPDATE ON `medicine_governance_events` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine history is immutable';
END$$
CREATE TRIGGER `medicine_events_no_delete` BEFORE DELETE ON `medicine_governance_events` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine history is immutable';
END$$
DELIMITER ;
```

UPDATE/DELETE attempts failed native 1644. Existing Treatment triggers were imported unchanged. No non-mode schema statement failed compatibility validation.

## 19. Registry census results

**Live registry census unavailable.** Normal local MariaDB/listeners were unavailable at the initial gate; no normal connection or census SELECT was performed. The later disposable server is not the live registry. All actual counts remain **not measured**: total/active/searchable distributions; identity status/type/naming standard; reviewed/unreviewed; verification-source/reviewer presence; exact/normalized duplicates; suspicious collisions/ambiguities; inactive searchable rows; unusual codes; candidates requiring review. No zero-duplicate claim or actual chemical representative inference. No normal registry/private record writes.

## 20. Test coverage

| Suite actually run | Cases/checks | Assertions | Result |
| --- | ---: | ---: | --- |
| tests/medicine-governance.php | 46 | 125 | PASS |
| tests/medicine-governance-mariadb.php | 29 | 87 final run | PASS |
| tests/medicine-foundation.php | 105 | 216 | PASS |
| tests/treatment-operations.php | 127 focused + 443 retained = 570 | Not separately counted | PASS |
| tests/auth-phase1.php | 45 | Not separately counted | PASS |

Total **795 cases/checks**, zero failures; new governance **75 cases**. Old foundation fixtures deliberately retain synthetic structural authority; new tests exercise persisted authority with synthetic policy. Prior Phase 3.5 live results are not included: its old live suite was not rerun. No browser validation performed because no UI/HTTP surface changed.

## 21. Validation results

All 10 new/modified PHP files passed PHP 8.2 syntax checks. Reference/source review confirmed DI, current-gate integrations and no publication route/method. `git diff --check` passed. Baseline hashes verify the declared five existing files changed/seven files added while prior user changes remain intact.

Disposable **MariaDB 10.4.27**, TCP **3328**, database **kobi_medicine_phase38_validation_20261009**, isolated project-local datadir/redo/tmp/log paths. Harness guards exact version/port/datadir/mode before run/drop/worker. No application configuration/credentials were used. Synthetic users/grants/registry/form/adapters/Medicine/private fixture/events were written only there; synthetic semantic-drift UPDATE was rolled back. No normal KOBI DDL/data writes.

Final import executed **130 SQL statements**, including all tables/indexes/FKs/CHECKs/views/triggers/other source statements. DELIMITER is a client parser control. Only these three exact mode directives were omitted from the transient stream to preserve observed mode:

```sql
/*!40101 SET SQL_MODE=''*/;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
```

All three remain in source. No other schema statements were silently omitted; non-mode failures stop import. Mode before/after: **NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION**. No global/session/persistent application mode change. Schema SHA-256: `7c74c0eacc6815ab59f6e108e4786cc760c79505b6f5c81fa45213ac8ad59761`.

After validation the disposable DB is dropped/absence verified, its dedicated server shut down and the new temporary directory removed. No commit/push/reset/checkout/merge/rebase.

Files created (relative to `D:/xampp/htdocs/kobi`):

- app/Repositories/MedicineGovernanceRepository.php
- app/Services/MedicineGovernancePolicy.php
- app/Services/MedicineEligibilityService.php
- app/Services/MedicineGovernanceService.php
- tests/medicine-governance.php
- tests/medicine-governance-mariadb.php
- docs/MEDICINE_PHASE_3_8_AUTHORITY_IMPLEMENTATION.md

Files modified:

- database/structure.sql
- app/Services/MedicineAuthorizationService.php
- app/Services/MedicineIdentityService.php
- tests/medicine-foundation.php
- tests/medicine-foundation-mariadb.php

## 22. Remaining limitations

Genuine blockers to real activation: unavailable live census; unapproved bootstrap administration, reviewer credential/competence/conflict policy, accepted identity evidence/freshness/withdrawal and representative/chemical-relationship policy. Production policy denies corresponding actions. Normal deployment/first issuance has not occurred. These block activation, not completion of this controlled infrastructure phase.

Non-blocking limitations: synthetic evidence cannot certify real facts; full-row fingerprints may require unnecessary re-review; privileged administrators exceed audit guarantees; external/manual registry writers require coordinated invalidation; replacement/restore policy remains deferred with withdrawn/superseded restoration closed. UI/publication/browser/load/bulk import/legacy linking and unrelated Phase 3.1 fixes remain out of scope.

## 23. Phase 3.9 recommendation

Perform the real SELECT-only census and explicitly approve bootstrap administrator, reviewer qualification and identity-evidence/representative policies. Review additive deployment SQL and registry-writer invalidation before tightly limited controlled activation. Retain shared creation/publication denial, private boundaries and Treatment HOLD. Do not start automatic population/merging/UI/publication based on synthetic validation.
