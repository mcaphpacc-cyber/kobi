# Step 50B — Deployment & Production Activation

> **Current status (16 October 2026): PASS — Step 50B Deployment & Production Activation COMPLETE.** Step 50B acceptance remains the independently verified **PASS, 458 checks**, finished `2026-10-15T21:10:56+00:00` (16 October 2026, 02:40:56 IST). Current endpoint availability is **PASS** after normal XAMPP operator startup resolved the temporary Apache-unavailable condition. Minimal read-only PHP/cURL HTTPS and deployment-integrity checks passed; acceptance was not rerun or changed. See **Availability Resolution** below. Sections 1–19, diagnosis and the earlier availability blocker are historical checkpoints.

## 1. Deployment status

**BLOCKED — production acceptance failed at valid-access-token logout.** The approved schema and dedicated limiter key are deployed; this is a partial deployment, not completed activation. The normal HTTPS smoke stopped when POST `/api/v1/auth/logout` returned **401 instead of 200** for a newly rotated access credential. No corrective application/server-header change or smoke rerun was performed. No Step 51 work began.

The Codex browser certificate warning is informational under the revised authorization and is **not** the acceptance blocker. PHP/cURL successfully verified the normal HTTPS endpoint with the intended certificate and normal peer/hostname verification.

This report supersedes the earlier stopped-preflight report. Earlier isolated implementation results remain documented separately.

## 2. Date/time

16 October 2026, Asia/Kolkata (IST, UTC+05:30). Fresh backup file completed approximately 01:59:45 IST; deployed verification approximately 02:01:08 IST. Protected production-smoke result timestamp: 02:03:28 IST (`2026-10-15T20:33:28Z`). Subsequent cleanup and final integrity checks completed during this deployment run. These times come from the evidence file timestamps, not an invented successful activation timestamp.

## 3. Environment

Workspace `D:\xampp\htdocs\kobi`; normal database `medical1`; MariaDB 10.4.27; PHP 8.2.0; XAMPP Apache 2.4.54. Normal API target `https://localhost/kobi/public/api/v1`. Configured application URL/base path were preserved. Account 1 was explicitly designated by the user for smoke testing; no new account was created.

## 4. Pre-deployment state and scope

Both approved tables and the approved trigger were absent; dedicated `API_AUTH_RATE_KEY` was absent. Account 1 was active with verified email and password eligibility. The approved SQL artifact matches the additive block in `database/structure.sql`.

Artifact SHA-256: `52ba71caea0f08b64542987ca96218c5f3ac7e4330e4d480758c22e80ce2eb12`.

Normal source inventory: 60 tables, one view, 15 triggers, no routines/events. Reference-schema comparison found these pre-existing absent Treatment tables: `treatment_sources`, `treatment_protocol_sources`, `treatment_item_sources`, `treatment_protocol_events`, `treatment_editorial_permissions`; and absent triggers `trg_treatment_source_no_self_insert`, `trg_treatment_source_no_self_update`. There were no additional database-only table/trigger names. This existing gap was recorded and left untouched; no Treatment deployment/repair was attempted.

The newly written operational helper initially assumed every non-API reference-schema object was already deployed. Its inventory guard was corrected before backup/deployment to record the exact five-table/two-trigger gap and reject other differences. Existing regression tests/assertions were not edited or weakened. Acceptance still requires all pre-existing metadata and rows to match the fresh backup exactly; it does not assume reference-schema objects absent before this task were deployed by it.

Initial sandboxed SQL test attempts were denied at the socket layer before normal DB work. The same existing suites were then run outside the socket sandbox with their original disposable-database guards; no test behavior was changed.

## 5. Backup and recovery verification

**PASS.** Protected directory outside the web root/repository: `D:\xampp\kobi-backups\step50b-20261016`. Directory inheritance is disabled; access is restricted to Sanjog, SYSTEM and Administrators.

Authoritative backup: `before.bin`, 11,306,334 bytes; SHA-256 `58a14da528019b653b7ef002bf461d002ad4e62d43c2520a2ee9762ba6241523`. It contains sensitive existing data and is intentionally not copied into this report or repository. Backup readback and SHA-256 equality passed.

Recovery rehearsal reused the established `sscCapture`/`sscDigest` procedure in `tests/saved-symptom-checks-recovery.php`, through a new Step-50B-scoped CLI helper. Restored only to guarded disposable `kobi_api_auth_recovery_20261016`: all 60 tables, ordinary-column inserts with generated columns recomputed, one view, 15 triggers preserving client charset/collation and SQL-mode metadata. Full recovered DDL/columns/generated values/rows/views/trigger metadata/defaults/SQL modes matched the original capture. No SQL-mode change was used. The exact API DDL was then rehearsed on this copy without warnings, producing expected normalized table/trigger metadata. Normal KOBI's complete fingerprint still matched before deployment.

Recovery proof `recovery-verified.json`, deployment/final-state evidence, environment rollback backup and smoke metadata remain in the protected directory. Only the disposable recovery database was dropped after final checks; the backup remains retained.

Recovery procedure: validate the backup hash/proof, use the reviewed CLI restoration logic against a separately guarded empty recovery database, recreate tables, insert ordinary columns, recreate view/trigger definitions with captured metadata, and compare full digests before considering any real recovery. A normal-database restore was NOT performed or authorized as a test. DDL autocommits; future production rollback must be separately planned to disable issuance and remove the API revocation trigger before removing its referenced tables. No rollback/drop of deployed normal API objects was attempted after the smoke failure.

## 6. Two approved tables deployed

**PASS — definitions match the exact approved artifact and isolated rehearsal.**

- `user_api_sessions`
- `user_api_refresh_tokens`

Verified metadata includes two primary keys, five unique constraints, two foreign keys and nine CHECK constraints. Generated active-slot uniqueness and fixed digest/label/public-ID validation remain intact. No unrelated table/index/constraint was changed.

## 7. One approved trigger deployed

**PASS — `trg_users_api_revoke`**. Definition and captured metadata match the exact rehearsal, including SQL mode and client charset/collation. It revokes only API families on password/account eligibility changes. Existing triggers were preserved. Normal account/password invalidation was not exercised by editing Account 1; actual invalidation/rollback behavior passed in the existing isolated MariaDB suite.

## 8. Limiter provisioning

Dedicated random 32-byte key provisioned through the existing `.env` setting `API_AUTH_RATE_KEY`; no secret was output, passed as a command-line argument, placed in source, or included in this report. Existing storage path remains `storage/cache/api-auth/counters.json`; actual smoke requests created/used the existing limiter mechanism. The limiter was not disabled, bypassed, redesigned or weakened.

A protected `env-before.bin` rollback copy was retained outside the web root. The original `.env` bytes remain an exact prefix of the resulting file; only a line terminator plus the dedicated key line was appended. The provisioning helper's `environment_other_bytes_preserved=false` diagnostic reflects its line-removal comparison against an original file lacking a final newline; a separate byte-prefix/tail check confirmed the only addition is the dedicated key line and required delimiter. No unrelated environment credential/value changed.

Isolated limiter tests: PASS, 13 checks. Normal HTTPS issuance passes the configured limiter; production 429 enforcement was not reached before the logout failure and is NOT claimed verified.

## 9. SQL mode before/after

Both session and global/server mode, before backup and after deployment/smoke cleanup:

`NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`

**PASS — identical.** No SQL-mode statement or persistent/server SQL-mode change was performed.

## 10. Application/configuration changes and file inventory

**Application-code changes: ZERO.** Aggregate SHA-256 over `app`, `bootstrap`, `routes`, `database`, `public`, `resources` before/after: `DB64C5F21A4D47F85E8A0AD7DA3458A733971336708367A1F2C8DE7D92746B4D` (identical).

Project files modified:

- `.env`: dedicated limiter key only.
- `docs/task-50b-deployment-activation.md`: this report.

Project files created (CLI deployment/verification tooling, not application routes):

- `tests/api-auth-deployment.php`: guarded fresh backup/recovery, exact deployment, verification, limiter provisioning and disposable recovery cleanup.
- `tests/api-auth-production-smoke.php`: normal verified HTTPS smoke with in-memory credentials and safe evidence output.
- `tests/api-auth-production-smoke.ps1`: user-operated hidden password prompt, JSON passed only on stdin.

Runtime counter data was created under the existing ignored storage path. No schema source, controller/service/repository, route, view/JS, public `.htaccess` or existing regression test was modified. No application behavior was redesigned.

Local TLS infrastructure outside the project was restored to the previously validated intended configuration solely for authoritative PHP verification: `D:\xampp\apache\conf\extra\httpd-ssl.conf` uses `localhost:443` and the existing `kobi-local-tls/localhost.pem`/`.key`; `D:\xampp\php\php.ini` uses the existing combined `php-ca-bundle.pem` for `curl.cainfo`/`openssl.cafile`. Verified original configuration backups remain in `kobi-local-tls/rollback-20261016`. Only exact XAMPP Apache was restarted (parent PID 15096 observed). No CA regeneration/install/removal, registry change or browser trust/settings change occurred in this revised deployment.

## 11. Authoritative PHP/cURL TLS verification

**PASS.** Verified normal HTTPS response 200, cURL errno 0, certificate verification result 0, correct live leaf SHA-256 `83f096f60d695d66513b57da98b4ba6cc41763b65b045afcd01f3db76cc18087`, hostname localhost and configured PHP CA path. OpenSSL chain/server-purpose/localhost verification passed before cutover; certificate/key pairing was previously verified for the unchanged staged material. Apache syntax remains OK.

Requests explicitly use `CURLOPT_SSL_VERIFYPEER=true`, `CURLOPT_SSL_VERIFYHOST=2`, redirects disabled, and the configured CA trust path (no per-request CA override). No insecure fallback, cURL -k, verification suppression or HTTP credential transmission occurred. Earlier negative hostname/old-bundle controls are historical TLS evidence, not newly executed production acceptance checks.

The valid-token logout request itself passed TLS and expected-peer-certificate assertions before receiving application HTTP 401. Thus the blocker is application/Authorization handling, not certificate trust.

## 12. Browser verification and limitation

Fresh informational HTTPS navigation returned `net::ERR_CERT_AUTHORITY_INVALID`.

“Codex/browser automation trust limitation persists; PHP/cURL is the authoritative HTTPS verifier.”

No warning was bypassed. The prior trust investigation remains stopped; no certificate/registry/browser-setting changes were made to satisfy this check. This warning did not prevent schema deployment or cause the current BLOCKED decision.

## 13. Normal authenticated API smoke

User explicitly selected Account 1 and entered its password through the local hidden PowerShell prompt. Credentials were piped on stdin and used only in-memory against certificate-verified localhost HTTPS. No new normal user, password change, role change or account-state edit occurred.

The protected result records **111 successful assertions before failure**. Observed normal results:

| Check | Result |
|---|---|
| Valid login / exact opaque credential response / ten-minute lifetime | PASS |
| Wrong password safe rejection | PASS |
| Refresh rotation, followed by successful use of the successor refresh | PASS |
| Old access rejection after rotation | Observed 401; this alone does not prove correct Bearer forwarding |
| Consumed refresh replay | PASS; database family reason `refresh_reuse` confirmed |
| Revoked family access rejection | Observed 401; same forwarding limitation applies |
| Malformed/unknown access rejection | Observed 401 |
| Newly rotated valid access used for single logout | **FAIL: expected 200, observed 401** |
| Single logout/device isolation | NOT completed |
| Real access expiry after ten minutes | NOT reached; wait phase did not start |
| Logout-all | NOT reached |
| Normal 429 enforcement | NOT reached |

Exact failure: `HTTP status for auth/logout expected 200 observed 401`. Protected evidence `production-smoke.json`, status FAILED. No automatic rerun or corrective change was made.

Possible cause: Bearer Authorization forwarding into PHP. The current `public/.htaccess` has only front-controller rewrite/protection rules, no explicit Authorization forwarding; the controller reads `HTTP_AUTHORIZATION`/`REDIRECT_HTTP_AUTHORIZATION`. No supported header-presence diagnostic established which values PHP received, so this is a hypothesis, NOT a confirmed root cause. Do not change authentication logic or add a diagnostic endpoint merely to claim success.

Cleanup: four new API families and seven digest-only refresh-history rows were created by the smoke. One family was already revoked by reuse detection. The three remaining active test families (guarded by IDs, account, labels, pre-smoke empty-table proof and exact total count) were revoked using the existing repository's locked transaction/revocation methods. **Zero active API families remain.** No rows were deleted and no user/health data changed. This administrative test cleanup is NOT a passing HTTP logout/logout-all test. Revoked hashes/history are retained according to the approved retention policy.

## 14. Regression results

