# Phase 2C-4B treatment administration / governance operations preflight

Date: 2026-10-09, Asia/Calcutta. **Audit status: PASS. Implementation has not started.**

## 1. Executive summary

KOBI already has a substantial administration foundation. Existing routes support scoped editorial listing, editing existing protocol/items, explicit item inclusion/order, shared immutable source creation/replacement, source verification/withdrawal, protocol/item reference management, submission, qualified independent review, risk decisions, approval, changes-required decisions and reviewer history. Recreating these components would duplicate working architecture.

The primary missing operations are creating a new protocol draft and adding a new item. Protocol withdrawal and review reset are implemented service commands with no HTTP/editorial wrappers or forms. A withdrawn protocol already reopens through a substantive edit; a separate restore command would conflict with the approved lifecycle. Publication and permission-grant commands exist internally, but have no administration routes. Publication UI/activation belongs to 4D and must stay deferred.

Two concrete hardening findings merit small corrections: submitted-content presentation omits the saved risk field; authorization availability checks may hide unwrapped database failures. Additional concurrency coverage is needed, rather than assuming the existing lock design is absent. No schema blocker or current public exposure bypass was found through static tracing. Schema sufficiency is based on structure.sql and prior isolated evidence, not a new live metadata inspection.

## 2. Scope, evidence and architecture

Read root `D:/xampp/htdocs/kobi/AGENTS.md` first. Inspected production services/repositories/controllers/routes/views and relevant tests/docs. This task did not load the application, start a server, contact a database, execute regression/live utilities, or perform a transition. Existing workspace changes listed by initial Git status were preserved. Prior 505 regression passes and 107 live passes are historical evidence, not checks rerun by this audit.

Architecture remains Controller -> TreatmentEditorialService -> TreatmentGovernanceService -> repositories -> shared Database/PDO. EditorialService supplies internal read/presentation and input allowlists; GovernanceService owns transactional mutations; GovernanceRules validates controlled values/state; GovernanceSnapshot owns canonical serialization/hash. Reflection DI in `D:/xampp/htdocs/kobi/app/Core/Container.php` shares Database. Repositories use `D:/xampp/htdocs/kobi/app/Repositories/BaseRepository.php` prepared-query/transaction helpers. No separate review/source service is necessary: equivalent functionality is already in these services.

Exact file inventory and shorthand used in the matrices:

| Key | Exact existing file / responsibility |
| --- | --- |
| G | `D:/xampp/htdocs/kobi/app/Services/TreatmentGovernanceService.php` — command transactions, lifecycle, grants |
| E | `D:/xampp/htdocs/kobi/app/Services/TreatmentEditorialService.php` — internal queries/input wrappers |
| A | `D:/xampp/htdocs/kobi/app/Services/TreatmentEditorialAuthorizationService.php` — actor/system capabilities |
| R | `D:/xampp/htdocs/kobi/app/Services/TreatmentGovernanceRules.php` — validation/state/independence |
| S | `D:/xampp/htdocs/kobi/app/Services/TreatmentGovernanceSnapshot.php` — canonical snapshot/hash |
| P | `D:/xampp/htdocs/kobi/app/Services/TreatmentGovernancePolicy.php` — HOLD, evidence/risk, grant administrators |
| GR | `D:/xampp/htdocs/kobi/app/Repositories/TreatmentGovernanceRepository.php` — protocol/items/references/events/internal lists |
| SR | `D:/xampp/htdocs/kobi/app/Repositories/TreatmentSourceRepository.php` — global source catalog and lifecycle persistence |
| AR | `D:/xampp/htdocs/kobi/app/Repositories/TreatmentEditorialPermissionRepository.php` — scoped grants/users |
| AC | `D:/xampp/htdocs/kobi/app/Controllers/TreatmentAdminController.php` — editorial/source HTTP |
| RC | `D:/xampp/htdocs/kobi/app/Controllers/TreatmentReviewController.php` — review HTTP |
| PS | `D:/xampp/htdocs/kobi/app/Services/TreatmentPublicationService.php` — single substantive eligibility/public projection |
| PR | `D:/xampp/htdocs/kobi/app/Repositories/TreatmentPublicationRepository.php` — consistent batched public read model |
| DS | `D:/xampp/htdocs/kobi/app/Services/DiseaseService.php` — disease/comparison integration and preference ordering |
| DR | `D:/xampp/htdocs/kobi/app/Repositories/DiseaseRepository.php` — disease knowledge; treatment placeholder only |
| SQL | `D:/xampp/htdocs/kobi/database/structure.sql` — authoritative schema |
| Routes | `D:/xampp/htdocs/kobi/routes/web.php` — existing registration |
| Edit | `D:/xampp/htdocs/kobi/app/Views/admin/treatments/edit.php` |
| Fields | `D:/xampp/htdocs/kobi/app/Views/admin/treatments/_fields.php` |
| Refs | `D:/xampp/htdocs/kobi/app/Views/admin/treatments/_references.php` |
| Source | `D:/xampp/htdocs/kobi/app/Views/admin/treatments/source.php` |
| SourceCreate | `D:/xampp/htdocs/kobi/app/Views/admin/treatments/source-create.php` |
| Review | `D:/xampp/htdocs/kobi/app/Views/admin/treatment-review/show.php` |
| ReviewContent | `D:/xampp/htdocs/kobi/app/Views/admin/treatment-review/_content.php` |

Other existing views inspected: `D:/xampp/htdocs/kobi/app/Views/admin/treatments/index.php`, `sources.php`, `error.php` and `D:/xampp/htdocs/kobi/app/Views/admin/treatment-review/index.php`, `error.php`. These filenames share the exact parent directory of their first named file.

Test keys: TG=`D:/xampp/htdocs/kobi/tests/treatment-governance.php`; TE=`D:/xampp/htdocs/kobi/tests/treatment-editorial.php`; TR=`D:/xampp/htdocs/kobi/tests/treatment-review.php`; TP=`D:/xampp/htdocs/kobi/tests/treatment-publication.php`; TC=`D:/xampp/htdocs/kobi/tests/treatment-publication-correction.php`; TPre=`D:/xampp/htdocs/kobi/tests/treatment-prerequisites.php`; Auth=`D:/xampp/htdocs/kobi/tests/auth-phase1.php`.

No TreatmentReviewService, TreatmentSourceService, TreatmentEditorialRepository or TreatmentReviewRepository exists. No separate treatment-risk.php, treatment-lifecycle.php or treatment-comparison.php exists; equivalent checks are in TPre. No database/seeds or database/migrations directory was found. `D:/xampp/htdocs/kobi/docs/DATABASE.md` is empty. Upgrade guidance exists in `D:/xampp/htdocs/kobi/docs/TREATMENT_PHASE_2C3_LIVE_TESTS.md`, with later correction/metadata evidence in `D:/xampp/htdocs/kobi/docs/TREATMENT_PHASE_2C3B_L_VALIDATION.md` and `D:/xampp/htdocs/kobi/docs/TREATMENT_PHASE_2C3B_L1_VALIDATION.md`. Earlier checklists are not proof that every listed stress case ran.

## 3. Capability matrix

Each row receives exactly one classification: **A** already sufficient; **B** implemented, needs hardening; **C** partial; **D** missing and required; **E** deferred to 4C/4D; **F** not needed/out of scope. File keys resolve to the exact inventory above. Security shorthand: **scope**=active actor plus actual-system capability; **CSRF**=controller POST/token gate; **rev**=transactional expected revision/state; **ind**=qualification plus contributor exclusion/attestation. Tests identify executable coverage, not new runs.

