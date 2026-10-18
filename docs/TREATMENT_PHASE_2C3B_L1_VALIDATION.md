# Phase 2C-3B-L1 integrity correction and validation

Date: 2026-10-08. Status: **VALIDATION PASSED**.

## Root cause and correction

MariaDB 10.4.27 under the existing non-strict SQL mode coerces invalid ENUM input to the empty ENUM value. NOT NULL can also yield a warning and coerced value on some statement paths. Existing governance coherency checks did not independently restrict the stored risk/event ENUM values. Application validation alone therefore did not establish database integrity.

Only three CHECK constraints were added to `database/structure.sql`. ENUM definitions, NOT NULL requirements, the `not_assessed` default, and every existing CHECK, index, foreign key and trigger were preserved. No new trigger was necessary.

On `treatment_protocols`:

```sql
CONSTRAINT `chk_treatment_risk_value` CHECK (
  `risk_eligibility` IS NOT NULL
  AND `risk_eligibility` IN ('eligible','not_eligible','not_assessed')
)
```

On `treatment_protocol_events`:

```sql
CONSTRAINT `chk_treatment_event_type` CHECK (
  `event_type` IS NOT NULL
  AND `event_type` IN (
    'legacy_initialized','content_changed','submitted','approved',
    'changes_required','review_reset','published','withdrawn',
    'publication_locales_changed','source_invalidated','risk_assessed'
  )
)
```

```sql
CONSTRAINT `chk_treatment_risk_payload` CHECK (
  `event_type` <> 'risk_assessed' OR COALESCE(
    JSON_TYPE(JSON_EXTRACT(`content_snapshot`, '$.protocol.risk_eligibility')) = 'STRING'
    AND BINARY JSON_UNQUOTE(JSON_EXTRACT(`content_snapshot`, '$.protocol.risk_eligibility'))
        IN ('eligible','not_eligible','not_assessed')
    AND JSON_TYPE(JSON_EXTRACT(`content_snapshot`, '$.protocol.id')) = 'INTEGER'
    AND JSON_EXTRACT(`content_snapshot`, '$.protocol.id') = `protocol_id`
    AND JSON_TYPE(JSON_EXTRACT(`content_snapshot`, '$.protocol.content_revision')) = 'INTEGER'
    AND JSON_EXTRACT(`content_snapshot`, '$.protocol.content_revision') = `content_revision`,
    0
  )
)
```

The payload CHECK uses the existing canonical snapshot representation. It compares snapshot revision to the event revision, preserving historical events when the current protocol advances. Missing/null JSON fields fail through COALESCE. The previous `chk_treatment_risk_event` still requires independence, snapshot/hash and nonblank rationale; existing JSON, hash-format, revision and FK checks remain intact.

## Additive upgrade

The existing SQL block in `TREATMENT_PHASE_2C3_LIVE_TESTS.md` is the exact upgrade tested. Its two ALTER statements add the risk column/default and risk-value CHECK, then extend the event ENUM and add the existing risk-event CHECK plus the two new event CHECKs. The exact executed SQL is also preserved in the evidence JSON under `upgrade_sql`.

For deployments already at Phase 2C-3B, apply only the three new named CHECKs after inspecting metadata; do not repeat ADD COLUMN or the existing risk-event constraint. Existing data must be preflighted for invalid/empty risk and event values and malformed risk snapshots. Do not silently rewrite/delete data. ALTER TABLE implicitly commits: validate all data before starting, and inspect metadata after any failure because an earlier statement can remain applied. The baseline upgrade is deterministic, not an idempotent migration runner. No upgrade was applied to the existing KOBI database.

## Environment and schema validation

- MariaDB: **10.4.27-MariaDB**, disposable freshly initialized server bound to `127.0.0.1:3317`.
- PHP CLI: **8.2.0**.
- Temporary data directory: `D:\xampp\htdocs\kobi\.phase2c3bl1-validation\data`.
- Disposable databases: `kobi_phase2c3bl1_clean_20261008` and `kobi_phase2c3bl1_upgrade_20261008`.
- Session and global SQL mode before, after validation and before cleanup: `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`.

Current complete import executed **111 statements**, all successful. Historical import executed **111 statements**, all successful, followed by **2 documented upgrade statements**, both successful. The historical baseline was the preserved exact executed SQL in `TREATMENT_PHASE_2C_IMPORT_RESULTS.json`, rather than an inferred/reconstructed schema.

Historical source SHA256: `ec84a58e3b5a107604108cf25e9d9663b8f1a06fc3e3a5989fff556f964d7cff`.

Corrected schema SHA256: `d3c73231d08047a413f19cf99b76ee769dfe4cbdd3f380bab8b85d8b8d3ded5b`.

Clean and upgraded metadata matched exactly for columns (type/order/default/nullability/collation/extra), named constraints, CHECK expressions, indexes, foreign-key actions and trigger bodies/SQL modes. Existing baseline constraints/indexes/FKs/triggers were preserved. A synthetic legacy protocol/event survived upgrade unchanged apart from the intended new risk default. Risk remains ENUM('eligible','not_eligible','not_assessed'), NOT NULL, DEFAULT 'not_assessed'.

Only these three SQL-mode directives were omitted from the current disposable import stream, while remaining unchanged in the source file (lines 9, 13 and 1089):