| Executed suite/check | Current result |
|---|---|
| `tests/api-auth.php` | 103 checks PASS |
| `tests/api-auth-rate-limit.php` | 13 checks PASS; disposable files removed |
| `tests/api-auth-mariadb.php` | 65 checks PASS; disposable DB removed |
| `tests/api-auth-http.php` | 326 isolated HTTPS checks PASS; fixture DB/server/files removed |
| `tests/auth-phase1.php` | 47 database-free scenarios PASS |
| `tests/public-api-v1.php` | 56 checks PASS |
| `tests/public-api-v1-bootstrap.php` | 8 checks PASS |
| `tests/public-api-v1-mariadb.php` | 38 checks PASS; disposable DB removed |
| `tests/public-api-v1-http.php` | 561 read-only assertions PASS |
| `tests/public-search.php` | 30 checks PASS |
| `tests/disease-references.php` | 71 checks PASS |
| `tests/disease-detail-presentation.php` | 7 scenarios PASS |
| `tests/treatment-publication.php` | 105 tests PASS |
| `node tests/substance-search.cjs` | 11 DOM-contract checks PASS |
| Normal PHP/cURL HTTPS public API/private-boundary smoke | 15 checks PASS |
| PHP syntax | 15 affected implementation/config/route/cleanup files plus two new PHP helpers PASS |
| New PowerShell helper parser | PASS |
| Full normal backup recovery / exact DDL rehearsal | PASS |
| Post-deployment full unrelated metadata/row integrity | PASS |
| Normal authenticated API smoke | **FAILED at valid-token logout; incomplete acceptance** |

Existing isolated suites exercise actual expiry boundaries, concurrent refresh/reuse, revocation, password/account invalidation and rollback. They do not replace the missing normal-environment acceptance results. Existing auth scenarios cover password reset/change, session-related behavior, CSRF and Google link/unlink/boundary cases. No real Google callback, reset email, normal password/account-state change, or fresh genuine browser login/logout was performed during this deployment. Browser trust remains informational; no such unexecuted flow is represented as newly passed.

## 15. Security verification

No JWT, CORS, external auth provider, native Google API, private-health endpoint or `/api/v1/me` was introduced. Ten-minute access policy, refresh rotation, reuse detection and immediate revocation logic are unchanged. Stored API digest lengths are all 32 bytes; raw access/refresh credentials were never written by the smoke tooling. Appended-log exact-secret scanning in the smoke was not reached because it stopped earlier. A subsequent read-only scan of available application/Apache/PHP logs found **zero raw API-token-format matches**; no matching log contents were printed. This is not a claim of exhaustive password-log forensics.

No password, raw token, API key, limiter secret or private key was printed or included in reports. The normal .env is covered by the existing ignore rule; the dedicated key is not hard-coded into source. Backup/environment rollback material remains protected outside the web root/repository. No key/certificate material was copied into KOBI. The current task performed no Git index operation or credential-related Git inspection.

Substantive unresolved security/functional requirement: HTTP Bearer-authenticated logout has not succeeded, so API activation cannot be declared PASS despite working login/refresh and passing isolated tests.

## 16. Data-integrity verification

**PASS.** The final full capture, excluding only the two approved API tables and approved new trigger, exactly matches the fresh pre-deployment backup fingerprint: existing DDL, columns, generated values, rows, views, trigger metadata, database defaults and SQL modes are unchanged. This includes all existing user/profile/mobile/authentication rows, health/medication/schedule/saved-check data, disease, symptom, Medicine and Treatment data. No counters were intentionally incremented by symptom matching.

Only new API infrastructure/history rows, its limiter runtime counters/key and approved schema objects changed. Final API counts: four revoked families, seven refresh-history rows, zero active families. Known API/public fixture and recovery database names were checked absent after cleanup. Backup retained.

## 17. Existing-feature preservation

Effective configuration: Treatment publication `hold`; Medicine pilot and internal pilot both false. No governed Treatment/rich Medicine exposure was added. Existing public disease/detail/reference/substance/search regressions passed. HTTPS boundary checks confirmed public APIs/legacy substance lookup still respond and private health/profile/document/medication/saved-check/summary, `/api/v1/me`, Treatment and rich Substance-ID routes remain unavailable. No Dashboard, schedules, mobile-number, health-profile or saved-check application code changed; full unchanged-data verification complements the focused regressions.

## 18. Limitations and smallest safe next action

The normal valid-token logout failure is a new substantive deployment finding. Stop before changing code or tests. Next action is a narrowly scoped, separately authorized diagnostic of Bearer Authorization forwarding through normal Apache/PHP, followed by the smallest confirmed correction and renewed smoke acceptance. No public credential/header dump, token logging, TLS bypass, new auth architecture or unrelated schema fix is justified.

The schema and key remain deployed; activation is **not accepted**. No remaining API test credentials are active. Real expiry, valid access/logout, logout-all, device isolation and normal rate-limit enforcement remain to be completed after the blocker is resolved. The existing failed smoke evidence must not be silently overwritten or the helper rerun against nonempty API history; any resumed run needs an explicit evidence/checkpoint plan. No Step 51 work is authorized or performed.

## 19. Final decision and Git

**BLOCKED — Step 50B Deployment & Production Activation is NOT complete.**

Backup/recovery, exact additive schema deployment, limiter provisioning, authoritative PHP/cURL TLS, isolated/public regressions and unrelated-data/schema preservation passed. Production Bearer-authenticated logout failed (HTTP 401 instead of 200), so acceptance criteria are incomplete.

No Git command or commit/push/pull/reset/checkout/switch/merge/rebase/amend/index/history mutation occurred. No unrelated file/data repair was attempted. Stop after reporting this blocker.

## Authorization Header Forwarding Diagnosis

**DIAGNOSIS ONLY — PASS. Root cause confirmed; corrective change NOT APPLIED.** Normal reproduction completed 16 October 2026 at approximately 02:21:46 IST (`2026-10-15T20:51:46Z`). This section supersedes the earlier forwarding hypothesis and pending-probe status. Overall Step 50B deployment acceptance remains BLOCKED until a separately authorized correction and resumed acceptance succeed.

### 1. Reproduction result

One fresh family was created for the previously designated test Account 1 through the normal HTTPS login endpoint, then rotated through normal refresh. One marked POST `/api/v1/auth/logout` with the new access token returned **401 UNAUTHENTICATED**, with a Bearer challenge. The request's peer/hostname verification remained enabled; TLS verification result was 0. Logout did not change the family's revoked state.

### 2–3. Successful refresh versus failing logout

| Property | Refresh | Logout |
|---|---|---|
| HTTPS base/bootstrap/router/controller class | Same | Same |
| Method | POST | POST |
| Content-Type | application/json | application/json |
| Credential source | refresh_token in JSON body | Authorization Bearer header |
| Body | Approved refresh object | `{}` |
| Result | 200 | 401 |
| TLS verification result | 0 | 0 |

Both use ApiAuthController but dispatch to different methods. Refresh requires no Authorization header. Its success never proved Bearer extraction worked. Request.php handles media type, body size and JSON shape; it does not extract Authorization. Router performs ordinary route dispatch and does not independently authenticate the token.

### 4. Client-side header presence

PHP/cURL request metadata was inspected in memory using CURLINFO_HEADER_OUT, without verbose output or raw-header logging. All logout checks were true: POST method, Authorization present, Bearer scheme, outgoing header exactly matches the newly selected access token, application/json, empty JSON object body. Redirect following was disabled. No credential was sent in a URL, cookie or diagnostic response.

Client display: `Authorization: Bearer <REDACTED>`.

### 5. Apache/PHP presence observations

| Runtime observation on the failing request | Result |
|---|---|
| Apache request headers exposed through getallheaders() | Authorization PRESENT |
| `# Step 50B — Deployment & Production Activation

## 1. Deployment status

**BLOCKED — production acceptance failed at valid-access-token logout.** The approved schema and dedicated limiter key are deployed; this is a partial deployment, not completed activation. The normal HTTPS smoke stopped when POST `/api/v1/auth/logout` returned **401 instead of 200** for a newly rotated access credential. No corrective application/server-header change or smoke rerun was performed. No Step 51 work began.

The Codex browser certificate warning is informational under the revised authorization and is **not** the acceptance blocker. PHP/cURL successfully verified the normal HTTPS endpoint with the intended certificate and normal peer/hostname verification.

This report supersedes the earlier stopped-preflight report. Earlier isolated implementation results remain documented separately.

## 2. Date/time

16 October 2026, Asia/Kolkata (IST, UTC+05:30). Fresh backup file completed approximately 01:59:45 IST; deployed verification approximately 02:01:08 IST. Protected production-smoke result timestamp: 02:03:28 IST (`2026-10-15T20:33:28Z`). Subsequent cleanup and final integrity checks completed during this deployment run. These times come from the evidence file timestamps, not an invented successful activation timestamp.

## 3. Environment

Workspace `D:\xampp\htdocs\kobi`; normal database `medical1`; MariaDB 10.4.27; PHP 8.2.0; XAMPP Apache 2.4.54. Normal API target `https://localhost/kobi/public/api/v1`. Configured application URL/base path were preserved. Account 1 was explicitly designated by the user for smoke testing; no new account was created.

## 4. Pre-deployment state and scope

Both approved tables and the approved trigger were absent; dedicated `API_AUTH_RATE_KEY` was absent. Account 1 was active with verified email and password eligibility. The approved SQL artifact matches the additive block in `database/structure.sql`.

Artifact SHA-256: `52ba71caea0f08b64542987ca96218c5f3ac7e4330e4d480758c22e80ce2eb12`.

Normal source inventory: 60 tables, one view, 15 triggers, no routines/events. Reference-schema comparison found these pre-existing absent Treatment tables: `treatment_sources`, `treatment_protocol_sources`, `treatment_item_sources`, `treatment_protocol_events`, `treatment_editorial_permissions`; and absent triggers `trg_treatment_source_no_self_insert`, `trg_treatment_source_no_self_update`. There were no additional database-only table/trigger names. This existing gap was recorded and left untouched; no Treatment deployment/repair was attempted.

The newly written operational helper initially assumed every non-API reference-schema object was already deployed. Its inventory guard was corrected before backup/deployment to record the exact five-table/two-trigger gap and reject other differences. Existing regression tests/assertions were not edited or weakened. Acceptance still requires all pre-existing metadata and rows to match the fresh backup exactly; it does not assume reference-schema objects absent before this task were deployed by it.

Initial sandboxed SQL test attempts were denied at the socket layer before normal DB work. The same existing suites were then run outside the socket sandbox with their original disposable-database guards; no test behavior was changed.

## 5. Backup and recovery verification

**PASS.** Protected directory outside the web root/repository: `D:\xampp\kobi-backups\step50b-20261016`. Directory inheritance is disabled; access is restricted to Sanjog, SYSTEM and Administrators.

Authoritative backup: `before.bin`, 11,306,334 bytes; SHA-256 `58a14da528019b653b7ef002bf461d002ad4e62d43c2520a2ee9762ba6241523`. It contains sensitive existing data and is intentionally not copied into this report or repository. Backup readback and SHA-256 equality passed.

Recovery rehearsal reused the established `sscCapture`/`sscDigest` procedure in `tests/saved-symptom-checks-recovery.php`, through a new Step-50B-scoped CLI helper. Restored only to guarded disposable `kobi_api_auth_recovery_20261016`: all 60 tables, ordinary-column inserts with generated columns recomputed, one view, 15 triggers preserving client charset/collation and SQL-mode metadata. Full recovered DDL/columns/generated values/rows/views/trigger metadata/defaults/SQL modes matched the original capture. No SQL-mode change was used. The exact API DDL was then rehearsed on this copy without warnings, producing expected normalized table/trigger metadata. Normal KOBI's complete fingerprint still matched before deployment.

Recovery proof `recovery-verified.json`, deployment/final-state evidence, environment rollback backup and smoke metadata remain in the protected directory. Only the disposable recovery database was dropped after final checks; the backup remains retained.

Recovery procedure: validate the backup hash/proof, use the reviewed CLI restoration logic against a separately guarded empty recovery database, recreate tables, insert ordinary columns, recreate view/trigger definitions with captured metadata, and compare full digests before considering any real recovery. A normal-database restore was NOT performed or authorized as a test. DDL autocommits; future production rollback must be separately planned to disable issuance and remove the API revocation trigger before removing its referenced tables. No rollback/drop of deployed normal API objects was attempted after the smoke failure.

## 6. Two approved tables deployed

**PASS — definitions match the exact approved artifact and isolated rehearsal.**

- `user_api_sessions`
- `user_api_refresh_tokens`

Verified metadata includes two primary keys, five unique constraints, two foreign keys and nine CHECK constraints. Generated active-slot uniqueness and fixed digest/label/public-ID validation remain intact. No unrelated table/index/constraint was changed.

## 7. One approved trigger deployed

**PASS — `trg_users_api_revoke`**. Definition and captured metadata match the exact rehearsal, including SQL mode and client charset/collation. It revokes only API families on password/account eligibility changes. Existing triggers were preserved. Normal account/password invalidation was not exercised by editing Account 1; actual invalidation/rollback behavior passed in the existing isolated MariaDB suite.

## 8. Limiter provisioning

Dedicated random 32-byte key provisioned through the existing `.env` setting `API_AUTH_RATE_KEY`; no secret was output, passed as a command-line argument, placed in source, or included in this report. Existing storage path remains `storage/cache/api-auth/counters.json`; actual smoke requests created/used the existing limiter mechanism. The limiter was not disabled, bypassed, redesigned or weakened.