| # | Capability | Traced implementation / files | Existing tests | Controls | Class / gap |
| ---: | --- | --- | --- | --- | --- |
| 1 | Create protocol draft | No insert command in G/GR/E/AC/Routes; INSERT fixtures exist only in tests | None for application creation | Must add scope/CSRF/audit | D: required |
| 2 | Edit draft | AC.save -> E.save -> G.edit -> GR.updateProtocol/updateItem; Edit/Fields | TE combined/no-op/truncated/stale saves; TG | scope/CSRF/rev | A |
| 3 | Assign disease | Existing IDs display; G.contentValues rejects disease_id edits | TE mass assignment | FK; immutable edit identity | D: initial assignment needed for missing creation; reassignment out of scope |
| 4 | Assign system | Existing system determines grant; no create/transfer operation | TE/TG wrong scope | scope/FK; rejects transfer | D: initial assignment needed; transfer out of scope |
| 5 | Edit title | E.save -> G.edit; PROTOCOL_CONTENT; Fields | TE/TR/TP required content/hash | scope/CSRF/rev/text | A |
| 6 | Edit overview | Same executable save path | TE/TR snapshot/stale | scope/CSRF/rev/text | A |
| 7 | Edit applicability | Same save path | TR/TP prerequisites | scope/CSRF/rev/text | A |
| 8 | Edit limitations | Same save path | TR/TP prerequisites | scope/CSRF/rev/text | A |
| 9 | Protocol evidence | G.contentValues validates EVIDENCE; Fields | TG coercion; TR evidence matrix | controlled enum/rev | A |
| 10 | Evidence summary | PROTOCOL_CONTENT/save/snapshot | TR same-revision hash changes | scope/CSRF/rev | A |
| 11 | Content purpose | Controlled PURPOSE/save | TG/TE/TR/TPre | enum/rev | A |
| 12 | Risk decision | RC.riskEligibility -> E.recordRiskEligibility -> G.recordRiskEligibility | TPre risk controls | review scope/CSRF/rev/ind | B: saved risk omitted from submitted display, see H1 |
| 13 | Add item | No insert path in G/GR/E/AC/Routes | None for application creation | Must bind parent and revision | D |
| 14 | Edit item | E.save item map -> G.edit -> GR.updateItem | TE item IDOR/combined save | parent membership/scope/CSRF/rev | A |
| 15 | Remove/exclude item | is_included=0 through existing save; no hard delete | TE inclusion; TP excluded projection | scope/CSRF/rev | A: exclusion sufficient; hard deletion unnecessary |
| 16 | Reorder items | sort_order fields in save; included orders positive/unique | TE zero/negative/duplicate; TP ordering | scope/CSRF/rev | A: numeric ordering supported, no drag UI needed |
| 17 | Item evidence | ITEM_CONTENT enum save | TE/TR/TP | controlled enum/rev | A |
| 18 | Item limitations | ITEM_CONTENT save, required on prepared locales | TE/TR/TP | scope/CSRF/rev | A |
| 19 | Add source | AC.storeSource -> E.createSource -> G.createSource -> SR.insert | TG/TE creation/date/URL | edit scope/CSRF/transaction | A |
| 20 | Edit source bibliography | Immutable; SourceCreate creates corrected replacement | TE immutable replacement | new source/version; original untouched | A: in-place metadata editor deliberately absent |
| 21 | Verify source | AC.verifySource -> E.verifySource -> G.verifySource -> SR.verify | TG/TE/TR explicit verification | review scope/CSRF/source lock | A: provenance only; self-verification not prohibited |
| 22 | Withdraw source | AC.withdrawSource -> E.withdrawSource -> G.withdrawSource | TG cross-system; TE/TR invalidation | edit scope plus publisher authority for every affected system/CSRF/locks | A |
| 23 | Replace source | E.sourceForm copies bibliography/predecessor; G.createSource validates chain | TG/TE direct self/cycles/finite chain | scope/CSRF; immutable predecessor/FK/triggers | A |
| 24 | Attach protocol source | AC.reference -> E.reference -> G.reference -> GR.link | TE explicit/duplicate attachment | scope/CSRF/rev/source lock | A |
| 25 | Attach item source | Same, validates item belongs to protocol | TE item IDOR/no inheritance | parent membership/CSRF/rev | A |
| 26 | Remove relationship | E.reference operation remove -> G.reference -> GR.unlink | TE removal/preserved source | scope/CSRF/rev/existing relationship | A |
| 27 | Submit | AC.submit -> E.submit -> G.submit | TG/TE/TR readiness/fresh revision | scope/CSRF/rev/snapshot | A |
| 28 | Review | RC.index/show -> E.reviewListing/reviewDetail; G.reviewAssessment; Review | TR detail/queue/qualification | review scope/no-store/escaped projection | B: H1 presentation defect |
| 29 | Approve | RC.approve -> E.approveReview -> G.approve | TR exact snapshot/submission/duplicate; TG | scope/CSRF/rev/ind/future due | A |
| 30 | Request changes | RC.changesRequired -> E.requestReviewChanges -> G.requestChanges | TR rationale/stale/duplicate/correction | scope/CSRF/rev/submission ID | A |
| 31 | Reset approval | G.resetReview exists; no E/controller/route/form | TG fresh reset/new submission | review scope/rev/reason/transaction | C: administration wrapper/form absent |
| 32 | Withdraw published protocol | G.withdraw exists; no E/controller/route/form | TG publisher/reason; TPre lifecycle | publish scope/rev/reason/transaction | C: publisher-only administration access absent |
| 33 | Reopen withdrawn | E.save -> G.edit -> saveInvalidation; Edit instructions | TPre withdrawn no-op/edit | scope/CSRF/rev; real change required | A: no restore shortcut |
| 34 | New revision after withdrawal | saveInvalidation changes withdrawn to unpublished after material edit | TPre full lifecycle | same edit controls | A |
| 35 | Revision tracking | R.invalidate; G.saveInvalidation/risk; GR events | TG combined/no-op; TPre | locks/rev/overflow guard | A |
| 36 | Append event | G.event -> R.event -> GR.appendEvent | TG validation; TR rollback/history | authenticated actor/transaction | A: append-only through app, not DB write-proof |
| 37 | Approval snapshot | G.approve -> S.capture -> GR.appendEvent | TG canonical; TR corruption | exact submitted/current equality | A |
| 38 | Approval hash | S.canonical/capture SHA256; R.event | TG/TR/TP integrity | hash pairing/current aggregate | A |
| 39 | Reviewer qualification | A.require -> R.grant; nonblank qualification_scope | TG/TR missing qualification | active actual-system can_review | A: approved recorded-scope model, not credential registry |
| 40 | Reviewer independence | G.approve/risk -> R.independence -> GR.contributors | TG/TR/TPre self-authorship | historical material contributors plus attestation | A |
| 41 | Edit authorization | AC auth -> E.detail/list -> A.require; G repeats under locks | TE/TG wrong scope/revoked | scope/CSRF/no client actor | B: A.can storage masking H2, no fail-open |
| 42 | Review authorization | RC auth -> E.reviewAccess -> A.require; G locked check | TR direct POST/IDOR/revoked | scope/CSRF/ind | B: A.can H2 for availability/listing |
| 43 | Publication authorization | G.publish requires can_publish; PS read mode independent | TG reviewer denied; TP/TC HOLD | publisher scope plus read exposure gate | E: HTTP publication/activation deferred 4D |
| 44 | No direct published edit | G.edit/reference reject; risk requires unpublished; Edit disabled | TG/TE/TPre | server checks, not only UI | A |
| 45 | Stale overwrite prevention | G.command checks revision/state under protocol lock | TG/TE/TR | FOR UPDATE plus expected tokens | A for current commands; new creation/item actions need tests |
| 46 | Invalid transitions | G submit/approve/requestChanges/resetReview/withdraw; R coherence | TG/TR/TPre | state/publication checks/rev | A |
| 47 | IDOR prevention | E resolves protocol then requires actual system; item membership | TE/TR/TP | scope/no posted actor/grant | A: source catalog is intentionally global, not owned by URL system |
| 48 | CSRF | AC/RC.handle POST + Session.verifyCsrfToken; csrfField forms | TE all POST; TR decisions; TPre risk | token before service writes | A |
| 49 | Transactions | GR.transaction begin/commit/rollback; shared PDO | TG rollback/retries; prior live rollback | atomic commands; no nesting | A |
| 50 | Locking/concurrency | G.command/source withdrawal lock order; GR/AR/SR FOR UPDATE | TG instrumentation; historical reviewer race | locked users/grants/system/sources/protocol/children | C: broader actual race coverage missing, not missing locks |
| 51 | Errors | AC/RC safe PDO/wrapped failures, 403/409/422/503/500 | TE/TR storage/CSRF/stale | no SQL text returned, class-only logs | B: A.can may mask PDO, H2 |
| 52 | History visibility | E.reviewDetail -> GR.history -> Review, cursor bounded 51 | TR escaped history/cursor | reviewer scope; no raw JSON | C: editors/publisher-only actors cannot inspect history; snapshots not every edit |
| 53 | Locale governance | R.locales en/hi; submission/approval/publish sets | TG/TR/TP/TC | controlled set/subset/no fallback | A |
| 54 | Source lifecycle | G.verifySource/withdrawSource; R.sourceTransition/replacement; SQL | TG/TE/TR; prior DB evidence | terminal withdrawal/current status/metadata/chain | A |
| 55 | Risk/evidence enforcement | G approvalReadiness/risk/publish + P; PS eligible | TPre/TR/TP/TC | independent risk; explicit evidence; new review | A for enforcement; row12 covers display hardening |
| 56 | Public integration | DS.withPublicTreatments -> PS; DR empty placeholder | TP/TC/TPre + prior live | canonical allowlist/HOLD/read-only | A: no exposed bypass found |

