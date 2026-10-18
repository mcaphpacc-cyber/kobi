# Step 57A — Saved Symptom Checks API Readiness Assessment

Date: 2026-10-17. Readiness only; no Step 57B implementation.

## 1. Executive Summary

**READY WITH NARROW SCOPE.** Recommend Option A: account-owned GET list/detail only, reading the persisted historical snapshot. Defer creation and deletion. No profile association, current symptom matching, current master-label lookup, automatic Conditions linkage, Summary/Dashboard integration, schema change or new authentication mechanism is required.

Current storage contains enough information to reproduce the **saved presentation** without rerunning today's checker: original selected labels/IDs, displayed result labels/order, scores, presentation percentages/match levels, reasons/explanations and disclaimer. It does not store the complete candidate dataset, full original live response or a reproducible algorithm/data-release provenance record. The API must not imply otherwise.

## 2. Current Saved Symptom Checks Implementation

Actual components inspected:

- `database/saved-symptom-checks.sql` and matching section in authoritative `database/structure.sql`.
- `SavedSymptomCheckRepository`, `SavedSymptomCheckService`, `SavedSymptomCheckController`.
- `SymptomCheckerController::match()`, `SymptomCheckerService`, `SymptomMatcher`, `SymptomRepository::findByIds()`.
- Browser routes in routes/web.php; account navigation, saved-check list/detail/error views; symptom-checker save form and global JS save lifecycle.
- Existing saved-check PHP/JS tests and source of schema/recovery/MariaDB harnesses; API conventions through Step 56B; Dashboard/Summary/Conditions boundaries.

Browser routes: GET/POST `/account/saved-symptom-checks`, GET `/account/saved-symptom-checks/{checkId}`, POST `/{checkId}/delete`. GET controller authenticates through AuthService, sets private no-store headers and renders existing views. POST requires CSRF; permanent delete additionally requires confirm_delete=1. Errors use existing allowlisted messages, not internal exceptions.

The Account index links Saved Symptom Checks. It is not integrated into HealthDashboardService or MedicalSummaryService. Symptom Checker displays a Save control only for a captured latest completed response whose selected IDs still match the UI selection. Selection changes/restored stale responses invalidate the control. No automatic database persistence occurs just by matching.

## 3. Actual Database Model

One table, `user_saved_symptom_checks`:

| Field | Actual schema / meaning |
|---|---|
| id | Signed INT auto-increment PK; saved-record identifier |
| user_id | Required signed INT FK to users.id, ON DELETE CASCADE; account ownership |
| check_key | Required ASCII binary CHAR(64), random capture/save nonce |
| checked_at | Required DATETIME, original server result generation instant normalized to UTC |
| saved_at | Required DATETIME, actual save instant from gmdate, UTC |
| selected_symptom_count | Unsigned SMALLINT, cached number of selected symptoms |
| displayed_result_count | Unsigned TINYINT, cached number of displayed results |
| snapshot_json | UTF-8 binary-collated LONGTEXT containing validated JSON |

Unique `(user_id,check_key)` provides account-scoped save retry deduplication. `(user_id,checked_at,id)` supports the list. CHECKs require selected count 1–50, displayed count 0–10, JSON_VALID and payload bytes <=262144. No profile ID, sharing field, IP/device, creator/provider, current clinical status, soft-delete flag or algorithm-version column. No UPDATE operation exists in the application repository; records are read or permanently deleted, not edited. This is application preservation, not a DB immutability trigger, digital signature or tamper-proof audit record.

## 4. Ownership and Authorization

Browser service owner() requires an active authenticated AuthService user and uses AuthService::id(). All repository reads bind user ownership:

- findForUser(id,userId): `WHERE id=:id AND user_id=:user`.
- pageForUser(userId,page): `WHERE user_id=:user`.
- findKeyForUser(key,userId): key **and** owner.
- deleteForUser(id,userId): delete ID **and** owner.

