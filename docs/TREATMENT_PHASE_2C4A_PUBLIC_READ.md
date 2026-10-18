# Phase 2C-4A canonical treatment public-read boundary

Implemented and validated on 2026-10-08. This establishes Phase 2C-4A only; it does not complete Phase 2C-4 or enable public publication.

## Contract and integration

`TreatmentPublicationService` is the canonical service. Its small public API is:

```php
assessProtocolForPublicRead(int $protocolId, int $diseaseId, string $locale = 'en'): array
getPublicTreatmentProjection(int $protocolId, int $diseaseId, string $locale = 'en'): ?array
getPublicTreatmentsForDisease(int $diseaseId, string $locale = 'en'): array
```

All three APIs use the same private substantive eligibility predicate. Assessment returns exactly `['status' => 'CANDIDATE']` or `['status' => 'DENY']`, without internal governance reasons or objects. It does not call projection or consult exposure mode. Public retrieval builds the existing explicit projection only when exposure is authorized. An invalid protocol is omitted; a single invalid included item denies its entire protocol. No API accepts publication mode, user permissions or preferences as request input.

DiseaseService injects the canonical service through the existing reflection container. Both `getKnowledgeBySlug()` and `getComparison()` call the same `withPublicTreatments()` method, passing the disease identity and exact requested language. DiseaseRepository now supplies an empty treatment placeholder; its legacy raw-treatment SQL was removed. Disease/comparison controllers and comparison algorithms were not modified. Existing preference ordering runs only on eligible projections, after filtering, and cannot create access.

## Eligibility

Substantive assessment is independent of deployment mode: every required gate passing produces CANDIDATE; any failed gate produces DENY. HOLD does not change CANDIDATE to DENY. Public exposure separately requires mode exactly `governed`; HOLD and unknown modes return null/empty from public projection/retrieval without querying treatment data. Assessment under HOLD still loads and evaluates the requested aggregate. The actual workspace publication setting remains `hold`; configuration/environment files were not modified.

Approval, publication and current read eligibility are distinct. Approval records a reviewed content revision. Publication records an authorized publisher's explicit state change. Current read eligibility rechecks those facts and every applicable content/source/policy gate; publication state alone never grants access. This reader performs no publication command.

Required gates:

- Positive coherent protocol/disease/system identities; existing disease and system; active system; coherent aggregate parents. Requested protocol and disease IDs must match the retrieved identities. System display name for the exact locale and slug must be meaningful.
- `review_state=approved`, `publication_status=published`, a positive approval pointer matching the stored approval event's ID, protocol and current revision, and no later unresolved invalidation event.
- Approval event must be `approved`, satisfy the existing event validator (actor, independence, rationale, snapshot/hash, locales and review deadline), and have a valid nonfuture UTC occurrence timestamp.
- Stored snapshot must be valid JSON and have a valid SHA-256 pairing. Both the exact current canonical snapshot JSON and SHA-256 must equal the stored approval pair. The existing versioned snapshot builder is reused; its full governed aggregate includes excluded content too, so changes to unpublished/excluded content cannot silently bypass approval integrity.
- Review deadline must exist, validate as UTC DATETIME(6), and be strictly later than the read assessment time. Equality is expired. Reads never mutate expired state.
- Requested locale must be exactly `en` or `hi`, explicitly approved and explicitly published. Published locales must be a subset of approved locales. No language substitution is performed. English can be approved/published without Hindi.
- Exact localized protocol title, overview, applicability and educational limitations must satisfy existing meaningful-text validation; content purpose must be controlled and present. Evidence must be `evidence_supported`, and the saved explicit protocol risk must be `eligible`.
- At least one item must be explicitly included. Every item's identity/parent/inclusion flag must be coherent. Every included item needs a controlled type/purpose, `evidence_supported`, positive unique governed ordering, meaningful localized title/instructions/limitations, and source support. There is no second item risk taxonomy: the approved protocol risk decision governs the projection.
- Protocol references and included-item references must exist. All reference parents/source IDs must be coherent, with meaningful reviewer-governed claim scope and a valid optional locator. Sentence-level claim coverage is not invented.
- Every directly referenced source loaded with the aggregate must currently exist and be `verified`, with valid verifier/date and bibliographic identity. This conservatively includes references on excluded items too; those references are not projected. Source status is rechecked on every fresh read, independently of snapshot/hash (which intentionally exclude mutable source lifecycle metadata). Withdrawn, unverified, missing or malformed source state denies the protocol.
- Source URLs, when present, must pass the existing URL contract: a valid HTTP/HTTPS URL. No URL is fetched. Source verification is not interpreted as effectiveness, ranking or clinical quality.
- Unknown evidence, risk, purpose, item type, flags, locales or unresolved governance conditions fail closed. Legacy/unreviewed/unassessed content has no eligibility exception. No medical eligibility is inferred from source count, free text, item type, treatment system, preferences or publication alone.

