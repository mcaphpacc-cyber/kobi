# Phase 2C-3 isolated validation checklist

Phase 2C-4A now supplies the shared canonical public-read service. Its contract,
projection allowlists and validation results are documented in
[TREATMENT_PHASE_2C4A_PUBLIC_READ.md](TREATMENT_PHASE_2C4A_PUBLIC_READ.md).
Publication remains HOLD; publication administration, public treatment UI and
first controlled publication remain later phases.

Prepared only. No live database or browser operations were performed for this phase.
The current KOBI database must not be used for these tests. Applying the existing
foundation schema, creating fixtures and executing writes require separate authorization.

## Isolated MariaDB 10.4.27

Use a disposable database and a separately configured application copy. Do not edit
the project's credentials or current database. Confirm the server version. Preserve
the observed server SQL mode (`NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`);
do not change global or project SQL mode. Record failures rather than relaxing checks.

1. Apply the authoritative `database/structure.sql` only to the authorized disposable
   database. Confirm every Phase 2C-1 FK, unique key, CHECK and both source
   self-replacement triggers were installed. This phase adds no schema or SQL.
2. Create isolated active users: editor, independent qualified reviewer, second
   independent reviewer, revoked reviewer, reviewer for another system and ordinary
   user. Use explicit system-scoped permission grants and meaningful qualification
   text. Do not create global/wildcard roles. Include an inactive system fixture.
3. Create synthetic complete bilingual content, included and excluded items,
   verified protocol/item references and excluded-item references. No real medical
   content or personal health data is needed. Keep public governance mode `hold`.
4. Submit through the existing editorial service. Check the submitted event's
   revision, locales, canonical JSON and SHA-256. Check pending_review with no
   approved pointer or published locales.
5. Approve through the review service with current expected revision/state/event,
   assessed locales, rationale, explicit independence and a future UTC due date.
   Verify exactly one approved event and a coherent approved_review_id; revision,
   governed fields, source records and unpublished status must be unchanged.
6. Check UTC DATETIME(6) persistence without timezone conversion; reject malformed,
   missing and past due dates. Re-read the stored snapshot/hash and rationale.
7. On a fresh submitted fixture, request changes. Verify exactly one changes_required
   event, retained rationale, unchanged content/revision, cleared approval pointer
   and unpublished status. Unmodified resubmission must fail. A material editorial
   correction must increment revision before fresh submission and approval.
8. Exercise FK failures for nonexistent actors, sources, protocols and event
   pointers; test that the approval pointer cannot reference another protocol or
   revision. Exercise all existing coherence/approval-event CHECK constraints.
   Preserve existing schema protections; never alter them to pass a test.
9. Test both AFTER INSERT and AFTER UPDATE self-replacement triggers with explicit
   synthetic IDs. Confirm rejected statements leave no row/change. Also exercise
   service rejection of direct self-replacement, two-node and longer cycles, and
   acceptance of a valid finite chain. Triggers do not replace chain validation.
10. Under the unchanged permissive SQL mode, submit invalid controlled ENUM/SET
    values through application services. Every invalid value must be rejected
    before persistence. Database coercion is not application validation.
11. Use two independent connections to approve the same pending revision
    concurrently. Only one may commit; the other must fail stale-state checks.
    Check one event, one pointer and no overwritten history. Repeat with an
    approval racing a changes-required decision.
12. Race an editorial material edit against approval; the serial outcome must be
    coherent and an old form must never approve a newer revision. Test changed
    relationships and bibliographic versions as well as protocol/item text.
13. Race explicit source withdrawal against approval. The transaction must check
    current locked source status, not a stale read snapshot. Withdrawal invalidates
    affected protocols through existing governance behavior. Inspect both serial
    outcomes and confirm withdrawn evidence cannot sustain a current approval.
14. Verify approval cannot silently verify an unverified source; explicit authorized
    verification remains a separate operation. Withdrawn sources cannot be restored.
15. Inject a failure between event insertion and pointer/state update, and between
    changes-required state update and event insertion. Both operations must roll
    back completely. Check deadlock retry behavior using the existing bounded
    retry mechanism; no duplicate events may survive.
16. Validate the review-list and history SQL with bound filters, bounded pagination,
    permissions/revocation and the older-event cursor. No raw snapshot or unrelated
    user data should appear in history results rendered to the browser.
17. Verify all failed authorization, independence, source, snapshot and concurrency
    decisions leave event counts and protocol content unchanged. Verify hold-mode
    public disease retrieval remains empty after successful internal approval.

## Future isolated browser run

1. Sign in as the editor and submit a complete fixture with explicit locales.
2. Sign in as the qualified reviewer and open `/admin/treatments/review`; confirm
   the pending fixture and system-scoped filters/pagination.
3. Confirm ordinary, wrong-system and revoked reviewers cannot list or open it,
   including direct protocol URLs and forged decision POSTs.
4. Open the review detail; inspect identity, actual system, qualification scope,
   state/revision, submission event and canonical match status.
