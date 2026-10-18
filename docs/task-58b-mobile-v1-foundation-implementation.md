# Step 58B — Mobile V1 Foundation & Authentication

Implementation date: 18 October 2026. Scope: mobile foundation only. **Implementation/build/test validation: PASS. Final acceptance: BLOCKED pending approved live authentication and native-device checks.** Step 58B is not declared fully complete. See sections 12–14 for executed checks and remaining requirements.

## 1. What was implemented

New Flutter application at `mobile/kobi_mobile`, with Android/iOS project scaffolding, mandatory HTTPS environment configuration, native two-destination routing, a blue/light Material theme, Login, a deliberately limited Foundation screen, and Account. There is no fake login or embedded website. Account displays only email, active status and email-verification state from `/api/v1/me`. No token, public session identifier or health information is displayed.

No Dashboard, profile selection, health record, medication/schedule, saved check, disease/substance screen, registration, recovery, native Google login, sharing or mobile verification was implemented. Steps 58C–58E have not begun.

## 2. Project structure and file scope

All mobile application/configuration/test files are new under `mobile/kobi_mobile`; `mobile/.gitignore` is new and excludes `/.tooling/`. Existing KOBI files were not edited. This report is the only new backend-project document. The inventory at the end identifies the source/scaffold files; downloaded SDKs, package caches, generated compiler/configuration files and build output are separate ignored developer artifacts.

```text
mobile/kobi_mobile/
  lib/app/                     environment, application bootstrap/theme/state gate
  lib/core/network/            bounded typed JSON transport
  lib/core/errors/             safe typed failures
  lib/core/storage/            protected session store
  lib/features/auth/           login, DTO, repository, session coordinator
  lib/features/account/        identity projection, account/session controls
  lib/navigation/              Foundation/Account shell
  android/                     native runner, durability bridge, privacy settings
  ios/                         future iOS runner and Keychain entitlements
  test/                        API/session/storage/widget regressions
  tool/flutter.ps1             scoped project-local tooling helper
```

Screens are views over one injected session controller and repository; they do not perform HTTP or access protected storage directly. No empty implementations for future features were created. The Foundation screen explicitly states that it is not the Personal Health Dashboard. Sign-out removes the authenticated shell instead of keeping private pages on a separate back stack.

## 3. API client architecture

`ApiClient` uses an injected `http.Client` and actual V1 JSON envelopes. Native bootstrap supplies `IOClient(HttpClient)` with a ten-second connection timeout; requests have a thirty-second overall timeout with an abort signal. Response handling is bounded at 262,144 bytes, matching the existing server guard. UTF-8/JSON, API version, envelope/status consistency and current object-shaped DTOs are checked. Unknown additive fields are ignored; malformed responses are errors, never empty account data.

Requests use known relative paths, never arbitrary caller-supplied origins. Redirects are disabled. GET has no body; authentication POSTs use `application/json`. Logout sends `{}`. Login and refresh have no bearer header; protected requests use current Bearer access only. Public disease/substance paths reject attempts to attach access tokens. No cookie jar, legacy AJAX/session route, HTTP fallback or automatic auth POST retry is present. This phase's API methods parse object responses; later collection APIs must add explicit typed collection parsing when implemented.

Actual consumed endpoints, relative to configured `/api/v1`:

| Method/path | Request / success data |
|---|---|
| POST `/auth/login` | email, password, device_label=`KOBI Mobile`; seven-field credential response |
| POST `/auth/refresh` | refresh_token; replacement seven-field credential response |
| POST `/auth/logout` | Bearer access, `{}`; `{revoked:true}` |
| POST `/auth/logout-all` | Bearer access, current_password; `{revoked:true}` |
| GET `/me` | Bearer access; id/string, email/string, status/active, email_verified/true |

Source inspected: `ApiAuthController`, `ApiAuthService`, `ApiAuthGuard`, `ApiIdentityService`, `PublicApiResponse`, `Request`, current routes, authenticated API contract, and approved 58A blueprint. Backend behavior remains authoritative.

## 4. Authentication architecture

