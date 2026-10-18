# Medicine Knowledge Foundation — Phase 3.1 Preflight Audit

## 1. Audit Scope

Date: 2026-10-09, Asia/Calcutta. This is a read-only repository/schema preflight, not a Medicine implementation or a clinical-content review. Only this document was created. No application, test, schema, configuration, route or Treatment governance change was made; Treatment exposure remains on HOLD. No medicine records, fixtures, schema upgrades, SQL-mode changes or Git history mutations were performed.

Inspected current medicine/substance repositories and services, AccountController medicine/search handlers, profile authorization, medicine views/JavaScript, global layout/CSP/AJAX conventions, summary/timeline/share flows, routes, authoritative `database/structure.sql`, test discovery and Treatment closure/public-governance references. Beginning Git status/diff showed substantial pre-existing authentication/Treatment changes; those were preserved. Paths below are relative to `D:/xampp/htdocs/kobi/`.

A narrowly scoped aggregate substance-catalog inspection was attempted through the existing Database class with a planned read-only transaction and SELECT-only queries. Connection construction fad with RuntimeException, so no catalog queries or transaction statements executed. Exception details and credentials were not printed. No personal medicine rows were requested. Live catalog counts, identity types/status distributions, duplicates, orphan relationships and the supplied Silodosin example therefore remain **NOT VERIFIED**. Schema AUTO_INCREMENT counters are not row counts or proof of populated data.

Validation completed: 20 inspected PHP files passed syntax checks; `git diff --check` passed; route/reference/schema searches and test discovery completed. A database-free invocation of the existing date-normalization method confirmed today's-date rejection (section 12). No browser, HTTP mutation test, disposable database or existing live fixture script was run. No medicine-specific regression suite was discovered. Final file hashes were compared against a task-start inventory; see file accounting below.

## 2. Existing Architecture

There are two separate foundations:

1. **Personal medication records:** `health_medicines`, owned through an accessible health profile, provide medication history and current medication details.
2. **Substance identity scaffolding:** `kobi_substances` and related aliases/forms/combinations/reviews/WHO naming-evidence structures exist in the schema. Current application integration is limited to canonical-name autocomplete.

Real paths:

```text
AccountController medicine methods
  → HealthMedicineService
    → HealthProfileService → HealthProfileRepository::findAccessible
    → HealthMedicineRepository → health_medicines

AccountController::searchSubstances
  → SubstanceService::search
    → SubstanceRepository::search → kobi_substances
```

Existing BaseRepository supplies parameterized PDO queries; the reflection container provides dependencies. No separate medicine model/data-object/catalog controller or medicine knowledge/publication service was found. MedicalSummaryService and HealthTimelineService read HealthMedicineRepository after profile access checks. MedicalSummaryShareService freezes a selected medication projection into an owner-created bearer-token share.

Preserve Controller → Service → Repository → Database, AuthService::id(), active profile-membership checks, owner/editor mutation rules, csrfField()/verifyCsrf(), existing `$this->view()`/redirect(), Bootstrap forms, and external global/page JavaScript. SymptomChecker's JSON GET search and `window.KOBI.apiBase` are the established AJAX pattern; they are not a medicine knowledge or identity-governance layer.

## 3. Database Inventory

Authoritative reference: `database/structure.sql`. There are nine directly relevant physical tables (eight identity tables plus the personal medication table) and one search view. Additional profile/share/Treatment tables are integration context. The identity tables use utf8mb4_unicode_ci; health_medicines uses utf8mb4_general_ci. No SQL was applied.

