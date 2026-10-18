# KOBI V1 API authentication contract

Step 50B authentication foundation, authenticated identity/read resources and Step 59A medication checkoff writes. Native first-party password authentication only; no clinical recommendations. Step 50B normal deployment and 458-check acceptance are complete; see `task-50b-deployment-activation.md`. Subsequent API validation is recorded in the individual implementation reports; Step 59A production deployment is not part of its implementation task.

## Boundary and transport

All authentication, identity and private resource routes require HTTPS. The application rejects HTTP rather than redirecting credentials. A trusted TLS-terminating deployment must supply truthful server HTTPS metadata; arbitrary forwarded headers are not trusted. Clients must verify the server certificate. Keep Authorization forwarded through the explicit private paths in `public/.htaccess`, including the single Step 59A checkoff path; public knowledge and browser routes are not added to that scope.

Authentication POST requests use `Content-Type: application/json`, a flat object, no query parameters and no unexpected fields. Strings/null only; duplicate keys (including escaped duplicates), nested values, invalid JSON/UTF-8, controls and bodies over 8 KiB are rejected. Email limit 255 bytes; password/current_password limit 1 KiB without trimming; optional device label maximum 80 characters, no controls. No user IDs, roles, mobile proof or client credentials are accepted. Existing web password policy is unchanged. The identity GET uses the separate input contract below.

API authentication never starts PHP sessions, accepts session cookies as identity, sets cookies, requires a browser CSRF token or changes browser logout. Existing browser CSRF protections remain. No CORS grant or OPTIONS permission is added. Public V1 GET routes remain public and independent of bearer/cookie identity.

## Endpoints

| Method/path | JSON | Authorization | Result |
|---|---|---|---|
| POST `/api/v1/auth/login` | `email`, `password`, optional `device_label` (string/null) | None | New independent device family and credential pair |
| POST `/api/v1/auth/refresh` | `refresh_token` | Not required | Replace refresh and access credentials atomically |
| POST `/api/v1/auth/logout` | Empty object | One `Bearer` access credential | Revoke that device family |
| POST `/api/v1/auth/logout-all` | `current_password` | One `Bearer` access credential | Reverify password, revoke all account API families |
| GET `/api/v1/me` | No parsed body; no query parameters | One `Bearer` access credential | Minimal own-account identity |
| GET `/api/v1/health-profiles` | No parsed body or query parameters | One `Bearer` access credential | Accessible active profile array |
| GET `/api/v1/health-profiles/{profileId}` | Positive decimal profile ID; no query parameters | One `Bearer` access credential | One accessible active profile |
| GET `/api/v1/health-profiles/{profileId}/medications` | Profile ID; no query parameters | One `Bearer` access credential | Personal medication array for authorized profile |
| GET `/api/v1/health-profiles/{profileId}/medications/{medicationId}` | Profile and medication IDs; no query parameters | One `Bearer` access credential | One medication constrained to authorized profile |
| GET `/api/v1/health-profiles/{profileId}/medical-summary` | Profile ID; no query parameters | One `Bearer` access credential | Live aggregate for authorized profile; five counts only |
| GET `/api/v1/health-profiles/{profileId}/conditions` | Profile ID; optional `page`, `page_size` | One `Bearer` access credential | Paginated recorded medical conditions for authorized profile |
| GET `/api/v1/health-profiles/{profileId}/conditions/{conditionId}` | Profile and condition IDs; no query parameters | One `Bearer` access credential | One condition constrained to authorized profile |
| GET `/api/v1/health-profiles/{profileId}/medication-schedules` | Profile ID; optional `page`, `page_size` | One `Bearer` access credential | Daily slot configuration for authorized profile |
| GET `/api/v1/health-profiles/{profileId}/medication-schedules/due-today` | Profile ID; optional `page`, `page_size` | One `Bearer` access credential | Canonical slot-local current occurrences and nullable state |
| POST `/api/v1/health-profiles/{profileId}/medication-schedules/{scheduleId}/checkoff` | Exactly `state: "taken"` or `state: "skipped"`; no query parameters | One `Bearer` access credential; active owner/editor membership | Committed server-today occurrence with resulting state |

There is no registration/recovery API or Google API exchange. Private resources include the documented read resources and the single Step 59A checkoff action below. Medication and schedule maintenance remain browser-only. Browser registration, verification and password recovery remain the entry points for those existing functions. Active, verified-email accounts with a real local password can log in; a linked Google identity does not prevent local-password login. Google-only accounts cannot authenticate with a placeholder password hash. Native Google login is deferred. A stored mobile number is unverified and plays no authentication role.

## Successful responses

HTTP 200, existing envelope: `success: true`, `data`, `meta: {api_version: "v1"}`. Login/refresh data has exactly these fields:

| Field | Meaning |
|---|---|
| `token_type` | `Bearer` |
| `access_token` | Opaque credential; disclose only to the authorized requesting client |
| `expires_in` | Access seconds remaining, at most 600; capped by the family deadline |
| `refresh_token` | New one-use opaque refresh credential |
| `refresh_expires_at` | UTC ISO-8601 deadline |
| `session_id` | Noncredential random device-session identifier |
| `session_expires_at` | UTC ISO-8601 absolute family deadline |

