# KOBI — Medicine Phase 3.6: Persisted Medicine Authority & Registry Reconciliation

Audit/design date: 2026-10-09. **Result: PASS WITH LIMITATIONS. Design readiness: READY WITH OPEN DESIGN QUESTIONS.** This is a reviewable proposal, not implementation or authorization to activate writes. Observed behavior is identified as current; recommendations describe future requirements. No existing file, schema, configuration or data was changed.

## 1. Executive summary

**KOBI can structurally distinguish a user selection from a canonical ingredient, but cannot yet establish the complete auditable authority chain needed to activate shared Medicine writes.** A stable substance ID is a reference, not proof of trusted chemical identity. The Phase 3.5 service validates persisted verified adapters and active registry dependencies; its production catalog authority deliberately denies every shared write.

Recommend explicit Medicine contributor grants, independently qualified identity reviewers, a separate grant administrator, immutable revision-bound review/evidence history, and evidence-backed registry reconciliation selecting one representative typed reference per chemical entity. Ordinary users and profile owners/editors retain private medication rights only. Treatment authority must never be reused as Medicine authority. Public approval/publication requires a later independent model and remains unavailable.

The missing authority/history/reconciliation mechanisms block production activation, not completion of this design audit. Live registry quality is unknown: no local mysqld process or default-port listener was available, no database connection was attempted, and the schema dump contains no INSERT/REPLACE data statements. AUTO_INCREMENT values do not establish row counts or eligibility. No record is classified as trusted by this report.

## 2. Current authority model

| Observed boundary | Actual evidence | Meaning |
| --- | --- | --- |
| Authentication | `AuthService::id()`; `MedicineIdentityService::actor()` rechecks active `users` | Establishes an actor, not a catalog role |
| Profile access | `HealthProfileRepository::findAccessible`; `health_profile_members.role/status` | Private profile rights; owner/editor may change medications; viewer cannot |
| Legacy medication access | `HealthMedicineService` → `HealthProfileService`; row/profile equality and owner/editor checks | Existing personal logging, not shared knowledge authority |
| Medicine shared commands | `MedicineAuthorizationService::requireCatalog(int $actor, string $action)` always throws safe 403 | No production shared creator, reviewer or publisher exists |
| Existing Treatment grants | `treatment_editorial_permissions` and `TreatmentEditorialAuthorizationService` | Persisted, revocable capabilities scoped by `treatment_system_id`, strictly Treatment-only |
| Treatment grant administration | `TreatmentGovernancePolicy` / `app/Config/Config.php` deployment-controlled `permission_admin_user_ids` | Not a general KOBI administrator role or a Medicine grant |
| Reviewer attribution | Foundation `verified_by/verified_at/verification_reference` | Stored attribution shape, not qualification, independence or immutable approval history |

No universal administrator/editor role column was found in `users`. No registry governance writer was found in the searched application code. Test-only `MedicineTestAuthority` grants and synthetic verified fixtures are not production authority. Future authorization can retain the existing service/repository/DI pattern without a framework rewrite, but needs a separate persisted Medicine contract.

## 3. Existing substance registry audit

This is a **structural and code-semantics audit**, not a row-level census. Current `SubstanceRepository::search()` returns only `substance_id,canonical_name`, using `is_active=1`, `is_searchable=1`, prefix matching on `canonical_name_normalized`, alphabetical order and a capped limit of ten. `SubstanceService::search()` trims input. `AccountController::searchSubstances()` returns JSON via `/api/substances/search`; this action has no explicit authentication check. Existing behavior was inspected, not altered. No verified-selection predicate or clinical review check participates in search.

| `kobi_substances` field | Proven storage/use | Authority limitation |
| --- | --- | --- |
| `substance_id` | BIGINT unsigned PK | Stable identifier, no semantic approval |
| `canonical_name` | Required human label | Names cannot prove chemical equivalence |
| `canonical_name_normalized` | Required UNIQUE under utf8mb4_unicode_ci; prefix search | No normalization writer/algorithm found; uniqueness is collation-based, not scientific deduplication |
| `naming_standard` | Nullable VARCHAR default WHO_INN | A default is not a verified naming-source decision |
| `identity_type` | Nullable VARCHAR default UNKNOWN | No enforced taxonomy or known live-value meanings |
| `identity_status` | Nullable VARCHAR default CANDIDATE | No constrained status vocabulary or implemented trusted-registry predicate |
| `is_searchable` | Default 1; autocomplete filter | Discovery only |
| `is_active` | Default 1; autocomplete and foundation dependency check | Operational availability only; no clinical verification |
| `verification_source` | Nullable text | No mandatory evidence/version/current-source relationship |
| `reviewed_by`, `reviewed_at` | Nullable BIGINT unsigned reviewer/date; no reviewer FK | No established qualification, independence or revision binding; user IDs elsewhere are signed INT |
| `description` | Optional text | No governed clinical meaning established |
| `created_at`, `updated_at` | Administrative timestamps | No immutable semantic revision or review snapshot |

