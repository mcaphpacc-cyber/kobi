# Step 45B — Personal Medication Schedules / Due Today V1

Date: 2026-10-14. **Step 45B PASS.** Implemented and deployed only the approved medication schedule feature. Step 45C has not started.

## 1. Implementation summary

Existing personal medication → daily schedule slots → profile-scoped Due Today → manual Taken/Skipped → simple paginated history. Multiple daily times are supported. No dose interpretation, adherence scoring, clinical advice, reminders or notifications.

Focused `MedicationScheduleController` → `MedicationScheduleService` → `MedicationScheduleRepository` follows the existing MVC, service, repository and constructor DI conventions. Existing medication records, profile memberships, Bootstrap forms, CSRF helpers, view rendering and redirects are reused. No new framework/dependency or JavaScript was needed.

## 2. Schema changes and deployment

Added exactly the two tables specified by Step 45A:

- `health_medicine_schedule_slots`: medication FK, local HH:MM, timezone, inclusive start/optional end dates, active flag, nullable creator and UTC timestamps. Time, nonempty timezone, active flag and date ordering CHECKs; medication/active and creator indexes.
- `health_medicine_schedule_checkoffs`: slot FK, local occurrence date, exact Taken/Skipped state, medication-label snapshot, nullable acting account and UTC timestamps. Unique `(schedule_id, scheduled_on)`, actor index and binary state CHECK.

The exact approved DDL is in `database/medication-schedules.sql` and appended to `database/structure.sql`. No proposed Step 45C mobile column was added. No existing table/column/index/constraint/trigger was altered by deployment.

MariaDB **10.4.27** accepted each exact CREATE TABLE without warnings in the disposable database before normal deployment. The deployed SHOW CREATE TABLE definitions match the isolated approved definitions, including foreign keys/indexes/CHECKs. No SQL-mode directive was executed. Session and global SQL modes remain **NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION**.

## 3. Files changed / created

Modified existing files (only the Step 45B edits; unrelated pre-existing workspace changes were retained):

- `database/structure.sql` — append approved tables.
- `routes/web.php` — focused authenticated schedule/Due Today/history routes.
- `app/Controllers/AccountController.php` — inject schedule service and project active-slot counts into medication list.
- `app/Repositories/HealthMedicineRepository.php` — serialize updates on the parent medicine; deactivate slots atomically when stopped/historical.
- `app/Views/account/health-medicines.php` — navigation, schedule links/counts, and a visible required deletion checkbox describing schedule/history deletion. This replaces the old inline confirm handler without introducing inline JS or weakening CSRF/profile authorization.

Created:

- `database/medication-schedules.sql`
- `app/Controllers/MedicationScheduleController.php`
- `app/Services/MedicationScheduleService.php`
- `app/Repositories/MedicationScheduleRepository.php`
- `app/Views/account/medication-schedule.php`
- `app/Views/account/medication-schedule-nav.php`
- `app/Views/account/medication-due-today.php`
- `app/Views/account/medication-history.php`
- `app/Views/account/medication-schedule-error.php`
- `tests/medication-schedules.php`
- `tests/medication-schedules-mariadb.php`
- `tests/medication-schedules-deployment.php`
- This implementation report.
- Browser evidence: `docs/task-45b-due-desktop.png`, `docs/task-45b-due-mobile.png`, `docs/task-45b-cleanup-complete.png`.
- Protected recovery evidence under `tests/.medication-schedules-recovery/`: `.htaccess`, `before.bin`, `verified.json`, `mariadb-passed.json`. The serialized backup contains private data; retain it privately, do not publish or commit it.

## 4. Schedule model and editing

One slot is one explicit daily wall-clock time belonging to `health_medicines.id`. All active slots for a medication share a timezone; duplicate active times are rejected. New/replacement slots start today or later. End date is optional and inclusive. Inactive creation is supported.

Edits retire the original slot and insert a replacement, preserving historical time/zone/date context. Remove/deactivate sets inactive rather than deleting history. Inactive slots can be explicitly replaced; they are not silently restored. No automatic parsing of existing dose/frequency text or creation of governed medicine associations occurs.

## 5. Due Today behavior

Profile-scoped `/account/health-records/{profileId}/medicines/due-today` includes current medicines with active slots whose local date falls within the slot range and any medication start lower bound. Includes later times today; does not assess whether a dose is overdue. Deterministic ordering: local time, medicine label, slot ID.

