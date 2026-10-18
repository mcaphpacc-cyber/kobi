# Step 58F — Mobile V1 Product & Write-API Readiness Review

Review date: 18 October 2026. **STEP 58F: PASS WITH LIMITATIONS.** This is a source-based readiness decision, not implementation or device acceptance. All routes and client changes labelled **proposed** below are future work. Step 59 has not started.

## 1. Executive Summary

Recommend a single daily checkoff vertical slice: explicitly record **Taken**, **Skipped**, or correct one of those states to the other for an existing, eligible occurrence today. These are the only MUST HAVE writes for the first mobile **write** release. Existing read-focused V1 already provides value to users with records prepared on the website; complete native record creation is not a prerequisite for that restricted audience.

The choice follows actual code: Due Today already exists; the browser domain already performs a transactional owner/editor check, validates the server-computed slot-local day, and upserts one checkoff per slot/date. The current schema supports this without migration. Schedule editing retires/replaces slots and medication stopping retires active slots, so those are materially larger lifecycle features.

Two client prerequisites belong in the same slice: identify the actual medicine before offering a checkoff, and distinguish read access from write permission. Current cards say only “Scheduled medication”; current profile/Due DTOs expose no permission flag. Resolve names through the existing authorized medications read and propose an additive Due Today metadata capability, rather than infer permission from profile access or expose membership details.

SHOULD HAVE next: medication add/edit/record-stop, schedule create/replace/retire, checkoff history, condition add/edit/status/date maintenance, and owner deletion of saved checks. These are not part of the recommended first Step 59 release. No generic CRUD expansion is proposed.

Limitations: native-device and approved live mobile HTTPS acceptance remain pending in 58B–58E. This review does not lift those limitations. No database connection, browser health-data exercise, or new clinical/source research was needed.

## 2. Mobile V1 Current State

Read `AGENTS.md` and the 58A readiness and 58B–58E implementation reports, then cross-checked actual Dart and PHP source. The reports are historical evidence, not a substitute for the source review.

`app/kobi_app.dart` gates anonymous/authenticated shells, hides private UI while backgrounded and rechecks identity on resume. `navigation/foundation_shell.dart` owns Home, Health, Saved, Account and Knowledge destinations, a local Health detail stack, profile state and disposable daily-health state. `anonymous_shell.dart` exposes Sign in and public Knowledge. No additional routing/state framework is present.

`SessionController` owns opaque credentials, protected storage, session epochs and single-flight refresh. `HealthRepository` uses it for authorized reads; `KnowledgeRepository` deliberately does not. `ApiClient` currently permits only GET/POST and restricts bearer POST to logout/logout-all. A future checkoff POST therefore requires a deliberate narrow transport allowance; it does not work merely by adding a button. `SessionController.authorized` currently retries an operation once after a definite 401; it must not become a general write retry mechanism.

`ProfileController` validates the selected profile, auto-selects only a sole profile, and requires a choice among multiple profiles. Zero profiles produces a genuine empty state. Scope disposal/generation checks discard old-account/profile/child responses. `LoadState`, `PagedState`, `Loaded` and `PagedView` distinguish loading, empty, safe error, cooldown, refresh and incremental paging. There is no persistent health cache or offline mutation queue.

## 3. Actual Mobile Screens Reviewed

Paths below are relative to `mobile/kobi_mobile/lib/`.

| Screen/area | Actual source and behavior | Write readiness |
|---|---|---|
| Login, restoration, session failure | `features/auth/{login_screen,auth_repository,session_controller,token_bundle}.dart`; `core/storage/session_store.dart`; `app/kobi_app.dart` | Existing authentication lifecycle only; preserve refresh/storage/epoch rules |
| Account | `features/account/{account_screen,account_identity}.dart` | Identity, Refresh, Logout, Logout All; no email/password/contact editor |
| Profile selector, Health Profile | `features/health/{health_screens,health_state,health_models}.dart` | Minimal id/name/type projection; no demographics form or membership controls |
| Home dashboard, Medical Summary | Same Health files and `navigation/foundation_shell.dart` | Five recorded counts; condition/medicine navigation; allergy/procedure counts are not detail editors; no insurance DTO |
| Conditions/detail | Health screens, models, state and `health_repository.dart` | Paginated records and five-field detail; no Add/Edit/Delete |
| Personal Medicines/detail | Same Health files | Existing ten-field read projection; no management form, stop or resume action |
| Schedule, Due Today | `features/daily_health/{daily_health_screen,daily_health_state,schedule_models}.dart` | Active/inactive slots; server dates/timezones/state, paging and Refresh; no action buttons; neutral unnamed cards |
| Saved checks/list/detail | `features/saved_checks/{saved_screen,saved_state,saved_models}.dart` | Account-owned immutable historical snapshots; no save/delete or new checker submission screen |
| Public Knowledge, catalog/search/detail/references, substance search | `features/knowledge/{knowledge_screen,knowledge_state,knowledge_models}.dart` | Public GETs, 350 ms debounce, stale-query protection, plain text and deliberate external source links; no health write dependency |

