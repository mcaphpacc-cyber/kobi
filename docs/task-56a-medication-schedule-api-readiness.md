# Step 56A — Medication Schedule / Due Today API Readiness Assessment

Date: 2026-10-17. Readiness only; no Step 56B implementation.

## 1. Executive Summary

**READY WITH NARROW SCOPE.** Recommend two read-only, profile-authorized collections: daily schedule slots and current Due Today occurrences, including existing nullable Taken/Skipped state. Exclude history, checkoff/schedule writes, notes/providers, embedded medication details and unrelated resources. No schema or authentication prerequisite is needed.

Reuse `MedicationScheduleService`'s canonical local-date eligibility, occurrence state and ordering. Its current public methods resolve browser-session actors, so they cannot simply be called from a bearer endpoint unchanged. A narrow principal-aware entry point sharing the existing Due Today assembly is required in 56B. This is routine implementation work, not a need for another readiness phase or a replacement scheduler.

## 2. Current Medication Schedule Implementation

Inspected actual source:

- `database/structure.sql` Step 45B tables and `database/medication-schedules.sql`.
- `MedicationScheduleController`, `MedicationScheduleService`, `MedicationScheduleRepository`.
- `HealthMedicineService`/repository, profile authorization services/repository, API controller/guard/principal and current profile/medication/summary/condition APIs.
- Browser routes at the beginning of `routes/web.php`; `AccountController::healthMedicines()` integration.
- Views `medication-schedule.php`, `medication-due-today.php`, `medication-history.php`, `medication-schedule-nav.php`, schedule error view and Personal Medications list.
- `tests/medication-schedules.php`, medication date tests, source of the legacy MariaDB/deployment harnesses, existing API/Dashboard regressions and Step 45B implementation report.

Existing browser routes provide per-medication schedule list/create/edit/replacement/remove, profile Due Today/history, and slot/date Taken/Skipped actions. Controller authenticates, sets private no-store headers, verifies CSRF for POST, invokes services, renders existing Bootstrap views and redirects. No scheduling AJAX endpoint or page-specific scheduler JS was found; actions are normal forms. Existing medication autocomplete is separate.

The medicine list links schedules/Due Today/history and shows `MedicationScheduleService::summary()` active-slot counts per medicine. This method counts active flags, not today's eligible occurrences. There is no schedule/Due Today integration in `HealthDashboardService` or `MedicalSummaryService`: Dashboard and Summary count current medication records, not slots or checkoffs. Step 53B remains recorded medication details only.

## 3. Actual Database Model

There is no schedule-header entity. **Each row of `health_medicine_schedule_slots` is one daily time slot**, with its own signed INT auto-increment `id`. `health_medicine_schedule_checkoffs.schedule_id` refers to that slot ID, despite its column name.

| Slot field | Actual meaning |
|---|---|
| id | Slot PK; not a universal record or separate schedule-header ID |
| health_medicine_id | Required FK to existing personal health_medicines.id |
| local_time | ASCII binary CHAR(5), HH:MM wall-clock time; not SQL TIME or UTC instant |
| timezone | ASCII binary VARCHAR(64), stored named timezone |
| starts_on | Required DATE, inclusive lower bound in slot's timezone |
| ends_on | Nullable DATE, inclusive upper bound |
| is_active | TINYINT flag constrained to 0/1 |
| created_by | Nullable user FK, SET NULL if account deleted |
| created_at, updated_at | Explicit UTC DATETIME values supplied by service |

Indexes: PK, `(health_medicine_id,is_active)`, creator. Medication FK cascades on deletion. CHECKs enforce HH:MM shape, nonempty timezone, flag domain and end >= start. The DB timezone CHECK does **not** establish that a nonempty value is a valid timezone; the service validates against PHP's timezone identifier list. There is no DB uniqueness constraint for active time/zone combinations.

| Checkoff field | Actual meaning |
|---|---|
| id | Signed INT auto-increment checkoff PK |
| schedule_id | Required FK to slot, ON DELETE CASCADE |
| scheduled_on | Required DATE: occurrence's slot-local calendar day |
| state | ASCII binary VARCHAR(7); CHECK allows exactly taken/skipped |
| medicine_label | Required VARCHAR(255), original recorded medicine label snapshot |
| recorded_by | Nullable user FK, SET NULL on account deletion |
| recorded_at | UTC DATETIME of latest correction/action |
| created_at, updated_at | UTC DATETIME, first creation/latest update semantics |

