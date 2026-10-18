# Step 59A — Medication Checkoff Write API

Completed: 19 October 2026. **STEP 59A: PASS — backend implementation and isolated validation.** No production deployment or mobile implementation was performed.

## 1. Objective

Implemented only today's authenticated medication checkoff action: Taken, Skipped and correction between them. Exactly one POST route was added. The Step 59A state-only request supersedes the earlier 58F proposal that included medication ID and scheduled_on. Medication/schedule CRUD, historical checkoff editing and other domain writes remain absent.

## 2. Existing Checkoff Architecture Inspected

Read the 58F review, 56B implementation report, authoritative `database/structure.sql` schedule/checkoff tables, browser `MedicationScheduleController`, `MedicationScheduleService`, `MedicationScheduleRepository`, medicine retirement interaction, profile service/repository, API guard/principal/limiter/controller/response/input/router, scoped Apache forwarding and relevant tests. Inspected the current Flutter DueEntry/PagedState contract without editing Flutter.

Existing browser behavior remains authoritative: active owner/editor membership, parent medicine locking, slot binding, schedule-local server day, inclusive effective dates, current medicine, one unique slot/day checkoff, and in-place upsert correction. Browser forms/routes/CSRF and history behavior were preserved.

## 3. Implemented Endpoint

`POST /api/v1/health-profiles/{profileId}/medication-schedules/{scheduleId}/checkoff`

Registered in `routes/web.php` using the existing authenticated controller. Existing routes were not replaced. Other methods on the new resource return 405/Allow POST; unknown resources retain 404. Only the new checkoff path alternative was added to the existing scoped Authorization rewrite in `public/.htaccess`, because otherwise the established Apache handler would not forward its Bearer header. No Apache/PHP/TLS/token/limiter configuration was changed or service restarted.

## 4. Request Contract

```json
{"state":"taken"}
```

Alternatively, exactly `{"state":"skipped"}`. HTTPS and JSON are required. No query parameters. No scheduled_on, timezone, medication ID, actor, owner, timestamps or custom fields. State is exact lowercase without whitespace trimming. IDs retain existing positive canonical signed-INT decimal syntax through 2147483647, represented as strings in responses.

Bearer admission precedes request-body/domain processing. The existing bounded flat JSON decoder rejects malformed JSON, duplicate/escaped-duplicate keys, arrays and invalid JSON value types with 400; wrong MIME is 415; body >8192 bytes is 413. Empty/missing/null/unknown/unsupported state or extra fields produce 422. No mass assignment or silent dropping of writable fields.

## 5. Response Contract

