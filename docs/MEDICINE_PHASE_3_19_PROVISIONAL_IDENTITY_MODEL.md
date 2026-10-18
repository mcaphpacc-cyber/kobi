# Medicine Phase 3.19 — Provisional identity / draft dependency model

Result: **PASS WITH DEPLOYMENT AND PROFESSIONAL-REVIEW LIMITATIONS.** A separate, preparation-only revision model is implemented and validated. It creates no canonical chemical adapter, canonical Medicine Identity, reviewer assignment, approval or pilot enrollment. Real candidate drafts were **not created in the normal database**, which was never connected to during this phase.

## 1. Objective and Phase 3.18 root cause

Phase 3.18 correctly stopped: `MedicineIdentityService::resolve()` requires verified chemical references and current independent decisions before `createMedicine()` can create a canonical composition. `loadIdentity(..., false)` and default composition governance snapshots also require verified ingredients. The normal pilot intake is disabled. Those requirements were inspected and preserved.

Phase 3.19 supports an unapproved, evidence-linked proposal about an UNKNOWN registry referent, without creating a canonical identity whose ingredients would fail the existing current-use checks. Existing authorization, governance, identity, canonicalization, eligibility, repository and schema contracts were inspected, together with Phase 3.17 packages and Phase 3.18 tests/report.

## 2. Options evaluated

| Dimension | A: extend chemical lifecycle | B: proposed chemical entity plus composition | C: separate draft directly referencing registry/evidence | D: reuse assessments/events or offline manifest |
| --- | --- | --- | --- | --- |
| Semantic correctness | Proposals and catalog adapters share a table; ambiguous use risk | Clear separation, but introduces another chemical entity | Clear proposal separate from all canonical subjects | Assessment requires chemical adapter; file alone is not a governed revision |
| Authorization | Every adapter consumer needs new-state handling | New role/scope or existing grants with new entity contract | Reuse contributor/editor composition grants; steward closure | Existing assessment gate requires pilot target; files lack persisted actor/grant |
| Lifecycle safety | Broad status/dependency changes needed | Two interacting lifecycles | Append-only draft/terminal journal; no verified transition | Does not resolve composition's verified dependency |
| Auditability | Existing journal must support added transitions | Separate proposed entity and composition histories | One immutable revision journal with actor/grant/time/rationale | Existing event subject XOR disallows direct registry subject |
| Evidence binding | Adapter and composition snapshots need new rules | Bind proposed chemical plus dependent draft | Exact composite target/revision/facts and captured sources | Local manifest lacks a persisted governed target |
| Stale snapshots | Adapt all current-use paths | Dependency propagation between new entities | Locked registry plus lifecycle event ID, revision and digests | Existing assessment path retains verified-dependency blocker |
| Service compatibility | Changes to resolve/load/current checks | New resolution service and relationship machinery | Add explicit preparation commands to governance service | Cannot reuse existing composition creation safely |
| Existing verified identities | Larger regression surface | Separate, but eventual promotion needs rules | Unchanged | Bypass would weaken existing path |
| Public projection | Must audit every shared adapter consumer | Separate projection exclusion | No canonical ID/FK/route; typed provisional target denied | File can remain private but does not supply persistence |
| Pilot eligibility | Every existing gate needs state proof | New exclusion rules for proposed dependencies | Existing scope/ID/approval gates reject proposal | Existing gate prevents intended preparation |
| Schema complexity | CHECK/status/event vocabulary changes | At least proposed references and relationship/revision design | One table, three triggers | Existing events require changed subjects or no durable draft |
| Migration risk | Existing CHECKs/data consumers affected | Additive, larger footprint | Additive only; no existing constraint changes | Existing event FK/XOR redesign or incomplete deliverable |
| Backward compatibility | Requires coordinated consumer changes | Mostly additive, more new concepts | Existing signatures and verified behavior unchanged | No safe completed implementation through existing path |

