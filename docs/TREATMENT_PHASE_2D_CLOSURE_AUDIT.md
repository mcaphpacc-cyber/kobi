# Treatment Phase 2D — Closure Audit

## 1. Audit Scope

Audit date: 2026-10-09, Asia/Calcutta. Result: **PASS WITH LIMITATIONS**.

This is a closure audit of the Treatment Knowledge Foundation through 2C-4B-L, including its final repository locking correction. It evaluates the current implementation, authoritative schema, regression suites and retained isolated-runtime evidence. It does not authorize public treatment exposure or certify the deployed normal database's current contents.

Only this document was created. Application code, tests, schema, configuration, routes and HOLD were not changed. No database connection, DDL, data write, SQL-mode change, treatment command, live fixture or new concurrency harness was executed during this audit. Existing database-free suites were rerun; earlier live results were inspected as historical evidence, not presented as new runtime execution. No Git history mutation occurred.

The worktree already contained substantial changes at task start. Those changes belong to earlier work and were preserved. Earlier blocked/preflight reports were reconciled with their subsequent closeouts rather than treated as current failures.

## 2. Repository / Code Areas Inspected

Paths below are relative to `D:/xampp/htdocs/kobi/`.

| Area | Files / references inspected |
| --- | --- |
| Governance and policy | `app/Services/TreatmentGovernanceService.php`, `TreatmentGovernanceRules.php`, `TreatmentGovernanceSnapshot.php`, `TreatmentGovernancePolicy.php` |
| Editorial, review and authorization | `app/Services/TreatmentEditorialService.php`, `TreatmentEditorialAuthorizationService.php`; `app/Controllers/TreatmentAdminController.php`, `TreatmentReviewController.php` |
| Internal repositories | `app/Repositories/TreatmentGovernanceRepository.php`, `TreatmentSourceRepository.php`, `TreatmentEditorialPermissionRepository.php`; existing BaseRepository and dependency-injection conventions |
| Canonical public read | `app/Services/TreatmentPublicationService.php`, `app/Repositories/TreatmentPublicationRepository.php` |
| Disease / comparison integration | `app/Services/DiseaseService.php`, `app/Repositories/DiseaseRepository.php`, disease/comparison references and existing public view contract |
| Views / routes | `app/Views/admin/treatments/`, `app/Views/admin/treatment-review/`, `app/Views/disease/show.php`, `routes/web.php`; searches for raw treatment SQL, alternate retrieval, publication and history mutation paths |
| Schema | `database/structure.sql`, particularly treatment protocols, items, sources, source links, events, permissions, candidate indexes, approval composite FK and two source triggers |
| Tests | Ten suites listed in section 10; final `latestSubmission()` regressions and existing lifecycle/risk/comparison coverage |
| Phase evidence | `docs/TREATMENT_PHASE_2C2_LIVE_TESTS.md`, `TREATMENT_PHASE_2C3_LIVE_TESTS.md`, `TREATMENT_PHASE_2C_VALIDATION_FINAL.md`, `TREATMENT_PHASE_2C3B_L1_VALIDATION.md`, `TREATMENT_PHASE_2C4A_PUBLIC_READ.md`, `TREATMENT_PHASE_2C4A_L_LIVE_VALIDATION.md`, `TREATMENT_PHASE_2C4B_PREFLIGHT.md`, `TREATMENT_PHASE_2C4B2L_LIVE_VALIDATION.md`, `TREATMENT_PHASE_2C4B3L_LIVE_VALIDATION.md`, `TREATMENT_PHASE_2C4BL_VALIDATION.md` and associated retained runtime/concurrency artifacts |

## 3. Prior Completed Phase Reconciliation