Related assets exist structurally:

- `kobi_substance_forms`: parent `substance_id`, names, `form_type`, `form_status`, `canonical_form_name`, `verification_source`, `is_active`; default UNKNOWN/CANDIDATE strings. A named chemical-form registry exists, but its populated semantics are unverified. It is not a dosage-form catalog.
- `kobi_form_components`: typed named components, optional substance FK, `stoichiometry`, `verification_status`, `evidence_reference`. These fields do not authorize salt/base potency conversion.
- `kobi_substance_aliases`: names, optional substance link, source keys/reference, review/search/active flags. An alias is not a new ingredient.
- `kobi_identity_reviews`: candidate substance/form links, source/alias, decision, reason, evidence and reviewer/date. No qualification, reviewer FK, immutable target revision/snapshot, enforced independent workflow or append-only contract exists. Candidate FKs use SET NULL/CASCADE actions, unsuitable alone as immutable historical identity proof.
- `kobi_who_inn_evidence`: INN names/status, list/date, publication/PDF URLs, page, SHA-256, verification status and notes. It can hold naming evidence; presence does not prove it was reviewed or supports every required identity fact.
- `kobi_combinations` / `kobi_combination_items`: source/name, review flags, nullable substance/form references and `strength_text`. Named combinations are not automatically the new complete canonical composition/presentation.
- `kobi_substance_search`: canonical/alias UNION view; current search repository does not use it. The alias arm does not recheck the linked substance's active status. Neither view output nor alias searchability is a trust gate.

No application references to the legacy review/evidence/component tables or search view were found in app/routes/public/bootstrap searches. This does not prove no external importer or manual writer exists. Live counts, nullness/status distributions, duplicates, source quality, form meanings and importer provenance remain unknown.

## 4. Existing personal medication audit

`health_medicines` stores profile-owned use records: `id`, `health_profile_id`, required `brand_name`, optional `generic_name`, `strength`, `dose`, `frequency`, `route`, `started_on`, `stopped_on`, `prescribed_by`, `notes`, current/stopped/historical `status`, and administrative timestamps. There is no dedicated instructions field; notes may contain instructions, but actual contents were not inspected. Strength/dose/route/frequency are free text. No substance, identity or presentation FK exists in this original table.

`AccountController` create/update methods pass explicit text/date fields to `HealthMedicineService`; `HealthMedicineRepository` persists those fields. The create/edit views use CSRF and existing page JavaScript. `health-medicine.js` receives autocomplete objects but calls `selectSubstance(item.canonical_name)`, preserving names in generic text and discarding the returned ID. Multiple comma-separated names are not evidence of a co-formulated combination. The endpoints remain disconnected from `MedicineIdentityService`.

The new private sidecars can preserve an explicitly selected reference through `chemical_ref_id`; this is a new internal persistence path, not evidence that live legacy records already contain IDs. No live medication rows or private health data were accessed. No automatic mapping is justified for the original model. Existing Phase 3.1 error-disclosure, today-date and endpoint validation issues remain unchanged and separately scoped.

## 5. Existing Medicine schema authority analysis

| Existing table | Identity/persistence facts | Authority currently stops at |
| --- | --- | --- |
| `kobi_medicine_chemical_refs` | XOR existing `substance_id` / `form_id`, separate UNIQUE reservations | One typed adapter per target; not cross-registry proof that S and F are distinct entities |
| `kobi_medicine_identities` | `composition_key`, `composition_hash`, `key_version`; complete typed ingredient composition | Deterministic structure/equality, not scientific review or clinical equivalence |
| `kobi_medicine_ingredients` | Identity + adapter memberships, scoped member IDs and display order | Membership integrity; no independent review/source history |
| `kobi_medicine_presentations` | Parent identity; complete key/hash, form/route/release; separate variants | Structured presentation identity, not reviewed clinical use |
| `kobi_medicine_strengths` | Scoped parent/member FKs, positive exact integer ratios, g numerator, unit/mL/g denominator | Exact representability, not source-supported factual strength |
| `health_medicine_identity_links` | One private sidecar, exclusive composition/presentation/ingredients mode, link revision/actors | Private association, not shared authority |
| `health_medicine_selected_ingredients` | Private ordered adapter selections under ingredients mode | User observation, not canonical composition confirmation |

