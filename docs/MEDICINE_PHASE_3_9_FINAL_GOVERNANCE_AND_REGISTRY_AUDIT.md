# Medicine Phase 3.9 — Final governance and live registry audit

Date: 2026-10-09. Audit result: **PASS WITH LIMITATIONS**. Governance readiness: **BLOCKED**. Activation readiness: **NOT READY**.

Only this report is retained from this phase. Application/schema/configuration/test-suite files were not changed. Shared Medicine creation/publication and Treatment HOLD remain closed/unchanged. Policy recommendations below are not owner/medical-governance approvals.

## 1. Executive Summary

Live MariaDB is now available. SELECT-only census of the configured normal database `medical1` found **2,224 substances**, all active/searchable, all `CANDIDATE`/`UNKNOWN`, all claiming the default `WHO_INN`, none with a nonblank verification source or reviewer/date. Exact stored-name collisions were absent; aggressive formatting triage flagged **18 groups / 39 rows**, without establishing chemical equivalence.

All ten Phase 3.4/3.8 Medicine foundation/governance tables are absent from the live database. There are **3 private medication records**, with no deployed authoritative canonical/substance-link path. Existing regression suites pass **766 cases/checks**. Two additional memory-based audit probes exposed a withdrawal-restoration bypass and reactivation of old approval after an unrecorded registry flag round trip. No production approval was opened and neither finding was fixed in this audit.

Real activation requires separately reviewed deployment, correction of the lifecycle bypass, an approved representative/evidence enforcement contract and actual owner decisions on administrators, qualifications and sources. Public publication remains separately out of scope.

## 2. Phase 3.8 Carry-Forward State

Reviewed the requested Phase 3.1–3.8 documents and corresponding current code/schema. Phase 3.8 added persisted capability/scope/qualification grants, immutable revision-bound events, current registry assessments and common dependency eligibility. `MedicineAuthorizationService::requireCatalog()` still always denies `register_reference`, `create_identity` and `create_presentation`. `MedicineGovernancePolicy` still denies administration, qualification and evidence approval in production. Publisher grants have no publish operation.

The earlier synthetic/schema validations do not imply deployment or live scientific approval. Phase 3.8's restoration statement requires qualification: direct restoration is blocked while withdrawal/supersession is the latest event; section 18 identifies a sequence that bypasses this condition.

## 3. Live Database Availability

Read-only process/listener inspection found mysqld PID 5256 listening on local port 3306. The existing Database class connected using existing configuration internally; no credentials were printed or changed. Server-reported version **10.4.27-MariaDB**, selected database **medical1**, port **3306**, SQL mode **NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION**.

Initial census completed 2026-10-09 07:22:09 UTC; final expanded census completed **07:26:19 UTC**. Repeated substance totals/distributions matched. Every SQL statement sent by the transient census helper was a guarded SELECT, including information_schema inspection; no session/transaction/mode/locking/DDL/data-write command was sent. No application bootstrap, session or log initialization. No server was started or stopped; the user's normal server remains running.

These are separate SELECT observations, not a transactionally frozen multi-table snapshot. Stable repeated totals do not prove that no concurrent semantic edit occurred.

## 4. kobi_substances Census

Every count below is from live `medical1.kobi_substances`; SUM/COUNT predicates are specified so administrative flags are not confused with verification.

| Measure | Count | Source field/predicate |
| --- | ---: | --- |
| Total | 2,224 | COUNT(*) |
| Active / inactive | 2,224 / 0 | is_active=1 / =0 |
| Searchable / non-searchable | 2,224 / 0 | is_searchable=1 / =0 |
| Invalid activity / search flags | 0 / 0 | respective flag NOT IN (0,1) |
| Complete recorded review / incomplete or missing review | 0 / 2,224 | reviewed_by AND reviewed_at non-NULL / either NULL |
| Reviewer present / absent | 0 / 2,224 | reviewed_by non-NULL / NULL |
| Review date present / absent | 0 / 2,224 | reviewed_at non-NULL / NULL |
| Nonblank verification source / missing or blank | 0 / 2,224 | NULLIF(TRIM(verification_source),'') non-NULL / NULL |
| NULL canonical name / blank after TRIM | 0 / 0 | canonical_name IS NULL / TRIM(name)='' |
| NULL normalized name / blank after TRIM | 0 / 0 | canonical_name_normalized IS NULL / TRIM(normalized)='' |
| Control-character canonical names | 0 | canonical_name REGEXP '[[:cntrl:]]' |
| Inactive but searchable | 0 | is_active=0 AND is_searchable=1 |
| Reviewers missing/inactive in users | 0 | reviewed_by non-NULL, LEFT JOIN users.id; missing or users.status<>'active' |

