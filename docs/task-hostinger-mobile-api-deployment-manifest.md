# KOBI Core v0.1.0 — Hostinger Mobile API Deployment Manifest

Date: 19 October 2026. Scope: local, read-only deployment planning. Only this report is created. Paths below are relative to the KOBI application root unless stated otherwise.

## 1. Executive Summary

**READY WITH HOSTINGER VERIFICATION REQUIRED**

The local workspace contains all 21 requested V1 API routes. The owner confirms that these endpoints have not been deployed to Hostinger. A coherent API deployment needs the shared bootstrap, routing, authentication, domain services, repositories, helpers, reference resource, Composer runtime and server configuration—not only API controllers.

The explicit API compatibility set contains **59 local PHP files**, plus conditional configuration, reviewed rewrite/header-forwarding rules and Composer dependencies. Section 19 enumerates all 147 backend/configuration candidates and 117 browser view/asset candidates so later comparison need not guess. DEPLOY means the file must exist at a compatible version; it does not establish that its remote copy differs. Conditional website/internal files are not an instruction to activate unrelated features.

**An exact upload delta and exact remote migrations cannot be determined without the deployed baseline.** No Hostinger connection was made. Resolve the configuration filename case gate, compare remote files/schema, verify backup recovery, and review configuration before authorizing upload. Do not upload Flutter, tests, reports, local secrets or the full schema dump.

## 2. Current Local KOBI State

| Item | Known local state |
|---|---|
| Application root | `D:\xampp\htdocs\kobi` |
| HTTP/public root | `public/`; entry point `public/index.php` |
| Application | `app/Controllers/`, `app/Services/`, `app/Repositories/`, `app/Core/`, `app/Algorithms/`, `app/Helpers/` |
| Configuration | Actual file `app/Config/config.php`; root private `.env`; `config/Database.php` is an empty, unused legacy stub |
| Bootstrap/routes | `bootstrap/app.php`, `routes/web.php` |
| Browser rendering | `app/Views/`; `public/assets/` |
| Runtime metadata | `resources/disease-references.php` |
| Dependency loading | `composer.json`, `composer.lock`, generated `vendor/autoload.php` |
| Data access | Repository → BaseRepository → Core Database → PDO MySQL |
| Mobile | `mobile/kobi_mobile/`, independent Flutter client |

Read `AGENTS.md`, current source, schema artifacts, public/authenticated API contracts and relevant implementation/deployment reports. `docs/INSTALL.md`, `docs/DATABASE.md` and `docs/ARCHITECTURE.md` are zero-byte stubs, not usable hosting instructions. Later implementation reports and current source take precedence over earlier reports' historic “not yet deployed locally” checkpoints. Local XAMPP deployment evidence does not prove Hostinger deployment.

**Case-sensitive hosting gate:** both `bootstrap/app.php` and `app/Helpers/functions.php::config()` require `app/Config/Config.php`, while the actual directory entry is `config.php`. Windows tolerates this; a case-sensitive filesystem does not. Before deployment, inspect the remote filename/configuration and approve a consistent destination mapping or a separately scoped correction. Mapping reviewed configuration bytes to **`app/Config/Config.php`** satisfies these two references without changing local source, but must be checked against all deployed references. Do not upload only lowercase `config.php` and assume Linux will find it. Nothing was renamed or fixed here.

## 3. Current Mobile API Surface

Every route below is registered in `routes/web.php`. Relative paths are under `/api/v1`. Total: **16 GET + 5 POST = 21 routes**.

| Method | Path | Controller action | Domain path |
|---|---|---|---|
| GET | `/diseases` | PublicDiseaseApiController::index | PublicApiService::listDiseases |
| GET | `/diseases/search` | PublicDiseaseApiController::search | PublicApiService::searchDiseases |
| GET | `/diseases/{slug}` | PublicDiseaseApiController::show | PublicApiService::detail |
| GET | `/substances/search` | PublicSubstanceApiController::search | PublicApiService::searchSubstances |
| POST | `/auth/login` | ApiAuthController::login | ApiAuthService |
| POST | `/auth/refresh` | ApiAuthController::refresh | ApiAuthService |
| POST | `/auth/logout` | ApiAuthController::logout | ApiAuthService |
| POST | `/auth/logout-all` | ApiAuthController::logoutAll | ApiAuthService |
| GET | `/me` | ApiAuthController::me | ApiIdentityService |
| GET | `/health-profiles` | ApiAuthController::healthProfiles | ApiHealthProfileService |
| GET | `/health-profiles/{profileId}` | ApiAuthController::healthProfile | ApiHealthProfileService |
| GET | `/health-profiles/{profileId}/medications` | ApiAuthController::medications | ApiHealthMedicineService |
| GET | `/health-profiles/{profileId}/medications/{medicationId}` | ApiAuthController::medication | ApiHealthMedicineService |
| GET | `/health-profiles/{profileId}/medical-summary` | ApiAuthController::medicalSummary | ApiMedicalSummaryService |
| GET | `/health-profiles/{profileId}/conditions` | ApiAuthController::conditions | ApiHealthConditionService |
| GET | `/health-profiles/{profileId}/conditions/{conditionId}` | ApiAuthController::condition | ApiHealthConditionService |
| GET | `/health-profiles/{profileId}/medication-schedules` | ApiAuthController::medicationSchedules | ApiMedicationScheduleService |
| GET | `/health-profiles/{profileId}/medication-schedules/due-today` | ApiAuthController::medicationDueToday | ApiMedicationScheduleService |
| GET | `/saved-symptom-checks` | ApiAuthController::savedSymptomChecks | ApiSavedSymptomCheckService |
| GET | `/saved-symptom-checks/{savedCheckId}` | ApiAuthController::savedSymptomCheck | ApiSavedSymptomCheckService |
| POST | `/health-profiles/{profileId}/medication-schedules/{scheduleId}/checkoff` | ApiAuthController::medicationCheckoff | ApiMedicationScheduleService → MedicationScheduleService |

The proposed root-domain mobile base is `https://kobi.monchez.in/api/v1`; confirm the actual mounted application path on Hostinger. Do not copy the local `/kobi/public` mount into production configuration.

## 4. Why Hostinger Currently Returns 404

The owner's deployment clarification establishes that the new API code is absent remotely. The prior Step 60C-1 report records four public GETs and 17 unsupported-method OPTIONS requests returning 404 HTML with successful TLS verification. That report alone could not distinguish missing code from rewrite/mount issues; the owner's new clarification supplies the missing deployment fact.

TLS success establishes an HTTPS connection to the hostname. It does not install PHP routes or prove Authorization forwarding, JSON envelopes, database prerequisites or API functionality. Current routing and document root still require remote verification after the code comparison. This task made **no new HTTP request** to Hostinger.

## 5. Changed File Inventory

**Total changed/added since a Git or deployed baseline: NOT DETERMINABLE under this audit.** No Git operations are permitted, no deployed file snapshot was supplied, and the 500+ observation is not a comparison baseline. Historic per-task totals overlap and must not be summed. The following are exact **current file inventory counts**, not invented changed-file counts.

Inventory command: `rg --files --hidden -g '!.git/**' -g '!vendor/**' -g '!storage/**'`, honoring ignore rules. Before this report:

| Disjoint category | Current inventoried files |
|---|---:|
| Backend runtime/configuration: app excluding Views, bootstrap, routes, resources, public index/.htaccess | 147 |
| Frontend web runtime: 96 views + 21 assets | 117 |
| Tests | 130 |
| Docs, including reports/screenshots | 146 |
| Flutter/mobile source/project metadata | 118 |
| Database reference/migration artifacts | 9 |
| Root project/dependency metadata | 10 |
| Other: 14 IDE files + empty config/Database.php + cleanup script + uploads/.gitkeep | 17 |
| **Total before report** | **694** |
| **Total after this report only** | **695** |

Of the backend set, 59 PHP files form the API compatibility set. The remaining 88 backend candidates include configuration/rules and other existing web/internal features; they are not 88 additional API features.

A separate raw `rg --files --hidden --no-ignore -g '!.git/**'` path inventory counted **143,838** files at inspection time:

| Raw, disjoint path group | Files |
|---|---:|
| vendor/ dependencies and contents | 38,322 |
| mobile/.tooling/ SDK/toolchain contents | 103,280 |
| mobile/kobi_mobile/build, .dart_tool, .gradle groups | 1,188 |
| storage/ contents | 319 |
| public/uploads/ contents | 2 |
| .vs/ IDE files | 14 |
| Everything else | 713 |
| **Total** | **143,838** |

These raw groups describe locations, not change history; vendor is dependency runtime, not all “generated code,” and storage/uploads may contain real data. Build/tool caches may change outside an audit and must never be treated as deployment deltas. The 118 source inventory entries include mobile/.gitignore. No trustworthy changed/added breakdown exists for generated or temporary files without a baseline.

## 6. Runtime Backend Files

The API compatibility set is the 59 DEPLOY PHP rows in section 19.1. It includes:

- Entry/bootstrap/routes/helper/resource files.
- Three API controllers and the shared Controller base.
- Core application/container/request/router/response/database/logger/session/view classes.
- All API authentication and private/public API services.
- Canonical summary, schedule, saved-check, profile, medication and password services.
- All repository parents and concrete repositories reached by those services.
- Whole-class supporting dependencies QuickFactsBuilder and SymptomMatcher.

Configuration and rewrite rules are separately conditional because their deployed values/layout must be merged. Composer runtime must exist too; its generated files are not included in the 59-source-file count.

**Constructor coupling matters:** Container recursively resolves every typed constructor parameter. ApiAuthController constructs all its API feature services even for login or /me. SavedSymptomCheckService constructs SymptomCheckerService, which constructs SymptomRepository, DiseaseRepository and SymptomMatcher. Shipping only the service for a particular requested action is insufficient. This does not mean saved-check detail reruns matching or needs new symptom fixtures. Constructors may establish the shared database connection; no constructors were executed in this audit.

## 7. Frontend/Web Runtime Files