GET derives entries without database writes. Unmarked is absence of a checkoff, never an inferred skipped/missed dose. Unscheduled, stopped, historical, inactive, future-start and expired entries are excluded. Existing medication list remains usable without a schedule.

## 6. Taken / Skipped and history

Server verifies the submitted occurrence date equals that slot's eligible local today. Only exact `taken` / `skipped` are accepted. Unique occurrence key plus serialized mutation prevents conflicting duplicate rows. A same-day correction updates state/actor/recorded timestamp while retaining first creation and original medication-label snapshot.

History displays recorded actions only, 20 per page, with preserved scheduled local date/time/zone, label and latest explicit actor/time. No clinical audit claim or retrospective backfill. Replacement/retirement/stop preserves history. Existing medication hard deletion cascades to its slots/checkoffs as approved; no archive subsystem.

## 7. Authorization and security

Controllers authenticate through existing AuthService, return private/no-store responses, use normal CSRF tokens and reject invalid tokens before mutations. Services derive actors with `AuthService::id()` and reuse `HealthMedicineService::getMedicine()` / `HealthProfileService::getProfile()` and `findAccessible`.

Mutation transactions lock and recheck the parent medicine, active profile and active membership; owner/editor may mutate, viewer reads only. Slot lookup also matches the medication ID. History joins through medicine/profile. Browser-supplied IDs/user IDs never grant access. State/time/date/timezone/boolean validation runs before SQL under existing non-strict mode. Errors are explicitly allowlisted; unexpected errors use safe messages and log exception class only. Output is escaped.

## 8. Date/time and medication lifecycle

The live form defaults to the established application timezone **Asia/Kolkata**, explicitly displayed for confirmation. Each slot stores its named timezone; DateTimeImmutable/DateTimeZone instances calculate local dates without changing global application or DB settings. New timestamps are explicit UTC; history converts to the preserved slot timezone. Existing medication calendar-date validation/messages remain unchanged.

One daily occurrence per slot/local date also applies during DST folds/gaps; this is a displayed wall-time entry, not an alarm instant. UTC midnight can differ from local today. New schedule dates do not replace medical start/stop dates.

Medicine status updates lock the parent and deactivate slots within the same transaction when stopped/historical. Checkoffs waiting on that lock re-evaluate fresh status. Returning to current does not restore slots. Existing medicine deletion uses the approved FK cascades. No unrelated lifecycle was changed.

## 9. Focused tests

| Test | Result |
| --- | --- |
| `tests/medication-schedules.php` | 52 database-free checks passed |
| `tests/medication-schedules-mariadb.php` | 36 isolated MariaDB checks passed |
| PHP syntax | All 15 changed/new PHP files passed |
| Diff review / `git diff --check` | Passed; existing unrelated changes retained |

Coverage includes create, multiple times, duplicate active time/timezone conflict, invalid time/date/zone/status, wrong profile/medicine/slot IDs, owner/editor/viewer/other-account/anonymous cases, current/stopped/historical/no-schedule/inactive eligibility, medication start lower bound, inclusive end/expiry, local midnight, repeated DST hour, Taken persistence, Skipped correction, label snapshot, edit retirement and historical time retention, remove, and controller CSRF rejection for all four mutation paths.

Isolated tests additionally exercise actual repository/service SQL, CHECK/FK rejection, binary state integrity, two concurrent real connections marking one occurrence, a checkoff blocked on a parent lock while stopping commits, real medication update deactivation, current restoration without schedules, and hard-delete cascades. All synthetic isolated fixtures were removed; no production fixture SQL was used.

## 10. Genuine browser validation

Authenticated Account 1 used its existing test health profile 2. One deliberately synthetic medication was created through normal UI: **ID 8, “Step45B temporary test — NOT REAL MEDICATION.”** Existing medication records were not edited. Autocomplete suggestions loaded; native Enter on the Paracetamol suggestion selected it without submitting the edit form. Existing add/edit/status/date form behavior passed.

At **1366×768**: navigation, empty Due Today/history, schedule form, invalid-timezone message with input retention, creation, two daily times (09:00 / 18:00 Asia/Kolkata), correct order, Taken, refresh persistence and Skipped correction passed. Due Today document scroll/client width both 1351: no horizontal overflow.