Chemical adapters, identities and presentations each have `identity_status` candidate/verified/retired, `metadata_revision`, creation attribution, verification tuple and retirement tuple. CHECKs enforce shape and allowed values; FKs protect attribution/reference existence. They do not prove reviewer qualifications, self-review prevention, legal transitions, approved evidence, current evidence, review independence or tuple immutability against arbitrary SQL.

Phase 3.5 `resolve()` requires a verified adapter; `lockRegistry()` checks active substance/form/parent; `loadIdentity()` / `loadPresentation()` recompute full aggregate keys and vectors. `state()` checks status, revision and nonempty verification attribution with a valid timestamp. **It does not consult the old registry verification fields, a qualification grant, an evidence snapshot or a registry semantic fingerprint.** Changed meaning under the same still-active registry ID could therefore leave an apparently verified adapter usable unless future governance coordinates invalidation.

Creation yields candidates. There is no shared metadata-edit, verify, approve, retire, merge or publish command. No persisted Medicine grants, immutable Medicine events/evidence bindings, supersession relationships, clinical content, locale-based public projection, approved/published state or public gate exists. These are missing design requirements, not additional existing fields. Do not add new meanings to existing status strings or repurpose private sidecars/Treatment tables.

## 6. Canonical Medicine Identity authority model

Recommended roles below are **proposed responsibilities**, not existing KOBI roles or approved personnel assignments.

| Actor | Private medication | Shared candidate creation/metadata edits | Identity verification | Public publication |
| --- | --- | --- | --- | --- |
| Ordinary user | Only authorized profile rights | Denied by default | Denied | Denied |
| Profile owner/editor | Existing scoped logging/association rights | No automatic authority | Denied | Denied |
| Operational administrator | Only separately authorized scope | No automatic content authority | No automatic qualification | No automatic publisher authority |
| Explicit Medicine contributor | No extra patient access | Permitted within persisted Medicine grant and audited commands | Cannot approve own work | Denied |
| Qualified Medicine identity reviewer | No extra patient access | Only if separately granted contributor rights | Independent revision/evidence-bound verification within qualification scope | No automatic publisher rights |
| Separate publisher | No extra patient access | Only if separately granted | Cannot substitute publication for identity review | Future separate public governance only |
| System/service actor | None by default | Optional narrowly scoped candidate staging after separate approval; named accountable actor | No autonomous verification/merge | Denied |

An administrator may grant/revoke authorized roles only through an approved bootstrap and persisted grant history; granting oneself review power or self-review cannot bypass independence. Medical/editorial credentials must be checked by the designated authority; a qualification text field alone is insufficient. Administrative status, email verification, a profile role and a Treatment grant confer no Medicine content privilege.

Current `requireCatalog` actions are `register_reference`, `create_identity`, `create_presentation`. A future persisted policy must map each action explicitly, reject unknown actions, and validate actor/grant status inside the owning transaction. Its current signature has no target/scope argument: an initial explicit Medicine-catalog-wide grant may cover only those actions; narrower target scopes would require a separately reviewed service contract extension, not an assumed hidden capability. Future metadata/verification/retirement actions need separate explicit capabilities and commands.

Identity-defining tuples stay immutable. New ingredient sets/forms or presentation strengths/form/route/release create new identities/presentations; editing verified meaning in place is prohibited. Future metadata-only edits require authorization, expected `metadata_revision`, an event and a defined review-invalidating policy. Recommend re-review for any changed fact included in an approved snapshot; labels never change chemical equality. Modification capability does not yet exist in Phase 3.5.

## 7. Substance eligibility model

Use the following **assessment categories**, not new values written into `kobi_substances.identity_status`. Actual row classifications require a later read-only census and reviewed evidence.

| Category | Required basis | Canonical ingredient eligibility |
| --- | --- | --- |
| Eligible candidate for reconciliation | Stable existing target, active, supported apparent entity scope, assessable provenance | May be assessed/register a candidate adapter by a granted contributor; not yet usable by current composition creation |
| Verified representative | Independent qualified decision, complete identity evidence, unambiguous S/F representative, current dependency snapshot and provenance | Eligible only through a current verified adapter and service validation |
| Insufficient information | Missing source/type/form/identity scope or unsupported verification claim | Block; retain registry/personal data |
| Ambiguous | Conflicting evidence, unclear form, unresolved active entity | Block pending review |
| Suspected duplicate | Possible same entity across IDs, aliases or S/F targets | Block conflicting representatives pending independent decision |
| Inactive | `is_active` is not exactly usable | Block new use even if older adapter says verified |
| Withdrawn/deprecated/superseded | Authoritative adverse or replacement decision, when such a concept is governed | Block new use; preserve history; existing registry has no enforced withdrawal/supersession contract |
| Unsuitable/unsupported | Combination encoded as one substance, excipient/form component or identity beyond supported scope | Do not flatten into an initial active ingredient; preserve unresolved data |

