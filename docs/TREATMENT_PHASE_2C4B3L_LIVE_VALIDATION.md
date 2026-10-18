# Phase 2C-4B-3-L live validation — 2026-10-09

## 1. STATUS

**PASS.** The implemented withdrawal, review-reset and scoped operations/history workflows passed real MariaDB, registered HTTP-route and browser validation. No application correction was required. Cleanup completed. The broader 4B-L campaign was not performed.

## 2. ENVIRONMENT

- PHP 8.2.0 (`D:/xampp/php/php.exe`); MariaDB 10.4.27-MariaDB (`D:/xampp/mysql/bin/mysqld.exe`).
- Separate disposable datadir: `D:/xampp/htdocs/kobi/tests/.phase2c4b3l/data`; loopback database port 3322; database `kobi_phase2c4b3l_validation_20261009`.
- Temporary PHP application server: `http://127.0.0.1:8132`, isolated session/cookie storage and process-local database/storage settings. No normal KOBI database connection or persistent credential/configuration change occurred.
- Disposable startup reproduced the established mode `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`; session/global metadata matched before import, after import and before cleanup. No SET SQL_MODE statement was executed and no normal-server/global/persistent SQL-mode configuration was changed.

All **111 schema-stream statements** executed successfully. CREATE DATABASE and final DROP DATABASE were separate disposable-environment operations. Client DELIMITER instructions were interpreted without changing object definitions. Following the approved isolated-import pattern, only these three statements were omitted:

| Source line | Intentionally omitted SQL-mode directive |
| --- | --- |
| 9 | `/*!40101 SET SQL_MODE=''*/;` |
| 13 | `/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;` |
| 1089 | `/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;` |

These omissions preserved the established non-strict validation mode. No other statement was omitted or rewritten. `database/structure.sql` remained unchanged; the import artifact preserves its SHA256 and every executed statement. Actual treatment-table DDL, columns, indexes, foreign keys, CHECK definitions and both source triggers were inspected. FK checks were active after import. Real invalid qualification/event/FK writes failed with MariaDB 4025/1452; both self-replacement triggers rejected insert/update with SQLSTATE 45000, leaving data unchanged.

## 3. LIVE ROUTE VALIDATION

The actual registered routes were exercised through the normal application router/bootstrap:

| Method | Route | Validated behavior |
| --- | --- | --- |
| GET | `/admin/treatments/operations` | Scoped editor/reviewer/publisher list, validated filters, pagination |
| GET | `/admin/treatments/{protocolId}/operations` | Scoped detail, appropriate controls, metadata-only history |
| POST | `/admin/treatments/{protocolId}/withdraw` | Publisher withdrawal and intended redirect |
| POST | `/admin/treatments/{protocolId}/reset-review` | Qualified reviewer reset and intended redirect |

GET/PUT/DELETE mutation requests returned 404 at the router. Guests redirected to login; valid inaccessible/nonexistent IDs returned safe 403; malformed IDs returned 422. Wrong-scope listing displayed only that actor's own protocol. Inactive/ungranted system 3 was absent from the scoped list. The inactive user could not authenticate. Scope/identity/permission query injection was rejected. Public users could not obtain internal history.

## 4. WITHDRAWAL VALIDATION

Protocol 2291 was created, prepared, submitted, independently approved and published using canonical services, entirely in the disposable database. Its real HTTP withdrawal persisted withdrawn/draft at revision 5, null approval, empty published locales and one actor-3 withdrawn event. Prior protocol content/items/history were preserved; no publication event was manufactured by withdrawal.

Draft, pending_review, changes_required, approved/unpublished and already-withdrawn attempts failed unchanged. Editor/reviewer without publication authority, wrong-scope publisher, revoked grant, unqualified actor and guest requests were denied. Missing/invalid/nested CSRF failed unchanged. Forged identifiers and privileged fields were rejected. A second real-login cookie context submitted the stale withdrawal and received 409 without a duplicate event; a fresh repeated withdrawal returned 422.

Temporary fault repositories performed valid real writes, then attempted an invalid-revision event INSERT to trigger an actual MariaDB CHECK failure. The unchanged governance transaction rolled back protocol/approval/locales/history fully and the real HTTP controller returned generic 503. An unexpected LogicException probe returned generic 500 with the same rollback guarantee. Withdrawn content returned DENY/null through canonical public reads, and actual DiseaseController/comparison HTTP did not expose it.

## 5. REVIEW RESET VALIDATION

- Protocol 2292: pending_review/unpublished → draft/unpublished, revision 5 unchanged, one review_reset event.
- Protocol 2293: approved/unpublished → pending_review/unpublished, revision 5 unchanged, approval cleared, review_reset plus a fresh submitted event using prior approval locale `en` and the canonical snapshot/hash mechanism.

