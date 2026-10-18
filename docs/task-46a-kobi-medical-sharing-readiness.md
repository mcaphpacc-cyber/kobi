# Step 46A — KOBI-to-KOBI Medical Summary Sharing Readiness

Date: 2026-10-14. **READY WITH PREREQUISITE.** Source/schema review only; no sharing, lookup, verification, UI or database implementation.

## 1. Executive summary

The existing MVC/repository/profile-access foundations support a narrow internal summary feature. **Verified, unambiguous mobile-to-account binding must be established before a mobile-addressed share can deliver medical information.** Step 45C contact storage does not supply that binding.

Recommend one dedicated private summary-share entity, reusing the existing summary data assembly and controlled projection/rendering patterns. Preserve public summary links and profile invitations. A short consent-first handshake is needed because returning an account name immediately after entering a number reveals account existence, even to authenticated senders.

Sequence: approve and implement the scoped mobile-verification prerequisite first; then implement Step 46B private snapshot sharing with recipient approval, final sender confirmation, fixed expiry and revocation. Do not substitute an unverified single-match lookup or an email/token workaround for the prerequisite.

## 2. Existing Medical Summary architecture

`GET /account/health-records/{profileId}/medical-summary` → `AccountController::medicalSummary()` → `MedicalSummaryService::getSummary()` → existing condition/medicine/allergy/procedure repositories and `HealthProfileService`.

Profile access uses `AuthService::id()` and `HealthProfileRepository::findAccessible(profileId,userId)`: active profile plus active owner/editor/viewer membership. Access to a private summary is not permission to redistribute it: existing share creation requires **owner**, which the new feature should preserve.

The service returns the accessible profile row and repository arrays, including internal record IDs, profile IDs and row timestamps/metadata. It separates active/chronic conditions from resolved/historical conditions, includes **current medications only**, all recorded allergies and procedures, sorts rows, and returns `timeline_available=true`. It does not generate diagnosis/advice/AI content. The private view formats dates/labels and derives age from DOB; age must be frozen in a shared snapshot.

Documents are not embedded in this service result. The private view has a Reports & Documents navigation link and a timeline link; these are private profile destinations, not shared data. No medication slots/checkoffs, document bytes, insurance, saved symptom checks or account mobile fields are assembled into the summary.

## 3. Existing sharing architecture

### Public summary links

`MedicalSummaryShareService` and `MedicalSummaryShareRepository` use `medical_summary_shares` and `medical_summary_share_access_logs`. Owner creates a frozen JSON snapshot; a 32-byte random token is returned once, and SHA-256 token hash is persisted. Sender selects 1–30-day expiry (controller default 7); owner can revoke. Expiry is computed at read time, rather than a stored expired state.

`/shared/medical-summary/{token}` is an anonymous bearer-link route: possession is sufficient, with active/expiry checks. The controller sets no-store/no-cache, noindex/noarchive and no-referrer headers. Successful reads insert IP/user-agent access logs. It renders `shared/medical-summary.php`, a read-only snapshot view with print styling. This readiness review did not call that route, avoiding access-log writes.

The table requires a token hash, has only active/revoked statuses, and has no intended-recipient account, acceptance or selected-scope model. Existing access logs do not identify an authenticated recipient. Adding private shares to that table would require changing its shape and every anonymous reader to distinguish authorization modes. **Do not reuse the bearer authorization path for private shares.**

### Profile invitations and membership

`HealthProfileInvitationService` / repository use `health_profile_invitations`: owner/sender (`invited_by`), profile, target email, editor/viewer role, token hash, expiry, pending/accepted/revoked status and accepted timestamp. Creation/management is owner-only. Authenticated acceptance checks the account's email matches the invitation, then `acceptWithMembership()` creates/reactivates membership transactionally. Tokens are hashed; expiry is 1–30 days. There is no mobile addressing or explicit rejected state/recipient decline operation in the inspected service.

Membership permits continuing live profile access and, for editors, health-record mutations. That is broader than a read-only summary snapshot. Reuse acceptance/transaction/access-check patterns, **not** the membership grant or invitation table for summary delivery. Relevant routes include `/shared/health-profile-invitation/{token}` and its POST accept route, and owner access-management routes.

No separate internal recipient-bound medical-summary mechanism was found. Existing document access remains profile-scoped; summary printing is presentation, not an internal recipient grant.

## 4. Mobile identity findings