Trusted means a current independently evidenced **identity**, not therapeutic efficacy. `WHO_INN`, active/searchable flags, an arbitrary VERIFIED string, a nonempty URL or a reviewer ID are insufficient individually or together without the authority/revision/evidence chain. Existing verification claims may be reused as evidence inputs after audit, never imported as automatic approvals.

## 8. Registry reconciliation rules

1. Preserve stable existing IDs and original records. Establish the actual meaning and provenance of each substance/form before deciding eligibility; never infer a chemical entity from a label alone.
2. Resolve whether the exact chemical entity is represented by SUBSTANCE(id) or CHEMICAL_FORM(form_id). Derive a form's parent from its reviewed relationship. Choose one approved representative per exact entity across both registries; current UNIQUEs only deduplicate each target, not S/F semantic overlap.
3. Use the substance reference directly when that reviewed row already denotes the intended exact entity. Do not manufacture a form row for an ordinary substance. Use an existing form only when its independent chemical identity and parent are established.
4. Do not infer salt/base, hydrate/solvate, stereochemistry, molecular equivalence, active-moiety relation or conversion factors from names, parent IDs or stoichiometry. A relationship is not identity equality or dose interchangeability.
5. Register a candidate adapter only through a separately authorized future authority. Independently verify the registry snapshot and adapter with attributable evidence. Current composition creation cannot consume candidate adapters; no temporary relaxation is proposed.
6. Once all members have current verified representatives, create a complete candidate composition through the exact Phase 3.5 contract. A complete combination needs evidence that this ingredient set is intended; individual ingredient trust alone cannot verify the combination.
7. Verify composition separately, then source and verify each presentation's full ingredient-strength vector and explicit supported form/route/release. Unsupported biological potency/base-equivalent conversions and complex formulations remain unresolved.
8. Coordinate all semantic registry changes with governed revisions/fingerprints and dependency invalidation. Mere `updated_at` or continued active status must not preserve an obsolete review. Existing external/manual writers must be inventoried; uncontrolled semantic changes are an activation blocker.

Minimum additional governance concepts: persisted Medicine grants/revocation; evidenced representative mapping/eligibility decisions; immutable snapshots and review events bound to target revision and registry semantics; current evidence validity; dependency invalidation. The seven foundation tables hold final structure and attribution but cannot alone represent this chain. Future additive persistence needs explicit design/approval; no DDL or exact new table definitions are proposed here.

## 9. Duplicate/ambiguity policy

| Situation | Required decision |
| --- | --- |
| Exact same typed ID repeated | Reject duplicate ingredient, as current service does |
| Same complete composition/presentation key | Reuse the unique owner after current full-key/aggregate validation; no new scientific assertion |
| Same visible name under different IDs | Suspect duplicate; do not merge by display equality |
| Same normalized name/case variant | Existing UNIQUE/collation blocks equal stored normalized keys within a valid substance table; still audit normalization mismatches, aliases and cross-registry overlap. No fuzzy merge |
| Substance A versus form A | Resolve representative using evidence; tags being different does not prove chemically different |
| Base versus salt, hydrate or stereoisomer | Treat as distinct or unresolved until qualified evidence establishes relationship; no automatic equivalence or conversion |
| Similar names but demonstrably distinct entities | Preserve distinct IDs/representatives and explanatory evidence |
| Historical alias/combination/free text | Preserve source wording and links; reviewer assessment cannot overwrite private observations |

An evidenced duplicate correction is a separately approved reconciliation operation, not automatic merging. Preserve loser IDs, historical reference meaning, rationale, reviewers, evidence and affected dependencies; do not rewrite existing canonical key membership or medication FKs silently. A reviewed representative/supersession mapping may guide future explicit selections, but history remains attached to its original target. If the current schema cannot represent that mapping safely, stop for an additive design rather than repointing rows. Retired canonical keys stay reserved.

## 10. Verification lifecycle

Retain the foundation's **candidate / verified / retired** identity statuses. Do not add approved/published to these CHECK-protected fields or reinterpret verified as clinical publication. Proposed review queues and approval/publication records are separate governance concepts needing a future schema contract.

