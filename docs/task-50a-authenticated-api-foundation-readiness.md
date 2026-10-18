# Step 50A — Authenticated API Foundation readiness

Assessment date: 2026-10-15. Scope: architecture and readiness only. This document distinguishes inspected current behavior from proposed Step 50B behavior. No API authentication has been implemented or deployed.

## 1. Existing authentication architecture

Inspected `AuthController`, `AuthService`, `UserAuthTokenService`, `UserAuthIdentityService`, `GoogleAuthController`, `GoogleAuthService`, `GoogleOAuthStateService`, their repositories, `Session`, `Request`, `Router`, `Container`, bootstrap, routes, and authoritative schema. Also read the Step 49A readiness report, Step 49B implementation report, and `docs/public-api-v1.md`.

Browser requests use controller → service → repository. Password login verifies credentials, checks active/verified account state, regenerates the PHP session, sets `auth_user_id`, and updates last-login metadata. Google login resolves a provider identity and ultimately establishes the same browser session. Neither operation is a session-free API login function.

The four public V1 GET routes remain unauthenticated: diseases list, disease search, disease detail, and substance search. V1 bootstrap deliberately skips PHP session initialization; responses use explicit projections, consistent JSON, no-store, and safe errors. No API access-token validator, refresh-token store, authenticated request context, or general API authentication limiter exists. The router supports GET/POST registration, but its current V1 method-error fallback and response `Allow` header assume GET. Future POST authentication routes need a narrowly scoped extension of that behavior.

## 2. Existing authentication inventory

| Mechanism | Purpose, implementation and storage | Lifetime, revocation and rotation | Boundary, recovery and mobile suitability |
|---|---|---|---|
| Local password | `AuthService`, `UserRepository`; `password_hash(PASSWORD_DEFAULT)` / `password_verify`; hash in `users.password_hash`, local-password marker in `password_set_at` | No credential expiry found; explicit password change/reset replaces hash. Password storage is adaptive, not a reversible secret store. No API credential rotation exists | Browser login creates a session. Existing change/reset forms use CSRF. Reset email is recovery. Reuse credential verification policy, not the session-establishing login method |
| Google sign-in/link | Google client exchanges a code and verifies an ID token; stable provider subject mapped through `user_auth_identities` | Provider token lifetime is external; OAuth state is single-use. No explicit application state TTL/PKCE in the inspected state service. Safe unlink preserves another login method | Login callback depends on browser session state; linking requires genuine authentication and CSRF. No email-only automatic merge. This browser flow is not a native token exchange |
| Email-verification token | `UserAuthTokenService` / repository; 32 random bytes encoded as hex; only SHA-256 digest stored in `user_auth_tokens` | 24 hours; one-time conditional consumption; issuance invalidates older unused tokens of the same purpose; 60-second resend cooldown | Verification purpose only, not an API credential. Browser verification flow establishes verified email, not persistent mobile authorization. Resend uses existing form/session/CSRF behavior |
| Password-reset token | Same token service, distinct allowed purpose and email link | 1 hour; conditional one-time consumption; new issuance and successful password persistence invalidate unused reset tokens | Browser reset form uses CSRF. Validation precedes consumption; controller consumes before calling reset service, rather than wrapping the whole controller sequence in one transaction. Never reuse for API access or refresh |
| PHP session cookie | Session ID cookie; server-side PHP session contains `auth_user_id` and CSRF token | Cookie lifetime 0; no explicit application idle/absolute session deadline found. Host PHP configuration/GC applies; do not infer a fixed server lifetime. Regenerated on login/password change; logout destroys current active session | Strict session mode, cookie-only, HttpOnly, SameSite=Lax; Secure depends on HTTPS. Browser boundary. Logout/reset does not enumerate and revoke every other browser session. Not the proposed native credential |
| CSRF token | Session-backed 32 random bytes encoded as hex; `hash_equals` check | Session lifetime; not a user login credential; session destruction removes server-side value | Protects ambient-cookie form actions. Preserve existing `csrfField()` and checks. Not an API bearer token |
| Google OAuth state | Random session-backed state plus login/link intent; constant-time comparison and clear on validation | Single-use, including failed validation; no explicit creation-time expiry in this class | Browser callback correlation. Not a refresh token and not a device credential |
| Remember-me / other user API credentials | No remember-me or user API bearer/refresh mechanism found in inspected auth code/schema/routes | Not applicable | Do not invent one from recovery tokens, mobile numbers, or governance authority records |

No secrets, hashes, addresses, raw identities, session IDs, or token values are reproduced here.

## 3. User identity model

`users.id` is a signed `int(11)` primary key. `AuthService::id()` returns the session `auth_user_id` cast to integer or null; `check()` tests presence of that key. Neither alone performs a fresh account-state lookup. `user()` performs a repository lookup and logs out if missing/inactive. Login requires active status and verified email. Deleting an account removes its identity through absence; future API foreign keys must also cascade credential deletion.

Future API resolution must be: access-token digest → active API device session → existing, active, email-verified KOBI user → immutable request principal containing that user ID. Never accept a caller-supplied user ID, populate PHP session identity, or pretend `AuthService::id()` resolved a bearer token. Existing web callers continue using `AuthService::id()` unchanged. Future resource services must explicitly receive the API-resolved ID and preserve established ownership checks.

Read-only normal-database census: 12 accounts, all active; 4 email-verified and 8 unverified; all 12 have a non-null local-password marker; 3 Google identity rows. Recovery inventory contains 7 email-verification and 4 password-reset records; these totals include used/expired rows and are not valid-token counts. No API session/refresh tables were found. These counts describe current data, not entitlement to issue credentials in Step 50A.

## 4. Native mobile authentication requirements

Provide genuine login, bounded persistence across app restarts, short access expiry, refresh, explicit single-device/all-device logout, and independent device sessions. Reject deleted, inactive, unverified, expired, revoked, or credential-invalidated identities. Disable/re-enable and password changes must not resurrect old credentials. A second authenticated device can revoke all API sessions if another device is lost.

