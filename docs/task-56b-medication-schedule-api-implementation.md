# Step 56B — Authenticated Medication Schedule / Due Today API

Date: 2026-10-17. GET-only; no history, checkoff writes or schema changes.

## 1. Implementation Summary

Added exactly two private read collections. They reuse existing bearer admission, active profile membership, Step 45B slot storage and canonical Due Today business logic. Responses are bounded, explicitly projected and session-independent. No scheduler redesign, medication details, browser UI, clinical interpretation, API mutations or normal-database writes.

## 2. Routes

- GET `/api/v1/health-profiles/{profileId}/medication-schedules`
- GET `/api/v1/health-profiles/{profileId}/medication-schedules/due-today`

The static due-today route is registered explicitly. No dynamic schedule-ID route exists; Router's anchored matching is unchanged. Unknown slot/history/mark/checkoff subroutes return 404. Other methods on the two known routes retain 405/Allow GET and empty HEAD body.

## 3. Authentication

Thin controller actions call the existing `admittedBearer(false)` once, using ApiAuthGuard/ApiAuthService and the authenticated principal. Existing parsing, account eligibility, expiry/revocation/refresh lifecycle and limiter remain unchanged. No browser session, cookie identity, new token/JWT/key/CORS or CSRF requirement for GET. Existing generic 401/Bearer challenge, 429/Retry-After and fail-closed behavior remain. Existing controller paginated response helper is reused without changing it.

## 4. Profile Authorization

`ApiMedicationScheduleService::collection()` calls existing `ApiHealthProfileService::detail()` → `HealthProfileRepository::findAccessible(profileId,principal.userId)` before paging validation or source reads. Active profile/member predicates are unchanged; owner/editor/viewer read access remains. Missing, inaccessible, archived, revoked-member and other-user profiles share the existing 404. Invalid positive decimal signed-INT profile IDs retain 422. Body, query, cookies and custom actor headers cannot override identity.

Count/list SQL joins slots to medicines and binds `m.health_profile_id=:profile`. Due Today candidates use existing `profileSlots(profileId)`, with that same profile-bound join. Subsequent checkoff lookup is only for those authorized candidate slot IDs and their canonical local dates. Slot/medication IDs alone never authorize a query.

## 5. Principal-Aware Schedule Eligibility

Existing `MedicationScheduleService::dueToday()` retains its browser actor/profile check and now delegates to private `assembleDueToday(profileId)`. That private method contains the original candidate read, unchanged eligible() calls, slot/day checkoff lookup and PHP sort. New `dueTodayForPrincipal(principal,profileId)` uses injected ApiHealthProfileService to authorize/recheck access before invoking the same assembler, without calling a browser actor method.

API service validates profile/paging first; Due Today then rechecks profile membership at the canonical service boundary. These are two profile reads, **not** two bearer/limiter admissions. Browser mutations, history, slot replacement/retirement, protected UTC clock and private eligible() implementation are unchanged. No parallel eligibility SQL, UTC/app-date substitution or client-side calculation.

The constructor gained only the API profile-service dependency. Existing browser/legacy test constructors received dependency wiring; assertions were not weakened.

## 6. Schedule Collection

Lists daily slot configuration for the authorized profile, using new narrow countApiSlots/listApiSlots methods in the existing MedicationScheduleRepository. Selects exactly seven returned columns. Existing browser repository methods remain unchanged.

As approved in 56A and in the existing browser slot list, raw configuration includes inactive/replaced rows, future/expired ranges and slots attached to stopped/historical medications. `is_active` does not by itself promise eligibility today. Due Today applies the unchanged occurrence eligibility. Filtering both collections to current eligible rows would remove the approved configuration visibility and make inactive flag handling meaningless.

No header schedule entity, generic medication abstraction or universal occurrence ID was introduced. id is the slot ID and health_medicine_id references the personal medication resource already available through Step 53B.

## 7. Due Today Semantics

Canonical eligibility requires current medicine, active slot, slot-local day >= starts_on, <= optional ends_on, and >= any medication started_on lower bound. No current-hour filter; later scheduled times remain included. Taken/Skipped entries remain represented. Null state means no explicit checkoff for that slot/day, never missed/overdue or inferred adherence.

