# Medicine Phase 3.15 — Approved pilot preparation

Result: **PASS WITH LIMITATIONS**. Read-only preparation completed; evidence, personnel, deployment and governance prerequisites remain outstanding. Per-candidate BLOCKED means an unmet operational prerequisite, not a defect preventing this assessment.

**No pilot identities were enrolled, approved, activated, or published during Phase 3.15.** No candidate drafts, evidence, grants or revisions were created. Registry labels are not verified identities, medically approved substances, therapeutic findings or regulator-confirmed records.

## 1. Five approved pilot IDs

The owner selected these IDs for preparation only, after Phase 3.14. They were neither substituted nor added to runtime configuration.

| ID | Current stored canonical label | Prospective ingredient reference |
| --- | --- | --- |
| 15 | Acetazolamide | S:15 |
| 54 | Allopurinol | S:54 |
| 319 | Carbamazepine | S:319 |
| 481 | Colchicine | S:481 |
| 2064 | Topiramate | S:2064 |

Registry identity → explicitly created candidate chemical/composition identity → submitted and independently reviewed identity → current approved identity → separately enrolled pilot identity → separately gated internal availability. None of those later stages follows from an ID or label alone.

## 2. Current registry state

Observed normal database `medical1`, port 3306, MariaDB `10.4.27-MariaDB`, at UTC **2026-10-09 21:09:10.689740**, repeated at **21:09:59.042679** (India 2026-10-10 02:39). Exactly the five records above were inspected in detail. Other registry records supplied only IDs/names/normalized names for collision and qualified-prefix screening; relationship/evidence counts were scoped to the selected five. No private medication records or real credential contents were read.

Every selected row exists and has `is_active=1`, `is_searchable=1`, `identity_status=CANDIDATE`, `identity_type=UNKNOWN`, `naming_standard=WHO_INN`. The naming-standard field is not provenance or evidence verification. Normalized labels are respectively `acetazolamide`, `allopurinol`, `carbamazepine`, `colchicine`, `topiramate`.

For each selected row: zero linked combination members, zero distinct linked combinations, zero exact-normalized unlinked component-name matches, zero registered forms and zero form-component memberships. No formatting collision, exact normalized-label collision, qualified-prefix peer or obvious compound/formulation marker was detected. Collision screening reuses Phase 3.14 NFC/case-folding/punctuation/spacing normalization; this is a naming screen, not chemical-equivalence certification.

Each row has one alias, with no source reference. Each has no verification source, reviewer or review date, zero legacy identity reviews and zero WHO-INN evidence rows. These observations do not establish the absence of authoritative evidence outside KOBI.

No changes were detected in the selected Phase 3.14 label/status/flags/provenance/combination/form/collision observations. Both live reads agreed on selected rows, readiness, schema metadata and the digest of registry names. There was no repeat full-registry governance census. No claim is made about changes in unrelated records.

Actual metadata shows none of the twelve Medicine foundation/governance/pilot/private-link schema tables deployed. This means **undeployed**, not deployed with zero rows. No persisted chemical adapter, composition revision, authority grant, appraisal journal or enrollment can currently be established. Deployment remains a known prerequisite from Phases 3.13–3.14, not a newly discovered model defect.

## 3. Identity readiness

The current model can prospectively represent each as an S-tag chemical reference and, after current independent chemical approval, a one-ingredient canonical composition. Ingredient count/chemical identity still require authoritative establishment: a simple registry label and absence of combination membership do not prove single-ingredient composition.

Canonical composition uses version 1, bounded distinct sorted registry references, persisted chemical-reference/ingredient relationships, canonical JSON key and SHA-256 key. Display labels are metadata, not identity keys. No duplicate chemical/form alternative or parallel model is proposed.

| Candidate | Technical fit | Authoritative ingredient identity/composition | Chemical form applicability | Presentation / strength / dosage form / route / release |
| --- | --- | --- | --- | --- |
| 15 Acetazolamide | Prospective S:15; PARTIAL | MISSING — REQUIRES AUTHORITATIVE INPUT | MISSING — REQUIRES AUTHORITATIVE INPUT | MISSING — REQUIRES AUTHORITATIVE INPUT |
| 54 Allopurinol | Prospective S:54; PARTIAL | MISSING — REQUIRES AUTHORITATIVE INPUT | MISSING — REQUIRES AUTHORITATIVE INPUT | MISSING — REQUIRES AUTHORITATIVE INPUT |
| 319 Carbamazepine | Prospective S:319; PARTIAL | MISSING — REQUIRES AUTHORITATIVE INPUT | MISSING — REQUIRES AUTHORITATIVE INPUT | MISSING — REQUIRES AUTHORITATIVE INPUT |
| 481 Colchicine | Prospective S:481; PARTIAL | MISSING — REQUIRES AUTHORITATIVE INPUT | MISSING — REQUIRES AUTHORITATIVE INPUT | MISSING — REQUIRES AUTHORITATIVE INPUT |
| 2064 Topiramate | Prospective S:2064; PARTIAL | MISSING — REQUIRES AUTHORITATIVE INPUT | MISSING — REQUIRES AUTHORITATIVE INPUT | MISSING — REQUIRES AUTHORITATIVE INPUT |