| Phase | Expected | Verified | Status | Notes |
| --- | --- | --- | --- | --- |
| 2C-1 | Foundation schema, service rules, transactions and legacy defaults | Current schema/rules/snapshot/repositories; foundation suite; subsequent MariaDB metadata | PASS | Direct self-replacement uses the approved MariaDB AFTER triggers; remaining chain/cycle rules stay in services. |
| 2C-2 | Scoped editorial and source administration | Admin controller, editorial service, source operations, permissions, routes and editorial suite | PASS | Architecture and persisted per-system scope retained. |
| 2C-3 | Independent review and approval workflow | Submit/risk/approve/changes-required commands, review controller/views, current-revision snapshot matching | PASS | Approval remains distinct from publication. |
| 2C-3A | Policy and presentation lock-in | Qualified reviewers, meaningful content, locale/deadline validation, provenance presentation and review tests | PASS | Verification is provenance, not an automatic efficacy claim. |
| 2C-3B | Explicit risk eligibility and withdrawal lifecycle | Risk event/revision/hash rules, withdrawal/invalidation, prerequisites tests | PASS | Later L1 schema correction protects risk/event integrity under non-strict MariaDB. |
| 2C-4A | Canonical eligibility, projection and shared public integration | Publication service/repository; DiseaseService shared helper; publication suite | PASS WITH LIMITATION | Public UI activation was deliberately deferred; HOLD blocks exposure. |
| 2C-4A-CORRECTION | Separate substantive assessment from exposure | Shared substantive predicate; assessment returns CANDIDATE/DENY independently of mode; correction suite | PASS | CANDIDATE under HOLD still has null projection and empty public lists. |
| 2C-4A-L | Isolated MariaDB assessment and HOLD validation | Retained live report/runtime evidence; schema hash still matches; current API tests | PASS WITH LIMITATION | Historical execution; no new live test here. Later 4B-L extends governed projection coverage. |
| 2C-4B-1 | Escaped review presentation and safe authorization failures | Review partials, persisted authorization and controller error boundaries; hardening suite | PASS | Infrastructure failure is not converted to authorization success or misleading denial. |
| 2C-4B-2 | Draft/item creation | Atomic draft with optional first item at revision 1; later item changes increment; creation suite | PASS WITH LIMITATION | Application repeat protection exists; no durable database logical-content uniqueness/idempotency. |
| 2C-4B-2-L | Live draft/item validation | Retained 80-check isolated report and artifacts | PASS | Synthetic creation stayed unpublished; normal database untouched in recorded run. |
| 2C-4B-3 | Withdrawal, reset, operations and history | Operations controller/service/views/routes, bounded history, operations suite | PASS WITH LIMITATION | Known LOW listing query scaling remains. |
| 2C-4B-3-L | Live operations validation | Retained 171-check isolated report and artifacts | PASS | Safe failures, rollback and withdrawal/reset paths recorded. |
| 2C-4B-L | Broader security/concurrency and final correction | Current conditional locking SQL, three regressions, actual recorded workers/state/metadata | PASS WITH LIMITATION | HIGH stale-submission race corrected; bounded schedules and accepted LOW issues remain. |

The 4B preflight document is chronological: its later implementation closeouts supersede earlier statements that creation/operations were missing. The earlier SQL-mode import block was resolved by the expressly approved omission of three import directives; it is not an unresolved compatibility defect.

## 4. Governance Closure Matrix

| Checklist | Invariant and evidence | Status |
| --- | --- | --- |
| A — Model | Disease-specific protocol, treatment-system context, protocol items, governed source references, controlled evidence/risk/purpose, applicability/limitations, review/publication state, revision, approval snapshot/hash, due date and history exist in schema/rules. | PASS |
| B — Lifecycle | Creation through independent review, explicit publication, withdrawal/reset and fresh withdrawn-content revision are implemented; no direct restoration. See section 5. | PASS |
| C — Revision | Substantive changes invalidate once, clear approval/locales, reject stale revisions/states; approved/current snapshot integrity is checked. Latest-submission command reads are current and transactional. | PASS |
| D — Sources | Unverified/verified/withdrawn lifecycle, immutable bibliography/predecessor model, chain/cycle validation, explicit references and invalidation of affected protocols; reader checks current sources. | PASS |
| E — Review | Scoped qualified independent reviewers; current submitted event/revision/hash; approval never implicitly publishes; historical events preserved. | PASS |
| F — Risk/evidence | Initial public eligibility requires eligible risk and evidence_supported; unknown values fail closed; risk events and substantive changes participate in revision/snapshot invalidation. | PASS |
| G — Public boundary | Single canonical service/predicate, deliberate allowlist, shared Disease/comparison integration, no alternate public retrieval found. Public UI adaptation remains deferred. | PASS WITH LIMITATION |
| H — HOLD | Current configured mode was read as hold using the policy class without database bootstrap. Exposure methods short-circuit; regression and prior HTTP evidence agree. | PASS |
| I — Authorization | Actual actor, active user, persisted non-revoked system grant, qualification, independence, publisher scope and CSRF checked; admin permission management grants no ordinary bypass. | PASS |
| J — Concurrency | Conditional FOR UPDATE correction, transaction callers and three regressions verified; all 80 retained race groups recomputed as overlapping. | PASS WITH LIMITATION |
| K — Database | Keys/checks/triggers and historical MariaDB metadata agree; approval/risk relationships protected to their specified extent. Fresh normal-database deployment/data status is NOT VERIFIED. | PASS WITH LIMITATION |
| L — Errors | 403/409/422/503/500 boundaries, rollback and safe messages covered by current tests and retained HTTP fault evidence. | PASS |
| M — Operations | Scoped list/detail, withdrawal/reset, immutable application history, protocol-bound descending cursor pagination, bounded pages; query scaling remains LOW. | PASS WITH LIMITATION |
| N — Legacy | Draft/unpublished, unassessed risk/evidence, excluded legacy items and no fabricated sources/approvals; incomplete protocols fail readiness/public gates. | PASS |
| O — Coverage | Current 784 distinct database-free checks plus specific retained live/overlap evidence; no material uncovered required foundation invariant identified. Exhaustive schedules/deployment/public UI activation are outside this closure. | PASS WITH LIMITATION |

