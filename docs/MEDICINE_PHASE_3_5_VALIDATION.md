# Medicine Knowledge Foundation — Phase 3.5 Persistence & Canonicalization

Validation date: 2026-10-09. Decision: **PASS WITH LIMITATIONS**.

## 1. Scope

Additive application persistence for the seven Phase 3.4 foundation tables, following the Phase 3.2 identity specification and Phase 3.3 schema/persistence contract. No existing project file was changed: SHA-256 comparison against the 271-file pre-implementation baseline found zero changes or removals. Eight files were created. Existing Git modifications/deletions were preserved.

No schema edit, migration, production population, legacy migration, endpoint/UI integration, verification workflow, clinical knowledge, publication, Treatment change, or Phase 3.6 implementation occurred. No normal KOBI database connection or write was made by these tests.

## 2. Implemented Application Components

| Created file | Responsibility |
| --- | --- |
| `app/Repositories/MedicineRepository.php` | Parameterized persistence primitives and owned transactions |
| `app/Services/MedicineCanonicalizer.php` | Exact identifiers, controlled codes, canonical keys and rational quantities |
| `app/Services/MedicineIdentityService.php` | Authorized atomic commands, aggregate validation and private associations |
| `app/Services/MedicineAuthorizationService.php` | Injectable shared-catalog boundary; production implementation always denies |
| `app/Services/MedicinePersistenceException.php` | Safe application errors |
| `tests/medicine-foundation.php` | Database-free fixtures and focused cases |
| `tests/medicine-foundation-mariadb.php` | Guarded disposable MariaDB import, real repository/service cases and workers |
| `docs/MEDICINE_PHASE_3_5_VALIDATION.md` | This report |

Files modified: **none**. The new concrete constructors fit the existing recursive dependency injection mechanism; no container, configuration, controller, route, view or JavaScript edit was needed.

## 3. Repository Contract

`MedicineRepository` extends `BaseRepository` and uses its shared PDO connection, parameter binding and transaction helpers. It retrieves actors, profile/member locks, registry dependencies, adapters, identity/member aggregates, presentations/strengths and scoped medication sidecars. Writes use fixed field allowlists and literal table/column identifiers. Only validated service commands should call these internal primitives.

`transaction(callable)` owns its boundary, rejects nesting, rolls back on failure, and retries the entire callback in a fresh transaction for native 1062/1213 errors, at most twice after the initial attempt. No independent ingredient-edit operation, upsert, registry insert, status transition or public retrieval method was added.

## 4. Service Contract

| Command | Inputs and result |
| --- | --- |
| `registerChemicalReference(array)` | Exactly one existing `substance_id` or `form_id`; returns adapter ID/status/created flag; new adapter is candidate |
| `createMedicine(array)` | Required ingredient list; optional display name and complete presentation; returns identity ID/status/created flag and optional presentation result |
| `createPresentation(identityId, array)` | Existing complete nonretired composition and full presentation; returns presentation ID/status/created flag |
| `medicationAssociation(profileId, medicationId)` | Authorized private snapshot: digest, link revision, association mode/IDs and selected ingredients |
| `setMedicationAssociation(profileId, medicationId, array)` | Explicit mode and expected digest/revision; atomic sidecar replacement; returns mode/revision |

Ingredient inputs contain exactly one typed ID, never an authoritative browser label. Presentation inputs require `dosage_form_code`, `route_code`, `release_code` and one strength for every member, identified by `chemical_ref_id`. Strengths use either explicit decimal quantity/unit strings or canonical integer ratio coefficient strings. Unknown fields and client actor/owner/status assertions are rejected.

## 5. Canonicalization Rules

Positive identifier strings normalize leading zeroes, reject nondecimal/scalar ambiguity and retain exact values up to the approved PHP integer ceiling. Composition contains 1–16 unique typed underlying references, ordered S before F, then by numeric ID. Adapter/member IDs and display labels do not define composition.

Fixed compact UTF-8 JSON has deterministic property order:

```json
{"v":1,"ingredients":[["S","2"],["S","10"]]}
```

Presentation key order is `v`, `identity`, `strengths`, `form`, `route`, `release`. Each strength entry contains typed underlying reference, reduced numerator/denominator strings and canonical unit codes. SHA-256 is stored as 32 raw bytes. Keys are limited to 8192/16384 bytes. Applicable display/original text is NFC-normalized with whitespace collapse, UTF-8/control-character validation and field limits; it never determines chemical identity.

## 6. Substance Persistence

Selected IDs resolve to a persisted chemical adapter and the actual existing substance/form. Creation and new selections require verified adapter provenance and active underlying rows, including a form's active parent. The service does not reinterpret an imported registry label/status as verification. Candidate adapter registration is separate from verification and cannot create substances/forms.