Logout/logout-all data is `{revoked: true}`. No database row, password hash, provider identity or health information is returned. No token examples are included here.

## Authenticated identity — Step 51B

GET `/api/v1/me` uses the same Bearer access guard and current account eligibility checks, without browser sessions, cookies or CSRF. The principal's userId alone selects `UserRepository::findById()`. It admits once using the normal non-logout family budget. No client URL/query/body/custom identity field selects an account.

HTTP 200 uses the existing envelope with exactly these data fields:

| Field | JSON type | Nullable | Meaning |
|---|---|---|---|
| id | Decimal string | No | Authenticated KOBI account ID |
| email | String | No | Own stored account email |
| status | String (`active`) | No | Existing eligible account state |
| email_verified | Boolean (`true` on success) | No | Derived from existing non-null email_verified_at |

Metadata is exactly `{api_version: "v1"}`. No name, mobile number (including masked form/presence flag), timestamps, provider/grant/session identifiers, credentials, health/profile/medication/document/symptom/summary/Treatment/Medicine/sharing data. The repository row is never serialized. Mobile contact is not proof of possession or verified identity.

No query parameters are accepted (400 BAD_REQUEST after bearer admission); GET body and request Content-Type are not parsed, as with public GET routes. Bodies cannot override identity. HTTPS is checked first; on HTTPS, unauthenticated requests receive the established generic 401 before query validation. Inactive/unverified/missing/no-longer-eligible accounts and malformed/invalid/expired/revoked/superseded credentials use the existing generic UNAUTHENTICATED error/challenge; no account-state detail or token reason is exposed. Eligibility loss detected between admission and account lookup also denies. Existing concurrent-admission semantics apply: an already admitted operation may finish before a revocation commits.

All success/errors retain JSON, no-store and nosniff, no cookies/CORS. Known identity route with another method receives 405 `Use GET for this endpoint.` / `Allow: GET`; HEAD has no body. No aliases or user-ID subroutes exist. The existing 120/family/minute and failed-access 60/direct-peer-IP/minute budgets apply unchanged, with 429/Retry-After and fail-closed limiter 503. The GET does not write account or token rows; existing private limiter-file accounting still occurs. There is no identity cache, last-seen write, schema addition or provider-specific branch.

## Accessible Health Profiles — Step 52B

GET `/api/v1/health-profiles` returns only active profiles with an active membership for the authenticated principal, using HealthProfileRepository::findAccessibleByUser(principal.userId). GET `/api/v1/health-profiles/{profileId}` uses findAccessible(profileId,principal.userId). A caller-supplied profile ID never supplies authorization. Existing owner/editor/viewer read access, including active shared memberships, is preserved; creator identity alone does not grant access.

Both projections contain exactly three non-null fields: `id` (positive decimal string), `full_name` (stored display string), `profile_type` (self/family/dependent). Type describes the existing profile category, not a verified legal relationship. No role, membership status/account IDs, creator ID, DOB, age, gender, timestamps, contact/mobile, notes, invitation/sharing secrets, credentials or clinical data is returned. Unknown stored profile types fail safely with generic 500 instead of a fabricated category. Profile names/types remain private and must be rendered as text by clients.

Success HTTP 200 uses `success:true`, `data`, `meta:{api_version:"v1"}`. Collection data is an array; an account with no accessible profiles receives `[]` without creation or other writes. Detail data is a single object. There is no pagination: current profile-selection behavior is a simple collection, and no realistic paging requirement was established. The unchanged 256-KiB response guard fails safely rather than silently truncating an unexpectedly oversized result. All query parameters (including page, page_size and user_id) are rejected with existing 400 BAD_REQUEST after admission. GET bodies/identity fields are not parsed or trusted.

On HTTPS both actions admit Bearer once with the normal existing family budget; cookies/session/CSRF are not required or accepted as identity. HTTP gives 400 HTTPS is required. Missing/malformed/invalid/expired/revoked tokens use the same generic 401/challenge as `/me`. Detail ID must have positive decimal syntax without leading zero/sign and fit current signed INT (1–2147483647); invalid syntax/range gives 422 VALIDATION_ERROR, “Use a valid profile ID.” A nonexistent, archived, revoked-membership or inaccessible detail gives identical 404 NOT_FOUND, “Health profile not found.” No owner or membership information appears in errors. Unexpected database/encoding failures retain generic 500; existing 429/Retry-After and fail-closed 503 apply unchanged.

All responses use JSON UTF-8, no-store and nosniff; no cookies/CORS grant. Other methods receive 405/Allow GET and HEAD is empty. No aliases, nested resource endpoints, profile edits/creation, membership/invitation, medical records/medicines/schedules/symptom checks/documents/summaries/sharing API is added. `/me` remains its original four-field account identity. Browser profile services/views/routes and AJAX contracts are unchanged. Reading profiles does not update database rows or create a self profile; the existing limiter still accounts for requests in its private runtime file.

