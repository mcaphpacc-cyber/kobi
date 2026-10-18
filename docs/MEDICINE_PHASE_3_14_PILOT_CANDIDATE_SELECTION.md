# Medicine Phase 3.14 — Read-only pilot candidate suitability audit

**Result: PASS WITH LIMITATIONS.** Prepared ten strong name-level candidates and five multiword alternatives for owner review. These are recommendations, not approved scientific identities or final pilot membership.

**No pilot identities were enrolled or activated during Phase 3.14.** No substances were verified, no evidence was created, and no application configuration was enabled.

## 1. Objective

Select a small, technically straightforward internal governance shortlist from actual `kobi_substances`, without altering registry, combinations, Medicine governance, personal medications or Treatment. Selection concerns identity labeling, existing relationships and representability, not effectiveness, safety, prescribing preference, popularity or medical superiority.

Reviewed authoritative schema and the implemented identity/authorization/governance/policy/eligibility/repository code, Phase 3.3–3.13 documentation and foundation/governance/pilot test contracts. Current implementation supports explicit S references, one-to-sixteen distinct ingredient references, independently current chemical approval before composition creation, scoped submission/review, and separately authorized post-approval pilot enrollment. It does not infer chemical equivalence, resolve missing form taxonomy or import legacy combinations. Phase 3.13 internal availability is separately disabled and manifest-bound. Source code capability is distinct from normal-instance deployment.

## 2. Actual data snapshot

Normal configured database: `medical1`; MariaDB `10.4.27-MariaDB`; port 3306. Recorded SQL mode: `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`, unchanged. Main observation: **2026-10-09 19:04:59 UTC** (2026-10-10 00:34:59 Asia/Calcutta); repeat: **19:09:25 UTC**. These are separate SELECT observations, not a frozen multi-table transaction. Repeated public-registry rows, combination members, forms, counts, distributions, duplicate summaries and relevant column metadata matched byte-for-byte canonical JSON fingerprints.

| Measurement | Actual current result |
| --- | ---: |
| Registry rows | 2,224 |
| Active AND searchable | 2,224 |
| CANDIDATE AND UNKNOWN | 2,224 |
| Nonblank verification source / reviewer / review date | 0 / 0 / 0 |
| Stored naming_standard=WHO_INN | 2,224; a stored default, not verified provenance |
| Byte-identical canonical-name duplicate groups | 0 |
| Byte-identical normalized-name duplicate groups | 0 |
| Formatting-collision groups / affected rows | 18 / 39 |
| Legacy combinations / member rows | 2,327 / 6,671 |
| Distinct substances explicitly linked to combinations | 1,265 |
| Combinations with multiple members | 2,327; member counts 2–12 |
| Members with missing substance link | 0 |
| Chemical forms / form components | 0 / 0 |
| Legacy identity reviews / WHO-INN evidence rows | 0 / 0 |

Live metadata found `kobi_medicine_chemical_refs`, `kobi_medicine_identities`, `medicine_authority_grants`, `medicine_governance_events`, `medicine_registry_assessments`, `medicine_registry_lifecycle_events` and `medicine_pilot_records` **absent**, not deployed-empty. Their counts are N/A. Registry status alone therefore cannot establish current Medicine authority. No private medication records, actual reviewer identities or credentials were fetched.

Registry-row fingerprint: `2d9f03fd16a34ac6e4522aa4953330c5d06d0301357dd141b9aced9caabe4d25`; combination-member fingerprint: `09814c645eaa65da71eaf82c7c0dcb8207a7c07cd4c7add5515540e98f2eaa0f`. Fingerprints use PowerShell compressed JSON with arrays preserved and UTF-8 SHA-256; volatile observation timestamps are excluded. Repeated equality cannot rule out an intervening concurrent edit that was reversed.

## 3. Selection criteria

Prefer a readable single canonical-style label, no combination link or join marker, no formatting collision, no explicit salt/hydrate/formulation marker, and no qualified name peer requiring representative review. A future one-ingredient composition would explicitly reference its reviewed S(id); this audit creates neither an adapter nor a composition.

