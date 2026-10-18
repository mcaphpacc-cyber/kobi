# KOBI Core v0.1.0 — Step 42A
# Personal Health Dashboard — Product & Architecture Readiness

Planning date: 13 October 2026 (Asia/Calcutta).
Read-only inspection; the only deliverable is this planning report.

## A. Executive Summary

**READY FOR STEP 42B — a bounded six-card V1.**

Existing schema and authorized services provide enough information for a useful dashboard. No schema change, new clinical metric, duplicate health-management workflow or dashboard repository is needed.

Recommend: Selected Health Profile; Health Records (conditions/allergies/procedures counts); Personal Medications (recorded current count); Documents (count); Medical Summary (existing live summary link); Saved Diseases (account-level count and navigation).

No major implementation blocker was identified. Two product facts must be respected: there is no persisted active-profile selection, and medical summaries are generated from current records rather than stored as an independently existing summary. Profile selection and honest labels are specified below.

Scope of evidence: current application code, routes, views, service/repository queries, database/structure.sql and existing test inspection. No live personal-health records were read and no authenticated browser audit was performed. Actual account population and live query timings were not measured. This is implementation readiness, not a fresh runtime acceptance pass.

## B. Existing Personal Health Inventory

Paths below refer to existing files under app/Controllers, app/Services and app/Repositories respectively. Repository methods use the established BaseRepository abstraction.

| Feature | Existing Read Path | Data Source | Authorization | Dashboard Reusable? |
|---|---|---|---|---|
| Account/Profile | AccountController::index → accountViewData → AuthService::user → UserRepository::findById; linked identity methods in UserAuthIdentityService | users; user_auth_identities for connected accounts | Controller check; user() verifies existing active account. AuthService::id reads auth_user_id through Session. | YES for identity/navigation; do not load password/identity settings for health cards. Existing account page already contains profile/settings forms. |
| Health profiles / family records | AccountController::healthRecords → HealthProfileService::getAccessibleProfiles → HealthProfileRepository::findAccessibleByUser; healthRecord → getProfile → findAccessible | health_profiles, health_profile_members | Current user ID; active profile AND active membership required. Reads allow owner/editor/viewer. | YES: profile chooser and selected-profile identity; no new query. |
| Conditions | AccountController::healthConditions → HealthConditionService::getConditions → HealthConditionRepository::findByProfile; also MedicalSummaryService::getSummary | health_conditions | getProfile/findAccessible before profile-scoped query | YES: summary already partitions active/chronic from historical/resolved. Existing standalone query needs no change. |
| Allergies | AccountController::healthAllergies → HealthAllergyService::getAllergies → HealthAllergyRepository::findByProfileId; also getSummary | health_allergies | Accessible profile checked before read | YES: recorded-entry count; no new query. Absence of entries is not “no known allergies”. |
| Procedures | AccountController::healthProcedures → HealthProcedureService::getProcedures → HealthProcedureRepository::findByProfileId; also getSummary | health_procedures | Accessible profile checked before read | YES: count of recorded procedures/surgeries/hospitalizations; no new query. |
| Personal medications | AccountController::healthMedicines → HealthMedicineService::getMedicines → HealthMedicineRepository::findByProfileId; also getSummary | health_medicines | Read path calls HealthProfileService::getProfile. The dashboard must use this authorized read or the authorized summary. | YES: getSummary already filters status=current; no new query or governed Medicine linkage required. |
| Documents | AccountController::healthDocuments / healthRecord → HealthDocumentService::getDocuments / getDocumentCount → HealthDocumentRepository::findByProfileId / countByProfileId | health_documents; files handled through HealthDocumentStorageService | AuthService check/id plus getProfile; file access rechecks profile access | YES: existing COUNT query is sufficient. Full document list also works but includes storage and private metadata that should not enter compact cards. |
| Medical / health summary | AccountController::medicalSummary → MedicalSummaryService::getSummary → HealthProfileService + four domain repositories | Current profile, conditions, medicines, allergies, procedures | getProfile establishes active user membership before any domain data read | YES: reuse one authorized aggregate for four count groups and profile. No health_summary table or persisted summary-existence flag. |
| Saved diseases | AccountController::savedDiseases; SavedDiseaseController save/remove → SavedDiseaseService::getSavedDiseases → UserSavedDiseaseRepository::findSavedDiseasesByUser | user_saved_diseases joined to diseases | Existing service reads Session auth_user_id; same account key used by AuthService. Query is user-scoped, independent of selected health profile. | YES: one list query; count in memory. These are saved knowledge items, not recorded diagnoses. |
| Recent disease history | DiseaseController::show → DiseaseService::rememberDisease; DiseaseController::index / DiscoveryService → getRecentlyViewed → DiseaseRepository::findByIds | Session recent_diseases IDs; public diseases table | Current browser session, not a user/profile-keyed medical history | DEFER: reuse exists, but should be labelled session browsing activity. No persisted personal activity table. |
| Disease comparison | ComparisonController::index/result → DiseaseService::getKnowledgeBySlug/getComparison → DiseaseRepository and DiseaseComparisonBuilder | Public disease knowledge; supplied slugs; governed comparison treatment projection | Public education route, not profile data or a personal comparison history | Navigation reusable at /compare; DEFER standalone dashboard card; do not associate results with recorded health conditions. |
| Medical-summary sharing | AccountController::medicalSummaryShares → MedicalSummaryShareService::getShares → getProfile + MedicalSummaryShareRepository::findByProfileId | medical_summary_shares: snapshots, expiry/revocation; access logs in medical_summary_share_access_logs | Management is owner-only. Public getPublicShare validates token hash/status/expiry; it writes an access log after successful validation. | DEFER card. Owner management link can remain inside the existing summary flow. Never call public share reads or create shares during dashboard assembly. |
| Health timeline | AccountController::healthTimeline → HealthTimelineService::getTimeline → getProfile + five repositories | Conditions, medicines, allergies, procedures, documents; events built in memory | Accessible profile checked first | DEFER card; existing link from health record can remain. Loading timeline duplicates most summary reads and materializes full events. |
| Health insurance | HealthInsuranceController::index → HealthInsuranceService::getPolicies → HealthProfileRepository::findAccessible + HealthInsurancePolicyRepository::findByProfileId | health_insurance_policies, health_insurance_members, health_insurance_contacts, health_insurance_documents; linked health_documents | Current AuthService ID and accessible profile; policy/detail ownership checked against selected profile. Writes enforce editable roles. | DEFER card; link from existing health record. No reason to fetch financial/policy details for V1. |
| Profile access / invitations / family sharing | AccountController::healthRecordAccess → HealthProfileInvitationService::getAccessManagement → getProfile, member/invitation repositories | health_profile_members, health_profile_invitations | Owner-only management; viewer/editor membership does not imply access-administration permission | DEFER card; retain owner-only access management on existing health record. |
| Treatment display preferences | AccountController and TreatmentPreferenceService → UserTreatmentPreferenceRepository | user_treatment_preferences and treatment_systems | Account scoped; preference order is not health data or publication authority | EXCLUDE health card; existing Account settings remain. |