## Personal Medications — Step 53B

Exactly two GET routes: `/api/v1/health-profiles/{profileId}/medications` and `/api/v1/health-profiles/{profileId}/medications/{medicationId}`. No `/medicines` alias, search, writes or nested schedule routes. Existing browser `/medicines` paths are unchanged.

Both use existing Bearer admission once, then ApiHealthProfileService::detail(principal,profileId) → HealthProfileRepository::findAccessible(profileId,principal.userId) before medicine data access. Active profile and active membership (owner/editor/viewer) are required. Detail SQL binds both medication id and authorized health_profile_id in the same query. Other-profile medication IDs cannot be resolved, even if the account has access to both profiles. No user/ownership fields from request data or cookies are trusted.

HTTP 200 uses the existing success/data/meta envelope with meta exactly `{api_version:"v1"}`. List data is an array; detail data is an object. Exact object fields:

| Field | Type / nullability |
|---|---|
| id | Non-null decimal string |
| brand_name | Non-null stored string |
| generic_name | String or null |
| strength | String or null |
| dose | String or null |
| frequency | String or null |
| route | String or null |
| status | Non-null current/stopped/historical string |
| started_on | Stored YYYY-MM-DD date string or null |
| stopped_on | Stored YYYY-MM-DD date string or null |

These are recorded personal values, not KOBI prescribing/dosage recommendations or verified Medicine knowledge. No inference, normalization of clinical meaning, ingredient/registry/governance enrichment or advice. Exclude notes, prescribed_by/provider/pharmacy, profile/creator/member IDs, demographics/contact/mobile, audit timestamps, credentials, sharing/invitation secrets, conditions/symptoms, documents/summary, schedules/checkoffs and all other fields. Render text safely in clients.

The collection retains existing order: current, stopped, historical; then started_on DESC and id DESC. Empty authorized profile is 200/[]; no profile is created. No pagination/filtering is added: current personal-medication behavior is a simple profile list and no realistic need was demonstrated for V1. Existing 256-KiB serialization guard applies; excess fails safely rather than silently truncating. All query parameters, including page/status/user_id, return established 400 BAD_REQUEST after admission. GET body/Content-Type is not parsed or used as identity.

HTTPS is required (HTTP 400). Missing/malformed/invalid/expired/revoked Bearer credentials retain generic 401/challenge; existing limiter may instead return 429/503. Inaccessible/nonexistent/archived profile or revoked membership retains existing 404 NOT_FOUND “Health profile not found.” Missing and other-profile medication detail share 404 NOT_FOUND “Medication not found.” No ownership information is revealed. Profile IDs retain Step 52B validation; medication IDs likewise require positive decimal syntax, no leading zero/sign and signed-INT range 1–2147483647; malformed IDs receive 422 VALIDATION_ERROR “Use a valid medication ID.” Unexpected data/infrastructure faults produce existing safe generic 500. Unknown stored medication status fails closed rather than inventing a state or changing SQL mode.

JSON UTF-8, no-store and nosniff apply on success/error, no cookies/CORS, no browser-session or CSRF dependency. Existing family/failed-access limiter policy is unchanged. Known unsupported methods return 405/Allow GET, HEAD empty. Non-numeric detail segments are rejected as IDs, not executed as search/schedule actions; any future authorized literal route must precede the parameter route. No such route is implemented here. GET does not update account/profile/medicine/token rows; existing private limiter-file accounting remains.

## Credential lifecycle (unchanged Step 50B)

Credentials contain a purpose prefix (`ka1_` access, `kr1_` refresh) and unpadded base64url encoding of 32 random bytes. They are opaque, not signed claims. The server stores binary SHA-256 digests with `kobi-api-v1`, purpose and separators in the hash input; no raw bearer secret is retained in the database or logs. A noncredential public family identifier uses separate randomness. Passwords retain existing adaptive hashing.

Access expires after ten minutes, or earlier at the absolute family deadline. Every admission checks the current access digest, expiry, device-family revocation/absolute expiry, current account existence/active/verified/local-password state and password-hash stamp. There is no positive authorization cache. Bearer possession permits replay until expiry/revocation; tokens are not sender-constrained.

Refresh inactivity lifetime is 14 days. Absolute family lifetime is 30 days from login and never slides. A refresh transaction consumes the old row, inserts its successor and replaces the session's access digest. Old access stops working immediately. All timestamps for new API records are UTC; no application timezone or SQL-mode change is needed.

Consumed refresh hashes are retained while the family can authorize. Reusing a known consumed credential revokes that whole family, including the latest access credential, and commits the revocation before returning generic 401. Unknown guesses cannot revoke another family. Already revoked families remain denied. Native clients MUST serialize refresh per device: two simultaneous requests can produce one initial success followed by family revocation, rendering even that success unusable. Lost successful refresh responses require genuine re-login. There is no grace window or plaintext successor cache.

Five active families maximum per account; a sixth login revokes the oldest live family by creation time/ID. Labels are display metadata only, not device proof. Single logout preserves other devices; logout-all preserves other accounts and all browser sessions. Repeated logout safely returns generic 401 once its credential is revoked; clear local secrets even if a retry fails. An expired access credential cannot authorize logout: refresh first if possible, otherwise clear locally and distinguish this from confirmed server revocation.