“Single substance” below means **a single label with no observed combination relationship or obvious join marker**, not chemically established single-ingredient content. “Clean” means **no detected name/form marker, typed form row or qualified exact-prefix peer**; it does not assert base/salt/hydrate/stereochemical identity. All identity_type values remain UNKNOWN.

There are 601 eligible rows tied at the maximum observed score of 90. The ten Group A entries are a deliberately curated subset of that tied pool, chosen for readable canonical-style labels with varied endings/lengths and no detected structural complication. They are not claimed to be uniquely best among all 601, selected by lowest ID, alphabetically ranked or scientifically proven more established. The ordered list is a stable review presentation; ties have no relative chemical/medical significance. Full-pool tie order uses SHA-256 of the stored normalized label solely for reproducibility.

## 4. Exclusion criteria

The audit helper implements these reproducible **conservative name-level screens**, not scientific taxonomic decisions:

- **Combination:** any explicit member link; matching unlinked component label if present; or a literal `+`, `/`, or whole word `and`, `with`, `combination`, `combo`. Being an ingredient in a combination is enough to exclude it from this first pilot; this does not mean the substance itself is a combination product.
- **Collision:** NFC normalize, Unicode case fold, remove Unicode punctuation, separators and whitespace; exclude every member of a repeated resulting key. This is formatting triage only and must never establish chemical equality or a merge.
- **Malformed/incomplete:** blank/control-character labels, fewer than four characters, or whole-word DS/SR/CR/XL/MR qualifiers. These are pilot-complexity holds, not approved corrections.
- **Form concern:** an actual form link, or whole-word hydrochloride, hydrobromide, bromide, chloride, sulfate, sulphate, phosphate, acetate, succinate, tartrate, maleate, mesylate, mesilate, besilate, citrate, fumarate, sodium, potassium, calcium, hydrate, hydrated, anhydrous, monohydrate, dihydrate, hemihydrate, solvate, ester, palmitate, pamoate, tosylate, tosilate, disulfate or disulphate. Hold these from the first pilot as `requires_unresolved_chemical_form_model`; the label alone does not prove a parent/form mapping or that future S representation is impossible.
- **Ambiguous/complex identity:** whole-word extract, oil, powder, vaccine, immunoglobulin, insulin, lysate, collagen, vitamin(s), peptide, decapeptide, phospholipid, probiotic, bacterial, bacillus, saccharomyces, botulinum, toxin, interferon, erythropoietin, gonadotropin/gonadotrophin, hormone, glycosides, enzyme(s), heparin, or label endings mab/cept/ase. This conservative complexity hold does not claim all such labels are malformed or chemically ambiguous. Also hold the observed near-spelling pairs Aciclovir/Acyclovir (20/29), Valaciclovir/Valacyclovir (2141/2142), Cilostazol/Cilostazole (423/424); no equivalence or preferred spelling is asserted.
- **Other:** whole-word injection, implant, liposomal, liposome, micronized, dispersion, nanodispersion, concentrate, solution, suspension, tablet, capsule, recombinant; or inactive/unsearchable status. These are formulation/unsupported-complexity holds.

An otherwise unscreened label with a separate exact case-insensitive `label + space + qualifier` peer is `possible_form_issue`, not a resolved relationship. Such rows lose form-simplicity points and are not in the recommended fifteen. Screens may overexclude and miss chemical relationships that are invisible in labels; retained candidates still need professional appraisal.

## 5. Scoring methodology

Bounded score = sum of seven components, maximum 100. Hard exclusions override score. Scores are technical/governance triage, not confidence probabilities or medical rankings.

