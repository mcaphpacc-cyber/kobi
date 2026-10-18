# Step 44A — Saved Symptom Checks Readiness

Date: 2026-10-13. **READY FOR A SCOPED STEP 44B IMPLEMENTATION**, with the schema and trusted-snapshot prerequisites identified below. This is design readiness, not deployed-schema or runtime acceptance. No implementation or migration was performed.

## A. Executive Summary

Recommend a **user-owned, explicitly saved historical snapshot** of a completed KOBI symptom check. V1 includes Save, a paginated private list, historical detail and individual permanent deletion. Add an Account navigation entry; defer Dashboard integration, search, notes, sharing and export.

The snapshot answers which symptoms were selected, when the check ran, and which ranked matches KOBI returned then. It is an educational interaction record, not a diagnosis, clinician record or inferred condition. Preserve the current disclaimer and label old results clearly as historical.

One new persistence table is necessary. Existing bookmarks, symptom master data, search counters and browser-local state cannot safely substitute for saved checks. The main implementation prerequisites are a trusted server-side capture/save handoff, a functional batch symptom-label lookup, bounded snapshot validation and deployment of the additive table under separate authorization.

## B. Current Symptom Checker Architecture

Inspected current sources, not only previous reports:

- `routes/web.php`: GET `/symptom-checker` → SymptomCheckerController::index; GET `/api/symptoms` → search; GET `/api/symptom-checker` → match. The controller's older `results()` action exists but is not wired as the current result route in the inspected route file.
- `app/Controllers/SymptomCheckerController.php`: index loads popular symptoms; search delegates to the service. Match splits the comma-separated GET `symptoms` input into integers, calls `recordSearch`, then `match`, and returns JSON. These routes have no account or profile ownership requirement.
- `app/Services/SymptomCheckerService.php`: SymptomRepository supplies popular/search data and increments counters; DiseaseRepository supplies candidates; SymptomMatcher supplies scores/results. No saved-check repository or user/profile binding is present.
- `app/Repositories/SymptomRepository.php`: symptom search returns `id`, `symptom_en`, `symptom_hi`, `slug`; IDs are master-data keys. Popular results are limited to 20 and search to 10. **`findByIds()` currently returns `[]`; `findBySlug()` returns null.** These are stubs, not functional lookup paths.
- `app/Repositories/DiseaseRepository.php`: `getCandidateDiseases()` reads disease/content fields, loads associated symptoms in a batch and constructs quick facts. Candidate disease objects include `id`, `disease_en`, `slug`, symptom associations, educational content and severity/urgency fields. `findByIds()` is an existing functional batch read usable for current disease-link resolution.
- `app/Algorithms/SymptomMatcher.php`: matched/missing symptoms are determined from selected IDs and disease associations. It computes `userMatchScore`, `coverage` and weighted `rankingScore`, sorts descending by rankingScore and returns at most **10** result items. Preserve the returned sequence, including ties, rather than rescaling or resorting historical results.
- `app/Views/symptom-checker/index.php` and `public/assets/js/symptom-checker.js`: selection is an array of browser objects with symptom ID/name. The frontend sends comma-separated IDs to the live endpoint. The JSON result remains in `state.results`; changed selections/loading/errors invalidate results. Summary and result cards render in the existing page-specific global JavaScript architecture.

Actual response structure:

```text
summary:
  selectedSymptoms: selected ID array
  totalMatches: all candidate matches before truncation
  displayedMatches: up to 10
  generatedAt: server date('c') timestamp, with offset
results[]:
  disease: ID/name/slug, associations and educational fields
  matchedSymptoms[] / missingSymptoms[]: ID and English label
  matchedCount
  userMatchScore
  coverage
  rankingScore
```

The main UI “Symptom Match” percentage rounds **rankingScore**; selected-symptom match and disease coverage are separate measures. Do not replace one with another when saving. Match-level labels come from existing frontend thresholds. “Why this matched” lists matched labels and up to two associated unselected labels; the match-reason block also uses matched/missing counts and coverage. These are presentation over the response, not a separate persisted reason field.