Unique `(schedule_id,scheduled_on)` is the occurrence key; actor index and binary state CHECK exist. No profile column exists in either schedule table: profile ownership is obtained through the medication. Neither table has notes, prescriber, dose, frequency, route, documents or credentials.

## 4. Schedule / Medication Relationship

Every slot references `health_medicines.id`; every checkoff references a slot. The profile path is slot → medication → `health_profile_id`. Multiple daily times and retained inactive/replaced rows can belong to one medicine. Schedules do not parse medication dose/frequency text or create governed Medicine identities.

`HealthMedicineRepository::update()` locks the parent medicine and, when status is not current, atomically deactivates active slots. Returning a medication to current does not reactivate old slots. A new explicit slot/replacement is needed. `MedicationScheduleService::eligible()` independently excludes stopped/historical medicine statuses from Due Today. Schedule-list reads still permit seeing inactive/retired slots and slots of stopped/historical medicines, as the existing browser does.

Hard-deleting a medication cascades to slots and checkoffs; this is not a permanent clinical audit archive. Removing a slot through its normal UI only retires it and preserves history. Editing also retires the old row and inserts a new row with a new ID, preserving old time/zone history.

Boundary: Step 53B is the resource for recorded brand/generic name, strength, dose, frequency, route, status and medication start/stop dates. Proposed schedule APIs expose only slot timing and an ID reference; they do not duplicate the full medication DTO or reinterpret treatment instructions.

## 5. Profile Authorization

Current browser read path: `MedicationScheduleService::actor()` → `AuthService::id()`; `profile()`/`medicine()` reuse `HealthProfileService::getProfile()` and `HealthMedicineService::getMedicine()`. The canonical profile predicate is `HealthProfileRepository::findAccessible(profileId,userId)` with active membership and active profile. Owner/editor/viewer can read; owner/editor mutate.

Future bearer path: existing guard/principal → `ApiHealthProfileService::detail()` → the same `findAccessible()` predicate → profile-bound slot/medicine join. The actor must come from `principal.userId`, never client IDs or browser sessions. Do not use creator ownership, an unscoped medication ID, a share token or `ensureSelfProfile()` for GET.

No new roles, consent grant table or authentication mechanism is necessary to preserve the existing profile read boundary. A viewer can already see Due Today state; no write permission follows from that read access.

## 6. Schedule Ownership Enforcement

Existing repository reads:

- `profileSlots(profileId)` joins slots to medicines and filters `m.health_profile_id=:profile`: suitable canonical profile-bound Due Today candidate source.
- `slots(medicineId)` filters only medication ID; browser service authorizes and binds the medication to the requested profile first.
- `slot(medicineId,slotId)` binds slot ID plus medication ID, but does not itself bind profile ID. Safe in its current guarded browser chain; it is not independently sufficient for a new bearer detail endpoint.
- `checkoff(slotId,date)` binds slot/day but relies on the preceding authorized slot chain.
- `history(profileId,offset)` joins checkoffs → slots → medicines and filters medication profile.

For proposed profile-wide schedule collection/count, add narrow API repository methods selecting explicit slot columns through `JOIN health_medicines m ON m.id=s.health_medicine_id WHERE m.health_profile_id=:profile`. Authorize the profile before either read. Do not aggregate another profile's slots and filter after retrieval.

**No slot detail endpoint is recommended for V1.** If one were later authorized, its query must additionally bind `s.id=:slot` in that same profile join; a global slot ID alone or a post-fetch profile check is insufficient for the API. Existing `slot()` would need that narrow alternative rather than being exposed directly. No such change was made in 56A.

## 7. Due Today Semantics

Exact source: `MedicationScheduleService::dueToday()` → `profileSlots(profileId)` → private `eligible(slot,medicine)` → checkoff(slotId,day) → canonical sort.

For each slot, `now()` creates a current **UTC** DateTimeImmutable, converted to that slot's stored timezone. The resulting Y-m-d is its local today. Eligibility requires all:

1. Medicine status is exactly current.
2. Slot is_active is 1.
3. Local today >= slot starts_on.
4. ends_on is null or local today <= ends_on.
5. Medication started_on is empty/null or local today >= started_on.

There is **no comparison between local_time and the current hour**. Later times today remain included. Taken and Skipped entries remain included; state annotates rather than removes them. Unmarked is `state: null`, not a stored missed/overdue/adherence outcome. Each slot is represented independently, including multiple times for the same medicine.

