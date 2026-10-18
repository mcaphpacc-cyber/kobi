# Step 52B — Authenticated Health Profiles API Implementation

17 October 2026, Asia/Calcutta. **PASS — Step 52B complete.** Validation scope and normal-environment limits are stated below. Step 53 has not been started.

## 1. Summary

Implemented read-only accessible profile selection using existing Bearer admission and unchanged HealthProfileRepository authorization. No profile creation/edit/membership behavior changed. A small API-specific service provides explicit-principal orchestration/projection without invoking the browser-session HealthProfileService, creating a second authorization model or adding a framework.

The final implementation follows the Step 52B instruction to avoid pagination absent a demonstrated realistic requirement. Collection remains the existing simple accessible-profile list. It also excludes role/internal authorization metadata: the deliberately minimal projection has three fields, narrower than the earlier readiness proposal.

## 2. Routes and contract

- GET `/api/v1/health-profiles`
- GET `/api/v1/health-profiles/{profileId}`

Both require HTTPS and an existing access Bearer token. No query parameters are accepted (including user_id, page or page_size). After HTTPS and one guard admission, query input returns 400 BAD_REQUEST, “Query parameters are not accepted.” GET bodies and custom identity headers cannot supply an actor. No cookie/session/CSRF dependency or CORS permission was introduced.

HTTP 200 uses the existing envelope: success=true, data, meta={api_version:"v1"}. Collection data is an array; an empty accessible set returns []. Detail data is an object. Positive decimal profile IDs must fit the current signed INT column (1–2147483647), without leading zeros/sign/fractions; invalid IDs return 422 VALIDATION_ERROR, “Use a valid profile ID.” Known unsupported methods use existing router 405/Allow GET; HEAD has no body. There are no aliases or nested clinical endpoints.

## 3. Authentication reuse

The existing ApiAuthController::admittedBearer(false) retrieves the same ApiAuthPrincipal through the unchanged ApiAuthGuard/ApiAuthService. Header extraction/conflict handling, token parsing, session/family/stamp checks, refresh/revocation, limiter policy and login/logout action bodies are unchanged. New profile actions use the existing respond() and PublicApiResponse paths. ApiAuthController receives one additional API projection-service dependency; existing identity tests were updated only to supply this unused dependency, without weakening assertions.

`/api/v1/me` retains its exact four fields and action body; its fresh unit and isolated HTTPS regressions passed. No browser AuthService identity or synthetic PHP session is used for profile reads.

## 4. Authorization implementation

ApiHealthProfileService::accessible(principal) calls **HealthProfileRepository::findAccessibleByUser(principal.userId)**. Detail validates path syntax then calls **findAccessible(profileId,principal.userId)**. The unchanged methods join active membership for that user to an active profile. Owner/editor/viewer read behavior is preserved, including legitimately shared profiles. created_by alone does not confer access. Membership supplied in the request is ignored.

Missing/inaccessible/archived/revoked-membership detail always returns identical 404 NOT_FOUND, “Health profile not found.” No creator/owner/membership details appear in the error. A revoked membership denies the next request. No read calls ensureSelfProfile(), creates records, updates last-seen state or edits memberships.

## 5. Explicit projections

Both list entries and detail contain exactly these non-null fields:

| Field | JSON type |
| --- | --- |
| id | Positive decimal string |
| full_name | Stored display-name string |
| profile_type | self/family/dependent string |

Unknown stored profile types produce an unexpected-data exception handled by the existing safe generic API 500 path, not a fabricated valid type. No raw repository row is serialized. Existing repository SELECTs list columns explicitly; no new SELECT * was added. The unchanged global 256-KiB response guard remains: unexpectedly oversized collections fail safely rather than being silently truncated. There is no paging contract or new data cap.

## 6. Privacy/cache boundary

No role/membership/account/creator ID, status metadata, DOB/age/gender, timestamps, contact/mobile, notes, secrets/invitation tokens, password/token/digest/stamp, records/diagnoses/symptoms/medications/schedules/documents/summaries/sharing/Treatment/Medicine data is returned. Names and profile types are themselves private health-context information and are disclosed only after membership authorization. API clients should render them as text.