`users.mobile_number` is nullable `varchar(16)`, ASCII/binary, non-unique, with the approved format CHECK. AccountController → AuthService → UserRepository supports private own-account add/update/clear; raw length is capped, formatting separators removed, canonical `+` international shape checked. No verification flag/proof exists and the UI expressly denies sharing eligibility.

Authentication remains account-ID/session based. External provider identities live in `user_auth_identities`; Google linking is explicit and an email match alone does not merge accounts. Neither email verification nor Google authentication proves control of the stored mobile number. Mobile storage must not become a login credential, account merge key or access grant.

## 5. Recommended V1

1. Profile owner opens Medical Summary and chooses **Share with KOBI User**.
2. Select summary categories, preview scope, and enter a canonical recipient number.
3. Create a uniform sender-visible request receipt. Resolve privately against current verified bindings only. No account name/existence result is returned.
4. An eligible bound account sees a pending request in **Shared With Me**. It contains sender's consented display name, category labels and expiry, **no patient/health content**. Recipient approves contact/receipt and limited identity disclosure, or declines.
5. Only after approval, sender sees the consented recipient display name, previews the exact freshly assembled snapshot and explicitly confirms delivery. Activation stores exactly the confirmed snapshot.
6. Bound recipient opens the active snapshot inside KOBI; sender lists and revokes shares. No profile membership is created.

The extra recipient-approval step is a necessary privacy boundary, not a general messaging system. A pending request is not a share grant. No medical snapshot is stored/delivered before activation. Selection is category-level for V1; arbitrary per-record/field permissions are deferred.

## 6. Recipient identification and enumeration

Use authenticated, CSRF-protected POST inside this workflow, with Step 45C normalization reused in a focused helper rather than duplicated or changed. Perform an exact match against current eligible verified binding, never wildcard/partial public search and never `LIMIT 1` on duplicate contact values.

Unknown, unverified, inactive, duplicate/unresolved and self targets produce the same generic receipt/response as an eligible target: “Request recorded. Sharing can proceed only after recipient confirmation.” The sender list initially shows only this generic waiting state, not recipient IDs, lookup result, delivery status or names. Persist a null-recipient receipt for unresolved targets so list behavior cannot reveal existence. It cannot activate. Do not automatically resolve it later when a different account claims that number.

Recipient decline is an internal terminal result; do not expose “this account rejected you” before disclosure consent. Present the same generic unavailable/expired outcome as an unresolved request. Identity becomes visible only through the recipient's affirmative consent. This prevents ordinary lookup enumeration; it cannot prevent a consenting recipient from revealing their own existence, or a sender learning information through an agreed exchange.

Use per-actor persisted request counts and per-bound-recipient caps, one outstanding request per sender/recipient/profile/scope where resolved, cooldowns for repeated requests, and bounded response sizes. Recommend configurable initial caps of 5 new requests/hour and 20/day per sender, reviewed during testing. Authentication/rate limits alone do not make name-returning lookup private. Do not log raw numbers, request health data or failed-target details; retain only minimal actor/request outcome metadata. Use comparable generic response behavior across outcomes; no raw resolver API response is exposed.

## 7. Verification requirement

**Required before mobile-addressed delivery.** Stored = claimed contact; verified = possession established through an approved proof workflow; eligible = active account with current verified unique binding, willingness to receive requests and subsequent approval.

Recommend a small separate verified binding registry, independent of duplicate unverified `users.mobile_number` values. One current binding per user and globally unique bound number gives unambiguous routing. Bind only after proof for that authenticated account; keep it consistent with its current contact value. Reassignment/conflicts must never merge accounts or silently transfer grants.

The prerequisite must include approved proof provider/method, proof validity/re-verification policy, conflicting/recycled number handling, recipient opt-in and invalidation on contact change/clear. Its implementation may need bounded proof/challenge persistence, but this report does not invent an OTP-provider or challenge-storage specification. These are release prerequisites, not work to smuggle into 46B. No verification or current mobile behavior is changed now.

## 8. Exact shared data boundary

Private `getSummary()` arrays must **not** be serialized as-is. Use a versioned allowlist and category-level selection. Existing public projection provides the field inventory:

| Category | Allowed snapshot fields |
| --- | --- |
| Profile | Patient `full_name`, frozen `age`, `gender`; exact `date_of_birth` only with explicit include-DOB selection |
| Conditions | `condition_name`, `status`, `diagnosed_on`, `resolved_on`, `doctor_hospital`, `notes`; preserve active/chronic versus resolved/historical groups |
| Current medications | `brand_name`, `generic_name`, `strength`, `dose`, `frequency`, `route`, `status`, `started_on`, `stopped_on`, `prescribed_by`, `notes` |
| Allergies | `allergen`, `category`, `reaction`, `severity`, `identified_on`, `notes` |
| Procedures | `procedure_name`, `procedure_type`, `performed_on`, `hospital`, `doctor`, `reason`, `notes` |

Profile name/age/gender identify the patient; optional selected categories supply the rest. At least one health category must be selected. The final preview explicitly displays included notes and sensitive DOB if selected. Omitted categories contain no data. Generated metadata is limited to snapshot schema version, capture time and share expiry; timestamps are UTC, displayed using existing application convention. No clinical interpretation is generated.

Exclude account IDs/email/mobile/password/provider identities; raw internal record/profile/member IDs and row creation/update metadata; document files/download links; insurance; private timeline/navigation; stopped/historical medication lists; schedules/checkoffs; saved checks and unrelated account data. Server share context IDs stay in private authorization storage, not the rendered payload. Recipient has read-only snapshot access; public disease/substance links may remain ordinary public links, never private editing links.

## 9. Live versus snapshot

**Snapshot at final sender confirmation** is simplest and safest. Live sharing would expose new records/notes/DOB changes without renewed scope confirmation and require ongoing profile-data authorization. A versioned live-history subsystem adds unnecessary persistence.

Freeze the exact confirmed preview, including age. A short server-side confirmation step binds the reviewed projection to activation (e.g. session-held bounded draft and digest, recheck before commit); do not regenerate different content after the click. New health information never propagates. Changes require a new explicit share. Retain a schema version on the immutable snapshot; no separate summary-version table.

Revocation clears the stored snapshot in the same transaction and blocks new reads; it does not erase copies already printed, downloaded through browser functions, photographed or remembered. No refresh/edit/forward feature exists for recipients. KOBI cannot promise technical prevention of external copying.

## 10. Consent and acceptance

Recipient first approves receipt of the named categories and disclosure of their display name to the requesting sender; no medical content is available at that point. Sender then sees actual consented recipient, patient identity, selected categories/notes, optional DOB, capture time, fixed duration and revocation/copying limitations in an explicit final confirmation.

Suggested button: **Share this Medical Summary snapshot with [consented name] for 7 days**. Do not imply an ongoing profile grant. Two accounts with the same display name remain distinguishable by the verified number the sender entered and agreed exchange; never reveal private email/full account details to compensate. If sender is uncertain, do not activate.

## 11. Revocation, expiration and lifecycle

Persist only `pending`, `confirmed`, `active`, `rejected`, `revoked`; compute expired from `expires_at <= now`. Pending → confirmed by bound recipient approval; confirmed → active by original sender final confirmation; pending → rejected by recipient; sender may revoke pending/confirmed/active. No restoration, retargeting or recipient-created renewal. Concurrent activation/revocation must serialize.

Recommend fixed **7 days** for pending request, and 7 days from activation for active access. UI always shows the deadline. No perpetual V1 grant or expiry scheduler. Expired requests cannot activate; expired active rows cannot render. Sender can remove the snapshot by revoking even after expiry. On an expired read, erase the payload if still present; unopened expired payloads may remain in private storage until normal cleanup, but cannot be accessed. A periodic data-retention worker is deferred, not claimed implemented.

Revocation immediately blocks every subsequent server request with no-store/no-cache/noarchive responses; an already rendered page or in-flight response cannot be clawed back. Use private/no-store headers on confirmation, inbox and snapshot views; reload always rechecks authorization. Minimal retained history: sender/recipient/profile references, category scope, lifecycle/capture/expiry timestamps and last successful read time/count, no snapshot after revocation. This is not a complete immutable access/audit log.

## 12. Authorization model and product decisions

