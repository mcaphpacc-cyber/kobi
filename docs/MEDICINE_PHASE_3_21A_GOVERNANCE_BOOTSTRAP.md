# Medicine Phase 3.21A — Initial owner administrator bootstrap

Status: **IMPLEMENTED AND VALIDATED; NORMAL DEPLOYMENT AND AUTHENTICATED ESTABLISHMENT PENDING.** No normal grant was written. Operational establishment is incomplete.

## Owner decision and account model

The human owner designated existing KOBI account **1** as Medicine governance owner and chose **A: owner becomes initial Medicine Administrator**. Their subsequent instruction makes this administrator grant permanent with **NULL expiry**, while delegated grants must still expire. No contributor/deployment/reviewer/registry role was authorized.

Protected `medicine_governance.owner_bootstrap` configuration records account 1 as owner and initial administrator, decision reference `owner-account-1-choice-a-2026-10-10`, and policy version `initial-owner-v1`. This designation is **inert**, conferring no ordinary administrator/deployment authority before a real bootstrap grant is committed. Existing legacy owner/operator lists remain empty.

The normal SELECT-only inspection at **2026-10-10 18:15:09 UTC / 23:45:09 Asia/Calcutta** verified account 1 exists and is active. No names/emails/credentials were printed. The user's explicit decision was not substituted for genuine application authentication.

Inspected users/schema, UserRepository, AuthService, profile membership, Treatment/Disease admin flows, Medicine policy/authorization/governance/repository and existing tests. Existing accounts/authentication establish identity and account state, not company ownership. Profile/Treatment permissions are unrelated. The earlier missing-owner stop is resolved by explicit designation, not inference from the first user or DB credentials.

## Bootstrap, lock and audit

`MedicineGovernanceService::bootstrapAdministrator()` obtains the actual `AuthService::id()`, locks/checks the active actor, requires the designated owner and initial target, and accepts exactly user_id, capability, scope, expires_at, rationale and decision_reference. Only administrator/catalog, explicit NULL expiry, matching protected decision and nonblank rationale are allowed. Unexpected qualification/reviewer/bypass fields, forged identities or stale decisions fail closed. This implementation supports the approved Choice A only.

Required command payload, through a genuine authenticated account-1 integration:

```php
[
    'user_id' => 1,
    'capability' => 'administrator',
    'scope' => 'catalog',
    'expires_at' => null,
    'decision_reference' => 'owner-account-1-choice-a-2026-10-10',
    'rationale' => /* real explicit establishment rationale */,
]
```

The fixed owner user row serializes concurrent bootstraps, including empty authority history. Repository `authorityInitialized()` checks **any historical grant**, not current active administrator count. Any earlier grant closes bootstrap with 409. Revocation/expiry cannot reopen it. Legacy owner configuration must be empty, preventing a second implicit trust root.

The transaction inserts one self-established owner administrator grant and appends the existing immutable grant event, revision 1. Its snapshot adds `establishment.mechanism = initial_owner_bootstrap`, owner ID, decision reference and policy version. Existing grant-subject FKs, actor/time/rationale/state/hash protections and event no-update/no-delete triggers are reused. A failed audit append rolls back the grant. History retains the initial grant through its RESTRICT FK and cannot be deleted by supported operations.

This is separate from ordinary `grantAuthority()`, whose self-grant prohibition remains unchanged. No generic self-grant exception, default admin, first-user rule, new public endpoint or forced actor flag exists. Zero authority alone never authorizes bootstrap. No HTTP endpoint, page or CLI authentication substitute was added; normal execution still needs an approved genuine authenticated integration. An HTTP integration would require authentication/authorization, POST and CSRF.

## Non-expiring owner authority and delegates

Owner expiry is actual **NULL**, never an arbitrary distant timestamp. Authorization accepts it only for administrator/catalog, grantor=grantee, current designated owner, absent qualification fields, and a matching immutable revision-1 establishment event. Snapshot hash, grant fields, decision reference and policy version must match. Active account/current unrevoked grant and grant-date checks still apply. Non-expiring does not mean irrevocable.

Ordinary delegated grants still require a valid future timestamp, including delegated administrators. Contributor/editor/reviewer/registry/deployment cannot be permanent. Reviewer qualification rules are unchanged. A self-admin NULL-expiry SQL row without matching establishment proof cannot authorize an actor.

The schema backstop validates the special self-admin shape; service validation proves owner designation and immutable event binding. Privileged SQL/DDL able to fabricate/disable history is not an authorized bootstrap mechanism.