Password-hash change, active → inactive/invalid status, and verified → unverified account changes revoke API families transactionally through the API-only user-update trigger. Password stamp validation supplies a backstop without relying on second-resolution `password_set_at`. Re-enabling/re-verifying does not revive revoked families. User deletion cascades API records. Already admitted operations can finish before a concurrent revocation commits; later admission must fail.

## Errors and headers

All responses use JSON, `Cache-Control: no-store`, `X-Content-Type-Options: nosniff`. No Set-Cookie or CORS permission. Failures use the existing `success: false`, `error: {code,message}`, `meta` envelope. No credential echo or internal exception text.

| HTTP / code | Behavior |
|---|---|
| 400 `BAD_REQUEST` | HTTPS/query/JSON ambiguity errors |
| 401 `AUTHENTICATION_FAILED` | Same message for unknown, wrong password, inactive, unverified and no-local-password accounts: “Unable to sign in with these credentials.” |
| 401 `UNAUTHENTICATED` | “Authentication is required. Please sign in again.” No token-state/replay detail |
| 404 `NOT_FOUND` | Unregistered V1 route |
| 405 `METHOD_NOT_ALLOWED` | `Allow: POST` on these routes; public read routes keep `Allow: GET`; HEAD body empty |
| 413 `PAYLOAD_TOO_LARGE` / 415 `UNSUPPORTED_MEDIA_TYPE` | Size/type rejection |
| 422 `VALIDATION_ERROR` | Safe structural/field validation, independent of account existence |
| 429 `RATE_LIMITED` | “Too many requests. Please try again later.”, with `Retry-After` seconds |
| 500 `INTERNAL_ERROR` / 503 `SERVICE_UNAVAILABLE` | “Unable to complete the request.” Fail closed |

401 includes `WWW-Authenticate: Bearer`. Future resource authorization may use 403; no resource route is introduced here.

## Rate controls and logs

Single-host private-file limiter: exclusive `flock`, one bounded JSON store under configured storage `/cache/api-auth/counters.json`, HMAC-pseudonymized keys, expired-window pruning, 10,000 live-key and 2 MiB bounds. Requires a separate random `API_AUTH_RATE_KEY` of at least 32 bytes; unset/invalid key or unavailable/corrupt/full store fails issuance/refresh/admission closed with 503. Do not reuse another secret. `.env.example` documents the variable but contains no value; real credentials/configuration are not edited by this task.

- Login: 30 attempts/IP/15 minutes, five failures/normalized-account-key/15 minutes; unknown accounts follow the same policy.
- Refresh: 60/IP/minute, 30/known-family/minute. Guesses do not allocate per-token files/buckets.
- Failed bearer admission/header: 60/IP/minute; authenticated guard use: 120/family/minute.
- Own-family logout and logout-all do not consume the normal authenticated-use budget; malformed/failed credentials remain subject to abuse controls. Logout-all password failures share the account failure budget.

Direct peer IP only, no trust of arbitrary forwarded-IP headers. Multi-host rollout requires a shared atomic limiter before that deployment. File/storage permissions and web denial of application storage must remain enforced. No permanent anonymous-triggered account lock.

Security logs use fixed allowlisted event names through existing Logger; no credentials, digests, request/response bodies, submitted email, health data or authorization headers. Existing Logger is not a durable audit ledger. Operational rotation/access controls should retain authentication events no longer than 30 days; no new global log-deletion job is installed. Revocation does not depend on successful logging.

## Retention and cleanup

Live-family replay tombstones must remain until the family cannot authorize. Revoked families or absolutely expired families become eligible seven days after revocation/expiry. The CLI-only `scripts/cleanup-api-auth.php --expired-api-only` deletes at most 500 eligible families per invocation and cascades their refresh history. It does not touch browser/recovery credentials. Expiration/revocation is enforced without immediate deletion. No scheduler was installed; deployment operators must arrange documented bounded maintenance. Backups retain their own protected retention policy.

## Deployment and recovery

Step 50B normal-KOBI deployment is complete; the following prerequisites were satisfied and are retained as deployment guidance: explicitly authorize only `database/api-auth-foundation.sql`, create and verify a recoverable backup including trigger fidelity, rehearse restore, inspect the two new tables and one trigger for conflicts, validate the exact artifact in isolation, provision the separate limiter key/private storage and HTTPS/Authorization forwarding, then apply and verify those objects only. Do not execute the full destructive schema dump as an incremental migration. Keep existing SQL mode unchanged. Step 51B requires no database deployment.

Approved objects: `user_api_sessions`, `user_api_refresh_tokens`, `trg_users_api_revoke`. An approved rollback must remove the API-only trigger before removing its target tables, and disable API issuance/admission first. Rehearse on a disposable database; no rollback SQL was executed on normal KOBI. Do not leave a user-update trigger referencing absent API tables. No existing users, identities or recovery tokens are backfilled.

## Client/security limits