Published, withdrawn, draft and changes_required resets failed unchanged. Qualified scoped reviewer access passed; editor/publisher without review authority, wrong scope, revoked grant, unqualified actor and guests failed. A grant with can_review but no qualification was independently rejected by the real schema. Missing/invalid/nested CSRF and malformed/forged input failed. Separate authenticated request contexts proved stale reset returns 409 with no duplicate event.

Reset rollback passed after the first real event write, after the second submitted event write, and for an unexpected exception. Historical approval/submission rows and hashes remained immutable. Approval using the superseded submission returned 409. A qualified material contributor could reset a pending review but still received 422 when attempting self-approval after resubmission. Reset did not fabricate approval or publication.

## 6. WITHDRAWN LIFECYCLE

Protocol 2291 completed:

`published/revision 5 → withdrawn/draft/revision 5 → substantive HTTP edit/draft/unpublished/revision 6 → HTTP submission/revision 6 → independent HTTP approval/revision 6`.

It stopped approved/unpublished with a new approval pointer. The old approval and withdrawal remained historical. Tail event types were exactly withdrawn, content_changed, submitted, approved; revisions `[5,6,6,6]`, actors `[3,1,1,2]`. Direct withdrawn publication was rejected without mutation. No old approval was reattached, no automatic publication occurred, and no publication shortcut was introduced.

## 7. HISTORY / EVENT INTEGRITY

Real event rows were inspected for type, parent, revision, authenticated actor, UTC timestamp, locales, notes, due date and snapshots/hashes. Every final event passed canonical event/timestamp validation; every persisted snapshot matched SHA256. Initial creation used content_changed, consistent with the existing event model.

The history fixture used 56 canonical creation/edit events. Actual HTTP pagination yielded exactly 50 then 6 descending event IDs, with no duplicates, missing events or foreign-protocol rows. History POST/edit/delete attempts had no route; forged event fields and malformed cursors failed unchanged. Operations list pagination yielded 50 then 3 permitted protocols, excluding the other system. No history/snapshot mutation route was added or discovered.

## 8. PUBLIC BOUNDARY

Application/HTTP HOLD remained active throughout. Actual DB-backed canonical assessments and projections excluded withdrawn, reset/unapproved and approved/unpublished protocols. An otherwise eligible synthetic published protocol assessed CANDIDATE while HOLD projection remained null.

Governed-mode checks injected a test policy only into disposable DB-backed service probes; persistent/application HTTP HOLD was unchanged. The positive projection used the exact existing allowlist and omitted reviewer/contributor identities, approval/state/revision metadata, events and snapshots. Actual DiseaseService and comparison shared the canonical reader and excluded the denied protocols. Real disease/comparison HTTP responses exposed no sentinels or governance history. Before/after snapshots confirmed public reads were read-only.

## 9. SECURITY

Real authentication, persisted scope, qualification, revocation, HTTP method, CSRF and stale-state behavior passed. Required distinctions were observed: 403 access/CSRF denial, 409 stale revision/state/submission, 422 input/workflow error, 503 controlled database/permission infrastructure failure, 500 controlled unexpected failure; router method misses were 404.

The permission fault executed a nonexistent-table SELECT in a temporary repository injected into the real HTTP container. It returned generic 503, not ordinary 403, without mutation or raw SQL/exception details. Controller diagnostics contained class names only. The test entry executed the existing Logger implementation with only its output destination redirected into temporary storage, preserving the normal application log.

## 10. DATABASE VERIFICATION

All successful operations were checked against actual protocol/event rows; item rows and the complete synthetic final event history are retained in the runtime artifact. Source/reference/grant/preference snapshots confirmed rejected requests and reads left governance unchanged. Temporary DI probes used real PDO connections and governance transactions; they were not memory-repository tests or schema changes.

Query counts were measured through real prepared repository queries:

| Path | Observed queries |
| --- | ---: |
| Operations list, 13 protocols | 32 |
| Operations list, 51 fetched / 50 displayed | 108 |
| Operations detail | 14 |
| History repository page | 1 |

**Performance observation:** operations listing repeats actor/grant authorization lookups per returned row, including the lookahead row. This is a bounded N+1 pattern, not a query-count pass claim. Security checks were retained and no optimization was made during this validation. Detail/history counts are fixed. Broader review of this list cost remains outstanding; public query architecture was unchanged.

## 11. BROWSER VALIDATION

Real browser login and navigation passed for editor, publisher and qualified reviewer. Editor list/detail/history loaded without privileged controls; publisher withdrawal submitted the real form, redirected and displayed withdrawn/draft/revision 5 plus the new actor-3 event. The public disease page displayed zero treatments. Reviewer approved reset displayed pending_review/unpublished/revision 5 plus new review_reset/submitted events and the historical approval. Actual DB inspection independently confirmed both browser writes and immutable prior events. A wrong-scope account's direct operations-detail request displayed safe denial.

