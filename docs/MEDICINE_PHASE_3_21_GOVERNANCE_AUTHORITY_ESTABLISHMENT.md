# Medicine Phase 3.21 — Governance authority establishment

Status: **BLOCKED — EXPLICIT HUMAN AUTHORIZATION NOT PROVIDED.** This is an authority-model inspection and bootstrap proposal, not completed establishment or an acceptance-test pass.

> Governance authority establishment is blocked pending explicit owner authorization.

The Phase 3.21 request specifies the permitted process but supplies no real owner/grantor account, administrator grantee, contributor grantee or deployment delegate. Sections 19, 20 and 34 require a stop rather than guessing these identities. No real authority was established.

## Objective and Phase 3.20 blocker

Phase 3.21 establishes legitimate, separate Medicine administrator, contributor/draft-author and deployment authority. It does not deploy the provisional schema or create Medicine content. Phase 3.20's guarded read-only inspection found zero grants and zero eligible authors in `medical1` on MariaDB 10.4.27. Its report is the last observed normal-database state; this phase made **no normal database connection** and did not recensus accounts or grants.

The blocker is deliberate absence of staffing/authorization, not a missing authority table or a disabled pilot flag. The authority and event tables were already observed deployed in Phase 3.20. They contain no seeds. Current inspected configuration still has empty `owner_user_ids`, `registry_operator_user_ids` and `deployment_operator_user_ids`. Neither an application administrator nor a database credential can populate these by implication.

## Existing authority model

Inspected:

- `MedicineGovernancePolicy`, `MedicineAuthorizationService`, `MedicineGovernanceService`;
- `MedicineGovernanceRepository`, `MedicineProvisionalIdentity`, eligibility/verification gates;
- `app/Config/Config.php`, `database/structure.sql`;
- existing policy, governance, governance-MariaDB, provisional and foundation authorization tests.

Actual capabilities: `administrator`, `contributor`, `editor`, `reviewer`, `steward`, `registry`, `deployment`, and a stored `publisher` capability. The current authorization service does not expose a public publication operation or accept `publisher` as a governance-use capability. A stored role label alone is not publication permission.

Content scopes are `chemical`, `composition`, `presentation`, or a catalog-wide grant. Operator capabilities are accepted only with `catalog` scope. The smallest proposed draft-author grant is `contributor / composition`, not catalog-wide. Proposed administrator/deployment grants use `catalog`, because the existing operator contract requires it.

`MedicineGovernancePolicy::owner()` recognizes only strict, positive integer IDs explicitly listed in trusted owner configuration. It does not inspect application admin roles, the first user, company ownership or DB credentials. `requireOperator()` also requires an active existing user. It accepts administrator authority from explicit owner configuration or a current administrator/catalog grant; registry authority from its explicit list or current registry/catalog grant; deployment authority from an explicitly configured owner, explicit deployment list or deployment/catalog grant.

Important existing policy consequence: explicitly configuring a Medicine owner makes that account eligible for both administrator and deployment checks. It does not confer contributor/editor, reviewer qualification or registry authority. That existing coupling must be disclosed in the owner's establishment decision; this phase did not change it or treat company ownership as a configured Medicine owner.

## Bootstrap finding and smallest safe proposal

There is an existing trusted configuration root and a service for delegated grants. There is **no self-bootstrapping runtime operation**, no default administrator and no persisted grant event that establishes the initial configured owner. With empty owner configuration and zero administrator grants, the service correctly denies grant issuance.

Reuse the existing trusted root rather than introducing an escalation endpoint. Proposed establishment sequence, **not executed**:

1. Obtain an explicit owner decision naming the real existing active KOBI owner/grantor account. Record the decision/reference, date, intended administrator/deployment consequences and policy version in protected operator documentation; do not infer the relationship from account metadata.
2. After specific authorization, designate that account in the existing deployment-controlled owner configuration. Keep pilot/public settings disabled. Configuration designation is a trust-root change, not an ordinary self-grant; it must not be presented as a pre-existing immutable database grant event.
3. Authenticate that real account through normal KOBI authentication. Use existing `grantAuthority()` for any named administrator delegate (`administrator/catalog`) and explicitly named contributor (`contributor/composition`), with owner-approved expiry and rationale. Owner-controlled deployment delegation is a separate `deployment/catalog` grant if requested.
4. Verify each persisted grant and its immutable event, and exercise authorization/revocation in synthetic fixtures before any real grant use. Do not manufacture an authenticated session or use a test AuthService against the normal database.