Each daily time is independent. Stopped/historical medication and inactive/future/expired slots are excluded from Due Today. Existing stopped_on is not a separate new cutoff; canonical medicine lifecycle/deactivation behavior is untouched. No carry-forward of yesterday's unmarked occurrence or clinical dose computation.

## 8. Timezone and Effective-Date Handling

Protected canonical now() uses UTC and eligible() converts to each stored timezone before deriving YYYY-MM-DD. Application default timezone is not substituted. starts_on/ends_on are inclusive; a profile may have simultaneous occurrences with different scheduled_on dates. No global “today” response field, client date/zone override or invented UTC alarm instant.

Actual Step 45B local_time is CHAR(5) **HH:MM**, preserved as such in the API (e.g. 08:00). The request's illustrative 08:00:00 shape is not the stored model; seconds are neither stored nor added. The approved 56A actual representation and canonical wall-time meaning are preserved.

Output dates validate as real YYYY-MM-DD calendar dates; nullable ends_on stays null. Output time/zone/flag/state domains fail safely on impossible stored values, without repair/fallback. The canonical clock still runs per slot; cross-midnight/cross-request snapshot consistency is not newly promised. Deterministic test subclasses override only the existing protected clock in isolated test contexts, never production clock handling.

## 9. Taken / Skipped State

Due Today exposes only null, taken or skipped from canonical slot/day state lookup. No checkoff ID, actor, recorded_at/created_at/updated_at, label snapshot, audit metadata or history. Current state does not remove completed entries. No checkoffs are created for unmarked days or through API reads. Existing browser action behavior and unique occurrence model remain unchanged.

## 10. Pagination

Both endpoints use existing page/page_size convention: default page1/size20, maximum page10000/size50. Invalid ranges/formats, oversize and unsupported keys return 422; arrays/non-string/invalid UTF-8 values return 400. Reject unknown date/timezone/user/filter inputs. Larger sizes are rejected, not accepted as an unbounded request. Repository slot reads additionally clamp integer limits/offsets defensively, following existing BaseRepository/public API convention.

Success is the existing success/data/meta envelope. Metadata: api_version v1, integer page/page_size/total and boolean has_more. Empty authorized profiles and pages beyond the end return []. Slot configuration count/list are profile-bound SQL. Due Today is canonically assembled/filtered/sorted first, then counted/sliced; pre-limiting candidates would omit valid occurrences and is not done. Separate reads/pages can observe concurrent changes.

## 11. Ordering

- Schedule configuration: existing slot tuple `is_active DESC, local_time ASC, numeric id ASC`, applied profile-wide as approved in 56A. The old profileSlots candidate query was unordered, while browser slots already used this tuple.
- Due Today: unchanged canonical PHP tuple `local_time ASC, brand_name ASC, numeric slot id ASC`. Brand name is used internally only. No change to comparison collation/meaning or UTC conversion across zones. Pagination follows that completed ordering.

## 12. API Projection

Exact schedule item:

```text
id: decimal string
health_medicine_id: decimal string
local_time: HH:MM string
timezone: named timezone string
starts_on: YYYY-MM-DD string
ends_on: YYYY-MM-DD string | null
is_active: boolean
```

Exact Due Today item:

```text
id: decimal string
health_medicine_id: decimal string
local_time: HH:MM string
timezone: named timezone string
scheduled_on: YYYY-MM-DD string
state: null | taken | skipped
```

No additional detail fields or generic status. Both IDs are explicitly cast to strings. is_active maps only stored 0/1 to boolean. Explicit whitelist is built by ApiMedicationScheduleService, never a raw slot/checkoff/medicine row or full canonical array.

## 13. Privacy Boundary

No medication names/brand/generic/strength/dose/frequency/route, notes, prescribers/providers, checkoff identifiers/actors/timestamps, profile demographics, documents/attachments, membership/sharing/access logs, credentials/authentication/governance internals or other clinical categories. Canonical assembly may load its existing internal slot/medicine/checkoff fields; only approved projection reaches JSON. No sharing/document/summary service or access-log write is used.

All success/errors retain JSON UTF-8, Cache-Control no-store, nosniff, safe envelopes, no session cookies/CORS. Unexpected timezone/projection/database errors use existing safe 500, never SQL, values, paths or trace. Profile and bearer error conventions remain unchanged. Existing limiter still performs its private file accounting; GET performs no database writes.

