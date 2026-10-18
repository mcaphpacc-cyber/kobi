# KOBI Core — Phase 2C-4B-L validation

## 1. STATUS

**PASS WITH KNOWN LIMITATIONS.** One confirmed HIGH stale-submission concurrency defect was corrected and reproduced successfully after correction. No unresolved CRITICAL/HIGH finding remains in the tested scenarios. This is bounded validation, not a guarantee for every possible schedule or deployment.

## 2. ENVIRONMENT

Windows; PHP 8.2.0; MariaDB 10.4.27-MariaDB; disposable database `kobi_phase2c4bl_validation_20261009`, exclusively at `127.0.0.1:3323`. Four independent PHP HTTP processes used ports 8133–8136. Sessions, cookies, logs, synthetic credentials and data were confined to `tests/.phase2c4bl` and removed.

Authoritative `database/structure.sql` was unchanged. All 111 non-SQL-mode schema statements executed successfully. Actual metadata captured 48 table/view objects, 69 foreign keys, 23 CHECK constraints, indexes and both source self-replacement triggers. Foreign-key checks were enabled after import. Session and isolated-server modes remained `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`. No normal server/application configuration or persistent SQL mode changed.

Exactly these directives were omitted from the import stream, to validate the objects under the existing non-strict mode:

```sql
-- structure.sql line 9
/*!40101 SET SQL_MODE=''*/;
-- line 13
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
-- line 1089
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
```

No other schema statement was omitted or changed. Import SQL text, execution order, source hash and actual metadata are retained in the runtime artifact. No non-SQL-mode compatibility failure occurred. An initial unavailable background server caused connection error 2002 before any DDL; a tracked foreground server resolved that harness startup issue.

## 3. BASELINE

All 781 established distinct checks passed before stress. Every suite exited 0, with no recorded failure/warning/fatal. Embedded reruns are not counted twice. Risk, lifecycle and comparison are bundled in `treatment-prerequisites.php`.

## 4. CONCURRENCY HARNESS

80 overlapping race groups launched 318 PHP worker processes. Each worker connected independently, recorded its connection ID, wrote a ready marker and waited for a shared filesystem release barrier. Workers captured start/end times, application outcomes, native database errors and transaction closure. Every group had a positive operation interval overlap; outcomes include successful reads and safe rejected commands.

Write workers called the actual governance/editorial services and repositories using synthetic authenticated actors with persisted, scoped grants. HTTP workers used real login/CSRF/session flows and four independent application servers, avoiding Windows PHP's single-server request serialization. Fixture IDs were returned by canonical commands and passed explicitly to workers. Publication was an isolated CLI fixture command; no publication HTTP capability or persistent public-mode change was added.

Temporary repository adapters preserved SQL/lock order and added bounded pauses after actual locks, markers and controlled after-event failures. A deterministic reset reproduction paused the approving worker after its first non-locking protocol read but before permission/system/protocol locks. A second connection committed the reset, then released the old-snapshot worker. The same schedule was run before and after correction.

After every race, MariaDB protocol/item/source/event rows, revision, approval, publication/locales, event actors/order/count and applicable snapshots/hashes were inspected. Earlier event rows were compared byte-for-byte with subsequent persisted rows. Public checks used both the unchanged HOLD policy and a test-only governed policy against the actual consistent-snapshot repository. No production test endpoint was created.

## 5. CONCURRENCY RESULTS