Also inspected `core/network/api_client.dart`, `core/errors/api_failure.dart`, `features/health/paged_state.dart` and navigation entry/load/disposal paths. Searched the current `lib` tree for TODO/deferred/write/action markers. No unfinished write handler or advertised Add/Save/Mark button was found to finish. Taken/Skipped strings are display labels, not controls. Public registry search is names-only information, not a personal-medication editor. Calendar dates remain date components, not device-timezone timestamps.

## 4. Existing Backend Capabilities Reviewed

All current routes are in `routes/web.php`; versioned health APIs currently expose GET reads. Browser writes use their own authenticated, CSRF-protected forms. The inspection covered the relevant controllers, domain services, repository statements and `database/structure.sql`, including the additive saved-check/schedule definitions near its end.

| Domain | Existing browser/controller → service → repository evidence | Actual rules / reuse limits |
|---|---|---|
| Medicines | `AccountController` medicine actions → `HealthMedicineService` → `HealthMedicineRepository` | Owner/editor create/update/delete. Required brand; current/stopped/historical; stopped requires stop date; current forbids stop date; dates cannot be future and stop cannot precede start. Repository update locks medicine and retires active slots for non-current status. Delete cascades schedule/checkoff rows. No dedicated resume operation |
| Schedules/checkoffs/history | `MedicationScheduleController` → `MedicationScheduleService` → `MedicationScheduleRepository` | `withMedicine` locks medicine and active profile membership; only owner/editor may mutate. Save validates HH:MM, IANA timezone, inclusive ordered dates, start today or later, same timezone and unique active time per medicine. Edit retires original and creates a new slot ID. Remove retires. `mark` validates state and current eligible local day under the lock. History reads stored checkoffs, not a full change audit |
| Conditions | `AccountController` condition actions → `HealthConditionService` → `HealthConditionRepository` | Owner/editor CRUD; required name; active/resolved/chronic/historical; diagnosed/resolved dates; resolved needs resolved date, other states clear it; ordered dates. Child reads/updates/deletes bind profile. Date parser uses `Y-m-d` without `!`, unlike the corrected medication parser: today-versus-midnight behavior needs focused acceptance before future write reuse, not a change in this task |
| Saved checks | `SavedSymptomCheckController` → `SavedSymptomCheckService` → `SavedSymptomCheckRepository` | Browser save consumes an account-bound, expiring server snapshot kept in browser Session; unique user/check_key prevents duplicates. Deletion binds account and requires UI confirmation. Bearer read uses `getForPrincipal`, never reruns matcher. Browser save cannot simply be called from a session-independent API |
| Allergies | `AccountController` allergy actions → `HealthAllergyService` → `HealthAllergyRepository` | Owner/editor create/edit/delete; required allergen; medication/food/environmental/other; optional reaction, severity, identified date and notes. Severity is an explicit allowed value. No mobile detail API/UI yet |
| Procedures | `AccountController` procedure actions → `HealthProcedureService` → `HealthProcedureRepository` | Owner/editor create/edit/delete; required name, surgery/procedure/hospitalization; performed date, hospital, doctor, reason, notes. No mobile record-level screen/API |
| Documents | `AccountController` document upload/metadata/file/delete actions → `HealthDocumentService` → `HealthDocumentRepository` and `HealthDocumentStorageService` | Owner/editor upload/metadata edit; owner-only delete. Actual PHP upload verification, server MIME detection, nonempty file ≤20 MB, PDF/JPEG/PNG/WebP, randomized private paths, failed-insert file cleanup; file replacement unsupported. Delete handles physical file then metadata. Native JSON transport does not implement this multipart/binary lifecycle |
| Insurance | `HealthInsuranceController` → `HealthInsuranceService` → policy/member/contact/document-link repositories | Profile-bound owner/editor policy maintenance; provider required, enum/type/status/amount/currency/date validation; members, contacts and same-profile document associations add dependencies. Unlinking a document is owner-only. Browser Summary insurance does not imply an existing mobile insurance projection |
| Health Profiles | `AccountController` health-record create/edit → `HealthProfileService` → `HealthProfileRepository` | Browser family creation establishes owner; self setup also exists. Editing permits owner/editor, validates name/gender and normalized optional DOB. Current API deliberately returns only id/full_name/profile_type. Profile creation/membership is not an ordinary child write |
| Account | `AccountController` profile/mobile/password/identity actions → `AuthService`, email-verification and external-identity services → user/identity repositories | Email change follows re-verification; password change verifies current password and regenerates browser session, with existing API credential-stamp/revocation behavior to preserve. Mobile number is nullable normalized contact data, not verified identity. Google linking/unlinking has independent protections. No automatic account merge or native account-management API exists |