A protected `env-before.bin` rollback copy was retained outside the web root. The original `.env` bytes remain an exact prefix of the resulting file; only a line terminator plus the dedicated key line was appended. The provisioning helper's `environment_other_bytes_preserved=false` diagnostic reflects its line-removal comparison against an original file lacking a final newline; a separate byte-prefix/tail check confirmed the only addition is the dedicated key line and required delimiter. No unrelated environment credential/value changed.

Isolated limiter tests: PASS, 13 checks. Normal HTTPS issuance passes the configured limiter; production 429 enforcement was not reached before the logout failure and is NOT claimed verified.

## 9. SQL mode before/after

Both session and global/server mode, before backup and after deployment/smoke cleanup:

`NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`

**PASS — identical.** No SQL-mode statement or persistent/server SQL-mode change was performed.

## 10. Application/configuration changes and file inventory

**Application-code changes: ZERO.** Aggregate SHA-256 over `app`, `bootstrap`, `routes`, `database`, `public`, `resources` before/after: `DB64C5F21A4D47F85E8A0AD7DA3458A733971336708367A1F2C8DE7D92746B4D` (identical).

Project files modified:

- `.env`: dedicated limiter key only.
- `docs/task-50b-deployment-activation.md`: this report.

Project files created (CLI deployment/verification tooling, not application routes):

- `tests/api-auth-deployment.php`: guarded fresh backup/recovery, exact deployment, verification, limiter provisioning and disposable recovery cleanup.
- `tests/api-auth-production-smoke.php`: normal verified HTTPS smoke with in-memory credentials and safe evidence output.
- `tests/api-auth-production-smoke.ps1`: user-operated hidden password prompt, JSON passed only on stdin.

Runtime counter data was created under the existing ignored storage path. No schema source, controller/service/repository, route, view/JS, public `.htaccess` or existing regression test was modified. No application behavior was redesigned.

Local TLS infrastructure outside the project was restored to the previously validated intended configuration solely for authoritative PHP verification: `D:\xampp\apache\conf\extra\httpd-ssl.conf` uses `localhost:443` and the existing `kobi-local-tls/localhost.pem`/`.key`; `D:\xampp\php\php.ini` uses the existing combined `php-ca-bundle.pem` for `curl.cainfo`/`openssl.cafile`. Verified original configuration backups remain in `kobi-local-tls/rollback-20261016`. Only exact XAMPP Apache was restarted (parent PID 15096 observed). No CA regeneration/install/removal, registry change or browser trust/settings change occurred in this revised deployment.

## 11. Authoritative PHP/cURL TLS verification

**PASS.** Verified normal HTTPS response 200, cURL errno 0, certificate verification result 0, correct live leaf SHA-256 `83f096f60d695d66513b57da98b4ba6cc41763b65b045afcd01f3db76cc18087`, hostname localhost and configured PHP CA path. OpenSSL chain/server-purpose/localhost verification passed before cutover; certificate/key pairing was previously verified for the unchanged staged material. Apache syntax remains OK.

Requests explicitly use `CURLOPT_SSL_VERIFYPEER=true`, `CURLOPT_SSL_VERIFYHOST=2`, redirects disabled, and the configured CA trust path (no per-request CA override). No insecure fallback, cURL -k, verification suppression or HTTP credential transmission occurred. Earlier negative hostname/old-bundle controls are historical TLS evidence, not newly executed production acceptance checks.

The valid-token logout request itself passed TLS and expected-peer-certificate assertions before receiving application HTTP 401. Thus the blocker is application/Authorization handling, not certificate trust.

## 12. Browser verification and limitation

Fresh informational HTTPS navigation returned `net::ERR_CERT_AUTHORITY_INVALID`.

“Codex/browser automation trust limitation persists; PHP/cURL is the authoritative HTTPS verifier.”

No warning was bypassed. The prior trust investigation remains stopped; no certificate/registry/browser-setting changes were made to satisfy this check. This warning did not prevent schema deployment or cause the current BLOCKED decision.

## 13. Normal authenticated API smoke

User explicitly selected Account 1 and entered its password through the local hidden PowerShell prompt. Credentials were piped on stdin and used only in-memory against certificate-verified localhost HTTPS. No new normal user, password change, role change or account-state edit occurred.

The protected result records **111 successful assertions before failure**. Observed normal results:

| Check | Result |
|---|---|
| Valid login / exact opaque credential response / ten-minute lifetime | PASS |
| Wrong password safe rejection | PASS |
| Refresh rotation, followed by successful use of the successor refresh | PASS |
| Old access rejection after rotation | Observed 401; this alone does not prove correct Bearer forwarding |
| Consumed refresh replay | PASS; database family reason `refresh_reuse` confirmed |
| Revoked family access rejection | Observed 401; same forwarding limitation applies |
| Malformed/unknown access rejection | Observed 401 |
| Newly rotated valid access used for single logout | **FAIL: expected 200, observed 401** |
| Single logout/device isolation | NOT completed |
| Real access expiry after ten minutes | NOT reached; wait phase did not start |
| Logout-all | NOT reached |
| Normal 429 enforcement | NOT reached |

Exact failure: `HTTP status for auth/logout expected 200 observed 401`. Protected evidence `production-smoke.json`, status FAILED. No automatic rerun or corrective change was made.

Possible cause: Bearer Authorization forwarding into PHP. The current `public/.htaccess` has only front-controller rewrite/protection rules, no explicit Authorization forwarding; the controller reads `HTTP_AUTHORIZATION`/`REDIRECT_HTTP_AUTHORIZATION`. No supported header-presence diagnostic established which values PHP received, so this is a hypothesis, NOT a confirmed root cause. Do not change authentication logic or add a diagnostic endpoint merely to claim success.

Cleanup: four new API families and seven digest-only refresh-history rows were created by the smoke. One family was already revoked by reuse detection. The three remaining active test families (guarded by IDs, account, labels, pre-smoke empty-table proof and exact total count) were revoked using the existing repository's locked transaction/revocation methods. **Zero active API families remain.** No rows were deleted and no user/health data changed. This administrative test cleanup is NOT a passing HTTP logout/logout-all test. Revoked hashes/history are retained according to the approved retention policy.

## 14. Regression results

| Executed suite/check | Current result |
|---|---|
| `tests/api-auth.php` | 103 checks PASS |
| `tests/api-auth-rate-limit.php` | 13 checks PASS; disposable files removed |
| `tests/api-auth-mariadb.php` | 65 checks PASS; disposable DB removed |
| `tests/api-auth-http.php` | 326 isolated HTTPS checks PASS; fixture DB/server/files removed |
| `tests/auth-phase1.php` | 47 database-free scenarios PASS |
| `tests/public-api-v1.php` | 56 checks PASS |
| `tests/public-api-v1-bootstrap.php` | 8 checks PASS |
| `tests/public-api-v1-mariadb.php` | 38 checks PASS; disposable DB removed |
| `tests/public-api-v1-http.php` | 561 read-only assertions PASS |
| `tests/public-search.php` | 30 checks PASS |
| `tests/disease-references.php` | 71 checks PASS |
| `tests/disease-detail-presentation.php` | 7 scenarios PASS |
| `tests/treatment-publication.php` | 105 tests PASS |
| `node tests/substance-search.cjs` | 11 DOM-contract checks PASS |
| Normal PHP/cURL HTTPS public API/private-boundary smoke | 15 checks PASS |
| PHP syntax | 15 affected implementation/config/route/cleanup files plus two new PHP helpers PASS |
| New PowerShell helper parser | PASS |
| Full normal backup recovery / exact DDL rehearsal | PASS |
| Post-deployment full unrelated metadata/row integrity | PASS |
| Normal authenticated API smoke | **FAILED at valid-token logout; incomplete acceptance** |

Existing isolated suites exercise actual expiry boundaries, concurrent refresh/reuse, revocation, password/account invalidation and rollback. They do not replace the missing normal-environment acceptance results. Existing auth scenarios cover password reset/change, session-related behavior, CSRF and Google link/unlink/boundary cases. No real Google callback, reset email, normal password/account-state change, or fresh genuine browser login/logout was performed during this deployment. Browser trust remains informational; no such unexecuted flow is represented as newly passed.

## 15. Security verification

No JWT, CORS, external auth provider, native Google API, private-health endpoint or `/api/v1/me` was introduced. Ten-minute access policy, refresh rotation, reuse detection and immediate revocation logic are unchanged. Stored API digest lengths are all 32 bytes; raw access/refresh credentials were never written by the smoke tooling. Appended-log exact-secret scanning in the smoke was not reached because it stopped earlier. A subsequent read-only scan of available application/Apache/PHP logs found **zero raw API-token-format matches**; no matching log contents were printed. This is not a claim of exhaustive password-log forensics.

No password, raw token, API key, limiter secret or private key was printed or included in reports. The normal .env is covered by the existing ignore rule; the dedicated key is not hard-coded into source. Backup/environment rollback material remains protected outside the web root/repository. No key/certificate material was copied into KOBI. The current task performed no Git index operation or credential-related Git inspection.

Substantive unresolved security/functional requirement: HTTP Bearer-authenticated logout has not succeeded, so API activation cannot be declared PASS despite working login/refresh and passing isolated tests.

## 16. Data-integrity verification

**PASS.** The final full capture, excluding only the two approved API tables and approved new trigger, exactly matches the fresh pre-deployment backup fingerprint: existing DDL, columns, generated values, rows, views, trigger metadata, database defaults and SQL modes are unchanged. This includes all existing user/profile/mobile/authentication rows, health/medication/schedule/saved-check data, disease, symptom, Medicine and Treatment data. No counters were intentionally incremented by symptom matching.

Only new API infrastructure/history rows, its limiter runtime counters/key and approved schema objects changed. Final API counts: four revoked families, seven refresh-history rows, zero active families. Known API/public fixture and recovery database names were checked absent after cleanup. Backup retained.

## 17. Existing-feature preservation

Effective configuration: Treatment publication `hold`; Medicine pilot and internal pilot both false. No governed Treatment/rich Medicine exposure was added. Existing public disease/detail/reference/substance/search regressions passed. HTTPS boundary checks confirmed public APIs/legacy substance lookup still respond and private health/profile/document/medication/saved-check/summary, `/api/v1/me`, Treatment and rich Substance-ID routes remain unavailable. No Dashboard, schedules, mobile-number, health-profile or saved-check application code changed; full unchanged-data verification complements the focused regressions.

## 18. Limitations and smallest safe next action

The normal valid-token logout failure is a new substantive deployment finding. Stop before changing code or tests. Next action is a narrowly scoped, separately authorized diagnostic of Bearer Authorization forwarding through normal Apache/PHP, followed by the smallest confirmed correction and renewed smoke acceptance. No public credential/header dump, token logging, TLS bypass, new auth architecture or unrelated schema fix is justified.

The schema and key remain deployed; activation is **not accepted**. No remaining API test credentials are active. Real expiry, valid access/logout, logout-all, device isolation and normal rate-limit enforcement remain to be completed after the blocker is resolved. The existing failed smoke evidence must not be silently overwritten or the helper rerun against nonempty API history; any resumed run needs an explicit evidence/checkpoint plan. No Step 51 work is authorized or performed.

## 19. Final decision and Git

**BLOCKED — Step 50B Deployment & Production Activation is NOT complete.**

Backup/recovery, exact additive schema deployment, limiter provisioning, authoritative PHP/cURL TLS, isolated/public regressions and unrelated-data/schema preservation passed. Production Bearer-authenticated logout failed (HTTP 401 instead of 200), so acceptance criteria are incomplete.

No Git command or commit/push/pull/reset/checkout/switch/merge/rebase/amend/index/history mutation occurred. No unrelated file/data repair was attempted. Stop after reporting this blocker.

## Authorization Header Forwarding Diagnosis

Status: **DIAGNOSIS ONLY — BLOCKED pending permission for runtime presence-only observation.** No corrective change, authentication-family creation, test rerun or schema/configuration mutation has occurred in this diagnosis.

### Read-only findings

Historical reproduction remains POST `https://localhost/kobi/public/api/v1/auth/logout`, Content-Type application/json, body `{}`, using the newly refreshed `c2.access_token` directly, without redirect following. The deployment harness constructs one `Authorization: Bearer <REDACTED>` header through CURLOPT_HTTPHEADER; peer and hostname verification are enabled. Successful refresh instead passes `refresh_token` in a JSON body and requires no Bearer header. Both use the same bootstrap/router/controller class and POST method, but different controller methods/authentication extraction. Historical token-state metadata shows generation 2 and 600-second access lifetime for the failing family before administrative cleanup; its raw token was never retained. Its current revoked state is intentional cleanup and cannot be used to prove its earlier request state.

`Request::authJson()` handles only body/media type/field syntax; Request.php has no Authorization extractor. Router dispatches to `ApiAuthController::logout()` without a separate auth middleware. The controller validates `{}` first, then reads HTTP_AUTHORIZATION / REDIRECT_HTTP_AUTHORIZATION; conflicting values cause denial. Otherwise `ApiAuthGuard::token()` requires an exact valid Bearer access format; `resolve()` invokes authenticate before service logout. The guard/service may throw the same denied exception, which the controller converts to JSON 401. Status alone cannot establish the actual throwing branch. No database-backed logout success is inferred from earlier expected-401 requests.

