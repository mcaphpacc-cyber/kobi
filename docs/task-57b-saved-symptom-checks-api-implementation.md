# Step 57B — Authenticated Saved Symptom Checks API

Date: 2026-10-17. Scope: Step 57B only; Step 58 not started.

## 1. Implementation Summary

Added the two approved account-owned GET resources, using existing Bearer admission, the persisted historical snapshot and an explicit API projection. No create/delete/update/history/matching API was introduced. The API reads the persisted historical snapshot rather than recalculating current results.

Controller → ApiSavedSymptomCheckService → existing saved-check service/repository → database. Existing browser get now shares its unchanged bounded decoder/validator with a principal-aware historical reader. Browser owner checks and capture/save/page/delete semantics remain unchanged.

## 2. Routes

- GET `/api/v1/saved-symptom-checks`
- GET `/api/v1/saved-symptom-checks/{savedCheckId}`

Exactly two routes added. Unsupported methods retain existing 405/Allow GET; HEAD has no body. No history subroute or profile-nested alias. The dynamic ID route rejects `history` as an invalid ID; it does not implement history functionality.

## 3. Authentication

ApiAuthController uses the unchanged admittedBearer(false) path and existing guard/principal, one non-logout limiter admission and HTTPS requirement. No token parsing, JWT, API keys, CORS, session authentication or new limiter. The controller's new injected projection required only constructor fixture wiring in the existing identity test; its assertions were not altered.

public/.htaccess adds only `saved-symptom-checks(?:/[^/]+)?$` to existing scoped Authorization forwarding. Existing alternatives remain unchanged. Apache/PHP/TLS configuration and credentials were not modified.

## 4. Account Ownership

Every list/count query binds `user_id=:user` from principal.userId. Detail reuses findForUser with `WHERE id=:id AND user_id=:user`. No global ID lookup followed by an ownership check. No HealthProfileRepository::findAccessible or profile/member authorization. No request body/query/header/cookie actor overrides.

## 5. Cross-User Isolation

In-memory and real MariaDB/HTTPS fixtures prove A lists only A records, B lists only B records, both can retrieve their own records, and foreign IDs return the same 404 code/message/envelope as nonexistent IDs. Unknown query actor parameters are rejected. Alternate bearer devices for the same account see the same history. Body/cookie/custom identity headers cannot change ownership. Repository spy assertions verify ID and user binding in the same SQL query.

## 6. Historical Snapshot Handling

SavedSymptomCheckService::getForPrincipal performs only owner-bound lookup and the same private decodeStored used by browser get. Existing byte/depth/version/disclaimer/type/list/count validation is reused without duplication. Malformed/unsupported history fails safely; nothing is repaired, refreshed or deleted. The API additionally checks row checked_at/count mirrors and validates UTC timestamps.

Selected symptoms, results, reasons and explanations retain stored array order. Captured labels, numeric scores, display percentages and match levels remain stored values even if they differ from what today's matcher would calculate. Unknown JSON keys and internal matcher arrays are omitted by explicit nested projection.

## 7. No-Recalculation Guarantee

No API read calls match, recordSearch, capture, save, delete, current symptom lookup or current disease lookup. Unit tests inject a checker whose matching, counter and label methods throw; reads still succeed with an uninitialized browser AuthService and no session. Real HTTPS fixture databases intentionally have no diseases/symptoms tables: exact historical detail still succeeds. No current master tables are needed to reconstruct historical labels or scores.

Historical output represents the saved presentation, not a current diagnosis, Condition, risk/assessment, advice or treatment recommendation. Stored payload version1 is not an algorithm/dataset release version. The captured educational disclaimer is returned unchanged. No matcher/clinical wording was modified.

## 8. API Projection

Collection items have exactly:

```text
id: decimal string
checked_at: UTC RFC3339 string
saved_at: UTC RFC3339 string
selected_symptom_count: integer
displayed_result_count: integer
```

Detail has those same five fields plus exactly:

```text
snapshot: {
  version: stored integer (1),
  checked_at: UTC RFC3339 string,
  selected: [{id: decimal string, label: stored string}],
  summary: {total_matches: integer, displayed_matches: integer},
  results: [{
    id: decimal string,
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

UTC representation is `YYYY-MM-DDTHH:MM:SSZ`, preserving checked-vs-saved meanings. Nested result IDs are historical disease references, not Conditions. Strings must be rendered safely as text by clients. No nullable columns exist in this actual saved-check schema; zero matches remain an empty results array with zero displayed count. No artificial nullable fields were added.

## 9. Pagination

Existing page/page_size convention: page default1/max10000, page_size default20/max50. Unknown/range/format values use 422; arrays/non-string/invalid UTF-8 values use 400. Repository also bounds limit/offset defensively. Metadata-only SQL avoids snapshot blob loading. Empty or beyond-final pages return [] with actual owner total/has_more. Standard meta contains api_version, page, page_size, total, has_more. Detail accepts no query parameters.

## 10. Ordering

Collection SQL: `checked_at DESC,id DESC`. Tests include tied dates and a newer date with a lower ID, across default/max/partial/final pages. Browser ordering is unchanged. Detail arrays are not sorted by current IDs, scores, names or relevance.

## 11. Privacy Boundary

No owner/account ID, save key, raw snapshot_json, ranking_score, matched/missing_preview/missing_count arrays, unknown JSON keys, credentials, authentication, sharing/access/member metadata, demographics, IP/device fields or current-master expansions are returned. List uses five explicit columns. Detail decodes its owner-bound stored row internally and emits only the approved whitelist, never that row or raw JSON. JSON UTF-8, no-store, nosniff, safe errors, Bearer challenge and existing 429/Retry-After remain.

## 12. Deferred Scope

POST/create, DELETE, PUT/PATCH, all writes, matching API, broader history, profile association, Conditions linkage, sharing, Medical Summary and Dashboard integration remain deferred. Browser Saved Symptom Checks, browser checker, matching/scoring, other authenticated resources, public API, governance and Authentication behavior were not redesigned or changed.

## 13. Database / Schema

No normal database INSERT/UPDATE/DELETE or DDL. No schema artifact changes, including database/structure.sql. Existing table/index/constraints are reused. SQL mode remains `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`; no session/global/config mode changes.

Disposable tests use the established guarded `kobi_api_auth_50b_test_20261016` database, synthetic accounts/authentication families and the exact existing saved-check DDL. Schema creation/data mutations occur only there; the target must not exist before each run and is dropped afterward. No production check or API family was created.

## 14. Files Changed

Modified:

- app/Controllers/ApiAuthController.php — two thin read actions and injected projection.
- app/Repositories/SavedSymptomCheckRepository.php — owner-bound bounded metadata count/list.
- app/Services/SavedSymptomCheckService.php — shared unchanged decoder plus principal-aware reader.
- routes/web.php — exactly two GET routes.
- public/.htaccess — narrow existing forwarding alternative.
- tests/api-identity.php — constructor fixture dependency only; existing assertions retained.
- docs/authenticated-api-v1.md — scoped contract addition.

Created:

- app/Services/ApiSavedSymptomCheckService.php.
- tests/api-saved-symptom-checks-support.php — shared synthetic frozen fixture/expected DTO.
- tests/api-saved-symptom-checks.php — focused database-free tests.
- tests/api-saved-symptom-checks-http.php — guarded MariaDB/verified HTTPS tests.
- docs/task-57b-saved-symptom-checks-api-implementation.md — this report.

## 15. Tests and Validation

Focused unit suite: **58 checks PASS**. Covers exact projection including all nested whitelists/string IDs, stored labels/scores/order, current-dependency independence, empty/single/multiple lists, pagination/order/bounds, ownership/missing parity, zero results, malformed/unsupported/oversized payloads, metadata mismatches, bad timestamps, scoped forwarding and SQL ownership predicates. Tests exercise the exact 262144-byte stored-payload ceiling and preserve complete text or existing safe response failure; no global response limit increase or history truncation.

Focused real MariaDB/verified HTTPS: **989 checks PASS**. Covers authentication failures including invalid/expired/revoked tokens, owner/cross-user SQL, exact populated detail without current label tables, metadata paging/order, no GET row mutations, safe malformed/unsupported history errors, SQL JSON CHECK, timestamp/mirror errors, zero-result history, standard envelopes/errors/headers, methods, one normal limiter admission, 429 and lifecycle cleanup.

An initial test run stopped on an incorrect test assumption that NOT NULL guarantees UPDATE NULL rejection in non-strict MariaDB. Its finally cleanup removed the disposable database/server/files. The test was corrected to inspect actual non-nullable column metadata and explicitly test safe API failure for a zero stored timestamp. No application/schema/SQL-mode change was made to accommodate the test. The corrected full focused run passed.

PHP lint: **all 9 changed/new PHP files PASS**. Existing-file before/after diff review used captured file contents, without Git commands. Reference searches and file-hash inventory verify scoped changes only; no files deleted.

## 16. HTTP / HTTPS Validation

Both new normal Apache HTTPS endpoints were requested anonymously, without cookies/tokens/login. Both returned the exact generic 401 envelope, no-store, no cookies/CORS; PHP/cURL peer verification was true, hostname verification2 and TLS verify result **0**. No certificate/config changes or verification bypass.

Authenticated populated list/detail, pagination and isolation passed through the established disposable verified loopback TLS harness and actual application bootstrap/controller/guard/SQL. That harness is not normal Apache; it does not prove populated normal-Apache success. No production login family was created just for this task. Normal scoped forwarding was also checked statically for the exact new path alternative and exclusion of unrelated paths.

## 17. Regression Results

Database-free regressions PASS:

| Suite | Checks/scenarios |
|---|---:|
| API authentication | 103 |
| API limiter | 13 |
| API identity | 44 |
| Health Profiles API | 20 |
| Personal Medications API | 28 |
| Medical Summary API | 29, plus reused Dashboard76 |
| Conditions API | 65 |
| Medication Schedule API | 71, plus browser schedule52 |
| Public API | 56 |
| Public API bootstrap | 8 |
| Public Search | 30 |
| Authentication Phase1 | 47 scenarios |
| Browser Saved Symptom Checks | 63 |
| Checker JS lifecycle/presentation | 26 |
| Dashboard guidance | 76, plus Dashboard76 |

Existing isolated MariaDB/verified HTTPS regressions all PASS:

| Suite | Checks |
|---|---:|
| API authentication | 326 |
| API identity | 345 |
| Health Profiles | 494 |
| Personal Medications | 624 |
| Medical Summary | 508 |
| Conditions | 1106 |
| Medication Schedules | 1186 |

Each completed normally and removed its disposable database/server/files. No existing regression assertions were weakened.

## 18. Production Data Integrity

The actual starting normal database contained **1 existing saved check**, although 57A/57B context expected zero. It was not created by this task; contents were not inspected or seeded, and it must remain untouched. Validation uses this actual baseline rather than manufacturing an empty state.

Before/after validation uses counts for all 63 normal tables, normalized SHOW CREATE digests (excluding next AUTO_INCREMENT only), a trigger-definition digest, aggregate saved checked/saved dates, active API-family count and session/global SQL mode. No sensitive production row contents were queried or hashed. **Final comparison: identical in every captured category.** Saved rows remain **1**; active API families remain **0**; all 63 counts/schema digests and trigger digest match; both SQL modes remain unchanged. Existing API session/refresh counts match their starting values. No normal INSERT/UPDATE/DELETE/DDL or API credential lifecycle was performed.

Cleanup verification: disposable database count **0**, scoped HTTP test-directory count **0**, test-listener count on 18865/18866 **0**. No other environments or existing artifacts were deleted. File-hash inventory reports the seven intended modified files and five intended created files, no deletions; other application/test/docs/config/schema files retain their starting hashes. No Git commands were used.

## 19. Limitations

No authenticated normal-Apache success request or genuine browser audit was run; no production credentials/families/checks were needed or permitted for populated fixtures. Authenticated HTTPS behavior uses disposable fixtures; browser regressions use unchanged in-memory/JS suites. Safe counts/metadata comparison is not a byte-for-byte hash of all production health records. Existing response-size ceiling and payload-schema-only provenance remain as documented; no algorithm/dataset history was invented. Offset pages do not promise concurrency snapshot consistency.

## 20. Final Status

**PASS.** Exactly the approved GET list/detail resources are implemented and verified. Persisted snapshots remain authoritative, account isolation is enforced in SQL, and historical content is never reconstructed using today's matcher/labels. Focused tests, relevant regressions, syntax/diff review, disposable cleanup and normal production integrity comparisons passed. Normal Apache authenticated success is deliberately untested as described above; anonymous verified HTTPS and authenticated isolated HTTPS passed. No Git operations. **Step 58 not started.**