No strength, route, dosage form, release or chemical-form detail was inferred. Presentation is optional for an identity-only composition; absent presentation fields do **not** independently block chemical/composition enrollment. Phase 3.13 permits enrollment only for chemical/composition, not presentation. A later separately authorized presentation would need supported form/route/release and a complete per-ingredient rational strength vector with supported units/basis, validated by the existing canonicalizer. No new presentation scope was opened here.

## 4. Evidence readiness and collection checklists

Expected initial authoritative evidence: **Tier 1** primary authoritative identity/composition records or **Tier 2** recognized authoritative standards. Tier 3 established professional/clinical/pharmacological references and Tier 4 manufacturer documentation require explicit owner-controlled policy acceptance before independently qualifying; Tier 5 secondary/general sources never independently qualify. Configuration currently accepts tiers `[1,2]` but `evidence_sources=[]`.

Each candidate needs authoritative attribution of the exact chemical entity to its registry reference; ingredient composition and chemical-form applicability must be established, including distinctions if relevant. A composition approval needs its own supporting current scope/target appraisal and current chemical dependency approval. Identity evidence is not therapeutic-effectiveness evidence. Clinical/therapeutic evidence is **not applicable to this identity-only package**; any future therapeutic claim requires separately scoped authoritative collection/appraisal and is not authored here.

Checklist legend: **MISSING** means “MISSING — REQUIRES AUTHORITATIVE INPUT”; **NOT YET PROVIDED** indicates no supplied source/snapshot/appraisal; **NOT APPLICABLE** applies only to therapeutic claims in this identity-only phase.

| Candidate | Identity established | Ingredient composition | Form if applicable | Authoritative evidence | Source tier classification | Captured source/fact snapshot | Reviewer appraisal | Clinical/therapeutic evidence |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 15 Acetazolamide | MISSING | MISSING | MISSING | NOT YET PROVIDED | NOT YET PROVIDED | NOT YET PROVIDED | NOT YET PROVIDED | NOT APPLICABLE |
| 54 Allopurinol | MISSING | MISSING | MISSING | NOT YET PROVIDED | NOT YET PROVIDED | NOT YET PROVIDED | NOT YET PROVIDED | NOT APPLICABLE |
| 319 Carbamazepine | MISSING | MISSING | MISSING | NOT YET PROVIDED | NOT YET PROVIDED | NOT YET PROVIDED | NOT YET PROVIDED | NOT APPLICABLE |
| 481 Colchicine | MISSING | MISSING | MISSING | NOT YET PROVIDED | NOT YET PROVIDED | NOT YET PROVIDED | NOT YET PROVIDED | NOT APPLICABLE |
| 2064 Topiramate | MISSING | MISSING | MISSING | NOT YET PROVIDED | NOT YET PROVIDED | NOT YET PROVIDED | NOT YET PROVIDED | NOT APPLICABLE |

For **each** row, collect the actual issuer, record identifier, version, precise locator and source SHA-256, with `kind=identity`; capture the current fact snapshot and hash from the governed target after authorized preparation. Have qualified personnel appraise the record against that exact scope, target ID, snapshot hash and source record/version/locator/digest, with `identity_appraised=true` and current source/record expiry. Neither a digest nor a submitted tier establishes authority. No evidence source was invented, fetched, stored or externally verified during this phase. Existing aliases have no source references and provide no replacement for this appraisal.

## 5. Reviewer readiness

All five: **REQUIRES NAMED QUALIFIED REVIEWER**. No real reviewer or credential has been supplied/configured; absent governance tables cannot contain a deployed scoped reviewer grant. Existing private credential payloads were not accessed.