## 14. Deferred Scope

No history, slot detail, schedule/checkoff POST/PUT/PATCH/DELETE, mark-taken/mark-skipped/checkoff actions, schedule editing/removal, historical date queries, offline/idempotency model, reminders/notifications, medical recommendations or additional resource. Browser create/edit/retire/mark/history, medication editing and deletion are unchanged. **Step 57 not started.**

## 15. Database / Schema

No schema changes: no structure.sql/artifact change, migration, table/index/trigger/constraint modification or SQL-mode directive. Existing schedule/checkoff tables and constraints are used. Normal KOBI receives SELECT/SHOW only for integrity checks. Synthetic fixture DDL/writes occur exclusively in the hard-guarded disposable database, never normal KOBI.

## 16. Files Changed

Modified:

- app/Controllers/ApiAuthController.php
- app/Services/MedicationScheduleService.php (principal-aware/shared assembly seam only)
- app/Repositories/MedicationScheduleRepository.php (API slot count/list additions only)
- routes/web.php
- public/.htaccess
- tests/api-identity.php (constructor setup only)
- tests/medication-schedules.php (constructor setup only)
- tests/medication-schedules-mariadb.php (constructor setup only)
- docs/authenticated-api-v1.md

Created:

- app/Services/ApiMedicationScheduleService.php
- tests/api-medication-schedules.php
- tests/api-medication-schedules-http.php
- docs/task-56b-medication-schedule-api-implementation.md

The existing Authorization forwarding expression did not cover medication-schedules. Its necessary change was explained before editing; one narrow alternative now matches only the collection and literal due-today path. It does not match slot IDs/history/deeper paths or broaden all /api/v1 routes. Existing auth/profile/medicine/condition/summary scopes remain. No other Apache/PHP/TLS/limiter/environment/dependency configuration edit or restart.

## 17. Tests and Validation

- **71 focused API unit checks PASS**, plus **52 unchanged browser schedule checks** reused as fixture/regression support.
- **1186 isolated MariaDB/verified-HTTPS checks PASS**, including real repository reads under a deterministic protected domain clock and real application bootstrap via loopback HTTPS. Counts include per-row DTO and per-request transport assertions, not independent clinical cases.
- Unit fixtures cover exact 7/6-field DTOs, string IDs/boolean flag, paging, active/inactive/future/expired/stopped/historical/lower-bound behavior, slot-local dates, midnight, inclusive end/start, later times, completed Taken/Skipped/null state, browser/API parity, no browser-login dependency, denied profile/paging before schedule reads, invalid output domains and SQL profile predicates/minimal columns.
- DST unit fixtures cover both repeated hour and skipped wall-time hour, preserving one daily slot/local-date entry without an alarm instant.
- MariaDB deterministic clock uses 2026-10-17T00:30Z with UTC, Asia/Kolkata and America/New_York slots, distinct local dates, states and excluded boundaries; 04:00Z and next UTC midnight prove end expiry/start inclusion. Compares canonical browser/API reads over the same actual SQL fixture.
- HTTPS fixtures use 61 configuration slots/55 due occurrences under the main profile, plus owner/viewer/editor/other-user/archived/revoked/empty and clock-specific profiles. Exercises default20/max50, partial/beyond-end pages, ordering, types, exact DTOs, token failure/expiry/revocation, generic errors, query/method/alias rejection, no browser/body/header actor override, safe invalid-zone 500, one limiter admission/429, and byte-equal synthetic table snapshots across GETs.
- All **10** changed/new PHP files pass lint. Apache syntax OK. **22** forwarding scope cases PASS. Router inspection and unsupported-path requests establish no due-today/dynamic-ID collision. Source/call-site/patch inventory review is scoped; no Git operation was used.

No existing tests were changed to weaken assertions. Three existing test files only received required constructor dependencies. The legacy recovery-database MariaDB script was syntax-checked but not executed: its recovery database/certificate and report-writing lifecycle are not needed here; the new SQL/HTTPS suite provides scoped disposable evidence without using that historical recovery environment.

## 18. HTTP / HTTPS Validation