## 4. Current state machine

Review and publication are separate axes. No reviewed/approved state itself authorizes public exposure.

| Existing command | Allowed starting condition | Result | Revision / approval / events |
| --- | --- | --- | --- |
| edit / reference, material change | Matching current revision/state; publication not published | draft for ordinary states; changes_required stays changes_required | +1 once; clears pointer/locales; content_changed |
| submit | draft or changes_required AND unpublished, active system; complete requested locales/verified support | pending_review/unpublished | same revision; submitted snapshot/hash; changes_required requires revision later than latest decision |
| approve | pending_review/unpublished/current submission; qualified independent reviewer; readiness | approved/unpublished | same revision; approved event/pointer; assessed locales/current future due |
| requestChanges | pending_review, not published, matching latest submission | changes_required | same revision; changes_required reason; approval cleared; does not rewrite content |
| resetReview | approved or pending_review, not published | approved -> pending_review with fresh submitted event; pending_review -> draft | same revision; approval cleared; review_reset; approved reset reuses prior assessed locales |
| publish | approved/current coherent approval and eligible content, not withdrawn; publisher grant | published with explicit locale subset | same revision; published or publication_locales_changed event; identical published locale set no-op |
| withdraw | published + supplied current revision/state; publisher grant and reason | withdrawn/draft | same revision; pointer/locales clear; withdrawn event |
| withdrawn material edit/reference | withdrawn + matching revision/state | unpublished draft | +1; clears approval/locales; content_changed; history retained |
| withdrawn no-op edit | withdrawn | unchanged withdrawn | no revision/event; cannot submit or republish |
| changed risk decision | unpublished + current revision/state; independent reviewer | draft, or changes_required retained | +1; risk_assessed snapshot; approval/locales clear; resubmission required |
| source withdrawal | unverified or verified source, all affected withdrawal grants | source withdrawn; protocols invalidated; published ones withdrawn | +1 per affected protocol; draft; pointer/locales clear; source_invalidated; relationships retained |

Invalid paths: draft approval, unchanged changes-required resubmission, stale revision/state/submission decision, duplicate approval, edit/reference/risk mutation of published content, withdrawal of nonpublished content, restoration of withdrawn source, and republishing/submitting unchanged withdrawn protocol. No transition automatically exposes content under HOLD. Review reset and explicit protocol withdrawal do not increment revision; their state checks prevent replay despite an unchanged revision. An old submitted form cannot approve after reset because the latest submission ID changes.

## 5. Revision / history audit

G.edit compares normalized values, includes protocol text/purpose/evidence and existing item text/type/purpose/evidence/inclusion/order/limitations, then performs a single saveInvalidation before updates. G.reference revises on actual attach/update/remove only; no-op is preserved. A changed risk decision revises once with a canonical risk snapshot; unchanged decision does not. Source withdrawal revises every affected protocol once, including references on excluded items. Source creation/replacement/verification do not alter protocol references or revision. Replacing a relationship explicitly revises through reference commands.

All material edits clear approved_review_id and published_locales, invalidate old submission/approval through new revision/state, and require fresh submission/review. Content purpose/applicability/limitations and all language variants are governed; changing Hindi also invalidates approval globally. Stored risk decision is not automatically reset by ordinary content edits, but is part of the newly assessed snapshot and fresh independent approval; do not silently introduce a new risk policy.

Publication/locales events change no content_revision and do not enter governed snapshot content. Review transitions, explicit protocol withdrawal and reset preserve revision; source invalidation and changed risk increment it. Revision overflow is guarded. Full canonical snapshots are stored for submission/approval/risk, and legacy initialization; ordinary content_changed events record revision/actor/reason without a full per-edit snapshot. History therefore is an event/review record, not arbitrary revision restoration or a complete diff of every saved draft. No restoration UI is needed for minimal 4B.

## 6. Source lifecycle and permissions

Allowed lifecycle is unverified -> verified, unverified -> withdrawn, verified -> withdrawn. Withdrawn is terminal. Bibliographic fields and replaces_source_id are immutable through service/repository APIs. Corrections create an unverified replacement, preserve predecessor/history and leave all references unchanged. Replacement chains reject self, cyclic or missing predecessors; database triggers only prevent direct self-replacement. No provenance operation claims effectiveness or changes protocol evidence.

Source catalogs are shared globally: SR.catalog/find do not have a system ownership filter, and the source table has no treatment_system_id. The URL system provides a capability context. An editor in any valid context can create a shared source; a qualified reviewer in a valid context can verify it. **Self-verification is not prohibited**: verifySource has no created_by/actor inequality or independence check. This is exact current provenance policy, not an established independent medical review rule. Do not invent a source-owner model, credential taxonomy or independence requirement without a policy decision. Protocol/risk self-assessment protections remain enforced separately.

Source withdrawal additionally needs can_edit (manage_sources) and can_publish (withdraw) for the supplied context, plus withdrawal authority across every affected system. It locks/rechecks the affected set, invalidates all protocols atomically, retains references and withdraws published ones. There is no separately modeled source-manager capability. Public assessment rechecks current source status even though lifecycle metadata is excluded from bibliographic snapshot/hash.

## 7. Authorization matrix and security

| Operation | Existing capability / scope | Other requirements |
| --- | --- | --- |
| Editorial list/detail/save/reference/submit | can_edit, actual treatment_system_id | active user, nonrevoked valid grant, POST/CSRF for writes |
| Review list/detail/approve/request changes/reset/risk | can_review, actual system | nonblank recorded qualification_scope; approve/risk require independence; reset currently lacks HTTP path |
| Create source | can_edit in supplied valid system | global source catalog; strict bibliography validation |
| Verify source | can_review in supplied valid system | provenance transition and source lock, no self-verifier prohibition |
| Withdraw source | can_edit context + can_publish every affected system/context | reason, locked affected-set recheck |
| Publish / withdraw protocol | can_publish, actual system | distinct command from approval; publish UI deferred, withdraw UI missing |
| Grant/revoke permission | positive-ID deployment administrator allowlist | active actor, locked users/grants/system; revoked rows retained |

There is no generic logged-in admin bypass. `can_review=1` requires nonblank qualification text but no external credential validation/taxonomy. Reviewer and publisher may be the same account if both grants exist; no separation-of-persons rule applies to publication. Actual material authors cannot approve/risk-assess even with review rights; GR.contributors uses all historical content_changed actors, not only the latest revision. Unknown legacy authorship requires explicit attestation.

AC/RC.handle explicitly authenticate via AuthService::id(), enforce POST and CSRF before invoking services, set private/no-store and noindex headers, then call E's scope checks. Scope authorization is in E/A and repeated under G's locks rather than an inline controller permission query. Every current exposed action was traced to these checks; directly posting cannot rely on hidden UI controls to bypass them. Posted actor IDs, user IDs, permission flags, review/publication metadata and cross-system identity changes are rejected by allowlists. Item edits/references check membership in the resolved protocol. No treatment administration AJAX endpoint exists. Preserve this separation when adding actions.

Permission grant/revoke operations already retain revocation history and use generated active-slot uniqueness. No HTTP permission UI exists. Config currently defines an empty permission_admin_user_ids list; granting real permissions is a deployment-controlled setup decision, not a reason to infer permissions or change configuration in 4B.

Concrete findings:

- **H1 (B, presentation integrity):** G.reviewAssessment constructs submittedContent.protocol using PROTOCOL_CONTENT plus id/revision, omitting risk_eligibility although S.build stores it. ReviewContent defaults the missing risk field to not_assessed. A saved eligible submitted snapshot can therefore be displayed as not_assessed in the submitted-content panel. Approval enforcement still uses real data and stays correct. Minimal correction: explicitly include the existing governed risk field in the internal submitted projection and test distinct current/submitted risk display; no new public field.
- **H2 (B, error propagation):** A.can catches RuntimeException and only rethrows when getPrevious() is nonnull. PDOException is a RuntimeException and raw repository fetches can throw one with no previous exception. Thus a database outage in actor/grant lookup can become false/denied access or hidden buttons, instead of reaching safe 503 handling. It fails closed, not open. Minimal correction: propagate persistence/programming failures while returning false only for deliberate authorization denial; tests must show unauthorized=false and storage error safely reaches controllers.

Controllers map messages containing `Reload and retry` to 409 and ordinary validation/invalid transitions to 422; storage faults to 503; unexpected failures to 500. Existing revision/reference/submission mismatch messages match the stale-response phrase. No separate stale-response defect was demonstrated. New actions need tests preserving this convention rather than a framework-wide exception refactor.