## 5. Lifecycle Closure Matrix

| Operation | Verified behavior | Status |
| --- | --- | --- |
| Create draft | Scoped editor, active system, existing disease, allowlisted controlled input; atomic revision 1, unpublished, no approval; optional first item shares revision 1. | PASS |
| Add/edit item or protocol/reference | Later substantive changes increment once and invalidate approval/locales; stale revision/state rejected; no-op does not fabricate a revision. Published content cannot be edited directly. | PASS |
| Submit | Only permitted unpublished draft/changes-required content; readiness, locale, included items and verified references checked; canonical snapshot/hash persisted. Changes-required needs fresh material content. | PASS |
| Risk decision | Qualified independent reviewer, controlled decision and rationale; changed risk invalidates through a governed revision and risk event. | PASS |
| Approve | Pending review, exact newest submission/current revision, qualified independent reviewer, matching canonical snapshot/hash, evidence/risk readiness, future deadline and valid locales. Remains unpublished. | PASS |
| Request changes | Qualified scoped reviewer and exact latest submitted event; clears approval and records changes-required history. | PASS |
| Publish | Explicit scoped publisher command validates approved current revision/hash, deadline, sources, risk/evidence and locales; no HTTP publication route/form added. HOLD remains a separate read guard. | PASS |
| Withdraw | Published only, publisher scope, reason and optimistic state; same revision, withdrawn/draft, null approval, empty locales; one event, prior content/history preserved. | PASS |
| Reset pending review | Qualified reviewer; unpublished only; pending review to draft, same revision, review_reset event. | PASS |
| Reset approved/unpublished | Clears approval, moves to pending review, same revision; review_reset plus fresh submitted snapshot/event atomically. Superseded submissions cannot be approved. | PASS |
| Edit withdrawn content | Substantive edit creates a new unpublished revision; no-op keeps withdrawal. Fresh submission/independent approval and explicit publication are required. No direct restore/reset/republish shortcut. | PASS |
| Verify/withdraw source | Only unverified to verified/withdrawn and verified to withdrawn; withdrawn cannot be restored. Verification requires the qualified review capability under existing policy. | PASS |
| Source invalidation | Authorization covers affected systems, sorted locks and current relationship checks; affected protocols invalidate with fresh revision and cleared approval/locales; published protocols become withdrawn. History/references retained. | PASS |
| History | Event inserts only in application workflow, no event edit/delete route; bounded descending event-ID cursor scoped to protocol; metadata projection excludes raw snapshots/hashes. | PASS |

## 6. Public Boundary Verification

`TreatmentPublicationService` is the canonical substantive boundary. Assessment and exposure use its same private eligibility predicate. Assessment returns only CANDIDATE/DENY and does not consult mode. Exposure separately requires mode exactly governed; hold and unknown modes return null/empty before treatment retrieval. Request parameters, preferences or ordinary permissions cannot choose the deployment mode.