This is already the correct detail SQL boundary; no new ownership model or schema is needed. Future API uses only bearer principal.userId as that owner. Do **not** call findAccessible, accept profile/user IDs from the request, infer rights from shared profile membership or fetch by ID before checking ownership.

57B cross-user proof should use synthetic A/B accounts with disjoint saved records: A list excludes B; A detail for B ID equals unknown-ID 404; B can read its own record; body/query/header/cookie actor values do not select another owner. Assert the SQL predicate contains both IDs and that no matcher/master-data read occurs before or after it.

## 5. Historical Result Persistence

Live browser path is `SymptomCheckerController::match()` → recordSearch(selected IDs) → current checker match → SavedSymptomCheckService::capture(response). Capture first removes prior pending context and only proceeds for an authenticated eligible browser user. The current symptom-ID batch lookup returns id/symptom_en/symptom_hi/slug, but capture persists **English label** only, in selected-ID order. Result disease labels and matched/missing symptom labels come from that server response. They are not trusted client-submitted result arrays.

Persisted snapshot structure:

```text
version: 1
checked_at: UTC Y-m-d H:i:s from response.summary.generatedAt
selected: [{id: integer, label: captured English symptom name}]
summary: {total_matches: integer, displayed_matches: integer}
results: [
  {id: disease ID, label: captured disease_en,
   ranking_score, user_match_score, coverage,
   display_percent: integer, match_level: stored string,
   matched: [{id,label}], missing_preview: up to 3 {id,label}, missing_count,
   reason: stored string array, explanation: stored string array}
]
disclaimer: stored educational/non-diagnostic text
```

Capture freezes existing display percent rounding (`floor(score+0.5)`), one of five match labels, three reason strings and presentation explanation strings. The result array keeps the server-produced displayed ordering; up to ten displayed results are captured. total_matches can exceed displayed_matches; undisplayed candidate results and full missing-symptom labels are not fully retained. No severity/triage/current risk/treatment, clinician finding, model inference or clinical confidence field is stored.

Validated snapshot, random 32-byte hex key, owner and a one-hour expiry are kept in server session `pending_symptom_check`. Save accepts that matching owner/key before expiry, revalidates and serializes using JSON_THROW_ON_ERROR/UNESCAPED_UNICODE/PRESERVE_ZERO_FRACTION. It persists checked_at/count mirrors plus saved_at. Unique user/key and duplicate-key retry lookup return the same saved record. No client result payload is accepted by browser store.

## 6. Historical Integrity / Recalculation Risk

Current get(id) runs owner-bound findForUser, bounds stored JSON bytes, decodes at depth32 and runs existing validate(). It adds the decoded snapshot, removes snapshot_json/check_key/user_id and returns it. It does **not** call match(), recordSearch(), getSymptomsByIds(), fetch current disease labels or recompute percentages/reasons. Browser detail renders stored strings and order.

The validator requires version1 and the expected disclaimer, selected/result list bounds, nonempty bounded labels, positive integer nested IDs, valid checked_at timestamp, unique selected IDs, summary/result count consistency, finite 0–100 scores, valid presentation percent/match-level, bounded reason/explanation/matched/preview lists and selected membership of matched IDs. JSON/schema CHECKs alone do not enforce those semantic rules. Extra JSON keys are not categorically rejected by current validation; an API must whitelist every nested output field rather than return the decoded object blindly.

Existing tests mutate current symptom labels and the original live result after saving and verify the snapshot is identical. Test checker match()/recordSearch() throw if invoked, so successful historical get is evidence that it does not recalculate or increment counters.

**Yes, API retrieval can avoid recalculation completely.** Add a narrow principal-aware reader to the existing service using findForUser(id,principal.userId), factoring the current decode/validate/sanitize read block for shared browser/API use. An API-specific service projects that result. Existing browser owner/session checks remain on browser methods; no bearer path may call them as an authentication fallback.

Do not re-resolve labels, re-sort results, derive display percent from raw ranking_score, regenerate match levels/reasons/explanations, translate via current master data, refresh links from current disease records or write snapshots on GET. Malformed/unsupported stored payload must fail safely, not become an empty successful result or trigger a fresh assessment.

