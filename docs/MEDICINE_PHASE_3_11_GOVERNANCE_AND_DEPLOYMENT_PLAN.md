# Medicine Phase 3.11 — Governance contract and controlled deployment plan

Date: 2026-10-09. Result: **PASS WITH LIMITATIONS**. Governance readiness: **READY WITH OWNER DECISIONS** for deployment preparation. Actual deployment and shared-write activation are **NOT AUTHORIZED / NOT READY**.

## 1. Executive Summary

This phase produces a proposed final governance contract and a preparation checklist, without implementing policy, deploying schema, granting authority or creating records. Phase 3.10 lifecycle protections are retained. No broad architecture audit or full regression rerun was performed.

Status labels throughout this document have distinct meanings:

- **APPROVED EXISTING POLICY**: requirements already supplied by KOBI and implemented safety boundaries; this label does not certify any person, qualification or scientific source.
- **PROPOSED POLICY**: recommended contract for owner review, with no activation effect.
- **OWNER DECISION REQUIRED**: an explicit decision is still needed before implementation/operation. Actual administrator identities are **OPEN OWNER DECISION**.

Policies are representable in part, but approval is not a configuration toggle. Production administrator, qualification and evidence hooks currently deny; target-aware evidence and representative conflict enforcement remain activation gaps. The exact additive inventory is eleven tables and six triggers. Full-dump import is unsuitable: Phase 3.4 includes seven DROP statements, and adjacent search-view operations are unrelated.

Evidence used: [Phase 3.8](D:/xampp/htdocs/kobi/docs/MEDICINE_PHASE_3_8_AUTHORITY_IMPLEMENTATION.md), [Phase 3.9 actual census](D:/xampp/htdocs/kobi/docs/MEDICINE_PHASE_3_9_FINAL_GOVERNANCE_AND_REGISTRY_AUDIT.md), [Phase 3.10](D:/xampp/htdocs/kobi/docs/MEDICINE_PHASE_3_10_LIFECYCLE_INTEGRITY.md), and targeted current source/schema inspection. No fresh census is claimed.

## 2. Current Medicine State

**APPROVED EXISTING POLICY:** Preserve Controller → Service → Repository → shared Database conventions. Private profile access, Treatment grants and autocomplete access confer no canonical Medicine authority. Shared creation/publication remain disabled.

Current technical state: seven foundation tables in Phase 3.4; three authority/governance/assessment tables and two immutable-event triggers in Phase 3.8; one activity journal and four triggers in Phase 3.10. Phase 3.9 observed the original ten Medicine tables absent from normal `medical1`; Phase 3.10 applied no normal schema changes. The new journal is also undeployed according to the supplied current context, not a fresh metadata query here.

Internal service commands exist, without Medicine governance HTTP routes/UI. Production DI uses the closed policy. Synthetic test policies are not production configuration. `verified` represents identity review, not therapeutic efficacy or publication. `approved` below is a derived current eligibility predicate, not a new stored status.

## 3. Actual Registry Baseline

Verified against Phase 3.9 sections 3–9 and 24. That report records SELECT-only normal-database observations on MariaDB 10.4.27, final census 2026-10-09 07:26:19 UTC; they were separate observations, not a frozen transaction. These are historical measured counts, not a new Phase 3.11 SELECT census.

| Measured item | Phase 3.9 result |
| --- | ---: |
| kobi_substances total / active / searchable | 2,224 / 2,224 / 2,224 |
| identity_status CANDIDATE / identity_type UNKNOWN | 2,224 / 2,224 |
| Nonblank verification source / reviewer / review date | 0 / 0 / 0 |
| Exact stored-name duplicate groups | 0 |
| Stored-normalized duplicate groups | 0 |
| Suspected formatting collisions | 18 groups / 39 rows |
| kobi_combinations / kobi_combination_items | 2,327 / 6,671 |
| Chemical forms / form components | 0 / 0 |
| Personal medication records | 3 |

The legacy counts mean 2,327 combinations and 6,671 member rows; they are not two counts of canonical Medicines. All legacy combinations/items were PENDING. One repeated member-set group involved two combinations; four combinations repeated a member ID. These are review flags, not permission to merge.

All substances have the stored default WHO_INN naming label; no provenance was established by that label. **APPROVED EXISTING POLICY:** existing/active/searchable/named never means automatically verified. All 2,224 remain candidates requiring evidence. The 39 formatting rows are a subset, not an additional population.

## 4. Final Administrator Policy

**APPROVED EXISTING POLICY:** Administration is separate from contributor/editor/reviewer/steward/publisher authority. `grantAuthority()` rejects self-granting, requires an active actor/recipient, valid capability/scope/expiry, rationale and a transaction-bound event. `revokeAuthority()` retains grants/history and records actor/time/reason. Production `requireAdministrator()` denies all actors. Users/profile/Treatment roles supply no Medicine administrator role.