**Selected: Option C.** This phase has exactly five single-ingredient proposals, so an independent revision snapshot is sufficient. Option B may become appropriate for a later generalized multi-ingredient preparation model; that is not implemented here. No `skipVerification`, force-create flag, new canonical adapter status, or shared eligibility bypass was introduced.

## 3. Logical separation and lifecycle

The persisted object is `medicine_provisional_identity_revisions`, keyed by `(substance_id, revision)`. Its domain token is `provisional:S:<id>` and its scope is `provisional_identity`. These are actual provisional journal subjects, **not** fabricated canonical target IDs.

| Concept | Representation / boundary |
| --- | --- |
| Registry candidate | Exact existing `kobi_substances` row and activity history |
| Proposed mapping | Snapshot proposal with `PROPOSED_MAPPING_REQUIRES_REVIEW` |
| Captured evidence | Retained Phase 3.17 source metadata/digests; NOT_APPRAISED |
| Chemical reference / verified reference | Existing adapter tables, entirely untouched by preparation |
| Draft revision | Separate immutable provisional journal row |
| Reviewed/approved revision | Not representable in this table; existing canonical review path remains separate |
| Pilot enrollment/activation | Existing current-decision and explicit operator gates; no new markers/events |
| Public projection | No route, index, search, SEO or private medication linkage added |

Allowed service operations:

- Prepare `draft` revision 1.
- Prepare a new `draft` revision for newly observed registry facts using current tokens. Preserve earlier history.
- Return the identical current committed revision for an exact idempotent retry.
- Append `withdrawn` or `invalidated` using a current revision and steward authority.

Withdrawal and invalidation are terminal in this new preparation model. There is no automatic restoration, reassessment, submission, reviewer assignment, rejection decision, approval, promotion or publication operation. Later rejection must append an auditable professional decision through a separately designed reviewer workflow; it cannot edit this history. Existing canonical invalidation recovery through explicit reassessment and fresh independent review remains unchanged. Provisional recovery after an adverse decision is deliberately unavailable until a fresh-evidence/fresh-review policy is specified.

Canonical withdrawn/superseded/invalidated/retired history for the source reference also blocks new provisional preparation. The new journal is not a way to erase earlier canonical history. Closure remains possible after registry inactivity or source loss, since it uses the retained facts rather than requiring new evidence capture.

## 4. Service and authorization

`MedicineGovernanceService` now provides:

- `inspectProvisionalIdentityDraft($substanceId)` — authenticate and authorize, capture locked current registry facts, validate package, return expected revision/registry/package tokens.
- `prepareProvisionalIdentityDraft($input)` — validate exactly five allowed input fields, recapture facts, validate tokens/provenance, append an unapproved revision transactionally or return the exact current duplicate.
- `closeProvisionalIdentityDraft($substanceId, $input)` — steward-only terminal append with revision and rationale.

Preparation requires an active `AuthService::id()` actor and an existing current `contributor` or `editor` grant scoped to `composition` or `catalog`. Closure requires `steward` for that scope. Owner/admin/reviewer/deployment/registry authority alone does not grant draft authorship. No personnel/configuration/grants were added to the normal application. Existing policy/qualification checks are reused and unchanged.

Preparation has a separate explicit purpose from catalog/pilot intake, so it does not call `requireCatalog()` or enable `pilot_enabled`. It cannot create anything those catalog commands create. Its only persistence method inserts the new journal. No controller, endpoint or UI was added; if connected later, existing HTTP authentication, authorization, CSRF and safe error conventions remain required.

Application errors remain `MedicinePersistenceException` with safe 403/409/422/503 outcomes through the existing transaction/error boundary. No raw SQL or local source path enters ordinary application responses.

## 5. Registry, target and evidence binding

The service enforces exact IDs/names and active `CANDIDATE / UNKNOWN` status. It uses `MedicineEligibilityService::registrySnapshot()` to capture the complete observed row plus `medicine_registry_lifecycle_events` ID, including activity round-trip protection. The registry parent row is locked before reading the latest provisional revision. No registry write occurs in this service.