| Component | Exact points |
| --- | --- |
| Clear identity label, max 20 | 20 for ASCII `[A-Z][a-z]{3,19}`; 15 for `[A-Z][A-Za-z ]{3,59}` otherwise; 0 otherwise |
| Apparent single ingredient, max 20 | 20 only if no combination screen; otherwise 0 |
| Simple clean naming, max 10 | 10 for the first pattern above; 5 for the second; otherwise 0 |
| No formatting collision, max 10 | 10 if not in a collision; otherwise 0 |
| No detected form dependency, max 20 | 20 if no form/formulation screen and no qualified prefix peer; 5 if only a peer exists; 0 for a form/formulation screen |
| Current S/ingredient representability, max 10 | 10 if no combination, ambiguous/complex, form, formulation or malformed screen; otherwise 0. This establishes only prospective structural fit |
| Appraised authoritative identity evidence, max 10 | 0 for every row: no appraised evidence was established in this phase; no availability points are invented |

Observed eligible-pool distribution: 601 score 90; 79 score 80; 31 score 75; 2 score 65; 10 score 60. Total unscreened pool: **723**. Group A: `20+20+10+10+20+10+0=90`. Group B: `15+20+5+10+20+10+0=80`.

Evidence availability is a future prerequisite. Source likelihood, popularity, efficacy, safety, dosage and prescribing preference add no points. No external medical verification/search or source appraisal occurred.

## 6. Group A — Strong pilot candidates

All ten have observed active/searchable **1/1**, registry **CANDIDATE/UNKNOWN**, no verification source/reviewer/date, no combination-member link or join marker, no collision, no qualified prefix peer and form classification **clean** under the limited screen. Each is an apparent single label only; actual scientific identity and authoritative evidence remain outstanding.

| Review position (tied score) | substance_id | Exact stored canonical_name | Score | Identity concern / pilot rationale | Evidence prerequisite |
| --- | ---: | --- | ---: | --- | --- |
| 1 | 54 | Allopurinol | 90 | Readable one-word label, `-ol` ending; no detected structural complication | Exact entity/representative and identity evidence appraisal required |
| 2 | 319 | Carbamazepine | 90 | Longer one-word label, `-ine` ending; clean one-reference intake case | Same; no source verified |
| 3 | 15 | Acetazolamide | 90 | One-word `-amide` label; no salt/form qualifier found | Same; no chemical basis inferred |
| 4 | 156 | Azathioprine | 90 | Distinct one-word spelling for canonical key/audit handling | Same; source and naming provenance outstanding |
| 5 | 112 | Anastrozole | 90 | One-word `-zole` pattern adds label variation | Same; no efficacy/safety assessment |
| 6 | 481 | Colchicine | 90 | Shorter one-word label without link/collision/form markers | Same; exact identity requires professional review |
| 7 | 2064 | Topiramate | 90 | One-word `-ate` label; no whole-word form marker or prefix peer | Same; suffix is not a chemical-form conclusion |
| 8 | 1685 | Primidone | 90 | Compact one-word label, `-one` ending; simple prospective S member | Same; no molecular/form data assumed |
| 9 | 1776 | Ribavirin | 90 | One-word `-irin` label, different spelling pattern | Same; authoritative record appraisal outstanding |
| 10 | 2219 | Zonisamide | 90 | Second `-amide` spelling provides a comparable simple intake case | Same; exact scientific identity unverified |

For every entry: **single substance = apparent single label; collision status = none detected; combination status = no link/marker; chemical-form status = clean screen; identity concern = UNKNOWN, no chemical/evidence proof**. Evidence appraisal should seek the applicable Tier 1–3 identity material; initial Phase 3.12 authoritative acceptance remains appraised tiers 1–2, with tier 3 requiring explicit policy approval. “Likely reviewable” is not “verified by regulator.”

Diversity is limited to name patterns/lengths and future explicit single-reference representation. All share the simplest ingredient shape. No presentation/dosage/route is inferred; Phase 3.13 excludes presentation enrollment. No ambiguous candidate was promoted merely to increase diversity.

## 7. Group B — Possible candidates with a minor labeling issue

All five have active/searchable **1/1**, CANDIDATE/UNKNOWN, no recorded source/reviewer/date, no combination link/marker, no collision, no prefix peer and form classification **clean** under the screen. These are alternatives, not extra approved pilot members. They score **80** because multiword naming adds a whole-label precision check; chemistry is still unverified.

