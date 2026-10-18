# KOBI — Medicine Phase 3.7: Final Authority & Read-Only Registry Census

Date: 2026-10-09. **Result: PASS WITH LIMITATIONS. Design readiness: READY WITH OPEN DESIGN QUESTIONS.** Authority rules below are the final *proposed* contract for approval, not an implemented authorization system or permission to deploy/populate data. The requested live census remains unavailable, as explicitly permitted by this task's fallback rule.

## 1. Executive Summary

KOBI has deterministic canonical identity/presentation persistence and a private selected-reference contract, but no persisted Medicine grants or auditable independent verification mechanism. Shared commands remain denied by `MedicineAuthorizationService::requireCatalog()`.

This proposal fixes the separation of contribution, editing, identity review and publication; retains candidate/verified/retired foundation states; defines internal acceptance as a current revision-bound review predicate; and specifies the minimum grant, evidence/history and registry-invalidation information required for a narrow Phase 3.8. User selections never confer shared authority. Identity evidence never establishes therapeutic effectiveness.

**Live registry census could not be performed.** No local `mysqld`/`mariadbd` process or listener on checked local ports 3306/3307/3325 was found. No database connection, SELECT, startup, schema/data write or SQL-mode change was attempted. No counts are guessed from dump AUTO_INCREMENT values, synthetic tests or prior reports. Only this document was created; all 280 baseline application/document/test files remained unchanged.

## 2. Phase 3.6 Carry-Forward Findings

Carry forward: deny-by-default catalog access; separate private/profile versus catalog authority; qualified independent review; immutable tuple identity; evidence/revision/dependency binding; no name-based reconciliation; preserved legacy text; immediate denial when dependencies are ineligible; no Medicine public exposure and unchanged Treatment HOLD.

Correction to previous narrative: actual `kobi_substances.identity_type` and `identity_status` are **NOT NULL VARCHAR**, default UNKNOWN/CANDIDATE. The Phase 3.6 field table described them as nullable; that description is inaccurate. `naming_standard` is nullable. Current schema governs this report; the earlier file was not modified. NOT NULL still does not enforce a reviewed taxonomy or meaningful verification.

Phase 3.4 documented non-strict DECIMAL coercion and service prevalidation requirements. Phase 3.5 reported 105 database-free and 29 disposable MariaDB cases; those are historical synthetic validation, not live dataset evidence or rerun Phase 3.7 results. Phase 3.1 handler error/date/validation issues remain separately scoped and untouched.

## 3. Current Authority Model

`AuthService::id()` identifies the authenticated actor. `MedicineIdentityService::actor()` rechecks active `users`; `privateActor()` uses persisted profile/member locks and `HealthProfileRepository::findAccessible`, requiring owner/editor for private writes. Actual `health_medicines` row/profile equality is checked. Profile roles grant no catalog authority.

Shared service commands are `registerChemicalReference`, `createMedicine`, `createPresentation`, mapped respectively to catalog actions `register_reference`, `create_identity`, `create_presentation`. `MedicineAuthorizationService` denies every action. No shared editor, verifier, retire or publisher command exists. Test doubles and fixture verification cannot authorize production.

`treatment_editorial_permissions`, `TreatmentEditorialPermissionRepository` and `TreatmentEditorialAuthorizationService` demonstrate persisted scoped grants, active-user checks, qualification text, revocation and current locking reads. Their scope is `treatment_system_id`, exclusively Treatment. Existing `permission_admin_user_ids` in Treatment configuration is not a Medicine/general admin role; `users` has no universal admin role column. Reuse this architectural pattern, not Treatment grants/tables/configuration or its clinical workflow.

`SubstanceRepository::search()` returns `substance_id,canonical_name` from active/searchable prefix matches, capped at ten. `SubstanceService::search()` trims input; `/api/substances/search` calls `AccountController::searchSubstances`. Search has no reviewed-identity gate. `health-medicine.js` selects only `item.canonical_name`, discarding IDs. Existing medication handlers call `HealthMedicineService`/`HealthMedicineRepository`; they are not connected to the new foundation service.

## 4. Proposed Final Authority Contract

Use separately persisted **Medicine-domain, action-specific** capabilities. Default scope is the explicitly granted internal identity catalog, not all KOBI domains or health profiles. Restricted qualification/resource scopes must be checked for every target; if the service cannot express/check a requested restriction, deny rather than broaden it. The present `requireCatalog(actor, action)` signature supports action checks, not target-specific scope by itself; a later narrow contract extension is required for target/qualification-aware commands.

Minimum persisted grant information:

| Item | Required rule |
| --- | --- |
| Stable grant ID/version | Bind every authorized command/review to the exact grant used |
| Grantee | Actual active `users.id`, never submitted owner/reviewer ID; FK type must match signed INT |
| Domain/scope | Explicit Medicine identity domain and subject/qualification limits; no Treatment inheritance |
| Capabilities | Separate contribute, edit, verify-identity and reserved publish privileges; registry reconciliation/retirement/grant administration require explicit additional actions |
| Qualification | Validated credential/evidence reference, competent identity/form/presentation scope, accountable assessor, verification date and expiry/reassessment rule; required for reviewers |
| Issuance | Authorized administrator, grant time, reason and immutable history; controlled bootstrap |
| Active period | Defined start/end; expired, unknown, revoked or inactive-user grant denies |
| Revocation | Revoker/time/reason; immutable event; no silent overwrite of historical grants |
| Revision/concurrency | Expected version and current locking checks; authorization and audit commit atomically |

No client-supplied qualification string is sufficient. A designated grant administrator must validate evidence under an approved credential policy. The initial administrator and qualified reviewer personnel/source policy are human approval prerequisites, not values inferred from existing accounts. No automatic self-grant, imported grant, service-account privilege or boolean admin bypass is allowed.

