# Step 44B — Saved Symptom Checks V1

Date: 2026-10-13. Status: **Step 44B PASS — COMPLETE**. Deployment, save/list/detail, confirmed browser deletion and final cleanup verification passed.

## Deployment boundary

The initial deployment was blocked by the sandbox and automatic approval review before any DDL ran. The user then explicitly authorized backup/recovery verification and the one-table deployment. No rejected action was bypassed. Work resumed at the deployment boundary rather than restarting completed implementation.

Created a private, complete schema/data backup and successfully restored it to `kobi_saved_checks_recovery_20261013`. Fidelity verification matched all **57 tables, 1 view, 15 triggers and all rows**, including generated values and trigger creation contexts, with normal KOBI unchanged. Recovery inserts explicitly omit generated columns and create triggers after restoring data. SQL mode was never changed; isolated restoration temporarily disabled foreign-key checks only in the disposable copy before full schema/data verification. No routines/events existed; the verifier would stop if they did.

The retained backup is `tests/.saved-checks-recovery/before.bin` (11,295,336 bytes), SHA-256 `a533ab2b31b438f0c9215a1069c3b1a2ba4c952cdeee5ba11dd2000e52e26050`. Its certificate is `tests/.saved-checks-recovery/verified.json`; HTTP access is denied by that directory's `.htaccess`. The serialized backup contains the complete original definitions, columns, typed row values, view/trigger definitions and creation contexts. The tested recovery procedure is recorded in `tests/saved-symptom-checks-recovery.php`; restoring normal KOBI would require separate explicit authorization. Backup contents remain private locally.

After verifying the certificate, artifact hash and unchanged normal checkpoint, executed exactly **one CREATE TABLE** from the approved artifact on MariaDB **10.4.27**. No warnings occurred. Post-DDL fingerprints confirmed all preexisting objects/rows and triggers unchanged. Normal inventory is now **58 tables, 1 view, 15 triggers**. Session/global SQL mode remains `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`. The disposable recovery database was removed after isolated tests; the verified backup was retained.

The user completed genuine sign-in. Authenticated Save/list/detail verification passed. The single normal browser-created Fever + Cough test check (saved ID 1) was subsequently permanently deleted through the normal UI after explicit action-time confirmation.

## Implemented routes and ownership

- POST `/account/saved-symptom-checks`: save the trusted latest completed check.
- GET `/account/saved-symptom-checks`: owner-scoped paginated metadata list.
- GET `/account/saved-symptom-checks/{checkId}`: owned stored historical detail.
- POST `/account/saved-symptom-checks/{checkId}/delete`: confirmed owned deletion.

Ownership comes from AuthService::id(), with genuine active-account authentication checks. Every repository retrieval/list/deletion is constrained by `user_id`; no public saved-check route or health-profile relationship exists. A foreign/missing record has the same safe not-found response. Save/Delete use existing Session CSRF verification and csrfField forms. Deletion additionally requires an explicit permanent-delete checkbox. Private Account responses and the save-aware match response use no-store headers.

## Trusted handoff and historical snapshot

The current match endpoint still calls recordSearch once and the existing matcher once, returning its original summary/results. It additionally asks SavedSymptomCheckService to capture a narrow authenticated snapshot in the existing session and returns an opaque save key. Anonymous matches get no account-owned pending capture. Capture does not insert a database row; failures make Save unavailable without changing the match computation.

Save accepts only the opaque key and CSRF input; posted owner/profile/result data is not used. The key must belong to the current account/session's completed capture, be current and unexpired. One latest pending capture is retained for up to one hour. A replaced/expired/browser-created key fails safely. A unique owner/key pair supports repeated-submit idempotency. Different completed operations can save distinct records. Deleting a pending saved item clears its corresponding session context to prevent accidental recreation.

Snapshot format 1 stores:

- UTC checked timestamp, original selected symptom IDs and trusted English label snapshots.
- Original total/displayed match counts, including valid zero-match outcomes.
- Returned result order, disease IDs and historical names.
- Original rankingScore, userMatchScore and coverage; JSON preserves floating-point representation where applicable.
- The existing displayed rounded Symptom Match percentage and match-level label, frozen at capture.
- Matched symptom labels, bounded unselected-associated previews/count, and frozen plain-text reason/explanation strings matching the existing frontend presentation.
- The existing educational/non-diagnosis disclaimer.

Historical rendering reads this snapshot only. It never calls the matcher, increments counters, regenerates reasons or loads current disease content. No medical content archive, severity/urgency output, profile attachment, condition creation, notes or scoring system was added. This V1 has no disease links on historical detail, avoiding stale/reassigned link identity; current content lookup is not required by the scoped acceptance criteria.

The functional parameterized SymptomRepository::findByIds replaces the prior stub and is exposed through SymptomCheckerService for trusted capture labels. No second symptom taxonomy exists. Capture validates all selected IDs resolve; limits are 50 distinct symptoms, ten results and 256 KiB UTF-8 JSON. Retrieval safely handles malformed/unsupported snapshots and permits deleting such records from the list.