HTTP 200 for every successful initial record, same-state repetition or correction, through the existing envelope:

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
  "meta": {"api_version":"v1"}
}
```

These six fields reuse `ApiMedicationScheduleService`'s canonical Due Today projection. `id` is slot ID, not the internal checkoff row. Resulting state is taken/skipped, never null on POST success. No actors, timestamps, notes, prescriber, medication label, membership or credential internals are returned. JSON UTF-8, no-store, nosniff and safe error envelopes remain unchanged.

Projection runs inside the mutation transaction, before commit. A successful response is returned only after that transaction commits. Failed projection/upsert/commit does not produce success; rollback paths are tested. An ambiguous network failure after a committed transaction remains an uncertain client outcome, not a guaranteed rollback.

## 6. Authentication

Uses unchanged `admittedBearer(false)` → `ApiAuthGuard` → existing access admission/principal. No browser session fallback, cookies as identity, CSRF requirement, new token type, CORS permission or auth bypass. Existing token expiry/revocation and safe 401/Bearer challenge apply.

The ordinary 120/family/minute admission budget applies once per request; the action does not use logout's exemption. Existing failure controls, 429/Retry-After and fail-closed limiter remain unchanged. Limiter unit tests and real HTTPS repeated-write budget tests passed.

## 7. Authorization

Existing `HealthProfileRepository::findAccessible(profileId,principal.userId)` is reused through `ApiHealthProfileService::requireAccessible`. This new internal seam returns the authorized record for service-level role decisions; existing profile HTTP projections remain minimal. Existing stored profile-type validation remains in force.

Only active owner/editor membership may write. A viewer receives 403 without a schedule existence disclosure. Missing/inaccessible profile and foreign/missing/profile-mismatched schedule use safe 404. The repository resolves slot→medicine only within that authorized profile; no client medication ID is accepted. It then reuses `withMedicine` to lock/recheck the active medicine/profile/member relationship and re-read that same slot under the parent before mutation.

Tests include outsider, two accessible profiles with child mismatch, viewer, editor, archived/revoked profile access and membership revocation after initial authorization but before transactional admission. A vanished membership at that latter boundary is denied with 403; initial inaccessible profile uses 404.

## 8. Today's Occurrence Semantics

The unchanged `eligible` method converts the server clock to the slot's named timezone, requiring current medicine, active slot, inclusive starts_on/ends_on and any medication started_on lower bound. Later times today are still eligible. No device clock, supplied date or UTC-date shortcut determines the occurrence. Ineligible requests return 409 `OCCURRENCE_UNAVAILABLE` and do not write.

The API computes the current local day at processing time. If local midnight has passed and the slot remains eligible, a state-only request acts on the new local day's occurrence. The returned scheduled_on tells the future client exactly which day was recorded. This is the explicit Step 59A contract; it does not preserve the 58F client-date stale-occurrence check. The browser still submits its existing day and retains its stale-day rejection. No historical/future date override is exposed.

Tests cover UTC/local-date differences, local midnight, inclusive boundaries, later-today times, inactive/retired slots, future start/expired end, medication start, stopped/historical medicines and DST fold uniqueness. No scheduling or time-of-day rule was redesigned.

## 9. State Transition Semantics

| Current state | Requested state | Result / HTTP |
|---|---|---|
| Unset | taken | taken / 200 |
| Unset | skipped | skipped / 200 |
| taken | taken | taken / 200, one row |
| skipped | skipped | skipped / 200, one row |
| taken | skipped | skipped / 200, same row |
| skipped | taken | taken / 200, same row |
| Any | Any other value/null | Rejected; no mutation |

No missed, pending, partial, delayed, snoozed, undo-to-unrecorded or delete operation exists.

## 10. Idempotency Behavior

Reuses the existing unique `(schedule_id,scheduled_on)` key and `INSERT … ON DUPLICATE KEY UPDATE`. Repeated requests cannot create duplicate occurrences. No idempotency-key framework/table was introduced. Real concurrent requests retain one authoritative row; concurrent accepted actions serialize and the last committed state wins.

This is uniqueness/state idempotency, not strict timestamp or actor idempotency: repetition can update recorded_by/recorded_at/updated_at, as it does in the browser. Future clients must not automatically replay uncertain writes; reconcile with server state first. The existing API read-retry coordinator has not been extended to writes in this task.

## 11. Correction Behavior

Corrections use the same upsert, not delete/recreate. Existing row ID, initial medicine_label and created_at are retained. State, last recorded actor/time and updated_at change. History remains the current stored state per occurrence with original label/slot context; no new append-only correction audit is claimed.

Browser `mark` and principal-aware API marking share one private `recordOccurrence` implementation. Browser actor/day validation, redirects and CSRF are unchanged. The API resolves actor/date/medicine server-side and returns only the existing explicit projection. The browser Due Today/history reads observe API corrections in isolated integration tests.

## 12. Due Today Additive Metadata

Implemented exactly one additive key on existing Due Today GET: `meta.can_record_checkoffs`, boolean true for current owner/editor and false for viewer. It is present on empty/beyond-end pages. The existing pagination fields, six-field items and canonical ordering remain unchanged; schedule configuration GET metadata is unchanged.

The boolean exposes no role/member identifiers and is only a UI hint. POST independently rechecks permission under the lock. The current Flutter paging parser reads its named fields and ignores additive keys; no Flutter source/model was changed or mobile button enabled. Existing exact metadata tests were updated to assert the new key rather than weakening their projection checks.

## 13. Reused Services/Repositories

Controller → `ApiMedicationScheduleService` → principal-aware/shared `MedicationScheduleService` → `MedicationScheduleRepository`, with existing `ApiHealthProfileService` authorization. Shared Due Today projection, eligibility, `withMedicine`, slot lookup and upsert are reused. `slotInProfile` adds only the necessary server-side parent resolution query.

`withMedicine` now explicitly treats a false commit result as failure and rolls back, rather than publishing a successful callback result. Both an exception after real upsert and false commit are tested against real MariaDB. No generic transaction, scheduling, permissions or persistence framework was added.

## 14. Files Modified

Modified existing files:

- `app/Controllers/ApiAuthController.php`
- `app/Services/ApiHealthProfileService.php`
- `app/Services/ApiMedicationScheduleService.php`
- `app/Services/MedicationScheduleService.php`
- `app/Repositories/MedicationScheduleRepository.php`
- `routes/web.php`
- `public/.htaccess`
- `tests/api-medication-schedules.php`
- `tests/api-medication-schedules-http.php`
- `docs/authenticated-api-v1.md`

Created:

- `tests/api-medication-checkoffs.php`
- `tests/api-medication-checkoff-http-cases.php`
- `docs/task-59a-medication-checkoff-api-implementation.md`

The HTTP cases file is also a guarded CLI worker for real concurrent connections. The existing isolated harness invokes it inside its disposable-database lifecycle, after unchanged GET non-mutation assertions. No duplicate TLS/auth/database harness was created.

## 15. Database/Schema Impact

No migration or schema change. `database/structure.sql`, all SQL artifacts, existing triggers/indexes/FKs/CHECKs and global/session SQL mode were unchanged. Existing slot and checkoff tables are sufficient.

Mutation tests ran exclusively in hard-guarded disposable `kobi_api_auth_50b_test_20261016`, using synthetic users/profiles/medicines/slots/checkoffs/API families. The harness refuses to reuse an existing target and drops only that exact disposable name. The normal `medical1` connection is used for read-only source/isolation checks and creation/removal of the separately named test database; no normal KOBI table/data/schema mutation occurs. No real medication, checkoff or API family was created in normal KOBI.

Final read-only check: MariaDB **10.4.27-MariaDB**, disposable database count **0**. Both session/global SQL mode remain `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`. No SQL-mode directive was executed. Real CHECK rejection was exercised under that mode.

## 16. Testing

New focused database-free suite: **76 checks passed**, plus **52 reused browser schedule checks**. Includes exact projection, actor isolation with browser auth unavailable, permissions and recheck failure, validation, all six transitions, repeated correction, original history fields, eligibility/timezones/midnight/DST, rollback/error handling, unrelated state and scoped Authorization path matching.

Extended guarded MariaDB + certificate-verified PHP/cURL HTTPS harness: **1,712 checks passed overall**, including **506 focused checkoff checks**. The 506 are a subset, not additional to 1,712. Coverage includes actual route/envelope/status/header behavior; missing/invalid/expired/revoked access; cookies insufficient; request validation/duplicate keys/extra fields/size/MIME; owner/editor/viewer/foreign access; all transitions/unique row identity; later-time/local-date eligibility; real upsert; concurrent connections; medication stop and membership revocation races; rollback after upsert and false commit; browser visibility; direct binary CHECK rejection; safe stored-timezone failure; repeated-write limiter/429; unchanged unrelated fixture data, constraints and SQL mode. Expiry/revocation POST assertions also run after the focused cases.

Each HTTPS client request enables peer verification and hostname verification, with `CURLINFO_SSL_VERIFYRESULT=0`. A generated dedicated test certificate is used only by the loopback fixture; host trust/TLS configuration is not altered. All test server/proxy processes, fixture credential families, disposable database, certificate and limiter files were cleaned up.

Two harness issues were corrected without weakening application requirements: the initial schema comparison included the naturally advancing AUTO_INCREMENT table counter, so it now excludes only that runtime option while comparing all other DDL; the added revocation race initially could occur before initial authorization, so a worker barrier now proves it reached transactional admission before revocation commits. Final assertions test the required under-lock denial. The first sandboxed socket attempt was denied before fixture creation; the guarded tests then ran with approved socket access. No production workaround was used.

## 17. Regression Results

Executed existing suites, all exit 0:

| Suite | Passing checks/scenarios |
|---|---:|
| `api-auth.php` | 103 |
| `api-auth-rate-limit.php` | 13; private temporary limiter files removed |
| `api-identity.php` | 44 |
| `api-health-profiles.php` | 20 |
| `api-medications.php` | 28 |
| `api-medical-summary.php` | 29 + 76 reused Dashboard |
| `api-health-conditions.php` | 65 |
| `api-saved-symptom-checks.php` | 58 |
| `health-dashboard-guidance.php` | 76 guidance + 76 reused Dashboard |
| `health-profile-validation.php` | 57 checks + 4 redirect scenarios |
| `api-medication-schedules.php` | 71 + 52 reused browser schedule |
| `health-medicine-dates.php` | 26 |

The combined schedule HTTPS run also preserves its existing read, pagination, profile-boundary, browser-domain equivalence, identity/profile/medicine/summary route, limiter, expiry and revocation checks. No old assertions were removed to obtain PASS; metadata expectations were intentionally extended for the approved additive key.

PHP syntax: **all 10 changed/new PHP files passed** (five classes, route file and four test files). Apache `httpd.exe -t`: **Syntax OK**; no restart/configuration edit. Separate positive/negative tests verify the new scoped `.htaccess` path alternative and exclusion of unrelated paths. `httpd -t` validates main configuration, not a live per-directory Bearer request; no normal-Apache checkoff write was attempted.

Patch/source/reference review and SHA-256 scope comparison were used without any Git command. Baseline: 679 existing inventoried files (excluding `.git`, vendor, storage and ignored/generated caches). Final inventory: 682 files, with only the 10 listed existing-file changes, 669 byte-identical existing files, no unexpected changes and no removed files. Added files are precisely the three listed above. Flutter, schema/configuration outside the permitted `.htaccess` path edit, and unrelated application source remain byte-identical to baseline. Final cleanup inspection found zero fixture directories and zero listeners on fixture ports 18865/18866. The report contains all 22 required sections. No application/test credentials were printed.

## 18. Security/Privacy Review

Existing opaque Bearer admission, principal, limiter, HTTPS, input bounds, safe errors and response allowlists are retained. No session/CSRF bypass for browser operations and no second identity mechanism. Profile→schedule→medicine relationships are resolved and rechecked server-side. Capability metadata is not an access grant. Actor/time/date/label are never client-writable.

Unexpected database/projection failures are not translated by arbitrary exception message; only exact known domain RuntimeException denials map to public API statuses. No private data appears in success/error projections. Explicit Taken/Skipped is only a recorded user action, not verified adherence, clinical inference, medication recommendation or dosage change. Medication identity/frequency/dose/status and schedule configuration are not mutated by the action.

## 19. Deferred Step 59B/59C Work

No Flutter edits, action buttons, write transport support, mutation ViewModel, native label lookup, capability consumption, confirmation UI, pending/error reconciliation or mobile acceptance. Step 59B must identify medicine/profile/slot clearly and handle server-returned day, permission changes and uncertain outcomes without automatic replay. Step 59C remains integrated mobile acceptance on an approved trusted environment/device. Neither step started.

## 20. Production Deployment Status

Not performed. No production migration, data mutation, authentication family or live checkoff fixture. No TLS/certificate/Apache/PHP/limiter configuration change, restart or certificate-verification bypass. The only hosting-path source adjustment is the necessary scoped Authorization alternative in project `public/.htaccess`, ready for later operator-reviewed deployment with the endpoint.

## 21. Limitations

Verified HTTPS integration used the existing disposable PHP server/loopback TLS harness, not a normal-Apache health-data POST. Apache syntax and the scoped rule were checked separately. Actual mobile/device/runtime acceptance and live hosting activation are outside 59A. No claim of production/native acceptance, immutable correction audit, strict timestamp idempotency or offline replay protection is made.

The API deliberately targets the server's current local day without accepting an occurrence date. Future mobile presentation must reconcile the returned day and reload on uncertainty; it cannot assume a stale card still refers to the same day. This behavior follows the explicit 59A requirement and is documented in the API contract.

## 22. Final PASS/BLOCKED Status

**STEP 59A: PASS.** The single backend write and additive Due metadata are implemented and validated using the existing schema/domain rules. No production DB mutation, schema change, Flutter change, Git operation or unrelated domain change occurred. Step 59B and Step 59C remain deferred. **STOP after 59A.**