| Object / purpose | PK and important columns | FKs / unique constraints / indexes | Status, provenance, review, publication and ownership |
| --- | --- | --- | --- |
| health_medicines — personal medication entry (lines 360–380) | id; health_profile_id; brand_name varchar(255), generic_name varchar(500), strength/dose/frequency varchar(255), route varchar(100); started_on, stopped_on, prescribed_by, notes | Profile FK with DELETE CASCADE; indexes profile and status; no logical medicine uniqueness or substance/product FK | status ENUM current/stopped/historical; created_at/updated_at; ownership through profile, no record actor/reviewer/source/publication fields |
| kobi_substances — canonical named entity (621–640) | substance_id; canonical_name and canonical_name_normalized; naming_standard; identity_type; description | Unique normalized name; composite active/searchable/name index; identity_status index; no reviewer FK | Defaults WHO_INN, UNKNOWN, CANDIDATE; active/searchable default 1; verification_source text, reviewed_by bigint, reviewed_at; created_at/updated_at; no owner/publication state |
| kobi_substance_aliases — alternate/source names (573–592) | alias_id; nullable substance_id; alias_name/normalized; alias_type; source_name/key/reference | Substance FK SET NULL; active/search/name, substance, source indexes; no alias uniqueness | SOURCE_NAME/PENDING defaults, active/searchable flags; source_reference text; created_at/updated_at; no reviewer/publication/user ownership |
| kobi_substance_forms — named form of a substance (598–615) | form_id; substance_id; form_name/normalized; form_type/status; canonical_form_name | Substance FK, UPDATE CASCADE; unique substance_id + normalized form; active/name and substance indexes | UNKNOWN/CANDIDATE defaults, verification_source text, active flag, created_at/updated_at; no independent review/publication fields. Not demonstrated to be dosage forms. |
| kobi_form_components — composition of a named form (521–539) | form_component_id; form_id; component_type/name/normalized; nullable component_substance_id; stoichiometry decimal(12,6) | Form FK DELETE CASCADE; component-substance FK SET NULL; form/substance/name indexes; no component uniqueness | PENDING verification_status, evidence_reference text, created_at/updated_at; no reviewer/publication/ownership |
| kobi_combinations — named combination identity (501–515) | combination_id; combination_name/normalized; source_name/key | Unique normalized combination name; active/search/name index | PENDING review_status; active/searchable flags; created_at/updated_at; no linked source lifecycle, approval/publication/owner |
| kobi_combination_items — multiple components (476–495) | combination_item_id; combination_id; nullable substance_id and form_id; component_name/normalized; sequence_no; strength_text varchar(100) | Combination FK CASCADE; form and substance FKs SET NULL; parent/substance/form indexes; no ingredient/order uniqueness or joint form/substance coherence constraint | PENDING review_status; created_at/updated_at; textual strength; no reviewer/source/publication/ownership |
| kobi_identity_reviews — candidate identity decision (545–567) | review_id; source_name; nullable alias, candidate substance and candidate form IDs; decision/reason/evidence | Three identity FKs SET NULL; source/alias/substance/form/decision indexes; no reviewer FK | PENDING decision; reviewed_by/reviewed_at, created_at/updated_at; no qualification/independence/snapshot/revision/publication or append-only enforcement |
| kobi_who_inn_evidence — naming provenance (646–667) | evidence_id; nullable substance_id; inn_name/normalized/status; list_number/date; publication_url/pdf_url/page_reference; source_sha256 | Substance FK SET NULL; name/substance/INN-status indexes; no unique source record | PENDING verification_status; notes, created_at/updated_at; no verified actor/date, source withdrawal/replacement or publication fields. Naming evidence is not a therapeutic-evidence classification. |
| kobi_substance_search — search view (1087) | No PK; entity_id, alias_id, display_name, search_name, match_type, review_status | UNION ALL canonical/alias branches; SQL SECURITY DEFINER, root@localhost dump definer | Canonical branch filters active/searchable; alias branch filters alias flags and non-null substance_id, but not parent substance flags or reviewed status. Not used by current autocomplete repository. |

Identity/review/source status and type fields above are VARCHAR, not controlled ENUM/CHECK taxonomies. Apart from documented defaults, accepted values cannot be inferred from the schema; no application write validator for these identity tables was found. All nine tables have created_at/updated_at timestamps; none supplies Medicine content revision, approved-review pointer, published locale or review due date.

Context: health_profiles and health_profile_members connect a medication entry to active profile/member roles; the medicine record has no direct user_id. medical_summary_shares stores a profile/creator, unique token_hash, snapshot/schema version, expiry and active/revoked state with user/profile FKs and profile/creator/status-expiry indexes. medical_summary_share_access_logs records share FK, access time/IP/user-agent with share/date indexes. Their timestamps/token-share lifecycle govern private sharing, not medicine knowledge approval. Treatment items have their own protocol/source/governance relationships but no medicine or substance FK.

## 4. Medicine Application Flow

| Flow | Actual implementation |
| --- | --- |
| List | GET `/account/health-records/{profileId}/medicines` → healthMedicines → accessible profile + getMedicines → findByProfileId; current first, then stopped/historical, started_on DESC/id DESC; view groups the three statuses. No pagination or medicine-record search endpoint found. |
| Create form | GET `.../medicines/create` → createHealthMedicine; authenticated accessible owner/editor; renders brand/generic/strength/dose/frequency/route/status/dates/prescriber/notes. |
| Create persistence | POST `.../medicines` → storeHealthMedicine; verifyCsrf, explicit eleven-field input selection → createMedicine; service assigns profile ID after editable access check → explicit INSERT → fixed local redirect. |
| Edit/detail | GET `.../medicines/{medicineId}/edit` → editHealthMedicine; profile role plus getMedicine verifies returned row belongs to that exact accessible profile. List cards expose the recorded details; no separate medicine knowledge detail page found. |
| Update | POST `.../{medicineId}/update` → updateHealthMedicine; CSRF, same field allowlist, editable profile and row/profile association → UPDATE by ID. No expected revision/version or lost-update detection. |
| Delete | POST `.../{medicineId}/delete` → deleteHealthMedicine; CSRF, editable profile and row association → physical DELETE. Stopped/historical are user statuses, not soft deletion or immutable history. |
| Salt/generic suggestions | GET `/api/substances/search?q=...` → searchSubstances → SubstanceService → canonical-name prefix search, active/searchable, limit clamped 1–10. Blank query returns []; no authentication check in this handler. |
| Selection | `public/assets/js/health-medicine.js` debounces 300ms, starts search at two characters, uses GET JSON/API base and textContent; selections append/replace comma-delimited names. substance_id from the API is discarded. |
| Summary/timeline | Profile-checked services read personal records; timeline derives start/stop events from mutable rows. Summary selects current medicines. Owner-created shares copy current medicine fields into an expiry/revocation-controlled snapshot. |