Cross-cutting sources: `ApiAuthController`, `ApiAuthGuard`, `ApiAuthService`, `ApiAuthPrincipal`, `ApiHealthProfileService`, `ApiMedicationScheduleService`, `ApiSavedSymptomCheckService`, `Request`, `PublicApiResponse`, `Router`, and `HealthProfileRepository::findAccessible`.

## 5. Candidate Write Operations

Priorities in section 6 apply to individual operations, not entire tables. “MUST HAVE” means required for the proposed small daily-action **write release**, not a claim that read-only V1 is useless. SHOULD HAVE means a separately scoped next increment; DEFER means eventual product work; DO NOT ADD NOW means the suggested operation lacks appropriate semantics or prerequisites now.

Authorization/sensitivity notation for the matrix: **P** = active authorized profile plus owner/editor write permission, child bound to that profile; **O** = profile owner only; **A** = authenticated account owns the object; **I** = sensitive account identity/verification/password boundary. **H** = personal health/action data; **VH** = documents, insurance or credentials/contact data needing additional care. Complexity is relative implementation effort, including mobile UX and a session-independent adapter, not only SQL. Each row's backend description refers to the concrete sources in section 4.

## 6. MUST HAVE / SHOULD HAVE / DEFER / DO NOT ADD NOW Matrix

