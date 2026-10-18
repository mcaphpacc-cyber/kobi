# Step 60C-1 — Physical Android Acceptance Preparation

Date: 19 October 2026. **Final decision: BLOCKED.** Read-only preparation against the existing Hostinger backend; no fixture/account/build/install or backend change.

## 1. Architecture Decision

The owner has replaced the isolated-environment strategy. The selected future path is physical Android → KOBI Mobile APK → **https://kobi.monchez.in** → existing Hostinger KOBI backend/database. The previous 60B-1–60B-4 designs are historical, not implementation instructions. No VM, acceptance subdomain, port 8443, private DNS, second installation/runtime/database or certificate will be created for this strategy.

The current local source supports Mobile V1, but the tested public domain does **not presently provide the expected JSON API at the requested `/api/v1` root paths**. TLS succeeds; public GETs return 404 HTML. This is the immediate readiness blocker, not grounds to change Flutter/backend/hosting in this task.

Read the Mobile 58A–59C/60A evidence already inspected in this conversation and rechecked current endpoint/native configuration, routes/controllers/services/repositories, auth/profile boundaries, registration/verification, scheduling and cleanup paths. The request names `task-58f-mobile-v1-product-write-api-readiness-review.md`; the actual existing report is `task-58f-mobile-v1-write-readiness-review.md`. No historical report was modified or duplicated.

## 2. Existing KOBI HTTPS Endpoint

Target origin: **https://kobi.monchez.in**, Hostinger shared hosting, independent of local XAMPP as confirmed by the owner. Candidate mobile API base: **https://kobi.monchez.in/api/v1**. No remote configuration/access was available to establish another authoritative mount; no alternate path/domain was invented or scanned.

All live requests used this origin, normal certificate/hostname verification, no redirects, cookies, credentials or authorization header. The inspected public paths returned no Location header. A question requesting the documented deployed API mount/deployment status was sent to the owner; no authoritative alternative was available when recording these results. Local APP_BASE_PATH `/kobi/public` describes local XAMPP and is not evidence that Hostinger uses that mount.

## 3. Public API Verification

Actual unauthenticated read-only GET results:

| Request | HTTP | Content type | JSON V1 envelope | TLS verify |
| --- | ---: | --- | --- | ---: |
| `/api/v1/diseases?page=1&page_size=1` | 404 | text/html; charset=UTF-8 | No | 0 |
| `/api/v1/diseases/search?q=Migraine` | 404 | text/html; charset=UTF-8 | No | 0 |
| `/api/v1/substances/search?q=Paracetamol` | 404 | text/html; charset=UTF-8 | No | 0 |
| `/api/v1/diseases/migraine` | 404 | text/html; charset=UTF-8 | No | 0 |

All four had cURL error 0. Neither search used the Symptom Checker/counter-incrementing endpoint. Local PublicApiService/repositories implement these as reads; no clinical text, private response or raw HTML error was dumped into the report. Expected successful public catalog/search/detail behavior could not be verified on this target. No medical correctness assessment was attempted.

The concrete failure is **public API availability/routing or deployment at the specified mount**. It does not by itself identify whether Hostinger document-root/rewrite configuration, deployed code version or another mount is responsible. No remote-server fix was attempted.

## 4. Authenticated API Availability

No user credentials were used and no login/refresh/logout/logout-all POST was sent. Even a deliberately invalid login could change limiter state, so it was not used as a supposedly mutation-free probe.

Instead, **OPTIONS** was sent to each requested authentication path: `/api/v1/auth/login`, `/refresh`, `/logout`, `/logout-all`. All returned **404 HTML**, no Allow header/V1 error envelope, TLS verify 0. OPTIONS is an unsupported-method path in the inspected local Router and returns before controller/auth admission; it cannot issue/revoke a token there.