Later unresolved invalidations are detected for `content_changed`, `submitted`, `changes_required`, `review_reset`, `withdrawn`, `source_invalidated` and `risk_assessed` events after the pointed approval. Ordinary publication/locales events do not invalidate the approved content. Existing review authorization, system-scoped qualification and independence checks remain in the approval workflow; this task neither replaces nor relaxes them.

## Exact public projection

The service builds allowlists explicitly, rather than returning/unsetting fields from repository rows.

| Projection | Exact keys |
| --- | --- |
| Protocol | `protocol_id`, `disease_id`, `treatment_system_id`, `treatment_system_name`, `treatment_system_slug`, `locale`, `title`, `overview`, `content_purpose`, `evidence_classification`, `evidence_summary`, `applicability`, `limitations`, `included_items`, `references` |
| Included item | `item_id`, `item_type`, `title`, `instructions`, `limitations`, `references` |
| Each protocol/item reference | `reference_id`, `source_id`, `claim_scope`, `locator`, `source_type`, `title`, `author_or_organization`, `publication`, `citation_text`, `url`, `identifier`, `publication_date`, `edition_or_version`, `accessed_on` |

The output omits actor/reviewer identity and qualifications, grants/permissions, review rationale/event notes, snapshots, hashes, unpublished revisions, excluded items/references, source verification metadata, user/health-profile data and preferences. Citation fields are bibliographic data only. The future view layer must escape text and link attributes with established helpers; no treatment UI/view was created here.

Default protocol ordering is system ID then protocol ID ascending. Included items order by sort order then item ID ascending; references order by relationship ID then source ID ascending. Existing governance additionally rejects duplicate included sort orders. Preferences may reorder already eligible protocols afterward; default order remains deterministic for equal preference positions through PHP's stable sorting.

## Repository, consistency and errors

`TreatmentPublicationRepository` extends BaseRepository and uses its prepared-query methods. One candidate query is followed by up to five batched IN queries for items, protocol references, item references, sources and approval events. IDs are bound; table/column identifiers have only fixed private call sites. Assembly groups rows in memory rather than querying per protocol. A populated batch uses six data SELECTs plus one isolation check and a transaction-start statement; empty batches skip unnecessary child queries. No caching was introduced.

All data queries share `START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY` at existing REPEATABLE READ isolation. No isolation/SQL mode is changed. Nested transactions or unsupported isolation fail explicitly rather than returning a mixed aggregate. Commit ends the read transaction; failures roll it back. No locks or protocol/item/source/event/user/preference writes occur in this reader.

Consistency is as of the database snapshot: a concurrent withdrawal committed after the snapshot can be visible only on a subsequent fresh read. The service rechecks current source state in each new snapshot and does not cache it. This is the normal read-transaction boundary, not a guarantee against changes after data is returned.

Pure persisted-data validator RuntimeExceptions/JSON errors deny a protocol internally. Repository failures occur outside that validation catch, propagate with a safe application message, and retain the original exception for existing error handling. Assertion/typing/logic errors are not silently treated as ineligible. No new public error response or internal governance detail is exposed by these service APIs.

## Validation

PHP CLI: **8.2.0**. Isolated server: **10.4.27-MariaDB**. Actual production credentials/database were not used.

| Suite | Distinct passed checks |
| --- | ---: |
| `tests/treatment-publication.php` | 105 |
| `tests/treatment-governance.php` | 88 |
| `tests/treatment-editorial.php` | 73 |
| `tests/treatment-review.php` | 130 |
| `tests/auth-phase1.php` | 45 |
| `tests/treatment-prerequisites.php`: risk/lifecycle/comparison | 29 / 7 / 11 |
| Distinct total | **488** |

