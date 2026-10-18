# Medicine Knowledge Foundation — Phase 3.3 Schema & Persistence Design

## 1. Purpose

Translate the approved Phase 3.2 composition/presentation model into a concrete proposed schema and persistence contract. This document is **design only**. It contains no executable SQL and does not authorize schema application, seed data, registry reclassification, personal-data migration, clinical publication or fixes to existing code.

Date: 2026-10-09, Asia/Calcutta. Only this document is created. Existing application/tests/schema/configuration/routes, personal medication functionality and Treatment HOLD remain unchanged. No database connection or write is made. Phase 3.1 exception-disclosure/date-validation defects remain separately tracked implementation prerequisites.

## 2. Inputs and Current-State References

Primary inputs: `docs/MEDICINE_PHASE_3_1_PREFLIGHT.md`, `docs/MEDICINE_PHASE_3_2_IDENTITY_SPEC.md`, and current `database/structure.sql`. Paths are relative to `D:/xampp/htdocs/kobi/`.

Rechecked: AccountController medicine create/update/delete/search handlers; HealthMedicineService/Repository; SubstanceService/Repository; BaseRepository and Container; profile accessibility query; medicine create/edit/list views and health-medicine.js; medicine/substance routes; existing Treatment item schema/rules/snapshot references; test discovery. Current personal persistence stores explicit text fields, not substance IDs. AJAX returns IDs/names, while JavaScript retains only names. Personal access uses active profile/member scope, owner/editor writes and CSRF. No medicine-specific regression suite or canonical medicine persistence exists.

Phase 3.1's failed database connection means populated substance/form/combination semantics remain unverified. This design does not infer record counts from AUTO_INCREMENT counters or approve any legacy identity. Beginning Git status/relevant diffs were inspected; unrelated pre-existing changes were preserved.

No contradiction was found with Phase 3.2's intended composition/presentation split. Current mandatory brand_name and name-based combinations **do not implement** that model. The explicit reconciliation decisions below avoid silently redefining them. Numeric limits and constraint characteristics were checked against official MariaDB documentation; exact future DDL still requires separately authorized MariaDB 10.4.27 validation.

## 3. Current Medicine Schema Inventory

All nine physical tables below have created_at/updated_at timestamps. Identity-table type/status values are VARCHAR, not verified controlled taxonomies. No medicine content revision, clinical approval/publication locale/deadline is present.

| Table / purpose | PK / important columns | FKs / indexes / uniqueness | Status / ownership / source-review / actual usage |
| --- | --- | --- | --- |
| health_medicines — personal medication log | id INT; profile ID; brand_name, generic_name, strength, dose, frequency, route, dates, prescribed_by, notes | Profile FK DELETE CASCADE; profile/status indexes; no substance FK or logical uniqueness | ENUM current/stopped/historical; profile ownership; no knowledge source/review; active CRUD, summary/timeline/sharing |
| kobi_substances — canonical named substance | substance_id BIGINT unsigned; canonical/normalized names; naming_standard, identity_type/status, description | Unique normalized name; active/searchable/name and status indexes; no reviewer FK | Active/searchable flags; UNKNOWN/CANDIDATE and WHO_INN defaults; optional verification_source/reviewed_by/at; canonical autocomplete |
| kobi_substance_aliases — source/alternate labels | alias_id BIGINT unsigned; nullable substance ID; alias/normalized, type, source name/key/reference | Substance FK SET NULL; search/substance/source indexes; no unique alias | PENDING, active/searchable; source text, no approval contract; no current autocomplete use |
| kobi_substance_forms — named form of substance | form_id BIGINT unsigned; substance ID; form/normalized/type/status, canonical form label | Substance FK UPDATE CASCADE; unique substance + normalized form; search/substance indexes | UNKNOWN/CANDIDATE; active flag, verification_source; no dosage-form semantics enforced; no current application workflow |
| kobi_form_components — form composition evidence | form_component_id BIGINT unsigned; form ID; component type/name/normalized, nullable substance, stoichiometry | Form FK CASCADE, component substance SET NULL; form/substance/name indexes; no component unique key | PENDING verification, evidence_reference; no current workflow |
| kobi_combinations — named imported combination | combination_id BIGINT unsigned; name/normalized, source name/key | Unique normalized name; active/searchable/name index | PENDING, flags, source text; no current medicine-record linkage |
| kobi_combination_items — imported component | combination_item_id BIGINT unsigned; parent combination; nullable substance/form; name/normalized, sequence_no, strength_text | Combination CASCADE; substance/form SET NULL; parent/substance/form indexes; no member/order uniqueness or joint-parent guarantee | PENDING; no independent verification/clinical review; no current medicine persistence use |
| kobi_identity_reviews — candidate decisions | review_id BIGINT unsigned; source name, optional alias/substance/form, decision/reason/evidence, reviewed_by/at | Three identity FKs SET NULL; source/alias/substance/form/decision indexes; no reviewer FK | PENDING; mutable decision metadata, not qualified independent clinical approval; no current workflow |
| kobi_who_inn_evidence — naming evidence | evidence_id BIGINT unsigned; optional substance; INN name/normalized/status, list/date/URLs/page/hash/notes | Substance SET NULL; name/substance/status indexes; no unique evidence record | PENDING verification; naming provenance, not therapeutic evidence; no current workflow |

`kobi_substance_search` is an unused UNION ALL view over canonical names/aliases, with display/search names and review_status. Canonical branch filters parent flags; alias branch filters alias flags/non-null linkage but not parent flags/review eligibility. No change to this view is proposed in the first schema step.

Context tables: health_profiles/members retain access and ownership; users supplies actors. medical_summary_shares/access_logs govern private token sharing and snapshots, not clinical catalog approval. Treatment protocol/items/sources/events/permissions remain a separate governed domain; item_type=medicine has free-text educational content and no medicine/substance FK. No brand/product/manufacturer/package table exists.

## 4. Existing-Table Reuse Decisions

