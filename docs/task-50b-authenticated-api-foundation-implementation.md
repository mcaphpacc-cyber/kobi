# Step 50B — Authenticated API Foundation implementation

Completed validation: 2026-10-16, Asia/Kolkata. **Status: BLOCKED for normal deployment acceptance. Implementation and isolated validation: PASS.**

All four endpoints work in the isolated HTTPS environment. Normal KOBI intentionally has no new API tables/trigger or configured limiter key. The task explicitly prohibits modifying that database; no normal deployment or real API credential issuance was attempted. Deployment cannot be claimed complete. A separately authorized controlled deployment, verified backup/recovery and key/HTTPS provisioning remain necessary. This report does not authorize them.

## 1. Objective

Implement only the approved first-party API credential foundation. No `/me` or private/public medical resource API was added. Preserve browser authentication, public API and KOBI Controller → Service → Repository architecture.

## 2. Approved Step 50A design

Read Step 50A, public V1 contract/implementation, current authentication, schema, router/request/bootstrap and Google identity protections before editing. Current normal MariaDB is 10.4.27; users schema matches the readiness report; no conflicting API tables/user triggers were found. Approved opaque access/rotating refresh/device-family model implemented. Native Google remains deferred, verified mobile unnecessary, no JWT/OAuth server/CORS/dependency installation.

## 3. Authentication model

`ApiAuthController` validates transport/input, calls `ApiAuthService`, and uses `ApiAuthGuard` for explicit protected-route entry. The service uses `ApiAuthRepository`/BaseRepository and shared PDO. `ApiAuthPrincipal` contains user/family ID, not a role or user row. Existing `AuthService` supplies the extracted side-effect-free password verification; its browser login retains session regeneration, identity storage, last-login update, safe messages and hash removal.

Only these routes are registered: POST `/api/v1/auth/login`, `/api/v1/auth/refresh`, `/api/v1/auth/logout`, `/api/v1/auth/logout-all`. No browser cookies establish API identity. A clock class supplies deterministic test time without changing application timezone.

## 4. Access-token design

32 random bytes, purpose prefix and canonical unpadded base64url; opaque rather than signed claims. Domain-separated SHA-256 binary digest only. Ten-minute expiry, additionally capped by absolute family deadline. A single current access digest per family; refresh replaces it immediately. Every admission checks fresh account, stamp, expiry and revocation under locks. No raw bearer credential is persisted or logged; only the authorized issuance response contains it.

## 5. Refresh-token design

Independent 32 random bytes and refresh prefix/digest purpose. Fourteen-day inactivity expiry, capped at 30-day absolute family deadline. Conditional one-use consumption, retained consumed hashes, successor generation and unique active slot. No raw successor cache or retry grace. Clients must serialize refresh; loss of a successful response requires login again. Individual access expiry does not prevent valid refresh.

## 6. Device-session model

Independent families, separate noncredential random public session ID, optional bounded 80-character label, creation/last-refresh/absolute-expiry/revocation state. No hardware fingerprint, mobile proof, IP binding or per-request last-used write. Five active families maximum; oldest live family is revoked when a sixth is issued, under the account lock. Different users/devices remain isolated.

## 7. Login flow

Strict JSON/email/password/label validation and bounded account/IP rate controls precede expensive work. Resolve candidate ID, lock/recheck the account (including the submitted email), reuse password verification and require active/verified/real local password. Unknown/wrong/inactive/unverified/placeholder outcomes have the same safe 401. Dummy hashing reduces obvious unknown-account timing differences. In one transaction enforce device cap and insert family/current refresh; emit credentials only after commit. API login does not mutate browser session, last-login/user timestamps or create a profile.

## 8. Refresh flow

IP/family rate controls, exact credential syntax/digest lookup, account → family → refresh locks and fresh eligibility checks. Consume old row, insert successor, update access digest/generation/timestamps and commit together. Rollback/false commit yields no successful issuance. Old access cannot authenticate after rotation. Database overflow/failure test verified consumption rollback.

## 9. Logout flow

Valid bearer access plus empty JSON object revokes only that family and its refresh capability. Repeated/invalid logout safely returns generic 401; no token-history disclosure or browser logout. Client clears local secrets regardless of network/retry result. Own-family revocation bypasses the ordinary authenticated-use budget, while malformed/failed credentials remain limited.

## 10. Logout-all flow

Valid bearer and current password required. Reverification reuses the same password capability and failure budget. Transactionally revoke all API families for that account, including the caller; other accounts and PHP/Google browser sessions remain unaffected. No web-session revocation machinery added.

## 11. Revocation

Revoked/expired families fail immediately even if access time remains. Password hash stamp is checked freshly. Operations serialize on the user first, then session/token. Already admitted work can finish before a concurrent revocation; requests admitted after commit fail. Family deletion cascades refresh rows. No resource-level privileges are inferred.