**PROPOSED POLICY:** Start with the smallest separately approved Medicine-only deployment-controlled administrator allowlist. Administration is catalog-wide for issuance/revocation only; issued grants should use the narrowest competence scope. This fits the existing actor-only administrator hook. An administrator receives no review/edit/publish permission by implication. Do not copy Treatment's administrator list. Reviewers' qualifications must be independently assessed under the approved process; administrator convenience is insufficient.

Audit administrator-list changes through the accountable deployment/change record, including approving owner, acting operator, effective time, reason and prior/new membership. Grant/revoke commands retain actual authenticated actors and immutable events. There is no persisted administrator capability in the current Medicine grant table; do not invent one.

Emergency revocation: an approved Medicine administrator revokes affected grants through the existing service, with mandatory rationale; current-use checks deny revoked reviewer decisions immediately on subsequent governed reads, while preserving history. Removing an administrator from the allowlist denies future administration but does not automatically revoke separately held grants. Assess and explicitly revoke those grants as needed. If no authorized administrator remains, hold operations closed and use an owner-approved accountable recovery process; no direct SQL authority bypass is proposed.

**OWNER DECISION REQUIRED / OPEN OWNER DECISION:** actual people, initial bootstrap and replacement approver, global administrative reach, emergency coverage and change-record owner. Scoped administrators would require a separately approved target/capability-aware policy contract; they are not present today. No real authority is granted here.

## 5. Final Contributor Policy

**APPROVED EXISTING POLICY:** Canonical authority is separate from health-profile ownership, medication recording, substance selection and autocomplete. Existing contributor grants permit chemical candidate/unresolved assessment and internal inspection. Composition/presentation metadata edits require the separate editor capability. Creation still always denies at `requireCatalog()`.

**PROPOSED POLICY:** Only named, explicitly granted active contributors may later register approved explicit S/F candidate adapters and create complete candidate compositions/presentations in the approved pilot. Require an editor grant as well for permitted display-metadata edits. Every action has actual actor attribution, expected revision/snapshot where applicable, bounded scope and an accountable history. Identity-defining keys remain immutable; correction follows new governed identity rules, not ingredient/key mutation.

Submission is a deliberate handoff of an exact revision/snapshot and identity-evidence package to an independent reviewer. Submission confers no approval. Initially recommend a controlled internal service/operator procedure rather than new UI or a pending_review schema state. Existing candidate assessment plus review can represent the handoff, but no formal submission command/event exists. If a separate auditable submission milestone is approved, implement only that bounded gap later.

**OWNER DECISION REQUIRED:** contributor/editor assignments, allowed creation actions and scopes, pilot target allowlist, whether submission needs a separate persisted milestone. No real user assignment here.

## 6. Final Reviewer Policy

**APPROVED EXISTING POLICY:** Require explicit reviewer authority, active user and grant, exact scope or explicitly granted catalog reach, valid grant dates, qualification reference/assessor/scope, independence and accountable revision-bound evidence. Creator and anyone recorded as assessing/editing the target cannot review it. A recorded independent flag alone is insufficient. Revoked/inactive/expired authority denies new decisions and current eligibility; historical proof is retained.

**PROPOSED POLICY:** Independent, scope-limited qualification attestation by an owner-approved assessor, checked against approved competence and renewal rules. Credential claims require validation, not merely nonblank text. Grant expiry must not exceed the approved qualification validity. Disclosed conflicts beyond recorded contribution require an approved refusal/escalation process. Retain credential assessment provenance without exposing it publicly or in private-health logs.

**OWNER DECISION REQUIRED:** acceptable qualifications, issuers/assessors, competence scopes, qualification validity/renewal, conflict criteria, suspension/revocation handling and actual reviewers. No credential threshold or named professional authority is deemed approved. Production `requireQualification()` stays closed until the approved rules are implemented; there is no existing configuration-only switch or separate qualification-expiry field to assume.

## 7. Final Evidence Policy

**APPROVED EXISTING POLICY:** Identity evidence establishes the exact entity/composition/presentation. It does not establish therapeutic effectiveness, treatment suitability, dose advice or patient-specific safety. The existing envelope requires kind=identity, issuer, record, version, locator and content SHA-256; review binds a deterministic snapshot. A URL, digest or issuer name by itself is insufficient.

**PROPOSED POLICY:** Fact-specific, traceable source appraisal with explicit supported facts, exact target and chemical basis, version/date, source content digest, unresolved conflicts and current-validity disposition. Retain reviewable provenance under approved retention/access rules. Acceptable categories for owner consideration are controlled naming/identity references; authoritative chemical identity/form specifications; regulated product identity/composition/presentation records; validated manufacturer specifications; and primary analytical/identity publications where independently appraised. These are candidate categories, not an approved organization list or an assurance that every document in a category suffices.

| Identity fact | Proposed required coverage |
| --- | --- |
| Substance / representative | Exact entity and supported taxonomy; explicit S/F target; disposition of possible overlap |
| Form / parent | Exact salt, hydrate, solvate or stereochemical form and supported parent relationship |
| Composition | Complete distinct active set and intended combination; no name-derived members |
| Strength | Each member's amount, unit, denominator and chemical basis; no guessed base conversion |
| Presentation | Complete supported strength, dosage form, route and release vector within existing supported codes |

