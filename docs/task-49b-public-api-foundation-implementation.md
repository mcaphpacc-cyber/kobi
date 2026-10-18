# Step 49B — Public API Foundation V1

**PASS — COMPLETE.** Implemented and validated 14 October 2026, using the Step49A contract. No49C or other feature started.

## 1. Purpose and Approved Scope

Small public read-only JSON foundation for Disease list/search/detail and legacy Substance-name search. Controller → service → repository; no framework/dependencies/schema deployment, no private/mobile authentication or clinical recommendation surface. Stable contract: `docs/public-api-v1.md`.

## 2. Exact Files Modified

- `D:/xampp/htdocs/kobi/app/Core/Request.php` — shared normalized V1 path classification.
- `D:/xampp/htdocs/kobi/app/Core/Router.php` — V1-only JSON404/405, preserving existing web/AJAX dispatch.
- `D:/xampp/htdocs/kobi/bootstrap/app.php` — V1-only session-free and safe JSON error/output boundary.
- `D:/xampp/htdocs/kobi/routes/web.php` — exactly four GET routes, literal search before slug.
- `D:/xampp/htdocs/kobi/app/Repositories/DiseaseRepository.php` — public count/page/detail projection and bounded reuse of existing cause read.
- `D:/xampp/htdocs/kobi/app/Services/PublicSearchService.php` — shared LIKE escaping helper; existing web behavior unchanged.

## 3. Exact Files Created

- `D:/xampp/htdocs/kobi/app/Controllers/PublicDiseaseApiController.php`
- `D:/xampp/htdocs/kobi/app/Controllers/PublicSubstanceApiController.php`
- `D:/xampp/htdocs/kobi/app/Core/PublicApiResponse.php`
- `D:/xampp/htdocs/kobi/app/Services/PublicApiException.php`
- `D:/xampp/htdocs/kobi/app/Services/PublicApiService.php`
- `D:/xampp/htdocs/kobi/tests/public-api-v1.php`
- `D:/xampp/htdocs/kobi/tests/public-api-v1-bootstrap.php`
- `D:/xampp/htdocs/kobi/tests/public-api-v1-http.php`
- `D:/xampp/htdocs/kobi/tests/public-api-v1-mariadb.php`
- `D:/xampp/htdocs/kobi/docs/public-api-v1.md`
- `D:/xampp/htdocs/kobi/docs/task-49b-public-api-foundation-implementation.md`
- `D:/xampp/htdocs/kobi/docs/task-49b-mobile-regression.png`

Before/after SHA-256 inventory identified precisely the six modified/five new production paths above, no removals/unrelated production changes. Database, configuration, resources, web views/JS, Auth, Medicine governance, Treatment and private-health implementation files remained unchanged. Existing unrelated working-tree changes were preserved. Whole Git diff contains prior tasks too; it is not this task's change list.

## 4. Endpoints Implemented

| GET route | Operation |
| --- | --- |
| `/api/v1/diseases` | Public Disease list |
| `/api/v1/diseases/search` | Ranked Disease-name search |
| `/api/v1/diseases/{slug}` | Public English content subset and references |
| `/api/v1/substances/search` | Ranked legacy Substance-name search |

No other endpoints/methods, including no Substance ID/detail route. Literal search registered before slug. Native clients use versioned JSON; public web URLs remain their existing routes.

## 5. Request Parameters

List: page default1,max10000; page_size default20,max50, strict positive decimal string without signs/leading zeros/fractions. Searches: required q, trim/collapse Unicode whitespace,2–100 code points, raw4096-byte cap. Detail: existing slug, validated ASCII letters/numbers/hyphens,1–255 bytes, URL-decoded once; all400 current slugs checked compatible. Unknown parameters rejected; arrays/invalid UTF-8 are malformed. No body parser, include_private, locale narrative switch or arbitrary column/order parameter.

## 6. Response Contracts

