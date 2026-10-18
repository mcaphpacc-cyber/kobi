# Step 48A — Unified Public KOBI Search Readiness

14 October 2026. Readiness/architecture only; Step 48B has not begun.

## 1. Executive Summary

**Technically READY for the bounded V1 below. No deployment, reviewer or schema prerequisite.** Recommend a dedicated server-rendered `/search` page with two result groups: Diseases and Medicines/Substances (legacy names only). Provide separate static navigation to Symptom Checker and Discovery; do not present these tools as matched medical results.

Exclude Treatment records, symptom records, rich Medicine content, aliases requiring review, private health/account data, clinical relevance and AI/fuzzy search. The live Treatment schema differs from structure.sql and governed publication remains HOLD; neither is a prerequisite for a V1 that does not query Treatments. Curated Medicine readiness is likewise independent and does not block name discovery.

## 2. Current Public Search / Discovery Inventory

| Capability / routes | Controller → service → repository | Data/result fields | Visibility / reuse |
| --- | --- | --- | --- |
| `/`, `/api/search/suggestions` | HomeController → HomeService → HomeRepository | diseases: disease_en, slug; homepage suggestions | Anonymous; English name contains, eight suggestions. Preserve current endpoint/UI |
| `/diseases`, `/disease/{slug}`, `/api/diseases/search` | DiseaseController → DiseaseService → DiseaseRepository | id, slug, disease_en/hi; API maps to id/slug/name | Anonymous public diseases; catalog includes body/gender/symptom metadata and client-side filtering. Reuse registry identity/detail URLs, add a bounded name query |
| `/medicines`, `/api/substances/search` | SubstanceController renders page; API is AccountController → SubstanceService → SubstanceRepository | kobi_substances: substance_id, canonical_name | Anonymous, active AND searchable, names only. No rich detail destination; reuse visibility predicate, not a Medicine approval |
| Treatment cards on `/disease/{slug}` | DiseaseService → DiseaseRepository::findLegacyTreatmentsForDiseaseDetail | treatment_protocols title/overview; protocol items in detail path | Intentional legacy educational display, not governed publication. No standalone public protocol route. Do not federate into search |
| Governed Treatment projection | TreatmentPublicationService → TreatmentPublicationRepository | complete eligible published protocol projection | HOLD returns no public projection. No public Treatment search endpoint; exclude |
| `/symptom-checker`, `/api/symptoms` | SymptomCheckerController → SymptomCheckerService → SymptomRepository | symptoms id, symptom_en/hi, slug | Anonymous name lookup for symptom selection, not a symptom detail catalog. Static tool link only in unified V1 |
| `/api/symptom-checker` | SymptomCheckerController::match → SymptomCheckerService / matcher, SavedSymptomCheckService | result matches, summary and save_context | Increments counters and captures save context. Not a discovery backend; never call for unified search |
| `/discovery`, `/body-parts`, `/body-parts/{slug}`, `/diseases?body={slug}` | DiscoveryController/BodyPartController → DiscoveryService/BodyPartService → DiscoveryRepository/BodyPartRepository/DiseaseRepository | public body_parts and grouped diseases | Existing public navigation. Use static Discovery link; defer a third body-system search group |
| `/compare`, `/compare/result` | ComparisonController → disease comparison path | selected public disease comparison | Workflow destination, not an independent searchable entity; leave unchanged |

Disease References are section annotations, not separate public entities. Dashboard, saved diseases/checks, personal medicines and health records are explicitly outside this inventory's searchable scope. Recent-disease/session and treatment-preference functions must not influence unified results.

## 3. Existing Search Endpoint Analysis

All four name endpoints below are GET, use `q`, allow anonymous access and have no role-based requirement. They bind name patterns as SQL values. No explicit alias resolution/deduplication by name occurs; each row remains an entity. They have no dedicated local error response wrapper; unexpected failures depend on shared application error handling. Do not infer graceful error behavior from successful queries.