| Class | Observed result |
|---|---|
| A | Two/three editors, protocol versus item edits, identical/different item additions: one winner per expected revision; safe stale rejection; one revision/event increment. |
| B | Three submissions: one current submission; losers rejected as stale; stored snapshot/hash coherent. |
| C | Two independent reviewers approving/requesting changes: one permitted decision; repeated/stale approvals denied; correct approval pointer/hash. |
| D | Same publisher, two publishers, valid versus stale withdrawal: exactly one withdrawal, same revision, cleared approval/locales, public DENY. |
| E | Published withdrawal versus edit: withdrawal retained; published/stale edit rejected. |
| F | Approved/unpublished and already-published publication/withdrawal races: valid ordering. Publication may finish before withdrawal or become a no-op; withdrawal cannot be reversed by stale publication. |
| G | Pending reset versus approval, approved reset versus old approval, pending reset versus unsupported submission: safe after correction. Approved reset creates a fresh submission; superseded submission cannot approve it. |
| H | Published reset rejected while withdrawal succeeds. No impossible state was fabricated. |
| I | Source withdrawal versus public read and approval/publication: final invalid source denies exposure; source invalidation clears current approval and preserves historical events. A read may return the valid pre-commit consistent snapshot. |
| J | Source withdrawal versus replacement creation and versus verified replacement reference: historical predecessor retained; new source initially unverified, independently verified; valid finite chain; revised content requires fresh review. |
| K | Permission revocation/user deactivation versus edit: the operation either commits before revocation under its locks or is denied; future operations denied; no authority expansion. |
| L | Forged scope/reviewer/publisher/permission identifiers rejected; actual wrong-system actor denied; cross-protocol item injection denied without mutating the other protocol. |
| M | Parallel valid/invalid/missing CSRF: HTTP 302/403/403; only valid request changes revision/history. |
| N | 5/10/20-worker revision storms: one winner and one revision increment; stale losers rejected. |
| O | Ten mixed approval/reset/changes requests: one permitted transition; coherent final state and history. |
| P | Identical concurrent drafts: one created, one rejected. Existing treatment-system row locking serializes application duplicate lookup in this tested path. Database logical-content uniqueness/idempotency remains absent. |
| Q | Edit, submit, approve, withdraw and approved-reset failures injected after actual event insertion: failed transaction fully rolls back; related valid worker succeeds. Reset failed after its new submission, covering the multi-event rollback. |
| R | Related edit/item, withdrawal/publication and approval/reset operations: no native deadlock observed. Separately induced real deadlock exercised existing rollback/retry safely. |
| S | Held protocol lock with session-only timeout 1 second: native 1205, generic HTTP 503, no partial writes; retry succeeds after holder releases. |
| T | Reads alongside edit/submit/approve/withdraw/source invalidation/reset: valid consistent snapshots or DENY; no mixed approval/hash/source projection; HOLD always blocks. |
| U | 10/20/50 concurrent readers with withdrawal: no reader errors; deliberate projection allowlist retained. 269 recorded read samples included 197 governed test projections. |
| V | Disease/comparison HTTP reads raced with all six write types: HTTP 200, no raw errors, shared HOLD boundary; neither exposed treatments. |
| W | Operations/list/detail/history during appends; final 57-event history paginated exactly once in descending ID order without cross-protocol leakage. |
| X | Concurrent errors classified 403 permission denial, 503 missing permission table, 500 unexpected after-insert exception, 422 invalid value, 409 stale revision. Error paths left no writes and no raw detail. |
| Y | Historical events remained byte-for-byte unchanged throughout subsequent races; IDs ordered and not reused; snapshot/event identities and hashes valid. Rollback auto-increment gaps are expected. |
| Z | Final aggregate/approval/locales/relationship/orphan/public-boundary audits passed for all 97 disposable protocols. |

The audit recorded 969 assertion executions, including repeated final audits and per-worker checks; these are **not** 969 distinct regression scenarios. One rejected scheduling assertion is retained separately and excluded from passing assertions. The fault-injection loser had originally received a correct stale rejection before reaching the planned fault; all five Q cases were repeated with a first-lock marker. An earlier nonexistent test-only item validator was corrected in the temporary harness. Neither issue changed application assertions or concealed a governance finding.

## 6. DATABASE INTEGRITY

Final state: 97 protocols, 605 events; 70 unpublished, 24 withdrawn, 3 published. All current approval pointers reference an approved event for the exact protocol/revision and match the canonical current snapshot hash. Material-change revisions are coherent; command/event state transitions, item ownership, source relationships and locales are valid. No orphan protocol relationships, items, events, actors or references were detected.

All event snapshots retained valid SHA-256 hashes and matching protocol/revision identities. Historical submissions/approvals remain historical after invalidation/reset. The pre-correction disposable reproduction rows were preserved as evidence until the database was dropped; they are not normal application data. Schema source hash before/after matched. Actual integrity constraints and source triggers remained present; both self-replacement triggers rejected writes with SQLSTATE 45000 and left no accepted change. No event-immutability trigger was invented: append-only behavior was verified through observed application writes and retained row comparisons.