Provenance actually present: server generatedAt converted to checked_at, save UTC timestamp and **payload schema version 1**. Absent: checker/algorithm version, dataset version/hash, source revisions, separate capture timestamp and signed provenance. Version1 is not an algorithm-release claim. Selected labels reflect server lookup at capture, not proof of the exact labels first shown during an earlier client search. These limits are acceptable for read-only retrieval of the stored artifact.

## 7. Symptom Checker Boundary

Matching remains the existing live web interaction. That route increments symptom search counters and creates optional session pending context; it must **not** be invoked during API saved-record GET. The service's checker dependency is used for capture, not historical retrieval.

No `/api/v1/symptom-checker`, new matching request, save-context exchange or score calculation is proposed. Saved reads are historical educational Symptom Match output, not a diagnosis, confirmed condition, current symptom report, current disease/risk state, treatment suggestion or advice. Keep captured disclaimer and match terminology. Existing JS function names or underlying checker wording are not changed by this assessment.

## 8. Candidate API Routes

Recommend Option A only:

- GET `/api/v1/saved-symptom-checks`
- GET `/api/v1/saved-symptom-checks/{savedCheckId}`

Top-level authenticated account-owned collection is correct. A `/health-profiles/{profileId}/...` route would falsely attach records to a profile and could improperly grant shared-profile members access. No aliases, share tokens, history-union or extra resource abstraction.

Option B adds destructive deletion and mobile retry/confirmation decisions; defer. Option C additionally needs trusted API capture/save provenance, which browser session-key capture does not provide to a session-independent client. These optional writes are excluded, not reasons for another readiness phase.

## 9. Proposed API Projection

List data is an array of metadata objects with exactly:

```text
id: decimal string
checked_at: UTC RFC3339 string (YYYY-MM-DDTHH:MM:SSZ)
saved_at: UTC RFC3339 string
selected_symptom_count: integer
displayed_result_count: integer
```

Detail data contains those same five fields plus exactly one **controlled** snapshot object:

```text
snapshot: {
  version: integer (stored payload version, currently 1),
  checked_at: UTC RFC3339 string,
  selected: [{id: decimal string, label: stored string}],
  summary: {total_matches: integer, displayed_matches: integer},
  results: [{
    id: decimal string (historical disease reference),
    label: stored string,
    display_percent: stored integer,
    match_level: stored string,
    user_match_score: stored number,
    coverage: stored number,
    reason: stored string array,
    explanation: stored string array
  }],
  disclaimer: stored string
}
```

This is an explicit nested whitelist, **not raw snapshot_json or automatic serialization of the entire decoded payload**. It preserves the current saved browser presentation while omitting redundant matcher internals (ranking_score, matched, missing_preview, missing_count). No future API recomputation of any retained field. Selected/result/reason/explanation ordering is preserved. All returned IDs, including nested ones, follow decimal-string convention. Numeric scores stay numeric; no percentage string formatting or clinical-confidence renaming. Labels and text are plain stored content; clients must render safely as text.

UTC formatting follows existing API auth timestamp convention, interpreting source DATETIME under its actual UTC contract, not application timezone or a newly generated instant. Existing browser keeps its present timestamp formatting. Detail should confirm row checked_at/count mirrors agree with validated snapshot checked_at/array counts before returning both; current browser get validates the snapshot but does not explicitly compare those row mirrors. Any discrepancy should fail safely in the API, without repair/recalculation. No live symptom/disease reference resolution or new link field is required.

## 10. Field Privacy Classification

All symptoms, matches and associated dates are sensitive user health-interaction data even when included. Inclusion means **authenticated owner only**.