No existing supported diagnostic exposes live Apache/PHP header presence for this endpoint. Reading source, tests or configuration cannot prove the incoming runtime variables. No phpinfo/server-variable dump, debug endpoint, raw request trace/log, debugger attachment or certificate bypass was attempted.

### Concrete temporary diagnostic proposal — NOT APPLIED

The task forbids modifying application code; permission is therefore needed before even temporary front-controller instrumentation. Proposed insertion in `public/index.php`, after strict_types and before the two unchanged require statements:

```php
// Temporary presence-only observation, removed before the diagnosis ends.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && ($_SERVER['REQUEST_URI'] ?? '') === '/kobi/public/api/v1/auth/logout'
    && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
    && ($_SERVER['HTTP_X_KOBI_HEADER_DIAG'] ?? '') === 'step50b-presence-only') {
    $diagnosticHeaders = function_exists('getallheaders') ? getallheaders() : [];
    $diagnosticPresent = false;
    foreach ($diagnosticHeaders as $diagnosticName => $diagnosticValue) {
        if (strcasecmp($diagnosticName, 'Authorization') === 0) { $diagnosticPresent = true; }
    }
    header('X-Kobi-Diag-Server-Authorization: '.(isset($_SERVER['HTTP_AUTHORIZATION']) ? 'PRESENT' : 'ABSENT'));
    header('X-Kobi-Diag-Redirect-Authorization: '.(isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? 'PRESENT' : 'ABSENT'));
    header('X-Kobi-Diag-Apache-Headers-Authorization: '.($diagnosticPresent ? 'PRESENT' : 'ABSENT'));
    unset($diagnosticHeaders, $diagnosticPresent, $diagnosticName, $diagnosticValue);
}
```

This would emit three presence/absence response headers only for the marked loopback POST, then run the ordinary application unchanged. It would not set/replace any auth variable, skip the guard, alter JSON/status, log credential values, change Apache/.htaccess, or create a new route. No raw header or token would be output or saved. No ordinary request would receive diagnostic headers. The original file bytes/hash would be recorded before insertion and restored/verified before completion; no persistent instrumentation would remain.

A narrowly scoped CLI client would then require genuine test-account authentication, keep credentials only in memory, perform one login and one refresh, compare the new access digest/account/family/generation/expiry/revocation/stamp with read-only DB queries, and make the single marked logout request with CURLINFO_HEADER_OUT inspected only in memory to report method/header/scheme/body/HTTPS booleans. Cleanup can use normal refresh replay: reuse the consumed pre-rotation refresh token to trigger the existing committed family revocation, avoiding manual token-row edits if Bearer logout fails. Existing deployment smoke evidence would not be overwritten and full acceptance would not be rerun.

No fresh family is necessary until this observation is authorized; all existing test families remain revoked. Without runtime observation, header-forwarding root cause and the actual throwing layer remain unconfirmed. H1–H10 remain UNKNOWN for the actual failing request except source-inspection facts: refresh and logout have distinct extraction paths; the harness passes the newly returned token; POST/media type/JSON shape are correctly constructed. These facts alone do not prove whether runtime header forwarding, parsing or validation caused denial.

Only this report was edited during diagnosis so far. Application/authentication/routes/Request/Router/controller/services/repositories, deployed tables/trigger, TLS/limiter/Apache/.htaccess configuration and Git remain untouched. No corrective action was applied; no Step 51 work started.
SERVER['HTTP_AUTHORIZATION']` | ABSENT |
| `# Step 50B — Deployment & Production Activation

## 1. Deployment status

**BLOCKED — production acceptance failed at valid-access-token logout.** The approved schema and dedicated limiter key are deployed; this is a partial deployment, not completed activation. The normal HTTPS smoke stopped when POST `/api/v1/auth/logout` returned **401 instead of 200** for a newly rotated access credential. No corrective application/server-header change or smoke rerun was performed. No Step 51 work began.

The Codex browser certificate warning is informational under the revised authorization and is **not** the acceptance blocker. PHP/cURL successfully verified the normal HTTPS endpoint with the intended certificate and normal peer/hostname verification.

This report supersedes the earlier stopped-preflight report. Earlier isolated implementation results remain documented separately.

## 2. Date/time

16 October 2026, Asia/Kolkata (IST, UTC+05:30). Fresh backup file completed approximately 01:59:45 IST; deployed verification approximately 02:01:08 IST. Protected production-smoke result timestamp: 02:03:28 IST (`2026-10-15T20:33:28Z`). Subsequent cleanup and final integrity checks completed during this deployment run. These times come from the evidence file timestamps, not an invented successful activation timestamp.

## 3. Environment

Workspace `D:\xampp\htdocs\kobi`; normal database `medical1`; MariaDB 10.4.27; PHP 8.2.0; XAMPP Apache 2.4.54. Normal API target `https://localhost/kobi/public/api/v1`. Configured application URL/base path were preserved. Account 1 was explicitly designated by the user for smoke testing; no new account was created.

## 4. Pre-deployment state and scope

Both approved tables and the approved trigger were absent; dedicated `API_AUTH_RATE_KEY` was absent. Account 1 was active with verified email and password eligibility. The approved SQL artifact matches the additive block in `database/structure.sql`.

Artifact SHA-256: `52ba71caea0f08b64542987ca96218c5f3ac7e4330e4d480758c22e80ce2eb12`.

Normal source inventory: 60 tables, one view, 15 triggers, no routines/events. Reference-schema comparison found these pre-existing absent Treatment tables: `treatment_sources`, `treatment_protocol_sources`, `treatment_item_sources`, `treatment_protocol_events`, `treatment_editorial_permissions`; and absent triggers `trg_treatment_source_no_self_insert`, `trg_treatment_source_no_self_update`. There were no additional database-only table/trigger names. This existing gap was recorded and left untouched; no Treatment deployment/repair was attempted.

The newly written operational helper initially assumed every non-API reference-schema object was already deployed. Its inventory guard was corrected before backup/deployment to record the exact five-table/two-trigger gap and reject other differences. Existing regression tests/assertions were not edited or weakened. Acceptance still requires all pre-existing metadata and rows to match the fresh backup exactly; it does not assume reference-schema objects absent before this task were deployed by it.

Initial sandboxed SQL test attempts were denied at the socket layer before normal DB work. The same existing suites were then run outside the socket sandbox with their original disposable-database guards; no test behavior was changed.

## 5. Backup and recovery verification

**PASS.** Protected directory outside the web root/repository: `D:\xampp\kobi-backups\step50b-20261016`. Directory inheritance is disabled; access is restricted to Sanjog, SYSTEM and Administrators.

Authoritative backup: `before.bin`, 11,306,334 bytes; SHA-256 `58a14da528019b653b7ef002bf461d002ad4e62d43c2520a2ee9762ba6241523`. It contains sensitive existing data and is intentionally not copied into this report or repository. Backup readback and SHA-256 equality passed.

Recovery rehearsal reused the established `sscCapture`/`sscDigest` procedure in `tests/saved-symptom-checks-recovery.php`, through a new Step-50B-scoped CLI helper. Restored only to guarded disposable `kobi_api_auth_recovery_20261016`: all 60 tables, ordinary-column inserts with generated columns recomputed, one view, 15 triggers preserving client charset/collation and SQL-mode metadata. Full recovered DDL/columns/generated values/rows/views/trigger metadata/defaults/SQL modes matched the original capture. No SQL-mode change was used. The exact API DDL was then rehearsed on this copy without warnings, producing expected normalized table/trigger metadata. Normal KOBI's complete fingerprint still matched before deployment.

Recovery proof `recovery-verified.json`, deployment/final-state evidence, environment rollback backup and smoke metadata remain in the protected directory. Only the disposable recovery database was dropped after final checks; the backup remains retained.

Recovery procedure: validate the backup hash/proof, use the reviewed CLI restoration logic against a separately guarded empty recovery database, recreate tables, insert ordinary columns, recreate view/trigger definitions with captured metadata, and compare full digests before considering any real recovery. A normal-database restore was NOT performed or authorized as a test. DDL autocommits; future production rollback must be separately planned to disable issuance and remove the API revocation trigger before removing its referenced tables. No rollback/drop of deployed normal API objects was attempted after the smoke failure.

## 6. Two approved tables deployed

**PASS — definitions match the exact approved artifact and isolated rehearsal.**

- `user_api_sessions`
- `user_api_refresh_tokens`

Verified metadata includes two primary keys, five unique constraints, two foreign keys and nine CHECK constraints. Generated active-slot uniqueness and fixed digest/label/public-ID validation remain intact. No unrelated table/index/constraint was changed.

## 7. One approved trigger deployed

**PASS — `trg_users_api_revoke`**. Definition and captured metadata match the exact rehearsal, including SQL mode and client charset/collation. It revokes only API families on password/account eligibility changes. Existing triggers were preserved. Normal account/password invalidation was not exercised by editing Account 1; actual invalidation/rollback behavior passed in the existing isolated MariaDB suite.

## 8. Limiter provisioning

Dedicated random 32-byte key provisioned through the existing `.env` setting `API_AUTH_RATE_KEY`; no secret was output, passed as a command-line argument, placed in source, or included in this report. Existing storage path remains `storage/cache/api-auth/counters.json`; actual smoke requests created/used the existing limiter mechanism. The limiter was not disabled, bypassed, redesigned or weakened.

A protected `env-before.bin` rollback copy was retained outside the web root. The original `.env` bytes remain an exact prefix of the resulting file; only a line terminator plus the dedicated key line was appended. The provisioning helper's `environment_other_bytes_preserved=false` diagnostic reflects its line-removal comparison against an original file lacking a final newline; a separate byte-prefix/tail check confirmed the only addition is the dedicated key line and required delimiter. No unrelated environment credential/value changed.

Isolated limiter tests: PASS, 13 checks. Normal HTTPS issuance passes the configured limiter; production 429 enforcement was not reached before the logout failure and is NOT claimed verified.

## 9. SQL mode before/after

Both session and global/server mode, before backup and after deployment/smoke cleanup:

`NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`

**PASS — identical.** No SQL-mode statement or persistent/server SQL-mode change was performed.

## 10. Application/configuration changes and file inventory

**Application-code changes: ZERO.** Aggregate SHA-256 over `app`, `bootstrap`, `routes`, `database`, `public`, `resources` before/after: `DB64C5F21A4D47F85E8A0AD7DA3458A733971336708367A1F2C8DE7D92746B4D` (identical).

Project files modified:

- `.env`: dedicated limiter key only.
- `docs/task-50b-deployment-activation.md`: this report.

Project files created (CLI deployment/verification tooling, not application routes):

- `tests/api-auth-deployment.php`: guarded fresh backup/recovery, exact deployment, verification, limiter provisioning and disposable recovery cleanup.
- `tests/api-auth-production-smoke.php`: normal verified HTTPS smoke with in-memory credentials and safe evidence output.
- `tests/api-auth-production-smoke.ps1`: user-operated hidden password prompt, JSON passed only on stdin.

Runtime counter data was created under the existing ignored storage path. No schema source, controller/service/repository, route, view/JS, public `.htaccess` or existing regression test was modified. No application behavior was redesigned.

Local TLS infrastructure outside the project was restored to the previously validated intended configuration solely for authoritative PHP verification: `D:\xampp\apache\conf\extra\httpd-ssl.conf` uses `localhost:443` and the existing `kobi-local-tls/localhost.pem`/`.key`; `D:\xampp\php\php.ini` uses the existing combined `php-ca-bundle.pem` for `curl.cainfo`/`openssl.cafile`. Verified original configuration backups remain in `kobi-local-tls/rollback-20261016`. Only exact XAMPP Apache was restarted (parent PID 15096 observed). No CA regeneration/install/removal, registry change or browser trust/settings change occurred in this revised deployment.

## 11. Authoritative PHP/cURL TLS verification

**PASS.** Verified normal HTTPS response 200, cURL errno 0, certificate verification result 0, correct live leaf SHA-256 `83f096f60d695d66513b57da98b4ba6cc41763b65b045afcd01f3db76cc18087`, hostname localhost and configured PHP CA path. OpenSSL chain/server-purpose/localhost verification passed before cutover; certificate/key pairing was previously verified for the unchanged staged material. Apache syntax remains OK.

Requests explicitly use `CURLOPT_SSL_VERIFYPEER=true`, `CURLOPT_SSL_VERIFYHOST=2`, redirects disabled, and the configured CA trust path (no per-request CA override). No insecure fallback, cURL -k, verification suppression or HTTP credential transmission occurred. Earlier negative hostname/old-bundle controls are historical TLS evidence, not newly executed production acceptance checks.

The valid-token logout request itself passed TLS and expected-peer-certificate assertions before receiving application HTTP 401. Thus the blocker is application/Authorization handling, not certificate trust.

## 12. Browser verification and limitation

Fresh informational HTTPS navigation returned `net::ERR_CERT_AUTHORITY_INVALID`.

“Codex/browser automation trust limitation persists; PHP/cURL is the authoritative HTTPS verifier.”

No warning was bypassed. The prior trust investigation remains stopped; no certificate/registry/browser-setting changes were made to satisfy this check. This warning did not prevent schema deployment or cause the current BLOCKED decision.

