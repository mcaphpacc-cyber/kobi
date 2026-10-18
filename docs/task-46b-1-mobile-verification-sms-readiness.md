# Step 46B-1 — Mobile Verification Policy & SMS Provider Readiness

Date: 2026-10-14 (Asia/Calcutta). **READY WITH PREREQUISITE for Step 46B.** This report supplies concrete implementation recommendations; they are not deployed policy or authorization to contact a provider. Approval of these choices and production onboarding remain necessary. Only this document was created.

## 1. Executive summary

Recommend **Exotel direct SMS API** for an India-first V1, with MSG91 direct SMS as an optional later fallback. KOBI generates/checks the code and owns the challenge/rate-limit lifecycle; avoid simultaneously using a provider-managed OTP lifecycle. Use **two dedicated tables**: ephemeral verification challenges and current unique verified bindings. Existing email/reset tokens stay separate.

Recommend six-digit numeric codes, five-minute expiry, five failed attempts per challenge, 60-second resend spacing, persistent request/guess budgets, 15-minute failure cooldown and 90-day verified-proof validity. Provide a test transport only inside a separate disposable-database web fixture; simulated receipt can never establish a normal-KOBI verified binding. Production is fail-closed until real SMS configuration/onboarding is complete.

## 2. Current blocker

46B stopped because no SMS integration, approved test transport, proof-validity/conflict policy or challenge storage existed. The binding DDL in 46A cannot hold OTP/attempt/resend state. The current mail log driver proves nothing about phone possession and is inappropriate for production OTP exposure.

Reviewed the completed 45C report, 46A design and stopped 46B report. This task resolves design recommendations; it does not falsely turn the stopped implementation into PASS.

## 3. Existing KOBI security and test infrastructure

- AuthService derives account ID from existing authenticated session; AccountController checks authentication and existing CSRF. Mobile updates are own-account only.
- Step 45C accepts a bounded string, trims it, removes spaces/hyphens/parentheses and stores canonical `+` plus 2–15 ASCII digits with a nonzero first digit. Empty is NULL. Contact values are deliberately non-unique. Reuse this implementation through a small shared normalization helper in 46B, preserving behavior/messages.
- UserAuthTokenService generates 32 random bytes and hashes long email/reset tokens; repository conditionally consumes unused/unexpired records. Existing token-purpose ENUM supports email verification/password reset only. Its 60-second email resend query is a reusable pattern, not a complete OTP abuse control.
- BaseRepository provides PDO prepared queries and transactions; existing isolated tests guard their selected database, verify backup recovery and roll back fixtures. Unit tests use memory repositories/session doubles and in-memory logging; no broad external-service framework exists.
- MailService supports SMTP and development email logging. No SMS/notification implementation was found. Google client and PHPMailer are direct Composer dependencies; Guzzle already exists transitively in composer.lock and can supply a narrowly scoped HTTP adapter after confirming the installed version. Config reads environment values; do not reuse Google/mail credentials as SMS secrets.

## 4. Concrete OTP policy

| Item | Recommended V1 |
| --- | --- |
| Format | Six numeric digits, including possible leading zeros; treat input as a string |
| Randomness | `sprintf('%06d', random_int(0,999999))`; separate 16-byte random challenge nonce; fail closed on RNG failure |
| Lifetime | Five minutes from committed challenge creation, not extended by retries/provider delays |
| Success | Atomically consume challenge and create/refresh verified binding; replay fails |
| Replacement | A newly admitted request invalidates all previous open challenges for this account; no two codes remain usable |
| Incorrect guesses | Five failed attempts per challenge; fifth invalidates it and starts 15-minute account cooldown |
| Resend | At least 60 seconds; at most three admitted requests per rolling 15 minutes and ten per rolling 24 hours, per account and canonical number |
| Aggregate guesses | At most 20 failed guesses per rolling 24 hours per account and canonical number; enforced across replacement challenges |
| Verification validity | 90 days from successful proof; require fresh OTP after expiry; no permanent verified claim |

