# Phase 2C isolated live validation — 2026-10-08

**Verdict: VALIDATION PASSED WITH DEFERRED ITEMS.**

This report supersedes the earlier blocked precheck report. The user's explicit
SQL-mode import clarification resolved that blocker. No safety stop condition
occurred. Deferred stress/browser cases and one minor UI finding are listed below.

## Environment and schema execution

| Item | Actual result |
|---|---|
| Host | `localhost:3306` |
| MariaDB | `10.4.27-MariaDB` |
| Session/global SQL mode before and after | `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION` |
| Isolated database | `kobi_phase2c_validation_20261008` |
| Engine/collation precheck | InnoDB DEFAULT, transactions/XA/savepoints supported; utf8mb4_general_ci available |
| Schema import | PASS: all 111 parsed SQL statements executed successfully |
| Browser target | Loopback-only `http://127.0.0.1:8127`, separate sessions/logs |
| Cleanup | Server stopped, generated sessions/logs removed, isolated database dropped and absence verified |

The database was absent before creation. The importer refused to overwrite an
existing database and verified the selected database before executing SQL. No real
KOBI rows were copied. Every fixture/service/HTTP connection targeted the named
disposable database. Credentials/configuration were not edited or printed.

All tables, indexes, unique keys, FKs, CHECKs, both triggers, the composite approval
FK ALTER and the view were imported exactly as defined. Other dump session-control
SQL was executed unchanged. DELIMITER commands were interpreted as client syntax.
No non-SQL-mode statement was omitted or changed, and none failed.

Only these approved executable directives were omitted from the import stream:

```sql
-- database/structure.sql line 9
/*!40101 SET SQL_MODE=''*/;
-- line 13
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
-- line 1078
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
```

Reason: validate the objects under the server's existing mode. The dump clears the
mode before saving it, so its restoration would restore that cleared value. The
authoritative file was unchanged; no session/global/persistent SQL-mode mutation
occurred. No schema compatibility correction or weakened constraint was needed.

Schema SHA-256 before/after:
`ec84a58e3b5a107604108cf25e9d9663b8f1a06fc3e3a5989fff556f964d7cff`.
The exact ordered executed statements and omissions are in
[the import journal](TREATMENT_PHASE_2C_IMPORT_RESULTS.json). Cleanup DROP DATABASE
is separately recorded in [final database results](TREATMENT_PHASE_2C_FINAL_DATABASE_RESULTS.json).

## Actual metadata, triggers and constraints

**PASS:** SHOW CREATE TABLE, SHOW FULL COLUMNS, SHOW INDEX and information_schema
queries inspected installed objects, including all five foundation tables and
governance columns on protocols/items. Actual types, nullability, defaults,
collations, indexes, generated active_slot, unique keys, FK actions, CHECKs and
trigger bodies are retained in [runtime results](TREATMENT_PHASE_2C_RUNTIME_RESULTS.json).
That artifact records tested SQL, expected result and actual MariaDB error.

| Test group | Result |
|---|---|
| Both named source triggers | PASS: AFTER INSERT/UPDATE metadata, bodies and stored SQL_MODE verified |
| Self-replacement INSERT / UPDATE | PASS: error 1644, SQLSTATE 45000, `A treatment source cannot replace itself.`; rejected insert left no row |
| Valid source and A/B/C chain | PASS: accepted |
| General replacement cycle | PASS at service boundary: database permits a multi-node cycle, service rejects it; valid finite chain accepted; triggers do not provide general cycle prevention |
| 17 representative CHECK cases | PASS: error 4025 for revision, approval/publication coherence/locales, source identity/lifecycle metadata, inclusion flag, event actor/revision/JSON/snapshot/rationale/approval fields, grant capability/qualification and blank protocol/item claims |
| 13 FK cases | PASS: invalid protocol disease/system, item parent, protocol/item relationships and sources, event parent, permission user/system and composite approval references rejected with 1452; restrictive source/protocol deletes rejected with 1451 |
| Grant/reference uniqueness | PASS: duplicate active grant and protocol/item source relationships rejected with 1062 |
| Controlled ENUM/SET service validation | PASS: invalid evidence, purpose, item type and locale rejected before persistence |
| Permissive ENUM database behavior | OBSERVED: invalid evidence/purpose SQL values coerce to empty ENUM values; isolated test rolled back. This is not a database CHECK pass; application validation is required and passed |
| Positive/unique included order | PASS at service boundary: zero/duplicate order rejected; DB CHECK enforces inclusion flag, not positive/unique order |