Therapeutic effectiveness evidence belongs to a separate later clinical/publication contract. It must neither substitute for identity proof nor inherit acceptance from identity verification.

**OWNER DECISION REQUIRED:** approved source categories/issuers, fact-specific sufficiency, source hierarchy, conflicts, freshness, retraction/withdrawal and retention rights. Current `requireEvidence(evidence, scope)` lacks the target/snapshot context needed for this contract and a configured current-source validity rule. Both review and current-use paths need approved target-aware enforcement before real verification. No sources were fetched or medically appraised in this phase.

## 8. Final Approval Policy

**APPROVED EXISTING POLICY:** Contributor assessment/edit and independent reviewer verification are separate. Each verification increments metadata_revision and appends immutable actor/grant/evidence/snapshot history. Composition requires current chemical dependencies; presentation additionally requires current composition. No contributor self-approval or implied administrator medical authority. Publisher is stored as a separate capability but no publish operation is enabled.

**PROPOSED POLICY:** One qualified independent identity decision per exact target revision is the minimum pilot approval. That reviewer decision is the independent approval; no additional administrative signature is presumed to provide scientific review. Derived approval requires current reviewer authority/qualification, evidence, dependencies, full snapshot and lifecycle eligibility. An owner may require a second independent reviewer for selected cases, but the current single-latest-verify model does not enforce an approval quorum.

Choose one evidenced representative S(id) or F(id) for the exact supported entity, with explicit disposition of candidate overlaps. Different registry IDs and S/F references do not establish different scientific entities; per-target uniqueness does not enforce scientific uniqueness. Preserve aliases/imported records and history; no merge.

Approval becomes noncurrent after substantive metadata/revision changes, dependency changes, invalidation, protected withdrawal/supersession, registry/form/parent loss or activity-generation changes, authority loss, or failed evidence/qualification policy. Fresh approval is a new authorized independent decision against the currently locked revision/snapshot and current approved evidence. Reactivation and administrator intervention cannot freshen an old token. Old pre-3.10 snapshots remain stale.

**OWNER DECISION REQUIRED:** one versus multiple decisions, escalation cases, representative assessor/overlap resolution and qualification/evidence sufficiency. Publication policy remains a separately approved future workflow; do not equate reviewer verification with publisher authorization.

## 9. Restoration Policy

**APPROVED EXISTING POLICY:** Phase 3.10 checks all protected history. A withdrawn/superseded chemical/composition/presentation cannot be restored through invalidation, candidate assessment, metadata edit or review. Retirement is terminal. Old IDs, keys, events and private observations remain intact. Registry reactivation never restores old acceptance.

**PROPOSED POLICY:** Keep withdrawn/superseded restoration unavailable for the first pilot. Temporary governed invalidation may recover only through permitted explicit reassessment and fresh independent review; downstream targets require their own current reviews. A substance activity change makes reviewed adapters stale without rewriting the substance. Corrected identity-defining facts require a separately governed new supported target/presentation and preserved old history; metadata-only correction uses the existing revision increment and new review. No automatic replacement or private relink.

**OWNER DECISION REQUIRED:** whether protected restoration is ever permitted, who authorizes it, supporting correction/replacement evidence and relationship/history semantics. Any affirmative decision needs a separately designed transition; this phase implements none and approval alone must not disable the history guard.

## 10. Registry Reconciliation Policy

**APPROVED EXISTING POLICY:** No bulk verification/promotion, automatic duplicate merging, chemical-form inference or canonical creation. All 2,224 candidate/unknown rows with no verification metadata require individual identity assessment. Do not rewrite their flags to simulate review.

**PROPOSED POLICY:** Existing explicit substance/form ID → candidate adapter → evidence-backed identity and representative appraisal → contributor candidate assessment → independent chemical review → current approved ingredient → candidate composition from only current approved ingredients → independent composition review → eligible Medicine Identity. Presentation, if needed, has a further independent review of its complete supported tuple. Identity evidence gathering precedes review; it is not itself a verified decision. Composition evidence may be assembled earlier, but the service correctly requires chemical approval before accepting composition review.

Uncertain cases become unresolved and stop. Each decision has an accountable actor, exact IDs/revision/snapshot, evidence and immutable event. Rechecks recurse through dependencies. No mass importer or automatic population job is included.

**OWNER DECISION REQUIRED:** accountable reconciliation/representative lead, case acceptance/escalation, approved evidence and pilot targets. Registry rows remain unchanged; separate approved registry-writer processes are required for any later correction of their semantics.

## 11. Duplicate/Formatting Collision Policy

**APPROVED EXISTING POLICY:** The 18 groups/39 rows are suspected formatting collisions only. Similarity never establishes chemical equality. Keep all candidate records and IDs; do not merge, rename, normalize differently or suppress automatically.

**PROPOSED POLICY:** Route flagged cases for evidence-backed identity review; maintain unresolved disposition until exact supported entities and representatives are established. Record a reason for either distinct identity or representative selection. Exclude unresolved collisions from the first pilot. Preserve original names/history and any private references.