Browser console inspection returned no warning/error entries or CSP violations. HTTP logs showed no application PHP warnings/notices/fatals. Forms contained CSRF and expected-state/revision fields. No inline-JS change was made. Success screenshots are intentionally retained in the two named PNG artifacts.

## 12. TEST RESULTS

All final commands used PHP 8.2.0 and exited 0. No assertion failed; embedded/rerun suites are not double-counted:

| Suite | Distinct passing checks |
| --- | ---: |
| Current live HTTP/MariaDB/browser-persistence/cleanup checks | 171 |
| treatment-operations | 127 |
| treatment-creation | 110 |
| treatment-admin-hardening | 39 |
| treatment-review | 130 |
| treatment-editorial | 73 |
| treatment-governance | 88 |
| treatment-prerequisites | 29 risk + 7 lifecycle + 11 comparison |
| treatment-publication | 105 |
| treatment-publication-correction | 17 |
| auth-phase1 | 45 |
| Application regression subtotal | **781** |
| **Current distinct total** | **952** |

The historical 4B-2-L 80 live checks were not rerun or added to this total. Schema statements are not counted as assertions. Browser visual observations are documented separately; database persistence checks for the two browser mutations are included in the 171.

Initial harness notes: per-command sandbox MariaDB processes were unreachable from separate PHP processes (2002 connection prechecks); the standalone host process pattern succeeded before any schema import. The first runtime harness emitted an `end(events(...))` notice; this was corrected to a local array variable, followed by warning-free persisted-lifecycle/event verification with warnings promoted to exceptions. No application warning, schema failure or invariant failure occurred. The live lifecycle itself and all assertions passed; the harness correction did not change application code or weaken tests.

## 13. STATIC CHECKS

All 12 temporary PHP validation utilities passed syntax checks before removal. No application PHP file changed. `git diff --check` passed. Task-start SHA256 comparison preserved every pre-existing inventoried app/route/schema/test/bootstrap/config/documentation file, with no deletion. Final cleanup verification found zero temporary processes/listeners and no temporary directory.

## 14. CORRECTIONS

None to application code, schema, configuration or existing tests. Only temporary harness setup and notice corrections were made. No feature development, locking redesign or performance optimization occurred.

## 15. CLEANUP

The disposable database was dropped and absence verified through information_schema. The temporary MariaDB server shut down; the temporary PHP server stopped. Any remaining verified disposable MariaDB process was terminated using its exact datadir command line. A final host process/listener inspection found no validation processes or listeners on 3322/8132.

The browser tab closed. The exact project-local temporary directory was resolved and checked against the workspace before recursive removal. Datadir, generated my.ini, utility PHP, synthetic credentials, cookie jars, sessions, server/test logs and fixture files were removed. Only the five audit artifacts below remain. Normal KOBI credentials/configuration/database and application files were untouched.

## 16. GIT

No commit, push, reset, checkout, merge, rebase, amend or history mutation occurred. Working-tree changes from this validation consist only of five newly created audit artifacts; all pre-existing inventoried files remain unchanged.

## 17. PUBLICATION

**Five synthetic protocols were explicitly published during fixture setup using the existing canonical workflow**, solely in the disposable database. They never escaped application HOLD and were removed with that database. No normal/public content was published, no automatic publication occurred, and the withdrawn fresh-review lifecycle stopped at approval/unpublished.

## 18. DOCUMENTATION / AUDIT ARTIFACTS

New files, all under `D:/xampp/htdocs/kobi/docs/`:

1. `TREATMENT_PHASE_2C4B3L_LIVE_VALIDATION.md` — this report.
2. `TREATMENT_PHASE_2C4B3L_IMPORT_RESULTS.json` — exact executed schema statements, omissions, schema hash and mode metadata.
3. `TREATMENT_PHASE_2C4B3L_RUNTIME_RESULTS.json` — all 171 checks, actual metadata/rows, query counts, regression results, harness hashes/notes and cleanup evidence; no credentials/cookies/session tokens.
4. `TREATMENT_PHASE_2C4B3L_BROWSER_WITHDRAWAL.png` — browser withdrawal success/history.
5. `TREATMENT_PHASE_2C4B3L_BROWSER_RESET.png` — browser approved-reset success/history.

Existing preflight and historical audit documents were not modified. No pre-existing files were deleted.

## 19. FINAL 4B-3-L STATUS

**PASS for the requested targeted live-validation scope.** No authorization, CSRF, independent-review, state/approval/event integrity, transaction or public-boundary blocker was found. The measured bounded operations-list N+1 remains a documented performance concern, not an assertion that query scaling passed. This report does not certify full production/concurrency validation.

## 20. REMAINING

4B-L remains unimplemented. No high-volume concurrency, editor/reviewer/source races, deadlock stress, isolation-level experiments, locking redesign or load testing was performed. The list permission-query scaling observation needs separate review. The existing duplicate/idempotency limitation remains: application repeat detection is not a database uniqueness guarantee or durable idempotency key. Stop after this targeted validation.