No demonstrated scope/CSRF/IDOR fail-open defect was identified. Self-source-verification, recorded qualification text, combined reviewer/publisher roles and global source access are policy properties requiring explicit decisions only if changed; they are not automatically security bugs.

## 8. Concurrency audit

G.command validates inputs, enters GR.transaction, reads initial protocol, locks authenticated user/current grant and system, gathers source IDs, locks sources in ascending order, then locks protocol and children. After locking it rechecks revision/state/system and discovers newly referenced sources; a changed set causes reload/retry. It replaces snapshot source rows with locked current lifecycle states. Consequently permission revocation or source withdrawal cannot be ignored by relying on an earlier aggregate.

G.withdrawSource takes the initial affected set, locks sorted affected systems, source, current affected protocols and rechecks newly discovered systems. Protocol reference writers lock source before protocol. Deadlocks remain possible because actor/system combinations and overlapping work serialize differently; GR.transaction rolls back and retries error 1213 at most twice after the first attempt, rerunning authorization and aggregation. Other SQL errors are wrapped safely and not retried; lock-wait timeout 1205 is not specially retried. Nested transactions are rejected.

| Race | Existing protection | Evidence / remaining work |
| --- | --- | --- |
| Two editors, same revision | protocol lock + revision/state check; one material save increments | TE stale sequential fixture; prior MariaDB stale read; simultaneous editor race deferred |
| Edit vs submit/approve | locks and revision/state/snapshot checks | TG/TR sequential stale rejection; actual concurrent schedules deferred |
| Two reviewers/duplicate approval | protocol lock, pending state, latest submission ID | historical live two-process race: one approval and one rejected; one event/pointer |
| Approval vs changes-required | pending state + submission ID under locks | sequential/duplicate tests; actual race deferred |
| Withdrawal vs edit | direct published edit rejected; withdrawal changes state | service checks present; both real serial schedules need validation |
| Source withdrawal vs approval/reference | source-first/current-source locks; affected-set recheck | lock-order instrumentation, current-state/rollback tests; true race deferred |
| Grant revocation vs command | active actor/grant locked and revalidated on retry | revoked grant tests; real simultaneous revocation coverage absent |
| Retry after deadlock | rollback then whole closure reexecution | PDO double tests bound attempts; forced real MariaDB deadlock deferred |

GR.updateFields and SR.verify/withdraw do not independently assert affected-row counts or include revision CAS predicates. For current service callers, locked existing rows and transition checks supply that guarantee. This is not proof of a stale-write bug. New insert/item/withdraw wrappers must retain locks, verify insertion outcomes and atomic event/revision changes; do not substitute blind repository calls. Unique active grant and source relationship indexes prevent duplicate records, but no unique protocol disease/system pair exists. Repeated draft/item creation requests have no existing command/idempotency semantics because those commands are missing.

## 9. UI, routes, JavaScript and error handling

Exact existing routes (path registration; framework has no separate names here):

| Verb/path | Controller method |
| --- | --- |
| GET /admin/treatments | AC.index |
| GET /admin/treatments/{protocolId}/edit | AC.edit |
| POST /admin/treatments/{protocolId}/save | AC.save |
| POST /admin/treatments/{protocolId}/submit | AC.submit |
| POST /admin/treatments/{protocolId}/references | AC.reference |
| GET /admin/treatment-sources/{systemId} | AC.sources |
| GET /admin/treatment-sources/{systemId}/create | AC.createSource |
| POST /admin/treatment-sources/{systemId}/create | AC.storeSource |
| GET /admin/treatment-sources/{systemId}/{sourceId} | AC.source |
| POST /admin/treatment-sources/{systemId}/{sourceId}/verify | AC.verifySource |
| POST /admin/treatment-sources/{systemId}/{sourceId}/withdraw | AC.withdrawSource |
| GET /admin/treatments/review | RC.index |
| GET /admin/treatments/{protocolId}/review | RC.show |
| POST /admin/treatments/{protocolId}/approve | RC.approve |
| POST /admin/treatments/{protocolId}/changes-required | RC.changesRequired |
| POST /admin/treatments/{protocolId}/risk-eligibility | RC.riskEligibility |

All 16 routes are internal, two controllers, eleven editorial/source plus five review. No draft/item create, protocol withdrawal/reset, publication or permission route exists. Publisher-only actors cannot use E.listing/detail (can_edit required) or reviewer history (can_review required); adding a withdraw button to Edit alone would leave publisher-only users without an authorized access path. Reuse A's existing `view`/`withdraw` capabilities and GR internal reads for a narrowly scoped operation/detail path; do not broaden edit/review privileges.

Editor forms support bilingual fields, existing items, inclusion, numeric ordering, sources and submission. Published fieldsets are disabled and source mutation controls hidden, backed by service rejection. Withdrawn instructions describe the real substantive edit cycle. Full-form completion marker detects PHP form truncation. Review views show qualification, canonical match, live sources, projected submitted content, explicit risk decision, independent attestation, UTC due timestamp, rationale and cursor-paginated history. Review and source pages use Bootstrap controls, labels, escaping e(), csrfField(), $this->view() and global redirect(); normal successes redirect to existing edit/source/review paths. Errors escape text and hide SQL/internal failures.

No treatment admin/review page-specific JavaScript or AJAX is present; native forms/details/selects suffice. Templates contain no inline scripts or event handlers. `D:/xampp/htdocs/kobi/public/assets/js/app.js` preserves the existing global KOBI configuration. The related `D:/xampp/htdocs/kobi/app/Views/account/treatment-preferences.php` does contain an existing inline drag/drop script. AccountController save/reset enforce authentication and CSRF; TreatmentPreferenceService and UserTreatmentPreferenceRepository persist only the authenticated user's ordering. That preference UI is outside 4B; do not copy its inline script into administration or refactor it here.

No CSP directive definition was found in inspected app/public PHP, JS or project .htaccess files; server-provided CSP cannot be assessed without a server, which this audit did not start. Preserve any existing deployment CSP and use script-free administration forms. Previous browser evidence reported unchanged CSP/clean console; it is historical, not a fresh header verification.

## 10. Public boundary audit

Internal E/GR/SR reads intentionally use governance data and never PS public reads, so HOLD does not hide necessary editorial content from authorized staff. Public `D:/xampp/htdocs/kobi/app/Controllers/DiseaseController.php::show` calls DS.getKnowledgeBySlug; `D:/xampp/htdocs/kobi/app/Controllers/ComparisonController.php::result` calls DS.getComparison. Both use withPublicTreatments -> PS.getPublicTreatmentsForDisease. DR.findKnowledgeBySlug supplies an empty placeholder, not a raw treatment SQL fallback. Preferences reorder eligible results only, through DS.applyTreatmentPreference; they cannot create results/counts.

PS.assessProtocolForPublicRead returns DENY/CANDIDATE using the same private eligible predicate as retrieval without consulting mode. PS.read separately requires exactly governed; HOLD/unknown modes return null/empty without treatment queries. Review approval saves approved/unpublished and never invokes G.publish. Editing clears approval and forces resubmission. Bibliographic replacement leaves existing sources/references unchanged; relationship edits revise; actual source withdrawals invalidate and are rechecked by PS reads. Current risk/evidence/source/locale/snapshot integrity gates remain intact.

Existing G.publish is an explicit persisted-state command authorized by can_publish; it does **not** consult mode, because HOLD gates exposure rather than all internal state changes. It has existing readiness checks; it is not a new 4B route and must not be exposed by this plan. Its validation and PS read eligibility are not interchangeable: the assessment requires already-published state. Future 4D must align publication authorization with canonical eligibility without duplicating a new read predicate or bypassing HOLD. No public exposure bypass was found in current executable administration routes.

Config source `D:/xampp/htdocs/kobi/app/Config/Config.php` uses env('TREATMENT_PUBLICATION_MODE','hold'); targeted environment-file inspection found no override for that key. The unchanged workspace configuration remains HOLD. No runtime was booted to revalidate deployment environment injected by another process.

## 11. Legacy content

SQL defaults/R.legacyProtocol keep existing content draft/unpublished revision 1, null approval/empty published locales, unassessed evidence/risk, null purpose/educational governance defaults. Items remain excluded/unassessed. G.initializeLegacy is a restricted administrator, idempotent append-event operation: it refuses nondefault assessed metadata or fabricated references and never creates fake reviewer identity/approval. There is no legacy-initialization UI route.