Contribution authorizes complete candidate creation only. Editing authorizes metadata/evidence edits on a candidate, never in-place mutation of composition or presentation keys. Identity-defining corrections create a new tuple with governed historical relationship; administrative labels remain metadata. Current code has no edit command, so future edits need expected `metadata_revision` and immutable history. Any change to a fact included in a review snapshot invalidates that approval; exact duplicate reuse does not overwrite the owner or establish a new review.

Publication is a separate future capability and separate gate. Phase 3.8 must not implement public publication merely because the capability is documented. Missing infrastructure or policy always keeps the relevant capability disabled.

## 5. Contributor/Editor/Reviewer/Publisher Roles

| Role | May do | Must not do |
| --- | --- | --- |
| Contributor | Register supported candidate adapters and create complete candidate compositions/presentations, within explicit grants and trusted dependency checks | Verify own work, create substances automatically, bypass evidence or publish |
| Editor | Amend candidate descriptive metadata/evidence under explicit edit grant, expected revision and audit | Change immutable keys/member semantics in place, erase provenance or retain stale approvals |
| Identity Reviewer | Independently verify exact substance/form representation, composition, measured strength and presentation within validated competence; reject/request changes/invalidate on evidence | Self-review, review outside qualification, approve unknown facts or equate identity with efficacy |
| Publisher | In a later public-governance phase, authorize reviewed content exposure after independent approval and current reader gate | Repair missing identity review through publication, expose private data or publish during current hold/absence of public governance |
| Grant administrator | Validate credentials; explicitly issue/revoke limited grants with accountability | Obtain content privileges implicitly or bypass independence |
| Ordinary/profile user | Existing authorized personal logging and later explicit private selection/linking | Create shared candidates without a separate grant or elevate selection into canonical approval |
| System actor | Only separately approved bounded candidate/staging tasks under a named active actor/grant | Autonomous semantic merging, verification or publication |

Contributor and editor may be the same person with both grants. A reviewer must differ from **every substantive contributor/editor** to the reviewed facts, not merely `created_by`; a historical contributor set must therefore be recorded. Credentials and independence are checked at decision time. For initial future public exposure, recommend publisher distinct from contributors and the identity/content approving reviewer for that item; relaxing that separation requires separate explicit policy approval. No publisher workflow exists now.

## 6. Verification Lifecycle

Keep foundation `identity_status` values candidate/verified/retired. Do not add approved/published/withdrawn/superseded/ambiguous to those existing CHECKs. Review/invalidation/supersession events carry independent governance facts; **effective eligibility** is derived from current valid approval and dependencies. Verification/retirement transitions require future services; direct SQL shape constraints alone do not authorize transitions.

| State/concept | Meaning and entry authority | Exit authority/rule | Canonical new use / public / re-review |
| --- | --- | --- | --- |
| Candidate | Contributor creates complete unreviewed adapter/identity/presentation | Independent qualified reviewer verifies; authorized lifecycle actor may retire; contributor/editor may revise metadata under expected revision | Adapter cannot enter composition; candidate composition may support candidate presentation construction as Phase 3.5 permits, but cannot be privately selected as verified; never public; review required |
| Verified | Independent qualified reviewer accepts exact current revision/evidence/dependencies | Reviewed metadata/evidence change or dependency loss invalidates effective acceptance; final retirement by explicit lifecycle authority | Verified adapter usable in composition only while eligible; verified composition/presentation usable internally under scoped checks; no public right; changed facts need re-review |
| Approved internally | Derived predicate: current verified decision, eligible dependencies, supported scope, no adverse event | Automatically false on stale snapshot, evidence withdrawal, invalidation or retirement; qualified fresh review can restore eligibility if not retired | May be used internally; never sufficient for public; not a new identity_status |
| Published | Future separately approved public content/projection state | Separate publisher withdrawal plus immediate deny by public current-eligibility gate on dependency failure | Public only after future clinical/content/locale/source/risk/publication gates; any material change needs applicable re-review; unavailable now |
| Withdrawn/ineligible | Explicit evidenced adverse decision or lost eligibility; lifecycle/reviewer authority records reason; automatic current gate denies immediately | Qualified reassessment may clear temporary invalidation with new review, preserving old events; no restoration of retired tuple | No new use or public output; historical IDs/text retained; re-review mandatory |
| Superseded | Independent reviewed replacement relationship preserving original target/key | Original mapping/history never rewritten; replacement separately verified; final old tuple may retire | Old target blocked from new use under supersession policy; no automatic patient relink/public substitution; replacement requires review |
| Ambiguous | Conflicting/missing identity facts; assessed by reviewer, or detected as an unresolved intake flag | Qualified evidence-backed resolution and fresh review | Deny new canonical acceptance/public; do not persist a guessed status or entity |
| Retired | Terminal authoritative retirement of existing tuple; preserves unique reservation/history | No return; a new correct tuple/replacement is independently governed | No new use/public; historical record retained; no restoration |

An eligible substance/adapter verifies what an ingredient is. A composition review verifies the complete active set. A presentation review additionally verifies the entire strength vector/form/route/release. None proves therapeutic effectiveness, dosing safety, interchangeability, indication or clinical publication approval.

## 7. Substance Eligibility Contract

The positive eligibility predicate for a typed ingredient is:

```text
existing stable substance/form target
AND active target (and active form parent)
AND independently established supported chemical identity
AND one unambiguous approved S/F representative for that exact entity
AND current verified chemical adapter and qualified independent decision
AND evidence/dependency snapshot matches current registry semantics
AND no withdrawal, ambiguity, supersession block, retirement or stale review
```

All conditions are required; searchability is neither required scientific evidence nor sufficient eligibility. A legitimately non-searchable reviewed target can be eligible for explicitly authorized internal use; discovery policy remains separate.