| Field/category | Actual source | Historical? | Sensitive? | V1 decision / reason |
|---|---|---|---|---|
| Saved id | row.id | Yes | Yes | INCLUDE string; identifies this saved artifact |
| checked_at | row and snapshot | Yes, generated instant | Yes | INCLUDE; not symptom onset/current assessment time |
| saved_at | row.saved_at | Yes, save instant | Yes | INCLUDE, distinct from checked_at |
| selected_symptom_count / displayed_result_count | row count mirrors | Yes | Yes | INCLUDE metadata; no blob needed for list |
| version | snapshot.version | Yes, payload schema | Internal but nonsecret | INCLUDE; do not label algorithm/dataset version |
| selected[].id | snapshot selected IDs | Yes | Yes | INCLUDE string, no current-master lookup/FK dependency |
| selected[].label | captured symptom_en | Yes | Yes | INCLUDE original captured label; no current translation |
| summary.total_matches / displayed_matches | stored summary | Yes | Yes | INCLUDE original counts; undisplayed results not invented |
| results[].id / label | captured disease ID/disease_en | Yes | Yes | INCLUDE strings; ranked matches, not actual user Conditions |
| display_percent / match_level | frozen display data | Yes | Yes | INCLUDE exact saved presentation as Symptom Match |
| user_match_score / coverage | original numeric result | Yes | Yes | INCLUDE displayed historical overlap/coverage, not probability/risk |
| reason / explanation | frozen presentation strings | Yes | Yes | INCLUDE stored text only; never regenerate/upgrade to advice |
| ranking_score | stored raw ranking number | Yes | Internal matching | EXCLUDE; display_percent and original array order already capture public presentation |
| matched / missing_preview / missing_count | stored IDs/labels/truncated internal collections | Yes | Yes | EXCLUDE raw internal arrays; frozen display strings retained |
| disclaimer | captured educational disclaimer | Yes | No personal content | INCLUDE, preserve stored scope |
| severity/diagnosis/current risk | Not stored in snapshot | No | Potentially clinical | EXCLUDE; do not invent or infer from match_level |
| user_id | row owner FK | Administrative | Yes | EXCLUDE; principal determines it |
| check_key | capture/save nonce | Internal save provenance/deduplication | Secret-like operation context | EXCLUDE, never an API read credential/share token |
| snapshot_json / unknown extra keys | raw blob/storage | Mixed | Potentially any | EXCLUDE raw data; whitelist known fields only |
| profile ID/demographics/membership | No saved-record association | N/A | Yes | EXCLUDE, no profile joins |
| IP/device information | Not stored in this table/snapshot | N/A | Yes | EXCLUDE, no API-auth/session joins |
| sharing/access metadata | Not part of saved feature | N/A | Yes | EXCLUDE |
| credentials/authentication/token data | Separate auth infrastructure | N/A | Secrets | EXCLUDE |
| new algorithms/dataset provenance, richer current links/translations | Not persisted | No | Mixed | DEFER; do not fabricate provenance or revise old content |

## 11. Pagination

Browser currently accepts page only (controller integer filter/default1); service/repository clamp 1–100000. Page size is fixed20, no page_size parameter/max-size control. Repository fetches **21** lightweight metadata rows, service returns20 plus has_next. It does **not** issue COUNT or return total. No snapshot JSON is loaded by the list.

Recommend API page/page_size matching Steps 55–56: page default1/max10000, size default20/max50, strict scalar decimal UTF-8 input; reject invalid/unknown values using established 400/422 conventions. Add only narrow API owner-bound metadata list/count methods to the existing repository; keep browser fixed20/has_next implementation unchanged. Response meta: api_version v1, page, page_size, total, has_more. Empty and beyond-final pages return []. Never load or repeat snapshot payloads in a paginated list.

This is reuse of standardized API semantics, not a browser pagination change. No unlimited list, body actor/filter, arbitrary sort or full-snapshot collection mode. Concurrent saves/deletes can shift offset pages; no immutable pagination snapshot guarantee.

## 12. Ordering

