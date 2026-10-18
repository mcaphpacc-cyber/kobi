# Step 54B — Authenticated Live Aggregate Medical Summary API

Date: 2026-10-17. Status: **PASS**.

## 1. Executive summary

Implemented one read-only private aggregate endpoint. It reuses bearer admission, profile authorization and the canonical live summary assembler, then explicitly whitelists three profile fields and five integer counts. No normal-KOBI database write or production credential family was created.

## 2. Exact route

`GET /api/v1/health-profiles/{profileId}/medical-summary`. No aliases, record subresources or mutation methods. Existing router returns 405/Allow GET for other methods, with an empty HEAD body.

## 3. Authentication reuse

`ApiAuthController::medicalSummary()` uses existing `admittedBearer(false)` → `ApiAuthGuard` → principal, once. Existing HTTPS requirement, family limiter, generic credential failures, safe response/error envelope and no-store policy remain. No new parser, token rules, browser session, CSRF requirement or CORS grant.

## 4. Profile authorization

`MedicalSummaryService::getSummaryForPrincipal()` first calls existing `ApiHealthProfileService::detail()` → `HealthProfileRepository::findAccessible(profileId, principal.userId)`. Only then does it assemble records for that returned profile. Existing positive decimal signed-INT ID validation and active profile/membership predicates are unchanged. Owner/editor/viewer read access remains; missing, inaccessible, archived and revoked-member profiles share the existing safe 404. Neither body nor cookie/custom headers can override the principal.

## 5. Summary assembly source

The original body of `MedicalSummaryService::getSummary()` now lives in private `assemble(profileId, profile)`. Browser `getSummary()` retains its existing HealthProfileService authorization and delegates to that same body. The new principal-aware method authorizes through the API profile service and delegates to it. Grouping, sorting, repository calls and browser return shape are unchanged. `ApiMedicalSummaryService::detail()` projects only approved fields. There is no second summary calculation or SQL count implementation.

## 6. Exact count definitions

| Key | Canonical live definition |
|---|---|
| conditions | Count of active-group conditions: `active` or `chronic` |
| historical_conditions | Count of all remaining conditions; valid stored statuses are `resolved` and `historical` |
| current_medications | Count of medications with status exactly `current` |
| allergies | Count of all rows returned by existing profile-scoped allergy lookup |
| procedures | Count of all rows returned by existing profile-scoped procedure lookup, including surgery/procedure/hospitalization |

These match the Dashboard's five corresponding counts. Dashboard additionally counts documents separately; this API does not. The canonical historical fallback for any other stored condition status is preserved. Sharing's existing snapshot projection has different condition filtering; it is not used or changed here.

## 7. Exact API projection

Existing envelope: `success: true`, `data`, `meta: {api_version: "v1"}`. Data has exactly:

```text
profile: {id: decimal string, full_name: string, profile_type: string}
counts: {conditions: integer, historical_conditions: integer,
         current_medications: integer, allergies: integer, procedures: integer}
```

Empty profiles return integer zeros. The existing Step 52B profile projector supplies profile values. No raw row or full assembled summary is serialized.

## 8. Privacy exclusions

No record arrays/IDs/dates/details, medication names/doses/schedules/checkoffs, diagnosis/symptom details, allergy/procedure details, notes/providers, documents/attachments/measurements, DOB/gender/mobile/demographics, membership/invitation/internal metadata, credentials, sharing data, logs, recommendations, scores or AI output. Exact DTO equality is asserted with synthetic private fields present internally.

## 9. Sharing isolation

The endpoint never calls MedicalSummaryShareService or its repository. Synthetic snapshot/share/access-log rows remain byte-for-byte equal across GETs in the disposable HTTP fixture, including after live source changes. In-memory regression also exercises existing create/read/revoke sharing behavior and confirms snapshot immutability versus live aggregate changes. No normal sharing endpoint was opened, avoiding its intentional access-log write.

