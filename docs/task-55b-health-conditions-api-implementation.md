# Step 55B — Authenticated Medical Conditions API

Date: 2026-10-17. Medical Conditions only; no generic Health Records resource.

## 1. Implementation Summary

Implemented the two approved private GET endpoints using existing bearer admission, active profile membership authorization, the existing Conditions repository and an API-specific five-field projection service. Collection is bounded and paginated; detail is bound to both child and profile in SQL. Browser Conditions methods, mutation validation, views, Dashboard, Medical Summary, medicines, public APIs and authentication logic remain unchanged.

## 2. Routes

- GET `/api/v1/health-profiles/{profileId}/conditions`
- GET `/api/v1/health-profiles/{profileId}/conditions/{conditionId}`

No aliases, universal record IDs, generic health-record union or mutation routes. Known routes with other methods retain 405/Allow GET; HEAD body is empty.

## 3. Authentication

New thin actions reuse `ApiAuthController::admittedBearer(false)` → existing `ApiAuthGuard`/`ApiAuthService` → principal. Admission occurs exactly once per request and uses the existing non-logout family budget. No token parser/type, authentication mechanism, session dependency, CSRF requirement for GET or CORS grant was added. Missing/malformed/invalid/expired/revoked credentials retain generic 401 UNAUTHENTICATED with Bearer challenge. Existing limiter 429/Retry-After and fail-closed behavior remain.

The existing response helper gained an opt-in paginated-data/meta branch used only by the new collection. Existing actions use its unchanged default envelope and error handling. Identity unit setup gained the required Conditions dependency; assertions were not changed.

## 4. Profile Authorization

`ApiHealthConditionService` first invokes existing `ApiHealthProfileService::detail(principal,profileId)` → `HealthProfileRepository::findAccessible(profileId,principal.userId)`. Both profile and membership must be active. Owner/editor/viewer read access remains. Authorization precedes record count/list/detail SQL and pagination validation. Neither `created_by` nor body/query/custom header/cookie identity replaces the principal.

Invalid profile IDs retain 422. Missing, inaccessible, archived, revoked-member and another user's profiles share existing safe 404 `Health profile not found.`. No profile-existence disclosure through a new 403 distinction.

## 5. Condition Ownership Enforcement

Collection count and rows both use `WHERE health_profile_id = :profile`. Detail uses `WHERE id = :id AND health_profile_id = :profile LIMIT 1`, with parameters for both requested condition and authorized profile. No fetch-by-ID-then-check path. A child under another profile remains 404 even if the actor can independently access both profiles. Missing and wrong-profile conditions share `Medical condition not found.`.

The existing `HealthConditionRepository` contains three narrow API methods: countApiByProfile, listApiByProfile, findApiByIdInProfile. Existing browser methods and their SQL are unchanged. Data access remains through BaseRepository, with bound profile/child IDs. LIMIT/OFFSET use bounded integer interpolation following the existing public Disease repository convention, never raw query text.

## 6. API Projection

Both collection rows and detail contain exactly:

```text
id: decimal string
condition_name: stored string
status: active | resolved | chronic | historical
diagnosed_on: YYYY-MM-DD | null
resolved_on: YYYY-MM-DD | null
```

Only these columns are selected for returned rows. The service constructs an explicit whitelist, checks the stored status domain and validates non-null calendar-date representation. Impossible stored status/date values cause the existing safe internal error; no values are silently repaired, rewritten or exposed inconsistently. Read projection does not invoke mutation validators, infer a diagnosis, enforce new clinical ordering rules or reject a valid stored future date merely because it is future.

Existing success/data/meta and error/code/message/meta envelopes, JSON UTF-8, no-store and nosniff remain. Detail metadata is only api_version v1. Collection adds existing pagination metadata to the same envelope.

## 7. Pagination

Matches public V1 conventions: `page` default 1/max 10000; `page_size` default 20/max 50. Only scalar UTF-8 decimal strings without sign, leading zero or fraction are accepted. Unknown keys and invalid ranges/formats return 422; array/non-string/invalid UTF-8 values return 400. Sizes above 50 are **rejected**. Detail rejects query parameters with 400. PHP's existing repeated-scalar query parsing behavior is unchanged.

Collection meta: api_version, page, page_size, integer total and boolean has_more. Empty profiles and pages beyond the last page return data `[]`. Repository limits are additionally clamped defensively to 1–50 and offsets 0–499950. No unbounded mode, new search/filter/sort convention or caching. Count and rows use separate reads; concurrent edits can affect page consistency, as with existing pagination.

