# Step 51B — Authenticated API Identity Implementation

17 October 2026, Asia/Calcutta. **PASS — Step 51B Authenticated API Identity COMPLETE.** Implementation, isolated validation, regressions and the explicitly authorized one-family normal-Apache smoke passed. The protected smoke result was independently verified; its completion timestamp is `2026-10-16T18:42:52+00:00` (17 October 2026, 00:12:52 IST). Step 52 has not been started.

## 1. Implementation summary and endpoint contract

Added exactly GET `/api/v1/me`. HTTP 200 uses the unchanged V1 envelope: success=true, data containing exactly id/email/status/email_verified, meta containing exactly api_version="v1". ID is a non-null decimal string; email a non-null string; status the non-null existing "active" state; email_verified a non-null boolean, true for admitted eligible accounts. No aliases, user-ID routes or UI were added.

HTTPS is required. No query parameters are accepted; after valid admission they return 400 BAD_REQUEST. GET body and request Content-Type are ignored as identity inputs, matching public GET conventions. No body JSON or CSRF token is required. Unsupported methods return router 405/Allow GET; HEAD has no body. Unregistered aliases/subroutes return existing 404.

## 2. Authentication flow and 3. Identity projection

ApiAuthController shares its original header-extraction/conflict/guard logic through `admittedBearer()`. The original `bearer(bool)` wrapper still returns a token for unchanged logout/logout-all actions. The new action retains the principal from **one** `ApiAuthGuard::resolve(token, false)` call. No second admission or family-budget exemption occurs. ApiAuthGuard/ApiAuthService policies and code are unchanged.

The principal's userId is passed to new `ApiIdentityService::forPrincipal()`, which calls existing `UserRepository::findById()`. The controller contains no SQL or projection business rules. The service checks the retrieved account remains present/active/verified, then builds a new exact four-key DTO. Existing repository fields are never serialized wholesale. There is no SELECT * for this account lookup and no new repository method.

## 4. Privacy, mobile and health boundaries

The response excludes mobile_number, including masked/presence variants: Step 45C contact is private and is not verified phone identity. It also excludes name, profile fields, timestamps, password/hash/stamp, tokens/digests, session identifiers, provider/role/grant fields, health profiles/records, saved diseases, medications/schedules, documents, saved symptom checks, summaries, Treatment/Medicine information and sharing data. The new projection service depends only on UserRepository, not private-health services. No Account UI, navbar, Dashboard, browser login/logout/session or mobile-number implementation changed.

## 5. Account state and error behavior

Existing guard admission remains authoritative for account existence, active status, verified email, local-password marker/stamp, access/family deadlines and revocation. Projection-time existence/status/verification loss also gives generic denial. No new states or provider-specific identity logic are introduced. Google-linked eligible accounts use the same token path; native Google API login remains deferred.

Missing, malformed, invalid, expired, superseded, revoked or otherwise ineligible Bearer access returns the existing 401 UNAUTHENTICATED: “Authentication is required. Please sign in again.” with WWW-Authenticate: Bearer and standard error/meta envelope. No token-state reason is revealed. Existing rate-limit exhaustion/failure can instead return 429/503. Existing bootstrap handles unexpected failures safely as 500; no exception details are exposed. HTTP gives 400 “HTTPS is required.” HTTPS bearer admission precedes 400 “Query parameters are not accepted.” No client query/body/URL/custom identity field overrides the principal.

## 6. Cache, limiter and session independence

Unchanged PublicApiResponse supplies application/json; charset=UTF-8, Cache-Control: no-store and nosniff. No cookie or CORS grant is emitted. V1 bootstrap already skips PHP session startup, so no browser login/session/cookie/CSRF dependency was added.

The existing limiter applies: successful non-logout admission 120/family/minute; failed access 60/direct-peer-IP/minute; 429 with Retry-After, fail-closed 503 when unavailable. Exactly one family increment per GET was verified. No new limiter, thresholds, key or token semantics changed. GET causes existing private limiter-file accounting but no user/session/refresh database writes.