The current summary disclaimer is: **“KOBI Symptom Checker is an educational tool. It does not provide a medical diagnosis.”** Cards also state that results are ranked by symptom overlap and are not a diagnosis. Retain these boundaries on historical detail. Existing internal function names such as `renderConfidence()` are not permission to introduce Clinical Confidence wording.

A valid response can have zero matches and still be a completed check. V1 should allow explicitly saving that outcome with the original selected symptoms and “No matching results were returned for this check,” without inventing a diagnosis or empty disease item.

## C. Existing Persistence / Storage Findings

Schema inspection used `database/structure.sql`; no live database connection was needed.

| Information | Current storage |
| --- | --- |
| Symptom identities/English/Hindi labels | `symptoms` master table |
| Disease-to-symptom associations | `disease_symptoms` |
| Frequency of submitted symptom IDs | `symptoms.search_count`, incremented by the live match endpoint; not a user history |
| Selected symptoms and latest response during page use | JavaScript memory |
| Navigation-restoration result state | Browser sessionStorage `kobi.symptomSearch`: selected symptoms, response and client timestamp; not server-owned, authenticated or durable account history |
| Recent symptom choices | localStorage `kobi.recentSymptoms`: five individual choices, not five completed checks |
| Recently viewed diseases | Application session `recent_diseases`: different interaction and session lifetime |
| Saved diseases | `user_saved_diseases`: account/disease bookmark relationships, not inputs/results/percentages |
| Completed checks with owner, checked timestamp and historical results | **No corresponding table/path found** |

The match response's `generatedAt` is not currently written to a saved-record table. Browser restoration can show prior output but provides neither ownership nor trustworthy persistence. Do not import browser storage as historical server evidence or repurpose a Medicine/Treatment audit or health-condition table.

## D. User vs Health Profile Ownership Decision

**Select model A: user-owned saved check.** Owner comes only from AuthService::id().

The current checker is public and has no selected health profile, patient context or profile-role model. A person may explore symptoms for many reasons; the interaction does not establish whose symptoms they are. Account ownership records who saved the interaction, not a medical fact about that person.

Model B, profile ownership, would require an explicit subject selection, relationship semantics and decisions about whether profile viewers/editors can see or delete potentially unrelated searches. None is established by the current checker. It would also blur these records with the personal-health summary. Do not add `health_profile_id` in V1, auto-attach the dashboard-selected profile, grant shared-profile access or feed saved matches into conditions/Medical Summary.

## E. Authorization & Privacy Model

- Only a genuinely authenticated, active account can save, list, view or delete its checks. Reuse AuthService::check/user/id behavior and existing Account conventions.
- Anonymous visitors retain the current checker, but cannot persist checks. No anonymous history database is proposed.
- Repository list, detail and deletion queries must all constrain `user_id` to the server-authenticated ID. A check ID alone never authorizes access. Use the same safe not-found/denied behavior for a foreign or missing item.
- Do not accept a browser-supplied owner/profile ID or disclose whether another account has a given check. Profile membership checks are not required for this selected account-owned model; they also cannot grant access to these checks.
- Historical list/detail are private/no-store responses. Do not place symptoms, scores, payloads or labels in URLs, public HTML, analytics or diagnostic logs. An opaque saved-row ID in an owned Account route is sufficient.
- No sharing, family-access inheritance, public links or clinical-record integration. Do not use saved interests/results to infer a condition.

## F. Minimum Saved Check Data Model

Store plain validated data, not HTML or the complete live disease/content objects.