This design adds no endpoint, fallback or parallel role system. It remains a proposal awaiting real identities and owner authorization. A requirement for a separately persisted initial-owner bootstrap event would need an explicit design decision before implementation; existing `grantAuthority()` rejects **all self-grants**, including owner-to-self contributor/admin/deployment grants. This rejection was preserved. If the owner is also intended as draft author, name a legitimate distinct administrator/grantor or explicitly resolve that self-grant policy first; do not bypass it with SQL.

## Existing grant and revoke mechanisms

`MedicineGovernanceService::grantAuthority()` runs transactionally through the repository. It obtains `AuthService::id()`, verifies an active actor and administrator authority, requires an existing active target distinct from the actor, validates capability/scope/expiry, and rejects unexpected fields. Operator grants additionally require the grantor to be an explicitly configured owner and the scope to be `catalog`. A delegated administrator may issue ordinary content grants but cannot delegate administrator/registry/deployment authority.

A grant stores user, capability, scope, active state, grantor/time/expiry and optional reviewer qualification tuple. Its `grant` event stores actor, target grant ID, revision 1, active state, rationale, facts snapshot/hash and timestamp. No qualification fields are permitted for ordinary contributor/operator grants.

`revokeAuthority()` rechecks current administrator authority, locks the grant, forbids repeated revocation and restricts operator-grant revocation to the owner. It marks the grant inactive with revoker/time/reason and appends a `revoke` event, revision 2, with resulting revoked state. Both mutations and event append share a transaction. Historical events are retained; a failed audit append rolls back the command.

The schema's `uq_medicine_active_grant(user_id,capability,scope,active_slot)` permits only one unrevoked grant for the same exact tuple. Revocation frees the slot while retaining the old grant. An expired but unrevoked row still occupies that slot. There is no grant-request idempotency token; a duplicate cannot be assumed to return the original event. The command boundary maps PDO failures to a safe storage error. Different roles/scopes may coexist; exact duplicate rejection is not proof of every possible conflicting-role policy.

## Audit, revocation and independence boundaries

`medicine_governance_events` has subject XOR, action/state/JSON/shape/review CHECKs and foreign keys. `medicine_events_no_update` and `medicine_events_no_delete` reject history edits/deletes. Original grant and revocation snapshots are immutable events. The current grant row is intentionally mutable for revocation; the inspected schema does **not** have a general immutable-grant-row trigger. Do not describe current grant rows as immutable or presume the service controls privileged direct SQL.

Authority is checked afresh using active account, exact capability/scope, active/unrevoked grant and current grant/expiry dates. Reviewer use additionally requires verified current Doctor/Pharmacist credentials and scope qualification. Administrator or deployment authority does not satisfy those requirements.

Canonical `verify()` rejects the target creator and every prior assess/edit/create/submit contributor, even if the account also holds reviewer credentials. It requires current facts, submission, evidence appraisal and qualified independent authority. Provisional verification/approval is not an available operation: the separate provisional model cannot create a canonical target, reviewer decision or approval. Future cross-object handoff must preserve provisional authorship independence; Phase 3.19 documented that future design requirement rather than claiming it implemented.

## Authority matrix

Permissions below describe the role **alone**, not all combinations. “Gate only” means there is an authorization check, not a registry-write/DDL command. There is no Phase 3.21 operation enabling those actions.

| Role (actual vocabulary) | Can prepare draft | Can review | Can approve | Can modify registry | Can deploy | Can activate pilot | Can publish |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Medicine Administrator (`administrator/catalog`) | NO; separate contributor/editor needed | NO | NO | NO | NO; separate deployment authority needed | NO by role alone | NO |
| Contributor (`contributor/composition`) | YES for selected provisional candidates once schema exists | NO | NO | NO | NO | NO | NO |
| Qualified Reviewer (`reviewer` + qualified scope) | NO; separate contributor/editor needed | Conditional independent canonical review | Conditional canonical `verify()` only; never own work or provisional approval | NO | NO | NO by role alone | NO |
| Registry Operator (`registry/catalog`) | NO | NO | NO | Gate only; no registry command in inspected governance service | NO | NO | NO |
| Deployment Operator (`deployment/catalog`) | NO | NO | NO | NO | Gate only; deployment execution remains separately controlled | Not automatic; current approved enrollments and complete owner-controlled activation prerequisites required | NO |

An explicitly configured owner satisfies administrator and deployment checks under existing policy, unlike an administrator delegate. Editor/composition can prepare provisional drafts; steward/composition can append terminal provisional closure. These do not create reviewer/public/registry authority. Current pilot intake/internal flags are false, so no hypothetical conditional entry above is presently enabled as a normal content workflow.