**OWNER DECISION REQUIRED:** adjudicator and overlap-resolution contract. Existing unresolved assessments fail closed, but there is no automatic scientific ambiguity detector or cross-ID representative enforcement. Approval must not turn a label heuristic into a verification rule.

## 12. Combination Medicine Policy

**APPROVED EXISTING POLICY:** The 2,327 legacy combinations/6,671 members are import scaffolding, not canonical combinations. No automatic conversion, merge or membership inference. Repeated sets may denote different products/presentations; repeated member IDs need explicit review.

**PROPOSED POLICY:** Future case-by-case review establishes distinct exact ingredient entities and the intended complete composition, then supported per-member strength/chemical basis and presentation attributes. Reconcile each adapter independently first; create a candidate only under the approved contributor gate; obtain separate composition and presentation reviews. Missing strength/form/route/release evidence remains unresolved. No name-derived tuple or automatic legacy-to-canonical link.

**OWNER DECISION REQUIRED:** combination case intake, evidence sufficiency and exception escalation. Recommend single-ingredient compositions only for the first pilot; legacy combinations remain deferred.

## 13. Chemical Form Policy

**APPROVED EXISTING POLICY:** There are no measured existing form records. Base, salt, hydrate, solvate and stereochemical identity remain distinct absent approved authoritative relationship evidence. S and F references stay typed; an F adapter needs its own review and active, current parent facts. Parent approval alone does not verify a form.

**PROPOSED POLICY:** Before introducing forms, establish exact form identifier/taxonomy, supported parent ID and relationship, stoichiometry/components where relevant, provenance/version and reviewer qualification. Strength must state the measured form/base basis; any conversion requires explicit evidence and a separately supported rule. Unsupported structural details remain unresolved, rather than being fitted into current fields or inferred from names.

**OWNER DECISION REQUIRED:** accepted relationship evidence, representation scope and review responsibility. No form records/mappings or new schema fields are created here. Forms are excluded from the initial proposed pilot.

## 14. Personal Medication Linking Policy

**APPROVED EXISTING POLICY:** All three private records remain untouched. No name matching, automatic canonical linking, legacy-text rewriting or user-wide migration. Profile accessibility and owner/editor write checks remain separate from canonical authority. Existing association services use explicit identifiers, expected record/link tokens and current canonical eligibility; historical links do not assert current approval.

**PROPOSED POLICY:** Later explicit linking requires an authorized private-record actor, exact current reviewed identity/presentation, confirmation of intended association and reviewer assistance for ambiguous interpretation. A Medicine reviewer receives no patient access by virtue of a reviewer grant. Assistance requires independently authorized private access or an approved privacy-preserving process. Unresolved records stay unchanged.

**OWNER DECISION REQUIRED:** consent/confirmation and reviewer-assistance procedure. Private linking is excluded from this pilot; no patient payloads are needed for governance monitoring.

## 15. Shared-Write Activation Gate

**APPROVED EXISTING POLICY:** Any unresolved prerequisite means **SHARED WRITE = DISABLED**. `requireCatalog()` always denies today; no role/grant/configuration currently opens it.

**PROPOSED POLICY:** Require a signed-off checklist with evidence for every condition before a separately authorized limited activation:

1. Approved Medicine administrator identity/bootstrap/revocation process and implemented administrator check.
2. Explicit active contributor/editor assignments with narrow action/target scope.
3. Approved qualification/conflict/renewal policy and implemented qualification enforcement.
4. Qualified active independent reviewer grants within competence.
5. Approved fact-specific/current evidence policy and target-aware enforcement.
6. Creator/contributor self-review prevention retained and tested.
7. Deployed revision-bound immutable audit history and atomic command behavior.
8. Representative/reconciliation controls, excluding unsupported/ambiguous references.
9. Authorized invalidation/revocation operators and failure procedures.
10. Deployed Phase 3.10 history/registry-generation protections, with controlled writer privileges.
11. Explicitly independently verified pilot ingredients; active/default-labelled registry rows never substitute.
12. Public publication independently disabled; Treatment HOLD preserved.
13. Reviewed application/schema compatibility, restoration/rollback plan and operator monitoring.
14. Owner-approved internal-only action mapping, target/count bounds and stop conditions implemented and checked.

**OWNER DECISION REQUIRED:** approve this gate and exact pilot envelope. No condition is satisfied by this document alone. Schema-only deployment must leave every activation denial in place.

## 16. Deployment Preparation

**KNOWN EXISTING PROCESS:** Developer-controlled database changes, authoritative [structure.sql](D:/xampp/htdocs/kobi/database/structure.sql), existing MVC/repository/DI application and isolated MariaDB harnesses. The inspected documentation does not establish a production backup/restore automation or migration runner; docs/DATABASE.md is empty. Historical isolated imports validate syntax/constraints, not production deployment. Do not infer a backup mechanism from XAMPP or prescribe an undocumented command as existing procedure.