5. Inspect current and submitted protocol/item content, included/excluded labels,
   evidence classifications, claim scopes, locators and bibliographic source fields.
   Confirm live provenance status is separate from efficacy assessment. Source
   verification must be an explicit existing source-management action.
6. Explicitly select assessed languages, enter rationale and a future UTC due date,
   confirm independence and approve. Missing CSRF, rationale, date, locale or
   attestation must fail without writes; material authors cannot self-approve.
7. Confirm internal Approved / Unpublished, one approval event and retained due date,
   rationale and independence confirmation. No publish action should exist.
8. Open the public disease page; the approved fixture and internal reviewer data
   must remain absent while hold mode is unchanged.
9. On another pending fixture request changes with rationale; inspect history and
   Changes Required / Unpublished. Content must remain untouched by that action.
10. Have the editor make material corrections and confirm revision increments.
11. Submit an old open reviewer form; it must fail safely with a reload message.
    Repeat with two reviewers and with source withdrawal while the form is open.
12. Require fresh submission; an uncorrected changes-required revision cannot be
    resubmitted, and corrected content cannot be approved before submission.
13. Open the fresh submitted revision, reassess and approve. Check escaped stored
    treatment/source/rationale text, safe source URLs, keyboard labels, mobile
    Bootstrap layout, older history and absence of inline JavaScript.

## Initial publication policy locked by Phase 2C-3A

`TreatmentGovernancePolicy::INITIAL_PUBLICATION_EVIDENCE` explicitly permits only
`evidence_supported`, subject to every other governance requirement. It denies
`insufficient_evidence`, `evidence_does_not_support`, `not_assessed` and unknown
values. Insufficient and nonsupporting evidence remain governed and assessable
internally, including requests for changes; this does not relax the existing
approval gate. Protocols and included items must satisfy the evidence rule.
Evidence classification is a recorded assessment, not a claim that approval proves
clinical effectiveness. Source verification concerns provenance and bibliographic
integrity; it does not prove treatment effectiveness or confer approval.

`TreatmentGovernancePolicy::INITIAL_PUBLICATION_RULES` records these fixed initial
release requirements for the eventual publication workflow and reader:

- Wellness/self-care receives no blanket evidence or governance exception.
- Higher-risk treatment content is ineligible for initial public publication.
- The approved review must be current. An expired `review_due_at` blocks
  eligibility and requires fresh review/approval; expiry alone must not mutate
  stored review state. The existing publication operation rejects a due date at
  or before the current UTC timestamp. There is no invented maximum interval.
- Reviewer qualification remains the existing system-scoped grant and its
  nonblank recorded qualification scope, with existing authorization and
  independence checks. No new credential taxonomy or onboarding is introduced.
- Reviewer identity and internal review rationale stay internal.
- Approval and publication are separate. Approval records that the submitted
  revision passed governance review; it leaves content unpublished. Publication
  requires a separate explicit action and does not mean KOBI recommends treatment.
- Published content cannot be edited in place. The required lifecycle is
  published → withdraw → edit/new revision → submit → review → approve → publish.
- Legacy initialization remains unpublished, draft and not assessed. No automatic
  approval/publication or fabricated sources, classifications, history or identity.
- Phase 2C-4 must use one canonical publication-eligibility service/predicate for
  public disease pages and any future public API. It must incorporate current
  review expiry and all other gates; an API must not implement a parallel rule.

These constants are policy requirements, not a second eligibility implementation.
Existing approval/publication validation and public hold mode are unchanged.
No public publication workflow, reader or API is implemented by Phase 2C-3A.

### Phase 2C-3B risk decision and lifecycle prerequisite

`treatment_protocols.risk_eligibility` is a reviewer-controlled governance decision:
`eligible`, `not_eligible`, or `not_assessed` (the legacy/default value). It is not
a risk taxonomy, score or automatic inference from system, item type or evidence.
Eligible means explicitly assessed under the current KOBI public-risk policy;
it does not mean safe, effective, recommended or risk-free. Initial approval and
eventual publication require both `eligible` and `evidence_supported`; neither
is sufficient by itself. Unknown, missing, not-assessed and not-eligible decisions
fail closed. All existing source/content/locale/qualification/independence gates
remain required. Public publication is still deferred to Phase 2C-4.

Only a qualified independent reviewer with the existing system-scoped capability
can record this decision through the internal review detail's dedicated form.
A changed decision increments the revision once, clears approval and records a
`risk_assessed` event with rationale and canonical snapshot/hash. It requires
fresh editorial submission before approval. Risk assessment is recorded separately
from material authorship; the reviewer must still be independent of all recorded
material authors. Approval explicitly confirms the saved eligible decision and
never changes it implicitly. Missing confirmation fails. Editors cannot assign
risk eligibility through protocol/item save input. Risk is included in submission
and approval snapshots, including submitted-content display. Later material edits
invalidate approval; retained decisions must be explicitly reassessed/confirmed
for the new revision. Existing approvals with the old snapshot shape cannot pass
the new publication hash/risk gate and need fresh review; no approval is fabricated.

