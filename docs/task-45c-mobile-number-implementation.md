# Step 45C — Private Account Mobile Number V1

Date: 2026-10-14. **Step 45C PASS.** Private add/update/clear is complete. Mobile verification, recipient lookup and medical sharing were not implemented.

## 1. Implementation summary

Added an optional mobile contact field inside the existing Account → Profile section. A separate server-rendered mobile form uses the existing AccountController → AuthService account-profile responsibility → UserRepository path. Mobile-only saves do not submit name/email or trigger email verification. Existing authentication methods and behavior were left intact; AuthService received only the scoped contact-update method approved in 45A.

## 2. Exact schema change

The approved 45A SQL is in `database/user-mobile-number.sql`; the equivalent definition is incorporated in the `users` CREATE TABLE in `database/structure.sql`:

```sql
ALTER TABLE users
    ADD COLUMN mobile_number varchar(16)
        CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
    ADD CONSTRAINT chk_user_mobile_number CHECK
        (mobile_number IS NULL OR
         mobile_number REGEXP '^[+][1-9][0-9]{1,14}$');
```

Exactly one nullable contact column and the already-approved format CHECK were deployed. No mobile index, uniqueness constraint, identity/verification/sharing table or unrelated schema change. Exact DDL was accepted without warnings by MariaDB **10.4.27** in the isolated database before normal deployment. Actual deployed users definition matched that isolated definition. Session/global SQL modes remain **NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION**.

## 3. Profile integration and files

Modified existing files:

- `app/Controllers/AccountController.php`: authenticated `updateMobileNumber()` with existing CSRF/session helper, safe errors, retained bounded input and normal success redirect.
- `app/Services/AuthService.php`: focused account-contact normalization/update; derive own ID through `id()`, require active account, verify stored result. No login/password/email/Google logic changed.
- `app/Repositories/UserRepository.php`: private user-by-ID projection includes mobile; parameterized own-account active-user update. Same-value saves succeed even if affected-row count is zero.
- `app/Views/account/index.php`: includes the scoped mobile form within Profile; existing profile form untouched.
- `routes/web.php`: only `POST /account/profile/mobile`; no user/profile ID parameter or lookup route.
- `database/structure.sql`: approved users column/CHECK.

Created:

- `app/Views/account/mobile-number.php`
- `database/user-mobile-number.sql`
- `tests/user-mobile-number.php`
- `tests/user-mobile-number-mariadb.php`
- `tests/user-mobile-number-deployment.php`
- This report.
- `docs/task-45c-final-login.png`: final anonymous browser state after logout/protected Profile request.
- Protected backup evidence under `tests/.mobile-number-recovery/`: `.htaccess`, `before.bin`, `verified.json`, `mariadb-passed.json`. The backup includes private data; retain privately, do not publish or commit.

Existing unrelated working-tree changes were preserved. No new dependencies, inline JavaScript or parallel profile system.

## 4. Validation and normalization

One representation: `+` followed by 2–15 ASCII digits, first digit nonzero, matching the exact approved `\A\+[1-9][0-9]{1,14}\z` shape. The string column preserves international prefixes/leading digits; no country code is inferred.

- Input must be a string, at most 64 raw bytes before trimming/normalization.
- Trim surrounding whitespace; empty becomes SQL NULL.
- Permit digits, `+`, ordinary spaces, hyphens and parentheses; remove the three presentation separators.
- Reject letters, extensions, other punctuation, ambiguous national/dialling-prefix-only input, misplaced/repeated `+`, excessive normalized length and non-ASCII digits.
- Display escaped canonical stored form. Error responses retain safe submitted text, bounded to 64 characters.

Format validation does not establish assignment, mobile capability or possession. Duplicate contact values are allowed; no account merging, identity reservation or role transfer occurs. Clear is the normal empty-input update, not a separate deletion endpoint.

## 5. Privacy, ownership and CSRF

Both controller and service require authentication/current active account. The service derives the actor using `AuthService::id()`; posted `user_id` / `profile_id` are ignored. Repository WHERE restricts to that actor and active status. Only the private authenticated Profile form displays the contact value; health-profile editors/viewers gain no account contact rights.

Every save uses `csrfField()` and server verification. Missing, invalid and array-shaped tokens were rejected without writes in focused controller tests. The genuine browser form contained a real token and its normal add/update/clear requests succeeded. Invalid-token browser/session manipulation was not performed; negative CSRF coverage is in the controller test harness.

Known validation messages are explicitly allowlisted; PDO/internal errors become a generic message and log exception class only, without number or SQL details. Escaping of retained malicious input was tested.

No public GET/JSON/mobile search endpoint was added. Existing public summary projection explicitly excludes the injected contact field in a test. Source/reference checks found mobile use only in the scoped account code and persistence. Browser checks of homepage, Migraine detail, public Medicines and Symptom Checker while the synthetic number was stored found no mobile field/number disclosure. No Symptom Checker submission was made.

## 6. Verification boundary

**Stored contact number ≠ verified mobile identity ≠ sharing eligibility.** UI explicitly states no mobile verification is performed and stored numbers cannot be used for sharing. No verification status column, timestamp, flag, OTP/token/provider or eligibility rule was added. No claim of verified identity is made.

