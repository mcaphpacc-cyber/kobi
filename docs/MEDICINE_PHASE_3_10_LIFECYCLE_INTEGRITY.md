# Medicine Phase 3.10 — Lifecycle integrity correction

Date: 2026-10-09. Result: **PASS WITH LIMITATIONS**. Lifecycle integrity: **FIXED WITH LIMITATIONS** (source/disposable validation; normal schema deployment is pending). Shared creation/publication remains disabled.

Before schema edits this document recorded why current-row snapshots cannot detect an unobserved activity round trip and why actor-required governance events cannot truthfully represent external database writers. The final implementation follows that narrow necessity; no clinical policy was invented.

## 1. Defect 1 description

Withdrawn/superseded targets could regain trust through an intermediate invalidated event, contributor candidate reassessment and independent verification. Phase 3.9 reproduced this using memory fixtures. Old history remained present, but no longer controlled restoration.

## 2. Defect 1 root cause

`MedicineGovernanceService::checkedSnapshot()` checked only the latest event's protected state. `invalidate()` could append a different adverse state after withdrawal. Candidate reassessment and review then saw an unprotected latest event. This affected chemical references, compositions and presentations, with no explicit restoration workflow.

## 3. Defect 1 correction

`MedicineEligibilityService::requireLifecycle()` rejects any recorded withdrawn/superseded decision for the target, irrespective of later events. `checkedSnapshot()`, `invalidate()` and `requireCurrent()` share that check. There is no restore exception. Protected targets cannot be reassessed, edited, reviewed or transitioned through another adverse command, including retirement, in this phase. Original objects/keys/events are retained.

Commands lock the target before reading history; `MedicineGovernanceRepository::events()` uses a current FOR UPDATE read. This matches adverse-command ordering and prevents an old repeatable-read view from hiding protected history. Existing expected revision, scope, evidence and independence checks remain.

## 4. Defect 1 regression tests

Six new memory cases cover chemical/composition/presentation × withdrawn/superseded. Each attempts invalidated/withdrawn/superseded/retired intermediate paths through actual service commands, then candidate reassessment or metadata edit, direct verification and current eligibility. All reject safely with 409 and leave full target/assessment/history state unchanged.

Live tests cover withdrawal versus invalidation with two PHP/PDO workers, then attempts to invalidate/reassess the withdrawn winner. A separate concurrent stale-review versus candidate-restoration case rejects both attempts on protected history. Existing direct-restoration tests remain retained. No fake user/source approval was added to production.

## 5. Defect 2 description

An external registry inactive→active round trip restoring identical current facts could make an old independent decision eligible again. The application may never see the inactive interval. Second-resolution updated_at is not a durable lifecycle revision, and writers can explicitly preserve it.

## 6. Defect 2 root cause

`MedicineEligibilityService::registrySnapshot()` fingerprinted only current substance/form/parent fields. After a round trip those bytes could match old evidence exactly. Application-only comparison cannot recover an unobserved event. Writing invalidation during an eligibility read would still miss unseen transitions and introduce unauthorized read-side writes.

Existing `medicine_governance_events.actor_id` is required and refers to an application user; assigning a made-up user to external SQL activity would be false attribution. Registry table fields/definitions were not expanded or rewritten.

## 7. Defect 2 correction

Added `medicine_registry_lifecycle_events` and four additive triggers in `database/structure.sql`. AFTER UPDATE triggers on substances/forms append actual activity changes to the separate journal in the same database transaction. They never assign NEW fields or update any registry/Medicine/private row. No event is generated for a no-op flag write. Events capture XOR target, old/new flag, UTC microsecond time and fixed `source='registry_trigger'`; application actor is unknown and not fabricated.

BEFORE UPDATE/DELETE triggers make the journal immutable. Its per-target ID/history indexes and restrictive FKs preserve the observed source. Three CHECKs validate target XOR, genuine tinyint-range change and exact source. Invalid nonbinary activity values are also journaled; eligibility still requires exactly active=1.

`MedicineGovernanceRepository::registryLifecycle()` returns the latest current event ID (or '0' when that target has no events), after its registry row is locked. Missing journal schema is an infrastructure failure, not a fallback to zero. `registrySnapshot()` binds both target and form-parent lifecycle IDs. Chemical review/assessment snapshots therefore change permanently after a committed activity round trip, even when all registry fields/timestamps return to their original values. Current composition/presentation reads inherit this invalidation through dependency recursion.

