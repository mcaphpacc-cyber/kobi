# Phase 2C live validation report — 2026-10-08

**Verdict: VALIDATION FAILED — blocked before schema import.**

This is an incomplete validation run, not a demonstrated MariaDB schema failure.
The schema import was not executed because applying the dump unchanged conflicts
with the explicit instruction not to change SQL mode. No constraints were weakened,
no feature implementation was performed and Phase 2C-4 was not started.

## Read-only server precheck

| Check | Status | Actual result |
|---|---|---|
| Configured host | PASS | `localhost:3306` |
| `SELECT VERSION()` | PASS | `10.4.27-MariaDB` |
| `SELECT @@sql_mode` | PASS | `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION` |
| `SELECT @@global.sql_mode` | PASS | Same as session mode |
| `SHOW ENGINES` | PASS | InnoDB is DEFAULT; transactions, XA and savepoints supported |
| Charset/collation metadata | PASS | `utf8mb4_general_ci` exists, charset `utf8mb4`, compiled Yes |
| Isolated database existence check | PASS | Proposed `kobi_phase2c_validation_20261008` does not exist |

The connection did not select the existing KOBI database. Only server and
information_schema metadata reads were issued. The initial sandbox connection
failed with PDO code 2002; the approved read-only retry outside the sandbox
succeeded. Credentials were loaded through existing configuration helpers and
were not printed or modified.

## Blocking schema-import conflict

Intended method: create the named disposable database after reporting the target,
then import the authoritative `database/structure.sql` over a connection explicitly
scoped to that database. No existing KOBI data would be copied or modified.

The following executable dump directives prevent proceeding under the current
instructions:

```sql
-- database/structure.sql:9
/*!40101 SET SQL_MODE=''*/;
-- database/structure.sql:13
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
-- database/structure.sql:1078
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
```

These are executable version comments on MariaDB 10.4.27, not inert comments.
They change the import connection's session SQL mode. The first directive clears
the mode before the second saves it; consequently the final restoration would
restore an empty mode instead of the observed starting mode.

There is no MariaDB execution error to report: import was blocked before execution.
SQL compatibility and actual constraint/trigger enforcement remain unverified.
This requires clarification of the validation instructions, not weakening a
governance constraint: either authorize these dump directives solely in the
disposable import session, or authorize an import stream that omits the SQL_MODE
directives while leaving the authoritative file unchanged. Neither exception has
been assumed or executed. Global and application SQL mode must remain unchanged.

## Live validation coverage

| Requested category | Status | Evidence / remaining work |
|---|---|---|
| Schema application | DEFERRED | Blocked by unchanged-import / unchanged-mode conflict |
| Actual tables, governance columns, types, defaults, indexes and unique keys | DEFERRED | Isolated schema not installed |
| CHECK enforcement and invalid ENUM/SET service validation | DEFERRED | No isolated SQL operations executed |
| All specified FK relationships and restrictive behavior | DEFERRED | No isolated SQL operations executed |
| Both source triggers: creation, valid insert, self insert/update rejection | DEFERRED | No triggers created or exercised |
| Source lifecycle and withdrawal irreversibility | DEFERRED | Live service tests not run |
| Valid replacement chains and service cycle rejection | DEFERRED | Live service tests not run; triggers do not provide general cycle prevention |
| Legacy initialization | DEFERRED | No synthetic fixtures created |
| Editorial save, updated_by, history, no-op and stale save | DEFERRED | No synthetic fixtures created |
| Item inclusion, positive/duplicate order and item types | DEFERRED | No synthetic fixtures created |
| Source attachment, duplicate rejection and claim validation | DEFERRED | No synthetic fixtures created |
| Submission event, canonical snapshot/hash and unpublished status | DEFERRED | Live workflow not run |
| Reviewer scope, revocation, independence and legacy attestation | DEFERRED | Live workflow not run |
| Approval event/pointer, rationale, UTC expiry and unchanged revision | DEFERRED | Live workflow not run |
| Stale revision and editor/reviewer races | DEFERRED | Live workflow not run |
| Competing reviewers and exactly one approval | DEFERRED | Neither true concurrent testing nor simulation executed |
| Changes Required and fresh corrected submission | DEFERRED | Live workflow not run |
| Snapshot/hash changes | DEFERRED | Live workflow not run; existing model hashes bibliography/content/relationships and validates live source status separately |
| Source invalidation, including withdrawal during review | DEFERRED | Live workflow not run |
| Transaction rollback/failure injection and deadlock retry | DEFERRED | Live workflow not run |
| Review expiry eligibility | DEFERRED | Live workflow not run; no interval invented |
| Editorial/reviewer/changes browser workflows | DEFERRED | Browser testing depends on successful isolated database validation |
| Browser authorization and stale/GET mutation protection | DEFERRED | No isolated browser session started |
| CSRF browser rejection paths | DEFERRED | No isolated browser session started |
| Browser CSP console and scripts/event handlers | DEFERRED | No isolated browser session started; CSP unchanged |
| Public hold for draft, pending and approved/unpublished fixtures | DEFERRED | No isolated browser session started; public reader unchanged |
| Cleanup | PASS | No test database, fixtures or test server were created; nothing to destroy |

The previous 88 foundation, 73 editorial, 107 review and 45 authentication results
remain earlier database-free test evidence. They were not reclassified as live
MariaDB or browser results, and the suites were not rerun in this blocked stage.

## Defects, policy dependencies and next steps

Observed issue: dump SQL-mode bookkeeping clears the mode before saving it and
cannot meet the current unchanged-session-mode requirement. No application or
governance runtime defect was demonstrated; the remaining live checks are deferred.
No schema syntax correction is justified without an attempted authorized import.

Resolve the import conflict first, then complete the existing Phase 2C-2 and
Phase 2C-3 live checklists plus all categories above. Stop on any specified schema,
authorization, stale/concurrent approval, invalidation, public-visibility or CSP
failure. Do not silently fix application code during this validation-only task.

Before Phase 2C-4, publication exceptions for insufficient/unsupported evidence,
wellness and higher-risk content still need approved policy. A maximum review
interval and qualification operations also remain policy dependencies. Internal
review may run under public hold; this does not authorize publication or public
retrieval changes. Explicit Phase 2C-4 authorization remains required.

## Safety and files

Only this report was created. Application source, `database/structure.sql`,
configuration and credentials were not changed. No database creation/drop,
schema/data write, SQL-mode mutation, existing-database modification or Git mutation
was performed. No browser testing or Phase 2C-4 implementation was performed.