## 7. PUBLIC BOUNDARY

Actual disease/comparison HTTP/browser paths stayed on HOLD and displayed no treatments. CLI probes injected a governed policy solely to exercise eligible projection and denial against actual MariaDB snapshots. Emitted projection keys matched the deliberate allowlist; included data required a valid approval/revision/hash and verified sources in the same read snapshot. Source invalidation denied subsequent reads. Public-only and comparison control reads left governance-table digests unchanged; the actual repository issued only read queries in read-only consistent transactions. Its query count remained eight for batched candidate retrieval.

## 8. AUTHORIZATION / SECURITY

Persisted user/grant scope was authoritative. Revoked/inactive users lost future command access; an administrator had no ordinary editorial bypass. Forged top-level identities/scope and another protocol's item did not confer access. Authentication, CSRF, stale-state and controlled-value validations were exercised with real HTTP sessions. Permission infrastructure failure remained 503 rather than 403, and unexpected failure remained 500. No SQLSTATE, PDO exception, private failure text or stack output appeared in captured response bodies. Browser login/logout and session continuity worked.

## 9. DEADLOCK / LOCKING

No native deadlock occurred in normal governance races. A disposable test deliberately locked two protocol rows in opposite order, producing exactly one real `40001 / 1213`; the unchanged repository retried after rollback, and both read-lock transactions finished without writes. This deliberate order is a harness probe, not evidence that production commands take that order.

Two held-lock HTTP probes produced safe infrastructure failure; native evidence captured `HY000 / 1205`. The setting was connection-local, never global. The subsequent valid write succeeded. Fresh final connections reported timeout 50 for both session and global values. No request hung; the longest recorded worker operation was approximately 3.012 seconds, including the deliberate three-second holder. All transactions closed and cleanup left no locks/processes/listeners.

## 10. PERFORMANCE OBSERVATIONS

Existing admin scaling remains LOW: 108 queries for 51 fetched/50 displayed protocol rows, consistent with the earlier 32-query/13-row observation. No optimization was made and no correctness/security consequence was demonstrated. Public aggregate retrieval remained eight queries with batching. This bounded run is not a throughput benchmark or unrestricted load test.

## 11. FINDINGS

- **HIGH, corrected — superseded submission accepted after concurrent reset.** Original race protocol 2308 reset from approved to pending and created submission 108, but the concurrent old-submission-105 request committed approval 109. Deterministic reproduction then repeated the issue with old/fresh submission 115/118. The command's early protocol SELECT established an older repeatable-read snapshot; later `latestSubmission()` read that snapshot despite the protocol locking read observing the new pending state.
- **LOW, known limitation — no database content uniqueness/idempotency.** The tested creation path prevented the duplicate through existing system-row locking and the service repeat check. Other writers or a future path are not protected by a logical-content unique constraint. No schema change was made.
- **LOW, known performance concern — operations listing query scaling.** Unchanged, separately trackable.
- Controlled deadlock/timeout retry behavior is a safe infrastructure outcome, not observed corruption. Temporary harness startup/validator/scheduling issues were separated from the confirmed application defect.

## 12. CORRECTIONS

Modified exactly two pre-existing files during this task:

1. `app/Repositories/TreatmentGovernanceRepository.php`: `latestSubmission()` appends `FOR UPDATE` only when the shared connection is already in a transaction. Governance commands already hold the protocol lock, so the lookup now sees the latest committed submission. Presentation remains non-locking. Both approval and request-changes use this method.
2. `tests/treatment-governance.php`: added three regression checks for superseded-submission rejection, command current-read behavior against an older snapshot model, and non-locking presentation lookup.

No service/controller/public model/HOLD/route/schema/configuration change was made. The deterministic replay after correction rejected the old submission 125 after fresh submission 128 and left pending_review with a null approval. All subsequent bounded live groups and the full established baseline passed.

## 13. REGRESSION RESULTS

