# Step 51A — Authenticated API Identity Readiness

16 October 2026, Asia/Calcutta. **READY — Step 51B implementation can proceed under separate implementation authorization.** This is a design/readiness decision, not an implemented endpoint or deployment approval. Step 51B must include the narrow forwarding dependency below; deploying only a PHP action/route would be insufficient on the observed Apache setup.

## 1. Current-state assessment

Step 50B is complete. `docs/task-50b-deployment-activation.md` records the verified 458-check acceptance PASS and subsequent minimal HTTPS availability PASS following normal operator startup. Its acceptance result remains authoritative; no production lifecycle test was repeated here. The existing four auth POST routes and four public GET routes remain unchanged. `/api/v1/me` is not registered.

Reviewed `docs/public-api-v1.md`, `docs/authenticated-api-v1.md`, deployed-checkpoint documentation, schema source, routes, bootstrap, Request/Router/Container/response classes, auth controller/guard/principal/service/repository/limiter and UserRepository. The two API contract documents retain historical “deployment pending” wording; the deployment report is the current operational checkpoint. Leave those documents unchanged during readiness; update the applicable contract in Step 51B without rewriting historical results.

## 2. Existing authentication path

`routes/web.php` → `App\Core\Router::dispatch()` → DI-resolved `ApiAuthController` → bearer extraction → `ApiAuthGuard::token()` → `ApiAuthGuard::resolve()` → `ApiAuthService::authenticate()` → `ApiAuthRepository`.

`ApiAuthController::bearer()` currently reads `HTTP_AUTHORIZATION` and `REDIRECT_HTTP_AUTHORIZATION`, rejects unequal simultaneous values, parses through the guard and resolves the credential. The guard strictly accepts the established access-token Bearer format. It does not accept refresh tokens, query/body identity, cookies or arbitrary custom headers. `authenticate()` finds the digest's session candidate, locks the account/session, validates current account eligibility, credential stamp, family and access deadlines/revocation, and returns `ApiAuthPrincipal(userId, sessionId)`. Its transaction performs reads/locks, not last-seen/account/token updates. Limiter bookkeeping remains private runtime-file writes, not database business-data writes.

Use `resolve($token, false)` exactly once for identity admission. Do not pass logout=true to bypass the normal family budget. Do not call browser `AuthService::id()` or `AuthService::user()` for this API: those identify the browser session, whereas `ApiAuthPrincipal::userId` is authoritative here. Existing browser conventions remain intact.

### Reuse without duplicate authentication or double admission

The current private `bearer()` returns only the raw token after resolving it; it discards the principal. For Step 51B, minimally factor its exact existing logic into a private shared helper returning token and principal. Keep the existing `bearer(bool)` wrapper delegating to that helper and returning the token for existing logout callers. The new identity action uses the shared helper's principal with logout=false. This is an internal reuse adjustment, not a new credential mechanism or changed validation policy. It avoids copying extraction/conflict logic or calling resolve twice. Do not return this internal token/principal bundle in JSON or log it.

### Hosting dependency — mandatory for Step 51B

`public/.htaccess` currently forwards Authorization only for `^api/v1/auth/`. `/api/v1/me` is outside that scope. The earlier diagnosis proved the existing server variables are otherwise unavailable on this hosting setup. Step 51B must separately authorize and add forwarding for exactly `^api/v1/me$` before the front-controller rewrite, using the same condition/environment mechanism and leaving the working auth-path rule intact. Do not broaden forwarding to all private-health paths or substitute another credential source. No forwarding/configuration change was made here. Live verified-HTTPS `/me` success through the existing controller variables is a required implementation acceptance check.

## 3. Existing account lookup and projection path

After guard admission, pass only `principal->userId` to a small identity-projection service (proposed `ApiIdentityService`). Reuse `UserRepository::findById(int)`, which uses BaseRepository's parameterized SELECT and already returns id/email/status/email_verified_at. No new repository, SQL pattern or account table is necessary. Its broader internal result also contains name/mobile/timestamps: the service must construct a fresh allowlisted DTO; never serialize that result wholesale.

Recheck existence, active status and non-null verification on the retrieved row; deletion or eligibility loss between admission and lookup gives the same generic 401. Guard admission already enforces the local-password marker and password-hash stamp. Do not duplicate those authentication checks with a second parser or password validation. A request admitted before a concurrent revocation may finish, as in Step 50B; do not promise a cross-request authorization snapshot. Future tests must exercise post-change admission denial.

## 4. Proposed exact V1 endpoint contract