Existing editor pages display legacy rows without automatically classifying, including, verifying or publishing them. Inclusion requires explicit save. Submission requires complete governed content, assessed items, verified explicit support and coherent locales. Approval adds independent qualified risk/evidence/snapshot gates. HOLD finally blocks exposure. No mass legacy approval/migration should be added. This audit did not inspect or change actual database legacy records.

## 12. Database/schema audit and impact

SQL defines:

- treatment_protocols: disease/system FKs, bilingual content; review/publication enums; unsigned revision; composite approval pointer; risk; published locale SET; purpose/evidence/educational fields; updated actor/time. Candidate index `(disease_id,publication_status,review_state,treatment_system_id,id)`, revision/pointer index; revision, risk-value, approval-pointer and publication/locales CHECKs.
- treatment_protocol_items: parent FK; controlled type; bilingual title/instructions/limitations; inclusion/evidence/purpose/order; parent and display indexes; inclusion CHECK. No DB positive/unique sort-order CHECK: service enforces it.
- treatment_sources: immutable application bibliography; lifecycle actor/timestamps/reason; predecessor FK; creator/verifier/withdrawer/status/predecessor indexes; nonblank identity and lifecycle metadata CHECKs. AFTER INSERT/UPDATE `trg_treatment_source_no_self_insert` and `trg_treatment_source_no_self_update` reject NEW.replaces_source_id=NEW.id. General cycles and transition irreversibility remain service rules, not those triggers.
- treatment_protocol_sources / treatment_item_sources: parent/source restrictive FKs; unique parent/source pair; reverse source indexes; nonblank claim CHECK. No implicit inherited support.
- treatment_protocol_events: protocol/actor restrictive FKs; history/action/actor indexes; unique `(protocol_id,content_revision,id)` for composite approval FK. Controlled event enum, locale SET, revision/actor/independence/JSON/hash-format/reason/snapshot/approval CHECKs; risk event and typed JSON payload CHECKs bind risk decision/protocol/revision to the event. These do not make all event rows immutable against arbitrary SQL or prove hash content equality; services do.
- treatment_editorial_permissions: user/system/authorizer/revoker FKs; explicit can_edit/can_review/can_publish; nonblank reviewer qualification; revocation metadata; generated active_slot and unique active user/system grant; capability/qualification/revocation CHECKs.
- treatment_systems, diseases, users supply identity/active status. User treatment preferences have distinct user/system and order unique keys; preferences are not editorial rights.

The existing schema can support draft/item creation and protocol withdrawal/reset administration without a migration. No schema blocker was found. There is no disease/system protocol uniqueness rule, full per-edit revision store, separate source-manager/credential taxonomy or grant UI. Do not infer these from columns or add them without scope approval. Planned draft creation must record authenticated authorship using the existing event vocabulary and defaults; it must not use a null-actor legacy event for new content. An existing content_changed event at initial revision can record creation without inventing a new schema event; exact new API naming is for implementation review, not asserted as existing.

Permissive MariaDB enum/set coercion remains a known database property. Existing service allowlists protect controlled values; every new creation input must use R validators. Keep global/session/project SQL mode unchanged. structure.sql is a destructive full schema dump, not an existing-database upgrade script. No SQL should be applied for this audit or for the proposed minimal code/UI phases absent separately approved schema needs.

## 13. Test coverage audit

| Area / exact tests | What existing coverage proves | Remaining validation |
| --- | --- | --- |
| TG draft submission/independent approval; published edit rejection; reset; withdrawn cannot republish | legal core transitions, pointer/state coherency, historical reset and withdrawal semantics | actual HTTP reset/withdraw paths do not yet exist |
| TG one revision/no-op/stale; canonical hashes; permission scope/revocation; lock order/retry | material changes and source relationships invalidate once; controlled values; bounded retry/rollback via doubles | real simultaneous editor/source/grant schedules; creation authorship |
| TE auth/IDOR/CSRF/mass assignment/form truncation; combined save; source lifecycle/reference tests | current controller entry points and semantic edit/source safeguards; script-free escaped forms | new create/item/withdraw actions and publisher-only scope |
| TR reviewer scope/qualification/independence; exact submission/hash; duplicate/stale decisions; current source/rationale/UTC | real executable approval rules in memory and controller/test rendering | submitted risk presentation; H2 outage classification; new reset forms |
| TPre 29 risk /7 lifecycle /11 comparison checks | independent saved risk revisions; withdrawn material/no-op behavior; shared HOLD comparison boundary | new draft/item commands plus live operation concurrency |
| TP 105 checks, TC 17 correction checks | one canonical substantive predicate, mode independent assessment, exact projection, whole-protocol fail-closed, source/expiry/locale, preferences and batched read orchestration | public exposure remains intentionally held; no 4B need to switch mode |
| Auth 45 historical checks | authentication regression baseline, not a treatment permission model | rerun after implementation if affected dependencies justify it |

Historical live evidence: `D:/xampp/htdocs/kobi/docs/TREATMENT_PHASE_2C_VALIDATION_FINAL.md` records 58 core live, 23 HTTP, reviewer race and observed administration browser workflows, with forced deadlock/editor/source races explicitly deferred. `D:/xampp/htdocs/kobi/docs/TREATMENT_PHASE_2C_CONCURRENCY_RESULTS.json` records exactly one approval from two reviewers. The older approved-page warning was later corrected in 2C-3A (current TR tests); do not reclassify that resolved issue as open. Initial unresolved evidence policy notes were superseded by 3A/3B policy lock-in.

`D:/xampp/htdocs/kobi/docs/TREATMENT_PHASE_2C3B_L1_VALIDATION.md` records non-strict risk/event integrity and application/lifecycle validation. `D:/xampp/htdocs/kobi/docs/TREATMENT_PHASE_2C4A_PUBLIC_READ.md`, `D:/xampp/htdocs/kobi/docs/TREATMENT_PHASE_2C4A_L_LIVE_VALIDATION.md` and corresponding runtime JSON establish the latest 505 distinct automated/107 live evidence; the latter documents internally inspected serializer and actual HOLD disease/comparison/HTTP results, not positive public publication rendering. No historical count is claimed as newly executed here.

## 14. Gap classification register

| ID | Classification | Finding / disposition |
| --- | --- | --- |
| G1 | D | New protocol draft command and initial disease/system selection missing |
| G2 | D | New item command missing; exclusion already sufficient for removal |
| G3 | C | Protocol withdraw service exists; publisher-only authorized operations read/action UI missing |
| G4 | C | Review reset service exists; wrapper/form absent |
| G5 | C | History only available to reviewers; expose a bounded internal history to authorized editor/publisher operations without granting review |
| G6 | C | Real contention coverage incomplete despite existing transaction/locking architecture |
| H1 | B | Saved risk omitted from submitted-content presentation |
| H2 | B | Availability authorization can mask raw PDOException |
| K1 | A | Existing edit/items/reference/source/submit/review/risk/lifecycle/public boundaries largely sufficient; preserve |
| K2 | A | Source bibliography immutable replacement and global catalog are intentional |
| D1 | E | Publication UI/activation and alignment of future publication command/read requirements — 4D |
| D2 | E | Public treatment UI/API presentation — 4C/later authorized scope |
| O1 | F | Hard deletes, restoration shortcut, full revision restoration/diff engine, system transfer, new roles/credential taxonomy |
| O2 | F | Permission administration UI is not necessary for minimum 4B; reuse deployment-managed existing grants |
| O3 | F | Preference inline script and deployment CSP ownership; preserve, do not refactor here |

## 15. Recommended minimal subphases

New method/route names below are deliberately not invented. All named classes/methods/files are existing. Exact names for genuinely new create/item operations should be proposed with their implementation request. Keep all phases under HOLD and stop between separately authorized tasks.

### 4B-1 — Internal presentation and authorization error hardening

- Objective: fix H1/H2 without broad exception redesign; preserve existing stale/validation response semantics.
- Reuse G.reviewAssessment, E.reviewDetail, A.can, AC/RC.handle and ReviewContent.
- Likely files: G, A, E if necessary, AC/RC only where response distinction is needed, ReviewContent only if projection correction alone is insufficient; TR, TE, TPre as relevant tests. No new production classes/files necessary.
- Database impact: none. Authorization impact: deliberate denials remain closed; storage faults propagate to safe handlers. No role/capability expansion.
- Required tests: actual saved submitted eligible/not-eligible risk displays accurately; current/submitted values not conflated; unavailable storage is not silent false; normal unauthorized=false; generic error has no SQL/paths; stale vs validation response distinction.
- Security/dependencies: preserve no-store/escaping/snapshot allowlists; no live DB needed for automated tests. Out of scope: source self-verification policy, qualification taxonomy, public projection changes, publish UI.

