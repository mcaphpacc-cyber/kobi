# Step 48B — Unified Public Search V1

**PASS.** Implemented and validated 14 October 2026. Step 48A is the scope reference. No subsequent feature started.

## 1. Objective

Provide one simple public knowledge discovery page without diagnosis, recommendations, private-data search or additional search infrastructure.

## 2. Approved Scope

Exactly two groups: public Diseases and legacy searchable Substance names. Normal GET `/search?q=...`, navbar Search link, deterministic exact/prefix/contains ranking, bounded results and unchanged existing APIs. Static Symptom Checker/Discovery links are navigation tools, not matched entities. No Treatment, symptoms, rich Medicine, alias, fuzzy, AI or personal-data search.

## 3. Exact Files Changed

Modified (only these eight existing files during this task):

- `D:/xampp/htdocs/kobi/app/Repositories/DiseaseRepository.php` — separate ranked public-name method.
- `D:/xampp/htdocs/kobi/app/Repositories/SubstanceRepository.php` — separate ranked legacy-name method; existing search method unchanged.
- `D:/xampp/htdocs/kobi/app/Controllers/SubstanceController.php` — safe optional query prefill for the existing lookup.
- `D:/xampp/htdocs/kobi/app/Views/substances/index.php` — escaped input prefill.
- `D:/xampp/htdocs/kobi/public/assets/js/substance-search.js` — use existing form submission once for prefilled query.
- `D:/xampp/htdocs/kobi/app/Views/layouts/app.php` — filemtime version for the changed substance script, matching existing asset conventions.
- `D:/xampp/htdocs/kobi/app/Views/layouts/navbar.php` — public Search link.
- `D:/xampp/htdocs/kobi/routes/web.php` — PublicSearchController import and GET `/search` route.

Created:

- `D:/xampp/htdocs/kobi/app/Controllers/PublicSearchController.php`
- `D:/xampp/htdocs/kobi/app/Services/PublicSearchService.php`
- `D:/xampp/htdocs/kobi/app/Views/search/index.php`
- `D:/xampp/htdocs/kobi/tests/public-search.php`
- `D:/xampp/htdocs/kobi/tests/public-search-mariadb.php`
- `D:/xampp/htdocs/kobi/tests/substance-search.cjs`
- `D:/xampp/htdocs/kobi/docs/task-48b-unified-search-implementation.md`
- `D:/xampp/htdocs/kobi/docs/task-48b-search-mobile.png`
- `D:/xampp/htdocs/kobi/docs/task-48b-search-desktop.png`

The working tree already contained substantial earlier changes/untracked files. Git's whole-worktree diff includes those; they were not made by Step 48B. A SHA-256 comparison against the Step 48A file inventory identified exactly the eleven changed/new production paths above (eight modifications and three additions), no removals, and no unrelated app/routes/database/resources/public changes.

## 4. Search Architecture

PublicSearchController → PublicSearchService → existing DiseaseRepository/SubstanceRepository → PDO through BaseRepository. Reflection DI resolves typed constructors. No aggregate repository, new endpoint, inline JS or framework. Controller renders via `$this->view()`, emits noindex/follow, handles invalid input as422 and unexpected failure as503 with a generic user message. Diagnostics log exception class only, not query, SQL, stack or private data.

## 5. Search Matching / Ranking

One shared normalizer: require string/valid UTF-8, raw input cap4096 bytes, trim/collapse Unicode whitespace, maximum100 code points. Empty/one-character queries show instructions without repository queries. Preserve punctuation and entity identity. Matching uses existing case-insensitive table collations; no transliteration/fuzzy/medical semantics. SQL LIKE metacharacters `%`, `_` and the explicit escape character `=` are escaped as literals. Every placeholder is separately bound for native PDO.

SQL ranks before LIMIT: exact0 → prefix1 → contains2, then canonical display/normalized-name collation order, then numeric ID. Disease uses best EN/HI name tier. Fetch11 per group, display10 with refine notice when more exist. Maximum20 displayed; no pagination or full-count claim. Same-name records remain separate; neither medical relevance nor popularity affects results.

## 6. Disease Behavior