| Candidate operation | Exactly one priority | User value and mobile UX dependency | Existing backend support | Auth / sensitivity | Complexity and reason |
|---|---|---|---|---|---|
| Medication add | SHOULD HAVE | Enter newly recorded medicine; needs validated entry form and list refresh | `createMedicine` | P / H | Medium; enables native onboarding later, unnecessary for existing scheduled users |
| Medication edit | SHOULD HAVE | Correct recorded fields; needs current detail/form and hidden-field preservation | `updateMedicine` | P / H | Medium; avoid clearing notes/prescriber fields omitted by current read DTO |
| Record medication stopped | SHOULD HAVE | Record an already-made real-world change; explicit date/confirmation | Existing update status + slot retirement | P / H | Medium; cascade affects Due Today; must not imply advice to stop treatment |
| Resume medication | DO NOT ADD NOW | Ambiguous restart date/schedules; would need lifecycle UX | General update can set current and clear stop date, but does not reactivate retired slots or preserve stop episodes | P / H | High semantic risk; no dedicated safe resume contract to expose |
| Medication delete | DEFER | Remove mistaken record; destructive confirmation | Existing hard delete, schedule/checkoff cascades | P / H | Medium/high; losing daily history is unnecessary in first release |
| Schedule create | SHOULD HAVE | Make new medicine actionable; medicine-detail schedule form required | `save` new slot | P / H | Medium; timezone/effective-date rules need full form/test coverage |
| Schedule edit | SHOULD HAVE | Correct future schedule; explain replacement, refresh both lists | `save` retires and creates replacement ID | P / H | Medium; cannot model as an in-place PATCH with unchanged identity |
| Schedule delete/remove | SHOULD HAVE | Stop using a slot; explicit retirement confirmation | `remove` retires, preserves history | P / H | Medium; expose retirement semantics, never physical deletion |
| Mark Taken | MUST HAVE | Explicit daily action on identified Due Today occurrence | `mark` + transactional upsert | P / H | Medium; highest immediate value in an existing screen; one shared endpoint |
| Mark Skipped | MUST HAVE | Explicit alternative daily action; never infer from elapsed time | Same `mark` | P / H | Medium shared with Taken; no extra lifecycle/schema |
| Correct Taken ↔ Skipped today | MUST HAVE | Recover from mistaken tap; confirmation shows current/new state | Same occurrence upsert updates state/actor/time | P / H | Low incremental; inseparable from existing browser semantics |
| Undo checkoff to unrecorded | DO NOT ADD NOW | Would erase action; needs distinct semantics | No null-state/delete-checkoff domain operation | P / H | Medium/high; do not invent history erasure |
| View checkoff history (read adjunct) | SHOULD HAVE | Review previous days; needs new history screen/projection | `history`, stored initial medicine label and original slot | Profile read / H | Medium; useful soon, not a write or prerequisite for today's correction |
| Condition add | SHOULD HAVE | Record known condition; new form | `createCondition` | P / H | Medium; explicit input only, no diagnostic suggestions; date acceptance gate applies |
| Condition edit | SHOULD HAVE | Correct record; current detail + omitted-field handling | `updateCondition` | P / H | Medium; current DTO omits doctor/notes; avoid full-row replacement |
| Condition status change | SHOULD HAVE | Maintain active/chronic/historical/resolved | Existing validated update | P / H | Medium; not an independent clinical inference or automatic resolution |
| Set condition diagnosed date | SHOULD HAVE | Correct historical chronology; date editor | Existing update/validation | P / H | Medium shared form; test today/malformed/future dates before reuse |
| Set condition resolved date | SHOULD HAVE | Record resolution with explicit status | Required for resolved; cleared for other states | P / H | Medium; date ordering/status coupling cannot be bypassed |
| Condition delete | DEFER | Remove mistaken history; confirmation | Existing profile-bound delete | P / H | Medium; destructive maintenance is not core daily workflow |
| Save new symptom check | DO NOT ADD NOW | Save after a completed checker session; no such mobile session exists | Browser pending Session snapshot only | A / H | High; needs separately approved versioned submission/server-capture contract; never accept client-authored result JSON |
| Delete saved check | SHOULD HAVE | Control own saved history; detail confirmation/list refresh | Account-bound `deleteForUser`, browser confirmation | A / H | Low/medium; independent small later increment, not needed for checkoffs |
| Allergy add | DEFER | Record allergy; must first expose meaningful read/detail UX | Browser create exists | P / H | Medium; a count-only mobile Summary cannot support safe maintenance alone |
| Allergy edit | DEFER | Correct allergen/reaction/severity/date; full detail/form | Browser update exists | P / H | Medium; requires explicit fields and acceptance of date handling |
| Allergy delete | DEFER | Remove mistaken entry with confirmation | Browser delete exists | P / H | Medium; sensitive removal with no current record-level mobile view |
| Procedure add | DEFER | Record completed event; new record/form UX | Browser create exists | P / H | Medium; no current mobile list/detail |
| Procedure edit | DEFER | Correct event chronology/details | Browser update exists | P / H | Medium; read/projection/form prerequisite |
| Procedure delete | DEFER | Remove erroneous event | Browser delete exists | P / H | Medium; not needed for daily medication action |
| Document upload | DEFER | Carry health records; picker/progress/cancel/private viewing needed | Validated PHP upload + storage + insert | P / VH | High; multipart, size, MIME, interrupted upload and cleanup exceed this slice |
| Standalone document metadata creation | DO NOT ADD NOW | No useful file without completed upload | Create requires uploaded file; no standalone placeholder lifecycle | P / VH | High/unsupported; do not add orphan/placeholder records |
| Document metadata edit | DEFER | Correct title/type/date; needs document detail | Existing metadata-only update | P / VH | Medium; no current mobile document projection |
| Document delete | DEFER | Remove document; owner confirmation and file/row consistency | Owner-only physical-file/metadata deletion | O / VH | High; do not accidentally grant editor deletion |
| Document listing refresh after upload (read adjunct) | DEFER | Show completed upload; depends on upload/list API | Browser profile document list | Profile read / VH | Medium; refresh is not itself a write and no mobile upload/list exists |
| Insurance create | DEFER | Record policy; new sensitive forms and reads | Policy/member/contact/document services exist | P / VH | High; browser Summary parity alone is not justification |
| Insurance edit | DEFER | Maintain policy details/coverage dates | Existing validated update | P / VH | High; policy identifiers and nested ownership need separate scope |
| Health Profile edit | DEFER | Correct demographics; requires deliberately expanded read/form contract | Existing owner/editor edit | P / H | Medium; current minimal projection does not expose DOB/gender; no demonstrated first-write need |
| Health Profile create/setup | DEFER | Native onboarding for zero-profile users | Browser family creation + self setup | A then new owner / H | Medium/high; membership lifecycle deserves separate scope; initial write pilot assumes existing web-prepared profiles |
| Account email change | DEFER | Maintain login address; re-verification/recovery UX | Browser verified-email workflow | I / VH | High; affects API eligibility, not daily health workflow |
| Account password change | DEFER | Credential maintenance; reauthentication/revocation UX | Browser current-password verification/change | I / VH | High; preserve credential stamps/family invalidation, no isolated settings CRUD |
| Account mobile-number update | DEFER | Maintain unverified contact; clear contact-only label | Browser normalized nullable number | I / VH | Medium; current Account projection has no contact field; does not restart OTP track |
| Account display-name edit | DEFER | Cosmetic account maintenance; settings form | Browser name/email profile workflow | I / H | Medium; distinguish account name from health-profile identity |
| Google linking/unlinking / other identity settings | DO NOT ADD NOW | Additional login options need dedicated native security flow | Protected browser identity workflow only | I / VH | High; no duplicate native auth system or email-based merge |
| Verified mobile / OTP / sharing identity | DO NOT ADD NOW | Depends on paused policy/provider work | Separate paused verified-identity track | I / VH | High; remains paused, not contact-number CRUD |