`stopped_on` is not an independent eligibility boundary: the query reads current status and started_on, and the canonical mutation path deactivates slots when stopping/historicizing medication. Do not invent a new stopped-date cutoff in the API.

There is no overnight window, carry-forward of yesterday's unmarked slot or elapsed-time dosing computation. After local midnight the occurrence uses the new local date, while an eligible slot in another timezone may still use yesterday. Consequently there is no single profile-wide “today” date to attach to all items.

Due Today deserves its own semantic endpoint: it is date/timezone-dependent eligibility plus current occurrence state and distinct ordering, not merely `is_active=1` on the raw slot collection. Mobile clients should reuse this server meaning instead of reproducing eligibility locally.

## 8. Date and Timezone Handling

Application config currently resolves APP_TIMEZONE to **Asia/Kolkata**; bootstrap sets the application default timezone. The browser schedule form initially suggests that configured zone and explicitly displays/accepts the selected zone. This does not override a stored slot's zone.

Schedule clock `protected now()` explicitly uses UTC; `eligible()` converts that clock to the stored timezone to obtain the local calendar date. Mutation audit timestamps use UTC. Schedule date parsing uses `!Y-m-d`, validates roundtrip/range 1000–9999, and compares date strings; time parsing accepts only HH:MM. New/replacement schedule starts must be today or later **in the selected slot timezone**, and optional end cannot precede start.

Existing `HealthMedicineService::normalizeDate()` also uses date-only `!Y-m-d`; its “today” validation follows application timezone. The schedule treats medication started_on as an existing date-only lower bound against the slot's local date; it does not convert that DATE as an instant. Do not change medication date validation as part of a schedule read API.

**Can Due Today be reused without a second interpretation of today?** The eligibility/state/sort logic can and should remain unchanged. The current public `dueToday(profileId)` method cannot be called unchanged from a session-independent bearer request because it first calls browser-only `profile()`/`actor()`. Minimal 56B adaptation: factor its existing read/eligibility/checkoff/sort body into one shared private assembler; keep browser `dueToday()` authorization and behavior, and add a principal-aware entry point that calls `ApiHealthProfileService::detail()` before that same assembler. A small API projection service then whitelists/pages the canonical result. Use the same protected clock and private eligible method, not parallel SQL/client/app-timezone eligibility.

The clock is evaluated per slot in the current implementation. Do not promise a frozen request-wide day or cross-page snapshot. Midnight between requests may change eligibility/state. DST repeated/skipped wall-clock hours still have one **slot/local-date** occurrence; no alarm instant is constructed or delivered. The existing unit suite checks local-vs-UTC midnight and repeated-hour behavior. Spring-gap behavior follows the date-only algorithm and prior documented policy; no new spring-gap runtime test was created here.

## 9. Taken / Skipped Checkoffs

`mark()` requires an authenticated browser actor, matching authorized medication/slot, exact taken/skipped state, and a submitted day equal to that slot's currently eligible local today. Past/future backfill and marking inactive/stopped/retired occurrences are rejected.

Repository mutation transactions lock/recheck parent medicine, active profile and membership with owner/editor permission. Service uses these locks for same-medicine time/zone consistency and concurrent lifecycle changes. Unique slot/day plus INSERT ON DUPLICATE KEY UPDATE prevents duplicate occurrence rows. A correction changes state, recorded_by, recorded_at, updated_at; it preserves the first medicine_label and created_at. This is latest recorded state, not an append-only action ledger.

V1 should INCLUDE only `state: null | taken | skipped` on Due Today. Exclude checkoff PK, actor, label snapshot and timestamps. Do not fabricate unmarked checkoff records, missed states or adherence percentages. Schedule collection itself is timeless configuration and should not carry a guessed day-specific state.

## 10. Checkoff History

Browser history reads explicit checkoffs only. Repository joins through medicine/profile and orders `scheduled_on DESC, slot.local_time DESC, checkoff.id DESC`, fetching 21 rows to display 20 plus has_next. Service clamps page to 1–100000. It shows original medicine_label, preserved slot time/timezone, latest state and recorded actor/time; the view converts recorded_at UTC into slot timezone for display. Retired slots remain readable until parent medication is hard-deleted.

Option assessment:

- **A, schedule + current Due Today state: recommend for V1.** Useful without adding retrospective disclosure.
- B, include bounded history: technically possible but unnecessary for the first schedule read scope and includes additional temporal/actor privacy decisions.
- **C, separate future history endpoint: defer full history this way.** Not a prerequisite or a request for another readiness phase.

No history endpoint, query/date-range override or audit claim is recommended in 56B.

## 11. Write Operation Assessment

Recommend **GET only**. Existing browser writes are understandable, but uniqueness does not establish complete mobile idempotency: identical retries keep one row yet advance latest-recorded metadata, and an older delayed Taken request can overwrite a newer Skipped correction. There is no client idempotency key/version precondition or append-only audit trail. Slot replacement changes ID, and crossing local midnight makes an offline occurrence stale.

Existing parent/member locks and unique key handle duplicate rows and serialize medicine stop/checkoff races; they should not be weakened. They do not themselves resolve offline retry intent or correction conflicts. Exclude POST Taken/Skipped, undo/delete, schedule creation/edit/removal and all API mutations from V1. Any later write scope needs explicit retry/concurrency/date semantics; no schema change or new readiness phase is proposed now.

## 12. Candidate API Routes

Recommend exactly:

- GET `/api/v1/health-profiles/{profileId}/medication-schedules`
- GET `/api/v1/health-profiles/{profileId}/medication-schedules/due-today`

The first is explicitly a collection of **daily slots**, including inactive/retired rows and slots attached to stopped/historical medicines, matching visibility of the existing per-medication browser list. It is not a new schedule-header resource. The second returns eligible current occurrences under the canonical rules in section 7, including completed state.

No slot detail route is necessary: both collections already identify their slots, and the read-only client has no separate detail expansion. No per-medication alias, occurrence-ID abstraction, history route or mutation endpoint. Use only page/page_size, no client date/timezone override, actor, arbitrary filter or sort parameter.

## 13. Proposed API Projection

Both return existing success/data-array/meta envelopes; empty arrays are successful results. Exact slot collection item:

```text
id: decimal string
health_medicine_id: decimal string
local_time: HH:MM string
timezone: stored named timezone string
starts_on: YYYY-MM-DD string
ends_on: YYYY-MM-DD string or null
is_active: boolean derived from stored 0/1
```

Exact Due Today item:

```text
id: decimal string                 (same slot ID)
health_medicine_id: decimal string
local_time: HH:MM string
timezone: stored named timezone string
scheduled_on: YYYY-MM-DD string    (canonical slot-local today)
state: null | "taken" | "skipped"
```

| Proposed field | Actual source | Sensitive? | V1 reason |
|---|---|---|---|
| id | slots.id | Yes, medication-associated resource ID | INCLUDE; not a new header/occurrence ID |
| health_medicine_id | slots.health_medicine_id | Yes | INCLUDE; existing Personal Medicines resource reference |
| local_time | slots.local_time | Yes, routine information | INCLUDE, actual daily wall time |
| timezone | slots.timezone | Yes, potentially location-associated | INCLUDE, required to interpret wall time/day |
| starts_on | slots.starts_on | Yes | INCLUDE in schedule list only |
| ends_on | slots.ends_on | Yes | INCLUDE nullable schedule boundary only |
| is_active | slots.is_active | Yes | INCLUDE boolean in schedule list; avoid treating retired rows as current |
| scheduled_on | canonical dueToday/eligible result; matches checkoff occurrence date | Yes | INCLUDE in Due Today only; no invented global schedule_date |
| state | matching slot/day checkoff.state, otherwise null | Yes, self-recorded action | INCLUDE Due Today only; no inference |

IDs must be decimal strings consistent with Steps 51–55. Due occurrence identity is naturally `(id,scheduled_on)`, matching the existing unique key; no universal ID needed. Validate controlled output time/date/zone/flag/state domains and fail safely on impossible stored values; do not silently default a bad zone to UTC or change data. Never serialize s.*, raw medicine/checkoff rows or canonical Due Today arrays wholesale.

## 14. Medication Data Duplication

Recommend **Option A: medication ID only** in these DTOs, using actual `health_medicine_id` terminology. First-party clients can resolve it through existing profile-scoped Personal Medicines list/detail, fetching all needed pages/resources as applicable. There is no ambiguity with governed Medicine IDs: it references the personal medication record.