Only disease name/id/slug plus public body/gender disambiguation fields are queried; usable nonempty slug required. No Disease knowledge/overview/session/preference calls. Existing disease searches and homepage suggestions remain unchanged. Results use `/disease/{stored slug}`, show Hindi name where present, and preserve duplicate IDs. Genuine browser showed Genital Warts male/female entries linking to their separate stored slugs; public body-system/gender labels distinguish them without displaying internal disease IDs.

## 7. Substance Behavior

Only active=1 AND searchable=1 canonical registry names are queried. No aliases, descriptions, review/evidence/identity status or approval badges. Existing SubstanceRepository::search, SubstanceService and AccountController API implementation are unchanged; `/api/substances/search` retains its prefix behavior, array contract and10-result bound. Personal health-medicine JS is unchanged.

Substance hits remain explicit legacy registry names, not clinically reviewed/KOBI-approved products or recommendations. No rich Medicine data or new detail route exists.

## 8. URL / Destination Behavior

Disease hits use existing detail routes. Substance hits use existing `/medicines?q={encoded canonical name}` with a minimal prefill plus initial lookup submission; the normal lookup form/API still run as before. No admin/private/governance destination. Static links point to existing `/symptom-checker` and `/discovery` and perform no symptom submission.

Issue encountered: browser cached the previous substance-search.js, so the prefilled input initially had no automatic results. Resolved using the existing filemtime asset-version convention in the layout. Reload then completed the Paracetamol lookup normally; cancellation/version behavior passed DOM tests. No API or CSP change was needed.

## 9. Security Boundaries

Anonymous public GET and genuine authenticated browser both work. No authentication/authorization modifications. Name searches contain bound parameters and fixed ordering/limits. Labels, query and destination attributes are escaped; URLs built locally through existing helpers and encoded slugs/query parameters. SQL-like/HTML/quote inputs are treated as text. No raw backend exceptions exposed. Malformed arrays/invalid UTF-8/overlong input rejected safely. No new rate limiter or security framework.

Queries read only diseases/body_parts/kobi_substances. No users, health profiles, personal medications, documents, saved checks, sharing records, mobile numbers, governance records or symptom matcher/counter/save-context operations participate.

## 10. Accessibility

Visible search label/help, semantic GET form, normal submit button, grouped headings/lists and ordinary links. Accessible error alert/aria-describedby/aria-invalid. Query retained for valid searches; invalid input receives guidance rather than being rendered unsafely. Standard keyboard Enter submission worked on mobile. No focus trap or custom combobox. Body/gender text distinguishes duplicate disease links without relying on color.

## 11. Mobile / Desktop Validation

Genuine in-app browser interaction at **1366×768** and **390×844**:

- `/search` initial/empty/short-query states loaded.
- Mixed-case padded Migraine query normalized and returned its detail link.
- Para displayed both groups; prefix results preceded contains results.
- Paracetamol exact result and existing lookup handoff worked.
- Genital Warts duplicate entries stayed separate; mobile navigation menu reached Search.
- Enter submitted the mobile form.
- HTML-tag query remained literal escaped text and produced no matches.
- Migraine detail link navigated successfully; Diagnosis and five legacy Treatment cards remained present.
- DOM width checks found no horizontal overflow at both sizes; screenshot inspection confirmed readable controls/wrapped labels.
- Browser error/warning logs were empty; no CSP violations observed.

An initial anonymous browser `/search` load showed Login/Register. After the user signed in through the genuine login flow, browser searches showed the authenticated account navigation and worked. Separately, cookie-free HTTP requests proved anonymous queries work. No credentials were supplied to the agent. Browser viewport override was reset; existing authenticated session was retained, not replaced or manipulated. Public search was left open as a reviewable result.

Screenshots: [mobile](task-48b-search-mobile.png), [desktop](task-48b-search-desktop.png). Desktop viewport capture shows the top of the scrollable two-group page; the complete group order was also verified from the DOM.

## 12. Tests Run / Exact Results