Required: actual active account; Doctor OR Pharmacist; registration/licence identifier, jurisdiction and issuing authority; verified credential status; canonical UTC microsecond verification timestamp not in the future; renewal date where applicable, still current with grant expiry bounded by renewal; actual verifier matching `qualification_assessed_by`; qualification reference and applicable scope. Administrator attestation is accountable professional verification, not an automated licence lookup. A current active, non-revoked scoped reviewer grant with valid dates is also required.

Reviewer must be independent of creator and every prior contributor who created, assessed, edited or submitted that target. Administrator/operator status does not waive independence. A qualified independent reviewer may cover the five targets if scope/current credentials and actual historical independence permit it; no person or credential is assumed here.

## 6. Administrator/operator readiness

| Role | Current configuration/availability | Required next input |
| --- | --- | --- |
| Medicine Administrator | `owner_user_ids=[]`; delegated grants unavailable before deployment; no activation administrator | Named active owner-approved administrator; accountable owner bootstrap or legitimate delegated catalog grant |
| Deployment Operator | `deployment_operator_user_ids=[]`; owners empty; no deployed delegated grant or activation operator | Named active owner/listed operator or legitimate current deployment catalog grant; verified deployment/backup records |
| Registry Operator | `registry_operator_user_ids=[]`; no deployed delegated grant | Separate named registry authority only if future registry changes are separately authorized; unnecessary for this read-only preparation |
| Contributor / Editor | No deployed scoped grants; no supplied personnel | Named active scoped contributor for chemical preparation, editor for composition submission/edits, issued through existing authorized governance |

No roles were granted. Empty operator lists do not prove that no suitable human exists; they establish that current configuration supplies no authority. Registry authority is separate and unnecessary for adopting neither labels nor records in this phase. No registry writes are proposed.

## 7. Revision readiness and matrix

Every candidate: **REQUIRES MEDICINE IDENTITY PREPARATION**. There is no suitable persisted governed chemical/composition revision in this undeployed normal database. A registry substance ID is not a chemical-ref/composition target ID, revision token, approval event or enrollment event. No drafts were created.

| Candidate | Identity | Evidence | Reviewer | Revision | Pilot Gate | Overall |
| --- | --- | --- | --- | --- | --- | --- |
| 15 Acetazolamide | PARTIAL | NOT YET PROVIDED | NOT YET PROVIDED | BLOCKED | BLOCKED | BLOCKED |
| 54 Allopurinol | PARTIAL | NOT YET PROVIDED | NOT YET PROVIDED | BLOCKED | BLOCKED | BLOCKED |
| 319 Carbamazepine | PARTIAL | NOT YET PROVIDED | NOT YET PROVIDED | BLOCKED | BLOCKED | BLOCKED |
| 481 Colchicine | PARTIAL | NOT YET PROVIDED | NOT YET PROVIDED | BLOCKED | BLOCKED | BLOCKED |
| 2064 Topiramate | PARTIAL | NOT YET PROVIDED | NOT YET PROVIDED | BLOCKED | BLOCKED | BLOCKED |

## 8. Pilot enrollment readiness

All five can prospectively follow the existing chemical/composition workflow, conditional on evidence resolving their actual identities. None currently satisfies enrollment.

| Gate | Current finding for all five |
| --- | --- |
| Explicit ID | Owner-approved for preparation; runtime `pilot_substances=[]`, not authorized intake |
| Eligible revision | No deployed target/revision/current snapshot |
| Evidence | No appraised issuer/record configuration, supporting approval or current dependencies |
| Reviewer / independent approval | Named qualified reviewer and actual independent approval unavailable |
| Lifecycle | Registry active/CANDIDATE; this does not establish governed eligibility/history. Current reviewed lifecycle must be checked after preparation |
| Operator authority | No configured owner/listed/delegated deployment authority |
| Public publication | No Medicine publication method/public route connected; internal responses enforce `published=false`; future activation needs `public_disabled=true` and route/search/SEO inspection |
| Pilot configuration | `pilot_enabled=false`, `pilot_internal_enabled=false`, `pilot_code=''`, `pilot_activation=[]`; default limit 10 and actions `register_reference`, `create_identity` do not enable intake |

Current approval must survive exact fact hash, reviewer/grant/credential, appraisal expiry, dependency and durable registry/lifecycle rechecks. Withdrawn/superseded history cannot be automatically restored; retirement is terminal. Invalidation requires explicit reassessment, fresh submission and independent review, then fresh operator enrollment. No adverse history was fabricated or overwritten.

## 9. Candidate-specific blockers