Option B (small name) would reduce a client lookup but adds label duplication and rename consistency; not needed for this minimal read V1. Option C (full medication DTO) repeats strength/dose/frequency/route/status/date data already available and is excluded. Canonical Due Today still reads brand_name internally for its established sort; excluding it from output does not authorize changing that ordering.

## 15. Pagination

Observed normal data: **0 slots, 0 checkoffs, 0 active slots**, zero nonempty scheduled profiles, no orphan slot→medicine or medicine→profile relationships. Start/end/checkoff date aggregates are null and state distribution is empty. There are 3 existing personal medication records. This establishes no realistic upper usage bound; it is not evidence that unbounded responses are safe.

The schema has no profile-wide slot cap. Service rejects duplicate active local times and mixed active timezones per medication, but there can be many medications and arbitrarily many retired rows. Due Today is daily, yet is also not globally bounded to 20/50 entries.

Recommend pagination for **both** endpoints: page default1/max10000 and page_size default20/max50, reject larger sizes and invalid/unknown input consistent with Step 55B. Meta: api_version, page, page_size, integer total, boolean has_more. Empty/beyond-end pages return []. No top-level single today date, because rows can have different local dates.

Slot collection: use profile-bound count and minimal-column paginated SQL. Due Today: reuse full canonical eligible/state/sorted result, then slice/count it; **do not LIMIT candidates before eligibility or sort**. This preserves behavior with a small V1 adaptation. Existing assembler loads all profile slots, including inactive rows, and performs a checkoff read per eligible occurrence; output pagination does not eliminate that existing memory/N+1 cost. Accept this current-model cost for V1, document it, and do not duplicate eligibility merely to push LIMIT into SQL. No optimization/caching/schema prerequisite is established by today's empty data.

## 16. Ordering

Actual existing orders differ by read:

- `slots(medicineId)`: is_active DESC, local_time ASC, id ASC.
- `profileSlots(profileId)`: **no ORDER BY**; its raw order is not a contract.
- Canonical Due Today PHP sort: local_time ASC, live brand_name ASC, numeric slot id ASC. It compares wall-clock labels, not UTC instants across zones. Preserve the actual PHP comparison rather than substituting a different SQL collation.
- History: occurrence date DESC, slot time DESC, checkoff id DESC.

Recommendation: apply the existing slot-list tuple **is_active DESC, local_time ASC, id ASC** to the new profile-wide slot collection. This is the minimal explicit ordering for a new collection whose existing profile candidate query is unordered, and preserves the relative browser slot ordering. Due Today must use the canonical sorted result unchanged before paging. Slot PK already supplies a deterministic final tie-breaker; no new synthetic ID/name-order rule is necessary.

## 17. Privacy Boundary

| Category | V1 decision | Reason |
|---|---|---|
| Slot/medication reference IDs | INCLUDE | Required identity and link to existing medication API |
| Schedule time/timezone/effective dates/active flag | INCLUDE as specified | Meaning of recorded schedule |
| Due Today local occurrence date | INCLUDE | Necessary per-slot date context |
| Current Taken/Skipped/null state | INCLUDE | Existing explicit action state, no adherence inference |
| Medication name | EXCLUDE from new DTO | Resolve using existing Personal Medicines API |
| Strength/dose/route/frequency/medication lifecycle dates | EXCLUDE from new DTO | Separate medication projection; no duplication or dosing interpretation |
| Checkoff timestamps/history | DEFER | Unnecessary retrospective metadata for current-state V1 |
| Notes/prescriber/provider | EXCLUDE | Intentionally omitted by Personal Medicines API; schedule tables add no such fields |
| medicine_label history snapshot | DEFER | Belongs to history, not live schedule identity |
| created_by/recorded_by/timestamps | EXCLUDE | Account/admin metadata not needed for these reads |
| Profile demographics | EXCLUDE | No unrelated identity/DOB/gender/mobile projection |
| Membership/invitation/sharing/access logs | EXCLUDE | Authorization plumbing or separate sharing workflow |
| Documents/attachments/storage paths | EXCLUDE | Separate private resources; no schedule relationship needed |
| Credentials/governance/internal metadata | EXCLUDE | No exposure or joins |

Schedule timing and state are sensitive health/routine information even without names. Existing active profile membership is required for every page. No public cache, anonymous endpoint, diagnostic advice, missed-dose claim, clinical adherence score, reminder or notification is proposed.

## 18. Authentication and Authorization Reuse