Fresh explicit assessment/independent review binds the new journal IDs and increments governed metadata_revision. Fresh direct independent review is also available through the existing verified-assessment re-review path; old tokens cannot authorize it. No new restoration, registry-writing service, clinical qualification or evidence policy was invented.

## 8. Defect 2 regression tests

Three memory cases cover substance, form and form parent: reviewed → inactive → active denies old acceptance and stale submission, then explicit candidate assessment plus independent review succeeds. A fourth case proves composition/presentation remain stale even after chemical recovery until their own fresh reviews.

Live tests change only disposable synthetic sources, preserving updated_at (`updated_at=updated_at`) and performing 0→1 in one transaction with no intermediate eligibility read. Both substance and form cases deny old review/token, preserve the old event exactly and accept fresh governed recovery. Form-parent dependency is tested through substance 3/ref 53. Real journal events show 1→0 then 0→1 with source/time; no-op UPDATE emits none. Rolling back activity also rolls back its journal event and leaves the previous review current. UPDATE/DELETE journal mutation fails native 1644; malformed change/source fails CHECK 4025; nonexistent source FK fails 1452.

## 9. Revision/review implications

Registry lifecycle IDs are dependency generations, not a replacement for governed metadata_revision. They enter the immutable review snapshot/hash; current eligibility requires exact current snapshot equality and latest revision-bound independent decision. Registry changes do not rewrite old review/assessment rows or erase verification proof. Fresh chemical review increases its revision, making dependent reviewed compositions/presentations stale; fresh reviews at each affected level are required.

Pre-3.10 snapshots lack these fields and fail equality rather than silently adopting the new baseline. Deployment must require fresh independent review of previously verified targets. No backfill or normal record update was performed.

## 10. Audit-history implications

The immutable source journal supplies target, old/new activity, UTC time and machine origin for external lifecycle changes, including a reactivation unseen by application reads. Actor is explicitly unavailable; this phase does not pretend a database connection identifies the human/application actor. Existing governance events retain actual contributor/reviewer/steward and rationale/revision/evidence attribution. Comparing each historical snapshot's lifecycle IDs with source journal history determines which decisions became stale and which fresh revision accepted the new generation.

Withdrawal remains a protected accountable historical decision. Neither protection nor current read rejection writes new events. Trigger failure rolls back the source UPDATE instead of committing an unrecorded lifecycle change. A rolled-back event can leave an auto-increment gap; gaplessness is not an integrity requirement.

## 11. Concurrency validation

Four added barrier-synchronized two-process/PDO cases:

1. Withdrawal versus invalidation: one expected-revision winner; once withdrawal is recorded no alternative transition restores eligibility.
2. Registry inactivation versus review: regardless of ordering, final inactive dependency denies current use.
3. Registry reactivation versus stale review: reactivation is recorded, stale review is denied, old current use stays denied, fresh recovery succeeds.
4. Stale review versus protected-state candidate reassessment: both deny, protected history remains authoritative.

Five earlier governance races (edit/edit, review/edit, revoke/edit, invalidation/review, duplicate reconciliation) and the foundation creation race also passed. Governance uses its existing bounded whole-transaction duplicate/deadlock retry. Registry activity test workers use transactional direct SQL solely on the guarded disposable instance. Registry locks serialize activity UPDATEs and lifecycle reads; triggers commit atomically with source changes. No new production concurrency subsystem was introduced.

## 12. Database validation

Normal KOBI database: **no connection, writes or schema changes in this phase**. The prior Phase 3.9 census remains the factual baseline; its 2,224 substances and 3 private records were not touched. No credentials/configuration were printed/modified.

Two separate guarded MariaDB **10.4.27** instances, each with project-local datadir/redo/log/tmp paths:

- Port 3328: `kobi_medicine_phase38_validation_20261009`, governance/lifecycle suite.
- Port 3325: `kobi_medicine_phase35_validation_20261009`, existing foundation suite.

Each imported **135 statements**, including all schema objects. Client DELIMITER directives were interpreted normally. Source SHA-256: `322bea83a831d085c02464168e50055c193f9cfefdf5567e4495c0a2625d0164`. Only these existing SQL-mode directives were omitted from each transient stream:

```sql
/*!40101 SET SQL_MODE=''*/;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
```

Reason: validate unchanged-mode behavior. Source retains them. No other SQL statement omitted or compatibility repair needed. Initial/final mode remained **NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION**; no global/server/session/persistent application mode change.

Actual metadata verified journal PK, two FK/index pairs, three named CHECKs and four triggers. Existing foundation/governance constraints passed their retained metadata/runtime checks. An intermediate journal immutability test incorrectly targeted rolled-back auto-increment ID 1; corrected the test to target an existing row. The corrected final run passed; no schema change was made to hide a failure.

Deployment remains a separate human action. Apply only the exact following additive Phase 3.10 block after required existing parent tables are deployed and metadata reviewed. Do not reimport the complete dump into normal KOBI. There is no seed/backfill, DROP or ALTER in this block:

```sql
/*Medicine Phase 3.10: durable registry activity history; existing registry definitions untouched. */
CREATE TABLE `medicine_registry_lifecycle_events` (
 `id` bigint unsigned NOT NULL AUTO_INCREMENT,
 `substance_id` bigint unsigned DEFAULT NULL,
 `form_id` bigint unsigned DEFAULT NULL,
 `old_active` smallint NOT NULL,
 `new_active` smallint NOT NULL,
 `source` varchar(32) COLLATE utf8mb4_bin NOT NULL DEFAULT 'registry_trigger',
 `occurred_at` datetime(6) NOT NULL,
 PRIMARY KEY (`id`),
 KEY `idx_medicine_lifecycle_substance` (`substance_id`,`id`),
 KEY `idx_medicine_lifecycle_form` (`form_id`,`id`),
 CONSTRAINT `fk_medicine_lifecycle_substance` FOREIGN KEY (`substance_id`) REFERENCES `kobi_substances` (`substance_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_lifecycle_form` FOREIGN KEY (`form_id`) REFERENCES `kobi_substance_forms` (`form_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `chk_medicine_lifecycle_target` CHECK ((substance_id IS NOT NULL)+(form_id IS NOT NULL)=1),
 CONSTRAINT `chk_medicine_lifecycle_change` CHECK (old_active<>new_active AND old_active BETWEEN -128 AND 127 AND new_active BETWEEN -128 AND 127),
 CONSTRAINT `chk_medicine_lifecycle_source` CHECK (BINARY source=BINARY 'registry_trigger')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$
CREATE TRIGGER `medicine_substance_activity_history` AFTER UPDATE ON `kobi_substances` FOR EACH ROW
BEGIN
 IF NOT (OLD.is_active <=> NEW.is_active) THEN
  INSERT INTO medicine_registry_lifecycle_events(substance_id,old_active,new_active,source,occurred_at)
  VALUES(NEW.substance_id,OLD.is_active,NEW.is_active,'registry_trigger',UTC_TIMESTAMP(6));
 END IF;
END$$
CREATE TRIGGER `medicine_form_activity_history` AFTER UPDATE ON `kobi_substance_forms` FOR EACH ROW
BEGIN
 IF NOT (OLD.is_active <=> NEW.is_active) THEN
  INSERT INTO medicine_registry_lifecycle_events(form_id,old_active,new_active,source,occurred_at)
  VALUES(NEW.form_id,OLD.is_active,NEW.is_active,'registry_trigger',UTC_TIMESTAMP(6));
 END IF;