| Decision | Recommendation |
| --- | --- |
| Who receives? | Existing active account with current unique verified binding, request opt-in and explicit approval |
| Verified number? | Required prerequisite; contact value alone insufficient |
| Identification? | Exact private verified-binding resolution; generic receipt until recipient consent |
| Data / live? | Selected category allowlist, frozen snapshot; optional exact DOB; no live profile |
| Acceptance? | Recipient approves request before sender identity confirmation/delivery |
| Revoke / expire? | Original sender revokes; fixed 7 days; future reads denied immediately |
| No KOBI or ambiguous target? | Generic unresolved receipt; no health payload, external fallback or automatic account creation |
| Number changes? | Invalidate verified binding and pending approvals; do not retarget. Active accepted shares remain tied to recipient user ID |
| Recipient inactive/deleted? | Inactive denied every read/action. Deletion cascades private share rows; no orphan grant. After deactivation/reactivation, require a new share rather than restoring old access |
| Sender/profile changes? | Require original sender still active owner of active profile on every activation/read; access loss/deactivation invalidates grant, not transferable to a new owner |
| Forwarding? | No in-product forward/re-share or delegation; external copying cannot be prevented |
| Retention? | Minimal lifecycle metadata; purge snapshot on revocation; inaccessible expired payload behavior stated above |

Deactivation/access-loss must make existing private shares terminal revoked and clear payloads, not merely temporarily hide them until reactivation. This requires focused lifecycle integration in 46B and denial on reads while cleanup is pending. Sender/recipient identity is always immutable account ID. Recipient never receives profile membership or general repository access.

## 13. Proposed database design — not executed

**Sharing itself: one additive table. Verification prerequisite: one binding table**, with proof workflow storage to be approved separately. No alteration of existing public-share/invitation tables, generic permission/contact/message platform or notification table.