Keep credentials out of URLs, logs, analytics, crash reports, clipboard, backups, and ordinary preferences. Refresh replay must be detected after rotation; stolen bearer access remains replayable until expiry/revocation. Failed refresh or unavailable secure storage must lead to reauthentication, not session-cookie fallback. This task does not design private health resources or a mobile application.

## 5. Authentication options

| Option | Security, revocation and recovery | Complexity, storage, operations and KOBI fit |
|---|---|---|
| A. Browser cookies | Existing regeneration/CSRF protections; ambient authority, server session persistence and logout behavior would become mobile contracts | Few new tables, but couples native login to PHP session/CSRF/browser redirects. Poor session separation; not selected |
| B. Long-lived bearer | Easy persistent login; theft grants a long replay window. Database lookup can revoke, but a single stolen credential remains sufficient | One store and no refresh protocol; simple dependencies/operations, inadequate theft containment for health accounts |
| C. Short access + refresh | Separates frequent API use from long-lived persistence; requires rotation, atomic reuse handling, revocation and re-login policy | A model family rather than a token format; additional transactional state justified. Selected with opaque tokens |
| D. JWT access + refresh | Signature validation alone cannot immediately revoke accounts/devices; adding state checks sacrifices much stateless benefit. Claim/audience/algorithm/key handling adds failure cases | Refresh database still needed; maintained JWT dependency and signing-key lifecycle needed. No current distributed verifier requirement; not selected |
| E. Opaque access + refresh | Indexed digest lookup gives immediate state-aware denial; no user/health claims in token. Database availability required, failures deny access | Two small tables, existing PDO transactions and PHP cryptography, no new library. Best current architecture fit; selected |
| F. OAuth authorization server / OAuth 2.1-style code + PKCE | Appropriate for delegated clients, federation and mature authorization workflows; authorization server handles protocol controls | External service or substantial maintained server dependency, client/redirect/key operations and migration burden. Optional later, not a foundation prerequisite |
| G. Access token only with frequent password entry | Short expiry limits theft but repeatedly exposes credentials to client handling and gives poor restart/offline experience | Less token state, worse user recovery. No existing mechanism justifies preferring it |

