# Step 53B — Personal Medicines API Implementation

17 October 2026, Asia/Calcutta. **PASS — read-only Personal Medications list/detail complete.** Step 54 has not been started.

## 1. Executive summary

Added only the two approved `/medications` GET endpoints. Existing Step 50B admission and Step 52B principal-aware profile authorization are reused. Medication SQL selects explicit columns and constrains detail by both medication and authorized profile ID. Browser medication functionality, validation, memberships and schedules remain unchanged. No normal database writes, production credentials/families, schema changes or Git operations occurred.

## 2. Exact routes

- GET `/api/v1/health-profiles/{profileId}/medications`
- GET `/api/v1/health-profiles/{profileId}/medications/{medicationId}`

No `/medicines` alias, search, public, mutation or schedule route. The approved implementation route name supersedes the readiness report's proposed noun. Existing browser `/medicines` routes are untouched. Anchored routing does not collide with `/health-profiles/{profileId}`. A nonnumeric single detail segment such as search fails medication-ID validation with 422; no search/action executes. Any future approved literal action must be registered before the parameter route, following existing router conventions.

## 3. Authentication reuse

ApiAuthController uses its existing admittedBearer(false) → ApiAuthGuard → ApiAuthService → ApiAuthPrincipal path once per request, with unchanged header conflict checks, token format/lifetimes/digests, account-state checks, refresh/replay/revocation and error envelope. New medication actions use the existing respond()/PublicApiResponse and trusted principal. No duplicate parser/guard/auth service or session impersonation. Existing auth, identity and profile action bodies remain unchanged; the controller receives one new API read service dependency.

HTTPS is required. GET query parameters are rejected with the existing 400 BAD_REQUEST after admission; GET body/Content-Type, client user/profile fields, cookies and custom identity headers do not select an actor. No browser session or CSRF is required. Existing non-logout 120/family/minute and failed-access 60/direct-peer-IP/minute policy remains; no new limiter/key/policy. Its private runtime-file accounting remains an operational side effect, not a database write from GET.

## 4. Authorization mechanism

ApiHealthMedicineService first calls ApiHealthProfileService::detail(principal,profileId). That existing seam validates profile ID and calls **HealthProfileRepository::findAccessible(profileId,principal.userId)**. Active membership and active profile are required; existing owner/editor/viewer reads, including legitimately shared profiles, are preserved. No assumption of creator ownership or request-supplied membership.

Only after profile admission does collection call HealthMedicineRepository::findApiByProfile(authorizedProfileId). Detail calls **findApiByIdInProfile(medicationId,authorizedProfileId)** with SQL `WHERE id=:id AND health_profile_id=:profile`. It never fetches a medication by ID alone and then inspects ownership. A record cannot be reused under another authorized profile, even when the account may access both profiles.

## 5. Exact projections and collection decision

The current schema entity is health_medicines, key id, direct health_profile_id FK. No new entity/field/link was invented. Both list entries and detail use exactly:

| Field | JSON type/nullability |
| --- | --- |
| id | Non-null decimal string |
| brand_name | Non-null stored string |
| generic_name | String/null |
| strength | String/null |
| dose | String/null |
| frequency | String/null |
| route | String/null |
| status | Non-null current/stopped/historical string |
| started_on | Stored date string/null |
| stopped_on | Stored date string/null |

Recorded text/date/null values are preserved, not interpreted or clinically rewritten. Unknown stored status fails through the existing safe generic 500 path, rather than being fabricated/coerced into an allowed status. The service constructs the DTO explicitly even though new SQL already selects only approved columns.

HTTP 200 uses the established success/data/meta envelope, meta exactly api_version=v1. Collection data is an array; an authorized empty profile returns []; detail data is an object. Ordering matches the existing medication list: current, stopped, historical; started_on DESC; id DESC tie-break. No pagination or filters were added: existing functionality is a simple per-profile list and the baseline has three medication records across the normal application. That count is supporting local evidence, not a maximum or promise of future usage. No realistic V1 requirement justified adding paging. Existing 256-KiB response guard fails safely rather than silently truncating an unexpectedly large collection. All query parameters including page/status/user_id are rejected.

## 6. Privacy and errors

No notes/prescriber/provider/pharmacy, profile/member/creator IDs, demographics/contact/mobile, invitation/sharing secrets, credentials/token digests/session IDs, internal audit timestamps, diagnoses/symptoms/diseases, documents/summary, Medicine governance/ingredient identity, schedules/checkoffs/Taken/Skipped, risk scores or recommendations. Dose/strength/frequency/route are recorded personal values, not generated advice. No ingredient/registry/approval joins or enrichment. Clients must render strings as text.

Missing/malformed/invalid/expired/revoked credentials keep generic 401 UNAUTHENTICATED with the existing message/challenge. Inaccessible, revoked-membership, archived and nonexistent profiles share the existing safe 404 “Health profile not found.” Missing and other-profile medication details share 404 NOT_FOUND “Medication not found.” Profile ID validation remains unchanged; medication IDs require positive decimal syntax, no leading zero/sign and signed-INT range 1–2147483647; invalid values return 422 VALIDATION_ERROR “Use a valid medication ID.” Unexpected failures retain generic 500 with no SQL/stack detail; existing 429/Retry-After and fail-closed 503 apply.