| Existing field | Current fact | Proposed eligibility use |
| --- | --- | --- |
| `canonical_name` | Required display label | Must be valid/nonblank and correctly interpreted by reviewed evidence; never a key/equivalence proof |
| `canonical_name_normalized` | Required UNIQUE collation-based search label | Audit normalization provenance/collisions; never scientific equality proof |
| `naming_standard` | Nullable arbitrary VARCHAR, default WHO_INN | Source claim to assess; no implicit WHO verification |
| `identity_type` | NOT NULL arbitrary VARCHAR, default UNKNOWN | Establish actual entity scope from evidence; unknown/unsupported scope blocks trust |
| `identity_status` | NOT NULL arbitrary VARCHAR, default CANDIDATE | Legacy claim only; even VERIFIED text requires independent validated evidence/history |
| `is_active` | Operational flag; foundation checks exactly active | Inactive/invalid flag blocks new use; active does not verify |
| `is_searchable` | Autocomplete flag | Discovery only; inactive-but-searchable is an audit inconsistency, not eligibility |
| `verification_source` | Nullable text | Input provenance requiring an immutable fact-specific source binding; nonempty text alone insufficient |
| `reviewed_by`, `reviewed_at` | Nullable attribution, no reviewer FK/qualification/revision binding | Assess historical review; no automatic reuse as current approval |

Candidate adapter registration can precede adapter verification under explicit authority/history. Current `MedicineIdentityService::resolve()` requires verified adapters even for candidate composition creation; this remains unchanged. Existing `lockRegistry()` checks activity only, so future semantic-fingerprint and decision validation must extend that boundary before accepting trusted data. No automatic creation of `kobi_substances` or forms is allowed.

## 8. Registry Census Method

Availability checks performed: process inspection for mysqld/mariadbd and listener inspection for local ports 3306/3307/3325. All returned no matching running process/listener. This is local availability evidence, not proof about a remote database or every custom port. No server was started and no credentials/configuration changed or printed. The authorized normal dataset was not connected to; no disposable database was needed.

The following are **planned SELECT-only queries/logic, not executed results**. On a future available authorized connection, first verify selected database identity, version, SQL mode and actual relevant metadata using read-only metadata SELECTs. Do not assume the seven foundation tables are deployed merely because the dump defines them. Run only fixed aggregate reads with no locking, INTO/file output, UDFs, application bootstrap, temporary DB structures or mutating statements. Record execution time/version/schema and preserve no private medication content. A sequence of separate SELECTs is not an atomic census snapshot; report concurrent changes and reconcile repeated totals rather than claiming consistency without evidence.

Q1 — registry totals and flags/provenance:

```sql
SELECT COUNT(*) AS total,
 COUNT(CASE WHEN is_active=1 THEN 1 END) AS active,
 COUNT(CASE WHEN is_active=0 THEN 1 END) AS inactive,
 COUNT(CASE WHEN is_active NOT IN (0,1) THEN 1 END) AS invalid_active_flag,
 COUNT(CASE WHEN is_searchable=1 THEN 1 END) AS searchable,
 COUNT(CASE WHEN is_searchable=0 THEN 1 END) AS non_searchable,
 COUNT(CASE WHEN is_searchable NOT IN (0,1) THEN 1 END) AS invalid_searchable_flag,
 COUNT(CASE WHEN is_active=0 AND is_searchable=1 THEN 1 END) AS inactive_searchable,
 COUNT(CASE WHEN NULLIF(TRIM(verification_source),'') IS NOT NULL THEN 1 END) AS with_source,
 COUNT(CASE WHEN NULLIF(TRIM(verification_source),'') IS NULL THEN 1 END) AS without_source,
 COUNT(CASE WHEN reviewed_by IS NOT NULL THEN 1 END) AS with_reviewer,
 COUNT(CASE WHEN reviewed_by IS NULL THEN 1 END) AS without_reviewer,
 COUNT(CASE WHEN reviewed_by IS NOT NULL AND reviewed_at IS NOT NULL THEN 1 END) AS review_tuple_present,
 COUNT(CASE WHEN reviewed_by IS NULL AND reviewed_at IS NULL THEN 1 END) AS no_review_tuple,
 COUNT(CASE WHEN (reviewed_by IS NULL) <> (reviewed_at IS NULL) THEN 1 END) AS incomplete_review_tuple,
 COUNT(CASE WHEN TRIM(canonical_name)='' THEN 1 END) AS blank_name,
 COUNT(CASE WHEN TRIM(canonical_name_normalized)='' THEN 1 END) AS blank_normalized,
 COUNT(CASE WHEN canonical_name REGEXP '[[:cntrl:]]' THEN 1 END) AS control_character_name
FROM kobi_substances;
```

Q2/Q3/Q4 — distributions: independently run the following template for the three fixed fields `identity_status`, `identity_type`, `naming_standard`. Binary grouping preserves exact spelling/case/spacing rather than silently collapsing under collation; NULL in naming_standard stays a separate group. No field name is accepted from user input.

```sql
SELECT BINARY identity_status AS observed_value, COUNT(*) AS rows_in_group
FROM kobi_substances GROUP BY BINARY identity_status;
```

Q5 — duplicate byte-identical canonical-name groups (name signal, not proven chemical duplication):

```sql
SELECT COUNT(*) AS duplicate_name_groups, COALESCE(SUM(n),0) AS rows_in_groups
FROM (SELECT COUNT(*) AS n FROM kobi_substances
      GROUP BY BINARY canonical_name HAVING COUNT(*)>1) AS groups_found;
```

Q6 — stored normalized-name collisions under the column's actual collation:

```sql
SELECT COUNT(*) AS normalized_collision_groups, COALESCE(SUM(n),0) AS rows_in_groups
FROM (SELECT COUNT(*) AS n FROM kobi_substances
      GROUP BY canonical_name_normalized HAVING COUNT(*)>1) AS groups_found;
```