Current autocomplete supports several comma-separated selected names, despite the earlier single-selection observation. It also allows arbitrary text. There is no hidden selected identity, server-side substance lookup, ingredient parser, per-ingredient amount or persisted join. Client-side duplicate prevention is case-insensitive within the preceding comma-separated names; it does not prevent duplicate medication rows or server-submitted duplicate ingredients.

Service validation requires nonempty brand_name, controlled current/stopped/historical status, valid nonfuture dates, stopped date for stopped status, no stop date for current status, and chronological date order. Optional fields are trimmed to text/null. It does not validate scalar shape, server-side lengths, generic identity, strength/unit/concentration, route taxonomy, duplicate medication, or clinical dosing. TODAY currently fails the date rule incorrectly (section 12).

## 5. Substance / Salt Foundation

`substance_id` is the canonical entity identifier; canonical_name is its label. Unique canonical_name_normalized prevents duplicate stored normalized strings under the database collation. It does not prove equivalent scientific identities are deduplicated or that two variants normalize consistently. No normalization/import algorithm was found: autocomplete trims the query and applies prefix LIKE to the normalized column, without transforming punctuation/spacing or escaping SQL LIKE wildcards. Case comparison depends on collation. Bound query parameters prevent SQL injection; wildcard broadening is a search behavior, not SQL injection.

naming_standard defaults to WHO_INN but is arbitrary nullable text. identity_type/status default UNKNOWN/CANDIDATE and are arbitrary non-null text; active/searchable defaults allow candidate rows to be suggested. Current search does not require an approved/verified identity_status, verification_source or reviewed fields.

verification_source is optional text, not a mandatory verified-source relationship. reviewed_by/reviewed_at are optional and lack FK/qualification/independence/coherence enforcement. Aliases, identity reviews and WHO evidence provide useful schema scaffolding, but no corresponding administrative write/review workflow was found. Aliases are not queried by the current autocomplete, and duplicate alias/source records are not uniquely prevented.

Named substance forms, form components and stoichiometry could support a future explicit base/form/composition distinction. The current schema does not enforce a salt/base taxonomy, salt equivalence or chemical identifier mapping, and there is no separate parent-base/active-moiety column. No CAS, InChI, SMILES or comparable chemical-identity field was found. Actual form_type/identity_type values and populated relationships are unverified because the database was unavailable.

The UI calls canonical substances “Salt / Generic Name”; this conflates display terminology. It does not establish that every canonical substance is a salt, that a generic name equals a particular salt, or that WHO naming evidence establishes medicine efficacy. Do not silently relabel or reinterpret existing data. The supplied substance_id 1861 / Silodosin remains a user-observed example, not an independently confirmed record in this audit.

## 6. Medicine Identity Model

There is no single canonical marketed medicine identity. A “medicine” in the implemented personal flow is a health_medicines.id row under a profile with mandatory brand text and optional other text. A substance is separately a kobi_substances.substance_id. A combination has its own combination_id, without a medication-record link.

| Question | Current capability / gap |
| --- | --- |
| One medicine → one substance | A generic-name string can contain one name; no identity FK or enforced mapping. |
| One medicine → multiple substances | Comma-separated free text in health record; separate combination/items schema supports multiple nullable identities, but no integration between them. |
| Substance → multiple medicines | Multiple records may reuse the same text; no relational link or catalog query. |
| Uniqueness | Personal row PK only; substance/combination normalized names unique; form name unique within substance. No canonical ingredient + strength + dosage-form + route + product uniqueness. |
| Same substance, different strengths | Free text can represent this in separate records/combination items; no structured variant relation. |
| Multiple formulations/routes | Route string per record; no formulation/variant model. Named substance forms cannot be assumed to mean tablet/capsule products. |
| Brand versus generic | Two separate text fields; mandatory brand_name does not reference a product. Generic-only recording requires filling that mandatory field; no verified distinction. |
| Salt versus ingredient | UI terminology and free text do not enforce the distinction; substance/forms/composition need explicit semantics. |
| Combination medicines | Existing combination schema can hold components/strength_text, but nullable IDs, independent form/substance references and no canonical composition constraint leave unresolved semantics. |