Required eligibility includes coherent disease/system/protocol identities and active system; approved/published state; exact approval event, protocol and current revision; independent valid approval actor/rationale; valid snapshot/hash and equality with current canonical content; no unresolved later invalidation; future review deadline; exact approved/published locale; meaningful localized title/overview/applicability/limitations and controlled purpose; eligible risk and evidence_supported; at least one valid included item with positive unique ordering, supported evidence and localized content; explicit protocol/included-item claim references and current verified valid bibliographic sources. Draft, pending_review, changes_required, approved/unpublished, withdrawn, expired, malformed risk/evidence, invalid source or wrong locale therefore deny. Reads do not mutate expired content.

`TreatmentPublicationRepository` assembles batched aggregates inside a read-only consistent REPEATABLE READ snapshot. It rejects nested/unsupported transaction contexts and rolls back safely on failure. Populated retrieval uses eight recorded queries; it has no publication command.

Both `DiseaseService::getKnowledgeBySlug()` and `getComparison()` call `withPublicTreatments()`, which invokes the canonical service for the same disease identity/requested locale. `DiseaseRepository` supplies an empty placeholder and no longer retrieves raw treatments. Preferences reorder already eligible projections. Repository/controller/route/public searches found no alternate raw public treatment retrieval or AJAX bypass; internal governance repositories remain authenticated administration dependencies.

The public projection explicitly allowlists localized educational protocol/item and bibliographic citation fields. It excludes reviewer/actor identity, grants/qualifications, internal rationale/history, snapshots/hashes, verification metadata, excluded items, unpublished content, health profiles/records and preferences. Internal history's scoped actor metadata is not the public projection.

Actual configured HOLD is active. Current tests show a fully eligible CANDIDATE still has null projection and empty Disease/comparison treatments. Earlier isolated HTTP/browser flows likewise remained empty; governed positive probes used injected test policy only. No audit action lifted HOLD or published normal content.

Public rendering is deferred: `app/Views/disease/show.php` still consumes legacy title_en/overview_en, while the canonical projection provides localized title/overview. `TREATMENT_PHASE_2C4A_PUBLIC_READ.md` explicitly defers adapting views and enabling exposure. This is accepted future activation work, not a current HOLD bypass or missing foundation lifecycle. Adapt and validate public Disease/comparison rendering before any separately authorized exposure change.

## 7. Security / Authorization Review

Controllers authenticate, use the real authenticated actor, require POST/CSRF for mutations, validate input allowlists and delegate business rules to services. Services resolve the actual stored protocol/system rather than trusting forged scope or user IDs. Active users and current non-revoked grants determine edit/review/publish capabilities. Reviewer qualifications and contributor independence are required for applicable review/risk commands; the permission-administrator allowlist does not confer ordinary editorial/review/publisher authority.

Persisted authorization is rechecked within mutation transactions with user/grant locks. Source withdrawal covers every affected system. The permission service returns false for deliberate denial and propagates infrastructure failures; it does not fail open. Existing repositories use BaseRepository and bound parameters. No authentication redesign, email-based account merging or health-profile access change was introduced.

Expected failures are explicit: authorization and CSRF 403; stale revision/state/submission 409; invalid content 422; permission/database infrastructure 503; unexpected failure 500. Controller responses use safe application messages and escaped views. Current hardening/operations tests and retained real HTTP fault probes cover these distinctions without SQLSTATE, stack trace, credentials or private exception text in user bodies. Review/source views escape content and restrict usable URLs; server-rendered confirmation forms retain CSRF and do not introduce inline JavaScript.

## 8. Concurrency Review

The earlier HIGH issue was an obsolete REPEATABLE READ snapshot selecting an old submitted event after a concurrent approved-review reset. The final correction is present at `TreatmentGovernanceRepository::latestSubmission()`:

```sql
SELECT * FROM treatment_protocol_events
WHERE protocol_id=:id AND content_revision=:revision AND event_type='submitted'
ORDER BY id DESC LIMIT 1
```

The repository appends `FOR UPDATE` **only when its PDO connection is already in a transaction**. It does not begin a transaction for this lookup. Approval and changes-required call it inside `TreatmentGovernanceService::command()`, which uses repository transaction handling and already locks the protocol. Presentation's editorial review-detail lookup remains non-locking outside a transaction.