The focused suite covers the requested state, approval integrity, review dates (including equality), system/disease identity, evidence/risk, content/items, source lifecycle/URL, locale, HOLD/unknown mode, exact projection/leak prevention, ordering, preference boundary, shared disease/comparison wiring and side-effect requirements. Real repository orchestration is exercised through a PDO test double: bound batched queries, constant query count with 50 candidates, consistent snapshot visibility, rollback/safe errors, unsupported isolation/nesting, and propagation of programming errors. Synthetic eligible/published-looking candidates exist only in memory.

The existing governance/prerequisite test adapters were updated for the new injected service; their assertions and counts continue to pass. Production authentication and preference code were not changed.

Seven created/modified permanent PHP files passed `php -l`. The temporary runtime PHP utility also passed syntax checking/execution. `git diff --check` passed. Read-only status/diff/reference inspection was performed; pre-existing workspace changes were preserved.

### Disposable MariaDB check

Evidence: `TREATMENT_PHASE_2C4A_RUNTIME_RESULTS.json`.

The unchanged authoritative schema imported **111/111 statements** into `kobi_phase2c4a_validation_20261008` on a newly initialized loopback server at `127.0.0.1:3317`. All six generated data SELECTs and the isolation query executed successfully with bound parameters. The real repository's consistent read-only transaction syntax was accepted. **11 live checks passed**, including actual service/repository disease and comparison HOLD boundaries with a synthetic unpublished draft, no events or revision/state changes on reads, zero published protocols, unchanged modes, and database removal.

Only the existing three SQL-mode directives at schema lines 9, 13 and 1089 were omitted from the disposable import stream:

```sql
/*!40101 SET SQL_MODE=''*/;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
```

They remain unchanged in `database/structure.sql`; all other schema objects/statements were imported without compatibility edits. Session and global modes before/after were unchanged: `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`. Schema SHA256 remains `d3c73231d08047a413f19cf99b76ee769dfe4cbdd3f380bab8b85d8b8d3ded5b`.

Two temporary harness corrections were necessary: missing PHP namespace imports, then using distinct disease slugs to respect the existing same-disease comparison rejection. Neither required an application/schema change. The complete final isolated run passed; the evidence records these corrections.

The disposable database was dropped and absence verified. The temporary MariaDB process was shut down and absence verified; the guarded `.phase2c4a-validation` directory, data/config/logs/utilities and disposable credentials were removed. No browser sessions or network requests were created.

## Deliverables, limitations and scope

Modified:

- `app/Services/DiseaseService.php`
- `app/Repositories/DiseaseRepository.php`
- `tests/treatment-governance.php`
- `tests/treatment-prerequisites.php`
- `docs/TREATMENT_PHASE_2C3_LIVE_TESTS.md`

Created:

- `app/Services/TreatmentPublicationService.php`
- `app/Repositories/TreatmentPublicationRepository.php`
- `tests/treatment-publication.php`
- `docs/TREATMENT_PHASE_2C4A_PUBLIC_READ.md`
- `docs/TREATMENT_PHASE_2C4A_RUNTIME_RESULTS.json`

No implementation blocker or discovered security bypass remains. Actual published MariaDB fixtures and public browser rendering were intentionally not exercised: positive projections were tested internally in memory, and all generated SQL/transaction syntax plus unpublished/HOLD integration were validated on MariaDB. Public UI and controlled publication are later tasks. Turning on governed mode requires those later deliverables; this task does not change the deployment setting or adapt existing views to render the new projection.

Scope confirmations: Phase 2C-4A only. No 4B publication administration, 4C public treatment UI or 4D first publication. HOLD remains configured. No treatment or legacy treatment was published/auto-published. No public treatment API or endpoint was created. No schema/migration, live/production database write, SQL-mode change or Git mutation occurred. Authentication, medicine, health-profile and treatment-preference behavior are unchanged. No unrelated refactoring occurred.

## Phase 2C-4A-CORRECTION

Status: **PASS**, 2026-10-08. Ready for the next validation phase; Phase 2C-4A-L live validation was **not run** and remains pending.