Current authoritative source SHA-256: `322bea83a831d085c02464168e50055c193f9cfefdf5567e4495c0a2625d0164`. Exact table definitions include these FK/CHECK counts (FK counts are constraints; composite FKs contain additional column pairs). Every table has a primary key; preserve every named key/constraint in its source definition, including generated active_slot uniqueness.

| Phase / CREATE TABLE, in dependency-safe order | FK constraints | CHECKs |
| --- | ---: | ---: |
| 3.4 kobi_medicine_chemical_refs | 5 | 7 |
| 3.4 kobi_medicine_identities | 3 | 8 |
| 3.4 kobi_medicine_ingredients | 2 | 1 |
| 3.4 kobi_medicine_presentations | 4 | 13 |
| 3.4 kobi_medicine_strengths | 2 | 3 |
| 3.4 health_medicine_identity_links | 5 | 3 |
| 3.4 health_medicine_selected_ingredients | 2 | 2 |
| 3.8 medicine_authority_grants | 4 | 5 |
| 3.8 medicine_governance_events | 6 | 5 |
| 3.8 medicine_registry_assessments | 4 | 2 |
| 3.10 medicine_registry_lifecycle_events | 2 | 3 |
| Total | 39 | 52 |

Six exact CREATE TRIGGER definitions:

- Phase 3.8: `medicine_events_no_update`, `medicine_events_no_delete` (governance-event immutability).
- Phase 3.10: `medicine_substance_activity_history`, `medicine_form_activity_history` (actual is_active changes, atomic journal append); `medicine_lifecycle_no_update`, `medicine_lifecycle_no_delete` (journal immutability).

**RECOMMENDED DEPLOYMENT STEP:** The later reviewed upgrade stream contains exactly the eleven complete CREATE TABLE definitions above and six complete CREATE TRIGGER definitions from this pinned source, with their exact indexes/FKs/CHECKs/charset/engine/generated column. This is **17 DDL statements**, with client DELIMITER controls for trigger bodies. No SQL has been executed or upgrade artifact generated here. Verify statement content byte-for-byte against the source and manifest before approval; do not rewrite definitions from the summary counts.

Selection boundaries: Phase 3.4 starts at its comment (source line 1066), but select only the seven listed CREATE TABLE definitions, ending with health_medicine_selected_ingredients. Explicitly exclude its seven DROP TABLE IF EXISTS statements and all adjacent kobi_substance_search placeholder/DROP/VIEW statements. Phase 3.8 block (line 1319) contributes its three CREATE TABLE and two CREATE TRIGGER definitions. Phase 3.10 block (line 1417) contributes one CREATE TABLE and four CREATE TRIGGER definitions. Exclude full-dump prologue/epilogue, mode directives, foreign/unique-check disabling, seeds and every unrelated object. This is a declared future additive selection, not a claim that the original dump is a safe migration.

Preconditions/checklist:

- Owner/operator approves deployment target, operator privileges, outage/write pause and backup/restore method; obtain a verified restorable backup plus compatible application release. Identify RPO/RTO and restore rehearsal evidence. Backup method/retention and rehearsal are **OWNER DECISION REQUIRED**; none was performed here.
- Fresh SELECT-only target preflight: actual version/mode/schema; current objects/counts; all eleven targets and six trigger names must be absent for this initial deployment. If anything already exists, STOP and compare metadata/history; no IF NOT EXISTS, DROP or overwrite to conceal drift.
- Confirm external parents `users`, `health_medicines`, `kobi_substances`, `kobi_substance_forms` exist with matching engine, signedness/key types and supporting keys. The new journal references actual registry PKs; it does not alter their definitions or populate them.
- Keep FK/unique enforcement enabled. Confirm privileges for exact DDL/triggers and the deployment definer/runtime behavior. No hardcoded root definer is introduced. Confirm controlled registry writers cannot remove/truncate/falsify the journal or bypass triggers; schema-only deployment itself must not edit registry rows.
- Apply only the reviewed 17 statements with shared/public writes still disabled. DDL is not a transactionally atomic migration; record each success. On any non-mode failure STOP with the exact diagnostic for the operator, without weakening constraints or retrying destructive statements.
- Compare SHOW CREATE and information_schema columns, PK/UNIQUE/ordinary/generated indexes, FK targets/actions, all 39 FK constraints and 52 CHECKs, engines/collations and all six trigger bodies/timing/event/table against source. Required explicit indexes must be present; legitimate implicit FK indexes are acceptable. Review active-grant uniqueness and actor/qualification/revocation relationships.
- Verify authority/grants, governance events, current assessments and lifecycle journal are initially empty by SELECT. No grant seed, administrator row, automatic adapter/backfill, private association or registry reconciliation belongs in the deployment stream.
- Check code/schema compatibility against the reviewed application artifact and version/hash; DI still uses shared PDO and closed production policy. Old snapshots without lifecycle IDs cannot be accepted after an upgrade containing existing reviews.
- Use retained Phase 3.10 live validation for exact syntax/runtime behavior. Future deployed negative/success smoke mutations, if needed, require an explicitly authorized isolated clone/fixture environment; do not fabricate normal registry or private test records. On the normal DB perform metadata/count checks and safely rejected closed-gate checks only after confirming they cannot write.