| Data | Decision | Reason |
| --- | --- | --- |
| Saved-check ID and authenticated owner ID | REQUIRED | Owned retrieval and deletion |
| Server checked timestamp and saved timestamp | REQUIRED | Distinguish check generation from explicit saving; normalize storage to UTC and label the displayed timezone without changing global timezone configuration |
| Selected symptom IDs **and English labels at capture** | REQUIRED | Preserve meaning after a label changes or master row is removed; IDs alone are insufficient |
| Ordered returned disease IDs and name labels at capture | REQUIRED | Preserve result identity/rank after content/name changes |
| Original result order | REQUIRED | Historical ranking, including original tie order |
| rankingScore, userMatchScore and coverage values | REQUIRED | Preserve the distinct percentages actually used by the result UI; keep the original numeric values rather than recalculating |
| Displayed Symptom Match integer and match-level label | REQUIRED in snapshot | Preserve the browser's existing formatting/level semantics if presentation thresholds later change; do not invent a new confidence measure |
| Matched symptom ID/label list and matched count | REQUIRED | Historical reason context |
| Unselected-associated symptom count and bounded label preview | REQUIRED | Preserve the explanation context without copying the full disease taxonomy; capture the first three missing entries used by the card preview, and use its first two where the original explanation used two |
| Original totalMatches/displayedMatches summary | REQUIRED | Explain that only the returned top ten were saved |
| Plain-text reason/level presentation and existing disclaimer | REQUIRED | Freeze the core historical explanation and educational boundary, not arbitrary client HTML |
| Snapshot format version | REQUIRED | A small integer such as 1 enables stable readers; not an elaborate algorithm-version system |
| Server-generated check key | REQUIRED operational metadata | Link Save to the captured completed response and prevent duplicate saves on retry |
| Count columns for list rows | REQUIRED small projection | Render paginated metadata without loading every JSON payload |
| Actual producer/build identifier | OPTIONAL | Useful if reliably available; not needed to display the frozen result and not an algorithm reproducibility guarantee |
| Disease slug snapshot | OPTIONAL | Current disease links can be resolved by ID; never rely on a stale/reassigned slug as identity |
| Health profile ID | NOT NEEDED | Account-owned interaction, not a profile medical record |
| Entire disease overview, causes, risk factors, diagnosis, prevention, quick facts, severity or urgency notes | NOT NEEDED | They are live educational content, not the saved matching context; avoid turning this into an archive of all public disease cards or introducing historical clinical-urgency display |
| Complete master symptom/disease records; candidate diseases beyond the returned ten | NOT NEEDED | Unnecessary duplication |
| IP/device/location, free notes, diagnosis status, health/risk score, audit hash chain | NOT NEEDED | No demonstrated V1 need |

“Historical snapshot” means the **original selected-input and ranked-result context**, not a pixel/HTML archive of every expanded educational card. The saved detail must explicitly label its disease links as current information. If a current disease no longer exists, keep its captured name and omit the link; do not replace it with a different disease or delete the saved check.

Snapshot format version 1 should contain the selected-label array, response summary, ordered narrow result array and plain presentation/disclaimer metadata. Structural/cardinality/range validation belongs in the service. No second symptom taxonomy or per-disease child table is needed.

## G. Historical Snapshot Decision

**Save the original server-produced matching snapshot; do not recompute on revisit or at Save.**

Algorithm weights, labels, disease/symptom associations, candidate content and percentage formatting can all change. Saving only input IDs and rerunning the matcher would answer what KOBI returns now, not what it returned then. It could also increment search counters accidentally. Retain scores, labels and order from the completed response, together with its timestamp and frozen explanation/disclaimer presentation.

A snapshot preserves historical display integrity; it does not promise that the old computation can be reproduced against today's database. Such a guarantee would need complete versioned data/algorithm archives and is outside V1. An optional build identifier does not change this distinction. Revisit reads storage only; a user deliberately starting a new check creates a new interaction.

**Trusted capture:** extend the existing successful authenticated match path with one bounded server-session pending snapshot and an opaque random check key. Capture the response actually returned, along with trusted selected labels and the neutral presentation metadata. Return the key additively in the response; preserve the existing matching fields and computation. Do not persist a history row automatically. Anonymous matches receive no savable account-owned pending record.

The future saved-check service should own this capture/save handoff. Save POST sends only the key and CSRF token. The service verifies the active account, session-bound owner/key, expiry, limits and successful completed-state metadata, and writes the captured snapshot. Never accept client JSON scores, names, results, owner or a client-created timestamp as authoritative. Failure to capture should leave educational matching usable, but omit/disable Save with a safe message.