## 12. Reuse detection

Presenting a known consumed refresh credential in a still-active family revokes the family with reason `refresh_reuse`. The denial is returned from the transaction, committed, then thrown as safe 401, preventing accidental rollback of the security action. Unknown hashes do not revoke families. Two separate PHP processes/connections submitted the same refresh simultaneously: exactly one initial success and one denial; the loser revoked the winning family, with one successor lineage only.

## 13. Password/account invalidation

The one AFTER UPDATE trigger `trg_users_api_revoke` updates only new API families when password hash changes, active status is lost, or verified email becomes unverified. Uses UTC, binary/null-safe hash comparisons, and runs in the original account transaction. No browser session changes. Fresh account/stamp checks provide an additional backstop. Re-enabling/re-verifying does not revive families; user deletion cascades.

Actual existing `AuthService::changePassword`/`resetPassword` persistence was exercised with synthetic isolated accounts and an in-memory browser-session double: API credentials revoked, password-change browser identity retained, and reset remained non-auto-login. Account-update rollback restored valid prior API state. No real password/reset/email was used or changed.

## 14. Authentication middleware/resolution

Explicit guard fits the existing router, avoiding a new middleware framework. Accept exactly one well-formed Bearer access value; reject missing/unsupported/empty/long/ambiguous values and refresh-as-access. Request principal is independent from `AuthService::id()`/PHP sessions. Existing profile-access rules remain untouched and mandatory for any separately designed future health API.

## 15. CSRF

Explicit bearer/JSON credentials, no ambient cookie authentication and no browser CSRF requirement on these new endpoints. All existing browser CSRF checks and state-changing routes are unchanged. Strict body/content-type/query policy prevents form/cookie shortcuts.

## 16. CORS

No wildcard/reflected origin or credential grant. OPTIONS fails with method denial. Native clients do not require browser CORS permission. Public V1 behavior unchanged.

## 17. Cache policy

JSON/no-store/nosniff on success and errors. No cookies; no token-bearing URLs. Correct POST Allow on authentication routes, GET preserved for public routes, HEAD bodies suppressed. HTTPS required in application; HTTP is rejected, with no insecure fallback.

## 18. Rate limiting

Single-host bounded exclusive-file-lock store with HMAC account/IP pseudonyms; dedicated configured key, fixed expiry windows, 10,000 live buckets and 2 MiB cap. Corrupt/unavailable/full store or missing key fails closed. Uses direct peer IP; does not trust arbitrary forwarded IP. Login 30/IP/15 min plus five account failures/15 min; refresh 60/IP/min and 30/known-family/min; failed access/header 60/IP/min; ordinary authenticated admission 120/family/min. Own-family logout remains available outside the ordinary usage cap. Logout-all password failures share login's account budget. No Redis/platform dependency.

Normal key was not provisioned or printed. `.env.example` documents the empty variable; no real `.env` credential was altered. No multi-host guarantee: that deployment needs a shared atomic limiter. Tests covered failure-window expiry, account isolation, pseudonymization, missing key, corruption, size/capacity fail-closed and expired-entry recovery.

## 19. Logging

Fixed allowlisted event names through existing Logger: successful/failed login, rotation/reuse, logout/all logout. No body, submitted email, passwords, raw tokens/digests, Authorization or health data. Test Logger captures events in memory; unit assertions checked that secrets did not enter it. API exception boundary logs safe exception classes only. Existing Logger remains best-effort, not a durable audit ledger. Operational access/30-day rotation guidance documented; no broad log purge installed.

## 20. Database objects and retention

Artifact: `database/api-auth-foundation.sql`; identical additive block appended to `database/structure.sql`.

- `user_api_sessions`: user FK/cascade, public/access uniqueness, account/state/expiry/revocation indexes; six CHECKs.
- `user_api_refresh_tokens`: family FK/cascade, digest/generation uniqueness, generated active-slot uniqueness; three CHECKs.
- `trg_users_api_revoke`: the sole new trigger, API-only user-update revocation.

MariaDB metadata verified two new tables, two FKs, nine CHECKs and the trigger. VARBINARY(33)/length=32 and overlength guard columns avoid relying on permissive truncation to validate fixed digests/labels/IDs. Services explicitly validate controlled inputs; no ENUM/SQL-mode dependency added. Exact DDL executed successfully under the existing mode. No constraint omitted or weakened.

Consumed hashes remain for live-family replay detection. CLI cleanup deletes at most 500 families expired/revoked over seven days ago with cascading token deletion. It is CLI-only, requires `--expired-api-only`, and installs no schedule. Cleanup was checked to preserve live/young replay history; normal cleanup was not executed. Authentication validity never depends on immediate physical deletion.

## 21. Deployment result