Success `{success:true,data:array-or-object,meta:{api_version:"v1",...}}`; errors `{success:false,error:{code,message,fields?},meta:{api_version:"v1"}}`. No raw row serialization or internal exception messages. Empty list/search arrays[], absent detail404. Explicit types and null semantics described in contract document.

DiseaseSummary: id decimal string,slug,name_en,name_hi nullable,public_url,body_system nullable,gender nullable stored descriptor. Detail adds fixed en/plain_text, five sections (overview/causes/risk_factors/diagnosis/prevention), causes_items,urgency_note and section references (organization,title,url,date,date_type). Blank text→null; public text/Unicode/quotes preserved without HTML encoding/medical rewriting. Dates are bibliographic meanings, not invented professional reviews. Missing reference mappings remain[].

SubstanceSummary: decimal-string id,canonical_name,registry_url,information_type=`legacy_registry_name`. No identity status/review badge/evidence/content. Unsigned bigint precision preserved in actual MariaDB test. Clients should render strings safely as text and ignore future optional additive fields.

## 7. HTTP Status Behavior

200 valid reads (including empty results);400 malformed scalar/UTF-8;422 missing/invalid/out-of-range/unsupported parameters;404 public resource/unknown V1 route;405 known route with unsupported method,Allow:GET (HEAD body suppressed);500 safe unexpected/serialization/size failure. No login redirects,401/403/token or fictitious429 policy.

All application V1 responses: application/json;charset=UTF-8,Cache-Control:no-store,nosniff; no CORS grant or session cookie. Error messages contain no SQL/trace/path/credentials. Accept does not change JSON format. Host rejection before PHP is explicitly separate (section25).

## 8. Pagination and Limits

Disease list deterministic English name then numeric ID; two bounded count/page reads, default20,max50,meta page/page_size/total/has_more. Beyond final page→[]. Searches fetch11/display10/meta query/limit/has_more. No cursor/search paging/full-count claim. Cause lookup fetches at most101; if over100 it fails safely rather than truncates content. Whole serialized-response256KiB guard similarly fails500 rather than truncating medical text.

## 9. Search Ranking

Reuses48B `searchPublicNames` methods and shared normalizer/LIKE escaping: exact→prefix→contains, name/collation order then numeric ID, rank before LIMIT. EN/HI names for Diseases; normalized canonical names for Substance. Case-insensitive existing collations; `%`, `_`, `=` literal. Duplicate Genital Warts IDs179/199 remain separate. No severity/popularity/personalization/medical/AI/fuzzy scoring.

## 10. Public Visibility / Projection Review

Disease public usable-slug criterion matches48B; source has no invented disease active/approval flag. Query selects explicit fields from diseases/disease_content/body_parts. Existing public cause relationship reused; DiseaseReferenceService supplies only its validated public section mappings. No full knowledge graph, saved status, recent-view updates or Treatment preferences invoked by API.

Substances use existing active/searchable ranked names, with original registry IDs, no alias promotion or rich content. Manual projection inspection and unit/SQL tests verified no passwords/tokens/mobile/account health/documents/sharing/governance fields. Existing web Detail legacy Treatment exception remains confined to web display.

## 11. Authentication / Session Behavior

V1 requires no login/key/token and calls no AuthService/profile-access/membership methods. New namespace skips session configuration/start. API errors/unknown routes also session-free. Bootstrap tests ran with web debug enabled and asserted PHP_SESSION_NONE; HTTP checks confirmed no Set-Cookie and identical JSON with irrelevant Cookie/Authorization headers. No issued tokens or accounts created. Existing genuine authenticated web navigation remained available after API changes.

Web/unversioned AJAX retains existing session/cookie/Auth behavior. Request classification is shared and distinguishes `/api/v1` from `/api/v10`; it honors configured public base path. No configuration/Session/Auth class change.

## 12. CSRF Behavior

No CSRF requirement for read-only public V1 GET. Existing browser state-changing forms and private/auth endpoints retain original protections. No exemption for old mutations and no new POST endpoint.

## 13. CORS Behavior