Opaque access/refresh credentials are preserved. There is no JWT parsing or new authentication protocol. `TokenBundle` validates actual token purpose/format and the seven fields: token_type, access_token, expires_in, refresh_token, refresh_expires_at, session_id, session_expires_at. Date/time metadata is UTC. Access deadline uses request-start time plus server expires_in conservatively, with a fifteen-second reuse margin; server `/me` admission still decides validity. Refresh and absolute expiry are respected before sending refresh.

The existing backend's access lifetime is up to ten minutes; refresh slides fourteen days within a thirty-day absolute family. Rotation replaces both credentials and invalidates old access. Reusing consumed refresh revokes the family. The client does not weaken these rules, create a grace window or retain an old refresh token as a fallback.

Session state distinguishes startup checking, anonymous, login in progress, authenticated, account checking, startup connection failure and signing out. Identity is held in memory only and cleared on session exit. An epoch prevents late responses from reinstalling an ended session. One serialized storage queue prevents late old-session writes from undoing cleanup. Startup network failure hides the authenticated shell while allowing connection retry or local exit; it is not represented as valid authenticated data or empty data.

## 5. Secure token storage

`flutter_secure_storage 11.2.0` protects one record containing only the access/refresh pair and minimal deadlines/family-continuity metadata. The record is bound to environment and base URL. No password, identity response, health payload or complete API response is persisted. Android uses the pinned plugin's default RSA-OAEP/AES-GCM encrypted preferences with Keystore-protected key material; iOS explicitly uses nonsynchronizing, unlocked/device-only Keychain. Platform storage errors fail closed without printing native error details. Android reset-on-error and migration are disabled for this new store.

Before any refresh request, the stored credential record is replaced with a **token-free rotation-pending marker**. An interrupted rotation cannot restore the old one-use refresh. A successful replacement pair is persisted and verified before publication to waiting requests. One encrypted record avoids split access/refresh updates.

Pinned Android plugin source review found `SharedPreferences.apply()` in its write path. A small native `org.kobi.mobile/secure_session` flush method uses `commit()` on the same default encrypted preference file, waiting for pending disk writes before Dart proceeds. It writes only a nonsensitive durability marker, never plaintext credentials. The bridge/plugin namespace is coupled to the pinned package and must be reviewed on upgrades. Android backup/cloud/device-transfer exclusions are present; iOS Runner build configurations reference the new Keychain entitlements.

Logout writes a token-free signed-out marker before attempting key deletion. If deletion fails after the verified marker, no usable bundle remains. If the OS cannot write the marker at all, local memory still clears, another login is blocked, and the UI asks the user to unlock/retry secure cleanup. Successful physical erasure from an unavailable/failing OS store is not falsely guaranteed. Native disk/crash/Keychain behavior still needs real-device validation; mocked method-channel tests do not establish hardware behavior.

## 6. Serialized refresh coordinator

All private reads go through `SessionController.authorized`. Known expired access triggers one shared refresh before the read. Concurrent access 401s join that refresh; a late old-generation 401 retries using the already-installed new pair instead of rotating again. Each original read retries at most once. A second 401 ends the session.

Refresh rejection, malformed replacement, network timeout, server failure or storage failure clears local authenticated state and returns to Login. No automatic retry of an uncertain refresh is made. A refresh lost after server consumption cannot fall back to the previous persisted token. Waiting requests fail rather than creating independent refresh storms. No background isolate/worker owns a separate refresh path.

## 7. Login behavior

Email/password form has labels, required/email/size/control-character validation, loading state, disabled submission, password visibility control and keyboard next/done navigation. Password is not trimmed and is removed from its text controller on submit. A successful server issuance is securely persisted before `/me` validates startup. Persistence/startup failure attempts ordinary server logout when a valid issued credential is available, then clears local state; remote cleanup cannot be promised during network failure.

Login errors use fixed safe application messages; raw backend text is not trusted for UI display. A rejected login never triggers refresh. Retry-After sets a local cooldown and re-enables interaction when the cooldown ends. No account creation, password reset, placeholder success or Google login is offered.

## 8. Session restoration

Startup reads protected storage, rejects mismatched environment/invalid/pending/signed-out records, checks deadlines and calls `/me` to validate eligible identity. Unexpired valid access does not cause another login or unnecessary refresh. Expired access uses the single coordinator, then `/me`. Failed refresh returns to Login; ordinary startup network failure remains a distinct unverified connection state with retry/local exit.