Safe reuse means preserving personal medication records and stable substance IDs while defining an explicit knowledge identity contract first. Do not promote personal rows to reviewed catalog entries or discard the existing identity scaffolding without reconciliation.

## 7. Strength / Potency

Strength is **not absent**. health_medicines.strength stores text such as the UI's `8 mg`; dose is separate text with `1 capsule` as an example, frequency is text, and kobi_combination_items.strength_text allows component-specific text. These are not structured quantities/units. No numerical potency, strength unit, denominator volume, concentration, quantity-per-dosage-form, unit conversion, release profile or multi-strength medicine-variant relation was found.

Multiple combination ingredients can each carry strength_text, but those values are not unit-validated and the combination is not connected to a personal medicine/product. kobi_form_components.stoichiometry is a composition coefficient, not proof of an administered dose or product potency model. Notes/dose text may describe concentration or quantities but cannot support reliable calculations/comparison or independent active-ingredient validation. Do not calculate dosing or infer base/salt equivalence from these fields.

## 8. Formulation / Route

health_medicines.route is optional free text, shown as “Oral” in form examples. There is no controlled route vocabulary, route FK or validation for oral, topical, intravenous, intramuscular, subcutaneous, inhaled, ophthalmic, otic, nasal, rectal, vaginal or other routes. These may be typed, but that is not a structured route model.

No dosage-form column/catalog was found for tablet, capsule, syrup, suspension, injection, cream, ointment, drops, inhaler, powder, solution or other presentations. “1 capsule” in dose is an example, not a capsule entity. kobi_substance_forms stores named substance forms with UNKNOWN/CANDIDATE defaults; actual chemical-versus-dosage-form meaning is undocumented and live data is unverified. Resolve that terminology before reusing the table for medicine formulation.

## 9. Brand / Product / Manufacturer

brand_name is required personal-record free text, not a normalized brand or marketed-product entity. No product/brand/manufacturer/package/SKU/product-code table, FK, dosage-form/strength variant or package quantity/unit model was found. prescribed_by is a recorded prescriber, not manufacturer. source_record_key in identity tables identifies source records, not an established commercial SKU. A full commercial drug database is not established by the current roadmap; reserve this as a later scoped decision rather than assume it is required for the initial foundation.

## 10. Medicine Knowledge Content

| Category | Current location / structure | Sources / review / publication |
| --- | --- | --- |
| Uses/indications; contraindications; precautions/warnings; adverse effects; interactions | No dedicated medicine knowledge fields/workflow found; optional personal notes/substance description could contain arbitrary text without defined semantics. | No medicine knowledge source linkage, review or publication state. |
| Dosage / administration | Personal strength/dose/frequency/route text; combination strength_text. Records of use, not approved prescribing knowledge. | No clinical-source/review/publication enforcement. |
| Storage; pregnancy/lactation; monitoring; patient education | No structured categories or editorial content model found. | Absent as governed categories. |
| Mechanism / pharmacology | Optional kobi_substances.description only; no dedicated category, localization or validated content workflow. | Optional verification_source/identity review metadata does not approve therapeutic content. |

No medicine multilingual knowledge version, indication-specific recommendation or public medicine monograph was found. Treatment item's localized instructions/title may discuss a medicine independently; those remain Treatment content, not a canonical medicine knowledge record.

## 11. Source / Evidence / Review Governance

| Concept | Medicine foundation now | Treatment lesson / reuse decision |
| --- | --- | --- |
| Source records / verification | WHO naming-evidence rows and free-text source references/statuses; no governed clinical-source lifecycle service. | Reuse provenance lifecycle principles; distinguish identity evidence from therapeutic evidence and product labeling. |
| Evidence classification / risk eligibility | No medicine classification or risk-eligibility field/predicate. | Specify medicine-relevant evidence/risk gates; do not mechanically copy Treatment ENUMs. |
| Purpose / applicability / limitations | No medicine knowledge fields; notes/description unstructured. | Define content scope/audience/limitations before authoring. |
| Review / qualification / independence | Candidate/PENDING strings, reviewer IDs/dates and identity reviews; no enforced qualified independent approval workflow. | Reuse persisted scoped authority and independent review, separately defining identity and clinical approval. |
| Revision / snapshot / approval | No medicine content revision, canonical snapshot/hash or approved event pointer. | Reuse immutable revision-bound approval concepts. Private share snapshot is not medicine knowledge approval. |
| Publication / locales / due date | No medicine publication state, approved locales, deadline or canonical exposure guard. | Require fail-closed projection and explicit exposure policy before public knowledge. Treatment HOLD must remain separate and unchanged. |
| History / invalidation / replacement | Mutable identity review rows; no append-only governed events or source withdrawal/replacement cascade found. | Define history, stale-state locks and invalidation before editorial/public workflows. Timeline-derived personal events are not append-only governance. |