| Property | Proposed contract |
| --- | --- |
| Method/path | GET `/api/v1/me` |
| Transport | HTTPS required; do not redirect credentials from HTTP |
| Credential | One Step 50B access token in Authorization: Bearer; no session/cookie fallback |
| Inputs | No query parameters. GET body and request Content-Type are not parsed, consistent with public GET conventions; they cannot supply identity |
| Success | HTTP 200; exactly the four data fields below |
| Metadata | Exactly api_version: "v1" |
| Content type | application/json; charset=UTF-8 |
| Headers | Cache-Control: no-store; X-Content-Type-Options: nosniff; no cookies/CORS grants |
| Unsupported methods | Existing router 405, Allow: GET; HEAD has no body; no OPTIONS permission |

Success example uses synthetic identity values, not a real account export:

```json
{"success":true,"data":{"id":"123","email":"account@example.invalid","status":"active","email_verified":true},"meta":{"api_version":"v1"}}
```

Use `PublicApiResponse::success()` and `ApiAuthController::respond()`; no new envelope implementation. HTTPS validation precedes admission; for HTTPS GET, bearer admission precedes query validation so anonymous callers receive generic 401. After admission, any query parameters produce existing-style 400 BAD_REQUEST, “Query parameters are not accepted.” Do not call the POST-only `input()`/`Request::authJson()` for GET or require an empty JSON object. A GET body is ignored, not an identity source.

## 5. Exact projection and 6. Field privacy assessment

| Field | JSON type / nullability | Decision |
| --- | --- | --- |
| id | Non-null decimal string, positive integer syntax | Include: canonical authenticated account ID; preserve public V1 string-ID convention. Not session ID or authorization proof |
| email | Non-null string | Include: own account's stored email, no independent client override or extra normalization. Private identifying information; authenticated/no-store only |
| status | Non-null string; only "active" on success | Include explicit current eligibility state; do not invent disabled/deleted/pending states or return inactive user objects |
| email_verified | Non-null boolean; true on success | Include safe derived flag (`email_verified_at !== null`); do not return verification timestamp/token |
| name | Excluded | Not needed to answer account identity in minimal V1; no display/profile expansion |
| mobile_number | Excluded | Private, unverified contact; see decision below |
| password_hash/password_set_at, tokens/digests/stamp | Excluded | Authentication internals, never resource fields |
| provider identities, roles/grants, session/device IDs, timestamps | Excluded | No provider, governance, session-management or audit projection in identity V1 |

No field is nullable in the proposed success object. Status and email_verified are intentionally constrained by admission; unsuccessful account states are represented by denial, not a partial identity object. IDs are strings even though current users.id is signed INT and fits JavaScript precision; consistency avoids a separate identity convention.

## 7. Mobile-number decision

**A — exclude entirely**, including masked form, normalized form, presence flags and invented verification flags. Step 45C stores an optional normalized private contact with a format CHECK; duplicate contacts are allowed and format does not prove possession, uniqueness, mobile capability or sharing eligibility. Step 46B/46B-1 readiness work is not deployed proof of verified mobile identity. No need for phone data exists in this minimal endpoint. Future mobile disclosure/verified identity requires its own approved purpose, consent/privacy and verification model; do not silently enable it when a column becomes populated.

## 8. Account-state and provider behavior

Current users.status values are active/inactive, not a new disabled enum. Active + verified + real local-password marker + valid credential stamp and valid family/access state can succeed. Inactive, unknown/invalid status, missing account, verification removed, local-password eligibility lost, credential stamp changed, revoked/expired family or expired/superseded access all fail generic 401 through the existing guard. No restoration of revoked credentials occurs when an account becomes eligible again. The approved API-only user-update trigger remains unchanged.

`/me` does not branch on password versus Google origin and never looks up provider identity. A Google-linked account with an eligible Step 50B local password can use its token identically. A Google-only account cannot currently obtain Step 50B API credentials; this endpoint does not add a Google exchange or weaken local-password eligibility. Future native Google is separate scope.

## 9. Error contract

All missing/malformed/invalid/expired/revoked/refresh-instead-of-access/ambiguous-header and ineligible-account denials share HTTP 401 and `WWW-Authenticate: Bearer`:

```json
{"success":false,"error":{"code":"UNAUTHENTICATED","message":"Authentication is required. Please sign in again."},"meta":{"api_version":"v1"}}
```

No token-condition discriminator or client-supplied identity appears. Existing budgets may return 429 instead once exhausted; limiter failures return 503, not an authorization success. Reuse `ApiAuthException` handling, including Retry-After.