Foreground resume rechecks `/me` when authenticated. Epoch checks and temporary local variables prevent an old startup/account response from repopulating identity after logout. Unit tests cover persisted-session restoration and interrupted rotation; native process restart acceptance remains outstanding.

## 9. Logout and Logout All

Logout attempts one ordinary server request with valid access (one preflight refresh if locally expired), then always leaves authenticated state and attempts secure cleanup. It does not automatically replay a state-changing POST after an uncertain response. Network/server failure reports that local sign-out occurred but server revocation could not be confirmed.

Logout All uses a password dialog and the actual `current_password` contract. Empty input is rejected. Password controller is cleared and disposed; password is never persisted. Success ends local state and explains that API sessions, not browser sessions, were revoked. Rejected password or network/server failure still performs local cleanup and reports unconfirmed remote sign-out. No new family is created automatically afterward.

## 10. Environment configuration and versions

Compile-time `KOBI_ENV` accepts local/staging/production; `API_BASE_URL` must be an absolute HTTPS URL ending exactly `/api/v1`, with no embedded credentials/query/fragment. Production is the default environment but has no default URL. Invalid/missing settings show a safe setup screen and send no requests. Local/staging display an environment marker. No localhost production default, insecure TLS callback, trusted-certificate bypass or backend configuration change exists. The APK build command uses a reserved `.invalid` host solely to prove compilation, not a real endpoint.

| Component | Exact version used |
|---|---|
| Flutter stable | 3.47.7; framework abaf9c5237; engine deb287481e |
| Dart | 3.13.5 |
| DevTools supplied by SDK | 2.60.0 |
| http | 1.6.0 |
| flutter_secure_storage | 11.2.0 |
| flutter_secure_storage_platform_interface | 2.1.1 |
| flutter_secure_storage_darwin | 0.4.3 |
| flutter_lints | 6.0.0 |
| flutter_test | SDK version, locked to Flutter 3.47.7 |
| Android Gradle plugin / Kotlin plugin | 9.1.0 / 2.4.0 (SDK scaffold) |
| Gradle wrapper | 9.3.1 |
| Project-local JDK | Eclipse Temurin 21.0.12.1+1 |
| Android command-line tools | 23.0, official Windows archive 16111833 |
| Android compile/target/minimum | 36 / 36 / 24 |
| Android build-tools initially provisioned | 36.0.0 |
| Additional Android native build dependencies | Platform 35 revision 2; NDK 28.2.13676358 (r28c); CMake 3.22.1 |
| iOS deployment target | 15.0 |

All resolved transitive versions and checksums are in `mobile/kobi_mobile/pubspec.lock`. Flutter and JDK downloads were verified against published SHA-256; Android command-line archive against its official repository checksum. No system PATH, Apache, PHP, certificates, limiter or backend environment was changed. SDKs/caches live under ignored `mobile/.tooling`; tool processes can maintain their own runtime metadata, independently of KOBI application configuration. The wrapper scopes its variables, uses project-local temp/cache paths and suppresses SDK telemetry. No PowerShell execution-policy change was made.