These observations **do not prove the underlying POST implementation is absent**—an upstream layer could treat OPTIONS differently. They fail to establish live route availability. The failed public GETs independently establish the blocker. No synthetic account is known/verified on this domain; do not infer availability from prior localhost acceptance.

## 5. Mobile Route Coverage

All 21 current V1 routes are registered locally. Live coverage at the requested mount is shown below; IDs `1` in OPTIONS probes were routing placeholders only, not retrieved records or chosen acceptance fixtures.

| Expected route, relative to `/api/v1` | Live read-only probe | Result |
| --- | --- | --- |
| POST `/auth/login` | OPTIONS | 404 HTML |
| POST `/auth/refresh` | OPTIONS | 404 HTML |
| POST `/auth/logout` | OPTIONS | 404 HTML |
| POST `/auth/logout-all` | OPTIONS | 404 HTML |
| GET `/me` | OPTIONS | 404 HTML |
| GET `/health-profiles` | OPTIONS | 404 HTML |
| GET `/health-profiles/{p}` | OPTIONS `/health-profiles/1` | 404 HTML |
| GET `/health-profiles/{p}/medical-summary` | OPTIONS | 404 HTML |
| GET `/health-profiles/{p}/conditions` | OPTIONS | 404 HTML |
| GET `/health-profiles/{p}/conditions/{c}` | OPTIONS | 404 HTML |
| GET `/health-profiles/{p}/medications` | OPTIONS | 404 HTML |
| GET `/health-profiles/{p}/medications/{m}` | OPTIONS | 404 HTML |
| GET `/health-profiles/{p}/medication-schedules` | OPTIONS | 404 HTML |
| GET `/health-profiles/{p}/medication-schedules/due-today` | OPTIONS | 404 HTML |
| GET `/saved-symptom-checks` | OPTIONS | 404 HTML |
| GET `/saved-symptom-checks/{id}` | OPTIONS | 404 HTML |
| POST `/health-profiles/{p}/medication-schedules/{s}/checkoff` | OPTIONS | 404 HTML |
| GET `/diseases` | GET with one-row page | 404 HTML |
| GET `/diseases/search` | GET Migraine | 404 HTML |
| GET `/diseases/{slug}` | GET migraine | 404 HTML |
| GET `/substances/search` | GET Paracetamol | 404 HTML |

All 17 OPTIONS probes had TLS verify 0, cURL error 0 and no Allow/Location/API-version indication. No protected GET or checkoff/auth POST was made. Local Router would produce JSON 405/Allow on a recognized unsupported method; these live responses differ, but deployed routing internals are not inferred from the difference alone.

## 6. Authorization Boundaries

Source/test evidence, **not live Hostinger certification**:

- Existing ApiAuthGuard validates Bearer and yields the established authenticated principal; browser cookies are not API identity.
- ApiHealthProfileService uses `findAccessible(profileId, principal.userId)` before projection. Unrelated/missing profile gets safe 404.
- Medication/condition details bind their child ID to that authorized profile; schedule service binds slot/medicine/profile.
- Checkoff requires current active owner/editor (not viewer), rechecked with parent locking; date/timezone/actor come from server. `can_record_checkoffs` is advisory only. Owner and explicitly authorized editor can write; it is not owner-only in the sense of excluding editors.
- Saved checks use the authenticated account's ownership independently of the selected profile; no live rerun of symptom matching.
- Scope disposal/session epochs, exact state-only POST, transaction/upsert and unique slot/day constraint remain in local source.

Eight existing database-free test processes passed **502 checks** (section 18). No cross-account fixture/data was created and no live account/profile enumeration performed.

## 7. Synthetic Account Plan

After the live API blocker is resolved and fixture creation is separately authorized, use the **normal website registration/verification** flow, subject to deployed-UI confirmation:

1. Owner supplies a dedicated **deliverable technical test inbox/alias**, clearly named for acceptance and not a real person's health identity; use a unique acceptance-only password entered through normal UI, never chat/report/source.
2. Open `/register`, enter synthetic name/email/password, submit normal CSRF-protected registration. The inspected controller sends the email verification link and does not authenticate until verified.
3. Receive and follow the genuine `/verify-email?token=...` link in that controlled inbox; never copy its token to this report. Source then verifies the email, authenticates the user and normally establishes their self profile.
4. Sign in normally and confirm profile/account identifiers privately for fixture targeting; do not reuse Account 1 or another existing user's sessions/data.

A literal `@example.invalid` address cannot receive normal verification mail and is **not** the selected plan for this existing backend. Do not set email_verified_at/password state by SQL or introduce an administrative bypass. If normal delivery/registration is unavailable, stop and document the operator-supported normal verification/recovery path. No registration page/session or email flow was exercised remotely here, and no account was created.

## 8. Synthetic Fixture Plan

The inspected normal UI can create the required records once the synthetic account is genuinely verified; deployed parity remains pending:

1. Open Account → Health Records (`/account/health-records`); use the newly verified synthetic self profile, or **Add Family Member** at `/account/health-records/create` for a separate clearly labelled synthetic profile. Leave optional DOB blank; use non-personal permitted values. Record exact created IDs privately; never guess IDs.
2. Open that profile → Medicines → **Add Medicine** (`.../{p}/medicines/create`). Brand: `MOBILE ACCEPTANCE TEST — NOT REAL MEDICATION`; status Current; use only required harmless test text, no actual prescription/advice. Started date today or past; no stop date for Current.
3. From its recorded medicine choose **Schedule** (`.../{p}/medicines/{m}/schedule`). Add active daily time, IANA zone **Asia/Kolkata** (or another explicitly chosen zone), starts on the server's current zone-local day, optional inclusive end day. Add a second distinct time if independent initial Skipped is needed. Keep all active times for that medicine in the same zone.
4. Open Due Today (`.../{p}/medicines/due-today`) and verify named medicine/date/zone and **unrecorded** state; do not pre-mark it during preparation.

Future acceptance: slot A unset → Taken → Skipped → Taken; slot B unset → Skipped; refresh/re-login persistence. Same-state repeat may use separately approved synthetic API requests because UI hides the already-current action; no new controls/automatic replay. The server controls eligibility/local date, including later times today. Future real checkoffs require their own execution authorization, not this report.

Profile-switch tests may use a second owned synthetic profile, created normally. Do not invite real accounts/share data or manufacture hostile cross-account probes on the existing backend. Existing isolated/unit tests cover unrelated-account denials.

## 9. Cleanup Plan

Cleanup capabilities found in current source:

| Item | Normal supported cleanup / limitation |
| --- | --- |
| Test medication + its schedules/checkoffs | Medicines list Delete with explicit **“Delete medicine, schedules and history”** confirmation and CSRF; current schema cascades medicine→slots→checkoffs |
| Schedule alone | Remove/deactivate with confirmation; **retains history**, not permanent cleanup |
| Saved synthetic snapshot | Normal saved-check Delete confirmation/account-bound handler |
| API test sessions | Normal Logout / password-confirmed Logout All for the dedicated synthetic account; revokes credentials, does not promise immediate erasure of retained audit/history rows |
| Extra conditions | Normal synthetic record Delete if separately created/authorized |
| Health profile/account | **No normal user account-deletion or health-profile-deletion/archival route/method was found in the scoped current source. Complete UI cleanup cannot be promised.** |

Before creating fixtures, owner must agree whether to retain the clearly labelled synthetic account/profile or use a separately approved, existing operator cleanup process. Do not invent a deletion API or recommend ad hoc SQL deletion of an existing shared backend. Permanent deletion must use genuine UI confirmation at deletion time if the tool/UI requires it; do not simulate it. Nothing was deleted here.

## 10. Production/Demo Safety Assessment

