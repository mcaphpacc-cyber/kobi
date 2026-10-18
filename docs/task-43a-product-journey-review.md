# Step 43A — Product Journey Review

Date: 2026-10-13. Review status: **COMPLETE**.

This is a source-traced product review, not a new browser audit or implementation phase. Current routes, controllers, services, repositories, views, JavaScript and `database/structure.sql` were inspected. Existing Step 42B verification evidence and focused test source were also inspected; their results were not rerun or represented as new testing. No application request, login, symptom submission, database connection or regression test was executed for this review.

## A. Executive Summary

**NEXT FEATURE: Guided Personal Health Dashboard.**

Turn the existing overview into a useful starting point for recording, reviewing and using personal health information. Within its existing six areas, offer clear, permission-appropriate next actions and explain how records, medicines and documents contribute to the existing Medical Summary. Provide a prominent authenticated navigation entry without changing authentication redirects or behavior.

The biggest product gap is between having personal-health capabilities and knowing how to use them toward a practical outcome. KOBI already has record management, documents and a derived Medical Summary. Its dashboard currently shows counts and View links; it does not guide a new user from an empty profile to a useful summary, or help a returning user review recorded information before a consultation.

This recommendation wins on immediate usefulness, reuse and low implementation cost. It strengthens the personal-health proposition before adding another store of sensitive information or a scheduling subsystem. It is a prioritization judgment, not a measured retention finding: no usage analytics, user interviews or comparative conversion data were available in this review.

## B. Current Product Journey

The inspected implementation supports more than the six dashboard areas:

| Current capability | Actual implementation and boundary |
| --- | --- |
| Homepage and disease discovery | HomeController/HomeService/HomeRepository provide disease suggestions and body-system discovery. DiscoveryController/DiscoveryService combine public discovery data and recently viewed diseases. DiseaseController and DiseaseService support the catalog, search and filters. |
| Disease Detail | DiseaseController loads DiseaseService knowledge, related diseases and saved state. The view renders overview, causes, risk factors, diagnosis, prevention, Treatments and other available sections. |
| Disease section references | DiseaseReferenceService reads `resources/disease-references.php`. Its approved scope is eight disease IDs and five English sections; the two unresolved Overview mappings are deliberately excluded. References are not universal clinical-review certification. |
| Treatment information | DiseaseService explicitly uses the existing legacy educational read path. This is distinct from governed Treatment publication and must remain so in this review. |
| Symptom Checker | SymptomCheckerController and SymptomCheckerService search symptoms and call the matcher. `/api/symptom-checker` records search counters before matching. The frontend supports selected symptoms, results, summary and neutral Symptom Match wording. |
| Public Medicines | SubstanceController renders a name-search page; SubstanceService/SubstanceRepository return up to ten active/searchable canonical-name prefix matches. The page explicitly identifies legacy registry names and excludes prescribing guidance. It is not a curated medicine monograph or approved-product catalog. |
| Account and profiles | Account settings, email/password and Google entry paths exist. HealthProfileService and HealthProfileRepository scope profile reads to active memberships and profiles; owner/editor/viewer roles govern existing destinations. Family profiles and profile access/invitation management exist. |
| Personal health records | Existing Account destinations and domain services manage conditions, allergies, procedures, medications and documents for accessible profiles. Medication status distinguishes current, stopped and historical entries. |
| Medical Summary and timeline | MedicalSummaryService derives a summary from authorized current records. Timeline navigation exists. MedicalSummaryShareService provides owner-controlled, expiring shares and existing management/revocation paths; it is already a feature, not a missing subsystem. |
| Saved diseases | SavedDiseaseService and UserSavedDiseaseRepository persist account-scoped bookmarks in `user_saved_diseases`. The saved list and its empty state already link back to disease exploration. |
| Recent disease history | DiseaseService stores up to ten recently viewed IDs in the application session. Discovery and the recent catalog can show them. This is not durable account history across devices or new sessions. |
| Disease comparison | ComparisonController supports selecting diseases and viewing comparison through DiseaseService. There is no need to propose comparison as a new feature. |
| Dashboard | `/account/health-dashboard` has the six approved areas, authorized profile selection, current-only medication counts, document counts, Medical Summary navigation and account-level saved disease counts. Account settings links to it. |
| Additional management | Insurance routes, tables and Health Record navigation also exist. They were recognized in the inventory; this review does not assess their complete runtime behavior or propose them as a new feature. |