## Schema artifact

Exactly one additive table is defined in `database/saved-symptom-checks.sql` and appended verbatim to authoritative `database/structure.sql`: `user_saved_symptom_checks`.

Columns: id, user_id, check_key, checked_at, saved_at, selected_symptom_count, displayed_result_count, snapshot_json. It has an integer primary key, unique owner/key, indexed owner/time/id listing, users ownership FK with ON DELETE CASCADE, count-range checks, JSON validity and payload-size checks. No unrelated table definition was changed and no SQL-mode directive was added. Live DDL/metadata verification passed; isolated MariaDB tests exercised the actual CHECK/FK/unique rejections.

`tests/saved-symptom-checks-schema.php` is CLI-only. Default mode verifies read-only metadata. Its explicit `--deploy-normal` mode now requires the verified backup hash, recovery certificate, artifact hash and unchanged normal checkpoint. It guards normal database identity and one-statement scope, requires absence of the new table, executes only CREATE TABLE, rejects warnings and fingerprints all existing table/view data/definitions and triggers before/after. It checks SQL mode, columns/constraints/engine and row count. No drop/reset/redeploy or normal data seeding is possible.

## UX and navigation

The existing checker adds “Save this check” only for authenticated completed state with a current matching-selection key; selection edits, Clear, loading, unavailable captures and mismatched/restored states disable/hide Save appropriately. POST/redirect/GET shows “Symptom check saved.” No new save API platform is introduced.

Anonymous visitors get “Sign in to save checks,” with clear wording that a fresh completed check after sign-in is required. There is no automatic anonymous transfer. Account navigation gains Saved Symptom Checks. Dashboard, Guided Dashboard and all existing six areas/rules are unchanged.

The list reads 21 metadata rows for 20-per-page plus next-page availability, ordered checked_at DESC/id DESC, without loading the JSON history. Detail shows stored labels, original percentages/reasons, checked/saved UTC timestamps, historical framing and disclaimer. Individual delete is available in detail and list, including for corrupt snapshots.

Browser verification exposed the previously cached checker script. A filemtime query version was added only to the existing checker script reference in the layout. The global asset helper and other asset references were not changed. After refresh, the new anonymous controls worked.

## Files created

- `database/saved-symptom-checks.sql`
- `app/Repositories/SavedSymptomCheckRepository.php`
- `app/Services/SavedSymptomCheckService.php`
- `app/Controllers/SavedSymptomCheckController.php`
- `app/Views/account/saved-symptom-checks.php`
- `app/Views/account/saved-symptom-check.php`
- `app/Views/account/saved-symptom-check-error.php`
- `tests/saved-symptom-checks.php`
- `tests/saved-symptom-checks.cjs`
- `tests/saved-symptom-checks-schema.php`
- `tests/saved-symptom-checks-recovery.php`
- `tests/saved-symptom-checks-mariadb.php`
- Private recovery artifacts: `tests/.saved-checks-recovery/{before.bin,verified.json,.htaccess}`
- `docs/task-44b-saved-check-desktop.png`
- `docs/task-44b-saved-check-mobile.png`
- `docs/task-44b-final-cleanup.png`
- `docs/task-44b-saved-symptom-checks-implementation.md`

## Files modified

- `database/structure.sql`: one additive table artifact appended.
- `app/Repositories/SymptomRepository.php`: complete required batch-ID lookup stub only.
- `app/Services/SymptomCheckerService.php`: expose batch label lookup, matching unchanged.
- `app/Controllers/SymptomCheckerController.php`: trusted capture handoff and private response cache header.
- `routes/web.php`: four private Account routes and controller import.
- `app/Views/account/index.php`: existing Account sidebar entry.
- `app/Views/symptom-checker/index.php`: normal Save/sign-in controls.
- `public/assets/js/symptom-checker.js`: save-control state lifecycle, no matcher/ranking changes.
- `app/Views/layouts/app.php`: checker-only asset version for integration.

## Tests and results

All database-free checks passed:

| Test | Result |
| --- | --- |
| `php tests/saved-symptom-checks.php` | **63 new checks** |
| `node tests/saved-symptom-checks.cjs` | **26 new checker lifecycle/presentation checks**, no network/DB |
| `php tests/health-dashboard-guidance.php` | **76 guidance + 76 unchanged dashboard checks** |
| `php tests/health-profile-validation.php` | **57 checks + 4 redirect scenarios** |
| `php tests/health-medicine-dates.php` | **26 checks** |
| `php tests/auth-phase1.php` | **47 scenarios** |
| `php tests/saved-symptom-checks-mariadb.php` | **17 isolated MariaDB checks**, all fixtures rolled back; its repeated 63-check harness is not counted twice |

Total: **392 distinct checks/scenarios**, including **89 new database-free checks and 17 new MariaDB checks**. Existing tests were not weakened or changed. The first isolated-test attempt stopped at a test-helper loading conflict before database operations; the test bootstrap was corrected and all assertions then passed without weakening them.

