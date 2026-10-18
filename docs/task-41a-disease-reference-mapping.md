# KOBI Core v0.1.0 — Step 41A: Disease Reference Mapping Preparation

Preparation date: 13 October 2026 (Asia/Calcutta).
Step 41A-2 verification date: 13 October 2026 (Asia/Calcutta).
Step 41A-3 editorial closure date: 13 October 2026 (Asia/Calcutta).
Scope: eight specified diseases; five English sections only.
This document is RESEARCH / EDITORIAL EVIDENCE. It is not a PERSISTED APPLICATION MAPPING.

## A. Executive Summary

Task 41A-3 status: PASS — final editorial closure completed; two sections intentionally have no confirmed public source mapping.

- Diseases evaluated: 8.
- Section cells evaluated: 40 (8 × 5).
- Previous Step 41A counts: 36 VERIFIED, 4 UNVERIFIED, 0 NO_CONFIRMED_SOURCE.
- Previous Step 41A-2 counts: 38 VERIFIED, 2 UNVERIFIED, 0 NO_CONFIRMED_SOURCE.
- VERIFIED section cells: 38.
- UNVERIFIED section cells: 0.
- NO_CONFIRMED_SOURCE section cells: 2.
- Full eight-disease mapping ready for implementation: YES; omit public source blocks for Migraine overview and Acne overview. All previously VERIFIED mappings are unchanged.
- Historical source inventory: 32 deduplicated URLs. A verified section can have several complementary sources; 38 is a cell count, not a count of source links.
- No references feature, manifest, service or public UI was implemented.

All earlier references were recovered from the existing task history. The project-file search did not find a separate disease research report or machine-readable section mapping. Earlier references were not replaced with newly selected authoritative sources.

### Recovery provenance and current content check

The existing Codex conversation is “Audit KOBI Core architecture”, thread ID 01a112da-0554-7811-b6a0-29490ca2ec8b.

| Record | Recovered evidence |
|---|---|
| Task 21, turn 01a12c92-619c-7bc1-be23-9e785160318d | Original five-disease research, source IDs M1–M3, B1–B7, D1–D3, C1–C6 and P1–P5; explicit field-specific citations and exact proposed text. |
| Task 22 and its timestamp-clarification continuation | Authorization/application history for those approved five-disease English fields. |
| Task 36, turn 01a12e44-2bfd-7ec0-8a00-9d4021d4c5b3 | Original Typhoid Fever, Boils and Acne source inventory, dated source metadata, explicit proposed-field citations and exact text. |
| Task 37, turn 01a12e49-f3d9-79e2-9c96-50e72957ff97 | Approved application of those three diseases; prior report validated all 15 values. |

For this task, normal KOBI was queried in a READ ONLY transaction for only disease_id, overview_en, causes_en, risk_factors_en, diagnosis_en and prevention_en of the eight IDs. All 40 current fields exactly matched the recovered proposals: 25/25 Task 21 fields and 15/15 Task 36 fields. This exact equality establishes which current wording the historical references supported; it does not itself prove every historical citation supports every clause.

The original URLs were reopened and their relevant headings/date labels checked. During Step 41A, D1 failed direct retrieval with HTTP 503; indexed search text was available but was not used as substantive verification. Step 41A-2 directly reopened the original CDC URL successfully and resolved its Overview and Causes cells. M2's clean URL initially timed out, but the same page/path loaded with the search engine's linkId query parameter; its public citation remains the original clean URL. No different source was substituted.

VERIFIED means explicit historical section association plus checked support for the stated current topic. Complementary sources support different parts of a section; none is assigned blanket disease-wide coverage. Step 41A-3 closes the two remaining cells as NO_CONFIRMED_SOURCE because the recovered editorial evidence does not sufficiently establish their public section-level relationships, even though potentially relevant sources are known. This classification supersedes the earlier provisional UNVERIFIED treatment.

NO_CONFIRMED_SOURCE means: "No source relationship is sufficiently established for public section-level attribution from the currently recovered editorial evidence." It does not describe medically unsupported content; it describes the absence of a confirmed mapping for the public reference feature.

## B. Disease/Section Mapping Matrix

Column labels correspond exactly to overview, causes, risk_factors, diagnosis and prevention.

| Disease | Overview | Causes | Risk Factors | Diagnosis | Prevention |
|---|---|---|---|---|---|
| Migraine (ID 1) | NO_CONFIRMED_SOURCE | VERIFIED | VERIFIED | VERIFIED | VERIFIED |
| Celiac Disease (ID 120) | VERIFIED | VERIFIED | VERIFIED | VERIFIED | VERIFIED |
| Breast Cancer (ID 200) | VERIFIED | VERIFIED | VERIFIED | VERIFIED | VERIFIED |
| Diabetic Ketoacidosis (ID 364) | VERIFIED | VERIFIED | VERIFIED | VERIFIED | VERIFIED |
| Pulmonary Hypertension (ID 398) | VERIFIED | VERIFIED | VERIFIED | VERIFIED | VERIFIED |
| Typhoid Fever (ID 358) | VERIFIED | VERIFIED | VERIFIED | VERIFIED | VERIFIED |
| Boils (ID 217) | VERIFIED | VERIFIED | VERIFIED | VERIFIED | VERIFIED |
| Acne (ID 201) | NO_CONFIRMED_SOURCE | VERIFIED | VERIFIED | VERIFIED | VERIFIED |

Each disease occurs once in the main matrix, with exactly five classified cells. No Hindi section coverage is inferred.

## C. Detailed Verified Mapping

Every row is a VERIFIED disease/section mapping to the indicated historically used source. Source IDs are document-local editorial identifiers, not database IDs. Date and Date type retain the source's labels and precision; “not stated” is not replaced with a KOBI timestamp. Sources sharing a cell are complementary, not competing alternatives. The bounded supported topics in the reason are the intended coverage.

