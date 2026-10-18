# Medicine Phase 3.12 — Governance policy enforcement

Implementation/validation: 2026-10-09; closeout: 2026-10-10. Result: **PASS WITH LIMITATIONS**. Deployment readiness: **ready with prerequisites**, not activated or production-ready.

## 1. Governance roles

Separate capabilities use the existing `medicine_authority_grants` and immutable grant/revocation events: contributor, editor, reviewer, steward, publisher, administrator, registry and deployment. The stored `registry` capability denotes a registry-authorized operator; it is not an editorial grant. Operator grants require catalog scope. Reviewer authority never implies administration, registry or deployment authority. No real personnel or grants were seeded.

## 2. Administrator policy

Owner-approved positive IDs are supplied through `medicine_governance.owner_user_ids`, initially empty; no person's ID is hardcoded in application logic. Actual active actors come from AuthService::id in commands. Owners can explicitly delegate/revoke persisted administrator, registry and deployment grants. Delegated administrators can manage ordinary Medicine grants but cannot issue/revoke operator grants; only approved owners can do that. Self-granting remains rejected. Grant/revocation attribution, qualification and rationale remain transaction-bound and historically retained.

Policy configuration uses the existing `config()` architecture, with a new Medicine-only section. Configuration changes remain accountable owner-approved deployment actions; this phase adds no configuration editor/CMS or deployment execution tooling. Bootstrap owner/configuration membership changes require the operator's external change record. Persisted delegated grants are auditable/revocable through the existing service. Ordinary user/profile/Treatment roles do not bootstrap administration. Empty lists deny access.

## 3. Contributor policy

Creation is permitted only through the separately enabled bounded internal pilot and a current contributor grant for the operation's scope. Default pilot_enabled=false. Editor grants authorize allowed composition/presentation metadata edits; identity-defining keys remain immutable. New targets receive an immutable `create` event and persistent pilot enrollment in the same transaction. `submit()` attaches an identity evidence package to a candidate revision with actual contributor/editor attribution and increments revision; it does not approve it. Secondary/unknown issuer identity material can be attached to a draft but cannot meet authoritative review policy.

Production review requires the latest exact revision to have a `submit` event. `reject()` requires a qualified independent reviewer, appends a revision-bound unresolved decision and preserves history. Subsequent chemical review needs explicit candidate reassessment and a new submission. No new HTTP/UI/private medication integration exists.

## 4. Reviewer qualification

Reuse existing qualification_reference TEXT as a strictly validated JSON credential envelope; qualification_assessed_by/scope and immutable issuance snapshot remain authoritative attribution. No duplicate credential table is needed. Required fields: profession (`doctor` or `pharmacist`), registration identifier, explicit jurisdiction, issuing authority, status=`verified`, canonical UTC microsecond verified_at, nullable renewal_due, and verifier matching the actual granting/assessing actor. Required text is bounded/nonblank. Verification cannot be future-dated; renewal, when applicable, must remain current and bound grant expiry. Grant/user activation, dates, revocation and scope are checked separately on every review/current-use decision.

An authorized administrator must professionally verify the registration before attesting it. This implementation records/enforces that attestation, not an automated licence lookup or claim that real credentials were checked. No country's licensing system is hardcoded. Suspending/revoking a reviewer grant immediately denies subsequent governed use while preserving credential/decision history. Renewal requires separately audited reissuance after revocation. Legacy unstructured qualification text cannot qualify under v1 production policy. No public response exposes registration details; no public Medicine response/endpoint was added.

## 5. Reviewer independence

One qualified independent reviewer suffices. Creators and anyone recorded as creating, assessing, editing or submitting the target cannot approve/reject their own work. Current eligibility rechecks reviewer grant/user/qualification, historical independence and exact revision/snapshot. Administrator status is not medical-review authority. Existing expected-revision/hash, transaction locking/retry and dependency recursion remain intact.

## 6. Evidence hierarchy

**Medicine Governance v1 proposed/initial policy**, implementing the supplied initial hierarchy:

| Tier | Categories | Initial authoritative acceptance |
| --- | --- | --- |
| 1 | Official regulator/government registries, product information, official pharmacopoeias/regulatory publications | Permitted after source AND record appraisal |
| 2 | Recognized pharmacopoeias, pharmaceutical/professional standards | Permitted after source AND record appraisal |
| 3 | Established professional/clinical/pharmacological references | Disabled initially; configurable after appraisal |
| 4 | Manufacturer documentation/monographs/product information | Disabled initially; configurable after appraisal |
| 5 | General websites/search/user submissions/unverified secondary material | Never independently authoritative, even if configuration attempts promotion |

No website/organization allowlist is embedded in business logic. `authoritative_tiers` controls tiers 1–4; evidence_sources is initially empty, so production evidence acceptance remains closed. Issuer configuration must specify an active tier, allowed scopes and current valid_until; its appraised records bind record/version/locator/content digest to exact scope, target ID and current snapshot hash, with identity_appraised=true and a current appraisal expiry. The six-field evidence envelope stays unchanged. Review AND current-use policy enforce this binding, rejecting unrelated/retracted/expired/changed-content evidence. Submitted tier/permission claims cannot choose authority. Accepted record configuration needs an accountable professional appraisal; a digest or tier alone proves nothing.

Configuration shape (placeholder field names, no accepted external source seeded):

```text
evidence_sources[issuer] = {active: true, tier: 1..4, scopes: [...], valid_until: UTC datetime(6),
  records: [{scope, target_id, snapshot_hash, record, version, locator, source_sha256,
             identity_appraised: true, valid_until: UTC datetime(6)}]}
```

Identity evidence establishes exact identity/composition/presentation facts. It never implies therapeutic effectiveness, patient suitability or treatment advice. Sources/content/licences were not fetched or independently verified by these tests; all positive fixtures are explicitly synthetic. Configuration appraisal is manual v1, not an evidence-management platform.

## 7. Representative approval policy

Pilot substances must be explicitly declared unambiguous with a nonblank appraised representative_reference. Duplicate IDs or duplicate representative references deny configuration before creation. Only approved S identifiers are admitted; forms and inferred name relationships are excluded from v1 pilot. One independent qualified decision verifies each chemical/composition/presentation revision. Composition depends on current chemical approval; presentation adds current composition and complete supported attributes. Public/deployment authority remains separate.

This is a bounded owner-appraised representative list, not an automated scientific-equivalence classifier. Owner appraisal must identify genuine cross-ID overlap; arbitrary different representative labels cannot prove different entities. Formatting similarity never authorizes merge or equivalence. The 18 collision groups remain unresolved outside this task.

## 8. Lifecycle/restoration rules

Phase 3.10 all-history protection for withdrawn/superseded records and terminal retirement is preserved. No protected restoration workflow is introduced. Withdrawal cannot be neutralized by another adverse state, candidate edit/assessment or review.

Owner clarification for this phase: **explicit reassessment plus fresh independent review remains available after invalidation; only automatic restoration is rejected**. Invalidated chemical records cannot jump directly to review: candidate reassessment, fresh revision-bound submission and independent review are required. Composition/presentation recovery similarly needs fresh permitted draft/submission/review with current dependencies. Registry activity round trips retain journal IDs; old decisions remain stale even if updated_at/current facts return to their original bytes. Fresh evidence appraisal must bind the new snapshot. Old audit events and journal history are never rewritten or recreated.

## 9. Registry authority

`requireOperator(actor, 'registry')` checks a separate current grant or separately approved registry_operator_user_ids setting. Owner status alone does not imply registry permission. Contributors/reviewers cannot use this gate. No registry mutation command, endpoint or automatic reconciliation is implemented. The gate prepares a boundary for later explicitly approved registry operations; it does not grant database credentials or protect arbitrary privileged direct SQL. Existing registry rows/definitions remain untouched; Phase 3.10 activity instrumentation is retained.

## 10. Deployment authority

`requireOperator(actor, 'deployment')` accepts an active approved owner, separately approved deployment_operator_user_ids, or a current owner-issued deployment grant. Reviewer/contributor grants do not satisfy it. The gate executes no deployment or migration. Only an explicitly approved operator may apply reviewed SQL later. Configuration changes, backups, restore procedures and writer privileges remain accountable deployment prerequisites, not automatic operations.

## 11. Pilot scope

