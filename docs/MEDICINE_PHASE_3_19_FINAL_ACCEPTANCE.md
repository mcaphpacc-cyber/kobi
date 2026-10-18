# Medicine Phase 3.19 final acceptance

**PHASE 3.19 — FULL ACCEPTANCE PASS**

Date: **2026-10-11 Asia/Calcutta**. All required regressions, the corrected read-only readiness audit, the deployment verifier and certified trigger metadata checks pass. Normal KOBI's complete before/after database snapshots match exactly. Only validation code and this report changed.

## Previous blockers and authorized scope

Phase 3.19 had already deployed with four statements, zero warnings/errors, 55 tables and 11 triggers. The earlier utf8 rehearsal versus utf8mb4 normal trigger metadata difference was closed by causal/behavioral investigation and operator tooling correction, without normal corrective DDL. The remaining blockers were two failing regression inventory test cases and the readiness audit's obsolete schema-absence prerequisite.

This task performed no normal database DDL, data writes, authority operations, restoration, application feature changes or Git mutations. Before MariaDB testing, the user explicitly clarified the conflicting testing restriction:

> Allow isolated test DDL/writes only; normal KOBI stays read-only

All synthetic schema/data writes were confined to the established guarded disposable regression instances. No Phase 3.22 objects were deployed to normal KOBI.

## Exact stale inventory corrections

Authoritative source: the existing, unchanged `database/structure.sql`, SHA-256 `52f2d6c6ee8832a2f3114845e18fa8ce4359ebb023a23f614a5d59eb948f473c`. The existing MariaDB suites import this full schema into disposable databases. It contains the two Phase 3.22 qualification/appraisal tables and four associated immutable triggers as well as Phase 3.19. Their presence in disposable full-schema tests is distinct from normal KOBI deployment.

| Test file and failing case | Old expectation | Authoritative disposable import | Minimal correction |
| --- | --- | --- | --- |
| `tests/medicine-policy-mariadb.php`: `pilot actual metadata FKs checks indexes triggers` | 8 triggers matching `medicine_%` | 12 | Exact count 8 → 12 |
| `tests/medicine-pilot-mariadb.php`: `fresh schema all Medicine constraints indexes FKs and immutable triggers` | 13 matching Medicine tables; later 8 `medicine_%` triggers | 15 matching tables; 12 matching triggers | Exact counts 13 → 15 and 8 → 12 |

These are **two previously failing test cases containing three stale scalar count expectations**. The pilot case previously failed at the first table count before reaching its also-stale trigger count. Both were independently demonstrated against the unchanged authoritative source and actual disposable metadata during the prior investigation.

Two tables explain 13 → 15: `medicine_reviewer_qualifications`, `medicine_evidence_appraisals`. Four triggers explain 8 → 12: `medicine_qualification_no_update`, `medicine_qualification_no_delete`, `medicine_appraisal_no_update`, `medicine_appraisal_no_delete`.

No count became a minimum/range. No assertion, case, security gate or constraint/index/FK comparison was removed. The separate exact count of three `provisional_identity_%` triggers remains unchanged. Short comments distinguish the full-schema disposable inventory from normal KOBI. No unrelated expectations were edited.

Corrected policy MariaDB result: **11 cases / 43 assertions / 0 failures**. Corrected pilot MariaDB result: **15 cases / 141 assertions / 0 failures**; previously unreachable complete table/index/FK coverage now runs.

## Readiness prerequisite correction

The old `tests/medicine-pilot-readiness.php audit` queried persisted Medicine tables and rejected any nonempty result. Its offline test likewise required no persisted Medicine schema. This was a Phase 3.14/3.15 pre-deployment premise; it became obsolete once Phase 3.19 was successfully deployed and certified.

The updated schema gate requires the exact deployed prerequisite using the **existing SELECT/SHOW-only deployment verification library**, not a new application persistence mechanism. It verifies:

- Unchanged Phase 3.19 artifact SHA-256 `84166a59636a8eefcf580bc8bc71b09c19951255a865d2b8a8415ba62a41be48` and authoritative structure block.
- Valid certified metadata reference and unchanged hash-bound semantic/rehearsal certificates.
- Exact current 55-table / 11-trigger inventory, full table definitions/data digests, defaults, privileges and connection/server state against the certified normal snapshot.
- Preservation of all 54 pre-existing tables and eight pre-existing triggers against the original pre-deployment reference.
- Exact Phase 3.19 table definition, empty table and exact three trigger definitions through full snapshot equality.
- Complete normalized `information_schema.TRIGGERS` rows for Phase 3.19, including bodies, timing/events, ordering, orientation, condition, definer, SQL mode and the accepted utf8mb4 creation metadata. Only independent creation timestamps and isolated schema-name fields are excluded in the pre-existing certification normalization.

The audit exports a compact `schema_prerequisite` proof with artifact/definition digests, exact object inventories and creation context. Offline readiness tests now require that exact proof and the exact current Medicine inventory instead of schema absence.

**All other readiness logic is unchanged.** Fixed five-candidate selection, registry state/identity checks, collision/qualified-name/form/relationship guards, missing evidence/reviewer/revision/pilot assessments, configuration gates, SQL-mode stability and unsafe-query rejection still run. Schema verification does not grant roles or establish scientific approval.

The readiness audit exits successfully while all five candidate assessments correctly retain `overall=BLOCKED`, `pilot_gate=BLOCKED`, evidence/reviewer `NOT YET PROVIDED` and revision `BLOCKED`. Audit success means the deployed technical prerequisite is valid; it does not mean those future clinical/governance prerequisites are met.

Final readiness tests: **43 checks / 0 failures / 5 actual candidates / 0 database writes**. Candidate-selection snapshot and readiness snapshot were generated using the existing read-only modes, tested, retained in protected storage and removed from temporary test paths afterward.

## Normal database before and after testing

Target: `medical1`, configured host `localhost`, server `LAPTOP-PTFFHOMV`, port **3306**, **MariaDB 10.4.27**.

- Before: `2026-10-10 21:33:13.599840 UTC` / `2026-10-11 03:03:13.599840 Asia/Calcutta`.
- After: `2026-10-10 21:36:50.477508 UTC` / `2026-10-11 03:06:50.477508 Asia/Calcutta`.
- **55 tables / 11 triggers** before and after.
- Complete `actual` snapshot identity: **PASS** across data, metadata, exact SHOW CREATE trigger results, server/session state, defaults and privileges.
- All 54 original tables' complete counts/digests/definitions remain intact; all eight original triggers remain exact. No unexpected objects, data changes or authority changes.
- New Phase 3.19 table remains **empty**. All existing 26,209 rows retain their full digests.

| Data set | Unchanged rows |
| --- | ---: |
| Registry (`kobi_substances`) | 2,224 |
| Personal medications (`health_medicines`) | 3 |
| Combinations (`kobi_combinations`) | 2,327 |
| Combination members (`kobi_combination_items`) | 6,671 |
| Treatment protocols | 2,150 |
| Treatment items | 3,190 |
| Diseases | 400 |

Existing indexes, FKs, CHECK constraints, routines/events and auth tables/data are unchanged. Hashes of application PHP/JS/CSS, routes, configuration and recorded schema artifacts match the deployment preflight inventory. Authentication configuration/credentials were not changed.

Global/server and normal session SQL mode remain:

`NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`

The protected original and fresh pre-deployment backups retain their verified identities. No new backup/restore or migration was required for this validation-only task. The migration and `database/structure.sql` remain unchanged.

## Complete regression results

All suites below ran in this task. No tests were skipped or deleted. JSON suites report no failures; all required CLI processes exit successfully, with no runtime stderr diagnostics in the final validation transcripts.

