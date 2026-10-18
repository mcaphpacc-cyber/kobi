# Step 49A — Public API Foundation Readiness

14 October 2026. Readiness only; no endpoints implemented. Workspace root: `D:/xampp/htdocs/kobi`.

## 1. Executive Summary

KOBI is technically **READY** for a deliberately small, public, read-only JSON API: Disease list/search/detail and legacy Substance-name search. Recommend a separate `/api/v1` namespace, preserving every existing AJAX endpoint and `/search` contract.

No schema, credentials, reviewer, token, package or migration prerequisite. API-specific JSON routing/error handling and session-free execution are necessary implementation work, not external prerequisites. Exclude Treatments, rich Medicine, Symptom Checker matching, user/profile/health APIs and all state changes.

## 2. Existing JSON / AJAX Inventory

Source-wide JSON/fetch/route inspection found **five routed JSON endpoints**. All are GET, anonymous, without role authorization or CSRF verification. None has paging. They are called by the current browser UI; do not replace their contracts.

| Route → controller method | Service → query layer | Parameters / response / limits | Errors / reuse |
| --- | --- | --- | --- |
| `/api/search/suggestions` → HomeController::searchSuggestions | HomeService → HomeRepository::searchDiseases → diseases | q, trimmed; under2 bytes→[]; English contains; disease_en ascending;8. Array `{slug,disease_en}` | Normally200; controller json helper exits. No local exception envelope. Keep homepage endpoint; reuse data conventions, not mobile contract |
| `/api/diseases/search` → DiseaseController::search | DiseaseService::searchDiseases → DiseaseRepository::searchByName → diseases | q trim; empty→[]; EN/HI contains, disease_en ascending,10. Array `{id,slug,name}` English display | Normally200; manual header/encode; invalid q array may throw. Keep unchanged; ranked repository methods from48B are better V1 reuse |
| `/api/substances/search` → AccountController::searchSubstances | SubstanceService → SubstanceRepository::search → kobi_substances | q trim; empty→[]; canonical normalized prefix, active/searchable, alphabetic, max10. Array `{substance_id,canonical_name}` | Normally200; no local safe failure contract; preserve exactly for registry and personal autocomplete. Do not add V1 fields/envelope here |
| `/api/symptoms` → SymptomCheckerController::search | SymptomCheckerService → SymptomRepository::search → symptoms | q trim; empty→[]; English contains, alphabetic,10. Array `{id,symptom_en,symptom_hi,slug}` | Normally200; manual encode/shared errors. Browser symptom selector only; defer public V1 contract |
| `/api/symptom-checker` → SymptomCheckerController::match | SymptomCheckerService → DiseaseRepository/SymptomRepository/matcher; SavedSymptomCheckService::capture | symptoms comma-separated IDs→intval; empty→[]; match output plus save_context; no controller-enforced input/count paging contract | Records search counters and captures session context; `Cache-Control: private, no-store`; shared failure handling. Not a read-only API despite GET. Do not invoke during readiness or reuse for V1 |

No authentication requirement on these methods does not make them stateless: shared bootstrap starts a session. No endpoint has a stable versioned error/validation contract. Unknown route/wrong method currently emits HTML404; uncaught failures use shared HTML/debug handling. Browser callers: home.js, compare.js, health-medicine.js, substance-search.js, symptom-checker.js; window.KOBI resolves base/API URLs. `/search` is server-rendered GET, not a sixth JSON endpoint. Internal Medicine JSON snapshots are persistence data, not routed APIs. Private forms and downloads are not public JSON discovery endpoints.

## 3. Public API vs Existing AJAX

Choose **C: separate versioned API namespace while preserving existing AJAX**. Existing bare arrays, limits, matching semantics and session-oriented matching are unsuitable as a single stable mobile contract. Reuse existing repository/service logic, not HTTP requests to browser endpoints and not controller methods that echo/exit.

V1 services explicitly project allowed public fields. Existing web UI keeps its own shape and limits. No forced migration of JavaScript or token system. Public information being discoverable does not authorize private APIs.

## 4. API Versioning