| Current table/object | Decision | Concrete reason / future role |
| --- | --- | --- |
| kobi_substances | REUSE AS-IS | Existing stable registry IDs/labels; future adapters reference it. Do not rewrite names/statuses or copy it into another substance registry. |
| kobi_substance_aliases | EXTEND LATER | Preserve source labels; later verified alias resolution must require target eligibility. No automatic relink or new alias writer now. |
| kobi_substance_forms | REUSE AS-IS | Reference only after evidence-backed chemical-form interpretation. It is not the dosage-form vocabulary. Parent derivation prevents conflicting base IDs. |
| kobi_form_components | REUSE AS-IS | Retain chemical-form evidence; do not convert stoichiometry into medicine strength or an ingredient automatically. |
| kobi_combinations | REPLACE/LEGACY | Preserve as import/provenance staging. Name uniqueness and ambiguous source contents cannot become the verified composition owner without risky reinterpretation. |
| kobi_combination_items | REPLACE/LEGACY | Retain unresolved IDs/names/strength_text as source evidence; reviewed later mapping into the single new canonical composition/presentation owner. No dual authoritative lists. |
| kobi_identity_reviews | EXTEND LATER | Existing evidence preserved; not sufficient as governed verification/clinical event store. No retrospective approval inference. |
| kobi_who_inn_evidence | REUSE AS-IS | Naming evidence asset; may support identity verification references, never automatic therapeutic approval. |
| kobi_substance_search | EXTEND LATER | Later canonical search can use verified adapter resolution; current unfiltered view is not an acceptance predicate. |
| health_medicines | REUSE AS-IS in first implementation | Keep private row IDs and all text/fields. Add optional sidecars instead of requiring catalog links or changing brand semantics. |
| health_profiles / health_profile_members / users | REUSE AS-IS | Existing ownership/actor boundaries and matching FK types. |
| medical_summary_shares / access_logs | REUSE AS-IS | Private sharing preserved; not knowledge publication machinery. |
| Treatment tables | NOT RELATED to this schema implementation | Future conceptual links only; no schema/HOLD/governance modification. |

“REPLACE/LEGACY” authorizes no deletion, rename or write. New canonical tables alone own validated composition; imported combinations retain provenance only. A later provenance-mapping record may identify the reviewed source-to-target relation, but it is deferred until a migration scope exists. This settles physical ownership without claiming legacy data can be safely converted now.

## 5. Canonical Entity Model

Seven proposed tables constitute the first additive foundation; vocabulary values are controlled columns, not seven more lookup tables.

| Proposed entity/table | Purpose / level | Lifecycle / ownership | Why a separate entity |
| --- | --- | --- | --- |
| kobi_medicine_chemical_refs | Typed, evidence-verified reference to one existing substance or chemical form; identity adapter | Shared identity metadata; candidate/verified/retired | One enforceable FK target for members/bases/private selections; avoids repeated unchecked polymorphic pairs. It stores no duplicate substance label/entity. |
| kobi_medicine_identities | Canonical ingredient composition | Shared, complete immutable tuple; candidate/verified/retired | Composition spans many presentations and clinical scopes; not a column on personal log. |
| kobi_medicine_ingredients | Explicit composition membership | Owned by identity; immutable with it | One-to-many ingredients and compound support cannot be a comma string or fixed columns. |
| kobi_medicine_presentations | Strength/form/route/release variant | Shared, complete immutable tuple; candidate/verified/retired | Many variants per composition, independent of brands/doses. |
| kobi_medicine_strengths | Exact per-ingredient presentation ratio | Owned by presentation; immutable with it | A combination needs independent member-specific strengths; not one parent text amount. |
| health_medicine_identity_links | Optional association for one private medication row | Profile-owned through health_medicines; mutable with optimistic revision | Leaves legacy parent unchanged; expresses one association mode, not another medication record. |
| health_medicine_selected_ingredients | Private explicit IDs where composition/presentation unresolved | Owned by an ingredients-mode private link | Persists several selected IDs without global catalog creation or competing canonical membership. |

No brand/manufacturer/product/pack, chemical ontology, general unit table, activity-assay table or clinical governance schema is created in the initial proposal. Dosage form, route, units, release and sole active-ingredient role are explicit controlled contracts (section 24).

Common proposed conventions: new shared PKs BIGINT unsigned; existing substance/form FKs match their BIGINT unsigned types; health_medicines/users references match signed INT. Public input IDs use decimal strings to avoid JavaScript precision loss; later PHP service rejects values beyond PHP_INT_MAX rather than truncating them. New shared tables use InnoDB; human labels UTF-8; codes/key bytes compare case-sensitively. No CHECK references a new AUTO_INCREMENT PK.

Shared adapter/composition/presentation status defaults to candidate and metadata_revision to 1. Permitted transitions are candidate → verified, candidate → retired and verified → retired; retired is terminal. Row CHECKs enforce status membership, positive revision and required verification/retirement attribution tuples (nonblank reference/reason), while service locks/authority enforce transitions and evidence validity. Verification attribution is retained when a previously verified entity retires; an unverified candidate can retire without a verification tuple. New selected-ingredient display_order is bounded 1–16. All proposed new FK updates are RESTRICT; private-child deletes cascade only where explicitly listed.

## 6. Medicine Identity Design

Proposed table: `kobi_medicine_identities`.

| Column | Type concept / required | Purpose / constraint |
| --- | --- | --- |
| medicine_identity_id | BIGINT unsigned PK, required | Stable identity; not brand/user record ID. |
| key_version | SMALLINT unsigned, required, initially 1 | One approved serializer generation; no concurrent versions bypassing deduplication. |
| composition_hash | BINARY(32), required, UNIQUE | Server-generated SHA-256 of canonical composition tuple; race-safe reservation. |
| composition_key | TEXT, required, bounded 8 KiB, binary comparison | Versioned canonical tuple for collision/full-equality validation; derived index representation, not second editable ingredient list. |
| display_name | VARCHAR(500), optional | Human label; nonunique, not identity. Generated labels need not be stored. |
| identity_status | VARCHAR(16), required, candidate default | Exact candidate/verified/retired CHECK + server validation. Verification is composition identity, not clinical approval. |
| metadata_revision | INT unsigned, required >=1 | Optimistic metadata/status version; not clinical content revision. |
| created_by / created_at / updated_at | INT actor FK; UTC DATETIME(6), required | Administrative ownership/evidence actor; no profile/user ownership of shared identity. |
| verified_by / verified_at / verification_reference | Optional INT FK, UTC DATETIME(6), TEXT | All required for verified; evidence must substantiate composition. Retain prior tuple on retirement. |
| retired_by / retired_at / retirement_reason | Optional actor/time/text | All required when retired; no restoration by ordinary update. |

Indexes: composition_hash unique; status + identity ID for bounded list; actor FKs. Ingredients are the authoritative relational membership; key/hash are server-derived and validated from them at construction/verification/read resolution. Version-1 key payloads use the fixed JSON serialization below, with JSON-validity and byte-length CHECKs; JSON validity alone does not prove membership equality. Ingredient count (1–16), tuple equality, hash correctness and evidence/current-reference eligibility are service invariants.

Candidate rows already have complete deterministic compositions; incomplete proposals stay request/draft data, not partially keyed catalog rows. Identity fields cannot be edited in place after commit, including candidate membership. Corrections create a distinct identity or separately governed supersession. Mutable display/provenance/status edits use metadata_revision. Verification/status changes and retirement require trusted identity authority, not personal membership; implementation of that authority is outside a schema-only first step.

## 7. Medicine Ingredient Design

Proposed `kobi_medicine_ingredients`:

| Column | Type concept / required | Constraint |
| --- | --- | --- |
| medicine_ingredient_id | BIGINT unsigned PK | Stable membership reference. |
| medicine_identity_id | BIGINT unsigned FK, required | Identity owns 1–16 memberships; RESTRICT deletion/update. |
| chemical_ref_id | BIGINT unsigned FK, required | Exactly one effective approved chemical entity; adapter resolves existing substance/form. |
| display_order | SMALLINT unsigned, required, 1–16 | Unique within identity; not semantic identity order. Default to canonical rank. |
| created_at | UTC DATETIME(6), required | Membership construction time; no independent status. |

UNIQUE identity + chemical_ref prevents duplicate ingredients; UNIQUE identity + display_order bounds deterministic display; UNIQUE identity + ingredient ID supplies the scoped strength FK target. Reverse chemical_ref + identity index supports dependency/reconciliation reads. Role is implicitly active in version 1; no redundant role column/role catalog. Form components/excipients are not automatically therapeutic ingredients.

The typed chemical adapter (section 13) supplies the eventual Substance FK path. Salt/form is part of exact chemical reference and therefore composition identity; route/strength/release are not. Strength is stored only in kobi_medicine_strengths, with one descriptor per membership/presentation. A separate active-moiety relationship/conversion is deferred; initial measured basis is the ingredient itself.

## 8. Combination Medicine Design

A, A+B and A+B+C use the same tables; the maximum 16 is an initial engineering bound, not a scientific limit. Larger/unsupported compositions remain unresolved until a scoped extension. No combination becomes an extra substance row.

Canonical composition algorithm: resolve evidence-verified effective references; reject duplicates (including alias-to-same-reference); obtain tagged underlying references S(substance_id) or F(form_id); sort by S-before-F then exact numeric ID ascending; serialize version and the ordered set using fixed fields/integer strings. Source display order, brand, quantities and names are excluded. Hash the exact UTF-8 canonical bytes. Adapter surrogate IDs are not chemical identity key material; underlying stable S/F references are.

Fixed version-1 serialization is compact UTF-8 JSON, no whitespace or BOM, object properties in the declared order, version as integer 1, IDs/coefficient values as canonical unsigned decimal strings, and codes as exact ASCII strings. Composition properties are v then ingredients; ingredients is an ordered array of two-element arrays [S-or-F tag, underlying ID string]. No labels or optional properties enter the payload. This fixes the byte contract rather than depending on arbitrary JSON property ordering.

Same set means same composition. Different exact chemical form or added/removed member means different composition. Same composition with different amounts, form, route or release means a different presentation; same presentation sold under another brand/package is a commercial variant. Equality says nothing about interchangeability. Existing named combination rows are not canonical inputs until every relationship and chemical scope is reconciled.

## 9. Medicine Presentation Design

Proposed `kobi_medicine_presentations`:

| Column | Type concept / required | Purpose / constraint |
| --- | --- | --- |
| medicine_presentation_id | BIGINT unsigned PK | Stable variant. |
| medicine_identity_id | BIGINT unsigned FK, required | Exactly one composition; RESTRICT. |
| key_version / presentation_hash / presentation_key | SMALLINT unsigned / BINARY(32) / TEXT, required | Version 1; hash UNIQUE; full canonical tuple bounded 16 KiB and compared on duplicate. |
| dosage_form_code / route_code / release_code | Case-sensitive VARCHAR(32), required | Explicit allowlists/CHECKs; no unknown or silent default. |
| display_name | VARCHAR(500), optional | Nonunique label, not brand or identity. |
| identity_status / metadata_revision | VARCHAR(16) / INT unsigned, required | Same candidate/verified/retired and stale metadata contract as composition. |
| created_by / created_at / updated_at | Actor INT FK / UTC DATETIME(6), required | Administrative attribution. |
| verified_by / verified_at / verification_reference | Optional actor/time/text | Required for verified; proves presentation facts, not public clinical approval. |
| retired_by / retired_at / retirement_reason | Optional actor/time/text | Required for retired; retain identity history/references. |

UNIQUE identity + presentation ID supplies scoped strength FK; indexes identity + status + presentation ID and actor FKs. A composition has zero or many complete presentations. Candidate presentations have all supported identity-defining values and a full strength vector; unknown form/route/release/strength does not create a presentation.

Presentation key includes composition ID, canonical per-member strength descriptors and explicit form/route/release. One route per presentation; multiple supported routes require distinct route-specific variants. No top-level scalar strength/dose column. Strength rows own exact amount/concentration. Metadata edits do not mutate tuple; amount/form/route/release changes resolve/create another presentation. Unsupported device/excipient/multiphase distinctions cannot be flattened into a supposedly exact supported variant.

Its fixed JSON property order is v, identity, strengths, form, route, release. identity is the composition ID string. strengths is in canonical chemical-reference order, each entry [S-or-F tag, underlying ID string, reduced numerator string, reduced denominator string, numerator unit code, denominator unit code]. The reference explicitly identifies the measured chemical basis. The same compact byte rules apply; JSON-validity and the 16 KiB byte bound are row CHECKs, while full aggregate equality is a service rule.

## 10. Strength / Quantity Contract

Proposed `kobi_medicine_strengths` has composite PK (presentation ID, ingredient ID), plus medicine_identity_id as an FK scoping discriminator. This repeats parent scope solely to enforce composite membership, not as an independently writable composition owner.

| Column | Type concept / required | Constraint / meaning |
| --- | --- | --- |
| medicine_presentation_id / medicine_identity_id / medicine_ingredient_id | BIGINT unsigned, required | Composite FKs to (identity, presentation) and (identity, ingredient) prevent cross-composition strength rows. |
| ratio_numerator / ratio_denominator | DECIMAL(38,0), required | Positive integer coefficients, reduced by greatest common divisor, each 1–38 digits; exact rational strength, no rounding. |
| numerator_unit_code | Case-sensitive VARCHAR(8), required | Initial canonical mass unit g only; explicit CHECK, no dose quantity. |
| denominator_unit_code | Case-sensitive VARCHAR(8), required | unit, mL or g; determines per-dosage-unit, per-volume or per-mass basis. |
| original_strength_text | VARCHAR(512), optional | Source/display wording only; not a second canonical numeric representation. |
| created_at | UTC DATETIME(6), required | Construction timestamp. |

Initial input quantities are positive decimal **strings** with at most 12 integral and 12 fractional digits after insignificant zeros are normalized; a DECIMAL(24,12) equivalent input range, but raw numeric inputs are not stored as another authoritative amount. Reject exponent notation, NaN/infinity, signed/negative/zero, unsupported units, ranges and excess precision. Denominator dosage-unit counts must be positive integers; volume/mass denominators may be decimal. This bounded envelope is deliberately revisable; it does not claim to encode all future pharmaceuticals.