## Exact minimal schema correction

Modified authoritative schema only for `medicine_authority_grants.expires_at` nullability and `chk_medicine_grant_active`. All unrelated grant columns/indexes/constraints and other table/view/trigger definitions remain unchanged.

Prepared artifact: `database/medicine-phase321a-owner-authority.sql`, SHA-256 `2c96acc2e198b9838c9388c45abedb6c355c7b8ae1cd750bbad7c1365b97ebd1`.

```sql
ALTER TABLE `medicine_authority_grants`
 MODIFY COLUMN `expires_at` datetime(6) DEFAULT NULL,
 DROP CONSTRAINT `chk_medicine_grant_active`,
 ADD CONSTRAINT `chk_medicine_grant_active` CHECK (is_active IN (0,1) AND ((expires_at IS NOT NULL AND expires_at > granted_at) OR (expires_at IS NULL AND BINARY capability=BINARY 'administrator' AND BINARY scope=BINARY 'catalog' AND user_id=granted_by AND qualification_reference IS NULL AND qualification_assessed_by IS NULL AND qualification_scope IS NULL)));
```

The **exact artifact** succeeded on disposable MariaDB **10.4.27**, with no warnings. The harness reverses only the two source changes and recovers the exact previously tested Phase 3.19 schema hash `e9ce3a2be1153845b035207a35a8a319c4d03f5e87f7b96d87da122059081f79`, imports that schema, then executes the real ALTER. It verifies all unrelated actual definitions remain unchanged. The comparison removes only the two intentionally changed lines and trailing-comma framing caused by relocating the replaced CHECK.

Updated structure SHA-256: `43603f352d4b21f3ca9d6d61690546f1301d81034926269d27e7bb77432279b2`. Phase 3.19's separate provisional artifact is unchanged: `84166a59636a8eefcf580bc8bc71b09c19951255a865d2b8a8415ba62a41be48`.

**Normal deployment prerequisite:** expiry is still NOT NULL in `medical1`. Phase 3.21A section 19 requires a stop for newly required schema deployment unless explicitly approved for the normal database. The later user instruction authorized this minimal model correction; this run prepared/tested it but did not interpret that as permission to execute the new normal ALTER. Explicit normal application of this exact artifact is still required. No normal DDL or grant attempt occurred.

## Grant/revoke and separation

Designated owner delegation recognition requires current administrator authority first. The owner can then explicitly delegate/revoke operator grants; a delegate administrator cannot issue administrator/registry/deployment grants. No such role was automatically issued. Legacy owner semantics are preserved, but the new root does not use the old list that would automatically pass administrator/deployment checks.

Existing grants/revocations are transactional and attributed. Revocation sets inactive/revoker/time/reason and appends revision-2 history rather than deleting grants. Exact unrevoked tuple uniqueness prevents duplicates; expired unrevoked grants still occupy their slot until revoked. Duplicate attempts fail safely, with no silent replacement. Different roles may coexist only when explicitly issued; no all-access role was added.

Current grant rows are mutable for revocation. Immutable history lives in the protected governance events; no new immutable-grant-row trigger is claimed. Reviewer use still requires real verified current Doctor/Pharmacist qualifications, current submitted facts/evidence, and independence from creator/prior contributors. No provisional approval exists. Future cross-object provisional-to-canonical authorship preservation remains a separate handoff design requirement.

## Authority matrix

Roles alone are shown. A registry/deployment gate is an authorization check, not an execution API.

| Role | Prepare draft | Review | Approve | Registry write | Deploy | Pilot activation | Publish |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Medicine Administrator (`administrator/catalog`) | NO without contributor/editor | NO | NO | NO | NO without deployment grant | NO by role alone | NO |
| Contributor (`contributor/composition`) | Permitted provisional work after deployment | NO | NO | NO | NO | NO | NO |
| Qualified Reviewer (`reviewer` + qualified scope) | NO without contributor/editor | Independent canonical review | Conditional canonical verification, never own work/provisional approval | NO | NO | NO by role alone | NO |
| Registry Operator (`registry/catalog`) | NO | NO | NO | Separate gate only | NO | NO | NO |
| Deployment Operator (`deployment/catalog`) | NO | NO | NO | NO | Separate gate only | Not automatic; all current approval/enrollment/activation prerequisites | NO |

Editor/composition can prepare provisional work and steward can append authorized terminal closure. Neither is implicit in administrator authority. Stored publisher vocabulary does not enable public Medicine publishing.

## Current normal-state table