A valid deployed `uq_substance_canonical_normalized` should prohibit such groups; a nonzero result requires metadata/collation investigation, never automatic repair. A zero result would not prove semantic deduplication.

Q7 — low-cost formatting/normalization suspicion:

```sql
SELECT COUNT(*) AS heuristic_groups, COALESCE(SUM(n),0) AS rows_in_groups
FROM (SELECT COUNT(*) AS n FROM kobi_substances
      GROUP BY BINARY LOWER(TRIM(canonical_name)) HAVING COUNT(*)>1) AS groups_found;
```

This heuristic handles limited trim/case variation only. Later bounded read-only external analysis can apply versioned Unicode NFC/whitespace/case-folding to registry labels to identify additional review flags, preserving punctuation/salt/stereochemistry markers and never writing normalization back. SQL collation/LOWER/TRIM is not the approved canonicalization or chemical identity algorithm; Unicode-only blank/control cases need explicit additional analysis. Report query groups versus affected rows separately.

Q8 — questionable attribution:

```sql
SELECT COUNT(*) AS referenced_reviewer_missing_or_inactive
FROM kobi_substances s LEFT JOIN users u ON u.id=s.reviewed_by
WHERE s.reviewed_by IS NOT NULL AND (u.id IS NULL OR u.status<>'active');
```

No user names/emails/credentials are returned. An inactive historical reviewer does not itself prove a historical decision invalid; current qualification/review validity requires a separate governance assessment.

Unusual type/status/standard values and conflicting records are reviewed from exact distributions plus bounded registry-only evidence inspection. There is no established live taxonomy against which to label every unfamiliar value invalid. Salt/base names, multi-ingredient punctuation and source claims are flags, never an automatic duplicate/entity classification. Form/components/aliases/reviews/WHO evidence require separate verified metadata and aggregate/bounded reads before semantic reconciliation. No query in this plan was executed in Phase 3.7.

## 9. Registry Census Results

**Live registry census could not be performed.** N/M below means **not measured**, not zero, no records, or no duplicates. There are no SELECT result sets against which numeric counts can be reconciled.

| Requested measure | Table/field and query/logic | Actual result | Interpretation |
| --- | --- | --- | --- |
| Total substances | kobi_substances; Q1 COUNT(*) | N/M | Dataset size unknown; AUTO_INCREMENT=2225 is not a count |
| Active/inactive/invalid flags | is_active; Q1 | N/M | Activity distribution unknown |
| Searchable/non-searchable/invalid flags | is_searchable; Q1 | N/M | Discovery distribution unknown |
| Inactive yet searchable | is_active=0 AND is_searchable=1; Q1 | N/M | Inconsistency prevalence unknown |
| Status distribution | identity_status; Q2 | N/M | Actual vocabulary/frequency unknown |
| Type distribution | identity_type; Q3 | N/M | Entity classes unknown |
| Naming standard distribution | naming_standard; Q4 | N/M | Claims and missing standards unknown |
| Reviewed/unreviewed/incomplete tuple | reviewed_by/reviewed_at; Q1 | N/M | Metadata completeness only, not valid approval |
| With/without verification source | verification_source; Q1 | N/M | Provenance-text coverage unknown |
| With/without reviewer | reviewed_by; Q1 | N/M | Attribution coverage unknown |
| Missing/inactive referenced reviewer | users.id/status join; Q8 | N/M | Attribution flags unknown |
| Exact canonical-name duplication | canonical_name binary groups; Q5 | N/M | Does not establish chemical duplicate identity |
| Normalized-name collisions | canonical_name_normalized collation groups; Q6 | N/M | Actual uniqueness/collation metadata unverified |
| Formatting/case suspicion | LOWER/TRIM(name); Q7 and later Unicode external analysis | N/M | Heuristic review flags only |
| Blank/invalid names | Q1 blank/control flags; later bounded Unicode review | N/M | Name quality unknown |
| Unusual statuses/types; conflict | Q2/Q3 plus qualified provenance assessment | N/M | No invented allowed taxonomy or row classification |

Conceptual candidate estimates also remain unmeasured:

| Proposed audit category | Assignment logic after evidence inspection | Count |
| --- | --- | --- |
| Strong candidate for canonical ingredient | Unambiguous supported exact entity and substantial attributable identity evidence; still requires current independent adapter review | N/M |
| Candidate requiring verification | Assessable active entity with sufficient intake information, not yet independently accepted | N/M |
| Ambiguous | Conflicting identity/form/scope or unresolved representative | N/M |
| Duplicate/suspected duplicate | Qualified equivalence finding or heuristic flag explicitly distinguished | N/M |
| Inactive | Observed unusable activity; Q1 supplies only flag count | N/M |
| Withdrawn/deprecated | Evidenced governed adverse decision; no enforced legacy status mapping currently | N/M |
| Insufficient information | Missing entity/source scope sufficient to block a decision | N/M |
| Unsuitable | Evidence establishes unsupported ingredient concept or complex composition needing another model | N/M |

These classifications are proposed governance assessments, not database facts. Future census should report independent flags and a single primary category per row with a declared precedence (inactive/adverse, unsuitable, ambiguous, suspected duplicate, insufficient, verification-needed, strong candidate), so overlaps are not falsely summed as disjoint totals. Proven semantic duplicates require evidence, not just Q5/Q7. No classification was computed or persisted here.

## 10. Duplicate and Ambiguity Analysis