| Endpoint | Request/response | Matching / ordering / limit / normalization | Reuse decision |
| --- | --- | --- | --- |
| `/api/search/suggestions` | q; array of `{slug,disease_en}` | Controller trims; strlen below 2 returns []; English contains LIKE; disease_en ASC; 8; no secondary ID ordering | Keep unchanged; HomeRepository lookup is specific to homepage suggestions |
| `/api/diseases/search` | q; array of `{id,slug,name}` with English display | Trim/empty→[]; DiseaseService::searchDiseases → searchByName; EN OR HI contains; disease_en ASC; default10; no lowercasing/collapsed spaces or alias search | Preserve; existing alphabetical truncation cannot implement unified exact-first ranking reliably |
| `/api/substances/search` | q; array of `{substance_id,canonical_name}` | Trim/empty→[]; active/searchable canonical normalized-name prefix LIKE; sorted normalized name; limit clamped1–10; no reviewed aliases | **Do not modify.** Personal autocomplete depends on this contract. Reuse predicate and repository conventions through a separate bounded query |
| `/api/symptoms` | q; array of `{id,symptom_en,symptom_hi,slug}` | Trim/empty→[]; English contains LIKE, symptom_en ASC,10; Hindi displayed but not searched by this query | Preserve symptom selection endpoint; do not treat slug as a functioning symptom-detail route |

Existing LIKE paths treat `%` and `_` as pattern characters; parameterization prevents SQL injection but does not make these literal search characters. Unified V1 should deliberately escape them without silently changing existing endpoints. Collation controls case/accent equivalence; existing APIs do not implement spelling tolerance.

`GET /api/symptom-checker?symptoms=comma-separated-ids` returns matching output plus save_context; empty parameter returns []. Controller converts IDs with intval, records search counters then matches. It is neither read-only nor a name-search endpoint. Do not reuse it, its matcher, popularity counters or saved-context service in unified search.

Recommend direct lower-level repository calls through a new orchestrating service, not HTTP calls to these endpoints. Internal HTTP adds network/error/session coupling and returns already truncated candidate sets. Do not load full Disease knowledge to search names; that path uses session/preferences and legacy Treatments.

## 4. Public URL / Destination Map

Existing canonical destinations: Disease → `/disease/{stored slug}`; Substance → `/medicines` lookup only; Treatment → within Disease Detail (no independent protocol detail); Symptoms → `/symptom-checker` selector (no working symptom detail); body system → `/body-parts/{slug}` or `/diseases?body={slug}`; Discovery → `/discovery`.

Proposed **new** `/search?q=...` is an implementation route, not an existing route. Substance result action may use existing `/medicines?q={canonical_name}` only with the small prefill/initial-search enhancement specified below: the current page does **not** consume that GET value. Do not claim such deep linking already works. No curated `/medicines/substances/...` route exists; Step 47A's recommendation is not implemented. Never link internal/admin/governance destinations.

## 5. Searchable Entity Scope

| Type | V1 | Fields/destination and restriction |
| --- | --- | --- |
| Disease | Yes | English and Hindi canonical disease names; stored nonempty unique slug; name/body/gender label → existing detail. Current schema has no disease publication/active flag; do not invent one |
| Substance | Yes, explicitly legacy | canonical_name_normalized/name only, is_active=1 AND is_searchable=1; names-only lookup destination; no description, approval or suitability claim |
| Treatment | No | HOLD plus absent standalone destination; do not search titles/items or broaden restored legacy detail exception |
| Symptom | No individual result | Selector has no detail destination; do not silently preselect/submit symptoms. Static Symptom Checker link is a navigation shortcut, not a result |
| Body system | Defer matched group | Twenty useful navigation entries exist, but two groups suffice; static Discovery link |
| References/comparisons/private records | No | Supporting annotations/workflows or explicitly excluded private data |

