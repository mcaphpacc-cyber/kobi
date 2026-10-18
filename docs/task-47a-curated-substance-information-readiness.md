# Step 47A — Curated Substance Information Pilot Readiness

Readiness only. Inspected 14 October 2026. No implementation or publication authorized by this report.

## 1. Executive Summary

**READY WITH PREREQUISITE.** Recommend exactly **three** educational substance pages: Acetazolamide (15), Allopurinol (54), and Topiramate (2064). These are a proposed editorial set, not approved medicines or verified identities. Preserve the other 2,221 registry entries' current behavior.

The search infrastructure is usable. Qualified reviewers, current identity decisions and reviewed educational content are absent. Existing identity evidence cannot approve patient-facing prose. A focused extension of the existing Medicine services/policy and two additive content tables is needed; no new personnel/credential system, source-master system or broad Medicine rollout is justified. Public release must remain disabled until the prerequisites below are satisfied.

## 2. Current Medicine Architecture

Actual paths inspected: `database/structure.sql`; `app/Controllers/SubstanceController.php`, `AccountController.php`, `MedicineOwnerBootstrapController.php`; `app/Services/SubstanceService.php`, `MedicineIdentityService.php`, `MedicineCanonicalizer.php`, `MedicineProvisionalIdentity.php`, `MedicineGovernanceService.php`, `MedicineGovernancePolicy.php`, `MedicineEligibilityService.php`, `MedicineAuthorizationService.php`, `HealthMedicineService.php`, `DiseaseReferenceService.php`; `app/Repositories/SubstanceRepository.php`, `MedicineRepository.php`, `MedicineGovernanceRepository.php`; `app/Views/substances/index.php`, `app/Views/disease/show.php`; `public/assets/js/substance-search.js`, `health-medicine.js`; `routes/web.php`, `app/Config/config.php`, and the Phase 3.17 evidence manifest and Phase 3.20/3.22 deployment reports.

Registry: `kobi_substances`, `kobi_substance_aliases`, `kobi_substance_forms`, `kobi_form_components`, `kobi_combinations`, `kobi_combination_items`, `kobi_identity_reviews`, `kobi_who_inn_evidence`, and the `kobi_substance_search` union view. The public repository searches canonical master rows directly, not that alias union view. Combinations are legacy records, not verified composition identities.

Canonical Medicine: `kobi_medicine_chemical_refs` identifies a substance OR form; `kobi_medicine_identities` and `kobi_medicine_ingredients` represent compositions; `kobi_medicine_presentations` and `kobi_medicine_strengths` represent dosage-form/route/release/strength facts. There is no public brand-product catalog/detail route or patient-information content table in this implementation. Do not mistake composition/presentation display names for reviewed brand pages.

Governance: `medicine_authority_grants`, immutable `medicine_governance_events`, `medicine_registry_assessments`, `medicine_registry_lifecycle_events`, `medicine_pilot_records`, `medicine_provisional_identity_revisions`, `medicine_reviewer_qualifications`, `medicine_evidence_appraisals`. Personal records are separate: `health_medicines`, optional `health_medicine_identity_links` and `health_medicine_selected_ingredients`, plus the schedule tables.

Preserve controller → service → repository. Extend existing Medicine governance/authorization/eligibility services for the focused purpose; public SubstanceController consumes only a safe public projection. No SQL in views/controllers.

## 3. Current Public Medicine Behavior

`GET /medicines` renders `substances/index` with a search form. `GET /api/substances/search` invokes the anonymous `AccountController::searchSubstances()` → SubstanceService → SubstanceRepository path. Empty input yields `[]`; prefix search returns at most ten active/searchable canonical names, ordered by normalized name. Response rows contain `substance_id` and `canonical_name`.

The page expressly labels names as legacy registry information, without clinical review/KOBI product approval or prescribing recommendations. Results are text-only list items, with abort/version handling for stale requests and textContent rendering. No curated detail links or rich public Medicine projection currently exist. This finding is from source inspection, not a new browser exercise; the browser/session was untouched.

## 4. Existing Governance/Pilot Infrastructure

Read-only SELECT census of normal `medical1`, MariaDB **10.4.27**, on 14 October:

| Object/state | Actual count/state |
| --- | --- |
| Substances | 2,224; all CANDIDATE, active=1, searchable=1 |
| Aliases / forms | 2,224 / 0 |
| Combinations / items | 2,327 / 6,671 |
| Legacy identity reviews / WHO INN evidence | 0 / 0 |
| Chemical refs / identities / ingredients / presentations | 0 / 0 / 0 / 0 |
| Registry assessments / pilot records | 0 / 0 |
| Provisional revisions / reviewer qualifications / evidence appraisals | 0 / 0 / 0 |
| Authority / governance events | One active Account 1 administrator/catalog grant, NULL expiry, not revoked; one bootstrap event |
| Reviewer authority | None |

All five candidate rows have identity_type UNKNOWN, identity_status CANDIDATE, no description, no verification_source and NULL reviewed_at. No candidate has persisted WHO evidence; the globally empty governance tables establish absence of candidate reviews, appraisals, enrollment and canonical approval. Registry flags alone are not verification.

Configuration has `pilot_enabled=false`, `pilot_internal_enabled=false`, empty activation/code/substances, empty evidence_sources and reviewer_verifications, and no delegated operators. `activation()` requires at least five explicit targets and `public_disabled=true`. `internalPilot()` is an internal service projection, not a public publication mechanism. There is **no existing rich public Medicine release switch/projection to turn on**. “Public OFF” here means absent public projection and explicit internal policy prohibition, not a verified separate public boolean.

Reuse authority grants, qualified reviewer journals, identity currentness/lifecycle checks, source policy configuration, snapshot hashing, transactions and immutable decision conventions. Do not reinterpret provisional `accepted` or canonical `verified` as educational-content approval. The Phase 3.17 local manifest is PREPARATION_ONLY_NOT_APPROVED: five PubChem/DailyMed packages exist on disk. Calling the pure `package()` method confirmed the pinned manifest and ten retained digests; all five `identity_appraised=false`. Those files are not database evidence or permission to publish.

## 5. Candidate Evaluation

Assessments below are product/editorial judgments based on the inspected records and official sources in section 9, not clinical approvals. All five have clear familiar generic labels but exact chemical/form mapping still requires professional confirmation; previous stereochemical uncertainty for Colchicine/Topiramate is not resolved by name matching.

| Candidate | Sources and boundedness | Complexity/work | First-pilot decision |
| --- | --- | --- | --- |
| Acetazolamide, 15 | MedlinePlus patient monograph and current product label cover identity, uses, oral forms and safety | Medium-high: multiple uses, renal/electrolyte precautions; older patient-source revision and reproduction restrictions need attention | Include conditionally; no altitude dosing/prevention regimen or product-wide extrapolation |
| Allopurinol, 54 | Current NHS patient page covers uses, tablets, side effects and precautions | Medium: serious reactions still need prominent review; bounded overview feasible | Include; simplest editorial starting page of this set |
| Carbamazepine, 319 | Current NHS patient page covers uses/forms/safety | High: serious skin reactions, pregnancy, interactions and formulation distinctions make abbreviated education more demanding | Defer, not reject identity/source quality |
| Colchicine, 481 | Current NHS page covers gout/FMF, tablets and safety | High: toxicity and interaction context are easy to lose when shortened; generic mapping has stereochemical questions | Defer; higher misunderstanding risk for initial small set |
| Topiramate, 2064 | Current NHS page plus MHRA pregnancy safety update | High: pregnancy/regulatory context and safety communication require specialist scrutiny | Include conditionally to test one substantial warning page and connection to existing Migraine knowledge; never a “safer” drug claim |

No replacement candidate is necessary: adding a sixth candidate increases identity/source work without solving missing reviewers. Topiramate's inclusion is conditional on reviewer competence and adequate warning coverage; do not publish a third page merely to meet a quota.

## 6. Recommended Pilot Set

**3: IDs 15, 54 and 2064**, English only initially. Allopurinol first, Acetazolamide second, Topiramate last after specific regulatory review. The count tests a repeatable workflow with different content complexity, rather than maximizing registry coverage. No forms, salts, combinations, brands or presentations inherit these pages automatically. No currently selected substance is ready for public release today.

## 7. Public Information Structure