**NOT DEPLOYED to normal KOBI.** Section 44 prohibits modifying normal KOBI, and no separate normal deployment authorization was obtained. No normal backup/restore/migration was attempted or claimed verified. The test-only database received all DDL/data writes and was removed; original SQL mode was never changed.

Normal final metadata: both new tables absent, API trigger absent, 12 users, dedicated limiter key unconfigured. Therefore normal API issuance remains unavailable/fail-closed and must not be advertised as deployed. Existing normal browser authentication/public features work. Genuine requested browser login/logout ran normally and may update existing last-login metadata; there were no manual normal data writes, fake production users/credentials, profile/health edits or emails/SMS.

For an authorized deployment: verified recoverable backup/restore including triggers, exact schema-only scope checks, isolated recovery rehearsal, provision private limiter key/storage and HTTPS/Authorization forwarding, apply only the additive artifact, then inspect metadata/data and rerun scoped acceptance. Rollback must disable API admission/issuance and remove its user-update trigger before removing tables. Do not use full `structure.sql` as an incremental migration. No corrective/rollback DDL was run on normal KOBI.

## 22. Tests and exact results

| Executed command | Result |
|---|---|
| `php tests/api-auth.php` | **103** checks PASS, database/session/log-file free |
| `php tests/api-auth-rate-limit.php` | **13** checks PASS; private test counter files removed |
| `php tests/api-auth-mariadb.php` | **65** checks PASS on MariaDB 10.4.27; fixture database removed |
| `php tests/api-auth-http.php` | **326** HTTPS assertions PASS; fixture database, servers, certificate/key and counter files removed |
| `php tests/auth-phase1.php` | **47** database-free scenarios PASS: passwords, reset/change/rollback, verification, CSRF, logout-related/session and Google/link/unlink/error cases |
| `php tests/public-api-v1.php` | **56** checks PASS |
| `php tests/public-api-v1-bootstrap.php` | **8** session-free/debug/error checks PASS |
| `php tests/public-api-v1-mariadb.php` | **38** checks PASS; separate public fixture database removed |
| `php tests/public-api-v1-http.php` | **561** assertions PASS, normal read-only requests |
| Normal HTTP transport gate | **4** empty-object requests rejected with HTTPS-required 400 before credential processing; no credentials submitted |
| `php tests/public-search.php` | **30** checks PASS |
| `php tests/disease-references.php` | **71** checks PASS |
| `php tests/disease-detail-presentation.php` | **7** scenarios PASS |
| `php tests/treatment-publication.php` | **105** tests PASS |
| PHP syntax | **22** created/modified PHP files PASS |
| `git diff --check` | PASS; existing unrelated health-medicines line-ending notice only |

New cases cover unknown/wrong/unverified/inactive/no-password identity, malformed/duplicate JSON, hostile inputs, bearer parsing, exact DTO, digest-only storage, purpose separation, access/refresh/absolute expiry boundaries, cap near absolute deadline, logout/device isolation, strict replay commits, false-commit rollback and no secrets logged. Database cases additionally cover actual constraints, overflow rollback, account trigger invalidation/rollback/deletion and concurrency. No existing test was weakened/rewritten.

## 23. Genuine HTTPS validation

All four routes tested through the actual bootstrap/router/controller/services/repositories on a disposable PHP server behind a loopback TLS proxy. Client verified the ephemeral test certificate and hostname (`verify_peer`/`verify_peer_name` true); production HTTPS enforcement was not disabled. The test upstream alone marks HTTPS because TLS terminates at its dedicated proxy. No application HTTP fallback or real credential used.

Verified status, envelope, no-store, no cookies/CORS, safe/challenged 401, POST method errors/HEAD/OPTIONS, query/size/JSON errors, exact issuance, expired-access refresh, replacement/reuse denial, single/all logout and account-state error parity. No tokens printed or saved to files. Test TLS key existed only in its private temporary fixture and was removed. Normal public HTTP suite verified all four read endpoints plus unchanged legacy `/api/substances/search`, `/search` and Disease Detail. Test-only TLS servers are not a production deployment.

## 24. Browser regression

Genuine existing-account password login was completed by the user. Own Account loaded; ordinary Logout worked; direct Account navigation afterward returned Login. Browser ended anonymous on Login. No API credentials were created for that account.

1366×768 and 390×844 checks: Account/login/recovery entry, public Search, Migraine Disease Detail; no measured horizontal overflow. Diagnosis, four reference asides and five existing legacy Treatment cards preserved. Google entry reached the real “Sign in with Google / KOBI web” provider page. No provider credentials entered, consent/linking performed or provider callback claimed tested through a real account. Browser reset form navigated correctly without submitting email/password changes. Existing automated scenarios covered callback and reset success/failure behavior.