Account/Profile and Health Profile are distinct: users identifies the login account; health_profiles can describe self/family/dependent people with shared membership. Never use account identity as proof of ownership of an arbitrary health profile.

Relevant schema facts: health-domain tables have health_profile_id foreign keys/indexes; memberships have unique (health_profile_id,user_id), active/revoked status and owner/editor/viewer roles; profiles have active/archived status. Personal medicines use current/stopped/historical status. Conditions use active/chronic/resolved/historical status. Source-backed public disease knowledge and Medicine governance are separate domains.

## C. Candidate Dashboard Cards

| Card | Existing Data | User Value | Complexity | V1 Recommendation |
|---|---|---|---|---|
| Selected Health Profile | Accessible profiles, profile type/name/member role | Makes whose records are being viewed explicit | Low; explicit selection rule needed | INCLUDE |
| Conditions | Active/chronic and historical arrays from summary | Shows how many condition entries are recorded | Low | INCLUDE within Health Records card |
| Allergies | Recorded allergy entries | Clear route to allergy management | Low | INCLUDE within Health Records card |
| Procedures | Recorded procedure entries | Clear route to history | Low | INCLUDE within Health Records card |
| Personal Medications | Current-status entries from summary | Quick way to find medication records | Low | INCLUDE count; defer drug-name/dose previews |
| Documents | Existing authorized document count | Shows whether files have been recorded | Low | INCLUDE |
| Medical Summary | Existing generated summary | Consolidated deliberate detail view | Low; reuse already fetched aggregate | INCLUDE link; no invented “summary exists” flag |
| Saved Diseases | Current account's saved list | Return to saved educational information | Low | INCLUDE count; no diagnostic interpretation |
| Recent Disease Activity | Session IDs and public disease rows | Resume browsing | Low technical cost; ambiguous account/profile semantics | DEFER |
| Timeline | Generated full history/events | Chronological detail | Medium; duplicate reads/materialization | DEFER |
| Insurance | Existing policies/members/documents | Useful administration | Medium; financial/private detail | DEFER |
| Summary sharing / access administration | Owner permissions, snapshots, invitations | Existing deliberate sharing control | Medium; sensitive workflows | DEFER |
| Disease comparison | Public comparison selector/results | Educational discovery | Low for a navigation link | DEFER card; keep existing public navigation |
| Account settings / linked authentication | Account and identity state | Existing settings | Low | EXCLUDE health cards; keep /account link |
| Health/risk scores, adherence, AI advice | No applicable stored metric for this dashboard | Would imply unsupported assessment | Outside scope | EXCLUDE |