| HTTP/code | Exact message / behavior |
| --- | --- |
| 400 BAD_REQUEST | HTTPS is required. / Query parameters are not accepted. (respective validation) |
| 405 METHOD_NOT_ALLOWED | Use GET for this endpoint. / Allow: GET, HEAD empty |
| 404 NOT_FOUND | Public resource not found. (unregistered paths such as `/me/123`) |
| 429 RATE_LIMITED | Too many requests. Please try again later. / Retry-After integer seconds |
| 503 SERVICE_UNAVAILABLE | Unable to complete the request. |
| 500 INTERNAL_ERROR | Unable to complete the request. / existing bootstrap/serialization handling |

Every error uses `success:false`, `error:{code,message}`, `meta:{api_version:"v1"}`, without data or extra fields for these cases. No SQL, stack trace, account/token existence reason or credentials. Authentication is required for own identity, so no separate role/grant/403 requirement is introduced.

## 10. Cache policy and 11. Rate-limit behavior

Retain `Cache-Control: no-store` on success and error through PublicApiResponse. No ETag/Last-Modified, shared cache or positive identity cache. No Set-Cookie or CORS grant. PHP/session authentication and CSRF do not apply to this bearer-only read; browser form/session protections remain unchanged. `Request::isPublicApiUri()` covers all `/api/v1/` paths despite its historical name, so bootstrap already skips session startup for `/me`.

There is no global authenticated-route middleware limiter. Protection is explicit guard admission: `token()` meters malformed/missing header failures; `authenticate()` checks/meters failed access at 60/direct-peer-IP/minute and meters successful non-logout admission at **120/family/minute**. Independent device families retain independent valid-use budgets; failed-peer budget is shared. Use the existing limiter unchanged, with one resolve per request. Exhaustion gives 429/Retry-After; unavailable/invalid key or counter storage gives fail-closed 503. Successful GETs still cause existing limiter-file accounting; “read-only” means no account/health/token database mutation, not zero operational side effects. Do not create another limiter or change thresholds.

## 12. Database assessment

**No schema changes required.** `database/structure.sql` defines users.id/name/email/password_hash/status as non-null, email_verified_at/password_set_at nullable and mobile_number nullable with format CHECK. UserRepository already supports the needed lookup; the deployed API tables and revocation trigger provide existing admission semantics. No new identity/profile tables, index, trigger, migration, backfill or user update is justified. Do not run structure.sql. No database connection or mutation was necessary for this readiness review; the recent deployment verification is historical/current-checkpoint evidence, not a newly executed schema census here.

## 13. Security assessment and 14. Health-data boundary

The token's principal alone selects the account. Reject query parameters; ignore client identity in GET bodies/custom headers/cookies. No route accepts another account ID. Do not reuse AccountController or its accountViewData aggregation: it includes provider and personal-product data. Keep the projection service limited to UserRepository and an explicit four-field DTO.

Explicitly exclude health profiles/records, saved diseases, medications/schedules/checkoffs, documents, medical summaries, saved symptom checks/history, treatments, preferences, sharing/invitations/recipients, all private medical information and every other user's information. Do not inject their services/repositories into the new identity projection. No joins/enrichment, Dashboard/UI page, health endpoint or browser behavior change. Render returned email as text in future clients; never put credentials in URLs or logs. No raw token, password or limiter secret is present in this report.

## 15. Focused Step 51B test plan

1. Database-free service/controller tests: exact envelope, four-key DTO, positive decimal-string ID, email/status/boolean types, no nulls/extras, broad repository fields never leaked. Missing account/eligibility lost between admission and projection fails generic 401; unexpected DB/encoding failure is safe 500. Verify existing lookup is called with principal ID only.
2. Admission matrix in guarded isolated fixtures: missing/malformed/duplicate-conflicting headers, wrong scheme, invalid/refresh token, expired/superseded/revoked token and expired family; same 401 body/challenge/no-store. Matching dual server variables preserve current behavior. Valid access succeeds. No cookie/session/CSRF dependency or fallback; cookies cannot override bearer or authenticate without it.
3. Account/device isolation: two devices of account A return A; account B returns B; no request ID/header/body/query can select B using A's token. Query supplied gives 400 after admission; GET body is ignored. Single logout revokes only its family; logout-all all account families, other accounts unaffected. Refresh invalidates old access and successor works. Inactive/unverified/deleted/password-marker/stamp transitions deny subsequent admission; no automatic restoration. Google-linked eligible account follows same path, Google-only issuance remains denied.
4. Privacy: recursive exact-key allowlist; no name/mobile/presence/masked value, credential/hash/token/stamp/provider/role, timestamps, private health/summary/document/medication/symptom/sharing fields or other users. Missing/invalid token state is not disclosed.
5. Transport/router: verified HTTPS through normal Apache forwarding for exact `/me`; expected existing server-variable path, no probe or raw-header logging. Correct Content-Type/nosniff/no-store, no cookies/CORS, no error leakage with web debug enabled. GET body/Content-Type do not invoke authJson; HTTP denied 400; POST/PUT/PATCH/DELETE/OPTIONS/HEAD 405 with Allow GET and HEAD empty; `/me/123` remains 404. Public V1 endpoints remain public and unaffected by Authorization.
6. Limiter: exactly one non-logout resolve and one family-use accounting per accepted GET; current 120/family/minute, failed-peer 60/minute, Retry-After and fail-closed 503 with isolated private files. No exemption/new policy. No user/token DB writes from `/me`; snapshots show unchanged rows/schema, including last_login_at and session refresh timestamps.
7. Regressions: existing auth unit/limiter, isolated MariaDB/HTTPS lifecycle, public API unit/bootstrap/MariaDB/HTTP and relevant browser-auth scenarios. Real fixture creation, counter writes, token lifecycle and live authenticated `/me` tests belong to separately authorized Step 51B validation, not this review. Prefer isolated synthetic accounts; normal-environment token tests require explicit approval and genuine credential input/normal lifecycle cleanup.