The last zero is vacuous because all reviewed_by values are NULL, not evidence of qualified reviewers. Reviewed/unreviewed above measures tuple presence only.

Binary-grouped distributions preserve exact spelling/case:

| Source field | Only observed value | Count |
| --- | --- | ---: |
| kobi_substances.identity_status | CANDIDATE | 2,224 |
| kobi_substances.identity_type | UNKNOWN | 2,224 |
| kobi_substances.naming_standard | WHO_INN | 2,224 |

No additional legacy codes appeared; all identity types nevertheless remain semantically unknown. Live metadata confirms VARCHAR status/type, defaults CANDIDATE/UNKNOWN/WHO_INN, utf8mb4_unicode_ci name columns, PK, `uq_substance_canonical_normalized`, active/search/name index and status index. There is no live registry reviewer FK or governed qualification/independence/evidence constraint.

## 5. Registry Quality Findings

| OBSERVED DATABASE FACT | PROPOSED GOVERNANCE INTERPRETATION |
| --- | --- |
| All 2,224 rows are CANDIDATE/UNKNOWN with no recorded source/review | All require identity/type/evidence assessment before canonical trust; administrative visibility is insufficient |
| 18 formatting triage groups affect 39 rows | Suspected duplicate/ambiguous queue; unresolved pending evidence, no merge |
| No inactive rows or alternate status codes observed | No current recorded inactivation/withdrawal signal found; not proof of absence of real-world withdrawal |
| Empty form/component/review/WHO-evidence tables | No stored form relationship or these structured evidence/review records available for reuse |
| All legacy alias/combination/item statuses PENDING | Unreviewed import scaffolding, not approved canonical content |

The categories overlap; the 39 flagged rows are a subset of the 2,224 candidate rows. No category was persisted. No row is certified a verified representative, suitable active ingredient or medically approved merely by this audit. No authoritative source claim was independently verified. The actual data supplies no defensible medically verified-candidate population.

`SubstanceRepository::search()` checks only activity/searchability/prefix. `is_searchable` means autocomplete visibility; `is_active` is an operational gate. `verification_source` is optional text; `reviewed_by/at` are unqualified/unbound attribution. `WHO_INN` is a default claim. These fields neither establish clinical effectiveness nor independently establish chemical identity.

## 6. Duplicate Analysis

Counts derive from the 2,224 live `kobi_substances` name/normalized-name rows, with IDs retained solely for registry triage. No private names were fetched.

| Method | Groups | Rows in groups |
| --- | ---: | ---: |
| Byte-identical canonical_name | 0 | 0 |
| Byte-identical canonical_name_normalized | 0 | 0 |
| Normalized-name GROUP BY under deployed collation | 0 | 0 |
| Canonical name Unicode lowercasing only | 0 | 0 |
| Canonical name whitespace trim/collapse only | 0 | 0 |
| NFC + Unicode case fold + whitespace trim/collapse | 0 | 0 |
| Aggressive punctuation/all-separator removal after NFC/case fold | 18 | 39 |

The last algorithm is **triage only**, deliberately more aggressive than the approved canonicalization contract. It can erase scientifically meaningful punctuation; it must never supply equality keys, overwrite normalized names or approve a merge. Existing normalized names all matched lowercased trim/whitespace-collapsed labels (0 mismatches); this does not establish normalization provenance or correct scientific spelling.

Public-registry examples of flagged groups: IDs 65/66 `Alpha Glycerylphosphoryl Choline` / `Alpha Glycerylphosphorylcholine`; 330/331 `Carboxymethyl Cellulose` / `Carboxymethylcellulose`; 1134/1151 `L Glutathione` / `L-Glutathione`. They are observed strings, not asserted equivalent substances.

