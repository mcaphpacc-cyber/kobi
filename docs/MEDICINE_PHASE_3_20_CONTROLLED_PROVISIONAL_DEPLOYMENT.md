# Medicine Phase 3.20 — Controlled provisional deployment

Result: **STOPPED BEFORE DEPLOYMENT — AUTHORIZATION PREREQUISITE UNAVAILABLE.**

> Provisional record creation is blocked pending legitimate contributor/operator authorization.

Read-only normal-database inspection completed at **2026-10-09 22:39:18 UTC** (2026-10-10 04:09:18 Asia/Calcutta). No database DDL or data writes were executed. Zero provisional records were created. Phase 3.20 is incomplete; this is a documented stop, not a successful deployment.

## Objective and architecture

The authorized objective was to deploy the unchanged Phase 3.19 additive schema and create exactly five real, unapproved provisional revision-journal records. Option C remains authoritative: registry candidate plus retained evidence feeds a separate append-only journal; future independent appraisal and canonical review remain separate prerequisites. Provisional tokens cannot act as canonical Medicine Identity IDs or verified Chemical References.

The Phase 3.20 specification requires an immediate stop when an authorized contributor/operator is unavailable (sections 11 and 39.6). That prerequisite was checked before backup or deployment to avoid applying DDL for a creation operation that cannot lawfully proceed.

## Inspection and artifact verification

Inspected the requested provisional model, governance service/repository, authorization, eligibility, identity service, canonicalizer, authoritative schema, additive artifact, Phase 3.17/3.18/3.19 documentation and evidence manifest, and relevant provisional/foundation/governance/pilot tests. The normal preflight used only SELECT statements for server metadata, object presence, Medicine table counts and current grant availability. Credentials and personal user records were not printed.

`MedicineGovernanceService::inspectProvisionalIdentityDraft()` and `prepareProvisionalIdentityDraft()` obtain the actual actor through `AuthService::id()`, require an active account, and require a current contributor **or editor** grant scoped to composition/catalog. Administrator, reviewer, owner or deployment authority alone does not authorize provisional authorship. No actor was impersonated, no synthetic test authentication was used against the normal database, and no account/grant/configuration was created or changed.

Validated local hashes:

| Artifact | SHA-256 / result |
| --- | --- |
| `database/structure.sql` | `e9ce3a2be1153845b035207a35a8a319c4d03f5e87f7b96d87da122059081f79` — matches the final Phase 3.19 tested hash |
| `database/medicine-phase319-provisional.sql` | `84166a59636a8eefcf580bc8bc71b09c19951255a865d2b8a8415ba62a41be48` |
| Additive artifact versus tested schema block | Exact byte equality after trimming surrounding whitespace, between the Phase 3.19 start/end markers |
| Phase 3.17 manifest | Pinned service hash `3fc38ca47fa9ace4bd8a9963854e120425909565b2a91e2c8c4ac763da454086` matched |

The additive artifact was not changed. The Phase 3.16 deployment harness pins an older full-schema hash and inventory; it was inspected but not invoked or weakened to accommodate the new schema.

## Observed normal pre-deployment state

| Check | Observed result |
| --- | --- |
| Database | `medical1`, port 3306 |
| Server | `10.4.27-MariaDB` |
| Session SQL mode | `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION` |
| Global SQL mode | `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION` |
| Provisional journal table | Absent |
| Three expected provisional triggers | All absent; no partial deployment among these four expected objects |
| Medicine authority grants | 0 rows |
| Eligible draft authors | 0 distinct active users with current, active, unrevoked contributor/editor composition/catalog grants |
| Configured Medicine owners | 0 |
| Configured registry operators | 0 |
| Configured deployment operators | 0 |
| Pilot intake / internal activation flags | Both false |
| Effective Treatment publication mode | `hold` |

All twelve existing Medicine foundation/governance/private-link tables were observed empty:

`health_medicine_identity_links`, `health_medicine_selected_ingredients`, `kobi_medicine_chemical_refs`, `kobi_medicine_identities`, `kobi_medicine_ingredients`, `kobi_medicine_presentations`, `kobi_medicine_strengths`, `medicine_authority_grants`, `medicine_governance_events`, `medicine_pilot_records`, `medicine_registry_assessments`, `medicine_registry_lifecycle_events`.

This is a targeted prerequisite inspection, not a full deployed-schema metadata comparison or before/after data-digest audit. Those steps were deferred at the authorization stop.

## Backup, deployment and integrity

No fresh backup was created, no backup path/digest is claimed, and no recovery rehearsal was performed in this phase. The authorization stop occurred before the stage requiring a fresh protected backup. Before any later DDL attempt, the established protected backup and disposable recovery rehearsal must succeed and be recorded; prior backup results alone are insufficient.

Deployment statement count: **0**. No deployment warnings or MariaDB DDL errors occurred because deployment was not attempted. Objects created: **none**. The intended unchanged artifact defines only:

- `medicine_provisional_identity_revisions`;
- `provisional_identity_before_insert`;
- `provisional_identity_no_update`;
- `provisional_identity_no_delete`.

The expected table retains its composite primary key, two secondary indexes, registry/user/grant RESTRICT foreign keys and four scope/state/shape/hash CHECK constraints. Actual deployed columns/indexes/FKs/CHECKs/triggers were not verified because the table is absent.

No existing table, constraint, trigger or data was altered by this phase. Full before/after row digests, schema metadata and privilege digests were not captured; do not infer that they were compared. The no-write confirmation describes the operations performed by this run, not a claim that unrelated concurrent application activity was monitored.

The initial sandboxed connection attempt was unavailable; the same reviewed, guarded SELECT-only helper then succeeded with local connection permission. An earlier stdin diagnostic attempt could not use PHP's STDERR constant; it was replaced with a temporary file-based CLI helper. Neither failed attempt executed DDL or data writes. The temporary helper was removed after the inspection; it is not an application endpoint or retained deployment tool.

## Evidence, snapshots and scientific uncertainty

The actual existing `MedicineProvisionalIdentity::package()` method was called for all five IDs before the normal connection. It verified the pinned manifest and both fixed retained source files per candidate: **10 of 10 raw SHA-256 digests matched**. It did not fetch replacement evidence, change the manifest, accept evidence or upgrade source tiers.

| ID | PubChem record | DailyMed retained version | Digest verification |
| --- | --- | --- | --- |
| 15 | CID 1986 | SPL 7 effective 20260120 | Both matched |
| 54 | CID 135401907 | SPL 20 effective 20251120 | Both matched |
| 319 | CID 2554 | SPL 21 effective 20260826 | Both matched |
| 481 | CID 6167 | SPL 7 effective 20260409 | Both matched |
| 2064 | CID 5284627 | SPL 31 effective 20260312 | Both matched |

PubChem remains proposed Tier 1 pending qualified appraisal; DailyMed remains Tier 4 manufacturer/packager-origin corroboration. Evidence remains `NOT_APPRAISED` in the preparation model. Hash verification proves retained byte provenance, not scientific correctness.

No current registry snapshot, provisional revision, evidence-to-revision binding, created_by/created_at, or lifecycle audit row was persisted. No authorized inspection/preparation command was invoked against the normal journal. The service's exact snapshot binding and immutable authorship therefore remain implementation properties validated in Phase 3.19, not newly demonstrated normal-database results.

## A–Q results