Identity verification must not automatically certify clinical content. Reuse the architecture and lessons established in `TREATMENT_PHASE_2D_CLOSURE_AUDIT.md`, not Treatment table identities, permission scope or therapeutic eligibility assumptions.

## 12. Security / Authorization

**Verified strengths:** medicine handlers authenticate; HealthProfileService uses AuthService::id() and findAccessible with active profile/member constraints. Reads require membership. Mutations require owner/editor and exact row-to-profile association. Controllers select explicit fields, services assign profile ID and repositories bind values. Forged user/profile ownership fields are not mass-assigned. Mutation routes are POST with CSRF forms/verification; redirects use fixed local paths. Displayed medicine/notes/errors use e()/nl2br(e()); suggestion labels use textContent. No static IDOR, SQL injection or stored-XSS bypass was identified in these paths. This is static inspection, not proof of every runtime/race condition.

**Confirmed and important gaps:**

- **M31-01, MEDIUM — unsafe exception disclosure.** AccountController store/update catches Throwable and passes getMessage() directly to medicine views (lines 596/753). A PDOException from BaseRepository can reveal SQLSTATE/internal table/query details even when debug mode is off; escaping prevents XSS, not disclosure. sharedMedicalSummary likewise passes raw errors to a public error view (around 2480). No HTTP fault was injected; the source-to-view path is direct. Connection construction failures can escape before controller invocation and depend on the global handler, so do not claim every connection error follows this catch. Independently authorize safe error-boundary correction before extending these flows.
- **M31-02, MEDIUM — today rejected as future.** HealthMedicineService::normalizeDate uses DateTime::createFromFormat('Y-m-d', value), retaining current time, then compares against DateTime('today') at midnight. A database-free call to the actual method rejected `2026-10-09` as future, accepted `2026-10-08`, and rejected `2026-02-30` as invalid. This blocks recording a medicine started/stopped today during ordinary daytime execution. No method or test was changed.
- **M31-03, MEDIUM — incomplete server input validation.** Optional and required fields cast mixed input to strings without scalar/type/length validation. UI maxlength is not server enforcement; arbitrary array payloads can produce warnings/“Array” strings, and oversized values reach SQL. Under permissive mode truncation is a possible data-quality failure, not an observed live write here. Strength/route remain deliberately unstructured, but basic transport/column constraints should not depend on the browser/SQL mode.
- Autocomplete has no authentication/review gate and no server query-length minimum/maximum; q arrays cause a TypeError at trim. It returns only shared substance ID/name, not personal medicines. Anonymous names are an existing exposure decision, not evidence of personal-data IDOR. Require a defined identity-search eligibility/error contract before treating suggestions as verified knowledge.
- No row revision or transaction spanning profile-grant checks and medicine writes was found. Do not claim atomic permission-revocation/lost-update protection; establish required concurrency behavior and tests before adding knowledge workflows.
- **LOW — CSP/UI mismatch.** Edit view contains an inline script duplicating external status behavior; its computed hash differs from the sole allowed CSP hash. Delete forms use inline onsubmit confirmation, blocked by script-src without unsafe-inline. External health-medicine.js supplies status behavior, but deletion confirmation should not be relied upon under CSP. No browser behavior was tested.

No medicine-specific tests were found for ownership/roles, CSRF, safe errors, today's date, scalar/length validation, substance selection or concurrent updates. Existing auth/Treatment suites do not establish those medication invariants and were not rerun as substitute coverage.

## 13. Public / SEO Exposure

No public medicine catalog/monograph, medicine SEO route or medicine structured-data renderer was found. Personal medication list/edit endpoints are authenticated/profile-scoped.

Two external exposure paths exist:

1. `/api/substances/search` is an unauthenticated GET and returns active/searchable canonical names and IDs without identity-status verification. It does not return clinical content or profile medication data. Candidate defaults can be searchable; actual served row statuses were not observed.
2. `/shared/medical-summary/{token}` is a deliberate bearer-token sharing feature. An authenticated profile owner creates a frozen current-medication snapshot using random token/hashed token lookup, expiry and revocation. The selected medicine fields include brand/generic/strength/dose/frequency/route/status/dates/prescriber/notes. The public controller applies noindex/nofollow/noarchive, no-store and no-referrer headers. This is patient-authorized sharing, not knowledge publication approval. Token possession permits access; SEO headers are not authorization. Successful retrieval records access, which is why no share was opened during this audit. Its raw error disclosure is included in M31-01.