Salt/base, distinct forms and historical synonyms remain unresolved because naming patterns cannot establish relationships. Legacy combinations were also inspected by their explicit `kobi_combination_items.substance_id` sets: **1 repeated-set group / 2 combination records**, and **4 combinations with repeated member IDs**. These are structural findings, not approved deduplication. Repeated membership violates the initial unique-active-member intake contract and needs reviewed correction; repeated sets may denote different presentations/products and cannot be merged by this audit.

## 7. Chemical-Form Analysis

Live `COUNT(*)`: `kobi_substance_forms` **0**, `kobi_form_components` **0**. No rows supply typed form/parent/stoichiometry evidence. All `kobi_substances.identity_type` values are UNKNOWN. No canonical adapter table is deployed. The registry therefore cannot currently establish base/salt/hydrate/solvate/stereoisomer taxonomy or S/F representative relationships.

Lexical observations over `kobi_substances.canonical_name`, solely potential review flags: **205** rows contain selected salt-word tokens; **10** contain `hydrate`; **0** contain `solvate`; **38** match selected stereochemistry-like markers; **0** contain + or /. Predicates are recorded in section 24. Categories overlap, are incomplete, and are not counts of actual salts/hydrates/stereoisomers or combination substances. Absence of a word does not establish base identity or absence of that chemical form.

Phase 3.8 snapshots can safely retain an explicit observed row and its IDs without certifying meaning. Future S(id) verification requires evidence that the row denotes the exact intended supported entity. Future F(id) verification requires an independently established form and parent; there are currently no such form rows. Nothing can be reconciled automatically or approved from names/defaults alone. No chemical relationships or conversion factors were inferred.

## 8. Canonical Medicine Census

Information_schema.COLUMNS/TABLE_CONSTRAINTS inspection found these **absent**, not deployed-empty:

- kobi_medicine_chemical_refs
- kobi_medicine_identities
- kobi_medicine_ingredients
- kobi_medicine_presentations
- kobi_medicine_strengths
- health_medicine_identity_links
- health_medicine_selected_ingredients
- medicine_authority_grants
- medicine_governance_events
- medicine_registry_assessments

Their row counts/lifecycle distributions are **N/A: table absent**, not invented zero COUNT results. The two Medicine immutability triggers are also absent. The source dump defines these objects; normal deployment has not applied them. No existing live record can be certified governed under the complete Phase 3.8 chain.

Related legacy objects, each measured with COUNT(*) and binary status grouping:

| Source table | Rows | Observed state |
| --- | ---: | --- |
| kobi_substance_aliases | 2,224 | review_status=PENDING for all |
| kobi_combinations | 2,327 | review_status=PENDING for all |
| kobi_combination_items | 6,671 | review_status=PENDING for all |
| kobi_identity_reviews | 0 | No decisions |
| kobi_who_inn_evidence | 0 | No structured naming evidence |

All **6,671** legacy items have substance_id; **0** have form_id, neither ID, both IDs or nonblank strength_text; **1,265** distinct substance IDs are referenced (COUNT DISTINCT). All **2,327** combinations have **2–12** item rows; none is single-member or exceeds 16 (group by combination_id). These are referential/size facts, not complete reviewed presentation data; strengths/form/route/release evidence is missing.

Inactive substance references in live aliases/forms/combination items/WHO evidence: **0 referring rows / 0 distinct inactive substances** in each, joining respective substance_id to kobi_substances.is_active=0. Empty form-components/review tables cannot contain inactive references. Medicine/private selected-reference paths are absent and were not queried as though deployed.

## 9. Personal Medication Census

Only aggregate health_medicines SELECTs and schema metadata were returned; no patient/profile/user IDs, names, notes, dates or dose contents were exported.