## Live service workflows

**58 core live checks passed**, using actual repositories, services, prepared
queries and MariaDB transactions. The harness supplies actor identity; user/grant/
system lookup and governance logic remain real. Normal authentication is separately
exercised in browser/HTTP testing.

| Workflow | Status and actual result |
|---|---|
| Legacy initialization | PASS: draft/unpublished revision 1, NULL approval, empty locales, not_assessed, excluded items; unknown-actor legacy event; no fabricated approval/reviewer/references |
| Editorial save | PASS: revision 1 -> 2, updated_by editor and history; no-op unchanged; stale save rejected without overwrite |
| Items | PASS: explicit inclusion revises parent; legacy unused item stays excluded; type/order validation rejects invalid input |
| Source attachments | PASS: verified protocol/item references revise once each; duplicate, blank claim and withdrawn-source attachment rejected |
| Source lifecycle | PASS: unverified -> verified, unverified -> withdrawn and verified -> withdrawn; restore rejected; verification explicit |
| Submission | PASS: pending/current revision, persisted event, canonical JSON/SHA-256, locales, unchanged revision/unpublished |
| Reviewer authorization | PASS: actual-system qualified reviewer allowed; editor-only, wrong-system, revoked and ordinary actors denied |
| Independence | PASS: material author denied despite capability/attestation; missing attestation denied; explicit unknown-legacy attestation persisted |
| Approval | PASS: one event, coherent pointer, rationale, independence, UTC DATETIME(6), valid snapshot/hash; revision retained, unpublished |
| Stale/duplicate decisions | PASS: prior state/revision rejected after edit or approval; no overwritten content/extra approval |
| Snapshot/hash | PASS: unchanged content same hash; title/overview/item/claim/bibliographic version changes block same-revision approval |
| Live source status | PASS: current unverified/withdrawn status blocks approval; status is intentionally outside bibliographic hash and checked separately; no silent verification |
| Changes Required | PASS: reason event, unchanged decision content/revision, unpublished; uncorrected resubmission denied; corrected fresh revision submitted/approved |
| Expiry | PASS: past due approval rejected; isolated historical expired approval appears in due filter and fails existing core eligibility; no successful publish action run |
| Approval/changes rollback | PASS: failure injected after event insert rolls back event and state/pointer writes |
| Withdrawal rollback | PASS: injected failure rolls back source, affected protocols and history |
| Source invalidation | PASS: pending/approved content invalidated, approval cleared, revision/history/relationships retained; stale approval and restore denied |

The initial legacy-initialization harness attempt was denied because the real
administrator allowlist is empty. The harness was corrected to inject an isolated
administrator policy for user 4; application configuration remained unchanged.
This setup denial is not reported as an application failure.

Historical expiry/publication scenarios used explicit synthetic SQL fixtures only.
No prior event was edited by the review UI/services and no publication feature was
added. The existing publish method was invoked only in an expiry-rejection test.

## Concurrency and final persistence

**PASS:** Two independent PHP processes/connections synchronized different
reviewers against one pending revision. Their operations overlapped. One committed
event 38; the other rejected with `Treatment content changed. Reload and retry.`
Exactly one approved event/pointer remained, unpublished. Process timestamps and
outcomes are in [concurrency results](TREATMENT_PHASE_2C_CONCURRENCY_RESULTS.json).

**8 final checks passed:** browser rows 901/902 persisted approved/unpublished at
revisions 4/5, reviewer 14, explicit independence, correct snapshot/hash/rationale
and UTC expiry `2027-10-08 12:00:00.000000`. Source withdrawal then withdrew a
synthetic historically published fixture and invalidated other approvals sharing
that source. The rejected self-insert left no row; schema hash and modes matched.
[Final database results](TREATMENT_PHASE_2C_FINAL_DATABASE_RESULTS.json) preserve these checks.

## Browser and HTTP results

Synthetic users registered and logged in through normal application forms; fixture
email verification/grants were prepared separately in the disposable database.
Browser evidence came from rendered DOM/accessibility state and screenshot.