| Class | Required evidence / treatment |
| --- | --- |
| Exact chemical duplicate | Same precise entity represented twice, established by authoritative evidence; retain IDs/history and independently decide representative; no automatic merge |
| Formatting/normalization duplicate | Name/case/whitespace equivalence is a suspicion, not identity proof; inspect normalization and actual evidence |
| Salt/base relationship | Related identities, not equivalent ingredient keys; evidence must establish precise entities and relationship |
| Chemical-form difference | Preserve distinct chemical identities despite similar names; no guessed conversions/interchangeability |
| Combination/multi-ingredient | Verify full active set independently; do not turn a comma label into one substance or a list of selections into approved composition |
| Historical/legacy | Preserve historical names/IDs and observations; any successor relationship is explicit and reviewed |
| Unresolved ambiguity | Deny canonical acceptance until evidence resolves it; keep original data |

Current composition deduplication is exact typed-reference structure: S/F numeric order, full JSON key/hash/current aggregate, UNIQUE hash reservation. It cannot detect two different registry IDs denoting the same chemical entity. Exactly matching reviewed canonical tuples can reuse their owner; that is not semantic registry merging. Retirement keeps key reservations. Cross-S/F representation conflicts must be reconciled before verifying both representations. No actual duplicate findings/counts are available.

## 11. Chemical-Form Reconciliation

`kobi_substance_forms` holds form_id, parent substance_id, names, form_type/status, verification_source and active flag. `kobi_form_components` adds component relationships/stoichiometry/evidence. `kobi_substance_aliases`, `kobi_identity_reviews`, `kobi_who_inn_evidence`, combinations/items provide additional evidence scaffolding. No application-governed taxonomy/reconciliation writer or enforced CAS/InChI/SMILES/molecular-identity contract was found. Actual populated meanings are unknown.

Phase 3.8 must establish whether each target is a supported exact base, salt, hydrate, solvate, stereoisomer or other chemical form; verify parent relationships and distinguish active entity from counterion/excipient. Names, nullable metadata, stoichiometry or default WHO_INN never establish equality or potency conversion. If the row already denotes the exact entity, use S(id); if an independently verified existing form is the representative, use F(form_id) and derive its parent. Do not create artificial forms or infer unknown form as base. Registry review must choose one representative per exact entity without altering historical references.

The existing seven tables cannot represent reviewed semantic supersession/overlap/evidence history alone. Additional governance persistence is demonstrably needed, but expanding/replacing the chemical registry or adding molecular identifiers is not justified until live semantic audit and scoped requirements establish need. No chemical-form data was populated or corrected.

## 12. Personal Medication Census

No personal medication rows were accessed. Planned aggregate-only P1:

```sql
SELECT COUNT(*) AS total,
 COUNT(CASE WHEN NULLIF(TRIM(brand_name),'') IS NOT NULL THEN 1 END) AS with_brand_text,
 COUNT(CASE WHEN NULLIF(TRIM(generic_name),'') IS NOT NULL THEN 1 END) AS with_generic_text,
 COUNT(CASE WHEN NULLIF(TRIM(strength),'') IS NOT NULL THEN 1 END) AS with_strength_text,
 COUNT(CASE WHEN NULLIF(TRIM(dose),'') IS NOT NULL THEN 1 END) AS with_dose_text,
 COUNT(CASE WHEN NULLIF(TRIM(route),'') IS NOT NULL THEN 1 END) AS with_route_text
FROM health_medicines;
```

If actual metadata confirms both sidecar tables exist, planned P2:

```sql
SELECT COUNT(*) AS total,
 COUNT(CASE WHEN l.health_medicine_id IS NULL THEN 1 END) AS no_sidecar,
 COUNT(CASE WHEN l.association_mode='composition' THEN 1 END) AS composition_links,
 COUNT(CASE WHEN l.association_mode='presentation' THEN 1 END) AS presentation_links,
 COUNT(CASE WHEN l.association_mode='ingredients' THEN 1 END) AS ingredients_links,
 COUNT(CASE WHEN EXISTS (SELECT 1 FROM health_medicine_selected_ingredients s
                         WHERE s.health_medicine_id=m.id) THEN 1 END) AS records_with_selected_refs
FROM health_medicines m LEFT JOIN health_medicine_identity_links l ON l.health_medicine_id=m.id;
```

| Requested measure | Actual result | Interpretation |
| --- | --- | --- |
| Total personal records | N/M; P1 | No count inferred from AUTO_INCREMENT=6 |
| With/without substance identifiers | N/M; P2 + selected-ref → chemical-ref relationship | No substance FK in original health_medicines; sidecar deployment/data unknown |
| Free-text medicine names | N/M; P1 | Required brand_name and optional generic_name are text, not verified identity |
| Enough structured information for possible linking | N/M; reviewed existing ID/provenance/eligibility, not text completeness | Even a sidecar ID needs scope/current verification; strength/route text alone cannot satisfy presentation proof |
| Unresolved records | N/M; explicit review classification | No guessed subtraction or name-based identity inference |

P2 counts each medication once, avoiding selected-child join inflation. IDs in ingredients mode are user observations, not approved combination membership. No lack-of-ID count can be claimed until actual sidecar existence and data are measured. Do not export names, dates, notes, dosages, user/profile IDs or patient-level examples into the public audit document. Existing `health_medicines` retains free-text strength, dose, frequency, route, dates/prescriber/notes; no dedicated instructions field exists. No data was linked, rewritten or classified.

## 13. Legacy Linking Policy

Safe automatic linking is permitted only when a persisted authoritative relationship unambiguously identifies the exact target level, its provenance/approval is validated and current eligibility is checked. A name match, brand, comma list, strength text, old WHO_INN default or selected substance ID by itself is insufficient to identify a composition/presentation. No demonstrably safe legacy automatic batch was found.

Reviewer-assisted linking requires qualified identity assessment **and separate authorized private profile access**; catalog authority grants no patient access. User-confirmed private association is explicit owner/editor selection of a current verified target under the future integrated flow; it never verifies a catalog identity. Unresolved records remain intact.

