# Step 58A — KOBI Mobile V1 Foundation Readiness

Readiness assessment: 18 October 2026. Scope: inspection, database-free validation and this blueprint only. No mobile project or backend implementation is authorized by this document.

## 1. Executive summary

Begin an Android-first, read-focused Flutter application using the existing opaque-token `/api/v1` APIs. The available endpoints support account sign-in, accessible profile selection, a modest personal dashboard, aggregate Medical Summary, recorded conditions and medications, schedules/Due Today, account-owned saved symptom checks, and public disease/substance discovery. No additional backend feature is required before starting this scoped implementation.

Keep the product promise precise: Medical Summary is five live counts, not a portable complete clinical record; Due Today displays recorded schedules and existing checkoff states without recording new ones; substance search provides registry names, not prescribing information. A user with no profiles or records gets an honest empty state. V1 cannot create the missing data. Existing web workflows remain separate.

Flutter is recommended for a small team's consistent Android/iOS UI and typed client, subject to confirming team Dart capacity. Principal trade-off: learning Dart and building native screens rather than reusing the existing PHP/Bootstrap/JavaScript UI. A reachable, device-trusted HTTPS environment is required for real-device integration and release acceptance; it is not a missing API feature or reason to postpone client foundation work.

## 2. Current API baseline

Source authority: `AGENTS.md`; `routes/web.php`; `bootstrap/app.php`; `app/Core/{Request,Router,PublicApiResponse}.php`; public API controllers; `ApiAuthController`; `ApiAuthService`, guard, limiter and repository; all seven private resource projection services; their canonical services and repositories; `PublicApiService`, `PublicSearchService`, and `DiseaseReferenceService`. UI references: `app/Views/account/health-dashboard.php` and `public/assets/css/theme.css`. Contracts compared: `docs/public-api-v1.md`, `docs/authenticated-api-v1.md`, and the Step 50–57 implementation context. Paths here are repository-relative.

There are **20 implemented V1 routes: four public GETs, four authentication POSTs, twelve private GETs**. Section 12 enumerates all of them. PHP retains controller → service → repository separation and DI. The API branch bypasses browser session startup, buffers accidental output and returns safe JSON on unexpected errors. A mobile client is an HTTP consumer, not another PHP architecture layer.

Success: `{success:true,data:object|array,meta:{api_version:"v1",...}}`. Failure: `{success:false,error:{code,message,fields?},meta:{api_version:"v1"}}`. Public validation can include `fields`; current authenticated controller errors do not project field maps. HTTP status and envelope must both agree. JSON uses UTF-8; responses carry `Cache-Control: no-store` and `X-Content-Type-Options: nosniff`. Serialization/size failure produces safe 500; the whole-response guard is 262,144 bytes. Missing data is not silently truncated. Unsupported methods return 405/Allow; HEAD has no JSON body. No session cookies or CORS grant is required for native clients.

Private endpoints and authentication require HTTPS. Server-side Authorization extraction uses `HTTP_AUTHORIZATION` or `REDIRECT_HTTP_AUTHORIZATION`, rejecting conflicting values. Keep existing forwarding/configuration intact. Native requests need no browser CSRF token; this does not change web CSRF behavior.

Documentation differences found, without changing the documents:

| Existing wording | Current code / mobile consequence |
|---|---|
| Public contract says normal auth deployment is still pending | Historical statement; authenticated contract and completed deployment reports record deployment. Routes are implemented. This readiness pass did not recheck live hosting or deployment metadata. |
| Auth contract introduction lists only Steps 50–53; its early route table omits saved checks | Later sections and current routing contain summary, conditions, schedules and saved checks. Use the complete inventory below. |
| Earlier auth sections say private resources/future resource authorization are deferred or that no nested resources were added | Those statements describe the original increments, not today's cumulative surface. Current twelve private reads exist; inaccessible profiles currently return 404, not an assumed 403. |
| Public documentation gives an HTTP localhost development example | Public reads may use it locally, but private routes reject HTTP. Mobile must use configured HTTPS with certificate verification. |

Detailed resource projections, pagination and lifecycle sections agree with the inspected implementations. Browser-only fields/features must not be inferred from similarly named API resources.

## 3. Recommended mobile technology