Read-only target verification: `medical1`, port 3306, `10.4.27-MariaDB`. Session/global mode: `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`. Grants/events/identities/chemical references/pilot rows were counted 0. Provisional table and all three triggers are absent. No normal mutation followed this check.

| Item | Before | After implementation |
| --- | --- | --- |
| Explicit owner designation | Absent | Account 1; inert before grant |
| Medicine Administrators | 0 | 0 persisted; account 1 pending bootstrap |
| Contributors | 0 | 0; not authorized |
| Deployment Operators | 0 | 0; not authorized |
| Qualified Reviewers | 0 | 0; not assigned |
| Registry Operators | 0 | 0; not authorized |
| Provisional records | 0; journal absent | 0; journal absent |
| Canonical Medicine Identities | 0 | 0 |
| Chemical References | 0 | 0 |
| Reviewer decisions/approvals | 0 | 0 |
| Pilot enrollment | 0 | 0 |
| Pilot activation | OFF | OFF; flags preserved |
| Public Medicine | OFF in inspected implementation | Unchanged; no normal browser test |
| Treatment HOLD | ON | ON; effective configuration preserved |

## Tests actually executed

PHP 8.2.0, disposable MariaDB 10.4.27 in matching non-strict mode. New database-free tests were first observed failing for missing implementation, then passed after implementation.

| Suite | Cases/checks | Assertions | Failures |
| --- | ---: | ---: | ---: |
| New authority bootstrap, database-free | 27 | 116 | 0 |
| New authority bootstrap, MariaDB | 12 | 122 | 0 |
| Medicine policy | 33 | 92 | 0 |
| Medicine governance | 56 | 249 | 0 |
| Medicine provisional identity | 43 | 240 | 0 |
| Medicine pilot | 40 | 143 | 0 |
| Medicine foundation | 105 | 216 | 0 |
| Phase 3.18 preflight reproducer | 11 | 116 | 0 |
| Phase 3.17 evidence readiness (`test`) | 11 | 134 | 0 |
| Medicine foundation MariaDB | 29 | 96 | 0 |
| Medicine policy MariaDB | 11 | 43 | 0 |
| Medicine governance MariaDB | 38 | 141 | 0 |
| Medicine pilot MariaDB | 15 | 135 | 0 |
| Medicine provisional identity MariaDB | 15 | 65 | 0 |
| Treatment admin hardening | 39 focused + embedded suites | Not separately counted | 0 |
| Treatment creation | 110 focused + 333 embedded | Not separately counted | 0 |
| Treatment operations | 127 focused + 443 earlier = 570 | Not separately counted | 0 |
| Treatment publication correction | 17 new + 105 retained = 122 | Not separately counted | 0 |
| Authentication | 45 database-free scenarios | Not separately counted | 0 |

New bootstrap: **39 cases / 238 assertions**. Retained Medicine: **407 cases / 1,670 assertions**. Combined Medicine: **446 cases / 1,908 assertions**. Counts reflect actual runs; lawful concurrency winners can vary retained assertions. Treatment embedded suites overlap and are not summed as distinct cases.

Security negatives cover absent/forged/inactive owner actor; malformed/missing/stale designation; substituted target/role/scope/decision; reviewer/qualification/bypass fields; SQL syntax/type injection; ordinary owner self-grants; no automatic contributor/reviewer/deployment/registry authority; non-expiring delegates rejected by service/CHECK; SQL self-admin forgery without audit proof denied; hash/decision tampering; audit rollback/immutability; revoked authority reuse; historical bootstrap lock and owner-only delegation. Existing canonical self-approval, scientific/pilot/public/lifecycle regressions passed.

Actual two-process database races passed: simultaneous bootstraps (one success/one 409), bootstrap versus normal grant from empty history (ordinary grant only after establishment or denied), bootstrap reuse versus later grant, duplicate administrator delegation (one tuple), and grant/revoke ordering with post-revocation denial. No normal concurrency/mutation tests ran.

Seven new/modified PHP files passed lint; reference/diff/whitespace review passed. Initial evidence invocation lacked required `test` mode and was corrected without code changes. Initial isolated startup hit an asynchronous-I/O assertion; restarting only the isolated instance with synchronous I/O succeeded before schema tests. Initial metadata comparison exposed SHOW CREATE trailing-comma placement; normalization was corrected and all unrelated definitions matched. No normal schema error/repair occurred.

## Isolated import and cleanup