| Measure | Result | Source/basis |
| --- | ---: | --- |
| Total medication records | 3 | health_medicines COUNT(*) |
| Free-text medicine name present | 3 | nonblank brand_name OR generic_name |
| Text strength present / absent | 1 / 2 | nonblank strength / total minus present |
| Text route present / absent | 3 / 0 | nonblank route / total minus present |
| Structured canonical strength | 0 representable records | No structured strength field/canonical sidecar/presentation tables deployed |
| Dosage-form information | No dedicated field; 0 structured records | information_schema confirms no dosage_form_code/form field; other text not interpreted |
| Records with persisted authoritative substance link | 0 | No substance_id column or deployed adapter/selected sidecar path |
| Records without that authoritative link | 3 | Total plus absence of all supported reference paths |
| Current / stopped | 1 / 2 | health_medicines.status binary grouping |
| Proven automatically linkable records | Not established | No authoritative identity/provenance; no name mapping attempted |

All 3 are currently unresolved for authoritative canonical linkage, a proposed linking-workflow interpretation rather than a new patient lifecycle status. Text strength and route do not prove presentation eligibility. Potential reviewer-assisted linking remains unmeasured; no patient-level clinical assessment was made or legacy record linked/rewritten.

## 10. Administrator Policy

Existing users has active/inactive state but no general Medicine administrator role. Profile roles govern private access. Treatment uses its separate deployment-controlled permission-admin allowlist and scoped grants; those do not authorize Medicine.

**OPEN POLICY DECISION:** owner must designate actual authorized administrators, issuance/revocation process and global-versus-scoped administrative reach. Recommended initial option: a separately approved, minimal Medicine-only deployment-controlled administrator allowlist, following the existing Treatment policy pattern without reusing its people/permissions. Keep initial grant administration catalog-wide only if explicitly approved; narrower administrative scopes require an explicit contract. No real user nominated here.

Approved administrators may grant/revoke only approved capability/scope/qualification combinations with active recipients, bounded expiry and mandatory rationale. They do not gain editorial/reviewer/publisher privileges automatically, cannot self-grant through grantAuthority, and cannot approve their own contributions. Grant/revocation events record actor/time/subject; changes to administrator configuration also need accountable deployment change records. Removal denies future administration and must coordinate any separately held grants. Administrator-removal automation is not implemented or presumed.

## 11. Reviewer Qualification Policy

Technical minimum: explicit active reviewer grant, approved competence scope/qualification attestation, valid dates, active user, independent of creator and all recorded substantive contributors/editors, identity-specific evidence and accountable revision-bound decision. Revocation/expiry denies new decisions and current-use eligibility while preserving historical proof. No admin/email/profile/Treatment status substitutes.

**OPEN POLICY DECISION:** credential issuer/assessor, accepted qualifications, competence scopes, conflict rules, validity/renewal and adverse credential handling require owner/medical approval. Recommended option: independently attested scope-limited qualifications with expiry and recorded assessor; credential assertions must be checked against approved policy, not merely nonblank text. No professional credential threshold is invented. `requireQualification()` remains denying until approved implementation exists.

## 12. Evidence Policy

Identity evidence proves a specified identity fact; therapeutic evidence evaluates effectiveness and cannot be inherited. Minimum technical expectations follow the existing design: traceable issuer/record/version/locator/content digest, explicit supported facts, conflict resolution, target/revision/dependency binding and accountable independent appraisal. A valid URL/hash or source name alone is insufficient.

| Fact | Required identity-evidence coverage, subject to approved source policy |
| --- | --- |
| Substance/chemical identity | Exact entity scope and supported identity, not label similarity |
| Chemical form | Exact salt/hydrate/solvate/stereochemical identity and explicit parent relation; no equivalence/conversion inference |
| Composition | Complete distinct active set and intended combined composition |
| Strength | Every ingredient's exact amount/unit/denominator and measured chemical basis |
| Dosage form | Explicit supported pharmaceutical form |
| Route/release | Explicit supported route/release attributes, no guessed defaults |
| Presentation | Consistent complete composition/strength/form/route/release vector |

**OPEN POLICY DECISION:** accepted issuers/source classes, hierarchy, sufficient fact-specific evidence, conflict/freshness/retraction rules and retention rights. Recommended option: owner-approved fact-specific source policy with versioned traceability and an explicit current-validity check; naming provenance alone should never cover all form/product facts. These are proposals, not accepted external authorities or clinical thresholds. Keep production `requireEvidence()` closed. Policy must receive sufficient target/snapshot context to verify what the source actually supports (gap E39-4).