Keep **one latest pending check per authenticated session**, expiring after a proposed 60 minutes. A new completed check replaces it; logout destroys the existing application session. An older tab or expired browser-restored result must show “Run a new check before saving” rather than silently save another result. Multi-check pending queues, anonymous-to-user transfer and importing browser storage are deferred. Document this V1 limitation visibly when relevant.

Save retries for the same owner/check key return the already saved owned row; a unique owner/key constraint supports this. Different completed checks remain separate even if their selected symptoms are identical. After deletion, remove any session reference to that saved key so a stale Save action cannot silently recreate the deleted item.

## H. Recommended User Flow

1. Anonymous visitor completes the existing checker unchanged. Show **“Sign in to save checks”**, explaining that V1 requires a new completed check after sign-in. Do not promise automatic preservation through login or change authentication redirects.
2. Authenticated visitor completes a valid check, including a zero-match result. Show **“Save this check”** only when a current server capture/key is available and the selected IDs still match that response. Selection changes, Clear, loading and failed requests disable/invalidate the save control alongside the existing result lifecycle.
3. Explicit Save uses a normal CSRF-protected POST. On success redirect to the owned historical detail and show **“Saved.”** Do not run the matcher or increment counters again.
4. Account → **Saved Symptom Checks** shows date/count metadata and **“View previous result.”**
5. Detail shows **“KOBI symptom-check result — historical”**, **“Checked on”** and **“Saved on”**, captured inputs and ordered original matching context, with the current educational disclaimer and the captured disclaimer if it differs. Disease links are clearly current pages, not captured clinical conclusions.
6. **“Delete saved check”** uses an explicit permanent-deletion confirmation and CSRF-protected POST, scoped to the owner, then returns to the list. This is included in V1 because the interaction is private health-related data.

Individual deletion is sufficient for V1. No account-deletion product path was found in the inspected Account/Auth routes/controllers; do not invent or extend one. The new owner relationship can cascade if account deletion is separately introduced later. Deletion removes the active saved record; do not promise instantaneous removal from infrastructure backups or add a backup-purge subsystem.

Value is recalling prior interactions or keeping reference material for a later conversation, not creating clinician-equivalent history or measuring symptom progression. Retention uplift remains a hypothesis requiring user feedback/measurement.

## I. Dashboard Integration Recommendation

**DEFER Dashboard integration for V1.** Add only the Account navigation entry for Saved Symptom Checks. Preserve the six dashboard areas and current deterministic guidance. Account-owned searches are not selected-profile facts; a new profile card or medical-summary connection would obscure that distinction. A later small account-level link/count can be evaluated after actual use; no new card or guidance item is proposed now.

## J. V1 Scope

| Capability | V1 decision |
| --- | --- |
| Explicit save of a completed authenticated check | INCLUDE |
| Private saved-check list | INCLUDE |
| Historical result detail | INCLUDE |
| Individual owner-authorized deletion | INCLUDE |
| Account navigation entry | INCLUDE |
| Pagination | INCLUDE, 20 metadata rows per page |
| Zero-match completed check | INCLUDE |
| Dashboard card/count/guidance | DEFER |
| Search/filter | DEFER |
| Notes/editing saved results | EXCLUDE |
| Sharing/export/clinician sharing | EXCLUDE |
| Reminders/notifications | EXCLUDE |
| Anonymous-save transfer or multi-tab capture queue | DEFER |

## K. Explicit Non-Goals

No diagnosis history, confirmed conditions, clinician assessments, clinical-record replacement, AI interpretation, symptom trend analysis, risk/health/urgency scoring, treatment or medication recommendations, clinician portal, ABHA/ABDM integration, public/social sharing, notifications, API platform or native mobile work. No matching algorithm/weight changes, master-data rewrites, profile-owned records, automatic condition creation, Medicine/Treatment governance changes or dashboard expansion.