Fixed V1 sections: (1) About and general uses; (2) Forms and routes; (3) Common side effects; (4) Important precautions; (5) Important warnings and when to seek urgent help. Plus References, KOBI review date and concise disclaimer. Canonical name comes from confirmed identity; aliases are optional only if explicitly reviewed. Sections use short plain-text paragraphs/lists, not a dosage table. Every material claim must cite an approved source locator; no empty required section.

Exclude dosage amounts/frequencies, titration, missed-dose algorithms, personalized contraindication/interaction decisions, diagnosis, treatment selection/substitution, prescription interpretation, risk scoring and AI advice. General use descriptions must not become instructions to start/stop/change a medicine. Foreign emergency numbers and jurisdiction-specific regulatory programs require contextual review, not mechanical copying into an Indian product.

## 8. Source Policy

Primary support: current regulator safety communications and applicable official product labeling; national-health-service patient information; recognized institutional drug references where rights permit. Product labels support their exact jurisdiction/form/product facts, not every brand or every possible use. Government hosting alone does not prove independent authorship, unrestricted rights or WHO identity evidence tier.

Secondary: PubChem for chemical identifiers, professional/institutional summaries for corroboration, and the retained manufacturer-origin DailyMed packages for identity preparation. These cannot alone establish complete patient-facing sections. Search snippets, commercial pharmacy marketing, blogs, user-generated material, unsourced lists and AI text are unsuitable primary support.

Use the existing `medicine_governance.evidence_sources` policy namespace: explicit publisher/record/version/locator/hash, allowed purpose, currentness and rights. Extend it for `educational_information` alongside the existing **identity-only** policy; never auto-upgrade identity tiers. A shared source key resolves to one approved record, reused in section references; do not create a second source catalog.

## 9. Source Findings

Official pages opened during this task; discovery checked 14 October 2026. These are prospective sources, not accepted evidence. Plain URLs are currently reachable and suitable canonical reference links; availability does not guarantee permanent stability. Capture exact revision/locator/date when preparing content.