## 13. Normal authenticated API smoke

User explicitly selected Account 1 and entered its password through the local hidden PowerShell prompt. Credentials were piped on stdin and used only in-memory against certificate-verified localhost HTTPS. No new normal user, password change, role change or account-state edit occurred.

The protected result records **111 successful assertions before failure**. Observed normal results:

| Check | Result |
|---|---|
| Valid login / exact opaque credential response / ten-minute lifetime | PASS |
| Wrong password safe rejection | PASS |
| Refresh rotation, followed by successful use of the successor refresh | PASS |
| Old access rejection after rotation | Observed 401; this alone does not prove correct Bearer forwarding |
| Consumed refresh replay | PASS; database family reason `refresh_reuse` confirmed |
| Revoked family access rejection | Observed 401; same forwarding limitation applies |
| Malformed/unknown access rejection | Observed 401 |
| Newly rotated valid access used for single logout | **FAIL: expected 200, observed 401** |
| Single logout/device isolation | NOT completed |
| Real access expiry after ten minutes | NOT reached; wait phase did not start |
| Logout-all | NOT reached |
| Normal 429 enforcement | NOT reached |

Exact failure: `HTTP status for auth/logout expected 200 observed 401`. Protected evidence `production-smoke.json`, status FAILED. No automatic rerun or corrective change was made.

Possible cause: Bearer Authorization forwarding into PHP. The current `public/.htaccess` has only front-controller rewrite/protection rules, no explicit Authorization forwarding; the controller reads `HTTP_AUTHORIZATION`/`REDIRECT_HTTP_AUTHORIZATION`. No supported header-presence diagnostic established which values PHP received, so this is a hypothesis, NOT a confirmed root cause. Do not change authentication logic or add a diagnostic endpoint merely to claim success.

Cleanup: four new API families and seven digest-only refresh-history rows were created by the smoke. One family was already revoked by reuse detection. The three remaining active test families (guarded by IDs, account, labels, pre-smoke empty-table proof and exact total count) were revoked using the existing repository's locked transaction/revocation methods. **Zero active API families remain.** No rows were deleted and no user/health data changed. This administrative test cleanup is NOT a passing HTTP logout/logout-all test. Revoked hashes/history are retained according to the approved retention policy.

## 14. Regression results

| Executed suite/check | Current result |
|---|---|
| `tests/api-auth.php` | 103 checks PASS |
| `tests/api-auth-rate-limit.php` | 13 checks PASS; disposable files removed |
| `tests/api-auth-mariadb.php` | 65 checks PASS; disposable DB removed |
| `tests/api-auth-http.php` | 326 isolated HTTPS checks PASS; fixture DB/server/files removed |
| `tests/auth-phase1.php` | 47 database-free scenarios PASS |
| `tests/public-api-v1.php` | 56 checks PASS |
| `tests/public-api-v1-bootstrap.php` | 8 checks PASS |
| `tests/public-api-v1-mariadb.php` | 38 checks PASS; disposable DB removed |
| `tests/public-api-v1-http.php` | 561 read-only assertions PASS |
| `tests/public-search.php` | 30 checks PASS |
| `tests/disease-references.php` | 71 checks PASS |
| `tests/disease-detail-presentation.php` | 7 scenarios PASS |
| `tests/treatment-publication.php` | 105 tests PASS |
| `node tests/substance-search.cjs` | 11 DOM-contract checks PASS |
| Normal PHP/cURL HTTPS public API/private-boundary smoke | 15 checks PASS |
| PHP syntax | 15 affected implementation/config/route/cleanup files plus two new PHP helpers PASS |
| New PowerShell helper parser | PASS |
| Full normal backup recovery / exact DDL rehearsal | PASS |
| Post-deployment full unrelated metadata/row integrity | PASS |
| Normal authenticated API smoke | **FAILED at valid-token logout; incomplete acceptance** |

Existing isolated suites exercise actual expiry boundaries, concurrent refresh/reuse, revocation, password/account invalidation and rollback. They do not replace the missing normal-environment acceptance results. Existing auth scenarios cover password reset/change, session-related behavior, CSRF and Google link/unlink/boundary cases. No real Google callback, reset email, normal password/account-state change, or fresh genuine browser login/logout was performed during this deployment. Browser trust remains informational; no such unexecuted flow is represented as newly passed.

## 15. Security verification

No JWT, CORS, external auth provider, native Google API, private-health endpoint or `/api/v1/me` was introduced. Ten-minute access policy, refresh rotation, reuse detection and immediate revocation logic are unchanged. Stored API digest lengths are all 32 bytes; raw access/refresh credentials were never written by the smoke tooling. Appended-log exact-secret scanning in the smoke was not reached because it stopped earlier. A subsequent read-only scan of available application/Apache/PHP logs found **zero raw API-token-format matches**; no matching log contents were printed. This is not a claim of exhaustive password-log forensics.

No password, raw token, API key, limiter secret or private key was printed or included in reports. The normal .env is covered by the existing ignore rule; the dedicated key is not hard-coded into source. Backup/environment rollback material remains protected outside the web root/repository. No key/certificate material was copied into KOBI. The current task performed no Git index operation or credential-related Git inspection.

Substantive unresolved security/functional requirement: HTTP Bearer-authenticated logout has not succeeded, so API activation cannot be declared PASS despite working login/refresh and passing isolated tests.

## 16. Data-integrity verification

**PASS.** The final full capture, excluding only the two approved API tables and approved new trigger, exactly matches the fresh pre-deployment backup fingerprint: existing DDL, columns, generated values, rows, views, trigger metadata, database defaults and SQL modes are unchanged. This includes all existing user/profile/mobile/authentication rows, health/medication/schedule/saved-check data, disease, symptom, Medicine and Treatment data. No counters were intentionally incremented by symptom matching.

Only new API infrastructure/history rows, its limiter runtime counters/key and approved schema objects changed. Final API counts: four revoked families, seven refresh-history rows, zero active families. Known API/public fixture and recovery database names were checked absent after cleanup. Backup retained.

## 17. Existing-feature preservation

Effective configuration: Treatment publication `hold`; Medicine pilot and internal pilot both false. No governed Treatment/rich Medicine exposure was added. Existing public disease/detail/reference/substance/search regressions passed. HTTPS boundary checks confirmed public APIs/legacy substance lookup still respond and private health/profile/document/medication/saved-check/summary, `/api/v1/me`, Treatment and rich Substance-ID routes remain unavailable. No Dashboard, schedules, mobile-number, health-profile or saved-check application code changed; full unchanged-data verification complements the focused regressions.

## 18. Limitations and smallest safe next action

The normal valid-token logout failure is a new substantive deployment finding. Stop before changing code or tests. Next action is a narrowly scoped, separately authorized diagnostic of Bearer Authorization forwarding through normal Apache/PHP, followed by the smallest confirmed correction and renewed smoke acceptance. No public credential/header dump, token logging, TLS bypass, new auth architecture or unrelated schema fix is justified.

The schema and key remain deployed; activation is **not accepted**. No remaining API test credentials are active. Real expiry, valid access/logout, logout-all, device isolation and normal rate-limit enforcement remain to be completed after the blocker is resolved. The existing failed smoke evidence must not be silently overwritten or the helper rerun against nonempty API history; any resumed run needs an explicit evidence/checkpoint plan. No Step 51 work is authorized or performed.

## 19. Final decision and Git

**BLOCKED — Step 50B Deployment & Production Activation is NOT complete.**

Backup/recovery, exact additive schema deployment, limiter provisioning, authoritative PHP/cURL TLS, isolated/public regressions and unrelated-data/schema preservation passed. Production Bearer-authenticated logout failed (HTTP 401 instead of 200), so acceptance criteria are incomplete.

No Git command or commit/push/pull/reset/checkout/switch/merge/rebase/amend/index/history mutation occurred. No unrelated file/data repair was attempted. Stop after reporting this blocker.

## Authorization Header Forwarding Diagnosis

Status: **DIAGNOSIS ONLY — BLOCKED pending permission for runtime presence-only observation.** No corrective change, authentication-family creation, test rerun or schema/configuration mutation has occurred in this diagnosis.

### Read-only findings

Historical reproduction remains POST `https://localhost/kobi/public/api/v1/auth/logout`, Content-Type application/json, body `{}`, using the newly refreshed `c2.access_token` directly, without redirect following. The deployment harness constructs one `Authorization: Bearer <REDACTED>` header through CURLOPT_HTTPHEADER; peer and hostname verification are enabled. Successful refresh instead passes `refresh_token` in a JSON body and requires no Bearer header. Both use the same bootstrap/router/controller class and POST method, but different controller methods/authentication extraction. Historical token-state metadata shows generation 2 and 600-second access lifetime for the failing family before administrative cleanup; its raw token was never retained. Its current revoked state is intentional cleanup and cannot be used to prove its earlier request state.

`Request::authJson()` handles only body/media type/field syntax; Request.php has no Authorization extractor. Router dispatches to `ApiAuthController::logout()` without a separate auth middleware. The controller validates `{}` first, then reads HTTP_AUTHORIZATION / REDIRECT_HTTP_AUTHORIZATION; conflicting values cause denial. Otherwise `ApiAuthGuard::token()` requires an exact valid Bearer access format; `resolve()` invokes authenticate before service logout. The guard/service may throw the same denied exception, which the controller converts to JSON 401. Status alone cannot establish the actual throwing branch. No database-backed logout success is inferred from earlier expected-401 requests.

No existing supported diagnostic exposes live Apache/PHP header presence for this endpoint. Reading source, tests or configuration cannot prove the incoming runtime variables. No phpinfo/server-variable dump, debug endpoint, raw request trace/log, debugger attachment or certificate bypass was attempted.

### Concrete temporary diagnostic proposal — NOT APPLIED

The task forbids modifying application code; permission is therefore needed before even temporary front-controller instrumentation. Proposed insertion in `public/index.php`, after strict_types and before the two unchanged require statements:

```php
// Temporary presence-only observation, removed before the diagnosis ends.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && ($_SERVER['REQUEST_URI'] ?? '') === '/kobi/public/api/v1/auth/logout'
    && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
    && ($_SERVER['HTTP_X_KOBI_HEADER_DIAG'] ?? '') === 'step50b-presence-only') {
    $diagnosticHeaders = function_exists('getallheaders') ? getallheaders() : [];
    $diagnosticPresent = false;
    foreach ($diagnosticHeaders as $diagnosticName => $diagnosticValue) {
        if (strcasecmp($diagnosticName, 'Authorization') === 0) { $diagnosticPresent = true; }
    }
    header('X-Kobi-Diag-Server-Authorization: '.(isset($_SERVER['HTTP_AUTHORIZATION']) ? 'PRESENT' : 'ABSENT'));
    header('X-Kobi-Diag-Redirect-Authorization: '.(isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? 'PRESENT' : 'ABSENT'));
    header('X-Kobi-Diag-Apache-Headers-Authorization: '.($diagnosticPresent ? 'PRESENT' : 'ABSENT'));
    unset($diagnosticHeaders, $diagnosticPresent, $diagnosticName, $diagnosticValue);
}
```

This would emit three presence/absence response headers only for the marked loopback POST, then run the ordinary application unchanged. It would not set/replace any auth variable, skip the guard, alter JSON/status, log credential values, change Apache/.htaccess, or create a new route. No raw header or token would be output or saved. No ordinary request would receive diagnostic headers. The original file bytes/hash would be recorded before insertion and restored/verified before completion; no persistent instrumentation would remain.

A narrowly scoped CLI client would then require genuine test-account authentication, keep credentials only in memory, perform one login and one refresh, compare the new access digest/account/family/generation/expiry/revocation/stamp with read-only DB queries, and make the single marked logout request with CURLINFO_HEADER_OUT inspected only in memory to report method/header/scheme/body/HTTPS booleans. Cleanup can use normal refresh replay: reuse the consumed pre-rotation refresh token to trigger the existing committed family revocation, avoiding manual token-row edits if Bearer logout fails. Existing deployment smoke evidence would not be overwritten and full acceptance would not be rerun.

No fresh family is necessary until this observation is authorized; all existing test families remain revoked. Without runtime observation, header-forwarding root cause and the actual throwing layer remain unconfirmed. H1–H10 remain UNKNOWN for the actual failing request except source-inspection facts: refresh and logout have distinct extraction paths; the harness passes the newly returned token; POST/media type/JSON shape are correctly constructed. These facts alone do not prove whether runtime header forwarding, parsing or validation caused denial.