Future knowledge projection must remain separate from personal medication/sharing data. Do not reuse medical-summary sharing as a public medicine knowledge boundary, and do not infer a medicine HOLD from the existing Treatment HOLD.

## 14. Relationship With Treatment Knowledge

Treatment item_type includes medicine; item fields contain localized title/instructions/limitations and governed sources. No medicine_id, substance_id, combination_id or product FK exists in the Treatment item schema or its snapshot/projection field lists. Relationship today is free-text content, not identity linkage.

A future foundation can connect through explicit stable knowledge identities after their model/governance is approved. It must not point public Treatment knowledge to private health_medicines IDs. Define how linked identity/content changes affect Treatment revision/snapshot and source/current eligibility; a link must not implicitly publish either domain. No treatment schema, public reader, governance or HOLD change is recommended for execution in this audit.

## 15. Data Quality / Identity Risks

Live data was unavailable; duplicate canonical identities, inconsistent stored normalization, ambiguous salt mappings, missing verification, inactive referenced substances, orphans, incomplete combinations and real unit inconsistencies were not measured. No sample data or personal records were fabricated/read. The planned aggregate inspection stopped on connection failure and printed only the exception class.

Schema/application risks are independently visible:

- Unique normalized substance/combination names protect stored keys but not scientific identity equivalence. Aliases/evidence/components lack logical uniqueness. No documented normalization writer exists.
- Candidate/PENDING defaults and flags do not establish verified identity; reviewer/source fields can be absent or incoherent.
- Null ingredient/form IDs and SET NULL FKs allow unresolved/detached catalog components by design. Combination form_id and substance_id can point to inconsistent parents; no composite invariant prevents it.
- Comma-separated personal generic text cannot enforce ingredient count/order/identity/strength or follow renamed/inactive substances. There is no persisted reference to become an orphan, but also no referential integrity.
- No uniqueness prevents repeated medication entries; retries/concurrent POSTs can create duplicates. Exact duplicates are not necessarily clinically equivalent, so do not impose a key without domain decisions.
- Free-text strength/dose/route has no unit or concentration semantics; brand and generic text are not verified product composition. Server truncation/type risks and the confirmed date bug affect record quality.
- Hard deletion loses personal row history; summary snapshots may legitimately retain older entries. Identity review rows are not append-only and reviewed_by is not constrained to users.

## 16. Preserve / Reuse / Redesign Matrix

“Redesign” below means resolve a documented model/behavior gap in a separately approved task; it does not authorize changes or deletion now.

| Area | Existing KOBI capability | Keep | Reuse | Redesign | Reason |
| --- | --- | --- | --- | --- | --- |
| Substance identity | Stable IDs, canonical labels, unique normalized name | Yes | IDs/schema after reconciliation | Semantics/verification contract | Useful base; name uniqueness is not scientific identity approval. |
| Normalization | Stored normalized columns/collation | Yes | Existing stored identifiers | Specify algorithm | No authoritative writer/normalization policy found. |
| Generic/salt search | Bound capped canonical prefix autocomplete | Yes | AJAX/service/repository mechanics | Eligibility/selection contract | Retain suggestions; avoid presenting candidate names as verified identity. |
| Medicine identity | Profile medication row | Yes, private record | Personal flow | Knowledge identity needs design | Private record and canonical catalog identity are distinct. |
| Ingredients | Combination/components schema, comma text UI | Yes | Inspect existing relationships | Define coherent mapping | No medication link or joint composition invariant. |
| Potency/strength | Personal/component strength text | Yes | Preserve raw text | Structured quantity semantics | Unit/concentration/per-ingredient identity missing. |
| Formulation | Named substance forms | Yes | Only after semantic audit | Separate dosage-form meaning | Chemical/name form cannot be assumed to mean presentation. |
| Route | Personal free text | Yes | Display/raw legacy text | Knowledge route contract | No controlled structured route model. |
| Brand/product | Required brand text | Yes | Personal UI data | Catalog boundary, if in scope | Not a marketed-product entity. |
| Manufacturer | None | N/A | None | Later scoped decision | Not required automatically by this audit. |
| Content | Personal notes, substance description | Yes | Architecture | Define knowledge categories | Not sourced/reviewed monographs. |
| Sources | Naming evidence/free references | Yes | Provenance principles | Clinical-source lifecycle | Naming and therapeutic sources differ. |
| Evidence | WHO INN evidence scaffolding | Yes | Identity provenance | Medicine evidence semantics | Not Treatment evidence_supported automatically. |
| Risk | No medicine boundary | N/A | Fail-closed principles | Domain-specific policy | No governed public medicine eligibility. |
| Review | Candidate fields/review table | Yes | Existing data + independent-review lessons | Authority/revision/snapshot | Review fields alone do not enforce approval. |
| Publication | No knowledge publication model | Keep sharing separate | Canonical projection/HOLD lessons | Before public knowledge | Never expose private medicine records through catalog projection. |
| History | Mutable records + derived timeline/share snapshot | Yes | Timeline/sharing behavior | Knowledge append-only events | Separate patient history from editorial history. |
| Authorization | Profile membership + owner/editor roles | Yes | Established services | Test/atomicity as required | Preserve scoping; knowledge editorial scope differs. |
| AJAX | GET JSON/apiBase/debounce/textContent | Yes | Existing mechanisms | Safe input/errors | Avoid new frontend architecture. |
| UI | Bootstrap forms/external JS | Yes | Layout/forms/escaping | Date/error/input/CSP cleanup | Correct existing defects before extending affected flows. |