## 13. Representative/Approval Policy

Use one independently evidenced representative S(id) or F(id) per exact entity. Preserve original registry/history; equivalent-entity representative selection is a governed decision, never a name merge or salt/base inference. Current per-target UNIQUEs do not establish cross-ID/cross-S-F semantic uniqueness.

`verified` means qualified independent acceptance of the specific revision. Internal `approved` should be the derived predicate of current verified decision, eligible dependencies, supported scope and no unresolved/adverse condition; it is not a new identity_status or independent administrative shortcut. Trusted compositions require separately current chemical and composition approvals; presentations require their own review too.

**OPEN POLICY DECISION:** accountable chemical-equivalence/representative assessor, fact-specific approval sufficiency and conflict handling. Recommended option: independent reviewer confirms exact entity/representative before adapter verification, with recorded evidence and explicit conflict disposition. Existing generic rationale/snapshots can retain proof but do not yet enforce representative selection across possible overlaps. No schema expansion is authorized here.

Future public publication additionally needs a separately approved publisher/content/clinical/source/locale/current-read contract. Neither reviewer nor administrator approval publishes anything; that future workflow remains absent and disabled.

## 14. Replacement/Restoration Policy

Fixed design boundaries: identity-defining tuples remain immutable; corrected ingredient/chemical/strength/form/route/release tuples require separately governed new identities/presentations. Old keys, references, private observations and events remain reserved/preserved. Retired targets are terminal. No automatic patient relink or public restoration.

**OPEN POLICY DECISION:** whether any withdrawn/superseded target may ever be restored, who authorizes it and what evidence establishes correction/replacement. Recommended initial option: forbid withdrawn/superseded restoration until a dedicated approved transition exists; any later permitted restoration requires explicit rationale, new revision, fresh evidence and independent review. Temporary invalidation may be resolved only through fresh assessment/review according to approved policy. Old publication can never revive automatically.

Current metadata-only edit/re-review operations already increment revision. No replacement-link workflow exists. `invalidate()` can overwrite a latest withdrawal with an invalidated event and thereby bypass the latest-only restoration guard; this must be corrected before activation, regardless of who later approves restoration. No restoration policy was invented or code changed here.

## 15. Invalidation Policy

| Event | Actual Phase 3.8 current-use behavior / requirement |
| --- | --- |
| Inactive substance/form/parent | Current snapshot rejects; original historical links/decisions retained |
| Governed withdrawal/supersession | Target nonverified and adverse assessment/latest event deny use; direct restoration blocked, but sequence gap exists |
| Unresolved ambiguity | unresolved assessment cannot be verified directly; dependencies fail current gate |
| Invalidated identity/evidence | Steward adverse decision increments revision and blocks acceptance; fresh approved evidence/review needed |
| Reviewer revoked/inactive/expired | Current reviewer chain rejects; events retain historical proof |
| Incorrect composition/presentation | Invalidate/retire old target; immutable tuple correction needs separate reviewed target, no FK rewriting |

Historical decision proof is not a current-use permission. Recommended policy requires no automatic restoration after adverse registry/evidence events. A raw registry inactive→active round trip restoring identical facts is not detectable by present-state hashing: synthetic probe confirms old acceptance returns if no governed adverse event was recorded. Coordinated external/manual writer invalidation or another reviewed mechanism is therefore required before adopting that policy; this audit neither changed writers nor established their ownership.

## 16. Final Authority Contract

The final technical contract is fail-closed and preserves existing architecture:

1. Actual active authenticated actor; explicit persisted exact capability/scope/expiry/revocation checks.
2. Approved administration and qualified independent reviewer policy, with no profile/admin/Treatment shortcut.
3. Explicit eligible chemical IDs and approved unambiguous representatives; identity-defining tuples immutable.
4. Fact-specific current evidence and dependencies bound to exact revision/key/snapshot.
5. Expected revision/digest, same-transaction locking, complete aggregate persistence and immutable event.
6. Adverse lifecycle history cannot be neutralized by unrelated subsequent adverse commands; retirement terminal.
7. Private profile authorization remains separate and old records remain intact.
8. Publication uses a separately authorized future gate; missing policy/deployment/infrastructure denies.