Opaque/JWT choices are separate from OAuth protocol compliance. The proposed first-party password endpoint is not an OAuth password grant or an OAuth 2.1 implementation. OAuth security guidance prohibits the OAuth resource-owner-password grant; future delegated/native provider integration should use authorization code with PKCE. [RFC 9700](https://www.rfc-editor.org/rfc/rfc9700.html)

## 6. Recommended model

Use first-party email/password authentication with short-lived opaque access tokens and rotating opaque refresh tokens, grouped into independent API device-session families. Keep existing browser authentication unchanged. Access validation remains online and stateful; do not add JWT, a second user identity, or a new framework.

The following lifetimes, caps and limits are proposed KOBI V1 policy decisions, not descriptions of deployed behavior or values mandated by a standard. They form a concrete Step 50B implementation boundary.

## 7. Access-token design

Generate 32 cryptographically random bytes per credential, base64url encode without padding, and distinguish access/refresh with fixed purpose prefixes. Validate exact syntax/length before lookup. Store only a domain-separated SHA-256 digest, with purpose included in the hash input. Never store plaintext access credentials. PHP `random_bytes` supplies suitable cryptographic randomness. [PHP documentation](https://www.php.net/manual/en/function.random-bytes.php)

Access TTL: 10 minutes, additionally bounded by family absolute expiry. A session has one current access digest; refresh atomically replaces it, immediately invalidating its predecessor. Accept access credentials only in one `Authorization: Bearer` header. Reject duplicate/ambiguous headers, query credentials and refresh tokens in this header. No credentials in cookies or URLs. Every use checks current account/family/credential state without a positive authorization cache. TLS is mandatory for real credentials because possession of a bearer credential permits use. [RFC 6750](https://www.rfc-editor.org/rfc/rfc6750.html)

## 8. Refresh-token design

Use separately generated 32-byte opaque credentials, hashed with refresh purpose separation, sent only in JSON POST bodies. A family expires absolutely 30 days after login; refresh has 14-day inactivity expiry capped by that deadline. Rotation does not extend the absolute deadline. Client responses include explicit expiry timestamps and access lifetime.

In one transaction: resolve candidate owner, lock user → family → refresh row, reread and validate current state, consume the current refresh token conditionally, insert its successor, and replace current access digest/expiry. Emit credentials only after successful commit. Retain consumed hashes until the family can no longer authorize anything. A known consumed token reused before family expiry revokes that whole family and its current access; commit the revocation before returning an authentication failure. Unknown random hashes cannot revoke a family.

Require single-flight refresh per device. Two concurrent uses of the same refresh token intentionally result in one initial rotation followed by family revocation; neither resulting credential remains usable after that revocation. A lost successful response requires re-login. No grace window, cached plaintext successor, or automatic retry of an already committed refresh is proposed. This availability cost is explicit. Rotation cannot distinguish a thief from the legitimate holder. [RFC 9700, refresh-token protection](https://www.rfc-editor.org/rfc/rfc9700.html#section-4.14)

## 9. Multiple devices

One family per successful device login; labels are optional untrusted display text, never proof of device identity. No hardware identifiers or IP binding. Proposed cap: five active families per account. Serialize login issuance under the user lock; prune expired families from the active count and revoke the oldest active family by creation time/ID if issuance would exceed five. Document this automatic sign-out behavior.

Creation and last-refresh times are enough for this milestone. Defer a separate per-request last-used write/device activity display; it is not needed to validate access or revoke a device. The public session identifier is noncredential metadata, never sufficient for authentication.

Logout affects only its family; logout-all affects every API family. Native clients have no embedded shared secret. Device listing, remote single-device selection, push logout, and a web management UI are later features; logout-all supplies the initial lost-device escape path.

## 10. Logout and revocation

`POST /api/v1/auth/logout` requires a valid access credential and revokes only that family, including refresh capability. Clear client secrets even if a retry receives 401. It does not destroy a PHP session. With expired access, a valid refresh can obtain access before logout; if both credentials are unusable, clear local storage and reauthenticate as needed. Do not promise successful server revocation from a credential the server cannot authenticate.

`POST /api/v1/auth/logout-all` requires valid access plus current password, then revokes all account API families, including the caller. Return success only after commit. Serialize these operations with refresh/login using the common user-first lock order. A request authenticated before concurrent logout can finish; subsequent authentication after committed revocation must fail. These endpoints do not revoke browser sessions.

## 11. Password change/reset interactions

Current `persistPassword` updates the hash/local-password timestamp and invalidates unused reset tokens within a transaction. Browser change regenerates its current session; reset removes authentication from the current session and does not auto-login. Global browser-session revocation is not currently implemented and is outside this scope.

For Step 50B, all API families must be durably revoked on a successful password-hash change. Proposed minimal database backstop: one API-only AFTER UPDATE trigger on `users`, revoking API families when the password hash changes, status leaves active, or verified email becomes unverified. It updates only the new API session table. It leaves browser logic/user fields untouched and shares the initiating transaction. This is additional proposed schema, not an existing trigger. Trigger failure must abort the originating account update, not silently preserve active API credentials.

Store a family credential stamp derived from the current password hash and compare it on admission as a backstop. `password_set_at` alone is insufficient because timestamp precision can miss same-second changes. Never store a plaintext password or return its hash. Test rollback, reset and concurrent refresh carefully. Status reactivation/email re-verification must not restore revoked families. Deleted users cascade API credentials. No API password-change/reset endpoint is needed in 50B; existing browser recovery remains available.

## 12. Existing Google login interaction

Existing Google login verifies provider ID-token claims, requires verified provider email, and resolves stable provider subject. Existing email collisions are rejected instead of merging; linking requires an already authenticated account. Preserve these rules.

Do not call the browser callback or `loginById()` from a mobile password endpoint. Accounts with a real local password may use password API login regardless of a Google link. Google-only accounts must establish a local password through existing genuine recovery/account flows before using this first milestone; the API must not treat the random placeholder hash as a local password. No identity linking/unlinking change is proposed.

## 13. Mobile-number interaction

`users.mobile_number` is optional, canonical-format checked, nonunique and unverified. Step 45C storage is not verified identity. The paused mobile-verification/SMS work is not a prerequisite for email/password API authentication. Do not authenticate, deduplicate, merge, recover, or authorize by mobile number. No mobile-number lookup, SMS, OTP, sharing or verification endpoint in 50B.

## 14. Separation from browser sessions

Keep V1 bootstrap session-free, including future auth POST routes and their failures. Never invoke `Session::start`, `AuthService::login`, `loginById`, `logout`, session regeneration, or a controller redirect from API credential operations. Do not accept PHP cookies as API authentication, set a session cookie, or convert bearer credentials to a browser login.

Extract a side-effect-free password-verification method in the existing `AuthService` during 50B, retaining browser validation/messages and existing login side effects at their current call site. The API orchestrator calls that verification capability, then issues API credentials through repositories. The API layer additionally requires `UserRepository::hasPassword()`; current `findByEmail()` does not include `password_set_at`. Recheck credential/account state under the issuance lock. This avoids duplicating password rules or bypassing service separation.

## 15. CSRF boundary

Existing cookie-authenticated forms keep their CSRF checks unchanged. Future native auth endpoints require strict JSON bodies and explicit bearer headers where applicable; they never consume ambient cookies, HTML forms or session identity. Therefore they need no session CSRF token. Reject form-encoded and text/plain token requests; do not make a browser-cookie shortcut. Login/refresh secrets are explicit request credentials, not browser ambient authority. CSRF independence must not be confused with freedom from abuse controls.

## 16. CORS boundary

Keep the existing no-CORS-grant policy. Native apps are not enabled by browser CORS headers. No wildcard origins, credentialed browser sharing, origin reflection or permissive OPTIONS handler. If a browser client becomes a requirement later, specify its exact origin and credential model separately. CORS is not server authentication; non-browser clients can always send requests. Preserve public API behavior.

## 17. Server and mobile credential storage

Server: only indexed digests and bounded session metadata in the new tables; restrict access/backups and exclude token request/response bodies and Authorization from logs. Access-token state may be retained in app memory on the client; persisted refresh credentials require OS-protected storage, no plaintext preferences/database or cloud backup.

Android: use an established Keystore-backed secure-storage facility; Keystore protects keys rather than acting as a generic plaintext token database. iOS: use Keychain with an appropriate device-only/unlocked accessibility policy. Verify actual chosen client APIs later; no mobile dependency selected now. [Android Keystore](https://developer.android.com/privacy-and-security/keystore), [Apple Keychain protection](https://support.apple.com/guide/security/keychain-data-protection-secb0694df1a/web).

## 18. Hashing and secret protection

Keep adaptive password hashing and `password_verify` for human passwords. Random 256-bit token secrets can use SHA-256 digests for efficient indexed lookup; password hashing each bearer lookup is unnecessary. Separate hash purposes so an access credential cannot become a refresh credential. Use `hash_equals` for in-process secret/stamp comparisons; database indexed digest matching does not justify claiming every request is constant-time.

Encryption/recoverable token storage is unnecessary. A separate token pepper/keyed hash is optional defense against database-only theft, not required for high-entropy tokens. Do not introduce token encryption/key rotation machinery just to return the same secret twice. Rate-limit account/IP pseudonyms need a separate deployment HMAC key; never reuse a password, Google client secret, CSRF token or database password. No key is created in 50A.

A public token selector plus secret digest could also provide indexed lookup, but adds a parsing/identifier contract without a current benefit: indexing the complete high-entropy token digest already gives direct lookup. Select the latter. Rotation replaces digests; revocation denies the family without requiring the raw credential.

## 19. Conceptual database design

Two additive InnoDB tables are proposed; names are implementation targets, not statements executed:

| Table | Minimal fields | Integrity and indexes |
|---|---|---|
| `user_api_sessions` | bigint unsigned ID; random noncredential public session ID; signed int `user_id`; nullable bounded device label; created/last-refreshed timestamps; absolute expiry; nullable revoked-at/reason; credential stamp; current access digest/expiry; current refresh generation | User FK → `users.id` ON DELETE CASCADE; unique public ID and access digest; indexes for `(user_id, revoked_at, absolute_expires_at)` and cleanup expiry; positive generation and expiry ordering checks |
| `user_api_refresh_tokens` | bigint unsigned ID; family FK; generation; unique digest; issued/expires timestamps; nullable consumed-at; generated active slot (1 for unconsumed, NULL otherwise) | Family FK ON DELETE CASCADE; unique digest; unique `(api_session_id, generation)`; unique `(api_session_id, active_slot)` permits one unconsumed successor; index expiry; checks for valid times/consumption ordering |

Use fixed binary digest storage, server-generated values and explicit length checks before persistence. Do not rely on permissive MariaDB coercion or a binary column's padded length check to validate malformed input. Use DATETIME with a consistently documented UTC persistence convention for these new API records; do not change global/application timezone or SQL mode. The existing application timezone remains unchanged.

Refresh consumes the old row before inserting its successor so the active-slot uniqueness constraint remains satisfied. Service checks ensure the unconsumed generation equals the family's current generation; database uniqueness alone does not express every cross-row lifecycle rule. Revocation lives on the family and does not depend on deleting every token row immediately. Retain consumed hashes for replay detection while the family can still be active.

Add the single API revocation trigger described in section 11 after creating the tables. No purpose extension of `user_auth_tokens`, no identity-table changes, no user column, no health-data table, and no credential in mobile-number storage. Exact DDL, trigger metadata and MariaDB 10.4.27 generated-column behavior require isolated validation in 50B before any separately authorized normal deployment.

## 20. Database security and transaction rules

Use `BaseRepository` prepared-query conventions and the shared `Database` PDO connection. Controllers/services must not start a second SQL access pattern. All lifecycle operations use user → session → token lock order; candidate digest lookup before these locks is only identification, never authorization. Recheck every condition after acquiring locks. Password/account updates naturally lock the user before the trigger revokes families.

Use conditional affected-row checks, rollback all partial issuance/rotation, and return no credential on commit failure. Replay revocation is a deliberate committed state transition followed by an error response, not an exception accidentally rolling it back. Revalidate after any deadlock retry. No positive result survives a transaction that did not commit. Restrict database permissions and protect backups; never print rows or enable SQL/parameter dumping for credential operations.

## 21. API authentication architecture

Recommended flow: router → `ApiAuthController` → `ApiAuthService` → existing `AuthService` credential verification and new `ApiAuthRepository` → database. Reuse KOBI container autowiring. Keep request parsing in `Request`/controller and lifecycle decisions in the service. A small `ApiAuthGuard` resolves an explicit `ApiAuthPrincipal` for routes requiring access; the current router has no general middleware stack, so an explicit guard call at controller entry is sufficient. Do not introduce a framework or implicit global authentication for public routes.

Reuse `PublicApiResponse` envelope, no-store, output suppression and safe exception boundary despite its public-oriented name; extend its method-header handling narrowly for auth POST routes. A typed `ApiAuthException` carries allowlisted safe status/code/message without internal exception details. Any unexpected failure returns generic JSON. No private resource controllers are introduced.

Controller-local password/token verification would duplicate policy. Router middleware would require introducing a middleware mechanism the project does not currently have. A request helper alone cannot enforce database/account state. Prefer an explicit controller guard backed by the dedicated service; reuse `AuthService` only for pure password policy. This is the smallest useful middleware equivalent in the existing architecture.

## 22. Authenticated user resolution

The guard accepts exactly one bearer access credential; validates syntax, digest, access expiry, family absolute expiry/revocation, account existence/status/email verification and credential stamp; then constructs a request-local principal. No global mutable user cache, PHP session, client `user_id`, or caller-supplied role. The principal contains integer internal user ID and family ID only; serialize IDs as strings if exposed. Do not expose repository rows, password markers/hashes, tokens or governance grants through it. Missing/unusable credentials produce 401 JSON, never login HTML.

## 23. Authorization boundary

Authentication proves which KOBI account holds the credential. It does not prove access to a profile, medication, another account, or any Medicine/Treatment role. Do not infer roles/scopes from labels, email, token possession or account ID 1. No governance-role grants are added. Future profile access must retain `HealthProfileRepository::findAccessible($profileId, $userId)` and explicit resource authorization. No resource access is implemented in this milestone.

## 24. Private health-data safety

50B returns credentials/session metadata only. No `/api/v1/me`, profile, medication, health-record, document, sharing, Saved Checks or Dashboard API. Never join health data just to resolve authentication. Keep existing public V1 DTO allowlists and ignore cookie/Authorization identity on their public read behavior. No personal health values in logs, error fields or authentication events. Later private APIs must separately test ownership, output minimization and denial paths.

## 25. Rate limiting

No general login/API limiter was found; the verification resend cooldown is not equivalent. A limiter is MUST HAVE inside 50B, before credential issuance is deployable. Proposed single-host implementation: atomic `flock` counters in a private application storage directory, bounded keys/files/retention and no dependency. HMAC normalized email/IP into keys using a dedicated configured secret. Do not trust arbitrary forwarded IP headers; use direct peer IP unless a trusted proxy is explicitly configured.

Initial policy: login at most 30 attempts per IP per 15 minutes and five failures per account key per 15 minutes; unknown accounts use the identical account-key path. Refresh at most 60 attempts/IP/minute and 30/family/minute after identifying a known digest; unknown requests cannot allocate unlimited family buckets. Logout-all password failures share the login account-failure bucket. Known authenticated logout is a cheap bounded revocation operation, not password guessing, and should remain available rather than be blocked by a login cooldown. Bound request/body/header sizes before expensive work. Return 429 with `Retry-After`; limiter storage failure denies issuance/refresh with safe 503. File cleanup/capacity exhaustion must fail closed rather than erase active counters to admit new keys.

This design is valid for the current single application host; multiple hosts require a shared atomic limiter before that deployment, not an external dependency now. Limits are policy and need deterministic tests, not live brute force.

For failed access-token authentication, cap attempts at 60/IP/minute before repeated database lookup. For authenticated guard use, propose 120 requests/family/minute, excluding the inexpensive own-family logout so an exhausted usage budget does not prevent revocation. This is a foundation ceiling, not a design of future private endpoints; later resource-specific limits remain separate. Cap limiter file/key cardinality and never allocate a persistent bucket for every attacker-supplied random token.

## 26. Brute force and abuse

Apply IP and account-key budgets together; no permanent account lockout from anonymous requests. Use a same-cost dummy password verification for unknown/no-local-password cases to reduce obvious timing differences, without claiming perfect timing indistinguishability. Preserve passwords byte-for-byte; normalize email consistently. Cap JSON request size at 8 KiB, email at the existing field limit, password input at a defensive 1 KiB byte limit, device label at 80 characters, and tokens at exact format length. Reject malformed/ambiguous JSON and unexpected keys. Do not log submitted passwords or token fragments. Distributed credential stuffing remains a residual risk; risk scoring/MFA are later enhancements, not invented blockers.

## 27. Enumeration resistance

API login gives the same 401 code/message for unknown account, wrong password, Google-only/no-local-password, inactive and unverified account. Do not expose whether an email/provider/mobile exists. Rate keys and failure accounting apply to unknown emails too. Syntactically invalid requests may receive 400/422 independently of account existence. Current browser login deliberately has safe inactive/unverified messages after password verification and registration can report existing email; preserve these existing web contracts. Generic API errors do not require redesigning browser forms.

## 28. Error contract

Reuse `{success:false,error:{code,message,fields?},meta:{api_version:"v1"}}`; success remains `{success:true,data:...,meta:{api_version:"v1"}}`. Never echo credentials in `fields`. Suggested fixed vocabulary:

| HTTP | Code | Safe meaning |
|---|---|---|
| 400 | `BAD_REQUEST` | Invalid JSON, ambiguous authorization or unsupported input shape |
| 401 | `AUTHENTICATION_FAILED` | Login credentials cannot authenticate |
| 401 | `UNAUTHENTICATED` | Access/refresh missing, expired, revoked or otherwise unusable; do not reveal replay details |
| 403 | `FORBIDDEN` | Authenticated user lacks permission, reserved for future authorized operations |
| 404 / 405 | `NOT_FOUND` / `METHOD_NOT_ALLOWED` | Unknown route / wrong method; correct `Allow: POST` for auth routes, GET unchanged for public routes |
| 413 / 415 | `PAYLOAD_TOO_LARGE` / `UNSUPPORTED_MEDIA_TYPE` | Body limit / non-JSON request |
| 422 | `VALIDATION_ERROR` | Allowlisted field-shape/length errors independent of account existence |
| 429 | `RATE_LIMITED` | Retry later; include `Retry-After` |
| 500 / 503 | `INTERNAL_ERROR` / `SERVICE_UNAVAILABLE` | Safe failure; no SQL, exception, filesystem or secret details |

Bearer-protected failures include an appropriate `WWW-Authenticate: Bearer` challenge without secret diagnostics. All credential responses/errors are no-store. HEAD returns no body even on method rejection. Do not redirect API failures.

Use the same literal login message, "Unable to sign in with these credentials.", for all account-related login failures. Access/refresh denial uses "Authentication is required. Please sign in again."; 429 uses "Too many requests. Please try again later." Internal failures retain the existing safe "Unable to complete the request." message. Never tell a caller that a specific refresh token was consumed or a particular account is inactive.

## 29. Security events and logging

Allowlist login success/failure, issuance, refresh rotation/reuse, family/logout-all revocation, account-trigger invalidation and rate-limit events. Record UTC time, event code, noncredential correlation ID, known user/family ID where justified and coarse/pseudonymized peer key. Unknown-account events do not contain submitted email. Never log Authorization, JSON bodies, plaintext tokens, token digests, passwords, recovery links, provider ID/access tokens, health data or full user records.

Existing `Logger` writes `storage/logs/app.log` and silently tolerates logging failure; it is not a tamper-proof audit ledger. Use allowlisted `info`/`warning` messages rather than dumping exception objects/arguments. API revocation cannot depend on a successful log write. Define log access and rotation operationally; do not claim a new audit table is necessary.

## 30. Retention and cleanup

Keep consumed refresh rows until family absolute expiry so reuse detection is available for the whole useful family lifetime. Once expired/revoked, retain bounded security metadata for seven additional days then purge the family with cascading token deletion; do not delete a live family's replay tombstones early. Logout marks revoked immediately, regardless of cleanup availability. Login enforces the live-family cap before issuance; cleanup is not an authorization dependency.

Proposed authentication event retention: 30 days, excluding secrets/health data; private limiter records expire with their windows plus a small cleanup margin. Document protected backup retention separately because database deletion does not erase historical backups. A guarded CLI cleanup command may delete only expired API artifacts in bounded batches; do not add an unapproved scheduler/automation in 50A or silently purge existing logs.

## 31. API keys versus user authentication

A static app/API key embedded in a distributed mobile app cannot prove user identity or remain confidential. Do not use one to unlock account data, identify an installation as a trusted user, bypass limits, or replace the access/refresh model. Optional public client/version identifiers are metadata only. Machine-to-machine credentials and third-party developer keys are different future requirements.

## 32. OAuth / external identity provider choice

No external authorization server is required for this first-party foundation. Existing Google client dependency remains for current browser OAuth only. If delegated access or multiple clients become requirements, evaluate a maintained authorization server with code+PKCE, strict redirect/audience validation and operational key management. Do not build a partial OAuth server, market custom login as OAuth compliance, or add a JWT package merely for popularity. External provider availability must not become a new dependency of local password login.

## 33. Password authentication plan

Reuse existing normalization, adaptive password verification, active/verified checks and established password/recovery policy through a pure service capability. API adds local-password-marker enforcement, input caps, rate controls and generic error mapping. Browser `login()` continues its existing session/last-login behavior. API login must not call registration, auto-create a health profile, alter `last_login_at`/user timestamps as a hidden browser side effect, or send verification/reset email. Successful API authentication stores only its new API session state. Users who need verification/recovery continue using the existing browser flow; no API recovery feature in 50B.

## 34. Native Google authentication plan

DEFERRED for initial 50B. Do not accept arbitrary Google email, reuse a web authorization code with the wrong client/audience, or hand provider access tokens to KOBI resource endpoints. A later native feature needs its own registered clients, system-browser code/PKCE flow, server-side issuer/audience/signature/expiry/nonce validation and the existing provider-subject/no-auto-merge policies. Native Google credentials would be exchanged for KOBI credentials, never treated as KOBI access tokens. This is a separate, justified feature rather than an implementation prerequisite.

## 35. Mobile security boundary

Require HTTPS with certificate validation for real traffic; no insecure TLS bypass or bearer secrets in deep links. App locking/OS biometrics can protect local storage but are not server authentication or proof of a particular human. Rooted/jailbroken devices, accessibility malware, instrumentation and a compromised unlocked device can expose usable bearer credentials. Short access TTL, bounded refresh life and server revocation limit consequences; they do not eliminate theft. Sender-constrained tokens, device attestation, pinning and MFA are deferred pending an actual client/threat requirement. No mobile code or credential provision in 50A.

Never hard-code a user's token in an app. On terminal 401/refresh failure, clear unusable credentials and request genuine login; do not loop refresh indefinitely or fall back to cookies. On explicit logout, clear local credentials regardless of network outcome and distinguish local sign-out from confirmed server revocation.

## 36. Backward compatibility

Preserve browser email/password login/logout, verification, reset/change, Google link/unlink, CSRF/session behavior and all current URLs. Preserve public V1 routes/DTOs, no CORS, no-store, no session, safe 404/405/HEAD behavior, and existing AJAX contracts. Do not modify Dashboard, saved checks, mobile numbers, Treatment, Medicine or Symptom Checker. Only the future credential-verification extraction and API-specific schema/backstop integrate with existing authentication; regression tests must prove browser behavior stays the same. No role inheritance or governance migration follows from API login.

Step 48B `/search`, its public disease/substance results and navigation remain unchanged and belong in the compatibility regression.

## 37. Threat model

| Threat | Initial mitigation / residual risk | Boundary |
|---|---|---|
| Access-token theft | Short TTL, TLS, protected storage/log redaction, online revocation; replay possible until invalidated | MUST 50B; sender constraint later |
| Refresh-token theft | Protected storage, one-use rotation, idle/absolute expiry; unused stolen token can win first use | MUST 50B; stronger device binding later |
| Database compromise | Digests only, strong entropy, least privilege and protected backups; full write compromise can forge state | MUST 50B; recovery procedures remain operational |
| Replay | Refresh history/reuse revokes family; access remains ordinary bearer, not one-use | MUST 50B; do not promise replay-proof access |
| Credential stuffing | Combined account/IP limits, generic errors, bounded work; distributed attacks remain possible | MUST 50B; adaptive risk/MFA later |
| Password brute force | Adaptive verification, dummy path, failure budgets and cooldowns | MUST 50B |
| Session fixation | No cookie/session reuse on API, server-generated families; existing web regeneration unchanged | MUST 50B regression |
| Account takeover | No automatic merge/role grant; credential changes revoke API families; bearer theft still matters | MUST 50B; MFA/recovery redesign deferred |
| Device theft | Secure storage, client clear, logout-all and bounded persistence | Server controls 50B; device-management UI later |
| Logout/refresh race | Common lock order and committed revocation, revalidation; already admitted request may finish | MUST 50B concurrency test |
| Password reset/change | Transactional API revocation trigger + hash stamp, same-second-change test, no old-family revival | MUST 50B; browser session overhaul excluded |
| Disabled/deleted account | Fresh account checks, durable status/verification invalidation, FK cascade deletion | MUST 50B |
| Authorization failure | Explicit user principal, no caller ID/role trust; resource-access checks remain mandatory later | Foundation isolation 50B; private-resource tests with later endpoints |

## 38. Exact Step 50B test requirements

Add focused authentication tests rather than rewriting existing suites:

1. Successful active/verified/local-password login issues only a credential DTO; no cookie/session/user/profile/last-login mutation. Unknown, wrong-password, inactive, unverified and no-local-password accounts have the same safe failure.
2. Exact JSON/media/body limits; malformed/array/duplicate credential inputs; unknown keys; invalid UTF-8; bounded device labels; rejection before expensive work; credential values never appear in errors/logs.
3. Token syntax/entropy source/one-time output, digest purpose separation, raw-token absence from persistence, access expiry boundaries and credential/family expiry minimum.
4. Guard rejects missing, multiple, malformed, query/body access credentials and refresh-as-access; ignores cookies/client user IDs. Fresh account and credential-stamp checks always occur.
5. Refresh succeeds once, changes both credentials and invalidates old access; idle/absolute bounds hold; expired/revoked/wrong-purpose/random credentials fail without revoking unrelated families.
6. Consumed-token reuse commits whole-family revocation, including the newly issued access; repeat/retry/lost-response behavior matches the documented strict policy.
7. Five-family limit, deterministic oldest revocation, independent devices, single logout isolation, all-device logout with current password, no browser logout side effect.
8. Password change/reset, same-second hash change, disable/re-enable, unverify/reverify and account deletion never revive old credentials. Rollback preserves prior consistent state.
9. Parallel refresh/login/logout/password reset tests prove lock/commit semantics, no double successor, and no credential returned on failed commit.
10. Deterministic clock/limiter tests cover unknown account parity, IP/account budgets, Retry-After, storage failure, file cap/TTL and proxy-spoofing denial. No real brute-force traffic.
11. V1 JSON envelopes, 401 challenge, correct per-route Allow, HEAD suppression, no-store, no-CORS, no-session and safe unexpected errors with debug enabled.
12. Rerun existing auth-phase1 and public-api-v1/bootstrap/HTTP tests in the appropriate isolated environments, plus public-search regression and relevant browser auth smoke. Never weaken tests to pass.

These are future acceptance cases, not claims that token authentication currently exists or was tested in 50A.

## 39. Isolated MariaDB validation plan

Use a guarded disposable database on actual MariaDB 10.4.27 with the observed non-strict SQL mode unchanged. Synthetic users only. Import authorized schema objects, inspect SHOW CREATE/INFORMATION_SCHEMA, verify FKs/indexes/generated active-slot/CHECKs/trigger metadata, and deliberately test direct malformed inserts/duplicate active tokens/orphans/invalid lifecycle values. Stop on compatibility errors instead of silently dropping constraints or changing SQL mode.

Use separate connections/processes and barriers for concurrent refresh, logout-all, five-family issuance and credential updates. Assert one successor, committed reuse revocation, atomic rollback, immediate state denial, same-second password changes and no revival after reactivation. Verify actual trigger firing from existing password persistence as well as direct synthetic account updates. Test cleanup retains live-family replay history and removes only eligible API rows.

Compare all unrelated data/schema before/after isolated tests. No normal-KOBI DDL follows from 50A readiness. Any later normal deployment needs explicit authorization, verified recoverable backup, demonstrated recovery including trigger fidelity, metadata/data checks and a scoped rollback plan. Drop the API-only trigger before dropping API tables during an approved rollback; never leave an existing user-update trigger referencing an absent table.

## 40. Future browser validation

After implementation, genuinely authenticate a permitted test account through the normal UI; verify login, own account, verification/recovery navigation, safe logout and protected-route denial without bypassing authentication. Verify API requests neither authenticate a browser nor consume/destroy its session and public API stays anonymous even when cookies exist. Use disposable fixture accounts for actual password reset/change and account-state writes; do not experiment on the owner's normal account.

Native client code is deferred. A guarded API test client can exercise the four endpoints without browser cache/CORS confusion. No genuine browser authentication/session experiment was needed or performed in this readiness task.

## 41. Future authentication documentation

50B should create `docs/authenticated-api-v1.md` and `docs/task-50b-authenticated-api-foundation-implementation.md`. Specify exact request/response fields, endpoints, no health-data scope, lifetimes, refresh single-flight/lost-response rules, immediate old-access invalidation, device cap, logout/reset/disable behavior, secure mobile storage guidance, TLS, no-CORS/session boundary, errors/rate limits, logging exclusions, cleanup, deployment/recovery and executed test evidence. These future documents are not created in 50A. Keep `docs/public-api-v1.md` accurate with a small cross-reference, preserving public contracts.

## 42. Likely exact Step 50B files

These are proposed paths, not files changed by this task:

| Action / path | Reason |
|---|---|
| Add `app/Controllers/ApiAuthController.php` | Four POST request handlers; strict input, explicit guard, JSON response |
| Add `app/Services/ApiAuthService.php` | Issuance, rotation, multi-device cap, revocation transactions |
| Add `app/Services/ApiAuthGuard.php` | Stateless bearer parsing and principal resolution |
| Add `app/Services/ApiAuthPrincipal.php` | Small immutable request identity |
| Add `app/Services/ApiAuthException.php` | Safe typed authentication failures |
| Add `app/Services/ApiAuthRateLimiter.php` | Bounded atomic private-file limiter using existing PHP facilities |
| Add `app/Repositories/ApiAuthRepository.php` | Prepared queries through BaseRepository/shared Database; account locks and new API table access |
| Modify `app/Services/AuthService.php` | Extract reusable pure password verification while preserving browser behavior |
| Modify `app/Core/Request.php` | Strict bounded JSON input capability; preserve existing GET/form APIs |
| Modify `app/Core/Router.php`, `app/Core/PublicApiResponse.php` | Accurate auth POST 405/Allow behavior and safe response headers; preserve public GET contracts |
| Modify `routes/web.php` | Register exactly the four auth endpoints through existing router |
| Modify `app/Config/Config.php` and `.env.example` | Document/read the dedicated limiter key and API policy configuration; never copy or overwrite real secrets |
| Add `database/api-auth-foundation.sql`; update `database/structure.sql` | Two additive tables and one API-only user-update revocation trigger, isolated-validated before any authorized deployment |
| Add `scripts/cleanup-api-auth.php` | Guarded bounded cleanup of expired API artifacts only; no automatic scheduling |
| Add `tests/api-auth.php`, `tests/api-auth-mariadb.php`, `tests/api-auth-http.php` | Deterministic policy/guard tests, guarded transactional MariaDB tests, endpoint/header/session tests |
| Extend `tests/auth-phase1.php`, `tests/public-api-v1.php`, `tests/public-api-v1-bootstrap.php` only where needed | Meaningful compatibility cases for pure verification extraction and shared response/router changes |
| Add the two documents in section 41; minimally update `docs/public-api-v1.md` | Authentication contract and implementation evidence, public API cross-reference |

Existing container autowiring should resolve new classes without a bootstrap/container redesign. Private limiter state under `storage/cache/api-auth/` is runtime state to provision in 50B, not a committed credential or a new database. Verify `scripts/` conventions before adding the single cleanup entry point. No view, Google controller, mobile client, health repository or browser route change is required.

## 43. Schema decision

**ADDITIVE SCHEMA CHANGE REQUIRED.** The minimum complete proposal is two credential/session tables plus one API-only revocation trigger, with indexes, FKs and checks described above. Reusing recovery tokens cannot represent independent device families or consumed-token replay history. No SQL/migration/structure file is changed in 50A. No existing data needs rewriting for the proposed design; existing users acquire API rows only through future genuine login. No API credentials may be backfilled from browser sessions.

## 44. Dependencies

Existing PHP 8.2+, PDO/MariaDB, random bytes, SHA-256, password verification, file locking and KOBI container/repository facilities are sufficient. No new Composer package, JWT library, Redis, SMS provider, external authorization server or mobile-verification work is required. Existing Google dependency stays in its existing role.

Implementation must provision a private writable limiter directory and separate limiter HMAC secret, verify reverse-proxy/Authorization forwarding, and enforce HTTPS before real credential deployment. Those are normal 50B configuration/deployment controls, not a separate product prerequisite or authorization to change configuration now. Multi-host deployment would change the limiter requirement; no evidence requires that deployment today.

## 45. Step 50B boundary

**MUST HAVE:** the four auth endpoints; pure credential-verification reuse; active/verified/local-password policy; random opaque digest-only credentials; online principal/guard; strict JSON/no-cookie/no-session boundary; TTLs; atomic refresh/reuse handling; device cap; single/all logout; durable password/status/verification revocation and deletion behavior; limiter/size controls; safe errors/no-store/log redaction; additive isolated-tested schema; scoped cleanup; documentation and success/failure/concurrency/regression tests.

**SHOULD HAVE:** clear device labels, concise operational event summaries, explicit clock injection for reliable tests, capacity guidance and documented client refresh recovery. These do not justify a new administration UI.

**DEFERRED:** `/me` and all private resources; Google/native provider exchange; registration/recovery API; verified-mobile/SMS; API keys/delegated OAuth; scopes/consent; device management UI; biometrics/mobile app; MFA/attestation/DPoP; JWT/external authorization server; browser-session global revocation; Dashboard/governance changes.

## 46. Readiness decision

**READY** for scoped Step 50B implementation. Existing user identity, password policy, repositories, transactions, public JSON/session boundary and PHP capabilities provide a workable base. Missing token storage, limiter and guard are the implementation itself, not reasons to invent a preceding project phase. No external prerequisite was identified. This is not a declaration that mobile authentication is currently deployed or safe to expose without implementing/testing the MUST controls. Normal-database deployment remains a separate explicitly authorized operation.

## 47. Implementation-ready Step 50B plan

| Endpoint | Required input | Result / boundary |
|---|---|---|
| `POST /api/v1/auth/login` | JSON `email`, `password`, optional `device_label` | Generic failure or one opaque access/refresh pair for a new family; no PHP login |
| `POST /api/v1/auth/refresh` | JSON `refresh_token` only | Atomically rotate refresh and access; known reuse revokes family |
| `POST /api/v1/auth/logout` | Bearer access; empty JSON object | Revoke caller family and acknowledge; no browser session change |
| `POST /api/v1/auth/logout-all` | Bearer access plus JSON `current_password` | Reverify password, revoke all API families, acknowledge |

Successful login/refresh `data`: `token_type` = `Bearer`, `access_token`, `expires_in` (600 or smaller remaining bound), `refresh_token`, `refresh_expires_at`, noncredential `session_id`, `session_expires_at`. UTC ISO-8601 timestamps. No user/health DTO. Logout `data`: `revoked: true`. All return existing V1 envelopes, HTTP 200 on success, no-store, and no cookies.

Implement service/repository/guard and strict parser/limiter; add two tables plus durable revocation trigger; extract pure password checking without changing browser outputs; wire only these routes. Store digests, enforce 10-minute access/14-day refresh inactivity/30-day absolute families and five-device cap. Commit rotation/revocation before responding; never return rollback credentials. Preserve Google/email verification/recovery web flows, keeping native Google deferred. Execute sections 38–40 in isolated environments and document verified results/deployment procedure. No `/me` or private API follows automatically.

## 48. Non-mutating validation and safety

Performed in Step 50A:

- Static schema/code/routes inspection, including the current public API documentation, authentication handlers, service/repository boundaries, Google identity protections, CSRF/session lifecycle and password-reset transaction boundary.
- Read-only normal MariaDB SELECT/metadata inventory only. Observed SQL mode: `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`; no SET or mode change. No raw credentials/identity rows were output.
- PHP syntax checks: all 16 inspected authentication/session/API/bootstrap/route PHP files passed.
- Existing `tests/public-api-v1-bootstrap.php`: **8 checks passed**, including debug-mode safe failures and session-free bootstrap; the harness uses no database/session/log writes. No API token was created. Tests that exercise token issuance or browser sessions were deliberately not executed in this readiness task.
- Final report exists with exactly 48 sequential numbered sections and ends with READY. SHA-256 aggregate comparison across `app`, `bootstrap`, `routes`, `database`, `resources`, `public` and `tests` matched the pre-report baseline exactly. `git diff --check` passed; Git emitted only its existing line-ending notice for `health-medicines.php`, which was not changed in this task. The new untracked report was checked directly because ordinary Git diff excludes untracked files.

No application code, database data/schema, configuration, user, password, token, genuine session, email/SMS, authentication behavior, Treatment/Medicine, mobile-number flow or Git index/history was modified. Only this readiness report is created. Existing unrelated workspace edits are preserved; the workspace was already dirty before this task. The report describes proposed SQL/files/tests, not applied changes or completed token tests. Step 50B has not begun.

Final readiness:

READY