The existing deterministic governance JSON serializer defines the bytes. The immutable snapshot contains registry facts, proposed single-ingredient composition serialization, unresolved questions, captured source metadata, the manifest digest, and explicit null canonical/reviewer IDs. Evidence records contain the six-field source envelope, proposed tier/classification, retrieval time, NOT_APPRAISED, provisional scope/target, exact revision and SHA-256 of the snapshot bytes. Separate snapshot/evidence digests are stored as binary(32).

The source manifest is pinned to SHA-256:

`3fc38ca47fa9ace4bd8a9963854e120425909565b2a91e2c8c4ac763da454086`

Each command reads the fixed deployment manifest and checks the relevant two retained raw responses against their actual digests. Request input cannot supply a path, URL, locator, issuer, source version, scientific mapping, appraisal flag, reviewer, qualification or approval. Manifest/source changes are rejected; no new evidence was fetched. Source updates require a later explicit package/model release, not a request-controlled replacement.

The Phase 3.17 files remain unchanged. PubChem is still **proposed Tier 1 pending qualified acceptance**, and DailyMed is **Tier 4 manufacturer/packager-origin corroboration**. JSON normalization represents numeric metadata as strings, as in existing governance snapshots; this does not configure runtime authoritative evidence policy. Hashes establish byte provenance and binding, not scientific approval.

SQL backstops enforce stored hashes, target/revision/evidence-to-snapshot linkage and preparation-only metadata. They do not independently establish the truth of the source or perform filesystem digest validation: the service performs fixed-package validation. Privileged direct SQL that fabricates a coherent new JSON payload is not a professional appraisal and still cannot turn this table into a canonical approved subject.

## 6. Unknown composition, form and stereochemistry

`MedicineCanonicalizer::composition()` normalizes the proposed one-ingredient S reference. The serialized key is retained as a **proposal**, never inserted into canonical identities or ingredient relationships. Canonicalization is not verification.

`chemical_form` and `stereochemistry` remain NULL. Mapping, form and stereochemistry statuses are `PENDING_REVIEW`; evidence is `NOT_APPRAISED`, review `NOT_ASSIGNED`, approval `NONE`, and reviewer/canonical identity/chemical reference IDs NULL. Sources retain their observed structural identifiers as evidence context, not accepted equivalences. No presentation, brand, strength, dose, therapeutic claim, combination or new registry form is created.

## 7. Pilot/public/private isolation

No new journal row is a valid canonical scope or numeric target. Existing verify/enroll/internal projection methods reject `provisional_identity` and typed provisional tokens. No `medicine_pilot_records`, canonical events, chemical references, canonical compositions, presentations, personal links or selected ingredients are written by preparation.

No public query path reads the new table. Both normal pilot flags remain false. Public Medicine remains unavailable; Treatment HOLD configuration is unchanged. No live/browser or normal database verification was performed in this phase, so these availability statements refer to inspected unchanged code/configuration and regression results.

## 8. Exact additive schema

Authoritative definition: Phase 3.19 block in `database/structure.sql`. Separately reviewable deployment SQL: `database/medicine-phase319-provisional.sql`, verified to match that block exactly. **Do not apply the full structure dump to the existing normal database.** No deployment was performed here.

Table columns:

| Column | Type / purpose |
| --- | --- |
| substance_id | bigint unsigned, registry FK; exact five-ID CHECK |
| revision | int unsigned, >=1, composite primary key with substance_id |
| state | binary-collated varchar(16); draft/withdrawn/invalidated only |
| snapshot_json / snapshot_hash | bounded valid JSON facts / binary(32) SHA-256 |
| evidence_json / evidence_hash | bounded valid JSON binding / binary(32) SHA-256 |
| actor_id | int, users FK |
| authority_grant_id | bigint unsigned, medicine_authority_grants FK |
| rationale | nonblank text |
| occurred_at | datetime(6), authenticated operation UTC |

