# Medicine Phase 3.17 — Evidence and qualified reviewer preparation

Result: **PASS WITH LIMITATIONS**. Five fixed provisional packages and ten traceable captured source responses were prepared. Real professional qualification, scientific appraisal, source-policy acceptance and governed target/revision binding remain outstanding. Matrix BLOCKED entries denote missing prerequisites, not a technical defect preventing this preparation.

**No Medicine was verified, approved, enrolled, activated, or published during Phase 3.17.** No normal-database evidence, credentials, grants, revisions, chemical forms or governance data were created.

## 1. Objective

Prepare source material and exact provenance for independent professional identity appraisal, using the deployed Phase 3.12/3.13 governance model. This is an application/data/source readiness package, not an approval, regulatory confirmation of KOBI records or a therapeutic assessment. Existing architecture, schema, lifecycle, authorization and tests remain unchanged.

## 2. Fixed pilot scope and current state

| Substance ID | Stored canonical label | Prospective registry reference |
| --- | --- | --- |
| 15 | Acetazolamide | S:15 |
| 54 | Allopurinol | S:54 |
| 319 | Carbamazepine | S:319 |
| 481 | Colchicine | S:481 |
| 2064 | Topiramate | S:2064 |

SELECT-only observation of `medical1:3306`, MariaDB 10.4.27, at UTC **2026-10-09 21:50:44.957520**: all five still exist, active/searchable, `identity_status=CANDIDATE`, `identity_type=UNKNOWN`. All twelve newly deployed Medicine tables contain zero rows. Phase 3.16 metadata and existing-data digest verification also passed. No candidate was added/substituted. Registry labels still are not verified chemical or Medicine identities.

## 3. Evidence policy and tier classification

Tier 1: primary regulatory/government identity records/publications. Tier 2: recognized authoritative standards. Tier 3: established professional/clinical/pharmacological references. Tier 4: manufacturer documentation. Tier 5: secondary/general discovery sources, never independently qualifying. Initially the existing policy accepts only appraised tiers `[1,2]`; `evidence_sources=[]` means none of these proposals currently qualifies at runtime.

The captured **PubChem government chemical-registry responses are proposed Tier 1**, subject to qualified appraisal of their provenance and sufficiency for the exact intended entity. NIH hosting alone does not prove scientific sufficiency, primary experimental evidence, a pharmacopoeial specification or FDA approval. If the reviewer finds a curated property response insufficient, additional accepted Tier 1/2 regulatory/pharmacopoeial identity material is **SOURCE_REQUIRED**. No publisher has been hardcoded into application business logic or enabled in policy configuration.

The **DailyMed SPLs are classified conservatively as Tier 4 manufacturer/packager-origin material**, with NIH/NLM as government custodian. They were not promoted to Tier 1 just because the host is governmental. These are corroborating product-identity sources; their use as independently qualifying authority would require separately approved policy acceptance, currently absent. No such policy change was made. Tier 3/5 material and search snippets were not adopted as identity evidence.

## 4. Source methodology and limitations

Targeted official-source discovery was followed by actual HTTPS retrieval of five NCBI PubChem property JSON responses and five NLM DailyMed SPL XML documents. Search snippets were discovery aids only. The raw successful responses were stored in protected, Git-ignored `storage/logs/medicine317-preparation/`; their exact byte SHA-256 values were calculated locally. Source parsing used JSON and namespace-aware XML with external network entity loading disabled. Only chemical identity and active-ingredient nodes inform this report; clinical/dosage text was not converted into claims.

The PubChem browser pages presented a browser check to the browsing tool. The official property API was successfully retrieved directly; its JSON bytes, not a browser-check page or generated summary, are the hashed evidence. API responses provide no publisher edition/publication date. Their `version` proposal explicitly identifies a **retrieval snapshot**, not an invented publisher version. Source freshness is limited to successful live retrieval; latest authoritative status and appraisal expiry are not established.

