# Medicine Knowledge Foundation — Phase 3.2 Identity Specification

## 1. Purpose

Specify a minimal deterministic Medicine identity and data contract before schema design. This document is a proposal for review, not an implemented model or an authorization to migrate, publish, prescribe, or correct existing code.

Primary evidence: `docs/MEDICINE_PHASE_3_1_PREFLIGHT.md`; current `database/structure.sql` medicine/substance/combination/form/alias/evidence definitions; AccountController medicine and search methods; HealthMedicineService/Repository, SubstanceService/Repository; medicine create/edit/list views; `public/assets/js/health-medicine.js`; relevant routes; Treatment item rules and snapshot fields. Paths are relative to `D:/xampp/htdocs/kobi/`. Current implementation was rechecked; no live database connection or data inspection occurred in Phase 3.2. Phase 3.1's unavailable live-data baseline remains a limitation.

Scope is identity design only. Existing exception/date defects remain outside this task and require separately authorized correction. Their presence does not prevent conceptual model design and is not evidence that this specification fixes them.

## 2. Design Principles

- Preserve `kobi_substances` and existing stable IDs; distinguish verified semantics from stored labels/defaults.
- Separate substance, ingredient composition, pharmaceutical presentation, commercial product and personal medication record.
- Determine identity from references and normalized attributes, never from comma-separated labels or brand names.
- Keep strength separate from administered dose, chemical form separate from dosage form, and formulation separate from route.
- Support one or more active ingredients and many presentations per composition without requiring brands/manufacturers.
- Treat unknown values as unresolved, not as equivalence or verified defaults. Preserve raw legacy text without manufacturing canonical facts.
- Avoid redundant identity owners: composition owns ingredient membership; presentation owns strengths/form/route/release; personal records reference the best-known level.
- Leave clinical content/governance independent of identity verification. Identity equality never asserts therapeutic interchangeability.
- Retain KOBI Controller → Service → Repository → Database, BaseRepository, profile authorization, AuthService::id(), CSRF, views/redirect and external JavaScript conventions.

## 3. Current KOBI Model Summary

The implemented “medicine” is a personal `health_medicines` row with profile FK, mandatory brand_name, optional generic_name/strength/dose/frequency/route/dates/prescriber/notes and current/stopped/historical status. There is no substance/product FK. HealthMedicineService checks profile access and owner/editor role; repository writes explicit fields. Summary/timeline/sharing depend on these records and must be preserved.

`/api/substances/search` returns substance_id/canonical_name from active/searchable canonical rows through SubstanceService/Repository. The JavaScript stores selected names in generic_name and discards IDs; several comma-separated names and arbitrary text are accepted. No canonical medicine or clinical knowledge publication service exists.

Existing identity assets: substances; aliases; named substance forms; form components/stoichiometry; combinations and component strength_text; identity reviews; WHO naming evidence; an unused canonical/alias search view. These are not a completed medicine composition/presentation contract. VARCHAR type/status defaults and optional provenance/reviewer fields do not establish verified identity. Neither an existing named combination nor a named substance form can be imported into the proposed model by name alone.

## 4. Terminology

| Term | Contract | Existing terminology / ambiguity |
| --- | --- | --- |
| Substance | Stable canonical chemical/active entity represented by the existing registry. A record must have an explicitly reviewed semantic scope to participate in verified identity. | `kobi_substances`; “Salt / Generic Name” UI does not mean every row is a salt. |
| Chemical form | A separately distinguished salt/form of a substance where identity evidence establishes that relationship. | `kobi_substance_forms` may support this, but actual populated meanings are unverified. Never assume dosage form. |
| Active moiety / base | Identity relationship used to describe what a chemical form relates to; not automatic equivalence or a conversion factor. | No enforced base/active-moiety contract currently exists. |
| Ingredient | Membership of one effective chemical identity in a Medicine Identity. Strength is an associated presentation property, not membership itself. | Existing combination_items has partial membership scaffolding; generic text has no relationship integrity. |
| Medicine Identity | Generic ingredient-composition concept, with a stable ID and one or more distinct active ingredient references. | New conceptual level; not health_medicines.id and not a commercial product. |
| Medicine Presentation / formulation | A specific pharmaceutical variant of a Medicine Identity, identified by the ingredient-strength vector, dosage form, route and declared release characteristics. | New conceptual level; existing strength/route text is not a verified presentation. |
| Strength / potency | Amount or concentration of an ingredient in a presentation, with explicit unit, denominator and measurement basis. | strength / strength_text currently free text. |
| Dose | Patient-specific amount taken/administered on an occasion or schedule. | Existing dose/frequency; never an ingredient-identity attribute. |
| Dosage form | Pharmaceutical form such as tablet, capsule, solution or cream. | Different from chemical form and from route. |
| Route | Administration route associated with a presentation or a user's recorded use, each with separate meaning. | route currently free text. |
| Brand | Marketed name associated with a product; not generic identity. | brand_name currently user-entered text. |
| Manufacturer | Organization associated with manufacture/marketing; roles may differ. | No entity currently established. |
| Product / pack | Optional marketed product and its package/SKU/presentation. | No current commercial catalog. |
| User medication record | Profile-owned record of use, optionally linked to canonical identity/presentation, retaining dose/schedule/dates/instructions/raw observations. | Preserve `health_medicines` and its personal meaning. |