## D. Recommended V1

Exactly six cards and one accessible-profile selector. Counts describe recorded entries, not medical status, completeness, safety or clinical priority.

| Title | Displayed information | Empty state | Destination | Data source / existing read |
|---|---|---|---|---|
| Health Profile | Selected person's full name, profile type and membership role; link to profile detail. No DOB/gender required in compact card; no completion percentage. | “No health profile available.” Link to existing Health Records/create flow. | /account/health-records/{profileId}; profile switching returns dashboard with selected ID | getAccessibleProfiles; profile returned by authorized getSummary |
| Health Records | Separate labelled counts: active/chronic conditions, allergy entries, procedure entries. Historical condition count may be a secondary label, not mixed into active count. No sensitive names/notes preview. | “No condition entries recorded”, “No allergy entries recorded”, “No procedure entries recorded” independently. | /account/health-records/{profileId}/conditions, /allergies, /procedures | getSummary → conditions.active/historical, allergies, procedures |
| Personal Medications | Count labelled “Medications recorded as current”; use explicit stored status, not date inference or adherence. | “No medications recorded as current.” View all remains available when only stopped/historical records exist. | /account/health-records/{profileId}/medicines | getSummary → medicines.current |
| Documents | Number of documents recorded; no filename, storage location or medical-result preview | “No documents recorded.” Existing management page provides upload action to editable roles. | /account/health-records/{profileId}/documents | HealthDocumentService::getDocumentCount |
| Medical Summary | “View medical summary”; explain it is assembled from recorded information, not a clinical review. No creation/review date or persisted-existence badge. | “No health entries recorded yet.” Summary remains viewable for an accessible profile with empty arrays. | /account/health-records/{profileId}/medical-summary | Same getSummary result already used by health cards; no second fetch |
| Saved Diseases | Count labelled “Saved disease information”; make clear it is knowledge saved by this account, not this person's diagnoses | “No saved diseases yet.” Link to Disease Catalog to browse/save through normal flow. | /account/saved-diseases; empty-state browse /diseases | SavedDiseaseService::getSavedDiseases; count resulting list |

Existing /account remains Account settings; existing /account/health-records remains profile management/navigation. No duplicate edit/upload forms inside cards. No new query is required for any card; only orchestration, a deliberately small projection and rendering are new.

## E. Architecture Recommendation

Proposed files for later Step 42B, not created here:

- app/Controllers/PersonalHealthDashboardController.php: authenticated GET action; validate optional profile_id, call orchestration service, render with $this->view(). Use existing redirect() behavior for anonymous/unavailable-profile cases.
- app/Services/PersonalHealthDashboardService.php: orchestrate AuthService, HealthProfileService, MedicalSummaryService, HealthDocumentService and SavedDiseaseService. Return minimal profile identity, counts, role-dependent links and empty-state flags.
- app/Views/account/health-dashboard.php: Bootstrap cards with existing escaping/layout conventions.
- routes/web.php: one GET /account/health-dashboard, optional profile_id query. No API.
- Existing account/navigation view: add one link to the new overview when implementing; preserve existing destinations/forms.

Use the existing App/Core/Container.php constructor dependency resolution. No new framework or global state. A dashboard-specific repository is not justified: all reads already exist. Repositories remain behind existing domain services, with authorized profile checks intact.

MedicalSummaryService already invokes HealthProfileService before its direct domain repository reads. Reusing that service preserves Controller → Service → Repository → Database. The dashboard must not invoke raw repository queries merely to avoid authorization.