### 4B-2 — Draft creation and new item administration

- Objective: add only missing G1/G2 with initial disease/system selection, authenticated authorship and an initially excluded/unassessed item. Existing item exclusion/order/editor remains the primary interface.
- Reuse E input/read patterns, G command/invalidation/event rules, GR transaction/insert pattern through BaseRepository, A scoped rights, R/S validation, AC forms/routes and existing Edit/Fields.
- Likely files: G, GR, E, AC, Routes, Edit, editorial index view, TE/TG/TPre. New server-rendered create/form view files only if existing partials cannot express the new forms; no duplicate repository/service/controller or JS file. Names to be selected during authorized implementation.
- Database impact: intended application inserts only when later authorized/run; no schema migration expected. No assumed disease/system pair uniqueness. Define/review duplicate form submission behavior explicitly rather than silently adding a unique constraint or collapsing multiple protocols supported by the reader.
- Authorization impact: can_edit in selected actual system; validate disease exists and scoped system; no client actor/grant fields; block identity transfer on existing protocols.
- Required tests: minimal draft defaults (revision1, no approval/locales, not_assessed evidence/risk, no automatic refs); creation author contributor recorded; creator cannot self-approve; parent/item IDOR; published add rejected; withdrawn add as material edit enters new unpublished revision; one parent revision/event, no partial writes on insert/event failure; invalid enum/SET/content/order; CSRF, guest/wrong-scope/revoked, direct POST, repeated/stale forms.
- Security/dependencies: after 4B-1; lock existing parent/source/permission state and preserve overflow checks; keep user/health data out of forms/events. Out of scope: hard deletes, medicine integration, identity reassignment, migration, automatic inclusion/classification.

### 4B-3 — Withdrawal, review reset and bounded operations history

- Objective: expose existing G.withdraw/G.resetReview with narrowly scoped internal reads for publisher-only/editor/reviewer roles, plus retained bounded history. Reopening continues through a real edit, not an independent restore action.
- Reuse A.require('view'/'withdraw'/'review'), E/GR detail/history patterns, G commands, AC/RC.handle, existing review/editor layouts/errors.
- Likely files: E, AC, RC, Routes, GR only for minimal bounded authorized operations query; Edit/Review/internal index views; TE/TR/TPre. An additional internal operations/detail view only if necessary for publisher-only access; no publication administration controller/service.
- Database impact: no schema change. Later authorized runtime command writes only existing state/events; withdrawal preserves revision and history, reset preserves revision and creates current submission when required.
- Authorization impact: can_publish grants withdrawal only; does not imply edit/review. can_review grants reset; editor history read must not grant approve. Controllers authenticate and POST/CSRF, services resolve actual-system scope again under locks.
- Required tests: publisher-only can inspect/withdraw own system but cannot edit/approve; wrong scope/revoked/guest/CSRF denied; reason required; stale revision/state rejection; duplicate withdrawal/reset; published reset rejected; approved reset fresh submission ID; unchanged withdrawn edit stays withdrawn; fresh edit/review cycle; bounded escaped history with no raw snapshot JSON or public use.
- Security/dependencies: after 4B-1 and preferably 4B-2 for complete user workflow; preserve event actors/reasons and safe redirects. Out of scope: publish button, grant UI, restore shortcut, content rollback, mode switch.

### 4B-L — Isolated administration/security/concurrency validation

- Objective: validate implemented 4B paths on disposable MariaDB 10.4.27 and loopback HTTP/browser with synthetic actors and content, plus outstanding G6 races.
- Reuse existing regression scripts and inspected validation fixtures/router patterns; preserve DB identity guards and isolated paths. Likely durable changes: validation report/results and only focused tests required by new actions. Temporary utilities must be removed; do not blindly execute existing live helpers using their default connection settings.
- Database impact: separately authorized disposable schema/fixtures only, no existing KOBI database. Preserve SQL mode and apply only the already-approved omission of import SQL-mode directives if that authorization is reaffirmed for this run; never omit other schema objects silently.
- Authorization impact: none to production; synthetic scoped grants exercise editor/reviewer/publisher-only/revoked/wrong-system accounts.
- Required tests: create -> edit/items/refs -> risk -> submit -> review approve unpublished; published fixture withdraw -> substantive edit -> fresh submit/approve; two editors, edit vs approval, changes vs approval, withdrawal vs edit, source withdrawal vs approval/reference, revocation vs command; forced real deadlock retry/rollback; exact revision/event counts; DOM labels/escaping/CSP/CSRF and actual login sessions; canonical CANDIDATE + HOLD blocked; no raw disease/comparison leak; all appropriate regressions.
- Dependencies: implemented prior phases and explicit isolated validation authorization. Out of scope: real/legacy publication, existing DB writes, production configuration changes, public positive rendering under a switched mode, performance refactor.

## 16. Exact likely change set, security and testing strategy

Use the inventory's exact G/E/A/GR/AC/RC/Routes files and existing view files named above, plus `D:/xampp/htdocs/kobi/app/Views/admin/treatments/index.php` for new draft navigation and `D:/xampp/htdocs/kobi/tests/treatment-editorial.php`, `treatment-governance.php`, `treatment-review.php`, `treatment-prerequisites.php` in the same exact tests directory for focused coverage. New create/item/operations view filenames are not decided in this audit. No expected changes to SQL, Config.php, AuthService, DiseaseService, DiseaseRepository, TreatmentPublicationService, public JS or dependencies. Public regression tests should still run after implementation as preservation evidence without changing their semantics.

Preserve Controller -> Service -> Repository separation, explicit actor/scope authorization, CSRF helper/token validation, prepared SQL, locked expected revision/state, controlled enum/SET validation under permissive MariaDB, independent review, UTC timestamps, immutable source versions, append events, private/no-store headers and escaped allowlists. UI visibility alone is never authorization. Use meaningful tests for new allowed/denied operations, atomic failure paths, stale transitions and state separation rather than duplicating field setters.

Each implemented subphase should run PHP lint on changed PHP, relevant focused suites and diff review. Full relevant regression baseline is 505 distinct assertions with repeated embedded suites counted once; risk/lifecycle/comparison checks are in TPre. Only 4B-L should claim newly observed live MariaDB/browser/race outcomes. All logs/evidence must omit credentials, sessions/CSRF secrets and private data.

## 17. Non-goals, risks and next step

Not 4B: public UI redesign/API, automatic publication or changing HOLD, mass legacy approval/migration, evidence ingestion/scraping, medicine integration, disease expansion, recommendations/patient-specific decisions, unrelated refactoring, source ownership/role redesign or credential taxonomy. Approval remains independently governed and unpublished; publisher operations in this phase only withdraw, never activate exposure.

No blocker prevents minimal code/UI 4B using current schema. Operational risks are adding creation without recording authorship, granting publisher-only users implicit edit/review, bypassing command locks, assuming schema unique keys enforce all lifecycle rules, silently widening self-source-verification/qualification policy, treating historical deferred races as passed, or exposing G.publish while HOLD remains mandatory. Real grant provisioning remains developer-controlled; no real grants/database metadata were inspected here.

**Recommended next implementation request: 4B-1 — Internal presentation and authorization error hardening.** It is a small, database-free correction before new actions. This audit does not authorize implementation, server/database setup or a continuation into 4B-1.

## 18. Audit verification

Only this new document is a project change from this task. Read-only SHA256 comparison preserved all 243 pre-existing files inventoried in app/routes/database/tests/docs/public, with no modified or missing file; this report was the sole new file. Root-file hash comparison also matched. `git diff --check` passed. No regression suite or application bootstrap executed; no PHP source changed, so new PHP syntax checks were unnecessary for this audit. No database was contacted or changed, no schema/migration/configuration/SQL-mode change occurred, no server was started and no Git history mutation occurred. HOLD is unchanged and nothing was published.

## 19. Phase 2C-4B-1 implementation closeout — 2026-10-09

**Status: PASS.** This separately authorized implementation resolves H1/H2 only and hardens existing error presentation. The preflight observations above remain the historical audit; this section records the subsequent correction. 4B-2, 4B-3 and 4B-L have not started.

### Changes and exact behavior