Use path version `/api/v1/...` in current route registration. Stable field types, null meaning, validation/error codes and ordering are contractual within V1. Additive optional fields may be added; clients ignore unknown fields. Renaming/removing/changing types or semantics requires a new major namespace; retain V1 during a documented future migration. No media-type/header version negotiation or speculative V2 implementation.

## 5. Initial API Surface

All routes below are **proposed**, GET-only, anonymous, public, no token/session/CSRF, JSON-only, no CORS grant. Cache-Control no-store for first foundation (section19). Error matrix in12; unexpected failures safe500. No auth-dependent fields.

| Route | Inputs | Data contract | Bounds/public rules |
| --- | --- | --- | --- |
| `/api/v1/diseases` | page=1, page_size=20 | Array of DiseaseSummary (below) | Max50/page; deterministic disease_en then id; usable nonempty public slug; count over same predicate; meta pagination |
| `/api/v1/diseases/search` | required q | Array of DiseaseSummary |2–100 Unicode code points; exact→prefix→contains;10 returned, fetch11 for has_more; no paging; same public predicate/ranking as48B |
| `/api/v1/diseases/{slug}` | validated stored slug | DiseaseDetail object |404 when absent; narrow disease/content projection, existing English public references, no Treatments/private reads |
| `/api/v1/substances/search` | required q | Array of SubstanceSummary | Same normalized query/ranking and10+extra bound as48B; active=1 AND searchable=1 canonical names only |

Register literal `/diseases/search` before `/diseases/{slug}` to avoid interpreting search as a slug. Disease slug identifies public detail; decimal IDs remain explicit identity fields, not route guesses.

DiseaseSummary exact fields: `id` (decimal string), `slug` (string), `name_en` (string), `name_hi` (string|null), `public_url` (string), `body_system` (string|null), `gender` (male|female|both|null, stored descriptor, not suitability).

SubstanceSummary exact fields: `id` (decimal string), `canonical_name` (string), `registry_url` (string to current `/medicines?q=...`), `information_type` (fixed `legacy_registry_name`). No description, brand, dosage, identity/review/approval flag. No Substance detail/ID lookup endpoint in first release: names-only search already meets scope without implying a rich profile. A future name-only ID lookup can be separately justified; not needed now.

DiseaseDetail = all DiseaseSummary fields plus `locale` (fixed en), `content_format` (fixed plain_text), `sections` (object with overview,causes,risk_factors,diagnosis,prevention: each string|null), `causes_items` (array of English cause-name strings from existing public relationship, empty allowed), `urgency_note` (English string|null), `references` (fixed five section keys, arrays of Reference). Reference exact fields: organization,title,url strings; date,date_type strings|null. Missing reference metadata becomes null rather than invented dates. No public clinician review claim.

No symptoms, related-disease ranking, FAQ/diet graphs or severity/risk scores in this minimal contract. It is a documented subset of public Disease Detail, not a complete mobile recreation of every web section.

## 6. Disease API Analysis

DiseaseController defaults detail/search to English. DiseaseService::getKnowledgeBySlug uses a raw knowledge aggregate, legacy Treatments and user preference ordering; DiseaseController::show also remembers recently viewed diseases and checks saved status. **Do not call those web workflows from V1.** DiseaseRepository::findKnowledgeBySlug currently SELECTs d.* and dc.* and loads symptoms/causes/FAQs/diet, so returning that row is unsafe/unbounded contract design.

Add a narrow public base/content projection in DiseaseRepository, then a side-effect-free API service. Reuse the same existing usable-slug public criterion, cause relationship and DiseaseReferenceService::getForSection. Share existing ordered cause-name read with the new projection rather than copying its SQL into another data-access layer. Public Disease schema has no publication/active flag; do not invent a reviewed-only disease filter.

Stored overview/causes/risk_factors/diagnosis/prevention/urgency_note English fields are rendered as escaped text in the current view. Serialize their original text/line breaks without medical rewriting, HTML widgets or generated diagnosis advice. Blank/NULL→null; clients display text, never inject it as markup. Causes items preserve already displayed relationship names when narrative is missing. Do not invent icd10/category fields merely because optional view branches mention them; current schema lacks them.