| Stage | Requirements and visibility |
| --- | --- |
| Candidate | Authorized creation; complete deterministic structure; attributed audit event; composition already depends on verified adapters under Phase 3.5. Internal only, untrusted as reviewed composition |
| Pending review / changes required | Proposed workflow state outside current identity_status; immutable submitted snapshot, evidence/dependency versions, qualified independent reviewer; not an implemented field |
| Verified identity | Exact reviewed revision/key/membership and dependency snapshots match current state; reviewer authorized/qualified and independent of creator/all substantive contributors; source facts current; verification decision and history retained |
| Approved for shared internal use | Proposed acceptance decision: current verified identity and dependencies, supported scope, governance checks met. Could be derived from current verified review rather than a new identity_status; final decision remains open |
| Published knowledge | Separate future content/source/evidence/risk/locale/publication approval and publisher capability; current seven tables cannot express it. No public output from identity verification alone |
| Retired | Terminal identity reservation/history; no new association or restoration; replacement needs separately verified entity/key and reviewed relationship |

Recommended identity transitions: candidate → verified or retired; verified → retired. Review invalidation can make effective eligibility false without fabricating an unimplemented withdrawn/suspended status or erasing prior verification. Define metadata re-review and invalidation in the later governance contract. No lifecycle command is implemented or activated here.

Evidence must establish the specific fact being approved:

| Fact | Proposed evidence requirement |
| --- | --- |
| Substance identity/naming | Traceable authoritative identity/naming record with exact entity scope; naming evidence does not establish every molecular/form fact |
| Chemical form/parent | Explicit form identity, relationships and distinctions; no name-only inference |
| Ingredient composition | Source explicitly supporting the complete active set; no assumption from co-mentioned personal selections |
| Strength | Each exact ingredient, measured chemical basis, quantity/unit/denominator and full vector |
| Dosage form/route/release | Explicit supported pharmaceutical attributes; no guessed route or release default |
| Presentation | Consistent combined identity/strength/form/route/release evidence tied to the canonical key |
| Therapeutic effectiveness | Separate clinical evidence appraisal and clinical governance; never inherited from identity/naming verification |

Candidate source classes for policy review include official naming/chemical registries, pharmacopoeial identity records, regulator-approved product documents and attributable manufacturer specifications. They are proposals, not an accepted-source allowlist. Preserve source issuer, record/version/date, exact locator/page, retrieved date, immutable digest/snapshot where permitted, asserted facts and conflict/withdrawal status. Existing free-text verification references/WHO rows alone are insufficient. Reviewer qualification, acceptable source hierarchy, conflicts and freshness rules require human approval.

## 11. Inactive/withdrawn policy

Current code already rejects new canonical creation/association through inactive underlying substances/forms or retired/unverified adapters; it does not perform reverse lifecycle cascades or public unpublishing. Existing private association reads return historical IDs without asserting current validity.

Proposed response to dependency loss:

| Event | Future governed response |
| --- | --- |
| Inactivation, evidence withdrawal or unresolved ambiguity | Immediately deny new use/verification/public eligibility; invalidate dependent current acceptance; queue reassessment |
| Supersession/deprecation | Preserve original semantic target/history; permit a replacement only after separate review, never automatic FK rewriting |
| Proven incorrect identity/composition | Record adverse decision; block affected identity and every presentation; retire when a final terminal decision is justified |
| Administrative reactivation | Does not automatically restore evidence/approval; require current review before eligibility resumes |
| Canonical obsolescence | Keep stable IDs and reserved keys, retain history; final retirement terminal; no silent restoration |

A combination fails effective eligibility when any member fails. A presentation fails when its identity or any required source/dependency fails. Existing private medication text and historical links remain intact; future UI may show an authorized current-validity warning without diagnosing or giving treatment advice. Historical identity validity and eligibility for new use are distinct.

A later public reader must dynamically deny ineligible dependencies even before a background invalidation job runs; historical publication events must remain auditable. There is currently no Medicine public reader to change. Avoid mass-changing every child to retired for a temporary issue: preserve historical decisions and govern effective eligibility separately. Exact invalidation storage and re-review policy remain open.

## 12. Legacy medication linking policy

| Link method | Allowed future contract |
| --- | --- |
| Safe automatic reuse | Only an already persisted unambiguous identity/presentation relationship, with provenance, target scope and current eligibility. Merely having a substance ID proves a selected entity, not a medicine/presentation. No eligible automatic legacy batch is demonstrated |
| Reviewer-assisted | Qualified reviewer confirms exact level of identity with source/relationship evidence, within separately authorized private profile access and reconciliation approval; catalog grant alone cannot read patient records |
| User-confirmed | Owner/editor explicitly selects a current verified target for a private observation; no change to catalog status/authority and no claim of independent clinical verification |
| Unresolved record | Keep original raw name, strength, dose, route, dates and notes; no manufactured canonical identity |

Use composition level only when exact ingredient composition is established; presentation level requires the complete supported strength/form/route/release. Strength/route similarity is insufficient. Multiple selected IDs may remain ingredients-mode observation without becoming a canonical combination. Never infer dosage advice, commercial product, therapeutic equivalence or user dose from canonical identity.