At **390×844**: medication list, schedule form, Due Today, action buttons, history, edit replacement, remove/deactivate and Account navigation passed. Scroll/client width both 375 for the checked list/form/Due Today/history pages: no horizontal overflow. Schedule changed 09:00 → 09:15; history retained 09:00. Removing 18:00 excluded it from Due Today. Stopping the temporary medicine through its normal edit form removed all entries while retaining history. Returning to current did not restore schedules. The no-medications empty state was checked on an existing accessible profile without creating/changing that profile.

Mobile navigation exposed the normal Logout control. Logout completed through normal UI with navigation awaited; subsequent direct Schedule, Due Today and History requests redirected to Login without rendering protected information. Browser left anonymous and viewport override reset. No session storage was inspected/manipulated.

Captured console errors: **none**. Screenshot evidence contains the synthetic test cards, not a real medication recommendation.

## 11. Existing regressions

| Existing suite | Result |
| --- | --- |
| Medication dates | 26 database-free checks passed |
| Health Profile | 57 database-free checks + 4 redirect scenarios passed |
| Authentication/account/session-related suite | 47 database-free scenarios passed |
| Medication autocomplete | 16 database-free checks passed; genuine native suggestion selection also verified |
| Dashboard | 76 in-memory checks passed |
| Dashboard guidance | 76 in-memory checks passed |

Dashboard code was not modified; its tests verify preservation, not new Dashboard functionality. Governance suites were not rerun because this feature does not depend on them.

## 12. Recovery, database verification and cleanup

Before normal DDL, captured a transaction-consistent full schema/data backup, read it back byte-for-byte and verified its hash. Recovered all tables, non-generated data columns, views and triggers into `kobi_medication_schedule_recovery_20261014`. Compared full schema/columns/data/view/trigger metadata, including trigger SQL-mode/charset/collation fidelity. Recovery reproduced the baseline. Normal KOBI was unchanged during rehearsal.

Recovery procedure: use the retained serialized checkpoint and the reconstruction sequence in `tests/medication-schedules-deployment.php` to restore into a separately named empty database, omit generated-column assignments, recreate views/triggers in captured contexts, then verify the normalized full fingerprint before any separately authorized normal recovery. Do not rerun deployment or overwrite an existing recovery database. No recovery/restoration writes were made to normal KOBI.

Then tested exact schedule DDL and real service SQL in the guarded isolated database. Only after recovery and isolated tests passed, the deployment gate checked backup/artifact/test certificates and the unchanged normal fingerprint, then executed the two CREATE TABLE statements. Post-deployment verification matched actual definitions and all existing data/schema, views/triggers and session/global SQL modes.

After explicit action-time permanent-deletion confirmation, the normal CSRF-protected medication UI deleted **only medication 8**, its three slots and two checkoffs. Browser history returned to the empty state. The final verifier, also repeated after logout, confirmed both new tables empty and all existing rows/objects intact. The normal UI consumed one medication ID; its expected monotonic AUTO_INCREMENT advance was retained, not reset by corrective DDL. No symptom-checker submission/counter increment was performed.

The guarded disposable recovery/test database was dropped after successful verification. The verified backup and certificates are retained privately. No temporary normal-KOBI test records remain.

## 13. Deferred functionality

45C mobile numbers, mobile verification, KOBI-to-KOBI sharing, notifications/reminders/queues/cron, weekly/interval/PRN/taper schedules, drug interactions, prescribing/clinical recommendations, automatic dose changes, adherence/risk scores, AI advice, broad API/native mobile architecture, and governance work remain deferred.

## 14. Limits and completion

V1 intentionally supports daily slots and current-day manual actions only. Same-day correction history shows latest state rather than a separate immutable action audit. Removal retires a slot; medication hard deletion removes its checkoffs as approved. No clinical correctness/adherence assessment was attempted. Full `structure.sql` import was not rerun; the exact additive artifact and actual deployed objects were verified.

Dashboard, authentication behavior, disease/Treatment functionality and publication, Medicine governance, mobile/account identity and sharing code were not modified. No unrelated data/schema changes, SQL-mode changes, Git commits/pushes/resets/checkouts/merges/rebases/index/history/branch mutations occurred.

**Step 45B PASS. Step 45C is ready for a separate authorization; it has not begun.**