English narrative only for V1. Hindi canonical names are already public/searchable; stored Hindi narratives are not automatically equivalent to an approved public Hindi presentation. Defer narrative locale selection and translation parity. References are section-level English mappings for eight diseases, deliberately excluding Migraine/Acne Overview; empty arrays mean no confirmed section mapping, not medically unsupported content. Do not expose mapping rationale, file paths, reviewers or inferred whole-page attribution. Database updated_at is not a medical review date.

## 7. Substance / Medicine API Analysis

Current data: canonical legacy names, normalized-name search and active/searchable flags. SubstanceRepository::searchPublicNames already supplies ranked active/searchable rows for48B. Reuse it directly through a public service; do not read canonical Medicine identities, appraisals, qualification history or private health_medicines. All current2,224 registry rows are CANDIDATE; this does not prevent their existing names-only public discovery, and must not become a reviewed label.

Use decimal-string IDs consistently to support unsigned-bigint substance identity across clients. No silent collision merging, alias promotion, form/composition inheritance, prescribing advice or curated detail. Registry URL is a web lookup handoff, not a medicine-detail guarantee.

## 8. Treatment API Boundary

**Deferred entirely**, including nested DiseaseDetail Treatments. Effective policy is HOLD. Existing Disease Detail intentionally restores legacy educational cards through a scoped reader, but this is not an unambiguous governed API publication permission. The new API must not broaden that exception. No Treatment routes, candidate checks masquerading as release, governance metadata or legacy protocol/items embedded in Disease JSON.

Live protocol table still differs from governance schema reference (Step48A found missing publication columns). No API V1 query needs these columns; no corrective migration is appropriate here.

## 9. Symptom Checker API Boundary

Defer both selector and matching to a dedicated future contract step. Matching GET increments search counts and captures browser-session save_context, and its medical interpretation/safeguards differ from name discovery. V1 must never call match/recordSearch/capture or attach personalized rank/confidence. Unified `/search` likewise excludes it. No live matching request performed in49A.

## 10. Private API Boundary

Exclude users/accounts/security info, health profiles/records, accessibility grants, private documents/files, personal medications/schedules/Due Today, saved checks/diseases, Dashboard, mobile/verification state, private sharing/access logs and authentication secrets/tokens. No joins to these tables, no optional include_private expansion, no authenticated enrichment. Future private APIs require separately designed authentication/authorization/profile-access contracts; do not design them here.

## 11. Response Envelope

Use a minimal consistent new V1 envelope, leaving existing AJAX untouched:

```json
{"success":true,"data":[],"meta":{"api_version":"v1"}}
```

```json
{"success":false,"error":{"code":"VALIDATION_ERROR","message":"Check the request parameters.","fields":{"q":"Enter 2–100 characters."}},"meta":{"api_version":"v1"}}
```

Success always has data/meta, never error. List/search empty→200 with []; detail absent→404 error, not success/null. Error has no data; fields is present only for parameter validation and maps approved parameter names to safe messages. Stable error codes: BAD_REQUEST, VALIDATION_ERROR, NOT_FOUND, METHOD_NOT_ALLOWED, INTERNAL_ERROR; RATE_LIMITED only if a limiter is actually installed. No exception class/message/query echo/trace/SQL or request body in errors.

List meta adds page,page_size,total,has_more. Search meta adds normalized query,limit=10,has_more; no fabricated total. Detail meta only api_version. No generated timestamps/request IDs initially; small predictable payloads suffice.

## 12. HTTP Status Codes

200 valid success including empty/out-of-range list page.400 structurally malformed request (e.g. array where scalar expected, invalid UTF-8).422 semantic validation (short/long query, invalid page size/slug, unknown parameter).404 unknown V1 route or absent public entity.405 known route with unsupported method, `Allow: GET`.500 unexpected backend/serialization failure with safe JSON.