Future integrated saves must authenticate through `AuthService::id()`, use `findAccessible`, check owner/editor and exact row/profile, validate CSRF, and atomically preserve raw text while replacing sidecars. Compare locked expected raw/association digest and `link_revision` as in Phase 3.5; preserve the delete/recreate ABA protection. Audit explicit linking/unlinking provenance without copying private content into shared records/logs. No sidecar removal deletes original medication text.

No bulk migration is authorized. The existing seven-table private attribution is not an immutable linking-review history. Reviewer-assisted/batch workflows require a separate provenance/consent/access and rollback contract before implementation, plus the separately scoped Phase 3.1 handler prerequisites.

## 13. Reviewer/editor/publisher responsibilities

Contributors assess/register candidate adapters and create supported candidate aggregates under explicit capability; they supply evidence, cannot alter tuples in place, and cannot self-verify. Reviewers evaluate exact entity representation, full composition/presentation and current evidence within a verified qualification scope. Require reviewer distinct from every substantive contributor to the reviewed facts, not merely different from `created_by`; the current tables cannot prove that contributor history.

Grant administrators validate qualifications, assign limited domain/action scope, preserve grant/revoke reasons and accountable actors, and control an explicit deployment/bootstrap procedure. No user may manufacture credentials or derive Medicine privilege from Treatment permissions. Multiple hats require separate grants and never remove independence restrictions. A reviewer may review unrelated work if scoped and independent; a publisher cannot repair missing review by approving exposure.

Minimum immutable audit record for future writes/decisions: subject type/ID, command, actor and authorizing grant/version, timestamp, expected/result revision, canonical full key/hash, affected dependency identities/revisions/fingerprints, source snapshots, contributor/reviewer attribution, decision/rationale and supersession/invalidation relationships. Grant issuance/revocation and rejected governance decisions need auditable outcomes without patient-content disclosure. Audit failure must roll back the associated command. Existing attribution columns and `kobi_identity_reviews` are not this event ledger.

Publishers are reserved for separately approved future clinical/content/public governance. Identity review does not approve indications, interactions, safety advice, dose or efficacy. Service accounts, if introduced, have bounded jobs with named responsibility, no blanket trust and no independent review role.

## 14. Security model

Preserve Controller → Service → Repository → Database, concrete DI, BaseRepository parameterized queries, server-side authorization, CSRF, local redirects, existing AJAX/JSON, external JavaScript/CSP and Bootstrap conventions. No new framework or generic RBAC rewrite is needed.

For future governance commands, derive the actual actor from authentication; lock/recheck active actor, current grant, qualification and dependency state in the owning transaction. Revocation/inactivation racing a write must yield either a write validly ordered before revocation or a denied write after it; stale/cached privileges must never authorize commit. Preserve Phase 3.5 ordering (authority/profile before references/registry, then identity/presentation, then private row/link), with a reviewed global order for new grant/review commands and bounded whole-transaction retries.

Use explicit action/field allowlists and expected revisions/snapshots. Fail closed on absent/unknown/expired/revoked grants, unsupported identity, ambiguous mapping, missing evidence, self-review or source invalidation. Qualifications are established by an approved authority, not a client-supplied label. SQL mode remains unchanged; controlled values and exact quantities are validated by services. No raw SQL errors/secrets/paths/private records in normal responses or shared audit entries.

Catalog access never grants health-profile access. Do not extend the existing autocomplete endpoint into a candidate/verified catalog response or expose review/internal fields. Future internal trusted-selection queries need their own current-eligibility predicate and appropriate authorization. Public knowledge requires a later explicit projection/gate; Treatment HOLD remains untouched.

## 15. Proposed reconciliation workflow

```text
Read-only registry inventory + provenance/semantic assessment
    → unresolved / unsuitable / inactive: retain; block canonical use
    → evidence-backed S/F representative candidate
    → qualified independent registry/adapter review
    → current verified adapter, stable meaning and audit chain
    → granted contributor creates complete candidate composition
    → independent composition review bound to revision/dependencies
    → verified identity for eligible internal association
    → separate complete sourced presentation candidate and review
    → verified presentation for eligible internal association
    → later clinical/content/publication design and approval
```

Read-only automated tooling may flag duplicates, missing fields, collation/normalization collisions, source conflicts, broken provenance and unsupported type/status values for human review. Such flags are not approvals and must not mutate records. Exact deterministic hash reuse is allowed only for already validated structure; automation never invents source facts or turns names/selections into trusted representatives.

## 16. Required prerequisites before shared writes

These are **activation gates**, not changes made by this audit:

| Gate | Missing/current status | Required reviewable result |
| --- | --- | --- |
| Persisted scoped authority | Production deny-all only | Approved Medicine grant/qualification/bootstrap/revocation contract, capabilities mapped to current actions, tested current transactional checks |
| Auditable writes and review | Attribution tuples only | Additive approved immutable history/evidence/revision design and atomic implementation; no write enabled without its event |
| Registry inventory | Live data unavailable | Authorized read-only aggregate audit, actual vocabularies/import provenance, candidate assessment and duplicate/S/F overlap report |
| Trusted adapter establishment | No production verification command | Qualified independent evidence-bound adapter verification and representative decisions; candidate refs remain unusable by composition creation |
| Stable registry meaning | No governed semantic revision/invalidations | Coordinated writer policy, snapshot/fingerprint/revision tracking, immediate current-eligibility checks and review invalidation |
| Aggregate governance | Phase 3.5 exact persistence works in synthetic validation | Preserve deterministic keys/exact arithmetic/immutable tuples; approved review and lifecycle commands before any verified-data acceptance |
| Isolated validation/deployment | Previous synthetic tests only | New grant/revoke races, independence, stale review, invalidation and audit-rollback tests on disposable MariaDB; separate developer deployment authorization |

Enabling a grant alone is insufficient. No approved live adapter set, audit ledger or revision-bound authority chain currently justifies activation. Candidate adapter staging could be a separately approved smaller pilot once authority/audit gates exist, while identity/presentation creation stays denied until trusted dependencies exist. UI, production population and public activation remain separate approvals.

## 17. Open design questions

| Question | Recommended default / decision still needed |
| --- | --- |
| Who edits canonical Medicine? | Explicit Medicine contributors; nominate personnel, grant administrator and bootstrap authority |
| Reviewer qualification? | Independently validated chemical/pharmaceutical identity expertise by scope; approve credentials, verification, expiry and renewal rules |
| Sufficient verification authority? | Qualified accountable reviewer plus accepted fact-specific evidence; choose responsible body and appeal/conflict process |
| Reuse old registry verification? | Evidence inputs only until independently audited; define acceptable historical proof and review scope |
| Chemical-form registry? | Existing `kobi_substance_forms` is structural scaffolding; inspect live semantics before reuse or any controlled-registry extension |
| Salt/base representation? | Evidence-backed distinct entities/relations, never equivalence by name; approve representative and supported relationship policy |
| Combination verification? | Complete sourced active set plus independently verified members; define acceptable composition proof |
| Acceptable source types? | Approve issuer/record/version hierarchy for naming, chemical identity and presentation separately; choose freshness/conflict rules |
| Verified substance becomes inactive? | Immediate new-use denial, retained history, reassessment; decide invalidation storage and restoration review |
| Canonical medicine obsolete? | Terminal retirement and separately reviewed replacement; decide explicit supersession/history persistence |
| Publication lifecycle? | Entirely separate later governance/content design; no activation now |
| Verification versus publication? | Independent; decide whether internal acceptance is derived from review or a separate recorded decision |
| Legacy private reconciliation? | Explicit user or authorized reviewer confirmation, original text retained; approve access/provenance/consent and no batch inference |
| Safe automation? | Read-only flags and exact validated structural reuse; choose audited tooling scope, no automated semantic merge |
| Mandatory audit history? | Immutable grants, contributor changes, snapshots, review/evidence decisions and invalidations; approve minimal additive persistence contract |
| Minimum shared-write authority? | Current persisted grants + audit + trusted adapter gate + registry change control; no profile/admin/Treatment inference |
| Modification after verification? | Immutable key tuples; reviewed metadata policy with expected revisions; define review invalidation and qualified independence across all contributors |

These policy choices are not silently resolved by proposed role names or empty schema fields. They do not prevent further design work; they must be settled before the corresponding feature is implemented/activated.

## 18. Recommended Phase 3.7 scope

The smallest safe next step is **Medicine authority/reconciliation persistence contract design plus an authorized read-only registry census**, before implementing authorization. Approve named bootstrap/grant authority, reviewer/source policy and representative/invalidation rules; specify the minimum additive persisted grant and immutable audit/review/evidence contract compatible with the unchanged seven tables and MariaDB 10.4.27. Inventory actual registry values/quality and uncontrolled writers using only safe aggregated reads; do not copy private medication data into a report.

Deliver explicit future schema/service contracts and a disposable test plan for deny-by-default grants, revocation races, self-review, stale snapshots, source/registry invalidation and audit rollback. Only after that contract is approved should a separately authorized implementation enable limited candidate commands. Do not bundle registry correction, verification population, Medicine UI, legacy migration, public knowledge or Treatment linkage into that task. Existing Phase 3.1 endpoint defects remain separate work. Phase 3.7 was not implemented.

## 19. Validation performed