Reuse existing Step 50B Bearer access guard/principal, current eligibility checks, expiry/revocation, non-logout limiter admission, session-independent bootstrap and no-store response policy. Then reuse `ApiHealthProfileService::detail()`/findAccessible before slot/count/checkoff reads. No JWT/API key/CORS/mobile verification, browser-login fallback, new token or additional role prerequisite.

Current `.htaccess` forwarding expression covers auth/identity/profile/medications/conditions/summary, **not medication-schedules**. 56B will need one narrowly scoped extension for exactly the two proposed paths; no global /api/v1 forwarding or Apache/PHP/TLS reconfiguration. This is an expected implementation edit, not a readiness blocker. No configuration changed during 56A.

## 19. API Response / Error Conventions

Use existing PublicApiResponse success/data/meta and safe error/code/message/meta; decimal-string IDs, JSON UTF-8, Cache-Control no-store, nosniff, no cookies/CORS, existing 256 KiB ceiling. New collections can reuse the Step 55B controller's opt-in paginated response path without modifying old API contracts.

Missing/malformed/invalid/expired/revoked bearer: existing generic 401/Bearer challenge. Invalid profile ID: safe 422. Missing/inaccessible/archived/revoked-member/other-user profile: identical existing 404, not a new 403 confirming existence. Authorized profile with no schedule/due rows: 200/[] with zero total. Invalid paging follows current 400/422 conventions. HTTP rejected rather than redirecting credentials; other methods 405/Allow GET, HEAD empty. Retain existing 429/Retry-After, fail-closed 503 and generic 500. Impossible stored timezone/state/time/date must fail safely without SQL/path/trace or sensitive values. No new error mechanism or private data logs.

## 20. Database / Schema Assessment

**No schema changes required.** Existing profile/medicine keys, slot indexes, occurrence unique key and five schedule/checkoff CHECK constraints support the read scope. Actual normal metadata confirms slot PK + two FKs + four CHECKs, and checkoff PK + occurrence UNIQUE + two FKs + binary-state CHECK. Service remains responsible for valid timezone identifiers and active-slot consistency beyond those DB checks. No triggers/indexes/constraints need changing.

Baseline/final inspection uses SELECT/SHOW only: safe counts, orphan counts, aggregate date/state distributions and seven normalized SHOW CREATE digests, with next AUTO_INCREMENT value excluded. No medical contents or sensitive-row hashing. Baseline normal counts: profiles14, members17, medicines3, slots0, checkoffs0, API families11, refresh history21, active API families0. Final counts/metadata/digests and session/global SQL mode match exactly. Mode remains `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`.

No normal DB writes, credentials/families, temporary production objects or disposable database were created for this assessment. No schema import or SQL-mode directive was executed.

## 21. Validation Performed

All existing database-free suites below passed unchanged during 56A:

| Suite | Passed checks/scenarios |
|---|---:|
| medication-schedules.php | 52 |
| health-medicine-dates.php | 26 |
| api-health-profiles.php | 20 |
| api-medications.php | 28 |
| api-medical-summary.php | 29 plus reused Dashboard76 |
| api-health-conditions.php | 65 |
| api-auth.php | 103 |
| api-auth-rate-limit.php | 13; private disposable files removed |
| api-identity.php | 44 |
| health-profile-validation.php | 57 plus 4 redirect scenarios |
| health-dashboard-guidance.php | 76 guidance plus rerun Dashboard dependency |
| public-api-v1.php | 56 |
| public-api-v1-bootstrap.php | 8 |
| public-search.php | 30 |

Schedule suite exercises creation/replacement/retirement, duplicate active times/zones, invalid values, wrong profile/medicine/slot, viewer/owner/editor/anonymous, future times today, medication start/current/stopped/historical boundaries, inclusive end, local midnight, DST fold, Taken/Skipped correction/label preservation, history preservation and browser CSRF failures. These are in-memory fixtures, not live mutations. Date suite confirms today/past accepted, future/malformed/order failures and optional dates for medication writes.

PHP lint passed for **10** inspected files: schedule controller/service/repository, medicine service/repository, API profile/medicine services, Dashboard, Summary and API controller. Source inspection covered routes/views/forms, canonical auth/profile/medication joins, date/time clock, DTO conventions, schema and current forwarding rule. Runtime aggregate/constraint/config-timezone inspection was read-only.