| Requested browser steps | Status / evidence |
|---|---|
| 1-7 login/list/system filter/open/edit/save/revision | PASS: protocol 901 revision 1 -> 2 |
| 8-10 include item/attach verified source/submit | PASS: explicit inclusion and protocol/item source claims; submitted revision 4 |
| 11-17 reviewer login/list/detail/items/sources/approve/state | PASS: Match, submitted snapshot and live verified provenance visible; Approved / Unpublished |
| 18-22 second submit/changes/editor correction/fresh submit | PASS: protocol 902 Changes Required revision 4, correction revision 5, fresh submission and fresh browser approval |
| 23 wrong-system access | PASS: browser detail denied; valid-CSRF forged HTTP decisions denied |
| 24-27 stale save/approval, GET, CSRF | PASS at HTTP boundary; native stale/forged-form clicks DEFERRED |
| 28-29 public disease/hold | PASS: anonymous Treatments 0 with draft/pending/approved-unpublished fixtures present together; no governed titles/reviewer content |
| Anonymous review route | PASS: login redirect |
| CSP/JavaScript | PASS in observed pages: no console warnings/errors, zero inline scripts/event handlers on review/public pages; CSP untouched |

**23 HTTP security checks passed** with actual synthetic browser login sessions:
missing/invalid CSRF 403, GET mutation URLs 404, stale save/approval 409, wrong-system
detail and valid-CSRF decisions 403. Protocol/event hashes were unchanged. No fake
login session was injected; no session ID/CSRF value was printed or saved in results.
[HTTP results](TREATMENT_PHASE_2C_HTTP_RESULTS.json) record each case.
[Public fixtures](TREATMENT_PHASE_2C_PUBLIC_FIXTURES.json) record simultaneous states
902 draft, 903 pending_review and 904 approved, all unpublished. Browser inspection
confirmed none appeared, with Treatments 0 and a clean console.

## Deferred items and finding

**DEFERRED, not PASS:**

- Forced MariaDB deadlock 1213 and bounded retry under contention.
- True simultaneous editor-vs-approval and withdrawal-vs-approval stress races.
  Sequential/current-state rejection and withdrawal rollback passed; competing
  reviewers were truly concurrent.
- Native browser submission of already stale forms and manually forged CSRF
  forms. Equivalent real-session HTTP requests passed; stale services were
  tested against MariaDB.

**Minor presentation defect, left unchanged:** an approved review detail still
shows `Approval prerequisites need attention: Treatment review is not ready for
an approval decision.` The shared pending-review predicate correctly prevents
another approval, but the alert is confusing after success. Actual Approved /
Unpublished state, hidden decision forms and database behavior were correct.

No required constraint failure, compatibility error, unauthorized/stale success,
duplicate concurrent approval, surviving invalid approval, public exposure or CSP
weakening occurred. No application fix was made in this validation-only task.

Publication exceptions for insufficient/unsupported evidence, wellness and
higher-risk content remain unresolved and fail closed. No universal maximum review
interval or qualification taxonomy was invented. Resolve those policies, review
operational qualification grants and finish deferred checks before separately
authorizing Phase 2C-4.

## Files, verification and cleanup

Created validation utilities only:

- `tests/phase2c-live-import.php`
- `tests/phase2c-live-runtime.php`
- `tests/phase2c-live-concurrency.php`
- `tests/phase2c-browser-router.php`
- `tests/phase2c-browser-fixtures.php`
- `tests/phase2c-live-http.php`
- `tests/phase2c-live-finalize.php`
- `tests/phase2c-public-fixtures.php`

Created this report and the six linked JSON result artifacts. The earlier blocked
report remains a historical precheck record. All eight utility PHP syntax checks
and `git diff --check` passed. The earlier 88 foundation, 73 editorial, 107 review
and 45 authentication results were not rerun or relabeled as live evidence.

Only the disposable database received DDL/data writes. It was dropped after
preserving results. Its test server, temporary browser tab and generated sessions/
logs were removed; no project source file was deleted. Application source, schema,
persistent configuration and existing KOBI database were unchanged. No Git mutation,
SQL-mode change or Phase 2C-4 implementation occurred.