| Candidate | Official source and observed date | Coverage/qualification |
| --- | --- | --- |
| Acetazolamide | [MedlinePlus/ASHP monograph](https://medlineplus.gov/druginfo/meds/a682756.html), Last Revised 15 Sep 2017; [DailyMed acetazolamide tablet label](https://www.dailymed.nlm.nih.gov/dailymed/drugInfo.cfm?setid=0700a910-79aa-4431-aecf-637fe14fcae8), Updated 12 May 2026, archive current version 11 published 21 May 2026 | Patient topics cover uses/forms/precautions/effects; old revised date needs current label cross-check. Label is Heritage/Avet product-specific, not a universal substance approval |
| Allopurinol | [NHS Allopurinol](https://www.nhs.uk/medicines/allopurinol/), reviewed 28 Sep 2026, next review 28 Sep 2029 | General uses/forms/effects/precautions/serious warnings; suitable patient-source discovery |
| Carbamazepine | [NHS Carbamazepine](https://www.nhs.uk/medicines/carbamazepine/), reviewed 6 Aug 2026, next review 6 Aug 2029 | Patient topics present; serious reactions and interaction/pregnancy context increase editorial work |
| Colchicine | [NHS Colchicine](https://www.nhs.uk/medicines/colchicine/), reviewed 20 Aug 2026, next review 20 Aug 2029 | Patient topics present; toxicity/extra-dose risks demand careful warning context |
| Topiramate | [NHS Topiramate](https://www.nhs.uk/medicines/topiramate/), reviewed 29 Jul 2026, next review 29 Jul 2029; [MHRA safety update](https://www.gov.uk/drug-safety-update/topiramate-topamax-introduction-of-new-safety-measures-including-a-pregnancy-prevention-programme), 20 Jun 2024 | Patient topics and regulator pregnancy measures; preserve UK jurisdiction and do not imply an Indian regulatory program |

[MedlinePlus reuse policy](https://medlineplus.gov/about/using/usingcontent/) distinguishes licensed drug content from government-authored material. The acetazolamide monograph expressly carries ASHP copyright. Link to it; do not reproduce its prose commercially without appropriate permission. Do not assume DailyMed/manufacturer text is public domain; rights clearance precedes any reproduction.

[NHS terms](https://www.nhs.uk/our-policies/terms-and-conditions/) permit eligible content reuse under OGL with exceptions and attribution conditions. Unchanged dated copies and adapted content have different rules; adaptations/non-refreshed content must not be represented as NHS-approved content. Prefer independently written, professionally reviewed summaries of facts with links to original source pages, not a copied/syndicated NHS page. Confirm the distinction between bibliographic links to original research sources and attribution of adapted content before publishing; obtain written clarification if necessary. Do not promise routine annual checks satisfy NHS syndication freshness terms. No NHS logos or copied visuals.

No final medical text, bulk passages, accepted source records or database content were produced.

## 10. Content Model

Use one immutable document revision per substance/locale, with fixed section keys and a JSON citation map, plus append-only decisions (DDL in section 22). This is a bounded extension of Medicine governance, not a CMS. `content_json` contains schema_version=1, the five sections and optional approved aliases; `references_json` maps each section/claim to approved evidence_sources keys, exact locators, source titles/publishers/URLs/dates, accessed date, digest and rights decision. Public output projects only bibliographic metadata, never internal appraisal/credential details.

Existing WHO INN evidence concerns naming; provisional revisions and evidence appraisals are enforced as **identity** purpose. Existing canonical snapshots cover registry/composition/presentation, not educational text. Reusing them unchanged would leave prose unreviewed. Do not overload their JSON or amend historical events. Reuse the authority/source policy and hashing machinery; add only the missing content-revision and content-decision subjects.

## 11. Review Model

An independent, genuinely credential-verified doctor/pharmacist with current existing reviewer chemical/catalog authority must review identity, all five sections, claim-to-source fit, omissions, currentness, localization and rights. Reuse `medicine_reviewer_qualifications`, `requireGovernance(...,'reviewer','chemical')` and `requireQualificationBinding`; reviewer must not have authored any substantive revision of this document. Content review binds exact revision/content/reference/registry hashes, current identity event and qualification journal ID. Record rejection or withdrawal without erasing history.

Account 1's administrator grant is not contributor, reviewer or deployment authority. No delegation is implied by this report. Real enrollment and explicit appropriate author/operator grants are prerequisites. Clinical-content competence must be assessed rather than inferred from a bare registration number. No fake reviewer or automated approval.

## 12. Publication Model

Extend MedicineEligibilityService with the **single combined public-substance decision** used by detail and search. Existing `requireCurrent('chemical', ref)` remains the identity condition, including live registry snapshot, adverse lifecycle history, independent verification, authoritative identity evidence and current reviewer qualification. Add content-specific requirements: approved pilot ID/locale, latest immutable revision, complete source-backed sections, current independent content review, live source policy/rights, review deadline and explicit publication decision. Missing, stale, rejected, withdrawn or unpublished fails closed. An identity review alone never supplies content approval.

Reuse MedicineGovernanceService/AuthorizationService for append-only submit/review/reject/publish/withdraw commands. Use existing deployment operator authority for explicit release; `publisher` appears in the schema capability list but is not accepted by `requireGovernance`, so do not presume that path works. Publication must reference an approved content-review decision for the same revision. Withdrawing/revoking authority, changing a source or registry facts, or reaching review expiry removes curated projection and returns search to legacy behavior. No automatic restoration.

There is no current public gate that supports this. The smallest correction is a bounded **curated-information purpose** in the existing Medicine policy/services with an explicit three-ID allowlist and release-disabled default. Do not enable the internal five-to-ten identity/composition pilot, loosen its minimum, or remove `public_disabled`. Chemical-reference creation currently requires internal pilot intake: narrowly extend that intake authorization for approved curated chemical IDs only, reusing existing contributor checks; no general catalog creation, compositions or presentations. This purpose-specific correction is required in Step 47B, not performed now. If approval excludes that correction, public release is blocked; do not bypass it through SubstanceRepository.

## 13. Public Substance Page

Recommend `/medicines/substances/{substanceId}/{slug}`, e.g. `/medicines/substances/54/allopurinol`; numeric identity resolves the entry, slug is display/canonical routing only. Wrong slug redirects only when the same ID is currently publishable. No slug-only fuzzy identity resolution. Ineligible direct requests return a neutral unavailable/not-found page, never drafts. Back link to `/medicines`.

Title/name, educational badge/disclaimer, wrapped section links, five stacked sections, section sources, references and honest KOBI-reviewed date. Use existing Bootstrap layout/global loading patterns. At 390×844: single column, wrapping headings/source titles and buttons, no tables or fixed-width warnings, no sticky navigation obscuring anchors. At 1366×768: readable bounded line length; optional existing section-navigation pattern, not a redesign. Warning content remains visible rather than hidden behind an accordion. Mobile findings are design requirements, not screenshots or runtime verification.

## 14. Legacy Registry Behavior

Keep names-only search and its disclaimers for every non-pilot/ineligible row. Curated pages should say educational substance information, not “KOBI-approved Medicine product.” Revise the blanket page notice only during implementation to explain that most results remain legacy, and only explicitly marked results have reviewed educational pages. Retain the no-prescribing-guidance message. Never fill missing sections from another substance or attach unreviewed aliases/combination facts.

## 15. Search Integration

Preserve the existing bare-array JSON contract, IDs/names, ten-result limit, prefix matching/order and personal autocomplete. Recommended opt-in `GET /api/substances/search?q=...&include_information=1` for the public page only: add `information_url` and `information_label` only from the same combined gate; ordinary requests keep exactly their current shape. No cached boolean on registry rows. Legacy hits remain text-only, eligible pilot hits gain a link and “Reviewed educational information” label. Escape names and use DOM textContent; accept only locally constructed URLs. Ineligible/disabled items never advertise a detail link.

Existing `idx_substance_search` already supports prefix lookup. Resolve publication for the at-most-ten result IDs in one bounded projection query plus current policy validation; avoid N+1 credential/source scans. New indexes below cover latest revision/decision reads. Three pages require no caching framework, external search service or preload of all 2,224 rows.

## 16. References UI

Reuse the Disease Reference presentation pattern: escaped organization/title, safe HTTPS original-source link, clear external-link cue, `noopener noreferrer`, precisely labeled source date and KOBI review date. Reuse the visual pattern, not the disease manifest or its eight-ID validator. Show section-level sources only where the relationship is actually reviewed; an overview citation must not imply complete-page support. Unknown source dates remain unknown. No reviewer registration numbers, grant IDs, source hashes, private notes or internal history in public HTML/JSON. NHS adaptations require the rights/attribution resolution in section 9.

## 17. Disclaimer

Proposed: “General educational information only. This is not a diagnosis or advice tailored to you. Speak with a qualified healthcare professional about your medicines and personal treatment decisions.” Do not add dosage or stop/start directions to this disclaimer. No approval/superiority claims.

## 18. SEO

One canonical URL per publishable ID/English slug; title “Allopurinol — General substance information | KOBI”; short editorial description of the educational page, not effectiveness promises. Only publishable pages enter indexing/sitemap; draft/unavailable responses have no curated SEO content and are not indexable. Reuse existing layout meta mechanisms after inspecting implementation details in Step 47B. No broad SEO subsystem, Drug structured-data efficacy claims, prescription schema or misleading review ratings. Ordinary WebPage markup is optional and unnecessary for V1.

## 19. Personal Medication Relationship

Ordinary HealthMedicineService stores user-entered generic_name text. Autocomplete selection does not persist a canonical registry ID in normal CRUD; text is not a reliable identity association. Existing optional identity_links/selected_ingredients can represent explicit governed associations, but combinations/presentations must not resolve automatically to one pilot page. Future linking requires explicit confirmed substance selection and current public eligibility, while retaining `findAccessible` profile checks. No link migration or medication edits in Step 47B; a public detail visit needs no private medication/profile data.

## 20. Disease/Treatment Relationship

Existing disease → treatment_protocols → treatment_protocol_items connects content and includes medicine-type items, but item title/instructions are text: no canonical substance FK. Composition ingredients have chemical refs, not disease-treatment associations. Do not infer a link from free text or automatically recommend a pilot drug for a disease. A future reviewed association can reuse existing item IDs and chemical identity, rather than duplicate disease protocols; designing that relationship is deferred. Current legacy Treatment cards and HOLD/publication behavior remain unchanged.

## 21. Security / Privacy

Public projection uses only registry identity, approved educational content and public bibliographic metadata. No users/health_profiles/health_medicines/documents/mobile data in public joins or responses. Internal user IDs are used solely to validate current authority; they are not projected. Admin commands require genuine AuthService identity, explicit existing role checks and csrfField/CSRF verification; no author/reviewer ID supplied by a browser substitutes for the actor. Treat source URLs as reviewed HTTPS links, not arbitrary runtime fetch targets. Plain text content is escaped; do not render arbitrary source HTML. No authentication/mobile-verification change is required.

## 22. Schema Impact

**Multiple additive tables: two**, with four immutability triggers. No existing-table ALTER required. Proposed names are new and were not found in structure.sql. These store content/review/publication missing from the identity schema; personnel and sources remain shared existing Medicine infrastructure. Exact proposal below is **not executed, not imported and not syntax-certified on MariaDB in this readiness task**. Step 47B must validate in disposable 10.4.27 under the unchanged non-strict mode before separately authorized normal deployment with verified recovery. No schema change is justified merely for source discovery.

```sql
CREATE TABLE medicine_substance_information_revisions (
  substance_id bigint unsigned NOT NULL,
  locale varchar(8) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  revision int unsigned NOT NULL,
  chemical_ref_id bigint unsigned NOT NULL,
  identity_event_id bigint unsigned NOT NULL,
  registry_hash binary(32) NOT NULL,
  content_json mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  references_json mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  content_hash binary(32) NOT NULL,
  references_hash binary(32) NOT NULL,
  author_id int(11) NOT NULL,
  authority_grant_id bigint unsigned NOT NULL,
  created_at datetime(6) NOT NULL,
  PRIMARY KEY (substance_id,locale,revision),
  CONSTRAINT fk_msi_substance FOREIGN KEY (substance_id)
    REFERENCES kobi_substances(substance_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_msi_chemical FOREIGN KEY (chemical_ref_id)
    REFERENCES kobi_medicine_chemical_refs(chemical_ref_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_msi_identity_event FOREIGN KEY (identity_event_id)
    REFERENCES medicine_governance_events(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_msi_author FOREIGN KEY (author_id)
    REFERENCES users(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_msi_author_grant FOREIGN KEY (authority_grant_id)
    REFERENCES medicine_authority_grants(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT chk_msi_pilot CHECK (substance_id IN (15,54,2064)),
  CONSTRAINT chk_msi_locale CHECK (BINARY locale=BINARY 'en'),
  CONSTRAINT chk_msi_revision CHECK (revision>=1),
  CONSTRAINT chk_msi_content CHECK (JSON_VALID(content_json)
    AND OCTET_LENGTH(content_json) BETWEEN 2 AND 65535
    AND content_hash=UNHEX(SHA2(content_json,256))),
  CONSTRAINT chk_msi_references CHECK (JSON_VALID(references_json)
    AND OCTET_LENGTH(references_json) BETWEEN 2 AND 65535
    AND references_hash=UNHEX(SHA2(references_json,256)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE medicine_substance_information_decisions (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  substance_id bigint unsigned NOT NULL,
  locale varchar(8) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  revision int unsigned NOT NULL,
  sequence_no int unsigned NOT NULL,
  action varchar(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  actor_id int(11) NOT NULL,
  authority_grant_id bigint unsigned NOT NULL,
  qualification_event_id bigint unsigned DEFAULT NULL,
  approved_review_id bigint unsigned DEFAULT NULL,
  review_due_at datetime(6) DEFAULT NULL,
  snapshot_json mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  snapshot_hash binary(32) NOT NULL,
  occurred_at datetime(6) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_msid_sequence (substance_id,locale,revision,sequence_no),
  UNIQUE KEY uq_msid_subject (id,substance_id,locale,revision),
  KEY idx_msid_qualification (qualification_event_id,actor_id),
  CONSTRAINT fk_msid_revision FOREIGN KEY (substance_id,locale,revision)
    REFERENCES medicine_substance_information_revisions(substance_id,locale,revision)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_msid_actor FOREIGN KEY (actor_id)
    REFERENCES users(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_msid_grant FOREIGN KEY (authority_grant_id)
    REFERENCES medicine_authority_grants(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_msid_qualification FOREIGN KEY (qualification_event_id,actor_id)
    REFERENCES medicine_reviewer_qualifications(id,user_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_msid_review FOREIGN KEY (approved_review_id,substance_id,locale,revision)
    REFERENCES medicine_substance_information_decisions(id,substance_id,locale,revision)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT chk_msid_action CHECK (BINARY action IN
    (BINARY 'submit',BINARY 'review',BINARY 'reject',BINARY 'publish',BINARY 'withdraw')),
  CONSTRAINT chk_msid_sequence CHECK (sequence_no>=1),
  CONSTRAINT chk_msid_review CHECK (
    (BINARY action IN (BINARY 'review',BINARY 'reject') AND qualification_event_id IS NOT NULL)
    OR (BINARY action NOT IN (BINARY 'review',BINARY 'reject') AND qualification_event_id IS NULL)),
  CONSTRAINT chk_msid_due CHECK (
    (BINARY action=BINARY 'review' AND review_due_at IS NOT NULL AND review_due_at>occurred_at)
    OR (BINARY action<>BINARY 'review' AND review_due_at IS NULL)),
  CONSTRAINT chk_msid_publish CHECK (
    (BINARY action=BINARY 'publish' AND approved_review_id IS NOT NULL)
    OR (BINARY action<>BINARY 'publish' AND approved_review_id IS NULL)),
  CONSTRAINT chk_msid_snapshot CHECK (JSON_VALID(snapshot_json)
    AND OCTET_LENGTH(snapshot_json) BETWEEN 2 AND 65535
    AND snapshot_hash=UNHEX(SHA2(snapshot_json,256)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$
CREATE TRIGGER msi_no_update BEFORE UPDATE ON medicine_substance_information_revisions
FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine information revisions are immutable';
END$$
CREATE TRIGGER msi_no_delete BEFORE DELETE ON medicine_substance_information_revisions
FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine information revisions are immutable';
END$$
CREATE TRIGGER msid_no_update BEFORE UPDATE ON medicine_substance_information_decisions
FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine information decisions are immutable';
END$$
CREATE TRIGGER msid_no_delete BEFORE DELETE ON medicine_substance_information_decisions
FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine information decisions are immutable';
END$$
DELIMITER ;
```

DB constraints ensure subject FK, allowed pilot/locale/action values, exact hashes, immutable history and same-revision publication pointers. They do not alone prove reviewer authority, source truth or transition validity. Service validation must check exact JSON schema/types/key allowlists, nonempty sections/citations, canonical source IDs, registry-to-chemical/event binding, authenticated authorship/grant, prior authors' independence, review action and ordering, identical approved hashes, live qualification/source/identity state, and source/review deadlines. Lock the registry subject and append next revision/sequence within a transaction; reject stale expected revision/hash/decision to serialize concurrent review/edit/publish/withdraw. Withdraw closes that revision; restoration requires new content revision and fresh review. Check direct self/forward review pointers at service level; no CHECK involving AUTO_INCREMENT id is proposed.

## 23. Testing Plan

Step 47B: focused unit tests for exact ID/chemical mapping (including salts/forms/combinations and stale facts), required sections/claim citations, unknown source IDs/rights/purpose, immutable hashes, independent review, expired/revoked grants/qualifications/sources, current review deadline and transition validation. Explicitly prove registry CANDIDATE/provisional accepted/identity verified alone cannot publish prose; identity gate failure, unreviewed/rejected/withdrawn/new draft or disabled release yields legacy search and no public detail.

Disposable MariaDB 10.4.27: DDL/metadata/trigger fidelity, non-strict invalid values, FKs/hash CHECKs, duplicate revision/sequence, immutability, concurrent authors/review/publication and stale optimistic inputs. Do not change SQL mode. Run relevant existing Medicine foundation/governance/policy/reviewer-evidence/provisional/pilot regressions in their guarded test environments, public substance search and personal autocomplete checks, Disease References tests and relevant Auth/Treatment regressions. Never use normal KOBI for destructive tests or credentials/fixture seeding.

Browser after implementation: anonymous `/medicines` pilot and non-pilot hits, empty/no-result/prefix/stale-request behavior, valid/ineligible/canonical detail routes, section anchors, bibliographic links/date labels, preserved no-advice disclaimer, keyboard navigation, 390×844 and 1366×768 overflow/console checks. Inspect JSON/HTML for private data and unauthorized admin/CSRF failure. Verify personal autocomplete unchanged without creating live medications. Before any normal deployment, separately authorize verified backup/recovery and bounded schema/content writes; verify existing data/schema, SQL mode and session/config boundaries afterward.

This task ran no mutation-based tests and no new browser test. Readiness validation: source tracing, SELECT-only live census, pure retained-package digest checks (five packages/ten retained sources passed), official-source/date/rights research, and eight PHP syntax checks (SubstanceController, SubstanceService, SubstanceRepository, MedicineEligibilityService, MedicineGovernanceService, MedicineGovernancePolicy, MedicineAuthorizationService, MedicineProvisionalIdentity: all passed). Report has exactly 27 numbered sections and no trailing whitespace. `git diff --check` passed; its existing health-medicines line-ending warning was informational. Before/after SHA-256 inventories of app, routes, database, resources and public files matched. No clinical correctness or reviewer credential verification claimed.

## 24. Product Value

Useful incremental value: search names can lead to short educational explanations with transparent sources instead of a dead-end name list. Consistent KOBI navigation and reviewed links can complement Disease knowledge and eventually explicitly selected personal medicines. No evidence supports claims of superior clinical quality, recommendation accuracy or measured demand; the pilot should test usefulness rather than assert it.

Main cost is professional review and rights/source maintenance, not search/UI code. Start with three pages; manually check sources monthly, formally re-review at most every six months, and immediately after material source/label/regulator updates or adverse identity/qualification changes. Six months is a proposed KOBI maximum, not a source licensing rule. Store exact source review date separately from accessed date and KOBI review date. Set review_due_at to the earliest applicable content/source validity deadline. Missing material support means withdraw curated exposure until fresh review. No automated monitoring promised; source-license freshness conditions can require a different process or avoiding reproduced content.

## 25. Implementation Sequence

Step 47B should implement only: the approved three-ID allowlist and educational-purpose policy; two additive tables/four triggers validated in isolation; bounded content/source validator and immutable decisions in existing Medicine service/repository architecture; genuine role/qualification enrollment as separately authorized operational prerequisites; three exact chemical mappings with current independent identity review; three source-backed five-section documents and independent content reviews; explicit operator release; one public detail projection/page and opt-in search enrichment; shared visual reference pattern and focused tests.

Use a small controlled preparation/review command path within existing service conventions, not a general CMS. Content/schema deployment and grant creation require explicit authorization and verified recovery where applicable. A code delivery may remain release-disabled if qualified personnel or identity/source approval is unavailable. Do not roll forward public exposure to claim implementation PASS. No broad governance stages, five-target internal pilot activation, all-substance enrichment, private-data integration or sharing/SMS work.

## 26. Deferred Scope

All-substance rollout; brands/combinations/presentation pages; Hindi translation until independently reviewed; personalized dosing/contraindications/interactions; diagnosis; clinical recommendations/substitution; AI advice; notifications; mobile app/external API rollout; automated source monitoring; broad Medicine governance expansion; personal-medication association edits; new disease-treatment-substance relationships; verified mobile/SMS/sharing; generic CMS/caching/SEO frameworks.

## 27. Risks / Open Questions

Genuine prerequisites: (1) real qualified independent reviewer and authorized contributor/deployment operator; (2) exact chemical mapping and current identity evidence, including Topiramate stereochemistry; (3) reviewed content/source coverage and jurisdiction-appropriate warnings; (4) rights/attribution approach, especially ASHP and adapted NHS information; (5) explicit approval of the focused educational-policy extension and proposed additive DDL. There are no persisted candidates to “resume publishing.” Do not infer authority from Account 1 administrator status.

Implementation-time validation must establish proposed DDL compatibility and concurrency semantics; this readiness document is not a migration certificate. Source permanence and clinical completeness cannot be guaranteed by successful URL retrieval. The recommended set is conditional, not an instruction to publish without these prerequisites.

Created only `docs/task-47a-curated-substance-information-readiness.md`. Normal DB activity was SELECT-only; the first sandbox connection was blocked, then the approved read-only census succeeded. No application, database data/schema, disease/medical content, evidence/review/publication, configuration, browser/session, SQL-mode or Git mutation was performed. Observed SQL mode remained `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`. Existing unrelated working-tree changes were preserved. **Stop: Step 47B has not begun.**