Exact normalization: convert approved mass units g/mg/ug to g and volume mL/L to mL using exact powers of ten, form the amount divided by denominator quantity, cancel common integer factors, store reduced N/D with canonical numerator/denominator units. N/D g per mL, g per g and g per dosage unit are distinct dimensional bases. A terminating input ratio can produce a repeating decimal; the two integer coefficients preserve it exactly. Ratios with either reduced coefficient beyond 38 digits are rejected, never rounded/saturated. A, B may have independently different input units, but each descriptor normalizes in its proper dimension/basis.

Each version-1 descriptor measures the exact ingredient chemical reference itself. Measured-basis identity is derived and serialized explicitly from that membership, not copied into a conflicting FK column. Salt-mass versus base-equivalent strength cannot be converted/accepted as the same descriptor without later sourced basis-relationship design; base-equivalent/biological activity/assay units and multiphase potency are unsupported canonical inputs initially. Preserve such values as personal/source text instead. No dose or stoichiometric conversion is performed.

DECIMAL(38,0) was chosen over BIGINT to hold bounded integer ratios exceeding 64-bit range, and over FLOAT/DOUBLE or a rounded concentration DECIMAL to preserve exact equality. Application arithmetic must operate on decimal strings with exact integer operations, not PHP floats or SQL division. Do not assume BCMath/GMP availability; a later implementation must verify/use an available exact mechanism or implement the bounded operations with tests, without an unapproved package dependency.