The first discovered Colchicine SPL record (`28efb8cf-3ef4-4cd6-b6f6-131f3ca01112`) returned HTTP 404. It is not included as evidence and has no invented digest. An accessible same-candidate label with setId `4b79a786-3f9e-4dad-aa66-bae5f299b966` was captured instead. Some search results reported older label revisions; the successful XML's actual version/effective date was used. No candidate substitution occurred.

## 5. Exact provenance and snapshot binding

The reviewable machine-readable manifest is `docs/MEDICINE_PHASE_3_17_EVIDENCE_PACKAGE.json`. Every source records title, authority/originator, stable record URL, exact captured API URL, locator, actual version/date where exposed, retrieval UTC timestamp, raw-file path/byte length, SHA-256 and limited supported purpose. Original document titles/markup remain in the raw XML at `/document/title`; displayed source titles below are the exact DailyMed page-label titles. SPL `effectiveTime` is a declared label effective date, not an invented publication date or independent regulatory-currentness certification.

Existing six-field evidence envelope: `kind`, `issuer`, `record`, `version`, `locator`, `source_sha256`. Each source has a **proposed** envelope, not an accepted issuer/configuration record or database row. No appraisal boolean was set true.

Existing appraisal acceptance additionally binds scope, actual governed target ID, current fact `snapshot_hash`, identical source record/version/locator/digest, `identity_appraised=true` and current record/source expiry. Actual event records retain revision, evidence JSON, deterministic fact snapshot JSON and binary SHA-256. Source-byte digest and target-fact snapshot digest are different values with different purposes.

All package bindings deliberately retain `target_id=null`, `revision=null`, `snapshot_json=null`, `snapshot_hash=null`, with prospective `scope=chemical` and registry reference S:ID. There is no governed target to bind today. A registry substance ID must not stand in for a chemical-ref/composition target ID, and hashing the downloaded document must not masquerade as a target snapshot. These packages are **not executable ingestion/configuration records**. A later separately authorized contributor must prepare the actual target, preserve their authenticated authorship, obtain the current snapshot through existing governance, and the reviewer must bind appraisal to that exact target/revision and its dependencies.

### Proposed government chemical-registry source records

All five: proposed Tier 1; authority NIH/NLM/NCBI PubChem; exact JSON locator `PropertyTable.Properties[0]`, requested fields `Title`, `MolecularFormula`, `InChI`, `InChIKey`. Publisher version/publication date **not exposed**. Captured URL for each is `https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/name/{stored-name}/property/Title,MolecularFormula,InChI,InChIKey/JSON` (the full exact URLs are in the manifest). Supported purpose is the named compound record and its returned structural identifiers, not final KOBI equivalence, formulation or clinical claims.