| Candidate | Work still needed for this exact candidate |
| --- | --- |
| 15 Acetazolamide | Establish S:15 entity/composition/form applicability from authoritative records; collect/appraise target-specific evidence; prepare and review its exact chemical/composition revision |
| 54 Allopurinol | Establish S:54 entity/composition/form applicability from authoritative records; collect/appraise target-specific evidence; prepare and review its exact chemical/composition revision |
| 319 Carbamazepine | Establish S:319 entity/composition/form applicability from authoritative records; collect/appraise target-specific evidence; prepare and review its exact chemical/composition revision |
| 481 Colchicine | Establish S:481 entity/composition/form applicability from authoritative records; collect/appraise target-specific evidence; prepare and review its exact chemical/composition revision |
| 2064 Topiramate | Establish S:2064 entity/composition/form applicability from authoritative records; collect/appraise target-specific evidence; prepare and review its exact chemical/composition revision |

No newly observed naming collision, combination/form relationship or specific representation defect requires replacing any selected ID. Label-level fit remains provisional. Each item's evidence and revision are separate obligations even when the same reviewer/source class is used.

## 10. Common blockers

Undeployed Medicine schema; no named administrator/deployment operator/contributors/editor/reviewer; no verified professional qualification or current scoped grants; no configured evidence issuer/appraisals; no appraised representative references; bounded intake disabled and empty; no independent approvals/enrollment manifest; no recorded verified backup/deployment/public-disabled attestations. These can be prepared centrally, but approvals and appraisal bindings remain target-specific. No public Medicine access is to be enabled.

## 11. Exact remaining activation prerequisites

These are prerequisites for a **separate authorized operational phase**, not actions performed or newly authorized here.

1. Named deployment operator verifies backup and restoration in a separate environment and records an accountable `backup_reference`. Inspect actual metadata; deploy reviewed compatible Medicine schema/code through the Phase 3.13 controlled procedure, without a full dump over normal KOBI. Verify columns/CHECKs/FKs/indexes/immutable triggers, supported runtimes, unchanged SQL mode, private-record behavior and Treatment HOLD; record `deployment_reference`.
2. Supply named active administrator, deployment operator, qualified independent reviewer and contributor/editor accounts. Establish owner bootstrap/delegated scoped authority legitimately; verify professional credentials and current grants. Registry operator only for separately authorized registry operations.
3. Obtain authoritative identity/composition/form-applicability evidence for each fixed S ID. Approve issuer tier/scope/expiry and exact appraised records; explicitly appraise each representative. Configure the five unique canonical positive decimal `substance_id` strings with `unambiguous=true` and unique nonblank `representative_reference`, a valid lowercase `pilot_code` (1–64 alphanumeric/underscore/hyphen), bounded integer limit 5–10 and permitted intake actions. Owner selection in this document does not substitute for that configuration.
4. After separately authorized bounded intake is enabled, explicitly create each candidate chemical reference with immutable creation/intake marker. Assess, submit supporting identity evidence and obtain fresh qualified independent approval for its current revision/hash. For composition scope, resolve currently approved chemical dependencies, create canonical composition and separately submit/review it. Retain all decisions/history. No automatic registry adoption.
5. Actual deployment-authorized operator enrolls each exact current approved chemical/composition revision using `expected_revision`, `expected_snapshot_hash`, `expected_enrollment_event_id` (string `0` initially, otherwise actual latest operation ID), and rationale. Record actual approval and enrollment event IDs. Enrollment remains separate from intake-marker creation and does not increment the clinical revision.
6. Prepare deployment-controlled `pilot_activation`: matching `pilot_code`; positive integer `administrator_id`, `deployment_operator_id`, `reviewer_id`; nonblank verified `backup_reference` and `deployment_reference` (maximum 512 bytes); `public_disabled=true`; unique five-entry manifest for this fixed scope, each with `scope` chemical/composition and canonical positive decimal string `target_id`, `revision`, `approval_event_id`, `enrollment_event_id`. All underlying current authority, facts, appraisal, approval, dependencies, lifecycle and original enrolling operator grant must remain valid.
7. Inspect absence of Medicine public endpoints, search/SEO integration and public claims. Do not enable public Medicine publication. Only after explicit authorized review of the entire manifest/attestations may `pilot_internal_enabled` be set true. Every internal read must continue to check all entries and actual scoped access; administrator/deployment authority alone does not authorize internal content reads.
8. Separately validate authorized/unauthorized internal paths and pause/rollback: disable internal/intake flags, append `disablePilot` operations when needed, revoke authority or apply steward decisions as appropriate, preserve audit history. Reissued grants or old approvals cannot automatically revive availability.