## 5. Canonical Identity Model

Recommend **composition plus presentation**, with immutable identity-defining tuples and separate mutable/governed descriptive metadata.

```text
Substance / verified chemical form
        ↑ ingredient membership (one or more)
Medicine Identity (composition)
        ↑ one parent
Medicine Presentation (strength vector + dosage form + route + release)
        ↑ optional personal reference
User medication record

Optional later: marketed product → presentation; pack → product
```

Composition identity is the nonempty, deduplicated set of effective chemical ingredient references. No quantities, ingredient order, dosage form, route, brand or patient data enter its key. A different ingredient set or explicitly different chemical form produces a different composition. One ingredient can appear in many compositions.

Presentation identity is its composition reference plus a complete normalized strength vector indexed by that composition's ingredients, dosage-form code, one administration-route code and release-characteristic code. Different presentations under the same composition are valid variants. A presentation is not a claim of clinical equivalence.

| Candidate attribute | Identity level / decision | Reason |
| --- | --- | --- |
| Active ingredient set / exact chemical form | Medicine Identity | Defines composition independently of particular strengths or products. |
| Ingredient quantity, unit, denominator, measurement basis | Presentation ingredient strength | Different strengths/bases must not collapse into the same presentation. |
| Dosage form | Presentation | Same ingredient composition can have several pharmaceutical forms. |
| Route | Presentation; user's actual route separately personal | Route is not chemical identity; different canonical route variants must remain distinguishable. |
| Release characteristics | Presentation | Explicit distinctions must not merge; unknown is not assumed immediate/ordinary release. |
| Brand, manufacturer, package/SKU | Optional product/pack | Generic identity must not require commercial data. |
| Excipients/device/particle characteristics | Deferred extension where clinically meaningful | Do not claim the initial key captures every pharmaceutical equivalence distinction; unsupported variants remain unresolved. |
| Dose, schedule, dates, prescriber, notes, adherence | User record | Patient use cannot determine a shared canonical identity. |
| Labels, locale, sources, review status | Metadata / future governance | Can change without creating a new chemical composition or presentation tuple. |

Abstract single-ingredient example: composition M has ingredient A. Presentation P references M and records A at strength X per unit U, form Y, route Z and explicit release R. A user's record references P and records dose Y_units on that user's schedule. Dose changes do not change M or P. If X or Y changes, create/reference another presentation under M; do not mutate P's identity in place.

## 6. Substance / Salt Contract

Keep `kobi_substances` as the canonical substance registry. Its actual fields mean:

| Current field | Observed storage semantics / design boundary |
| --- | --- |
| substance_id | Stable surrogate PK; canonical reference, not a clinical approval. |
| canonical_name | Human canonical label; not medicine/presentation key. |
| canonical_name_normalized | Unique stored comparison label under utf8mb4_unicode_ci; no authoritative normalization writer found. Preserve until audited. |
| naming_standard | Nullable VARCHAR default WHO_INN; designation does not enforce WHO verification. |
| identity_type | VARCHAR default UNKNOWN; potential discriminator, no enforced taxonomy or confirmed populated meanings. |
| identity_status | VARCHAR default CANDIDATE; no implemented verified-selection predicate. |
| is_searchable / is_active | Flags used by current autocomplete, default 1; eligibility flags are not review approval. |
| description | Optional descriptive text; not governed therapeutic knowledge. |
| verification_source | Optional source text, not a mandatory linked verified-source lifecycle. |
| reviewed_by / reviewed_at | Optional reviewer ID/date, without reviewer FK/qualification/independence contract. |
| created_at / updated_at | Administrative timestamps, not immutable identity/content revisions. |

Aliases exist with optional substance linkage and source/review metadata; they resolve display/search names, not new ingredients. Combinations exist in separate tables; a combination medicine must not be manufactured as an extra substance row. Existing identity_type could later distinguish supported concepts, but cannot be relied upon until its vocabulary and existing data are reconciled.

Minimum chemical-reference contract:

1. An effective ingredient reference is tagged **SUBSTANCE(id)** or **CHEMICAL_FORM(form_id)**, exactly one authoritative reference. A form's parent substance is derived from the verified form relationship; clients cannot supply a conflicting parent.
2. Use SUBSTANCE(id) when an existing reviewed substance already denotes that exact chemical entity, including a concrete salt where applicable. Use CHEMICAL_FORM only for an independently verified form not already represented by the chosen canonical substance entity.
3. There must be one approved representative reference per exact chemical entity. If both a substance row and a form row might describe it, reconciliation must choose the representative using evidence before accepting a verified composition. No label-based or automatic ID merge. Ambiguous mapping stays unresolved; the system must not create two approved representations and claim deterministic scientific deduplication.
4. Root/base, salt/form and active-moiety links describe related identities, not alias equality. Different concrete forms remain different ingredient keys unless an explicit reviewed identity correction establishes they were the same entity. Never infer interchangeability or dose conversion from these links.
5. Known ordinary substances need no artificial form row. Unknown salt/form must not be encoded as “base”, a default salt or a verified null. Preserve personal text or an unresolved selection instead.

Existing form_components and stoichiometry may support evidence for a form relationship; they are not a potency-conversion engine. No registry redesign or type-value change is implemented here. Live reconciliation and the exact verified-status mapping are prerequisites to canonical data acceptance.

## 7. Ingredient Contract

Composition membership has one effective chemical reference per active ingredient. Labels are derived; optional display order does not define identity. Reject duplicate resolved chemical references, including an alias resolving to an already selected reference. Do not silently sum repeated rows or treat them as a second ingredient.

An ingredient belongs to exactly one composition membership context; the referenced substance/form can belong to many compositions. Composition owns the ingredient set once. Presentation strengths reference these memberships and cannot add/remove ingredients. Brand/product and user records must derive composition through their referenced identity rather than maintain competing canonical ingredient lists.

The initial model contains active ingredients only. Counterions/form components are represented through verified chemical-form semantics, not added automatically as therapeutic ingredients. Excipients, coatings, device constituents and biphasic subcomponents are outside the initial composition contract and require a scoped extension if needed. Unsupported complex cases can remain unlinked personal text; they must not be flattened into misleading canonical entries.

## 8. Strength / Potency Contract

Strength belongs to a **presentation's ingredient-specific relationship**, not to the underlying substance or composition. Every verified initial presentation requires exactly one structured strength descriptor for every ingredient and none for unrelated ingredients.

Conceptual descriptor:

| Element | Requirement |
| --- | --- |
| Ingredient membership | Valid member of the presentation's parent composition. |
| Numerator amount | Positive finite exact decimal input; normalized using exact rational arithmetic, never floating-point identity comparison. |
| Numerator unit | Approved case-sensitive unit code with declared dimension and conversion policy; retain human display separately. |
| Denominator amount/unit | Positive amount and explicit basis: dosage unit count, volume or mass as applicable. “Per unit” must identify that presentation's dosage unit, not a package. |
| Measured chemical basis | Explicit target chemical reference: normally the exact ingredient; a different verified base/moiety requires sourced relationship. Salt amount and base-equivalent amount are not automatically the same. |
| Activity/potency basis | If strength is expressed in biological/activity units, preserve the declared substance/assay/reference basis required to interpret it. Do not convert to mass or other activity bases automatically. |
| Display / provenance | Original strength wording may be retained as non-key evidence/display, separate from the parsed canonical descriptor. |

Normalize only approved exact unit conversions within the same dimension and measurement basis. Normalize concentration ratios to the same canonical denominator unit and reduce exact ratios; equivalent scaled amount/volume expressions then compare equal. Per-dosage-unit amounts and per-volume concentrations remain different bases. A finite exact quantity must fit future approved precision/size bounds; reject excess rather than round silently. Unknown, ambiguous, zero/negative, range-based or unsupported descriptors cannot form a verified presentation key in the initial model.

Different units across combination ingredients are valid when each independently passes its dimension/basis contract. No conversion across mass, volume, dosage-unit count or activity dimensions is allowed merely to find a match. No molecular-weight/stoichiometry inference or clinical dose calculation is specified.

Existing personal strength text may be retained without structured strength, and composition-only links remain useful. Lack of a verified presentation must not block ordinary personal logging. Structured potency algorithms and complex multi-phase strengths are deferred; the fallback is unresolved text, not a fake canonical value.

## 9. Dosage Form / Formulation Contract

Dosage form is a presentation attribute. It must use a reviewed controlled code with a display label; initial persistence may be a controlled normalized string rather than a separate vocabulary table. A freely entered normalized label alone is not sufficient to establish verified form equivalence. The actual initial vocabulary is a Phase 3.3 approval item, not an enumeration created here.

Tablet/capsule/solution/suspension/cream/ointment/injection/drops/inhaled formulation are illustrative categories, not complete or interchangeable codes. Refinement is necessary where a broad term hides a relevant distinction. A verified presentation requires a supported explicit form code. Missing form leaves the medication at composition/selection/text level. Do not manufacture a presentation with an “unknown” form and compare it as a definite pharmaceutical variant.

Chemical forms in kobi_substance_forms must not be repurposed as dosage forms without their semantic audit. Release characteristic is a separate presentation key attribute with controlled supported values. Missing release information is unresolved; an explicitly reviewed “not applicable” value is permissible only for supported cases. It is not a universal default. Complex delivery/device or formulation differences not expressible by the initial codes require an extension before canonical acceptance.