The legacy `medication-schedules-mariadb.php` was inspected, **not executed**: it requires an existing Step 45B recovery certificate/database and writes a recovery-result artifact. Its historical Step 45B report records 36 isolated MariaDB checks; that is prior evidence, not a new 56A test result. No isolated database is necessary to resolve an unclear constraint here. No tests were added/modified and no browser/HTTP audit or checkoff mutation was run. Existing browser evidence in the Step 45B report is historical only.

File-hash inventory comparison confirms only this new readiness document was added; all existing application/test/docs/schema/config/dependency files remain unchanged. No Git commands were used.

## 22. Risks / Limitations

Normal scheduling data is currently empty, so no fresh populated production/browser behavior was sampled. Canonical source, deployed constraints and existing deterministic-clock tests establish readiness; they do not constitute acceptance of an unimplemented API. Safe aggregate comparisons do not establish byte-for-byte row identity, but this task performed no normal mutations.

Bearer adaptation must not call session-based actor methods or duplicate eligibility. Due pagination bounds the response but retains existing full-profile candidate loading and per-occurrence state reads. Across-midnight/page/concurrent edits can change results; there is no snapshot guarantee. Timezone database changes follow PHP's installed timezone rules. A stored invalid zone would fail, not imply UTC. No new clinical or timezone policy is introduced.

Current write semantics are latest-state, not immutable audit or offline-intent idempotency; defer writes/history instead of expanding this read scope. An active flag alone does not mean eligible today; future/expired and stopped/historical slot configuration can remain in the raw collection. No genuine prerequisite blocks the specified narrow V1.

## 23. Recommended Step 56B Scope

Implement the two GET routes in section 12, the exact section 13 DTOs, existing bearer/profile authorization, state on Due Today only, page/page_size default20/max50/page max10000, and section 16 ordering. Preserve canonical per-slot timezone/eligibility, future-times-today and completed-entry behavior. No client-selected date, timezone override, history, writes, full medication embedding or other resource expansion. No schema change.

Planning-only likely files:

- Existing `app/Controllers/ApiAuthController.php`: two thin collection actions using existing admission and paginated response helper.
- New `app/Services/ApiMedicationScheduleService.php`: explicit slot/occurrence DTOs, paging and API profile read boundary.
- Existing `app/Services/MedicationScheduleService.php`: minimal principal-aware Due Today entry point and shared original assembler; preserve browser actor paths, protected clock, private eligibility/state/sort logic and all mutation/history methods.
- Existing `app/Repositories/MedicationScheduleRepository.php`: minimal profile-bound explicit-column slot list/count methods with canonical slot ordering; reuse profileSlots/checkoff reads for canonical Due Today.
- Existing routes/web.php and narrowly scoped public/.htaccess extension; API contract docs; new implementation report and focused tests. Existing fixture constructor wiring may need adjustment only where dependencies change.

No changes required to authentication/token/guard/limiter/session semantics, profile authorization predicates, identity, Personal Medicines DTOs, Summary/Dashboard counts, Conditions/public APIs/search, browser UI or medicine lifecycle/deactivation logic. The narrow schedule-service seam is a read adaptation, not a scheduler rewrite.

Expected 56B acceptance: exact DTO/types/privacy; missing/expired/revoked bearer; owner/editor/viewer and cross-user/archived/revoked/missing profiles; SQL profile binding and no wrong-medication slot disclosure; empty/populated/max-page ordering; active/inactive/future/expired slots and medicine states/start dates; UTC-vs-local midnight, differing zones, DST fold/gap date-only policy and multiple daily slots; null/taken/skipped retained in Due Today; no row/snapshot/log/token writes; one limiter admission; no-store/session independence/errors; verified isolated HTTPS and normal Apache denial where practical. Use existing protected clock for deterministic fixtures and compare browser/API canonical Due Today results. All fixture writes must be in guarded disposable environments with cleanup. Run relevant existing API, schedule/date, profile, Summary and Dashboard regressions unchanged. No new readiness phase is needed.

## 24. Final Readiness Decision

**READY WITH NARROW SCOPE.** Read-only daily slots plus canonical Due Today state can proceed directly to separately authorized Step 56B implementation. History, writes, extra medication/private data and notifications remain excluded, not blocking prerequisites.

Only `docs/task-56a-medication-schedule-api-readiness.md` was created. No application/test/production data/schema/config/authentication changes or Git operations. **Step 56B NOT IMPLEMENTED. Step 57 NOT STARTED.**