Use composition links only for exact known active membership; presentations require complete exact ingredient strength/form/route/release proof; ingredients mode remains observation. Preserve every original text field and private scope, CSRF, `AuthService::id()` and `findAccessible`. Future integrated raw/sidecar saves must be atomic and compare locked digest/link_revision, preserving Phase 3.5 ABA protection. Do not silently repoint private FKs during registry reconciliation. Linking provenance/access/consent and the Phase 3.1 endpoint prerequisites must be resolved separately before UI or batch integration.

## 14. Inactive/Withdrawn Policy

| Affected operation | Final proposed rule on inactive/withdrawn/deprecated/superseded/ambiguous/incorrect verification |
| --- | --- |
| Historical canonical records | Retain original stable IDs, keys, attribution and events; eligibility may be false without erasing past review |
| New canonical creation/use | Immediately deny affected ingredient/dependency; a combination fails if any member fails |
| Existing private records | Preserve raw text and historical links; no automatic substitution/deletion or clinical advice |
| Future edits/review | Metadata edits remain scoped; no stale review acceptance or in-place tuple change; require independent fresh review before eligible reuse |
| Future public output | Current reader denies immediately; background unpublish/invalidation events preserve history and cannot be the sole enforcement gate |

Record exact reason/source/actor/revision of adverse decisions. Registry semantic change under the same still-active ID must invalidate acceptance through snapshot/fingerprint comparison. Form changes also affect derived parent/identity meaning; presentations inherit dependency loss. Administrative reactivation never restores review automatically. Supersession requires separately verified replacement and explicit relationship; terminal retirement is not reversible. Temporary ambiguity/invalidation should not mass-retire all dependent tuples; preserve history and use effective eligibility plus reassessment. Nothing was implemented or cascaded.

## 15. Source and Evidence Requirements

| Fact | Minimum independent evidence |
| --- | --- |
| Ingredient identity | Exact supported chemical entity, stable authoritative record and semantic scope; name evidence alone may be insufficient |
| Chemical form | Exact form and parent/relationship with salt/hydrate/stereochemical distinctions; no name-only inference |
| Composition | Full active set as intended together; verify individual representatives and composition evidence separately |
| Dosage form | Explicit supported pharmaceutical form, distinct from chemical form |
| Strength | Exact ingredient/measured basis, positive quantity/unit/denominator and complete vector; preserve source wording |
| Route | Explicit administration route; no inference from instructions/form name |
| Presentation | Consistent full composition/strength/form/route/release tuple with each fact supported |

Required source binding: issuer, record identifier/version/date, exact page/locator, accessed time, asserted facts, immutable digest/snapshot where retention is permitted, reviewer decision, conflicts and current withdrawal/supersession validity. A nonempty `verification_reference` is an attribution slot, not a complete approved evidence store. Existing WHO evidence may prove a reviewed naming fact, not therapeutic efficacy or every form/strength fact.

Recommended source-policy classes: accountable official identity/naming registries, pharmacopoeial identity records, regulator-approved presentation documents and attributable manufacturer specifications when appropriate to the asserted fact. This is a proposal requiring a named competent authority's approved allowlist/hierarchy, conflict and freshness rules; no source document was independently validated in this audit. Therapeutic evidence belongs to a separate future clinical appraisal/publication model. Never derive treatment effectiveness, dose, safety, indication or interchangeability from identity verification.

## 16. Audit/History Contract

Before any shared write, persist an immutable event atomically with it. Minimum record:

| Information | Persistence/visibility contract |
| --- | --- |
| Actor/action/time | Actual authenticated actor, exact capability/grant version, UTC microsecond time, stable event ID; internal |
| Target/revision | Typed subject and stable ID, expected/prior/result revision, previous/new lifecycle and eligibility; immutable/revision-bound |
| Canonical facts | Full key/hash, member/vector snapshot and affected dependency versions/semantic fingerprints; immutable review snapshot |
| Evidence | Fact-specific source/version/locator/digest, evidence acceptance/conflict/withdrawal references; immutable references with append-only subsequent decisions |
| Review | Decision, rationale, qualified reviewer/grant scope, submitted revision, contributor set and independence result; append-only |
| Invalidation/withdrawal | Reason, authoritative source/actor, affected target/dependencies, old/new acceptance, relation to prior decision; append-only |
| Reconciliation/supersession | Original and proposed representative identities, evidence/qualified decision, preserved historical references; no silent rewrite |
| Grants/qualification | Issuance/revocation/expiry/scope decisions and validating authority/credential evidence; internal and access-restricted |

No update/delete of past events or silent overwrite of the only review proof. Corrections append a new event. `metadata_revision`, actor/time and free-text verification tuples remain useful current projection/attribution, but do not replace history. `kobi_identity_reviews` lacks immutable revisions, reviewer FK/qualification/independence and protected target history; do not assume it supplies this contract.

No audit material is publicly exposed now. Future public projection may expose approved identity labels, fact evidence citations and appropriate review date/status after independent design; credential details, grant identifiers, internal rationale, contributor personal information and all private medication data stay internal. Do not place patient content in catalog evidence/events. Audit persistence failure rolls back the corresponding command; denial/infrastructure failures use safe messages and sanitized operational diagnostics without fabricating a success event.

## 17. Shared-Write Activation Gate

Two gates avoid confusing candidate creation with trusted approval:

**Candidate-write gate:** approved deployment scope; current active actor plus exact contribute/edit grant; supported complete immutable structure and expected revision; eligible verified adapters for composition/presentation dependencies under the existing contract; no unresolved S/F representation or adverse registry/evidence event; current semantic snapshot checks; atomic immutable audit; bounded rollback/retry validation. Candidate output remains internal/unapproved. Candidate adapter staging may precede adapter verification; it cannot bypass the verified-adapter condition for compositions.