Key evidence: `routes/web.php`; `app/Controllers/{Home,Disease,Discovery,SymptomChecker,Comparison,Auth,GoogleAuth,Account,Substance,PersonalHealthDashboard,SavedDisease}Controller.php`; corresponding inspected services/repositories; `app/Views/{home,disease,discovery,symptom-checker,substances,account}`; `app/Views/layouts/navbar.php`; `public/assets/js/symptom-checker.js`; `database/structure.sql`; `docs/task-42b-personal-health-dashboard-implementation.md` and `tests/health-dashboard.php`.

## C. Anonymous User Journey

Homepage → suggestions or body-system discovery → catalog/detail → educational sections and Treatments → Symptom Checker → registry-name lookup → registration/login.

Immediately useful: disease discovery, readable educational sections, section sources where confirmed, comparison and symptom-based exploration without registration. The public Medicine lookup has an explicit and limited purpose.

Meaningful friction: the public journey primarily answers knowledge questions; the reason to create an account becomes clearest when saving a disease or wanting private personal/family records. Clicking Save Disease anonymously is routed to Login by SavedDiseaseController. The checked authentication redirect normally returns to the homepage, except pending profile invitations; do not assume saving intent is automatically resumed. This is journey friction, not a new authentication defect established by this review.

The missing next action is a clear explanation of the personal benefit after registration: record information once, maintain it when it changes, and view a useful summary. More public search fields would not by themselves establish that benefit. Registry names should continue to be presented as names, not as answers to medication-use questions.

## D. New Registered User Journey

Registration → email verification/authentication → homepage → My Account → Health Records or Dashboard → existing record/medication/document pages → Medical Summary → saved knowledge.

AuthController creates/ensures a self profile after ordinary email verification; Google authentication also has an existing self-profile path. Pending invitations are handled separately. Account settings is not the automatic post-login health dashboard. Its sidebar exposes the dashboard, Health Records and Saved Diseases; the global authenticated navbar links to My Account.

First use has working destinations and neutral empty states. The dashboard makes the available categories visible, but its category links primarily say View rather than explaining where to start or why recording information helps. A user can reach an empty summary without having a clear process for making it useful.

The meaningful next step is optional, guided record setup through existing forms, followed by viewing the summary. Conditions, allergies, medication use and document ownership must never be presumed. A zero count is an absence of entries, not evidence of clinical absence or an obligation to add data.

## E. Returning User Journey

Existing reasons to return include reading saved disease information, looking up a new question, updating a medicine/record when it changes, storing a new report, and reviewing a Medical Summary before a consultation. These are genuine but largely event-driven reasons. A dashboard count does not itself create a daily task.

Current loops:

- Knowledge question → explore disease → save it → revisit the saved list.
- New or changed personal information → update the authorized profile → view the derived summary.
- New report or upcoming consultation → documents/records → summary → existing print/share workflow where appropriate.

The weakest loop is activation and return navigation within the personal-health journey. Its components already exist, but the overview does little to connect them to the outcome. The smallest improvement is guidance and direct actions in the existing dashboard, not notifications manufactured to create visits.

Symptom history has an important distinction: the frontend's `kobi.recentSymptoms` stores five recent individual symptom choices in localStorage. `kobi.symptomSearch` stores a result/selection state in sessionStorage for navigation restoration. Neither is a dated, account-scoped Saved Symptom Check. Saved checks could add continuity, but their recurrence and usefulness have not been measured and they would persist additional sensitive data.

## F. Current Value Proposition

The inspected product supports the positioning **personal health knowledge and management platform**: public educational exploration and private personal/family records coexist, with a useful summary already available.

The strongest differentiator within this product is that combination of readable knowledge and authorized personal/family information that can be summarized. This is not a claim of competitive superiority. Saved disease interests remain distinct from diagnosed conditions, which should be preserved.

The weakest part is the transition from exploration/account creation to getting practical value from personal records. The biggest missing user-value loop is an understandable path from initial entries to a useful summary and back to reviewing those entries when information changes. KOBI should not claim EHR replacement, automated diagnosis, clinical completeness or clinically approved Medicine information based on its current public registry lookup.

## G. Feature Candidate Comparison

Ratings are relative product judgments for the next increment. Retention describes plausible potential, not observed outcomes. Complexity includes ongoing editorial or operational work, not only coding.

