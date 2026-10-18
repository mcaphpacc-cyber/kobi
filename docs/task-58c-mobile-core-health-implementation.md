# Step 58C — Mobile V1 Core Health Experience

Implementation date: 18 October 2026. **Status: PASS WITH LIMITATIONS. Implementation, automated tests, analysis and Android build passed.** Device/live-environment limitations are recorded below; no physical-device or live integration acceptance is claimed.

## 1. Scope implemented

Eight read-only capabilities: Profile Selection, Health Profile, Health Dashboard, Medical Summary, Conditions List, Condition Detail, Personal Medicines List and Personal Medicine Detail. They extend the Flutter application at `mobile/kobi_mobile` and the existing 58B authentication foundation. Home, Health and Account are the three navigation destinations. No health write controls or future-feature placeholders were added.

## 2. Exact source/document file inventory

Modified:

- `mobile/kobi_mobile/lib/core/network/api_client.dart` — explicit collection/envelope method and encoded query parameters, preserving the existing object-returning authentication method.
- `mobile/kobi_mobile/lib/navigation/foundation_shell.dart` — replace the foundation placeholder with the scoped Home/Health/Account experience.
- `mobile/kobi_mobile/test/auth_widgets_test.dart` — update the shell navigation expectations and provide a real empty-profile response fixture; retain login, logout, Logout All, expiry and layout assertions.

Created:

- `mobile/kobi_mobile/lib/features/health/health_models.dart`
- `mobile/kobi_mobile/lib/features/health/health_repository.dart`
- `mobile/kobi_mobile/lib/features/health/health_state.dart`
- `mobile/kobi_mobile/lib/features/health/health_screens.dart`
- `mobile/kobi_mobile/test/health_support.dart`
- `mobile/kobi_mobile/test/health_models_test.dart`
- `mobile/kobi_mobile/test/health_state_test.dart`
- `mobile/kobi_mobile/test/health_widgets_test.dart`
- `docs/task-58c-mobile-core-health-implementation.md` — this report.

SDK caches, logs, a protected-file hash baseline and the three original edited-file copies are developer artifacts under ignored `mobile/.tooling`. Generated test/compiler/build files and the APK are separate from the source inventory. No package, dependency-lock, native runner, credential-store, session-coordinator, Login or Account implementation change was needed.

## 3. Profile-selection architecture

`ProfileController` uses `HealthRepository` to load the unpaginated accessible-profile collection. Zero profiles produces a dedicated no-accessible-profiles state with refresh, not fabricated empty medical records. Exactly one profile is automatically selected only after its detail endpoint succeeds. Multiple profiles require an explicit choice. No role, ownership or demographic information is inferred from collection ordering or profile category.

Change Profile immediately removes the old scope, shows the selector and allows the profile collection to be refreshed to discover additional profiles. Detail validation uses only the selected collection item's ID. The API exposes only decimal-string `id`, `full_name` and `profile_type`; the mobile Health Profile screen displays name/type, with IDs kept internal. Profile types retain the existing self/family/dependent values.

## 4. Dashboard architecture

Home composes the existing aggregate Medical Summary API. It displays the active profile banner, all five actual counts and shortcuts to Medical Summary, Conditions, Personal Medicines and Health Profile. There is no new dashboard endpoint or local calculation of medical counts. Allergy/procedure counts have no unsupported detail link. Explicit refresh hides prior values while the request is being checked.

## 5. Medical Summary

The screen displays the five counts: active/chronic conditions, historical/resolved conditions, current medicines, allergies and procedures. Zero is a legitimate count. Missing/malformed responses are errors, never fabricated zero counts. Condition and medicine count cards navigate to the appropriate implemented lists; allergy/procedure cards remain noninteractive. A visible statement explains the aggregate-only scope. The summary profile ID must match the selected profile.

## 6. Conditions

List/detail DTOs use `id`, `condition_name`, `status`, `diagnosed_on` and `resolved_on`. Recorded statuses are preserved without medical interpretation. List rows show status and available dates; details also handle null dates explicitly.

The collection sends only `page` and `page_size`, using size 20, and validates returned `page`, `page_size`, `total` and `has_more`. Load More appends server-ordered records and deduplicates IDs while preserving first appearance. Refresh resets paging. A failed next page retains the already loaded page with a visible error/retry; it does not silently claim completion. Server paging is not an immutable clinical snapshot.

## 7. Personal Medicines

The unpaginated collection sends no invented query/filter parameters. Models retain the ten actual fields: `id`, `brand_name`, `generic_name`, `strength`, `dose`, `frequency`, `route`, `status`, `started_on`, `stopped_on`. List cards show available recorded names/dose information/status/dates in backend order. Detail shows each exposed field, with “Not recorded” for null/empty optional values. No registry enrichment, prescribing advice, clinical warnings or recommendations were introduced.

## 8. Navigation