PublicApiResponse continues JSON UTF-8, Cache-Control: no-store, nosniff, generic errors and no Set-Cookie/CORS grants. Missing/malformed/invalid/expired/revoked credentials use existing generic 401 UNAUTHENTICATED with Bearer challenge, subject to unchanged abuse controls. Unexpected infrastructure exceptions use the existing bootstrap safe 500 behavior, not raw SQL/exception text.

## 7. Files changed/created

Modified:

1. `app/Controllers/ApiAuthController.php` — profile service dependency, shared profile GET action wrapper and two public actions; original auth/identity action semantics retained.
2. `routes/web.php` — exactly two GET registrations.
3. `public/.htaccess` — extend the existing single conditional forwarding rule for collection/single profile paths only.
4. `tests/api-identity.php` — supply the new dependency in the existing unit harness; assertions unchanged.
5. `tests/api-identity-http.php` — add an explicit library guard to reuse verified request/assertion helpers; default test execution unchanged and rerun successfully.
6. `docs/authenticated-api-v1.md` — authoritative routes, projection, errors, no-store and authorization contract.

Created:

7. `app/Services/ApiHealthProfileService.php` — principal-based repository calls and three-field DTO.
8. `tests/api-health-profiles.php` — database-free projection/orchestration/ID tests.
9. `tests/api-health-profiles-http.php` — guarded disposable MariaDB + verified PHP/cURL integration and authorization tests, reusing existing TLS/router fixture infrastructure.
10. `docs/task-52b-authenticated-health-profiles-implementation.md` — this report.

No HealthProfileRepository/HealthProfileService, membership/invitation implementation, browser controller/view, database source, token service/guard/limiter, private-health feature or application configuration file was modified. No migration or extra endpoint was created.

## 8. Focused test coverage

**20 unit checks PASS** (`tests/api-health-profiles.php`): exact list/detail privacy projection; decimal-string ID; correct existing repository methods/principal arguments; empty list without creation; identical inaccessible/nonexistent error; invalid/oversized IDs; existing profile types; invalid stored type fail-closed; no browser session.

**494 PHP/cURL HTTPS checks PASS** (`tests/api-health-profiles-http.php`) against actual bootstrap/router/controller/service/repository code and an explicitly guarded disposable database:

- Three accounts and four independent device families; own, shared viewer, shared editor, other-user, revoked, archived and creator-without-membership fixtures.
- Collection only accessible active profiles; detail authorization cannot be crossed by changing profileId. Unknown/revoked/archived/inaccessible errors are identical. Account with no profiles returns [] without writes.
- Exact three-field projection with sensitive demographics/metadata present internally but excluded. Request body/cookie/custom header cannot override principal; query user_id and pagination rejected.
- Both routes reject missing, malformed, unknown, expired and revoked access. Existing `/me` exact identity remains valid. Route-method/HEAD, no-store/JSON/nosniff, safe error/challenge/no cookies/CORS checks.
- One existing family-budget hit per admission, unchanged 120/family/minute limit gives 429/Retry-After. No new limiter.
- SQL membership revocation denies the next read; malformed stored enum gives safe 500 without SQL/stack detail. These mutations and expiry-fixture adjustments occurred only in the disposable database.
- Before/after equality of users/profiles/memberships/API-session/refresh rows across successful reads. Fixture families revoked through normal API lifecycle before full disposable-database removal.

**514 focused checks total PASS.** No tests were weakened to obtain a pass. Server stdout/stderr diagnostics are discarded; the fixture Logger stores only existing allowlisted event messages in memory. No new request/credential logging was added. Existing auth unit checks verify no password/raw token in auth log messages; normal Apache requests carried no credentials. Exact projection checks exclude credentials from JSON.

## 9. Fresh validation and regression results

| Test/check freshly executed | Result |
| --- | --- |
| API identity unit | 44 PASS |
| API identity PHP/cURL HTTPS | 345 PASS; fixture cleanup complete |
| API auth unit | 103 PASS |
| API auth limiter | 13 PASS; temporary files removed |
| auth-phase1 | 47 database-free scenarios PASS |
| Health Profile validation | 57 checks and 4 redirect scenarios PASS |
| Health Dashboard | 76 PASS |
| Dashboard guidance | 76 guidance checks PASS; its dependent dashboard checks also rerun |
| Public API unit | 56 PASS |
| Public API bootstrap | 8 PASS |
| Public API read-only HTTP | 561 PASS |
| PHP syntax | All seven changed/created PHP source/test files PASS |
| Apache configuration syntax | Syntax OK |
| Forwarding scope | 10 matching/nonmatching paths PASS; one mechanism |
| Normal deployed schema/unrelated-data verifier | PASS before final reporting and again after tests |

