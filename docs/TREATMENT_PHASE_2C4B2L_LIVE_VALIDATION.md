# Phase 2C-4B-2-L live validation — 2026-10-09

## 1. STATUS

**PASS.** The implemented 4B-2 creation workflows passed real MariaDB, application HTTP, repository/service/controller and browser validation. No application correction was needed. Cleanup completed.

## 2. ENVIRONMENT

PHP 8.2.0; MariaDB 10.4.27-MariaDB. A fresh separate datadir and loopback MariaDB process used port 3321 and database `kobi_phase2c4b2l_validation_20261009`. A temporary PHP application server used port 8131, process-local database configuration and isolated session storage. The normal KOBI server/database was never connected to.

The temporary server startup reproduced the existing configured mode `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`. This mode matched session/global metadata before import, after import and before cleanup. No SET SQL_MODE command, normal-server change, global configuration edit or persistent application configuration change occurred.

All **111 schema-stream statements** executed successfully, unchanged. CREATE DATABASE and final DROP DATABASE were separate disposable-environment operations. Following the previously approved import exception, only these directives were omitted; database/structure.sql was not edited:

| Source line | Intentionally omitted directive |
| --- | --- |
| 9 | `/*!40101 SET SQL_MODE=''*/;` |
| 13 | `/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;` |
| 1089 | `/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;` |

No other schema statement was omitted. SQL-mode omissions preserved the non-strict compatibility environment. The import evidence records every executed statement and the source schema hash in `TREATMENT_PHASE_2C4B2L_IMPORT_RESULTS.json`.

## 3. LIVE VALIDATION

80 live checks passed. Real accounts authenticated through the existing HTTP login flow. Actual registered creation routes returned authorized GET 200, successful POST 302 with editor redirects, authorization/CSRF 403, stale revision 409 and input/workflow 422. Anonymous create access redirected to login. Different-system editors saw only their own active scoped system and could not write against system 1.

The actual HTTP workflow created an empty-item draft, a Hindi-only draft with an atomic first item, and later items. Browser creation produced protocol 2293 and excluded item 3405, with a successful redirect and revision 2. Inactive/forged/missing/nonexistent identities, invalid nested data, empty/overlong titles/text, unsupported item types, invalid numeric orders and unexpected privileged fields were rejected without mutation. Missing and invalid CSRF failed both creation POST routes unchanged. A repeat draft returned 422; repeat item POST returned 409.

Existing service commands prepared, submitted and independently approved a synthetic fixture. Adding an item through the actual route changed revision 5 to 6, cleared approval, and returned to draft/unpublished. Section 14's explicitly permitted synthetic published-state fixture was established through existing commands entirely inside the disposable database; HTTP GET and POST item additions returned 422 without mutation. It never exposed content through the application HOLD and was deleted with the disposable database. No normal/public content was published.

Controlled test-repository faults after real protocol/item/event inserts verified transaction rollback; a second fault after real later-item insertion verified revision/history/item rollback. These were runtime fault-injection probes using real database writes and transactions, not corruption of production tables or changes to application behavior.

Browser observations confirmed working forms, redirects, draft state, excluded item, other-system rejection, zero public treatments, and no console warnings/errors or CSP violations. Screenshot evidence: `TREATMENT_PHASE_2C4B2L_BROWSER_DRAFT.png`.

## 4. DATABASE VERIFICATION

Actual rows, complete relevant DDL/columns/indexes, constraints and both trigger definitions are retained in `TREATMENT_PHASE_2C4B2L_RUNTIME_RESULTS.json`. Foreign-key checking was active. Real invalid item-parent and event-revision writes failed with MariaDB errors 1452 and 4025 respectively and left data unchanged.

Protocol 2291 initially persisted draft/unpublished/revision 1, null approval, empty published locales, evidence/risk not_assessed, null purpose/evidence summaries/applicability/limitations and editor updated_by 1. Its initial content_changed event had actor 1/revision 1, explicit creation notes, empty locales and null snapshot/hash. No review, approval or publication event was created for the new draft.

Protocol 2292 and its optional first item persisted together at revision 1. The item had the correct parent/type, order 0, excluded flag, not_assessed evidence, null purpose/limitations and no fabricated English translation. Subsequent event revision sequences were exactly `[1,2]` and `[1,2,3]`. Browser rows independently matched the displayed draft/unpublished/revision 2 state and two actor-1 content_changed events.