All responses use JSON UTF-8, no-store, nosniff and no cookie/CORS grant. Unsupported known-route methods give 405/Allow GET; HEAD has no body. No positive authorization cache was added.

## 7. Repository/service changes

The existing browser HealthMedicineService depends on browser HealthProfileService identity; its existing detail fetch also uses findById before checking profile association. Those mature browser methods were preserved, not reused incorrectly under Bearer or rewritten. New narrow methods on the existing HealthMedicineRepository provide explicit-column, parameterized profile-bound reads through BaseRepository. Existing methods/SQL/date/write behavior are untouched. New ApiHealthMedicineService supplies only API read orchestration and projection; it reuses existing profile authorization rather than creating an access abstraction.

## 8. Focused tests

**28 unit checks PASS** (`tests/api-medications.php`): exact ten-field whitelist and null preservation; principal-based profile selection before medication queries; no unscoped detail fetch; empty collection; cross-user/cross-profile/missing detail parity; invalid ID before query; existing statuses/unknown stored value; SQL spy proves explicit minimal columns and combined child/profile predicate; no session.

**624 PHP/cURL HTTPS integration checks PASS** (`tests/api-medications-http.php`), actual routing/controller/service/repository with an isolated guarded MariaDB database:

- Two accounts, three independent API families; own, shared viewer, editor, archived/revoked/inaccessible/missing profiles and authorized empty profile.
- Zero/one/many medication collections, exact deterministic status/date/id order and DTO types/nulls; sensitive notes/prescriber values stored but excluded.
- Correct authorized detail and same medication ID under a different profile, including another profile the actor may access. All child reads constrained to profile.
- Missing/malformed/unknown/expired/revoked access for both endpoints; generic errors, no-store/JSON/nosniff/challenge; no cookies/CORS/CSRF/session dependency. Query/body/custom-header/cookie identity overrides denied or ignored as specified.
- All unsupported methods/HEAD, invalid IDs, absent aliases/nested schedules, safe handling of nonnumeric detail segments.
- Existing `/me` and profile projections, one guard-family budget increment and 429/Retry-After under unchanged policy, next-read membership revocation, safe generic 500 for invalid stored enum.
- Snapshot equality of users/profiles/memberships/medications/API sessions/refresh history across GETs. Expiry/enum/membership fixture changes confined to the disposable database. Synthetic API families revoked through normal lifecycle before the whole disposable database was removed.

**652 focused checks passed.** No expectations were weakened. The isolated upstream Logger is the existing in-memory fixture stub; server diagnostics are discarded. New production code adds no logging. Exact DTO/SQL tests exclude credentials and private extra fields; existing auth unit tests check no raw credential content in auth log messages. No normal credentials were used, so no claim of a newly performed production-password log scan is made.

## 9. Fresh regression results

| Suite/check | Result |
| --- | --- |
| API identity unit | 44 PASS |
| API identity verified HTTPS | 345 PASS |
| Health Profiles API unit | 20 PASS |
| Health Profiles API verified HTTPS | 494 PASS |
| API auth unit | 103 PASS |
| API auth limiter | 13 PASS |
| auth-phase1 | 47 database-free scenarios PASS |
| Medication dates | 26 PASS |
| Medication schedules | 52 PASS |
| Medication autocomplete JS | 16 PASS; native key/browser limitation remains documented by existing test |
| Substance-search JS | 11 PASS |
| Health Profile validation | 57 checks and 4 redirect scenarios PASS |
| Health Dashboard | 76 PASS |
| Dashboard guidance | 76 guidance checks PASS, plus its dependent dashboard checks |
| Public API unit | 56 PASS |
| Public API bootstrap | 8 PASS |
| Public API read-only HTTP | 561 PASS |
| Public Search | 30 PASS |
| PHP syntax | All seven changed/created PHP files PASS |
| Apache syntax | Syntax OK |
| Forwarding scope | 11 matching/nonmatching paths PASS, single mechanism |

Step 50B's full ten-minute/458-check production acceptance and unrelated enormous suites were not rerun. Existing auth unit/limiter and fresh identity HTTPS tests cover the unchanged lifecycle including login/refresh/reuse/device isolation/logout/logout-all. Profile authorization and public reads were freshly tested. No browser UI changed; relevant browser service/validation/DOM regressions passed. No genuine Google callback, email delivery or new browser key-event test is claimed.

## 10. Normal Apache and 11. HTTPS/TLS

Credential-free normal Apache GETs to both approved medication routes returned **401 UNAUTHENTICATED**, cURL error **0**, no-store and TLS verification result **0**, with peer verification enabled and hostname verification 2. Authenticated success and denials were verified in the controlled disposable HTTPS environment with PHP/cURL, explicit test-certificate trust and verification enabled. No production token family was created to perform positive normal-Apache testing because normal database writes are forbidden here. This matches the task's minimum normal-401/controlled-success validation boundary; it is not a claim of normal positive-authenticated smoke. Browser CA trust limitations remain informational; no TLS bypass/certificate remediation occurred.