| substance_id | Exact stored canonical_name | Score | Issue / rationale | Evidence prerequisite |
| ---: | --- | ---: | --- | --- |
| 93 | Aminocaproic Acid | 80 | Two-word label; appraisal must cover the complete named entity, without treating its second word as an optional alias | Whole-label identity and chemical basis; no inferred salt conversion |
| 133 | Arsenic Trioxide | 80 | Two-word label with a compositional discriminator; confirm the entire precise label | Exact entity specification, not a broader element/class label |
| 1404 | Mycophenolic Acid | 80 | Two-word label; do not silently substitute a related spelling or formulation | Complete named entity and representative appraisal |
| 1418 | Nalidixic Acid | 80 | Two-word alternative for testing space-preserving labels | Exact complete identity; no form mapping established |
| 2057 | Tolfenamic Acid | 80 | Two-word alternative for audit/key label precision | Current authoritative whole-label identity appraisal |

No scientific relationships between these labels or other registry entities were inferred. The minor issue is explicit multiword precision, not a claim that an acid necessarily requires an unresolved form model.

## 8. Group C — Exclusion summary

| Reason | Rows triggering screen (overlap allowed) | Primary excluded rows |
| --- | ---: | ---: |
| Combination relationship/join label | 1,266 | 1,266 |
| Formatting collision | 39 | 2 |
| Ambiguous/complex identity or observed near spelling | 260 | 132 |
| Chemical-form concern | 271 | 94 |
| Malformed/incomplete/abbreviated label | 3 | 1 |
| Other formulation/status concern | 17 | 6 |
| **Distinct primary exclusions** | — | **1,501** |
| **Unscreened pool** | — | **723** |

Primary counting precedence: combination → collision → malformed → form concern → ambiguous/complex → other. Primary counts partition all 2,224 rows; overlapping counts must not be summed. All **39** collision rows are excluded, including the **37** already counted primarily under combination. The two additional collision-only exclusions are 590 `Di Iodohydroxyquinoline` and 616 `Diiodohydroxyquinoline`.

Combination count is 1,265 distinct explicitly linked substances plus 1243 `Live Freeze Dried Lactic Acid Bacteria And Bifidobacteria`, whose name has an explicit `And` join but no member link. No missing substance links require name-only inference. Familiar-looking examples deliberately excluded because of actual member relationships: 275 `Caffeine` (22 member rows), 1332 `Metformin` (49), 1552 `Paracetamol` (107). This is a first-pilot relationship exclusion, not a claim these labels denote combination products.

Representative remaining exclusions: 6 `Abiraterone Acetate`, 23 `Acotiamide Hydrochloride`, 87 `Amikacin Sulphate` (form-marker hold); 2 `Abatacept`, 3 `Abciximab` (conservative label-complexity hold); 20/29 `Aciclovir`/`Acyclovir` and 2141/2142 `Valaciclovir`/`Valacyclovir` (unresolved near-spelling identity disposition); 68 `Alpha Ketoanalogue Ds` (abbreviated qualifier); 573 `Dexamethasone Intravitreal Implant`, 1535 `Paclitaxel Injection Concentrate For Nanodispersion`, 1697 `Progesterone (Natural Micronized)` (formulation rather than simple identity label).

### Specific formatting-collision rows excluded

