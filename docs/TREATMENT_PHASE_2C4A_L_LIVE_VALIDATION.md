# Phase 2C-4A-L canonical public-read live validation

Status: **PASS**, subject to the explicitly deferred public-exposure tests below. Recorded 2026-10-08 23:55:52 Asia/Calcutta (18:25:52 UTC).

The corrected substantive assessment passes against a real MariaDB fixture independently of HOLD. HOLD still blocks all public exposure. This supersedes the earlier blocked preflight; no application, test, schema or persistent configuration changes were made during this validation. Machine-readable evidence is in `TREATMENT_PHASE_2C4A_L_RUNTIME_RESULTS.json`.

## Disposable environment and schema import

- PHP 8.2.0; MariaDB 10.4.27-MariaDB.
- Database `kobi_phase2c4al_validation_20261008`, host `127.0.0.1`, port `3317`, fresh separate server/datadir inside `.phase2c4al-validation`.
- Session and global SQL mode before, after and at cleanup: `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`.
- Existing KOBI database/server was never connected to. All fixture writes and DDL were confined to the disposable server.
- All 111 non-SQL-mode import statements succeeded. Tables, indexes, foreign keys, CHECKs, triggers and other statements were imported as defined. No constraints were disabled or altered.
- `database/structure.sql` remained unchanged: SHA256 `D3C73231D08047A413F19CF99B76EE769DFE4CBDD3F380BAB8B85D8B8D3DED5B`.

Only these directives were omitted from the import stream to preserve the existing server/session SQL mode, following the approved validation exception:

| Source line | Omitted directive |
| --- | --- |
| 9 | `/*!40101 SET SQL_MODE=''*/;` |
| 13 | `/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;` |
| 1089 | `/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;` |

No other schema statement was omitted. No schema compatibility defect was observed.

## Persisted positive fixture and HOLD

Seeded only synthetic diseases, an active system, actors, scoped permissions, blank draft structures and items. Used existing service commands `edit`, `createSource`, `verifySource`, `reference`, `recordRiskEligibility`, `submit`, `approve`, `publish`. The existing publish command was used directly with a scoped synthetic publisher; no new publication feature or mode override was introduced. Actual database permission checks were used, with a test actor-ID override to simulate authenticated synthetic actors.

Protocol 1 has revision 6 and approval event 7, English approved and published, future review deadline `2031-01-01 00:00:00.000000`, `evidence_supported`, eligible risk, required educational content, two included items and two verified sources. The real governance snapshot/hash mechanisms generated the approval. An excluded item carries a private sentinel.

Observed through actual repositories/services:

| Path | Actual result |
| --- | --- |
| `assessProtocolForPublicRead()` | `{"status":"CANDIDATE"}` |
| `getPublicTreatmentProjection()` under HOLD | `null` |
| `getPublicTreatmentsForDisease()` under HOLD | `[]` |
| DiseaseService knowledge retrieval | treatments `[]` |
| Comparison of two distinct synthetic diseases | both treatment results `[]` |

The eligible persisted fixture remains a CANDIDATE while public exposure is blocked. Disease/comparison paths use the canonical boundary; no raw treatment fallback appeared. No real or legacy treatment was published.

## Projection allowlist and leak inspection

The public API correctly returns null under HOLD. To inspect intended projection serialization without changing authorization, the disposable harness invoked the existing private `project()` serializer through ReflectionMethod on the actual eligible database aggregate. This is internal serializer evidence, not a positive public API response or exposure bypass.

Exact protocol, item and citation allowlists matched the existing specification. Expected localized content, identities, system metadata, educational fields and public citations were present. Included items were ordered `[2,1]`; excluded item 3 was absent. Internal actor/reviewer IDs, notes, rationale, events, hashes, snapshots, verification metadata, permissions, private identifiers and forbidden governance fields/sentinels were absent. Full synthetic serialized evidence is retained in JSON.

## Live failure matrix

Each persisted mutation was isolated, assessed using the real service, then restored and rechecked as CANDIDATE. Relevant rows were compared around reads to detect writes. One failing included item denied the entire protocol.

| Test | Controlled failure | Result |
| --- | --- | --- |
| A | Approval event has wrong event type | DENY |
| B | Approval snapshot protocol revision differs, with recalculated snapshot hash | DENY |
| C | Stored approval content hash mismatch | DENY |
| D | Review deadline in the past | DENY |
| E | Hindi neither approved nor published | DENY |
| F | English title missing | DENY |
| G | English overview missing | DENY |
| H | English applicability missing | DENY |
| I | English educational limitation missing | DENY |
| J | Protocol evidence insufficient | DENY |
| K | Risk `not_eligible` | DENY |
| L | Risk `not_assessed` | DENY |
| M | No included items | DENY |
| N | One included item missing instructions | Whole protocol DENY |
| O | One included item evidence insufficient | Whole protocol DENY |
| P | Protocol source withdrawn | DENY |
| Q | Protocol source unverified | DENY |
| R | Item source withdrawn | DENY |
| S | Item source unverified | DENY |
| T | Treatment system inactive | DENY |
| U | Wrong requested disease; persisted disease incoherent with approval | DENY for both |
| V | Later unresolved `source_invalidated` event | DENY |