Hostinger demo is **not assumed disposable**. Current API 404s prevent live testing, and its DB/schema/code parity has not been remotely inspected. A verified dedicated synthetic account/profile with no real memberships can provide ordinary account/profile separation under the reviewed rules, but it still writes to the existing shared environment during future acceptance.

Synthetic records will be visible in that account's normal web/mobile interfaces and potentially to existing authorized operators/backups, not to be represented as segregated from the backend's administration/retention. Test writes may affect shared operational metadata: auth-family/refresh history, rate counters and logs. Creating a saved symptom snapshot via a future normal checker submission may also increment shared search counters; do not perform it incidentally or read a real user's saved history. Agree to that limited synthetic operation or defer detail testing until a legitimate synthetic snapshot exists.

Checkoff code changes only the bound synthetic occurrence, not medicine dose/status or another profile. This is local source/test evidence; Hostinger behavior remains unverified. No real health profile, medication, condition, saved check, document, insurance record or real-user credential may be used. No database copy or direct SQL cleanup. Owner acceptance of this controlled shared-backend scope and incomplete account/profile cleanup is required before writes, not inferred from the term demo.

## 11. Physical Android Requirements

Android **API 24+**; manifest target 36. Use the known owner-approved phone with sufficient free space for the chosen APK and installation overhead. Current local universal debug artifact is about 181 MB; final endpoint build size must be checked later. Existing package ID is `org.kobi.kobi_mobile`; confirm whether already installed so replacement/uninstall does not destroy real local app state.

The public target can be used over Wi-Fi or mobile data; Windows LAN IP, private DNS, 8443/firewall/router changes are no longer relevant. Phone's actual DNS/TCP/TLS path has not been tested and must be verified there later. Host HTTPS success proves neither phone connectivity nor native storage behavior.

Use an owner-approved transfer/manual installer or USB/ADB if desired; **USB debugging is optional**, not required for a manual APK install. Owner may need to allow installation from the chosen trusted source; no setting was changed here. App requires INTERNET and its app-scoped receiver permission, not SMS/location/contact/health-record permissions.

Later verify cold launch/process restart, session restore/rotation, background privacy/foreground refresh, system Back, keyboard/touch/scroll, actual screen/large fonts/orientation and native secure storage. No phone was connected, installed, modified or inspected during this task.

## 12. HTTPS Assessment

Fresh public GET/OPTIONS connections all succeeded with **CURLOPT_SSL_VERIFYPEER=true**, **CURLOPT_SSL_VERIFYHOST=2**, **CURLINFO_SSL_VERIFYRESULT=0**; redirects disabled and no custom trust context/CA override. Certificate metadata captured on the public GETs:

- Subject CN **www.kobi.monchez.in**.
- Issuer **Let's Encrypt YR2**.
- SAN **DNS:*.monchez.in, DNS:www.kobi.monchez.in**; wildcard matches kobi.monchez.in.
- Valid **2026-08-21 18:51:15 UTC → 2026-11-19 18:51:14 UTC**, current at this review date.

Normal host certificate verification passes. An up-to-date compatible Android with correct clock should be able to validate the public chain, but actual native trust is pending device testing. No arbitrary CA installation, TLS bypass, HTTP fallback or certificate change is justified. **The observed blocker is API response/routing, not TLS.**

## 13. Flutter Endpoint Configuration

`Environment.fromBuild` reads compile-time `KOBI_ENV` and `API_BASE_URL`; HTTPS base must end exactly `/api/v1`. No default API host exists. The intended origin remains https://kobi.monchez.in, with candidate base https://kobi.monchez.in/api/v1. Confirm a documented authoritative deployed mount before building; current root paths fail, and local `/kobi/public` is not proof of remote configuration.

Future command **only after live API readiness and explicit build authorization**:

```powershell
& 'mobile/kobi_mobile/tool/flutter.ps1' build apk --debug --no-pub --dart-define=KOBI_ENV=production --dart-define=API_BASE_URL=https://kobi.monchez.in/api/v1
```