- A.lookup now wraps runtime repository failures from user/locked-user/active-grant lookups with a safe RuntimeException retaining the original cause. A.can therefore propagates infrastructure failures instead of returning false. Ordinary absent/revoked/wrong-scope permission still returns false, and require still denies it. Error/logic/assertion failures are not treated as permission absence. This is a local boundary around existing permission queries, not a new authorization framework or capability model.
- AC/RC preserve deliberate RuntimeException validation/workflow messages and existing 403 authorization/CSRF, 409 reload/stale and 422 validation behavior. PDO/wrapped infrastructure faults remain generic 503. Unexpected runtime subclasses now use generic 500, matching existing Throwable handling; their messages are not rendered as validation text. Diagnostics retain exception/cause class names only, not SQL, host, credentials, paths or raw messages. Successful views and redirects are unchanged.
- G.reviewAssessment includes the exact saved risk_eligibility in its submitted-content protocol allowlist. It reads the verified persisted submission snapshot, never substitutes current-row risk. Only eligible/not_eligible/not_assessed are accepted. Missing/null/unknown/nonstring values withhold the entire submitted-content projection and add a safe issue; they are not inferred as not_assessed. Existing approval/snapshot integrity checks remain intact. The existing ReviewContent template already displays this field, so no view change was necessary. Wording remains policy eligibility, not safety/effectiveness/recommendation.

Modified existing files:

1. `D:/xampp/htdocs/kobi/app/Services/TreatmentEditorialAuthorizationService.php`
2. `D:/xampp/htdocs/kobi/app/Services/TreatmentGovernanceService.php`
3. `D:/xampp/htdocs/kobi/app/Controllers/TreatmentAdminController.php`
4. `D:/xampp/htdocs/kobi/app/Controllers/TreatmentReviewController.php`
5. `D:/xampp/htdocs/kobi/docs/TREATMENT_PHASE_2C4B_PREFLIGHT.md`

Created: `D:/xampp/htdocs/kobi/tests/treatment-admin-hardening.php`. No existing assertion was changed, removed or weakened. The new suite reuses existing database-free fixtures and test/check semantics; captured application exceptions are checked outside the catch, and the established test runner exits 1 on assertion failure.

### Newly executed validation

Runtime: `D:/xampp/php/php.exe` (PHP 8.2.0). Commands: that runtime followed by each exact `tests/<script>.php` below. All completed with exit 0 and zero failures.

| Script | Distinct passing checks |
| --- | ---: |
| treatment-admin-hardening | 39 new focused |
| treatment-publication-correction | 17 |
| treatment-publication | 105 |
| treatment-governance | 88 |
| treatment-editorial | 73 |
| treatment-review | 130 |
| treatment-prerequisites | 29 risk + 7 lifecycle + 11 comparison |
| auth-phase1 | 45 |
| Baseline + new, excluding embedded repeated suites | **505 + 39 = 544** |

Separate risk/lifecycle/comparison script files do not exist; their established tests ran in treatment-prerequisites. Embedded foundation/editorial/review/publication reruns also remained green and are not counted twice. The focused tests cover successful/absent/revoked/wrong-scope permission, PDO/runtime/unexpected lookup faults including locked lookup, error/assertion propagation, admin/review direct route and POST failures, generic diagnostics/no raw disclosure, CSRF, stale save, validation and invalid review transition, three saved risk values, current/submitted mismatch, and missing/malformed saved decisions.

PHP syntax checks passed for all five changed/new PHP files. `git diff --check` passed. A task-start SHA256 comparison verifies only the listed intended file changes; public-read service/repository, DiseaseService/DiseaseRepository, policy/rules/snapshot builder, routes, views, schema, authentication, configuration and public JS remain unchanged. Full public-read/correction suites retain CANDIDATE independent of mode and HOLD-blocked projection/disease/comparison exposure.

No database connection or write occurred; tests use memory repositories/PDO doubles and memory logging. No disposable server/database was created, and no browser/live validation was claimed. No schema/migration, SQL-mode or persistent configuration change; no Git history mutation. HOLD remains unchanged, nothing was published, and no later 4B phase was implemented. Stop after this closeout; the next phase requires the user's review/request.

## 20. Phase 2C-4B-2 implementation closeout — 2026-10-09

**Status: PASS; 4B-2 complete.** Earlier sections describe historical states. The user resolved the revision ambiguity by approving an optional first item in the atomic draft-creation form at revision 1; all later additions use the existing revision increment. 4B-3 and 4B-L remain unimplemented. Public HOLD remains active and no publication was performed.

### Implemented behavior

- AC → E → G → GR now supports new drafts and item additions, using existing DI, repositories, transactions, authorization and canonical rules. New GET/POST routes are `/admin/treatments/create` and `/admin/treatments/{protocolId}/items/create`. The editorial list links to creation; editable protocol pages link to item creation. Successful POSTs redirect to the existing editor. No publisher, withdrawal, reset or history UI was added.
- Draft creation requires an active authenticated editor with an actual scoped edit grant, an existing disease, an existing active treatment system, and a meaningful English or Hindi title. The disease schema has no active-status column; the existing GR.diseaseExists query is reused. Titles are limited to 255 characters, text to the existing 65,535-byte limit; malformed UTF-8, nested fields, unknown fields and identity/governance mass assignment fail validation. Both locales are optional individually; no translations are fabricated.
- G.createDraft reuses R.legacyProtocol defaults: draft/unpublished/revision 1, null approval, empty published locales, evidence and risk not_assessed, null purpose/evidence summaries/applicability/limitations. updated_by/updated_at identify the authenticated creator and UTC time. One content_changed event explicitly records new draft creation at revision 1. This makes the creator a contributor for independent-review checks; it does not assert legacy unknown authorship, assessment, review, approval or publication.
- An optional first item is inserted in the same initial transaction and revision, with a canonical required item type, meaningful localized title, validated nonnegative integer order (default 0), excluded flag, not_assessed evidence, null purpose and limitations. Missing localized content remains null. Publication eligibility and sources are not prerequisites for draft creation.
- G.addItem uses the existing command locks and optimistic revision/state checks, actual persisted parent system authorization and saveInvalidation. Every later addition increments once, clears approval/locales and records content_changed. Existing changes_required behavior is preserved; approved/pending content returns to draft, and a governed edit of withdrawn content becomes unpublished. Published rows are rejected unchanged. No published-edit transition was introduced. Excluded items do not change existing included-item positive/unique ordering rules.
- Initial protocol/item/event writes are atomic. Later item insertion, protocol revision/invalidation and event writes are atomic. Existing rollback/deadlock-retry behavior is reused. Creation queries are parameterized BaseRepository operations with insert-field allowlists.
- Existing controller authentication, CSRF, safe 403/409/422/503/500 responses and class-only diagnostics are preserved. The permission-system-list query used by the new form now wraps runtime repository failures with their cause, matching the established safe infrastructure boundary; ordinary missing permissions still deny. No raw storage/permission error text is rendered. Forms use csrfField(), Bootstrap and existing field partials; no JavaScript was added.

### Duplicate behavior and remaining limitations

The actual schema has no protocol uniqueness constraint for disease/system or initial content. Multiple distinct protocols for the same pair remain allowed. Creation rejects a repeat whose disease/system and localized title/overview match an existing protocol using SQL null-safe equality and the existing database collation. This repeat check intentionally uses the protocol's initial content, not the optional first item's identity. It runs after the existing system-row lock; normal successful creation follows POST/redirect/GET. Subsequent item POST repeats fail the stale revision check.

This is application-level repeat protection, not a database uniqueness guarantee or durable idempotency key. Direct writes/other workflows that do not share these locks can still produce duplicates; collation behavior and concurrent/live execution require later isolated validation. No unique index, schema change or concurrency redesign was introduced. Browser and MariaDB execution were deferred to separately authorized 4B-L; memory tests do not establish live SQL or browser results.

### Files changed in this phase

Modified existing files:

1. `D:/xampp/htdocs/kobi/app/Controllers/TreatmentAdminController.php`
2. `D:/xampp/htdocs/kobi/app/Repositories/TreatmentGovernanceRepository.php`
3. `D:/xampp/htdocs/kobi/app/Services/TreatmentEditorialService.php`
4. `D:/xampp/htdocs/kobi/app/Services/TreatmentGovernanceService.php`
5. `D:/xampp/htdocs/kobi/app/Views/admin/treatments/edit.php`
6. `D:/xampp/htdocs/kobi/app/Views/admin/treatments/index.php`
7. `D:/xampp/htdocs/kobi/routes/web.php`
8. `D:/xampp/htdocs/kobi/tests/treatment-editorial.php`
9. `D:/xampp/htdocs/kobi/docs/TREATMENT_PHASE_2C4B_PREFLIGHT.md`

Created:

1. `D:/xampp/htdocs/kobi/app/Views/admin/treatments/create.php`
2. `D:/xampp/htdocs/kobi/app/Views/admin/treatments/item-create.php`
3. `D:/xampp/htdocs/kobi/tests/treatment-creation.php`