## L. Technical Architecture Recommendation

One focused `SavedSymptomCheckController` → `SavedSymptomCheckService` → `SavedSymptomCheckRepository` using existing BaseRepository parameterized methods is justified: none of the inspected existing persistence classes owns this aggregate. Use the current Container dependency resolution, `$this->view()` and global `redirect()` helpers.

Reuse SymptomCheckerService/SymptomMatcher for the completed live response; never copy matching logic into the new service. Complete the existing `SymptomRepository::findByIds()` stub as a parameterized, bounded batch lookup and expose it through the existing symptom service for trusted labels. Preserve original selected-ID ordering after the batch read. Verify every selected ID resolves; do not omit unknown IDs silently in a saved snapshot. Changes needed for snapshot eligibility must not change scores or the existing public educational output.

The narrow snapshot projection may freeze presentation strings/levels from the existing formatter semantics. This is presentation work, not a second matching algorithm; fixture tests must prove correspondence with current output. Do not add a generic versioning/rules engine. Resolve current disease links in one existing DiseaseRepository batch read of at most ten IDs, without changing historical names/scores.

Proposed routes, for Step 44B only:

- POST `/account/saved-symptom-checks` — explicit save.
- GET `/account/saved-symptom-checks` — owned paginated list.
- GET `/account/saved-symptom-checks/{checkId}` — owned historical detail.
- POST `/account/saved-symptom-checks/{checkId}/delete` — owned deletion.

Use normal server-rendered views for list/detail, and the existing checker JavaScript to populate a normal Save form with the returned opaque key. Render `csrfField()` through the existing view mechanism; add no inline JavaScript. POST/redirect/GET is sufficient; no new JSON save API or API foundation is needed.

## M. Schema Requirement

**YES — one additive table is required**, provisionally `user_saved_symptom_checks`:

| Column / object | Minimum purpose |
| --- | --- |
| `id` | Existing project integer auto-increment key convention |
| `user_id` | NOT NULL, same integer type as `users.id`, foreign key to users |
| `check_key` | Server-generated opaque key, proposed 64 hex characters; never an owner credential |
| `checked_at` | Original server-generated checked time, normalized UTC |
| `saved_at` | Actual server save time, normalized UTC |
| `selected_symptom_count` | Validated metadata count for list rows |
| `displayed_result_count` | Validated metadata count, 0–10 |
| `snapshot_json` | Bounded LONGTEXT/JSON-valid payload following existing project schema conventions; format version and original result summary inside |
| `(user_id, checked_at, id)` index | Owner-scoped stable date ordering |
| Unique `(user_id, check_key)` | Same-check save retry idempotency |
| Owner foreign key | ON DELETE CASCADE, consistent with existing account bookmark ownership |
| JSON-valid / payload-size / count checks | Database backstops plus strict application validation, using existing schema conventions |

Do not add foreign keys from nested symptom/disease IDs to live master rows: those relations would erase historical meaning or prohibit ordinary master-data changes. Owner relationship is the access/persistence relationship; labels and result identity inside the snapshot are historical values. No child-result table, master taxonomy duplication, profile FK, immutable governance triggers or result-update API is needed.

Future DDL must be reviewed against the actual local MariaDB version/schema before deployment. Do not change SQL mode to support it. Prepare schema/migration artifacts in Step 44B only within its authorization; applying them to normal KOBI requires explicit deployment permission. No SQL was authored or executed in Step 44A.

## N. Performance Considerations

- Match returns at most ten results today. Preserve that bound, do not load/store all candidate diseases.
- Proposed save eligibility limits: at most 50 distinct valid selected IDs, at most ten returned results and at most 256 KiB UTF-8 encoded snapshot. These are save-feature limits, not new clinical logic or a restriction on anonymous matching. Reject oversize captures with a safe Save-unavailable explanation; never truncate selected symptoms or silently alter historical percentages.
- Keep matched label lists bounded by selected-input count and missing previews to the three entries actually needed for the existing preview/explanation. Validate master-label lengths against current schema.
- List returns 20 lightweight rows with counts/dates, ordered `checked_at DESC, id DESC`. Validate/clamp page values; fetch one extra row for next-page availability if total page counts are unnecessary. No snapshot payload loading for the entire list and no per-row matcher/master queries.
- Detail reads one owned bounded payload; optional current-link lookup is one batch. Save needs a single trusted batch label read at capture plus a single insert/idempotent lookup; delete is one owner-constrained mutation. No write or search-counter increment on list/detail.
- One bounded pending session item is temporary capture context, not a second history database. No persistent storage for every public check is introduced.