```sql
/*!40101 SET SQL_MODE=''*/;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
```

They were omitted to validate the actual schema under the existing server mode without changing session mode. Every other schema statement, including all tables, indexes, foreign keys, CHECKs and triggers, was executed. The saved historical stream had already excluded only its SQL-mode directives. No non-SQL-mode statement was skipped or modified for compatibility. No compatibility issue remains.

## Database rejection evidence

**111 database checks passed**: three upgrade/metadata checks and 54 checks on each database. All three valid risk states succeeded on INSERT and UPDATE; omitted risk used `not_assessed`. All ten previous event types and valid risk_assessed events for all three risk decisions succeeded.

Invalid/empty/NULL risk and event inputs were tested on INSERT and UPDATE. Malformed risk events tested missing/invalid/empty/null/numeric/array/uppercase decisions; missing or mismatched protocol/revision; string IDs/revisions; missing/empty snapshots and hashes; null/empty/blank rationale; false independence; invalid FK; and zero revision.

Every rejection verified actual SQL statement failure, SQLSTATE/error code and SHOW WARNINGS diagnostics/count, unchanged stored rows and risk value, an active usable transaction, successful continued query, rollback, and unchanged rows after rollback. No invalid/coerced state persisted. Full per-case evidence is in `TREATMENT_PHASE_2C3B_L1_RESULTS.json`.

| Representative input | SQLSTATE / error | Diagnostics |
| --- | --- | --- |
| Invalid risk INSERT | 23000 / 4025, risk-value CHECK | Warning 1265 + Error 4025, count 2 |
| NULL risk UPDATE | 23000 / 4025, risk-value CHECK | Warning 1048 + Error 4025, count 2 |
| Invalid event INSERT | 23000 / 4025, event-type CHECK | Warning 1265 + Error 4025, count 2 |
| Invalid snapshot decision or mismatched revision | 23000 / 4025, payload CHECK | Error 4025, count 1 |
| Explicit NULL INSERT on native NOT NULL path | 23000 / 1048 | Actual statement rejection |
| Nonexistent protocol FK | 23000 / 1452 | Actual statement rejection |

Warnings were never treated as sufficient rejection. Precursor coercion warnings can accompany an actual CHECK error; the statement fails and no coerced row is stored. The harness initially stopped because it expected a CHECK/FK error for a NULL INSERT that MariaDB rejected natively with error 1048. Its expectation was corrected only for explicit NULL inputs, the two disposable databases were recreated, and the complete final run passed. Schema behavior was not changed to accommodate the test.

## Application and regression checks

**15 live disposable application checks passed**, using real services/repositories and the isolated PDO connection. Covered default/legacy risk, all three valid decisions, invalid/missing/nonstring decisions, forged editor input, saved eligible approval requirement, risk revision and canonical snapshot/hash, approval invalidation on material change, withdrawn no-op preservation, withdrawn substantive edit returning to draft with one revision increment, withdrawal restrictions, and disease/comparison empty boundaries under HOLD. Final queries confirmed zero published protocol rows and unchanged SQL mode.

No successful publication command or published protocol fixture was used. Synthetic historical withdrawn fixtures and transactional event-type checks did not publish treatment content. These were direct service/repository integration checks, not browser/HTTP tests.

| Automated suite | Passed |
| --- | ---: |
| treatment-governance.php | 88 |
| treatment-editorial.php | 73 |
| treatment-review.php | 130 |
| auth-phase1.php | 45 |
| treatment-prerequisites.php: risk | 29 |
| treatment-prerequisites.php: lifecycle | 7 |
| treatment-prerequisites.php: comparison | 11 |
| Distinct total | **383** |

All required suites passed. Some suites repeat foundation checks; the total above counts each distinct suite once. PHP syntax checks passed for all three temporary PHP utilities (schema validation, application integration and guarded cleanup). No production PHP file was modified by this correction. `git diff --check` passed with no output; read-only Git status was inspected. Pre-existing workspace changes were preserved.

## Cleanup, scope and limitations

Both disposable databases were dropped and their absence verified. The temporary MariaDB server was shut down and its process absence verified. The entire guarded temporary validation directory, including data, credentials/config artifacts, logs and PHP utilities, was removed. No browser sessions were created. Durable validation evidence was retained in the new report and JSON only.

Files modified by this task: `database/structure.sql`, `docs/TREATMENT_PHASE_2C3_LIVE_TESTS.md`. Files created: this report and `docs/TREATMENT_PHASE_2C3B_L1_RESULTS.json`. No production data was copied into validation. Applying the upgrade to the real database remains the developer's responsibility after existing-data preflight; no requested isolated test is deferred.

Mandatory confirmations:

- No existing/live KOBI database was modified; no production database write occurred and no production schema was applied.
- No session/global/persistent SQL mode was changed. Source SQL-mode directives were preserved.
- `database/structure.sql` changed only for the three required integrity CHECKs.
- No Git mutation occurred.
- No treatment content was published and no legacy content was auto-approved.
- `TREATMENT_PUBLICATION_MODE` remains `hold`.
- No Phase 2C-4 implementation, public treatment API or publication service was created.
- Authentication, medicine, health-profile and treatment-preference behavior are unchanged.
- No unrelated refactoring occurred.
