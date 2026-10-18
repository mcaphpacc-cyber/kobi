# Step 58E — Mobile V1 Public Knowledge & V1 Acceptance

Implementation date: 18 October 2026. **Status: PASS WITH LIMITATIONS.** All 159 mobile tests, 712 database-free backend checks, formatting, static analysis and the Android debug build passed. Mobile V1 client feature/navigation acceptance passed; native-device/live release acceptance remains pending.

## 1. Scope and exact files

Implemented public Disease Catalog, Disease Search, Disease Detail with returned references, and Substance / Generic Name Search. Integrated anonymous access and authenticated navigation into the existing Flutter application. No backend or clinical-content work was required.

Modified:

- `mobile/kobi_mobile/lib/app/kobi_app.dart` — host the anonymous Sign in/Knowledge shell while preserving the existing private session gate.
- `mobile/kobi_mobile/lib/navigation/foundation_shell.dart` — add authenticated Knowledge and dispose its public state with the shell.
- `mobile/kobi_mobile/pubspec.yaml` — pin the external-reference link launcher.
- `mobile/kobi_mobile/pubspec.lock` — lock the launcher and seven platform/interface dependencies; existing packages were not upgraded.

Created:

- `mobile/kobi_mobile/lib/features/knowledge/knowledge_models.dart`
- `mobile/kobi_mobile/lib/features/knowledge/knowledge_state.dart`
- `mobile/kobi_mobile/lib/features/knowledge/knowledge_screen.dart`
- `mobile/kobi_mobile/lib/navigation/anonymous_shell.dart`
- `mobile/kobi_mobile/test/knowledge_support.dart`
- `mobile/kobi_mobile/test/knowledge_models_test.dart`
- `mobile/kobi_mobile/test/knowledge_state_test.dart`
- `mobile/kobi_mobile/test/knowledge_widgets_test.dart`
- `docs/task-58e-mobile-public-knowledge-implementation.md`

Generated plugin registrations, `.flutter-plugins-dependencies`, compiler/test caches and APK output are generated developer artifacts, not authored backend/native-security changes. Ignored `mobile/.tooling` contains logs, the before-edit protected-file baseline and original copies of the two edited existing Dart files. Existing tests, transport, session coordinator, secure storage, Login/Account implementations, profile state and daily-health implementations were not edited.

## 2. Disease Catalog

Consumes the current summary projection: decimal-string ID, canonical slug, English name, nullable Hindi name, public URL, nullable body system and gender. UI displays available names/body-system/gender metadata, with no invented category filter or exposed database ID. Catalog loading is incremental, 20 rows per explicit page; no eager full-catalog download. Standard loading/empty/error/retry states reuse the existing read-page infrastructure. Server order is preserved.

## 3. Disease Search

Search is integrated into Catalog. A labeled field accepts 2–100 normalized characters. Input is not a local matching engine: only the existing disease search endpoint supplies matches and ordering. Clear cancels pending searches, clears the field/results and restores the retained catalog. Detail Back restores the query, returned matches and input instead of fetching the catalog again. Partial matches are not discarded or re-ranked.

## 4. Disease Detail

Navigation uses the actual returned slug, validated against the backend's ASCII/hyphen syntax and 255-byte limit; display names are never converted into slugs. The current public API exposes English plain-text Overview, Causes, Risk Factors, Diagnosis and Prevention, plus cause names, urgency note and section-level references. Those actual fields are displayed. Optional absent/null section text is omitted sensibly; available cause names/references can still render. A completely text-empty detail explains that section text is unavailable.

Treatments, FAQ, symptoms and other web-only content are not in this API projection and were not fabricated or obtained through legacy HTML routes. Disease 404 has “Disease not found” with Back to diseases. Network/server/malformed responses have safe retry without returning the user to Login.

## 5. Substance Search

Uses the existing names-only registry projection: string `id`, `canonical_name`, `registry_url`, `information_type=legacy_registry_name`. Even unsigned-bigint identifiers remain strings. Cards display the returned canonical name only; no detail route, strength/form, manufacturer, clinical label, prescribing guidance or recommendation is invented. Empty text explicitly refers to KOBI's current registry rather than implying that a medicine does not exist.

## 6. Navigation

Authenticated navigation remains the existing Home/Health/Saved/Account shell, with Knowledge added as the fifth destination. Its simple landing offers Diseases and Medicine / Substance Registry. Public Back behavior is handled using the same in-widget state/PopScope convention; no router package or alternate framework was introduced. Switching between Knowledge and private areas preserves the current authenticated session and selected-profile models.

Anonymous users see the existing Login form with a clearly available Knowledge destination; browsing needs no login. A small anonymous host reuses the unchanged Login widget and public Knowledge controller. It does not construct private health/saved controllers. Login success replaces that host with the existing authenticated shell; logout disposes private state and returns to the anonymous host. Keyboard resizing remains delegated to the inner screen, preserving the existing Login accessibility checks.