## 7. Future sharing boundary

Later verification/account binding can establish a candidate recipient identifier under a separately approved design. That future flow still needs authenticated recipient consent, explicit sender selection/authorization, recipient user-ID binding, expiry/revocation and controlled data projection. The current contact field grants no access and does not resolve recipients. Changes to a future verified number would require renewed proof; accepted shares should remain tied to account IDs, not follow phone-number reassignment.

Existing bearer-link summary sharing and email-based whole-profile invitations remain unchanged. Their authorization boundaries were not repurposed for phone contact storage.

## 8. Tests and regression results

| Suite/check | Result |
| --- | --- |
| `tests/user-mobile-number.php` | 61 database-free checks passed |
| `tests/user-mobile-number-mariadb.php` | 17 isolated MariaDB checks passed |
| Authentication/account suite | 47 database-free scenarios passed |
| Health Profile | 57 database-free checks + 4 redirect scenarios passed |
| Medication dates | 26 database-free checks passed |
| Medication schedules | 52 database-free checks passed |
| Medication autocomplete | 16 database-free checks passed |
| Dashboard | 76 in-memory checks passed |
| Dashboard guidance | 76 in-memory checks passed |
| PHP syntax | All 9 changed/new PHP files passed |
| Diff/source-reference review | Passed; `git diff --check` passed |

Focused coverage: NULL/whitespace, normalized international input, min/max representation boundaries, repeated save, update/clear, malformed prefix/characters/length/type, no writes on validation failure, duplicate numbers, active/anonymous/inactive account access, posted-ID tampering, valid/missing/invalid CSRF, safe internal errors, escaped input retention, public snapshot exclusion and absence of a lookup route.

Isolated tests used actual UserRepository/AuthService SQL, exact metadata/nullability/collation, no mobile index, duplicate values across two accounts, CHECK rejection, same-value saves and full rollback of all fixture edits. No existing tests were changed to make them pass. No unrelated governance suites were run.

## 9. Genuine browser validation

Existing Account 1 authenticated through the normal login flow. At **1366×768**, Account → Profile showed empty mobile state, existing name/email and navigation. Normal form saved a synthetic formatted international value, reload retained a populated field, and success feedback appeared. At **390×844**, updating with alternate formatting, invalid-input message, retained form input, field/buttons/help and Account navigation passed. No horizontal overflow: observed scroll/client widths matched (1351 desktop; 375/390 mobile depending on scrollbar presence). Captured console errors: **none**.

The browser tool masks contact values, so direct automated DOM value equality could not verify the canonical phone text. A separately guarded read-only normal-DB equality assertion confirmed the genuine browser update persisted the exact expected synthetic normalized number without printing it. Isolated SQL roundtrip tests also verified normalization. This is a tooling observation limit, not a failed save.

The temporary value was cleared by submitting the normal mobile form with empty input. Reload showed “No mobile number stored.” Name/email were not submitted by these actions. Final database verification proved original name/email/account identity data unchanged. No real mobile number or permanent test account was created.

Normal UI logout succeeded. A fresh direct Account/Profile request redirected to Login; no protected contact information was rendered anonymously. Browser left anonymous; viewport override reset. No session storage was inspected or manipulated.

## 10. Database recovery, preservation and cleanup

Captured a transaction-consistent full pre-deployment backup, verified complete write/readback and hash, and reconstructed tables/non-generated rows/views/triggers in guarded `kobi_mobile_number_recovery_20261014`. Full comparison included trigger SQL-mode/charset/collation metadata. Only after recovery fidelity passed, executed the exact approved ALTER in the isolated database and ran storage/integrity tests there. All fixture edits rolled back.

Normal deployment gate verified backup/artifact/test certificates and unchanged normal fingerprint, then executed only the approved ALTER. Actual full users definition matched the isolated expected definition, including all pre-existing columns/indexes/constraints. Other tables, data, views and triggers matched the baseline.

Final verification after UI clear/logout: every normal mobile value is NULL; all existing data is unchanged except Account 1's expected `updated_at` from genuine contact edits. That timestamp was not manually reset. Existing authentication/profile data, health records, Dashboard data, both Step 45B tables and all unrelated schema objects remained intact. No manual normal-database data writes were performed. SQL modes unchanged.

The disposable recovery/test database was removed after successful final verification. Verified private backup/certificates retained. Recovery procedure is the reconstruction sequence in the deployment helper: restore the checkpoint into a separately named empty database, omit generated-column assignments, recreate views/triggers in their captured contexts, then verify full fidelity before any separately authorized normal recovery. Do not overwrite/restart existing recovery evidence or redeploy this ALTER.

## 11. Deferred scope and completion

SMS/OTP, mobile verification, recipient lookup, account discovery/public phone search, KOBI-to-KOBI sharing, notifications/reminders, API/native mobile support and generic contact management remain deferred. No clinical functionality or phone-based login/linking was introduced.

Dashboard, authentication behavior, medication/schedules, Health Profile, Disease/Treatment, Medicine governance and existing sharing functionality were preserved. No SQL-mode changes, unrelated schema/data changes, Git commit/push/reset/checkout/merge/rebase/index/history/branch mutations occurred.

**Step 45C PASS. Stop here; no future sharing implementation has begun.**