For B, a separate attempted update of the actual referenced approval event revision was rejected by MariaDB: SQLSTATE `23000`, error `1451`, `fk_treatment_approved_revision`. Thus that incoherent event/pointer state cannot be persisted with constraints active; it was not falsely reported as an assessed row. The persistable snapshot-revision mismatch independently demonstrated service DENY.

For D, the approval event occurrence timestamp was moved earlier alongside the past deadline to preserve the event CHECK requiring deadline after occurrence. Both fields were restored. Constraints were never bypassed.

The dedicated source-withdrawal test observed CANDIDATE -> source withdrawal only -> DENY. Full protocol and approval event rows, including snapshot/hash, remained unchanged. Restoring the disposable source restored CANDIDATE. This proves read-time source validation. English was CANDIDATE; unapproved/unpublished Hindi was DENY without fallback.

## Read-only and batched-query evidence

Compared complete relevant rows before/after assessment, projection, public list, DiseaseService and comparison: protocols, items, sources, source relations, events, systems, diseases, users, editorial permissions and preferences. All remained unchanged; instrumented prepared statements contained no DML. HTTP row hashes also matched. Existing controller session history used only disposable session files.

Actual repository reads used **8 prepared statements for one candidate and 8 for ten candidates**: isolation SELECT, read-only consistent-snapshot transaction start and six batched data SELECTs. Nine additional synthetic protocols were built through the same existing governance workflow. All ten assessed CANDIDATE; draft protocol 99 was excluded. No N+1 growth was observed at these sizes; no optimization was performed.

| Operation | Prepared statements |
| --- | ---: |
| Assessment | 8 |
| Public projection under HOLD | 0 |
| Public disease list under HOLD | 0 |
| DiseaseService | 5 |
| Two-disease comparison | 10 |

Disease/comparison counts above are ordinary disease queries; HOLD prevented treatment queries.

## HTTP validation

Ran the existing disease route using a disposable adaptation of the established test router, with process-local isolated database/session/storage settings and unchanged HOLD policy. PHP listened only on `127.0.0.1:8128`.

`GET /disease/phase4al-disease` returned **HTTP 200**, expected disease title, 10,738-character HTML and none of 13 treatment/private/governance sentinels. Content/governance rows remained unchanged. No graphical browser was used. Positive public publication rendering was not exercised.

## Regression and syntax results

Commands used the PHP 8.2.0 runtime at `D:\xampp\php\php.exe`, with existing `tests/<name>.php` scripts. Every command exited 0; no assertions were changed.

| Script | Distinct passing assertions | Failures | Exit |
| --- | ---: | ---: | ---: |
| treatment-publication-correction | 17 new (+105 publication rerun) | 0 | 0 |
| treatment-publication | 105 | 0 | 0 |
| treatment-governance | 88 | 0 | 0 |
| treatment-editorial | 73 (+88 foundation rerun) | 0 | 0 |
| treatment-review | 130 (+88 foundation, 73 editorial rerun) | 0 | 0 |
| treatment-prerequisites | 29 risk +7 lifecycle +11 comparison | 0 | 0 |
| auth-phase1 | 45 | 0 | 0 |
| Total, excluding repeated embedded suites | **505** | **0** | |

Separate treatment-risk/lifecycle/comparison scripts do not exist; their established checks reside in treatment-prerequisites. Live evidence contains **107 PASS checks, zero failures**. Both temporary PHP utilities passed syntax checks; no permanent PHP file changed. `git diff --check` passed. Final SHA256 comparison against the pre-validation inventory preserved every app/routes/database/tests file and all unrelated docs.

Initial harness issues were corrected only in disposable utilities: actor insertion order for the scoped-grant FK and native PDO integer normalization. A sandbox-blocked first loopback HTTP attempt succeeded on the authorized retry. These were not application/schema defects; final complete runs passed.

## Scope and cleanup

Only these existing artifacts were updated:

- `docs/TREATMENT_PHASE_2C4A_L_LIVE_VALIDATION.md`
- `docs/TREATMENT_PHASE_2C4A_L_RUNTIME_RESULTS.json`

No permanent files created. No application/test/schema/configuration changes; no migration, SQL-mode change, production/live database operation or Git history mutation. No Phase 4B, 4C or 4D work performed.

The exact disposable database was dropped and its absence verified. MariaDB and PHP HTTP processes stopped; temporary utilities, datadir, logs, configuration and session files were removed. `.phase2c4al-validation` no longer exists.

## Genuine remaining limitations

HOLD intentionally prevents positive public projection retrieval, positive disease/comparison exposure, live preference reordering of exposed treatments and positive browser publication rendering. The live serializer inspection is internal only. Preference reorder-only behavior remains covered by the existing automated suite. No publication authorization mechanism or new test exposure mechanism was added. The schema-prohibited direct approval-event revision mismatch could only be tested as a rejected write; the snapshot mismatch was tested as DENY. Query behavior was observed at one and ten candidates, not benchmarked at production scale.