Here production labels the existing shared target; it is still a debug acceptance build, not release signing/publication. If the owner supplies another authoritative HTTPS mount on this domain, adjust only the define accordingly and document why. No source/production configuration edit is needed. Client defines contain URL/environment label only, never server passwords, DB credentials, API keys, TLS keys or tokens. Native HTTP has no permissive TLS callback. No build/configuration change occurred.

## 14. Step 60C Acceptance Checklist

Execute only after blocker resolution, fixture/cleanup approval and device/build authorization:

| Area | Eventual physical-device verification |
| --- | --- |
| Authentication | Launch; synthetic login/rejection; `/me`; cold/session restoration; real access expiry and serialized refresh; normal Logout and password-confirmed Logout All; remote revocation/uncertain outcome wording |
| Profiles | Selector; synthetic self/family switch; profile view; no old profile data after a pending request/switch |
| Health | Dashboard, aggregate-only Summary, synthetic conditions and medicines list/detail, safe null/empty/error states |
| Daily | Read-only schedule; Due name/time/zone/server date/capability; explicit Taken/Skipped, both corrections, same-state repeat via approved method, uniqueness and refresh persistence |
| Saved checks | Account-level empty/list/detail; approved synthetic historic snapshot ordering/labels/disclaimer; no real saved-check access or unapproved counter-generating submission |
| Knowledge | Public catalog/search/detail/references and registry names-only; public requests without bearer; no medical correctness testing |
| Layout/device | Phone size, large text, touch, scroll, keyboard, system Back, orientation and accessibility; loading/errors; background/foreground/privacy/process restart |
| Network | Normal native TLS; temporary disconnect/timeout, explicit retry, no optimistic checkoff or offline queue, reconcile uncertain writes; safe authentication expiry |
| Cleanup | Exact synthetic medicine/snapshot deletion with confirmation, synthetic API revocation, agreement on retained account/profile/operator process; no shared-real-data changes |

Use only prepared synthetic records; never use the owner's real profile/medicines/records/documents/insurance to fill a missing case. Do not repeat submissions merely to obtain a desired result. Host/source/unit tests do not replace this native checklist. Full 60C was not started.

## 15. Risks

| Risk | Evidence / required response |
| --- | --- |
| Live API mount/deployment mismatch | Four public GETs return 404 HTML; confirm authoritative mount/deployed release before build/fixtures |
| Mistaking local source for deployed backend | Local tests pass, but no deployed parity/auth forwarding/schema evidence; do not certify Hostinger from localhost reports |
| Using real account/data | Dedicated verified technical test identity only; no Account 1/real profile fallback |
| Verification bypass | Use deliverable test inbox and actual email link; `.invalid` cannot receive normal mail |
| Incomplete cleanup | No normal account/profile deletion path found; agree retention/operator cleanup before creation |
| Shared metadata/counters | Auth and optional synthetic checker operations affect shared history/rate/search/log metadata; explicitly scope future actions |
| Device install/session loss | Confirm package state/install method; native trust/storage/lifecycle untested |
| Expiring certificate | Current leaf ends 19 November 2026 UTC; revalidate normally at later test time, no trust workaround |

## 16. External Prerequisites

1. **Resolve the existing-domain API availability blocker**: owner supplies the documented authoritative HTTPS API mount or confirms/deploys correct server routing/release through a separately authorized process. This task authorizes no deployment or hosting fix.
2. Verify the resulting public JSON routes and authenticated admission/forwarding against the actual deployed version. OPTIONS alone does not validate auth or persistence.
3. Owner-approved deliverable test inbox, normal verification/registration availability and later fixture authorization; acceptance-only password kept private.
4. Cleanup/retention decision for synthetic account/profile and shared auth/counter metadata before account creation.
5. Known physical Android, approved install/build inputs and actual native connectivity/trust/storage/layout checks.