| Candidate | User Value | Retention | Fit | Complexity | Priority |
| --- | --- | --- | --- | --- | --- |
| A. Saved Symptom Checks | MEDIUM | MEDIUM | HIGH | MEDIUM | MEDIUM |
| B. Medication Scheduling / Due-Today | HIGH for users with recurring schedules | HIGH | HIGH | HIGH | MEDIUM |
| C. Curated Substance Information | HIGH | MEDIUM | HIGH | HIGH | MEDIUM |
| D. Guided Personal Health Dashboard | HIGH | MEDIUM | HIGH | LOW | **HIGH — NEXT** |
| E. Unified Search | MEDIUM | MEDIUM | MEDIUM | MEDIUM | LOW |
| F. Richer Disease Knowledge | HIGH | MEDIUM | HIGH | MEDIUM | MEDIUM |
| G. Richer Treatment Knowledge | HIGH | MEDIUM | HIGH | HIGH | LOW |
| H. API Foundation | LOW immediate direct value | LOW direct effect | MEDIUM | HIGH | LOW |
| I. Native Mobile Foundation | MEDIUM potential | MEDIUM potential | MEDIUM | HIGH | LOW |
| J. ABHA/ABDM interoperability preparation | LOW immediate direct value | LOW direct effect | MEDIUM | HIGH | LOW |

Candidate-specific frequency, reuse, privacy, next action and subsystem implications:

- **A — Saved Symptom Checks:** Episodic use for revisiting a prior exploration. Reuses symptom selection, result rendering, disease links and authentication. Adds genuine recall beyond existing browser-local state, but requires durable private storage and management; existing schema has no saved-check table. V1 would need deliberate save consent, clear historical labeling and deletion. Scores must not be treated as diagnoses or longitudinal clinical progression. A new small persistence feature is necessary, not a new matcher.
- **B — Scheduling/Due-Today:** Potential daily utility for people who want to organize an already prescribed regimen. Reuses profile authorization and medicine records, but `health_medicines.frequency` is free text and current status is not a schedule. Structured user-entered timing, timezone and exceptions require a new scheduling subsystem; notifications add another delivery/operational burden. Do not infer schedules from text, recommend doses or treat missed checkoffs as adherence evidence. Valuable, but disproportionate as the very next increment.
- **C — Curated Substance Information:** Episodic but meaningful education following name lookup. Reuses canonical identity/search infrastructure and existing editorial groundwork. Public verified content requires authoritative evidence, qualified editorial review and a publication path; the name registry cannot substitute for these. Clinical misinformation risk and continuing content upkeep are substantial. It requires a reviewed content layer, not just a richer result card, and remains separate from pilot activation.
- **D — Dashboard guidance:** First-use value plus periodic value when records change or consultations are approaching. Reuses the current dashboard, profile authorization, counts, existing create/manage destinations and derived summary. A next action leads to an existing useful outcome. No new table or subsystem is needed for static contextual guidance and links. Privacy exposure can remain at the current count/label level; use existing roles for write actions. This is the strongest near-term value-to-cost choice.
- **E — Unified Search:** Useful when users cannot choose a knowledge domain, but existing disease discovery and substance prefix lookup already serve distinct tasks. Public search can reuse these services with separate result types; it must not imply registry names and medical content have equivalent review status. Mixing private records would add substantially more privacy/access complexity. A search orchestration/presentation layer is needed, though a new index is not inherently required. It improves discovery more than personal-health return use.
- **F — Richer Disease Knowledge:** Episodic reading and repeat use for saved interests. Reuses disease sections, references and the existing editorial content workflow. More source-backed coverage offers value, but requires research and field-level attribution rather than automated expansion. No new application subsystem is necessarily required; ongoing medical editorial responsibility remains. Prioritize specific demonstrated content gaps rather than rewriting all diseases.
- **G — Richer Treatment Knowledge:** Episodic educational value, with more consequential medical-risk implications. Reuses legacy display and existing governance groundwork, but review/publication dependencies cannot be presumed complete for public exposure. A larger verified-content and publication effort is required. No autonomous treatment suggestions or bypass of HOLD/publication policy. It should follow evidence-backed content work, not become the next product increment by default.
- **H — API Foundation:** Benefits future clients/integrations; API use is not itself a user return loop. Existing AJAX routes and service/repository layers provide reuse, so the app does not lack APIs entirely. A general private-health API adds contracts, access control and sensitive-data exposure work. New interface infrastructure is needed, not necessarily new tables. Defer until a concrete client use case justifies it.
- **I — Native Mobile Foundation:** Could support frequent use, but a native shell does not establish a reason to return. Reuses responsive journey concepts and business services; a native client still needs suitable API/session contracts and maintenance. Sensitive data on devices and potential notifications add obligations. Requires a new client subsystem. Improve and validate the useful responsive web workflow first.
- **J — ABHA/ABDM preparation:** Potential future integration value, not an immediate task for the current user. Profile/record structure offers conceptual reuse, but inspecting local code does not establish external compatibility or implementation readiness. Requires separately validated external requirements, consent/identity considerations and integration work. No regulatory or interoperability readiness claim is made here. A new integration subsystem would be necessary; defer until an explicit use case exists.

## H. Biggest Current Product Gap