## 17. Gap Analysis

### Critical foundation gaps

- Correct the confirmed date and unsafe exception boundaries, and establish server scalar/length validation before extending existing medicine write flows. These are existing correctness/security issues, not merely future catalog features.
- Define the knowledge identity contract separating substance/base/salt/form/ingredient from strength/dosage form/route and optional product. Decide stable identifiers and how existing tables participate before schema implementation.
- Specify verified-search eligibility and identity provenance semantics; do not convert active/searchable or WHO_INN labels into clinical approval.
- Preserve private record/sharing boundaries and define clinical governance before publishing medicine knowledge or linking it authoritatively from Treatment.

### Important gaps

No selected-ID persistence, governed ingredient relationships, structured strength/unit validation, joint combination/form coherence, medicine revision/review/source lifecycle, controlled clinical content, medicine regression tests or live catalog-quality baseline. Existing mutable write flows have no optimistic revision or durable repeat protection. These require staged design/validation, not a blanket rewrite.

### Deferred enhancements

Commercial brand/manufacturer/package/SKU coverage, exhaustive synonym/chemical identifiers, unit conversion, large-scale search/alias optimization and advanced product matching. Define their need from an approved roadmap. Do not add dose calculators or recommendation engines based on current free text.

### Existing strengths

Stable substance keys and normalized-name indexes; identity provenance/composition scaffolding; capped parameterized autocomplete; clean service/repository separation; profile/member ownership; explicit write field selection and CSRF; escaped Bootstrap UI; external JavaScript and API-base conventions; separate timeline/summary and controlled private sharing; completed Treatment governance lessons.

## 18. Recommended Medicine Implementation Sequence

This is an audit recommendation, not authorization to implement or a prescribed schema.

1. **3.2 — narrow foundation decisions and existing-flow safety:** document exact identity/normalization/search contracts and reconciliation of existing identity tables; separately correct/test M31-01–03 before extending their handlers. Obtain an available read-only catalog-quality baseline; preserve IDs and private records.
2. **3.3 — approved identity/ingredient relationships:** implement only the reviewed model, resolving salt/form/combination semantics and selected-ID persistence where appropriate; validate legacy mapping rather than infer text identity.
3. **3.4 — strength/dosage-form/route semantics:** support explicit quantity/unit/concentration and ingredient-specific strength according to the approved scope; preserve original text during any migration. Product variants only if required.
4. **3.5 — knowledge scope and provenance foundation:** define medicine content categories/audience, clinical versus identity sources, evidence/risk policy and invalidation. Establish these before unsourced authoring.
5. **3.6 — revision/review/approval governance:** scoped qualified independent roles, immutable snapshots/history, due dates and current-source checks with concurrency/error validation.
6. **3.7 — editorial administration:** build narrow workflows on approved services, not direct catalog writes; include safe errors/CSRF/stale state and success/failure tests.
7. **3.8 — canonical public projection and exposure validation:** explicit allowlist, fail-closed eligibility, separate exposure authorization and browser/runtime validation. Exclude all personal medication/share data.
8. **3.9 — Treatment linkage:** separately specify revision/invalidation rules for references to approved medicine identities; keep Treatment HOLD until its own activation requirements are authorized and satisfied.

Brand/product/manufacturer expansion follows only if a later approved scope requires it. An available database and a reviewed model should precede migrations; do not reimport structure.sql into a populated database as an upgrade.

## 19. Findings

