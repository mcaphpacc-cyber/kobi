# KOBI Mobile — Step 58B

Flutter Android-first authentication foundation, with an iOS scaffold for later device validation. Only Login, Foundation and Account/session actions are implemented. No health/knowledge feature or fake login is included.

## Toolchain

Validated SDK: Flutter **3.47.7 stable**, Dart **3.13.5**. Runtime dependencies are pinned to `http 1.6.0` and `flutter_secure_storage 11.2.0`; development uses `flutter_lints 6.0.0` and SDK `flutter_test`. Keep `pubspec.lock` with the app source for developer review. Android minimum API 24, compile/target API 36; iOS scaffold targets 15.0. No release signing or store registration is configured.

The task's SDK/JDK/Android SDK/caches live under `../.tooling/`, excluded by `mobile/.gitignore`. They are developer tools, not app source, assets or distribution payloads. They are not reproducibly installed by this README. A developer may instead use their own matching Flutter/Android setup. System PATH and backend configuration are not changed.

The PowerShell helper scopes tooling variables to its process invocation and restores the caller's environment. It also suppresses Flutter/Dart tool telemetry and uses a project-local temporary directory. It never changes PowerShell execution policy. From this directory:

```powershell
& .\tool\flutter.ps1 pub get
& .\tool\flutter.ps1 analyze --no-pub
& .\tool\flutter.ps1 test --no-pub
```

Or use your installed SDK's normal `flutter` command. Formatting:

```powershell
& ..\.tooling\flutter\bin\cache\dart-sdk\bin\dart.exe format --output=none --set-exit-if-changed lib test
```

The Android build needs API 36/build-tools 36.0.0, the dependency's API 35, NDK 28.2.13676358 and CMake 3.22.1. These are installed in the task's project-local SDK. With the new Android CLI 23 compatibility wrapper, a missing NDK may fail because legacy semicolon package names are split. Use the native `android.exe` CLI's `sdk install` with slash package names (for example `ndk/28.2.13676358`), targeting only the intended SDK. Review/accept any genuinely new license through the normal operator procedure; do not script acceptance. No SDK sources were patched to work around this.

## Configuring an endpoint

Supply `KOBI_ENV=local|staging|production` and `API_BASE_URL` with `--dart-define`. Base URL must be HTTPS and end **exactly** `/api/v1`, including the application's actual mount if any. No trailing slash, query, fragment or embedded credentials. Missing/invalid config shows a safe setup message and sends no requests. Production is the default environment, with no default host. HTTPS is required in every environment; there is no certificate bypass or HTTP fallback.

Example only (replace the reserved, non-routable host with an approved trusted non-production endpoint):

```powershell
& .\tool\flutter.ps1 run --dart-define=KOBI_ENV=staging --dart-define=API_BASE_URL=https://staging.example.invalid/api/v1
& .\tool\flutter.ps1 build apk --debug --dart-define=KOBI_ENV=staging --dart-define=API_BASE_URL=https://staging.example.invalid/api/v1
```

These hosts are not real KOBI deployments. A phone's localhost refers to that phone. Never bypass trust to reach a local XAMPP instance. Normal login/refresh/logout writes API credential lifecycle rows, so live testing requires an explicitly approved non-production environment/account. Do not enter real credentials into a debug build pointing to an unverified host. No password is stored or logged by this client.

## Architecture and boundaries

`app` owns bootstrap/environment/theme; `navigation` owns the two-tab Foundation/Account shell; `core/network`, `core/errors`, `core/storage` own transport, safe failures and protected session persistence; `features/auth` owns DTOs, repository, coordinator and Login; `features/account` owns the exact `/me` projection and session actions. New health features can later add their own repositories/ViewModels without moving this foundation.

Consumed endpoints: POST `/auth/login`, `/auth/refresh`, `/auth/logout`, `/auth/logout-all`, GET `/me`, all relative to the V1 base. All four POST bodies and the seven-field credential response match current PHP. Logout All requires the **current password** and affects API families, not browser sessions. `/me` exposes only ID/email/status/email verification; no tokens/session identifiers are shown.

One session controller owns rotation. Parallel 401s share one refresh. A late old-token 401 uses the already-rotated pair; original reads retry at most once. Rotation replaces both tokens and persists before publication. Any failed or uncertain refresh ends local authentication; consumed refresh credentials are never automatically replayed. Startup validates persisted credentials through `/me`; unavailable network is not treated as a valid account or empty data. A connection retry/local-sign-out screen is provided.

Credentials are one OS-protected record, bound to environment plus base URL. Before refresh the record is replaced with a token-free pending marker. Process interruption then requires login. Android's pinned storage plugin uses encrypted SharedPreferences with Keystore-protected keys. Its asynchronous disk writes are followed by a small `SharedPreferences.commit()` barrier in MainActivity; the barrier stores only a nonsensitive durability marker. Do not rename the plugin namespace/store or upgrade it without revalidating that barrier. iOS uses nonsynchronizing, unlocked/device-only Keychain with Runner entitlements. No Windows/web credential-storage fallback is shipped.

Logout attempts server revocation, always clears local session and returns to Login. Network/password/server failure is described as unconfirmed remote sign-out. A token-free signed-out marker precedes key deletion. If protected storage itself cannot be written, the UI blocks another login and exposes Retry secure cleanup; it does not falsely claim credentials were erased. Unlock/retry or OS app-data removal may be needed for a persistently broken device store. No mobile implementation can promise successful physical erasure from a failing OS store.

No sensitive application logging, analytics, disk HTTP cache, cookies, permissive TLS callbacks or automatic auth POST retries. Android denies cleartext, backup/device transfer and screenshots; the Flutter shell hides identity in background previews. Native screenshot/Keychain/Keystore behavior still needs device verification. Future phases and release signing are outside 58B.

See `../../docs/task-58b-mobile-v1-foundation-implementation.md` for actual validation and remaining acceptance limitations.