## 5. PUBLIC BOUNDARY

Application/HTTP HOLD remained active. The real canonical publication repository/service, DiseaseService/DiseaseController and comparison paths withheld all draft sentinels and the synthetic candidate under HOLD. Eligible synthetic published-state assessment was CANDIDATE while HOLD projection was null and disease treatments empty.

Governed-mode probes injected a test policy into real database-backed services only; persistent configuration and HTTP HOLD were unchanged. New drafts and approval-invalidated content remained DENY/null. The positive synthetic projection matched the exact protocol/item/citation allowlists and omitted reviewer/contributor identities, revision/state/approval fields, audit notes, snapshots/hashes, risk-review and permission metadata. DiseaseService/comparison retained draft isolation under the injected policy.

State comparisons confirmed public API, disease/comparison HTTP and service reads did not alter protocols, items, events, timestamps, sources, references, grants or preferences. Instrumenting the real repository's query method showed **8 queries before and after five additional drafts**; no N+1 regression appeared. This is a bounded query-count probe, not concurrency stress testing.

## 6. SECURITY

Actual scoped authorization, CSRF and persisted-parent checks passed. A controlled permission-repository failure reached the real controller's generic 503 boundary, without becoming 403 or exposing its raw sentinel. Actual HTTP responses contained no SQLSTATE, PHP warnings, fatal errors or stack traces. No security/governance defect was found.

## 7. TEST RESULTS

All final test commands used `D:/xampp/php/php.exe`, exited 0 and passed:

| Suite | Distinct checks |
| --- | ---: |
| Live validation | 80 |
| treatment-creation | 110 |
| treatment-admin-hardening | 39 |
| treatment-publication-correction | 17 |
| treatment-publication | 105 |
| treatment-governance | 88 |
| treatment-editorial | 73 |
| treatment-review | 130 |
| treatment-prerequisites | 29 risk + 7 lifecycle + 11 comparison |
| auth-phase1 | 45 |
| Regression subtotal | 654 |
| **Distinct total** | **734** |

Embedded/rerun assertions are excluded from these totals. Schema statements are not counted as test assertions. Initial harness/environment attempts are recorded separately: optional Windows-service registration failed with error 5; the standalone process pattern worked without a service. An incorrect initial four-trigger expectation was corrected to the schema's two before fixture/application testing. A CLI controller probe lacked REQUEST_URI and emitted two layout warnings; its repeat with complete request context and warnings promoted to errors passed. These were harness issues, not application corrections. Final live checks/regressions had zero failures.

## 8. STATIC CHECKS

All eight temporary validation PHP utilities passed syntax checks before removal. No application PHP was changed. git diff --check passed. Task-start SHA256 comparison preserved every pre-existing inventoried application, route, schema, test, bootstrap/config and documentation file. No pre-existing file was deleted.

## 9. CORRECTIONS

None to application code, schema or existing tests. Only temporary harness setup/context corrections were made. Retained new audit artifacts are this report, the two named JSON evidence files and the browser PNG. Existing preflight/history documentation remains untouched.

## 10. CLEANUP

The disposable database was dropped and its absence checked through information_schema before server shutdown. The temporary HTTP server stopped. The verified temporary MariaDB process lingered after graceful shutdown and was terminated after checking its exact disposable datadir command line. Ports 3321/8131 had no listeners afterward. The guarded temporary directory, datadir, utilities, logs, cookie jars, credentials, sessions and fixtures were removed; the browser tab closed.

The application logger uses a fixed normal log path despite storage configuration. Two class-only validation diagnostics were appended there by the controlled failure probes. Cleanup removed only their exact verified suffix, preserving every earlier byte. No unrelated log entries or user files were removed. The normal database was never selected or connected to.

## 11. GIT

No commit, push, reset, checkout, merge, rebase, amend or other Git history mutation occurred. Only the four retained audit artifacts were created by this validation.

## 12. FINAL 4B-2 STATUS

4B-2 implementation and this specific 4B-2-L live validation pass. No unresolved serious creation/security/governance defect remains. Application-level repeat checks still cannot guarantee uniqueness against writes outside the service; no unique constraint or concurrency redesign was added. Concurrent creation stress was not performed.

## 13. REMAINING

4B-3 and the broader 4B-L remain unimplemented and were not validated. No publisher/withdrawal/reset UI or public publication workflow was implemented or changed. Stop after this validation.
