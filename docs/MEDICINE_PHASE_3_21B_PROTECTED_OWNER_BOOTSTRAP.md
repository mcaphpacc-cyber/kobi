# Medicine Phase 3.21B protected owner-bootstrap entry point

**Final result: PASS.** Date: 2026-10-11, Asia/Calcutta. This implements and validates the legitimate entry point only. The normal migration is NOT APPLIED; normal Account 1 authority remains ZERO; no normal bootstrap was performed.

## Existing implementation and architecture

Inspected `MedicineGovernanceService::bootstrapAdministrator`, `MedicineGovernanceRepository`, `MedicineGovernancePolicy`, `MedicineAuthorizationService`, the authority schema in `database/structure.sql`, authority grant/event constraints, immutable audit triggers, existing account/Treatment controllers, AuthService, Session, Router, Container and views/CSRF helpers.

The existing bootstrap locks the authenticated active owner user within the write transaction, validates protected designation/target/role/NULL expiry, checks all historical authority initialization, inserts one grant and appends one immutable establishment event atomically. It does not make an exception in ordinary self-grant issuance. Non-expiring authorization requires the matching immutable owner establishment event. These mechanisms and ordinary delegated expiry rules were preserved.

The flow remains Controller → Service → Repository → Database. The new controller contains no queries or repository writes. Authentication, Session CSRF, global redirect(), controller view rendering and Bootstrap styling use the existing conventions. No shared authentication/base-controller/session code, JavaScript or dependencies changed.

## Routes and authentication boundary

- GET `/admin/medicine/owner-bootstrap`: authenticated, fixed-owner status/confirmation page; never grants authority.
- POST `/admin/medicine/owner-bootstrap`: authenticated CSRF-protected confirmation; calls `bootstrapOwnerFromSession()`, which calls the existing `bootstrapAdministrator()` operation.

Both controller actions explicitly require `AuthService::id() === 1`. Unauthenticated GET/POST redirect to `/login` according to existing account conventions. Both use `Cache-Control: no-store, private` and `X-Robots-Tag: noindex, nofollow`.

The service independently checks that authenticated Account 1 exists and is active, is explicitly the designated owner, and is the designated initial administrator. Missing/malformed designation, another owner/target or conflicting legacy owner designation fails closed. An owner indication supplied by a browser or ordinary account metadata is never accepted as authority.

Actual application wiring was checked with an unauthenticated GET to the new route; it redirected to the real login page. No login form or state-changing normal request was submitted. Positive authentication behavior was tested using CLI-only synthetic fixtures/disposable users, never normal sessions or Account 1 credentials.

## Fixed target, CSRF and method controls

The POST accepts only `_csrf_token` and the exact confirmation value `confirm=establish_owner_administrator`. Any additional POST field or query parameter is rejected. CSRF must be a string from POST and pass the existing `Session::verifyCsrfToken`; missing, invalid, array/numeric and URL-only tokens are denied.

Account/authority/expiry/rationale/audit fields are not accepted from clients. The server constructs:

- Subject and owner: Account 1.
- Capability/scope: `administrator` / `catalog`.
- Expiry: `NULL`.
- Decision reference: protected deployment policy.
- Rationale: fixed owner-establishment text.

The GET page displays **“Account 1 — KOBI Owner — Medicine Administrator — No Expiry”** with no editable account/role/expiry controls. It displays a CSRF-protected confirmation button only when setup is ready and historical bootstrap is open. Wrong methods are denied even when controller actions are invoked directly. GET cannot call the authority-changing operation.

## Migration readiness and replay/concurrency

A read-only repository status method checks historical initialization and whether `medicine_authority_grants.expires_at` is nullable. The service refuses execution with safe 503 while the required schema is absent. This readiness indication is an additional guard, not a replacement for the separately required full migration/schema/backup verification. No new schema is needed for the entry point.

`bootstrapOwnerFromSession()` refuses already-initialized history with 409 and delegates writes to the existing bootstrap command. That command rechecks active authentication/owner policy under the existing transaction/owner row lock. The GET status is not trusted to authorize a later write; concurrent or stale POSTs must pass the command's final locked check.

Successful POST redirects to the GET status page with a Session success flash. Refresh does not repeat a command. Repeated POSTs, double-clicks and concurrent attempts cannot insert another grant/event. Expired or revoked historical authority does not reopen bootstrap. No form can modify audit history.

## Error handling

The controller uses fixed safe messages for 403/405/409/422/503/500 and escapes error output. It never displays service/SQL exception text, internal class names, stack traces, paths or credentials. Server diagnostics use existing Logger and exception classification only. Redirects occur outside exception-catching blocks, preserving existing controller/test conventions.

Forced persistence/audit failure rolls back and returns safe failure feedback. Unexpected service exceptions return generic 500 without their private sentinel text. Unauthorized or failed requests do not create a successful establishment event.

## Focused tests and actual results

| Suite | Cases/scenarios | Assertions | Failures |
|---|---:|---:|---:|
| New DB-free owner entry-point tests | 40 | 82 | 0 |
| New MariaDB owner entry-point tests | 6 | 44 | 0 |
| Retained owner-bootstrap service | 27 | 116 | 0 |
| Retained Medicine governance | 56 | 249 | 0 |
| Retained Medicine policy | 33 | 92 | 0 |
| Retained Authentication | 45 | Runner reports scenarios only | 0 |
| Retained Treatment admin authorization hardening | 39 focused plus its retained embedded suites | Runner reports tests only | 0 |

The new suites total **46 cases / 126 assertions**. Treatment embedded suites overlap and are not summed as distinct new cases. No unrelated test suites were run.