No step in this checklist was executed by Phase 3.15.

## 12. Security findings and tests

Reviewed root AGENTS, Phases 3.8–3.14 documentation/schema, Medicine repository, policy, authorization, identity/governance/eligibility services, configuration and policy/governance/pilot/foundation tests. The preparation tool is CLI-only, uses the existing Database connection and shared SELECT/collision helpers, never bootstraps a session or invokes governed mutation/read services. It exports only the fixed five safe registry details, aggregate counts, metadata, empty/disabled configuration and readiness; it excludes credential contents, source payloads and other registry names. It stops on changed deployed Medicine metadata or real personnel/evidence/pilot configuration rather than claiming readiness without a deeper authorized assessment.

Knowing a substance ID supplies none of actual authenticated active actor, scoped authority, professional qualification, historical independence, exact target/revision/hash, current evidence or deployment authority. `verify` requires independent scoped reviewer/current submitted facts; `submit`/edits require contributor/editor authority. `enrollPilot` additionally requires deployment authority, approved revision and current intake scope. Public `publish` action is denied and no publication service method exists. Registry authority is separate; no registry mutation API was introduced. Internal reads expose minimal `published=false` projection, not reviewer credentials. Existing routed medicine pages are private health-record CRUD, not these governance services. No new endpoint or public preparation path exists.

Executed database-free regressions: pilot **40 cases / 143 assertions**, policy **33 / 92**, governance **56 / 249**, foundation **105 / 216**: **234 cases / 700 assertions, zero failures**. These include unauthorized enrollment/evidence changes, client role escalation, self-review, credential/privacy, public-action denial and original-operator revocation paths. Synthetic in-memory fixtures do not verify real people or evidence and do not connect to normal DB.

Readiness **41 checks, zero failures**: five live records, exact deterministic scope, reported missing prerequisites, live classification reproduction, reversed-order independence, injected combination/unlinked-member/form/component blockers, injected formatting collision and qualified label, missing/substituted ID rejection, rejected write/DDL/locking/export/mode-changing SQL, undeployed schema and stable SQL mode. Two live SELECT observations matched on rows/readiness/name digest/metadata (four additional comparisons, all passed). PHP syntax checks passed for both test files; `git diff --check` passed. No live mutation harness was needed or run. Real review, deployment, backup restore, runtime enrollment/activation and browser authorization tests are deferred to a separately authorized operational phase.

Reproduce: run `D:\xampp\php\php.exe tests/medicine-pilot-readiness.php audit`, save its UTF-8 JSON stdout to `tests/.medicine315-readiness.json`, then run the same script with `test`. `audit` connects only to normal `medical1:3306` and performs five fixed SELECT statements; `test` reads that local snapshot and uses in-memory fixtures only. Remove temporary snapshots after assessment. The shared SQL guard protects these fixed audit queries; it is not a general-purpose SQL parser or read-only database-account guarantee. No SQL input is accepted from a web request.

## 13. Database safety and file accounting

**Normal KOBI database was read-only; no INSERT/UPDATE/DELETE/DDL was performed.** No pilot or governance command, grant, approval, evidence write, schema deployment, registry/private/Treatment/disease write was executed. Two successful audit passes performed ten SELECT statements total. The initial sandboxed connection was unavailable; the authorized local SELECT-only connection then succeeded. No disposable DB or server was created/changed.

SQL mode observed before/after each read: `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`. No session, project or global/server SQL-mode directive was executed or modified. `database/structure.sql`, application code/configuration and credentials remain unchanged. No commit, push, reset, checkout, merge or rebase was performed.

Created: this document; `tests/medicine-pilot-readiness.php`. Modified: `tests/medicine-candidate-selection.php` only adds a library guard so the shared read-only helpers can be reused without running its full census CLI; existing tests/assertions are unchanged. Temporary audit snapshots were removed after verification. Hash comparison of 298 pre-existing files under app/public/tests/bootstrap/database/routes/docs found only that intended helper modification; all 297 other files were unchanged. The two additions are the files listed above.

## 14. Recommended next step

Supply the actual named personnel, independently verified credentials, authoritative item-specific evidence/appraisals and accountable deployment/backup plan. Review this fixed package before separately authorizing controlled schema deployment and candidate governance work. No new representative was selected.

**Do not activate the pilot until the five candidates have current evidence, qualified independent review, explicit approval, and all Phase 3.13 activation prerequisites are satisfied.**