## 12. Database/schema integrity and 13. SQL mode

Before editing, captured SELECT-only counts and typed row hashes for relevant normal tables, plus SELECT-only information_schema column/index/constraint inventory and trigger body/context metadata digest. Repeated the same reads after tests: **all counts, row hashes, schema metadata digest and SQL modes are exactly identical**.

| Normal table/state | Before | After |
| --- | --- | --- |
| health_medicines | 3 | 3 |
| health_profiles | 14 | 14 |
| health_profile_members | 17 | 17 |
| health_medicine_schedule_slots | 0 | 0 |
| health_medicine_schedule_checkoffs | 0 | 0 |
| user_api_sessions | 11 | 11 |
| user_api_refresh_tokens | 21 | 21 |
| Active API families | 0 | 0 |

Schema inventory SHA-256 before/after: ecb23806f512d2d3a6f8ff33e09544825b042db9b697ce841bb8f8834534a087. Session/global SQL mode both remain **NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION**. No normal schema/data write, SQL-mode change, migration, index or trigger was executed. database/structure.sql is unchanged. Full rows were read internally only for integrity hashing, not exposed in API/log/report output; only safe counts/digests were printed.

## 14. Production-data integrity

Medication rows, profile/membership rows, schedule/checkoff rows and all API family/refresh rows match the pre-implementation hashes, not merely counts. No user or health data was seeded, edited, deleted or repaired; no normal tokens/families/sessions were created/revoked. Relevant API/profile/auth/schema/.env hashes and scoped edits were reviewed without Git. Unrelated medication/browser/schedule/governance code was not changed.

## 15. Configuration/forwarding

The current forwarding rule did not cover nested medications, so the existing single rule was minimally extended:

```apache
RewriteCond %{HTTP:Authorization} .
RewriteRule ^api/v1/(?:auth/|me$|health-profiles(?:/[^/]+(?:/medications(?:/[^/]+)?)?)?$) - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

Existing auth/me/profile coverage stays intact. Only collection/single medication segments are added; nested schedule, `/medicines` alias, summary and unrelated paths remain outside forwarding scope. Invalid single medication segments reach ID validation rather than executing actions. No duplicate mechanism, global Apache/PHP/TLS setting, .env, limiter-key or unrelated configuration change. Apache syntax passes; no restart/certificate change was performed. API admission policy is unchanged.

## 16. Disposable cleanup

All three disposable HTTPS integration runs removed their database, synthetic users/profiles/memberships/medications/token rows, loopback servers, test certificates/private keys and private limiter files. Final SELECT-only schema census found **no disposable API databases**. Normal active family count remains zero; no production history deletion. Test source remains as regression tooling.

## 17. Exact file inventory

Modified:

1. `app/Controllers/ApiAuthController.php` — inject API medication service, add two GET actions using existing admission/response helper.
2. `app/Repositories/HealthMedicineRepository.php` — add two narrow explicit-column profile-scoped read methods; existing browser methods preserved.
3. `routes/web.php` — register exactly the two approved GET routes.
4. `public/.htaccess` — authorized scoped extension/comment only.
5. `tests/api-identity.php` — supply new controller dependency; all existing assertions retained.
6. `docs/authenticated-api-v1.md` — final medication read contract and current scope context.

Created:

7. `app/Services/ApiHealthMedicineService.php` — existing profile admission then medication read/DTO.
8. `tests/api-medications.php` — focused unit/SQL/projection tests.
9. `tests/api-medications-http.php` — isolated verified HTTPS/MariaDB authorization/privacy tests using existing helpers/router/TLS harness.
10. `docs/task-53b-personal-medicines-api-implementation.md` — this report.

## 18. Limitations

Positive normal-Apache authenticated testing was intentionally excluded to honor the prohibition on normal token-family/data changes; controlled authenticated success and normal no-credential failures passed. No new genuine browser interaction was needed for an API-only change. The unpaginated collection retains the existing response-size limit; no silent truncation or speculative pagination was added. Text/dates are stored personal record values, not independently verified medical information. Existing admission-versus-concurrent-revocation behavior remains unchanged; no new cross-request snapshot guarantee is claimed.

## 19. Schedules / Due Today

**NOT IMPLEMENTED.** No schedule/checkoff/history/reminder/adherence API, dose calculation, interaction/prescribing logic, governed Medicine linkage, sharing, mobile verification, general health record, documents or summary feature. Existing schedule business behavior passed regressions and its normal rows remain unchanged.

## 20. Final result

**PASS — Step 53B complete.** Schema/production data/SQL mode/authentication behavior preserved; focused and relevant regression validation passed; disposable fixtures removed; no raw credentials exposed; no Git operations. **Step 54 NOT STARTED.** Stop here.
