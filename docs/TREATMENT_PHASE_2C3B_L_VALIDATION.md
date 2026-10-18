# Phase 2C-3B-L — Live MariaDB validation

Date: 2026-10-08. Final status: **VALIDATION FAILED** for the requested database
rejection behavior. Clean installation, additive upgrade and application workflows
passed; raw invalid ENUM/NULL input was accepted with warnings under the unchanged
permissive SQL mode. No application/schema correction was made to force success.

Detailed import counts, metadata, checks, errors, coercions and SQL mode are in
[the result artifact](TREATMENT_PHASE_2C3B_L_RESULTS.json).

## 1. Environment

- MariaDB server: `10.4.27-MariaDB`, matching the required target.
- PHP: `8.2.0` CLI, ZTS, Windows x64.
- Client: mysql Ver 15.1, Distrib 10.4.27-MariaDB, Win64.
- Separate disposable server: `127.0.0.1:3317`, final process ID 13388.
- New data/InnoDB/log/temp paths were confined to the project-local temporary
  `.phase2c3bl-validation` directory. Its system databases were freshly initialized.
- Temporary server root had no password; no existing application DB credentials,
  real users or health records were used. No external network requests were made.
- Databases: `kobi_phase2c3bl_clean_20261008` and
  `kobi_phase2c3bl_upgrade_20261008`. Neither was an existing KOBI database.

Initial startup failed before accepting connections because inherited XAMPP
InnoDB paths were not overridden by datadir alone (`ibdata1 must be writable`).
The retry explicitly redirected both InnoDB home directories and the socket,
logs, PID and temp paths. Sandbox socket restrictions required approved local
execution. The final live server paths were verified before any schema execution.
Existing configuration files were read, never edited.

## 2. Full schema import

Current `database/structure.sql`: **111 statements executed successfully**.
Client DELIMITER commands were parsed to preserve both exact trigger bodies.
No non-SQL-mode schema statement was omitted or changed.

The only omitted executable directives were:

```sql
/*!40101 SET SQL_MODE=''*/;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
```

These remain in the source at lines 9, 13 and 1080. They were omitted only from
the import stream so objects were validated under the existing configured mode.

Authoritative schema SHA-256 before/after:
`57d7f7a5da45846a03d66e39fdb308187ea667ee55ac4c6172f6de3702c8c4d2`.

## 3. Additive upgrade

The reliable pre-2C-3B source was the previous successful import artifact,
`docs/TREATMENT_PHASE_2C_IMPORT_RESULTS.json`: all 111 executed statements,
status SCHEMA_IMPORTED, original schema SHA-256
`ec84a58e3b5a107604108cf25e9d9663b8f1a06fc3e3a5989fff556f964d7cff`.
It contains neither risk_eligibility nor risk_assessed. No old schema was fabricated
by removing new objects from the current schema.

Those **111 historical statements** were executed in the upgrade database.
Synthetic legacy protocol/event rows were seeded before applying the exact two
documented upgrade statements:

```sql
ALTER TABLE treatment_protocols
  ADD COLUMN risk_eligibility ENUM('eligible','not_eligible','not_assessed')
  NOT NULL DEFAULT 'not_assessed' AFTER approved_review_id;
ALTER TABLE treatment_protocol_events
  MODIFY COLUMN event_type ENUM('legacy_initialized','content_changed','submitted','approved','changes_required','review_reset','published','withdrawn','publication_locales_changed','source_invalidated','risk_assessed') NOT NULL,
  ADD CONSTRAINT chk_treatment_risk_event CHECK (
    event_type <> 'risk_assessed' OR (
      independence_confirmed = 1 AND content_snapshot IS NOT NULL
      AND content_hash IS NOT NULL AND notes IS NOT NULL
      AND CHAR_LENGTH(TRIM(notes)) > 0
    )
  );
```

Both statements succeeded. Existing protocol fields and event data were preserved;
the new field defaulted to not_assessed. Existing indexes, foreign keys, constraints,
CHECK clauses and triggers were retained. Clean and upgraded metadata matched
exactly across columns/types/order/defaults/nullability/collation, indexes,
constraints, CHECK clauses, FK actions and trigger bodies/modes.

## 4. Schema behavior: 27 passed, 6 failed

Both databases correctly accepted all three risk values, provided the not_assessed
default, accepted a valid risk_assessed event and preserved the legacy event type.
Risk events with missing snapshot/hash/rationale or missing independence were
rejected. Invalid protocol references and zero event revision were rejected.

The three rejection expectations failed on **each** database:

| Input | Actual result | Warning |
|---|---|---|
| Invalid risk ENUM | UPDATE accepted | 1265: Data truncated for column risk_eligibility at row 1 |
| Explicit NULL risk | UPDATE accepted | 1048: Column risk_eligibility cannot be null |
| Invalid event ENUM | INSERT accepted | 1265: Data truncated for column event_type at row 1 |