The new selected-ID path is `health_medicine_selected_ingredients.chemical_ref_id` → `kobi_medicine_chemical_refs.substance_id/form_id`; canonical members use the same adapter. No selected ID is discarded by this contract. Existing autocomplete/controller behavior remains unchanged until separately integrated.

## 7. Ingredient Persistence

Every identity is created with all members inside one transaction. Members persist the adapter FK, canonical order and creation time. Reloading recomputes the complete composition and compares its full key/hash, membership count, reference identity and parent scope. Missing, duplicate, wrong-parent, inactive or unusable members cause rejection rather than silent filtering.

## 8. Combination Medicine Handling

One or multiple distinct typed chemical references are supported up to sixteen. Reordered input and altered labels reuse the same composition. S and F references retain their distinct approved meanings. Complete presentation strengths map to the canonical members, independent of input order. User selections remain private observations; they do not create or modify a canonical combination.

## 9. Presentation Persistence

Presentations belong to one existing identity and have exactly one strength row per member. Identity, optional presentation and strengths can be created together atomically. Standalone presentation creation also validates the current complete identity.

Supported forms/routes: tablet/capsule/solution/suspension → oral; cream/ointment → cutaneous. Solid releases are immediate/delayed/extended; others require explicit not_applicable. Strength denominator basis is unit for solids, mL for liquids and g for topical forms. Codes are exact case-sensitive values; nothing is inferred from user instructions.

Multiple valid strength/form/release variants share a composition while reserving distinct presentation keys. Brand, manufacturer, pack and patient dose are excluded. Creation produces candidate rows; private composition/presentation associations require verified dependencies.

## 10. Strength / Quantity Validation

No floating-point arithmetic is used. Decimal input is an explicit positive string, at most twelve integer and twelve fractional places after removal of insignificant zeroes, bounded in raw length. Floats, exponents, signs, zero, malformed decimals and unsupported units fail before SQL.

Accepted mass units: g/mg/ug, with explicit µg/μg aliases. Accepted volume: mL/L. Dosage denominator unit counts must be integral. Canonical numerator is g; denominator is unit/mL/g. BCMath converts exact powers of ten and reduces the fraction by GCD. Coefficients must be positive integer strings of at most 38 digits before and after reduction. Fractional coefficients and overflow are rejected, protecting against MariaDB non-strict rounding/clamping.

Stored strength vectors are recomputed and checked for canonical reduced coefficients and parent/member scope. Original strength text may be preserved as bounded display data; patient dose never enters the vector. BCMath, intl and mbstring were available on PHP 8.2.0; missing required arithmetic/normalization support fails safely with 503.

## 11. Duplicate Detection

Unique hash reservations remain the database backstop. Current locking hash lookups compare the complete key and reload/recompute the aggregate; a hash alone is insufficient proof. Exact owners are reused, labels are not overwritten, collisions/inconsistent aggregates fail, and retired owners retain their reservation without restoration or bypass.

Presentation reuse retains the row from the current locking lookup instead of rediscovering a concurrent winner through an older repeatable-read snapshot. A focused double explicitly simulates that stale discovery case.

## 12. Transaction / Rollback Behavior

Commands lock the authenticated actor, applicable private profile/member scope, chemical adapters and registry dependencies in deterministic order, identity, presentation, then the actual medication/link. Transactions cover complete parent/member/presentation/strength construction or private sidecar replacement. Actor and active membership are rechecked using persisted rows; profile access also uses `HealthProfileRepository::findAccessible`.

Database-free and real PDO tests inject failures after writes. Actual database checks confirm unchanged aggregate counts after identity/member/presentation/strength failures, and preservation of the previous private association after a child write failure. Callback failures, native retry conditions and infrastructure failures cannot commit partial aggregates. Auto-increment gaps are acceptable; row-count integrity is checked, not gaplessness.

## 13. User Medication Integration Boundary

Approved modes are text (no sidecar), composition (verified identity), presentation (verified presentation and identity), or ingredients (verified selected adapters). Mode-specific IDs/lists are exclusive. Children are deleted before changing mode, and the entire replacement commits together. Created attribution is preserved; updated attribution comes from `AuthService::id()`.

Expected tokens contain a server-generated SHA-256 digest of original editable medication fields plus the complete association state/selections, and the expected link revision. The combined state prevents stale raw edits, concurrent link edits and delete/recreate ABA reuse. Sidecar writes never update `health_medicines`.

Existing create/edit endpoints do not call this service. Their CSRF, redirects, views and JavaScript remain intact. Future integrated raw-record and sidecar saves must share an approved transaction; these existing legacy writers remain outside the new stale-token guarantee.

## 14. Legacy Data Handling