| Suite | Cases / assertions or checks | Result |
| --- | --- | --- |
| Provisional identity unit | 43 / 240 | PASS |
| Provisional identity MariaDB | 15 / 65 | PASS |
| Governance unit | 56 / 249 | PASS |
| Governance MariaDB | 38 / 142 | PASS |
| Policy unit | 33 / 92 | PASS |
| Policy MariaDB, including corrected inventory case | 11 / 43 | PASS |
| Foundation unit | 105 / 216 | PASS |
| Foundation MariaDB | 29 / 96 | PASS |
| Pilot unit | 40 / 143 | PASS |
| Pilot MariaDB, including corrected inventory case | 15 / 141 | PASS |
| Candidate selection | 55 checks | PASS |
| Pilot readiness | 43 checks | PASS |
| Evidence readiness | 11 / 134 | PASS |
| Read-only Phase 3.19 readiness audit | Exact deployed schema + unchanged future gates | PASS |
| Phase 3.19 deployment verifier | Complete schema/data/authority/state checks | PASS |
| Trigger metadata verifier | Complete certified information-schema and SHOW CREATE identity | PASS |
| Authentication | 45 scenarios | PASS |

Treatment regression results:

| Suite | Focused results | Result |
| --- | --- | --- |
| Prerequisites | 29 risk / 7 lifecycle / 11 comparison checks, plus existing embedded checks | PASS |
| Governance | 91 | PASS |
| Editorial | 73, plus 91 foundation | PASS |
| Review | 130, plus 91 foundation / 73 editorial | PASS |
| Publication | 105 | PASS |
| Publication correction | 17 new / 105 existing | PASS |
| Creation | 110 focused / 333 embedded | PASS |
| Operations | 127 focused / 443 earlier | PASS |
| Administration hardening | 39 focused, earlier suites retained | PASS |

Embedded cases are not summed as unique tests. Governance concurrency scheduling can change assertion counts by branch; the recorded count for this run is 142, with all 38 distinct cases passing.

PHP syntax checks passed for all three modified PHP files. Final source diffs were inspected against protected pre-edit copies. `git diff --check` passed for tracked changes; the existing untracked validation files were reviewed explicitly with read-only file comparisons. Git's existing LF/CRLF comparison advisory is not a PHP/MariaDB verification warning and did not cause source or Git-state mutation.

### Isolated MariaDB testing boundary

The user-authorized synthetic tests ran only on guarded disposable instances:

- **3325**: foundation suite.
- **3328**: policy, pilot and governance suites, sequentially.
- **3339**: provisional identity suite.

Each suite imported the unchanged full authoritative schema, **148 statements**, into its own disposable database. Only the three established SQL-mode-changing import directives were omitted; the existing approved mode was preserved. Full-schema Phase 3.22 objects existed in these disposable databases only. Synthetic users/grants/evidence/revisions/enrollments used by existing test fixtures never targeted normal KOBI.

Expected negative-test SQL/application rejections were asserted, not suppressed or treated as unexpected failures. There are no unresolved warnings/errors in the relevant acceptance verification. No global/server or project-wide SQL-mode change was made.

After tests, all three owned processes were stopped and their exact guarded project datadirs removed after PID/executable/port/path/reparse-point checks. Protected test evidence and server logs remain retained. No disposable environment remains running.

## Security and governance negatives

Existing unit/MariaDB/Treatment/auth cases continue to reject:

- Unauthorized operations and forged or revoked/expired authority.
- Self-approval and invalid independent-review relationships.
- Stale revisions, concurrent lost updates and registry lifecycle ABA changes.
- Evidence/source/package/hash mutation and forged review/approval fields.
- UPDATE/DELETE of immutable history.
- Restoration of protected withdrawn/terminal provisional histories.
- Invalid automatic restoration and stale invalidation/recovery decisions.
- Enrollment/internal projection without current qualified approval and controlled operator gates.
- Public exposure through disabled Medicine/Treatment gates.
- Unauthenticated/unauthorized account flows and existing authentication regressions.