SQL mode: historical existing mode is `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`. Do not change global/server/project mode. The additive 17 statements contain no SET SQL_MODE. Full-source directives `/*!40101 SET SQL_MODE=''*/;`, `/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;`, and `/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;` are outside this future deployment selection. Source remains unchanged. Preflight must confirm actual deployed mode; unknown environment drift stops review rather than being corrected silently.

Rollback strategy: freeze affected operations and registry writers during deployment. On partial DDL failure, keep activation closed, retain new objects/history and reconcile the recorded partial state; no automatic drops or blind rerun. Prefer compatible application rollback while retaining additive schema and activity instrumentation. If writes/history have occurred, do not remove triggers/journal or restore old code that accepts pre-generation reviews. Any database restore requires a separately approved coherent DB/application restore with a plan to preserve/reconcile post-backup history and registry changes. Reversibility of pilot access means closing grants/gates and retaining records, not deleting decisions. No restore or rollback was executed here.

**OWNER DECISION REQUIRED:** backup/restore mechanism, authorized operator/target, writer freeze/instrumentation ownership, definer/privilege management and deployment window. No additional schema change is demonstrated necessary for preparation.

## 17. Deployment Order

**PROPOSED POLICY / RECOMMENDED DEPLOYMENT STEP:**

1. Approve the schema-only deployment envelope and responsible operator; record application/schema hashes. Do not bundle shared activation into this approval.
2. Verify backup/restore readiness; pause affected application/registry writers to avoid an uninstrumented transition window.
3. Run fresh SELECT-only metadata/parent/type/name/mode preflight. If targets exist or drift is present, stop for a reviewed delta.
4. Prepare/review the exact 17-statement additive stream described above; preserve constraints and enabled FK enforcement.
5. Deploy foundation tables in listed order, then authority/events/assessments and governance-event triggers, then lifecycle journal and all four lifecycle triggers before resuming registry writers.
6. Verify actual metadata, empty authority/history/assessment tables, trigger definer/body and unchanged registry/private counts; no test fixture INSERT/UPDATE in normal KOBI.
7. Deploy/verify compatible Phase 3.10 application artifact under the same controlled pause. Recheck closed policy and catalog denials. Schema precedes code because the current code requires the new journal; both must be paired before affected use resumes.
8. Grant no real authority. Resume only approved existing operations after read-only/safely denied smoke checks and operator review; keep canonical/public gates closed.
9. Record deployment outcome, failures/drift and rollback readiness. Obtain separate owner decisions and separately scoped implementation/tests for activation gaps.
10. Require an explicit later activation authorization after all section 15 gates pass. This document authorizes none of these execution steps.

## 18. Limited Activation Plan

**PROPOSED POLICY:** Internal, unpublished, single-ingredient identity pilot using named contributors and independent reviewers, approved evidence and exact independently reviewed registry targets. Start with no patient linking, forms, legacy combinations or public pages/SEO. Target/count/action bounds are enforced server-side, never derived from submitted user IDs or client flags.

Stage A is schema-only deployment with all gates closed. Stage B, separately approved and implemented, allows only pilot adapter intake/assessment and review of selected existing substances; no composition may be built from unreviewed adapters. Stage C, after accepted ingredient reviews, allows bounded candidate composition creation and independent review; presentation creation stays closed unless separately approved with complete evidence. Publisher/public output stays closed in every stage.

Record each actor/grant/decision/revision/evidence/current lifecycle generation. Monitor denials, storage/audit failures, stale review and representative conflicts without private payloads. Any uncertain evidence, unauthorized action, lifecycle instrumentation failure or out-of-envelope record stops the pilot. Revoke/pause access through approved governance; preserve history and existing private observations. No automatic cleanup of canonical records is proposed.

**OWNER DECISION REQUIRED:** named operators/contributors/reviewers, exact actions/targets/count limit, review sufficiency, stop conditions and pilot acceptance criteria. The internal operator invocation must preserve AuthService actor handling and existing service boundaries; future HTTP controls require explicit auth/authorization/CSRF and a separately reviewed task. No route/UI/operator script is added here.

## 19. Registry Pilot Strategy

**PROPOSED POLICY:** Recommend **five manually selected single-substance cases**, one contributor/editor and two available independent reviewers, with one review decision per target unless the owner chooses a higher threshold. Five and the staffing numbers are product/governance recommendations, not approved requirements; availability of two reviewers is not a two-signature quorum.

Choose cases with complete owner-accepted identity evidence and no unresolved representative/form/formatting ambiguity. The census identifies no already verified pilot population; actual IDs must be chosen and appraised later, not inferred from names. Hold the 18 collision groups, forms and legacy combinations outside the pilot initially. Register only the approved explicit IDs after the adapter-intake gate is implemented. Review chemical identity first, then any selected composition. Advance one case at a time, evaluate freshness/revocation/invalidation behavior and account for every new record before increasing scope.