| Deliverable | Phase 3.20 result |
| --- | --- |
| A. Deployment | Stopped before DDL under sections 11/39.6 |
| B. Backup | Not attempted; required before a later deployment |
| C. Database integrity | SELECT-only inspection; no writes by this run. Full before/after schema/data/privilege digest comparisons deferred |
| D. Exact schema objects created | None; expected table and three triggers all absent |
| E. Exact five records created | None; legitimate scoped author unavailable |
| F. Evidence digest verification | PASS: pinned manifest and all 10 raw evidence digests matched |
| G. Registry snapshot verification | Deferred: no authenticated authorized preparation/snapshot capture |
| H. Authorization actor/result | No actor used; 0 grants and 0 eligible draft authors; owner/operator lists empty |
| I. Idempotency | Not rerun; normal creation/retry and disposable conflict tests deferred |
| J. Security negatives | Not rerun; deployed-service and disposable mutation tests deferred |
| K. Concurrency | Not rerun; disposable suite deferred |
| L. Lifecycle | Not rerun; disposable withdrawal/invalidation/restoration tests deferred |
| M. Medicine regressions | Not rerun following immediate stop; Phase 3.19 results remain historical only |
| N. Treatment regressions | Not rerun; effective HOLD configuration observed |
| O. Authentication regressions | Not rerun; authentication behavior/code unchanged |
| P. Readiness | Five-row table below; no persisted drafts |
| Q. Remaining prerequisites | Legitimate authenticated scoped author first; then fresh backup/recovery, exact deployment, metadata/integrity validation, controlled service creation and all required tests |

No disposable MariaDB instance or database was created in this phase. No destructive normal tests were run. Browser/public projection checks, pilot eligibility/enrollment/activation negative tests, security matrix, stale snapshot simulation, evidence/audit mutation, concurrency and lifecycle suites were all deferred. Phase 3.19's reported passing suites were not counted as Phase 3.20 executions.

## Five-candidate readiness

“Evidence bound” below means bound to a **persisted provisional revision**, not merely present in a verified local package. No five-record audit IDs, fingerprints, authors or timestamps can be supplied because none exist.

| ID | Candidate | Provisional revision | Evidence bound | Verified | Reviewer | Approved | Pilot | Public | Scientific blocker |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 15 | Acetazolamide | Not created | NO | NO | None | NO | NO | NO | Exact identity/form |
| 54 | Allopurinol | Not created | NO | NO | None | NO | NO | NO | Exact identity/form |
| 319 | Carbamazepine | Not created | NO | NO | None | NO | NO | NO | Identity/material form |
| 481 | Colchicine | Not created | NO | NO | None | NO | NO | NO | Stereochemical mapping |
| 2064 | Topiramate | Not created | NO | NO | None | NO | NO | NO | Stereochemical mapping |

## Resume and Phase 3.21 prerequisites

An actual authenticated active KOBI contributor/editor with an existing current composition/catalog grant must be made available through the legitimate owner-controlled authorization process. Account IDs alone, database connection credentials, administrator claims or synthetic authentication do not satisfy this requirement. Creating personnel or grants is outside this run's authorization.

After that prerequisite is satisfied, resume Phase 3.20: recheck artifact/evidence/current registry and schema; establish authorized operator execution; create and verify a fresh protected backup and recovery rehearsal; capture existing metadata/data/privilege baselines; deploy exactly the four approved CREATE statements; verify metadata/modes/integrity; prepare exactly five revision-1 drafts through the service; test safe idempotent retries; run mutation/concurrency/lifecycle and regressions in disposable MariaDB; verify normal separation and document the actual five records.

Phase 3.21 must not presume deployment or drafts already exist. Independent professional appraisal still requires real qualified reviewers, source sufficiency/currency/tier acceptance, resolution of identity/form/stereochemical questions and a separately designed audit-preserving canonical handoff. Provisional creation alone cannot authorize verification, approval, enrollment, activation or publication.

## File changes and explicit safety confirmation

Created: `docs/MEDICINE_PHASE_3_20_CONTROLLED_PROVISIONAL_DEPLOYMENT.md` only as a retained deliverable. No existing application, test, configuration, evidence or schema file was modified. The temporary SELECT-only helper was created and removed. No new authoritative state manifest was added.

Zero provisional drafts were created because authorization is unavailable. No canonical Medicine Identity or Chemical Reference was created or verified; no reviewer assignment/decision, approval, pilot enrollment/activation or publication occurred. No registry, personal medication, Treatment, Disease or authentication data was modified by this run. Treatment HOLD and both disabled pilot flags were observed unchanged in effective configuration; public Medicine routing was not changed. Session/global/persistent SQL mode and privileges were not changed. No commit, push, reset, checkout, merge, rebase or other Git mutation occurred.