Current SQL order is **checked_at DESC, numeric id DESC**. It is result-generation time first, not saved_at or an inferred clinical date. ID already breaks timestamp ties deterministically. Preserve exact order; do not re-sort by save time or score. Detail results preserve stored array order, including original ties; do not apply a new ranking/ID tie-breaker inside historical results.

## 13. Create Operation Assessment

Defer POST/save from API V1. Current trusted browser flow captures only the latest server result into an owner-bound expiring session snapshot, then saves by opaque key. Client POSTed result arrays/owner/profile are ignored. The one-hour capture expiry and user/key uniqueness support browser retry safety but are not a bearer-native capture/provenance protocol.

A bearer client cannot safely submit arbitrary JSON or only symptom IDs and call that the original saved result. Rerunning the current checker on save would create a new assessment; replaying stale/client-made output would bypass current trusted context. No algorithm/dataset version is persisted to establish stronger replay provenance. Keep the existing browser save path unchanged; no new result signing, session bridge, token, schema or matching endpoint is needed for read-only 57B.

## 14. Delete Operation Assessment

Browser delete is a permanent owner-bound SQL delete, protected by authentication, CSRF and an explicit confirmation checkbox. It also clears matching pending session context so the deleted capture cannot immediately be saved again from that pending key. There is no soft-delete/undo/archive.

SQL predicate is already safe for ownership. DELETE could be independently scoped later, but first API value comes from reading saved history. Mobile retry status for an already-absent record, user confirmation/accidental deletion handling and interaction with browser pending context need explicit write scope. Defer DELETE in V1, without weakening the existing normal browser deletion or creating another readiness phase.

## 15. Authentication and Authorization Reuse

Existing Step 50B Bearer guard/principal, current account eligibility, access expiry/revocation, non-logout limiter, session-independent bootstrap and generic 401 semantics are sufficient. A principal-aware saved-read method takes the authenticated account ID exclusively from principal. No additional role/grant/profile-membership prerequisite, JWT/API key/CORS/browser-login fallback/mobile proof or new token.

Current `.htaccess` forwards only explicit auth/me/profile-nested resource paths. It does not cover the proposed top-level saved-symptom-checks paths. 57B will need one narrow alternative matching exactly the account collection/single-ID path, with normal ID validation, rather than broadening all /api/v1 routes. No forwarding/config/auth changes were made during readiness.

## 16. API Response / Error Conventions

Use PublicApiResponse success/data/meta and error/code/message/meta, JSON UTF-8, decimal-string IDs and private no-store/nosniff. Existing paginated controller response support can serve the new list; detail retains meta api_version only.

No bearer/invalid/expired/revoked: generic 401/Bearer challenge before record disclosure. Invalid savedCheckId: 422 using existing positive decimal signed-INT convention. A missing or another user's saved row: **identical 404 NOT_FOUND**, not 403 or an owner-detail error. Empty owner list: 200/[]. Invalid query/paging follows established 400/422; detail accepts no query parameters. Methods other than GET: existing 405/Allow GET and empty HEAD body; no CORS/OPTIONS grant.

Owned malformed/unsupported-version/disclaimer-invalid payload or row/snapshot mismatch: safe existing INTERNAL_ERROR 500 with no parser detail. Do not return newer results, repair/delete data, silently drop rows or invent a new unavailable snapshot. This API handling need not change the browser's existing safe unavailable-record message/400 behavior. Account eligibility failures remain auth failures, not snapshot errors. Existing 429/Retry-After and fail-closed 503 remain.

Existing response ceiling is 262144 bytes; stored snapshot limit is also 262144 bytes. Whitelisting removes internal arrays, but the API adds metadata/envelope and converts nested IDs to strings. Do not assume a storage-size check proves response-size safety. 57B should test near-limit projected responses under the existing ceiling and preserve safe failure rather than truncate historical strings/results or increase the global limit. This is a bounded-response limitation, not a schema prerequisite or a reason to rerun matching.

## 17. Cache and Rate-Limit Considerations