Three current foundation regressions verify superseded-submission rejection without writes, a current command read despite an older snapshot model, and non-locking presentation without starting a transaction. The retained deterministic live replay independently records old submission 115 versus fresh 118 being incorrectly accepted before correction; after correction old 125 versus fresh 128 is rejected, leaving pending_review with a null approval pointer. Historical pre-correction synthetic rows are reproduction evidence, not an unresolved defect in the corrected code or normal KOBI data.

This audit recalculated worker timing from `TREATMENT_PHASE_2C4BL_CONCURRENCY_RESULTS.json`: 80 groups, 318 workers, distinct connections within every group, no worker began before its release barrier, and min(finished) minus max(began) was positive in all groups. This establishes genuine overlapping operation intervals rather than sequential simulation; it does not establish overlap at every SQL statement or exhaust all interleavings.

Retained schedules cover edits, submit/approve/reset, withdrawal/publication, source invalidation, permissions/user status, duplicate creation, fault rollback and public snapshot reads. They record 969 passing assertion executions (442 distinct labels), with one superseded harness scheduling assertion retained separately; these are not 969 distinct scenarios. Read samples include 269 probes and 197 governed test projections, with HOLD still blocked.

Normal races had no native deadlock. A deliberate opposite-order lock probe produced 40001/1213 and exercised rollback/retry; held-lock probes produced HY000/1205, safe HTTP 503 and no partial writes, followed by a successful retry. These safe infrastructure outcomes are not outstanding corruption. Repository retries recompute work after rollback; all transactions close. The historical harness was removed as required, with results/hashes and cleanup evidence retained. No new harness was run here.

## 9. Database Integrity Review

The authoritative schema SHA256 remains `d3c73231d08047a413f19cf99b76ee769dfe4cbdd3f380bab8b85d8b8d3ded5b`, identical to the final isolated import evidence. Retained MariaDB 10.4.27 metadata includes 48 table/view objects, 69 FKs, 23 CHECKs, primary/unique/index definitions and both source triggers. All 111 non-SQL-mode import statements succeeded in the recorded isolated run.

| Integrity area | Enforcement inspected |
| --- | --- |
| Parents/references | Protocol disease/system and actor FKs; item parent; source creator/verifier/withdrawer/predecessor; unique protocol-source/item-source links; RESTRICT relationships preserve governance records. |
| Revision / approval | Revision >= 1; approved-state pointer coherence; composite FK (protocol ID, current revision, approval event ID) to event identity; publication requires approved and nonempty locales, other publication states empty locales. |
| Sources | Lifecycle metadata checks and predecessor FK; AFTER INSERT/UPDATE triggers reject NEW.replaces_source_id = NEW.id with SQLSTATE 45000. Both actual trigger definitions are retained in runtime metadata. |
| Events | Positive revision, controlled event type, actor requirements, valid JSON/hash format, required submission/approval snapshot and locales, independent approval/rationale/future deadline, reason requirements and risk payload identity/type/value checks. |
| Permissions | Scoped user/system FKs, active-slot uniqueness, nonempty capabilities, reviewer qualification and coherent revocation tuple. |
| Non-strict mode | Explicit risk-value/event-type/risk-payload CHECKs added in L1 prevent demonstrated ENUM coercion gaps; services validate all controlled governance ENUM/SET inputs independently of permissive database behavior. |

Only three SQL-mode directives were intentionally omitted in historical isolated imports, under the approved exception: line 9 `/*!40101 SET SQL_MODE=''*/;`, line 13 `/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;`, and line 1089 `/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;`. No other schema statement was silently omitted. Recorded session/global mode remained `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`. No SQL-mode directive or other SQL was executed during this audit.

Database enforcement is a backstop, not the entire governance policy. The approval FK enforces protocol/revision/event identity, not event_type=approved; service/public validators enforce approved event semantics and cryptographic snapshot equality. Append-only history, bibliographic immutability, legal source transitions and multi-node replacement-cycle detection are application rules, without invented database immutability/cycle triggers. The two approved triggers prevent direct self-replacement only. Privileged direct SQL writers must not be treated as governed application commands.