**OWNER DECISION REQUIRED:** pilot size/IDs, source packages, personnel and success/stop criteria. No five records were selected, reconciled or created in this phase; all 2,224 remain untouched.

## 20. Phase 3.8/3.10 Gap Analysis

Classification is requirement-specific; policy approval, implementation and deployment are distinct. Gaps below block future activation, not completion of this preparation document.

| Requirement | Classification | Actual position / smallest follow-up |
| --- | --- | --- |
| Scoped persisted expiring/revocable grants; active actors | already implemented | Preserve repository/service enforcement; no health/Treatment inheritance |
| Actual administrator identity/bootstrap/global reach | unresolved owner decision | Owner approves separate Medicine administrators; no identities invented |
| Production administrator check | implementation gap | requireAdministrator always denies; approved allowlist/policy needs implementation, not an existing config switch |
| Administrator-list membership after an approved check exists | configuration only (future, conditional) | Named allowlist management; no such Medicine allowlist implemented now |
| Reviewer qualification, conflict and renewal criteria | unresolved owner decision / policy only | Approve criteria and assessor before implementing closed qualification hook |
| Production qualification enforcement | implementation gap | requireQualification always denies; implement approved attestation/validity rules and scope checks |
| Fact-specific identity sources/current validity | unresolved owner decision / policy only | Decide source hierarchy, supported facts, retraction/freshness/retention |
| Evidence bound to actual target/facts | implementation gap | requireEvidence receives evidence+scope only; provide target/snapshot context in review AND current-use validation; reject unrelated identity evidence |
| Representative S/F overlap disposition | unresolved owner decision + implementation gap | Per-ID UNIQUEs do not enforce scientific uniqueness; approve bounded target-aware conflict controls; no speculative new table mandated |
| Contributor shared intake/creation action mapping and pilot bounds | implementation gap | requireCatalog always denies; implement narrowly approved actions/targets/counts later, preserving public denial |
| Candidate edit/review handoff | already implemented in part | Chemical assessments and composition/presentation metadata edits exist; assign separate capabilities. No formal submit API/state/event |
| Separate immutable creation/submission milestone, if required | unresolved owner decision / conditional implementation gap | Creation has actor/time attribution but no governance create/submit event. Event vocabulary currently excludes those actions; choose approved accountability contract before any bounded code/schema change |
| One independent reviewer and revision/current dependency history | already implemented | Current model supports one latest independent verification; approvals at chemical/composition/presentation levels are distinct |
| Multiple-review quorum, if selected | unresolved owner decision / conditional implementation gap | Current latest-event gate does not count independent signatures; do not claim quorum implemented |
| Withdrawal/supersession bypass; activity round-trip stale acceptance | already implemented correction | Phase 3.10 fixes E39-1/E39-2; retain all-history and durable registry-generation checks |
| New restoration/replacement links | policy only / unresolved owner decision | Deliberately unavailable; not required for conservative first pilot |
| Normal deployed Medicine objects | schema gap (deployment) | Historical ten absent plus undeployed journal; exact eleven-table/six-trigger additive deployment awaits fresh metadata and approval |
| New schema design for this phase | documentation only | None necessary for preparation; conditional future policies may need separate bounded design |
| Backup/restore/writer privileges and deployment window | unresolved owner decision / documentation only | Preparation checklist; no documented automated production backup process claimed |
| Manual candidate/collision/legacy triage | policy only | No data changes or automatic classifier/merge; individual approved future case review |
| Private association access/current review checks | already implemented | Separate private authority/current eligibility; pilot leaves three records untouched |
| Public Medicine publishing workflow | future implementation gap, excluded from pilot | No publish operation/routes/projection; no need to build it to deploy closed foundation |

No concrete blocker prevents safe documentation/deployment preparation, so no PHP/schema change was made. Do not reinterpret READY WITH OWNER DECISIONS as ready to activate: target-aware evidence, representative controls and closed policy/action hooks require separately approved implementation before operation.

## 21. Focused Security Confirmation

Current-source confirmation: actual actors via AuthService::id; independent active scoped grants in MedicineAuthorizationService; privateActor uses findAccessible and owner/editor rules; review checks creator/all recorded contributors; expected revision/hash and recursive requireCurrent; requireLifecycle scans protected history; registry snapshots include target/parent lifecycle IDs; journal/immutable triggers preserve committed activity; unresolved chemical assessments cannot verify; requireCatalog always denies; no Medicine publish operation/route. Treatment configuration still defaults to hold and was not changed.

Eight fresh focused runtime cases used existing memory libraries, guarded by MEDICINE_GOVERNANCE_LIBRARY, from transient PHP stdin (no file retained, bootstrap or database connection). **8 cases / 23 assertions / zero failures**:

1. Ordinary actor cannot administer through production policy.
2. Private/profile actor without Medicine grants cannot get contributor or catalog authority.
3. Reviewer cannot verify own created target.
4. Stale submission cannot replace a newer independent review.
5. Withdrawn → invalidated bypass rejects and current use stays denied.
6. Synthetic registry inactive → active leaves old acceptance stale.
7. Explicit unresolved assessment cannot be verified.
8. Publication/catalog access and production evidence remain closed; no publish method exists.