| Command/check | Result |
| --- | --- |
| `php tests/public-search.php` | 30 database-free checks PASS |
| `php tests/public-search-mariadb.php` | 18 checks PASS; disposable database removed |
| `node tests/substance-search.cjs` | 11 DOM contract checks PASS |
| Cookie-free localhost HTTP checks | 12 cases PASS:7 query/status/escaping/noindex cases, malformed query,3 existing APIs and explicit anonymous state |
| PHP lint for10 production files plus2 PHP tests | 12 files PASS |
| `node --check public/assets/js/substance-search.js` | PASS |
| `git diff --check` | PASS; existing health-medicines line-ending warning informational |

Focused tests cover normalization, short queries/no DB calls, bad input, safe projection, duplicate IDs/labels, encoded destinations, caps/refine flags, controller public behavior and backend errors/XSS. Actual MariaDB repository tests prove rank-before-limit, case handling, exact/prefix/contains hierarchy, duplicate tie ordering, missing-slug/disabled/nonsearchable exclusion, Hindi matching, literal wildcard/SQL/HTML strings and legacy API row semantics. JS tests prove prefill submission, unchanged endpoint, empty-page no request, textContent rendering, cancellation, stale-response suppression and failure state.

Unexpected-backend-failure validation used memory test doubles, not a deliberately broken normal database. Synthetic name records existed only in the named disposable test database, never in normal KOBI.

## 13. Regression Results

- `php tests/disease-references.php`:71 database-free checks PASS.
- `php tests/disease-detail-presentation.php`:7 scenarios PASS.
- `php tests/treatment-publication.php`:105 tests PASS, no database/session/log writes.
- `php tests/auth-phase1.php`:47 database-free scenarios PASS.
- Anonymous live GET `/api/substances/search?q=Para`, `/api/diseases/search?q=Migraine`, `/api/search/suggestions?q=Migraine`: returned expected public matches.
- Existing public registry form/search rendered and returned Paracetamol through its unchanged API; focused isolated tests verified old prefix ordering/row keys/10-limit.
- No live personal medication create/edit/save was performed; personal autocomplete script is unchanged, and its shared substance API contract was verified. No repeated symptom-checker matching submissions were made.

## 14. Database / Schema Confirmation

**No normal KOBI schema/data deployment or data writes performed by this implementation/tests.** No migration, structure.sql edit, data cleanup/backfill or production fixtures. Ordinary user login and browser session/navigation were normal application operations, not manual DB manipulation.

MariaDB tests created exactly `kobi_public_search_test_20261014`, refused preexisting target reuse, selected it before DDL/inserts, created three small synthetic name tables, and removed that database in finally after tests. Post-removal information_schema check passed. No retained disposable environment.

Normal SQL-mode check after validation: `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`. Isolated suite verified session/global modes unchanged. No mode-changing statements or configuration changes. Database source files all matched the pre-task file inventory.

## 15. Treatment HOLD / Other Boundaries

Effective policy rechecked as **hold**. No Treatment query or publication service is called by unified search. Legacy Disease Detail cards were preserved. Medicine governance/publication, Disease content/references, Symptom Checker backend/API/scoring, Auth, Dashboard, Health Profile, medication persistence/schedules, mobile verification and sharing were unchanged. No source/evidence/review/approval records created. Git read-only status/diff inspection only; no commit/push/reset/checkout/merge/rebase/index mutation.

## 16. Private Health Data Exclusion

Public results are identical in scope for anonymous/authenticated users. The service has only DiseaseRepository/SubstanceRepository dependencies. Its query projections exclude private tables and metadata by construction; tests and source inspection verify the allowlists. The current page's normal authenticated navbar is existing layout behavior, not searched or returned account data.

## 17. Limitations / Deferred Scope

Name lookup only; no medical correctness evaluation or suitability ranking. Contains matching uses existing collations and scans small public name datasets; no load-test/large-scale performance guarantee. Ten results per group with refine notice, no pagination. No aliases/fuzzy/spell correction, symptom/Treatment/body-system matched groups, private search, AI, rich Medicine pages, notifications or external API. Existing registry collisions remain distinct; no identity cleanup. Backend failure tested synthetically. Personal medication forms were not exercised or mutated.

**Step 48B PASS — COMPLETE. Stop after this feature.**