Only this report was edited during diagnosis so far. Application/authentication/routes/Request/Router/controller/services/repositories, deployed tables/trigger, TLS/limiter/Apache/.htaccess configuration and Git remain untouched. No corrective action was applied; no Step 51 work started.
SERVER['REDIRECT_HTTP_AUTHORIZATION']` | ABSENT |

Thus Apache receives the header and PHP can access it through the request-header API. It is not mapped into either of the two server variables the controller actually reads. Calling this “Authorization absent everywhere in PHP” would be incorrect. PHP documents [getallheaders() as an alias of apache_request_headers()](https://www.php.net/manual/en/function.getallheaders.php).

The task initially prohibited application changes. The user then explicitly authorized the documented temporary presence-only probe and one diagnosis family. Only the approved loopback/exact-route/marked-request guard was temporarily inserted in public/index.php before bootstrap, emitting three PRESENT/ABSENT response headers and no values. It did not set auth variables, alter route/controller/service logic or bypass authentication. No persistent credential logging was added. The probe was removed immediately after capture; the original front-controller bytes/hash and PHP syntax were verified. No probe marker remains.

### 6–7. Request/router/authentication extraction

Exact source path:

1. Router dispatches POST `/api/v1/auth/logout` to `ApiAuthController::logout()`.
2. `input([],[])` accepts the correct HTTPS request and `{}` JSON body. Those checks would produce 400/415/422-style errors if invalid, not the observed UNAUTHENTICATED.
3. `ApiAuthController::bearer(true)`, lines 35–38, reads only HTTP_AUTHORIZATION and REDIRECT_HTTP_AUTHORIZATION. Both were absent in the observed request, so the selected header is null; the conflict branch is not taken.
4. `ApiAuthGuard::token(null)`, lines 10–12, takes its missing-header rejection branch, consumes the existing failure-limit operation and throws `ApiAuthException::denied()`.
5. The controller's respond() handler catches that exception and sends JSON 401 plus WWW-Authenticate: Bearer.

The controller does not call getallheaders(). Request.php and Router.php never supply the missing variable. `guard->resolve()`, `ApiAuthService::authenticate()`, the HTTP-path digest lookup and the logout service/repository operation are not reached for this request.

### 8. Token validation result

Read-only checks immediately before logout all passed:

- New post-rotation access token differs from the original.
- Access-token format valid.
- Its purpose-separated digest finds the exact expected family.
- Family belongs to test Account 1 and is generation 2.
- Family unrevoked, access unexpired, absolute session deadline unexpired.
- Stored credential stamp matches the current account password-hash stamp.

The actual outgoing header matches this checked token. No token record was manually changed. Digest lookup PASS here means the independent read-only diagnostic query; the HTTP handler never reaches that lookup because the guard has already rejected a missing header.

### 9. Exact 401 source

**Authentication header-extraction guard, called by the logout controller:** `ApiAuthGuard::token()` rejects null input; `ApiAuthController::respond()` serializes the denial. The 401 is not generated by Apache, TLS, generic bootstrap, Router, token expiry, refresh state or the logout repository write.

This conclusion follows from the observed missing variables and deterministic source branch, supported by the exact UNAUTHENTICATED/Bearer response. No debugger/trace containing arguments was required.

### 10. Hypothesis matrix

| Hypothesis | Status | Finding |
|---|---|---|
| H1 — Authorization absent at PHP | REFUTED as a whole-PHP claim | Present through getallheaders(); absence in both expected # Step 50B — Deployment & Production Activation

## 1. Deployment status

**BLOCKED — production acceptance failed at valid-access-token logout.** The approved schema and dedicated limiter key are deployed; this is a partial deployment, not completed activation. The normal HTTPS smoke stopped when POST `/api/v1/auth/logout` returned **401 instead of 200** for a newly rotated access credential. No corrective application/server-header change or smoke rerun was performed. No Step 51 work began.

The Codex browser certificate warning is informational under the revised authorization and is **not** the acceptance blocker. PHP/cURL successfully verified the normal HTTPS endpoint with the intended certificate and normal peer/hostname verification.

This report supersedes the earlier stopped-preflight report. Earlier isolated implementation results remain documented separately.

## 2. Date/time

16 October 2026, Asia/Kolkata (IST, UTC+05:30). Fresh backup file completed approximately 01:59:45 IST; deployed verification approximately 02:01:08 IST. Protected production-smoke result timestamp: 02:03:28 IST (`2026-10-15T20:33:28Z`). Subsequent cleanup and final integrity checks completed during this deployment run. These times come from the evidence file timestamps, not an invented successful activation timestamp.

## 3. Environment

Workspace `D:\xampp\htdocs\kobi`; normal database `medical1`; MariaDB 10.4.27; PHP 8.2.0; XAMPP Apache 2.4.54. Normal API target `https://localhost/kobi/public/api/v1`. Configured application URL/base path were preserved. Account 1 was explicitly designated by the user for smoke testing; no new account was created.

## 4. Pre-deployment state and scope

Both approved tables and the approved trigger were absent; dedicated `API_AUTH_RATE_KEY` was absent. Account 1 was active with verified email and password eligibility. The approved SQL artifact matches the additive block in `database/structure.sql`.

Artifact SHA-256: `52ba71caea0f08b64542987ca96218c5f3ac7e4330e4d480758c22e80ce2eb12`.

Normal source inventory: 60 tables, one view, 15 triggers, no routines/events. Reference-schema comparison found these pre-existing absent Treatment tables: `treatment_sources`, `treatment_protocol_sources`, `treatment_item_sources`, `treatment_protocol_events`, `treatment_editorial_permissions`; and absent triggers `trg_treatment_source_no_self_insert`, `trg_treatment_source_no_self_update`. There were no additional database-only table/trigger names. This existing gap was recorded and left untouched; no Treatment deployment/repair was attempted.

The newly written operational helper initially assumed every non-API reference-schema object was already deployed. Its inventory guard was corrected before backup/deployment to record the exact five-table/two-trigger gap and reject other differences. Existing regression tests/assertions were not edited or weakened. Acceptance still requires all pre-existing metadata and rows to match the fresh backup exactly; it does not assume reference-schema objects absent before this task were deployed by it.

Initial sandboxed SQL test attempts were denied at the socket layer before normal DB work. The same existing suites were then run outside the socket sandbox with their original disposable-database guards; no test behavior was changed.

## 5. Backup and recovery verification

**PASS.** Protected directory outside the web root/repository: `D:\xampp\kobi-backups\step50b-20261016`. Directory inheritance is disabled; access is restricted to Sanjog, SYSTEM and Administrators.

Authoritative backup: `before.bin`, 11,306,334 bytes; SHA-256 `58a14da528019b653b7ef002bf461d002ad4e62d43c2520a2ee9762ba6241523`. It contains sensitive existing data and is intentionally not copied into this report or repository. Backup readback and SHA-256 equality passed.

Recovery rehearsal reused the established `sscCapture`/`sscDigest` procedure in `tests/saved-symptom-checks-recovery.php`, through a new Step-50B-scoped CLI helper. Restored only to guarded disposable `kobi_api_auth_recovery_20261016`: all 60 tables, ordinary-column inserts with generated columns recomputed, one view, 15 triggers preserving client charset/collation and SQL-mode metadata. Full recovered DDL/columns/generated values/rows/views/trigger metadata/defaults/SQL modes matched the original capture. No SQL-mode change was used. The exact API DDL was then rehearsed on this copy without warnings, producing expected normalized table/trigger metadata. Normal KOBI's complete fingerprint still matched before deployment.

Recovery proof `recovery-verified.json`, deployment/final-state evidence, environment rollback backup and smoke metadata remain in the protected directory. Only the disposable recovery database was dropped after final checks; the backup remains retained.

Recovery procedure: validate the backup hash/proof, use the reviewed CLI restoration logic against a separately guarded empty recovery database, recreate tables, insert ordinary columns, recreate view/trigger definitions with captured metadata, and compare full digests before considering any real recovery. A normal-database restore was NOT performed or authorized as a test. DDL autocommits; future production rollback must be separately planned to disable issuance and remove the API revocation trigger before removing its referenced tables. No rollback/drop of deployed normal API objects was attempted after the smoke failure.

## 6. Two approved tables deployed

**PASS — definitions match the exact approved artifact and isolated rehearsal.**

- `user_api_sessions`
- `user_api_refresh_tokens`

Verified metadata includes two primary keys, five unique constraints, two foreign keys and nine CHECK constraints. Generated active-slot uniqueness and fixed digest/label/public-ID validation remain intact. No unrelated table/index/constraint was changed.

## 7. One approved trigger deployed

**PASS — `trg_users_api_revoke`**. Definition and captured metadata match the exact rehearsal, including SQL mode and client charset/collation. It revokes only API families on password/account eligibility changes. Existing triggers were preserved. Normal account/password invalidation was not exercised by editing Account 1; actual invalidation/rollback behavior passed in the existing isolated MariaDB suite.

## 8. Limiter provisioning

Dedicated random 32-byte key provisioned through the existing `.env` setting `API_AUTH_RATE_KEY`; no secret was output, passed as a command-line argument, placed in source, or included in this report. Existing storage path remains `storage/cache/api-auth/counters.json`; actual smoke requests created/used the existing limiter mechanism. The limiter was not disabled, bypassed, redesigned or weakened.

A protected `env-before.bin` rollback copy was retained outside the web root. The original `.env` bytes remain an exact prefix of the resulting file; only a line terminator plus the dedicated key line was appended. The provisioning helper's `environment_other_bytes_preserved=false` diagnostic reflects its line-removal comparison against an original file lacking a final newline; a separate byte-prefix/tail check confirmed the only addition is the dedicated key line and required delimiter. No unrelated environment credential/value changed.

Isolated limiter tests: PASS, 13 checks. Normal HTTPS issuance passes the configured limiter; production 429 enforcement was not reached before the logout failure and is NOT claimed verified.

## 9. SQL mode before/after

Both session and global/server mode, before backup and after deployment/smoke cleanup:

`NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`

**PASS — identical.** No SQL-mode statement or persistent/server SQL-mode change was performed.

## 10. Application/configuration changes and file inventory

**Application-code changes: ZERO.** Aggregate SHA-256 over `app`, `bootstrap`, `routes`, `database`, `public`, `resources` before/after: `DB64C5F21A4D47F85E8A0AD7DA3458A733971336708367A1F2C8DE7D92746B4D` (identical).

Project files modified:

- `.env`: dedicated limiter key only.
- `docs/task-50b-deployment-activation.md`: this report.

Project files created (CLI deployment/verification tooling, not application routes):

- `tests/api-auth-deployment.php`: guarded fresh backup/recovery, exact deployment, verification, limiter provisioning and disposable recovery cleanup.
- `tests/api-auth-production-smoke.php`: normal verified HTTPS smoke with in-memory credentials and safe evidence output.
- `tests/api-auth-production-smoke.ps1`: user-operated hidden password prompt, JSON passed only on stdin.

Runtime counter data was created under the existing ignored storage path. No schema source, controller/service/repository, route, view/JS, public `.htaccess` or existing regression test was modified. No application behavior was redesigned.

Local TLS infrastructure outside the project was restored to the previously validated intended configuration solely for authoritative PHP verification: `D:\xampp\apache\conf\extra\httpd-ssl.conf` uses `localhost:443` and the existing `kobi-local-tls/localhost.pem`/`.key`; `D:\xampp\php\php.ini` uses the existing combined `php-ca-bundle.pem` for `curl.cainfo`/`openssl.cafile`. Verified original configuration backups remain in `kobi-local-tls/rollback-20261016`. Only exact XAMPP Apache was restarted (parent PID 15096 observed). No CA regeneration/install/removal, registry change or browser trust/settings change occurred in this revised deployment.

## 11. Authoritative PHP/cURL TLS verification

**PASS.** Verified normal HTTPS response 200, cURL errno 0, certificate verification result 0, correct live leaf SHA-256 `83f096f60d695d66513b57da98b4ba6cc41763b65b045afcd01f3db76cc18087`, hostname localhost and configured PHP CA path. OpenSSL chain/server-purpose/localhost verification passed before cutover; certificate/key pairing was previously verified for the unchanged staged material. Apache syntax remains OK.

Requests explicitly use `CURLOPT_SSL_VERIFYPEER=true`, `CURLOPT_SSL_VERIFYHOST=2`, redirects disabled, and the configured CA trust path (no per-request CA override). No insecure fallback, cURL -k, verification suppression or HTTP credential transmission occurred. Earlier negative hostname/old-bundle controls are historical TLS evidence, not newly executed production acceptance checks.

The valid-token logout request itself passed TLS and expected-peer-certificate assertions before receiving application HTTP 401. Thus the blocker is application/Authorization handling, not certificate trust.

## 12. Browser verification and limitation

Fresh informational HTTPS navigation returned `net::ERR_CERT_AUTHORITY_INVALID`.

“Codex/browser automation trust limitation persists; PHP/cURL is the authoritative HTTPS verifier.”

No warning was bypassed. The prior trust investigation remains stopped; no certificate/registry/browser-setting changes were made to satisfy this check. This warning did not prevent schema deployment or cause the current BLOCKED decision.

## 13. Normal authenticated API smoke

User explicitly selected Account 1 and entered its password through the local hidden PowerShell prompt. Credentials were piped on stdin and used only in-memory against certificate-verified localhost HTTPS. No new normal user, password change, role change or account-state edit occurred.

The protected result records **111 successful assertions before failure**. Observed normal results:

| Check | Result |
|---|---|
| Valid login / exact opaque credential response / ten-minute lifetime | PASS |
| Wrong password safe rejection | PASS |
| Refresh rotation, followed by successful use of the successor refresh | PASS |
| Old access rejection after rotation | Observed 401; this alone does not prove correct Bearer forwarding |
| Consumed refresh replay | PASS; database family reason `refresh_reuse` confirmed |
| Revoked family access rejection | Observed 401; same forwarding limitation applies |
| Malformed/unknown access rejection | Observed 401 |
| Newly rotated valid access used for single logout | **FAIL: expected 200, observed 401** |
| Single logout/device isolation | NOT completed |
| Real access expiry after ten minutes | NOT reached; wait phase did not start |
| Logout-all | NOT reached |
| Normal 429 enforcement | NOT reached |

Exact failure: `HTTP status for auth/logout expected 200 observed 401`. Protected evidence `production-smoke.json`, status FAILED. No automatic rerun or corrective change was made.

Possible cause: Bearer Authorization forwarding into PHP. The current `public/.htaccess` has only front-controller rewrite/protection rules, no explicit Authorization forwarding; the controller reads `HTTP_AUTHORIZATION`/`REDIRECT_HTTP_AUTHORIZATION`. No supported header-presence diagnostic established which values PHP received, so this is a hypothesis, NOT a confirmed root cause. Do not change authentication logic or add a diagnostic endpoint merely to claim success.

Cleanup: four new API families and seven digest-only refresh-history rows were created by the smoke. One family was already revoked by reuse detection. The three remaining active test families (guarded by IDs, account, labels, pre-smoke empty-table proof and exact total count) were revoked using the existing repository's locked transaction/revocation methods. **Zero active API families remain.** No rows were deleted and no user/health data changed. This administrative test cleanup is NOT a passing HTTP logout/logout-all test. Revoked hashes/history are retained according to the approved retention policy.

## 14. Regression results

| Executed suite/check | Current result |
|---|---|
| `tests/api-auth.php` | 103 checks PASS |
| `tests/api-auth-rate-limit.php` | 13 checks PASS; disposable files removed |
| `tests/api-auth-mariadb.php` | 65 checks PASS; disposable DB removed |
| `tests/api-auth-http.php` | 326 isolated HTTPS checks PASS; fixture DB/server/files removed |
| `tests/auth-phase1.php` | 47 database-free scenarios PASS |
| `tests/public-api-v1.php` | 56 checks PASS |
| `tests/public-api-v1-bootstrap.php` | 8 checks PASS |
| `tests/public-api-v1-mariadb.php` | 38 checks PASS; disposable DB removed |
| `tests/public-api-v1-http.php` | 561 read-only assertions PASS |
| `tests/public-search.php` | 30 checks PASS |
| `tests/disease-references.php` | 71 checks PASS |
| `tests/disease-detail-presentation.php` | 7 scenarios PASS |
| `tests/treatment-publication.php` | 105 tests PASS |
| `node tests/substance-search.cjs` | 11 DOM-contract checks PASS |
| Normal PHP/cURL HTTPS public API/private-boundary smoke | 15 checks PASS |
| PHP syntax | 15 affected implementation/config/route/cleanup files plus two new PHP helpers PASS |
| New PowerShell helper parser | PASS |
| Full normal backup recovery / exact DDL rehearsal | PASS |
| Post-deployment full unrelated metadata/row integrity | PASS |
| Normal authenticated API smoke | **FAILED at valid-token logout; incomplete acceptance** |

Existing isolated suites exercise actual expiry boundaries, concurrent refresh/reuse, revocation, password/account invalidation and rollback. They do not replace the missing normal-environment acceptance results. Existing auth scenarios cover password reset/change, session-related behavior, CSRF and Google link/unlink/boundary cases. No real Google callback, reset email, normal password/account-state change, or fresh genuine browser login/logout was performed during this deployment. Browser trust remains informational; no such unexecuted flow is represented as newly passed.

## 15. Security verification

No JWT, CORS, external auth provider, native Google API, private-health endpoint or `/api/v1/me` was introduced. Ten-minute access policy, refresh rotation, reuse detection and immediate revocation logic are unchanged. Stored API digest lengths are all 32 bytes; raw access/refresh credentials were never written by the smoke tooling. Appended-log exact-secret scanning in the smoke was not reached because it stopped earlier. A subsequent read-only scan of available application/Apache/PHP logs found **zero raw API-token-format matches**; no matching log contents were printed. This is not a claim of exhaustive password-log forensics.

No password, raw token, API key, limiter secret or private key was printed or included in reports. The normal .env is covered by the existing ignore rule; the dedicated key is not hard-coded into source. Backup/environment rollback material remains protected outside the web root/repository. No key/certificate material was copied into KOBI. The current task performed no Git index operation or credential-related Git inspection.

Substantive unresolved security/functional requirement: HTTP Bearer-authenticated logout has not succeeded, so API activation cannot be declared PASS despite working login/refresh and passing isolated tests.

## 16. Data-integrity verification

**PASS.** The final full capture, excluding only the two approved API tables and approved new trigger, exactly matches the fresh pre-deployment backup fingerprint: existing DDL, columns, generated values, rows, views, trigger metadata, database defaults and SQL modes are unchanged. This includes all existing user/profile/mobile/authentication rows, health/medication/schedule/saved-check data, disease, symptom, Medicine and Treatment data. No counters were intentionally incremented by symptom matching.

Only new API infrastructure/history rows, its limiter runtime counters/key and approved schema objects changed. Final API counts: four revoked families, seven refresh-history rows, zero active families. Known API/public fixture and recovery database names were checked absent after cleanup. Backup retained.

## 17. Existing-feature preservation

Effective configuration: Treatment publication `hold`; Medicine pilot and internal pilot both false. No governed Treatment/rich Medicine exposure was added. Existing public disease/detail/reference/substance/search regressions passed. HTTPS boundary checks confirmed public APIs/legacy substance lookup still respond and private health/profile/document/medication/saved-check/summary, `/api/v1/me`, Treatment and rich Substance-ID routes remain unavailable. No Dashboard, schedules, mobile-number, health-profile or saved-check application code changed; full unchanged-data verification complements the focused regressions.

## 18. Limitations and smallest safe next action

The normal valid-token logout failure is a new substantive deployment finding. Stop before changing code or tests. Next action is a narrowly scoped, separately authorized diagnostic of Bearer Authorization forwarding through normal Apache/PHP, followed by the smallest confirmed correction and renewed smoke acceptance. No public credential/header dump, token logging, TLS bypass, new auth architecture or unrelated schema fix is justified.

The schema and key remain deployed; activation is **not accepted**. No remaining API test credentials are active. Real expiry, valid access/logout, logout-all, device isolation and normal rate-limit enforcement remain to be completed after the blocker is resolved. The existing failed smoke evidence must not be silently overwritten or the helper rerun against nonempty API history; any resumed run needs an explicit evidence/checkpoint plan. No Step 51 work is authorized or performed.

## 19. Final decision and Git

**BLOCKED — Step 50B Deployment & Production Activation is NOT complete.**

Backup/recovery, exact additive schema deployment, limiter provisioning, authoritative PHP/cURL TLS, isolated/public regressions and unrelated-data/schema preservation passed. Production Bearer-authenticated logout failed (HTTP 401 instead of 200), so acceptance criteria are incomplete.

No Git command or commit/push/pull/reset/checkout/switch/merge/rebase/amend/index/history mutation occurred. No unrelated file/data repair was attempted. Stop after reporting this blocker.

## Authorization Header Forwarding Diagnosis

Status: **DIAGNOSIS ONLY — BLOCKED pending permission for runtime presence-only observation.** No corrective change, authentication-family creation, test rerun or schema/configuration mutation has occurred in this diagnosis.

### Read-only findings

Historical reproduction remains POST `https://localhost/kobi/public/api/v1/auth/logout`, Content-Type application/json, body `{}`, using the newly refreshed `c2.access_token` directly, without redirect following. The deployment harness constructs one `Authorization: Bearer <REDACTED>` header through CURLOPT_HTTPHEADER; peer and hostname verification are enabled. Successful refresh instead passes `refresh_token` in a JSON body and requires no Bearer header. Both use the same bootstrap/router/controller class and POST method, but different controller methods/authentication extraction. Historical token-state metadata shows generation 2 and 600-second access lifetime for the failing family before administrative cleanup; its raw token was never retained. Its current revoked state is intentional cleanup and cannot be used to prove its earlier request state.

`Request::authJson()` handles only body/media type/field syntax; Request.php has no Authorization extractor. Router dispatches to `ApiAuthController::logout()` without a separate auth middleware. The controller validates `{}` first, then reads HTTP_AUTHORIZATION / REDIRECT_HTTP_AUTHORIZATION; conflicting values cause denial. Otherwise `ApiAuthGuard::token()` requires an exact valid Bearer access format; `resolve()` invokes authenticate before service logout. The guard/service may throw the same denied exception, which the controller converts to JSON 401. Status alone cannot establish the actual throwing branch. No database-backed logout success is inferred from earlier expected-401 requests.

No existing supported diagnostic exposes live Apache/PHP header presence for this endpoint. Reading source, tests or configuration cannot prove the incoming runtime variables. No phpinfo/server-variable dump, debug endpoint, raw request trace/log, debugger attachment or certificate bypass was attempted.

### Concrete temporary diagnostic proposal — NOT APPLIED

The task forbids modifying application code; permission is therefore needed before even temporary front-controller instrumentation. Proposed insertion in `public/index.php`, after strict_types and before the two unchanged require statements:

```php
// Temporary presence-only observation, removed before the diagnosis ends.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && ($_SERVER['REQUEST_URI'] ?? '') === '/kobi/public/api/v1/auth/logout'
    && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
    && ($_SERVER['HTTP_X_KOBI_HEADER_DIAG'] ?? '') === 'step50b-presence-only') {
    $diagnosticHeaders = function_exists('getallheaders') ? getallheaders() : [];
    $diagnosticPresent = false;
    foreach ($diagnosticHeaders as $diagnosticName => $diagnosticValue) {
        if (strcasecmp($diagnosticName, 'Authorization') === 0) { $diagnosticPresent = true; }
    }
    header('X-Kobi-Diag-Server-Authorization: '.(isset($_SERVER['HTTP_AUTHORIZATION']) ? 'PRESENT' : 'ABSENT'));
    header('X-Kobi-Diag-Redirect-Authorization: '.(isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? 'PRESENT' : 'ABSENT'));
    header('X-Kobi-Diag-Apache-Headers-Authorization: '.($diagnosticPresent ? 'PRESENT' : 'ABSENT'));
    unset($diagnosticHeaders, $diagnosticPresent, $diagnosticName, $diagnosticValue);
}
```

This would emit three presence/absence response headers only for the marked loopback POST, then run the ordinary application unchanged. It would not set/replace any auth variable, skip the guard, alter JSON/status, log credential values, change Apache/.htaccess, or create a new route. No raw header or token would be output or saved. No ordinary request would receive diagnostic headers. The original file bytes/hash would be recorded before insertion and restored/verified before completion; no persistent instrumentation would remain.

A narrowly scoped CLI client would then require genuine test-account authentication, keep credentials only in memory, perform one login and one refresh, compare the new access digest/account/family/generation/expiry/revocation/stamp with read-only DB queries, and make the single marked logout request with CURLINFO_HEADER_OUT inspected only in memory to report method/header/scheme/body/HTTPS booleans. Cleanup can use normal refresh replay: reuse the consumed pre-rotation refresh token to trigger the existing committed family revocation, avoiding manual token-row edits if Bearer logout fails. Existing deployment smoke evidence would not be overwritten and full acceptance would not be rerun.

No fresh family is necessary until this observation is authorized; all existing test families remain revoked. Without runtime observation, header-forwarding root cause and the actual throwing layer remain unconfirmed. H1–H10 remain UNKNOWN for the actual failing request except source-inspection facts: refresh and logout have distinct extraction paths; the harness passes the newly returned token; POST/media type/JSON shape are correctly constructed. These facts alone do not prove whether runtime header forwarding, parsing or validation caused denial.

Only this report was edited during diagnosis so far. Application/authentication/routes/Request/Router/controller/services/repositories, deployed tables/trigger, TLS/limiter/Apache/.htaccess configuration and Git remain untouched. No corrective action was applied; no Step 51 work started.
SERVER keys is CONFIRMED. |
| H2 — Header present but not extracted | CONFIRMED | Controller reads only the absent server keys. |
| H3 — Bearer syntax/token-format failure | REFUTED for the sent token | Correct outgoing Bearer and valid format; guard rejects null before parsing it. |
| H4 — Wrong token used after rotation | REFUTED | Exact new token selected and sent. |
| H5 — Digest lookup failure | REFUTED | Independent lookup matches family; HTTP lookup is not reached. |
| H6 — Token expired/revoked | REFUTED | Fresh state checks pass before the request. |
| H7 — Logout uses a different credential path | CONFIRMED | Refresh body credential versus logout Bearer extraction; same bootstrap/router. This is intended design, not an authorization bypass. |
| H8 — Logout controller/guard produces 401 | CONFIRMED | Guard missing-header denial, serialized by controller; logout write is not reached. |
| H9 — POST request handling is the cause | REFUTED for this reproduction | Both requests are POST/JSON over verified HTTPS; correct logout shape reaches the header guard. |
| H10 — Another cause explains this failure | REFUTED for the captured failure | Missing server-variable mapping fully explains this 401. Other untested acceptance behavior is not certified by this diagnosis. |