Recommended private/no-store response headers follow existing sensitive document/share-response conventions in AccountController; do not apply a project-wide cache change. Keep this page authenticated and server-rendered, with no sensitive summary serialized into JavaScript or public caches.

## F. Authorization Model

1. Determine the account using existing AuthService::check()/id(); use AuthService::user() where existing account validation requires confirming an active account. Never accept a user ID from query/form input.
2. Obtain selectable profiles through HealthProfileService::getAccessibleProfiles(), which calls findAccessibleByUser(currentUserId). Both profile and membership must be active.
3. No existing persisted “active profile” or selected-profile session setting was found. For V1, explicit profile_id selects only from that accessible list. Without it, choose the unique accessible self profile for which the user is owner; if none/ambiguous, choose automatically only when exactly one accessible profile exists. Otherwise show the chooser without any selected-profile clinical counts.
4. Selection from a prior list is not the final access check: call MedicalSummaryService::getSummary(selectedId), which calls HealthProfileService::getProfile → HealthProfileRepository::findAccessible(selectedId,currentUserId). It returns the current membership role and rejects inaccessible/archived/revoked profiles.
5. Documents use HealthDocumentService::getDocumentCount(selectedId), which repeats authentication/profile authorization. Keep that guard; do not bypass it using countByProfileId directly.
6. All health cards describe one selected profile. Do not combine self/family/shared profiles into “your” counts. Show the selected person's identity prominently.
7. Read access permits active owner/editor/viewer memberships. Editing CTAs must follow existing owner/editor permissions; viewers get “View all” rather than actions implying edit rights. Sharing/access administration remains owner-only on existing detail flows.
8. A missing/invalid/inaccessible requested ID yields the existing safe account/health-record destination or a chooser message without sensitive data/internal exception details. Never silently render another profile's counts as though it were the requested profile.
9. Saved diseases remain account-scoped via SavedDiseaseService's existing auth_user_id session convention, not profile-scoped. Do not add a second session key or authorization mechanism.
10. Do not call ensureSelfProfile() during dashboard GET. It can create a profile/membership; login flows already call it. A missing profile is an ordinary empty state.

User-supplied names, dates, notes and document metadata must not become URL parameters. Only a validated profile ID is needed. Do not expose DOB, allergy reactions/severity, diagnoses, medication doses/prescribers, procedure reasons, document content/storage paths, insurance IDs/financial values, access membership identities, token hashes or public share tokens in compact V1 data.

## G. Query / Performance Considerations

- Profile selector: one findAccessibleByUser query.
- One getSummary for selected profile: one findAccessible query plus four profile-scoped domain list queries (five total).
- Documents count: one findAccessible query plus one COUNT query (two total).
- Saved diseases: one joined user-scoped list query.
- Baseline: approximately nine repository SELECTs with a selected profile, plus an account lookup if AuthService::user() is used. Auth check/id reads session state. No selected profile: profile-list and saved-disease reads suffice.
- Compute existing counts in memory from the single summary. Do not fetch HealthConditionService/getMedicines/getAllergies/getProcedures separately after getSummary; that would duplicate profile checks and domain queries.
- The repeated profile check in document counting is existing authorization, not a reason to bypass the service. Avoid a separate controller getProfile call if getSummary already supplies the authorized profile.
- Do not load aggregates per accessible profile just to decorate the chooser. This would create a profile-count N+1 pattern. Only selected-profile data is read.
- Saved diseases already returns joined names/slugs; do not follow each result with getKnowledgeBySlug or isSaved queries. Count-only V1 avoids extra hydration.
- Domain lists are unbounded. Count-only cards still materialize rows through getSummary; acceptable first reuse for a selected profile, but actual large-profile performance is unmeasured. If measured later, add small count methods to existing domain repositories/services, not a broad dashboard repository.
- Document COUNT uses the existing health_profile_id index and does not load files. Do not fetch getDocuments as well merely to show a count.
- Loading both getTimeline and getSummary duplicates four record lists; timeline also reads documents and sorts generated events. Defer its card.
- Insurance detail loading can cascade into member/contact/document reads; do not fetch detail per policy for a count/overview.
- getPublicShare() is not a pure read: after token validation it logs access. Sharing creation persists a snapshot. Neither belongs in dashboard assembly.
- Recent browsing preserves the session ID order through DiseaseRepository::findByIds using ORDER BY FIELD(d.id, ...). It is session browsing order, not a persisted account/profile medical-event chronology; no ordering fix is needed.
- No optimization, query, authorization or caching code was changed in this task.

