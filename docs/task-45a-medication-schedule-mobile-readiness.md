# Step 45A — Medication Schedules / Due Today + Mobile Identity Readiness

Date: 2026-10-14. Status: **READY for scoped implementation planning.** This is a source/schema review, not a deployment or browser acceptance test. All SQL below is proposed only.

## 1. Executive summary

Implement **45B — Medication Schedules / Due Today**, then **45C — private Mobile Number add/update**. Neither depends on a new authentication system, Medicine governance, notifications, or internal sharing.

- Reuse `health_medicines` and its health-profile access boundary. Recommend two additive tables: daily schedule slots and explicit Taken/Skipped records.
- Reuse Account → Profile for one nullable, non-unique `users.mobile_number` column. Stored numbers remain unverified and unavailable as sharing identifiers.
- Extend existing medical-summary sharing patterns in a later task. Existing bearer-link snapshots and whole-profile membership invitations are different access mechanisms; neither is already a mobile-addressed private share.
- No blocking architectural conflict was found. Proposed DDL compatibility, isolated integrity tests, deployment backup/recovery, and real browser acceptance remain implementation work. READY does not authorize deployment.

## 2. Existing Personal Medication architecture

### Persistence and access

`database/structure.sql` defines `health_medicines`: signed `int(11)` primary key `id`, `health_profile_id` FK to `health_profiles` with `ON DELETE CASCADE`, required `brand_name`, optional `generic_name`, `strength`, `dose`, `frequency`, `route`, `prescribed_by`, `notes`, dates `started_on` / `stopped_on`, timestamps, and indexes on profile and status. Status is `current`, `stopped`, or `historical`. There is no schedule/timezone/checkoff field or daily occurrence table in this personal-medication path.

The inspected path uses repository arrays, not a medication entity/model class:

`routes/web.php` → `AccountController` → `HealthMedicineService` → `HealthMedicineRepository`.

`HealthProfileService::getProfile()` delegates to `HealthProfileRepository::findAccessible(profileId, userId)`. This requires an active profile and active membership. Owner/editor can mutate medication records; viewer can read. The service verifies that a fetched medicine belongs to the requested accessible profile. New schedule/history access must use the same chain, with `AuthService::id()` as the authenticated actor.

Existing routes under `/account/health-records/{profileId}/medicines` provide list, create, store, edit, update, and delete. Controllers authenticate, validate CSRF for mutations, call services, render through `$this->view()`, and redirect through the global helper. Repository deletion is a hard delete, not a soft-delete lifecycle.

### Validation and substance relationship

`HealthMedicineService` requires a brand name, validates the three statuses, normalizes optional text, and strictly parses calendar dates with `!Y-m-d`. Today/past dates are accepted; future medical dates are rejected. Current medicine cannot have a stopped date; stopped medicine requires one; stop cannot precede start. Preserve these rules and messages.

`dose` and `frequency` are free text, not executable instructions. Do not infer a schedule from them. `public/assets/js/health-medicine.js` handles status/date visibility and keyboard autocomplete using `/api/substances/search`. `SubstanceRepository` returns active/searchable registry name suggestions. Normal create/update persists the selected text in `generic_name`, not a governed medicine identifier.

The schema also contains `health_medicine_identity_links` and `health_medicine_selected_ingredients`, linked to governed identity/presentation/chemical reference tables. They are not a prerequisite of ordinary personal medication CRUD. Attach schedules to `health_medicines.id`; do not create or alter governed associations or require autocomplete selection.

### UI and Dashboard reuse

`account/health-medicines.php` groups current/stopped/historical records, handles empty state, escapes displayed text, and shows edit/delete controls to owner/editor. Create/edit views retain submitted values, show errors, include CSRF, and use Bootstrap forms with wrapping controls. Keep their architecture and existing autocomplete. Existing delete confirmation must eventually explain the deletion of schedule/checkoff history as well.

`HealthDashboardService` uses accessible-profile selection and `MedicalSummaryService` for counts, including current medications; it already links to selected-profile Personal Medications. No Due Today calculation exists. V1 can be reached through that existing destination without redesigning Dashboard or adding cross-profile aggregation.