All original medication fields were compared byte-for-byte at the PDO result level before/after isolated association transitions, including free-text strength and dose. No migration or label-based inference occurred. Unresolved data remains original private text; explicit text fallback removes only the sidecar/selections. Historical discarded autocomplete IDs cannot be recovered by this phase.

## 15. Authorization / Security

Private reads require an actual active authenticated actor and active profile membership. Writes require owner/editor and exact medication/profile scope. Viewer mutations, inactive/anonymous actors, cross-profile IDs and spoofed actor fields fail safely. Shared writes require a separate injected catalog authority.

KOBI has no approved persisted medicine grant mechanism. The production `MedicineAuthorizationService` therefore denies **all** catalog writes; no profile role or Treatment grant confers catalog authority. Synthetic test grants exist only in test doubles. Production activation remains deferred until an explicitly approved persisted authority and registry reconciliation mechanism exists.

## 16. Error Handling

New commands return safe `MedicinePersistenceException` messages with 403/409/422/503/500 codes. Expected validation/authorization failures retain their safe outcome; PDO failures map to fixed retryable storage errors and unexpected errors to a fixed application error without raw diagnostic chaining. Repository primitives are internal and must remain behind the service boundary.

Fault tests use private diagnostic sentinel strings and assert they are absent from service messages. No new response exposes SQL, stack traces, credentials, paths or medication content. The existing Phase 3.1 disclosure/date defects remain separately scoped and unchanged.

## 17. MariaDB 10.4.27 Validation

**PASS.** A new project-local disposable datadir `tests/.medicine35-validation/data` was initialized and served only on 127.0.0.1:3325, process 11652. The harness checked exact datadir, port, version and SQL mode before mutations. It never loaded application bootstrap or credentials. Database: `kobi_medicine_phase35_validation_20261009`.

Source `database/structure.sql` SHA-256 stayed:

```text
5a7ec86aef99200052731e5169026e9a7e22cba5cd71e122a3b9ce23c3496365
```

Executed: all **125 non-SQL-mode schema statements**, including the existing DROP/CREATE statements, indexes, foreign keys, CHECK constraints, triggers and other dump directives, solely inside the empty disposable database. Client DELIMITER directives were interpreted by the importer, not sent as server SQL. Any non-mode import error is fatal; no schema statement failed and none was repaired or silently skipped.

Intentionally omitted only these exact directives, consistent with the prior explicit isolated-validation approval:

```sql
/*!40101 SET SQL_MODE=''*/;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
```

Reason: validate unchanged schema objects under the server's existing `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`, without executing SQL-mode changes. Initial/final mode matched. No global, server, persistent application or session SQL-mode change occurred.

Actual SHOW CREATE metadata matched every named foundation FK/CHECK and index from the source; all seven primary keys existed. information_schema reported:

| Table suffix/name | CHECK | FK | UNIQUE | PRIMARY |
| --- | ---: | ---: | ---: | ---: |
| chemical_refs | 7 | 5 | 2 | 1 |
| identities | 8 | 3 | 1 | 1 |
| ingredients | 1 | 2 | 3 | 1 |
| presentations | 13 | 4 | 2 | 1 |
| strengths | 3 | 2 | 0 | 1 |
| health_medicine_identity_links | 3 | 5 | 1 | 1 |
| health_medicine_selected_ingredients | 2 | 2 | 1 | 1 |

Foreign-key checks were restored to 1 by the imported stream. Real repository/service queries worked with native prepared statements. No compatibility issue required a schema modification.

Cleanup: disposable database dropped; temporary server shut down normally via its explicit port; process/listener termination verified; newly created temporary directory removed after checking its resolved workspace path.

Reproduction requires an explicitly authorized fresh disposable datadir/server at the same guarded path/port/version/mode. Run `php tests/medicine-foundation-mariadb.php run`, inspect with `metadata`, then `drop` and shut down that instance. `run` refuses an existing validation database. These commands are intentionally unusable against the normal application database.

## 18. Test Results

| Suite | Result | Accounting |
| --- | --- | --- |
| Medicine database-free | PASS | 105 distinct cases, 216 assertions |
| Medicine disposable MariaDB | PASS | 29 distinct cases, 96 assertions |
| Medicine total | PASS | **134 distinct cases**, 312 assertions |
| Authentication regression | PASS | 45 existing scenarios |
| Treatment operations regression | PASS | 127 focused plus 443 inherited checks = 570 |
| Treatment prerequisites regression | PASS | 29 risk, 7 lifecycle, 11 comparison focused checks; inherited checks overlap the operations suite |
| New PHP syntax | PASS | All seven created PHP files |
| Diff/reference/baseline inspection | PASS | Existing tracked diff whitespace check; new file inspection; no references from existing HTTP/public flows; 271 baseline files unchanged |