## 7. Public/authenticated API separation

`KnowledgeRepository` calls the existing HTTPS `ApiClient` directly, without access tokens and without `SessionController.authorized`. No public request can initiate refresh or logout. Existing transport safeguards continue to reject attaching credentials to disease/substance paths. Public 401/403 is presented as public-information unavailability, not a reason to end an otherwise authenticated session. Private APIs continue using the existing coordinator and server authorization.

| Capability | Existing GET endpoint relative to configured HTTPS `/api/v1` |
|---|---|
| Catalog | `/diseases?page=…&page_size=20` |
| Disease Search | `/diseases/search?q=…` |
| Disease Detail | `/diseases/{returnedCanonicalSlug}` |
| Registry Search | `/substances/search?q=…` |

Query parameters use typed URI construction, including punctuation and Unicode; no raw query concatenation or alternate web/AJAX endpoint is used. Slugs follow the returned validated path-segment contract and URI path encoding. `public_url` and `registry_url` are retained as metadata, not used as JSON or authentication destinations.

## 8. Debounce

Both searches wait **350 ms** after the latest change. Short, empty or overlong input sends no request. Whitespace normalization is input preparation only; backend normalization/matching remains authoritative. Explicit retry uses the current query and honors Retry-After. There is no background search polling or per-keystroke request flood.

## 9. Stale-response protection

Every query change cancels its previous debounce and clears/increments the existing `LoadState` generation while preserving cooldown. This permits a newer read while ensuring late old responses cannot repopulate results. Explicit tests send `acn`, then `acne`, deliver `acne` first and `acn` last for both resources; `acne` remains displayed. Changing disease detail slug similarly invalidates the previous detail response. Disposal cancels timers and invalidates pending results; no global public/health state or persistent cache was added.

## 10. Pagination

Catalog uses actual `page`, `page_size`, `total` and `has_more`, with size 20 within the API maximum 50. It preserves server order, deduplicates identities and respects the existing page-10000 bound. Load More is incremental and refresh resets the catalog.

Disease and registry search use the distinct capped metadata: `query`, `limit=10`, `has_more`. They have no invented next page/cursor/total. When more matches exist, the UI asks the user to refine the query. Malformed metadata is an error, not silently accepted as an empty result.

## 11. Disease content and references

Narratives remain plain Flutter text. Literal markup-like strings are not executed or interpreted as HTML. Paragraph spacing, headings, cause-name bullets and readable line width improve presentation without rewriting medical text. English content and optional Hindi disease names are distinguished; no translated clinical narrative is invented.

Sources are grouped under their returned section. Cards retain organization, title, URL and optional date/date-type semantics. No citation or KOBI review date is invented for an empty mapping. HTTPS source links with a host and no embedded credentials open externally, with no API headers/cookies passed. Other returned URLs remain visible/selectable without automatic insecure navigation. Failed OS dispatch shows a safe message and leaves the URL available to copy.