Internal pilot settings are initially disabled, with empty code/targets/personnel/sources. The intended first pilot is approximately 5–10 individually appraised substances/Medicine identities. Enforcement accepts at most ten explicitly approved substance IDs, a stable code, a positive limit <=10 and an explicit action allowlist. Forms and out-of-list IDs deny. Presentation creation is off in default actions. Maximum enrollment is per target scope/code (chemical, composition, presentation), not ten audit events total; retained withdrawn enrollments still consume quota. All pilot registry rows are locked in deterministic order before current enrollment count and creation, serializing quota checks.

`medicine_pilot_records` links typed target, code/scope and its immutable creation event. Unique target/event indexes, four restrictive FKs, two CHECKs and UPDATE/DELETE rejecting triggers preserve enrollment. Creation audit/enrollment failure rolls back the whole target transaction. Existing un-enrolled targets are not silently adopted. Quota-full requests, including reuse requests, deny conservatively. Scope/action/target changes are deployment-approved configuration operations, never client flags.

Reversibility means disabling new pilot commands and revoking/invalidation/withdrawal governance controls while retaining records/history, not deleting targets or restoring old approval. Clinical current eligibility remains a separate current-evidence predicate; pausing pilot creation is not a publication permission. No normal pilot data was created and no bulk activation path exists.

## 12. Private medication linking boundary

The three historical private records remain untouched. No automatic link/migration, text replacement or endpoint integration. Existing private access still uses findAccessible and owner/editor checks, independent of canonical permissions. Future explicit association requires authoritative current IDs where applicable, separately authorized reviewer assistance and user confirmation; confirmation is personalization, not canonical medical authority. No private payloads are required in governance logs/tests.

## 13. Public publication status

Public Medicine publication is disabled. Unknown/publish catalog actions always deny, no publish method/controller/route/SEO/projection was introduced, and successful submission/review returns published=false. Existing substance autocomplete is unchanged. Treatment configuration/functionality and HOLD remain unchanged.

## 14. Deployment prerequisites and exact SQL

No connection, SELECT, DDL or data writes targeted normal KOBI. SQL mode/configuration credentials were not changed. Phase 3.9 measured historical baseline remains 2,224 active/searchable CANDIDATE/UNKNOWN substances, zero verification metadata, 18 collision groups/39 rows, 2,327 combinations/6,671 members, zero forms, three private records; no new census was performed.

Authoritative current schema hash: `b5fbc3dea77772e88fbfcff58baa8ebc601a31977cea4a3d6d55a931096a7eed`. Changes: extend two existing controlled CHECK vocabularies with explicit operator capabilities and create/submit/reject actions; add one enrollment table and two immutable-enrollment triggers. Removing only the new block and the two vocabulary extensions reconstructs the exact Phase 3.10 schema hash `322bea83a831d085c02464168e50055c193f9cfefdf5567e4495c0a2625d0164`, proving all other schema content preserved.

Fresh initial deployment selects exactly twelve Medicine CREATE TABLE and eight Medicine CREATE TRIGGER definitions from structure.sql, in dependency order: the Phase 3.11 inventory followed by the new pilot table/triggers. Do not import the complete dump over normal KOBI or execute its DROP/view/mode/check-disabling directives. If Phase 3.10 objects already exist, use a reviewed five-statement upgrade: the two exact CHECK replacements below plus the exact three-statement Phase 3.12 block. No approved migration execution tool is added. Check fresh metadata first; partial/drifted installations require operator review, not IF NOT EXISTS or destructive retry.

```sql
ALTER TABLE medicine_authority_grants
 DROP CONSTRAINT chk_medicine_grant_codes,
 ADD CONSTRAINT chk_medicine_grant_codes CHECK (BINARY capability IN (BINARY 'contributor',BINARY 'editor',BINARY 'reviewer',BINARY 'publisher',BINARY 'steward',BINARY 'administrator',BINARY 'registry',BINARY 'deployment') AND BINARY scope IN (BINARY 'chemical',BINARY 'composition',BINARY 'presentation',BINARY 'catalog'));
ALTER TABLE medicine_governance_events
 DROP CONSTRAINT chk_medicine_event_action,
 ADD CONSTRAINT chk_medicine_event_action CHECK (BINARY action IN (BINARY 'grant',BINARY 'revoke',BINARY 'assess',BINARY 'edit',BINARY 'verify',BINARY 'invalidate',BINARY 'retire',BINARY 'create',BINARY 'submit',BINARY 'reject'));
```