Both searchable types already have usable source tables and repository conventions; unified orchestration, bounded ranking queries and the page are missing.

## 6. Search Semantics

V1: exact → prefix → contains canonical **name** matching. Disease matching considers EN and HI, with the best matching name tier; display English primary and Hindi when present. Substance matching uses existing normalized canonical name. No content/body/symptom-derived disease matches, treatment text, clinical secondary fields or rank based on severity/popularity.

Accept scalar UTF-8 query only, trim and collapse Unicode whitespace; minimum2 Unicode code points, maximum100, also cap raw byte input before processing. Empty/one-character input renders guidance with zero DB searches; malformed/non-scalar/overlong input gets a safe validation message rather than truncation or exceptions. Preserve punctuation, diacritics and Hindi characters; no punctuation stripping or transliteration. Rely on existing case-insensitive collations (which can also equate accents), document that behavior, and test it. Do not claim binary exactness or introduce a hidden accent-normalization system. Escape literal `=`, `%`, `_` for LIKE with an explicit `ESCAPE '='` clause; separately bound placeholders per occurrence with native PDO.

No fuzzy matching, spell correction, stems, medical synonyms, inferred disease alternates, reviewed substance aliases, brand names or combinations in V1. Name normalization is for matching, never identity merging. Query whitespace collapse does not rewrite stored labels or guarantee matching inconsistent internal spacing; test and document this bounded limitation.

## 7. Result Ranking

Group order fixed: Diseases, then Medicines/Substances. Within each group: tier0 canonical EN/HI exact equality under existing collation; tier1 prefix; tier2 contains. Use best tier if more than one disease name matches. Tie-break by canonical display name under table collation, then numeric entity ID ascending. This is deterministic lexical discovery, not medical relevance. Compute tier/order in SQL **before LIMIT**, not after an alphabetical endpoint's top10.

Maximum10 shown per group,20 total. Fetch11 per group to mark “More matches exist; refine your search,” then project10. No paging in first release; no claim of a full result count from the extra-row indicator. One row per `(type,id)` even if both names match. Identical names across IDs or types may appear separately with labels. Use no join fan-out, no merge by text and no rank percentages. Response to the view: normalized query, groups of safe `{type,id,label,secondary_label,url,context}`, per-group has_more. Private/governance columns never enter that projection.

## 8. Duplicate / Ambiguous Names

SELECT-only census: 400 diseases,400 distinct nonempty slugs. One exact English duplicate group: **Genital Warts**, IDs **179** (`genital-warts-male`) and **199** (`genital-warts-female`). Preserve both results; show stored body-system name and gender as record-disambiguation labels, not personalized applicability. ID can be a final neutral identifier when labels remain identical.

2,224 substances are currently CANDIDATE, active/searchable. All2,224 aliases are active/searchable SOURCE_NAME/PENDING; do not expand search into them merely because search flags are set. Diagnostic ASCII-only key `lowercase(name)` stripped of non-a–z/0–9 found **18 multi-ID formatting-collision groups**. This is not chemical equivalence or a production normalization rule. Examples: 65/66 Alpha Glycerylphosphoryl Choline / Alpha Glycerylphosphorylcholine; 330/331 Carboxymethyl Cellulose / Carboxymethylcellulose; 472/480 Co Enzyme Q10 / Coenzyme Q10; 474/475 Coal Tar / Coaltar; 590/616 Di Iodohydroxyquinoline / Diiodohydroxyquinoline; 764/765 Ethinyl Estradiol / Ethinylestradiol.

Keep original labels/IDs; show “Legacy registry entry · ID …” if needed. Do not clean, merge, backfill aliases or assert interchangeable medicines. No observed exact normalized-name duplication is implied; the unique normalized-name index does not prevent near-formatting differences.

## 9. Public Visibility Rules