The retained final isolated evidence contains 97 protocol aggregates and 605 events, with 70 unpublished, 24 withdrawn and three synthetic published protocols. Recorded orphan checks are zero for events/items/disease/system/approval/actors/protocol and item references. This audit independently recomputed all 174 stored nonempty event snapshot SHA256 values: zero mismatches. Historical event revisions can differ from the protocol's current revision after change/reset; that is expected history, not an orphan.

**NOT VERIFIED:** current normal KOBI database deployment, metadata or populated-data integrity. This audit did not connect to it or issue fresh orphan/cycle queries. Foundation closure rests on current schema/code/tests and matching isolated metadata, not an assertion that normal data is migration-complete or orphan-free. Database upgrades remain developer-controlled and require separately authorized deployment validation. Historical cleanup records the disposable database absent, no remaining harness processes/listeners, browser closed and temporary files removed.

## 10. Test Coverage Reconciliation

All following suites were inspected for database/session/log-write-free execution and rerun successfully using PHP 8.2.0. Embedded repeats are excluded from the distinct total.

| Suite in tests/ | Distinct checks | Material coverage |
| --- | ---: | --- |
| treatment-governance.php | 91 | Controlled values, transitions, source replacement self/cycle/valid chains, revision/events, transaction rollback, snapshot and final three locking regressions. |
| treatment-editorial.php | 73 | Actual actor/system scope, revoked/inactive users, source/reference rules, input allowlists, controller/route/CSRF contracts. |
| treatment-review.php | 130 | Submission, independence/qualification, approval/current snapshot/hash, changes-required, risk, review dates/locales, explicit publication and legacy exclusion. |
| treatment-admin-hardening.php | 39 | Escaped review/source presentation, malformed risk, safe permission/database failures and no fail-open. |
| treatment-creation.php | 110 | Draft/optional first item atomicity, revision 1 versus later changes, repeat handling, stale/forged input, rollback, routes and draft public denial. |
| treatment-operations.php | 127 | Withdrawal/reset/fresh edit lifecycle, history projection/order/pagination, publisher-only access, stale submission, rollback, 403/409/422/503/500, HOLD/shared public integration. |
| treatment-prerequisites.php | 47 | 29 risk, seven lifecycle and 11 comparison checks, including eligibility, invalidation and shared comparison behavior. |
| treatment-publication.php | 105 | Substantive gates, source lifecycle, risk/evidence, revision/hash/deadline/locales, explicit projection/leak prevention, HOLD, shared Disease/comparison and consistent read repository orchestration. |
| treatment-publication-correction.php | 17 | CANDIDATE versus DENY independent of mode; exposure still blocked by HOLD; no assessment projection/mode coupling. |
| auth-phase1.php | 45 | Existing authentication/security regression baseline, retained alongside treatment-specific authorization tests. |
| Total | **784** | All suites exit 0, no failed checks. |

The three concurrency correction checks in treatment-governance are supported by retained real MariaDB replay, not only memory simulation. Live creation/operations reports and 4B-L artifacts additionally establish real SQL constraints/triggers, persisted authority, HTTP CSRF/error handling, overlapping workers, transaction rollback and current-source public snapshots. No old live-fixture utility was executed during this read-only task.

Static validation: 45 relevant PHP files passed syntax checks (treatment services/repositories/controllers/admin views/tests, auth baseline, Disease service/repository/view and routes). `git diff --check` passed. Route/method references and alternate public retrieval/history-write paths were inspected. No required foundation invariant was found wholly without implementation or relevant evidence; exhaustive scheduling, production-volume performance, current normal database state and governed public browser rendering remain outside this validation claim.

## 11. Known Limitations / Deferred Items

1. **LOW — operations-list query scaling.** Per-row authorization/scope work produces 108 queries for 51 fetched/50 displayed rows in retained evidence (earlier 32 queries/13 rows). Pagination bounds output; no security/correctness violation was demonstrated. Track batching separately while preserving persisted authorization.
2. **LOW — logical creation uniqueness/idempotency.** The schema has no unique logical-content key or durable request token. Current creation serializes its repeat lookup under the treatment-system row lock; the actual duplicate race had one winner. Direct SQL or a future nonparticipating path can still duplicate logical content. Do not imply database-level uniqueness; design any future invariant separately without banning legitimate distinct protocols for the same disease/system.
3. **INFORMATIONAL — public activation work.** Legacy view fields must be adapted to the localized canonical projection, including escaped rendering and Disease/comparison browser validation. Publication UI/rollout and HOLD changes need separate authorization. This foundation audit does not authorize them.
4. **INFORMATIONAL — deployment and evidence limits.** No fresh normal-database inspection or migration execution; finite Windows/PHP 8.2.0/MariaDB 10.4.27 schedules are not exhaustive isolation/version/load validation. Historical live results are identified explicitly.
5. **INFORMATIONAL — trusted-writer boundary.** Several semantic rules/history immutability are service-enforced; privileged direct SQL can bypass them. Current public read revalidates exposure eligibility. Any additional database immutability/full-cycle hardening is a separately scoped design, not an unimplemented approved trigger requirement.