**Trusted-acceptance gate:** candidate gate as applicable, plus exact current submitted revision/full key and dependency/evidence snapshots; valid qualified independent reviewer; evidence proving each reviewed fact; no self-review by any contributor; revision-bound accepted decision and immutable history; verified state/current internal acceptance; no stale/withdrawn/ambiguous/superseded/retired dependency. Composition and presentation decisions have their respective complete fact scopes. Grant activation alone cannot satisfy this gate.

**Public gate:** always false in current KOBI Medicine; later requires separately approved clinical/content/locale/evidence/risk/reader/publication controls and independent publisher. Trusted identity acceptance never activates it.

Current gate result: **FAIL CLOSED / shared writes remain disabled**. Persisted Medicine grants, reviewed live representative set, qualified/evidenced decisions, immutable audit/history and registry semantic invalidation are missing. The live census and human bootstrap/qualification/source choices remain unresolved. No approved/deployed authority chain justifies enabling production canonical creation/modification.

## 18. Security and Fail-Closed Rules

Retain Controller → Service → BaseRepository → shared Database/DI, parameterized values, explicit field allowlists, CSRF/server rendering/local redirects and external JS/CSP. No generic permission framework, ORM or broad endpoint rewrite is required.

Authorize using current persisted actor/grant/qualification, not cached profile/admin/Treatment flags. Lock/recheck authority and dependencies in the same transaction before mutation, preserve deterministic authority/profile → adapter/registry → identity/presentation → private row/link order, and design consistent sorted locks for new governance actors/grants. Revocation races must order safely: command validly before revocation or denied after; a stale grant cannot authorize later commit.

Reject unknown actions/scopes/states, missing/expired qualifications, unsupported chemical/strength basis, evidence conflicts, stale snapshots, incomplete aggregates or independence failures. Reviewer revocation prevents future decisions; retain historical decision proof and record whether the adverse reason invalidates earlier reviews, rather than blindly erasing all approvals. Recheck material adverse credential/evidence findings through governed invalidation.

No catalog grant gives profile access. No arbitrary label auto-merge, legacy relink, SQL-mode change or private-data exposure. New infrastructure failures remain safe 503/500 outcomes as appropriate, never raw diagnostics. Searchability/active flags do not assert verified identity. Publication and Treatment HOLD remain separate and unchanged.

## 19. Phase 3.8 Implementation Contract

Recommend a narrowly staged **persisted Medicine authority, identity-review history and reconciliation/invalidation foundation**, after explicit policy/schema approval. It must preserve the seven identity/private tables and existing UI/endpoints. Missing live facts mean no data corrections/population are authorized by this report.

Minimum demonstrably required additional persisted concepts (logical contracts, not existing tables or applied DDL):

1. Medicine-scoped grant/qualification issuance and revocation history: existing Treatment grants cannot authorize this domain; profile roles are insufficient. Current active grant queries need indexed scope/actor and exact capability/version checks.
2. Immutable governance event/review snapshots including evidence/dependency binding and contributor independence: current attribution tuples cannot retain historical proof or prevent self-review across edits. Evidence snapshots can be stored/referenced through the same bounded governance contract; avoid a full clinical source catalog unless requirements prove need.
3. Governed representative and current invalidation/review-validity decisions over existing registry targets: needed for S/F overlap, semantic drift, withdrawn evidence and supersession; stable target semantics cannot be inferred from is_active. Current validity may be derived from indexed latest events plus matching snapshots, provided the approved design guarantees immediate fail-closed reads; do not add redundant status ownership just for convenience.

Exact table names/DDL, FK targets/type compatibility, CHECK/index/uniqueness design and bounded snapshot format require review before implementation. Do not add approved/published values to existing identity_status, alter `kobi_substances`, replace the chemical registry, reuse Treatment governance tables or invent a broad RBAC model. Preserve signed INT user references, BIGINT chemical/Medicine IDs, current immutable keys, exact ratios and MariaDB 10.4.27/non-strict service validation. If a proposed missing concept cannot fit additive governance without altering approved identity semantics, stop for review.

Application work: extend the existing Medicine authorization boundary with a BaseRepository-backed persisted policy; preserve concrete DI; add only narrowly scoped candidate metadata/submit/independent identity-review/adverse-decision commands required by the approved contract. Bind current verification tuples to the exact accepted event/revision; use expected metadata revisions and current registry semantic snapshots. Registration/creation, review, retirement and grant issuance/revocation must each write their event atomically. Governance does not automatically import any registry row or verify an adapter.

Every existing new-use path through `resolve()`, `loadIdentity()`, `loadPresentation()` and `setMedicationAssociation()` must consume the same current eligibility/invalidation decision; leaving the Phase 3.5 status/activity-only checks as a bypass would violate this contract. Historical `medicationAssociation()` reads may retain original IDs without claiming current validity. The implementation must not attach new endpoints or migrate private records to achieve this internal consistency.

Release in stages: first authority/events with production catalog still denied; then approved synthetic/disposable verification tests; then explicit limited candidate/adapter review activation only after credential/source/bootstrap choices and a real read-only registry baseline/representative assessment. Broad catalog population, automatic reconciliation/merge, personal UI/link migration, clinical content, publication and Treatment linkage remain out of scope. Registry semantic writers must coordinate current-invalidating decisions; uncontrolled/manual meaning changes prevent trusted activation.

Required isolated tests: actor/grant expiry/revocation/qualification/scope; unknown actions; contributor versus editor separation; all-contributor self-review denial; current revision/evidence/dependency fingerprint checks; inactive/adverse/form-parent drift and S/F overlap; audit failure rollback at every command; two-session revocation/review/invalidation races; immutable tuple/history preservation and exact duplicate reuse; safe errors; private boundary and public-deny/HOLD regressions. No normal KOBI mutation tests. Deployment/data authority remains a separate human action. Phase 3.8 was not implemented.