## 10. Route Contract

The initial verified presentation uses **one explicit administration-route code**. If a presentation is documented for several routes, represent distinct route-specific presentations sharing the composition and appropriate strength/form; do not let user-entered route text expand a canonical route set. Optional future grouping can relate them without changing the key.

Route is separate from form: a form label does not imply route. Codes must come from an approved small vocabulary; initial normalized strings are acceptable under that allowlist. Missing route prevents a route-specific verified presentation. A documented not-applicable case is allowed only by a defined supported policy; null does not mean any route.

Personal recorded route can remain raw text and does not mutate catalog identity. If a structured personal route conflicts with a linked presentation, retain the observation but mark/reject the canonical-link claim according to an explicit validation workflow; never silently rewrite the presentation. This contract does not instruct how the person should administer a medicine.

## 11. Combination Medicine Contract

A + B and A + B + C are ordinary Medicine Identities with two or three ingredient memberships. They reference existing substance/form identities; they do not become synthetic single-substance records.

Ingredient order is not part of composition identity. Resolve aliases/forms to approved effective references, reject duplicates, then sort by fixed tag rank (SUBSTANCE before CHEMICAL_FORM) and numeric ID ascending. Numeric comparison must not be lexicographic. Keep any reviewed display ordering separately.

Each presentation holds a strength vector indexed by those exact memberships. A+B at X/Y and A+B at X/Z share composition but are different presentations. Reordering display A+B to B+A changes neither composition nor presentation if the same amounts stay attached to the same ingredients. Swapping amounts between A and B changes the vector. Ingredients may use distinct units/bases; no single combined strength field substitutes for the vector.

Existing kobi_combinations/items are candidates for reuse, not a second parallel owner of verified composition. Their name-based uniqueness, nullable IDs, strength_text and independently linked form/substance do not meet the proposed invariant. Phase 3.3 must choose reviewed adaptation or explicit mapping to a canonical owner; do not duplicate the same ingredient set in two authoritative models or import unresolved components automatically.

## 12. Brand / Manufacturer / Product Contract

Generic Medicine Identity and Presentation do not require brand, manufacturer, pack or SKU. Distinct brands/manufacturers/packages can refer to the same generic presentation without becoming new composition/presentation identities.

Personal logging may preserve user-entered brand_name/product wording as an observation. It must not create a shared brand/product entity or imply a verified composition match. Future knowledge can initially describe generic composition/presentation with no commercial catalog. Public/SEO content remains governed separately; a brand-like slug is not authorization or canonical identity.

If later scoped requirements justify a catalog: a marketed product references a verified presentation plus its brand/manufacturer/regional identity; packs reference that product plus package attributes. Establish product identifiers separately; brand name alone is insufficient for global uniqueness. Manufacturer and marketing organization roles must not be conflated. Commercial data and package size never define patient dose. Full product/pack/organization schema, regional codes, manufacturer taxonomy and SKU import are explicitly deferred.

## 13. User Medication Record Contract

Preserve health_medicines as a profile-owned record of use. A future reference contract has one authoritative association mode:

| Association mode | Persisted canonical reference / fallback |
| --- | --- |
| Known presentation | Reference presentation ID; derive Medicine Identity and ingredients. Do not maintain independent conflicting composition/substance IDs. |
| Known composition only | Reference Medicine Identity ID; presentation absent. Observed strength/form/route remain personal unresolved data unless matched and confirmed. |
| Selected ingredients only | Profile-owned selection relationships persist one or more explicit substance/form references where no approved composition is resolved. They are not global canonical composition records and do not create one automatically. |
| Unresolved text | Retain original names/strength/dose/route/product wording; no inferred canonical association. |

Only one mode governs the link. Personal raw wording may coexist as historical observation; it is not a competing identity owner. Later product selection derives presentation/composition through the product and must be validated as coherent, not added as an unconstrained second reference.

When a future autocomplete selection occurs, persist the returned **substance_id** as a validated SUBSTANCE reference in the private selected-ingredients association, or use an explicitly chosen already approved composition/presentation. If a verified chemical form is selected, persist its tagged form reference instead. Display text is a label snapshot/observation, not the reference. Editing the text after selection must clear or explicitly reconfirm that selection; arbitrary labels cannot silently retain a different ID. The server resolves eligibility and linkage; the browser cannot declare a candidate verified.

Personal fields: profile ownership, observed medicine/brand/generic wording, dose taken, frequency/schedule, actual-use instructions/route, start/stop dates, status, prescriber, notes and future adherence observations. None belongs in shared identity keys. A name/identity relabel must not rewrite historic observed wording. A shared snapshot retains its original patient record projection; later registry changes must not silently alter it.