## 8. Ordering

Preserves the existing browser repository ordering exactly:

1. Status CASE rank: active, chronic, historical, resolved, other.
2. diagnosed_on DESC (null follows dated values within the same status).
3. Numeric id DESC as deterministic tie-breaker.

This intentionally follows Conditions repository ordering, not the different internal Medical Summary array sort. No count definition or existing browser ordering changed.

## 9. Privacy Boundary

No notes, doctor_hospital/providers, documents/attachments, paths/file metadata, demographics, profile/member/user IDs or roles, sharing/access logs, credentials/authentication/internal governance data or other clinical categories are selected/exposed. No joins or sharing/summary/document service calls occur in the Conditions endpoint. Existing medicines remain available through their separate resource; allergies/procedures/insurance/schedules/saved checks are not added. Stored labels remain sensitive user-recorded plain text, not verified medical claims; clients must render them safely.

GET performs no database writes. Existing private limiter-file accounting remains; no database last-seen/audit/snapshot/share write is introduced.

## 10. Database / Schema

No schema changes. No structure.sql change, migration, table, index, trigger, constraint or SQL-mode modification. Uses existing health_conditions and canonical profile/member authorization. Actual normal conditions SHOW CREATE matches authoritative structure.sql after ignoring only formatting and next AUTO_INCREMENT value. SQL mode remains the established non-strict mode; projection checks do not depend on permissive ENUM coercion.

## 11. Files Changed

Modified:

- app/Controllers/ApiAuthController.php
- app/Repositories/HealthConditionRepository.php
- routes/web.php
- public/.htaccess
- tests/api-identity.php (constructor dependency setup only)
- docs/authenticated-api-v1.md

Created:

- app/Services/ApiHealthConditionService.php
- tests/api-health-conditions.php
- tests/api-health-conditions-http.php
- docs/task-55b-health-conditions-api-implementation.md

The existing forwarding rule did not match Conditions; this necessity was explained before editing. Its single scoped expression now also matches exactly the nested condition collection/detail paths. Existing scopes remain; deeper notes, generic health-records, unrelated clinical or public paths are not admitted by the extension. No other Apache/PHP/TLS/limiter/environment/dependency configuration change or restart was performed. No unrelated source changes or deleted files.

## 12. Tests and Validation

- `tests/api-health-conditions.php`: **65 unit checks PASS**, no database/session/log writes.
- `tests/api-health-conditions-http.php`: **1106 checks PASS**, real application bootstrap, MariaDB and certificate-verified loopback HTTPS. Check count includes per-row projection and per-request transport assertions; it is not a count of independent clinical cases.
- Fixture: 55 conditions under the main profile plus records under other authorized/unauthorized/shared/archived/revoked profiles. Exercises default20/max50, multiple/partial/beyond-end pages, status/date/ID ties and null dates, exact DTO, ownership and read-only side effects.
- Missing/malformed/invalid/refresh-as-access/expired/revoked credentials, owner/editor/viewer, inaccessible/cross-user/missing/archived/revoked profiles, wrong-profile children even between accessible profiles, invalid IDs/paging/unknown inputs, method/alias rejection, private fields, generic failures, safe 500 on invalid stored status, cookie/body/header independence and one existing limiter admission/429 are covered.
- Unit tests additionally cover malformed stored date representations and no domain reads on profile/paging denial. SQL spies check minimal selected columns and both detail predicates.
- One initial new fixture assertion incorrectly expected a corrupt status row on page1. Canonical ELSE ordering moved it to the final page. The new test was corrected to request that page, then the entire focused HTTPS suite passed. No application behavior or unrelated test expectation was weakened.
- All **7** changed/new PHP files pass syntax checks. Apache syntax is OK. **17** forwarding scope cases pass. New source/tests have no conflict markers or trailing whitespace; source/call-site changes and file inventory were reviewed without Git.

## 13. HTTP / HTTPS Validation

Normal Apache, both credential-free collection/detail URLs: **401**, exact generic V1 JSON, no-store, Bearer challenge, no cookies/CORS. PHP/cURL used SSL_VERIFYPEER true and SSL_VERIFYHOST 2, existing configured trust, no redirects or bypass; **TLS verification result 0** for both.