| ID | Severity | Finding | Evidence | Impact | Recommendation | Blocking? |
| --- | --- | --- | --- | --- | --- | --- |
| M31-01 | MEDIUM | Raw caught errors exposed in medicine write views and public share error view. | AccountController 596/753/sharedMedicalSummary; Throwable getMessage → escaped error rendering. | Internal DB details can reach users; escaping does not sanitize message content. | Separately authorize safe expected/error classification and regression tests. | Yes, before extending affected flows. |
| M31-02 | MEDIUM | Today's medicine date rejected as future. | HealthMedicineService normalizeDate and actual database-free today/yesterday/invalid-date invocation. | Ordinary current-day medicine start/stop entry fails. | Correct date-only comparison with boundary tests in an authorized task. | Yes, existing-flow correctness. |
| M31-03 | MEDIUM | Server does not enforce scalar and column-length input contracts. | validateAndPrepareData/normalizeText; browser maxlength versus mixed casts and SQL columns. | Malformed/oversized data warnings, failures or possible permissive-mode truncation. | Define server transport/length contracts and tests. | Yes, before extending writes. |
| M31-04 | MEDIUM | Canonical medicine/ingredient identity contract missing; selected IDs discarded. | health_medicines has text only; JS persists names; nullable combination identities and independent form/substance FKs. | Cannot safely assume catalog identity/composition or link Treatment authoritatively. | Resolve model semantics before schema implementation; preserve personal records. | Yes for canonical knowledge implementation. |
| M31-05 | MEDIUM | No medicine clinical evidence/review/publication boundary. | No clinical source/revision/approval/publication workflow; naming metadata only. | Existing records cannot be treated as reviewed therapeutic knowledge. | Specify governance before clinical authoring/public exposure. | Yes for governed clinical publication; not a claim of current monograph leakage. |
| M31-06 | LOW | Searchable candidates/aliases are not verification-gated. | Current search flags only; unused view alias branch lacks parent-state filter; statuses VARCHAR. | Suggestions are not verified identities; future view reuse could include inactive parents. | Define search contract and validate status/input/errors. | Before authoritative identity use; current name-only suggestion is not a HIGH exposure finding. |
| M31-07 | LOW | Inline edit/delete behavior conflicts with global CSP. | Edit script hash differs from allowed hash; inline onsubmit; external status handler exists. | Confirmation cannot be relied on; duplicate inline behavior. | Use established external JS when separately authorized; browser-test. | No |
| M31-08 | INFORMATIONAL | No live data-quality result or medicine-specific regression suite. | Connection failure; tests discovery contains auth/Treatment/live Treatment utilities only. | No population/duplication/orphan guarantees or medicine runtime-security proof. | Read-only data audit when available; focused tests in implementation phase. | Data decisions/migrations need validation. |
| M31-09 | LOW | Personal records lack duplicate/revision/history guarantees. | No unique content key/version; UPDATE/DELETE by ID after service checks; derived timeline. | Duplicate retries/lost updates and deletion history not controlled. | Define required behavior without assuming clinical-equivalence uniqueness. | No for personal logging baseline; review before expanding semantics. |

No HIGH vulnerability was established. Findings distinguish existing confirmed defects from expected new-foundation design gaps. No finding was corrected during this audit.

## 20. Phase 3.1 Readiness Decision

**NOT READY — FOUNDATION BLOCKER**

The audit is complete, with live-data limitations. Existing personal medicine logging and substance-ID/schema/search scaffolding are worth preserving; a wholesale application redesign is not justified. However, confirmed error-disclosure and current-day validation defects, incomplete server input contracts, and unresolved canonical identity prevent treating the current flow as an implementation-ready Medicine Knowledge Foundation. New therapeutic knowledge/publication cannot rely on naming evidence or private medication rows.

This decision does not prohibit a narrow design/safety task. It requires the next task to resolve those foundations explicitly before expanding medicine functionality, clinical content or public exposure. Unavailable live data is an additional limitation, not the sole reason for this decision.

Task file accounting: created only `docs/MEDICINE_PHASE_3_1_PREFLIGHT.md`; existing files modified: none; files deleted: none. SHA256 comparison of 267 task-start files across app/public/tests/config/bootstrap/database/routes/docs found no modified or deleted inventoried file and only this document added. Pre-existing changes preserved. No normal KOBI database writes, SQL mode changes or Git commit/push/reset/checkout/merge/rebase. Treatment governance and publication HOLD unchanged.

## 21. Proposed Phase 3.2 Starting Point

Start with a separately authorized **Medicine identity and existing-flow safety specification**, narrowly defining substance/base/salt/form/combination versus personal medication/knowledge identity, normalized-name rules, verified-search policy, preservation/mapping boundaries and the required corrections/tests for M31-01–03. Reconcile the existing eight identity tables and preserve the personal medication table rather than invent replacements or silently convert substance forms into dosage forms. Arrange read-only aggregate catalog verification when the configured database is available; do not require personal-record disclosure.

The resulting design should be reviewable before any schema or knowledge implementation. If existing-flow safety fixes are authorized in that task, confine them to the documented medicine/related error boundaries and input/date rules with explicit tests; no Treatment/HOLD/authentication redesign, clinical content, public rollout, normal database mutation or commercial product import is implied. Phase 3.2 is not implemented here.