Disease query uses public disease master rows with usable slugs; no admin-only content projection, recent-view history, saved status or user preferences. Substance query reuses active/searchable predicate and returns canonical names only, regardless of CANDIDATE status; that is already intentionally public registry behavior, not bypass of rich-Medicine governance. Never return evidence, review status as a professional badge, qualifications or draft content.

Effective Treatment policy inspected through the existing policy class is **hold**. TreatmentPublicationService checks mode before public reads. Disease Detail intentionally uses its separate legacy reader; unified search must not call it or expand it into independent Treatment hits. Public curated Medicine is not implemented and remains excluded. No user-owned table, administrative route, unpublished governed record or reviewer metadata is searched. Anonymous and logged-in users receive the same public results.

## 10. Medical Safety / UX Boundaries

Heading “Search KOBI”; group labels “Diseases” and “Medicines / Substances — registry names.” Context: “Find public information and registry names. Results are for navigation, not diagnosis or treatment recommendations.” Substance rows retain the legacy/no prescribing-information explanation. No “recommended,” “best,” “safe for you,” efficacy/clinical-confidence scores, or personal-risk labels. Discovery links are separate from matches. Do not add a global alarming warning.

## 11. Proposed V1 UX

Choose **B: dedicated server-rendered /search page**, reached through one navbar “Search” link. Labeled GET form, retained query, Search button; grouped semantic lists of regular links. Preserve homepage disease autocomplete; do not replace it in this independent V1. No dropdown or new autocomplete.

Empty/short query: clear instructions. Successful empty group: “No disease matches”/“No registry-name matches”; no matches overall: suggest shorter/name-based wording without guessing diseases. More than10: refine notice and existing catalog/lookup link. Validation errors visible beside form, retain safe input. Backend error: generic visible unavailable message, safe logging via existing conventions; do not silently portray partial success as complete results. Normal full-page submit needs no artificial AJAX loading state; progressive enhancement can be deferred. On mobile stack input/button/results, preserve focus and wrap long labels.

## 12. Existing JS / AJAX Architecture

`public/assets/js/app.js`/kobi-config.js establish frozen window.KOBI from HTML data-base-url/data-api-base. Existing global/page-specific files build same-origin endpoints; home.js uses a response version, substance-search.js uses AbortController and version guards. Layout conditionally loads substance-search.js and separates personal health-medicine.js. Bootstrap assets are self-hosted.

`bootstrap/app.php` CSP restricts script-src to self plus an existing fixed hash, connect-src/form-action to self; do not loosen it or add inline scripts. Dedicated server-rendered search needs **no new JSON endpoint, JS file, debounce or cancellation logic**. The small `/medicines?q=...` handoff should use escaped input prefill and the existing page-specific JS submit logic once on initial load; preserve its stale-response protections. Future dropdown, if later justified, should use250–300ms debounce and cancellation/version checks, but is outside Step48B.

## 13. Backend Architecture Recommendation

One new `PublicSearchController`, one new `PublicSearchService`. No existing service owns cross-entity public name discovery without coupling it to homepage, Disease knowledge/preferences or Account behavior. Service validates query and orchestrates two existing repositories; controller renders `$this->view()` and handles safe validation/error responses. Repositories expose new bounded `searchPublicNames` methods using BaseRepository's parameterized query pattern. No new aggregate repository, search framework or internal HTTP client.

DiseaseRepository returns master name/id/slug plus optional public body/gender disambiguation in a one-to-one body_parts join. SubstanceRepository returns only name/id under existing flags. Do not change searchByName/search/current endpoints or reuse private findForAdmin projections. Construct local destination URLs through existing url()/baseUrl helpers, encode slugs/query values and escape output. Existing reflection DI resolves typed constructors; no service-container rewrite is required.

## 14. Database / Query Analysis