MariaDB documents exact fixed-point DECIMAL and precision up to 65 digits, making this type envelope plausible; it also documents coercion/rounding outside representable values, so the service must prevalidate before persistence. This is documentation-based design, not executed 10.4.27 compatibility proof. [MariaDB DECIMAL](https://mariadb.com/docs/server/reference/data-types/numeric-data-types/decimal).

## 11. Dosage Form Contract

Use controlled case-sensitive VARCHAR(32) strings with exact same allowlist in row CHECKs and the service. Initial supported codes: tablet, capsule, solution, suspension, cream, ointment. Localized labels live in existing application translation/view mechanisms; codes never change with language. No new form table or ENUM initially.

Bounded presentation support: tablet/capsule use denominator unit; oral solution/suspension use mL; cutaneous cream/ointment use g. This is a proposed identity-input envelope, not prescribing guidance or a declaration that broad form labels capture every clinically meaningful formulation detail. Evidence must substantiate each actual record, and unsupported distinctions remain unresolved. Do not infer form/route/release from a user's dose text.

Injections, drops/devices, inhalers, powders, ophthalmic/otic/nasal/other forms and extended composition characteristics are deferred until their exact distinctions are specified. They can still be recorded privately as text. This controlled set resolves the initial vocabulary question while avoiding a giant catalog or repurposing chemical forms.

## 12. Route Contract

Use controlled case-sensitive VARCHAR(32): initial codes oral and cutaneous. Canonical cutaneous has the display wording “Topical (cutaneous)”; no silent conversion of every free-text “topical” observation because that term can be insufficiently specific. One explicit route per presentation.

Initial allowed form/route pairs: tablet/capsule/solution/suspension with oral; cream/ointment with cutaneous. CHECK/service rules cover these local fields; missing/unknown/arbitrary route cannot form a presentation. No route is stored on substances. Personal actual-use route remains separate.

Other routes in the Phase 3.2 examples are deferred canonical scope, not lost personal logging capability. Extending supported routes requires an approved code and form/strength/release policy with tests; it is not a name normalization shortcut. No route reference table or ENUM is required initially.

## 13. Chemical Form / Salt Contract

Proposed adapter `kobi_medicine_chemical_refs`:

| Column | Type concept / required | Contract |
| --- | --- | --- |
| chemical_ref_id | BIGINT unsigned PK | One FK target for a resolved effective chemical reference; not a replacement substance registry. |
| substance_id | Nullable BIGINT unsigned FK to kobi_substances | UNIQUE when nonnull; exclusive with form_id. |
| form_id | Nullable BIGINT unsigned FK to kobi_substance_forms | UNIQUE when nonnull; exclusive with substance_id; base parent derived through existing form FK. |
| identity_status / metadata_revision | VARCHAR(16) / INT unsigned, required | candidate/verified/retired; stale metadata protection. |
| verification_reference / verified_by / verified_at | TEXT / signed INT user FK / UTC DATETIME(6), optional | Required for verified; exact entity and nonduplication must be evidence-reconciled. |
| created_by / created_at / updated_at | Signed INT user FK / UTC DATETIME(6), required | Attribution. |
| retired_by / retired_at / retirement_reason | Optional actor/time/text | Required for retired; historical pointers retained. |

Row CHECK enforces exactly one substance_id/form_id. Separate nullable UNIQUEs prevent two adapters to the same registry row. Index status + chemical_ref ID; explicit supporting FK indexes. FKs RESTRICT parent deletion/update. No duplicated canonical_name, base_id, chemical_type or normalized label; effective S/F tag is derived from which FK is populated.

This gives exact eventual FK usage: ingredient → chemical_ref → existing substance, or ingredient → chemical_ref → existing form → its parent substance. Direct substance_id is sufficient only when that existing row has an evidence-verified exact entity scope. A salt already represented as a concrete substance uses that substance ID; a genuinely distinct verified chemical form can use form_id. An underlying form-parent reassignment must not silently change an accepted adapter's semantics: governed writers reject it while referenced, and current resolution compares evidence/expected entity scope. Existing external registry writers remain a trusted-writer limitation until future registry governance is implemented.

Separate UNIQUEs cannot prove a substance row and a form row describe the same real entity. That requires evidence-backed reconciliation; only one representative is verified. No populated identity_type/status values are assumed. Until reviewed, the adapter remains candidate and cannot enter a verified composition/presentation/new canonical private association. Existing logging remains text. This is an explicit service/data-review gate, not an invented database chemical-equivalence guarantee.

No general substance relationship table or chemical ontology is required initially. Existing form-parent/components can supply provenance after semantic audit. Distinct active-moiety links, mass-equivalence bases, cross-entity supersession and registry merges require later relationship/evidence design; do not infer them from stoichiometry. No existing registry field/value is redefined or altered now.

## 14. Brand / Manufacturer / Product Contract

| Concept | Initial decision | Later scope |
| --- | --- | --- |
| Brand | Preserve private brand_name text; no shared entity required | Optional catalog naming when explicitly needed; not generic key. |
| Manufacturer / marketing organization | Deferred | Separate roles if commercial provenance is commissioned. |
| Commercial product | Deferred | References an approved presentation, with its own identifiers/market scope. |
| Pack | Deferred | References product/package quantity, never ingredient strength or patient dose. |
| SKU/product code | Deferred | Scoped source/market identifier; not brand-name uniqueness. |

Different commercial variants do not change generic composition/presentation identity when the full generic key is equal. No mandatory external identifier or commerce catalog is introduced. Existing mandatory brand_name is preserved in the first step, even though the canonical model itself needs no brand.

## 15. User Medication Integration

Sidecar `health_medicine_identity_links`:

| Column | Type concept / required | Contract |
| --- | --- | --- |
| health_medicine_id | Signed INT PK/FK | Zero or one sidecar per existing personal record. |
| association_mode | Case-sensitive VARCHAR(16), required | composition, presentation or ingredients. No row means raw-text mode. |
| medicine_identity_id / medicine_presentation_id | Nullable BIGINT unsigned FKs | Composition mode: identity only; presentation mode: presentation only; ingredients mode: neither. Row CHECK enforces exact shape. |
| link_revision | INT unsigned, required >=1 | Optimistic association revision. |
| created_by / updated_by | Signed INT users FKs, required | Actor attribution; no client ownership override. |
| created_at / updated_at | UTC DATETIME(6), required | Association times. |

UNIQUE health_medicine_id + association_mode supports the selection-mode FK; indexes identity/presentation reverse references and actor FKs. Derive composition from presentation; do not store both. Derive profile/user access from health_medicines/profile membership; no repeated user/profile owner column. Existing private deletion cascades sidecar deletion, while shared identity references RESTRICT deletion.

`health_medicine_selected_ingredients`: composite PK (health_medicine_id, chemical_ref_id); association_mode fixed to ingredients by CHECK; composite FK (health_medicine_id, association_mode) to sidecar; chemical_ref FK RESTRICT; display_order SMALLINT positive, UNIQUE within medication; optional observed_name VARCHAR(500); created_at UTC DATETIME(6). One to sixteen selections required by service when ingredients mode is committed; DB cannot enforce nonempty/count solely with row FKs. This private list is absent in composition/presentation mode and never owns global composition.

Future selection persists a validated substance ID by resolving its adapter, not storing only canonical_name. Explicit form selections resolve a form adapter. Only evidence-verified active references may become new canonical selections; if unresolved, retain user text without pretending an ID is verified. Personal logging must not auto-create global adapters/compositions or verify them.

All current text fields stay in health_medicines as recorded observations: brand/generic names, strength, dose, frequency, route, dates/prescriber/notes/status. Linked canonical labels/strength can be shown separately; mismatched observations cannot silently mutate the link. Text edit clears/reconfirms selection. Optional later generic/unbranded UI requires an explicit personal display-name/nullable-brand contract; do not relabel the current mandatory brand field during this schema-only step. Adherence, structured schedules and personal instruction fields are deferred.

## 16. Identity / Uniqueness Contract

| Invariant | Proposed DB enforcement | Service enforcement |
| --- | --- | --- |
| One adapter per exact registry reference | Exclusive FKs + separate UNIQUE substance/form | Scientific equivalence/reconciliation across the two tables; active verification. |
| One composition tuple | UNIQUE binary composition_hash | Recompute tuple from members; compare full payload on conflict; identity correction/version policy. |
| One membership per chemical entity in a composition | UNIQUE identity + chemical_ref | Alias/equivalence resolution, nonempty/count/order. |
| One presentation tuple | UNIQUE binary presentation_hash | Recompute normalized full strength/form/route/release tuple; compare exact payload. |
| One strength per member/presentation | Composite PK and scoped parent FKs | Complete vector, reduced ratio, correct chemical basis and denominator/form compatibility. |
| Controlled code identity | Case-sensitive column/check allowlist | Exact mapping; no silent new values, no locale-dependent equality. |
| Display names | No UNIQUE | Labels can repeat/change without identity changes. |
| Personal record | Existing row PK; sidecar PK; selection unique pairs | Explicit retry/stale contract; multiple medication courses can be legitimate. |
| Commercial uniqueness | Not implemented | Later source/market-specific model; never generic key. |

Hashes are reservation/index keys, not equality proof. A duplicate-key loser rolls back and rereads the committed owner using a current read, compares full canonical bytes and current status, then returns the existing ID only on exact equality. A different payload under the same digest is a collision: safe conflict, no merge and no overwrite. Never use a duplicate-key update to rewrite the winning identity. A retired identical owner reserves its key; no automatic restoration or duplicate creation to bypass retirement.

Within contract version 1, keys are stable. A future normalization version cannot coexist as a deduplication loophole: explicit reconciled re-key/cross-version mapping is required before enabling its writers. Stable tuple members cannot change in place. Service controls and transaction rules, not DB row CHECKs, enforce hashes matching aggregate rows and semantic immutability.

## 17. Foreign-Key Contract

All new shared-reference actions are RESTRICT for delete and update: IDs are stable; retirement keeps history. Active/verified eligibility is a service predicate, not an FK capability. Existing records may retain inactive/retired references and show their status; new canonical associations require current verified active dependencies.

| Parent → child | Cardinality / keys | Delete / active-history rule |
| --- | --- | --- |
| kobi_substances → chemical_refs | Substance 0..1 adapter, adapter chooses substance or form; exact signedness matches | RESTRICT; historical inactive substance retained, new verified use denied. |
| kobi_substance_forms → chemical_refs | Form 0..1 adapter; parent substance derives through existing FK | RESTRICT; current form and parent eligibility rechecked. |
| chemical_refs → ingredients | Ref 0..many memberships | RESTRICT; retirement not deletion. |
| identities → ingredients | Identity 1..16; scoped unique identity + ingredient ID | RESTRICT; no partial committed identity; complete count is service rule. |
| identities → presentations | Identity 0..many presentations | RESTRICT; parent must be verified/current for new verified variant. |
| (identity, presentation) → strengths | Presentation exactly one row per member | RESTRICT; composite FK scopes child. |
| (identity, ingredient) → strengths | Member many presentation amounts | RESTRICT; composite FK rejects wrong parent identity. |
| health_medicines → identity_links | Private record 0..1 link | CASCADE matches existing authorized physical delete; profile ownership unchanged. |
| identities / presentations → identity_links | Many personal references; XOR mode | RESTRICT; inactive parent history retained, no new active claim. |
| (health medicine, ingredients mode) → selected_ingredients | One link 1..16 selections in that mode | CASCADE on link removal; mode update restricted while child selections exist; service removes old private children before changing mode atomically. |
| chemical_refs → selected_ingredients | Ref many private selections | RESTRICT; inactive selections remain historical. |
| users → shared/private actor columns | Actor many attributed rows, signed INT types | RESTRICT; inactive actors can remain historical; new action requires active persisted actor. |

Existing form → substance FK remains unchanged. Proposed constraints do not turn SET NULL legacy components into canonical ones. No Treatment FK, private-to-public knowledge FK or source-governance table is added in the first step.

## 18. Normalization Contract

Canonical storage: stable underlying S/F IDs through adapters; sorted numeric membership; exact reduced ratios/canonical units; controlled form/route/release codes; versioned fixed-field UTF-8 serialization. Labels are separate display/source text and are not part of equality. Scope columns in composite FKs must always derive from validated parent rows.

New text search/display normalization uses valid UTF-8, NFC, explicit whitespace rules and search-only case folding where appropriate. Preserve chemical punctuation/digits/stereochemical/salt distinctions. No existing normalized-name column is rewritten. Unit codes are case-sensitive: exact aliases for microgram display characters may map to ug; arbitrary unit case folding is rejected. g/mg/ug and mL/L normalize only through approved exact conversion. Strength coefficients have no leading zeros, signs or decimal points in canonical serialization.

Human brand spelling remains personal text; normalized brand search is not product/generic equality. Form/route labels can localize independently of their stable codes. No prefix LIKE search decides scientific identity, and no free-text similarity score silently produces a canonical FK.

## 19. Legacy Data Contract

| Current data | Proposed destination / action | Safe automation? |
| --- | --- | --- |
| Discarded selected ID | New adapter-backed private selection on future explicit choice | Historical ID cannot be recovered from label alone. No automatic link. |
| brand_name / generic_name | Preserve original health_medicines wording; optional reviewed/confirmed link | Copy/preserve text only; do not invent brand/product/salt semantics. |
| strength | Retain personal/source text; optional complete verified presentation | Only after unambiguous per-ingredient amount/unit/basis/form/route/release mapping. |
| dose / frequency | Personal use fields unchanged | Never convert to canonical strength. |
| route | Recorded actual-use text unchanged; optional confirmed canonical route | Ambiguous “topical”/form-derived route must not be guessed. |
| status / dates / notes / prescriber | Personal row unchanged | No clinical approval inference. |
| Existing combination/component rows | Legacy/import evidence; later explicit source-to-canonical mapping | Requires complete reviewed chemical identities and strengths; names/nullable refs insufficient. |
| Existing forms/aliases/identity evidence | Preserve registry/provenance; verify adapter only after reconciliation | No automatic status-to-verification mapping or registry merge. |

Raw data preservation can be lossless; semantic canonical conversion cannot be guaranteed. Sidecar absence explicitly means unresolved text. Retain original values and mapping provenance; do not overwrite names/units or force every medication into catalog completeness. Summary/share snapshots remain frozen, and timeline continues to derive existing medication events until a separately reviewed change.

## 20. Persistence Contract

Controllers authenticate/authorize/CSRF, validate basic shape and fixed field allowlists, collect explicit expected revision and delegate. No controller/raw view SQL. New canonical responsibilities belong to a narrowly scoped MedicineIdentityService with BaseRepository-derived repositories; existing HealthMedicineService remains the personal-record entry point. Names are proposed responsibilities, not classes created now.

Service resolves active actor and identity authority, adapters/aliases, current dependency status; canonicalizes full aggregates; validates exact ratios/code compatibility; owns duplicate/equality/status rules and transaction boundary. Ordinary profile editors may link existing eligible identities/selections but cannot create/verify shared catalog entries. Initial schema-only rollout exposes no catalog writer or new verified autocomplete.

Repository handles parameterized aggregate retrieval, explicit columns, scoped parent/member joins, current locking reads, insert-only identity construction, metadata optimistic updates and private link writes. It returns typed safe outcomes through the service; PDO/raw constraint exceptions never become normal response text. Reuse the existing shared PDO/container; do not introduce another ORM/connection pattern.

Current resolution derives the canonical display/strength from the full relational aggregate and checks key consistency/status. Reads for presentation resolution must not assemble mixed membership/strength state; identity tuples are immutable and dependency verification uses a consistent read boundary. Clinical public-read service is deferred.

## 21. Transaction Contract

| Operation | Required atomic work / immutability |
| --- | --- |
| Register adapter | Authorized exact-reference check, parent current read, exclusive FK reservation and candidate metadata in one transaction; verification is separate authorized evidence action. |
| Create identity | Lock actor/authority and adapters in deterministic order, build complete tuple, reserve unique hash, insert all members, recompute aggregate, commit together. No committed empty/partial parent. |
| Add/update/remove ingredient | Not an in-place operation after identity construction. Create a new complete identity; maintain old references. No independent member CRUD route. |
| Create presentation | Lock actor/authority/dependencies/composition, validate full vector and codes, reserve hash, insert all strength rows and recompute key before commit. |
| Update strength/form/route/release | Resolve/create another presentation; no tuple overwrite. |
| Metadata / verify / retire | Current row/dependency locks, expected metadata_revision, safe status transition and attribution increment; no tuple edit. Retirement prevents new associations, keeps history. |
| Personal medication + link/selections | Recheck active profile/member/owner-editor under locks, lock actual medication, validate expected record/link state, update row and replace association/selection set in one transaction. No successful half-linked save. |
| Private mode change / delete | Lock parent/link; remove old private selection rows before mode change/remove link; roll back all on failure. Authorized parent delete cascades sidecars as specified. |

Service begins/ends transactions via the existing repository abstraction. Lock order for new workflows: actual actor/grant/profile/member authority rows in sorted IDs; chemical refs and underlying registry rows in a fixed S/F order; identity; presentation; actual private medication/link. Every implementing path must use the same declared order; retirement/verification coordinate on the same rows. Existing unrelated registry writers do not automatically gain this lock discipline.

Canonical duplicate races rely on UNIQUE reservations, not sequential lookup simulation. After duplicate failure roll back and use a fresh/current owner read; do not return an obsolete REPEATABLE READ snapshot. Deadlocks have bounded whole-transaction retry with recomputation; timeout/other infrastructure failures yield safe retryable errors and no partial write. Fault after parent/member/strength/private-link writes must roll back entirely.

Private raw rows currently have no reliable version field. Future integrated writes must compare a server-derived expected digest of all editable original values against the locked row, plus link_revision when a sidecar exists; do not rely on second-resolution updated_at alone. This can protect the updated integrated paths without altering health_medicines initially. Legacy writers remain outside that guarantee until all routes adopt it. No transaction or handler is implemented here.

## 22. Treatment Integration Contract

Future generic Treatment item reference targets Medicine Identity; presentation-specific content targets Presentation and derives identity/ingredients/substances. A generic substance statement need not invent a product/strength. Never link Treatment knowledge to private health_medicines IDs or patient doses.

Adding/changing links must participate in Treatment content revision/snapshot; medicine identity/presentation semantic supersession or source changes require reassessment rather than silent inherited approval. Independent domain governance and canonical current-reader gates remain necessary. Label-only versus semantic updates need explicit dependency policy. No FK, route, publication control or Treatment table/HOLD change occurs in this design.

## 23. Future Governance Attachment Points

| Scope | Future governed facts / ownership |
| --- | --- |
| Chemical adapter / substance-form relationship | Exact identity evidence and source verification, entity mapping/supersession, provenance history. Not therapeutic efficacy. |
| Medicine Identity / ingredient composition | Composition claims, member/effective entity integrity and identity verification history; clinical claims attach separately. |
| Presentation / strength member | Evidence for amount/basis/form/route/release and exact variant facts, dependencies and supersession. |
| Medicine knowledge content | Explicit subject scope (substance, identity or presentation), locale, purpose, applicability/limitations, clinical sources, evidence/risk, content revision, independent review/snapshot/approval/deadline, publication and append-only events. |
| Private medication | Personal facts and sharing/access; never inherited clinical approval or public catalog ownership. |

Reuse Treatment principles, not its tables or assumed ENUM meanings: persisted scoped authority, independent qualified review, immutable snapshots/history, current source/date/locale checks, explicit exposure mode and projection, stale-state locks and safe errors. Initial identity_status=verified means only evidence-reconciled identity facts; it does not approve indications, dosage advice, interactions or public knowledge. Clinical governance schemas/permissions/routes are deferred.

## 24. Vocabulary Strategy

| Domain | Initial controlled set / canonical storage | Expansion / unknown / localization |
| --- | --- | --- |
| Dosage forms | tablet, capsule, solution, suspension, cream, ointment; VARCHAR(32) exact codes | Add supported distinctions through reviewed checks/service contract; labels localize; no unknown canonical presentation. |
| Routes | oral, cutaneous; VARCHAR(32) | Other routes remain personal text until approved policy/constraints; never infer from name. |
| Strength input units | Mass g/mg/ug; volume mL/L; denominator unit/g/mg/ug/mL/L | Normalize to numerator g and denominator unit/g/mL; no activity, percentages without explicit basis, molar units or conversion across dimensions initially. |
| Release | immediate, delayed, extended, not_applicable; VARCHAR(32) | Tablet/capsule require explicitly evidenced immediate/delayed/extended; supported solution/suspension/cream/ointment require explicitly evidenced not_applicable. Missing is unresolved, never a default. Unsupported release nuance is deferred. |
| Ingredient role | active only, implicit | Excipients/form components/device parts do not enter initial composition. New semantic roles require key-version review. |
| Identity status | candidate, verified, retired; VARCHAR(16) | Complete candidates allowed; unknown denied. Metadata status is not clinical publication. |
| Private association | composition, presentation, ingredients; sidecar absence = text | Private fallback permitted; arbitrary modes rejected. |

Controlled strings are selected over ENUM (permissive coercion concerns) and reference tables (no present administration/catalog need). CHECKs are a backstop with exact nonnull/case-sensitive membership; service validates the same code set, length and combination policy before SQL. No global/project SQL-mode change is required or proposed. Unknown raw text is allowed privately, not inserted as a canonical code. Code synonyms and localized labels live in the established application mechanism; adding a code is a reviewed deployment change, not a user-created vocabulary record.

## 25. Minimum Viable Schema

### Required in first implementation

Seven new additive tables in section 5; keys/FKs/unique reservations, exact ratio/type/shape/code CHECKs, actor/timestamp/status attribution and composite parent scope. Existing schema assets are untouched apart from adding these proposed objects later. No seed/verified records, CRUD routes, migration, new search behavior or normal-database DDL is implied. Identity completeness/key consistency remain service requirements for the later persistence phase.

### Required later

Verified registry reconciliation and explicit identity authority; exact arithmetic/canonicalization and transaction services; optional private selection UI/persistence with raw fallback; controlled isolated success/failure/concurrency validation. Before clinical exposure: clinical source/evidence/review/publication/history model and canonical projection. Before Treatment links: cross-domain invalidation contract.

### Deferred

Commercial tables, giant vocabularies, general chemical relations, active-moiety/biological potency conversion, sophisticated formulation equivalence, automatic legacy backfill, clinical content and public rollout. Initial scope intentionally refuses unsupported variants rather than guesses.

## 26. Schema Evolution Strategy

Recommended sequence: Phase 3.3 design only; separately authorized Phase 3.4 additive schema foundation and isolated MariaDB compatibility/constraint validation; later persistence/canonicalization phase; then optional private selection integration; separately approved reviewed legacy mapping; clinical governance and public projection after those foundations; Treatment linkage last under its own scope.

Preserve existing logging throughout. New sidecars may remain empty; unresolved existing rows need no backfill to continue working. Do not reimport the full authoritative dump into the populated normal database as an upgrade. Developer applies an explicit reviewed additive change unless separately authorizing Codex execution. Rollback planning is reviewed before deployment; this document supplies no drop/rollback SQL.

MariaDB 10.4.27 compatibility gates: exact types/FK signedness/indexes; positive integer ratios/code XOR/null behavior under existing non-strict mode; unique nullable adapter reservations; composite scope/mode FKs; JSON/key bounds; safe concurrent UNIQUE conflicts. Avoid CHECK references to AUTO_INCREMENT row IDs, subqueries or aggregate rules. MariaDB's constraint documentation explicitly excludes AUTO_INCREMENT columns from CHECKs; aggregate completeness/hash/equivalence are service rules. [MariaDB constraints](https://mariadb.com/docs/server/reference/sql-statements/data-definition/constraint).

No DDL was executed for this design, so readiness is not a claim of tested syntax. Proposed storage choices are concrete enough for DDL generation and isolated validation in a separately authorized task. Do not silently improvise around compatibility failures or weaken other constraints.

## 27. Data Migration Strategy

Safe automatic work is preserving existing rows/text and creating empty additive structures when authorized. No populated adapters/compositions/presentations are seeded from flags/names alone. Existing aliases/combination/form records require read-only aggregate audit, evidence reconciliation and reviewed mappings.

Explicit future selection can persist a current verified substance/form ID. Historical discarded IDs are irrecoverable without confirmation. Fully unambiguous reviewed mappings may be eligible for a scoped backfill with provenance and rollback review; ambiguous names, comma tokens, salts, strength units/bases, form/route/release and product assumptions remain text. User confirmation concerns personal observation; trusted identity review concerns shared canonical facts. Neither substitutes for the other.

Mapping must preserve source record ID/raw values and destination scope; do not rewrite old summary snapshots or claim every raw row maps to a verified presentation. No migration operation is performed here.

## 28. Security Contract

Authenticate actual active actor; profile access through HealthProfileRepository::findAccessible via existing service; owner/editor for personal changes, separate persisted catalog authority for shared edits/verification. User-supplied owner/profile/verified fields are never mass assigned. Verify exact row/profile and all sidecar selection ownership; CSRF on state-changing HTTP requests, fixed local redirects, explicit field allowlists and bound repository values.

Validate identifier strings, UTF-8 scalar shapes/lengths, quantities before decimal coercion, allowed codes, active verified canonical refs, parent/mode completeness and stale tokens. No SQL mode dependence, ambiguous label auto-linking, candidate-as-approved search or clinical approval from identity verification. Private text logging remains available.

Return safe 403/409/422/503/500 outcomes appropriate to deliberate denial, stale/duplicate conflict, invalid data, infrastructure and unexpected failures. Log only necessary sanitized diagnostics, not personal medication content/SQL/secrets. Roll back all failed writes; full identity key recomputation and current locks support integrity. XSS-safe text rendering/textContent and external JS/CSP are preserved.

Phase 3.1 raw exception/date defects remain uncorrected and are prerequisites to extending affected existing handlers. No new medicine exposure or authorization grant is introduced by an empty schema proposal. Exact identity verification permissions require review before a writer UI/API is enabled.

## 29. Design Options Considered

| Choice | Advantages / compatibility | Disadvantages / complexity / migration | Governance impact / decision |
| --- | --- | --- | --- |
| Adapt existing combinations/items as canonical composition | Fewer new tables, keeps imported IDs | Must reinterpret name-based uniqueness, nullable IDs and strength-bearing component data without live reconciliation; would mix imported and canonical authority | Reject for first foundation; preserve legacy evidence and map later. |
| New composition/member store, preserve imported combinations | Additive, explicit deterministic ownership and no existing behavior change | New two-table composition owner; later reviewed mappings required | Recommended; no dual authoritative model. |
| Repeat nullable substance/form pair on every membership/selection/basis | Fewer adapter table joins | Duplicate resolution/status logic everywhere, contradictory parent pairs easier | Prefer one adapter with concrete FKs; no uncontrolled type/id pair. |
| Fixed rounded decimal concentration | Easy scalar math/storage | Can collapse distinct ratios or fail repeating values; identity precision unreliable | Reject; exact bounded N/D coefficients. |
| Controlled strings versus vocabulary tables | Small stable scope, easy localization using existing UI mechanism | CHECK/service updates needed for code expansion | Strings now; lookup entities only if managed vocab administration is commissioned. |
| Extend health_medicines with multiple optional identity columns | Fewer sidecars | Changes legacy schema/writers immediately; conflicting redundant links and selections | Recommend sidecars now; preserve raw record and derive parent association. |

No commercial-catalog alternative is needed for initial scope. Implementation complexity must not justify silently losing chemical/strength distinctions.

## 30. Recommended Architecture

Recommend the seven-table additive model: one typed chemical adapter, one composition/member owner, presentation/member-strength owner, and optional private association/selection sidecars. Preserve the existing registry and personal log; old combinations remain provenance staging. Use exact ratios and controlled code columns with CHECK/service validation. Clinical governance attaches later by explicit subject scope.

**Design status: READY WITH OPEN DESIGN QUESTIONS.** The schema contract settles entity ownership, column/type envelopes, vocabulary, quantity precision, FK scope, duplicate and transaction behavior. Remaining factual catalog reconciliation and exact 10.4.27 DDL testing are gates to verified data/persistence rollout; actual authority/acceptance scope needs developer review. None requires rewriting the approved conceptual model. Existing Phase 3.1 safety defects do not block creating empty additive schema, but do block extending their affected flows until corrected separately.

## 31. Required First Implementation Scope

If separately authorized, Phase 3.4 should implement only the seven additive schema objects and documented constraints/indexes, without changing existing tables/rows/routes/config/HOLD. Start with empty tables; no automatic verified adapters or name-based seeds. Prepare a reviewable additive application/deployment plan distinct from the full schema dump, and validate exact DDL/metadata/invalid values in a disposable MariaDB 10.4.27 environment only under explicit authorization.

Required future validation cases: both/neither adapter FK, duplicate underlying adapter; duplicate/reordered composition; UNIQUE race winner/current reread; missing/wrong-parent strength; zero/fractional/overflow integer ratios and precise normalization boundaries; unknown/malformed codes and invalid form-route-release/denominator; mode XOR and wrong-mode private child; forbidden shared parent deletion versus private cascade; candidate/inactive canonical association denial in services; rollback at each construction step. These are proposed validation requirements, not tests executed here.

Do not claim DB row constraints enforce nonempty/full vectors, semantic equivalence, immutable tuples, key correctness or trusted-writer discipline. Those service and governance requirements must be implemented/validated before accepting canonical data. No first-step publication/clinical content feature is required.

## 32. Later / Deferred Scope

Later: catalog verification authority/history, exact service arithmetic/keys and atomic construction, optional private selection UI/persistence, reviewed registry/form/combination mappings and new medicine regression suites; then medicine knowledge/source/evidence/risk/review/publication/history; then public projection and separately authorized Treatment links. Separate correction tasks address Phase 3.1 error/date/input issues.

Deferred: product/brand/manufacturer/pack/SKU tables, chemical ontology, biological activity/base-equivalence calculations, complex release/device/excipient variants, additional routes/forms, automatic clinical recommendations or dose calculations, registry merges, normal-data migration and public rollout. No additional documents/artifacts/scripts are created in this task.

## 33. Open Questions

| Remaining item | Resolved design / required follow-up | Gate |
| --- | --- | --- |
| Actual substance/form/combination semantics and duplicate scientific identities | Adapter + new canonical owner chosen; read-only populated-data audit and evidence review still required | Verified adapter import/selection; not empty schema definition. |
| Exact MariaDB 10.4.27 syntax/metadata/coercion results | Concrete types/constraints specified; isolated validation needed, no compatibility claim from this design alone | Schema deployment approval. |
| Identity verifier authority and provenance/history workflow | Separate from profile editor and clinical publisher; no writer enabled initially | Shared catalog verification/persistence rollout. |
| Acceptance of initial limited form/route/unit/release envelope | Explicit proposed sets and fail-closed fallback settled; developer/domain review before canonical use | Presentation acceptance; extension only by reviewed contract. |
| Exact arithmetic mechanism available in PHP deployment | Bounded decimal-string N/D contract settled; verify tool/extension availability or implement tested exact bounded helper | Persistence implementation, not DB type design. |
| Later unbranded UI and migration confirmation UX | Private sidecars/raw preservation chosen; mandatory legacy brand unchanged | Separate private UI/data changes. |

No unknown code, status, registry relationship or chemical equivalence is treated as verified while these gates remain. No unresolved conceptual identity/schema blocker was identified; outstanding evidence/authority/compatibility approvals are stated explicitly.

## 34. Proposed Phase 3.4

Request a separately authorized **additive Medicine schema foundation implementation and isolated compatibility validation** against this document: seven new tables only, no existing-table/data modification, no seeds/catalog approval, no PHP/UI/routes/Treatment/HOLD/config change and no normal-database application without explicit authority. Stop/report exact incompatibilities rather than redesigning silently. A later persistence phase must implement service invariants and safety prerequisites before canonical data or private-link behavior is enabled.

Task validation/accounting: source/schema/routes/tests and prior designs inspected; static diff check; all 34 required sections and absence of executable SQL checked; final task-start SHA256 inventory comparison. Only `docs/MEDICINE_PHASE_3_3_SCHEMA_DESIGN.md` is created; no existing file modified/deleted, pre-existing changes untouched. No database connection/writes/SQL-mode changes and no Git commit/push/reset/checkout/merge/rebase. No implementation or runtime compatibility test is claimed.