DB-free coverage includes unauthenticated GET/POST, fixed-owner read-only GET/form, Account 2/other accounts, inactive owner, missing/incorrect/conflicting designation, CSRF presence/type/value/location, wrong HTTP methods, missing/malformed confirmation, account/role/authority/expiry/owner/reviewer/contributor/deployment/audit tampering, query parameters, schema-unavailable execution, fixed positive authority/audit, refresh/repeated POST/revoked-history lock, ordinary self-grant, expiring delegation requirements, persistence rollback and exception disclosure. Fixtures reset session state between cases.

An initial positive audit assertion expected an integer owner ID; the existing snapshot canonicalizer stores IDs as strings. The fixture assertion was corrected to the established canonical representation, without changing production audit handling or weakening comparisons. No security-negative test failed.

## Disposable MariaDB results

Guarded MariaDB **10.4.27**, port **3342**, separate datadir `tests/.medicine-owner-recovery/data`, database **`kobi_medicine_phase321b_web_validation_20261011`**. Only synthetic users 1/2 with `NOT_A_LOGIN_CREDENTIAL` password markers were inserted there; these are not normal account credentials or usable login sessions.

The current authoritative schema was imported into the empty disposable database: **142 schema statements**, including all defined schema objects. Original source SHA-256 remained `43603f352d4b21f3ca9d6d61690546f1301d81034926269d27e7bb77432279b2`. The approved normal migration file was never executed. To test under the existing non-strict mode, only these full-schema dump directives were omitted from the disposable import stream:

```sql
/*!40101 SET SQL_MODE=''*/;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
```

No other schema statement was omitted or edited. This synthetic schema import is not a recovery import: the authoritative protected backup and its lossless recovery procedure remain unchanged.

Two separate PHP worker processes invoked the actual new controller → service → repository path concurrently, using distinct PDO connections and a start barrier. Exactly one redirected after success; the other returned **409**. Actual tables contained exactly one administrator/catalog grant for synthetic user 1 with NULL expiry and one correctly attributed `initial_owner_bootstrap` event. Actor/subject/owner/role/NULL expiry and binary snapshot hash were verified. Read-only status and repeated POST created no additional rows.

Actual inactive/cross-account/tampered POST negatives created no grants/events. Actual audit UPDATE/DELETE attempts were rejected by immutable triggers. Normal self-grant and non-expiring delegated administrator requests remained denied. Contributor/editor/reviewer/registry/deployment authorization remained absent. No Medicine content, provisional record, pilot record, personal medication or Treatment record was created by this flow. Session/global modes remained `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`.

The synthetic database was dropped and absence checked. Its isolated server/datadir/barrier/output files were removed after actual process identity/exit and path/reparsepoint checks. No validation database remains. The isolated startup server log is retained with existing protected recovery diagnostics; the original backup is intact.

## Normal preservation and final security review

Normal database inspection was strictly SELECT/metadata only. The retained recovery verifier confirmed all **54 table row digests**, schema/view/trigger/routine/event metadata, all **8 existing triggers**, recorded privileges and SQL/character settings still match the original verified baseline. Account 1 remains active; normal authority grants **0**; Medicine governance events **0**; expiry column remains NOT NULL. No migration/normal authority write/bootstrap occurred.

Final review checked authentication/active-owner designation, IDOR/arbitrary target exclusion, role/expiry/audit tampering, CSRF, method isolation, replay, transactional initialization lock, error disclosure, least privilege and controller/service separation. No shared auth/controller behavior was weakened. The actual unauthenticated route and all relevant retained regressions pass.

Both pilot flags and activation configuration remain disabled; public Medicine was not activated. Treatment HOLD and all Treatment data/governance are unchanged. No identity revision/evidence/reviewer/pilot candidate/approval/publication work was performed. Application/configuration/authority schema/migration files unrelated to this endpoint were preserved. Git was not committed/pushed/reset/checked out/merged/rebased or otherwise mutated.

## Files and checks

Modified:

- `app/Repositories/MedicineGovernanceRepository.php` — read-only owner status/readiness.
- `app/Services/MedicineGovernanceService.php` — session/active-owner status and fixed input adapter to existing command.
- `routes/web.php` — protected GET/POST routes.

Created:

- `app/Controllers/MedicineOwnerBootstrapController.php`.
- `app/Views/admin/medicine/owner-bootstrap.php`.
- `app/Views/admin/medicine/owner-bootstrap-error.php`.
- `tests/medicine-owner-bootstrap-web.php`.
- `tests/medicine-owner-bootstrap-web-mariadb.php`.
- `docs/MEDICINE_PHASE_3_21B_PROTECTED_OWNER_BOOTSTRAP.md`.

All eight modified/new PHP files passed lint. Focused/retained tests, route/reference review and `git diff --check` passed. No SQL/schema modification is required by this phase. No change to `database/structure.sql` or `database/medicine-phase321a-owner-authority.sql` was made.

## Remaining prerequisite

The next prerequisite is a **genuine authenticated Account 1 session** in the actual application. Credentials were neither requested nor fabricated. Positive real-owner browser execution remains untested and intentionally deferred. The normal deployment must then be explicitly resumed, recheck the verified backup and current preconditions, apply only the approved migration, and pass all post-migration preservation checks before the owner submits this protected POST.

This phase ends with **Migration NOT APPLIED; Account 1 authority ZERO; normal bootstrap NOT PERFORMED; pilot OFF; public Medicine OFF; Treatment UNCHANGED; Git UNCHANGED**. The endpoint being available does not authorize executing it now.