These durations are product/security recommendations, not claims of an external mandated standard. PHP documents the secure RNG and HMAC/comparison primitives used by the proposed implementation: [random_int](https://www.php.net/manual/en/function.random-int.php), [hash_hmac](https://www.php.net/manual/en/function.hash-hmac.php), [hash_equals](https://www.php.net/manual/en/function.hash-equals.php).

## 5. Challenge storage and secure representation

One row per admitted request, including failed/unknown deliveries, retains request counters across sessions and resends. Explicit fixed purpose `mobile_verification` prevents reuse for phone login, password reset or sharing. No generic token platform or change to user_auth_tokens.

Store binary HMAC-SHA-256 of a length-delimited context containing purpose, account ID, canonical number, random nonce and six-digit code. Use an independently generated 32-byte secret outside the DB; compare with hash_equals. Plain SHA-256 of a six-digit code can be exhaustively guessed from a DB copy. Password hashing is possible but unnecessary here if a separate HMAC key and strict online budgets are enforced; neither method makes a compromised application safe.

Use a different HMAC key for canonical-number/IP rate digests, with separate purpose tags. Never store raw IP. Full destination and OTP MAC exist only while the challenge is usable; clear both on consumption/invalidation/expiry, retaining digests/counters temporarily. No plaintext OTP is stored in DB. The SMS adapter receives the code in memory only for delivery. A masked UI shows last four digits; full value remains editable only through the existing owner contact form.

## 6. Proof-of-possession policy

Correct unexpired, unused OTP delivered through the configured real SMS channel to the authenticated user's current stored number is evidence of control **at that moment**. Provider acceptance/delivery status alone is not proof. Test messages are never real-world proof.

This does not prove legal SIM ownership, identity, exclusive ongoing control, absence of SIM swap or future number reassignment. It does not merge accounts, change login identity/Google linking, grant authority or authorize medical sharing. Require account active, exact current contact match and all challenge/budget checks before binding.

## 7. Verified binding and lifecycle

Before proof: stored contact only. After proof: one row keyed by user ID, globally unique canonical mobile, verified_at and valid_until; eligible only while unexpired, account active and contact still matches. Preserve 46A request opt-in column default **0**, without implementing a sharing/opt-in workflow now.

- Verified A → canonical B: update contact, delete A binding and invalidate/purge open challenges atomically; B is unverified.
- Formatting-only save of canonical A: preserve current binding; do not refresh its validity.
- Clear: NULL contact, delete binding, invalidate challenges in one transaction.
- B → A: requires new OTP, never historical automatic restoration.
- Reverify current A: refresh proof after successful new OTP; do not reset attempt/request budgets.
- Active binding held by another account: do not displace it, even after a correct code. Consume/invalidate the completed challenge and show generic failure without identity details.
- Expired binding or inactive holder: permit reassignment **only after fresh successful SMS proof**, locking/rechecking old binding and holder. Delete stale binding and insert the new one atomically; never transfer account data, roles or future accepted shares. A still-active unexpired holder must explicitly release or reach expiry. No manual self-claim/admin override is introduced.

90-day reassignment delay is a tradeoff, not a guarantee against recycling. Future sharing remains account-ID bound and requires its own consent/access checks. Supported account-deactivation handling must invalidate bindings/challenges; all eligibility reads independently deny inactive/mismatched/expired records even before cleanup.

## 8. Races and transaction behavior

Lock current account row first for contact mutation, challenge mutation and successful proof; then lock challenge/current binding. Verify actor/current number again under lock. Conditional attempt updates and consume must commit even for rejected guesses; do not roll back the failed-attempt increment by throwing too early. Unique mobile index is final arbiter for two accounts claiming the same number; catch duplicate-key safely without exposing holder.

For V1 admission budgets, use one short database-scoped advisory mutex (name includes database identity) to serialize aggregate budget checks and challenge insertion. Also serialize failed-guess budget admission through it. Acquire before row locks, with two-second timeout; release explicitly in finally. Do not hold any DB/advisory lock during HTTP delivery. This needs no generic rate table or distributed limiter for the current single-database deployment. MariaDB documents that transaction commit does **not** release GET_LOCK locks: [GET_LOCK reference](https://mariadb.com/docs/server/reference/sql-functions/secondary-functions/miscellaneous-functions/get_lock). Test lock failure, concurrent resends, incorrect guesses, verification/change races, deadlocks and duplicate-key outcomes on MariaDB 10.4.27 before release.

## 9. SMS provider requirements and India

HTTPS only, authenticated server requests, verified TLS, bounded connection/total timeout, structured success/failure, identifiable provider request ID, template/sender support and obtainable delivery reports. No credentials in URL/query logs, browser code or exceptions. Disable request-body debug logging and cross-host credential forwarding. API acceptance is queued/accepted, not delivery confirmation.

For the recommended domestic India route, obtain Principal Entity/DLT registration, approved header and correct content template, provider mapping and any required telemarketer chain/consent setup for the selected category. OTP copy must match approved fixed/variable fields; do not label KOBI as banking or invent exemptions. TRAI's guidance explicitly addresses sender/header/content-template registration, including OTP senders: [TRAI advice to senders](https://www.trai.gov.in/advice-to-senders). Confirm KOBI's entity/category with the provider; this report is not a legal determination.

Retain international canonical storage unchanged. Initial production SMS rollout should explicitly allow only enabled routes (recommend India +91 first); unsupported destinations receive a delivery-availability message, not altered contact validation. Local provider formatting belongs only in the adapter. No prefix inference or second internal identity representation.

## 10. Provider shortlist — official research, 2026-10-14

| Provider | Capability/API and India | Test/report/security findings | Main concern |
| --- | --- | --- | --- |
| **Exotel** | Direct transactional/OTP SMS; HTTPS account SMS API, API key/token authentication, India DLT entity/header/template fields | Status reporting/webhooks; trial SMS uses constrained/default templates; trial is actual service usage, not a no-send sandbox | SMS-only commercial terms, approved OTP template and exact trial allowance must be confirmed |
| **MSG91** | SMS Flow API plus separate managed OTP product; India templates/DLT support; Authkey server header | Delivery reports/webhooks; Authkey IP security/whitelist controls; demo mode substitutes message content and is not a proven no-send OTP sandbox | Do not confuse Flow delivery with managed OTP; use local fake for no-charge tests; verify chosen account route/pricing |
| **Twilio** | Programmable Messaging and managed Verify; India domestic/international routes documented | Legacy test credentials simulate Messages without live sends or callbacks; current docs say new-console test credentials cannot be created | India route economics and onboarding; managed Verify would duplicate the proposed local code lifecycle |

Sources for the table: [Exotel SMS API](https://developer.exotel.com/api/send-sms), [Exotel trial limitations](https://docs.exotel.com/business-phone-system/create-a-trial-account), [MSG91 SMS API](https://docs.msg91.com/sms/send-sms), [MSG91 DLT checklist](https://msg91.com/help/dlt-registration-in-india/dlt-debugging-checklist), [MSG91 Authkey security](https://msg91.com/help/api/where-can-i-find-my-authentication-ke), [MSG91 reports](https://msg91.com/help/where-can-i-check-the-delivery-reports), [MSG91 demo behavior](https://msg91.com/help/text-sms/my-account-status-is-in-demo-what-can-i-do-to-make-my-account-live), [Twilio India guidance](https://www.twilio.com/en-us/guidelines/in/sms), [Twilio test credentials](https://www.twilio.com/docs/iam/test-credentials).

No provider account, credentials or API delivery was exercised. Documentation proves listed capabilities, not comparative delivery reliability in KOBI's deployment. Separate restricted development/production credentials or accounts must be arranged during onboarding; a marketing trial is not evidence of an isolated sandbox.

## 11. Recommendation

Choose **Exotel direct SMS** for India-first V1: clear documented domestic template fields, direct delivery API and local operational fit with application-managed challenge state. This is an inference from the inspected capabilities, not a measured reliability ranking. MSG91 is the fallback candidate if Exotel's SMS-only onboarding/cost/template support is unsuitable. Do not build automatic multi-provider failover now; ambiguous retries can send multiple codes and incur duplicate charges.

Twilio remains a credible later international option, but its managed Verify lifecycle and India route/onboarding choices add complexity to this scoped local-HMAC design. If managed Verify is selected instead, revisit challenge verification responsibilities rather than checking a second independent locally generated code.

## 12. Development/test transport

Use `TestSmsProvider` under **tests/**, excluded from production distribution and unavailable from the normal application provider factory. Unit tests inject an in-memory collector plus deterministic clock/random fixtures; production randomness is never replaced. Browser tests run actual controllers/services/auth/CSRF in a separate localhost web fixture with separate session cookie/path and a **guarded disposable database containing synthetic users**, not normal KOBI.

The collector writes test-only generated messages to a permission-restricted, short-lived spool **outside the web root** for CLI/test-runner inspection. No HTTP code viewer, debug route, URL, page HTML or application log reveals OTP. Browser runner retrieves the synthetic code through that private test harness and enters it through normal UI. Banner says simulated delivery/testing; no fake production “SMS sent” claim.

Safety gates: test implementation absent from production artifact; factory production allowlist excludes it; fixture bootstrap requires explicit test mode, exact disposable DB identity and separately provisioned test configuration; refuses normal `medical1` and any live provider credential; no public deployment of fixture. APP_DEBUG or a single env flag alone must never enable fake proof. Tests must prove normal DB/production mode rejection. Simulated verified bindings exist only in disposable DB; they are removed with fixtures and never eligible for production recipient routing.

This enables genuine browser/controller-flow testing **without claiming actual phone possession**. Real carrier validation remains a separately authorized small live test after onboarding. No local fixture/test transport is implemented by this report.

## 13. Production configuration

Conceptual variables only: `MOBILE_VERIFICATION_ENABLED` (default false), `SMS_DRIVER` (disabled/exotel), provider account/key/token/region, DLT entity/header/template IDs, route allowlist, `MOBILE_OTP_HMAC_KEY`, `MOBILE_RATE_HMAC_KEY`. Existing env/config mechanism remains; no .env edits now. Credentials provisioned privately by operator, with least provider privileges, spending limits and separate test/live accounts where supported.

Reuse installed Guzzle through a small adapter, HTTP Basic auth header for Exotel rather than credential-bearing URL examples. Connect timeout 3 seconds, total timeout 10 seconds; no automatic send retry on timeout. Missing keys/template/route or unsupported driver disables verification safely, never falls back to fake/log/email. Hold plaintext only during send. Key rotation invalidates open OTPs; preserve old rate-digest key for the rolling budget/retention interval or apply a conservative admission pause so rotation cannot reset budgets.

## 14. Practical rate limits

Account and canonical-number limits: 60 seconds spacing, 3 requests/15 minutes, 10/24 hours. Aggregate failed guesses: 20/24 hours. Challenge five-failure invalidation causes a 15-minute account cooldown, enforced from persisted terminal timestamp so changing numbers/sessions does not bypass it. **The cap is three total requests per 15 minutes**, including the initial request and resends; no separate resend budget.

Source IP (direct REMOTE_ADDR unless an explicitly trusted proxy exists): 20 admitted requests/hour, 100/24 hours. These higher IP caps reduce shared-network impact; monitor safe counts and tune after pilot. CSRF-required authenticated endpoint, same-site sessions and rate limits across sessions; no general account-login lockout. Count accepted admissions including provider failed/unknown attempts. Budget-denied operations create no SMS; use bounded security logs, not unlimited denied-request rows. Keyed digests plus indexed request history supply counters.

## 15. Privacy and enumeration

Request a code only for the actor's stored number; ignore posted user/phone substitutions. Do not check/disclose other bindings before sending to a legitimate current contact; valid proof then gets a generic conflict response if needed. Wrong, expired, terminal or used code: “The code is invalid or expired. Request a new code when available.” Conflict/eligibility failure: “Unable to verify this number. Review your number or try again later.” No names, email, account ID, binding details or public lookup. An authenticated, proven holder may learn inability to bind, but never another holder's identity; avoid claiming this removes all possible possession-based inference.

## 16. Failure handling

| Failure | State / safe response |
| --- | --- |
| Provider timeout / ambiguous acceptance | Mark unknown; no retry. “Delivery could not be confirmed. If a code arrives, you may enter it; otherwise request another after the wait.” |
| Provider rejection / definite delivery failure | Invalidate, purge MAC/destination; count request. “Unable to send a code right now. Check your number or try later.” |
| Provider accepted | “Code requested. Check your phone.” Never assert delivered from API acceptance alone |
| Invalid/unsupported destination | Existing format feedback or generic route-unavailable message; no identity lookup |
| Wrong/expired/reused code | Generic invalid/expired; persist failed-attempt counters for live challenges only |
| Too many attempts/budget | Show bounded cooldown/retry guidance without naming another account |
| Resend too soon | “Please wait at least 60 seconds before requesting another code.” |
| Duplicate verified binding | Consume/invalidate proof and generic bind failure; existing holder unchanged |
| Database error | Roll back partial binding state, safe failure; never claim verified |

Reserve challenge and commit before network send; send outside locks. Finalize delivery result only if the same challenge remains open/current. If timeout actually delivered, unknown status is eligible for code entry while all proof checks still apply. A number change/resend racing delivery can produce a late SMS, but its invalidated code cannot bind. If post-send DB update fails, leave pending non-verifiable until a bounded recovery check invalidates it; no insecure success fallback.

## 17. Logging, SMS copy and retention

Log account/challenge ID, action, safe delivery/error category, provider request ID when necessary, rate event and success/failure. No code/MAC/HMAC key, full phone/IP, raw body, auth headers or provider payload. UI mask last four digits; logs use digests or omit number. Disable HTTP debug logging. V1 does not consume callbacks: obtain reports from provider; if added later, verify vendor-supported authenticity/replay controls and never let a callback create a binding. Callback signing guarantees were not established for all candidates in this research.

Example template for approval: “Your KOBI mobile verification code is {code}. Valid for 5 minutes. Do not share this code.” No medical/profile details, links or claims of identity/sharing permission. Provider-approved template may require different fixed text; approve final copy before sending.

Purge MAC and raw destination immediately on consumption/invalidation; expiry is enforced on every action even before cleanup. Expired rows have secrets cleared by bounded repository maintenance (including idle-site scheduled maintenance if needed). Keep terminal challenge digests/counters/provider-category metadata **7 days**, then delete in bounded batches; never delete within the 24-hour budget window. Test spools are wiped at test completion. Binding is current state, not permanent security history; delete on change/clear, retain expired reservation until legitimate release/reproof as described. No big audit system.

## 18. Exact proposed schema — documentation only

Two tables are necessary: repeated ephemeral challenges carry proof and budgets; one current binding holds uniqueness. Combining them would either retain multiple verified rows or overwrite history needed for rate limits. No users/contact/token schema changes. The binding DDL preserves 46A's design; challenge is the now-resolved additive design.

```sql
CREATE TABLE user_verified_mobile_bindings (
    user_id int(11) NOT NULL,
    mobile_number varchar(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    verified_at datetime(6) NOT NULL,
    valid_until datetime(6) NOT NULL,
    accepts_summary_requests tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (user_id),
    UNIQUE KEY uq_verified_mobile (mobile_number),
    CONSTRAINT fk_verified_mobile_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_verified_mobile_format CHECK (mobile_number REGEXP '^[+][1-9][0-9]{1,14}$'),
    CONSTRAINT chk_verified_mobile_validity CHECK (valid_until > verified_at),
    CONSTRAINT chk_verified_mobile_opt_in CHECK (accepts_summary_requests IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_mobile_verification_challenges (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    user_id int(11) NOT NULL,
    nonce char(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    purpose varchar(19) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'mobile_verification',
    mobile_number varchar(16) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
    mobile_digest binary(32) NOT NULL,
    ip_digest binary(32) DEFAULT NULL,
    otp_mac binary(32) DEFAULT NULL,
    failed_attempts tinyint unsigned NOT NULL DEFAULT 0,
    delivery_status varchar(8) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'pending',
    provider_message_id varchar(128) DEFAULT NULL,
    provider_error_category varchar(32) DEFAULT NULL,
    created_at datetime(6) NOT NULL,
    expires_at datetime(6) NOT NULL,
    delivery_updated_at datetime(6) DEFAULT NULL,
    last_attempt_at datetime(6) DEFAULT NULL,
    consumed_at datetime(6) DEFAULT NULL,
    invalidated_at datetime(6) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mobile_challenge_nonce (nonce),
    KEY idx_mobile_challenge_user (user_id,created_at,id),
    KEY idx_mobile_challenge_number (mobile_digest,created_at),
    KEY idx_mobile_challenge_ip (ip_digest,created_at),
    KEY idx_mobile_challenge_expiry (expires_at),
    CONSTRAINT fk_mobile_challenge_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_mobile_challenge_purpose CHECK (BINARY purpose=BINARY 'mobile_verification'),
    CONSTRAINT chk_mobile_challenge_nonce CHECK (nonce REGEXP '^[0-9a-f]{32}$'),
    CONSTRAINT chk_mobile_challenge_number CHECK (mobile_number IS NULL OR mobile_number REGEXP '^[+][1-9][0-9]{1,14}$'),
    CONSTRAINT chk_mobile_challenge_attempts CHECK (failed_attempts BETWEEN 0 AND 5),
    CONSTRAINT chk_mobile_challenge_delivery CHECK (BINARY delivery_status IN (BINARY 'pending',BINARY 'accepted',BINARY 'unknown',BINARY 'failed')),
    CONSTRAINT chk_mobile_challenge_expiry CHECK (expires_at > created_at),
    CONSTRAINT chk_mobile_challenge_terminal CHECK (consumed_at IS NULL OR invalidated_at IS NULL),
    CONSTRAINT chk_mobile_challenge_secret_shape CHECK (
        (consumed_at IS NULL AND invalidated_at IS NULL AND mobile_number IS NOT NULL AND otp_mac IS NOT NULL) OR
        ((consumed_at IS NOT NULL OR invalidated_at IS NOT NULL) AND mobile_number IS NULL AND otp_mac IS NULL)
    ),
    CONSTRAINT chk_mobile_challenge_failed_delivery CHECK (delivery_status <> 'failed' OR invalidated_at IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

All timestamps explicitly UTC. No CHECK references the auto-increment ID. Exact DDL must be tested on isolated MariaDB 10.4.27; no compatibility execution was performed here. Services enforce five-minute/90-day policy, fixed purpose, state transitions, timestamp ordering, eligibility and counter admission in current SQL mode. Expiry cleanup marks invalidated and clears secrets atomically; fifth failure likewise. Nonce is an opaque identifier, not authorization. Account row locking and invalidating predecessors enforce one usable challenge per account without an unsafe partial-unique assumption. Digest indexes support scoped rolling counts; no global contacts/permissions/rate table.

## 19. Implementation architecture

Focused MobileVerificationController/Service/Repository plus VerifiedMobileBindingRepository using existing DI/BaseRepository. Narrow `SmsProviderInterface::sendVerification(number,code,challengeReference)` returns accepted/unknown/failed plus sanitized request ID/category. Exotel adapter contains all provider-specific HTTPS/template formatting. Test implementation is only in fixture code. Do not scatter HTTP through controllers or copy MailService log semantics.

Extend Account Profile with masked stored/unverified/verified-until state, Request/Resend/Verify/Cancel forms; existing `csrfField()`, AuthService::id(), safe errors, retained safe input, Bootstrap and no inline JS. Service rechecks active actor/current canonical mobile and challenge ownership; ignores posted phone/user/profile substitutions. Cancellation invalidates challenge, not binding. Mobile update repository transaction integrates binding/challenge invalidation; equivalent saves preserve proof. No session regeneration/login change is needed; no phone-login route.

## 20. Step 46B implementation sequence

Approve recommendations → implement two-table artifacts and strict normalization reuse → transactional challenge/budget and unique-binding service → narrow Exotel adapter and fixture-only test transport → Profile request/code/state/change/clear UX → tests for hashes, expiry/replay/resend/limits, failures and races → isolated MariaDB constraints/concurrency → genuine isolated-login fixture browser at 390×844 and 1366×768 → backup recovery before separately authorized normal deployment → explicitly authorized minimal real SMS/carrier verification after onboarding → cleanup and honest report of simulated versus real validation.

No extra governance phase is needed. Production verification stays disabled if credentials/templates/routes are absent; that is an operational prerequisite, not an excuse for fake delivery. No new tests or feature code were created during readiness.

## 21. Future Medical Sharing dependency

Only a current real verified binding may later participate in recipient identification. It does not grant summary/profile/document/medication access. Step 46C separately implements the 46A consent-first recipient-bound snapshot, seven-day access and revocation. Request opt-in remains off until that workflow exists; no recipient resolver/API is added by verification.

## 22. Costs and operations

Public pages checked on 2026-10-14; amounts are not quotes or commitments:

- Exotel advertises SMS starting **INR 0.18 at 100,000 SMS**, GST applicable; request SMS-only onboarding/minimum commitment and low-volume price rather than buying voice bundles. Its pricing and trial documentation present differing trial windows/credits, so no guaranteed free allowance is assumed. [Exotel pricing](https://exotel.com/pricing/business-phone-system/)
- MSG91's **OTP product** page shows **5,000 at INR 0.25/OTP** and **27,000 at INR 0.20/OTP**, with GST indicated. These are not a confirmed quote for the recommended direct SMS Flow adapter; the direct SMS price page did not expose a numeric tariff in retrieved content. Confirm route/product price, validity and minimum pack. [MSG91 OTP pricing](https://msg91.com/in/pricing/otp), [SMS pricing](https://msg91.com/in/pricing/sms/pricing-india)
- Twilio Verify advertises a standard **USD 0.05 successful-verification fee plus channel costs**; not the same as direct Messaging pricing. India Messaging pricing is usage/destination/segment based; a numeric India tariff was not recoverable from the retrieved dynamic page. [Verify pricing](https://www.twilio.com/en-us/verify/pricing), [India SMS pricing](https://www.twilio.com/en-us/sms/pricing/in)

Budget by admitted SMS requests, including retries/failures where billable, encoding/segments, GST, DLT/onboarding fees and provider-specific validity. Obtain actual tariff and spend caps before production activation. No verified no-charge sandbox was established for Exotel/MSG91; local fixture makes routine tests no-send/no-charge. SMS reliability/latency across Indian carriers must be measured in an authorized pilot, not inferred from marketing claims.

## 23. Deferred scope and validation

Medical Summary sharing/recipient lookup, notifications/email/push, general messaging, phone login/account merging, broader/legal identity verification, multi-provider automatic failover, public mobile search, broad APIs/native mobile work and detailed callback infrastructure remain deferred.

Inspected files: the three prerequisite reports; `database/structure.sql`; `app/Services/AuthService.php`, `UserAuthTokenService.php`, `MailService.php`; `app/Repositories/UserRepository.php`, `UserAuthTokenRepository.php`, `BaseRepository.php`; `app/Core/Session.php`; `app/Controllers/AccountController.php`; `app/Views/account/mobile-number.php`; `app/Config/Config.php`; `routes/web.php`; `composer.lock`; `tests/auth-phase1.php`, `user-mobile-number.php`, `user-mobile-number-mariadb.php`, `user-mobile-number-deployment.php`. Official external sources are linked beside findings; no provider accounts/API requests were made.

Eight inspected PHP files passed syntax checks; diff check passed. Only this readiness document was created. No application/SQL/config/.env edits, database access/writes, generated OTPs, SMS, provider account/credential changes, browser/session changes, SQL-mode changes or Git mutations. No implemented OTP/browser/database tests are claimed.

## 24. Genuine remaining decisions

Owner approval of the concrete policy, two-table design and isolated-test-only transport; Exotel selection or fallback; actual commercial/route coverage, DLT entity/header/template eligibility and secure credential provisioning. These operational facts cannot be established without the operator/provider and remain production activation prerequisites. The challenge storage, code lifecycle and concurrency/test isolation recommendations are now concrete, rather than unresolved implementation guesses.

**Step 46B-1 READY WITH PREREQUISITE. Stop here; Step 46B has not started.**