Store refresh credentials in OS-protected storage (iOS Keychain / established Android Keystore-backed facility); access may remain in memory. Never log/back up/hard-code user credentials or put them in URLs. Clear unusable secrets on terminal failure/logout; no endless refresh retry or cookie fallback. Compromised unlocked devices and unused stolen refresh tokens remain risks. MFA, sender-constrained credentials, attestation, device management UI, native Google, private resources and verified-mobile work are deferred.

See `docs/task-50b-authenticated-api-foundation-implementation.md` for exact executed tests, file inventory, deployment boundary and browser limitations.

## Live aggregate Medical Summary — Step 54B

GET `/api/v1/health-profiles/{profileId}/medical-summary` requires the existing Bearer access guard and principal, with one normal non-logout limiter admission. Scoped Authorization forwarding includes this exact nested summary path. It requires HTTPS and accepts no query parameters; GET bodies, cookies and custom actor headers cannot select identity. No browser session or CSRF token is required or created.

Before any clinical source read, `MedicalSummaryService::getSummaryForPrincipal()` calls `ApiHealthProfileService::detail()` → `HealthProfileRepository::findAccessible(profileId, principal.userId)`. Active profile and active membership rules are unchanged; owner/editor/viewer read access is preserved. A valid positive decimal profile ID within signed INT range is required.

HTTP 200 uses exactly `success: true`, `data` below, and `meta: {api_version: "v1"}`:

```json
{
  "profile": {"id": "1", "full_name": "Example profile", "profile_type": "self"},
  "counts": {
    "conditions": 0,
    "historical_conditions": 0,
    "current_medications": 0,
    "allergies": 0,
    "procedures": 0
  }
}
```

This illustrative profile is not production data. Profile ID is a decimal string; counts are integers, including zeros. Profile fields use the Step 52B projection. Counts come from the canonical live `MedicalSummaryService` assembler, matching the existing Dashboard:

| Count | Existing live definition |
|---|---|
| conditions | Number in the assembler's active condition group: status `active` or `chronic` |
| historical_conditions | Number in its historical group: every condition outside `active`/`chronic` (normally `resolved`/`historical`) |
| current_medications | Number of medications whose status is exactly `current`; excludes `stopped`/`historical` |
| allergies | All rows returned by the existing profile-scoped allergy repository, without an added category/severity filter |
| procedures | All rows returned by the existing profile-scoped procedure repository, including surgery/procedure/hospitalization |

This is a fresh live assembly, not a cached or persisted snapshot. The existing assembler loads and sorts record arrays internally; only the explicit profile/count whitelist is serialized. No clinical arrays, medication names/doses/schedules, condition/allergy/procedure details, notes/providers, documents, demographics, memberships, mobile, internal metadata, recommendations, sharing information or credentials are returned. The separate sharing service is never called; there are no snapshot/share/access-log writes.

Missing/malformed/invalid/expired/revoked credentials return the existing generic 401 UNAUTHENTICATED with Bearer challenge. Authenticated invalid profile IDs return existing 422 VALIDATION_ERROR; inaccessible, archived, revoked-membership, other-user and nonexistent profiles all receive the same 404 NOT_FOUND (`Health profile not found.`). Query parameters return 400 after bearer admission; HTTP returns 400 HTTPS required. Other methods receive 405 with `Allow: GET`; HEAD has no response body. Existing limiter 429/Retry-After and fail-closed 503 remain. Unexpected failures use the existing safe 500 envelope. All responses retain JSON UTF-8, no-store and nosniff, with no cookies or CORS grant. GET does not write database rows; the existing limiter still accounts in its private file.

## Recorded Medical Conditions — Step 55B

Only these new routes exist:

- GET `/api/v1/health-profiles/{profileId}/conditions`
- GET `/api/v1/health-profiles/{profileId}/conditions/{conditionId}`

These are private Medical Conditions resources, not a generic Health Records union. Each request requires HTTPS, existing Bearer guard/principal and one normal non-logout limiter admission. Current Authorization forwarding includes exactly these two condition paths. No browser session, cookie identity, CSRF or CORS grant is introduced.

`ApiHealthConditionService` first calls existing `ApiHealthProfileService::detail()` → `HealthProfileRepository::findAccessible(profileId, principal.userId)`. Active profile and membership rules remain unchanged; owner/editor/viewer can read. Count/list SQL is profile-bound; detail SQL requires `id = :id AND health_profile_id = :profile`. A condition from another profile is never disclosed, even when the actor separately has access to both profiles. Profile/condition IDs must be positive decimal signed-INT values without leading zeros/signs/fractions.

Each condition has exactly this projection (illustration, not production data):

```json
{
  "id": "123",
  "condition_name": "Example recorded condition",
  "status": "active",
  "diagnosed_on": "2026-01-15",
  "resolved_on": null
}
```

ID is a decimal string. Status is the actual stored `active`, `resolved`, `chronic` or `historical` value. Dates are valid `YYYY-MM-DD` strings or null; impossible stored status/date values fail with the existing safe 500 rather than being repaired or serialized inconsistently. Read projection does not reapply mutation lifecycle/date rules or infer clinical facts. Names are plain stored text and must be rendered safely by clients.