**Users have an overview of stored information, but little guidance toward a useful outcome.**

Concrete evidence: `app/Views/account/health-dashboard.php` renders counts with View links and neutral empty messages. The selected profile is safe and clear, and a summary already exists as a derived view. The user still has to infer which management page to use and how those entries make the summary useful. Normal entry through My Account also emphasizes settings before the health dashboard.

This is an opportunity to make existing capabilities work together, not a finding that the dashboard is broken. It is more immediately actionable than adding saved symptom history, a daily scheduling system or a second client application.

## I. Recommended Next Feature

**Guided Personal Health Dashboard — exactly one next feature.**

User problem: “I can see my health categories, but what can I do here, and how do I turn these records into something useful?”

Connect the selected authorized profile → existing record/medication/document workflows → existing Medical Summary. Keep saved diseases as a separate reading interest. Provide guidance for new users and a neutral review action for returning users. The same destinations remain authoritative; no duplicated editing or new data model is needed.

It comes before Saved Symptom Checks because it benefits the existing personal-health journey without storing another type of sensitive history. It comes before scheduling because it avoids inventing structured regimen semantics from free text. It comes before broader content and integrations because those need sustained editorial or subsystem work while this improves the usefulness of capabilities already present.

## J. Smallest Useful V1

1. Add a clear authenticated Personal Health Dashboard navigation entry alongside the existing Account destination. Preserve settings and all authentication redirects.
2. Keep the six areas and authorized selection behavior. Within them, explain one practical purpose: “Record information you want to keep together, then review your medical summary.”
3. Offer permission-appropriate links to existing Add/Manage destinations for record categories, medications and documents. Owner/editor actions must follow each destination's actual rules; viewers retain read-only navigation. Do not duplicate forms or perform writes from dashboard rendering.
4. Pair empty states with optional next steps, using language such as “Add an entry if relevant” rather than “complete this required health category.” For populated states, offer review/manage navigation without claiming the content is current or complete.
5. Give Medical Summary a clear outcome-oriented action, such as “Review your recorded information,” linking to the existing summary. Existing sharing remains separately owner-controlled. Explain that the summary reflects entered records and is not a clinical assessment.

High-level complexity: **LOW**, reusing current services, counts, view conventions and authenticated/profile authorization. Existing authorized detail reads may supply presentation context only where necessary; no new repository abstraction, table or schema change is required for this V1. If an action needs more state than currently available, defer it rather than inferring clinical information.

Validate the hypothesis through practical first-use tasks: can a user find the dashboard, identify a relevant existing form, understand that adding information is optional, and reach the summary without assistance? For return use, can they select the right profile and find where to review changed information? Do not claim retention uplift from button availability alone.

## K. Explicit Non-Goals

- No health/completeness score, progress percentage, mandatory clinical checklist, diagnosis or risk interpretation.
- No automatic creation of records, profiles, symptoms, medications or inferred clinical absence from zero counts.
- No treatment recommendations, medication schedules, reminder delivery or adherence tracking.
- No saved symptom subsystem, persistent onboarding/dismissal state, recent-change timeline or new summary generator in this V1.
- No automatic linking of saved disease interests or symptom-match results to conditions.
- No automatic sharing, new authority model, authentication changes, public private-data exposure or clinical-record previews in the dashboard.
- No changes to Disease References, Treatment publication/HOLD, Medicine governance/pilot, the symptom matcher, public registry claims, APIs for a native client or ABHA/ABDM integration.

## L. Short Next-Feature Ordering

**Next:** Guided Personal Health Dashboard.

**Following:** Saved Symptom Checks, if users demonstrate a need to recall past explorations, with explicit save and historical-result boundaries.

**Later:** Targeted source-backed Disease Knowledge expansion → a small curated Substance Information scope after evidence/reviewer/publication prerequisites are met.

Scheduling remains a candidate for a separately scoped prescribed-regimen use case, not a prerequisite for this ordering. This is four items, not a commitment to implement them automatically.

## Review boundary and completion

- File created: `docs/task-43a-product-journey-review.md` only.
- Existing files modified: **NONE**.
- Database writes: **NO**. No live database inspection was needed; schema and repository reads were inspected from files.
- Schema changes: **NO**.
- Application code/routes/controllers/services/repositories/views/tests changed: **NO**.
- Git mutations: **NO**.
- New browser/runtime verification and regression tests: **NOT PERFORMED**, as no application behavior changed. Existing Step 42B results were used as historical evidence with that distinction stated.
- Limitation: product value and retention ratings are reasoned hypotheses from the inspected implementation, not findings from a new-user study, live usage analytics or external integration review.

Stop after Step 43A. The recommended feature has not been implemented.