## 7. Recommended Step 59 Scope

**Recommendation only:** one coherent daily checkoff release for existing eligible schedules. No medication/schedule CRUD, no historical edits, no delete, no new checker, no account changes. A user explicitly records an action for a named medicine and the exact occurrence returned by the server. Correction means Taken ↔ Skipped for that same still-eligible local day; there is no “undo” to null.

Keep the current `eligible` rules: current medicine; active slot; slot-local server date within inclusive start/end; not before medication started_on. Due Today includes later times today. There is **no existing time-of-day gate**, so do not introduce one or label later times overdue. One occurrence per slot/local date also covers a daylight-saving repeated clock hour. A stale prior-day occurrence must fail rather than be silently reassigned to today's date.

The schema stores a current recorded state per occurrence, not an immutable trail of every correction. Concurrent explicit changes serialize; the last accepted action wins. Do not promise audit-history versioning, strict timestamp idempotence or compare-and-swap protection that the existing model does not provide.

## 8. Recommended Step 59 Sequence

These are proposed implementation slices of the **same** release, not authorization to start them.

| Proposed step | Concrete scope and contracts | Reuse / effects / acceptance |
|---|---|---|
| 59A — Bearer daily checkoff contract | Add the POST in section 9 and additive permission metadata on existing Due Today GET. Two required body fields; exact six-field response. Authenticate principal, authorize profile, bind medication and slot, recheck editable membership/current eligibility under lock | Extract/share an actor-explicit domain operation behind existing browser `mark`, preserving browser wrapper/AuthService/CSRF. Reuse `withMedicine`, slot lookup, eligibility and `mark` repository upsert. No schema/migration. Unit + isolated transaction/HTTP tests required before UI |
| 59B — Identified, explicit mobile actions | Consume precisely those same endpoints; no additional routes/request fields/response fields. Existing authorized medication reads provide label lookup. Due Today shows named medicine, selected profile, timezone/date/time and state; permission false/unknown disables actions. Confirm first action/correction, submit once, reconcile with server | Extend narrow bearer-POST path allowance and a write-specific coordinator policy; retain epoch/profile guards, safe errors and cooldowns. Reuse daily scope, existing reads/models and paging. No schema/migration/browser UI change. Mock/widget and native acceptance in section 15 |
| 59C — Integrated acceptance and scoped release decision | No new endpoints or payloads; validate 59A/59B exact contract, shared browser behavior and state visibility | Existing browser Due Today/history must observe the same authorized checkoff; browser requests/forms remain unchanged. Run regressions, concurrency/authorization tests and approved non-production device HTTPS flows. No production fixture creation or migration implied |

Stop after this slice and assess real usage before separately authorizing SHOULD HAVE work. Do not preallocate 59D CRUD routes or build empty editors.

## 9. Proposed API Route Inventory

**Future proposal; no route was registered in 58F.** Full paths include `/api/v1`; mount remains environment configuration. POST fits the existing GET/POST router and avoids introducing PUT/PATCH/DELETE infrastructure just for this action.

| Method and route | Required input | Exact success `data` / metadata |
|---|---|---|
| **NEW proposed** POST `/api/v1/health-profiles/{profileId}/medications/{medicationId}/schedule-slots/{slotId}/checkoff` | JSON object with only `scheduled_on` (strict `YYYY-MM-DD` string copied from server occurrence) and `state` (exact `taken` or `skipped` string); no query parameters | HTTP 200; `data:{id:string,health_medicine_id:string,local_time:string,timezone:string,scheduled_on:string,state:"taken"\|"skipped"}`; `meta:{api_version:"v1"}`. `id` is slot ID, not checkoff row ID. Return the committed occurrence from the shared domain operation, not client input or an unrestricted row |
| **EXISTING GET, proposed additive metadata only** `/api/v1/health-profiles/{profileId}/medication-schedules/due-today` | Existing optional `page`, `page_size`; no new input | Existing array of `{id:string,health_medicine_id:string,local_time:string,timezone:string,scheduled_on:string,state:null\|"taken"\|"skipped"}` unchanged. Proposed exact metadata: `{api_version:"v1",page:int,page_size:int,total:int,has_more:bool,can_record_checkoffs:bool}`. Boolean derives from current active owner/editor membership, including empty pages; does not grant authority |

