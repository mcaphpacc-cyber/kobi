# Step 58D — Mobile V1 Daily Health & Saved Symptom Checks

Implementation date: 18 October 2026. **Status: PASS WITH LIMITATIONS.** Read-only implementation, automated tests, formatting, static analysis and Android build passed. Device/live integration limitations remain explicit.

## 1. Scope and exact file inventory

Added Medication Schedule, Due Today, Saved Symptom Checks list and Saved Symptom Check detail to the existing Flutter app. Existing authentication, core health screens, profile selection and native security settings are reused. No backend or health write capability was added.

Modified:

- `mobile/kobi_mobile/lib/navigation/foundation_shell.dart`
- `mobile/kobi_mobile/lib/features/health/health_screens.dart`

Created:

- `mobile/kobi_mobile/lib/features/health/paged_state.dart`
- `mobile/kobi_mobile/lib/features/health/paged_view.dart`
- `mobile/kobi_mobile/lib/features/daily_health/schedule_models.dart`
- `mobile/kobi_mobile/lib/features/daily_health/daily_health_state.dart`
- `mobile/kobi_mobile/lib/features/daily_health/daily_health_screen.dart`
- `mobile/kobi_mobile/lib/features/saved_checks/saved_models.dart`
- `mobile/kobi_mobile/lib/features/saved_checks/saved_state.dart`
- `mobile/kobi_mobile/lib/features/saved_checks/saved_screen.dart`
- `mobile/kobi_mobile/test/daily_support.dart`
- `mobile/kobi_mobile/test/daily_models_test.dart`
- `mobile/kobi_mobile/test/daily_state_test.dart`
- `mobile/kobi_mobile/test/daily_widgets_test.dart`
- `docs/task-58d-mobile-daily-health-implementation.md`

No dependency, lockfile, native runner, session coordinator, secure store, Login, Account, backend or existing test source changes were needed. Ignored tooling contains task logs, a protected-file SHA-256 baseline and original copies of the two edited mobile files. Compiler/test caches and the debug APK are generated artifacts, outside the source inventory.

## 2. Medication Schedule

Typed slots retain exactly `id`, `health_medicine_id`, `local_time`, `timezone`, `starts_on`, `ends_on` and `is_active`. IDs remain decimal strings internally. Cards display the recorded local time/zone, Active/Inactive and calendar effective dates, preserving server ordering and inactive rows. Null end dates show “Not recorded.” No medicine-name lookup/join is performed: the neutral label is “Scheduled medication,” and internal references are not shown as names or exposed unnecessarily. Recorded schedules are explicitly distinguished from medical recommendations.

## 3. Due Today

Typed occurrences retain slot ID, medicine reference, wall time, zone, `scheduled_on` and nullable `state`. Null displays “No checkoff recorded,” taken displays “Taken,” skipped displays “Skipped.” All returned entries remain visible, including later times and completed/skipped records. No device-clock filtering, local eligibility calculation, sorting, overdue/missed inference or state-changing action exists.

The screen shows each occurrence's actual scheduled date because different zones can yield different dates in one response. Server time/date eligibility remains authoritative. Refresh starts a new first page, hiding old results while loading. Entering a daily screen performs a fresh read; existing app background/resume privacy behavior discards the private scope. When paging observes a changed date for a previously encountered zone, or an unexpectedly empty subsequent page, it restarts at page one instead of appending incompatible occurrences. A detected rollover clears old records even if replacement loading fails. Occurrence identity for deduplication is slot ID plus scheduled date.

## 4. Saved Symptom Checks

The account-level list parses `id`, `checked_at`, `saved_at`, `selected_symptom_count` and `displayed_result_count`. It displays check/save times and counts without inventing symptom names. Backend checked-date/ID ordering is retained.

Detail parses the version-1 persisted snapshot, including selected symptom labels, summary counts, ordered results and disclaimer. Results show stored label, display percentage with “Symptom Match,” match level, user match score, coverage, reason lines and explanation lines. IDs remain internal. Selected symptoms, results and text arrays retain their persisted order; a higher saved percentage does not cause re-ranking. The original disclaimer is rendered verbatim. No current disease-label lookup, live matcher, score calculation or symptom-counter increment occurs. Unsupported snapshot versions, malformed fields or inconsistent metadata produce a safe error instead of reconstructed history.

## 5. Profile isolation

`DailyHealthScope` is created from the existing validated `HealthScope`, not an ID supplied by navigation. Every schedule/Due Today request uses that scope's profile ID through `SessionController.authorized`. Backend principal/profile checks remain authoritative.

Changing profile immediately disposes both daily pagers along with the previous core-health scope. Generations/disposal checks prevent pending old-profile responses from repopulating state. When switching from Schedule or Due Today, only the destination intent is retained: the previous records/Back stack disappear; selection is revalidated; the new profile's daily page loads from scratch. No previous record or profile ID is carried into the new request. Profile 403/404 recovers through the existing selector mechanism.