Organizational/medical choices remain OPEN. This contract specifies technical requirements, not personnel/source certification or activation permission.

## 17. Shared-Write Activation Gate

Limited shared writes require ALL: owner-approved activation scope/actions; deployed verified metadata; approved administrator/grant and contributor; eligible supported unambiguous references; approved fact-specific evidence; qualified independent reviewer available; current revisions/dependencies; complete audit/transaction behavior; valid lifecycle and durable invalidation; approved representative conflict handling; monitoring and explicit failure/rollback procedures. Any unknown condition denies. Candidate creation is not verification and neither is publication.

No actor may create shared canonical records now. If eventually approved: only specifically granted contributors may register existing explicit references/create complete candidate compositions/presentations; reviewers independently verify within competence; internal approval remains a current derived predicate. Unsupported/ambiguous/duplicate/form-unresolved inputs remain denied. Initial monitoring should inspect denials, failed transactions, review freshness/adverse events and representative conflicts without private medication payloads. No automatic imports, user grants, personal migration or public output.

## 18. Phase 3.8 Implementation Gap Analysis

| ID / classification | Evidence and effect | Required next step |
| --- | --- | --- |
| D39-1 — schema/deployment gap | Ten required tables and two audit triggers absent from normal metadata | Separately approved additive deployment, verify original objects/new constraints; never reimport full destructive dump |
| P39-1 — unresolved policy / configuration-policy implementation | Production admin/qualification/evidence methods always deny; real personnel/source criteria unknown | Owner decisions, then minimal approved policy implementation; not simply toggling existing config |
| E39-1 — implementation gap, reproduced | checkedSnapshot checks only latest withdrawn/superseded event; invalidate accepts subsequent invalidated state | Enforce durable adverse transitions and add regression for the multi-command bypass |
| E39-2 — implementation/integration gap conditional on restoration policy | Unrecorded inactive→active with identical facts restores old current review | Approve durable invalidation coordination for registry writers; require fresh decision after adverse change |
| E39-3 — unresolved policy plus implementation gap | One adapter per target and typed composition hash do not enforce one representative per scientific entity | Approve evidence-backed representative conflict contract and add bounded target-aware enforcement before real verification |
| E39-4 — implementation gap for fact-specific evidence contract | requireEvidence(evidence, scope) receives no reviewed target/snapshot; fields and stored adjacent snapshot alone cannot prove source supports that target | Approve source policy, then provide target/fact context and reject unrelated valid identity evidence |
| S39-1 — already sufficient | Scoped grants, independence history, revision/digest, current dependency recursion, transaction rollback, event triggers | Preserve and extend targeted tests |
| S39-2 — already sufficient safety boundary | requireCatalog always denies; no publish operation/routes | Keep closed; limited activation later needs explicit approved action mapping |
| L39-1 — documentation/data-review only | Legacy flags/defaults/format collisions/combinations are not verification | Preserve registry, review cases individually; no automatic corrections |
| F39-1 — deferred future publication, not this activation prerequisite | No Medicine clinical/public content workflow | Separate later design; do not treat publication as a missing identity-status field |

No new schema is demonstrated necessary to correct E39-1: immutable existing events can supply durable transition context. Storage for an approved representative/replacement process remains an open design question; no speculative CREATE/ALTER proposal.

E39-1 reproduction used only existing memory fixtures and synthetic policy: verify chemical ref 51 → steward withdrawal → steward invalidated event → contributor candidate reassessment → independent fresh review → `requireCurrent` **allowed**. Direct restore tests in the existing suite do not cover the intermediate overwrite. The [latest-only guard](D:/xampp/htdocs/kobi/app/Services/MedicineGovernanceService.php:63) and [adverse command](D:/xampp/htdocs/kobi/app/Services/MedicineGovernanceService.php:110) establish the cause. Production qualification/evidence remain denied, so this is a latent activation blocker, not a current normal-database exploit demonstrated here.

E39-2 reproduction: verified synthetic ref → underlying fixture inactive (current gate denied 409) → same row active with original facts, no adverse event → old current decision **allowed**. Snapshots detect present differences, not missing historical transitions. Neither probe executed a database write.