Supporting **existing unchanged GETs**: `/health-profiles`, `/health-profiles/{p}`, `/health-profiles/{p}/medications`, `/health-profiles/{p}/medications/{m}` and `/health-profiles/{p}/medication-schedules` (all under `/api/v1`). No new label/role/history endpoint is needed for this slice. Current clients ignore additive metadata; prove this in contract tests before release.

POST response example shape is exactly `{"success":true,"data":{...six fields above...},"meta":{"api_version":"v1"}}`. Return 200 on initial record and correction through existing `PublicApiResponse::success`; no need to change shared response helpers for 201/204. Existing error shape remains `{"success":false,"error":{"code":...,"message":...},"meta":{"api_version":"v1"}}`, without record/owner/path/SQL details.

Proposed errors: HTTPS violation 400; malformed JSON 400; wrong MIME 415; >8192-byte body 413; missing/unknown/wrong-type fields, invalid IDs/dates/state 422; missing/invalid/revoked bearer 401 with challenge; unavailable/inaccessible profile or child mismatch 404; accessible read-only member attempting a mutation 403; stale day/ineligible occurrence 409 with safe instruction to reload; existing 429/Retry-After and limiter-unavailable 503; generic unexpected failure 500. Do not classify arbitrary RuntimeException text as a public API error. Validate profile before child lookup; return no existence detail across profiles.

No proposed routes are specified for deferred domains: inventing their contracts now would expand Step 59 beyond the evidence-supported release.

## 10. Authorization Model

Use the unchanged opaque Bearer extraction/admission/guard/principal and ordinary `resolve(..., false)` path, never logout's rate-limit exemption. Existing admission uses 120 requests/family/minute and access-failure controls; preserve limiter failure behavior. No new limiter, auth mechanism, credentials, session fallback or browser token is needed.

Authenticate before domain access; validate positive canonical decimal-string IDs up to existing signed-INT maximum 2147483647. Resolve active profile membership through `HealthProfileRepository::findAccessible(profile, principal.userId)`, preserving the boundary behind `ApiHealthProfileService`. The current projected profile has **no role**, so reading its DTO is insufficient to establish write permission. Use internal authorized membership, not a client role/capability.

Then bind medication to profile and slot to medication, even if the same user can access both profiles. `MedicationScheduleRepository::withMedicine` already rechecks active membership/active profile and owner/editor under the parent lock. Reuse that transaction check for the proposed bearer write; viewer may read but may not mark. Recheck at mutation time even when GET metadata said true. Do not install a bearer principal into `$_SESSION` or spoof browser AuthService.

Browser methods currently obtain their actor from AuthService and HealthProfileService. Reuse requires a small actor-explicit shared domain entry, with browser wrappers unchanged; calling `mark` directly from an API controller today would still require browser identity. Saved checks are separately account-owned; profile editor status never grants access to someone else's saved results. Document delete remains owner-only in any future phase.

## 11. Request/Response Design Principles

Only the two allowlisted JSON strings are writable. Derive recorded_by, medicine label, recorded/created/updated times, timezone, eligibility and related IDs from admitted principal/locked server data. Reject null state, user IDs, client timestamps, label overrides, mass assignment, duplicate keys and query-injected fields. Use existing bounded flat JSON decoding where applicable; the authentication-specific `input` helper must not be treated as a universal health-record validator. Validate calendar correctness and compare to the server slot-local date inside the transaction.

Response projection must be shared with existing Due semantics but bounded to the six fields. No database checkoff ID, recorded_by, notes, DOB, memberships, token/family or raw repository row. Preserve `application/json`, no-store, safe envelope/version handling and string IDs. Calendar dates/local times do not convert to device timezone.

Do not add CSRF/session requirements to bearer APIs; preserve CSRF on browser forms. The native transport must allow only the approved new private POST shape, still reject credentials on public routes, and retain HTTPS/no redirect/no cookies behavior.

Preflight access refresh uses the existing single-flight coordinator. A definite admission 401 may recover at most once **only with a proven before-mutation rejection contract**; otherwise obtain fresh admission and ask the user to resubmit. Never retry timeout, abort, lost response, 5xx or ambiguous write completion automatically. First refresh authoritative Due Today on uncertainty, show whether current state is now observable, and do not attribute another device's action to this request. Identical state resubmission does not create a second occurrence, but existing upsert updates actor/time: this is uniqueness, not strict side-effect-free replay. No offline queue or background auto-checkoff.

## 12. Database/Schema Impact