The first public HTTP attempt lacked sandbox socket access and failed before a response. Retrying the unchanged read-only test with the required socket permission passed all 561 checks; no application correction was made for this environment restriction.

The full historical 458-check/ten-minute production acceptance and separate expensive Step 50B HTTP/MariaDB suites were not unnecessarily rerun: guard/service/schema hashes remain identical; fresh identity HTTPS regression exercised login/refresh/logout/logout-all/reuse/device isolation and fresh auth/limiter unit suites exercised their existing policies. Prior completed Step 50B evidence remains authoritative for the broader unchanged foundation. No real Google callback, email delivery or genuine browser UI roundtrip is newly claimed. Browser Health Profile/Dashboard tests passed; browser UI code/services/repositories are unchanged.

## 10. Database/schema status

**No normal KOBI database writes and no schema changes.** The explicitly permitted disposable database was constructed from unchanged schema definitions, populated with synthetic users/profiles/memberships and removed completely. No normal profile/test user/record/token family was created or edited. The read-only deployment verifier confirms approved API table/trigger definitions and all unrelated schema/rows/defaults remain identical to its verified backup checkpoint. Normal API history remains **11 families / 21 refresh rows / zero active families**.

## 11. Configuration status and 12. Apache/Authorization forwarding

Only the authorized existing .htaccess forwarding rule/comment changed:

```apache
RewriteCond %{HTTP:Authorization} .
RewriteRule ^api/v1/(?:auth/|me$|health-profiles(?:/[^/]+)?$) - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

This preserves auth/ and exact me, adds the profile collection or a single path segment and excludes nested records/medicines or unrelated API routes. Invalid single-segment IDs reach existing safe ID validation; they do not authorize access. No duplicate mechanism/global Apache behavior change, probe, PHP configuration, TLS/certificate, .env or limiter-key change. Protected source hashes for guard/service/limiter/identity service/profile service/repository/schema/.env matched their pre-edit values. Existing front-controller rewrite order remains intact.

Normal Apache verified PHP/cURL GETs to both profile routes without credentials returned **401 UNAUTHENTICATED**, with no-store, cURL error 0 and TLS verification result **0**, peer verification enabled and hostname verification 2. Normal positive-authenticated profile requests were deliberately not performed because this task prohibits normal data changes/token-family creation for testing. Positive and denied authorization paths were verified in the disposable HTTPS environment; the normal forwarding extension is syntax/scope checked. This is a stated normal positive-smoke limitation, not a claim that such a smoke was run. The existing Codex/browser trust limitation was not bypassed; no browser certificate change occurred.

## 13. SQL mode status

Session and global mode remain **NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION**, verified read-only against normal KOBI. No SQL-mode directive was applied; profile type validation does not rely on permissive enum coercion producing a valid API category.

## 14. Cleanup status

Both new profile HTTPS and existing identity HTTPS runs removed their disposable databases, servers, synthetic accounts/profiles/memberships/token rows, temporary certificates/private keys and limiter files. Final read-only API database inventory is empty; active normal API families remain zero. No production history was deleted, no cleanup writes were made to normal KOBI and no accidental production change required repair. Test source files are retained as regression tooling.

## 15. Existing-system preservation

Existing profile creation/edit/memberships/invitations, Account UI, Dashboard, health records, medications/schedules, symptom checks, documents and summaries remain unchanged. `/me` fields/action behavior and public API contracts remain intact, with fresh focused regressions above. Step 50B policy code and underlying profile repository/service hashes are unchanged. Scoped source edits and references were reviewed without Git; the only modifications are the ten listed files. No Git commands/index/history mutations occurred.

## 16. Explicit exclusions

No clinical records, personal medicines, schedule/checkoff, saved symptom check, document, medical summary, sharing, mobile-verification, notification or other private-resource API. No CORS/JWT/provider/authentication redesign, browser-session change, mutation endpoint, schema deployment or pagination. No Step 53.

## 17. Final status

**PASS — Step 52B Authenticated Health Profiles API implementation and required isolated/readonly validation completed.** The positive normal-Apache smoke limitation is explicit above; normal data remained untouched. **Step 53 has not been started.**