## 12. Findings

| ID | Severity | Finding | Evidence | Impact | Recommendation | Blocking? |
| --- | --- | --- | --- | --- | --- | --- |
| D2-01 | LOW | Operations list scales queries with rows. | Current service and retained 108-query/50-row probe. | Admin performance at larger volume. | Track authorization-preserving batching separately. | No |
| D2-02 | LOW | No DB logical draft uniqueness/durable idempotency. | Schema lacks such key; system-lock repeat lookup and recorded duplicate race prevent repeats in current path. | Other writers/future paths can duplicate logical drafts. | Record boundary; separately design if required. | No |
| D2-03 | INFORMATIONAL | Public UI adaptation/activation is deliberately deferred. | Legacy title_en/overview_en in disease view; canonical localized projection; explicit 4A deferral; configured HOLD. | UI contract must be completed before exposure. | Adapt and validate public rendering before separately authorized HOLD change. | No for foundation closure; prerequisite for exposure |
| D2-04 | INFORMATIONAL | Current normal database deployment/data not independently inspected. | Audit is connection-free; matching schema hash and prior isolated metadata only. | Cannot certify current deployed records/upgrade status. | Separately authorize deployment verification. | No for foundation closure |
| D2-05 | INFORMATIONAL | Service-enforced history/cycle/semantic guarantees assume governed writers; concurrency evidence is bounded. | Current schema/rules/repositories and historical timing/state evidence. | No claim of arbitrary-SQL protection or exhaustive interleavings. | Preserve service boundary; carry test/deployment limits forward. | No |

No unresolved HIGH or MEDIUM foundation blocker was identified. The earlier HIGH stale-submission race is resolved by the current conditional locking correction, passing regressions and recorded corrected replay; it is not carried forward as an open finding.

## 13. Final Closure Decision

**CLOSE WITH DOCUMENTED LIMITATIONS**

Current implementation and regression evidence support the required governance, lifecycle, authorization, revision/source integrity and fail-closed public boundary. The final stale-submission correction remains present and verified. Remaining findings are the accepted LOW/informational/deferred items above; none grants public exposure or leaves a required foundation invariant unverifiable within this audit scope.

Task file accounting: modified existing files: none; created: `docs/TREATMENT_PHASE_2D_CLOSURE_AUDIT.md`; deleted: none. Final SHA256 comparison against task-start hashes for 15 key governance/public/Disease files, the correction test, schema, routes and bootstrap found zero changes. This is a targeted hash comparison, not a full-worktree integrity certificate. Audit actions wrote only this document; pre-existing worktree changes were untouched. No normal KOBI database writes, schema/configuration/SQL-mode changes or Git commit/push/reset/checkout/merge/rebase occurred.

## 14. Handoff to Medicine Knowledge Foundation

Treatment governance foundation is closed with the documented limitations. Publication HOLD remains active. Treatment publication is not automatically authorized by this audit. Remaining LOW/deferred items are recorded above and must stay visible in future activation/deployment planning.

Medicine Knowledge Foundation becomes the next roadmap phase; no Medicine implementation is performed here. It should reuse the applicable lessons: scoped persisted authority and independent review; explicit evidence/risk/purpose and provenance; immutable revision-bound review snapshots; current source/deadline/locale checks; separate substantive eligibility, approval, publication and exposure; one canonical allowlisted public boundary; safe transaction/error handling; current locking reads for mutation decisions; overlapping runtime evidence alongside database-free regressions; explicit limits under permissive MariaDB. Preserve KOBI's Controller → Service → Repository → Database architecture and existing authentication, CSRF, view and database conventions.