Medicine cases cover reference/identity validation, single/combo creation, ordering/duplicate rejection, presentation variants, precision/coefficient overflow, units/forms/routes, transactional faults, exact duplicate reuse, authorization, private scoping, stale state, raw preservation and safe errors. Reruns, assertions and inherited regression cases are not added to the 134 count.

During test development an invalid overflow expectation was corrected to an exact large rational result; a PDO integer-versus-string ID assertion was also corrected. Production validation was not relaxed to make these assertions pass. Final executions had zero failures.

Deferred: browser/HTTP integration (no endpoints changed), production grant enforcement implementation, real authority-revocation concurrency, registry reconciliation, verification/retirement commands, legacy migration, broad stress testing, clinical/public workflows and deployment. These are not claimed tested.

## 19. Concurrency / Duplicate-Creation Assessment

Two independent PHP processes with different synthetic actors were synchronized at a file barrier and simultaneously created the same previously absent S10+F30 composition and presentation. Both returned the same identity and presentation IDs; exactly one reported creation of each. Completeness queries found no empty identities or incomplete presentation vectors.

The actual shared adapter/dependency locks serialize these cooperative writers; this proves the observed two-writer outcome, not every scheduling permutation or a forced native duplicate error. Native 1062/1213 retry branches were exercised separately by injected PDO errors after real writes, checking fresh rollback/retry and one resulting row. Schema uniqueness remains the backstop for competing reservations. No high-volume stress, native deadlock construction or unrelated legacy writer coordination is claimed.

## 20. Public Boundary Verification

No public route, controller, UI, API, projection or search integration was added. Reference searches show the new service/repository classes only in their internal implementation and tests. Existing routes/controllers/views/public assets match the baseline. Catalog access is denied in production; private sidecars are scoped and not exposed publicly. Identity verification never implies clinical publication.

## 21. Treatment HOLD Verification

Treatment tables, publication/governance services, configuration and public retrieval code match the baseline. Existing Treatment regression suites, including HOLD/public-boundary assertions, passed. No medicine-to-Treatment links, publication switch or bypass was introduced.

## 22. Findings

| ID | Severity | Finding | Impact | Blocking? | Recommendation |
| --- | --- | --- | --- | --- | --- |
| M35-01 | MEDIUM | No approved persisted medicine catalog authority exists | Production shared writes intentionally unavailable | No for this internal foundation; yes for activation | Approve a separate persisted authority/reconciliation phase |
| M35-02 | MEDIUM | Existing medication endpoints remain legacy text writers | New selected-ID/stale-token contract is not yet used by the UI | No for this phase; yes for endpoint rollout | Resolve Phase 3.1 prerequisites separately, then integrate atomically |
| M35-03 | LOW | Concurrency validation is focused, not a stress campaign | Unrelated writers and future authority/lifecycle changes need additional validation | No | Extend tests with each authorized integration/workflow |
| M35-04 | INFORMATIONAL | MariaDB non-strict quantity coercion remains a DB limitation | Raw SQL outside the service can still round/clamp coefficients | No | Keep validated service boundaries; never rely on coercion |
| M35-05 | INFORMATIONAL | Verification/publication workflows are absent by scope | Synthetic verified fixtures do not activate production catalog data | No | Design separately; preserve private/public boundaries |

## 23. Known Limitations

Production catalog denial is intentional until a persisted grant model is approved. Existing registry semantics require independent reconciliation before real verified adapters are populated. Verification/status workflows, clinical approval and public reading are deferred. Existing medication handlers still discard selected autocomplete IDs; only the new contract persists them. No deployment or actual patient-data exercise occurred.

Exact arithmetic/normalization requires BCMath/intl/mbstring. Private sidecar tokens cover this service, not untouched legacy writers. The isolated harness requires temporary MariaDB setup and synthetic fixture authority; it must not be adapted to production credentials.

## 24. Final Decision

**PASS WITH LIMITATIONS.** The approved seven-table schema supports deterministic, exact, atomic and authorized persistence without alteration. All 134 focused cases pass, actual MariaDB 10.4.27 queries and focused concurrency pass, no pre-existing file changed, and no normal database write or public exposure occurred. No blocking implementation/compatibility issue remains within Phase 3.5. Production catalog activation and existing endpoint integration remain explicitly deferred.

## 25. Recommended Phase 3.6

Approve a narrowly scoped persisted medicine catalog authority and existing substance/form reconciliation contract before enabling shared writes. Resolve the separate Phase 3.1 endpoint safety prerequisites before a later private selected-ID integration that preserves CSRF, raw fallback and atomic stale-state checks. Verification, clinical knowledge and public exposure need separate designs. Phase 3.6 was not implemented.