**Recommended Step 59 slice: no schema change, migration, new table/index/trigger or SQL-mode change.** `health_medicine_schedule_slots` already has medicine/creator FKs, active/time/date checks and an active index. `health_medicine_schedule_checkoffs` already has unique `(schedule_id,scheduled_on)`, binary taken/skipped CHECK, slot/actor FKs and recorded timestamps/initial label. Preserve all of them. Membership's unique profile/user and active role/status boundary already exists.

Schedule replacement retains old slot identity/history; remove sets inactive. Checkoff correction updates state/actor/recorded_at/updated_at while preserving initial medicine_label and created_at. Medication non-current updates serialize on medicine and retire active slots; returning current does not reactivate them. Hard medication deletion cascades schedule/checkoff history, explaining its deferral.

Existing conditions/allergies/procedures/profile/insurance/document and saved-check tables support their current browser operations; that does not mean bearer adapters or mobile projections exist. Future saved creation without browser Session requires a separately designed server-capture lifecycle; whether it needs persistent storage cannot be asserted without that scope. Do not pre-approve a migration. Standalone document metadata and automatic resume are not solved by table availability.

Only schema source was inspected. No live schema census, SQL statement or connection was executed; this report does not claim a fresh deployment/metadata certification.

## 13. Mobile UX Impact

Later adjust only the daily workflow for Step 59: selected profile → Due Today → identified medication/time/timezone/date → explicit Taken or Skipped → confirmation → pending state → server result → authoritative refresh. Show the prior state when correcting. Reuse one scoped medications collection to join `health_medicine_id` to brand/generic information, optionally showing existing recorded strength; no per-slot N+1 fetch or inferred prescribing data. Missing/failed lookup leaves a read-only card with a safe details-unavailable retry, **no actionable anonymous medicine**. No changed server ordering.

Use proposed metadata to hide/disable editing for viewers; missing capability means read-only, never assumed writable. Disable both buttons for that occurrence while submitting and suppress duplicate taps. Bind pending work to account epoch/profile/slot/date; switching profile, signing out or disposal prevents late UI updates. An admitted server write can finish after navigation: cancellation is not rollback. Do not redirect a late write to the newly selected person.

On success show recorded state, invalidate/reload Due paging from page 1 and refresh the dashboard's related view; do not mix yesterday's pages with today. On 409 reload rather than silently substitute a new date. Existing `DailyHealthScope` and paging restart rules are reusable. Membership loss triggers existing safe profile recovery; a valid viewer 403 should explain read-only access rather than infer the profile disappeared. Retain explicit Refresh and server state; no device-time medication authority.

Other write UIs in the matrix need separately scoped forms/detail projections, validation retention and refresh behavior. No mobile checker result exists from which to offer Save. Do not put Add Allergy/Procedure behind count-only cards without their read/detail experience. No account, insurance or document placeholder buttons.

## 14. Safety/Privacy Considerations

Taken/Skipped records an explicit user report, not proof of ingestion, adherence scoring, prescribing advice or a recommendation to take/skip a medicine. Record-stop would report a user-entered historical change, not advise stopping. Never automatically record from elapsed time, symptoms, notification dismissal or device clock. No diagnosis, dosage/treatment recommendation, risk inference or AI clinical logic belongs in this slice.

Do not log payloads, medication labels, health IDs tied to identity, bearer tokens or credentials. Retain ephemeral health state, protected credential storage and private background behavior. Do not add analytics, shared caches or notification content exposing health information. Saved historical scores/labels/disclaimer remain stored snapshots; retrieval must never rerun matching or replace historical content with current results. No Medicine/Treatment governance/publication, Authentication, TLS or paused verified-phone track changes are recommended.

## 15. Testing Strategy for Step 59

Future tests, not work performed in 58F:

1. Domain tests with deterministic clock: owner/editor success, viewer/outsider/revoked/archived denial; medication/slot mismatch; only exact states; invalid/null/unknown fields; valid real calendar dates; stale/future local days; inclusive start/end, medication start, inactive/replaced slot and stopped/historical medicine; different timezones, local midnight and DST fold. Keep later-today occurrences eligible, as now.
2. Isolated MariaDB tests, under separate fixture authorization: real transaction/rollback and uniqueness; two simultaneous marks and correction; mark versus medicine stop, slot retirement/replacement, membership revocation and deletion; both possible serial orders respected. Verify no extra rows, preserved initial label/created_at and correct last recorded state. Same-state repetition may update actor/time; do not assert unsupported strict idempotence. No manual normal-KOBI fixture writes.
3. HTTPS API tests: genuine bearer only, browser cookie insufficient, no browser Session requirement, exact projections/string IDs, auth-before-domain, unknown query/body/type/duplicate fields, JSON/MIME/size/status handling, unchanged limiter/429/503, no leaks, capability false for viewer and stale capability not authority. Existing GET clients must still parse additive metadata.
4. Flutter repository/state tests: narrow POST allowlist, no cookie/public token leak, preflight single-flight refresh, definite 401 policy, no uncertain write replay, disable double taps, correction confirmation, refresh after success/uncertainty, rollover and pagination reset; label lookup missing/failure; permission unknown/false; read-only viewer; logout/profile-switch/late response safety.
5. Widget checks at 390×844 and 768×1024, long labels/large text, accessible controls, explicit state/confirmation/error/cooldown; unchanged Home/Health/Saved/Account/Knowledge journeys and empty profiles/schedules.
6. Reuse existing browser schedule and medication-date regressions, profile/identity/auth/read API/saved-check tests and full relevant Flutter suite. Verify browser CSRF, replacement/retirement/history and shared data visibility. Do not weaken tests for reuse.
7. On an approved trusted non-production HTTPS environment/device: genuine sign-in, checkoff/correction, refresh/restart, permission loss, logout; native secure storage/privacy and network error handling. iOS needs Apple tooling. Existing localhost Windows TLS acceptance is not mobile trust acceptance. No new production family or health fixture is implicitly authorized by this proposal.

## 16. Explicitly Deferred Work

All SHOULD HAVE operations remain outside the first Step 59 release and need subsequent scope approval. All DEFER and DO NOT ADD NOW matrix entries remain unimplemented. In particular: medication/schedule CRUD and resume, record history API, conditions/allergies/procedures CRUD, saved creation/deletion, profile creation/demographics, documents/uploads/downloads, insurance, account/email/password/contact management, Google/native registration/recovery, sharing and verified mobile/OTP. No reminders, background checkoffs, offline mutation queue, adherence analytics, clinical recommendations or governance redesign.

Before future condition writes, investigate the observed inherited date-only parsing issue with a DB-free focused test. Do not copy it into a new API or silently fix the browser as part of this review. Allergy/procedure/document date handling also merits calendar-day acceptance when those domains are actually scoped.

## 17. Validation Results

Executed in this review with `D:\xampp\php\php.exe`, unchanged existing tests and in-memory repositories; each process exited 0:

| Existing suite | Checks passed |
|---|---:|
| `tests/api-medication-schedules.php` | 71 API + 52 reused browser schedule |
| `tests/health-medicine-dates.php` | 26 |
| `tests/saved-symptom-checks.php` | 63 |
| `tests/api-saved-symptom-checks.php` | 58 |
| `tests/api-health-conditions.php` | 65 |
| `tests/api-health-profiles.php` | 20 |
| `tests/api-auth.php` | 103 |
| **Total** | **458** |

These are existing behavior regressions, not tests of the proposed API. No HTTP, MariaDB/deployment or credential-family harness ran. Source/route/schema inspection established existing versus proposed endpoints, role boundaries, transaction semantics and writable fields. No tests were created or modified.

File integrity: before review work, a sorted path/SHA-256 aggregate baseline covered **678 existing project files** returned by the read-only inventory, excluding this new report, `.git`, vendor, storage and generated/ignored tooling/build caches. Final recheck **PASS**: identical count and digest, `3B3FCCB9B930F624BB964FF486A5019E734404098393E65BF1802BC8180A92DE`; zero additions, removals or byte changes within that inventory. This covers application, Flutter source/native configuration, routes, schema, tests, configuration and existing documentation. It is file evidence, not a database-state audit. Report inspection confirmed all 19 required sections and 42 individually classified candidate operations.

No PHP source changed, so modified-file PHP lint is not applicable. No Dart source changed; analysis/build/Flutter tests were not rerun merely for a documentation review or to mutate SDK caches. Historical 58E evidence is 159 Flutter tests, clean analysis/formatting and Android debug compilation; these were **not rerun or newly certified** here. Native-device, real mobile HTTPS and iOS runtime acceptance remain explicitly pending. No Git command was used for diff inspection; baseline comparison is the read-only change check.

## 18. Files Changed

Created only `docs/task-58f-mobile-v1-write-readiness-review.md`. No pre-existing project file modified; no implementation file created. No report from an earlier step edited.

## 19. Confirmation of No Implementation / No DB Mutation / No Git Mutation

No Step 59 implementation, Flutter/backend source edit, route registration, configuration/.htaccess/TLS/authentication/limiter change, schema edit/migration, SQL execution/SQL-mode change, database connection, database data write, new normal credential family, browser mutation, or Git operation occurred. No disposable databases/fixtures were created. Treatment, Medicine, Symptom Checker and existing application behavior were not changed. The proposed endpoints remain documentation only.

**STOP: Step 58F only.**