## O. Security / CSRF Considerations

Use existing AuthService identity and Session::verifyCsrfToken for Save/Delete. GET pages remain read-only. Existing match GET still has its known counter side effect; do not call it during save/revisit or broaden this task into endpoint redesign.

Validate identifiers, owned keys, TTL, successful result shape, selected-label resolution, counts, rank order and finite bounded numeric percentages. Build the persisted projection from trusted server capture; ignore/reject client-supplied payload/identity/score fields. Use the unique owner/key constraint for retries and preserve normal transaction/error conventions. Cross-user key/ID requests must reveal no stored data.

Escape snapshot text in server-rendered historical views; never store/render client HTML. Do not interpolate symptom labels into unsafe JavaScript. Use existing CSRF/view/script conventions and safe application-level errors without payloads, SQL text, filesystem paths or clinical details in logs. Do not persist browser sessionStorage/localStorage or allow its restored result object to authenticate a Save.

Hard deletion needs explicit UI confirmation and owner/CSRF validation; deleting a nonexistent/foreign check follows the same safe denial semantics. Saving/viewing must not create public links or use the Medical Summary sharing system. List/detail have private/no-store headers, preserving existing privacy conventions without a project-wide cache redesign.

## P. Implementation Plan for Step 44B

1. Under the next task's authorization, add the minimal table/migration artifact and focused BaseRepository-based repository, service and controller. Complete only the required batch symptom lookup. Deploy normal-database DDL only with separately explicit permission.
2. Capture the authenticated successful response once in bounded server session context; add the opaque Save key, without changing matcher results. Wire a CSRF-protected Save form through existing checker JS and normal POST/redirect/GET.
3. Add the paginated owned list, historical detail/disclaimer, confirmed individual deletion and Account link. Keep Dashboard, health profiles, governance and authentication behavior unchanged.
4. Test owner/anonymous/cross-user denial, CSRF failures, client payload tampering, stale/restored keys, expired/replaced captures, retry idempotency, zero matches, limits, historical preservation after master/algorithm/format changes, pagination and deletion. Verify snapshot label/percentage/reason projection against current frontend semantics; Save/revisit must not call the matcher/counter. Use in-memory fixtures and authorized isolated schema tests, not real health-data seeds.
5. Verify genuine browser save/list/detail/delete at 390×844 and 1366×768 with explicitly authorized disposable interaction records, existing checker lifecycle and relevant regressions; inspect syntax/diff and report deployment/runtime limits honestly.

### Readiness prerequisites and limitations

There is no architectural blocker requiring another governance phase. There are concrete implementation prerequisites: **new table**, **functional symptom-ID batch lookup**, and **trusted capture rather than accepting browser result JSON**. Current schema does not yet support durable saving, and exact DDL compatibility was not tested in this read-only task. The initial result snapshot intentionally excludes copied educational disease content, anonymous-transfer guarantees and multi-tab history capture. These boundaries must be carried into Step 44B.

The review used code/routes/views/schema and existing conventions; it did not perform a fresh browser submission, inspect live user records, run regression suites or modify tests. Retention value and proposed operational bounds are planning decisions, not measured usage findings.

File created: `docs/task-44a-saved-symptom-checks-readiness.md` only. Existing files modified: **NONE**. Database writes: **NO**. Schema changes: **NO**. Application code/routes/controllers/services/repositories/views/tests changed: **NO**. Git mutations: **NO**.

Stop after Step 44A. Step 44B has not begun.