Saved results are private owner health data: Cache-Control no-store on all success/errors, no public/shared/application cache or ETag-based history refresh. Reuse existing authenticated family/failure limiter once per request; no feature-specific limiter. Existing private limiter accounting may write its normal counter file, not saved-check data or symptom search counters.

GET must not create capture/session/save keys, change latest browser pending context, update saved rows, increment searches, create shares or append access logs. Reads never call capture/save/delete or current matching. No cookies/session requirement for bearer API.

## 18. Database / Schema Assessment

**No schema changes required.** Existing owner FK, user/key UNIQUE, list index and JSON/count constraints support the scoped reads. Add API repository methods only for flexible bounded metadata list/count; existing findForUser already enforces the correct detail predicate.

Read-only normal inspection confirms **0 saved rows** and null checked/saved date ranges. Relevant counts: users12, profiles14, members17, medicines3, conditions4, slots0, checkoffs0, existing API families11, refresh history21, active API families0. Baseline/final safe counts, ten normalized SHOW CREATE digests, aggregate dates and session/global SQL modes match. Mode remains `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`.

Actual saved-check DDL matches approved artifact after accounting for MariaDB's next AUTO_INCREMENT value, integer display widths, whitespace and keyword/CHECK-function casing. Initial literal comparison differed on those presentation details; no structural discrepancy was found. Runtime before/after digests retain actual schema definitions and exclude only next AUTO_INCREMENT values, not constraints or indexes. No sensitive row contents/snapshots were queried or hashed.

No normal data/DDL write, production fixture, API-family lifecycle, browser session, matcher submission or disposable database was used for this readiness task.

## 19. Relationship to Conditions and Medical Summary

Saved symptom matches are not diagnoses, recorded Conditions or current health-profile records. Do not automatically create/link a health condition, grant a profile member access to another user's checks, or put saved result detail under a profile route. The disease-reference ID inside a snapshot is historical result identity, not a health_conditions ID.

Saved checks remain independent from live Medical Summary aggregates, Medication Schedule/Due Today, medicines and Dashboard. Account navigation already exists; there is no need to add summary/dashboard counts or a new browser UI. Existing public APIs/search and current symptom checker are unchanged.

## 20. Validation Performed

Source/schema investigation included all components above, ownership SQL, save nonce/session expiry, serialization/validation, counter side effects, original presentation capture, list/detail/delete views/forms, API envelopes/limits and existing test harnesses.

All unchanged database/network-free suites passed during 57A:

| Suite | Passed checks |
|---|---:|
| saved-symptom-checks.php | 63 |
| saved-symptom-checks.cjs | 26 checker lifecycle/presentation checks |
| api-auth.php | 103 |
| api-auth-rate-limit.php | 13, own private files removed |
| api-identity.php | 44 |
| api-health-profiles.php | 20 |
| api-medications.php | 28 |
| api-medical-summary.php | 29 + reused Dashboard76 |
| api-health-conditions.php | 65 |
| api-medication-schedules.php | 71 + reused browser schedule52 |
| health-dashboard-guidance.php | 76 guidance + rerun Dashboard dependency |
| public-api-v1.php | 56 |
| public-api-v1-bootstrap.php | 8 |
| public-search.php | 30 |

Saved-check suite covers original score/percent/reasons/explanations, master/result changes without refreshing history, owner-bound list/detail/delete/save, idempotent browser save, invalid/expired/stale pending context, zero results, corrupt payload, pagination/ties, CSRF/delete confirmation, anonymous redirects, SQL owner predicates, lightweight list and bound symptom lookup. JS suite uses a fake fetch in a VM: no real matcher endpoint/network/counter mutation.

PHP lint: **10 files PASS** (saved repository/service/controller, checker controller/service/algorithm, symptom repository, saved list/detail views and API controller). No implementation tests created or changed. No new browser/HTTP/API behavior is claimed tested during readiness.