| ID | Exact response title / record URL | Retrieved UTC |
| --- | --- | --- |
| 15 | [Acetazolamide — CID 1986](https://pubchem.ncbi.nlm.nih.gov/compound/1986) | 2026-10-09 21:44:39 |
| 54 | [Allopurinol — CID 135401907](https://pubchem.ncbi.nlm.nih.gov/compound/135401907) | 2026-10-09 21:45:18 |
| 319 | [Carbamazepine — CID 2554](https://pubchem.ncbi.nlm.nih.gov/compound/2554) | 2026-10-09 21:45:20 |
| 481 | [Colchicine — CID 6167](https://pubchem.ncbi.nlm.nih.gov/compound/6167) | 2026-10-09 21:45:23 |
| 2064 | [Topiramate — CID 5284627](https://pubchem.ncbi.nlm.nih.gov/compound/5284627) | 2026-10-09 21:45:58 |

### Corroborating manufacturer/packager source records

All five: Tier 4 origin, NIH/NLM DailyMed custody. Exact captured bytes come from `https://dailymed.nlm.nih.gov/dailymed/services/v2/spls/{setId}.xml`, with setId/document ID/version/effective date stored in the manifest. Locators: DESCRIPTION section code **34089-3**, and `manufacturedProduct/.../ingredient[@classCode=ACTIB]/ingredientSubstance/name` plus ingredient code. Supported purpose: the stated active ingredient in the captured product(s), not every formulation or universal chemical-form equivalence. Versioned page URLs below include actual XML revisions; these were not independently certified as latest FDA-approved labels.

| ID | Exact page-label title / versioned URL | Originator | SPL version; declared effective date | Retrieved UTC |
| --- | --- | --- | --- | --- |
| 15 | [ACETAZOLAMIDE tablet](https://dailymed.nlm.nih.gov/dailymed/drugInfo.cfm?setid=240ab24b-3124-4805-80c2-36dd1945e3b3&version=7) | Ajanta Pharma USA Inc. | 7; 2026-01-20 | 2026-10-09 21:44:41 |
| 54 | [ALLOPURINOL tablet](https://dailymed.nlm.nih.gov/dailymed/drugInfo.cfm?setid=bdbf5ad4-86f2-4e9c-a51a-fb0c7220c480&version=20) | Mylan Pharmaceuticals Inc. | 20; 2025-11-20 | 2026-10-09 21:45:20 |
| 319 | [CARBAMAZEPINE tablet](https://dailymed.nlm.nih.gov/dailymed/drugInfo.cfm?setid=bf0d012f-da14-8ce3-d74f-56172cddebc5&version=21) | Apotex Corp. | 21; 2026-08-26 | 2026-10-09 21:45:22 |
| 481 | [COLCHICINE tablet, film coated](https://dailymed.nlm.nih.gov/dailymed/drugInfo.cfm?setid=4b79a786-3f9e-4dad-aa66-bae5f299b966&version=7) | American Health Packaging | 7; 2026-04-09 | 2026-10-09 21:45:56 |
| 2064 | [TOPAMAX- topiramate tablet, coated TOPAMAX- topiramate capsule, coated pellets](https://dailymed.nlm.nih.gov/dailymed/drugInfo.cfm?setid=21628112-0c47-11df-95b3-498d55d89593&version=31) | Janssen Pharmaceuticals, Inc. | 31; 2026-03-12 | 2026-10-09 21:46:00 |

### Exact source-byte SHA-256 digests

| Candidate / captured response | SHA-256 |
| --- | --- |
| 15 PubChem JSON | `b866248be6018b44879200aa861ea22ffe461158c521888551abb59ad0aa6955` |
| 15 DailyMed XML | `42256b4b4361aefa10c9753443a807a00d46ad5032315b438b1a5dc4ae592135` |
| 54 PubChem JSON | `94b5c3d49608641310324882a543b8cd826336fb78096382cc07993e603ac683` |
| 54 DailyMed XML | `5c84d4a3f1f6ba7bee75ef2eab03f90ea77f0f314a37a0e81e5c076c9293640c` |
| 319 PubChem JSON | `9c5f30171a501e6e75b94165d01af6c8433b243e0c7976c4ba6e33551a3f7d97` |
| 319 DailyMed XML | `15105d9ea4c41d0fa4b354dd8bace155d4c14bb37bdac7aa348a1ffbc4a7c28f` |
| 481 PubChem JSON | `316beb74c1277be115c70548af982c565b1abb9a73c4ca70bc86e63cc8a4a30f` |
| 481 DailyMed XML | `175bfa6f649934246991c868c93b81909644c6b894ba6e2d308202faec0fb606` |
| 2064 PubChem JSON | `dab38c91f171dc1d0cd27ddc85d2839a692589dec6a79efd441b5aae01b003fb` |
| 2064 DailyMed XML | `84c705f9cab4f0162d8fa0bc27d6e2214d7a79be6d99ca2255ae535b8d718889` |

## 6. Identity evidence and appraisal checklist

Here **SUPPORTED** means the captured source actually contains the observed name/ingredient, not that KOBI has appraised/verified it. PARTIALLY_SUPPORTED is source-limited. NOT_ASSESSED requires professional judgment. SOURCE_REQUIRED applies where additional qualifying evidence is needed. No source absence was represented as scientific disproof.

| Candidate | Name/active ingredient present | Exact UNKNOWN KOBI entity mapping | Single-ingredient interpretation | Canonical composition | Form applicability | Absence of ambiguity |
| --- | --- | --- | --- | --- | --- | --- |
| 15 Acetazolamide | SUPPORTED | NOT_ASSESSED | PARTIALLY_SUPPORTED | NOT_ASSESSED | NOT_ASSESSED | NOT_ASSESSED |
| 54 Allopurinol | SUPPORTED | NOT_ASSESSED | PARTIALLY_SUPPORTED | NOT_ASSESSED | NOT_ASSESSED | NOT_ASSESSED |
| 319 Carbamazepine | SUPPORTED | NOT_ASSESSED | PARTIALLY_SUPPORTED | NOT_ASSESSED | NOT_ASSESSED | NOT_ASSESSED |
| 481 Colchicine | SUPPORTED | NOT_ASSESSED | PARTIALLY_SUPPORTED | NOT_ASSESSED | NOT_ASSESSED | NOT_ASSESSED |
| 2064 Topiramate | SUPPORTED | NOT_ASSESSED | PARTIALLY_SUPPORTED | NOT_ASSESSED | NOT_ASSESSED | NOT_ASSESSED |

Reviewer must check original source, issuer tier/authority, exact identifier/name correspondence, ambiguity, chemical basis, applicability and sufficiency. If regulatory/pharmacopoeial precision is needed beyond these records, additional appropriate Tier 1/2 material is SOURCE_REQUIRED; no inaccessible standard was invented or claimed consulted.

## 7. Composition evidence

Each captured SPL has one distinct named active ingredient and UNII after duplicate product entries are collapsed. This supports the labelled product's active-ingredient count; it does not mean the whole tablet/capsule is one chemical or that all products/registry rows are equivalent. Inactive ingredients are not added as canonical active ingredients. Strengths/formulations described by sources are not copied into KOBI or inferred for the registry labels.

The existing model can conditionally represent a reviewed S-tag chemical reference and one-active-ingredient composition. Composition creation requires currently approved chemical dependencies and its own current source/snapshot appraisal. Neither one word in a name nor one SPL active ingredient proves the canonical composition of an unreviewed KOBI target.

## 8. Chemical-form assessment

| Candidate | Source observation and provisional handling | Unresolved decision |
| --- | --- | --- |
| 15 Acetazolamide | Captured parent chemical record and label name the selected active ingredient; no active salt/hydrate variant is established by this package. Parent S-only representation is a candidate option. | Whether form handling is not materially applicable to this exact intended entity: NOT_ASSESSED; additional evidence if needed. Do not infer from hydrated inactive excipients. |
| 54 Allopurinol | Captured parent chemical record and label identify the named active ingredient; no active salt/hydrate variant established. | Exact entity/chemical-basis mapping and material form distinctions: NOT_ASSESSED. Parent S-only option requires qualified appraisal. |
| 319 Carbamazepine | Captured record/product identify the named compound; crystalline wording does not settle polymorph/hydrate equivalence. | Whether intended identity needs materially distinct solid-form handling: NOT_ASSESSED. Do not invent a polymorph relationship or declare all forms equivalent. |
| 481 Colchicine | Captured InChI contains explicit stereochemical information; label DESCRIPTION also specifies a stereochemical identity. | Qualified confirmation that S:481 denotes that exact entity is required. Conditionally representable through the current S reference if that mapping is accepted; otherwise hold for additional evidence/model decision. |
| 2064 Topiramate | Captured InChI and label chemical description specify stereochemical information; the SPL includes distinct product presentations. | Qualified exact stereochemical mapping to S:2064 is required. Current S reference may suffice only after appraisal; multiple presentations must not become one assumed formulation. |

No final declaration of “not materially applicable,” approved representability or necessary future form redesign is justified yet. Those are explicit reviewer decisions. Colchicine/Topiramate form readiness is BLOCKED pending this mapping; the other three remain PARTIAL. If appraisal establishes unresolved necessary form semantics, stop that candidate for future modeling rather than fabricate a form/relationship. No form records were created. This report does not assert a proven model defect.

## 9. Therapeutic-evidence boundary

Therapeutic claims are **not required for this initial identity-governance representation**. Product labels contain clinical text, but its presence is not an appraisal or permission to create indications, effectiveness, safety, dosing or treatment advice. No such claims were extracted/accepted. Matrix `NOT YET PROVIDED` for therapeutic evidence denotes no separately scoped therapeutic package; it is not an additional activation requirement for identity-only scope. Identity approval and therapeutic authorization remain separate.

## 10. Candidate packages and readiness matrix

Every package includes the exact two sources above, six-field proposals, prospective registry reference, missing governed binding, source-limited checklist and **QUALIFIED REVIEWER REQUIRED**. All packages preserve preparation attribution without inventing an authenticated contributor ID.

| Candidate | Identity evidence | Composition | Chemical form | Therapeutic evidence | Evidence snapshot | Overall |
| --- | --- | --- | --- | --- | --- | --- |
| 15 Acetazolamide | PARTIAL | PARTIAL | PARTIAL | NOT YET PROVIDED | BLOCKED | BLOCKED |
| 54 Allopurinol | PARTIAL | PARTIAL | PARTIAL | NOT YET PROVIDED | BLOCKED | BLOCKED |
| 319 Carbamazepine | PARTIAL | PARTIAL | PARTIAL | NOT YET PROVIDED | BLOCKED | BLOCKED |
| 481 Colchicine | PARTIAL | PARTIAL | BLOCKED | NOT YET PROVIDED | BLOCKED | BLOCKED |
| 2064 Topiramate | PARTIAL | PARTIAL | BLOCKED | NOT YET PROVIDED | BLOCKED | BLOCKED |

Evidence snapshot BLOCKED means the actual governed target/revision/fact snapshot is absent; the **source snapshots and their digests have been captured**. None is READY merely because a source exists. Each must proceed through independent professional appraisal; no expiry, validity period or source-policy acceptance was invented.

## 11. Qualified reviewer requirements

**QUALIFIED REVIEWER REQUIRED.** No real reviewer identity/registration has been supplied, no grants exist, and no professional credential payload was read or written.

Obtain only necessary restricted information: actual active account/full professional identity; Doctor OR Pharmacist category; licence/registration identifier; jurisdiction; issuing authority; current validity; accountable verification method and record; verification timestamp; actual verifier; expiry/renewal where applicable; precise governance/qualification scope. Professional identity belongs to the actual account/controlled verification process, not a duplicate public credential model.

Existing credential JSON fields are exactly `profession`, `registration`, `jurisdiction`, `authority`, `status`, `verified_at`, `renewal_due`, `verifier`, using verified status and canonical UTC microsecond dates. Existing grant fields bind qualification reference, `qualification_assessed_by`, qualification scope, issuing actor, effective/expiry dates and revocation. Renewal bounds grant validity. The verification method/proof must be retained in the legitimate restricted attestation record; a free-text claim or synthetic test credential is not real professional verification.

Independent reviewer must not be creator or a prior create/assess/edit/submit actor for the target. Preserve actual contributor authorship in later service-created events; never change authorship to manufacture independence. Administrator status is no substitute for medical review. Professional credential fields must not appear in public Medicine views/projections or ordinary health logs.

## 12. Administrator and operator requirements

Medicine Administrator: not configured (`owner_user_ids=[]`), no delegated grants. Deployment Operator: not configured (owner/list empty), no delegated grants. Contributor/Editor: no scoped grants/identified personnel. Registry Operator: empty configuration/grants; unnecessary for this read-only preparation and required only for separately authorized future registry changes. No role was automatically assigned.

Use distinct deployment operator and independent reviewer for the planned workflow unless a later explicit approved governance policy permits otherwise. Current code validates active/scoped capabilities and target-specific review independence but does **not** impose an additional globally distinct administrator/deployment/reviewer-ID rule. Do not claim operational separation is automatically enforced by such a check. This is an explicit staffing prerequisite; no role-overlap policy or application change was introduced here.

## 13. Common blockers

No named qualified reviewer/credential verification, contributor/editor or administrator/deployment authority; no accepted source issuer/appraisal configuration; no actual governed target/current revision/fact snapshot; no accountable source currency/expiry appraisal or independent approval; pilot intake/availability configuration remains disabled. Government chemical-record tier/sufficiency acceptance is pending, and Tier 4 labels alone do not qualify under current defaults. If necessary, additional Tier 1/2 material must be collected before review.

The existing implementation can persist evidence only through authenticated, revision-bound submission/decision events, alongside owner-controlled appraised source configuration. It has no separate Phase 3.17 draft-only evidence-ingestion workflow. Nothing in preparation requires a normal write, so no persistence was attempted. Local manifests are proposals, not substitutes for governed records.

## 14. Candidate-specific blockers

- **15:** exact S:15 entity mapping; confirm chemical-form relevance from accepted sources; appraise this candidate's sources and eventual snapshot. Product excipients/formulation do not define its parent identity.
- **54:** exact S:54 mapping and active-ingredient chemical basis; establish current accepted identity source sufficiency, then candidate-specific composition/snapshot appraisal.
- **319:** exact S:319 mapping and review of whether solid-form distinctions are material; the package does not settle polymorph/hydrate semantics.
- **481:** exact stereochemical entity-to-S:481 mapping; Tier 4 label corroboration is not sufficient policy acceptance; source currency and eventual target snapshot remain unappraised.
- **2064:** exact stereochemical mapping to S:2064; separately scoped products/presentations in TOPAMAX material must not be assumed equivalent; identity/source/snapshot appraisal required.

No substitution, new form or canonical identity claim was authored to resolve these gaps.

## 15. Activation prerequisites

First supply real qualified reviewer/staffing and legitimate scoped authority, review source sufficiency/tier/currency and resolve each exact chemical entity/composition/form question. In a separately authorized phase, prepare actual candidates, capture current fact snapshots, obtain explicit source appraisals and submit for independent professional review. Chemical and composition approvals require their respective current evidence and dependencies.

Only after current independent approvals may a separately authorized deployment operator enroll exact approved revisions using concurrency/snapshot/operation tokens. Phase 3.13 still requires explicit appraised representatives, legitimate intake markers, current reviewer/operator authority, backup/deployment references, five-item current approval/enrollment manifest and public-disabled attestations before separately approved internal activation. Phase 3.16 resolved schema deployment only; it did not supply people/evidence or activate anything. No prerequisite was waived here.

## 16. Focused security review and tests

Source digest substitution, changed issuer/record/version/locator, wrong scope/target, changed snapshot and unsupported evidence kinds are denied by existing exact appraisal binding. The software validates metadata/bindings and actual authority; it does **not** independently evaluate scientific truth. Qualified identity appraisal remains essential. External URLs may change; captured bytes identify what was inspected, and accountable expiry/reappraisal/source withdrawal must keep later evidence current rather than relying on a URL alone.

Evidence used by decisions is retained in immutable event JSON with exact revision/fact hash. Database no-update/no-delete triggers and append-only lifecycle/intake enforcement prevent silent rewriting of used provenance. Current-use checks re-evaluate grants/qualifications/independence/evidence and dependency/lifecycle history. Source replacement or withdrawal must deny stale current use without erasing history. Raw local preparation files are not claimed to be immutable governance records.

Actual authenticated actors/scoped grants, independent reviewer checks and expected target/revision/hash prevent ID-only approval/edit/enrollment/IDOR and client role escalation. Evidence preparation adds no endpoint. CLI-only test tooling returns 404 outside CLI; no public source/credential projection was created. New local source responses contain public source material, not real professional credentials; protected storage denies web access. Public Medicine publication OFF, pilot OFF, Treatment HOLD; no public routing/search/SEO integration was introduced.

Executed regressions:

| Suite | Cases/checks | Assertions where counted | Failures |
| --- | ---: | ---: | ---: |
| New evidence readiness | 11 | 134 | 0 |
| Medicine policy | 33 | 92 | 0 |
| Medicine governance | 56 | 249 | 0 |
| Medicine foundation | 105 | 216 | 0 |
| Medicine pilot | 40 | 143 | 0 |
| Medicine foundation MariaDB | 29 | 96 | 0 |
| Medicine pilot MariaDB | 15 | 131 | 0 |
| Medicine policy MariaDB | 11 | 43 | 0 |
| Medicine governance MariaDB | 38 | 141 | 0 |
| Candidate selection | 55 | — | 0 |
| Treatment operations (including earlier retained checks) | 570 | — | 0 |
| Authentication | 45 | — | 0 |

Existing Medicine suites: **327 cases / 1,111 assertions**, zero failures. New focused checks cover fixed deterministic scope, actual raw hashes/provenance/envelopes, missing provenance, source substitution, unsupported kind, wrong scope/target/snapshot, unavailable real policy authority, missing reviewer qualification, self-review, public action denial and empty/disabled observed state. They use existing synthetic in-memory fixtures, never real credentials or a normal DB mutation. An initial new fixture hit the valid 409 reassessment gate before the intended 422 missing-field check; the fixture was corrected to perform explicit synthetic reassessment, leaving application/old tests unchanged. Final focused run passed.

The legacy **Phase 3.15 pilot-readiness live audit is deferred**, because its unchanged guard intentionally refuses deployed Medicine tables and its test expects an undeployed snapshot. It was not run as a passing post-deployment audit or altered to hide that incompatibility. The new Phase 3.17 SELECT-only audit and Phase 3.16 verification cover current deployed/empty readiness. Real reviewer onboarding, professional verification, independent appraisal, submission/approval/enrollment, clinical claims and new browser flows are deferred, not claimed tested.

PHP syntax and diff checks passed. Required MariaDB mutation regressions used guarded disposable 10.4.27 databases only, with the existing documented mode-directive omissions and matching isolated startup mode. No new MariaDB mutation harness/schema was necessary. Disposable suite databases, instances/datadirs and temporary census snapshot were removed after execution.

## 17. Database safety, files and next step

**Normal KOBI database remained read-only and no Medicine governance data was created.** No INSERT/UPDATE/DELETE/DDL, source-policy change, grants, evidence persistence, candidate creation, registry/form/private/combination/Treatment/Disease changes were executed. All twelve Medicine tables remained empty. Normal existing-data full-row digests/schema/privileges matched Phase 3.16 before/after verification; final checks completed at UTC **2026-10-09 21:57:46.475318–21:57:47.191460**. Normal session/global SQL mode remained `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`; no mode/configuration/credential changes were made. Final hash comparison found all 303 pre-existing files under app/public/tests/bootstrap/database/routes/docs unchanged, with exactly the three additions listed below. All ten reported source digests matched the manifest and retained raw responses.

Created: this document; `docs/MEDICINE_PHASE_3_17_EVIDENCE_PACKAGE.json`; `tests/medicine-evidence-readiness.php`. Protected local preparation files: `.htaccess`, `readonly-state.json`, and `{15,54,319,481,2064}-{pubchem.json,dailymed.xml}` under `storage/logs/medicine317-preparation/`. Existing protected `storage/logs/medicine316-recovery/post-deployment.json` was refreshed by read-only verification; its content is an operator result, not database governance state. No application/configuration/schema or prior test/document source was modified. No commit, push, reset, checkout, merge or rebase was performed.

Supply the named qualified reviewer and legitimate contributor/operator personnel, have the reviewer inspect these exact captured bytes and limitations, obtain additional qualifying identity standards if required, then authorize the next governed preparation step separately. Nothing in this report permits approval or activation.

**The five candidates must proceed through independent qualified professional appraisal before any approval, pilot enrollment, or activation is permitted.**