No separate domain/VM/backend/private DNS/TLS work remains selected. These are deployment/manual/device prerequisites against the chosen backend, not permission to resurrect 60B designs.

## 17. Final Readiness Decision

**BLOCKED.** Existing TLS validates, but the required public APIs at https://kobi.monchez.in/api/v1 return non-JSON 404s and the non-mutating auth/private probes do not establish usable route availability. The current target therefore cannot be declared safe/usable for full Mobile V1 acceptance yet. A documented correct mount or deployed API availability is needed first; root cause is not proven and no fix was attempted.

Normal synthetic creation/verification is understood from current source, subject to deployed parity and delivery. Full account/profile UI deletion is unavailable in the reviewed source. Phone/build/fixtures remain future tasks. No implementation-level mobile regression was found; no full Step 60C work started.

## 18. Validation Performed

**Live read-only HTTPS:** exactly **4 public GETs + 17 unauthenticated OPTIONS**, all to the explicitly selected Hostinger origin, all TLS verify 0/cURL error 0, all 404 HTML. No POST, credential, token, protected GET, symptom submission, browser registration/session, mutation endpoint or remote-admin access. Router source was checked before selecting unsupported-method probes; local guard's missing-token path can update limiter counters, so it was not deliberately exercised. Ordinary host access logs may record read-only traffic; zero remote internal/cache/log side effects cannot be independently audited from HTTP responses.

**Local database-free regressions**, all existing tests, exit 0:

| Test | Passing checks |
| --- | ---: |
| api-auth.php | 103 |
| api-identity.php | 44 |
| api-health-profiles.php | 20 |
| api-medications.php | 28 |
| api-health-conditions.php | 65 |
| api-saved-symptom-checks.php | 58 |
| api-medication-checkoffs.php | 76 + 52 reused browser schedules |
| public-api-v1.php | 56 |
| **Total** | **502** |

These are in-memory source/contract/authorization checks, not deployed Hostinger tests. No DB connection/SQL, isolated DDL, normal auth family or local HTTP mutation was needed. No old assertion changed. No Flutter test/build/native acceptance rerun merely to mask the live API blocker. Source inspection covered normal UI setup/cleanup, cascade rules, profile/saved ownership, server-authoritative checkoff and endpoint configuration.

## 19. Files Changed

Created only `docs/task-60c1-physical-android-acceptance-preparation.md`. SHA-256 baseline: **693 existing nonignored project files**; final comparison found no existing-file changes/removals and only this report added. Inventory excludes .git, vendor, storage and ignored/generated caches. Previous reports/source/config/tests remain unchanged.

## 20. Confirmation of No Database Mutation

No direct DB connection/query/write, schema/mode change, data/account/profile/medicine/schedule/checkoff creation/deletion, auth-family issuance/revocation or real-data access was performed. Only public GET/OPTIONS methods were sent remotely; no authenticated or mutation requests. No claim of an independently audited remote database snapshot is made.

## 21. Confirmation of No Backend Mutation

No backend/application/API/hosting/runtime/configuration/test/schema changes, deployments, restarts, fixes, separate environment creation or remote control-panel operation. No source credentials copied or logged.

## 22. Confirmation of No Flutter Mutation

No Flutter/native/source/configuration change, APK build/install, phone settings/modification or AVD creation. Future build command is documentation only.

## 23. Confirmation of No DNS/TLS/Firewall Change

No DNS/hosts/private DNS, certificate/key/trust-store/TLS/firewall/router/port change. All connections used normal verification with no HTTP fallback or certificate override. No arbitrary CA installed.

## 24. Confirmation of No Git Operation

No Git command/staging/commit/push/reset/checkout/merge/rebase/branch/history mutation. File-integrity hashes only.

**STOP after Step 60C-1 preparation report. No account/fixture/build/install/checkoff/full 60C execution.**