Follow-up reads confirmed invalid and NULL risk values became the empty ENUM
value (`''`), not eligible. All invalid-input probes were rolled back. These are
server coercions, not rejected SQL statements; they are therefore reported as
failures of the requested database rejection behavior. No constraint or SQL mode
was weakened, and no CHECK was added during validation.

The existing risk-event CHECK validates required metadata presence and independence;
it does not independently prove complete canonical snapshot semantics or calculate
a cryptographic hash. Those remain application responsibilities. Positive event
revision and the approved-revision composite FK are distinct from requiring every
historical event to equal the protocol's current revision.

## 5. Application integration: 16 passed, zero failed

Real repositories and governance/editorial services used the disposable PDO
connection. Only authenticated-actor identity was injected for synthetic users;
scope/qualification/independence validation remained the actual implementation.
Application policy reported hold.

Validated:

- Legacy/default risk remains not_assessed, draft and unpublished.
- Qualified grants, source creation and explicit verification work.
- Preparation/submission can retain unassessed risk, but approval is denied.
- Reviewer saves eligible, creating the new revision and risk snapshot/hash.
- Fresh submission and explicit eligible confirmation permit approval, leaving
  publication unpublished and locales empty.
- Material changes to not_eligible and not_assessed clear approval and revise once.
- Invalid/missing/NULL/nonstring risk input and editor risk mass assignment reject.
- Withdrawn no-op edit preserves revision, withdrawal state and history.
- Direct withdrawn submission, approval, risk assignment and publication reject.
- Substantive withdrawn edit creates an unpublished draft revision exactly once.
- Fresh risk assessment/submission/approval completes without publication.
- Actual database-backed disease and comparison queries return empty treatment
  arrays under hold despite internal protocol rows.
- No stored protocol was published, and schema hash/SQL mode remained unchanged.

Withdrawal history was seeded as a synthetic historical withdrawn fixture;
no published fixture or successful publication command was used. A successful
live published-to-withdrawn command was consequently not exercised. Its existing
database-free regression remains covered.

## 6. Automated regressions

| Suite | Passed | Failed |
|---|---:|---:|
| Governance | 88 | 0 |
| Editorial | 73 | 0 |
| Review | 130 | 0 |
| Authentication | 45 | 0 |
| Prerequisites: risk | 29 | 0 |
| Prerequisites: lifecycle | 7 | 0 |
| Prerequisites: comparison | 11 | 0 |

383 distinct regression checks passed. Earlier suites are included by later suites;
their repeated execution is not counted again. Regression success does not erase
the six live database rejection failures.

## 7. Syntax and workspace

The three new temporary PHP validation utilities passed PHP syntax checks before
execution. No production PHP file was changed by this validation.
Read-only git status and git diff --check were performed. Existing tracked and
untracked authentication/governance/editorial/review/schema/docs/tests changes
were preserved; no unrelated cleanup was performed. Final git diff --check passed.
The two new artifacts contain no trailing whitespace. Hash comparison with the
pre-validation workspace confirmed no existing application/schema/test/document
file changed; only the two new validation artifacts were added.

Only this report and its JSON result artifact are retained as validation additions.
Temporary scripts were removed along with the disposable server directory.

## 8. SQL mode

Before import, after application testing and before database removal, both session
and global SQL mode were:
`NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`.

The temporary server read the existing setting without changing it. No SET SQL_MODE
statement was issued by the import/test utilities, no server/global mode was changed, and no persistent
application/database configuration was modified. The source directives remain intact.

## 9. Cleanup

Both disposable databases were explicitly dropped and verified absent. The
temporary MariaDB server was gracefully shut down and process exit verified.
The guarded project-local temporary directory was removed, including its data,
logs, generated configuration, validation scripts and temporary results copy.
No HTTP/browser server, sessions or cookies were created. No existing user files
or previous validation artifacts were removed.

## 10. Remaining limitations and final status

**VALIDATION FAILED**: the requested raw database ENUM/NULL rejection guarantees
do not hold under the required unchanged permissive SQL mode. Application input
validation remains fail closed and all tested application workflows passed.
Decide separately whether explicit database value CHECKs are required; no such
correction was implemented in this validation-only task.

Upgrade preservation used synthetic legacy/default governance data, not a copy of
all live data variants. Successful live publication/withdrawal, browser testing
and previously deferred concurrency stress tests were not expanded into this task.
Phase 2C-4 and its canonical public predicate remain unimplemented.

## 11. Confirmations

No existing/live KOBI database modification, production data write or production
schema application occurred. No SQL mode or authoritative schema change occurred.
No Git mutation, successful treatment publication or legacy auto-approval occurred.
Publication remains hold. No Phase 2C-4 or public treatment API was started.
Authentication, medicine, health-profile and treatment-preference behavior remained
unchanged; no unrelated refactoring occurred.