Indexes: composite PRIMARY KEY `(substance_id,revision)`, `idx_medicine_provisional_actor(actor_id,occurred_at)`, `idx_medicine_provisional_grant(authority_grant_id)`. Foreign keys all use RESTRICT for update/delete. No FK from a canonical identity, chemical adapter, pilot or private record points to this table.

CHECKs: `chk_medicine_provisional_scope`, `chk_medicine_provisional_state`, `chk_medicine_provisional_shape`, `chk_medicine_provisional_hashes`.

Triggers:

- `provisional_identity_before_insert`: lock registry parent; enforce next sequential revision and initial draft; block any continuation after terminal history; validate active actor/current matching scoped grant and capability; enforce unassigned/unapproved/pending metadata and exact two-source target/revision/snapshot/envelope/tier/retrieval binding.
- `provisional_identity_no_update`: reject all history updates.
- `provisional_identity_no_delete`: reject all history deletes.

No old CHECK, FK, index, lifecycle trigger or table definition changed. Removing only the new block restores the prior schema's exact SHA-256 `b7829ac3e1463d959386c714fdbd364a09ede97bb3c9c90549d0f1036d85f7f1`. Final structure SHA-256 is `e9ce3a2be1153845b035207a35a8a319c4d03f5e87f7b96d87da122059081f79`.

Deployment prerequisites: separately authorized normal schema deployment, legitimate contributor/editor/steward grants and retained local evidence assets. Existing verified data needs no migration/backfill. No candidate or governance data is seeded by the additive SQL. Its availability does not enable catalog intake, pilot access or public publication.

## 9. Idempotency and concurrency

Per-candidate registry locking serializes creation even when no provisional row exists. Latest journal reads are locking current reads; the unique composite revision key prevents parallel revision collisions. Existing repository transaction handling retries complete deadlock/duplicate-key transactions in fresh snapshots, never skips tokens.

An identical snapshot can return only the latest draft, with a current token or the immediately preceding token used for its committed exact retry. Older tokens fail. Changed registry facts require the latest revision token and matching newly captured registry digest. There is no broad uniqueness on identity name or source hash that prevents future revisions. Rationale/actor remain those of the original committed row for an idempotent retry; retries do not rewrite authorship.

Actual two-process MariaDB tests:

- Same-candidate first preparation: both requests returned the same revision; one created it, one was idempotent; exactly one journal row.
- Preparation versus registry mutation: only a correctly serialized observation may commit; the old token fails after mutation, and a fresh current token can append the new draft revision. Earlier snapshots remain historical, never current approval.
- Correct evidence-package token versus altered token: correct request succeeds/idempotently returns; altered binding is denied. This tests concurrent command-binding mutation, not editing retained source files.
- Withdrawal versus attempted reviewer transition: steward closure succeeds; reviewer transition is denied because this is not a reviewable canonical scope.
- Stale update and registry activity ABA: denied.
- Terminal direct-SQL restoration and skipped revisions: denied.

The initial trigger attempted `FOR UPDATE` reads on its own insert table and MariaDB returned **1442: Can't update table ... in stored function/trigger because it is already used by statement which invoked this stored function/trigger**. The new trigger was corrected before final validation: lock only the registry parent and read journal history without self-table locking. Parent serialization, sequential revision checks, unique keys and immutable terminal history remain. An older consistent snapshot can produce a safe conflict, not overwrite history or introduce verification. Existing triggers were not weakened.

## 10. Tests and security negatives

Runtime: **PHP 8.2.0 CLI; MariaDB 10.4.27** on guarded project-local disposable instances only.