The exact new block to apply separately is reproduced below; none was applied normally:

```sql
/*Medicine Phase 3.12: explicit internal pilot enrollment; no registry or private data changes. */
CREATE TABLE `medicine_pilot_records` (
 `id` bigint unsigned NOT NULL AUTO_INCREMENT,
 `pilot_code` varchar(64) COLLATE utf8mb4_bin NOT NULL,
 `scope` varchar(16) COLLATE utf8mb4_bin NOT NULL,
 `chemical_ref_id` bigint unsigned DEFAULT NULL,
 `medicine_identity_id` bigint unsigned DEFAULT NULL,
 `medicine_presentation_id` bigint unsigned DEFAULT NULL,
 `creation_event_id` bigint unsigned NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `uq_medicine_pilot_ref` (`chemical_ref_id`),
 UNIQUE KEY `uq_medicine_pilot_identity` (`medicine_identity_id`),
 UNIQUE KEY `uq_medicine_pilot_presentation` (`medicine_presentation_id`),
 UNIQUE KEY `uq_medicine_pilot_event` (`creation_event_id`),
 KEY `idx_medicine_pilot_scope` (`pilot_code`,`scope`,`id`),
 CONSTRAINT `fk_medicine_pilot_ref` FOREIGN KEY (`chemical_ref_id`) REFERENCES `kobi_medicine_chemical_refs` (`chemical_ref_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_pilot_identity` FOREIGN KEY (`medicine_identity_id`) REFERENCES `kobi_medicine_identities` (`medicine_identity_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_pilot_presentation` FOREIGN KEY (`medicine_presentation_id`) REFERENCES `kobi_medicine_presentations` (`medicine_presentation_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_pilot_event` FOREIGN KEY (`creation_event_id`) REFERENCES `medicine_governance_events` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `chk_medicine_pilot_code` CHECK (BINARY pilot_code REGEXP '^[a-z0-9_-]{1,64}$'),
 CONSTRAINT `chk_medicine_pilot_target` CHECK (
  (BINARY scope=BINARY 'chemical' AND chemical_ref_id IS NOT NULL AND medicine_identity_id IS NULL AND medicine_presentation_id IS NULL) OR
  (BINARY scope=BINARY 'composition' AND chemical_ref_id IS NULL AND medicine_identity_id IS NOT NULL AND medicine_presentation_id IS NULL) OR
  (BINARY scope=BINARY 'presentation' AND chemical_ref_id IS NULL AND medicine_identity_id IS NULL AND medicine_presentation_id IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DELIMITER $$
CREATE TRIGGER `medicine_pilot_no_update` BEFORE UPDATE ON `medicine_pilot_records` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine pilot enrollment is immutable';
END$$
CREATE TRIGGER `medicine_pilot_no_delete` BEFORE DELETE ON `medicine_pilot_records` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine pilot enrollment is immutable';
END$$
DELIMITER ;
```

Before any operator action: verified backup/restore readiness and writer pause; actual parent/type/index/engine preflight; exact source hash/metadata comparison; enabled FK/unique enforcement; reviewed trigger privileges/definer and protected writer permissions. After deployment: confirm all constraints/triggers/empty grants/history/enrollment before initial issuance, deploy compatible code, keep pilot/public disabled, use read-only/safely denied normal smoke checks and isolated mutation tests. DDL is not atomic; on partial failure stop and retain recorded state/history. Prefer compatible code rollback with additive schema/instrumentation retained. Restore requires separately approved coherent DB/code/history handling. Actual personnel, verifiable credentials, appraised sources/records and 5–10 pilot IDs must be selected outside these synthetic tests before a later explicit activation. The schema must be deployed before code paths requiring pilot enrollment are invoked.

## 15. Validation, security and known limitations

All actually run, final results:

| Suite | Cases/checks | Assertions | Failures |
| --- | ---: | ---: | ---: |
| tests/medicine-policy.php | 33 | 92 | 0 |
| tests/medicine-policy-mariadb.php | 11 | 43 | 0 |
| tests/medicine-governance.php | 56 | 249 | 0 |
| tests/medicine-governance-mariadb.php | 38 | 143 | 0 |
| tests/medicine-foundation.php | 105 | 216 | 0 |
| tests/medicine-foundation-mariadb.php | 29 | 96 | 0 |
| tests/treatment-operations.php | 127 focused + 443 retained = 570 | Not separately counted | 0 |
| tests/auth-phase1.php | 45 | Not separately counted | 0 |