The single new direct dependency is official Flutter [`url_launcher` 6.3.3](https://pub.dev/packages/url_launcher), used only for deliberate source-link taps with `LaunchMode.externalApplication`; it is not a WebView/API client. The documented direct-launch/failure-handling approach avoids adding platform URL-query permissions. Locked additions: url_launcher_android 6.3.33, url_launcher_ios 6.4.2, url_launcher_linux 3.2.3, url_launcher_macos 3.2.6, url_launcher_platform_interface 2.3.2, url_launcher_web 2.4.3 and url_launcher_windows 3.1.6. Existing package versions and native security configuration were preserved.

## 12. Registry boundary

The screen explicitly labels results as **Legacy registry/substance information** and describes generic-name lookup, not curated Medicine Knowledge or prescribing advice. It does not blur these public names with recorded Personal Medicines. No drug indications, dosage, adverse effects, contraindications, interactions, warnings or clinical-review claims were added.

## 13. Accessibility and responsive presentation

Knowledge reuses Material cards, touch controls, typography, margins and the existing maximum readable line width. Search/clear actions have labels/tooltips; disease/result names are text; narrative headings have heading semantics; progress indicators and safe error states have accessible descriptions. Source URLs and long labels wrap; source buttons retain readable actions and failed dispatch is announced. No medical meaning is communicated by color alone.

Widget journeys cover **390×844** and **768×1024**, including disease detail, search, registry names, Back and scrolling. A phone test uses **1.6× text scaling** with eighty long stored paragraphs and verifies the final section is reachable without Flutter layout/overflow exceptions. Existing authentication keyboard/large-text and private-health layout tests also remain passing. These are widget checks, not a claim of native screen-reader or physical-device acceptance.

## 14. Loading, empty and error states

Catalog, search and detail have deliberate progress treatment. Valid empty collections have specific messages. Short queries show an input prompt; capped searches ask for refinement. Source/date/optional-section nulls are handled without invented content. Disease 404 differs from ordinary network/server failure, and public errors do not navigate to Login. Existing safe errors and Retry-After are reused; raw PHP/SQL text, payloads, credentials and paths are not displayed or logged. New source has no analytics, response logging or TLS bypass.

## 15. Tests and backend regressions

Final full mobile suite: **159 tests passed**, exit 0. All **129 pre-existing 58B–58D tests passed unchanged**, including Login, session restoration/refresh/logout/Logout All, secure storage mocks, profile selection/isolation, Dashboard, Summary, condition/medicine list/detail, schedules, Due Today and saved history.

| New suite | Cases / coverage |
|---|---|
| `knowledge_models_test.dart` | 6; catalog/search/detail/null fields/slugs/sections/references, source URL opening eligibility, bigint registry IDs and malformed responses |
| `knowledge_state_test.dart` | 12; catalog paging/empty/error/retry/protocol, direct public reads, detail errors/races, both debounce/query races, punctuation/Unicode encoding, both search error/malformed/network/retry paths, public 401/429 without refresh/session exit |
| `knowledge_widgets_test.dart` | 12; anonymous journeys at two sizes, authenticated full-V1 navigation, catalog paging, safe errors/retries/404, empty/capped searches, public 401 isolation, both UI query races, detail loading/optional sections, external source dispatch with no headers, long content/large text |
| **New / total** | **30 / 159 passing** |

Existing assertions were not weakened or modified. The new asynchronous fixture assertion was moved outside a widget-pump callback, and its synthetic cooldown timer is explicitly cleaned up before widget invariants; these were test-harness corrections, not changes to application acceptance behavior.

All following existing PHP regression processes passed using in-memory fixtures, without a normal database connection:

| Suite | Checks/scenarios passed |
|---|---:|
| `tests/public-api-v1.php` | 56 |
| `tests/public-search.php` | 32 |
| `tests/disease-detail-presentation.php` | 7 scenarios |
| `tests/disease-references.php` | 71 |
| `tests/api-auth.php` | 103 |
| `tests/api-identity.php` | 44 |
| `tests/api-health-profiles.php` | 20 |
| `tests/api-medications.php` | 28 |
| `tests/api-medical-summary.php` | 29 + 76 Dashboard checks |
| `tests/api-health-conditions.php` | 65 |
| `tests/api-medication-schedules.php` | 71 + 52 browser schedule checks |
| `tests/api-saved-symptom-checks.php` | 58 |
| **Total** | **712** |

No live HTTP/MariaDB/deployment/auth-family acceptance harness was run. Fixtures and method-channel dispatch tests use synthetic reserved `.invalid` URLs; no actual source page or medical data was fetched for testing.

## 16. Static analysis and formatting

Final Flutter analysis **passed with no issues**, exit 0. Dart zero-change formatting verification **passed** across 46 Dart files. New-file style/unused-import diagnostics were corrected. No PHP was modified; modified-PHP syntax checking is not applicable.

## 17. Android build

Final Android debug build **passed**, exit 0; `assembleDebug` completed in **177.1 seconds**, including the new launcher plugin. Compilation uses the existing project-local SDK/helper and the intentionally non-routable `KOBI_ENV=local`, `API_BASE_URL=https://fixture.example.invalid/api/v1` configuration. It makes no live KOBI request. Native authentication/storage/privacy code was not manually edited. Release signing/distribution is not claimed.

Artifact: `mobile/kobi_mobile/build/app/outputs/flutter-apk/app-debug.apk`, **181423651 bytes**, SHA-256 **13EBA85E0950F18904D574E220F9968584123D54DC0B16134F84E84AE26126BE**. The existing SDK XML metadata-version warning was nonfatal; no SDK/dependency workaround was applied. Read-only inspection of the compiled manifest passed: min SDK 24, target 36, INTERNET permission, allowBackup=false, fullBackupContent=false, usesCleartextTraffic=false and dataExtractionRules present. This confirms merged manifest settings, not native runtime trust/storage acceptance.

## 18. Emulator/device verification

Project-local `adb devices` succeeded and returned no attached device/emulator. The inventory-started adb server was stopped afterward; no emulator or installation was created. Android native launch/storage/restart/external-browser handling and screen-reader acceptance remain pending. iOS build/Keychain/native-link acceptance requires an Apple toolchain/device and was not performed on Windows.

## 19. Live HTTPS verification

Pending. No trusted device-reachable non-production KOBI HTTPS API was supplied. As allowed by the task, full possible verification uses mocks. Normal KOBI was not used merely to obtain mobile fixtures or credential families. No TLS/certificate/Apache/PHP/limiter or server configuration change was made, and no verification bypass was added.

## 20. Complete Mobile V1 acceptance/navigation inventory

| Screen/action | Reachable entry / automated result |
|---|---|
| Login | Anonymous Sign in; existing form/session tests PASS |
| Session restoration | Existing app bootstrap/coordinator tests PASS; native process/device behavior pending |
| Profile Selector | Home/Health with zero/multiple profiles, Change Profile; PASS |
| Dashboard | Authenticated Home; summary/counts/core/daily/saved links PASS |
| Health Profile | Health tab or Dashboard; PASS |
| Medical Summary | Home/Health; aggregate cards/navigation PASS |
| Conditions → Condition Detail | Home/Health/Summary → recorded row; PASS |
| Personal Medicines → Medicine Detail | Home/Health/Summary → recorded row; PASS |
| Medication Schedule | Home/Health hub; active/inactive/paging/profile switching PASS |
| Due Today | Home/Health hub; wall time/date/states/read-only/paging PASS |
| Saved Symptom Checks → detail | Saved tab or Home/Health account-level link; snapshot/isolation PASS |
| Knowledge landing | Anonymous or authenticated Knowledge tab; PASS |
| Disease Catalog | Knowledge → Diseases; paging PASS |
| Disease Search | Catalog search field; debounce/server order/clear/Back PASS |
| Disease Detail | Catalog/search returned slug; sections/references/errors/Back PASS |
| Substance Registry Search | Knowledge → Medicine / Substance Registry; names-only/cap/empty/error PASS |
| Account | Authenticated Account tab; existing identity display PASS |
| Logout | Account → Logout; private data removed, anonymous Knowledge remains reachable; PASS |
| Logout All | Existing Account password-confirmation dialog/session tests; PASS |

An authenticated widget walk exercises Knowledge → Disease → Home/Summary → Conditions/Detail → Medicines/Detail → Schedule → Due Today → Saved/Detail → Health Profile → Account → Logout → anonymous Knowledge. Existing profile-selection and Logout All tests cover their alternate flows. No screen is intentionally orphaned. Client feature/navigation acceptance passes in automated fixtures; **native/live release acceptance remains pending**, including the inherited 58B platform-storage limitations. Personal recorded data, public disease information and registry names remain distinct product categories.

## 21. Backend/database/Git integrity

A before-edit SHA-256 baseline covers **548 existing backend/configuration/document/test files** across app/bootstrap/routes/database/public/tests/docs/config and root configuration/manifests/instructions. Final recheck found **zero changed or missing existing files**. This report is the sole new file in those protected trees. The two edited existing Dart files were compared with saved originals; new feature code, dependency additions, API paths and privacy/write-operation boundaries were reviewed. Changes remain confined to the listed mobile implementation/configuration/tests and this report.

No PHP, API routes/contracts, database data/schema/SQL mode, `.htaccess`, environment credentials, Authentication backend, Treatment or Medicine governance changed. No normal database connection/write, symptom-checker counter mutation, API-family issuance, production health-data read/write or normal KOBI endpoint request was made. Dependency downloads contacted the package service, not normal KOBI. File hashing is file-integrity evidence, not a live database-state query. No Git command or Git mutation was performed.

## 22. Intentionally deferred

Health-record writes, medication CRUD, Taken/Skipped writes, schedule CRUD, Saved Symptom Check creation/deletion, allergy/procedure detail, documents, timeline, curated Medicine Knowledge, mobile verification and KOBI-to-KOBI sharing remain intentionally deferred. Existing allergy/procedure aggregate counts are unchanged. Native registration/recovery/Google flows and release distribution are not introduced. These are post-V1 boundaries, not failing routes with working-looking controls.

## 23. Known limitations and stop boundary

Live/device/iOS/release acceptance remains pending for the reasons above. The reserved-host debug build cannot provide a real login until rebuilt with an approved trusted environment. Actual OS source-browser dispatch is pending device validation; method-channel arguments/failure fallback passed in tests. API English narratives do not imply Hindi clinical translations; Treatments and other web-only sections are absent from the public V1 projection. Public search is capped and catalog paging is offset-based as implemented by the backend; no immutable search/catalog snapshot is claimed.

Existing Step 58D freshness limitations remain: Daily views are explicit reads with Refresh, not live monitoring/alarms. No additional scope was implemented to disguise these boundaries. **Step 59 was not started.**

Final verification record: **159 mobile tests PASS, including all 129 unchanged existing cases; 712 database-free backend checks/scenarios PASS; formatting PASS; static analysis PASS; Android debug build/merged manifest PASS; existing-file integrity PASS.** Native/live/iOS/release acceptance is pending as documented. **Step 59 was not started.**