Withdrawal remains distinct from unpublished. A withdrawn protocol cannot be
submitted, approved or published directly. A substantive governed edit/reference
change enters an unpublished draft revision, clears approval/locales and retains
withdrawal history. A no-op edit leaves withdrawal intact. The revision then
requires submission and independent approval before a separate publication command.
Source invalidation continues to withdraw published content; it does not restore it.

Disease and comparison knowledge now use the same `DiseaseService` public-treatment
boundary before reaching a view or comparison builder. Under hold, treatment
arrays/counts are empty. Premature governed configuration also returns empty arrays
until a canonical reader exists. No comparison-specific predicate, treatment
comparison content, public reader, API or publication service is introduced.
Phase 2C-4 must extend this shared boundary with one canonical eligibility service.

#### Schema changes to apply separately after developer review

`database/structure.sql` adds the risk ENUM/default, extends the event ENUM and
adds risk-event integrity CHECKs, including the Phase 2C-3B-L1 stored-value and
snapshot relationship checks below. No database connection or schema application
is part of Phase 2C-3B. The corresponding upgrade statements below are documentation,
not a migration or executed SQL; existing data receives `not_assessed`, never eligible.

```sql
ALTER TABLE treatment_protocols
  ADD COLUMN risk_eligibility ENUM('eligible','not_eligible','not_assessed')
  NOT NULL DEFAULT 'not_assessed' AFTER approved_review_id,
  ADD CONSTRAINT chk_treatment_risk_value CHECK (
    risk_eligibility IS NOT NULL AND risk_eligibility IN ('eligible','not_eligible','not_assessed')
  );
ALTER TABLE treatment_protocol_events
  MODIFY COLUMN event_type ENUM('legacy_initialized','content_changed','submitted','approved','changes_required','review_reset','published','withdrawn','publication_locales_changed','source_invalidated','risk_assessed') NOT NULL,
  ADD CONSTRAINT chk_treatment_event_type CHECK (
    event_type IS NOT NULL AND event_type IN ('legacy_initialized','content_changed','submitted','approved','changes_required','review_reset','published','withdrawn','publication_locales_changed','source_invalidated','risk_assessed')
  ),
  ADD CONSTRAINT chk_treatment_risk_event CHECK (
    event_type <> 'risk_assessed' OR (
      independence_confirmed = 1 AND content_snapshot IS NOT NULL
      AND content_hash IS NOT NULL AND notes IS NOT NULL
      AND CHAR_LENGTH(TRIM(notes)) > 0
    )
  ),
  ADD CONSTRAINT chk_treatment_risk_payload CHECK (
    event_type <> 'risk_assessed' OR COALESCE(
      JSON_TYPE(JSON_EXTRACT(content_snapshot, '$.protocol.risk_eligibility')) = 'STRING'
      AND BINARY JSON_UNQUOTE(JSON_EXTRACT(content_snapshot, '$.protocol.risk_eligibility')) IN ('eligible','not_eligible','not_assessed')
      AND JSON_TYPE(JSON_EXTRACT(content_snapshot, '$.protocol.id')) = 'INTEGER'
      AND JSON_EXTRACT(content_snapshot, '$.protocol.id') = protocol_id
      AND JSON_TYPE(JSON_EXTRACT(content_snapshot, '$.protocol.content_revision')) = 'INTEGER'
      AND JSON_EXTRACT(content_snapshot, '$.protocol.content_revision') = content_revision, 0)
  );
```

Existing constraints and signed INT relationships are preserved. The new DDL was
statically inspected in Phase 2C-3B; Phase 2C-3B-L1 adds explicit non-strict stored
value checks and risk snapshot protocol/revision/decision checks. Deployment must
apply the additive schema before using the changed application. Do not import the
full destructive schema dump into an existing database. Review operational
qualification grants and obtain explicit Phase 2C-4 authorization before publication.

Before applying this upgrade to an existing database, verify that event_type has
no empty/invalid stored values, and validate all proposed risk_assessed snapshots
against the new payload check. The new risk column starts at not_assessed. If a
deployment already has the Phase 2C-3B column/event extension, apply only the three
new named CHECKs after validating existing risk values and risk-event payloads;
do not repeat ADD COLUMN or the existing chk_treatment_risk_event. Adding a CHECK
validates stored rows and must fail on violations. Inspect violations and stop for
manual review; never silently rewrite/delete data or change SQL mode. Retain all
historical events whose snapshots carry their own event protocol/revision; the
payload check compares against the event revision, not today's protocol revision.
Hash format, JSON validity and existing risk metadata checks remain unchanged.
MariaDB ALTER TABLE implicitly commits; run all data preflight checks before the
first upgrade statement. A later failure can leave an earlier ALTER applied.
Inspect actual metadata before resuming and do not blindly rerun ADD COLUMN or
ADD CONSTRAINT. This documented upgrade is for the pre-2C-3B baseline, not an
idempotent migration or a production-data remediation operation.

Completed isolated database/concurrency/HTTP/browser validation is recorded in
[the final validation report](TREATMENT_PHASE_2C_VALIDATION_FINAL.md). Its deferred
forced-deadlock, editor/source-withdrawal race and native-browser stale/forged-form
tests remain deferred and are not expanded by this policy/UI task.