References for reviewed dependencies: [HTTP package](https://pub.dev/packages/http), [secure-storage package and platform settings](https://pub.dev/packages/flutter_secure_storage), [Android commit/apply semantics](https://developer.android.com/reference/android/content/SharedPreferences.Editor#commit()). No dependency source was patched.

## 11. Error handling, visual and privacy foundation

Typed failures distinguish authentication, validation, network, timeout, server/protocol incompatibility, protected storage and cancelled old-session work. No SQL, PHP stack, filesystem path, raw response, password, access token or refresh token is surfaced. No sensitive application logger, analytics SDK or network-payload logger is installed. HTTP errors are not shown as “No data.”

Blue primary actions and light surfaces follow the requested KOBI appearance, with readable text, constrained wide-screen forms, scrollable keyboard layouts, semantic/live error messages, password labels/focus and Material touch targets. Account shows no health information. Android sets FLAG_SECURE; Flutter hides the shell while backgrounded. These native privacy behaviors and screen-reader operation have not been verified on hardware. The generated launcher icons remain development scaffold assets; release branding/signing/store distribution are not acceptance claims of this foundation.

## 12. Automated testing and read-only integrity checks

Final mobile suite: **40 passing tests**, using mock HTTP transport, in-memory synthetic credential stores and mocked secure-storage/native-barrier channels. No KOBI endpoint, database, real credential or authentication family is used by these tests.

| Suite | Tests / coverage |
|---|---|
| api_client_test.dart | 7 — envelope/status/version parsing, correct JSON/mount, headers/no cookies/redirects, public token exclusion, safe API errors, Retry-After, network/timeout, malformed/oversize responses |
| session_controller_test.dart | 21 — login/rejection, persist-before-me, restart restoration, expiry, five concurrent 401s with one rotation, late old-token response, one retry limit, replay/network/server rejection, expired/pending refresh, storage failures, logout success/failure, logout-all success/rejection, stale response suppression, cleanup blocking |
| secure_session_store_test.dart | 6 — one protected record, environment binding, pending marker, signed-out marker on delete failure, safe native error, malformed storage, durability barrier failure (multiple assertions within tests) |
| auth_widgets_test.dart | 6 — validation/rejection, successful shell/Account/logout at 390×844 and 768×1024, Logout All password dialog, session-expiry transition, keyboard inset/large-text layout |

The secure-store suite's six test cases include both successful roundtrip/environment binding and the barrier assertion in that case; its listed behaviors are not separate test-count claims. Widgets produced no reported layout/Flutter exceptions. These are widget tests, not real-device acceptance or browser tests.

`flutter pub get` completed with the pinned packages; static analysis completed with no issues; formatting verification reported no changes after formatting. Initial source diagnostics were corrected and rerun. The first sandboxed test compiler could not access temporary output; the same tests passed with a project-local temp directory. The initial analyzer process encountered SDK telemetry file permissions; process-scoped suppression was added, then analysis passed. No backend tests were edited to obtain these results.

A SHA-256 baseline of **541 existing files** was captured before mobile creation, held in tool state and subsequently persisted to ignored tooling metadata for reliable recheck. It covers app/bootstrap/routes/database/public/tests/docs/config and root environment/configuration/manifests/instructions. Recheck found **zero changed or missing existing files**. This is file integrity evidence, not a database-state query. No SQL, database connection, normal HTTP auth request or normal credential-family creation was performed, so no production database writes occurred. No manual Git command or KOBI repository Git mutation was performed.

## 13. Build and device validation

The Flutter SDK was absent initially. A project-local verified SDK and JDK were installed. Existing Android SDK had API 34/build-tools 34 and lacked command-line tools; a separate project-local Android SDK was provisioned for API 36/build-tools 36.0.0, reusing existing accepted license files without changing the system SDK. Native scaffolding was generated in an empty tooling directory and only absent Android/iOS support files were copied into the app, preserving custom Dart/pubspec files. Automatic approval review had rejected in-place generation because of overwrite risk; the empty-directory alternative completed safely.

Android debug APK build: **PASS**, exit 0, `assembleDebug` completed in 403.7 seconds on the successful attempt. The first attempt failed during native configuration because the new Android CLI compatibility wrapper split the requested `ndk;28.2.13676358` package name. Installing the exact `ndk/28.2.13676358` package through `android.exe --no-metrics --sdk=<project SDK> sdk install` resolved that missing prerequisite without patching SDK/dependency/backend code. The retry also provisioned Android Platform 35 and CMake 3.22.1 into the project SDK using existing accepted license records. An SDK XML metadata-version warning was emitted; the complete build passed without a schema/API/TLS workaround.

Command uses `--debug --no-pub --dart-define=KOBI_ENV=local --dart-define=API_BASE_URL=https://fixture.example.invalid/api/v1`. No endpoint request is made by compilation. Release signing is intentionally unconfigured; no store-ready binary is claimed.

Artifact: `mobile/kobi_mobile/build/app/outputs/flutter-apk/app-debug.apk`, 159157818 bytes. SHA-256: `9F3AC9C0CDC4AD8B626763142FB8C72E9877924BFA8CD5AE55E30EF98A5D5B2E`. This development APK uses the reserved non-routable configuration above; rebuild with an approved actual endpoint for live testing.

Compiled APK manifest inspection passed: min SDK 24, target SDK 36, INTERNET permission, allowBackup=false, fullBackupContent=false, usesCleartextTraffic=false, and dataExtractionRules resource present. PowerShell helper and native XML syntax checks passed; all three Runner build configurations reference Keychain entitlements. Source scanning found no application print/debugPrint calls or TLS override patterns. These static checks do not replace runtime device acceptance.

Device inventory through the project-local adb succeeded and returned **no attached devices/emulators**. The initial sandboxed adb discovery failed; the permitted normal tool invocation then established the empty inventory. No emulator was created or app installed. Native Android Keystore/commit-barrier power-loss behavior, real launch/login/restart/refresh/logout/logout-all, certificate trust on a device, and iOS build/Keychain behavior remain unverified. iOS build is unavailable on this Windows host.

After build/verification, one task-specific Gradle daemon and the task-started adb server were stopped. Device inventory was still empty before stopping adb. SDKs and ignored build artifacts remain available for continuation.

## 14. Acceptance limitations and next action

No approved trusted non-production endpoint/account or attached Android device has been supplied. Live login/refresh/logout would create credential lifecycle rows, so the task's no-production-database-writes restriction precludes silently using normal KOBI. No real password has been requested through chat or extracted. A live test must use an approved non-production backend and genuine UI credentials, with TLS verification enabled and lifecycle cleanup through ordinary logout.

Therefore do **not** mark all Step 58B acceptance criteria complete on the strength of mocked tests. Code/contract/widget validation and the Android debug build passed; real-device/live acceptance remains separate. Do not begin 58C. Required remaining acceptance: approved endpoint with device-trusted certificate; genuine sign-in and `/me`; protected store across real app restart; expiry/refresh and logout/logout-all on the device; confirmation that no credentials appear in application logs and that native privacy/storage settings behave as intended.

Backend PHP, API contracts, routes, database data/schema, backend configuration, `.htaccess`, Authentication behavior, Treatment, Medicine and existing tests remain unchanged. Only new mobile files/tool artifacts and this report were created. No Git commit/push/reset/checkout/merge/rebase/amend was performed.

## Source/scaffold inventory

Paths below are relative to `mobile/kobi_mobile`. Generated ignored caches, SDKs, local machine files and build outputs are excluded from this source inventory; they are not additional backend application changes.

Additional new paths outside the app source directory: `mobile/.gitignore` and this report. No pre-existing file was modified.

```text
.gitignore
.metadata
analysis_options.yaml
android/.gitignore
android/app/build.gradle.kts
android/app/src/debug/AndroidManifest.xml
android/app/src/main/AndroidManifest.xml
android/app/src/main/kotlin/org/kobi/kobi_mobile/MainActivity.kt
android/app/src/main/res/drawable-v21/launch_background.xml
android/app/src/main/res/drawable/launch_background.xml
android/app/src/main/res/mipmap-hdpi/ic_launcher.png
android/app/src/main/res/mipmap-mdpi/ic_launcher.png
android/app/src/main/res/mipmap-xhdpi/ic_launcher.png
android/app/src/main/res/mipmap-xxhdpi/ic_launcher.png
android/app/src/main/res/mipmap-xxxhdpi/ic_launcher.png
android/app/src/main/res/values-night/styles.xml
android/app/src/main/res/values/styles.xml
android/app/src/main/res/xml/data_extraction_rules.xml
android/app/src/profile/AndroidManifest.xml
android/build.gradle.kts
android/gradle.properties
android/gradle/wrapper/gradle-wrapper.jar
android/gradle/wrapper/gradle-wrapper.properties
android/gradlew
android/gradlew.bat
android/settings.gradle.kts
ios/.gitignore
ios/Flutter/AppFrameworkInfo.plist
ios/Flutter/Debug.xcconfig
ios/Flutter/Release.xcconfig
ios/Runner.xcodeproj/project.pbxproj
ios/Runner.xcodeproj/project.xcworkspace/contents.xcworkspacedata
ios/Runner.xcodeproj/project.xcworkspace/xcshareddata/IDEWorkspaceChecks.plist
ios/Runner.xcodeproj/project.xcworkspace/xcshareddata/WorkspaceSettings.xcsettings
ios/Runner.xcodeproj/xcshareddata/xcschemes/Runner.xcscheme
ios/Runner.xcworkspace/contents.xcworkspacedata
ios/Runner.xcworkspace/xcshareddata/IDEWorkspaceChecks.plist
ios/Runner.xcworkspace/xcshareddata/WorkspaceSettings.xcsettings
ios/Runner/AppDelegate.swift
ios/Runner/Assets.xcassets/AppIcon.appiconset/Contents.json
ios/Runner/Assets.xcassets/AppIcon.appiconset/Icon-App-1024x1024@1x.png
ios/Runner/Assets.xcassets/AppIcon.appiconset/Icon-App-20x20@1x.png
ios/Runner/Assets.xcassets/AppIcon.appiconset/Icon-App-20x20@2x.png
ios/Runner/Assets.xcassets/AppIcon.appiconset/Icon-App-20x20@3x.png
ios/Runner/Assets.xcassets/AppIcon.appiconset/Icon-App-29x29@1x.png
ios/Runner/Assets.xcassets/AppIcon.appiconset/Icon-App-29x29@2x.png
ios/Runner/Assets.xcassets/AppIcon.appiconset/Icon-App-29x29@3x.png
ios/Runner/Assets.xcassets/AppIcon.appiconset/Icon-App-40x40@1x.png
ios/Runner/Assets.xcassets/AppIcon.appiconset/Icon-App-40x40@2x.png
ios/Runner/Assets.xcassets/AppIcon.appiconset/Icon-App-40x40@3x.png
ios/Runner/Assets.xcassets/AppIcon.appiconset/Icon-App-60x60@2x.png
ios/Runner/Assets.xcassets/AppIcon.appiconset/Icon-App-60x60@3x.png
ios/Runner/Assets.xcassets/AppIcon.appiconset/Icon-App-76x76@1x.png
ios/Runner/Assets.xcassets/AppIcon.appiconset/Icon-App-76x76@2x.png
ios/Runner/Assets.xcassets/AppIcon.appiconset/Icon-App-83.5x83.5@2x.png
ios/Runner/Assets.xcassets/LaunchImage.imageset/Contents.json
ios/Runner/Assets.xcassets/LaunchImage.imageset/LaunchImage.png
ios/Runner/Assets.xcassets/LaunchImage.imageset/LaunchImage@2x.png
ios/Runner/Assets.xcassets/LaunchImage.imageset/LaunchImage@3x.png
ios/Runner/Assets.xcassets/LaunchImage.imageset/README.md
ios/Runner/Base.lproj/LaunchScreen.storyboard
ios/Runner/Base.lproj/Main.storyboard
ios/Runner/Info.plist
ios/Runner/Runner-Bridging-Header.h
ios/Runner/Runner.entitlements
ios/Runner/SceneDelegate.swift
ios/RunnerTests/RunnerTests.swift
lib/app/environment.dart
lib/app/kobi_app.dart
lib/core/errors/api_failure.dart
lib/core/network/api_client.dart
lib/core/storage/session_store.dart
lib/features/account/account_identity.dart
lib/features/account/account_screen.dart
lib/features/auth/auth_repository.dart
lib/features/auth/login_screen.dart
lib/features/auth/session_controller.dart
lib/features/auth/token_bundle.dart
lib/main.dart
lib/navigation/foundation_shell.dart
pubspec.lock
pubspec.yaml
README.md
test/api_client_test.dart
test/auth_widgets_test.dart
test/secure_session_store_test.dart
test/session_controller_test.dart
test/support.dart
tool/flutter.ps1
```

Ignored developer artifacts include `mobile/.tooling` (verified Flutter/JDK/Android SDK including NDK, downloaded archives, package/Gradle caches, temporary scaffolding and hash-baseline metadata); app `.dart_tool`, `build`, plugin registrants/dependency metadata, Android local.properties and Gradle caches; iOS generated environment/configuration. These are generated/install outputs, not production data or new backend functionality.