Section 19.2 enumerates the 96 views and 21 assets. JSON API output needs none of these templates/assets directly, but existing website routes and API public_url/registry_url links must still work after shared bootstrap/routes/service changes.

Compare the existing site's browser controllers/services and these assets as coherent feature sets. Preserve Disease Detail Diagnosis/Treatments, current legacy Treatment read behavior, homepage/catalog search, substance search, Account/Health Profile/Medication flows, medical summary and CSRF behavior. Do not replace isolated templates against incompatible older controllers.

Fonts under public/assets/css/fonts are runtime assets. Screenshots under docs are not. Existing uploads and protected health documents are server data; preserve them rather than synchronizing from this workstation.

## 8. Tests and Development Files

All **130 tests/** inventory entries are excluded from the public deployment. This includes unit/MariaDB/HTTPS harnesses, production-smoke launchers, recovery/deployment helpers and one-off acceptance artifacts. Do not run historic credential-family scripts on Hostinger.

All **146 existing docs/** entries, screenshots, JSON evidence and office documents stay local. IDE files, SDKs, emulator state, build caches and development logs stay local. Future off-server validation can use tests, but test tooling is not server runtime.

The CLI cleanup script is an operational exception described in section 11; it is not necessary to serve requests and must not be exposed as an HTTP endpoint.

## 9. Flutter/Mobile Files

**mobile/kobi_mobile/ is NOT a Hostinger deployment directory.**

The flow is **Flutter source → Android APK build → Android device → HTTPS KOBI API**. Flutter source is not uploaded to Hostinger. Exclude the entire mobile/ tree, including .tooling, platform projects, lib, test, pubspec files, Android/iOS files, tool scripts, lock files and local APKs.

The client requires an explicitly supplied HTTPS API base ending exactly /api/v1, without trailing slash/query/credentials. No insecure fallback or certificate bypass is supported. Read mobile/kobi_mobile/README.md for the exact environment defines.

## 10. Files That Must NOT Be Uploaded

| Repository area/artifact | Classification | Reason |
|---|---|---|
| mobile/** | DO NOT DEPLOY | Native client source/toolchain/APKs, not PHP server runtime |
| tests/** | DO NOT DEPLOY | Harnesses, synthetic fixture creation and operator scripts |
| docs/** | DO NOT DEPLOY | Reports, screenshots and research; not runtime citation resource |
| .git/** | DO NOT DEPLOY | Repository metadata |
| .vs/**, IDE/editor caches | DO NOT DEPLOY | Workstation state |
| storage/** existing contents | DO NOT DEPLOY | Logs, caches, sessions, private documents, test/recovery artifacts; provision directories separately |
| public/uploads/** local contents | DO NOT DEPLOY | Preserve remote user data; .gitkeep carries no runtime content |
| Local .env | DO NOT DEPLOY | Local credentials, paths, limiter secret and environment-specific settings |
| Local TLS/CA/private-key files | DO NOT DEPLOY | Hostinger already manages its own HTTPS identity |
| Temporary files, debug logs, backups, SQL dumps, packages | DO NOT DEPLOY | May contain secrets or real data; never place in web root |
| AGENTS.md, .editorconfig, .gitattributes, .gitignore | DO NOT DEPLOY | Development/project metadata |
| config/Database.php | DO NOT DEPLOY | Empty stub; runtime uses app/Core/Database.php |
| .htaccessSERVER | DO NOT DEPLOY | Local alternate rules hardcode /kobi/; filename is not evidence of deployed Hostinger rules |
| LICENCE.txt | CONDITIONAL | Distribution/legal artifact if required by release procedure; not an API dependency |

No uploaded source package should include development dependencies or local Composer caches. Required production vendor runtime is handled separately.

## 11. Conditional Files

| PATH | TYPE | DEPLOY? | REASON / DEPENDENCIES / NOTES |
|---|---|---|---|
| app/Config/config.php → reviewed app/Config/Config.php destination | PHP configuration | CONDITIONAL, REQUIRED CONFIGURATION | Resolve case gate; merge Hostinger settings; do not transfer local owner ID/bootstrap decisions or enable governance |
| public/.htaccess | Apache rules | CONDITIONAL, REQUIRED BEHAVIOR | Merge into actual deployed per-directory rules after inspection |
| .htaccess (application root) | Exposure protection | CONDITIONAL | Needed according to actual public-root layout; root protection file itself does not implement API forwarding |
| composer.json + composer.lock | Dependency specification | CONDITIONAL | Compare together; use locked production dependency installation, not composer update |
| vendor/** production output | Generated dependency runtime | CONDITIONAL, REQUIRED RUNTIME | Keep compatible existing vendor or generate from reviewed lock; vendor/autoload.php is mandatory |
| .env.example | Template | CONDITIONAL / operator reference only | Names reference, not production settings or complete mail/Google configuration |
| Hostinger private .env or populated $_ENV | Environment configuration | CONDITIONAL, REQUIRED CONFIGURATION | Provision/merge remotely through approved secret process; never upload this local .env |
| database/api-auth-foundation.sql | Schema artifact | CONDITIONAL / migration review only | Two API tables + one trigger, only if absent after review |
| database/saved-symptom-checks.sql | Schema artifact | CONDITIONAL / migration review only | Existing Saved Checks prerequisite if absent |
| database/medication-schedules.sql | Schema artifact | CONDITIONAL / migration review only | Existing schedule/checkoff prerequisites if absent |
| database/user-mobile-number.sql | Schema artifact | CONDITIONAL / migration review only | users.mobile_number is read by shared UserRepository; inspect prerequisite |
| database/structure.sql | Schema reference | DO NOT IMPORT/DEPLOY AS MIGRATION | Contains DROP TABLE, SQL-mode directives and unrelated governance objects |
| database/medicine-phase316-deployment.sql | Governance migration | DO NOT DEPLOY FOR THIS API TASK | Not a listed API prerequisite |
| database/medicine-phase319-provisional.sql | Governance migration | DO NOT DEPLOY FOR THIS API TASK | Not a listed API prerequisite |
| database/medicine-phase321a-owner-authority.sql | Governance migration | DO NOT DEPLOY FOR THIS API TASK | Local ownership must not be propagated to remote accounts |
| database/medicine-phase322-reviewer-evidence.sql | Governance migration | DO NOT DEPLOY FOR THIS API TASK | Not a listed API prerequisite |
| scripts/cleanup-api-auth.php | CLI operation | CONDITIONAL | Optional approved retention operation outside web root, with reviewed remote PHP/path/retention; deletes API history, so never run automatically |
| Seed files/fixtures | Data | DO NOT DEPLOY | No new seeds or local data required by API installation |
| Certificates/private keys | TLS | DO NOT DEPLOY LOCAL MATERIAL | Use existing Hostinger HTTPS chain; verify it later |

The shared configuration currently contains local owner-bootstrap metadata. This is not authority for any similarly numbered Hostinger account. Keep Treatment HOLD and Medicine/pilot exposure policies at their separately approved deployed values. No governance bootstrap or role grant belongs in this deployment.

## 12. API Dependency Map

All rows share: public/index.php → Composer autoload/functions → bootstrap/config → App/Container → routes/web.php → Router/Request → controller → service → repository → BaseRepository → Database/PDO. PublicApiResponse and Logger implement the response/error boundary. Parent Controller and Core View must exist even when no view is rendered.

| Endpoint group | Controller → service dependencies | Repository/data path |
|---|---|---|
| Disease list/search/detail | PublicDiseaseApiController → PublicApiService; PublicSearchService static normalization; DiseaseReferenceService | DiseaseRepository → diseases, body_parts, disease_content, disease_causes, causes; reference resource |
| Substance search | PublicSubstanceApiController → same PublicApiService and normalization | SubstanceRepository → kobi_substances (canonical names only) |
| Auth four POSTs | ApiAuthController → ApiAuthService, ApiAuthGuard, ApiAuthClock, ApiAuthRateLimiter, AuthService | ApiAuthRepository → users, user_api_sessions, user_api_refresh_tokens; shared UserRepository |
| /me | Same authenticated admission → ApiIdentityService | UserRepository → users; exact four-field identity projection |
| Profile list/detail | Admission → ApiHealthProfileService | HealthProfileRepository::findAccessibleByUser/findAccessible → health_profiles + health_profile_members |
| Medication list/detail | Admission → ApiHealthMedicineService → ApiHealthProfileService | HealthMedicineRepository; child constrained by authorized profile |
| Medical summary | Admission → ApiMedicalSummaryService → MedicalSummaryService → ApiHealthProfileService; constructor also HealthProfileService | HealthConditionRepository, HealthMedicineRepository, HealthAllergyRepository, HealthProcedureRepository; live counts only, not insurance/documents/shares |
| Condition list/detail | Admission → ApiHealthConditionService → ApiHealthProfileService | HealthConditionRepository; profile-bound count/list/detail |
| Schedule configuration/Due Today | Admission → ApiMedicationScheduleService → ApiHealthProfileService + MedicationScheduleService | MedicationScheduleRepository → medicines, slots, checkoffs, current profile membership |
| Saved-check list/detail | Admission → ApiSavedSymptomCheckService → SavedSymptomCheckService | SavedSymptomCheckRepository → user_saved_symptom_checks owned by principal; bounded stored snapshot decoder, no live matching |
| Checkoff POST | Admission → ApiMedicationScheduleService → MedicationScheduleService::markTodayForPrincipal | MedicationScheduleRepository; profile/member/medicine lock, current-family recheck, server date/timezone, validated taken/skipped upsert |

Additional constructor closure:
AuthService → UserRepository + Session + UserAuthTokenService + Database.
UserAuthTokenService → UserAuthTokenRepository.
MedicalSummaryService → HealthProfileService → HealthProfileRepository + AuthService.
MedicationScheduleService → HealthMedicineService → HealthMedicineRepository + HealthProfileService, plus AuthService and ApiHealthProfileService.
SavedSymptomCheckService → AuthService + Session + SymptomCheckerService → SymptomRepository + DiseaseRepository + SymptomMatcher.
DiseaseRepository's shared class references QuickFactsBuilder.
All are listed explicitly in section 19.

Profile reads use the authenticated principal and canonical membership authorization. Checkoff is the only new clinical-action write in this API surface; its metadata capability is not an authorization substitute. Do not deploy a reduced implementation that omits transactional rechecks.

## 13. Authentication Dependency Map

| Responsibility | Exact local files / requirement |
|---|---|
| HTTPS/input and Bearer server variables | app/Controllers/ApiAuthController.php; app/Core/Request.php |
| Bearer format/extraction and guard | app/Services/ApiAuthGuard.php; controller reads HTTP_AUTHORIZATION or REDIRECT_HTTP_AUTHORIZATION and rejects conflicts |
| Access/refresh generation, purpose-separated digests, family rotation/reuse | app/Services/ApiAuthService.php |
| Time, principal, safe failures | app/Services/ApiAuthClock.php, ApiAuthPrincipal.php, ApiAuthException.php |
| Credential verification | app/Services/AuthService.php; app/Repositories/UserRepository.php |
| Persistence, locks, transactions | app/Repositories/ApiAuthRepository.php, BaseRepository.php; app/Core/Database.php |
| Rate admission | app/Services/ApiAuthRateLimiter.php; independently provisioned API_AUTH_RATE_KEY; private locking storage |
| Account-change revocation | database/api-auth-foundation.sql: trg_users_api_revoke + two API tables |
| JSON/no-store/safe failure/session boundary | app/Core/PublicApiResponse.php; bootstrap/app.php; Router.php; Logger.php |
| Browser compatibility/constructor dependencies | app/Core/Session.php; UserAuthTokenService.php; UserAuthTokenRepository.php; other Controller constructor dependencies in section 12 |
| Forwarding | Reviewed Hostinger equivalent of scoped public/.htaccess rule |

Access lifetime is 10 minutes, refresh inactivity lifetime 14 days, absolute family limit 30 days. Rotation replaces access and refresh; replay revokes the family. Re-enabling an account does not restore revoked families. Preserve digest-only persistence and the existing model. Native Google authentication, new mobile-identity verification and medical sharing are not prerequisites.

API requests do not authenticate via browser cookies or require browser CSRF; existing browser actions retain CSRF. Successful API testing creates/revokes credential history through normal lifecycle operations and needs separate authorization. No test family was created here.

## 14. Apache/.htaccess Requirements

**CURRENT LOCAL VERSION:** public/.htaccess was read. It disables indexes, blocks hidden paths, forwards Authorization for explicit authenticated API routes, then routes non-file/non-directory requests to index.php with QSA/L.

The exact current local forwarding block is:

```apache
RewriteCond %{HTTP:Authorization} .
RewriteRule ^api/v1/(?:auth/|me$|saved-symptom-checks(?:/[^/]+)?$|health-profiles(?:/[^/]+(?:/(?:medications|conditions)(?:/[^/]+)?|/medical-summary|/medication-schedules(?:/due-today|/[^/]+/checkoff)?)?)?$) - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

It precedes:

```apache
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

**CURRENT HOSTINGER VERSION: UNKNOWN.** No verified remote copy is present in this audit. The root file named .htaccessSERVER hardcodes /kobi/ and has no established remote provenance. It cannot be treated as the current Hostinger file.

Inspect document root, Apache/LiteSpeed-compatible rewrite support, AllowOverride/Options permission, PHP handler and existing redirect/canonical/cache rules first. Merge only necessary equivalent rules in the correct directory context; preserve existing routes and Hostinger controls. This local block is not a universal drop-in for an arbitrary mount. Do not stack duplicate forwarding mechanisms or loosen the Bearer parser.

Prefer mapping the domain document root directly to the public directory while keeping app/vendor/config/storage outside public reach. If shared hosting prevents this, a separately reviewed layout/protection plan is required; the root protection rules alone do not prove all resources/routes are hidden. Verify no direct PHP source, private storage, .env, Composer metadata or backup is downloadable.

Controllers explicitly require $_SERVER['HTTPS'] to be present and not 'off'. Existing Hostinger TLS termination/PHP signaling must satisfy this without trusting arbitrary client headers. No local TLS configuration, Windows certificate, XAMPP vhost, /kobi RewriteBase or port 8443 belongs in this upload. Native Android does not require adding browser CORS permission.

## 15. Database/Schema Requirements

**Hostinger schema is unknown. REQUIRES MANUAL DATABASE MIGRATION REVIEW.** No local database connection or SQL was executed.

Separate the deployment into:
A. runtime files (section 19);
B. reviewed additive schema only where missing/incompatible;
C. data/fixtures (none required for code deployment; no local data transfer).

| Completed step | Schema conclusion from source + implementation report |
|---|---|
| 50B | New user_api_sessions, user_api_refresh_tokens and trg_users_api_revoke; artifact database/api-auth-foundation.sql. Later deployment report proves local XAMPP deployment only |
| 51B | No additional schema; identity reads users |
| 52B | No additional schema; existing profiles/memberships |
| 53B | No additional schema; health_medicines |
| 54B | No additional schema; existing live summary source tables |
| 55B | No additional schema; health_conditions |
| 56B | No additional schema; reuses Step 45B schedule/checkoff tables |
| 57B | No additional schema; reuses Step 44B saved-check table |
| 59A | No additional schema; reuses schedule/checkoff tables and constraints |

**Explicit conditional new/prerequisite objects:**

- user_api_sessions: primary key; unique public/access identifiers; user/state/expiry/revoked indexes; FK to users with cascade; six CHECK constraints enforcing IDs, labels, hashes, generation, times and revocation consistency.
- user_api_refresh_tokens: unique token hash, family/generation, family/active_slot; expiry index; cascade FK to sessions; three CHECKs; persistent generated active_slot. Do not replace the generated-column uniqueness model with a weaker nullable flag.
- trg_users_api_revoke: AFTER UPDATE ON users, revokes API families when password_hash changes, active status is lost or verified email becomes unverified; uses null-safe/binary comparisons and UTC_TIMESTAMP. Preserve existing users triggers too.
- user_saved_symptom_checks: unique (user_id,check_key), chronological index, cascade FK to users, count bounds, valid/bounded JSON CHECK. From database/saved-symptom-checks.sql.
- health_medicine_schedule_slots: medicine/creator indexes; medicine cascade and creator SET NULL FKs; time, timezone nonempty, active and date-order CHECKs.
- health_medicine_schedule_checkoffs: unique (schedule_id,scheduled_on), actor index; slot cascade and actor SET NULL FKs; binary taken/skipped CHECK. Last two from database/medication-schedules.sql.
- users.mobile_number and chk_user_mobile_number: conditional existing Step 45C prerequisite from database/user-mobile-number.sql because the shared UserRepository::findById selects it, including /me. Do not assume “mobile verification not required” means this deployed column can be absent.

**Existing data/schema prerequisites to compare:**

| API | Tables / important columns |
|---|---|
| Credentials/identity | users: id, email, password_hash, password_set_at, status, email_verified_at; shared reads also name, mobile_number, last_login_at, created_at, updated_at. Existing browser auth also user_auth_tokens and user_auth_identities |
| Profiles/access | health_profiles: id, profile_type, full_name, status and canonical demographics/creator metadata; health_profile_members: health_profile_id, user_id, role, status, unique profile/user and FKs |
| Medicines | health_medicines: id, health_profile_id, brand_name, generic_name, strength, dose, frequency, route, status, started_on, stopped_on; canonical browser fields preserved |
| Conditions | health_conditions: id, health_profile_id, condition_name, status, diagnosed_on, resolved_on |
| Summary | health_conditions, health_medicines, health_allergies, health_procedures with existing profile links and grouping fields |
| Public disease | diseases (id, slug, disease_en, disease_hi, gender, body_part_id); body_parts (id,name_en); disease_content (disease_id, five English sections, urgency_note_en); disease_causes + causes |
| Public substance | kobi_substances: substance_id, canonical_name, canonical_name_normalized, is_active, is_searchable; matching search index |
| Schedules/checkoff/saved | Exact objects above; existing parent users/profiles/medicines and memberships required |

Reference metadata is keyed to eight numeric disease IDs in resources/disease-references.php. Confirm the remote ID-to-disease mapping matches before enabling section attribution. Do not insert/reassign disease rows or copy local disease_content to make it match. Saved-check snapshot format must match the canonical decoder; incompatible existing data must be reported, not rewritten silently.

If remote objects already match, apply **no DDL**. If an object partially exists or parent columns differ, stop for an explicit reviewed incremental migration; never rerun CREATE blindly or drop an object to make it fit. Verify exact SHOW CREATE/index/FK/CHECK/generated-column/trigger metadata and existing data, not only table names. Check actual DB version/engine/collations/privileges and multiple-trigger support. Local MariaDB 10.4.27 compatibility does not prove Hostinger compatibility, especially a different MySQL version's CHECK/generated-column behavior.

structure.sql is a reference dump containing DROP TABLE statements, SQL_MODE changes and unrelated Medicine/Treatment objects. **Do not import it into the existing Hostinger database.** No full local-database overwrite, production-data replacement, SQL-mode change, governance migration, seed/fixture load or historical auth-family transfer is warranted. The three focused creation artifacts have no SQL-mode directives; review on the actual server without weakening constraints or silently dropping statements.

## 16. Configuration Requirements

| Requirement | Local evidence / Hostinger action to verify later |
|---|---|
| PHP | composer.json requires ^8.2; inspect web and CLI versions separately; check locked dependency platform compatibility |
| PDO/MySQL | Core Database uses PDO mysql DSN, native prepared statements, exceptions; require pdo_mysql and matching DB charset |
| Text/JSON/runtime | mbstring explicitly used; JSON, hash, filter, ctype, date/timezone, Reflection/SPL and secure random/password functions must work |
| Existing browser features | Session support; confirm fileinfo for upload features as applicable, cURL/OpenSSL and mail/Google SDK runtime requirements; run locked Composer platform check later |
| Composer lock | Existing google/apiclient ^2.20 and phpmailer/phpmailer ^7.1; keep compatible exact lock, no dependency upgrade. Lock explicitly lists ext-ctype/filter/hash/json in package requires, which is not a complete application-extension list |
| URL/mount | Correct APP_URL, APP_BASE_PATH; no local drive paths or /kobi/public assumption |
| Environment loading | env() reads root .env into $_ENV, then $_ENV only; raw process getenv alone is not its fallback. Verify FPM/Hostinger injects $_ENV or use protected remote .env |
| Debug | APP_DEBUG false; production PHP display_errors off, no argument/secret logging |
| DB | DB_CONNECTION/HOST/PORT/DATABASE/USERNAME/PASSWORD/CHARSET supplied privately for Hostinger |
| Locale/time | Preserve approved APP_TIMEZONE and APP_LOCALE; schedule date uses each slot timezone; no global SQL-mode/timezone workaround |
| Limiter | Separate stable random API_AUTH_RATE_KEY of at least 32 bytes; no local key reuse. storage/cache/api-auth needs private creation/write/locking support |
| Logging | Core Logger writes application-root storage/logs even if APP_STORAGE_PATH differs; both configured storage and this fixed log directory must be checked |
| Browser storage | Preserve existing session path, uploads, private documents and their access/permissions; do not overwrite contents |
| API caching | No-store JSON/errors; Hostinger/CDN must not cache authenticated responses or strip Authorization |
| HTTPS | Valid public chain/hostname and correct PHP HTTPS indicator; no client trust bypass |
| Operations | Single-host flock limiter assumption must match hosting execution/storage; no multi-node guarantee inferred |
| Cron | None required to answer API requests. Optional cleanup only after approved retention/CLI review; not an automatic migration step |

Use minimum necessary directory permissions for the actual PHP user; no world-writable blanket permissions. Dependency installation without SSH may use an operator-built locked production vendor set; build it for compatible PHP/platform and validate generated autoload paths. Do not treat the 38,322-file local vendor tree as the prescribed production package.

## 17. Secrets/Environment Safety

The local .env contains nonempty Google client-secret and API limiter-key settings (presence checked without printing values). It is excluded. Database/mail credentials, OAuth credentials, access/refresh tokens, encryption/session material, private keys and certificate files must never appear in the manifest or upload from local development.

Preserve Hostinger credentials through its existing approved configuration process. Provision only the new independent limiter secret where needed; do not copy local secrets, auth-family rows, cookies, caches or PHP sessions. .env.example provides some names, not a complete production credential/configuration inventory. Retain existing Google redirect URI and mail settings appropriate to Hostinger; do not transfer localhost settings.

Do not expose configuration, source archives, backups or diagnostics under the public root. No passwords or control-panel credentials are requested by this report.

## 18. Hostinger Information Still Required

Known: independent Hostinger shared-hosted domain; owner controls the domain; new API code not yet uploaded; earlier HTTPS succeeded while API paths returned HTML 404. Unknowns below are **MUST VERIFY ON HOSTINGER**, under a later authorized task:

1. Actual application/document root, URL mount, public/index relative layout and case-sensitive filenames.
2. Deployed application version and exact file hashes/inventory, including local-only/server-only/custom files; no reliable Git/deployed baseline exists here.
3. Current public/root .htaccess, rewrites/canonical redirects, PHP handler, Authorization forwarding, WAF/CDN/cache behavior.
4. Web/CLI PHP version, extensions, Composer availability, allowed directory permissions and flock behavior.
5. Current private configuration mechanism, $_ENV availability, configured URL/debug/timezone/storage; secret presence only.
6. Database server/version/engine/collations, actual current objects/columns/constraints/triggers/definers, migration history and user privileges.
7. Required existing datasets and matching disease reference IDs; no health-record values should be dumped into a deployment report.
8. Backup export capability including triggers, generated-column fidelity, protected off-web storage and demonstrated restore procedure.
9. Upload method: SSH/SFTP/FTP/File Manager availability, atomic release options, maintenance procedure and operator rollback access.
10. Remote policy settings for Treatment/Medicine/owner/bootstrap; preserve them and do not infer authority from local account IDs.
11. Separately approved synthetic account/inbox/profile/medication/schedule and cleanup/retention agreement for future authenticated write testing.
12. Existing account/profile cleanup limitations: no automatic SQL deletion or assuming archive/delete UI exists.

No need to invent a new server, acceptance subdomain, DNS route, port, TLS identity or VM for this selected deployment strategy.

## 19. Exact Deployment Manifest

**Interpretation:** DEPLOY = API compatibility source required to exist; retain a byte-compatible remote file rather than uploading it again. CONDITIONAL = compare/merge only after remote inspection and feature/config/schema review. DO NOT DEPLOY = excluded from server package. No remote difference is asserted. All paths are local source paths; map public/ to the confirmed domain document root, leaving sibling app/bootstrap/routes/resources/vendor in their required relative layout.

### 19.1 Exact backend/configuration candidates (147 files)

Dependencies below name classes whose exact paths are also listed. This is a conservative whole-class dependency inventory, including dependencies used by other methods on shared classes; it is not a claim that every method is called on every API request.

| PATH | TYPE | DEPLOY? | REASON | DEPENDENCIES | NOTES |
|---|---|---|---|---|---|
| `app/Algorithms/DiseaseComparisonBuilder.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | PHP built-ins; shared helpers | Not independently required by listed API; do not activate unrelated features |
| `app/Algorithms/DiseaseSimilarity.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | PHP built-ins; shared helpers | Not independently required by listed API; do not activate unrelated features |
| `app/Algorithms/RelatedDiseaseMatcher.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | PHP built-ins; shared helpers | Not independently required by listed API; do not activate unrelated features |
| `app/Algorithms/SymptomMatcher.php` | PHP | DEPLOY | API runtime/shared dependency | PHP built-ins; shared helpers | Retain whole-class dependency compatibility; API saved detail does not rerun matching |
| `app/Config/config.php` | PHP configuration | CONDITIONAL | Both bootstrap and config() require this configuration | env() | CASE GATE: destination app/Config/Config.php as referenced, after remote comparison and approved settings merge; do not copy local owner settings |
| `app/Controllers/AccountController.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | AuthService, Controller, EmailVerificationService, HealthAllergyService, HealthConditionService, HealthDocumentService, HealthInsuranceService, HealthMedicineService, HealthProcedureService, HealthProfileInvitationService, HealthProfileService, HealthTimelineService, Logger, MedicalSummaryService, MedicalSummaryShareService, MedicationScheduleService, SavedDiseaseService, Session, SubstanceService, TreatmentPreferenceService, UserAuthIdentityService | Not independently required by listed API; do not activate unrelated features |
| `app/Controllers/ApiAuthController.php` | PHP | DEPLOY | API runtime/shared dependency | ApiAuthException, ApiAuthGuard, ApiAuthService, ApiHealthConditionService, ApiHealthMedicineService, ApiHealthProfileService, ApiIdentityService, ApiMedicalSummaryService, ApiMedicationScheduleService, ApiSavedSymptomCheckService, Controller, PublicApiResponse, Request | Required locally; upload only if absent/different remotely |
| `app/Controllers/AuthController.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | AuthService, Controller, EmailVerificationService, HealthProfileService, Logger, MailService, Request, Session, UserAuthTokenService | Not independently required by listed API; do not activate unrelated features |
| `app/Controllers/BodyPartController.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BodyPartService, Controller | Not independently required by listed API; do not activate unrelated features |
| `app/Controllers/ComparisonController.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | Controller, DiseaseService | Not independently required by listed API; do not activate unrelated features |
| `app/Controllers/Controller.php` | PHP | DEPLOY | API runtime/shared dependency | View | Required locally; upload only if absent/different remotely |
| `app/Controllers/DiscoveryController.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BodyPartController, Controller, DiscoveryService | Not independently required by listed API; do not activate unrelated features |
| `app/Controllers/DiseaseAdminController.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | AuthService, Controller, DiseaseAdminService | Not independently required by listed API; do not activate unrelated features |
| `app/Controllers/DiseaseController.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | Controller, DiseaseService, SavedDiseaseService | Not independently required by listed API; do not activate unrelated features |
| `app/Controllers/GoogleAuthController.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | AuthService, Controller, GoogleAuthService, GoogleOAuthStateService, HealthProfileService, Logger, Session, UserAuthIdentityService | Not independently required by listed API; do not activate unrelated features |
| `app/Controllers/HealthInsuranceController.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | AuthService, Controller, HealthInsuranceService, HealthProfileService, Session | Not independently required by listed API; do not activate unrelated features |
| `app/Controllers/HomeController.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | Controller, HomeService | Not independently required by listed API; do not activate unrelated features |
| `app/Controllers/MedicationScheduleController.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | AuthService, Controller, Logger, MedicationScheduleService, Session | Not independently required by listed API; do not activate unrelated features |
| `app/Controllers/MedicineOwnerBootstrapController.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | AuthService, Controller, Logger, MedicineGovernanceService, MedicinePersistenceException, Session | Not independently required by listed API; do not activate unrelated features |
| `app/Controllers/PersonalHealthDashboardController.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | AuthService, Controller, HealthDashboardService | Not independently required by listed API; do not activate unrelated features |
| `app/Controllers/PublicDiseaseApiController.php` | PHP | DEPLOY | API runtime/shared dependency | Controller, PublicApiException, PublicApiResponse, PublicApiService | Required locally; upload only if absent/different remotely |
| `app/Controllers/PublicSearchController.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | Controller, Logger, PublicSearchService | Not independently required by listed API; do not activate unrelated features |
| `app/Controllers/PublicSubstanceApiController.php` | PHP | DEPLOY | API runtime/shared dependency | Controller, PublicApiException, PublicApiResponse, PublicApiService | Required locally; upload only if absent/different remotely |
| `app/Controllers/SavedDiseaseController.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | AuthService, Controller, SavedDiseaseService, Session | Not independently required by listed API; do not activate unrelated features |
| `app/Controllers/SavedSymptomCheckController.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | AuthService, Controller, Logger, SavedSymptomCheckService, Session | Not independently required by listed API; do not activate unrelated features |
| `app/Controllers/SubstanceController.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | Controller, PublicSearchService | Not independently required by listed API; do not activate unrelated features |
| `app/Controllers/SymptomCheckerController.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | Controller, SavedSymptomCheckService, SymptomCheckerService | Not independently required by listed API; do not activate unrelated features |
| `app/Controllers/TreatmentAdminController.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | AuthService, Controller, Logger, Session, TreatmentEditorialService, TreatmentGovernanceRules | Not independently required by listed API; do not activate unrelated features |
| `app/Controllers/TreatmentReviewController.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | AuthService, Controller, Logger, Session, TreatmentEditorialService, TreatmentGovernanceRules | Not independently required by listed API; do not activate unrelated features |
| `app/Core/App.php` | PHP | DEPLOY | API runtime/shared dependency | Container, Request, Router, View | Required locally; upload only if absent/different remotely |
| `app/Core/Container.php` | PHP | DEPLOY | API runtime/shared dependency | Database | Required locally; upload only if absent/different remotely |
| `app/Core/Database.php` | PHP | DEPLOY | API runtime/shared dependency | PHP built-ins; shared helpers | Required locally; upload only if absent/different remotely |
| `app/Core/ExceptionHandler.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | PHP built-ins; shared helpers | Not independently required by listed API; do not activate unrelated features |
| `app/Core/Logger.php` | PHP | DEPLOY | API runtime/shared dependency | PHP built-ins; shared helpers | Required locally; upload only if absent/different remotely |
| `app/Core/PublicApiResponse.php` | PHP | DEPLOY | API runtime/shared dependency | Logger | Required locally; upload only if absent/different remotely |
| `app/Core/Request.php` | PHP | DEPLOY | API runtime/shared dependency | ApiAuthException | Required locally; upload only if absent/different remotely |
| `app/Core/Response.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | PHP built-ins; shared helpers | Not independently required by listed API; do not activate unrelated features |
| `app/Core/Router.php` | PHP | DEPLOY | API runtime/shared dependency | Container, PublicApiResponse, Request | Required locally; upload only if absent/different remotely |
| `app/Core/Session.php` | PHP | DEPLOY | API runtime/shared dependency | PHP built-ins; shared helpers | Required locally; upload only if absent/different remotely |
| `app/Core/View.php` | PHP | DEPLOY | API runtime/shared dependency | AuthService, Container | Required locally; upload only if absent/different remotely |
| `app/Helpers/ViewHelper.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | PHP built-ins; shared helpers | Not independently required by listed API; do not activate unrelated features |
| `app/Helpers/functions.php` | PHP | DEPLOY | Composer autoloaded env/config/url/escaping/CSRF helpers | Root .env; Config/Config.php; browser helper dependencies | Server environment must populate $_ENV or protected root .env |
| `app/Repositories/ApiAuthRepository.php` | PHP | DEPLOY | API runtime/shared dependency | BaseRepository | Required locally; upload only if absent/different remotely |
| `app/Repositories/BaseRepository.php` | PHP | DEPLOY | API runtime/shared dependency | Database | Required locally; upload only if absent/different remotely |
| `app/Repositories/BodyPartRepository.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BaseRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Repositories/CauseRepository.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BaseRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Repositories/DiscoveryRepository.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BodyPartRepository, DiseaseRepository, SymptomRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Repositories/DiseaseRepository.php` | PHP | DEPLOY | API runtime/shared dependency | BaseRepository, QuickFactsBuilder | Required locally; upload only if absent/different remotely |
| `app/Repositories/FeaturedDiseaseRepository.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | PHP built-ins; shared helpers | Not independently required by listed API; do not activate unrelated features |
| `app/Repositories/HealthAllergyRepository.php` | PHP | DEPLOY | API runtime/shared dependency | BaseRepository | Required locally; upload only if absent/different remotely |
| `app/Repositories/HealthConditionRepository.php` | PHP | DEPLOY | API runtime/shared dependency | BaseRepository | Required locally; upload only if absent/different remotely |
| `app/Repositories/HealthDocumentRepository.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BaseRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Repositories/HealthInsuranceContactRepository.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BaseRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Repositories/HealthInsuranceDocumentRepository.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BaseRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Repositories/HealthInsuranceMemberRepository.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BaseRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Repositories/HealthInsurancePolicyRepository.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BaseRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Repositories/HealthMedicineRepository.php` | PHP | DEPLOY | API runtime/shared dependency | BaseRepository | Required locally; upload only if absent/different remotely |
| `app/Repositories/HealthProcedureRepository.php` | PHP | DEPLOY | API runtime/shared dependency | BaseRepository | Required locally; upload only if absent/different remotely |
| `app/Repositories/HealthProfileInvitationRepository.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BaseRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Repositories/HealthProfileRepository.php` | PHP | DEPLOY | API runtime/shared dependency | BaseRepository | Required locally; upload only if absent/different remotely |
| `app/Repositories/HomeRepository.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BaseRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Repositories/MedicalSummaryShareRepository.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BaseRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Repositories/MedicationScheduleRepository.php` | PHP | DEPLOY | API runtime/shared dependency | BaseRepository | Required locally; upload only if absent/different remotely |
| `app/Repositories/MedicineGovernanceRepository.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | MedicineRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Repositories/MedicineRepository.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BaseRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Repositories/SavedSymptomCheckRepository.php` | PHP | DEPLOY | API runtime/shared dependency | BaseRepository | Required locally; upload only if absent/different remotely |
| `app/Repositories/SubstanceRepository.php` | PHP | DEPLOY | API runtime/shared dependency | BaseRepository | Required locally; upload only if absent/different remotely |
| `app/Repositories/SymptomRepository.php` | PHP | DEPLOY | API runtime/shared dependency | BaseRepository | Required locally; upload only if absent/different remotely |
| `app/Repositories/TreatmentEditorialPermissionRepository.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BaseRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Repositories/TreatmentGovernanceRepository.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BaseRepository, TreatmentGovernanceRules | Not independently required by listed API; do not activate unrelated features |
| `app/Repositories/TreatmentPublicationRepository.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BaseRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Repositories/TreatmentSourceRepository.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BaseRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Repositories/UserAuthIdentityRepository.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BaseRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Repositories/UserAuthTokenRepository.php` | PHP | DEPLOY | API runtime/shared dependency | BaseRepository | Required locally; upload only if absent/different remotely |
| `app/Repositories/UserRepository.php` | PHP | DEPLOY | API runtime/shared dependency | BaseRepository | Required locally; upload only if absent/different remotely |
| `app/Repositories/UserSavedDiseaseRepository.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BaseRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Repositories/UserTreatmentPreferenceRepository.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BaseRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Services/ApiAuthClock.php` | PHP | DEPLOY | API runtime/shared dependency | PHP built-ins; shared helpers | Required locally; upload only if absent/different remotely |
| `app/Services/ApiAuthException.php` | PHP | DEPLOY | API runtime/shared dependency | PHP built-ins; shared helpers | Required locally; upload only if absent/different remotely |
| `app/Services/ApiAuthGuard.php` | PHP | DEPLOY | API runtime/shared dependency | ApiAuthException, ApiAuthPrincipal, ApiAuthRateLimiter, ApiAuthService | Required locally; upload only if absent/different remotely |
| `app/Services/ApiAuthPrincipal.php` | PHP | DEPLOY | API runtime/shared dependency | PHP built-ins; shared helpers | Required locally; upload only if absent/different remotely |
| `app/Services/ApiAuthRateLimiter.php` | PHP | DEPLOY | API runtime/shared dependency | ApiAuthClock, ApiAuthException | Required locally; upload only if absent/different remotely |
| `app/Services/ApiAuthService.php` | PHP | DEPLOY | API runtime/shared dependency | ApiAuthClock, ApiAuthException, ApiAuthPrincipal, ApiAuthRateLimiter, ApiAuthRepository, AuthService, Logger | Required locally; upload only if absent/different remotely |
| `app/Services/ApiHealthConditionService.php` | PHP | DEPLOY | API runtime/shared dependency | ApiAuthException, ApiAuthPrincipal, ApiHealthProfileService, HealthConditionRepository | Required locally; upload only if absent/different remotely |
| `app/Services/ApiHealthMedicineService.php` | PHP | DEPLOY | API runtime/shared dependency | ApiAuthException, ApiAuthPrincipal, ApiHealthProfileService, HealthMedicineRepository | Required locally; upload only if absent/different remotely |
| `app/Services/ApiHealthProfileService.php` | PHP | DEPLOY | API runtime/shared dependency | ApiAuthException, ApiAuthPrincipal, HealthProfileRepository | Required locally; upload only if absent/different remotely |
| `app/Services/ApiIdentityService.php` | PHP | DEPLOY | API runtime/shared dependency | ApiAuthException, ApiAuthPrincipal, UserRepository | Required locally; upload only if absent/different remotely |
| `app/Services/ApiMedicalSummaryService.php` | PHP | DEPLOY | API runtime/shared dependency | ApiAuthPrincipal, MedicalSummaryService | Required locally; upload only if absent/different remotely |
| `app/Services/ApiMedicationScheduleService.php` | PHP | DEPLOY | API runtime/shared dependency | ApiAuthException, ApiAuthPrincipal, ApiHealthProfileService, MedicationScheduleRepository, MedicationScheduleService | Required locally; upload only if absent/different remotely |
| `app/Services/ApiSavedSymptomCheckService.php` | PHP | DEPLOY | API runtime/shared dependency | ApiAuthException, ApiAuthPrincipal, SavedSymptomCheckRepository, SavedSymptomCheckService | Required locally; upload only if absent/different remotely |
| `app/Services/AuthService.php` | PHP | DEPLOY | API runtime/shared dependency | Database, Session, UserAuthTokenService, UserRepository | Required locally; upload only if absent/different remotely |
| `app/Services/BodyPartService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BodyPartRepository, DiseaseRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Services/DiscoveryDashboardBuilder.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | DiscoveryRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Services/DiscoveryService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | DiscoveryDashboardBuilder, DiscoveryRepository, DiseaseService | Not independently required by listed API; do not activate unrelated features |
| `app/Services/Disease/DiseaseAdminService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | DiseaseRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Services/Disease/QuickFactsBuilder.php` | PHP | DEPLOY | API runtime/shared dependency | PHP built-ins; shared helpers | Retain whole-class dependency compatibility; API saved detail does not rerun matching |
| `app/Services/DiseaseReferenceService.php` | PHP | DEPLOY | API runtime/shared dependency | PHP built-ins; shared helpers | Required locally; upload only if absent/different remotely |
| `app/Services/DiseaseService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BodyPartRepository, DiseaseComparisonBuilder, DiseaseReferenceService, DiseaseRepository, RelatedDiseaseMatcher, Session, TreatmentPreferenceService, TreatmentPublicationService | Not independently required by listed API; do not activate unrelated features |
| `app/Services/EmailVerificationService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | MailService, UserAuthTokenService, UserRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Services/GoogleAuthService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | PHP built-ins; shared helpers | Not independently required by listed API; do not activate unrelated features |
| `app/Services/GoogleOAuthStateService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | Session | Not independently required by listed API; do not activate unrelated features |
| `app/Services/HealthAllergyService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | HealthAllergyRepository, HealthProfileService | Not independently required by listed API; do not activate unrelated features |
| `app/Services/HealthConditionService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | HealthConditionRepository, HealthProfileService | Not independently required by listed API; do not activate unrelated features |
| `app/Services/HealthDashboardService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | AuthService, HealthDocumentService, HealthProfileService, MedicalSummaryService, SavedDiseaseService, View | Not independently required by listed API; do not activate unrelated features |
| `app/Services/HealthDocumentService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | AuthService, HealthDocumentRepository, HealthDocumentStorageService, HealthProfileService | Not independently required by listed API; do not activate unrelated features |
| `app/Services/HealthDocumentStorageService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | PHP built-ins; shared helpers | Not independently required by listed API; do not activate unrelated features |
| `app/Services/HealthInsuranceService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | AuthService, HealthDocumentRepository, HealthInsuranceContactRepository, HealthInsuranceDocumentRepository, HealthInsuranceMemberRepository, HealthInsurancePolicyRepository, HealthProfileRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Services/HealthMedicineService.php` | PHP | DEPLOY | API runtime/shared dependency | HealthMedicineRepository, HealthProfileService | Required locally; upload only if absent/different remotely |
| `app/Services/HealthProcedureService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | HealthProcedureRepository, HealthProfileService | Not independently required by listed API; do not activate unrelated features |
| `app/Services/HealthProfileInvitationService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | AuthService, HealthProfileInvitationRepository, HealthProfileRepository, HealthProfileService, UserRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Services/HealthProfileService.php` | PHP | DEPLOY | API runtime/shared dependency | AuthService, HealthProfileRepository | Required locally; upload only if absent/different remotely |
| `app/Services/HealthTimelineService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | HealthAllergyRepository, HealthConditionRepository, HealthDocumentRepository, HealthMedicineRepository, HealthProcedureRepository, HealthProfileService | Not independently required by listed API; do not activate unrelated features |
| `app/Services/HomeService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | BodyPartRepository, CauseRepository, DiseaseRepository, HomeRepository, Session, SymptomRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Services/MailService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | PHP built-ins; shared helpers | Not independently required by listed API; do not activate unrelated features |
| `app/Services/MedicalSummaryService.php` | PHP | DEPLOY | API runtime/shared dependency | ApiAuthPrincipal, ApiHealthProfileService, HealthAllergyRepository, HealthConditionRepository, HealthMedicineRepository, HealthProcedureRepository, HealthProfileService | Required locally; upload only if absent/different remotely |
| `app/Services/MedicalSummaryShareService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | AuthService, HealthProfileService, MedicalSummaryService, MedicalSummaryShareRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Services/MedicationScheduleService.php` | PHP | DEPLOY | API runtime/shared dependency | ApiAuthException, ApiAuthPrincipal, ApiHealthProfileService, AuthService, Database, HealthMedicineService, HealthProfileService, MedicationScheduleRepository | Required locally; upload only if absent/different remotely |
| `app/Services/MedicineAuthorizationService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | MedicineEligibilityService, MedicineGovernancePolicy, MedicineGovernanceRepository, MedicinePersistenceException | Not independently required by listed API; do not activate unrelated features |
| `app/Services/MedicineCanonicalizer.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | MedicinePersistenceException | Not independently required by listed API; do not activate unrelated features |
| `app/Services/MedicineEligibilityService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | MedicineCanonicalizer, MedicineGovernancePolicy, MedicineGovernanceRepository, MedicinePersistenceException | Not independently required by listed API; do not activate unrelated features |
| `app/Services/MedicineGovernancePolicy.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | MedicineGovernanceRepository, MedicinePersistenceException | Not independently required by listed API; do not activate unrelated features |
| `app/Services/MedicineGovernanceService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | AuthService, MedicineAuthorizationService, MedicineCanonicalizer, MedicineEligibilityService, MedicineGovernancePolicy, MedicineGovernanceRepository, MedicinePersistenceException, MedicineProvisionalIdentity, Session | Not independently required by listed API; do not activate unrelated features |
| `app/Services/MedicineIdentityService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | AuthService, HealthProfileRepository, MedicineAuthorizationService, MedicineCanonicalizer, MedicinePersistenceException, MedicineRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Services/MedicinePersistenceException.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | PHP built-ins; shared helpers | Not independently required by listed API; do not activate unrelated features |
| `app/Services/MedicineProvisionalIdentity.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | MedicineCanonicalizer, MedicinePersistenceException | Not independently required by listed API; do not activate unrelated features |
| `app/Services/PublicApiException.php` | PHP | DEPLOY | API runtime/shared dependency | PHP built-ins; shared helpers | Required locally; upload only if absent/different remotely |
| `app/Services/PublicApiService.php` | PHP | DEPLOY | API runtime/shared dependency | DiseaseReferenceService, DiseaseRepository, PublicApiException, PublicSearchService, SubstanceRepository | Required locally; upload only if absent/different remotely |
| `app/Services/PublicSearchService.php` | PHP | DEPLOY | API runtime/shared dependency | DiseaseRepository, SubstanceRepository | Required locally; upload only if absent/different remotely |
| `app/Services/SavedDiseaseService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | Session, UserSavedDiseaseRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Services/SavedSymptomCheckService.php` | PHP | DEPLOY | API runtime/shared dependency | ApiAuthPrincipal, AuthService, Logger, SavedSymptomCheckRepository, Session, SymptomCheckerService | Required locally; upload only if absent/different remotely |
| `app/Services/SubstanceService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | SubstanceRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Services/SymptomCheckerService.php` | PHP | DEPLOY | API runtime/shared dependency | DiseaseRepository, SymptomMatcher, SymptomRepository | Required locally; upload only if absent/different remotely |
| `app/Services/TreatmentEditorialAuthorizationService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | AuthService, TreatmentEditorialPermissionRepository, TreatmentGovernancePolicy, TreatmentGovernanceRules | Not independently required by listed API; do not activate unrelated features |
| `app/Services/TreatmentEditorialService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | TreatmentEditorialAuthorizationService, TreatmentEditorialPermissionRepository, TreatmentGovernanceRepository, TreatmentGovernanceRules, TreatmentGovernanceService, TreatmentSourceRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Services/TreatmentGovernancePolicy.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | PHP built-ins; shared helpers | Not independently required by listed API; do not activate unrelated features |
| `app/Services/TreatmentGovernanceRules.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | Database | Not independently required by listed API; do not activate unrelated features |
| `app/Services/TreatmentGovernanceService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | Request, TreatmentEditorialAuthorizationService, TreatmentEditorialPermissionRepository, TreatmentGovernancePolicy, TreatmentGovernanceRepository, TreatmentGovernanceRules, TreatmentGovernanceSnapshot, TreatmentSourceRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Services/TreatmentGovernanceSnapshot.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | TreatmentGovernanceRules | Not independently required by listed API; do not activate unrelated features |
| `app/Services/TreatmentPreferenceService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | AuthService, UserTreatmentPreferenceRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Services/TreatmentPublicationService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | Database, TreatmentGovernancePolicy, TreatmentGovernanceRules, TreatmentGovernanceSnapshot, TreatmentPublicationRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Services/UserAuthIdentityService.php` | PHP | CONDITIONAL | Existing browser/internal runtime; compare matched feature set | UserAuthIdentityRepository, UserRepository | Not independently required by listed API; do not activate unrelated features |
| `app/Services/UserAuthTokenService.php` | PHP | DEPLOY | API runtime/shared dependency | UserAuthTokenRepository | Required locally; upload only if absent/different remotely |
| `bootstrap/app.php` | PHP | DEPLOY | API classification, safe JSON failures, session separation | Config/Config.php; Request; PublicApiResponse; Logger; App | Preserve full bootstrap contract and existing browser headers |
| `public/.htaccess` | Apache rules | CONDITIONAL | Front-controller routing and scoped Bearer forwarding | Actual document root; rewrite-compatible hosting | Merge reviewed requirements into remote file; no blind replacement |
| `public/index.php` | PHP | DEPLOY | HTTP entry point | ../vendor/autoload.php; ../bootstrap/app.php | Relative layout must be preserved or separately reviewed |
| `resources/disease-references.php` | PHP | DEPLOY | Public Disease Detail reference metadata | DiseaseReferenceService | Runtime resource; references depend on matching remote disease IDs |
| `routes/web.php` | PHP | DEPLOY | All 21 API routes plus existing website route registrations | Core Router; three API controllers; conditional browser controllers | Deploy coherent route file; audit non-API registrations against remote features |

### 19.2 Exact browser view/asset candidates (117 files)

No new view, stylesheet, script or font is required solely to produce the listed JSON endpoints. These explicit candidates support the existing current website and API-provided public links. They are conditional comparisons, not an instruction to overwrite every live page.

| PATH | TYPE | DEPLOY? | REASON | DEPENDENCIES | NOTES |
|---|---|---|---|---|---|
| `app/Views/account/health-allergies.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-allergy-create.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-allergy-edit.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-condition-create.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-condition-edit.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-conditions.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-dashboard.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-document-create.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-document-edit.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-document-error.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-documents.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-insurance-contact-create.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-insurance-contact-edit.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-insurance-create.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-insurance-details.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-insurance-document-create.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-insurance-document-edit.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-insurance-edit.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-insurance-member-create.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-insurance-member-edit.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-insurance.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-medicine-create.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-medicine-edit.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-medicines.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-procedure-create.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-procedure-edit.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-procedures.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-profile-invitation-error.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-profile-invitation.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-record-access.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-record-create.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-record-edit.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-record-invitation-created.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-record.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-records.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/health-timeline.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/index.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/medical-summary-share-create.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/medical-summary-share-created.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/medical-summary-shares.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/medical-summary.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/medication-due-today.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/medication-history.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/medication-schedule-error.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/medication-schedule-nav.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/medication-schedule.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/mobile-number.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/saved-diseases.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/saved-symptom-check-error.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/saved-symptom-check.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/saved-symptom-checks.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/account/treatment-preferences.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/admin/disease-edit.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/admin/medicine/owner-bootstrap-error.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/admin/medicine/owner-bootstrap.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/admin/treatment-review/_content.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/admin/treatment-review/error.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/admin/treatment-review/index.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/admin/treatment-review/show.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/admin/treatments/_fields.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/admin/treatments/_history.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/admin/treatments/_references.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/admin/treatments/create.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/admin/treatments/edit.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/admin/treatments/error.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/admin/treatments/index.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/admin/treatments/item-create.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/admin/treatments/operation.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/admin/treatments/operations.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/admin/treatments/source-create.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/admin/treatments/source.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/admin/treatments/sources.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/auth/forgot-password.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/auth/login.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/auth/register.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/auth/resend-verification.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/auth/reset-password.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/auth/verification-failed.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/auth/verification-sent.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/comparison/index.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/comparison/result.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/components/discovery/widget.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/discovery/index.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/disease/index.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/disease/show.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/home/index.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/layouts/app.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/layouts/footer.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/layouts/header.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/layouts/navbar.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/layouts/scripts.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/search/index.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/shared/medical-summary-error.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/shared/medical-summary.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/substances/index.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `app/Views/symptom-checker/index.php` | PHP view | CONDITIONAL | Existing browser rendering; no API JSON template | Matching browser controller/service; helpers; layout | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `public/assets/css/app.css` | CSS | CONDITIONAL | Existing website asset; no native API dependency | Matching layouts/page loader; relative asset/font paths | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `public/assets/css/bootstrap-icons.css` | CSS | CONDITIONAL | Existing website asset; no native API dependency | Matching layouts/page loader; relative asset/font paths | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `public/assets/css/bootstrap.min.css` | CSS | CONDITIONAL | Existing website asset; no native API dependency | Matching layouts/page loader; relative asset/font paths | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `public/assets/css/components.css` | CSS | CONDITIONAL | Existing website asset; no native API dependency | Matching layouts/page loader; relative asset/font paths | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `public/assets/css/discovery.css` | CSS | CONDITIONAL | Existing website asset; no native API dependency | Matching layouts/page loader; relative asset/font paths | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `public/assets/css/disease.css` | CSS | CONDITIONAL | Existing website asset; no native API dependency | Matching layouts/page loader; relative asset/font paths | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `public/assets/css/fonts/bootstrap-icons.woff` | Font | CONDITIONAL | Existing website asset; no native API dependency | Matching layouts/page loader; relative asset/font paths | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `public/assets/css/fonts/bootstrap-icons.woff2` | Font | CONDITIONAL | Existing website asset; no native API dependency | Matching layouts/page loader; relative asset/font paths | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `public/assets/css/medical-summary.css` | CSS | CONDITIONAL | Existing website asset; no native API dependency | Matching layouts/page loader; relative asset/font paths | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `public/assets/css/symptom-checker.css` | CSS | CONDITIONAL | Existing website asset; no native API dependency | Matching layouts/page loader; relative asset/font paths | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `public/assets/css/theme.css` | CSS | CONDITIONAL | Existing website asset; no native API dependency | Matching layouts/page loader; relative asset/font paths | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `public/assets/js/app.js` | JavaScript | CONDITIONAL | Existing website asset; no native API dependency | Matching layouts/page loader; relative asset/font paths | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `public/assets/js/bootstrap.bundle.min.js` | JavaScript | CONDITIONAL | Existing website asset; no native API dependency | Matching layouts/page loader; relative asset/font paths | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `public/assets/js/compare.js` | JavaScript | CONDITIONAL | Existing website asset; no native API dependency | Matching layouts/page loader; relative asset/font paths | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `public/assets/js/disease-catalog.js` | JavaScript | CONDITIONAL | Existing website asset; no native API dependency | Matching layouts/page loader; relative asset/font paths | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `public/assets/js/disease.js` | JavaScript | CONDITIONAL | Existing website asset; no native API dependency | Matching layouts/page loader; relative asset/font paths | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `public/assets/js/health-medicine.js` | JavaScript | CONDITIONAL | Existing website asset; no native API dependency | Matching layouts/page loader; relative asset/font paths | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `public/assets/js/home.js` | JavaScript | CONDITIONAL | Existing website asset; no native API dependency | Matching layouts/page loader; relative asset/font paths | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `public/assets/js/kobi-config.js` | JavaScript | CONDITIONAL | Existing website asset; no native API dependency | Matching layouts/page loader; relative asset/font paths | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `public/assets/js/substance-search.js` | JavaScript | CONDITIONAL | Existing website asset; no native API dependency | Matching layouts/page loader; relative asset/font paths | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |
| `public/assets/js/symptom-checker.js` | JavaScript | CONDITIONAL | Existing website asset; no native API dependency | Matching layouts/page loader; relative asset/font paths | Compare remote; synchronize only with reviewed browser feature set; preserve current behavior |

### 19.3 Non-source runtime prerequisites, exclusions and package boundary

Section 11 supplies the same PATH/TYPE/DEPLOY/reason/dependency/notes classification for Composer, configuration, all nine SQL artifacts, root rewrite candidates and the optional cleanup script. Section 10 classifies all non-runtime repository areas. Together these cover every current top-level area: app, bootstrap, config, database, docs, mobile, public, resources, routes, scripts, storage, tests, vendor, .git, .vs and root metadata.

A future package should contain only remote-confirmed missing/different DEPLOY source files, the separately approved matched web-runtime changes, reviewed configuration/rule material, and compatible production Composer runtime if required. Include deployment hashes and destination mapping in an operator-only release record outside public reach. Migration artifacts belong to a separate operator-only review/application set, not an automatically executed public upload.

**Package creation deferred:** no ZIP, staging copy, dependency installation or upload list derived from unverified remote assumptions was produced. Do not omit an unchanged shared dependency just because it does not appear in a changed-files list. Do not overwrite a newer or server-customized file without review.

## 20. Recommended Deployment Sequence

This is a future procedure, not executed or newly authorized by this audit.

1. Establish actual Hostinger application/public layout, access method and operator maintenance/rollback window.
2. Back up current application files, rewrite rules, configuration and user-file storage through the existing safe procedure; preserve permissions and protect secrets.
3. Back up the existing database with schema/data/indexes/FKs/CHECKs/generated columns/triggers and relevant object context; verify recovery as section 21 requires.
4. Compare the deployed files/schema to this manifest and finalize a reviewed exact delta. Resolve configuration filename case, root/mount, Composer compatibility, prerequisite columns/objects and disease-reference IDs before upload.
5. Upload only approved runtime deltas to a non-public staged release or within a controlled maintenance window. Do not expose partially uploaded routes/controllers. Preserve remote .env/uploads/storage and server-only configuration.
6. Apply reviewed Hostinger configuration/rule merges, independently provision limiter configuration/private directories, and verify autoload/platform/path consistency without enabling an incomplete release.
7. If required, apply only explicitly approved incremental migrations after recovery rehearsal. Preserve existing data and SQL mode; stop on a conflicting object or incompatible statement. Do not full-import structure.sql.
8. Promote the coherent release only after files/config/schema are ready; reset server opcode cache using the normal operator mechanism if necessary. No server-wide reset or unrelated deployment is implied.
9. Verify public API JSON at the actual mount and negative-method envelopes.
10. Under separate credential-lifecycle authorization, verify genuine synthetic login/refresh/logout/replay/revocation, header forwarding and rate-limiter behavior.
11. Verify authorized private reads, inaccessible-profile denials, saved snapshots and server-controlled checkoff using only approved synthetic data. Revoke test credentials through normal lifecycle cleanup.
12. Verify existing public website and browser account/health/medication flows, current Diagnosis/Treatments, searches and CSRF/session behavior. Compare preserved data/schema/configuration against backup evidence.
13. Verify HTTPS chain/hostname, HTTPS signaling, no-store and no credential leakage; publish only after all release gates pass.
14. Only then prepare/test the Android APK against the confirmed HTTPS API base. No APK build or installation belongs to this task.

If shared hosting lacks atomic promotion, keep affected admission under a controlled operator maintenance procedure through upload/config/schema checks. Do not make half-compatible code publicly active merely to follow the numbered upload order.

## 21. Backup Strategy

Before any later change, record the actual remote paths/version and intended delta. Keep a protected recoverable file backup with manifest hashes, ownership/permissions, hidden rewrite files and configuration. Capture current user-upload/private-document storage without publishing it.

Use a consistent database export procedure appropriate to the actual engine, including all schema objects and triggers, their definitions/context, generated columns and data. Record database defaults/SQL mode and non-sensitive integrity counts. An export file or “download complete” notification alone is not recovery verification.

Verify backup readability, hashes, object completeness and a documented restore procedure. Rehearse restoration into an authorized disposable compatible environment and compare normalized SHOW CREATE definitions, indexes, FKs/CHECKs/generated expressions, trigger bodies/context and data integrity. Do not rehearse by overwriting the live Hostinger database. If a safe restore rehearsal or required object export cannot be established, stop before migration/deployment.

Backups must remain outside the web root with restricted access and an identified operator able to restore them. No backup, export or restore was created in this audit.

## 22. Rollback Strategy

| Scope | Future rollback procedure |
|---|---|
| Application files | Hold API admission/issuance during rollback; restore the recorded previous coherent runtime set or release pointer and verify its hashes; remove only newly introduced files recorded in the release manifest, never server-only files or user uploads |
| .htaccess | Restore the exact backed-up active rules in their original directory; preserve unrelated operator changes made after backup via review; verify existing website and route behavior |
| Configuration/vendor | Restore backed-up approved values and matching dependency runtime/autoload; keep secrets private; do not overwrite credentials from local examples |
| Additive tables/trigger | Prefer retaining compatible inert additive objects and their data while rolling code back. Do not drop tables or delete auth/checkoff/saved history as routine rollback |
| Migration failure/partial DDL | Stop; inspect exact committed objects and preserve evidence. DDL may auto-commit. Follow an individually reviewed recovery plan; no blanket rerun/drop/reset |
| Database restoration | Only separately authorized verified recovery, with write quiescence, a post-incident backup, consideration of legitimate intervening writes and explicit recovery scope; never silently restore an old full dump over new user data |

If an approved emergency plan truly requires removal of new API tables, it must first account for the users revocation trigger that references them, credential revocation/history retention and all dependencies. No destructive rollback SQL is generated here. Never remove referenced tables while leaving a live user-update trigger pointing at them. A file rollback is not permission to erase production data.

## 23. Post-Deployment API Verification

Deferred until a separately authorized deployment. Use certificate-verified HTTPS with peer verification enabled, hostname verification enabled and verification result 0; do not bypass certificate errors or follow credential-bearing redirects to unknown origins.

| Stage | Required expected behavior |
|---|---|
| Public list | GET /api/v1/diseases?page=1&page_size=1 → 200 JSON V1 envelope, stable ordering/pagination |
| Public search | Disease search q=Migraine and substance q=Paracetamol/Para → bounded projected results; existing empty-query validation |
| Public detail | /diseases/migraine → stored plain-text sections/reference projection; missing slug → safe 404; verify actual remote data rather than assume identical content |
| Routing | Known unsupported methods → 405/Allow and safe JSON; unknown API → JSON 404; HTML 404 is not a successful API response |
| Auth transport | JSON POST/HTTPS; Authorization reaches unchanged controller server variables; no password/token output or stored raw credentials |
| Credential lifecycle | Approved synthetic password login, access, refresh rotation, old-access rejection, single logout/device isolation, replay revocation and logout-all with current password |
| Identity/profile | /me exact four fields; list/detail only currently accessible profiles; malformed IDs rejected; no browser session/cookie identity fallback |
| Private resources | Both condition/medication routes, aggregate summary, schedule configuration/Due Today, and saved-check list/detail; exact projections and child/profile or user ownership |
| Checkoff | Separately approved synthetic current slot, owner/editor permission, state taken/skipped, current server occurrence; viewer/foreign/ineligible attempts denied; no manual row edits |
| Response boundary | JSON UTF-8, no-store/nosniff, no Set-Cookie/CORS grant, generic internal errors, no cached private payload |
| Limiter | Key configured, private locking storage, expected bounded 429/Retry-After and safe failure; avoid saturating a genuine user's budget |
| Integrity/cleanup | Compare approved changes only; revoke all new test families normally; preserve revoked digest history; remove approved synthetic artifacts only by agreed normal UI/lifecycle procedure |
| Website | Homepage/catalog/discovery search, disease section navigation/Diagnosis/Treatments, substance page, login/logout/reset/Google entry, account/profile/medications/summary as applicable |

The established local Step 50B 458-check result remains historical acceptance evidence for that environment. Do not call it a Hostinger result or automatically rerun its ten-minute expiry/one-off scripts there. A future scoped remote test plan must be authorized and suited to the actual baseline. No API credentials, clinical fixtures, live checks, login attempts or endpoint probes were used in this audit.

## 24. Mobile APK Preparation

After backend deployment acceptance, build the existing Flutter client through its documented tooling. Supply approved KOBI_ENV and the verified API_BASE_URL using dart-define; production is the default environment without a default host. The proposed root base remains https://kobi.monchez.in/api/v1 pending mount verification.

Keep signing material separate; choose the intended debug/controlled-acceptance or signed release procedure explicitly. Confirm the physical Android device's ordinary network and public certificate trust; no local CA installation, TLS override, phone localhost routing or Windows acceptance server is needed for the selected Hostinger strategy.

Test only the approved synthetic account/profile, including secure credential storage, refresh/logout, public knowledge/read screens and Due Today checkoff. Revoke test credential families and use the agreed cleanup path; account/profile deletion is not assumed available. Backend deployment does not itself certify physical-device acceptance or store release readiness. No build, install or Flutter edit occurred here.

## 25. Risks

1. **Known case gate:** lowercase config.php versus uppercase Config.php references can prevent bootstrap on Linux before safe API error handling starts. Resolve the reviewed destination/configuration before promotion.
2. **Unknown remote baseline:** replacing routes/bootstrap/shared services alone can break older browser controllers/views or missing constructor dependencies. Use the explicit manifest and matched release comparison.
3. **Schema drift:** missing users.mobile_number/password_set_at/verification or prerequisite tables may break even basic identity/login. Partial existing objects require manual migration review.
4. **Header forwarding:** TLS success and a valid token do not prove PHP receives the expected Authorization variable; historical local forwarding correction must be verified on Hostinger's handler.
5. **Misapplied environment settings:** localhost paths/URLs, local owner/bootstrap IDs, default mail logging or invalid limiter storage/key can cause outages or unintended internal authority exposure. Do not blindly copy configuration.
6. **Destructive dump/dependency copying:** full structure.sql import, local DB transfer or indiscriminate vendor/mobile upload would exceed this task and risk live data.
7. **Reference/dataset mismatch:** numeric source mappings and snapshot payload versions require compatible remote data, without unauthorized content repair.
8. **Hosting limitations:** CHECK enforcement, trigger privileges, generated-column syntax, private writable storage/locking and CDN behavior remain unverified.
9. **Partial uploads/rollback:** shared hosting may not support atomic switching; maintain a coherent release/maintenance plan and recoverable backup.
10. **Test lifecycle effects:** future auth/checkoff tests write credential/health history. They need specific synthetic-fixture and cleanup authorization; revoked history is not debris to delete manually.

No speculative redesign, governance rollout, additional endpoint, password-policy change, certificate replacement or limiter redesign is proposed.

## 26. Final Deployment Readiness

**READY WITH HOSTINGER VERIFICATION REQUIRED**

The local source is sufficiently inventoried to plan the deployment. This is not upload authorization or a claim that Hostinger is ready for immediate activation. Required gates: actual remote file/schema/config inventory; filename-case resolution; compatible PHP/Composer; reviewed routing/header handling; verified backup/recovery; explicit incremental migration review if needed; coherent release and validation plan.

Exact required remote changes remain conditional until comparison. No additional feature implementation is needed merely to explain the current undeployed API 404.

## 27. Validation Performed

- Read AGENTS.md and the attached audit specification.
- Inspected current 21 API registrations, three controllers, constructor resolution, entry/bootstrap/config/helpers/response/router and repository/service dependency graph. Matched runtime reference-resource loading.
- Inspected structure.sql object inventory and relevant table definitions, all focused auth/saved/schedule artifacts, mobile-number prerequisite, public/authenticated API contracts, Step 50B implementation plus later deployment report, and Step 51B/52B/53B/54B/55B/56B/57B/59A implementation/schema evidence.
- Read local root/public/alternate rewrite rules; no remote .htaccess copy was available. Confirmed the case mismatch from actual directory entries and both require statements.
- Inspected composer.json/lock extension declarations, environment-loader semantics, limiter storage/key requirement, Logger fixed storage path and mobile build/config documentation. Checked only secret presence, never values.
- Counted local paths with ignore-aware and raw inventories; no Git commands or repository-index baseline were used.
- PHP syntax check: all **59 API compatibility PHP files passed** using local PHP CLI lint only; no application boot or database connection.
- Final path/SHA-256 comparison: all **694 previously inventoried files unchanged**, no removals, only this report added. Inventory excludes .git/vendor/storage and ignored artifacts; it is not a statement that externally running processes cannot change ignored caches/logs.
- Report structure and manifest path/count checks completed. No remote connectivity, browser, database, live authentication, fixture, APK, Composer install or full regression suite was run; those would not be read-only evidence for this local planning task.

Syntax/static inspection is not Hostinger runtime acceptance. Historical PASS results in earlier reports were not rerun or reclassified.

## 28. Files Created

Created only: docs/task-hostinger-mobile-api-deployment-manifest.md.

Existing files modified: **none**. No source/configuration/schema/Flutter changes. No package, copy, SQL migration or test file created.

## 29. Confirmation of No Upload

No upload, deployment ZIP, staging copy, SSH/FTP/control-panel/browser connection or HTTP request to Hostinger occurred.

## 30. Confirmation of No Database Mutation

No database connection, SQL execution, schema/data write, migration, fixture or production-record change occurred. No local database was copied or exported.

## 31. Confirmation of No Server Changes

No Hostinger/XAMPP/Apache/PHP/environment/limiter/DNS/TLS/certificate/port/service configuration change, restart, APK build or installation occurred. Existing Treatment, Medicine, Authentication and clinical content behavior were not changed.

## 32. Confirmation of No Git Operations

No Git operation was performed, including status/diff/log/index inspection, add, commit, push, reset, checkout, merge or rebase. Final scope was checked with filesystem paths and SHA-256 hashes only.

**Stop after this manifest. No upload, migration or mobile acceptance execution is started.**