Inspect `database/structure.sql` and live metadata together. Live diseases indexes: PRIMARY(id), UNIQUE(slug), body_part_id; no name index. Symptoms: name/slug indexes (out of V1). Substances: `(is_active,is_searchable,canonical_name_normalized)` plus normalized-name unique and status indexes. Body_parts: primary and unique slug. These support V1 identity lookup, joins and prefix search; contains patterns can scan eligible names.400 +2,224 rows is a practical bounded scan, not grounds for a search engine.

Live counts:2,150 treatment_protocols,3,190 items,346 symptoms,20 body_parts. A SELECT of treatment publication fields failed with **1054 Unknown column 'publication_status'**. Subsequent information_schema SELECT confirmed live protocol columns only id,disease_id,treatment_system_id,title_en,title_hi,overview_en,overview_hi. structure.sql defines additional governance columns. No corrective DDL or data change followed. Treatment search is excluded, so V1 does not depend on those missing columns.

No new search table, FULLTEXT, generated columns or indexes recommended. Bound names-only results, rank before limit, escape LIKE metacharacters, use distinct placeholders with PDO emulation disabled. Do not query joined overview/symptom/protocol content merely to broaden matching.

## 15. Performance Expectations

Two small SELECT queries per valid submission; no user joins, N+1 fetching, remote calls or matching-engine execution. At most11 rows fetched per type; minimum2/max100 code points; no live keystroke traffic/debounce or pagination. Existing catalog pages paginate client-side at25; unified cap/refine behavior is deliberately separate.

Illustrative single-run local SELECT measurements (not a load benchmark): disease contains Migraine1.08ms, Genital Warts1.39ms, Para1.85ms; active/searchable substance contains para5.63ms. These inspected alphabetical samples, **not the unimplemented ranked query**. They support feasibility only. Step48B should measure actual exact/prefix/contains CASE ordering, broad terms and concurrent ordinary reads. Target a visibly prompt response (roughly<500ms local end-to-end); investigate sustained slow requests before adding infrastructure. No new server/query timeout configuration is proposed; handle existing failures gracefully. No Elasticsearch/OpenSearch/Meilisearch or caching layer.

## 16. SEO Interaction

Dynamic `/search?q=...` responses should be `noindex,follow` via controller response header (`X-Robots-Tag`) without redesigning global metadata. Optional canonical to `/search` rather than each arbitrary query; unnecessary for V1 with noindex. Do not add query URLs to sitemap. Existing destination URLs remain canonical targets; no disease/Medicine SEO rewrite. Current shared header has title/description, not a demonstrated configurable canonical mechanism; do not assume one exists.

## 17. Security

Read-only public GET: no authentication or CSRF token needed for searching; no account/session API calls or writes. Server scalar/UTF-8/length validation, fixed limits/ranking SQL and bound parameters; never user-controlled column/order/table names. Escape LIKE patterns independently of SQL injection protection; HTML labels/query retention use e(), no raw source/content HTML. Only server-constructed local URLs. Do not include private health records, account/mobile data, saved checks, documents or sharing records.

No new security framework or speculative rate-limit subsystem. Result/input bounds and submit-only UI constrain routine workload; reuse applicable existing request infrastructure and monitor normal server capacity if abuse becomes real. Do not claim an inspected per-search limiter exists. Safe existing logging must omit query details unnecessarily, credentials and internal exception output. No endpoint exposing administration and no inferred roles.

## 18. Accessibility

Visible form label and help text, semantic search form, button with accessible name, headings per result group, actual links in list items, descriptive type/context labels. Standard Tab/Enter navigation; no custom combobox ARIA or arrow-key contract. Preserve submitted input/focus; error/help linked via aria-describedby, validation invalid-state where applicable. Empty/no-match/refine text is readable without color. Normal page loading is sufficient; future AJAX would require polite status announcements and aria-busy. Distinguish duplicate links with public record labels, never ambiguous identical accessible names alone.

## 19. Mobile / Responsive Requirements