Selecting an ingredient is not selecting a strength/presentation, and ordinary users cannot publish or author the shared catalog merely by logging medication. Unresolved logging remains allowed. Existing mandatory brand field remains unchanged now; future user-flow design should permit unbranded/unknown-brand logging using a personal display name without inventing a brand. Future preservation/migration rules must be approved before changing that behavior.

## 14. Identity and Uniqueness Rules

Conceptual keys (not schema or executable code):

- **Composition key:** contract version + ordered list of distinct approved effective ingredient references.
- **Presentation key:** contract version + composition identity + ordered ingredient-strength descriptors (including exact ratios, units/dimensions and measurement/activity basis) + dosage-form code + route code + release code.

Canonical serialization must be versioned, unambiguous and derived server-side. Use typed tags, integer IDs, exact rational numeric forms and fixed field ordering; do not concatenate unescaped labels. A future digest may accelerate lookup, but compare the full canonical tuple and handle collisions; a hash alone is not identity proof. Equal complete tuples are duplicates. Unknown required values have no verified key and are not deduplicated as known facts.

| Comparison | Medicine Identity | Presentation / other level |
| --- | --- | --- |
| Same one effective substance | Same composition | Same presentation only if all presentation key fields match. |
| A+B versus B+A | Same composition | Same if strengths remain bound to the same ingredients and other fields match. |
| A+B versus A+B+C | Different composition | Different presentation. |
| Different concrete salt/form | Different composition | Related identity is not equality. |
| Same composition, different strength | Same | Valid different presentation. |
| Same composition, equal converted strength/basis | Same | Duplicate if all remaining fields match under approved exact conversion. |
| Salt-mass versus base-equivalent expression | Same if exact ingredient membership is the same | Distinct/unresolved strength basis unless an explicit future sourced equivalence rule approves normalization; no inferred equality. |
| Same composition, different dosage form | Same | Valid different presentation. |
| Same composition, different route | Same | Valid different presentation. |
| Same composition, different release | Same | Valid different presentation. |
| Different brand/manufacturer/pack | Same when effective ingredients match | Same generic presentation when its key matches; optional commercial variants separate. |
| Different dose/schedule/user/status | Unchanged | Different personal records/observations; not catalog duplicates. |

Duplicate ingredient submissions are validation errors, not a new composition. A repeat personal record is not necessarily a duplicate course of use; personal idempotency needs a request contract, not a catalog uniqueness rule. Concurrent canonical creation must atomically yield one tuple owner; lookup-then-create without database/transactional uniqueness is insufficient. Physical enforcement is for schema design.

Identity-defining changes create a new composition/presentation, or an explicitly governed correction/supersession with preserved old references/history. Never mutate an existing stable identity to represent a different composition. Renaming a label is metadata. Versioned normalization changes require explicit reconciliation; they cannot silently re-key or merge referenced historical records.

## 15. Normalization Rules

| Value | Human display | Canonical / comparison contract |
| --- | --- | --- |
| Substance/form | Approved label and optional recorded source label | Approved tagged ID is authoritative; preserve existing IDs. Text normalization assists search only. |
| Ingredient ordering | Optional reviewed display order | Fixed tag-rank/numeric-ID ordering; duplicate resolution precedes sorting. |
| Strength | Original amount/unit wording | Exact rational, approved case-sensitive unit code, dimension, denominator, measurement basis and activity reference. Only declared exact conversion. |
| Dosage form / route / release | Localized readable label | Approved stable code; explicit reviewed synonym-to-code mapping, no guess from spelling. |
| Brand/product | Preserve spelling/source label | Search comparison may use normalized text; never determine generic identity or create global product equality from it. |

For future text search, define a versioned policy: valid UTF-8, Unicode NFC, trim/collapse recognized whitespace, and explicit case-folding only for search where appropriate. Do not strip meaningful chemical punctuation, digits, stereochemical markers or salt words. Do not apply case-folding to unit codes. No existing normalized-name column is rewritten by this proposal. Existing collation equality is an implementation fact, not a reviewed scientific equivalence algorithm.

Unsupported unit/synonym/chemical mappings return unresolved/validation error in canonical workflows. Personal original text is retained. Reuse approved alias mappings only when the target is active and identity-verified; aliases do not independently grant verification. Unknown type/status values fail closed for canonical selection, without erasing legacy logging data.

## 16. Legacy Compatibility