## 3. Medication Schedule V1 recommendation

### Representation

One schedule slot belongs to one existing personal medication. A medication may have multiple explicitly entered times daily. V1 frequency is **daily only**, implicit in the slot model: one occurrence per eligible local calendar day per slot. Weekly, interval, PRN, tapering, and prescription interpretation are deferred. Unscheduled medications remain fully usable and appear with a neutral “No schedule” action; never auto-schedule them.

Each slot stores local `HH:MM`, a named timezone, inclusive `starts_on`, optional inclusive `ends_on`, and active/inactive state. These dates describe organizational scheduling, not prescribing. Keep medication dates intact: existing `started_on` can impose an additional lower bound, but cannot represent a future planned start. A separate schedule end date can be future; do not write it to `stopped_on`.

### Due Today and checkoffs

For each slot, compute today's date using `DateTimeImmutable` with its explicit `DateTimeZone`. Include it only when:

1. The authenticated actor currently has profile access.
2. Medicine status is `current` and the slot is active.
3. Local date is at/after slot start and any medication start date.
4. Local date is at/before a non-null slot end date.

Due Today means all scheduled entries for that local day, including later times, with clear time/zone labels. It is not an overdue-dose assessment. Sort consistently by displayed local time, medicine label, and slot ID. Do not combine unrelated profiles. GET performs no inserts; generate unmarked entries in memory and join any existing checkoff by `(schedule_id, scheduled_on)`.

Owner/editor can mark today's eligible occurrence Taken or Skipped. Persist only an explicit action; absence of a record means unmarked, never missed or skipped. Accept only those two exact values. Derive eligibility/date on the server; reject arbitrary past/future dates, inactive/stopped medicine, mismatched profile/medicine/slot IDs, and revoked access. A same-day correction may replace Taken with Skipped or vice versa; this is a simple current-state history, not an immutable clinical audit trail. Do not allow retrospective backfilling in V1.

Use a unique occurrence key and a transaction to serialize concurrent marking/correction. Schedule mutation and the medication status transition must coordinate through the parent medication row so a stop racing a checkoff cannot create a new eligible action after discontinuation.

### History, timezone, and lifecycle

Show a paginated recent history of recorded Taken/Skipped actions for the selected profile; no unrecorded historical “missed dose” rows. Capture the medicine label when recording so later name edits do not relabel old actions. Retained slot rows preserve the time/zone context. Show the acting account only within authorized private views.

Bootstrap currently sets the PHP default timezone from `APP_TIMEZONE`, with configuration fallback `UTC`. This review did not read deployment credentials or verify the effective environment value. Explicitly confirm the schedule timezone in the form; never infer it from a phone number, browser location, or server timezone. Validate against PHP's supported timezone identifiers and use per-instance timezone objects, without changing global PHP/DB timezone behavior. Write new-table action timestamps explicitly in UTC and convert for display.

Each daily slot is keyed by local date, including daylight-saving clock changes: one displayed wall-clock entry and at most one checkoff per local day. V1 does not calculate an alarm instant, run twice during a repeated hour, or invent a skipped action for a nonexistent clock time. Timezone changes require an explicit replacement slot and affect future presentation, not previous checkoff dates.

- Disable a slot to remove it from Due Today and keep recorded history.
- For time/zone/date edits, retire the old slot and create a new slot effective today or later. Do not mutate historical slot context. Reject duplicate active wall times for the same medication; use one timezone across its active slots.
- When medicine becomes stopped/historical, deactivate its slots in the same operation. History remains accessible under the existing profile permissions. Moving back to current does not silently restore old schedules; the user explicitly sets up new slots.
- Deleting a medication cascades to its slots and checkoffs. Profile deletion likewise follows existing cascades. This preserves existing hard-delete behavior; tell the user before deletion. Do not introduce an archive subsystem.

### Placement