## 16. Expected implementation files

Proposed narrow scope, to be authorized for Step 51B:

- `routes/web.php`: one GET route to `ApiAuthController::me`.
- `app/Controllers/ApiAuthController.php`: GET action, shared admission-result helper retaining existing bearer wrapper semantics, identity-service dependency, HTTPS/query handling separate from POST input.
- New `app/Services/ApiIdentityService.php`: allowlisted identity DTO through existing UserRepository; no direct SQL.
- `public/.htaccess`: exact `/api/v1/me` forwarding addition, preserving the approved auth rule. This is an explicit deployment prerequisite, not a change made during readiness.
- Focused identity tests and relevant existing controller/route/hosting test coverage; `docs/authenticated-api-v1.md` endpoint contract and Step 51B implementation report. Correct historical deployment wording in API contract docs only as applicable.

Existing reflection DI resolves class dependencies; no new container registration/framework is needed. Tests must preserve all four existing auth actions when constructor/helper reuse changes.

## 17. Files/areas not expected to change

ApiAuthGuard, ApiAuthService, ApiAuthPrincipal, ApiAuthRepository, ApiAuthRateLimiter, AuthService, UserRepository/BaseRepository, Request, Router, Container, PublicApiResponse, bootstrap/session behavior, deployed API SQL, database/structure.sql, normal DB data, .env/limiter key, PHP/Apache SSL/TLS/certificates, browser trust, UI/views/navbar/Dashboard, Treatment/Medicine, matcher/scoring and all private-health code. No provider/auth policy redesign. If implementation appears to need one of these changes, inspect and report the concrete reason before expanding scope.

## 18. Prerequisites and 19. Risks/limitations

Step 51B needs explicit implementation authorization including the exact-path forwarding addition and focused validation permissions. HTTPS, approved schema/trigger and dedicated limiter already have completed Step 50B evidence; preserve them. Readiness does not claim `/me` works today: it does not exist. The scope gap in forwarding and discarded principal must be addressed in that future implementation, not bypassed with cookies/getallheaders/query credentials or double guard admission.

Browser trust remains an informational limitation; future API transport verification uses normal certificate-verified PHP/cURL. Existing limiter is single-host and valid tokens remain bearer credentials until expiry/revocation; no multi-host/sender-constrained redesign belongs here. Concurrent revocation has the existing admission boundary, not a new absolute guarantee. Neither email nor the proposed verified-email flag implies phone or medical identity verification. No separate readiness blocker requires a schema or auth-policy change.

## 20. Validation and final recommendation

Executed existing readiness-safe suites: `tests/api-auth.php` **103** checks, `tests/public-api-v1.php` **56** database-free checks, `tests/public-api-v1-bootstrap.php` **8** checks, all PASS. The unit auth suite uses an in-memory repository and no-op limiter/logger; bootstrap uses stubs and no sessions/DB/log writes. Static inspection established method names, route absence, account schema, forwarding scope, no-session V1 classification and exact errors. PHP syntax checks passed for the unchanged ApiAuthController, ApiAuthGuard, ApiAuthService and UserRepository. No mutation-requiring MariaDB/HTTP/lifecycle tests or live authenticated requests were executed. Final report inspection and the unchanged .htaccess hash were checked without Git.

Only this readiness report was created; both API contract documents and all application/configuration/schema/test files remain unchanged. No database writes/connections, authentication families, token changes, limiter writes, TLS changes or Git operations occurred. No Step 51B implementation, private-health API or Step 52 work began.

**READY — Step 51B implementation can proceed**, with the explicit proposed contract, exclusions and narrow hosting/helper dependencies above, once separately authorized. This readiness recommendation does not authorize implementation or deployment in this task. STOP after readiness.