401/403 are reserved for future protected APIs, **not used for public V1** or login redirects.429 reserved for enforced throttling with Retry-After; do not advertise a nonexistent limiter. GET-only means HEAD/POST/OPTIONS etc on known routes return405 in the minimal implementation; HEAD body must still be suppressed at response emission. No state-changing method or preflight grant. HTTP semantics reference: [RFC9110](https://www.rfc-editor.org/rfc/rfc9110.html).

## 13. Validation

Reuse PublicSearchService::normalize for scalar UTF-8/raw4096-byte cap, Unicode whitespace and100-code-point limit; API adds required q and minimum2 (web's short-query instructions remain unchanged). Reuse its literal LIKE escape logic by extracting a shared method within the same service if needed, not copying variants into controllers.

Slug: scalar decoded UTF-8 stored-route identifier,1–255 characters, no slash/control/NUL, allow current letters/numbers/hyphens; check actual current slug patterns before freezing stricter regex. Do not lowercase/repair into another identity; look up bound exact stored slug. No numeric detail route in V1; response IDs are decimal strings. page/page_size strict positive decimal integers, bounded page1–10000 and size1–50; reject arrays/fractions/signs/overflow rather than intval coercion. Default20. Search endpoints accept q only; list accepts page/page_size only; detail no query params. Unknown parameters→422. Malformed q encodings→400. No request JSON body contract; GET uses URI parameters only, never POST/request-input fallback.

## 14. Pagination

Page/page_size for Disease list only: default1/20,max50, explicit page bound protects absurd offsets. Order disease_en then id; public predicate/count identical. Beyond last page→200 []/has_more=false with accurate total. Page count need not be returned. No cursor table/opaque cursor infrastructure;400 rows do not justify it. Concurrent editorial updates can shift offset pages; document no immutable-snapshot guarantee across requests. Each request may use an existing read-only consistent snapshot for count/items if needed.

Search stays bounded10+one extra, no pagination, matching48B. Expanding ranked paging later is separately designed; do not alter fixed LIMIT11 methods for this release.

## 15. Search API Relationship

New Disease/Substance API searches reuse existing `searchPublicNames(query,pattern)` repository methods, preserving SQL CASE exact→prefix→contains, name/ID tie-break and public flags. Share query normalization/escaping with PublicSearchService. Use a small API projection service that calls only the requested entity repository; do not call web search and fetch both categories unnecessarily or return label/context web arrays as a permanent mobile contract.

No `/api/v1/search` federation endpoint needed. Existing `/search`, its20-result page, old Disease AJAX and old `/api/substances/search` remain unchanged. The latter still has prefix semantics and original bare row keys; V1 has an envelope and ranked contains support by explicit new contract.

## 16. Authentication

No authentication, API keys, bearer tokens or session requirement for V1. Do not instantiate AuthService, View or private services in API controllers/projection. Ignore cookies/Authorization for public data; identical response contract with/without them, no personalization and no token consumption. Never infer role permission from the API namespace.

Current bootstrap unconditionally starts PHP session and configures cookies. Add a narrowly scoped path check for `/api/v1` and descendants using the existing base-path normalization, before session_start: only new public namespace skips session start/configuration and has API error headers. Existing web/AJAX authentication, cookies, CSRF, CSP and session behavior stay exactly as they are. Test unknown V1 paths as stateless too. Do not change global session settings or invent a separate front controller/framework. This scoped bootstrap adaptation is Step49B implementation work, not performed now.

## 17. CSRF

No CSRF token for new read-only GETs; they neither write data nor depend on browser authentication. Existing browser state-changing forms retain Session::verifyCsrfToken/csrfField and explicit auth/access checks. Future cookie-authenticated mutation APIs still need CSRF protection; future bearer-token design requires its own threat analysis. Do not exempt existing Account/auth routes or promote state-changing matching GET to safe API status.

## 18. Rate Limiting / Abuse

Source searches found no general request-throttle middleware, API counter, Retry-After handling or reusable limiter. Auth token tables/expiry flows are not an API rate-limit system. No Redis/external service prerequisite.

First local foundation: bound request size/query length/pages/results, use cheap name projections, avoid matching/expensive graphs, and keep existing web-server request/error logs. Before unrestricted Internet deployment, set a measured host/web-server per-client ceiling; a reasonable starting policy to validate is60 public-API reads/IP/minute, allowing NAT/shared-user considerations. Use remote address/trusted proxy configuration only, never blindly trust forwarded IP. Prefer hosting's existing controls; if unavailable, separately approve a tiny locked expiring file-based counter in existing storage, not DB tokens/tables. No such deployment/limiter is implemented or certified here. Throttling is SHOULD HAVE before external rollout, not an invented blocker to49B local contracts/tests.429 appears only with real enforcement.

## 19. Caching

Choose `Cache-Control: no-store` for initial V1 responses/errors, explicit and uniform; no ETag/Last-Modified/server cache. This avoids stale withdrawal/visibility/reference claims while publication/API contracts are first established. No-cache would allow storage with revalidation, a different policy. Later conditional GET may hash the complete safe serialized representation (including references and flags); do not use disease_content.updated_at alone, which omits manifest/registry changes. [RFC9111](https://www.rfc-editor.org/rfc/rfc9111.html) defines cache-control semantics. No cache headers changed now.

## 20. Content / Public Visibility

One public definition: public disease rows with usable slugs and already displayed English content/cause names; existing DiseaseReferenceService allowlist; substance active AND searchable names. New service has explicit response projections, not a second independent governance eligibility engine. Existing identity flags never imply professional review.

No full `getKnowledgeBySlug` web call, legacy Treatment reader, saved-status lookup, session-based recent/preference personalization or public API gate bypass. API detail projection is a selected subset of existing page text; no new medical content, HTML widgets or invented reviewer dates. Future public visibility changes must be applied to shared reads/queries, not API-only copies. Reference [] is allowed wherever existing service gives [].

## 21. Security Review

Bound SQL values, fixed projection/columns/order and bounded paging; native PDO emulation disabled. Literal LIKE escape separate from injection protection. Decimal-string IDs avoid mobile/JavaScript bigint precision loss. Public identifiers can be enumerated; that is appropriate for public names/pages, not grounds to expose related private records.

Use application/json;charset=UTF-8 plus existing nosniff. JSON escaping is not an HTML sanitizer: clients render text safely, never HTML-evaluate narratives/query/labels. Validate UTF-8 and serialize with JSON_THROW_ON_ERROR; serialization failure becomes safe500. Unknown/wrong-method V1 paths must produce JSON, not HTML. Scoped bootstrap/error handlers must suppress debug output even if web debug is enabled; no traces/SQL/credentials. Do not alter unrelated debug/config behavior.

No CORS wildcard or reflected Origin/credentials header. No new auth/role inference, private optional expands, URL fetch/SSRF behavior or unbounded joins. Bound responses; current inspected content is small. Oversized unexpected payload must fail safely rather than silently truncate medical text. Proposed whole-response guard256KiB can be validated against real source output in49B.

## 22. CORS

V1 emits **no Access-Control-Allow-Origin/Allow-Credentials headers**. Native HTTP clients are not browser fetch implementations and do not require browser CORS permission; absence of CORS also is not authentication or a network-level block. Browser fetch origin sharing is governed by the [WHATWG Fetch CORS protocol](https://fetch.spec.whatwg.org/#http-cors-protocol). Hybrid WebViews/browser-hosted clients may require a later explicit origin allowlist; native/mobile tooling is not an excuse for `*`. No OPTIONS preflight support promised. Existing CSP same-origin web calls remain unchanged.

## 23. Content Negotiation / Headers

Always JSON for V1, including routing/validation/backend errors. Required: Content-Type application/json;charset=UTF-8, Cache-Control no-store, existing X-Content-Type-Options:nosniff.405 adds Allow:GET; throttling if later enforced adds Retry-After. No Set-Cookie or session cache-limiter headers on the new stateless branch.

Accept absent/application/json/*/* is normal. To keep V1 simple, even an incompatible Accept receives the documented JSON representation; no media negotiation/406 logic. GET has no request Content-Type/body parser; ignore Content-Type header, do not process JSON bodies or define malformed-JSON mutation behavior. Existing global security headers may remain on API replies; no CSP weakening. HTTPS needed for Internet/mobile deployment, not an instruction to alter local HTTP setup.

## 24. Routing / Controller Architecture

Use current Router registration in routes/web.php, new flat `PublicDiseaseApiController` and `PublicSubstanceApiController` classes; version is in routes/contracts, not an elaborate namespace hierarchy. Existing Container autowires typed constructor dependencies.

One narrow `PublicApiService` handles validated public projections and read-only calls; repositories hold SQL. One small `PublicApiResponse` handles envelope/status/UTF-8 serialization. Scope Router's unknown-route/unsupported-method JSON behavior to normalized `/api/v1` only, checking path matches across registered methods for405 before container construction. HTML web404/old AJAX behavior remains untouched. Scope bootstrap error/fatal handling and session suppression to same namespace; share the path classifier to avoid mismatches. Minimal addition to Request for base-path-normalized API identification can be reused by Router/bootstrap; avoid string-prefix matching unrelated routes like `/api/v10`.

## 25. API Response Serialization

Service creates explicit DTO-like arrays per section5; never return `SELECT *`, raw aggregates or PHP model internals. Response helper wraps arrays and encodes JSON before emitting headers/body, with error fallback free of exceptions. Cast IDs to decimal strings, names to strings/null as specified, arrays to sequential lists and metadata integer/boolean types. Controllers map known validation/not-found errors; Router/bootstrap use same helper for route/method/unhandled failures. Do not alter Controller::json for existing callers just to force an envelope everywhere.

## 26. API Documentation

Step49B create `docs/public-api-v1.md`: exact routes/fields/types/examples, errors/null/empty semantics, query rules, paging, legacy labels, caching/CORS/session rules and stability/deferred boundaries. Add hand-maintained `docs/public-api-v1.openapi.yaml` if useful for future mobile tooling; no Swagger package/UI/install/generation pipeline. Markdown contract is MUST, OpenAPI SHOULD. Examples use existing public names/IDs or clearly labeled illustrative JSON, never fake database records. Publish implementation/test results separately.

## 27. API Contract Stability

Freeze section5's field names/types, decimal-string IDs, slug identity, fixed en narrative locale, plain_text format, nullable empty texts and references with honest date meanings. Summary fields shared between list/search/detail. Arrays [] remain arrays. Extra unrecognized fields may be added compatibly; clients must ignore them. No promise that mutable medical text/registry labels stay identical or that an item remains visible forever. Stored slug may change editorially; ID is stable identity, public_url is current destination.

No hidden optional include parameter widening the contract. No expose-all fields, joined private data, medical approval flags or future tokens. An empty reference list cannot become an implicit whole-page source/review statement.

## 28. Mobile Readiness

Four small predictable GET contracts can support initial public mobile knowledge/navigation: stable entity identity, explicit public web links, bounded page/search results, deterministic ordering and safe errors. No browser-session cookie or HTML layout required. Locale limitations and names-only substance semantics are explicit. No claim of complete mobile app readiness: private authentication/profile data, offline medical freshness, notifications, rich Medicine, matching and shared care are separate future tasks.

## 29. Performance

Live SELECT census14 October:400 diseases,400 disease_content rows,346 symptoms,2,224 substances,2,150 treatment_protocols. All substance rows active/searchable/CANDIDATE. English and Hindi overviews present on all400 content rows; maximum inspected combined English overview/causes/risk/diagnosis/prevention/urgency bytes **3,798** (not total JSON/related content).

Indexes: diseases primary id,unique slug,body_part_id; content primary id,unique disease_id; substances composite(active,searchable,normalized name),unique normalized name,status. Current400/2,224 name scans and bounded CASE ranking need no indexes/search server. List is two small count/page reads; search one requested entity query; detail one base/content read plus bounded existing cause lookup and in-process reference manifest. Do not load full knowledge graph. Validate actual V1 latency/response sizes in49B;48A/48B observations are not API load benchmarks. No Redis/queue/microservices.

## 30. Database / Schema Impact

**NO SCHEMA CHANGES.** No API tables/logging tables/token tables, indexes, generated columns, migrations or data backfills. Use current fields/FKs and file-backed Disease reference manifest. Missing live Treatment governance columns are outside V1 and not an API prerequisite. No SQL-mode change.

## 31. Logging / Observability

Existing Logger writes storage/logs/app.log; exception logging can include trace/message. For V1 use safe route/code/exception-class diagnostics only, excluding raw query/body, credentials, tokens, private data and source rows; do not expose logs. Existing web-server status/error logs plus these failure diagnostics suffice for local foundation. No API success logging table or new logging framework. Do not emit medical search terms unnecessarily into custom logs. Hosting access-log retention/privacy is an operational review before external deployment, not a schema task.

## 32. Testing Strategy

Step49B: contract/unit tests for all explicit fields/types, en-only narrative, null/[] values, reference date filtering, decimal IDs, duplicate names, query Unicode whitespace/case/punctuation/wildcards, required/short/overlong/malformed input, unknown parameters, page/size bounds, tie ordering and count/has_more. Verify exact→prefix→contains matches48B and old prefix API semantics remain unchanged.

HTTP tests: four routes; search literal route precedence; unknown entity/route404; known route POST/HEAD/OPTIONS405+Allow (HEAD no body); JSON content type/envelope on every path,400/422/500, cache/no-cookie/no-CORS headers; injected repository/encoding failure never emits HTML/debug trace even under web debug. No session started/created/modified for all V1 including404/error; browser/web/AJAX sessions/CSRF/Auth unchanged. Same public response with no cookies, existing cookies or irrelevant Authorization header.

SQL/disposable tests if needed **only in a separately guarded disposable target**: public slugs, active/searchable exclusions, native placeholders, paging/search duplicates/injection/XSS strings, missing content/cause/reference cases. Normal data stays SELECT-only; no normal fixtures/deployment. Boundary spies ensure no Treatments/Medicine review/private tables/symptom recordSearch/capture called. Test exact source projection excludes raw DB/internal fields; unexpected large responses fail safely, never truncate text.

Regress public-search30 checks and its MariaDB/JS suites in authorized disposable environments, Disease References71, Disease Detail7, Treatment publication105, Auth47; existing `/api/substances/search`, Disease AJAX/homepage, `/search` and registry query handoff. No gratuitous live symptom submission. Readiness ran database-free suites only, not mutation tests.

## 33. Browser / Manual Validation

Prefer direct HTTP/PHP contract/header/error tests for API; browser is a small regression check, not primary API validation. In49B open one new JSON endpoint, verify expected content, exercise existing `/search` and medicine handoff, and confirm no console/CSP/layout regression at390×844/1366×768 if shared files are touched. No account creation/token/private forms required. No browser navigation/session operations were performed in49A; current authenticated tabs were preserved.

## 34. Step 49B Implementation Boundary

**MUST HAVE:** exactly four proposed GET endpoints; new V1 response envelope/status/validation; public allowlist service; shared48B ranked queries/reference service; Disease list paging/detail subset; stateless V1 bootstrap; API-only JSON404/405/unhandled errors; noindex optional for JSON, no-store/no-CORS; documentation/tests and web regressions.

**SHOULD HAVE:** hand-written OpenAPI, measured payload/latency and deployment rate-limit recommendation using existing hosting controls. No new limiter datastore required for local foundation.

**DEFERRED:** private/mobile-wide APIs, auth/token/key systems, ABHA/ABDM, Treatments, rich Medicine/Substance detail, Symptom Checker, AI, files/documents, notifications, Hindi narratives, cursor pagination, full-text/remote search, cache servers/ETag, cross-origin web-client permission and new governance publication work.

## 35. Proposed Files

All are proposals only, under `D:/xampp/htdocs/kobi`:

| Path | Purpose |
| --- | --- |
| app/Controllers/PublicDiseaseApiController.php | New list/search/detail GET orchestration |
| app/Controllers/PublicSubstanceApiController.php | New legacy-name search controller |
| app/Services/PublicApiService.php | New validation/public DTO projection and read coordination |
| app/Core/PublicApiResponse.php | New shared V1 JSON success/error serialization |
| app/Core/Request.php | Reusable normalized public-V1 path detection, avoiding duplicate routing/base-path assumptions |
| app/Core/Router.php | API-only JSON404/405, preserving all other dispatch behavior |
| bootstrap/app.php | API-only stateless/error-output branch; existing web behavior preserved |
| routes/web.php | Register four versioned routes in correct literal-before-slug order |
| app/Repositories/DiseaseRepository.php | Narrow public list/count/content projection and reuse ordered cause read; no full knowledge load |
| app/Services/PublicSearchService.php | Share existing escaping helper if needed; web shape/normalization/limits unchanged |
| tests/public-api-v1.php | Database-free contracts/errors/boundaries |
| tests/public-api-v1-http.php | HTTP route/status/header/stateless regression checks |
| tests/public-api-v1-mariadb.php | Guarded disposable list/detail/native-query tests if fixtures necessary |
| docs/public-api-v1.md | Stable V1 contract |
| docs/public-api-v1.openapi.yaml | Optional manual OpenAPI artifact |
| docs/task-49b-public-api-foundation-implementation.md | Actual implementation/validation record |

SubstanceRepository ranked search and DiseaseReferenceService should need no modification. No web views/JS/CSP/config/Auth/Session class change intended. Existing Controller::json stays intact. Narrow bootstrap/Router changes require tests, not a framework rewrite.

## 36. Schema / Migration Plan

API can be implemented using current schema. **No SQL or migration/rollback script expected.** Code rollback would remove the new namespace/response branches without data restoration; preserve existing web routes. No data deployment is authorized by readiness.

## 37. Dependencies / Prerequisites

No external prerequisite for49B's scoped foundation. Existing PHP8.2/native PDO/mbstring, custom Router/Container and public repositories suffice. New stable projection and transport boundary are implementation deliverables, not missing personnel or governance approval dependencies. Production HTTPS/origin/rate-limit deployment decisions belong to external rollout, not a reason to defer local49B. Rich Medicine/Treatment prerequisites remain outside scope.

## 38. Risk Assessment

Architecture: web readers load session/preference/Treatments → use narrow service/repository projections. Security: debug/HTML output and wrong-method handling → API-scoped response/error branches tested. Compatibility: changing old JSON helper/endpoints → leave them untouched. Data exposure: SELECT* aggregates → explicit field allowlists and no private dependencies. Performance: unlimited lists/content graphs →20/50 pages,10-hit searches and small detail subset. Mobile: bigint/locale/HTML misunderstandings → decimal-string IDs, en/plain_text documented. Maintenance: duplicate matching/visibility/source logic → reuse48B queries and DiseaseReferenceService; central path detection. Cache/CORS confusion → initial no-store/no-CORS and no auth assumptions. Scope: legacy Treatment exception → exclude all Treatment JSON.

## 39. Readiness Decision

The application has the required public data and reusable search/reference logic. Its current AJAX transport is not a stable stateless API, but the required API-specific adjustments are bounded implementation work without schema or external dependencies. Ready for the four-endpoint49B plan, not the full mobile/private platform.

READY

## 40. Recommended Step 49B Plan

Implement GET `/api/v1/diseases`, `/api/v1/diseases/search`, `/api/v1/diseases/{slug}`, `/api/v1/substances/search`; exactly Diseases and legacy substance names. Service allowlists from5, success/data/meta and safe error envelope from11,200/400/422/404/405/500 from12; Disease list1/20,max50,search10+extra. Reuse shared normalization/escape/ranked repository methods and existing section references. No auth/token/CSRF/private work. Add scoped stateless bootstrap and JSON Router/error responder; no CORS and no-store; preserve all old web/AJAX behavior. Add proposed tests/docs, direct HTTP validation and small web regression check. No schema/data changes or further feature work.

Readiness validation completed: SELECT-only census/index/content-size and effective HOLD/SQL-mode checks; source/route/session/error/security inspection; nine PHP syntax checks PASS;30 Public Search,71 Disease Reference,7 Disease Detail and105 Treatment publication database-free checks PASS. No new API tests/fixtures or database mutation tests were run. Protocol research used official Fetch/RFC sources; no clinical source research/content edits.

Only `docs/task-49a-public-api-foundation-readiness.md` created. No application/database/schema/configuration/content/auth/session/authorization/SQL-mode/Git mutations, new tokens/users/packages, endpoints or dependencies. Existing unrelated work preserved. No49B implementation. Final verification: report exists, exactly40 numbered sections, zero trailing whitespace, ends READY; git diff --check passed (existing health-medicines line-ending warning informational); before/after SHA-256 inventories of app/bootstrap/routes/database/resources/public matched. Read-only Git status inspected; no index/branch/history changes.

READY