| Disease | Disease ID | Section | Source ID | Organization / publisher | Source title / public URL | Date | Date type | Historical association and evidence / reason |
|---|---|---|---|---|---|---|---|---|
| Migraine | 1 | causes | M1 | NHS | [Migraine](https://www.nhs.uk/conditions/migraine/) | 10 March 2026 | Reviewed | Task 21 causes mapping; brain changes, genetic susceptibility and variable individual triggers correspond to the stored Causes text. |
| Migraine | 1 | causes | M3 | NIH / National Library of Medicine, MedlinePlus | [Migraine](https://medlineplus.gov/migraine.html) | 20 November 2025 | Updated | Task 21 causes mapping; brain changes, genetic susceptibility and variable individual triggers correspond to the stored Causes text. |
| Migraine | 1 | risk_factors | M3 | NIH / National Library of Medicine, MedlinePlus | [Migraine](https://medlineplus.gov/migraine.html) | 20 November 2025 | Updated | Task 21 risk mapping; family history and sex differences are separated from hormonal attack triggers in the same cited material. |
| Migraine | 1 | risk_factors | M1 | NHS | [Migraine](https://www.nhs.uk/conditions/migraine/) | 10 March 2026 | Reviewed | Task 21 risk mapping; family history and sex differences are separated from hormonal attack triggers in the same cited material. |
| Migraine | 1 | diagnosis | M3 | NIH / National Library of Medicine, MedlinePlus | [Migraine](https://medlineplus.gov/migraine.html) | 20 November 2025 | Updated | Task 21 diagnosis mapping; clinical history/examination and selective exclusion tests, plus NHS urgent warning signs, match the stored section. |
| Migraine | 1 | diagnosis | M1 | NHS | [Migraine](https://www.nhs.uk/conditions/migraine/) | 10 March 2026 | Reviewed | Task 21 diagnosis mapping; clinical history/examination and selective exclusion tests, plus NHS urgent warning signs, match the stored section. |
| Migraine | 1 | prevention | M1 | NHS | [Migraine](https://www.nhs.uk/conditions/migraine/) | 10 March 2026 | Reviewed | Task 21 prevention mapping; regular habits, hydration, activity, stress management and a migraine diary match the source self-care guidance. |
| Celiac Disease | 120 | overview | C1 | NIH / NIDDK | [Definition & Facts for Celiac Disease](https://www.niddk.nih.gov/health-information/digestive-diseases/celiac-disease/definition-facts) | October 2020 | Reviewed (month precision) | Task 21 overview mapping; immune-mediated intestinal damage and allergy/sensitivity distinctions, with variable symptoms, match the stored section. |
| Celiac Disease | 120 | overview | C2 | NIH / NIDDK | [Symptoms & Causes of Celiac Disease](https://www.niddk.nih.gov/health-information/digestive-diseases/celiac-disease/symptoms-causes) | October 2020 | Reviewed (month precision) | Task 21 overview mapping; immune-mediated intestinal damage and allergy/sensitivity distinctions, with variable symptoms, match the stored section. |
| Celiac Disease | 120 | causes | C2 | NIH / NIDDK | [Symptoms & Causes of Celiac Disease](https://www.niddk.nih.gov/health-information/digestive-diseases/celiac-disease/symptoms-causes) | October 2020 | Reviewed (month precision) | Task 21 causes mapping; gluten-triggered immune response, DQ2/DQ8 susceptibility and uncertain other influences correspond to the stored text. |
| Celiac Disease | 120 | causes | C3 | NHS | [Causes - Coeliac disease](https://www.nhs.uk/conditions/coeliac-disease/causes/) | 31 March 2023 | Reviewed | Task 21 causes mapping; gluten-triggered immune response, DQ2/DQ8 susceptibility and uncertain other influences correspond to the stored text. |
| Celiac Disease | 120 | risk_factors | C1 | NIH / NIDDK | [Definition & Facts for Celiac Disease](https://www.niddk.nih.gov/health-information/digestive-diseases/celiac-disease/definition-facts) | October 2020 | Reviewed (month precision) | Task 21 risk mapping; close relatives and the named autoimmune/chromosomal associations are explicitly covered. |
| Celiac Disease | 120 | risk_factors | C3 | NHS | [Causes - Coeliac disease](https://www.nhs.uk/conditions/coeliac-disease/causes/) | 31 March 2023 | Reviewed | Task 21 risk mapping; close relatives and the named autoimmune/chromosomal associations are explicitly covered. |
| Celiac Disease | 120 | diagnosis | C4 | NIH / NIDDK | [Diagnosis of Celiac Disease](https://www.niddk.nih.gov/health-information/digestive-diseases/celiac-disease/diagnosis) | October 2020 | Reviewed (month precision) | Task 21 diagnosis mapping; antibodies, appropriate biopsy, genetic-test limitations and the pretesting gluten warning are explicitly covered. |
| Celiac Disease | 120 | diagnosis | C5 | NIH / NIDDK | [Eating, Diet, & Nutrition for Celiac Disease](https://www.niddk.nih.gov/health-information/digestive-diseases/celiac-disease/eating-diet-nutrition) | October 2020 | Reviewed (month precision) | Task 21 diagnosis mapping; antibodies, appropriate biopsy, genetic-test limitations and the pretesting gluten warning are explicitly covered. |
| Celiac Disease | 120 | prevention | C5 | NIH / NIDDK | [Eating, Diet, & Nutrition for Celiac Disease](https://www.niddk.nih.gov/health-information/digestive-diseases/celiac-disease/eating-diet-nutrition) | October 2020 | Reviewed (month precision) | Task 21 prevention mapping; postdiagnosis intestinal protection and nutritional/complication follow-up support the current management-versus-onset distinction. |
| Celiac Disease | 120 | prevention | C6 | NIH / NIDDK | [Treatment for Celiac Disease](https://www.niddk.nih.gov/health-information/digestive-diseases/celiac-disease/treatment) | October 2020 | Reviewed (month precision) | Task 21 prevention mapping; postdiagnosis intestinal protection and nutritional/complication follow-up support the current management-versus-onset distinction. |
| Breast Cancer | 200 | overview | B1 | CDC | [Breast Cancer Basics](https://www.cdc.gov/breast-cancer/about/index.html) | 15 May 2024; 2 October 2026; 2 October 2026 | Published; Updated; Reviewed | Task 21 overview mapping; duct/lobule origins, invasion, occurrence in men and possible presenting changes are covered by the paired sources. |
| Breast Cancer | 200 | overview | B2 | NIH / National Cancer Institute | [What Is Breast Cancer?](https://www.cancer.gov/types/breast/what-is-breast-cancer) | 2 December 2025 | Updated | Task 21 overview mapping; duct/lobule origins, invasion, occurrence in men and possible presenting changes are covered by the paired sources. |
| Breast Cancer | 200 | causes | B4 | NIH / National Cancer Institute | [What Is Cancer?](https://www.cancer.gov/about-cancer/understanding/what-is-cancer) | 28 August 2026 | Updated | Task 21 causes mapping; acquired/inherited genetic changes, BRCA susceptibility and the distinction from probabilistic risk match the stored Causes. |
| Breast Cancer | 200 | causes | B3 | NIH / National Cancer Institute | [Breast Cancer Causes and Risk Factors](https://www.cancer.gov/types/breast/causes-risk-factors) | 2 December 2025 | Updated | Task 21 causes mapping; acquired/inherited genetic changes, BRCA susceptibility and the distinction from probabilistic risk match the stored Causes. |
| Breast Cancer | 200 | risk_factors | B3 | NIH / National Cancer Institute | [Breast Cancer Causes and Risk Factors](https://www.cancer.gov/types/breast/causes-risk-factors) | 2 December 2025 | Updated | Task 21 risk mapping; age, history, genetics, breast density, reproductive factors, alcohol, weight and inactivity match the named risks. |
| Breast Cancer | 200 | diagnosis | B6 | NIH / National Cancer Institute | [Breast Cancer Screening](https://www.cancer.gov/types/breast/screening) | 2 December 2025 | Updated | Task 21 diagnosis mapping; symptom-free screening versus diagnostic evaluation, imaging, biopsy, biomarkers and extent are explicitly covered. |
| Breast Cancer | 200 | diagnosis | B5 | NIH / National Cancer Institute | [How Breast Cancer Is Diagnosed](https://www.cancer.gov/types/breast/diagnosis) | 2 December 2025 | Posted (publication) | Task 21 diagnosis mapping; symptom-free screening versus diagnostic evaluation, imaging, biopsy, biomarkers and extent are explicitly covered. |
| Breast Cancer | 200 | prevention | B7 | NIH / National Cancer Institute | [Breast Cancer Prevention](https://www.cancer.gov/types/breast/causes-risk-factors/prevention) | 2 December 2025 | Updated | Task 21 prevention mapping; limited risk reduction and professional discussion are distinct from the purpose of screening. |
| Breast Cancer | 200 | prevention | B6 | NIH / National Cancer Institute | [Breast Cancer Screening](https://www.cancer.gov/types/breast/screening) | 2 December 2025 | Updated | Task 21 prevention mapping; limited risk reduction and professional discussion are distinct from the purpose of screening. |
| Diabetic Ketoacidosis | 364 | overview | D1 | CDC | [Diabetic Ketoacidosis](https://www.cdc.gov/diabetes/about/diabetic-ketoacidosis.html) | 15 May 2024 | Displayed page date; type not explicitly labelled in retrieved page text | Step 41A-2 directly verified the original Task 21 citation: Overview explains insufficient insulin, fat breakdown and acid ketone accumulation; Signs and symptoms/Testing cover diabetes types, warning signs and emergency significance. |
| Diabetic Ketoacidosis | 364 | overview | D2 | NHS | [Diabetic ketoacidosis](https://www.nhs.uk/conditions/diabetic-ketoacidosis/) | 8 June 2023 | Reviewed | Original Task 21 companion citation: opening description, Symptoms and Immediate action required support urgent hospital assessment and the stored drowsiness/confusion wording. |
| Diabetic Ketoacidosis | 364 | causes | D1 | CDC | [Diabetic Ketoacidosis](https://www.cdc.gov/diabetes/about/diabetic-ketoacidosis.html) | 15 May 2024 | Displayed page date; type not explicitly labelled in retrieved page text | Step 41A-2 directly verified the original sole Task 21 citation: Overview covers insulin deficiency/fat breakdown, Causes lists illness and missed/pump-delivery insulin, Signs notes undiagnosed diabetes, and Treatment identifies infection as an underlying illness. |
| Diabetic Ketoacidosis | 364 | risk_factors | D3 | ADA, EASD, JBDS, AACE and DTS; published by Diabetologia / Springer Nature | [Hyperglycaemic crises in adults with diabetes: a consensus report](https://link.springer.com/article/10.1007/s00125-024-06183-8) | 22 June 2024 | Published | Task 21 explicitly cited D3 for Risk Factors; its introduction and risk-factor discussion cover insufficient insulin, illness, initial diabetes presentation and SGLT2-associated risk. |
| Diabetic Ketoacidosis | 364 | diagnosis | D3 | ADA, EASD, JBDS, AACE and DTS; published by Diabetologia / Springer Nature | [Hyperglycaemic crises in adults with diabetes: a consensus report](https://link.springer.com/article/10.1007/s00125-024-06183-8) | 22 June 2024 | Published | Task 21 diagnosis mapping; combined ketone/acidity findings, beta-hydroxybutyrate, additional assessment and euglycemic DKA match the nonnumeric stored section. |
| Diabetic Ketoacidosis | 364 | prevention | D2 | NHS | [Diabetic ketoacidosis](https://www.nhs.uk/conditions/diabetic-ketoacidosis/) | 8 June 2023 | Reviewed | Task 21 prevention mapping; agreed care plans, glucose/ketone monitoring, sick-day rules and contacting the care team match the stored section. |
| Pulmonary Hypertension | 398 | overview | P1 | NIH / NHLBI | [Pulmonary Hypertension - What Is Pulmonary Hypertension?](https://www.nhlbi.nih.gov/health/pulmonary-hypertension) | 1 May 2023 | Updated | Task 21 overview mapping; increased pulmonary pressure, heart strain, the PAH subset and named symptoms are supported. |
| Pulmonary Hypertension | 398 | overview | P5 | NIH / NHLBI | [Pulmonary Hypertension - Symptoms](https://www.nhlbi.nih.gov/health/pulmonary-hypertension/symptoms) | 24 March 2022 | Updated | Task 21 overview mapping; increased pulmonary pressure, heart strain, the PAH subset and named symptoms are supported. |
| Pulmonary Hypertension | 398 | causes | P4 | European Society of Cardiology | [Diagnosis and management of pulmonary hypertension due to left heart disease or pulmonary disease](https://www.escardio.org/communities/councils/cardiology-practice/education/cardiopractice/diagnosis-and-management-of-pulmonary-hypertension-due-to-left-heart-disease-or-pulmonary-disease/) | 4 August 2026 | Displayed article date; not explicitly labelled publication/update/review | Task 21 causes mapping; the article classification table explicitly lists the five groups and their distinct mechanisms. |
| Pulmonary Hypertension | 398 | risk_factors | P2 | NIH / NHLBI | [Pulmonary Hypertension - Causes and Risk Factors](https://www.nhlbi.nih.gov/health/pulmonary-hypertension/causes) | 24 March 2022 | Updated | Task 21 risk mapping; associated heart/lung disease, inherited factors, pulmonary clots, scleroderma and relevant exposures are covered. |
| Pulmonary Hypertension | 398 | diagnosis | P3 | NIH / NHLBI | [Pulmonary Hypertension - Diagnosis](https://www.nhlbi.nih.gov/health/pulmonary-hypertension/diagnosis) | 24 March 2022 | Updated | Task 21 diagnosis mapping; history, tests and cause investigation are combined with the explicit echocardiography/catheterization distinction. No numeric threshold is mapped. |
| Pulmonary Hypertension | 398 | diagnosis | P4 | European Society of Cardiology | [Diagnosis and management of pulmonary hypertension due to left heart disease or pulmonary disease](https://www.escardio.org/communities/councils/cardiology-practice/education/cardiopractice/diagnosis-and-management-of-pulmonary-hypertension-due-to-left-heart-disease-or-pulmonary-disease/) | 4 August 2026 | Displayed article date; not explicitly labelled publication/update/review | Task 21 diagnosis mapping; history, tests and cause investigation are combined with the explicit echocardiography/catheterization distinction. No numeric threshold is mapped. |
| Pulmonary Hypertension | 398 | prevention | P2 | NIH / NHLBI | [Pulmonary Hypertension - Causes and Risk Factors](https://www.nhlbi.nih.gov/health/pulmonary-hypertension/causes) | 24 March 2022 | Updated | Task 21 prevention mapping; prevention limits and individual risk-factor assessment match the cautious stored section. |
| Typhoid Fever | 358 | overview | T2 | CDC | [Clinical Guidance for Typhoid Fever and Paratyphoid Fever](https://www.cdc.gov/typhoid-fever/hcp/clinical-guidance/index.html) | 24 April 2024 | Displayed page date; not explicitly labelled publication/update/review | Task 36 overview citation; separate organisms, systemic illness, symptoms and poor clinical distinction between infections match the stored text. |
| Typhoid Fever | 358 | causes | T1 | CDC | [About Typhoid Fever and Paratyphoid Fever](https://www.cdc.gov/typhoid-fever/about/index.html) | 25 April 2024 | Displayed page date; not explicitly labelled publication/update/review | Task 36 causes citation; stool shedding, ongoing carriage and swallowing bacteria in contaminated food/water match the stored Causes. |
| Typhoid Fever | 358 | risk_factors | T1 | CDC | [About Typhoid Fever and Paratyphoid Fever](https://www.cdc.gov/typhoid-fever/about/index.html) | 25 April 2024 | Displayed page date; not explicitly labelled publication/update/review | Task 36 risk citation; unsafe food/water, sanitation, South Asian travel/exposure and absent vaccination are explicitly discussed. |
| Typhoid Fever | 358 | diagnosis | T2 | CDC | [Clinical Guidance for Typhoid Fever and Paratyphoid Fever](https://www.cdc.gov/typhoid-fever/hcp/clinical-guidance/index.html) | 24 April 2024 | Displayed page date; not explicitly labelled publication/update/review | Task 36 diagnosis citation; blood culture, multiple cultures and false-positive serology support the stored diagnostic summary. |
| Typhoid Fever | 358 | prevention | T3 | CDC | [Preventing Typhoid Fever and Paratyphoid Fever](https://www.cdc.gov/typhoid-fever/prevention/index.html) | 13 July 2026 | Displayed page date; not explicitly labelled publication/update/review | Task 36 prevention citation; food/water safety, handwashing, incomplete vaccine protection and no paratyphoid protection are covered. |
| Boils | 217 | overview | L1 | National Library of Medicine / MedlinePlus; A.D.A.M. Medical Encyclopedia | [Boils](https://medlineplus.gov/ency/article/001474.htm) | 14 October 2024 | Reviewed | Task 36 overview citation; infected follicles/tissue, a painful pus-filled lump and joining boils/carbuncles match the section. |
| Boils | 217 | causes | L1 | National Library of Medicine / MedlinePlus; A.D.A.M. Medical Encyclopedia | [Boils](https://medlineplus.gov/ency/article/001474.htm) | 14 October 2024 | Reviewed | Task 36 causes mapping; the common organism/follicle entry and harmless skin/nose carriage are explicitly covered. |
| Boils | 217 | causes | L3 | NHS | [Staph infection](https://www.nhs.uk/conditions/staphylococcal-infections/) | 11 February 2025 | Reviewed | Task 36 causes mapping; the common organism/follicle entry and harmless skin/nose carriage are explicitly covered. |
| Boils | 217 | risk_factors | L2 | NHS | [Boils](https://www.nhs.uk/conditions/boils/) | 20 June 2023 | Reviewed | Task 36 risk mapping; named susceptibility/contact factors and normal carriage support the non-blaming framing. |
| Boils | 217 | risk_factors | L3 | NHS | [Staph infection](https://www.nhs.uk/conditions/staphylococcal-infections/) | 11 February 2025 | Reviewed | Task 36 risk mapping; named susceptibility/contact factors and normal carriage support the non-blaming framing. |
| Boils | 217 | diagnosis | L1 | National Library of Medicine / MedlinePlus; A.D.A.M. Medical Encyclopedia | [Boils](https://medlineplus.gov/ency/article/001474.htm) | 14 October 2024 | Reviewed | Task 36 diagnosis mapping; visual examination, possible fluid culture and assessment of recurrence/wider infection match the section. |
| Boils | 217 | diagnosis | L2 | NHS | [Boils](https://www.nhs.uk/conditions/boils/) | 20 June 2023 | Reviewed | Task 36 diagnosis mapping; visual examination, possible fluid culture and assessment of recurrence/wider infection match the section. |
| Boils | 217 | prevention | L3 | NHS | [Staph infection](https://www.nhs.uk/conditions/staphylococcal-infections/) | 11 February 2025 | Reviewed | Task 36 prevention mapping; clean/covered cuts, handwashing, no shared personal items, dressings and imperfect prevention match the section. |
| Boils | 217 | prevention | L2 | NHS | [Boils](https://www.nhs.uk/conditions/boils/) | 20 June 2023 | Reviewed | Task 36 prevention mapping; clean/covered cuts, handwashing, no shared personal items, dressings and imperfect prevention match the section. |
| Acne | 201 | causes | A1 | NIH / NIAMS | [Acne: Overview, Symptoms, & Causes](https://www.niams.nih.gov/health-topics/acne) | July 2023 | Reviewed (month precision) | Task 36 causes mapping; oil, retained cells, normally present bacteria, inflammation and hormonal oil production are explicit. |
| Acne | 201 | risk_factors | A1 | NIH / NIAMS | [Acne: Overview, Symptoms, & Causes](https://www.niams.nih.gov/health-topics/acne) | July 2023 | Reviewed (month precision) | Task 36 risk mapping; age, hormones, family history and medicines are distinguished from stress/pressure/picking/scrubbing that worsen acne. |
| Acne | 201 | diagnosis | A2 | NIH / NIAMS | [Acne: Diagnosis, Treatment, and Steps to Take](https://www.niams.nih.gov/health-topics/acne/diagnosis-treatment-and-steps-to-take) | July 2023 | Reviewed (month precision) | Task 36 diagnosis mapping; skin examination, symptoms/history/medicines, hormonal history and conditional tests match the stored section. |
| Acne | 201 | prevention | A2 | NIH / NIAMS | [Acne: Diagnosis, Treatment, and Steps to Take](https://www.niams.nih.gov/health-topics/acne/diagnosis-treatment-and-steps-to-take) | July 2023 | Reviewed (month precision) | Task 36 prevention mapping; gentle cleansing, avoiding scrubbing/picking and non-comedogenic products with their limitations match the section. |

For DKA risk_factors, D3 was already expressly cited for this section in Task 21 and is readable now. Its introduction and risk-factor discussion also cover illness/insufficient insulin and initial presentation. Step 41A-2 directly confirmed D1 for its two outstanding Overview/Causes cells; the pre-existing D3 Risk Factors relationship is unchanged. This does not assign D3 to Overview or Causes.

For Boils risk_factors, the non-blaming sentence is cautious editorial framing supported by normal bacterial carriage, rather than a verbatim NHS claim. For Prevention sections, “risk reduction” is not a promise of prevention. For celiac prevention, sources support postdiagnosis protection/follow-up, not prevention of onset.

## D. Four-Cell Verification Results and Two-Cell Closure — Steps 41A-2 / 41A-3

Step 41A-2 reconsidered four outstanding cells and reread fifteen permitted English fields for IDs 1, 201 and 364 in a READ ONLY transaction; all matched the Step 41A snapshot. Step 41A-3 checked only the existing report, recovered Task 21/36 editorial evidence and project documentation for the two remaining cells. No additional explicit supporting association was recovered, so both close as NO_CONFIRMED_SOURCE under the user's final decision rule. No new external sources were introduced and no database connection was made in Step 41A-3. These are editorial decisions, not application-persisted associations. All 38 previously VERIFIED matrix cells and all 57 detailed source rows in section C remain unchanged.

| Disease | Disease ID | Section | Final status | Organization | Source title / public URL | Date | Date type | Evidence / reason and remaining uncertainty |
|---|---|---|---|---|---|---|---|---|
| Migraine | 1 | overview | NO_CONFIRMED_SOURCE | International Headache Society; NIH / National Library of Medicine; NHS | M2: [1.1 Migraine without aura](https://ichd-3.org/1-migraine/1-1-migraine-without-aura/); M3: [Migraine](https://medlineplus.gov/migraine.html); M1: [Migraine](https://www.nhs.uk/conditions/migraine/) | M2: not stated; M3: 20 November 2025; M1: 10 March 2026 | M2: not stated; M3: Updated; M1: Reviewed | Task 21 explicitly attached M2/M3 to Overview. M2 concerns migraine without aura. M3 discusses aura and separately migraine without headache, but does not clearly tie those statements together. M1 explicitly covers visual/sensory/speech aura, but neither establishes the headache-free aura clause in its checked text nor has a historical Overview association. The specific current phrase “aura, which may occur with or without headache” remains unverified against the recovered Overview evidence. |
| Diabetic Ketoacidosis | 364 | overview | VERIFIED | CDC; NHS | D1: [Diabetic Ketoacidosis](https://www.cdc.gov/diabetes/about/diabetic-ketoacidosis.html); D2: [Diabetic ketoacidosis](https://www.nhs.uk/conditions/diabetic-ketoacidosis/) | D1: 15 May 2024; D2: 8 June 2023 | D1: displayed page date, precise publication/update/review type not labelled in retrieved text; D2: Reviewed | Both were explicitly attached to this Overview in Task 21. The original D1 URL now opens directly. Its Overview explains insulin deficiency and accumulating acid ketones, diabetes types and seriousness; Signs/Testing cover the warning pattern and emergency significance. D2 supplies drowsiness/confusion and urgent assessment. No remaining mapping uncertainty identified; no substitute source used. |
| Diabetic Ketoacidosis | 364 | causes | VERIFIED | CDC | D1: [Diabetic Ketoacidosis](https://www.cdc.gov/diabetes/about/diabetic-ketoacidosis.html) | 15 May 2024 | Displayed page date; precise publication/update/review type not labelled in retrieved text | Original sole Task 21 citation now directly readable. Overview establishes insufficient insulin/fat breakdown; Causes explicitly lists illness, missed insulin and clogged pumps; Signs identifies initial undiagnosed-diabetes presentation. Its Treatment paragraph identifies infection as an underlying illness. Those existing topics support the stored summary without adding treatment instructions. No remaining mapping uncertainty identified. |
| Acne | 201 | overview | NO_CONFIRMED_SOURCE | NIH / NIAMS | A1: [Acne: Overview, Symptoms, & Causes](https://www.niams.nih.gov/health-topics/acne); A2: [Acne: Diagnosis, Treatment, and Steps to Take](https://www.niams.nih.gov/health-topics/acne/diagnosis-treatment-and-steps-to-take) | Both: July 2023 | Both: Reviewed (month precision) | A1 is the explicit Task 36 Overview citation and supports the mechanism, sites and lesions. A2 directly discusses significant scarring and discoloration after healing, and scars/dark marks after picking. However, Task 36 explicitly attached A2 to Diagnosis/Prevention, not Overview. Direct claim support exists, but the requested historical Overview association for that final sentence has not been recovered. This task does not silently add that section relationship. |

### Final editorial closure

- Migraine (1), overview: the existing Task 21 Overview citations and recovered inspection notes do not clearly establish the headache-free aura relationship for the complete stored Overview. No historical relationship is inferred from general migraine coverage. Final status: NO_CONFIRMED_SOURCE.
- Acne (201), overview: the already recovered A2 passage discusses scarring/discoloration, but Task 36 associated A2 with Diagnosis/Prevention, not the current Overview. No explicit historical Overview association was found on the final project/evidence check. Final status: NO_CONFIRMED_SOURCE.

These are final mapping decisions for the bounded MVP, not pending UNVERIFIED cells. They do not state that the disease content is medically unsupported or false. No source or section association was added to achieve readiness.

## E. No Confirmed Source

NO_CONFIRMED_SOURCE means:

> No source relationship is sufficiently established for public section-level attribution from the currently recovered editorial evidence.

| Disease | Disease ID | Section | Public-reference disposition |
|---|---|---|---|
| Migraine | 1 | overview | Omit this section's source block. Preserve current disease content and all other verified mappings. |
| Acne | 201 | overview | Omit this section's source block. Preserve current disease content and all other verified mappings. |

Exactly two of the 40 cells have this final status. This concerns confirmed public section-level attribution, not medical support for the content. A potentially relevant source does not establish the required relationship. No generic reference should fill either unmapped cell.

## F. Source Inventory

Deduplicated by the original public URL. “VERIFIED sections” are the proposed checked public relationships from section C; “held” relationships are historical candidates only; Migraine and Acne overview candidates now close as NO_CONFIRMED_SOURCE and are not public mappings. Additional sources are not inferred from outbound page links.

| Source ID | Organization / publisher | Title / public URL | Available date information / type | Disease supported | Explicit section associations and disposition |
|---|---|---|---|---|---|
| M1 | NHS | [Migraine](https://www.nhs.uk/conditions/migraine/) | 10 March 2026 — Reviewed | Migraine / 1 | Task 21; VERIFIED: causes, risk_factors, diagnosis, prevention; held: none |
| M2 | International Headache Society | [1.1 Migraine without aura](https://ichd-3.org/1-migraine/1-1-migraine-without-aura/) | Not stated — Not stated | Migraine / 1 | Task 21; VERIFIED: none; held: overview |
| M3 | NIH / National Library of Medicine, MedlinePlus | [Migraine](https://medlineplus.gov/migraine.html) | 20 November 2025 — Updated | Migraine / 1 | Task 21; VERIFIED: causes, risk_factors, diagnosis; held: overview |
| B1 | CDC | [Breast Cancer Basics](https://www.cdc.gov/breast-cancer/about/index.html) | 15 May 2024; 2 October 2026; 2 October 2026 — Published; Updated; Reviewed | Breast Cancer / 200 | Task 21; VERIFIED: overview; held: none |
| B2 | NIH / National Cancer Institute | [What Is Breast Cancer?](https://www.cancer.gov/types/breast/what-is-breast-cancer) | 2 December 2025 — Updated | Breast Cancer / 200 | Task 21; VERIFIED: overview; held: none |
| B3 | NIH / National Cancer Institute | [Breast Cancer Causes and Risk Factors](https://www.cancer.gov/types/breast/causes-risk-factors) | 2 December 2025 — Updated | Breast Cancer / 200 | Task 21; VERIFIED: causes, risk_factors; held: none |
| B4 | NIH / National Cancer Institute | [What Is Cancer?](https://www.cancer.gov/about-cancer/understanding/what-is-cancer) | 28 August 2026 — Updated | Breast Cancer / 200 | Task 21; VERIFIED: causes; held: none |
| B5 | NIH / National Cancer Institute | [How Breast Cancer Is Diagnosed](https://www.cancer.gov/types/breast/diagnosis) | 2 December 2025 — Posted (publication) | Breast Cancer / 200 | Task 21; VERIFIED: diagnosis; held: none |
| B6 | NIH / National Cancer Institute | [Breast Cancer Screening](https://www.cancer.gov/types/breast/screening) | 2 December 2025 — Updated | Breast Cancer / 200 | Task 21; VERIFIED: diagnosis, prevention; held: none |
| B7 | NIH / National Cancer Institute | [Breast Cancer Prevention](https://www.cancer.gov/types/breast/causes-risk-factors/prevention) | 2 December 2025 — Updated | Breast Cancer / 200 | Task 21; VERIFIED: prevention; held: none |
| D1 | CDC | [Diabetic Ketoacidosis](https://www.cdc.gov/diabetes/about/diabetic-ketoacidosis.html) | 15 May 2024 — Displayed page date; precise publication/update/review type not labelled in retrieved page text | Diabetic Ketoacidosis / 364 | Task 21; VERIFIED: overview, causes (directly rechecked in Step 41A-2). Historical risk_factors companion remains recorded; the pre-existing D3 public relationship for that cell is unchanged. |
| D2 | NHS | [Diabetic ketoacidosis](https://www.nhs.uk/conditions/diabetic-ketoacidosis/) | 8 June 2023 — Reviewed | Diabetic Ketoacidosis / 364 | Task 21; VERIFIED: prevention; held: overview |
| D3 | ADA, EASD, JBDS, AACE and DTS; published by Diabetologia / Springer Nature | [Hyperglycaemic crises in adults with diabetes: a consensus report](https://link.springer.com/article/10.1007/s00125-024-06183-8) | 22 June 2024 — Published | Diabetic Ketoacidosis / 364 | Task 21; VERIFIED: risk_factors, diagnosis; held: none |
| C1 | NIH / NIDDK | [Definition & Facts for Celiac Disease](https://www.niddk.nih.gov/health-information/digestive-diseases/celiac-disease/definition-facts) | October 2020 — Reviewed (month precision) | Celiac Disease / 120 | Task 21; VERIFIED: overview, risk_factors; held: none |
| C2 | NIH / NIDDK | [Symptoms & Causes of Celiac Disease](https://www.niddk.nih.gov/health-information/digestive-diseases/celiac-disease/symptoms-causes) | October 2020 — Reviewed (month precision) | Celiac Disease / 120 | Task 21; VERIFIED: overview, causes; held: none |
| C3 | NHS | [Causes - Coeliac disease](https://www.nhs.uk/conditions/coeliac-disease/causes/) | 31 March 2023 — Reviewed | Celiac Disease / 120 | Task 21; VERIFIED: causes, risk_factors; held: none |
| C4 | NIH / NIDDK | [Diagnosis of Celiac Disease](https://www.niddk.nih.gov/health-information/digestive-diseases/celiac-disease/diagnosis) | October 2020 — Reviewed (month precision) | Celiac Disease / 120 | Task 21; VERIFIED: diagnosis; held: none |
| C5 | NIH / NIDDK | [Eating, Diet, & Nutrition for Celiac Disease](https://www.niddk.nih.gov/health-information/digestive-diseases/celiac-disease/eating-diet-nutrition) | October 2020 — Reviewed (month precision) | Celiac Disease / 120 | Task 21; VERIFIED: diagnosis, prevention; held: none |
| C6 | NIH / NIDDK | [Treatment for Celiac Disease](https://www.niddk.nih.gov/health-information/digestive-diseases/celiac-disease/treatment) | October 2020 — Reviewed (month precision) | Celiac Disease / 120 | Task 21; VERIFIED: prevention; held: none |
| P1 | NIH / NHLBI | [Pulmonary Hypertension - What Is Pulmonary Hypertension?](https://www.nhlbi.nih.gov/health/pulmonary-hypertension) | 1 May 2023 — Updated | Pulmonary Hypertension / 398 | Task 21; VERIFIED: overview; held: none |
| P2 | NIH / NHLBI | [Pulmonary Hypertension - Causes and Risk Factors](https://www.nhlbi.nih.gov/health/pulmonary-hypertension/causes) | 24 March 2022 — Updated | Pulmonary Hypertension / 398 | Task 21; VERIFIED: risk_factors, prevention; held: none |
| P3 | NIH / NHLBI | [Pulmonary Hypertension - Diagnosis](https://www.nhlbi.nih.gov/health/pulmonary-hypertension/diagnosis) | 24 March 2022 — Updated | Pulmonary Hypertension / 398 | Task 21; VERIFIED: diagnosis; held: none |
| P4 | European Society of Cardiology | [Diagnosis and management of pulmonary hypertension due to left heart disease or pulmonary disease](https://www.escardio.org/communities/councils/cardiology-practice/education/cardiopractice/diagnosis-and-management-of-pulmonary-hypertension-due-to-left-heart-disease-or-pulmonary-disease/) | 4 August 2026 — Displayed article date; not explicitly labelled publication/update/review | Pulmonary Hypertension / 398 | Task 21; VERIFIED: causes, diagnosis; held: none |
| P5 | NIH / NHLBI | [Pulmonary Hypertension - Symptoms](https://www.nhlbi.nih.gov/health/pulmonary-hypertension/symptoms) | 24 March 2022 — Updated | Pulmonary Hypertension / 398 | Task 21; VERIFIED: overview; held: none |
| T1 | CDC | [About Typhoid Fever and Paratyphoid Fever](https://www.cdc.gov/typhoid-fever/about/index.html) | 25 April 2024 — Displayed page date; not explicitly labelled publication/update/review | Typhoid Fever / 358 | Task 36; VERIFIED: causes, risk_factors; held: none |
| T2 | CDC | [Clinical Guidance for Typhoid Fever and Paratyphoid Fever](https://www.cdc.gov/typhoid-fever/hcp/clinical-guidance/index.html) | 24 April 2024 — Displayed page date; not explicitly labelled publication/update/review | Typhoid Fever / 358 | Task 36; VERIFIED: overview, diagnosis; held: none |
| T3 | CDC | [Preventing Typhoid Fever and Paratyphoid Fever](https://www.cdc.gov/typhoid-fever/prevention/index.html) | 13 July 2026 — Displayed page date; not explicitly labelled publication/update/review | Typhoid Fever / 358 | Task 36; VERIFIED: prevention; held: none |
| L1 | National Library of Medicine / MedlinePlus; A.D.A.M. Medical Encyclopedia | [Boils](https://medlineplus.gov/ency/article/001474.htm) | 14 October 2024 — Reviewed | Boils / 217 | Task 36; VERIFIED: overview, causes, diagnosis; held: none |
| L2 | NHS | [Boils](https://www.nhs.uk/conditions/boils/) | 20 June 2023 — Reviewed | Boils / 217 | Task 36; VERIFIED: risk_factors, diagnosis, prevention; held: none |
| L3 | NHS | [Staph infection](https://www.nhs.uk/conditions/staphylococcal-infections/) | 11 February 2025 — Reviewed | Boils / 217 | Task 36; VERIFIED: causes, risk_factors, prevention; held: none |
| A1 | NIH / NIAMS | [Acne: Overview, Symptoms, & Causes](https://www.niams.nih.gov/health-topics/acne) | July 2023 — Reviewed (month precision) | Acne / 201 | Task 36; VERIFIED: causes, risk_factors; held: overview |
| A2 | NIH / NIAMS | [Acne: Diagnosis, Treatment, and Steps to Take](https://www.niams.nih.gov/health-topics/acne/diagnosis-treatment-and-steps-to-take) | July 2023 — Reviewed (month precision) | Acne / 201 | Task 36; VERIFIED: diagnosis, prevention; held: none |

Source-title notes: A1's historical report uses “Acne: Overview, Symptoms, and Causes”; its current main heading is “Acne” and its navigation says “Overview, Symptoms, & Causes”. These identify the same URL, not separate sources. Some NCI/NHLBI browser titles differ in punctuation from the displayed headings; URLs are the identity used for deduplication.

No publication date was inferred from an access date, copyright year or next-review deadline. B1's Published, Updated and Reviewed dates are three separate facts, even where two dates coincide. NCI B5 is explicitly “Posted”. Generic CDC page dates and P4's article date are not relabelled as reviewed dates. D1's displayed date was directly confirmed in Step 41A-2. Additional publication/update/review labels shown only by prior search text were not promoted into the inventory.

## G. Editorial Risks

- **Unmapped sections:** Migraine's Overview aura relationship and Acne's Overview historical scarring/color-change association are closed as NO_CONFIRMED_SOURCE in section D. The future UI omits their source blocks; this is not an assertion of medically unsupported content. The two CDC-dependent cells were resolved in Step 41A-2. General disease relevance was not used to manufacture either missing relationship.
- **Stale mappings:** current content matches the historical package today, but later edits can invalidate a section association. Recheck against the changed English text before continued use.
- **Older source dates:** the NIDDK pages retain October 2020 review dates; NHS celiac, boils and DKA pages have passed their displayed next-review dates. Actual checked topics still support the specified relationships; do not conceal age or pretend a fresh review occurred.
- **Conflicting numeric definitions:** the older NHLBI Diagnosis page and the ESC article differ on a diagnostic pressure threshold. Current KOBI Diagnosis contains no numerical threshold. The proposed relationship is restricted to evaluation/test roles; it does not endorse the older number or represent the complete ESC/ERS guideline as reviewed.
- **Historical clinical review notes:** Task 21 flagged formal thresholds, subtype interpretation and some prevention wording. Source-mapping verification is not a newly performed specialist review or a claim of clinical approval.
- **Missing dates:** M2 has no explicit page publication/update/review date. Preserve absence; its footer copyright year is not a source date.
- **Ambiguous date types:** T1/T2/T3 and P4 have displayed dates without an explicit type in the inspected page text. Preserve the date and uncertainty rather than converting it.
- **Missing URLs:** none of the 32 recovered records lacks a public HTTP(S) URL. D1's earlier retrieval limitation was resolved by direct inspection in Step 41A-2; a syntactically valid URL alone was not accepted as verification.
- **Duplicate/conflicting sources:** the inventory has no duplicate URL. Multiple sources in a section are complementary; different dates/titles do not automatically make them duplicates.
- **Date confusion:** 13 October 2026 is this preparation/check date, not a publisher review date. KOBI internal timestamps, account identities and reviewer/verification dates were not used as source metadata.
- **Privacy and scope:** expose only curated public source metadata in a later authorized task. This report gives no permission to expose Treatment governance or Medicine evidence/reviewer records.
- **Language:** all associations are English only. No Hindi, Treatment section, dosage or prescribing coverage is created.
- **Excluded diseases:** Specific Phobia and Schizophrenia remain outside this eight-disease task; no mappings were prepared for them.

## Validation and change record

- Main matrix validation: 8 unique diseases × 5 exact section labels = 40 cells; each disease/section pair occurs once.
- Previous totals: 36 VERIFIED + 4 UNVERIFIED + 0 NO_CONFIRMED_SOURCE = 40.
- Step 41A-2 totals: 38 VERIFIED + 2 UNVERIFIED + 0 NO_CONFIRMED_SOURCE = 40.
- Final Step 41A-3 totals: 38 VERIFIED + 0 UNVERIFIED + 2 NO_CONFIRMED_SOURCE = 40.
- Step 41A-3 checks: final existing-evidence search, exact 40-cell matrix/count reconciliation, unchanged section C and unchanged VERIFIED cells. No new external research, database access or application mutation.
- Step 41A-2 checks: all 15 permitted fields reread for the three affected diseases matched the Step 41A snapshot; no database writes. The other 36 matrix cells and their 54 detailed source rows were preserved. Three detailed source rows were added for the two resolved DKA cells, giving 57 rows covering 38 VERIFIED cells.
- Current-content equality: 40/40 fields matched the recovered approved text; database reads selected only the six permitted columns and ran in a read-only transaction ending in rollback.
- Detailed mapping validation: every VERIFIED cell has source metadata and a concise reason; no held cell is listed in section C.
- Inventory validation: 32 unique original HTTP(S) URLs; all cited source IDs resolve within this report.
- Preparation checks only. No application, integration, browser or medical-correctness regression suite was run; no tests were created or modified.
- Initial Step 41A file created: docs/task-41a-disease-reference-mapping.md only.
- Step 41A-2 files created: none.
- Step 41A-2 file modified: docs/task-41a-disease-reference-mapping.md only.
- Step 41A-3 files created: none.
- Step 41A-3 file modified: docs/task-41a-disease-reference-mapping.md only.
- Database writes: NO. Schema changes: NO. Application code/behavior changes: NO.
- Configuration, routes and Disease Detail UI changes: NO.
- Disease data, Hindi, urgency notes and timestamps: unchanged by this task.
- Treatment, Medicine, Authentication, Health Profile, Personal Medication and Symptom Checker: unchanged.
- No Git commit, push, reset, checkout, merge, rebase or other Git-state mutation.
- resources/disease-references.php and DiseaseReferenceService were not created.

## H. Implementation Readiness

**READY FOR STEP 41B**

Final counts: 38 VERIFIED + 2 NO_CONFIRMED_SOURCE = 40; UNVERIFIED = 0.

All previously VERIFIED mappings are preserved. Migraine (1) overview and Acne (201) overview have final NO_CONFIRMED_SOURCE status and their public source blocks must be omitted. This does not change their medical content or any other section's attribution.

PERSISTED APPLICATION MAPPING: unchanged and absent. This report persists editorial preparation as documentation only; the application does not read it, and no feature was implemented. Stop after Step 41A-3; do not start Step 41B.