| Suite | Cases/checks | Assertions | Final failures |
| --- | ---: | ---: | ---: |
| New provisional identity, database-free | 43 | 240 | 0 |
| New provisional identity, MariaDB | 15 | 65 | 0 |
| Medicine foundation | 105 | 216 | 0 |
| Medicine governance | 56 | 249 | 0 |
| Medicine policy | 33 | 92 | 0 |
| Medicine pilot | 40 | 143 | 0 |
| Phase 3.17 evidence readiness | 11 | 134 | 0 |
| Phase 3.18 stop-condition reproducer | 11 | 116 | 0 |
| Medicine foundation MariaDB | 29 | 96 | 0 |
| Medicine policy MariaDB | 11 | 43 | 0 |
| Medicine pilot MariaDB | 15 | 135 | 0 |
| Medicine governance MariaDB | 38 | 144 | 0 |
| Treatment operations | 570 checks | Not separately counted | 0 |
| Authentication Phase 1 | 45 scenarios | Not separately counted | 0 |

New tests total **58 cases / 305 assertions**. Retained Medicine suites total **349 cases / 1,368 assertions** in this run; combined **407 cases / 1,673 assertions**. Some retained concurrency assertion counts depend on the lawful winning operation; the table records this actual run.

The old pilot metadata inventory initially expected twelve Medicine tables. It was extended to thirteen and now additionally checks all three provisional triggers. All existing metadata/behavior assertions remain; no test was removed or relaxed to permit bad data. Initial connection/startup failures were isolated to test servers; one disposable datadir was reinitialized and all affected suites rerun successfully. Initial new-fixture projection expectations were corrected from 403 to the existing typed-ID 422 rejection. Final runs above passed.

Security negatives exercised:

- SQL syntax in registry ID; request-controlled locator/source/version fields rejected before SQL.
- Unauthorized actors, reviewer/admin-only authority, revoked/expired/wrong-scope contributor grants; unauthorized/stale closure.
- Forged appraisal acceptance/evidence, reviewer/licence/approval/canonical IDs and form verification fields rejected by input allowlist; database forged approval/reviewer/form/tier/source/target/digest rejected by integrity backstops.
- Provisional verification, enrollment and internal projection denied; no public route/index exists. Existing suites retain self-approval and reviewed dependency requirements.
- Stale revision/package/registry tokens, digest and target substitution, registry inactivity round trips.
- Registry/private mutation fields rejected; in-memory all original data remains exact. PDO service tests preserve registry/private fields except explicitly identified synthetic registry changes made by the race/ABA/fault test setup, outside the draft service.
- Withdrawn/invalidated provisional restoration and canonical protected-history bypass rejected.
- Direct SQL audit UPDATE/DELETE denied; injected append failure rolls back in both memory and real PDO with safe application diagnostics.

All six new/modified PHP files passed syntax checks. Whitespace/diff/reference review passed. No test claims real scientific appraisal, professional credential verification or browser workflow execution.

## 11. Database import and cleanup

New harness database: `kobi_medicine_phase319_validation_20261010`, MariaDB port **3339**, datadir `tests/.medicine319-validation/data`. Guard checks exact database/port/version/datadir and both session/global mode before writes. Retained harnesses used their existing isolated ports **3325/3328** and datadirs. No normal credentials/bootstrap connection were used.

Each schema import executed **142 statements**, including all tables, indexes, FKs, CHECKs, views and triggers from the authoritative structure. Client DELIMITER framing is interpreted by the harness. Only these three dump mode directives were omitted, to preserve the matching isolated server mode:

```sql
/*!40101 SET SQL_MODE=''*/;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
```

No other schema statement was omitted. The original directives remain in structure.sql unchanged. Normal server/application/global SQL mode was never changed. Test servers started with `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`, and tests confirmed it remained unchanged.

All five disposable suite databases were dropped through guarded harnesses and absence verified where supported. Test servers were shut down and project-local datadirs/logs/barrier files removed after process/path checks. Retained evidence captures were not removed or changed. Existing regression suites perform synthetic review/enrollment/activation scenarios inside isolated fixtures; these are not real candidate decisions or normal pilot activation. The new preparation suite itself creates no approval/reviewer decision/enrollment.

## 12. Files changed and created

Modified:

- `app/Services/MedicineGovernanceService.php` — explicit preparation/inspection/terminal closure commands.
- `app/Repositories/MedicineGovernanceRepository.php` — locking latest provisional read and parameterized append.
- `database/structure.sql` — one additive table and three triggers only.
- `tests/medicine-pilot-mariadb.php` — additive schema inventory (13 tables and new trigger checks).

Created:

- `app/Services/MedicineProvisionalIdentity.php` — pure fixed-package/proposal preparation.
- `database/medicine-phase319-provisional.sql` — exact additive deployment block, not applied normally.
- `tests/medicine-provisional-identity.php` — in-memory success/security/history checks.
- `tests/medicine-provisional-identity-mariadb.php` — guarded schema/PDO/concurrency checks.
- `docs/MEDICINE_PHASE_3_19_PROVISIONAL_IDENTITY_MODEL.md` — this report.

No authentication, private medication, registry, Treatment, Disease, configuration, controller, route, view or public asset was changed. Transient test-server directories were created and removed, not retained deliverables.

## 13. Five-candidate readiness and limitations

"Model ready" means the preparation representation works in code and disposable tests. It does not mean deployed, scientifically verified or ready for pilot use.

| ID | Candidate | Provisional model ready? | Evidence bound? | Remaining scientific question |
| --- | --- | --- | --- | --- |
| 15 | Acetazolamide | Yes, tested; normal deployment pending | Tested in memory/disposable only; no normal draft | Exact identity/form mapping |
| 54 | Allopurinol | Yes, tested; normal deployment pending | Tested in memory/disposable only; no normal draft | Exact identity/form mapping |
| 319 | Carbamazepine | Yes, tested; normal deployment pending | Tested in memory/disposable only; no normal draft | Identity/material form equivalence |
| 481 | Colchicine | Yes, tested; normal deployment pending | Tested in memory/disposable only; no normal draft | Qualified stereochemical mapping |
| 2064 | Topiramate | Yes, tested; normal deployment pending | Tested in memory/disposable only; no normal draft | Qualified stereochemical mapping |

For all five: **unverified; evidence NOT_APPRAISED; reviewer unassigned; approval absent; pilot ineligible/not enrolled; public unavailable**. The exact candidate identities remain the Phase 3.17 provisional source selections; their actual normal registry state was not re-censused here.

Remaining prerequisites: separately authorized additive deployment and real draft creation, legitimate scoped contributor/editor grants, real independent Doctor/Pharmacist qualification and assignment, source-policy acceptance, exact chemical/form/stereochemical appraisal, and an explicit audit-preserving handoff to canonical review. No clinician or authoritative mapping was invented.

## 14. Future professional review boundary

This phase intentionally does not implement promotion or approval of the provisional table. A later handoff must retain original preparation authorship/history and evidence bytes, reject stale/adverse drafts, establish current independent professional qualifications, appraise current evidence and resolve the UNKNOWN/form/stereochemical questions. It must bind evidence to the **new real canonical targets and current facts**, not reuse provisional IDs/hashes as canonical approval bindings.

Only existing explicit qualified review/approval operations may verify chemical references; only then may the unchanged canonical composition creation path run, followed by its own independent revision-bound review. Reviewer assignment/submission/appraisal are not themselves approval. Reviewer must remain independent of all contributors, including provisional authors during any future handoff. That cross-object attribution handoff is a later design requirement; it is not silently assumed implemented by this table. Fresh-evidence recovery and auditable rejected decisions similarly remain future work. Separate operator enrollment and activation remain required after canonical approval, and neither implies public publication.

## 15. Explicit safety confirmation

No real approval, reviewer decision, pilot enrollment, activation or publication occurred. No normal `kobi_substances` or personal medication record changed. No normal database connection or write occurred. No normal schema deployment, SQL-mode change, credentials/grants update, bulk verification or new public projection occurred. Treatment HOLD is unchanged. No commit, push, reset, checkout, merge, rebase or other Git mutation was performed.