Normal Apache HTTPS collection and due-today: **401** exact generic V1 denial, no-store, Bearer challenge, no cookies/CORS. PHP/cURL verifies peer and hostname (SSL_VERIFYPEER true, SSL_VERIFYHOST 2); **TLS verification result 0** on both, using existing trust and no redirect/bypass.

Authenticated success, principal/profile authorization, collection/Due Today/pagination/status/envelopes and session independence pass in the guarded real-bootstrap HTTPS environment. Test client verifies its ephemeral localhost certificate; production TLS settings were not modified. No normal production credential family was created or password requested. Authenticated normal-Apache success was not exercised; no TLS/Apache bypass was used to claim it.

## 19. Regression Results

Completed database-free suites all PASS:

| Suite | Checks/scenarios |
|---|---:|
| medication-schedules.php | 52 (also reused by focused unit) |
| health-medicine-dates.php | 26 |
| api-auth.php | 103 |
| api-auth-rate-limit.php | 13 |
| api-identity.php | 44 |
| api-health-profiles.php | 20 |
| api-medications.php | 28 |
| api-medical-summary.php | 29 + reused Dashboard76 |
| api-health-conditions.php | 65 |
| health-profile-validation.php | 57 + 4 redirect scenarios |
| health-dashboard-guidance.php | 76 guidance + rerun Dashboard dependency |
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
| api-health-conditions-http.php | PASS 1106 |
| api-auth-mariadb.php | PASS 65 |
| api-auth-http.php | PASS 326 |
| public-api-v1-http.php | PASS 561 |

No new browser UI or normal schedule mutation audit was performed; unchanged browser flows are covered by existing service/controller tests and canonical parity, not a claim that all browser interactions were repeated.

## 20. Production Data Integrity

Safe baseline: profiles14, members17, medicines3, slots0, checkoffs0, existing API families11, refresh history21, active API families0. No medical row samples/hashing; schema digests use normalized SHOW CREATE (excluding only next AUTO_INCREMENT values), not mutable statistics.

Final SELECT/SHOW-only comparison: **all seven table counts, relationship/date/state distributions and seven normalized table-definition digests match the baseline exactly**. Slots/checkoffs remain **0/0**, medicines **3**, existing API families/refresh history **11/21**, active API families **0**. Session/global SQL mode remains **NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION**. No normal production INSERT/UPDATE/DELETE/DDL or authentication lifecycle was performed.

All fixture DDL/writes were confined to guarded `kobi_api_auth_50b_test_20261016`; suites refuse an existing target and ran serially. New synthetic API families were revoked through normal isolated lifecycle. Final SELECT confirms the disposable database absent; invocation-owned HTTPS directories are absent. Harness finally blocks removed their loopback servers, ephemeral certificate/key and private counter files. No normal profile/medication/slot/checkoff/user/session row was seeded, edited or deleted.

File-hash inventory confirms exactly the nine modified and four created files listed above, no deleted/unrelated files. In particular structure.sql and schedule DDL, .env/dependencies, browser views, medication lifecycle repository/service, existing API profile/medicine/summary/condition services, Dashboard, auth/token/guard/limiter implementations and unrelated governance code remain unchanged. Only the documented narrow .htaccess extension changed configuration.

## 21. Limitations

Due Today keeps existing full-profile candidate loading and per-eligible-slot state queries; response pagination does not eliminate those internal costs. No large-profile optimization, cache or independent SQL eligibility was added. Count/page reads are not atomic snapshots, and canonical local date can change across midnight/requests. Brand name remains an internal sort key only. API service authorizes before pagination, and canonical Due Today rechecks profile access before reading; neither relies on a browser session.

Authenticated normal-Apache success remains untested; verified isolated success and normal anonymous HTTPS behavior are separately documented. Safe aggregate checks do not prove byte-for-byte identity of all production rows, but no normal mutation was executed. Existing historical recovery-script execution was not repeated. No other feature was implemented.

## 22. Final Status

**PASS.** Both approved GET routes, canonical principal-aware Due Today logic, profile binding, exact projections, per-slot timezone/effective dates, completed-state retention, bounded pagination, ordering, privacy, focused/regression tests, HTTPS/TLS and cleanup checks pass. No history/checkoff writes/schema change. No normal production data mutation or SQL-mode change. **Step 57 NOT STARTED. Git operations: NONE.**