The original assessment delegated to the public projection path. That path applied HOLD before repository retrieval/substantive validation, so `false` conflated failed eligibility with blocked exposure. The correction changes assessment to return only `['status'=>'DENY']` or `['status'=>'CANDIDATE']` and directly evaluate the existing `eligible()` predicate on the requested repository aggregate. It never reads publication mode or builds a public projection. Invalid IDs/locales fail before retrieval; database/programming errors still propagate rather than becoming DENY.

The predicate itself is unchanged. Approval, revision, snapshot/hash, review date, source lifecycle, locale, evidence, risk and included-item gates remain intact. Exposure methods retain their existing separate mode guard and explicit projection. A CANDIDATE under HOLD therefore remains blocked from public exposure: projection null, treatment list empty, disease/comparison treatment results empty. The public APIs keep their null/array behavior. Exposure may short-circuit without a treatment query when HOLD blocks it; that does not classify substantive eligibility. No mode argument or new publication mode was added.

The existing 105 publication tests were retained. Boolean assessment assertions now check the explicit status. Three existing HOLD/unknown-mode tests were corrected to expect CANDIDATE for their valid aggregate while still asserting null/empty exposure and exactly one repository call from assessment. This fixes the incorrect assertion semantics rather than removing or weakening exposure checks. No application assessment consumers outside this service/test suite were found; DiseaseService and comparison continue using the unchanged exposure API.

The new `tests/treatment-publication-correction.php` reuses the existing fixture and validators. Its exact 17 regression names all passed:

1. `correction eligible under HOLD is CANDIDATE`
2. `correction ineligible under HOLD is DENY`
3. `correction eligible HOLD projection remains null`
4. `correction eligible HOLD disease retrieval remains empty`
5. `correction eligible HOLD comparison remains empty`
6. `correction HOLD assessment loads requested aggregate`
7. `correction source withdrawal under HOLD causes DENY`
8. `correction expired review under HOLD causes DENY`
9. `correction risk not_assessed under HOLD causes DENY`
10. `correction risk not_eligible under HOLD causes DENY`
11. `correction unsupported evidence under HOLD causes DENY`
12. `correction included item failure under HOLD denies entire protocol`
13. `correction approval hash mismatch under HOLD causes DENY`
14. `correction approval revision mismatch under HOLD causes DENY`
15. `correction assessment never consults exposure mode`
16. `correction HOLD assessment propagates retrieval and programming failures`
17. `correction invalid assessment context denies before querying`

Required validation commands and results:

| Command | Distinct passed checks | Exit code |
| --- | ---: | ---: |
| `php tests/treatment-publication-correction.php` | 17 new (also reruns the existing 105) | 0 |
| `php tests/treatment-publication.php` | 105 | 0 |
| `php tests/treatment-governance.php` | 88 | 0 |
| `php tests/treatment-editorial.php` | 73 | 0 |
| `php tests/treatment-review.php` | 130 | 0 |
| `php tests/treatment-prerequisites.php` | 29 risk + 7 lifecycle + 11 comparison | 0 |
| `php tests/auth-phase1.php` | 45 | 0 |
| Distinct total | **505 (488 existing + 17 new)** | All 0 |

Standalone risk/lifecycle/comparison test files do not exist; their established prerequisite coverage was run. No existing test was removed. All three changed/created PHP files passed `php -l`; `git diff --check` passed. The new test's initial type-hint namespace error was corrected before the final complete passing run. No production failure was hidden or assertion relaxed to accommodate it.

Files modified by this correction: `app/Services/TreatmentPublicationService.php`, `tests/treatment-publication.php`, this document, and `docs/TREATMENT_PHASE_2C4A_L_LIVE_VALIDATION.md` (historical blocker report annotated only). File created: `tests/treatment-publication-correction.php`. The historical live-validation JSON/results were preserved.

No database was contacted/provisioned, no schema import/write or schema change occurred, no SQL mode was changed, and no Git commit/push/reset/checkout/merge/rebase or other Git mutation occurred. Actual workspace publication policy was checked and remains `hold`. No real/legacy/synthetic database content was published. No Phase 4B/4C/4D, public UI/API, authorization bypass, controller changes or unrelated refactoring was implemented. Live MariaDB-backed positive validation remains the next separately requested phase, not a completed deliverable of this correction.