The sole existing test adjustment changes the exact editorial-route inventory count and description from 11 to 15 because four creation routes were intentionally added. No old security/workflow assertion or test was removed; the new focused suite separately verifies every creation route's verb and authentication/CSRF behavior.

### Executed validation

Runtime: `D:/xampp/php/php.exe` (PHP 8.2.0), followed by each exact `tests/<script>.php` below. Final runs all completed with exit 0 and zero failures.

| Script | Distinct passing checks |
| --- | ---: |
| treatment-creation | 110 new focused |
| treatment-admin-hardening | 39 |
| treatment-publication-correction | 17 |
| treatment-publication | 105 |
| treatment-governance | 88 |
| treatment-editorial | 73 |
| treatment-review | 130 |
| treatment-prerequisites | 29 risk + 7 lifecycle + 11 comparison |
| auth-phase1 | 45 |
| Baseline + focused, excluding embedded repeated suites | **544 + 110 = 654** |

The focused suite also reruns 330 embedded governance/editorial/review/hardening checks; prerequisite tests rerun their embedded foundations. These are not counted twice. An initial new route-loader fixture failed because it omitted the existing router property; fixing that fixture retained all assertions and the final run passed. The later permission-list correction was validated by focused/embedded suites and a prerequisite rerun. Separate risk/lifecycle/comparison script files do not exist; those checks are in treatment-prerequisites.

All 11 changed/new PHP files passed syntax checks. git diff --check passed; final source inspection and task-start SHA256 comparison confined changes to the listed files, with no deletion of an inventoried file. Public-read services/repositories, DiseaseService/DiseaseRepository, comparison, policy/rules/snapshot builder, authentication, schema and application configuration remain unchanged. New created drafts return DENY and empty public projections under both test HOLD and governed modes; the full public/correction suites retain HOLD-blocked candidate exposure.

No database connection, DDL, database data write, disposable database/server, application bootstrap or live browser validation occurred. Tests use memory repositories, PDO doubles and memory logs/sessions. database/structure.sql, migrations, SQL mode, credentials and persistent configuration were not changed. No Git history mutation or publication occurred. Stop after 4B-2; 4B-3 and 4B-L require separate authorization.

## Phase 2C-4B-3 implementation — 2026-10-09

**Status: PASS for implementation and database-free regression validation.** This section supersedes earlier statements that 4B-3 is unimplemented. Broad 4B-L validation remains deferred; this is not a full production-validation claim.

- Scoped operations entry: `GET /admin/treatments/operations`; protocol operations/history: `GET /admin/treatments/{protocolId}/operations`. Editorial and review pages link to these paths. A publisher-only user can enter the operations list directly without edit/review authority. Existing active-user, non-revoked per-system grants determine access; no permissions are granted by this UI, and the permission-administrator allowlist does not bypass ordinary scope checks.
- `POST /admin/treatments/{protocolId}/withdraw` exposes existing G.withdraw with the actual persisted protocol's `withdraw` capability (`can_publish`). A server-rendered confirmation form requires a reason, CSRF, expected revision and review state. Published → withdrawn, review → draft, approval → null, published locales → empty; content revision stays unchanged. The existing transaction appends a withdrawn event with authenticated actor, UTC time, revision and reason; prior content, references and events remain intact.
- `POST /admin/treatments/{protocolId}/reset-review` exposes G.resetReview to a qualified scoped reviewer (`can_review`) for unpublished protocols only. Pending review → draft; approved/unpublished → pending review, clearing approval. Both retain the same content revision and append review_reset. Approved reset also appends a fresh submitted event using the prior approval locales and the existing canonical snapshot/hash builder. Approval must reference the latest submission and still satisfy contributor independence, risk, evidence, sources and all existing review requirements. Reset never implies approval/publication.
- Published review reset is rejected until withdrawal; withdrawn content cannot reset/restore directly, submit directly or republish directly. Draft and changes_required resets, and nonpublished withdrawals, are rejected. The normal withdrawn/draft lifecycle requires a substantive governed edit → a new unpublished revision → fresh submission → independent approval, then a separately authorized explicit publication command. A no-op edit leaves withdrawal intact. No publication route/form was added.
- Internal history is read-only for any valid scoped edit/review/publish grant, including publisher-only access. Existing GR.history is reused with protocol-scoped descending event IDs and before_event pagination (51 fetched, 50 shown). Operations additionally project a metadata allowlist: event ID/type, content revision, actor ID, UTC timestamp, locales, rationale, due date and independence flag. No raw snapshots/hashes, unrelated identities, health profiles or mutation controls are exposed. Review and operations share an escaped history partial; stored historical snapshots/events are never rewritten.
- The existing AC safe boundary handles authentication, POST/CSRF, 403 denial, 409 stale state, 422 validation, generic 503 storage/permission failures and generic 500 unexpected failures. Diagnostics retain exception class names only. Posted identity, permission, system and governance fields are rejected. Both commands repeat authorization and revision/state checks inside the existing locked governance transaction. No service state machine, locking/deadlock algorithm, schema, permission model or public predicate was redesigned.
- Public services/repositories, DiseaseService/DiseaseRepository, comparison, HOLD configuration, canonical eligibility/projection, rules, snapshots, governance commands and authentication are unchanged. Tests exercise a positive synthetic published candidate before withdrawal, then DENY/empty projections after withdrawal under governed/HOLD, through actual disease and comparison services; reset is DENY, and HOLD still suppresses a fully eligible synthetic candidate. Synthetic publication setup occurs only inside memory fixtures; no database/application publication occurred.

### Validation and files

Runtime: `D:/xampp/php/php.exe` (PHP 8.2.0). Every final script completed with exit 0 and zero failures/warnings:

| Script | Distinct passing checks |
| --- | ---: |
| tests/treatment-operations.php | 127 focused |
| tests/treatment-creation.php | 110 |
| tests/treatment-admin-hardening.php | 39 |
| tests/treatment-review.php | 130 |
| tests/treatment-editorial.php | 73 |
| tests/treatment-governance.php | 88 |
| tests/treatment-prerequisites.php | 29 risk + 7 lifecycle + 11 comparison |
| tests/treatment-publication.php | 105 |
| tests/treatment-publication-correction.php | 17 |
| tests/auth-phase1.php | 45 |
| Distinct total (embedded repetitions excluded) | **654 + 127 = 781** |

Focused coverage includes publisher-only controls; reviewer reset; authentication, scope, revoked/inactive users, qualification, forged IDs/fields, HTTP verbs, CSRF, invalid/stale/repeated actions, history projection/escaping/pagination, stale approval submission and contributor independence, reset snapshot/hash, transactional rollback after protocol/event writes and the second reset submission event, safe 403/409/422/503/500 errors, fresh withdrawn edit/review lifecycle and canonical public boundaries. The operations script also reruns 440 embedded earlier checks; these are not counted twice. Initial failures were in new test doubles (missing delegated disease lookup, nonexistent snapshot helper, invalid comparison symptom shape and missing router config); correcting those fixtures retained the assertions. No production invariant failure was observed.

Modified: `app/Controllers/TreatmentAdminController.php`, `app/Repositories/TreatmentGovernanceRepository.php`, `app/Services/TreatmentEditorialService.php`, `app/Views/admin/treatments/index.php`, `app/Views/admin/treatments/edit.php`, `app/Views/admin/treatment-review/index.php`, `app/Views/admin/treatment-review/show.php`, `routes/web.php`, `tests/treatment-editorial.php`, and this document. Created: `app/Views/admin/treatments/operations.php`, `app/Views/admin/treatments/operation.php`, `app/Views/admin/treatments/_history.php`, `tests/treatment-operations.php`. Paths are relative to `D:/xampp/htdocs/kobi/`.

The existing editorial route inventory now expects 19 instead of 15 routes, permits the explicitly requested review-reset route, and extends POST assertions to creation/operations methods. Approval/publication-route prohibitions remain. No existing test was removed. All 13 changed/new PHP files pass syntax checks; git diff --check passes. Task-start SHA256 comparison confines this phase to the listed files without deleting inventoried files.

No actual database connection, DDL, database data write, application bootstrap, browser/live MariaDB run, SQL-mode change, schema modification, credential/config change or Git history mutation occurred in this phase. Focused tests use memory repositories, PDO doubles and memory sessions/logs. No code outside this phase was intentionally changed.

Remaining: separately authorized 4B-L browser/live SQL and broader security validation, editor/reviewer races, source invalidation races, concurrency/locking/rollback stress and deadlock behavior. The prior creation duplicate limitation remains: no disease/system or initial-content database uniqueness constraint; application repeat detection is not durable idempotency or a database uniqueness guarantee. No duplicate/concurrency redesign was performed. Stop after 4B-3.