| Current Concept | Current Storage | Proposed Concept | Migration Need | Preserve? | Notes |
| --- | --- | --- | --- | --- | --- |
| Selected substance ID discarded | API ID not retained; JS stores names | Private ingredient selection / resolved canonical link | New reference path; explicit future selection | Yes, raw text | Old IDs cannot be recovered reliably from names. No automatic relink. |
| Salt / generic text | health_medicines.generic_name, comma-separated | Original observed wording; optional selected ingredients | Reviewed mapping/confirmation only | Yes | Do not blindly split/resolve names or assume salt equals base. |
| Strength | health_medicines.strength; combination_items.strength_text | Observed text plus optional structured presentation strength | Parse only with complete units/basis/evidence | Yes | Ambiguous amounts stay text; never distribute one string across ingredients by guess. |
| Dose | health_medicines.dose | Personal dose | Preserve; optional future structured record | Yes | Never promote to ingredient strength. |
| Route | health_medicines.route | Actual-use observation; optional presentation match | Approved mapping and confirmation | Yes | Do not assume form or rewrite catalog route from personal entry. |
| Brand/name | mandatory brand_name | Personal display/product observation | Later permit generic/unbranded logging without fictional brand | Yes | Not an authoritative product identity. |
| User medication row | health_medicines.id/profile/status/dates/prescriber/notes | Profile-owned record | Additive reference support, no forced catalog dependency | Yes | Preserve record IDs, access rules, timeline/summary/sharing behavior. |
| Substance names/IDs | kobi_substances and aliases | Existing registry asset | Semantic/normalization/status audit | Yes | No renumbering/reclassification/merge merely to fit design. |
| Named forms/components | kobi_substance_forms/form_components | Verified chemical-form relationship if semantics fit | Data audit and explicit mapping | Yes | Not dosage-form vocabulary by default. |
| Named combinations | kobi_combinations/items | Candidate canonical composition/presentation data | Reconcile ownership and complete references/strengths | Yes | Do not create a duplicate authoritative composition store or fake substance. |
| Reviews/naming evidence | identity_reviews/WHO evidence | Identity provenance | Define verified transitions/correct source scope | Yes | Not automatic clinical approval. |

A future additive migration can preserve raw data without loss, even when canonical mapping is impossible. **Lossless semantic conversion cannot be promised**: original discarded IDs, missing units/forms and ambiguous strings may be unrecoverable. Use explicit unresolved/mapping provenance and user/editor confirmation; never fabricate missing facts or overwrite original values. No migration, backfill or seed is authorized here.

## 17. Treatment Integration Contract

Current Treatment item_type includes medicine but its snapshot fields contain titles/instructions/limitations, not medicine/substance IDs. Leave it unchanged now.

Future generic link: Treatment Item → Medicine Identity → ingredient references → substance/chemical form. If the knowledge is presentation-specific, use an explicit Presentation reference instead and derive composition; no commercial brand is required. A broad substance-only statement can remain a scoped reference/content topic until a supported medicine-composition link is appropriate, rather than invent a strength or product.

Never link Treatment knowledge to private health_medicines records or user doses. The eventual link must specify reviewed identity/content version, source scope and applicable presentation distinctions. Adding/changing a reference or identity semantics must participate in Treatment revision/snapshot invalidation; identity/medicine-content supersession must be reassessed, not silently inherit approval. Mere label updates require a documented semantic-versus-display policy.

Public reads must validate both domains' applicable governance/current-source conditions without bypassing Treatment's canonical reader or HOLD. A Medicine candidate is not a Treatment candidate, and neither implies exposure. Cross-domain invalidation/locking and shared public projection are later design work; no Treatment FK/route/schema/reader change is made here.

## 18. Future Governance Compatibility

Separate **identity provenance** from **clinical knowledge approval**. Descriptive knowledge records can attach to a substance, composition or presentation with explicit scope; presentation-specific claims must not be silently generalized to every variant.

| Governance concept | Future attachment |
| --- | --- |
| Source / verification | Identity/form mapping evidence separately from clinical claim sources; valid linked provenance and lifecycle. |
| Evidence / risk | Scoped clinical knowledge revision; medicine-specific policy, not substance flags or blind Treatment ENUM copying. |
| Purpose / applicability / limitations | Knowledge record/revision, audience/context and localized educational content. |
| Review / approval | Qualified independent authorization for the relevant scope, revision-bound canonical snapshot/hash and deadline. |
| Publication / locale | Knowledge exposure state and explicit approved locale; identity/search eligibility remains separate. |
| Revision / history | Immutable semantic revisions, approvals and append-only events; stable identity tuple with governed correction/supersession. |
| Invalidation / replacement | Current source/identity/content reassessment; dependencies and prior snapshots/history retained. |

Reuse Treatment's lessons: service-owned rules, scoped persisted permissions, controlled values under permissive MariaDB, current locking reads for mutation decisions, read-only consistent public snapshots, safe error boundaries, explicit allowlists, independent review and separation of candidate/approval/publication/exposure. Medicine-specific identity, strength basis, interactions/indications, product labeling and content scope need their own rules. Do not reuse private share snapshots as clinical approvals or attach personal notes to shared knowledge.

## 19. Minimum Viable Medicine Model

### Required now

For conceptual/schema design: existing substance registry; one verified effective chemical reference contract; composition plus nonempty unique ingredient memberships; presentation plus one strength descriptor per ingredient and explicit form/route/release; private medication record with optional association mode and raw-text fallback. Versioned deterministic keys, basic selection eligibility, authority and validation are required. No brand/product entity is necessary. Ordinary substance-only selections can be saved without a presentation or global catalog write.