Collection HTTP 200 has `success: true`, `data: [condition, ...]`, and `meta: {api_version: "v1", page: 1, page_size: 20, total: 0, has_more: false}` (metadata values reflect the actual page). Pagination follows the existing public V1 names/bounds: `page` default 1/max 10000; `page_size` default 20/max 50. Larger sizes are rejected, not silently accepted. Only scalar UTF-8 decimal query strings without leading zeros/signs/fractions are accepted. Non-string/array/malformed UTF-8 parameters return 400; invalid ranges/formats and unknown parameters return 422. Repeated scalar query keys follow the existing PHP query parsing behavior. Empty profiles and pages beyond the end return `data: []`, with total/has_more computed for that authorized profile.

Ordering preserves the browser repository: status rank active → chronic → historical → resolved → other, then `diagnosed_on DESC`, then numeric `id DESC`. Null dates follow dated values within a status. No filters/search/custom sorting, unbounded result mode or cross-request snapshot consistency is offered. Count and page reads may observe concurrent changes independently.

Detail HTTP 200 uses the identical five-field object as `data`, with only `meta: {api_version: "v1"}`. No detail-only expansion. Query parameters are rejected with 400 after profile authorization. Collection query validation also follows profile authorization; both follow bearer admission. GET bodies/cookies/custom actor headers do not override identity.

Missing/malformed/invalid/expired/revoked credentials use existing generic 401 UNAUTHENTICATED/Bearer challenge. Inaccessible/archived/revoked-member/other-user/nonexistent profiles share existing 404 NOT_FOUND (`Health profile not found.`). Missing or wrong-profile conditions share 404 NOT_FOUND (`Medical condition not found.`). Invalid IDs return safe 422 VALIDATION_ERROR; HTTP is rejected with 400 HTTPS required. Other methods receive 405/Allow GET; HEAD body is empty. Existing 429/Retry-After, fail-closed 503 and safe 500 conventions remain. All success/errors retain JSON UTF-8, no-store and nosniff, without cookies/CORS.

Only the five named condition columns are selected for returned rows. No notes, doctor/hospital/providers, documents/attachments/paths/file metadata, demographics, membership/sharing/access logs, credentials/internal governance metadata, medication/allergy/procedure/insurance/schedule/saved-check resources are included or joined. Existing medicines and aggregate Medical Summary remain separate and unchanged. GET performs no database writes; the existing limiter still accounts through its private file. No schema, SQL-mode, browser UI or authentication behavior change is required.

## Medication Schedule / Due Today — Step 56B

Exactly two GET routes:

- `/api/v1/health-profiles/{profileId}/medication-schedules`
- `/api/v1/health-profiles/{profileId}/medication-schedules/due-today`

Both use existing HTTPS/Bearer guard and principal, one non-logout limiter admission, then `ApiHealthProfileService::detail()` → `HealthProfileRepository::findAccessible()`. Active profile/member rules and owner/editor/viewer reads are unchanged. Profile-bound SQL joins slots to personal medications through `m.health_profile_id = :profile`. No slot/medication ID alone grants access. No browser session, cookie identity, CSRF requirement for GET or CORS grant. Forwarding extends only to these exact two schedule paths. No dynamic slot-ID route exists, so due-today cannot collide with one.

Schedule items contain exactly:

```text
id: decimal string
health_medicine_id: decimal string
local_time: HH:MM string
timezone: stored named timezone string
starts_on: YYYY-MM-DD string
ends_on: YYYY-MM-DD string or null
is_active: boolean
```

`local_time` preserves the actual Step 45B CHAR(5) wall-time format (`08:00`); no seconds or UTC conversion are invented. IDs refer to a daily slot and its existing personal medication. Schedule configuration retains inactive/replaced rows and slots of stopped/historical medicines, matching browser slot-list visibility. It is not an eligibility-filtered occurrence list. Order is `is_active DESC, local_time ASC, numeric id ASC`.

Due Today items contain exactly:

```text
id: decimal string
health_medicine_id: decimal string
local_time: HH:MM string
timezone: stored named timezone string
scheduled_on: YYYY-MM-DD string
state: null | taken | skipped
```

`MedicationScheduleService::dueTodayForPrincipal()` rechecks API profile access and invokes the same private assembler used by browser dueToday. The unchanged eligibility method converts its UTC clock to each stored timezone. It requires current medication, active slot, local day within inclusive starts_on/ends_on and at/after any medication started_on lower bound. No current-hour filter: later times today are included. Taken/Skipped entries remain visible; absence of a matching slot/day checkoff produces null, never missed/overdue. Each slot has an independent occurrence; the same slot/local date remains one occurrence through DST folds/gaps. There is no single profile-wide today date or UTC alarm instant.

Due Today retains canonical PHP ordering: local_time, live brand_name internally, numeric slot ID. Medication name is used only internally to preserve sorting. Eligibility/state/sort run before pagination, so an early candidate limit cannot omit due rows. Existing full-profile loading and per-eligible-slot checkoff lookup remain internal; no separate eligibility SQL/calculation, caching or snapshot was added. Browser actor checks, schedule mutations, history and medicine deactivation semantics remain unchanged.