## 19. Security Review

Existing database-free suites confirm denial for ordinary/no-grant users, unknown capability, wrong scope, inactive/revoked/expired authority, missing qualification, self/all-contributor review, missing/therapeutic evidence, stale revision/snapshot, unresolved/inactive/withdrawn target and dependency drift. Private profile scope checks and raw-data preservation are exercised in the foundation suite. Audit-fault tests distinguish safe 503 storage errors from 403/409/422 denials and verify rollback, not exception-to-success.

Security qualification: E39-1 and E39-2 are additional negative-path counterexamples, not passing safeguards. Ambiguity denies when explicitly unresolved or when real production policy is closed; representative correctness is not automatically inferred from legacy type/name. No live user was granted authority or patient access. No normal governance service was invoked against absent tables to fabricate trust.

Existing public autocomplete still exposes active searchable registry labels, including candidates. That preexisting discovery endpoint is not canonical Medicine publication and was not altered. No new governed Medicine public route/projection exists. Treatment HOLD checks pass; source/configuration unchanged.

## 20. Activation Readiness

**NOT READY. Governance readiness: BLOCKED.** The real census is complete, but no approved source/qualification/admin/representative policies, no normal schema deployment and an actual adverse lifecycle gap prevent limited activation. Ready-with-conditions would obscure these unresolved essentials. Nobody is authorized to create/review/approve new live canonical records by this audit.

Genuine blockers: D39-1, P39-1, E39-1 and unimplemented target/evidence/representative enforcement for the approved trust contract. Durable registry-writer invalidation must also be resolved to uphold the proposed no-auto-restoration policy. Non-blocking deferred scope: public publication, commercial catalog, patient UI/link migration, broad importer/load/browser testing and unrelated Phase 3.1 fixes.

## 21. Phase 3.10 Recommendation

Recommend a narrowly authorized **adverse lifecycle correction** first: prevent withdrawal/supersession from being cleared by another adverse command, preserve history/keys and test both chemical and composition/presentation sequences. Keep production policy and catalog/public gates denied. This is justified by an actual reproduced gap, not speculative architecture work.

In parallel as owner decisions, approve administrator/qualification/source/representative/restoration rules. Then separately design the smallest target-aware evidence/representative and registry-writer invalidation changes those decisions require. Review/apply the existing additive schema only with separate database authorization; compare metadata and stop on mismatch. Do not activate all candidates, auto-merge the 18 flagged groups, import combinations, grant users for testing or publish anything.

## 22. Open Policy Questions

All are **OPEN POLICY DECISION**, requiring explicit owner/medical governance approval:

- Which actual people administer Medicine grants; global/scoped reach; who audits administrator-config changes/removal?
- Who attests reviewer credentials, accepted qualifications/competence, expiry/renewal and conflicts?
- Which fact-specific identity sources, versions/hierarchy/freshness/retraction/retention rules are sufficient?
- Who approves one precise S/F representative and resolves overlapping/ambiguous/imported records?
- Is withdrawal/supersession restoration ever permitted; who authorizes it; what replacement relationship and new review are required?
- Who owns external/manual registry writes and records durable invalidation before activity/evidence can return?
- What exact limited candidate actions/records/monitoring may later be activated?

No answer is supplied by defaults, admin convenience, real user names or synthetic test qualification.

## 23. Limitations

Registry census is real; chemical facts are not certified. Lexical/formatting signals are intentionally imperfect and overlapping. No accepted external identity documents or clinical evidence were fetched/appraised. No professional qualifications were assessed. Personal census is aggregate-only, not a clinical linkage assessment. Metadata absence is not a zero-row query. Repeated reads are not an atomic snapshot. External importers/manual writers were not observed/audited.

No code fix, normal schema upgrade, real authority issuance, candidate creation, source review or browser test was performed. Prior Phase 3.4/3.5/3.8 live synthetic results are historical evidence only; those live mutation suites were not rerun. No disposable DB was needed because this phase's new behavior findings reproduce entirely in memory and existing DB semantics are unchanged.

## 24. Validation

Actually rerun:

| Suite | Cases/checks | Assertions | Result |
| --- | ---: | ---: | --- |
| tests/medicine-governance.php | 46 | 125 | PASS |
| tests/medicine-foundation.php | 105 | 216 | PASS |
| tests/treatment-operations.php | 127 focused + 443 retained = 570 | Not separately counted | PASS |
| tests/auth-phase1.php | 45 | Not separately counted | PASS |

**766 existing cases/checks passed**, zero regression failures. Two separate audit probes reproduced adverse behaviors E39-1/E39-2; they are not counted as passing security tests. Existing live Medicine harnesses were inspected but not run against normal MariaDB. No app PHP changed, so application syntax verification was not required. Temporary audit helpers executed successfully and were removed after reporting.

SELECT-only census used existing Database/autoload/environment loading without bootstrap or credential output. It guarded every prepared query to SELECT and rejected multi-statements, INTO, locks and known delay functions. Queried information_schema.COLUMNS, TABLE_CONSTRAINTS, STATISTICS and TRIGGERS plus fixed aggregate/public-registry reads. Final counts agreed with earlier passes; mode before/after was unchanged. Each table count is COUNT(*) only when metadata confirmed presence.

Core reproducible census predicates (read-only, no inferred counts):

```sql
SELECT COUNT(*) total,
 SUM(is_active=1) active, SUM(is_active=0) inactive,
 SUM(is_searchable=1) searchable, SUM(is_searchable=0) non_searchable,
 SUM(reviewed_by IS NOT NULL AND reviewed_at IS NOT NULL) recorded_review,
 SUM(NULLIF(TRIM(verification_source),'') IS NOT NULL) nonblank_source
FROM kobi_substances;

SELECT BINARY identity_status observed_value, COUNT(*) n
FROM kobi_substances GROUP BY BINARY identity_status;
-- Same fixed template for identity_type and naming_standard.

SELECT COUNT(*) groups_found, COALESCE(SUM(n),0) affected_rows
FROM (SELECT COUNT(*) n FROM kobi_substances
      GROUP BY BINARY canonical_name HAVING COUNT(*)>1) g;

SELECT COUNT(*) groups_found, COALESCE(SUM(n),0) affected_rows
FROM (SELECT COUNT(*) n FROM kobi_substances
      GROUP BY canonical_name_normalized HAVING COUNT(*)>1) g;

SELECT COUNT(*) total,
 SUM(NULLIF(TRIM(brand_name),'') IS NOT NULL
     OR NULLIF(TRIM(generic_name),'') IS NOT NULL) with_name,
 SUM(NULLIF(TRIM(strength),'') IS NOT NULL) with_text_strength,
 SUM(NULLIF(TRIM(route),'') IS NOT NULL) with_text_route
FROM health_medicines;
```

External in-memory public-label triage used PHP intl NFC, mb_case folding and Unicode whitespace collapse. Aggressive review-only key removed `[\p{P}\p{Z}\s]+`; no key was persisted. Salt-token regex: word-boundary hydrochloride/hydrobromide/sodium/potassium/sulfate/sulphate/phosphate/acetate/citrate/maleate/tartrate/mesylate/besylate/succinate. Hydrate/solvate tests were substring searches; stereo-like regex matched levo/dextro/dex/racemic word prefixes, (R)/(S), D-/L-; +/ was a lexical combination hint. These methods flag spelling only, never chemical relationships. Combination signatures sorted unique stored substance IDs per combination; repeated-membership checks occurred before deduplication.

Task-start SHA-256 baseline covered **288 existing files** across app/public/tests/config/bootstrap/database/routes/docs. Final comparison found zero existing changes/removals and this requested report only added. `git diff --check` and section/link validation passed. No normal DB writes, schema/mode/config changes, authority grants, private migration, shared activation, public Medicine exposure or Treatment HOLD change. No disposable DB. No commit/push/reset/checkout/merge/rebase.

Files created: `D:/xampp/htdocs/kobi/docs/MEDICINE_PHASE_3_9_FINAL_GOVERNANCE_AND_REGISTRY_AUDIT.md`. Files modified: **none**. Transient census/reproduction PHP helpers under tests were removed and are not deliverables. Prior user changes remain intact.