## 6. Account-owned saved-check state

`SavedChecksController` lives in the authenticated shell, outside either profile scope. Its paths accept no profile ID/filter. It remains usable with zero accessible profiles, multiple unselected profiles, and after profile switches or profile-access loss. The Saved screens identify the records as account-owned and omit the selected-person banner.

Logout/session loss clears the list/detail; disposal invalidates pending requests. Changing saved detail ID supersedes an earlier in-flight detail response. All reads reuse the existing bearer principal and session epoch/refresh coordinator; no account ID comes from the UI.

## 7. Timezone and date handling

Schedule `local_time` is validated as HH:MM and displayed unchanged alongside its server-provided timezone. `starts_on`, `ends_on` and `scheduled_on` reuse the validated calendar-date type, formatting date components without timezone conversion. In particular, `08:00 Asia/Kolkata` stays `08:00 Asia/Kolkata`; a New York occurrence can show a different calendar day and a later `23:59` time.

Saved timestamps follow the current API's RFC3339 UTC `...Z` projection. Their exact wire strings and UTC instants are preserved internally; only presentation converts to device local time. Screens explicitly label this display convention. No timezone dependency, clock override, backend timezone change, alarm or notification was introduced.

## 8. Pagination and API mapping

All three collections use the actual page metadata. Requests send only `page` and `page_size=20`; responses must contain valid integer page/page size/total and boolean `has_more`. Load More preserves server order and deduplicates stable record identities. Refresh resets paging; requests are serialized; page 10001 is never sent. A page-limit message appears if necessary. Ordinary append failure preserves the prior loaded page with a visible error and retry; known Due Today rollover invalidates the prior day instead.

| Screen | Existing GET endpoint relative to HTTPS `/api/v1` |
|---|---|
| Medication Schedule | `/health-profiles/{selectedProfileId}/medication-schedules` |
| Due Today | `/health-profiles/{selectedProfileId}/medication-schedules/due-today` |
| Saved list | `/saved-symptom-checks` |
| Saved detail | `/saved-symptom-checks/{savedCheckId}` |

No new endpoint, query contract, response envelope or fabricated count/cursor was introduced. No new transport or authentication implementation was needed.

## 9. Navigation

The existing shell now has Home, Health, Saved and Account destinations. Health includes Schedule and Due Today through the profile hub. Saved is an independent account-level destination, including when no health profile exists. Saved detail has predictable Back-to-list behavior. Profile switching removes the old profile stack, and logout removes all private navigation. The completed Account Logout/Logout All controls are unchanged.

## 10. Dashboard integration

Added navigation cards for Due Today, Medication Schedule and Saved Symptom Checks. Due Today is placed near the top of Dashboard. Existing aggregate counts and Conditions/Medicines/Profile/Summary navigation remain intact. No badge count or dashboard preview fetch was added solely for these links.

## 11. Loading, empty, error and retry behavior

All four new screens have deliberate progress indicators, successful empty states and safe errors/retry. Schedule empty means no recorded schedules for the selected profile. Due empty means no schedule entries returned for today, explicitly not no medicines. Saved empty means no account-owned saved checks.

Network/server failures are not represented as successful empty data. 401 uses the existing shared refresh/one-retry/session termination mechanism. Profile 403/404 invalidates selection; unavailable saved detail remains a resource error without changing profile access. Raw PHP/SQL/backend text never reaches the UI. Retry-After disables premature retries. There are no infinite retry loops or automatic uncertain state-changing retries.

## 12. Privacy and read-only boundary

No health labels, schedules, snapshots, reasons, explanations, payloads, credentials or profile information are logged or sent to analytics. No persistent health cache or ordinary preferences are used. Text is rendered as Flutter text, not HTML or executable links. String IDs are retained safely and kept out of normal UI.

No Taken/Skipped button, schedule/medicine CRUD, saved-check create/delete/re-run, health editing or write request was added. New repositories issue GET only. Existing HTTPS validation, origin restrictions, no-redirect behavior, bounded response parsing, secure storage and session lifecycle remain intact. No TLS bypass or HTTP fallback exists.

## 13. Tests and regressions

Tests use injected HTTP mocks, in-memory credentials and synthetic `.invalid` fixtures. Final complete run: **129 tests passed**, exit 0, including all 87 existing 58B/58C tests unchanged.

| Mobile tests | Count / coverage |
|---|---|
| Existing 58B/58C | 87; unchanged transport/auth/storage/core-health/profile/navigation tests |
| `daily_models_test.dart` | 11; exact projections, string IDs, dates/times/zones, all states, frozen snapshots, ordering, UTC/local display, malformed/version errors and pagination |
| `daily_state_test.dart` | 17; loading/success/empty/error/retry, paging, later times, per-zone dates, day-rollover reset and reset failure, profile races/access loss, independent saved ownership/detail/logout, 401/429 |
| `daily_widgets_test.dart` | 14; all four screens, Dashboard links, two viewports, inactive/completed/later slots, loading/empty/error/retry/paging, profile races, zero-profile Saved, historic ordering, long text and logout |
| **Total passed** | **129** |