Home is Dashboard. Health exposes the selected Health Profile and the supported summary/lists. Details have an explicit Back control and system-back handling. Summary cards and Dashboard shortcuts open the same list/detail flows. Account retains the existing identity/Logout/Logout All screen and password-confirmation dialog. Health route state lives inside the authenticated shell; profile changes clear its private route stack, and logout removes the shell entirely.

## 9. State management

Existing ChangeNotifier/ListenableBuilder conventions are retained, with no added package or global health singleton. `LoadState<T>` represents loading, value/empty, error and retry eligibility. `ConditionListState` additionally owns server paging. A `HealthScope` owns the summary, lists and details for exactly one validated profile. Network operations originate from initialization, navigation or explicit actions, never from widget build methods. In-memory records are not persisted.

## 10. Loading, empty, error and retry states

Every API-backed screen has a progress indicator and safe retry/empty handling. Network failures remain distinct from server failures and successful empty collections. Raw backend error text is never displayed. Retry-After disables retry until its deadline, including when changing between detail records; no infinite retry loop is introduced.

401 handling uses the existing serialized refresh coordinator and its one retry/session termination behavior. Profile-scoped 403/404 clears selection and returns to safe profile recovery. A child 403/404 displays an unavailable-record error and rechecks the parent: accessible parent remains selected; denied/missing parent invalidates selection. An unavailable child alone is not treated as proof of lost profile access.

## 11. Profile-switch and asynchronous safety

Changing profile disposes all old profile models, clears prior values and routes, validates the new selection and creates a fresh scope. Request generations and disposal checks ignore late responses. A late profile-selection response cannot reinstall an earlier selection. Detail requests also have selected-record IDs so that opening a second condition/medicine while the first is pending supersedes the first response.

Tests cover A's loaded conditions/medicines being cleared, A's in-flight summary arriving after B is selected, late profile validation, same-profile detail races for both resources, and a real widget flow where A's pending medicines response completes after B's medicines are visible. Logout/session loss clears selection and removes private screens. Background-preview/session-resume behavior remains the existing 58B mechanism, which tears down private UI while backgrounded.

## 12. Existing API mapping

All paths are relative to the explicitly configured trusted HTTPS `/api/v1` origin. Health reads use `SessionController.authorized`; authentication remains the completed 58B implementation.

| Capability | Existing endpoint | Projection/paging |
|---|---|---|
| Account identity | GET `/me` | Existing 58B identity |
| Profile Selector | GET `/health-profiles` | Unpaginated array |
| Selected Health Profile | GET `/health-profiles/{profileId}` | Minimal profile object |
| Dashboard / Summary | GET `/health-profiles/{profileId}/medical-summary` | Profile + five counts |
| Conditions | GET `/health-profiles/{profileId}/conditions` | Array + actual page metadata |
| Condition Detail | GET `/health-profiles/{profileId}/conditions/{conditionId}` | Five-field condition object |
| Personal Medicines | GET `/health-profiles/{profileId}/medications` | Unpaginated array |
| Medicine Detail | GET `/health-profiles/{profileId}/medications/{medicationId}` | Ten-field medication object |

Transport adds explicit object/array envelope handling while preserving existing authentication object validation, safe errors, HTTPS/origin/redirect restrictions, response-size bound and timeouts. Health IDs stay decimal strings. Route IDs are checked against the existing signed-INT endpoint bounds. Calendar-date values are strictly validated and formatted from year/month/day components without device-timezone conversion. Unknown additive response fields are ignored; no invented fields are requested or displayed. Medical/profile labels render as plain Flutter text.

## 13. Automated tests and backend regressions

Final Flutter suite: **87 tests passed**, exit 0. Tests use the injected mock HTTP transport, synthetic fixture data and in-memory credential store at a reserved `.invalid` origin. No real credential, account, health record or database is used.

| Mobile suite | Test cases / coverage |
|---|---|
| Existing `api_client_test.dart` | 7; transport/envelope/TLS-origin/error/timeout constraints |
| Existing `session_controller_test.dart` | 21; login/restore/refresh races/storage/logout/session isolation |
| Existing `secure_session_store_test.dart` | 6; protected storage and durability/cleanup boundaries |
| Updated `auth_widgets_test.dart` | 6; login form flow with mock transport, Account, Logout/Logout All, expiry and layout |
| New `health_models_test.dart` | 12; all projections, string IDs, nulls, calendar validation, empty arrays, paging and malformed responses |
| New `health_state_test.dart` | 22; zero/one/multiple profiles, detail validation, summary/list/detail/error/retry/paging, access loss, 401/429 and response races |
| New `health_widgets_test.dart` | 13; eight screens, navigation, summary links, loading/empty/errors/retry, paging, profile switches including late response, layouts and logout |
| **Total passed** | **87** |

Database-free existing PHP regressions actually executed successfully:

| Suite | Passing checks |
|---|---:|
| `tests/api-auth.php` | 103 |
| `tests/api-identity.php` | 44 |
| `tests/api-health-profiles.php` | 20 |
| `tests/api-medications.php` | 28 |
| `tests/api-medical-summary.php` | 29 + 76 reused Dashboard checks |
| `tests/api-health-conditions.php` | 65 |
| **Total** | **365** |

These suites use in-memory repositories/support; no MariaDB, live HTTP, deployment or production authentication harness was run. Existing backend tests were not edited. The first new profile-switch widget assertion expected one name occurrence, but the intended Health Profile screen correctly renders both its banner and Name field; that new assertion was corrected to verify both while still requiring A's removal and no previous Back stack. No application behavior was weakened to make a test pass.

## 14. Formatting and static analysis

Dart formatting and zero-change formatting verification passed: 26 Dart files, zero changes in the final check. Final Flutter static analysis passed with **no issues**, exit 0. Initial brace-style/inference diagnostics in new/updated Dart files were corrected. No PHP changed, so modified-PHP syntax checking is not applicable.

## 15. Android build

The final Android debug build **passed**, exit 0, using the existing project-local SDK, pinned dependencies and `tool/flutter.ps1`. Final `assembleDebug` completed in 18.2 seconds after the final source/test verification. Configuration is deliberately non-routable: `--dart-define=KOBI_ENV=local --dart-define=API_BASE_URL=https://fixture.example.invalid/api/v1`. This proves compilation and does not connect to a live API. Release distribution/signing is not claimed.

Artifact: `mobile/kobi_mobile/build/app/outputs/flutter-apk/app-debug.apk`, **181309873 bytes**, SHA-256 **E83E41AB5282C971D52B30A06F5DD723CD0200E1469F8475076079D641597FE8**. The first build also succeeded and emitted the existing SDK XML metadata-version warning; final incremental build succeeded without that warning. No SDK or dependency upgrade/workaround was applied. One task-started Gradle daemon in the project's isolated cache was stopped after completion.

## 16. Emulator/device and layout results

Project-local `adb devices` succeeded and returned no attached devices/emulators. The adb server started solely for inventory was stopped afterward. No emulator was created, app installed or device trust configuration changed.

Widget navigation/layout verification runs at 390×844 and 768×1024, with a separate 1.6× text-scale/long-name flow. It checks major navigation, accessible scrolling, the supported lists/details, active-profile indication, stale-data removal and Flutter layout/exception reporting. These are automated mock UI checks, not manual native-runtime, physical-device, screen-reader or browser-console acceptance. Native launch/login/storage/network behavior remains pending an available device and approved environment.

## 17. Live HTTPS API status

Pending. No safe device-reachable non-production HTTPS environment was supplied. The task explicitly permits mock verification and continued documentation of this 58B limitation. The normal production-like KOBI database was not used to create fixtures or API families. No TLS verification bypass, HTTP fallback or Apache/PHP/certificate change was made.

## 18. Privacy and security

All profile-dependent calls use the selected profile's validated ID through the existing session coordinator and server authorization. No account number, self category or collection order confers access. Health state and selected-person routes are temporary memory only. New source contains no health logging, analytics, preferences/disk cache or raw-error display. No token/internal ID appears in health UI. Existing secure token storage, native privacy settings and session handling are unchanged.

## 19. Backend/database/Git integrity

A SHA-256 baseline of **546 existing files** was captured before implementation across app/bootstrap/routes/database/public/tests/docs/config and root configuration/manifests/instructions. Final recheck found **zero changed or missing existing files**, and the only new file in those protected trees is this report. The three edited mobile files were compared with saved original copies and all new feature files were reviewed. The new report is the only new file outside the mobile project/tooling area.

No PHP, backend API route/contract, schema, SQL mode, `.htaccess`, credentials, limiter, Authentication behavior, Treatment or Medicine governance changed. No database connection or write, live API request, normal credential-family creation or production health-data mutation was performed. File hashes are file-integrity evidence, not a claim that live database state was queried. No Git command or Git mutation was performed.

## 20. Limitations and final boundary

Native-device/manual end-to-end and live HTTPS integration remain pending. iOS build/Keychain acceptance requires an Apple toolchain/device and was not performed on Windows. The API intentionally limits Health Profile demographics and Medical Summary to aggregates; no unsupported mobile parity is claimed. Profiles and medicines remain subject to the existing unpaginated response-size guard; malformed/oversized responses fail visibly rather than truncating medical records.

No Schedule, Due Today, Saved Symptom Checks, public disease/substance feature, allergy/procedure detail, health write, sharing, document, timeline or verification feature was implemented. **58D was not started. 58E was not started.**

Final verification record: **87 Flutter tests PASS; 365 database-free PHP checks PASS; formatting PASS; static analysis PASS; Android debug build PASS; protected-file integrity PASS. Device/manual runtime and live HTTPS verification remain pending for the explicit reasons above. Step 58C implementation is complete within those documented limitations.**