This is focused confirmation, not a rerun of all Medicine suites. Phase 3.10 supplies retained 56/38/105/29 Medicine cases with 705 assertions and live journal/rollback/four lifecycle race evidence; none of those live suites was rerun here. Runtime fixtures contain synthetic policy/records only, not approved medical credentials or sources. Ambiguity denial means explicit unresolved or closed real policy; scientific overlap detection remains the identified gap, not a newly passed capability.

Document validation checks 24 required sections, exact inventory against source, links, dependency ordering, schema hash, preservation hashes and git diff --check. No PHP file changed, so PHP syntax checks are not required in this phase. No SQL or server operation occurred: normal writes **0**, new SELECT census **none**, schema changes **none**, SQL-mode changes **none**, disposable DB **none**. No credential/configuration changes, real grants, canonical records, private writes, registry changes, shared/public activation or Treatment HOLD change.

## 22. Owner Decisions Required

All remain **OWNER DECISION REQUIRED**; none is approved by this report:

| ID | Required owner decision | Recommendation / consequence while open |
| --- | --- | --- |
| O11-01 | Actual Medicine administrators, bootstrap/replacement/emergency cover and administrative reach | Minimal separate Medicine-only catalog-wide issuance/revocation allowlist; production administration stays closed |
| O11-02 | Contributor/editor/steward assignments, exact permitted actions and scopes | Narrow named grants; no inherited profile/Treatment authority or real grants now |
| O11-03 | Reviewer credentials/assessor/competence, validity/renewal, conflicts and actual reviewers | Independent attestation with grant expiry bounded by credential validity; qualification stays closed |
| O11-04 | Evidence source categories/issuers, fact coverage, hierarchy, freshness/retraction/conflicts/retention | Target-specific current identity evidence; no efficacy inference; evidence stays closed |
| O11-05 | Representative/equivalence assessor and cross-ID/S-F ambiguity resolution | One supported representative, unresolved overlaps excluded; enforcement required before review |
| O11-06 | Approval sufficiency/quorum and submission/creation accountability milestones | One independent decision minimum; separate quorum/milestone requirements need scoped implementation |
| O11-07 | Protected restoration and corrected/replacement identity policy | Keep protected restoration forbidden for pilot; no automatic recovery or relink |
| O11-08 | Registry-writer owner, privileges and lifecycle instrumentation operation | Preserve triggers/journal; no normal row correction or bulk reconciliation authorized |
| O11-09 | Deployment operator/target/window, verified backup/restore/RPO/RTO and rollback ownership | Schema-only reviewed deployment before activation; no undocumented backup assumed |
| O11-10 | Pilot size/IDs/staffing/actions/count bounds/monitoring/stop and acceptance criteria | Five individual cases proposed; no target or person selected here |
| O11-11 | Future private confirmation/reviewer-assisted linking and access/consent process | Defer patient linking; three records untouched |
| O11-12 | Future publisher/public/clinical content contract | Separate later approval/design; publication stays disabled |

These decisions can be answered by approving/amending this contract with actual assignments and evidence criteria. Do not grant people automatically upon receipt of policy answers; separately approve implementation, deployment and initial issuance.

## 23. Remaining Limitations

Phase 3.9 census is measured historical evidence; this phase does not assert unchanged current live counts/metadata through a new connection. It provides no chemical verification or credential/source appraisal. Fresh deployment metadata/counts/backup evidence remain mandatory before any DDL. No deployed grant/history/table presence is asserted by reading source.

Full schema syntax/metadata and lifecycle races were validated in disposable MariaDB 10.4.27 previously; no production upgrade, load certification, fresh isolation matrix or restore rehearsal was performed here. Phase 3.10 cannot reconstruct activity before instrumentation; old snapshots fail closed. Journal records activity only, not a complete semantic-edit history. Privileged DDL/TRUNCATE/trigger removal/falsified journal insertion lies outside ordinary application immutability; deployed writer permissions must protect instrumentation. Source/evidence policy must define current non-activity invalidation.

No professional role, source mandate, human representative or approval quorum was invented as approved. Existing code does not automatically recognize scientific ambiguity. Denials remain in place pending owner choices and required targeted code. No schema redesign, UI, public clinical knowledge, private migration or bulk reconciliation is justified here.

## 24. Phase 3.12 Recommendation

Smallest next step: owner approves/amends O11-01 through O11-10 and assigns accountable deployment/review operators, with public/patient paths still excluded. Then authorize a tightly bounded task to implement only the approved administrator/qualification/evidence-context/representative and pilot action controls, with targeted tests; resolve any approved creation/submission/quorum requirements explicitly. A schema-only deployment can be separately prepared/applied by the developer after backup/preflight without opening gates, but is not performed or authorized here.

Do not run another broad audit or population phase. Before initial real grants or one pilot record, require reviewed deployment metadata and all section 15 evidence. Retain closed public publication and unchanged Treatment HOLD. No commit, push, reset, checkout, merge or rebase in this phase.