Test390×844 and1366×768. Input full width with min-width:0 as needed; stacked button on small screens, wrapping names/secondary labels, no tables/fixed-height clipped results, usable touch targets and ordinary document scrolling. Navbar Search link reachable in mobile collapse; screen keyboard must not hide an unscrollable submit/result region. No sticky navigation overlay or autocomplete overflow. These are future acceptance requirements; no browser/mobile test occurred in readiness.

## 20. Testing Strategy

Step48B unit tests: scalar/UTF-8/max/min validation, empty/short/no-match, case and Unicode whitespace, punctuation retention/literal %/_/=, EN/HI match tiers, exact/prefix/contains ordering before limit, stable ties, duplicate IDs/names, mixed groups, type+ID dedupe and10+10 caps/has_more. Keep Genital Warts IDs179/199 distinct; never merge substance collision labels. Test unavailable backend as an error, not empty success.

Controller/view tests: anonymous/logged-in equivalent public output, escaped XSS query/labels, local encoded links, safe error text/status, noindex and no private/governance fields. Repository disposable fixtures: parameter-bound injection strings, disabled substances, missing disease slug, correct one-to-one body labels, native PDO placeholder semantics and case/accent collation. Visibility tests must prove no Treatment query/projection under HOLD, no rich-Medicine approval/content, no user tables or symptom matcher/counter/save-context invocation.

Regress existing `/api/substances/search` response/prefix/10-limit/flags and personal autocomplete; `/api/diseases/search` id/slug/name and homepage eight-result suggestions; Disease Detail Diagnosis/Treatments/References; Symptom Checker selector/wording without unnecessary live submission; Auth and CSP navigation. Browser:1366×768,390×844; keyboard Tab/Enter, collapsed navbar, empty/no-match/ambiguous/long-label results, medicine query handoff, no overflow or console/CSP errors. Do not use normal DB for fixture writes.

Readiness validation performed: static inspection; SELECT-only live counts, duplicates, indexes, metadata and bounded sample searches; effective HOLD policy check without bootstrap/session. Eight PHP syntax checks passed (HomeController, DiseaseController, SubstanceController, SymptomCheckerController, HomeRepository, DiseaseRepository, SubstanceRepository, SymptomRepository). Existing read-only suites passed:71 Disease Reference checks,7 Disease Detail presentation scenarios,105 Treatment publication checks. These do not constitute tests of an unimplemented unified search. `git diff --check` passed with an informational existing health-medicines line-ending warning; report has27 numbered sections and zero trailing-whitespace defects. Before/after SHA-256 inventories of app, routes, database, resources and public matched. No web requests, matching submissions, new fixtures or browser/session operations were necessary.

## 21. Implementation Boundary for Step 48B

**MUST HAVE:** anonymous `/search` GET page, navbar link, Diseases + legacy Substances, exact/prefix/contains tiers with deterministic ties/caps, retained validated query, duplicate labels, public-only projection, empty/error/refine states, noindex, focused tests and desktop/mobile verification. Preserve all existing APIs and Treatment/Medicine boundaries.

**SHOULD HAVE:** usable names-only `/medicines?q=...` handoff through existing route/script, Hindi canonical-name matching/display, per-group refine links, safe bounded disambiguation metadata. The handoff should be included in the chosen V1 to avoid a registry-name dead end; it does not create Medicine detail.

**DEFERRED:** dropdown/autocomplete, AJAX endpoint, body-system/symptom/Treatment entity groups, alias/fuzzy/full-text/semantic/AI ranking, private/personalized search, recommendations, notifications, external API/mobile rollout, schema/data cleanup, rich Medicine pilot and governed Treatment deployment.

## 22. Proposed Files to Add/Modify

Paths below are proposals only, relative to `D:\xampp\htdocs\kobi`:

| File | Change and necessity |
| --- | --- |
| app/Controllers/PublicSearchController.php | Add public GET validation/view/error orchestration and noindex header |
| app/Services/PublicSearchService.php | Add query policy, bounded orchestration and safe grouped projection |
| app/Repositories/DiseaseRepository.php | Add ranked public-name query without altering existing searches |
| app/Repositories/SubstanceRepository.php | Add separate ranked canonical-name query preserving flags/API |
| app/Views/search/index.php | Add GET form/grouped links/context/empty/error/refine states |
| routes/web.php | Add `/search`; no changes to existing endpoints |
| app/Views/layouts/navbar.php | Add single public Search link |
| app/Controllers/SubstanceController.php | Pass validated optional q for the existing lookup handoff |
| app/Views/substances/index.php | Escape/prefill that query; retain legacy wording |
| public/assets/js/substance-search.js | Run existing lookup once for prefilled q; preserve cancellation/version behavior |
| tests/public-search.php | Add database-free service/controller/view contract coverage |
| tests/public-search-mariadb.php | Add guarded disposable SQL ranking/visibility/parameter tests |
| tests/substance-search.cjs | Add minimal handoff/stale-response regression if existing JS test harness supports it |
| docs/task-48b-unified-search-implementation.md | Record actual scoped implementation and validation |

No application file was modified here. No new JavaScript framework, inline script, aggregate repository or DI configuration file needed. Exact test harness names can follow existing conventions after inspecting the chosen harness; do not create tests solely to produce a PASS.

## 23. Schema Impact

**No schema change recommended.** No DDL, migration, rollback DDL, full-text/search table or generated column. Existing indexes suffice for a measured small names-only V1. Missing live Treatment governance columns are an existing deployment-state observation; do not deploy them as part of search.

## 24. Data Migration / Backfill

**NONE.** Preserve all disease IDs/slugs and substance labels/IDs. No collision cleanup, alias promotion, placeholder search records, reviews/approvals, normalized-name rewrite or data repair. Query-time normalization is not a backfill.

## 25. Compatibility / Regression Risk

Highest practical risks: inadvertently replacing homepage/API contracts; alphabetical top10 preselection breaking ranking; merging duplicate names; confusing registry hits with reviewed medicines; querying Treatment columns absent live; wildcard-heavy queries; shared navbar/mobile layout and medicine handoff stale responses. Resolve through new isolated orchestration/query methods, stable ID projection and focused tests.

Disease Detail and legacy Treatments remain untouched. Symptom Checker algorithms/API/recordSearch/save_context are never called. Authentication and private Dashboard/health flows remain separate. Reuse same-origin Bootstrap/server-rendered conventions and self-hosted scripts without CSP changes. No medical text, references, publication configuration or private data needs modification.

## 26. Readiness Decision

No prerequisite for the proposed Diseases + legacy Substances discovery page. Existing rich-Medicine professional review and Treatment deployment restrictions apply to those deferred features, not to names-only V1. The live schema difference is documented, not repaired. Readiness does not claim implementation or load/browser acceptance already passed.

**READY**

## 27. Recommended Step 48B Scope

Implement the two-group `/search` page and one navbar entry, using a new small controller/service and separate ranked public-name methods in existing repositories. Validate2–100 code points, escape literal LIKE characters, exact→prefix→contains then name/ID, cap10 per group, retain original identities and legacy wording. Add only the optional q-prefill/initial-search handoff to the existing Medicines page. Keep existing APIs, homepage search, Auth, Symptom Checker backend, Disease/Treatment display/governance and private health features unchanged. Run focused unit/disposable-MariaDB and browser regressions; no normal database writes/schema change.

Only `docs/task-48a-unified-search-readiness.md` created. All normal SQL was SELECT-only, including metadata; one failed SELECT reported missing Treatment publication columns, followed by read-only inspection. SQL mode observed unchanged: `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`. No application/database/schema/content/configuration/session/auth/authorization/publication or Git mutations. Existing unrelated working-tree changes preserved. Stop after Step48A; do not begin Step48B automatically.