No security or governance assertion was changed. The corrected readiness schema gate preserves its unsafe-query rejection tests and every original candidate/configuration/readiness gate.

## Authority, Medicine and Treatment status

Account **1** remains the **sole active Medicine Administrator**, grant expiry **NULL**, exactly one unchanged owner-establishment audit event. No contributor/editor, reviewer, delegated administrator, deployment operator or other authority was added or modified. Owner event snapshot SHA-256 remains `41ad91d0341eb8a147429876a43a3b8573b3602cb280cf8439077024b860778d`.

Registry and personal medications remain unchanged. No provisional identity, canonical Medicine Identity, chemical reference, reviewer/evidence appraisal, approval or pilot enrollment was created in normal KOBI. Phase 3.22 reviewer/evidence schema remains absent there.

Medicine Pilot **OFF**. Public Medicine **OFF**. Treatment publication **HOLD**; Treatment data, schema, code, configuration and public exposure remain unchanged. Authentication schema, data and configuration remain unchanged.

All 11 normal triggers remain at the established authoritative **utf8mb4 / utf8mb4_general_ci** creation context, unchanged bodies/definers/SQL mode/ordering. The verifier continues using the certified normal reference and does not reintroduce the old utf8 rehearsal requirement. No actual normal trigger was touched.

## Files modified/created and evidence

Modified validation files only:

1. `tests/medicine-policy-mariadb.php`: exact stale trigger count and explanatory comment.
2. `tests/medicine-pilot-mariadb.php`: exact stale table/trigger counts and explanatory comment.
3. `tests/medicine-pilot-readiness.php`: obsolete schema prerequisite replaced with existing exact certified verification; corresponding offline schema assertions updated. Other readiness gates unchanged.

Created report:

- `docs/MEDICINE_PHASE_3_19_FINAL_ACCEPTANCE.md`.

Operational evidence under `storage/logs/medicine321a-owner-recovery/`:

- `acceptance-original-medicine-policy-mariadb.php`, `acceptance-original-medicine-pilot-mariadb.php`, `acceptance-original-medicine-pilot-readiness.php`: protected pre-edit review copies.
- `phase319-acceptance-before.json/.out/.stderr`, `phase319-acceptance-after.json/.out/.stderr`: exact complete normal snapshots and verifier outputs.
- `phase319-acceptance-regression-summary.json`: parsed JSON suite results.
- `acceptance-regression-<suite>.out` and associated stderr files for all suites named above; final readiness result uses `acceptance-regression-medicine-pilot-readiness-final.out/.stderr`.
- `acceptance-readiness-audit.stderr`, `acceptance-readiness-final-audit.stderr`, `acceptance-candidate-snapshot.stderr`.
- `acceptance-medicine314-snapshot.json`, `acceptance-medicine315-readiness.json`: retained read-only fixtures, temporary test copies removed.
- Archived verified-owned disposable server logs.
- Existing `phase319-final-post.json` was refreshed by the read-only verifier. Historical deployment, blocked and metadata investigation evidence remains retained.

No new application class, feature, dependency, migration, route or schema object was introduced. No database SQL needs to be applied separately. **Normal database changes during this task: none.** Isolated test DDL/synthetic writes occurred only under the explicit clarification quoted above.

## Final Phase 3.19 disposition

**DEPLOYED:** previously authorized Phase 3.19 schema; now fully accepted.

**NOT CREATED in normal KOBI:** provisional identity records, canonical Medicine Identity records, chemical references, reviewer records, evidence appraisal, approvals or pilot enrollment.

**NOT ACTIVE:** Medicine Pilot and Public Medicine. Treatment remains HOLD.

**NOT DEPLOYED to normal KOBI:** Phase 3.22.

**PHASE 3.19 — FULL ACCEPTANCE PASS.** No remaining acceptance blocker. Future evidence/reviewer/approval/activation work is outside this acceptance and remains separately governed. No normal DDL/data/authority change, production application change, restoration or Git mutation occurred. Work stops after this report.