Account → Health Records → selected profile → Personal Medications → Schedule / Due Today / History. Add small schedule actions to current medicine cards, a profile-scoped Due Today link, and a simple history destination. Viewer sees read-only entries/history; owner/editor sees action forms. Use server-rendered Bootstrap cards, visible labels, `csrfField()`, escaped output, error alerts and retained form input. At 390px controls should wrap; validate at 390×844 and desktop, with tablet checks where useful. New script behavior, if necessary, belongs in existing page asset conventions, not inline scripts or a new frontend framework.

## 4. Mobile Number recommendation

`users` currently has name/email/password/security timestamps and no phone/mobile column. Phone fields found in `health_insurance_contacts` belong to insurance contacts, not the authenticated account; do not reuse them. Health-profile memberships and `user_auth_identities` are also not account contact-number storage.

Recommend optional `users.mobile_number VARCHAR(16)` using ASCII/binary collation, default NULL. Store canonical `+` plus country-code/national digits, preserving numbers as strings. International E.164 numbers allow up to 15 digits; the extra character holds `+`. See [ITU-T E.164](https://www.itu.int/ITU-T/worksem/ip-telecoms/e164/e164.pdf), Annex A. This limits storage/format only; it does not establish assignment, mobile capability, or ownership.

Normalization: trim; permit/remove only presentation spaces, hyphens and parentheses; reject other characters, letters and extensions; require explicit international `+` prefix. Validate normalized shape with `\A\+[1-9][0-9]{1,14}\z`. Reject ambiguous national input rather than guessing a country code. Cap raw input length before normalization. Display the escaped canonical number with country-code guidance and “Not verified — unavailable for sharing.” Empty input becomes NULL, permitting deliberate removal.

**Do not add a global unique index for unverified profile contact numbers.** Shared/family contact numbers may be legitimate, and an unverified claim must not reserve an identity. No lookup index is needed for own-account reads by primary key. Duplicate stored numbers do not merge accounts, link Google identities, grant access, change login, or transfer ownership.

Reuse Account → Profile's form/error/CSRF patterns. A small separate mobile form/action avoids coupling a mobile-only update to email verification. Extend the existing account profile service responsibility (currently in `AuthService`) with a focused authenticated mobile-update method and `UserRepository` parameterized update; leave existing name/email update and authentication flows intact. No posted user ID: derive it with `AuthService::id()`. The owner alone can see/update this account contact field; profile editors/viewers are not account owners. Exclude it from public views, summary snapshots, and general recipient-search responses.

Initial 45C provides **stored**, not **verified and eligible for sharing**. Later verification needs a separate proof workflow and account-to-number binding, with uniqueness for an active verified recipient identifier if routing is to resolve exactly one account. That binding may coexist with duplicate unverified contact values. Do not reuse Google identity records or email-only tokens as proof of mobile possession.

Changing/removing a future verified number must invalidate its lookup eligibility until fresh verification. Previously accepted shares remain bound to recipient user ID, never migrate to a new holder of the number. Number recycling/conflicting verified claims need explicit resolution in that later task; no automatic account merge or grant. No verification columns/table/workflow are needed in 45C merely to label all numbers unverified.

## 5. Existing sharing functionality

There are two existing mechanisms:

| Mechanism | Current behavior | Reusable boundary |
| --- | --- | --- |
| Medical-summary share | Owner creates an expiring public bearer link to a frozen JSON snapshot; recipient need not be a KOBI user | Snapshot projection, token hashing, owner checks, expiry, revocation, access history |
| Health-profile invitation | Owner invites an email to editor/viewer membership; authenticated acceptance checks the account email matches the invitation and creates membership | Explicit acceptance, current profile authorization, membership management patterns |

`MedicalSummaryShareService` / repository use `medical_summary_shares` plus `medical_summary_share_access_logs`. A raw generated token is returned for the link; only its SHA-256 hash is stored. Allowed expiry is 1–30 days. Revocation is owner-only. `getPublicShare()` checks token/status/expiry, reads the stored snapshot, then writes an access log. This review did not open that route or invoke the reader.

The public snapshot allowlist includes profile name/DOB/age/gender, active/historical conditions, current medication details, allergies, and procedures. It includes selected notes and related descriptive fields but excludes internal memberships/identifiers. It is an existing summary projection, not a per-field recipient-selection UI, schedule history share, live profile grant, or document attachment share. The inspected sharing path generates a URL and renders a summary view; it does not establish an authenticated recipient binding.

`HealthProfileInvitationService` / repository use hashed tokens, email, role, status and expiry; acceptance checks matching authenticated email and adds membership. That matching check is not proof of mobile verification. Active editor/viewer membership exposes the accessible profile through existing services, which is broader than selected information sharing. Do not silently grant membership as a shortcut for internal snapshot sharing.

## 6. Future KOBI-to-KOBI sharing architecture

Extend the medical-summary sharing subsystem with an explicitly private recipient-bound share type and route, while preserving existing public-link behavior. Reuse projection/expiry/revocation patterns, not the anonymous bearer authorization rule.

Recommended flow: authorized profile owner selects exact records/fields → previews the frozen selected snapshot → enters a canonical number → requests contact with a verified, opted-in account without receiving a lookup directory result → recipient authenticates and accepts the request → sender confirms the consented recipient identity and exact data → activate an expiring share bound to both account IDs → authenticated recipient can read until expiry/revocation.

Do not disclose medical content in the preliminary request. Do not publicly return whether a number has an account, a name, email, profile, or verification status. A minimal recipient identity can be shown only after recipient consent/opt-in, for sender confirmation. No need to echo any stored number beyond the sender's own submitted value. Use generic submission responses for unknown, unverified, non-consenting and eligible recipients; rate limits supplement, rather than replace, non-enumerating responses. Unknown numbers must not cause automatic account creation, SMS disclosure, public-link fallback, or a share with an unbound recipient.

The eventual accepted share must contain an exact allowlist of selected information and immutable snapshot, not unrestricted profile/document access or arbitrary repository rows. Read checks require the bound recipient's authenticated user ID, accepted state, current expiry/revocation, and the chosen owner-access validity policy. If sender profile ownership/access is lost or the profile is deactivated/deleted, deny further access. Check on every request; possession of a token alone is insufficient. Sender can revoke; recipient can decline. Record sender/recipient IDs, selection, acceptance, expiry/revocation and authenticated access events without raw tokens, unnecessary phone values or health content in logs.

Future private/public share distinction, verified-number binding, acceptance state and actor-aware audit storage need a separately scoped design before implementation. No sharing migration is proposed for 45B/45C; existing access-log fields alone cannot prove a recipient-account read. Do not rewrite current public shares or invitations now.

## 7. Schema impact — exact proposal, not executed

| Feature | Classification | Proposed objects |
| --- | --- | --- |
| Schedule / Due Today / history | Multiple schema changes | Two new tables; no replacement medication table or existing medication columns |
| Private mobile add/update | One additive change | One nullable users column plus a format CHECK; no new table or lookup/unique index |
| Existing Dashboard/sharing/auth | No schema change in 45B/45C | Future verification/private-sharing design deferred |

Foreign-key types below match the signed `int(11)` parent IDs in `structure.sql`. New UTC timestamps are supplied explicitly by services; SQL mode and session/server timezone must not be changed. CHECKs are backstops; application validation remains mandatory under permissive MariaDB modes. Exact DDL must be tested on the target MariaDB version in a disposable database before separately authorized deployment.

```sql
CREATE TABLE health_medicine_schedule_slots (
    id int(11) NOT NULL AUTO_INCREMENT,
    health_medicine_id int(11) NOT NULL,
    local_time char(5) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    timezone varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    starts_on date NOT NULL,
    ends_on date DEFAULT NULL,
    is_active tinyint(1) NOT NULL DEFAULT 1,
    created_by int(11) DEFAULT NULL,
    created_at datetime NOT NULL,
    updated_at datetime NOT NULL,
    PRIMARY KEY (id),
    KEY idx_medicine_schedule_active (health_medicine_id, is_active),
    KEY idx_medicine_schedule_creator (created_by),
    CONSTRAINT fk_medicine_schedule_record FOREIGN KEY (health_medicine_id)
        REFERENCES health_medicines (id) ON DELETE CASCADE,
    CONSTRAINT fk_medicine_schedule_creator FOREIGN KEY (created_by)
        REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT chk_medicine_schedule_time CHECK
        (local_time REGEXP '^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$'),
    CONSTRAINT chk_medicine_schedule_timezone CHECK (CHAR_LENGTH(timezone) > 0),
    CONSTRAINT chk_medicine_schedule_active CHECK (is_active IN (0, 1)),
    CONSTRAINT chk_medicine_schedule_dates CHECK
        (ends_on IS NULL OR ends_on >= starts_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE health_medicine_schedule_checkoffs (
    id int(11) NOT NULL AUTO_INCREMENT,
    schedule_id int(11) NOT NULL,
    scheduled_on date NOT NULL,
    state varchar(7) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    medicine_label varchar(255) NOT NULL,
    recorded_by int(11) DEFAULT NULL,
    recorded_at datetime NOT NULL,
    created_at datetime NOT NULL,
    updated_at datetime NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_medicine_checkoff_occurrence (schedule_id, scheduled_on),
    KEY idx_medicine_checkoff_actor (recorded_by),
    CONSTRAINT fk_medicine_checkoff_schedule FOREIGN KEY (schedule_id)
        REFERENCES health_medicine_schedule_slots (id) ON DELETE CASCADE,
    CONSTRAINT fk_medicine_checkoff_actor FOREIGN KEY (recorded_by)
        REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT chk_medicine_checkoff_state CHECK
        (BINARY state IN (BINARY 'taken', BINARY 'skipped'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE users
    ADD COLUMN mobile_number varchar(16)
        CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
    ADD CONSTRAINT chk_user_mobile_number CHECK
        (mobile_number IS NULL OR
         mobile_number REGEXP '^[+][1-9][0-9]{1,14}$');
```

No redundant profile/user ownership column: resolve checkoff → slot → medicine → accessible profile on every request. Nullable actor/creator references preserve health-profile history if a user is removed; they do not grant ownership. `recorded_by` / `recorded_at` describe the latest explicit same-day mark/correction; `created_at` is its first creation. The original medicine label remains fixed for that occurrence.

The occurrence unique index also supports slot history queries. Profile-scoped joins use the existing medicine profile index and slot medicine index; no speculative global feed index. Active-time duplicate prevention and common active-slot timezone require service validation under a parent-row transaction; no misleading unique key preventing valid retired replacements. CHECKs cannot validate IANA timezone existence, current profile access, local today eligibility, or medication lifecycle. No trigger, recurring event, queue or scheduler is needed.

## 8. Concrete security/privacy requirements

- Authenticate controllers and services, derive actor from `AuthService::id()`, and recheck `findAccessible` rather than trusting posted profile/medicine/user IDs. Reading requires active membership; schedule/checkoff mutation requires owner/editor. Viewer and inaccessible-profile attempts must fail with safe application messages.
- Verify all three IDs belong together for schedule/history access. Recheck authorization during mutations and use existing repository/transaction conventions, parameterized queries and CSRF for every state change. GET must remain read-only.
- Reject malformed time/date/zone/state/boolean input before SQL, including truncation/coercion opportunities; validate exact normalized values and bounds. Do not rely on non-strict SQL mode. Preserve current medicine validation.
- History follows profile permissions and revocation, not merely the creator UID. Deactivation/stop removes future eligibility; medication/profile deletion cascades only related schedule data with disclosed consequences.
- Mobile read/update is own-account only, authenticated and CSRF-protected. Keep it out of health-profile sharing and public projections. No global duplicate-error lookup, login-by-mobile, provider linking, or account merging.
- Future sharing requires verified routing identity, recipient consent/authentication, explicit sender selection, bound recipient ID, expiry/revocation checks and minimal actor-aware history. Contact storage alone grants no sharing authority.

## 9. Implementation sequence and validation

### 45B — Medication Schedule / Due Today

Add only the proposed two-table persistence, focused schedule repository/service with existing DI, authenticated profile-scoped controller actions, daily slot form, Due Today Taken/Skipped controls and simple history. Reuse `HealthMedicineService` access/status validation and make only the lifecycle integration required to deactivate slots on stopping and explain hard deletion. Keep existing substance autocomplete, medication persistence fields, Dashboard counts/guidance and sharing projections unchanged.

Acceptance: database-free validation/calendar/timezone tests; isolated MariaDB FK/CHECK/duplicate occurrence/concurrent action tests; owner/editor success, viewer/no-access/ID mismatch/CSRF failures; multiple times, no schedule, inclusive dates, future planned start, stop/reactivation/delete, slot replacement, midnight/DST date identity, safe corrections and history. Rerun existing medication date/autocomplete and relevant profile/Dashboard regressions. Real authenticated desktop/mobile checks and deployment require separately authorized schema rollout with verified backup/recovery; no test writes to normal KOBI without authorization.

### 45C — private Mobile Number

Add only nullable contact storage, focused own-account update and Account Profile form with normalization, safe error retention, add/update/clear and explicit unverified label. No phone login, verification provider, recipient lookup, sharing, or account identity merging. Test accepted formatting, invalid/country-code-ambiguous/overlong inputs, NULL, duplicates, unauthorized/CSRF attempts, escaped private display, and unchanged email verification/password/Google behavior. Test the additive migration separately before deployment authorization.

### Readiness checks completed

- Source/schema tracing and targeted searches across services/repositories, routes, Bootstrap views and page JS; no application medication model or existing daily schedule implementation found in that path.
- PHP `-l`: all **17** selected controller/service/repository/view/route files passed.
- Existing `tests/health-medicine-dates.php`: **26 database-free checks passed**. Its repository/profile test doubles use memory; it does not connect to normal KOBI or create live records.
- `git diff --check` passed before report creation; repeated at final review. Existing unrelated working-tree changes were left intact.
- No browser automation, login/logout, share route access, live DB metadata/data query, schema import, DB writes, test records, or SQL-mode change. No runtime/browser acceptance of unimplemented features is claimed. Effective deployed timezone and exact proposed DDL compatibility remain to be checked during implementation.

### Inspected files

- `database/structure.sql`
- `routes/web.php`; `bootstrap/app.php`; `app/Core/Container.php`; `app/Config/Config.php`
- `app/Controllers/AccountController.php`
- `app/Services/HealthMedicineService.php`; `app/Repositories/HealthMedicineRepository.php`
- `app/Services/HealthProfileService.php`; `app/Repositories/HealthProfileRepository.php`
- `app/Services/AuthService.php`; `app/Repositories/UserRepository.php`; `app/Services/UserAuthIdentityService.php`
- `app/Services/MedicalSummaryService.php`; `app/Services/MedicalSummaryShareService.php`; `app/Repositories/MedicalSummaryShareRepository.php`
- `app/Services/HealthProfileInvitationService.php`
- `app/Services/HealthDashboardService.php`; `app/Repositories/SubstanceRepository.php`
- `app/Views/account/index.php`; `health-medicines.php`; `health-medicine-create.php`; `health-medicine-edit.php`; `health-dashboard.php` (last four under `app/Views/account/`)
- `public/assets/js/health-medicine.js`; `tests/health-medicine-dates.php`

Only this report was created by Step 45A. No existing file was modified. No application behavior, data/schema, SQL mode, authentication state, Treatment/Medicine governance, or Git index/history/branch state was changed.

## 10. Deferred scope

Notifications, medication reminders/background jobs, weekly/interval/PRN/taper schedules, drug-interaction engine, clinical/dose recommendations, prescription interpretation, automatic dose changes, AI advice, mobile proof/verification providers, mobile-based login, KOBI-to-KOBI sharing, public mobile lookup, whole-profile membership redesign, broad API/native mobile work, Dashboard redesign, and governance changes are outside 45B/45C.

**Step 45A READY. Stop here; do not begin 45B or 45C automatically.**