### 11–12. Confirmed root cause and remaining uncertainty

**Confirmed root cause:** the Authorization header survives client → Apache → PHP request-header access but is unavailable in HTTP_AUTHORIZATION and REDIRECT_HTTP_AUTHORIZATION, the only sources used by the controller. Missing mapping causes a fail-closed guard rejection before token validation/logout.

The precise Apache/PHP internal reason for omitting these variables was not separately traced. No claim is made that a particular core directive/default or module bug was proven. That narrower implementation detail does not change the demonstrated integration mismatch. The corrective configuration has not been applied or tested; full production acceptance remains pending.

### 13–14. Smallest proposed correction and why it preserves security — NOT APPLIED

Recommend a narrowly scoped header-to-environment forwarding rule in `public/.htaccess`, before the existing front-controller rewrite, preserving the existing controller, strict parser, ambiguity check and service validation:

```apache
RewriteCond %{HTTP:Authorization} .
RewriteRule ^api/v1/auth/ - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

This is a proposed correction, not an applied or verified fix. It copies the existing HTTP Authorization header only for the API authentication path; it does not accept query/cookie credentials, skip TLS, relax Bearer format, modify token validity/revocation, introduce an auth fallback, expose a debug endpoint or change database schema. Internal-redirect behavior must be verified in the resumed narrow test, using the controller's existing HTTP_AUTHORIZATION/REDIRECT_HTTP_AUTHORIZATION handling and conflict rejection. [Apache documents the E flag for setting request environment variables](https://httpd.apache.org/docs/2.4/mod/mod_rewrite.html#rewriteflags).

A broader CGIPassAuth change is not being applied or assumed correct. [Apache documents CGIPassAuth](https://httpd.apache.org/docs/2.4/mod/core.html#cgipassauth), but the observed environment and desired API-only scope justify evaluating the narrowly scoped rule first under separate implementation authorization. No Apache/.htaccess fix was made during this task.

### 15. Cleanup, integrity and no corrective change

The diagnosis family was revoked via normal refresh replay of its consumed pre-rotation refresh credential, resulting in 401 and committed reason `refresh_reuse`. This is expected lifecycle cleanup, not a passing logout test. No manual INSERT/UPDATE/DELETE or authentication-record repair was used. Final active API family count is zero.

One permitted family and two digest-only refresh-history rows were added; they remain as revoked lifecycle history. Existing four families and seven refresh-history rows are unchanged. All unrelated captured schema/data/defaults/trigger metadata/SQL modes match the diagnosis baseline. The deployed two tables and trigger definitions were not changed or rolled back. Session/global SQL mode remains `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`.

Original public/index.php bytes were restored and verified; its temporary source marker is absent. Aggregate application hash over app/bootstrap/routes/database/public/resources again equals `DB64C5F21A4D47F85E8A0AD7DA3458A733971336708367A1F2C8DE7D92746B4D`. Restored PHP syntax passes. One final marked request using a noncredential diagnostic string returned expected 401 with TLS verification 0 and no X-Kobi-Diag response headers, confirming runtime probe removal; it created no API family or DB write. The existing database/session/log-free authentication unit suite passed 103 checks; full production acceptance was not rerun.

Temporary CLI diagnostic PHP/PowerShell files were created to obtain the genuine user-entered credential on stdin and collect only safe metadata; they were removed after completion. No persistent application/diagnostic endpoint or logging remains. The safe boolean/status result and original-index/hash restoration evidence are retained in the existing protected directory `D:\xampp\kobi-backups\step50b-20261016`, outside the web root. No password/raw token/header/limiter key/private key was output or saved by the diagnostics.

Net project modification for this diagnosis: this report only. No corrective application, auth, route, Request/Router, controller/service/repository, Apache/.htaccess, TLS/CA, limiter or schema change. Only the explicitly allowed normal lifecycle of the one diagnosis family changed API data. No Git operations. Step 51 not started. Stop; the proposed forwarding correction is NOT APPLIED.

## Final Acceptance

### Authorization Forwarding Correction and Final Acceptance

The protected `D:\xampp\kobi-backups\step50b-20261016\final-acceptance.json` was independently read after resumption. It contains the completed PASS result with 458 assertions and successful final lifecycle cleanup, rather than an intermediate wait checkpoint. The user-returned result matches it. The full ten-minute acceptance test was **not rerun** during finalization.

The diagnosed integration mismatch was Apache/PHP header forwarding: Authorization was available through `getallheaders()`, but absent from both server variables read by the unchanged controller. The approved correction was inserted before the front-controller rewrite in `public/.htaccess`:

```apache
    # Forward the existing Bearer header only for API authentication routes.
    RewriteCond %{HTTP:Authorization} .
    RewriteRule ^api/v1/auth/ - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

Scope is only `api/v1/auth/`. No alternative credential source, parser relaxation or authentication PHP change was introduced. Existing rules retain their order and exact bytes after removal of this inserted block. Original SHA-256: `47F346DCB0E7C236D50D88AA5C971209D18E16E1D34AE47863AE67E200161552`; corrected SHA-256: `CF1DAF3F6E7C1B9A8F847E42BF92B905A8AD9CEEB5849BB3D94E1156B8EBD981`. Apache syntax validation passed in the correction run; per-request .htaccess handling required no restart. The previously failing newly rotated-token logout then returned **HTTP 200**. Because the unchanged controller still reads only `HTTP_AUTHORIZATION` / `REDIRECT_HTTP_AUTHORIZATION`, that success proves the existing extraction path works; it does not separately identify which variable was populated. No diagnostic probe was added for this correction.

### Completed authentication and TLS results

| Acceptance requirement | Completed result |
|---|---|
| Genuine Account 1 login; valid access and rotated-token logout | PASS; logout HTTP 200 |
| Missing, malformed, unknown and expired access | Rejected as expected |
| Real ten-minute access expiry | PASS; real elapsed time, no clock/data manipulation |
| Refresh rotation and old-access rejection | PASS |
| Consumed-refresh reuse detection | PASS; committed family revocation |
| Single logout and independent-device isolation | PASS |
| Logout-all, including wrong-password denial | PASS |
| Normal rate limit | HTTP 429 and Retry-After verified |
| Configured limiter key | Actual HMAC counter verified in memory |
| Persistence | Digest-only; no raw credentials found in API rows |
| Logging | Exact password/token scan of appended logs passed; no raw credentials output |
| Normal lifecycle cleanup | PASS; zero active API families |

**PHP/cURL HTTPS verification passed** during completed acceptance, with peer verification enabled, hostname verification 2, verification result **0**, no redirects or insecure fallback, and intended leaf SHA-256 `83f096f60d695d66513b57da98b4ba6cc41763b65b045afcd01f3db76cc18087`. Correction-run negative controls rejected a wrong hostname and an untrusted old CA bundle. TLS configuration has not changed in finalization.

**Codex/browser trust limitation remains informational.** The correction-run browser check returned `net::ERR_CERT_AUTHORITY_INVALID`; browser trust did not pass and no warning was bypassed. This differs from the new connection-availability limitation described below.

### Deployment, recovery and current integrity

The resumed `tests/api-auth-deployment.php verify` completed PASS. Both approved tables (`user_api_sessions`, `user_api_refresh_tokens`) and `trg_users_api_revoke` match the recovered/rehearsed definitions, including constraints and trigger metadata. Unrelated schema, rows, database defaults and SQL modes exactly match the pre-deployment backup. Existing undeployed Treatment objects remain absent as previously documented; no unexpected object was added.

The recoverable backup remains outside the web root at `D:\xampp\kobi-backups\step50b-20261016\before.bin`. Current hash matches the verified recovery evidence: `58a14da528019b653b7ef002bf461d002ad4e62d43c2520a2ee9762ba6241523`. Full disposable recovery and exact DDL rehearsal had passed before deployment; no recovery/deployment was repeated now. No API fixture/recovery databases remain (read-only schema inventory).

Current counts: **10 API families, 20 refresh-history rows, zero active families**. Completed acceptance added five families and eleven history rows; its evidence verifies the previous five families and nine history rows remained unchanged. New test credentials were revoked through normal API lifecycle operations; revoked digest history is intentionally retained. No manual row cleanup is necessary or performed.

Session and global SQL mode both remain `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`. Dedicated limiter configuration remains present without printing its secret. Effective Treatment publication remains `hold`; Medicine pilot and internal pilot remain false. The .env, PHP configuration and Apache SSL configuration hashes match the correction-run checkpoint.

Aggregate application hashing over app/bootstrap/routes/database/public/resources, substituting only the original .htaccess hash, matches the original baseline `DB64C5F21A4D47F85E8A0AD7DA3458A733971336708367A1F2C8DE7D92746B4D`. Thus authentication PHP, routes, services, public projections, schema source and unrelated application code remain unchanged by the correction/finalization.

### Regression evidence and artifact handling

The correction run completed: API auth unit **103**, limiter **13**, API auth MariaDB **65**, isolated HTTPS **326**, existing auth **47**, public API unit **56**, bootstrap **8**, MariaDB **38**, read-only HTTP **561**, public search **30**, disease references **71**, disease presentation **7**, Treatment publication **105**, substance-search DOM checks **11**, and public/private HTTPS boundaries **15**. All passed. Modified/affected PHP syntax and PowerShell parser checks passed. No test was altered to obtain a pass. These are completed correction-run results, not suites rerun during finalization. Real Google callbacks/reset email/browser login flows were not newly exercised; existing auth scenarios cover their relevant boundaries.

`tests/api-auth-final-acceptance.php` and `.ps1` were inspected and **retained unchanged** under the finalization instruction. They are one-off deployment artifacts, not permanent repeatable regressions: they bind to the protected evidence location and exact prior-history checkpoint and refuse to overwrite the existing result. They should be removed only under explicit cleanup authorization; do not execute them again. They contain no actual password, raw access/refresh token, limiter secret or private key. Password input uses a hidden local prompt and stdin; credentials remain in memory. The report and saved result contain safe booleans/counts/statuses and certificate/file hashes, not credentials. The PHP artifact syntax check passed again during finalization. No temporary application probe remains.

### Current availability blocker and decision

A new credential-free request to `https://localhost/kobi/public/api/v1/diseases?limit=1`, with peer/hostname verification enabled, returned **cURL error 7 / HTTP 0**. No certificate was received, so its reported verification field cannot establish a fresh TLS pass. Read-only process/listener checks reported no `httpd` process and no listener on port 80 or 443. This is current endpoint unavailability; it does not invalidate the saved completed 458-check acceptance evidence. No Apache restart, TLS remediation, database write, additional forwarding mechanism or corrective application change was attempted.

**BLOCKED for current finalization:** the completed acceptance and deployment integrity are verified, but current HTTPS availability/verification cannot be confirmed. Restore the intended local Apache service through the normal operator procedure before requesting a minimal availability recheck; the ten-minute acceptance does not need repeating merely due to the usage interruption. No completion declaration is made while this new availability failure remains.

Finalization changed only this report; the approved .htaccess correction and deployed infrastructure were preserved. The two acceptance artifacts remain unchanged. No database DDL/data write, SQL-mode change, medical-content change, governance change, authentication-code change, Git operation or Step 51 work occurred during finalization. **Step 51 has not been started.**

## Availability Resolution

After the user confirmed starting Apache through the normal XAMPP operator procedure, only the requested minimal read-only recheck was performed. Two `httpd` processes were present. A credential-free GET to `https://localhost/kobi/public/api/v1/diseases?page_size=1` returned **HTTP 200**, JSON `success: true`, and one item; cURL error was 0. Peer verification remained enabled (`CURLOPT_SSL_VERIFYPEER=true`), hostname verification remained 2, and **TLS verification result was 0**. The leaf fingerprint matched `83f096f60d695d66513b57da98b4ba6cc41763b65b045afcd01f3db76cc18087`. The initial request used unsupported `limit=1` and received the expected validation response HTTP 422; using the documented `page_size` parameter confirmed normal HTTP 200 behavior. No application defect or correction was involved.

The existing read-only deployment verifier again returned PASS: `user_api_sessions` and `user_api_refresh_tokens` and the approved `trg_users_api_revoke` metadata match the authorized deployment. All unrelated schema/data/defaults and SQL modes remain identical to the verified pre-deployment backup. Counts remain 10 API families and 20 refresh-history rows, with **zero active API families**. Limiter configuration remains present; its secret was not output. Session/global SQL mode remains `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`.

The protected acceptance result remains **PASS, 458 checks**, with its original completion timestamp unchanged. No login, refresh, logout, family creation/deletion, ten-minute expiry test or acceptance-suite rerun occurred. No Apache/PHP/TLS/certificate, authentication, .htaccess, limiter, database or KOBI-data changes were made. Only this report and the verifier's protected safe summary output were updated; no Git operations occurred. Browser trust was not retested or changed; its previously documented informational limitation remains.

**Step 50B acceptance: previously PASS and verified. Current endpoint availability: PASS.** The temporary Apache-unavailable blocker is resolved by normal operator startup. **Step 50B Deployment & Production Activation is complete. Step 51 has not been started.**