### Required later

Before verified data acceptance: approved status/type/unit/form/route/release vocabularies, semantic audit of existing form/combination data, exact precision bounds and transactional uniqueness enforcement. Before clinical authoring/public exposure: source/evidence/risk and revision/review/approval/publication/history design, safe failure/concurrency tests and canonical projection. Before Treatment integration: cross-domain reference/invalidation contract. Existing safety fixes remain separate prerequisites to extending affected medication handlers.

### Explicitly deferred

Full commercial catalog, manufacturer/pack/SKU entities, molecule conversion/dose calculation, excipient/device/formulation equivalence, complex multi-phase potency, automatic legacy linking, registry merges, public rollout and Treatment schema changes. Unsupported variants remain unresolved rather than distort the minimal model.

## 20. Design Options Considered

| Option | Advantages | Disadvantages / migration complexity | Existing KOBI effect / governance | Recommendation |
| --- | --- | --- | --- | --- |
| Personal record + one substance FK only | Very small; persists current selected ID. | Cannot faithfully model combinations/strength/form variants; would force another redesign. Low initial migration, high later correction. | Keeps logging but inadequate shared knowledge scope. | Reject as complete foundation; selected-ID fallback is still useful. |
| One flat canonical entity keyed by ingredient strengths/form/route/release | Deterministic exact variants; fewer conceptual layers. | Repeats membership across strengths; no explicit composition scope for generic knowledge. Moderate mapping, difficult broad-claim governance. | Possible architecture but less suited to Treatment composition links. | Viable alternative, not preferred. |
| Composition + presentation + optional personal links | Explicit generic scope, combination support, variant distinction and no required brand. | One extra relationship; must carefully separate ownership and existing combinations/forms. Moderate additive mapping, no mandatory raw-text conversion. | Preserves personal logs; permits scoped identity versus clinical governance. | Recommended. |
| Comprehensive ingredient/product/manufacturer/pack/device catalog | Detailed commercial specificity. | Extensive provenance/import/regional complexity and high migration cost. | Adds dependencies far beyond present logging/knowledge stage. | Defer. |

## 21. Recommended Architecture

Adopt composition/presentation as the conceptual contract for the next design phase, subject to developer review. It is the smallest option that gives composition-level knowledge and exact presentation variants while preserving unresolved personal logging and existing registry IDs.

Extend existing services/repositories/controllers when implemented; do not create a second persistence framework or frontend architecture. Private medication operations remain in HealthMedicineService; canonical catalog resolution/editing is a distinct scoped service responsibility with shared BaseRepository conventions. Ordinary logging cannot create or verify global identities. Reuse existing combination structures only after deciding their single authoritative role; avoid parallel global ingredient owners.

**Design readiness: READY WITH OPEN DESIGN QUESTIONS.** The identity-level split and uniqueness contract are resolved enough to proceed to a narrow schema/data-contract design. Chemical-reference reconciliation, vocabulary/precision details and physical table reuse must be settled there before accepting canonical data. Phase 3.1 safety defects remain open implementation prerequisites, not identity-model blockers or corrected findings.

## 22. Future Schema Implications

Conceptual relationships only; no executable schema is provided:

- Preserve substance/alias/form/evidence assets and stable IDs; determine how verified chemical-reference resolution is represented without redundant identity copies or uncontrolled polymorphic references.
- One composition owner with ingredient memberships, exact effective-reference uniqueness and an atomically enforced composition key.
- One presentation owner referencing composition; ingredient-strength associations reference its existing memberships and prohibit extra/missing ingredients. Form/route/release codes and full normalized strength tuple determine presentation uniqueness.
- Existing combinations/items must be adapted or mapped, with provenance and one canonical owner; no parallel equally authoritative composition lists.
- Personal medication association is optional and mutually coherent: presentation, composition, private ingredient selections or text fallback. Derive parent identities rather than maintain unrelated references. Do not require a product table.
- Future foreign keys, deletion policy, quantity precision, candidate versus verified completeness, unique-key serialization and concurrency enforcement need a MariaDB 10.4.27-compatible schema review. Preserve referenced records/history; prefer retirement/supersession over destructive identity deletion.
- Future metadata/source/review/history attaches by declared scope, separate from identity-defining attributes and private use records.

No table, migration, constraint, route or field is created now. No changes to database/structure.sql, kobi_substances, health_medicines or Treatment are authorized by this document.

## 23. Future Validation Contract

Server requirements for later implementation:

1. Reject malformed scalar/array shapes, unsupported fields, oversized UTF-8 values and invalid identifiers before SQL. IDs are positive exact integers; submitted labels/statuses never override persisted identity or authority.
2. For personal records, authenticate with AuthService::id(), use accessible active profile/member checks, require owner/editor for writes and verify CSRF. Check every existing personal record/selection belongs to that profile. For shared catalog edits, require separately defined persisted editorial authority; personal membership does not confer it.
3. Resolve every substance/form/alias to an existing active verified effective reference. Unknown/candidate/inactive/unverified mappings cannot create a verified composition/presentation. Personal logging uses explicit unresolved fallback rather than fabricated approval. Status allowlists must be defined from approved semantics, not current VARCHAR defaults.
4. Canonicalize/reject duplicate ingredients, require nonempty compositions, enforce complete and parent-consistent strength vectors and reject conflicting form/substance parent references.
5. Validate exact positive amounts, supported units/dimensions/denominators, chemical/activity basis, precision bounds and supported form/route/release combinations. Unknowns and unsafe conversions do not pass as canonical facts. Validate referential/form compatibility from approved policy, not by inferring route from form spelling.
6. Resolve associations server-side; a linked presentation determines its composition/ingredients. Clear or reconfirm selections when display text changes; verify optional later product linkage resolves to the same presentation. Raw personal wording is retained as observation, not trusted catalog data.
7. Perform canonical duplicate creation atomically under reviewed database constraints/transaction rules; return existing identity or safe conflict. Personal retry idempotency is separate. Require stale-revision/state protection for future governed catalog edits and define private lost-update behavior explicitly.
8. Return safe application errors for validation/authorization/stale state/infrastructure/unexpected failures; keep internal exceptions/secrets out of responses. Preserve parameterized repositories, escaped text/textContent, CSRF and external JS/CSP conventions. No validation may rely on strict SQL mode.

Later acceptance scenarios must include reordered combinations, duplicate aliases, swapped ingredient strengths, exact equivalent versus different units/bases, salt/form ambiguity, unknown form/route/release, brand/package independence, stale selected text, multiple personal selections, cross-profile IDs, concurrent duplicate creation and raw fallback preservation. These are proposed tests, not executed tests or new test files. Phase 3.1 exception/date issues are only referenced; no correction is attempted.

## 24. Open Questions

| Question | Recommended default / resolution | Blocks what? |
| --- | --- | --- |
| Which existing substance/form rows denote the same exact chemical entity, and what identity_type/status values are populated? | Read-only data reconciliation; choose one evidence-backed effective representative. Never merge by name. | Verified canonical mapping/import, not conceptual schema discussion. |
| Can existing combinations become the single composition owner without conflicting source/strength semantics? | Compare actual data/constraints; review adaptation versus explicit mapping before choosing physical entities. | Final physical schema/reuse plan. |
| Which initial unit dimensions, precision bounds, form/route/release codes and activity bases are in scope? | Small reviewed allowlists; reject unsupported canonical cases, preserve personal text. | Verified presentation acceptance and final constraints. |
| How is basic identity verification authority/status enforced before full clinical governance? | Define explicit identity verification scope and transitions; flags/defaults alone are insufficient. | Canonical editorial/search rollout. |
| When should private medication UI allow unbranded display naming and structured selections? | Additive, separately authorized UI/data contract retaining originals and profile rules. | Private-flow implementation/migration; no change now. |

No user answer is assumed or applied to the code. These are bounded Phase 3.3 decisions, not permission to migrate data or waive verified identity requirements. The conceptual keys do not depend on choosing a commercial catalog or performing clinical equivalence calculations.

## 25. Explicitly Deferred Decisions

No live data validation, schema implementation/migration, registry reclassification/merge, seed/import, automatic name-to-ID backfill, clinical content, potency/dose conversion, brand/manufacturer/package/SKU database, complex pharmaceutical-equivalence engine, source/review/publication implementation, public/SEO rollout or Treatment coupling. Exception/date safety corrections remain in a separately authorized task. No SQL mode/configuration/HOLD change or Git mutation.

Validation in this task: specification cross-checked against schema/code/routes and Phase 3.1; identity edge cases explicitly reconciled in sections 5–15 and 23; beginning Git status/relevant diff and final diff checks; task-start file-hash comparison. No executable behavior test was necessary for a documentation-only change, and none is claimed. Only this specification is created; existing files and pre-existing changes are preserved.

## 26. Proposed Phase 3.3 Starting Point

Perform a **narrow Medicine schema and persistence-contract design**, using this composition/presentation contract: reconcile the eight existing identity tables; decide the single composition owner and effective chemical-reference enforcement; define supported strength/unit/form/route/release bounds; define optional private selected-ID association and versioned canonical keys; review required constraints and concurrent-creation behavior against MariaDB 10.4.27. Obtain a safely scoped read-only catalog baseline when available.

Return a reviewable schema/data-contract proposal and compatibility/migration plan preserving stable IDs/raw medication data. Do not implement it until separately authorized. Keep Phase 3.1 safety corrections as a distinct prerequisite to extending the affected flows. No normal-database changes, Treatment modifications, clinical publication or commercial import is implied.