| substance_id group | Exact observed labels |
| --- | --- |
| 65 / 66 | Alpha Glycerylphosphoryl Choline / Alpha Glycerylphosphorylcholine |
| 330 / 331 | Carboxymethyl Cellulose / Carboxymethylcellulose |
| 472 / 480 | Co Enzyme Q10 / Coenzyme Q10 |
| 474 / 475 | Coal Tar / Coaltar |
| 590 / 616 | Di Iodohydroxyquinoline / Diiodohydroxyquinoline |
| 764 / 765 | Ethinyl Estradiol / Ethinylestradiol |
| 991 / 996 / 997 / 998 | Hydroxy Propyl Methyl Cellulose / Hydroxypropyl Methylcellulose / Hydroxypropylmethyl Cellulose / Hydroxypropylmethylcellulose |
| 1002 / 1003 | Hyoscine Butyl Bromide / Hyoscine Butylbromide |
| 1134 / 1151 | L Glutathione / L-Glutathione |
| 1140 / 1142 | L Methyl Folate / L Methylfolate |
| 1141 / 1143 | L Methyl Folate Calcium / L Methylfolate Calcium |
| 1203 / 1206 | Levo Carnitine / Levocarnitine |
| 1343 / 1346 | Methyl Cellulose / Methylcellulose |
| 1345 / 1354 | Methyl Sulfonyl Methane / Methylsulfonylmethane |
| 1405 / 1406 | Myo Inositol / Myoinositol |
| 1408 / 1410 | N Acetyl Cysteine / N Acetylcysteine |
| 1880 / 1881 / 1882 | Sodium Carboxy Methyl Cellulose / Sodium Carboxymethyl Cellulose / Sodium Carboxymethylcellulose |
| 2184 / 2185 | Vitamin K2 7 / Vitamin K2-7 |

These 18 groups/39 rows were not corrected, merged or scientifically resolved. No member appears in either recommended group.

## 9. Limitations, safety and validation

All registry identities remain UNKNOWN/CANDIDATE with no recorded evidence or review. Name-level simplicity cannot establish molecular identity, provenance, base/salt equivalence, stereochemistry, ingredient purity, therapeutic effectiveness or actual availability of authoritative sources. The conservative screens are not an exhaustive pharmaceutical taxonomy and are not automatically approved production policies. Named reviewers must independently validate any chosen representative; no external medical verification was performed.

**Normal KOBI database was read-only; no INSERT/UPDATE/DELETE/DDL was performed.** The helper loads existing configuration privately through the existing Database class, without application bootstrap, sessions, logging, governance services or credential output. Every prepared statement passes a SELECT-only guard rejecting multi-statements, file output, locks and delay functions. Only public-registry rows/links and metadata/counts were fetched. No transaction, SQL-mode directive, enrollment command or migration was executed. Repeated metadata still shows governed tables absent; none were created. No private/Medicine/Treatment/disease data was modified. No Git history actions occurred.

Validation: **55 offline checks passed**, including repeat/reversed-input determinism, fifteen unique actual IDs, collision/combination/form exclusions, transparent score sums, registry partitioning, evidence absence, rejection after injecting form/formulation/join markers and ten forbidden-query guards. Seven independently repeated snapshot-field fingerprints matched. PHP syntax and final diff checks passed. SHA-256 comparison of **296 pre-existing files** found no modification or removal; only this report and the SELECT-only audit helper were added. Temporary public snapshot/analysis files were removed after validation. Existing regression suites were not rerun because this phase changes no application code/schema.

Reproduction uses explicit modes; the test/analyze modes read a generated temporary snapshot and do not connect to a database themselves:

```powershell
& D:\xampp\php\php.exe tests/medicine-candidate-selection.php snapshot |
  Set-Content tests/.medicine314-snapshot.json -Encoding utf8
& D:\xampp\php\php.exe tests/medicine-candidate-selection.php test
& D:\xampp\php\php.exe tests/medicine-candidate-selection.php analyze
Remove-Item -LiteralPath tests/.medicine314-snapshot.json
```

The `snapshot` mode explicitly connects to the configured normal registry, requires medical1/3306, and sends only guarded SELECTs. A changed shortlist condition fails with “requires renewed audit” rather than silently accepting outdated recommendations. Protect output like other internal registry audit material; no credentials/private health data are included.

## 10. Recommended next action

Owner reviews the ten Group A recommendations and five Group B alternatives, then explicitly approves the final five to ten IDs and their intended exact representatives. Obtain actual appraised identity evidence, professionally verified independent reviewer and named administrator/deployment operator. Confirm/deploy the authoritative Medicine foundation through separately approved Phase 3.13 procedures before any real governed intake/enrollment. A shortlist does not satisfy those prerequisites.

**Final pilot identities require explicit owner approval before Phase 3.15.** No Phase 3.15 implementation, real identity creation, enrollment, activation or publication occurred here.