## Current-state table

“After” means **no change by this phase** to the Phase 3.20 observed state; it is not a fresh live census. No normal DB read/write was performed in Phase 3.21.

| Governance item | Before 3.21 (last observed) | After 3.21 (unchanged by this run) |
| --- | --- | --- |
| Medicine administrators | 0 | 0 |
| Eligible contributors | 0 | 0 |
| Deployment operators | 0 | 0 |
| Qualified reviewers | 0 | 0 |
| Reviewer decisions | 0 | 0 |
| Provisional drafts | 0; journal absent | 0; no deployment attempted |
| Canonical Medicine Identities | 0 | 0 |
| Pilot enrollment | 0 | 0 |
| Pilot activation | OFF | OFF |
| Public Medicine | OFF in existing inspected implementation | Unchanged; no live/public test performed |

## Test and validation status

No implementation or real authorization action occurred before the human-decision stop. Required `tests/medicine-authority-establishment.php` and conditional MariaDB harness creation, new authority security tests, and simultaneous grant/grant-revoke/duplicate/conflicting-grant concurrency tests are **deferred**. No cases/assertions are reported for them. Existing test code was inspected but not executed or counted as passing Phase 3.21 tests.

Also deferred: policy, governance, provisional, pilot, foundation, Phase 3.18, Treatment and Authentication regression execution. The earlier Phase 3.19 totals remain historical only. No synthetic credentials, content or disposable database were created by this run. Full acceptance criteria are not met and no PASS is claimed.

Read-only source/diff inspection completed. No modified PHP files require syntax checks. The source checks establish the fail-closed design and identify existing tests for delegated grants, owner-only operator delegation/revocation, revoked/expired/wrong-scope denial, audit rollback, qualified self-approval rejection and revocation races; they are not substitutes for the requested new execution matrix.

## Exact remaining human decisions

No intended grantee is yet known for any role. Supply an explicit owner instruction containing only existing KOBI account IDs and role decisions, not passwords or credentials:

| Decision | Proposed minimum scope | Intended grantee | Required authorization |
| --- | --- | --- | --- |
| Establish trusted Medicine owner/grantor | Existing configured owner root; administrator/deployment consequences disclosed above | Not supplied | Identify real owner account and explicitly authorize its trust-root designation and recorded establishment decision |
| Medicine Administrator | `administrator/catalog` if a delegate | Not supplied | Name owner or approved delegate; if delegate, name owner grantor, expiry and rationale |
| Draft Author | `contributor/composition` | Not supplied | Name existing active account, legitimate distinct authenticated administrator grantor, expiry and rationale |
| Deployment Operator | `deployment/catalog` if delegated | Not supplied | State owner or named delegate; delegated grant requires authenticated owner, expiry and rationale |

No reviewer or registry grant is requested or proposed. No current user ID should be inferred from this chat. Where the same person is intended for multiple roles, specify each role; that does not waive self-grant prohibition or reviewer independence. Establishment must use actual authentication and existing services, not ad-hoc inserts or test actor substitution.

## Resume procedure and database safety

After these decisions are supplied, resume Phase 3.21 first: revalidate target/version/modes and deployed authority metadata, verify selected accounts safely, complete test-first authority/security/concurrency coverage, establish the approved root without fallback, issue only explicit service grants, capture audit events and compare protected data/modes/configuration. Normal grants must not occur until legitimate authentication and the establishment mechanism are confirmed safe.

Only after Phase 3.21 is validated may Phase 3.20 resume under its original authorization. That still requires fresh protected backup and verified recovery, unchanged artifact/evidence, pre/post metadata/data/privilege digests, exact additive deployment, real service-based preparation of the five journal drafts and all deferred tests. This phase does not authorize or execute that deployment itself.

## Files and explicit confirmations

Created: `docs/MEDICINE_PHASE_3_21_GOVERNANCE_AUTHORITY_ESTABLISHMENT.md`. Existing files modified: **none**. Schema changes and SQL to apply in this phase: **none**. No normal DB connection, DDL, data write, owner configuration change or privilege change occurred.

No legitimate administrator/contributor/deployment/reviewer was established; none existed in the last Phase 3.20 census. No reviewer was assigned, credential invented, reviewer decision made or approval recorded. No provisional/canonical record or Chemical Reference was created. No pilot enrollment/activation or public publication occurred. No registry/personal-medication/authentication/Treatment/Disease data or behavior was modified. Existing Treatment HOLD and disabled pilot configuration were preserved. SQL mode was not changed. No commit, push, reset, checkout, merge, rebase or other Git mutation occurred.