## 20. Open Questions

The final proposed technical boundaries above do not nominate personnel or certify real evidence. Human approval remains needed for:

- Named Medicine bootstrap/grant administrator and permitted issuance/revocation process.
- Reviewer credential authority, competence scopes, expiry/renewal and conflicts policy.
- Accepted fact-specific sources, hierarchy/freshness/withdrawal handling and retention rights.
- Live registry status/type/standard values, external/manual writers, representative conflicts and actual data quality.
- Exact additive governance schema and event/snapshot/current-validity query format, avoiding redundant state owners.
- Controlled replacement/retirement rules for concrete reviewed cases; no bulk name-based merge.
- Later private reviewer-assisted linking access/provenance/consent, separately scoped handler safety fixes and UI integration.
- Future clinical/content/publication schema and qualification/publisher policy; no publication now.

These are prerequisites to the affected activation stage. They do not justify guessing census numbers or weakening the reviewed authority gate. The code/design portion is complete, but readiness is **READY WITH OPEN DESIGN QUESTIONS**, not an unconditional deployment-ready claim.

## 21. Limitations

Live registry and personal census unavailable; all actual counts/semantic classifications remain N/M. No database metadata/version/SQL mode was queried this phase, and deployed tables may differ from the authoritative source dump. Process/listener checks establish the checked local availability only; no remote/custom-port dataset was audited. No source documents, credentials/reviewer qualifications or external import tools were validated.

The SQL plan was inspected statically against source field names; it was not executed or runtime/syntax-certified on MariaDB. Trim/case/collation flags do not establish Unicode/scientific equality. Prior synthetic fixtures/test counts cannot establish live eligibility. No claims of zero duplicates, verified records or safe automatic legacy mappings are made.

Genuine blockers to live census: unavailable local MariaDB. Genuine blockers to shared-write activation: missing persisted authority/history/qualified review/semantic invalidation and unresolved approved personnel/source/representative facts. Deferred private UI, clinical publication and Phase 3.1 fixes are separate non-blocking limitations for this audit. No production change or activation occurred.

## 22. Validation

Inspected relevant content in all six requested Phase 3.1–3.6 artifacts, their schema/identity/authorization/transaction/legacy findings, actual current schema and implementation. Targeted searches/reinspection confirmed exact classes/methods/routes, substance search flags, discarded legacy IDs, private sidecars, lifecycle tuple limits, existing Treatment-scoped grants and current catalog denial. The NOT NULL narrative correction was verified directly from `database/structure.sql`.

Evidence references:

- [Phase 3.1](D:/xampp/htdocs/kobi/docs/MEDICINE_PHASE_3_1_PREFLIGHT.md), [Phase 3.2](D:/xampp/htdocs/kobi/docs/MEDICINE_PHASE_3_2_IDENTITY_SPEC.md), [Phase 3.3](D:/xampp/htdocs/kobi/docs/MEDICINE_PHASE_3_3_SCHEMA_DESIGN.md), [Phase 3.4](D:/xampp/htdocs/kobi/docs/MEDICINE_PHASE_3_4_SCHEMA_VALIDATION.md), [Phase 3.5](D:/xampp/htdocs/kobi/docs/MEDICINE_PHASE_3_5_VALIDATION.md), [Phase 3.6](D:/xampp/htdocs/kobi/docs/MEDICINE_PHASE_3_6_AUTHORITY_RECONCILIATION.md).
- [structure.sql](D:/xampp/htdocs/kobi/database/structure.sql), [MedicineAuthorizationService](D:/xampp/htdocs/kobi/app/Services/MedicineAuthorizationService.php), [MedicineIdentityService](D:/xampp/htdocs/kobi/app/Services/MedicineIdentityService.php), [MedicineRepository](D:/xampp/htdocs/kobi/app/Repositories/MedicineRepository.php), [MedicineCanonicalizer](D:/xampp/htdocs/kobi/app/Services/MedicineCanonicalizer.php).
- [SubstanceRepository](D:/xampp/htdocs/kobi/app/Repositories/SubstanceRepository.php), [SubstanceService](D:/xampp/htdocs/kobi/app/Services/SubstanceService.php), [AccountController](D:/xampp/htdocs/kobi/app/Controllers/AccountController.php), [HealthMedicineService](D:/xampp/htdocs/kobi/app/Services/HealthMedicineService.php), [HealthMedicineRepository](D:/xampp/htdocs/kobi/app/Repositories/HealthMedicineRepository.php), [health-medicine.js](D:/xampp/htdocs/kobi/public/assets/js/health-medicine.js), [routes](D:/xampp/htdocs/kobi/routes/web.php).
- [TreatmentEditorialAuthorizationService](D:/xampp/htdocs/kobi/app/Services/TreatmentEditorialAuthorizationService.php), [TreatmentEditorialPermissionRepository](D:/xampp/htdocs/kobi/app/Repositories/TreatmentEditorialPermissionRepository.php).

Checks/results: local database process/listener checks unavailable; no connection/SELECT performed; planned census counts explicitly N/M. Document has all 22 sequential required sections and valid local file references. Pre/post SHA-256 comparison of 280 baseline files found no existing changes/removals; only this requested document added. `git diff --check` passed and status reviewed. No PHP touched, so no syntax run was required. No browser, unit or disposable mutation suite was run for this design-only task.

No INSERT/UPDATE/DELETE/DDL, temporary database structures, registry correction, canonicalization write, production configuration/SQL-mode change, public exposure or Treatment HOLD change. No server was started. No credentials/private medication content printed. No commit/push/reset/checkout/merge/rebase/history mutation. Final result: **PASS WITH LIMITATIONS; READY WITH OPEN DESIGN QUESTIONS; live census unavailable and shared-write gate denied.**