New suite: guarded `kobi_medicine_phase321a_validation_20261010`, port 3341, `.medicine321a-validation/data`. Retained suites used their guarded ports 3325/3328/3339 and separate datadirs. All content/credential fixtures were synthetic, never normal accounts or credentials.

Final bootstrap suite imported 142 prior-schema statements, executed 1 exact ALTER, then imported 142 updated-schema statements in a fresh synthetic initial state for the bootstrap/normal-grant race. Retained suites each imported 142 updated-schema statements. All schema objects were imported, including provisional objects **only on disposable instances**. Only these original dump mode directives were omitted:

```sql
/*!40101 SET SQL_MODE=''*/;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
```

No other schema statement was silently omitted. Original directives remain in structure.sql. Normal/global/persistent SQL mode was not changed. Test servers used the matching expected mode.

All six synthetic databases were dropped through guarded suite commands, four test servers shut down, and transient datadirs/barrier/log files removed after resolved-path/reparse-point/process checks. Cleanup initially waited for shutdown process completion instead of deleting an active datadir. The temporary normal SELECT-only helper was removed; retained evidence/backups were untouched.

## Remaining steps and Phase 3.20

1. Explicitly authorize applying the exact tested `database/medicine-phase321a-owner-authority.sql` to the normal database. This is not Phase 3.19 provisional deployment.
2. Before normal DDL/grants, use the established fresh protected backup and recovery procedure: verify readability/hash and disposable recovery, recapture target/version/modes/schema/data/privileges and account-1 state. No fresh backup is claimed by this run.
3. Apply only that correction, verifying metadata, no unrelated changes and unchanged modes. Stop on warnings/errors/differences.
4. Execute protected bootstrap through genuine account-1 application authentication and approved integration. Record real grant/event IDs and NULL expiry and verify actual authority/closed bootstrap. Do not substitute test authentication, a forged session or a CLI user ID.
5. Contributor/draft-author and deployment decisions remain separate prerequisites for Phase 3.20. Ordinary self-grants stay denied; a legitimate distinct administrator must issue an ordinary grant to account 1 if later selected as contributor.

Phase 3.20 still needs its protected backup/recovery, exact provisional artifact/evidence validation, metadata/data/privilege comparisons, additive deployment and five real service-created drafts. Its old full-schema hash must be reconciled with this intentional owner correction, not bypassed. No professional review, pilot or public gate opens here.

## Files modified / created

Modified:

- `app/Config/Config.php` — inert owner/initial-admin account-1 designation.
- `app/Services/MedicineGovernancePolicy.php` — bootstrap decision and distinct owner delegation recognition.
- `app/Services/MedicineAuthorizationService.php` — permanent owner authority requires immutable establishment binding.
- `app/Services/MedicineGovernanceService.php` — one-time bootstrap; ordinary self-grants preserved; designated-owner operator delegation/revocation after administrator authorization.
- `app/Repositories/MedicineGovernanceRepository.php` — historical initialization locking read.
- `database/structure.sql` — only authority expiry column/CHECK.
- `docs/MEDICINE_PHASE_3_21A_GOVERNANCE_BOOTSTRAP.md` — replaces prior stop report with actual implementation/validation results.

Created:

- `database/medicine-phase321a-owner-authority.sql`;
- `tests/medicine-authority-bootstrap.php`;
- `tests/medicine-authority-bootstrap-mariadb.php`.

Pre-existing workspace changes were preserved. No unrelated refactor or reset occurred.

## Explicit safety confirmations

Latest authorized normal deployment attempt (2026-10-11 Asia/Calcutta): fresh backup created, but recovery import stopped on an unrecognized mysqldump trigger mode directive. No normal ALTER or bootstrap occurred. See [the deployment stop and retained backup evidence](MEDICINE_PHASE_3_21A_OWNER_DEPLOYMENT_ATTEMPT.md). The previous pending implementation steps remain incomplete; normal authority grants/events are still zero.

Owner designation/authorization is available for account 1; **no normal administrator or other role grant has yet been written**. Normal DB was SELECT-only, with no DDL/data/privilege change. No real fake account/credential/reviewer qualification was created; synthetic fixtures were removed.

No normal provisional schema deployment/record, canonical Medicine Identity, Chemical Reference, ingredient/presentation/strength, reviewer decision, evidence acceptance, approval, enrollment, activation or publication occurred. Registry/personal medication/authentication/Treatment/Disease data and behavior were untouched by this run. Treatment HOLD and disabled pilot flags remain unchanged. SQL mode and Git history were unchanged; no commit/push/reset/checkout/merge/rebase or other Git mutation occurred.