END$$
CREATE TRIGGER `medicine_lifecycle_no_update` BEFORE UPDATE ON `medicine_registry_lifecycle_events` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine lifecycle history is immutable';
END$$
CREATE TRIGGER `medicine_lifecycle_no_delete` BEFORE DELETE ON `medicine_registry_lifecycle_events` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine lifecycle history is immutable';
END$$
DELIMITER ;
```

Cleanup completed on resume. Both dedicated servers had already stopped: their former PIDs 2864/2696 were absent and ports 3328/3325 had no listeners. The guarded SQL DROP attempt on port 3328 failed with connection refused, before executing any SQL; the remaining DROP/shutdown commands were not executed. Both complete isolated instance directories (including database files, data/log/tmp files and worker fixtures) were then removed offline after exact path, process/listener and reparse-point checks. Both paths are absent. The normal server remained listening on port 3306 (PID 5024) and was not stopped. No successful SQL DROP is claimed.

## 13. Existing regression results

| Suite actually run | Cases/checks | Assertions | Final result |
| --- | ---: | ---: | --- |
| tests/medicine-governance.php | 56 | 249 | PASS |
| tests/medicine-governance-mariadb.php | 38 | 144 | PASS |
| tests/medicine-foundation.php | 105 | 216 | PASS |
| tests/medicine-foundation-mariadb.php | 29 | 96 | PASS |
| tests/treatment-operations.php | 127 focused + 443 retained = 570 | Not separately counted | PASS |
| tests/auth-phase1.php | 45 | Not separately counted | PASS |

Total **843 cases/checks**, zero final failures; Medicine **228 cases / 705 assertions**, including **19 new lifecycle cases**. Race assertions can vary slightly by lawful winner; numbers above are the recorded final run from the completed pre-interruption validation. Earlier repeated runs are not counted again. All five changed PHP files passed PHP 8.2 syntax checks. On resume, the implementation/references and seven Phase 3.10 files were inspected without rewriting them; completed expensive live suites were not repeated. `git diff --check` passed. The prior in-memory task-start hash inventory was unavailable after resumption, so a fresh resume baseline was taken to verify preservation during cleanup/reporting. The schema's original pre-3.10 hash can independently be reconstructed by removing only the additive block. Git's diff/stat covers tracked earlier changes; these Medicine files/schema/tests are untracked and were inspected directly.

Files created:

- D:/xampp/htdocs/kobi/docs/MEDICINE_PHASE_3_10_LIFECYCLE_INTEGRITY.md

Files modified:

- D:/xampp/htdocs/kobi/app/Repositories/MedicineGovernanceRepository.php
- D:/xampp/htdocs/kobi/app/Services/MedicineGovernanceService.php
- D:/xampp/htdocs/kobi/app/Services/MedicineEligibilityService.php
- D:/xampp/htdocs/kobi/database/structure.sql
- D:/xampp/htdocs/kobi/tests/medicine-governance.php
- D:/xampp/htdocs/kobi/tests/medicine-governance-mariadb.php

No prior user changes were discarded. No commit/push/reset/checkout/merge/rebase/history mutation.

## 14. Security validation

Protected-state transitions/current use/stale tokens reject 409; revoked/inactive/wrong-scope/unqualified/self-review remain denied by retained tests. Immutable source history cannot be changed through normal repository operations or UPDATE/DELETE SQL; malformed states/source and invalid references reject at the database. Registry reactivation alone never establishes a current review generation. No read-side registry/medication mutation, fabricated user actor, raw exception fallback, authority grant or permission widening was introduced. Missing infrastructure retains the existing safe owning-service 503/500 mapping. Production qualification/admin/evidence policies remain closed.

## 15. Public-exposure confirmation

No controller, route, public projection, UI, publication path or Treatment file/configuration changed. `requireCatalog()` remains always denying shared creation, governance still has no publish operation and review remains `published=false`. Existing registry autocomplete stays unchanged. Public canonical Medicine exposure is disabled and Treatment HOLD remains unchanged.

## 16. Remaining limitations

The correction requires reviewed deployment of the journal/triggers before any governed activation. They were validated only in disposable MariaDB and are not applied to normal KOBI. Historical activity before instrumentation cannot be reconstructed; old snapshots fail closed and need fresh review. Privileged DDL/TRUNCATE/trigger removal or falsified direct journal INSERTs remain outside ordinary application integrity; trusted writer privileges/deployment must preserve instrumentation. Transactional changes rolled back have no committed invalidation, correctly.

The journal captures activity transitions only; this phase does not add a complete registry semantic-change history. Existing current full-row snapshots still detect changed present semantics. Accepted identity sources, qualifications, administrators, representatives and restoration policy remain unresolved, as in Phase 3.9. No registry population, chemical equivalence, clinical efficacy, private linking, UI/browser rollout or normal upgrade was attempted. Withdrawn/superseded restoration is intentionally unavailable rather than decided medically.

## 17. Phase 3.11 recommendation

Resolve the existing owner/medical governance administrator, qualification, evidence and representative decisions; review additive deployment and instrumentation requirements before limited activation. Keep shared/public writes closed. Any future restoration must be a separately approved explicit workflow with fresh review and immutable history. Do not broaden this surgical correction into another import, merge, patient migration or publication phase.