```sql
-- Prerequisite binding registry; only approved proof workflow may write it.
CREATE TABLE user_verified_mobile_bindings (
    user_id int(11) NOT NULL,
    mobile_number varchar(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    verified_at datetime(6) NOT NULL,
    valid_until datetime(6) NOT NULL,
    accepts_summary_requests tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (user_id),
    UNIQUE KEY uq_verified_mobile (mobile_number),
    CONSTRAINT fk_verified_mobile_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_verified_mobile_format CHECK
        (mobile_number REGEXP '^[+][1-9][0-9]{1,14}$'),
    CONSTRAINT chk_verified_mobile_validity CHECK (valid_until > verified_at),
    CONSTRAINT chk_verified_mobile_opt_in CHECK (accepts_summary_requests IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE private_medical_summary_shares (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    sender_user_id int(11) NOT NULL,
    recipient_user_id int(11) DEFAULT NULL,
    health_profile_id int(11) NOT NULL,
    recipient_proof_at datetime(6) DEFAULT NULL,
    scope_json text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    status varchar(9) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'pending',
    snapshot_json mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
    snapshot_schema_version varchar(20) NOT NULL DEFAULT '1.0',
    snapshot_created_at datetime(6) DEFAULT NULL,
    created_at datetime(6) NOT NULL,
    accepted_at datetime(6) DEFAULT NULL,
    activated_at datetime(6) DEFAULT NULL,
    rejected_at datetime(6) DEFAULT NULL,
    revoked_at datetime(6) DEFAULT NULL,
    expires_at datetime(6) NOT NULL,
    last_viewed_at datetime(6) DEFAULT NULL,
    view_count int(10) unsigned NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_private_summary_sender (sender_user_id,created_at,id),
    KEY idx_private_summary_recipient (recipient_user_id,status,expires_at,id),
    KEY idx_private_summary_profile (health_profile_id),
    CONSTRAINT fk_private_summary_sender FOREIGN KEY (sender_user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_private_summary_recipient FOREIGN KEY (recipient_user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_private_summary_profile FOREIGN KEY (health_profile_id)
        REFERENCES health_profiles(id) ON DELETE CASCADE,
    CONSTRAINT chk_private_summary_parties CHECK
        (recipient_user_id IS NULL OR recipient_user_id <> sender_user_id),
    CONSTRAINT chk_private_summary_proof CHECK
        ((recipient_user_id IS NULL AND recipient_proof_at IS NULL) OR
         (recipient_user_id IS NOT NULL AND recipient_proof_at IS NOT NULL)),
    CONSTRAINT chk_private_summary_status CHECK
        (BINARY status IN (BINARY 'pending',BINARY 'confirmed',BINARY 'active',BINARY 'rejected',BINARY 'revoked')),
    CONSTRAINT chk_private_summary_scope CHECK
        (JSON_VALID(scope_json) AND OCTET_LENGTH(scope_json) <= 1024),
    CONSTRAINT chk_private_summary_snapshot CHECK
        (snapshot_json IS NULL OR (JSON_VALID(snapshot_json) AND OCTET_LENGTH(snapshot_json) <= 262144)),
    CONSTRAINT chk_private_summary_expiry CHECK (expires_at > created_at),
    CONSTRAINT chk_private_summary_shape CHECK (
        (status='pending' AND accepted_at IS NULL AND activated_at IS NULL AND rejected_at IS NULL AND revoked_at IS NULL AND snapshot_json IS NULL AND snapshot_created_at IS NULL) OR
        (status='confirmed' AND recipient_user_id IS NOT NULL AND accepted_at IS NOT NULL AND activated_at IS NULL AND rejected_at IS NULL AND revoked_at IS NULL AND snapshot_json IS NULL AND snapshot_created_at IS NULL) OR
        (status='active' AND recipient_user_id IS NOT NULL AND accepted_at IS NOT NULL AND activated_at IS NOT NULL AND rejected_at IS NULL AND revoked_at IS NULL AND snapshot_created_at IS NOT NULL) OR
        (status='rejected' AND recipient_user_id IS NOT NULL AND rejected_at IS NOT NULL AND accepted_at IS NULL AND activated_at IS NULL AND revoked_at IS NULL AND snapshot_json IS NULL AND snapshot_created_at IS NULL) OR
        (status='revoked' AND revoked_at IS NOT NULL AND rejected_at IS NULL AND snapshot_json IS NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

`scope_json` is an exact object of category booleans plus include-DOB flag, validated by service; no patient content, raw target number or source record IDs. A null recipient is an unresolved generic receipt only. `recipient_proof_at` must match the registry's current proof at approval/activation; changed/expired proof cannot activate. There is intentionally no FK from accepted shares to the mutable binding registry: later phone changes cannot retarget or destroy an account-bound accepted share.

For active rows, snapshot must be non-null at activation and whenever access is permitted; nullable payload allows expired data to be erased while retaining expiry metadata. The service must reject a missing active snapshot and may erase it only after expiry/revocation, never leave a readable active row with no payload. CHECKs do not enforce current time, transition permissions, fixed durations, scope semantics, ownership or proof validity. Enforce these in transactional service/repository operations, including strict controlled-value validation under current non-strict SQL mode. Signed parent FK types match the existing schema; no CHECK references an auto-increment row ID. Exact DDL remains unexecuted and requires isolated MariaDB 10.4.27 validation before any migration.

## 14. Route / controller / service / repository plan

Use focused `PrivateMedicalSummaryShareController`, service and repository under existing DI/BaseRepository. Reuse MedicalSummaryService assembly; extract reusable projection/rendering code carefully with regression tests for unchanged legacy public snapshots. Do not route private IDs into `getPublicShare()` or call membership acceptance.

Proposed routes: owner GET/POST `/account/health-records/{profileId}/medical-summary/kobi-share`; GET `/account/shared-by-me`; GET `/account/shared-with-me`; private GET `/account/shared-summary/{shareId}`; CSRF POST suffixes `/approve`, `/reject`, `/activate`, `/revoke`. The owner route records requests, not a standalone lookup API. Sequential IDs are acceptable because possession confers no access; verify actor and related profile on every request. Raw share tokens are unnecessary for internal V1.

Repositories should scope queries by sender/recipient actor ID. Services enforce owner and active-account/profile checks, current proof during request approval/activation, exact scope and immutable snapshot, conditional transitions, expiry and revocation. Recipient inbox projection omits patient/source IDs and content while pending. GET snapshot may record minimal successful-read metadata after authorization; never log before access checks. Do not bootstrap anonymous viewers into authenticated access or change existing login/session behavior.

## 15. UI and navigation

Medical Summary owner action **Share with KOBI User**, kept distinct from existing public shared links. Add two modest Account links: Shared by Me and Shared With Me. No notification is required: inbox lists pending/active entries when the user visits. No Dashboard redesign or top-level messaging destination.

At 390×844 use stacked labelled recipient/scope forms, wrapped controls, visible recipient approval and exact final preview, bounded names/notes with text wrapping, readable snapshot sections, status/expiry and revocation confirmation. At 1366×768 use existing Bootstrap cards without broad layout changes. Empty states explain no requests/shares; unavailable and expired pages disclose no patient content. Private reader includes no editing/download/profile links. Browser validation must cover these flows at both sizes, console/overflow, refresh/revoke, logout/anonymous access and unrelated Account behavior.

## 16. Concrete security/privacy requirements

Authentication and active account checks; AuthService::id-derived actors; `findAccessible` plus **owner** requirement for sender; bound recipient read/action checks; CSRF on every mutation; strict identifiers/scope/status/input/date validation; parameterized queries; locked transitions; payload byte bound; HTML escaping and existing CSP; generic non-enumerating receipt/list/error projections; per-actor/request limits; current verified opt-in binding; no raw phone/health content in logs; no-store/no-referrer/noarchive pages; recipient-ID tampering/horizontal access denied; fresh revocation/expiry/account/profile checks; no arbitrary private URLs in snapshot.

Approval must not allow a recipient to modify snapshot/scope or grant further access. Final sender activation must not accept a browser-posted substitute recipient ID. Reuse normal authentication rather than trusting possession of URLs, tokens, phone numbers or emailed addresses. Validate lookup, consent and deactivate/revoke races and repeated/double submissions in isolated tests.

## 17. Implementation sequence

1. Approve the mobile-verification/unique binding prerequisite, proof validity/conflict/reassignment policy and opt-in workflow. Implement/test it separately, including atomic invalidation when account contact is changed/cleared. Do not change 45C in this readiness task.
2. **Step 46B**, only once that prerequisite passes: one private request/share table; private resolution with uniform receipts; category selection; recipient approval/rejection; final sender snapshot confirmation; inbox/outbox; read-only reader; fixed expiry and revocation; minimal lifecycle/read metadata; focused account/profile-deactivation invalidation; safe projection reuse.
3. Isolated schema/ownership/CSRF/privacy/duplicate-binding/transition/concurrency tests; unchanged public-link and invitation regressions; snapshot category and content-boundary tests; genuine two-account desktop/mobile acceptance; verified recovery and separately authorized migration; explicit UI cleanup of temporary records.

No browser interactions or two-account tests were performed now; the current browser/login state was left untouched.

## 18. Deferred scope

Sharing documents/individual health records, live health-profile access, collaborative editing, forwarding/delegation, external sharing redesign, new public-link behavior, contacts/messaging platforms, SMS/email/push notifications, clinical advice, AI summaries, per-field/per-record permissions, broad APIs/native mobile work and a full immutable audit/retention platform. Verification is a **prerequisite**, not something to defer while enabling unsafe phone-addressed sharing.

## 19. Risks, open decisions and validation

Release-blocking decisions: approved verification/proof method/provider and international coverage; proof lifetime/reassignment/conflicting claims; acceptance of consent-first rather than immediate name lookup. Category selection, optional DOB, fixed 7-day periods and minimal retained metadata are recommendations awaiting product approval, not implemented policy.

Two inspected reuse cautions: existing public projection merges condition groups then classifies by literal `historical`, so `resolved` rows can appear in its active projection despite private summary grouping. The new versioned projection should preserve upstream groups while legacy behavior remains untouched in this task. Existing public share controller catches may display exception messages directly; the new private controller must follow current safe allowlisted error conventions rather than copy that handling. No existing defect was fixed here.

Files inspected: `database/structure.sql`; `routes/web.php`; `app/Controllers/AccountController.php`, `GoogleAuthController.php`; `app/Services/MedicalSummaryService.php`, `MedicalSummaryShareService.php`, `HealthProfileInvitationService.php`, `HealthProfileService.php`, `AuthService.php`, `GoogleAuthService.php`, `UserAuthIdentityService.php`; `app/Repositories/MedicalSummaryShareRepository.php`, `HealthProfileInvitationRepository.php`, `HealthProfileRepository.php`, `UserRepository.php`; `app/Views/account/medical-summary.php`, `mobile-number.php`, `app/Views/shared/medical-summary.php`; prior 45A/45C schema/design evidence.

Validation: source/schema/route/authorization tracing; all **17 selected PHP syntax checks passed**; `git diff --check` passed. No runtime, DDL or new-feature browser acceptance is claimed. No DB connection/query/write, share-route read/access logging, login/logout/session change, test records or tokens, SQL-mode change or Git mutation was performed. Only this readiness report was created; no existing application/schema/document file was modified.

**Step 46A READY WITH PREREQUISITE. Stop; do not implement Step 46B automatically.**