Both collection envelopes use `success: true`, `data: []` or the item array, and `meta: {api_version: "v1", page, page_size, total, has_more}`. The existing pagination convention applies: page default1/max10000, page_size default20/max50. Reject oversize/range/format/unknown fields with 422; non-string/array/invalid UTF-8 values with 400. No date/timezone/actor/filter/sort query fields. GET bodies/cookies/custom identity headers do not override the principal. Empty/beyond-end pages return [] with appropriate total/has_more. Count/page reads and separate requests do not promise immutable snapshot consistency, especially across local midnight.

Missing/malformed/invalid/expired/revoked credentials retain generic 401/Bearer challenge. Invalid profile IDs use 422; inaccessible/archived/revoked-member/other-user/missing profiles share existing 404. HTTP is rejected with 400 HTTPS required. Other methods receive 405/Allow GET, HEAD empty. Existing limiter 429/Retry-After, fail-closed 503 and safe 500 remain. Invalid stored time/zone/date/flag/state output fails safely; no repair or fallback timezone is applied. All responses are JSON UTF-8, no-store, nosniff, without cookies/CORS.

No extra item fields: medication names/details, notes/prescribers/providers, checkoff IDs/actors/timestamps, demographics, documents, membership/sharing data, credentials/governance metadata. Step 59A adds only the checkoff POST described below; history/detail and schedule-maintenance APIs remain absent. The Personal Medicines API remains the separate medication resource. GET does not write database rows; existing private limiter accounting remains. No schema, SQL-mode, browser UI or authentication behavior change. Due Today alone now adds the boolean `meta.can_record_checkoffs`; the item fields and ordering are unchanged.

## Saved Symptom Checks — Step 57B

Exactly two GET routes:

- `/api/v1/saved-symptom-checks`
- `/api/v1/saved-symptom-checks/{savedCheckId}`

These are **account-owned historical snapshots**, independent of Health Profiles, Conditions and Medical Summary. HTTPS and the existing Bearer guard/principal are required. SQL binds `user_id = principal.userId`; detail binds both record ID and owner in one query. Browser cookies, request bodies and custom actor headers cannot change that owner. No profile membership or sharing grants access. One existing non-logout limiter admission applies, without a new limiter or authentication mechanism. Authorization forwarding adds only the collection/single-ID path alternative.

Collection data contains metadata objects with exactly:

```text
id: decimal string
checked_at: UTC RFC3339 string (YYYY-MM-DDTHH:MM:SSZ)
saved_at: UTC RFC3339 string
selected_symptom_count: integer
displayed_result_count: integer
```

`checked_at` is the original result-generation instant; `saved_at` is when the snapshot was saved. Neither is symptom onset or a current assessment timestamp. Collection order is `checked_at DESC, numeric id DESC`. Only lightweight metadata is read, without loading snapshot JSON. Pagination is `page` default1/max10000 and `page_size` default20/max50. Invalid format/range/unknown parameters use 422; non-string/array/invalid UTF-8 query values use 400. Oversized page sizes are rejected. Empty and beyond-end pages return `data: []`. Standard collection meta is `{api_version: "v1", page, page_size, total, has_more}`. Count/page reads do not promise a concurrent immutable pagination snapshot.

Detail data has those same five fields plus exactly:

```text
snapshot: {
  version: integer (stored payload schema version, currently 1),
  checked_at: UTC RFC3339 string,
  selected: [{id: decimal string, label: stored string}],
  summary: {total_matches: integer, displayed_matches: integer},
  results: [{
    id: decimal string (historical disease reference),
    label: stored string,
    display_percent: stored integer,
    match_level: stored string,
    user_match_score: stored number,
    coverage: stored number,
    reason: stored string array,
    explanation: stored string array
  }],
  disclaimer: stored string
}
```

Detail retains `meta: {api_version: "v1"}`. All IDs, including nested IDs, are strings. Scores remain numbers. Selected symptoms, results, reasons and explanations retain their persisted ordering. Labels and explanations are plain stored text; clients must render them safely as text. There are no nullable fields in this projection's stored schema; zero-result snapshots retain an empty results array and zero displayed count.

The API uses the existing bounded snapshot decoder/validator and an explicit nested whitelist. It **does not rerun the Symptom Checker, query current symptom/disease labels, recalculate scores/percentages/reasons, resort results, translate history, or repair saved rows**. Stored metadata mirrors must agree with snapshot checked_at/selected/result counts. Unsupported versions, corrupt payloads, invalid timestamps or mismatches fail with the existing safe INTERNAL_ERROR 500. The existing 262144-byte response ceiling remains; an oversized serialized response fails safely rather than truncating history or raising the limit.

The persisted artifact reproduces the saved presentation, not the full original candidate dataset or a reproducible algorithm/data-release record. Payload version is not an algorithm version. Historical Symptom Match scores are not clinical confidence, a diagnosis, current condition/risk/assessment, medical advice or a treatment recommendation. The captured educational disclaimer is returned unchanged; no current clinical facts are inferred.