## 10. Performance/aggregation behavior

Reuses four existing profile-scoped repository reads and their canonical array grouping/sorting. Full detail arrays remain internal and are discarded after projection. This bounded architectural change is acceptable for V1; no direct-count repository equivalent existed for all five canonical groups. Very large profiles still incur the existing assembler's memory/sort costs. No caching, query redesign, transaction/snapshot promise or schema optimization was added. As with the browser summary, separate reads are not guaranteed a single transactional instant during concurrent edits.

## 11. Focused tests

| Command (PHP CLI) | Result |
|---|---|
| `tests/api-medical-summary.php` | **29 checks PASS**, plus reused **76 existing Dashboard checks** |
| `tests/api-medical-summary-http.php` | **508 checks PASS** over certificate-verified isolated HTTPS/MariaDB |

Coverage: valid/missing/malformed/invalid/refresh-as-access/expired/revoked credentials; owner/editor/viewer and cross-user isolation; archived/revoked/missing profiles and malformed IDs; authorization before any domain read; empty/populated/deterministic counts; active/chronic/resolved/historical grouping; current-only medication counts; all procedure types; integer zeros/string IDs/exact DTO; cookie/body/header identity independence; no snapshot/share/log/database GET side effects; source changes reflected live; safe 500 on invalid stored profile type; existing limiter admission once and 429; unsupported methods/aliases; JSON/no-store/nosniff/envelope/challenge/no cookie/no CORS.

## 12. Regression tests

All passed in this task:

| Suite | Passed checks/scenarios |
|---|---:|
| api-identity.php | 44 |
| api-identity-http.php | 345 |
| api-health-profiles.php | 20 |
| api-health-profiles-http.php | 494 |
| api-medications.php | 28 |
| api-medications-http.php | 624 |
| api-auth.php | 103 |
| api-auth-rate-limit.php | 13 |
| api-auth-mariadb.php | 65 |
| api-auth-http.php | 326 |
| auth-phase1.php | 47 scenarios |
| health-medicine-dates.php | 26 |
| medication-schedules.php | 52 |
| health-profile-validation.php | 57 plus 4 redirect scenarios |
| health-dashboard.php | 76 (reused by focused unit suite) |
| health-dashboard-guidance.php | 76 guidance checks; also reruns Dashboard dependency |
| user-mobile-number.php | 61, including snapshot mobile exclusion |
| public-api-v1.php | 56 |
| public-api-v1-bootstrap.php | 8 |
| public-api-v1-http.php | 561, read-only normal public requests |
| public-search.php | 30 |

Counts are reported per suite, not combined to count reused assertions twice. No tests were weakened. Existing identity/Dashboard test setup received only the newly required constructor dependencies. PHP syntax passed for all eight changed/new PHP files. Apache syntax passed. Fourteen forwarding regex cases passed, including rejection of unrelated/deeper paths. Changed-file review found no unrelated modifications, deleted files, conflict markers or trailing whitespace in task edits. No Git command was used.

## 13. Apache/HTTPS results

Normal `https://localhost/kobi/public/api/v1/health-profiles/1/medical-summary`: expected generic **401**, standard V1 JSON, no-store, Bearer challenge and no cookie/CORS. No credentials/families were used. Valid bearer success and authorization are exercised through real application bootstrap in the isolated loopback HTTPS fixture. Existing APIs passed their transport regressions.

## 14. TLS result

Normal PHP/cURL request used `CURLOPT_SSL_VERIFYPEER=true`, `CURLOPT_SSL_VERIFYHOST=2`, no redirect and the existing configured trust; **SSL_VERIFYRESULT=0**. Isolated cURL requests verified their dedicated ephemeral localhost test certificate with the same peer/host enforcement. No client certificate bypass or trust-store/TLS configuration change.

## 15. Database/schema integrity

Normal database inspection was SELECT/SHOW only; no INSERT/UPDATE/DELETE/DDL, fixtures, token families, snapshots or share-access writes there. Baseline and final safe counts:

| Table/state | Before | After |
|---|---:|---:|
| users | 12 | 12 |
| health_profiles | 14 | 14 |
| health_profile_members | 17 | 17 |
| health_conditions | 4 | 4 |
| health_medicines | 3 | 3 |
| health_allergies | 3 | 3 |
| health_procedures | 2 | 2 |
| medical_summary_shares | 13 | 13 |
| medical_summary_share_access_logs | 30 | 30 |
| user_api_sessions | 11 | 11 |
| user_api_refresh_tokens | 21 | 21 |
| active API families (revoked_at IS NULL) | 0 | 0 |

Schedule slots/checkoffs were each 0 on both additional reads. All eight relevant profile/membership/summary-source/sharing table definitions match authoritative structure.sql, ignoring only formatting and next AUTO_INCREMENT values. The schema reference file is unchanged; no normal schema statement was executed.

A broad initial information_schema digest included mutable index CARDINALITY statistics and did **not** match afterward. It is not used as evidence of structural equality. Relevant actual SHOW CREATE definitions were therefore compared directly with the unchanged authoritative reference, and all eight matched. No sensitive production row contents were inspected or hashed. Aggregate counts cannot prove byte-for-byte equality of every production row; isolation guards, read-only normal operations and exact synthetic fixture comparisons provide the practical integrity evidence for this task.

## 16. SQL mode

Normal session/global modes remain exactly `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION` before and after. No SQL-mode directive was executed by this task.

## 17. Configuration

Only `public/.htaccess` was extended: the existing Authorization forwarding expression now also matches exactly the nested `/health-profiles/{single-segment}/medical-summary` path. Existing authentication/identity/profile/medication scopes remain; no general API wildcard was introduced. `.env`, Apache/PHP/TLS/limiter/dependency configuration and authentication implementation were not changed. Normal GET admission still performs expected private limiter-file accounting.

## 18. Disposable fixture cleanup

Database writes occurred only within guarded `kobi_api_auth_50b_test_20261016`. Suites ran serially, refusing an existing fixture database, and removed it afterward. Synthetic API families were revoked through normal test lifecycle before cleanup. Final SELECT confirms disposable database absent; owned HTTPS harness directories absent. Each harness removed its loopback servers, ephemeral certificate/key and counter files. No normal family was created or revoked.

## 19. Exact files changed/created

Modified:

- app/Controllers/ApiAuthController.php
- app/Services/MedicalSummaryService.php
- routes/web.php
- public/.htaccess
- tests/api-identity.php (constructor setup only)
- tests/health-dashboard.php (constructor setup only)
- docs/authenticated-api-v1.md

Created:

- app/Services/ApiMedicalSummaryService.php
- tests/api-medical-summary.php
- tests/api-medical-summary-http.php
- docs/task-54b-medical-summary-api-implementation.md

File-hash inventory comparison across app/bootstrap/routes/database/public/tests/docs and .env found only the stated changes/additions. No unrelated application/browser/sharing/auth/profile/medication/governance code changed. Git operations: **NONE**.

## 20. Limitations

No authenticated normal-Apache success request: production token creation was prohibited. Success is verified in the isolated real-bootstrap TLS environment; normal Apache's credential-free route, TLS and scoped forwarding expression are verified separately. Browser UI was not exercised; existing browser assembly/Dashboard and sharing service behavior were exercised in memory. Existing internal array-loading costs, multi-read consistency and production aggregate/schema-metadata verification limits are described above. No unresolved test failure.

## 21. Health Records API

**NOT IMPLEMENTED.** No underlying record arrays or endpoints added.

## 22. Medication Scheduling / Due Today API

**NOT IMPLEMENTED.** Existing browser scheduling remains unchanged.

## 23. Saved Symptom Checks API

**NOT IMPLEMENTED.**

## 24. Step 55

**NOT STARTED.** Stop after Step 54B.