Tested KOBI surfaces reported no warning/error console entries or observed CSP violations. No views/CSS/JS changed. Temporary viewport override reset. Evidence: [mobile Login](task-50b-login-mobile.png). Broader provider round-trip/password-reset email delivery remains intentionally unperformed; no real provider credential or reset email was available/needed for this scoped regression.

## 25. Security validation and issues resolved

Reviewed all changed/new code for raw credential persistence/logging, secret literals, SQL injection/dynamic identifiers, session side effects, unrelated CSRF bypass, CORS grants, private-data projections, TTL/reuse/revocation/account bypass and commit handling. Prepared SQL values throughout; only a bounded integer LIMIT is concatenated. No runtime user rows returned, no role inheritance, no medical data join. Public dummy password hash is explicitly noncredential material, not a configured secret. Locked login rechecks email; false commit/rotation failures never yield credentials. Test exception traces omit arguments.

Harness corrections: corrected expected CHECK count to six plus three; replaced a test-only unavailable MariaDB `RANDOM_BYTES()` function (error 1305) with PHP random bytes bound as a parameter; replaced a real session in isolated password tests with a memory double so intentional browser reset behavior does not invalidate a no-session API assertion. That first reset harness inadvertently started an empty CLI PHP session; it did not authenticate or touch a genuine browser session. Final isolated tests use the memory double and start no PHP session. First HTTPS attempt hit Windows stderr-pipe backpressure after many requests; server diagnostics now go to NUL, avoiding both blockage and request-log persistence. Rerun passed all 326 assertions. No schema compatibility failure required removal/redesign of a constraint; no existing tests weakened.

SHA-256 comparison against the pre-edit workspace inventory found changes confined to the listed task paths. Existing dirty-workspace edits were preserved. Google controllers/services/state, AuthController, UserAuthTokenService, UserRepository, browser bootstrap, all views/CSS/JS and unrelated application/schema content were not changed by this task. Git inspection is read-only; no index/history/branch operations performed.

## 26. Deferred functionality

No `/me`, profile/health/medication/schedule/saved-check/document/summary/sharing endpoints; no Treatment, rich Medicine or Symptom Checker API; no native Google, mobile verification/SMS, JWT/authorization server, API key management, CORS, new role grant, Dashboard change, device-management UI, mobile code, MFA or sender constraints. Treatment effective publication mode rechecked **hold**. Private health and governance data remain excluded. Step 50C not started.

## 27. Known limitations and acceptance

Implementation/isolated lifecycle and concurrency tests pass. Normal deployment acceptance is **BLOCKED**, not silently represented as complete: approved objects/key/HTTPS hosting still need a separately authorized controlled deployment and backup/recovery verification. No normal schema/data deployment or real API credentials were created. Real Google provider callback and recovery email delivery were not executed; automated existing regressions and browser entry/handoff checks passed. No load/penetration testing or multi-host limiter guarantee is claimed. Strict refresh rotation intentionally favors theft detection over transparent retry recovery. Operational log retention and CLI cleanup scheduling require deployment ownership; no automation was installed.

Stopped after Step 50B implementation/validation; no additional feature begun.

## Exact task file inventory

Created:

- `app/Controllers/ApiAuthController.php`
- `app/Repositories/ApiAuthRepository.php`
- `app/Services/ApiAuthClock.php`
- `app/Services/ApiAuthException.php`
- `app/Services/ApiAuthGuard.php`
- `app/Services/ApiAuthPrincipal.php`
- `app/Services/ApiAuthRateLimiter.php`
- `app/Services/ApiAuthService.php`
- `database/api-auth-foundation.sql`
- `scripts/cleanup-api-auth.php`
- `tests/api-auth.php`
- `tests/api-auth-unit-support.php`
- `tests/api-auth-rate-limit.php`
- `tests/api-auth-fixture.php`
- `tests/api-auth-mariadb.php`
- `tests/api-auth-http.php`
- `tests/api-auth-http-router.php`
- `docs/authenticated-api-v1.md`
- `docs/task-50b-authenticated-api-foundation-implementation.md`
- `docs/task-50b-login-mobile.png`

Modified:

- `.env.example` (empty dedicated-key setting only; real `.env` unchanged)
- `app/Config/config.php` (API key lookup only; Windows path is case-insensitive)
- `app/Core/PublicApiResponse.php` (route-appropriate Allow header)
- `app/Core/Request.php` (bounded strict auth JSON parsing)
- `app/Core/Router.php` (V1 method fallback recognizes registered POST routes)
- `app/Services/AuthService.php` (pure credential-verification extraction only)
- `routes/web.php` (four approved auth routes)
- `database/structure.sql` (exact additive artifact appended only)
- `docs/public-api-v1.md` (authentication cross-reference/scope accuracy only)

Temporary TLS/limiter/test-server/database artifacts were removed. No other project files were changed by this task.