**Flutter, Android first, future iOS from the same client codebase.** Its typed models and consistent widget system suit profile-aware forms, readable health lists, explicit loading/error states and an accessible visual system. The server remains PHP/MariaDB; no framework-specific backend change is needed. Flutter supports Android and iOS; choose the SDK and minimum OS targets during 58B against the then-current [supported platform matrix](https://docs.flutter.dev/reference/supported-platforms).

Use a small MVVM-style UI/data separation: screens → ViewModels → repositories → typed API service, with an independently tested session coordinator. This follows the shape of Flutter's [architecture guide](https://docs.flutter.dev/app-architecture/guide). Favor constructor injection, immutable DTOs and SDK `ChangeNotifier`/`Listenable` initially. Do not add an elaborate domain/use-case layer for simple reads. These are project recommendations, not backend requirements.

## 4. Technology trade-offs

| Criterion | Flutter | React Native |
|---|---|---|
| Android first / future iOS | Suitable; shared screens with platform-specific integration where necessary | Suitable; shared React/TypeScript with native platform integration |
| Existing KOBI reuse | JSON contracts and visual language; no PHP/Bootstrap screen reuse | JSON contracts and some JavaScript familiarity; existing vanilla JS is not evidence of React expertise or reusable React screens |
| API-driven typed models | Dart fits explicit DTOs and date wrapper types | TypeScript fits explicit DTOs; runtime parsing still required |
| Small-team maintenance | One widget/UI approach and straightforward ViewModels; Dart learning cost | Attractive if team already knows React; dependency/native integration choices still need ownership |
| Secure tokens | Requires reviewed platform secure-storage integration | Also requires platform secure-storage integration; not ordinary AsyncStorage |
| UI polish and accessibility | Predictable branded components; test native accessibility/text scaling | Strong native ecosystem; test accessibility and consistency on both platforms |
| Offline later | Repository boundary can add controlled storage later | Same capability with disciplined repositories |

React Native remains a credible alternative if confirmed team React expertise outweighs Dart onboarding. Its own [security documentation](https://reactnative.dev/docs/security) identifies AsyncStorage as unencrypted and notes that sensitive storage is not bundled. Framework choice does not itself protect credentials. No evidence in this PHP/vanilla-JS repository establishes an existing React Native or Flutter investment. Native Kotlin alone adds a second UI effort for future iOS; a WebView wrapper would not provide the proposed native API/session architecture. Neither is a better default on the recovered evidence.

## 5. Mobile architecture

Proposed structure, **not directories created in this task**:

```text
lib/
  app/             bootstrap, dependency composition, environment, theme
  navigation/      app shell, route definitions, auth/profile guards
  core/api/        transport, envelope parsing, typed failures, pagination
  core/session/    coordinator, token bundle, session epoch, secure-store interface
  core/time/       calendar-date, wall-time and UTC timestamp handling
  core/ui/         profile banner, cards, loading/empty/error components
  features/
    auth/          login/account views and ViewModels
    profiles/      DTOs, repository, chooser and profile screen
    dashboard/     composed overview ViewModel and screen
    summary/       aggregate DTO/repository/screen
    conditions/    DTO/repository/list/detail
    medications/   DTO/repository/list/detail
    schedules/     slot/occurrence DTOs, repository, schedule/due screens
    saved_checks/  historical snapshot DTO/repository/list/detail
    knowledge/     diseases, references and substance search
```

Keep session and selected-profile state application-scoped; ViewModels own screen state. Repositories reuse in-memory results within the same account/profile scope. Dashboard composes existing repositories, not a fabricated `/dashboard` endpoint. Use one DTO per actual projection; avoid duplicating domain models unless later transformations justify them. A small shared typed `Result`/failure model separates transport, parsing, authorization and valid data.

Use a native four-area shell, readable cards and the existing teal `#0F766E`, blue `#2563EB`, light background `#F8FAFC` and restrained status colors as starting visual tokens. Preserve KOBI terminology without porting Bootstrap markup. Add large touch targets, screen-reader semantics, text scaling, dark-mode contrast review and plain-language states. Keep profile identity visible above personal health content.

## 6. API client architecture

Configure an absolute HTTPS base ending `/api/v1`; append only known relative resource paths. Distinct public and authenticated request policies may share transport, but public requests never attach Authorization, cookies or the token bundle. Login/refresh send only their allowed JSON fields. Only protected reads/logout actions attach `Authorization: Bearer <current access token>`. Do not attach refresh credentials to ordinary requests.

GET: no JSON body; encode approved scalar query parameters once. POST auth: `Content-Type: application/json`, `Accept: application/json`, flat object; logout sends `{}`, not an empty body or array. Request parser rejects duplicate/nested/unsupported fields; maximum auth body 8 KiB. Passwords are not trimmed; optional device label is at most 80 characters, without controls. Use a generic user-chosen device description, not hardware identifiers.

Set finite connect/request deadlines (initial proposal: 10-second connect, 30-second overall, configurable). Cancel obsolete screen loads, suppress stale search responses, and deduplicate identical in-flight GETs. Do not follow redirects with credentials or accept an origin change. Verify hostname/certificate normally; no permissive certificate callback, HTTP fallback, custom trust bypass or embedded server secrets. Use a maintained HTTP adapter behind an interface; select/pin packages in 58B after platform review, not here.

Parse expected envelope and each required field/type. IDs stay strings. Ignore unknown additive fields; reject missing/wrong-type required fields and unsupported snapshot versions with a recoverable compatibility error. Do not turn malformed JSON/HTML or a 500 into an empty collection. Bound response handling; do not truncate clinical text or normalize unknown clinical statuses into apparently valid values.

Ordinary GET transport/5xx failure: retain only authorized in-memory content for the same scope, mark refresh failure, and allow explicit retry; at most one bounded automatic retry for a clearly transient GET, never a retry storm. Honor 429 `Retry-After`, disable repeated actions until allowed, and do not refresh in response to 429/503. Do not automatically retry login, refresh, logout or logout-all after an uncertain network outcome.

## 7. Authentication/token architecture

Preserve opaque tokens; no JWT decoding, new issuer, cookie identity, API key or native Google exchange. Source: `ApiAuthService` and `ApiAuthGuard`.

| Fact | Actual behavior |
|---|---|
| Login input | `email`, `password`, optional `device_label` |
| Login eligibility | Active account, verified email, genuine local password; Google-only placeholder passwords do not qualify |
| Token format | Purpose-prefixed opaque random credentials; server stores purpose-separated digests |
| Login/refresh data | Exactly `token_type`, `access_token`, `expires_in`, `refresh_token`, `refresh_expires_at`, `session_id`, `session_expires_at` |
| Access lifetime | Up to 600 seconds, capped by absolute family expiry |
| Refresh lifetime | 14-day sliding inactivity deadline, capped by 30-day absolute family deadline |
| Rotation | Both credentials replaced; old access stops working immediately |
| Replay | Consumed refresh reuse revokes the whole family; no grace/recovery window |
| Device limit | Five active families/account; another login revokes the oldest live family |
| Revocation | Family/logout-all, account eligibility loss or password-hash change; re-enabling an account does not restore revoked families |
| Logout-all | Valid access plus `current_password`; API families only, not browser sessions |

Session states: restoring → anonymous / authenticated / refreshing / signing-out; connectivity failure is separate from authenticated eligibility. Store token deadlines, not invented JWT claims. Use `expires_in` with a monotonic in-process deadline and a small safety margin; use UTC deadlines for resume checks. Clock uncertainty never overrides server admission.

Required refresh algorithm:

1. All private requests share one coordinator and session generation. No second isolate/background worker may rotate independently. A request records the account epoch and token generation it used.
2. If access is known expired, or a protected resource returns 401, check that the refresh and absolute deadlines have not passed. Without a usable refresh, clear the private session and request genuine login. Login's `AUTHENTICATION_FAILED` must not trigger refresh.
3. If another request already installed a newer generation, retry the GET once with that token. Otherwise join/create one single-flight refresh operation. Waiting requests must not each send refresh.
4. Submit the current one-use refresh credential once. Pause new private admissions during rotation. On successful response, validate the complete bundle, securely persist it, then publish the new generation and release waiters. Retry each original GET at most once. A second 401 terminates that session; no recursive interceptors.
5. A definitive refresh 401 clears secrets and private state. If refresh may have reached the server but its success response was lost, or the replacement bundle cannot be stored, require login: **never replay the old refresh token**. A refresh 429 is a rate error, not proof of account revocation; wait for explicit retry after `Retry-After` only when a definitive server rejection establishes that rotation did not occur. For ambiguous transport/5xx outcomes, do not reuse it.
6. Increment the account epoch on sign-out/new login. Late responses, including successful refreshes, cannot reinstall an old session or repopulate another account's screens. A request already admitted by the server may finish during revocation; discard it locally when its epoch is obsolete.

Access-auth failures are generic: the client cannot tell expired from revoked/account-disabled by reading 401 text. At most one coordinated refresh discovers whether recovery is possible. Do not show invented reasons. Login budget: 30/direct-IP/15 minutes plus five failed attempts/email/15 minutes. Refresh budgets: 60/IP/minute and 30/family/minute. Ordinary private reads: 120/family/minute; failed-access budget 60/IP/minute. Share requests across dashboard/screens and avoid polling. Limiter unavailability fails closed with 503.

## 8. Secure storage strategy

Introduce a `SecureSessionStore` abstraction backed by reviewed OS facilities. On Android, use encrypted app-private storage with a key protected by Android Keystore; Keystore protects cryptographic keys, not arbitrary token strings directly. On iOS, use Keychain with a device-only, unlocked accessibility policy and no synchronization. Validate the selected Flutter plugin's actual implementation, backup behavior and supported OS versions in 58B. These properties follow [Android Keystore guidance](https://developer.android.com/privacy-and-security/keystore) and Apple's [WhenUnlockedThisDeviceOnly attribute](https://developer.apple.com/documentation/security/ksecattraccessiblewhenunlockedthisdeviceonly).

Persist one protected replacement bundle: access token, refresh token, their deadlines/receipt metadata, absolute session deadline, public session identifier and local generation. Access is used from memory while running; persisting the complete encrypted bundle enables safe restart after rotation. Never retain the previous refresh token. Test atomic replacement and failure handling; if the adapter cannot guarantee this, fail closed to login rather than selecting between old credentials.

Never persist passwords, full health responses, bearer headers, raw request/response logs or credentials in preferences/SQLite/plain files/analytics/crash reports. Exclude secure-session material from backup/restore to another device. Ordinary preferences may contain nonsensitive theme/environment choices; selected health-profile identity stays memory-only in V1. Key invalidation/unavailable protected storage means re-login, not plaintext fallback.

Logout normally calls the server with valid access, then clears secure bundle, in-memory repositories, selected profile, navigation history and pending requests. If access expired, one coordinated refresh may precede logout. On network failure, clear locally and explain that server revocation was not confirmed; do not retain credentials indefinitely for a retry or claim all sessions ended. Logout-all requires an explicit password dialog, no persisted password and no automatic POST retry. After confirmed success clear local state; other devices are denied on their next admission. If the response is lost, state is uncertain and the local client still signs out; do not claim confirmed remote cleanup. Browser sessions remain independent.

## 9. Navigation architecture

Four primary destinations: **Home, Health, Knowledge, Account**. Anonymous users can browse Knowledge and reach Login through Home/Health/Account. Authenticated Home is a small dashboard with current profile, counts, Due Today preview and shortcuts. Health contains profile details, summary, conditions, personal medicines, schedules and Due Today. Saved Checks is an account-owned destination reachable from Home and Account; it must not imply association with the selected health profile. Knowledge contains disease catalog/search/detail and substance search. Account shows only `/me` identity and session actions.

Use nested route stacks for details and preserve Back behavior within each area. Session guard gates private routes; profile guard gates only profile-owned routes. Sign-out replaces all private stacks. Native public disease navigation uses returned slug; browser `public_url`/`registry_url` are optional external-link metadata, not mobile JSON endpoints. Open approved source-reference HTTPS links externally without bearer/cookie transfer. No embedded browser login handoff or HTML scraping.

## 10. Profile-selection strategy

Fetch the accessible list after authenticated identity. Zero profiles: show “No accessible health profiles,” no synthetic self profile and no dead Add button. The current web setup can be mentioned as a separate workflow; browser authentication is independent. One profile: automatically select it. Multiple: require explicit choice before showing private profile content; never infer self/owner from list order.

Selection is a memory-only `{accountEpoch, profileId}` reference to a current list item. Display `full_name` and `profile_type` on all profile-owned screens. No API membership role/DOB/gender exists in the profile projection. Revalidate detail on entering a profile area and refresh the list on resume or access failure. Server checks remain authoritative for every request.

On switch, cancel/ignore old-profile loads, clear scoped screen data and pagination, and fetch the new scope. Repository keys include account epoch and profile ID, plus child ID/query. A profile 404 clears its selection, refreshes accessible profiles, and requests a new explicit choice; do not silently substitute another person. Detail child 404 hides that child without claiming ownership facts. Saved Checks and Account continue to work when no profile exists.

## 11. Mobile V1 screen inventory

R = required for scoped V1; O = optional presentation split; D = deferred functionality. The common states in section 16 apply to every row. API keys below resolve to exact routes in section 12. “Profile” means visible, authorized selection, not an account ID.

| Screen / classification | Purpose and primary data | Auth / profile | API keys | Loading / valid empty / error | Navigation destination |
|---|---|---|---|---|---|
| Login — R | Email/password sign-in, optional device label | No / no | A1; then I, P | Submit progress; no empty result; generic credentials error, validation or network state | Home or Profile Selector; Knowledge remains public |
| Home/Dashboard — R | Profile banner; five summary counts; Due Today preview; feature shortcuts | Yes / for health cards only | I, P, S, DUE; M for medicine labels | Independent card loading; zero-profile guidance or zero counts; card-specific retry, not whole-screen “no data” | Health/Profile Selector, Summary, Due Today, Saved Checks, Knowledge |
| Profile Selector — R | Accessible names and profile types | Yes / no | P; PD on selection | List progress; no profiles; retry/access state | Chosen profile's Home/Health |
| Health Profile — R | Selected name/type and health shortcuts only | Yes / yes | PD | Progress; no invented demographics; profile 404 returns chooser | Summary, Conditions, Medicines, Schedule, Due Today |
| Medical Summary — R | Explicit aggregate overview: five counts | Yes / yes | S | Progress; successful zero counts remain zero; scoped retry/404 | Conditions and Medicines; allergy/procedure counts have no detail link |
| Conditions — R | Recorded names, statuses, optional dates | Yes / yes | C | First-page and append progress; no recorded conditions; scoped retry | Condition Detail, Health |
| Condition Detail — R | Exact five-field recorded condition | Yes / yes | CD | Progress; missing dates “Not recorded”; missing record state on 404 | Back to Conditions |
| Personal Medicines — R | Recorded brand/generic, strength, status; no prescribing claim | Yes / yes | M | Progress; no recorded medicines; scoped retry | Medicine Detail, Schedule, Due Today |
| Medicine Detail — R | Eleven allowed fields with nullable values handled | Yes / yes | MD | Progress; absent fields “Not recorded”; record 404 | Medicines; Schedule list optionally highlight this medicine locally |
| Medication Schedule — R | Daily slot configuration, including inactive rows | Yes / yes | SCH, M | Page/label loads separately; no schedules; explicit retry if labels unavailable | Medicine Detail, Due Today |
| Due Today — R | Slot-local date/time/zone and recorded taken/skipped/null state | Yes / yes | DUE, M | Page progress; no scheduled occurrences today; scoped retry | Medicine Detail, Schedule, Home; no Taken/Skipped action |
| Saved Symptom Checks — R | Historical checked/saved instants and counts | Yes / no | SC | Page progress; no saved checks, no unsupported Create button; retry | Saved Check Detail |
| Saved Check Detail — R | Historical symptoms, summary, ordered matches, explanation and disclaimer | Yes / no | SCD | Progress; 404 unavailable; incompatible snapshot error; no fresh matching | Saved list; Knowledge search optional, without inventing a disease slug |
| Disease Catalog — R | Names, system, optional Hindi name | No / no | K | Page progress; valid empty catalog; retry | Disease Detail, Disease Search, Substance Search |
| Disease Search — R, integrated into Catalog | Name query/results; no clinical relevance claim | No / no | KS | Debounced search; prompt for 2+ characters/no matches; validation/network retry | Disease Detail, Catalog |
| Disease Detail — R | Five sections, cause names, urgency note, section-level references | No / no | KD | Progress; omit/label unavailable optional text; 404 unavailable; retry | Catalog/search; external reference links |
| Substance Search — R | Canonical names with “Legacy registry/substance information” label | No / no | SUB | Query prompt/progress; no matches; validation/network retry | Knowledge; optional external registry link, no native rich detail |
| Account — R | Account ID, email, status, email_verified; session actions | Yes / no | I | Progress; no fabricated contact fields; session/network error | Logout, Logout All dialog, Saved Checks; Login when anonymous |
| Logout — R action/dialog, no permanent screen | End this API session; truthful local/server result | Yes / no | A3, A2 only if needed | Disable duplicate submit; no empty state; network outcome explained | Anonymous Home/Knowledge |
| Logout All — R Account dialog | Password reconfirmation and API-family revocation | Yes / no | A4, A2 only if needed | Progress; no empty state; password/rate/network error, never automatic replay | Anonymous Account/Login after completion |

O: separate Disease Search page instead of inline search, expanded dashboard recent-check preview using SC page 1, separate Knowledge landing page. None needs a new backend endpoint. Avoid extra initial dashboard calls solely for optional previews.

D: registration/password recovery/email verification/native Google sign-in; account/profile editing; health-record CRUD; allergy/procedure detail; insurance/documents/timeline; medicine/schedule/checkoff writes; reminders/notifications; symptom checker submission/save/delete; sharing/mobile verification; rich Medicine and Treatment content. Do not show working-looking controls for deferred functions. Optional external web help is navigation, not an API implementation or shared native session.

## 12. Screen → API mapping

All paths below are relative to the configured origin/application mount and begin `/api/v1`. `{p}`, `{m}`, `{c}`, `{s}` are returned decimal string IDs; `{slug}` is a returned public disease slug. Never substitute a user ID for a profile ID.

| Key | Exact existing endpoint | Screens / status |
|---|---|---|
| A1 | POST `/api/v1/auth/login` | Login — ready |
| A2 | POST `/api/v1/auth/refresh` | Session coordinator — ready |
| A3 | POST `/api/v1/auth/logout` | Account logout — ready |
| A4 | POST `/api/v1/auth/logout-all` | Account Logout All — ready |
| I | GET `/api/v1/me` | Account, authenticated bootstrap — ready |
| P | GET `/api/v1/health-profiles` | Selector, Home — ready |
| PD | GET `/api/v1/health-profiles/{p}` | Profile, selection guard — ready |
| M | GET `/api/v1/health-profiles/{p}/medications` | Medicines, schedule label lookup — ready, unpaginated |
| MD | GET `/api/v1/health-profiles/{p}/medications/{m}` | Medicine Detail — ready |
| S | GET `/api/v1/health-profiles/{p}/medical-summary` | Home, aggregate Summary — ready |
| C | GET `/api/v1/health-profiles/{p}/conditions` | Conditions — ready, paginated |
| CD | GET `/api/v1/health-profiles/{p}/conditions/{c}` | Condition Detail — ready |
| SCH | GET `/api/v1/health-profiles/{p}/medication-schedules` | Schedule — ready, paginated |
| DUE | GET `/api/v1/health-profiles/{p}/medication-schedules/due-today` | Due Today, Home preview — ready, paginated |
| SC | GET `/api/v1/saved-symptom-checks` | Saved Checks — ready, paginated |
| SCD | GET `/api/v1/saved-symptom-checks/{s}` | Saved Check Detail — ready |
| K | GET `/api/v1/diseases` | Catalog — ready, paginated |
| KS | GET `/api/v1/diseases/search?q=...` | Disease Search — ready, capped results |
| KD | GET `/api/v1/diseases/{slug}` | Disease Detail — ready |
| SUB | GET `/api/v1/substances/search?q=...` | Substance Search — ready, capped results |

Use only these versioned JSON resources. Do not consume legacy `/api/substances/search`, unversioned symptom-matcher AJAX, browser session endpoints, HTML `/search` or `/account` pages, or internal controller endpoints as native APIs. No unified V1 search or V1 substance-detail route exists. Knowledge can offer two independent search modes.

## 13. Current API capabilities

The following are explicit DTOs recovered from projection code, not raw database rows. All IDs are decimal **strings**; do not coerce even substance unsigned-bigint IDs to floating point. Profile/child private path IDs currently require positive signed-INT syntax up to 2147483647, without leading zeros. Labels are plain text.

| DTO | Exact fields and important types |
|---|---|
| Identity | `id:string`, `email:string`, `status:string`, `email_verified:bool`; eligible success currently active/true |
| Profile | `id:string`, `full_name:string`, `profile_type:self\|family\|dependent`; no role, DOB, gender or contacts |
| Aggregate Summary | `profile:{id,full_name,profile_type}`, `counts:{conditions:int,historical_conditions:int,current_medications:int,allergies:int,procedures:int}` |
| Condition | `id:string`, `condition_name:string`, `status:active\|resolved\|chronic\|historical`, `diagnosed_on:date?`, `resolved_on:date?` |
| Personal Medication | `id:string`, `brand_name:string`, `generic_name:string?`, `strength:string?`, `dose:string?`, `frequency:string?`, `route:string?`, `status:current\|stopped\|historical`, `started_on:date?`, `stopped_on:date?` |
| Schedule | `id:string`, `health_medicine_id:string`, `local_time:string(HH:MM)`, `timezone:string`, `starts_on:date`, `ends_on:date?`, `is_active:bool` |
| Due occurrence | `id:string` (slot ID), `health_medicine_id:string`, `local_time:string(HH:MM)`, `timezone:string`, `scheduled_on:date`, `state:null\|taken\|skipped` |
| Saved metadata | `id:string`, `checked_at:UTC timestamp`, `saved_at:UTC timestamp`, `selected_symptom_count:int`, `displayed_result_count:int` |
| Disease Summary | `id:string`, `slug:string`, `name_en:string`, `name_hi:string?`, `public_url:string`, `body_system:string?`, `gender:male\|female\|both\|null` |
| Substance Summary | `id:string`, `canonical_name:string`, `registry_url:string`, `information_type:"legacy_registry_name"` |

Summary count semantics: conditions = active/chronic; historical_conditions = the canonical assembler's other condition group (normally historical/resolved); current_medications = exactly current; allergies/procedures count all canonical profile repository rows. No complete allergy/procedure records or insurance data is serialized. Do not reconstruct a complete clinical summary by asserting these counts are exhaustive clinical evidence.

Saved detail = saved metadata plus `snapshot:{version,checked_at,selected,summary,results,disclaimer}`. Version currently integer 1. `selected` preserves `{id:string,label:string}` order. `summary` has integer `total_matches,displayed_matches`. Each result has exactly `id:string,label:string,display_percent:int,match_level:string,user_match_score:number,coverage:number,reason:string[],explanation:string[]`. Preserve ordering and labels; render percentages as **Symptom Match**, never clinical confidence. No raw ranking score, private owner ID, raw JSON or current disease slug is returned. Snapshot version describes payload shape, not the medical dataset/matcher version. Do not rerun matching, replace historical labels or reinterpret this as a current condition. Display stored disclaimer.

Disease Detail = Disease Summary plus `locale:"en"`, `content_format:"plain_text"`, `sections:{overview,causes,risk_factors,diagnosis,prevention}` (each string/null), `references` with the same section keys (arrays of `{organization,title,url,date:string?,date_type:string?}`), `causes_items:string[]`, and `urgency_note:string?`. Cause list is bounded at 100, with excess failing safely rather than truncating. Empty reference arrays mean no confirmed public section mapping; do not invent sources or a KOBI review date. Source date meaning follows `date_type`. English narratives and optional Hindi names are distinct; a Hindi name does not imply translated content. No Treatment, FAQ, symptom, comparison, diet or clinical score DTO exists here.

Schedule DTOs intentionally omit medicine labels. Reuse one authorized M collection in memory for ID→brand/generic display, then link to MD; no per-slot N+1 fetch. If that lookup fails, show the slot with “Medication details unavailable” and a retry rather than fabricating a name or treating the schedule as empty. Do not resort server occurrences by client labels.

Authorization: profile list/detail use active `health_profiles` plus active `health_profile_members` membership. Owner/editor/viewer read membership is respected; creator identity alone is insufficient. Every profile resource rechecks through `ApiHealthProfileService::detail()`/`findAccessible()`. Child queries constrain both child ID and authorized profile ID, even if the user can access both profiles. Saved checks instead bind authenticated user ID directly; they are not profile-owned or shared by membership.

## 14. API gaps

| Priority | Actual gap | V1 decision |
|---|---|---|
| Required before scoped client start | None established in the read API contracts | Begin foundation; no speculative API changes |
| Required before real-device acceptance | Reachable HTTPS origin trusted by the target device; actual Authorization forwarding and environment configuration verified | Environment prerequisite; existing localhost/Windows trust evidence is not Android/iOS trust evidence |
| Useful after V1 | Profile create/update, condition/medication writes, schedule/checkoff writes | Choose the next write based on observed mobile use; authorize separately |
| Useful after V1 | Allergy/procedure details and richer summary projection | Needed only for full clinical-summary parity, not aggregate V1 |
| Useful after V1 | Versioned symptom submission and saved-check creation/deletion | Current API only reads already-saved browser snapshots |
| Useful after V1 | Native registration/recovery/verification and Google login | Existing verified local-password users are V1 audience; no new native auth mechanism now |
| Not required yet | Documents, timeline, insurance, sharing, verified-mobile identity, rich Medicine/Treatment knowledge | Defer; no placeholder APIs or browser scraping |
| Recommended, conditional | Pagination for very large medication/profile collections; summary/schedule performance profiling | No demonstrated blocker; observe realistic authorized data volumes first |

No Taken/Skipped buttons, reminders, prescribing recommendations or complete medical-report export should be promised with the present contract. A zero-data account cannot create records natively; decide whether that restricted audience fits the initial pilot. This is a product boundary, not a hidden prerequisite to build every write API.

## 15. Read-only V1 assessment

**Yes: start mobile implementation before adding a large write surface.** V1 offers portable access to existing records and daily schedule visibility, family/dependent profile selection, historical saved checks and public learning. Its only native state-changing network operations are the established authentication lifecycle. It is read-focused, not literally a client that makes no POST requests.

Call summary “Medical Summary — recorded overview” and explain that only counts are available. Show personal medications as recorded values, not clinical advice. Due Today means scheduled occurrences for each stored timezone, not a reminder system or verified adherence record. Users needing new records/checks use existing web workflows separately until explicitly scoped native write features arrive.

## 16. Loading/error/empty-state strategy

| State | Client behavior |
|---|---|
| Initial load | Accessible progress/skeleton; no misleading zero counts before success |
| Refresh / next page | Keep only same-account/profile in-memory data, indicate refresh/append progress, preserve scroll |
| 200 empty list | Feature-specific text: no recorded conditions/medicines/schedules/saved checks; no fake item |
| Null optional field / section | “Not recorded” on records, or sensible omitted/“Not available” public section; null is not zero |
| 401 | One coordinated recovery attempt under section 7; terminal failure → login, never “No records” |
| 404 profile | “Profile unavailable”; clear that selection and refresh chooser; do not infer forbidden vs missing |
| 404 child/public detail | “This item is unavailable”; Back to collection, no exception details |
| 403 if ever returned | Explain access unavailable; no refresh loop or ownership inference; current profile path uses 404 |
| 400/422 | Correct query/input; safe field messages where present; no session reset |
| 429 | Honor Retry-After with action cooldown; no repeated requests |
| Network/timeout | Explain connection/timeout; explicit retry, distinguish from empty/auth failure |
| 500/503 | Temporary service failure; safe retry, no fabricated medical data or automatic logout |
| Malformed envelope / unsupported snapshot | Compatibility/loading error; preserve scope, report nonsensitive diagnostic code only |

Dashboard cards fail independently. Remove private content immediately when its account/profile scope is lost; stale content must never survive a switch. Avoid implying that unverified account details are exposed by `/me`: login itself generically rejects ineligible accounts. Account cannot offer diagnosis of why credentials were denied.

## 17. Pagination strategy

| Endpoint | Actual contract | Mobile behavior / order |
|---|---|---|
| Disease catalog K | `page` default 1/max 10000; `page_size` default 20/max 50; meta `page,page_size,total,has_more` plus api_version | Load more; English name then numeric ID |
| Disease search KS | Only `q`; meta `query,limit:10,has_more` plus api_version | No next-page query; ask user to refine when has_more |
| Substance search SUB | Same capped search contract | Refine, no invented paging |
| Conditions C | Same bounded page/page_size and metadata as K | Preserve active,chronic,historical,resolved then diagnosed_on DESC,id DESC; null dates follow dated values in group |
| Medicines M | Unpaginated; meta api_version only; rejects all query parameters | Render returned ordered array; current,stopped,historical then started_on DESC,id DESC |
| Profiles P | Unpaginated; no query parameters | Preserve list order (owner memberships first, then name internally); do not infer role from order |
| Schedule SCH | Same bounded page/page_size | Active first, local_time ASC,id ASC; includes inactive configurations |
| Due Today DUE | Same bounded page/page_size after canonical eligibility and sorting | Preserve wall-time, internal medicine name, numeric slot ID order; no client filtering of completed/future times |
| Saved SC | Same bounded page/page_size | checked_at DESC,id DESC; metadata only until detail opened |
| All details / I / S | No paging; no query parameters | Fetch one object |

There is no `next`, cursor or `total_pages` response field. Stop on `has_more:false`; serialize page loads, never request page 10001. Unknown query fields are rejected. Dates, status filters, user IDs, search queries on list-only resources and timezone overrides must not be invented. Client-only filtering of an unpaginated medication list must be clearly local; do not present one paginated page as an exhaustive filtered collection.

For mutable offset pages, deduplicate IDs while retaining first server order, reset all pages on refresh/query/profile change, and accept that total/page reads are not a fixed snapshot. Do not keep appending across local-day transitions in Due Today; restart at page 1. Search debounce about 300 ms, cancel/ignore old-query responses, query only after 2–100 Unicode characters; retain punctuation and let server normalize whitespace. No fuzzy clinical ranking. No auto-pagination loop to exhaust all private resources on startup.

## 18. Date/time strategy

Use three separate client value concepts, not a single generic `DateTime` parser:

1. UTC instants: token deadlines, saved `checked_at` and `saved_at` use RFC3339 `...Z`. Parse as instants; display in phone timezone with a clear local label when appropriate. Checked time is historical result generation, not symptom onset. Keep absolute value for sorting; server order is authoritative.
2. Calendar dates: `diagnosed_on`, `resolved_on`, `started_on`, `stopped_on`, `starts_on`, `ends_on`, `scheduled_on` are `YYYY-MM-DD`. Validate calendar shape and retain date components. Do not parse as UTC midnight then timezone-convert, which can change the day. Preserve nulls.
3. Slot wall times: `local_time` is `HH:MM`, with separate stored named `timezone`. Display that time and zone together; optional 12-hour formatting must not change the underlying hour/date. Do not reinterpret it in the phone timezone or invent a UTC alarm instant.

Canonical Due Today converts the backend UTC clock separately into each slot's timezone. It includes only current medicines with active slots, local day within inclusive slot starts/ends, and at/after medication started_on where present. It does not filter by current hour: later slots today and taken/skipped rows remain. Null state means no recorded checkoff, not missed/overdue. Different slots may have different local dates; show per-row scheduled_on rather than asserting one universal profile-wide today.

DST gaps/folds do not create extra occurrence rows: identity is slot ID plus local date. There is no next-dose timestamp, reminder/notification contract or past adherence API. Re-fetch on app resume, explicit refresh and relevant local-day boundaries while open (use a reviewed timezone library if calculating refresh timing). The client never recomputes eligibility or schedules notifications from a guessed timezone conversion. If day transitions occur during pagination, clear/reset the list; server time is authoritative. A failed refresh is visible, not silently displayed as live current medication status.

## 19. Security/privacy requirements

No tokens, passwords, health labels, profile names, payloads or bearer headers in debug logs, analytics, network interceptors or crash attachments. Third-party tracking is out of V1. Use minimal nonsensitive failure categories and build/environment identifiers. Token storage requirements are in section 8; no secrets in compiled configuration.

Clear scoped data on logout/account switch/access loss; private back stacks and late responses must not revive it. Blur/replace private app-switcher previews while backgrounded; assess Android secure-window protection on health screens and platform screenshot limitations during implementation. Do not claim iOS can prevent all screenshots. Accessibility and user needs should inform screenshot restrictions. Avoid health content in notifications, clipboard exports and OS search indexing. No biometric login, analytics SDK or attestation dependency is required for initial read V1.

Keep selected person clearly visible. Treat all labels/narratives as text, not HTML or executable links. Reference links require valid approved HTTPS targets; never attach Authorization to them or returned browser URLs. Read summaries/schedules never confer permission to modify data. Native session, server eligibility and profile authorization remain independent boundaries; no owner/admin status inferred from account number or profile category.

## 20. Offline/cache recommendation

V1: **no persistent health cache and no offline claims**. Use short-lived in-memory read-through reuse to reduce duplicate same-scope calls. Clear it on logout/switch; fetch summary, permissions/profile list, Due Today and Account live on entry/resume as appropriate. Do not claim an old successful response proves current access. Honor the API's no-store intent; disable disk HTTP response caching.

Public disease/search text and references are candidates for a later explicitly designed offline feature, with retrieval date, version/invalidation and visible stale/offline labeling. Current responses are also no-store and have no ETag/Last-Modified contract, so do not add persistent public HTTP caching now. Saved checks are historical by definition, but still private; that is not permission to persist them outside protected session handling. Later encrypted health storage needs its own threat model, consent/retention/backup and account deletion rules. No background offline write queue in V1.

## 21. Environment configuration

Use build flavors or equivalent compile-time configuration: development mock, local integration, staging, production. Supply `API_BASE_URL` independently for each build; validate HTTPS scheme and expected host/application mount at bootstrap. Do not embed `localhost/kobi`, passwords, limiter keys, CA private keys or backend `.env` contents in the app. Display a clear nonproduction environment marker. Keep credential stores separate per flavor; switching origins clears session and all private state.

A phone's localhost is the phone, not XAMPP. Emulator routing and physical-device DNS/network reachability must be configured deliberately. Existing localhost certificates and Windows CurrentUser root trust do not establish device trust or hostname validity on another host. Prefer a dedicated reachable staging HTTPS domain with a trusted certificate. Any local development trust setup must be scoped and reviewed; do not change Apache/PHP/TLS here, add insecure production network policies or bypass certificate verification. Test the actual private routes and header forwarding through the chosen host before calling real-device acceptance complete.

No production domain or Internet deployment is assumed available from source inspection. Native HTTP does not require a browser CORS grant; do not request backend CORS relaxation as a mobile prerequisite. iOS build/signing requires the appropriate Apple toolchain when iOS work begins; it does not block Android-first foundation work on the current Windows workspace.

## 22. Testing strategy

Future mobile tests, not implemented here:

| Layer | Essential coverage |
|---|---|
| Unit / DTO | Every explicit projection/envelope, nulls, IDs beyond JS safe precision, Unicode/Hindi names, missing/wrong-type fields, additive fields, unsupported snapshots; no fabricated defaults |
| Session | Single-flight rotation; many simultaneous old-token 401s; late old-generation failure; one GET retry; lost refresh response; protected-store failure; deadline/resume behavior; account epoch isolation; logout during refresh |
| Repositories | Profile/account-keyed data, wrong-profile 404 handling, label-map failure, request deduplication, cancel/ignore stale response, no public Authorization |
| Pagination / dates | Exact metadata/bounds, capped search has_more without page, page reset/dedup, date-only preservation, slot zones differing from phone, mixed local dates, midnight, DST folds/gaps, null/taken/skipped and future-today times |
| Integration | Real login, refresh, logout/logout-all; `/me`; accessible profiles; each resource success/empty/error; auth forwarding through actual HTTPS host; 400/401/404/422/429/500/503 and timeouts |
| UI | Login, zero/one/multiple profiles, switch while loading, dashboard card failures, aggregate summary, conditions/medicines list/detail, schedules/Due Today, historical checks, disease search/detail/references, substances; Back and account cleanup |
| Privacy/security | Expired access, invalid/expired refresh, replay/revoked family, inaccessible profile/other-profile child, no secret/health logging or backup, app preview protection, logout cleanup, redirect/origin protection |
| Device acceptance | Android emulator and physical device; phone about 390×844 and larger/tablet layout; large fonts, screen reader, rotation/resume, poor network and trusted TLS. iOS later on real supported toolchain/device |

Use mock transport and fake secure store/clock for deterministic client tests. Real token rotation tests use independently authorized disposable accounts/environments; no automatic production-family creation. No backend test may be edited merely to match a client assumption.

Readiness validation actually run: existing database-free PHP suites using `D:\xampp\php\php.exe tests/<suite>.php`:

| Suite | Passing checks |
|---|---:|
| api-auth.php | 103 |
| api-identity.php | 44 |
| api-health-profiles.php | 20 |
| api-medications.php | 28 |
| api-medical-summary.php | 29 plus 76 reused dashboard checks |
| api-health-conditions.php | 65 |
| api-medication-schedules.php | 71 plus 52 reused schedule checks |
| api-saved-symptom-checks.php | 58 |
| public-api-v1.php | 56 |
| **Total** | **602** |

All nine processes exited successfully. They use synthetic in-memory repositories/fixtures and do not connect to the normal database. No HTTP/MariaDB/deployment harness or full live auth acceptance was rerun. Source/route/projection inspection establishes design readiness, not current server availability or real-device UX. No PHP changed, so a modified-PHP syntax check is not applicable. Edit-operation review confirms this report was the only project file written; no existing tests were edited. A before/after hash comparison could not be completed because the baseline saved in the execution environment's temporary directory was unavailable to the later invocation; no successful hash-comparison result is claimed. Static final checks confirmed 20 V1 routes, all 26 numbered report sections and the required final decision line.

## 23. Backend work required before/during Mobile V1

| Backend capability | Required before mobile start? | Reason / timing |
|---|---|---|
| Existing read APIs | No additional feature work | Current explicit contracts support scoped V1 |
| Existing password/token lifecycle | No redesign | Client must implement serialization and failure handling correctly |
| Health record write APIs | No | Read-focused V1; scope individually later |
| Allergies API | No | Display count only, no dead detail navigation |
| Procedures API | No | Display count only |
| Documents / timeline / insurance API | No | Deferred; do not advertise native availability |
| Medication Taken/Skipped write API | No | Read-only Due Today is useful; add only with an approved checkoff workflow |
| Profile update/create API | No | Select/view existing accessible profiles; zero-profile limitation explicit |
| Mobile verification / SMS | No | No phone-based sign-in or verified-mobile feature in V1 |
| KOBI-to-KOBI sharing | No | Separate future product/governance scope |
| Native registration/recovery/Google | No for existing verified password-user pilot | Broader acquisition/auth workflows require separate planning |
| Rich Medicine / Treatment API | No | Names-only substance information and five-section disease knowledge are the supported promise |
| Dedicated Dashboard API | No | Compose S/DUE/P using current repositories |
| Device-reachable trusted HTTPS | Required before integration/release acceptance, not additional resource work | Configure/test an approved environment separately; no changes made here |
| Contract documentation refresh | Recommended | Stale cumulative introductory wording noted in section 2; not a new API blocker |
| OpenAPI/schema tooling, usage/performance observation | Recommended later | Helpful for maintenance and measured scale, not a reason to invent endpoints |

## 24. Recommended implementation sequence

| Phase | Deliverable and acceptance boundary |
|---|---|
| 58A — this task | Readiness blueprint only; no implementation |
| 58B — Mobile foundation | Separately authorize project location/setup. Pin Flutter/dependencies; flavors/theme/navigation; typed transport/envelopes; secure-store adapter and atomic bundle; single session coordinator; Login, Account, Logout/Logout All. Mock tests first, then approved trusted HTTPS integration. No backend redesign. |
| 58C — Core health | Profile list/selector/banner; scoped repositories; Home and aggregate Summary; Conditions and Personal Medicines list/detail. Verify zero/one/multiple profiles, access loss and switching without data bleed. |
| 58D — Daily health | Schedule and Due Today with canonical timezone semantics and medicine-label reuse; Saved Checks list/detail as account-owned historical reads. No alarms/checkoff/symptom-submission writes. |
| 58E — Public knowledge and V1 acceptance | Public disease catalog/search/detail/references and substance search, anonymous navigation, accessibility, poor-network states, device sizes, privacy/logging and end-to-end acceptance. Public UI work can proceed independently once transport is stable. |

At each phase, test its real contract and preserve completed backend behavior. Do not add backend writes to “complete” a read-only screen. Decide the next smallest useful write workflow only after V1 user feedback and a separate readiness/authorization step.

## 25. Risks and open questions

| Item | Classification / resolution |
|---|---|
| Team Dart/React skills not documented | Confirm before 58B; Flutter default is a reasoned recommendation, not a measured staffing fact |
| Staging/production host and target-device TLS trust unverified | Integration/release prerequisite; select approved host and test actual private routes, no TLS bypass |
| Refresh response loss or concurrent rotation | Core client correctness risk; single-flight, atomic store, epoch isolation and re-login on ambiguous rotation |
| Existing-user/read-only audience | Product decision: current V1 cannot onboard a new empty account into health records or save a new symptom check |
| Counts-only summary / omitted browser features | Explicit scope boundary; avoid complete-report claims and unsupported links |
| Unpaginated profiles/medicines and canonical summary/schedule internal work | Observe actual scale; 256-KiB guard fails safely. No evidence yet warrants a preemptive API change |
| Offset paging and local midnight | No immutable server snapshot; reset/reload and preserve server ordering |
| Historic match labels with no slug/current knowledge relationship | Show stored snapshot; do not guess disease-detail routes or clinical correctness |
| User device compromise / background previews | OS storage and privacy UI reduce exposure; they cannot guarantee safety on compromised unlocked devices |
| App store, privacy policy, release ownership, minimum OS and iOS toolchain | Resolve for distribution; not missing PHP features and not validated by this source assessment |

No genuine API-contract blocker was found for the scoped read-focused client. Recommended improvements and optional future functionality above are not blockers. No current host availability, Android/iOS trust, mobile build or native UI was tested here; those validations belong to implementation. No database, live health record or credential family was accessed or mutated by this task.

## 26. Final readiness decision

Proceed to a separately authorized 58B foundation using Flutter, MVVM-style feature modules, one typed versioned API client, one secure session coordinator, and explicit profile-scoped state. Deliver the required screens in section 11 through the exact 20 existing routes in section 12. Keep Medical Summary aggregate-only, Due Today read-only and substance knowledge registry-only.

Validation: routes and DTOs inspected against contracts; authorization, envelopes, refresh lifecycle, pagination and dates traced in source; 602 database-free checks passed. This report is the only project file added. Application code, tests, database data/schema, configuration, authentication, `.htaccess`, Treatment/Medicine behavior and Git were not changed. No package installation, mobile project, build artifact, production request or deployment was performed. Step 58B has not begun.

READY FOR MOBILE IMPLEMENTATION