Missing/invalid/expired/revoked credentials return the existing generic 401 with Bearer challenge before resource disclosure. Saved IDs must be positive decimal signed-INT values without leading zeros/signs/fractions; invalid IDs use 422. Missing and foreign-owned IDs share 404 NOT_FOUND (`Saved symptom check not found.`). Detail rejects all query parameters with 400 after bearer admission. HTTP uses 400 HTTPS required. Other methods use 405/Allow GET; HEAD has an empty body. Existing 429/Retry-After, fail-closed 503, safe 500, JSON UTF-8, no-store and nosniff remain. No session/cookie or CORS grant is introduced.

Excluded at every level: owner/account IDs, check_key/save context, raw snapshot_json, raw ranking_score, matched/missing_preview/missing_count matcher arrays, unknown JSON keys, credentials/authentication data, sharing/access/profile-membership metadata, demographics, IP/device identifiers and current master-record expansions. Detail reads no profile/current master tables. GET performs no database writes, capture, search-counter updates or browser pending-context changes; normal private limiter accounting remains.

POST/create, DELETE, PUT/PATCH, history subroutes, profile association, current matching and all other writes remain deferred. Existing browser capture/save/list/detail/delete, current checker, Dashboard and other APIs retain their existing behavior. No schema or SQL-mode change.

## Medication Checkoff — Step 59A

Exactly one new operation:

`POST /api/v1/health-profiles/{profileId}/medication-schedules/{scheduleId}/checkoff`

HTTPS, one admitted opaque access Bearer, `Content-Type: application/json`, no query parameters, and exactly `{"state":"taken"}` or `{"state":"skipped"}`. Do not send scheduled_on, timezone, medication ID, actor, timestamps or any other field. Unknown/missing/null/unsupported state values return 422. Malformed/duplicate-key/non-flat/wrong-JSON-type bodies return 400 through the existing bounded decoder; unsupported media type 415; bodies over 8192 bytes 413. No trimming or case folding of state. Both path IDs follow the existing positive canonical decimal signed-INT bounds, including no leading zeros (invalid ID 422).

Bearer admission occurs before body/domain access; it is the ordinary guarded 120/family/minute path, not logout's exemption. Missing/invalid/expired/revoked access uses existing 401/Bearer challenge; cookies and custom user headers are not identity. Existing 429/Retry-After and fail-closed 503 remain. No browser session or CSRF requirement is added; browser CSRF remains unchanged.

Active profile/membership is resolved via `findAccessible(profileId,principal.userId)`. Only owner/editor can mutate (viewer 403). Missing/inaccessible profile and missing/foreign/profile-mismatched schedule use safe 404 without disclosing another profile's resource. Schedule→medicine→profile binding is resolved on the server. The existing medicine/membership transaction lock rechecks current write permission and looks up the same slot under that parent before recording.

The server computes today from its clock in the stored schedule timezone using the shared browser eligibility method. Current medication, active slot, inclusive schedule dates and medication started_on lower bound apply. Later times today remain eligible. Ineligible occurrences return 409 `OCCURRENCE_UNAVAILABLE`, without mutation. There is no client-specified or historical-date operation. A request arriving after local midnight acts on the server's **new** local day if eligible, as required by this state-only contract; clients must reconcile the returned `scheduled_on` and must not automatically replay uncertain writes. This supersedes the 58F proposal that included a client occurrence date.

All supported transitions use HTTP 200: unset→taken, unset→skipped, taken→taken, skipped→skipped, taken→skipped, skipped→taken. No null/undo/delete state. The existing unique `(schedule_id,scheduled_on)` upsert preserves one row, its ID, initial medicine label and created_at. It updates state/recorded_by/recorded_at/updated_at, so repetition is safe for uniqueness but not strict timestamp idempotence. Concurrent explicit actions serialize; last committed action wins. This is recorded user action, not verified ingestion or medical advice.

Success uses the existing envelope and exact Due Today projection:

```json
{
  "success": true,
  "data": {
    "id": "60",
    "health_medicine_id": "2",
    "local_time": "23:59",
    "timezone": "UTC",
    "scheduled_on": "2026-10-19",
    "state": "taken"
  },
  "meta": {"api_version": "v1"}
}
```

`id` is schedule slot ID, never checkoff-row ID. Dates/times keep existing field naming and types. Projection occurs inside the domain transaction; success is published only after commit. Known denials map to safe errors; unexpected faults remain safe 500, with rollback when the operation has not committed. No raw rows, actors, database timestamps, labels, notes, prescribers, membership or credentials are serialized.

The existing Due Today GET adds only `meta.can_record_checkoffs: boolean`, true for current owner/editor membership and false for viewer. It is returned even for empty/beyond-end pages; existing pagination fields, six-field items and ordering remain. Configuration GET metadata is unchanged. The capability is a UI hint, not authorization: the POST rechecks current membership under the transaction lock. Existing clients can ignore the additive key. No Flutter change, migration or production mutation is included in 59A.