Total **887 cases/checks**, Medicine **272 cases / 839 assertions**. All eleven new/changed PHP files passed PHP 8.2 syntax checks. References/final source and git diff --check were reviewed. Existing test assertions were retained: old synthetic structural/governance fixtures explicitly isolate prior policies, while the separate v1 suites exercise the real new policy and shared transaction behavior.

MariaDB 10.4.27: dedicated port 3328 governance/v1 and 3325 foundation instances, guarded exact datadir/version/port/mode. Fresh schema import executed 138 statements in each of the three disposable suite databases. All non-mode statements executed, including prior constraints/view/Treatment objects; no schema compatibility repair or hidden omission. Exact existing CHECK upgrade syntax was additionally tested against narrower prior vocabularies and unknown codes still rejected. New table FK/CHECK/index metadata and eight Medicine triggers were checked. Enrollment/history tampering rejected native 1644; invalid codes/targets rejected 4025. Credential JSON/actual issuer persistence, delegated role revocation, complete creation-audit rollback and two independent PDO workers for one remaining quota slot passed. Existing five governance races, four lifecycle races and foundation race passed. Live assertions vary with lawful winners; 143 is the recorded final governance run.

Only these three mode directives were omitted from each transient isolated import; they remain in source:

```sql
/*!40101 SET SQL_MODE=''*/;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
```

Reason: preserve existing `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION` throughout; no global/server/session/persistent application SQL-mode changes. Disposable databases: kobi_medicine_phase38_validation_20261009, kobi_medicine_phase35_validation_20261009 and kobi_medicine_phase312_validation_20261009. All were dropped and absence verified; dedicated servers shut down; temporary instance/worker directories removed. Normal port 3306 server was not stopped or queried.

Focused security: no self-escalation/operator inheritance; actual actor checks and qualified independence; stale submission/review and revoked grants deny; withdrawn bypass blocked and invalidation recovery demands fresh governed steps; registry/deployment gates separate with no execution APIs; immutable events/journal/enrollment; parameterized fixed-column repository SQL; private access unchanged; safe 403/409/422/503/500 mappings without raw diagnostic messages; no credential/public output or publication. No HTTP routes were added, so CSRF enforcement for new endpoints is not applicable here; any later endpoint must use explicit auth/authorization/CSRF. Licence/source correctness is an accountable human appraisal, not certified by fixture success. No actual external evidence or credential verification was claimed.

Intermediate findings were corrected openly: a foundation reflection fixture exposed uninitialized authorization dependencies, now explicitly denied; two policy cases attempted contributor inspection as a reviewer, corrected to the contributor actor without relaxing authorization. A disposable initialization command had invalid PowerShell port argument formatting and was corrected before import. All final tests passed. No failures were suppressed.

Limitations: no normal schema/application deployment, backup rehearsal, actual credential/source appraisal, real grants, registry reconciliation or pilot activation. Manual configuration changes need accountable external change records; no policy-management UI exists. Representative correctness is manually appraised within a bounded list, not global chemical deduplication. Evidence record configuration binds exact snapshots conservatively and requires new appraisal when facts/generations change. Privileged database DDL/TRUNCATE/trigger removal/falsified direct inserts remain outside application integrity; operational privileges must protect journals. Quotas are retained-history counts, not a cleanup mechanism. Phase 3.10 history before instrumentation cannot be reconstructed; earlier snapshots/qualifications fail closed. No public/patient rollout or unrestricted creation is justified by passing tests.

Files modified: app/Config/Config.php; app/Repositories/MedicineGovernanceRepository.php; app/Services/MedicineGovernancePolicy.php; app/Services/MedicineAuthorizationService.php; app/Services/MedicineIdentityService.php; app/Services/MedicineGovernanceService.php; app/Services/MedicineEligibilityService.php; database/structure.sql; tests/medicine-foundation.php; tests/medicine-governance.php.

Files created: tests/medicine-policy.php; tests/medicine-policy-mariadb.php; this document. No commit, push, reset, checkout, merge or rebase. Prior user changes were preserved; normal registry/private records, Treatment functionality/HOLD and public-disabled status remain unchanged.