## 7. Authorization forwarding

The existing single conditional rule in public/.htaccess was adjusted, not duplicated:

```apache
    # Forward the existing Bearer header only for API authentication and identity routes.
    RewriteCond %{HTTP:Authorization} .
    RewriteRule ^api/v1/(?:auth/|me$) - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

Existing `auth/` coverage is preserved and only exact `me` is added before the unchanged front-controller rewrite. No second credential mechanism, global Apache directive, TLS change, diagnostic probe or parser workaround was introduced. Apache syntax is OK. Normal verified HTTPS `/me` returns the expected safe 401 without credentials. The authorized one-family operator smoke then proved valid-token HTTP 200 through normal Apache and the unchanged guard/controller extraction mechanism, with exact own-account projection. No browser session was used.

## 8. Database impact and integrity

No schema change or normal user/account/health data write. UserRepository, database/structure.sql, deployed API schema/trigger, .env, limiter key, PHP configuration and Apache SSL configuration hashes match the pre-change checkpoint. The read-only Step 50B deployment verifier passes after regressions and again after the operator smoke: approved table/trigger metadata and all unrelated schema/rows/defaults/SQL modes are unchanged. Normal counts before smoke were 10 families/20 refresh rows; afterward they are **11 families/21 refresh rows, zero active**. Only the authorized family's normal API lifecycle added history. SQL mode session/global remains NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION.

The user explicitly authorized isolated test DDL/writes. Synthetic users/token state, test-counter seeding and expiry fixture adjustments occurred only inside the existing guarded disposable database and private test storage. The user subsequently explicitly authorized one temporary normal Account 1 API family through genuine login and normal logout for live forwarding verification; that operator-run check passed. Normal data was never edited manually. Existing API families and refresh history remained exactly unchanged; the one new revoked family and one digest-only refresh row are intentionally retained lifecycle history.

## 9. Files changed and created

Modified:

- app/Controllers/ApiAuthController.php — shared admission result and identity GET action/dependency.
- routes/web.php — one GET registration.
- public/.htaccess — extend existing scoped rule to exact identity path.
- docs/authenticated-api-v1.md — identity contract and current deployment context.

Created:

- app/Services/ApiIdentityService.php — narrow account projection.
- tests/api-identity.php — in-memory focused controller/service/guard tests.
- tests/api-identity-http.php — verified PHP/cURL disposable integration tests.
- tests/api-identity-http-router.php — CLI-server-only disposable upstream using actual bootstrap/routes.
- tests/api-identity-production-smoke.php and .ps1 — optional guarded operator smoke, hidden password input, one normal family only after authorization; safe protected metadata output, normal logout cleanup, no full acceptance rerun.
- docs/task-51b-authenticated-api-identity-implementation.md — this report.

No existing regression expectations were weakened or changed. No unrelated code was refactored. Step 51A report and Step 50B acceptance report/evidence remain unchanged; the read-only verifier updates only its protected safe final-state summary.

## 10. Focused tests

`tests/api-identity.php`: **44 checks PASS**, no DB/session/log writes. Covers two principals, independent devices, exact projection/privacy/types, correct repository selector, unchanged user/session/refresh rows, one family admission, missing/malformed/invalid/refresh/superseded/expired/revoked credentials, server-variable conflict/matching/redirect cases, HTTPS/query precedence, logout isolation/logout-all, missing/inactive/unverified/no-password/stamp-change account eligibility and projection-time eligibility loss.

`tests/api-identity-http.php`: **345 checks PASS** through actual routing/controller/services/repositories and PHP/cURL with certificate verification enabled. Covers exact identity for two synthetic accounts/three devices, body/query/custom header/cookie boundary, no cookies/CORS/session dependency, generic 401/challenge, no-store/nosniff/Content-Type, 405/Allow/HEAD and absent aliases/subroutes, refresh/superseded access/replay, expired access fixture and successor, logout/logout-all/device isolation, existing family budget count and 429/Retry-After, zero active fixture families and unchanged DB rows across identity GETs. The test certificate is generated for the disposable loopback proxy, trusted explicitly by its client; no normal TLS configuration is changed. It reuses the established TLS proxy from the auth test harness. All disposable DB/server/key/counter files were removed.

## 11. Regression results

| Executed test | Result |
|---|---|
| API auth unit | 103 PASS |
| API auth limiter | 13 PASS |
| API auth MariaDB | 65 PASS |
| API auth isolated HTTPS | 326 PASS |
| Existing auth-phase1 scenarios | 47 PASS |
| Public API unit | 56 PASS |
| Public API bootstrap | 8 PASS |
| Public API MariaDB | 38 PASS |
| Public API read-only HTTP | 561 PASS |
| Public Search | 30 PASS |
| Disease References | 71 PASS |
| Disease Detail presentation | 7 PASS |
| Treatment publication | 105 PASS |
| Substance-search JavaScript | 11 PASS |
| PHP syntax | Controller, service, route, four PHP test files: PASS |
| PowerShell operator helper parser | PASS |
| Apache configuration syntax | Syntax OK |
| Normal deployment integrity verifier | PASS |
| Normal Apache identity smoke | 35 PASS; one authorized family, normal logout cleanup |

Real Google callbacks, password-reset email delivery and fresh browser login/logout were not newly exercised. Existing auth scenarios cover their regression boundaries; browser UI changes are out of scope. No full ten-minute acceptance run was repeated.

## 12. HTTPS verification and security verification

Focused valid-token tests use PHP/cURL peer verification true and hostname verification 2; TLS verification result 0. The normal Apache credential-free check also returned TLS result 0, cURL error 0, intended leaf SHA-256 83f096f60d695d66513b57da98b4ba6cc41763b65b045afcd01f3db76cc18087 and generic HTTP 401. No cookies or credentials were sent in that credential-free check. The subsequent genuine-login identity smoke verified the same intended certificate with peer/hostname verification enabled, HTTP 200 for `/me`, correct JSON/no-store and no browser-session dependency. Codex/browser CA trust remains informational and was not bypassed or retested.

Protected safe evidence `D:\xampp\kobi-backups\step50b-20261016\step51b-identity.json` independently matches the user-returned result: PASS, 35 assertions, normal Apache identity/forwarding/exact projection/session independence/TLS result 0, normal logout cleanup, unrelated-state equality and unchanged existing API history. No full 458-check acceptance or ten-minute expiry run was repeated.

No raw password/access/refresh token, limiter secret, private key or raw Authorization header is printed in this report or helper output. Synthetic test credentials remain in memory; the disposable test TLS key is removed. Optional operator input is hidden and sent to PHP on stdin, never command arguments/environment/files. Its result contains safe statuses/counts/booleans only, outside the web root. No private health fields enter the DTO. Guard/service/limiter/repository policy hashes were checked unchanged.

## 13. Cleanup and final status

All required isolated test databases and servers were removed. A final read-only schema inventory found no disposable API databases; active normal API families remain **0**. No legitimate auth history was deleted. No manual normal DB changes, schema changes, Git operations, TLS/configuration changes or Step 52 work occurred.

**Final status: PASS — Step 51B Authenticated API Identity COMPLETE.** The 389 focused checks, 1,441 existing regression checks and 35 normal-Apache smoke assertions passed (1,865 total; syntax, static scope and integrity checks are additional). A static six-path check confirms the single forwarding rule covers auth and exact me while excluding me/user subpaths, medications, summaries and non-API paths. Genuine Account 1 login established the explicitly approved one temporary family; `/me` succeeded, normal logout revoked it and subsequent access was denied. No refresh or ten-minute wait occurred. Final read-only verification confirms zero active API families, no disposable API databases, approved deployed schema/trigger integrity and unrelated-state preservation. The guarded operator smoke artifacts are retained as documented test tooling; do not rerun automatically or overwrite their protected result.

**Step 51B Authenticated API Identity is complete. Step 52 has not been started.**