Reviewed authoritative schema, Phase 3.2/3.3 identity/schema designs, Phase 3.4 compatibility report and Phase 3.5 implementation/validation artifacts. Targeted source inspection/search confirmed each current table/field/class/method and the boundaries described above. Evidence anchors:

| Evidence | File reference |
| --- | --- |
| Actual registry, private and seven foundation definitions | [structure.sql](D:/xampp/htdocs/kobi/database/structure.sql) |
| Identity and reference meanings | [Phase 3.2](D:/xampp/htdocs/kobi/docs/MEDICINE_PHASE_3_2_IDENTITY_SPEC.md), [Phase 3.3](D:/xampp/htdocs/kobi/docs/MEDICINE_PHASE_3_3_SCHEMA_DESIGN.md) |
| MariaDB schema validation and service limitations | [Phase 3.4](D:/xampp/htdocs/kobi/docs/MEDICINE_PHASE_3_4_SCHEMA_VALIDATION.md), [Phase 3.5](D:/xampp/htdocs/kobi/docs/MEDICINE_PHASE_3_5_VALIDATION.md) |
| Catalog denial and aggregate/private checks | [MedicineAuthorizationService](D:/xampp/htdocs/kobi/app/Services/MedicineAuthorizationService.php), [MedicineIdentityService](D:/xampp/htdocs/kobi/app/Services/MedicineIdentityService.php), [MedicineRepository](D:/xampp/htdocs/kobi/app/Repositories/MedicineRepository.php), [MedicineCanonicalizer](D:/xampp/htdocs/kobi/app/Services/MedicineCanonicalizer.php) |
| Autocomplete and name-only legacy persistence | [SubstanceRepository](D:/xampp/htdocs/kobi/app/Repositories/SubstanceRepository.php), [SubstanceService](D:/xampp/htdocs/kobi/app/Services/SubstanceService.php), [AccountController](D:/xampp/htdocs/kobi/app/Controllers/AccountController.php), [health-medicine.js](D:/xampp/htdocs/kobi/public/assets/js/health-medicine.js) |
| Personal service/repository/views | [HealthMedicineService](D:/xampp/htdocs/kobi/app/Services/HealthMedicineService.php), [HealthMedicineRepository](D:/xampp/htdocs/kobi/app/Repositories/HealthMedicineRepository.php), [create view](D:/xampp/htdocs/kobi/app/Views/account/health-medicine-create.php), [edit view](D:/xampp/htdocs/kobi/app/Views/account/health-medicine-edit.php) |
| Existing scoped Treatment authority, not Medicine authority | [TreatmentEditorialAuthorizationService](D:/xampp/htdocs/kobi/app/Services/TreatmentEditorialAuthorizationService.php), [TreatmentEditorialPermissionRepository](D:/xampp/htdocs/kobi/app/Repositories/TreatmentEditorialPermissionRepository.php) |

Validation outcomes: schema/code terminology checked; selected-name discard and new sidecar ID path distinguished; no existing verification/publication command or persisted Medicine grant found; no mysqld process/default-port listener available; no row data in schema dump; no DB connection, query, write, startup or SQL-mode change. Documentation links/20 sections checked; `git diff --check` and status reviewed; 279-file pre-audit SHA-256 baseline compared after delivery with no changes/removals. Only this document was added.

No PHP was touched, so no syntax run was necessary. No unit/disposable mutation/browser suite was run for this documentation-only audit. The prior 105 database-free / 29 MariaDB Phase 3.5 results are historical reported validation, not rerun results for Phase 3.6. No secrets or actual private medication records were printed. No commit, push, reset, checkout, merge, rebase or history mutation occurred. Treatment HOLD, application configuration, all existing services/endpoints and SQL schema remain unchanged.

## 20. Explicit limitations

Live registry and medication data were not inspected; counts, candidate eligibility, duplicates, source coverage and actual form/identity vocabularies cannot be certified. No actual reviewer credentials, source documents or importer behavior was validated. External/manual writers may exist outside the application search. Schema/code inspection cannot prove that deployed metadata/data matches the dump.

Persisted Medicine authority, immutable revision/evidence history, representative reconciliation, qualification/independence and dependency invalidation are genuine blockers to **shared-write activation**, not blockers to this completed audit. The document recommends policy defaults but leaves human qualification/source/bootstrap decisions open. Additional governance persistence will require explicit later design and schema authorization; nothing was added or activated here.

Public governance, registry corrections/imports, verification/retirement implementations, data population, private UI integration, migration and Phase 3.1 safety fixes remain deferred. Personal selection is never upgraded into trusted shared knowledge. **Final result: PASS WITH LIMITATIONS; READY WITH OPEN DESIGN QUESTIONS; canonical shared writes remain denied.**