New coverage includes anonymous/foreign owner denial, trusted completed capture, arbitrary browser-result/owner fields ignored, no automatic DB save, invalid/unknown/duplicate input, score bounds, limits, original label/score/reason preservation after fixture changes, zero matches, expired/replaced keys, duplicate Save idempotency, owner deletion, post-delete stale Save rejection, malformed history, paginated ordering, production SQL ownership predicates, no-JSON list projection, CSRF and delete confirmation, all anonymous Account endpoints, schema artifact scope, functional batch lookup, lifecycle invalidation, frontend match-level boundary/reason parity and no save/revisit matcher/counter calls.

PHP syntax passed for all **17** created/modified PHP files, including the recovery and MariaDB helpers. `node --check public/assets/js/symptom-checker.js` and `git diff --check` passed. Final scoped edits were inspected. Existing regression results were retained rather than unnecessarily rerunning completed unchanged work.

## Browser results and remaining acceptance

Anonymous checker verification succeeded: selected Fever + Cough, received ten result cards and the existing disclaimer, neutral Symptom Match wording and a visible sign-in prompt with no Save button. The first attempt exposed the cached script; a necessary fresh completed run after the checker-only asset fix verified the new controls. These ordinary live match requests use the existing symptom search-counter behavior; no manual counter write or change to counter semantics occurred.

At 390×844 and 1366×768, anonymous checker entry and authenticated list/detail layouts had no horizontal overflow or clipped controls. Console warnings/errors: none observed. Screenshots of the genuine historical detail were saved in the two files listed above.

The genuine authenticated user completed **one** fresh Fever + Cough run after deployment. Save was enabled only after the response completed. Normal form submission redirected to the historical detail with “Symptom check saved.” The saved result showed ten matches, the same labels in the same order and displayed percentages as the original live result. Revisit through Account → Saved Symptom Checks → View previous result succeeded, without rerunning matching. The existing disclaimer and historical framing remained visible. The Account profile form/navigation remained intact; Dashboard files and guidance were not modified.

Genuine browser permanent deletion and final zero-saved-test-row verification passed after confirmation. Cross-user denial passed against the actual repository/service in isolated MariaDB and in database-free tests. A second live account was not impersonated or used to manufacture foreign saved data; this is a limitation of the literal two-account browser scenario, not a missing ownership test.

Only saved check 1 was created in normal KOBI, and that temporary row has now been deleted. All isolated synthetic fixture writes were rolled back and the disposable recovery database was removed. No saved-check test rows remain.

The read-only comparison before final cleanup against the retained pre-deployment backup also passed: all original tables, rows, columns, view and triggers remained identical except the expected **+1 search_count** for selected symptom IDs **1 and 47** from the single authenticated test run. At that checkpoint the new table contained exactly the one browser test row. No unrelated original data changes were found.

### Final cleanup — 2026-10-13

Verified saved check 1 was the sole documented temporary artifact: Account 1, Fever + Cough, ten results, matching the prior exact row fingerprint. It was not required seed data or a schema object, and no other table referenced it. After genuine sign-in and the user's explicit permanent-deletion confirmation, used the existing confirmation checkbox and CSRF-protected Delete form to permanently delete only that row.

The browser redirected to the saved list with a deletion notice; reload showed the normal empty state and zero entries, with no console warnings/errors. Independent read-only database verification confirmed **zero saved checks**. Full pre/post database/schema fingerprints matched with only the approved row removal excluded, including the authorized table definition and SQL modes; checked application/Auth/Dashboard/routes/structure.sql file hashes also matched. No unrelated production data, schema or application behavior changed. No tests, application code or Git state were modified for cleanup. The previous 392-check results stand; no unnecessary full regression rerun was performed. Final screenshot: `docs/task-44b-final-cleanup.png`.

## Final boundary

- Ownership: **USER**, not Health Profile.
- Historical integrity: **PASS in fixtures and genuine browser persistence/revisit**.
- CSRF: **PASS in controller tests and genuine authenticated Save/Delete flows**.
- Dashboard unchanged: **YES**.
- Database writes: **YES** — one normal additive table, existing checker counter activity, one genuine UI saved check, and isolated backup restoration/test fixtures. No unrelated normal data writes occurred.
- Schema changes: **YES** — exactly one additive normal table; source artifact and structure.sql agree. Isolated recovery/test schema was cleaned up.
- Application data changes: **YES**, only existing normal sign-in metadata/counters and the authorized save/delete of the temporary test check; no saved test rows remain and no existing health-record/content/governance changes occurred.
- Authentication behavior changed: **NO**.
- Disease References/Detail/content, Treatment/Medicine governance, Health Profile, Medications, Documents, Medical Summary and Saved Diseases behavior changed: **NO**.
- Git mutations: **NO**.

The initial automatic-review deployment rejection was resolved through the user's explicit authorization and verified recovery proof. Final UI permanent-deletion confirmation and cleanup are complete. **Step 44B PASS — COMPLETE.** Stop after Step 44B; no further feature or governance phase was started.