## H. Mobile/Desktop UX Recommendation

390×844: one column; profile selector/identity first; Health Records then Personal Medications, Documents, Medical Summary, Saved Diseases. Keep counts and labels concise. Health Records uses three stacked rows with full-width touchable destinations. Cards show no clinical previews or dense tables. Display all six cards below the fold without hiding them in tabs.

1366×768: profile identity/selector across the top; two or three columns using existing Bootstrap breakpoints. Preserve the same reading order, selected-profile context and clearly separated account-level Saved Diseases.

Use clear “View all”/“View summary” links; no hover-only controls, icon-only meaning, fixed-width labels, duplicate interactive nesting or inline JS. Do not add charts. Keyboard focus and headings must remain coherent. The dashboard supplements existing navigation.

## I. Empty-State Strategy

A new user sees an ordinary overview, not an error or sample dataset. Existing login may have ensured a self profile, but this is not assumed.

- No accessible profiles: show profile setup/Health Records navigation; do not call an auto-create method. Keep account-level Saved Diseases available.
- Several profiles and no unambiguous default: ask the user to select one; show no health counts until selected.
- Empty record categories: say no entries recorded, not absence of disease/allergy or good health. A zero current-medication count does not mean there are no historical medication records.
- Existing profile with missing optional DOB or unspecified gender: no fabricated completion score; details may be updated through the normal profile form.
- Documents: upload/add action stays in the existing management page and follows edit permission.
- Summary: derived view remains available even with no entries; no persisted summary-existence claim.
- Saved diseases: offer browsing; no sample bookmarks or inferred diagnoses.
- Inaccessible requested profile: safe selection/navigation response, distinct from a legitimate zero-count result.

## J. Explicit Non-Goals

No health/wellness/completion percentage, clinical risk/severity score, AI diagnosis/advice, clinical confidence, medication reminders/adherence metric, ABHA/ABDM integration, EHR interoperability, lab interpretation, clinical alerts, Treatment governance/publication, Medicine governance, broad analytics or saved symptom-check implementation.

No new schema, metric persistence, aggregate profile merging, duplication of health edit forms, upload/storage redesign, external sharing/token generation, connected-account changes, authentication redesign, public health disclosure, new API, or clinical prioritization inferred from counts.

Timeline, insurance detail, recent-session activity, comparison results and sharing/access cards are deferred. Their existing management/navigation flows remain intact.

## K. Implementation Plan — Step 42B

1. Implement the single authenticated GET route/controller and dashboard orchestration service using existing constructor DI.
2. Implement the explicit accessible-profile selection rule; reuse one authorized medical summary, document count and saved-disease list. Project only V1 identity/counts/links; avoid write-capable helpers.
3. Render the six cards in an account view with honest empty states and role-appropriate navigation; add one existing Account/navigation link without replacing current pages.
4. Add focused database-free service/view tests: owner/editor/viewer, profile selection/revocation/inaccessibility, no profile/data, account-versus-profile scope, current/status counts, projection/escaping, and no writes. Test orchestration read counts to catch duplicate/N+1 assembly.
5. Verify through genuine login at 390×844 and 1366×768; check existing management destinations, profile switching, no cross-profile data, anonymous redirect and empty states. Restore any test data if a separately authorized flow changes it; dashboard GET itself must remain read-only.

This is one product implementation step, not another governance phase. Schema changes are not a prerequisite. Any genuine new blocker during implementation should be reported rather than widening scope.

## Validation / Completion Record

- Inventory covers all requested A–L features plus timeline, insurance, profile sharing/access and existing account preferences.
- Existing relevant tests inspected: tests/health-profile-validation.php; existing medication date/autocomplete test files identified. No application regression suite was required or run.
- Source routes and method references were checked; report completeness/whitespace checks performed.
- File created: docs/task-42a-personal-health-dashboard-readiness.md only.
- Existing files modified: none.
- Database writes: NO. No database connection or private health-record reads were performed.
- Schema changes: NO. Application code/behavior changes: NO.
- Routes/controllers/services/repositories/views/tests/configuration changed: NO.
- Treatment, Medicine, Authentication, Health Profile, Personal Medications, Symptom Checker, disease content and Step 41B references: unchanged.
- Git mutations: NO.
- Step 42B was not implemented. Stop after Step 42A.