Authenticated collection/detail, authorized/unauthorized profiles, headers/envelopes and no browser-session dependency passed using real application bootstrap in the isolated HTTPS harness. Its client verified its own ephemeral localhost certificate with peer/hostname verification enabled. No normal production API family was created, no normal login/logout/refresh was run and no password was requested. Authenticated normal-Apache success was not exercised; this practical limitation does not substitute anonymous denial for authenticated acceptance, which is established in isolation.

## 14. Regression Results

All completed database-free regressions passed:

| Suite | Checks/scenarios passed |
|---|---:|
| api-auth.php | 103 |
| api-auth-rate-limit.php | 13 |
| api-identity.php | 44 |
| api-health-profiles.php | 20 |
| api-medications.php | 28 |
| api-medical-summary.php | 29, plus reused Dashboard 76 |
| health-profile-validation.php | 57 plus 4 redirect scenarios |
| health-dashboard-guidance.php | 76 guidance; reruns Dashboard dependency |
| auth-phase1.php | 47 scenarios |
| public-api-v1.php | 56 |
| public-api-v1-bootstrap.php | 8 |
| public-search.php | 30 |

Integration regressions:

| Suite | Result |
|---|---|
| api-identity-http.php | PASS 345 |
| api-health-profiles-http.php | PASS 494 |
| api-medications-http.php | PASS 624 |
| api-medical-summary-http.php | PASS 508 |
| api-auth-mariadb.php | PASS 65 |
| api-auth-http.php | PASS 326 |
| public-api-v1-http.php | PASS 561 |

No standalone existing Conditions CRUD suite was present. Existing Dashboard/summary suites exercise browser assembly and condition group behavior; profile validation covers the profile service/controller/view. Browser views, condition mutation validation and original repository methods were not edited. No new browser UI was created or browser mutation audit repeated.

## 15. Production Data Integrity

Normal database operations in this task are SELECT/SHOW only. No normal clinical/profile/member/share/log/API-family writes or sensitive row dumps/hashes. Safe baseline: profiles14, members17, conditions4, medicines3, allergies3, procedures2, documents2, shares13, share-access logs30, API families11, refresh history21, active API families0.

Final SELECT/SHOW comparison: **all eleven row counts, clinical count distributions and eleven normalized SHOW CREATE definition digests match the baseline exactly**. Conditions remain **4**, existing API families/history remain **11/21**, active API families remain **0**. Session/global SQL mode is unchanged: `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`. Schema digests exclude only next AUTO_INCREMENT values, not structural objects, and do not include mutable index CARDINALITY statistics. No normal authentication lifecycle was exercised.

All test DDL/data writes were confined to guarded disposable `kobi_api_auth_50b_test_20261016`; existing fixtures refuse reuse of an already-existing target. Suites ran serially. Synthetic API families were revoked through the normal test lifecycle before final fixture removal; the first unsuccessful fixture assertion also ran its finally cleanup. Final read-only check confirms the disposable database and owned HTTPS harness directories are absent. Harnesses removed their loopback processes, ephemeral certificate/key and private counter files. No normal record, snapshot/share/log, membership or API-family row was created, edited or deleted.

File-hash inventory confirms only the stated six existing files changed and four files were created; no deletions or unrelated modifications. In particular structure.sql, .env, dependency files, authentication/guard/limiter implementations, browser views/services, profile authorization, existing summary assembly and other clinical/governance implementations remain unchanged.

## 16. Limitations / Deferred Scope

Authenticated normal-Apache success was not performed; controlled valid-bearer HTTPS success plus normal Apache denial and forwarding scope checks are documented above. No browser UI interaction was needed for this API-only task. Existing browser/auth/public API behavior is covered by unchanged source and relevant regressions, not a claim that every browser flow was repeated. Pagination is not an immutable cross-request snapshot. Safe production counts do not prove byte-for-byte equality of every row; no normal mutating operation was performed.

No generic Health Records, allergies/procedures/documents/insurance API, notes/providers, medication scheduling/Due Today API, saved symptom checks API, medical inference or additional feature was implemented. No Step 56 work.

## 17. Final Status

**PASS.** Both approved Medical Conditions routes, bearer/profile authorization, SQL child/profile binding, exact five-field projection, integer-bounded pagination, canonical ordering, privacy, relevant regressions, HTTPS and cleanup checks passed. No normal production data/schema write or SQL-mode change. Only the necessary scoped .htaccess extension changed configuration. No generic Health Records API or Step 56 work. Git operations: **NONE**.