Legacy schema verifier was inspected but **not run**, because its default verification hashes full sensitive rows across normal tables. Legacy MariaDB/recovery harnesses were also inspected but not run: they require historical recovery state and can create schema/write artifacts. Existing in-memory tests and SELECT-only schema checks suffice; no isolated DB was necessary. Historical 44B browser/MariaDB acceptance is background evidence, not a fresh 57A execution result.

File-hash inventory confirms only this readiness report was added, no existing application/test/docs/config/schema file changed or deleted. No Git commands used.

## 21. Risks / Limitations

Production saved data is empty, so populated production reads were not demonstrated or seeded. Existing fixtures demonstrate historical behavior and ownership. Safe aggregates do not prove byte-for-byte equality of every production row, but no normal mutations were performed.

Snapshot version is only payload schema version, with no algorithm/dataset release provenance. It reproduces captured displayed history, not an entire source dataset/live response. Unknown versions or changed required disclaimers can make current validator reject stored history; never substitute a fresh check. Raw unknown JSON keys are not suitable API output. No clinically verified status should be inferred from saved scores or strings.

Current read service resolves browser actors, so a principal-aware read seam is necessary; constructing it alone does not authorize bypassing ownership. Row metadata mirrors are not fully cross-checked by current browser get; recommend a cheap API consistency check. The existing response-size ceiling may reject a near-limit projected artifact rather than truncate it. None requires schema mutation, another readiness phase or algorithm changes.

## 22. Recommended Step 57B Scope

Implement **only** GET collection/detail routes in section 8, exact section 9 projections, principal.userId-bound SQL, UTC timestamps and preserved snapshot array ordering/content. Default20/max50/page max10000 and checked_at DESC/id DESC. Defer POST/save/DELETE, current matching, profile association, sharing and any other feature. Schema: **None**.

Expected files for planning only:

- Existing app/Controllers/ApiAuthController.php: thin list/detail using existing admittedBearer/error/paginated helpers, no duplicate auth.
- New app/Services/ApiSavedSymptomCheckService.php: account-owned metadata paging, strict ID/parameter handling, explicit nested historical DTO, timestamp/type conversion and mirror consistency checks.
- Existing SavedSymptomCheckService: narrow principal-aware detail reader sharing the original bounded decode/validation/sanitize block; keep browser owner/capture/save/delete unchanged. Do not call the matcher or label lookup from the API read path.
- Existing SavedSymptomCheckRepository: bounded owner-only API metadata list/count; reuse existing owner-bound findForUser for detail, optionally with explicit required columns. Preserve browser methods.
- routes/web.php and a narrow public/.htaccess alternative for the two top-level paths; authenticated API contract docs; focused tests/implementation report. Existing constructor fixture wiring only if necessary.

57B tests: A/B owner isolation/unknown-ID parity with SQL ID+user binding; no profile-membership authorization; no token/invalid/expired/revoked and genuine principal selection; list metadata only, paging/default/max/beyond-end/tied timestamps; exact root/nested fields and string IDs; zero results, full captured presentation and stored order; labels/scores/reasons unchanged after master-data changes; matcher/recordSearch/label lookup configured to throw on historical reads; malformed/unsupported/oversized snapshots and near-response-limit handling; row mirrors; no cookies/session/mutations/counters/shares/logs; limiter/no-store/safe errors/methods; isolated real MariaDB and verified HTTPS with cleanup, normal anonymous Apache denial where practical. No production fixture/credential family is implied by this recommendation.

Run relevant existing saved/checker lifecycle, auth/identity/profile/medication/summary/condition/schedule/public/Dashboard regressions unchanged. API implementation does not require changing those features' behavior. No additional discovery/governance phase is recommended.

## 23. Final Readiness Decision

**READY WITH NARROW SCOPE.** Owner-only historical saved-check list/detail can proceed directly to separately authorized Step 57B. Persisted original result is the source; **no current Symptom Checker recalculation or rewrite of history**.

Only docs/task-57a-saved-symptom-checks-api-readiness.md created. No normal production data/schema/config/authentication changes or Git operations. **Step 57B NOT IMPLEMENTED. Step 58 NOT STARTED.**