No Allow-Origin/Allow-Credentials grant, wildcard, reflected Origin or preflight permission. Known-route OPTIONS returns405. Native clients do not need browser-origin permissions; later cross-origin web clients require separately approved policy. HTTP tests verify absence even with Origin header.

## 14. Cache Behavior

No-store for application V1 success/error. No ETag/Last-Modified/application cache/Redis. No session cache-limiter headers on V1. Existing web/AJAX policies unchanged.

## 15. Security Validation

Bound SQL values and fixed page bounds/columns/order, native placeholders, explicit DTO allowlists. Query raw size/Unicode/length/type checks. Tested quotes,apostrophes,HTML/script/svg,SQL-like expressions,UNION SELECT,LIKE metacharacters,invalid arrays/UTF-8 and long inputs. JSON values are actual text, not HTML-encoded. Safe validation/not-found/method/backend envelopes.

API-specific output buffer discards unexpected output before JSON emission; scoped runtime warnings become safe failures even with web debug enabled. Global API exception/fatal branches use generic JSON and safe diagnostic messages. Memory tests cover runtime exceptions,E_USER_WARNING/E_USER_ERROR,discarded output,invalid-UTF8 serialization and response-size guard. These are simulated isolated failures, not a deliberately broken live database or resource-exhaustion test. Existing non-API error handlers remain intact.

## 16. Tests and Exact Counts

| Command/check | Result |
| --- | --- |
| `php tests/public-api-v1.php` |56 database-free checks PASS |
| `php tests/public-api-v1-bootstrap.php` |8 checks PASS with web debug enabled; no session/DB/log writes |
| `php tests/public-api-v1-mariadb.php` |38 actual MariaDB checks PASS; target removed |
| `php tests/public-api-v1-http.php` |561 HTTP assertions PASS, read-only requests |
| PHP lint |15 modified/new PHP files PASS |
| `git diff --check` |PASS; preexisting health-medicines line-ending warning informational |

No existing tests weakened/rewritten. New HTTP test initially assumed encoded NUL reached PHP with422; actual Apache returned404 before application execution. Test now explicitly verifies that transport rejection, while PHP NUL validation remains covered by unit tests. No application/server protection was weakened to force a result.

## 17. MariaDB Validation

Guarded target `kobi_public_api_v1_test_20261014` refused existing-target reuse. After selecting/guarding that target, created six minimal synthetic public-name/content/relationship tables only, with clearly marked nonclinical fixture text; no users/tokens/private/governance fixtures or production edits. Tested actual count/paging,ordered list/ranking before limit,duplicate identities,Hindi names,active/searchable exclusion,missing slug/content/resource,source projection,cause ordering,hostile strings,unsigned bigint and old registry prefix contract.

Finally removed the disposable database; information_schema confirmed removal. Final independent normal connection also found zero named API/search disposable targets. MariaDB10.4.27; session/global SQL modes unchanged. No normal schema/table/data mutation.

## 18. HTTP/API Validation

Actual cookie-free localhost requests covered all four endpoints,default20/custom/max50/out-of-range pages,invalid paging,q/unknown params,malformed UTF-8/arrays,duplicates,source mappings,no private includes and exact field projection. Validated statuses,JSON decoding/envelopes,Content-Type,no-store/no-cookie/no-CORS,unsupported POST/PUT/PATCH/DELETE/OPTIONS/HEAD+Allow,unknown/excluded routes and identical output with Cookie/Authorization/Origin/incompatible Accept.

Old `/api/substances/search` bare keys,prefix order,max10 and old `/search`/Migraine web content verified. HTTP tests did not call symptom matcher/counters, private mutations or create accounts/tokens. Backend500 validated in memory/bootstrap tests; no normal service failure intentionally induced.

## 19. Browser Validation

Genuine in-app browser regression at1366×768 and390×844:

- Existing `/search` loaded and submitted Migraine/Para, showing unchanged groups/rank order.
- Migraine link navigated to existing Disease Detail; Diagnosis and five legacy Treatment cards remained visible.
- Mobile Para→Paracetamol link opened existing registry lookup and completed its original AJAX request with one result.
- DOM measurements showed no horizontal overflow at both sizes; saved mobile image inspected for readable/unclipped controls/results.
- Error/warning logs for tested web surfaces were empty; no CSP violations observed.

Direct API browser navigation was attempted but in-app client blocked JSON URL navigation (`ERR_BLOCKED_BY_CLIENT`). No bypass, browser switch or server change attempted; API response correctness verified through561 HTTP assertions instead. Browser returned to `/search?q=Para`; viewport override reset; existing genuine login/session preserved through ordinary web navigation.

Screenshot: [mobile registry regression](task-49b-mobile-regression.png). No private medication form/account changes or clinical correctness audit performed.

## 20. Regression Results

- Public Search:30 database-free checks PASS.
- Disease References:71 checks PASS.
- Disease Detail:7 scenarios PASS.
- Treatment publication:105 tests PASS, no DB/session/log writes.
- Authentication Phase1:47 database-free scenarios PASS.
- Substance search DOM contract:11 checks PASS (prefill,unchanged API,textContent,cancellation/stale protection/error handling).
- Live old Substance AJAX and web `/search`/Disease Detail checked in HTTP suite and browser.

No JS modified, so no new JS syntax changes. Existing personal-autocomplete implementation/contract unchanged; no live personal medication edits. No unnecessary live symptom submission.

## 21. Schema / Data / Configuration / Git Confirmation

No database source/migration/index/token/API table changes, no normal-KOBI data writes or test records. Normal final public counts:400 diseases,400 content rows,2,224 substances. Current slugs all compatible. Normal session/global SQL mode: `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`. No mode-changing SQL/configuration/environment/credential changes.

Only isolated test target received DDL/fixtures and was removed. Production file inventory confirmed changes confined to listed paths; schema/config/resources/web JS/views and unrelated features unchanged. Read-only Git diff/status inspection; no commit/push/reset/checkout/merge/rebase/index/history mutation. Existing unrelated work preserved.

## 22. Treatment HOLD Confirmation

Effective Treatment policy rechecked **hold**. No Treatment API/projection/data published; no governed candidate/source/reviewer internals returned. Existing web legacy cards remain unchanged. No Treatment schema deployment undertaken.

## 23. Private Health / Governance Boundary Confirmation

No API dependency/join/projection for users,private health records,profile membership,medications/schedules/Due Today,saved checks,documents,sharing,mobile/security state. No Medicine identity/evidence/qualification/review/publication data. Symptom matching/save-context/recordSearch excluded. Public API results do not depend on browser identity/preferences.

## 24. Deferred Scope / Mobile-client Notes

Authenticated APIs/API tokens/private profile/health records/medication/schedule/saved-check/document/sharing APIs; Treatment/rich Medicine/Symptom Checker APIs; ABHA/ABDM;AI/semantic/fuzzy/external search;Hindi narratives;notification APIs;rate limiter infrastructure,cursor paging,cache servers and CORS web-client grants. No49C work begun.

Mobile foundation supports versioned predictable JSON,decimal-string IDs,bounded paging/search,explicit English public content,nullable references and consistent errors without browser sessions. It does not implement mobile auth or offline medical freshness. Markdown contract complete; optional OpenAPI artifact deferred without tooling/dependencies. Hosting HTTPS/abuse limits require separate external-rollout planning.

## 25. Known Limitations / Completion

Application JSON guarantee applies to requests reaching PHP. Local Apache rejects encoded NUL/slash URI components with HTML404 before PHP; server settings unchanged. In-app browser direct JSON navigation blocked, so direct API verification used HTTP. Fatal resource-exhaustion/server outages were not induced; error handling tested with memory/bootstrap fakes. Offset paging does not promise an immutable snapshot across requests; no large-scale load test. Only English narratives and selected public fields, no rich Medicine or clinical suitability interpretation.

**Step 49B PASS — COMPLETE. Stop after this implementation.**