Existing database-free backend regression processes all exited successfully:

| Suite | Passing checks |
|---|---:|
| `tests/api-auth.php` | 103 |
| `tests/api-identity.php` | 44 |
| `tests/api-health-profiles.php` | 20 |
| `tests/api-medications.php` | 28 |
| `tests/health-medicine-dates.php` | 26 |
| `tests/api-medication-schedules.php` | 71 + 52 reused browser schedule checks |
| `tests/api-saved-symptom-checks.php` | 58 |
| `tests/saved-symptom-checks.php` | 63 |
| **Total** | **465** |

The PHP suites use memory repositories/fixture support, including medication-date and existing saved-check/schedule behavior. No live/MariaDB/deployment/auth-family harness was executed. No existing assertion was removed, weakened or edited. New unit/widget cases print only synthetic test labels; no production health payload was accessed.

## 14. Formatting and static analysis

Final formatting verification **passed**: 38 Dart files, zero changes. Final Flutter static analysis **passed with no issues**, exit 0. New-test brace-style diagnostics were corrected without changing assertions. There were no PHP edits; modified-PHP syntax checking is not applicable.

## 15. Android build

Final Android debug build **passed**, exit 0; final `assembleDebug` completed in **15.1 seconds** after final source/tests. Uses the existing project-local SDK, dependencies and `tool/flutter.ps1`; no package upgrade or native configuration change was required. Compilation configuration deliberately uses `KOBI_ENV=local` and `API_BASE_URL=https://fixture.example.invalid/api/v1`. This is not a live-integration or release-ready configuration.

Artifact: `mobile/kobi_mobile/build/app/outputs/flutter-apk/app-debug.apk`, **181338514 bytes**, SHA-256 **726439B843176CE0F4C9F7EFE816AC34D9362164337C6DB711591E8FC489767B**. An earlier successful build emitted the existing SDK XML metadata-version warning; the final incremental build completed without it. No SDK/dependency workaround or production configuration change was made.

## 16. Emulator/device and responsive verification

Project-local `adb devices` succeeded and returned no attached device/emulator. The adb server started for that inventory was stopped afterward. No emulator was created or app installed.

Widget verification exercises the four new screens at **390×844** and **768×1024**, and separately checks 1.6× text scaling/long historical explanations and labels. Times/timezones/status remain readable; text wraps; content and controls are scrollable; states use text rather than color alone; headers/loading/errors have semantics and standard Material controls retain accessible tap targets. No Flutter layout/exception failures were reported in the successful run. These are automated widget checks, not manual native-device, screen-reader, browser-console or physical-device acceptance.

## 17. Live HTTPS verification

Pending: no trusted device-reachable non-production HTTPS environment was supplied. As permitted by the task, verification uses mocks without touching normal KOBI to create fixtures. No production API family or live health record was created/read. Existing TLS, certificates, Apache, PHP and production configuration were not altered.

## 18. Backend, database and Git integrity

A before-edit SHA-256 baseline covers **547 existing backend/configuration/document/test files** across app/bootstrap/routes/database/public/tests/docs/config and root configuration/manifests/instructions. The final recheck found **zero changed or missing existing files**. The only new file in those protected trees is this report. The two edited mobile files were compared with saved original copies; new files and API-path/privacy/write-operation scans were reviewed. Changes remain confined to the documented mobile features and this report.

No PHP, backend route/API contract, schema, database data, SQL mode, `.htaccess`, environment credential, limiter, Treatment, Medicine governance, Authentication implementation or production health record was changed. No database connection/write or normal API request was made. File integrity is not represented as a live database-state query. No Git command or Git mutation was performed.

## 19. Known limitations and completion boundary

Device/manual end-to-end, trusted live HTTPS and iOS runtime/build acceptance remain pending appropriate infrastructure. Existing 58B native storage/restart/device-trust limitations are unchanged. The APK's reserved `.invalid` host intentionally cannot provide a live login.

Daily screens are reads of the last server response, with explicit Refresh and per-slot dates; they are not live monitoring, reminders or adherence verification. No clock-driven background polling or alarm is added. A continuously open screen must be refreshed for newer server data. Detected date changes during pagination reset the list, but the backend exposes offset pages rather than an immutable snapshot, so concurrent server changes cannot be guaranteed to form a single historical snapshot across all pages. Saved checks, in contrast, display the immutable persisted historical snapshot returned by their detail API.

No public disease/substance knowledge, medicine knowledge, writes, sharing or mobile verification was implemented. **58E was not started.**

Final verification record: **129 mobile tests PASS; 465 database-free backend checks PASS; formatting PASS; static analysis PASS; Android debug build PASS; existing-file integrity PASS.** No device/live API acceptance is claimed. Step 58D implementation is complete within the documented limitations. **58E was not started.**