| Suite | Before distinct | After distinct | Exit before/after | Failures |
|---|---:|---:|---|---:|
| treatment-operations | 127 | 127 | 0 / 0 | 0 |
| treatment-creation | 110 | 110 | 0 / 0 | 0 |
| treatment-admin-hardening | 39 | 39 | 0 / 0 | 0 |
| treatment-review | 130 | 130 | 0 / 0 | 0 |
| treatment-editorial | 73 | 73 | 0 / 0 | 0 |
| treatment-governance | 88 | 91 | 0 / 0 | 0 |
| prerequisites: risk/lifecycle/comparison | 29 + 7 + 11 | 29 + 7 + 11 | 0 / 0 | 0 |
| treatment-publication | 105 | 105 | 0 / 0 | 0 |
| treatment-publication-correction | 17 | 17 | 0 / 0 | 0 |
| auth-phase1 | 45 | 45 | 0 / 0 | 0 |
| **Distinct total** | **781** | **784** | **all 0** | **0** |

Embedded dependent-suite repetitions and live repeated audit assertions are not added to this distinct total. Existing assertions were not weakened.

## 14. STATIC CHECKS

Both modified PHP files passed PHP syntax checks. Temporary executable PHP utilities also passed syntax or executed successfully and were removed. `git diff --check` exited 0. References to `latestSubmission()` were inspected: approval, changes-required and presentation callers retain their signatures. A pre-task versus final file-hash comparison identified exactly the two intended PHP modifications plus the three new audit artifacts. Existing unrelated working-tree changes were preserved.

## 15. CLEANUP

Guarded disposable database DROP completed and absence was verified through information_schema before isolated MariaDB shutdown. Four temporary HTTP processes were stopped only after matching their exact validation router command lines. Worker processes finished. No listeners remained on 3323 or 8133–8136 and no matching validation PHP/MariaDB processes remained. The exact workspace-contained temporary directory was checked before recursive removal. Datadir, credentials, sessions, cookies, utilities and logs were removed. Browser was logged out, its credential binding cleared, and its validation tab closed. The normal KOBI database was never selected or written.

## 16. GIT

No commit, push, reset, checkout, merge, rebase, amend or history mutation. This task changed only the two PHP files listed above and created the three audit artifacts below. The workspace already contained extensive earlier modified/untracked files; these were not treated as task changes or discarded.

## 17. AUDIT ARTIFACTS

Intentionally retained, uncommitted:

- `docs/TREATMENT_PHASE_2C4BL_VALIDATION.md` — this report.
- `docs/TREATMENT_PHASE_2C4BL_RUNTIME_RESULTS.json` — import stream and omissions, actual metadata/final states/events, before/after defect evidence, regression/static/browser results and cleanup evidence.
- `docs/TREATMENT_PHASE_2C4BL_CONCURRENCY_RESULTS.json` — barrier timing, connection identities, worker outcomes, native errors, per-race persisted snapshots and assertion records.

Temporary harness SHA-256 values are retained, but scripts, secrets and sessions are not. Evidence contains synthetic fixture data only.

## 18. FINAL 2C-4B-L STATUS

**PASS WITH KNOWN LIMITATIONS.** Confirmed HIGH defect corrected; no unresolved CRITICAL/HIGH finding in tested coverage. Regression, live persistence, public security boundary, browser, static and cleanup checks passed.

## 19. REMAINING LIMITATIONS

The run covers bounded adversarial schedules on Windows/PHP 8.2.0/MariaDB 10.4.27, not exhaustive interleavings, production-volume load, another isolation level/version, or every deployment. The database still lacks logical draft-content uniqueness/idempotency. Existing admin query scaling remains. Governed exposure was tested through an injected CLI policy; deployed HTTP HOLD remains active. Native deadlock evidence was deliberately induced, not naturally observed. No requested class was left wholly deferred; unsupported reset/publication combinations were tested as safe rejections instead of fabricated transitions.

## 20. RECOMMENDATION

Treatment Governance 2C-4B passed this hardening-validation scope after the targeted correction. It is reasonable to proceed to a separately authorized next roadmap phase after developer review of the two-file change; this validation did not start that phase or authorize lifting HOLD. Track database idempotency/uniqueness and admin query scaling as separate decisions. Do not claim exhaustive or unconditional production hardening from this bounded run.
