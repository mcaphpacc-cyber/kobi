# Step 55A — Health Records API Readiness Assessment

Date: 2026-10-17. Readiness only; Step 55B is not implemented.

## 1. Executive Summary

**READY WITH NARROW SCOPE.** KOBI does not have a generic `health_records` table, HealthRecordRepository or HealthRecordService. The browser's “Health Records” collection is a list of accessible **health profiles**, and its detail page is a profile hub. A further generic profile-record list/detail would duplicate Step 52B or require inventing a heterogeneous resource model.

Recommend Step 55B implement only the existing **Medical Conditions** resource, using typed `/conditions` list/detail routes, five stored fields, current profile-membership authorization and bounded pagination. This exposes actual recorded information without inventing record types, merging resources or returning notes/providers/documents. Other categories are excluded from this V1 recommendation, not prerequisites requiring another readiness phase. No schema or new authentication prerequisite is needed.

## 2. Current Health Records Implementation

Primary source locations inspected:

- `routes/web.php`: browser Health Records routes around 347–657; insurance routes follow. API routes through Step 54B are at the beginning.
- `AccountController::healthRecords()` lists `HealthProfileService::getAccessibleProfiles()`; `healthRecord(profileId)` retrieves the profile and document count, then renders `account/health-record`.
- `createHealthRecord()`/`storeHealthRecord()` actually create a **family health profile**, not a clinical event. Edit/update likewise change profile name/DOB/gender. Existing profile validation and POST CSRF checks remain separate from reads.
- `app/Views/account/health-records.php`, `health-record.php`, `health-record-create.php`, `health-record-edit.php`: list, profile hub and profile forms. Hub links to Conditions, Medicines, Allergies, Surgeries/Procedures, Timeline, Medical Summary, Documents and Insurance.
- AccountController's condition/allergy/procedure/document actions call the corresponding services and render existing list/create/edit views. Their forms use normal POST and `csrfField()`; inspected record/condition/allergy/procedure views have no AJAX data-read implementation. Medication autocomplete has its own existing JS/endpoint and is unrelated.
- `HealthConditionService`, `HealthMedicineService`, `HealthAllergyService`, `HealthProcedureService`, `HealthDocumentService`, `HealthTimelineService`, `MedicalSummaryService`, `HealthInsuranceService` and their existing repositories implement these distinct resources.

Conditions have current/chronic/resolved/historical statuses. Procedures include surgery, procedure and hospitalization. Allergies have category/severity rather than a lifecycle status. Medicines already have Step 53B API routes. Timeline is derived events, not a persisted generic record collection: one condition or medication can generate multiple dated events, undated rows can disappear, and IDs are source-type-specific. It is unsuitable as a generic record detail store.

Inspected mutation validation: condition name required; four allowed statuses; optional diagnosis/resolution dates, resolution ordering, required resolution date for resolved status, and clearing resolution date for other statuses. Allergy validation requires allergen and validates category/severity; procedure validation requires name and validates its three types. Document validation restricts document type, upload MIME and size (PDF/JPEG/PNG/WebP, up to 20 MiB), title/date and optional provider/notes fields. These mutation validators must not be invoked or redesigned for a read API.

## 3. Actual Database Model

Authoritative reference: `database/structure.sql`. Normal-database inspection confirms zero tables named `health_records`.

| Entity | Identity / association | Stored meaning |
|---|---|---|
| health_profiles | Signed INT auto-increment `id`; `created_by` references users | `profile_type` self/family/dependent, `full_name`, nullable `date_of_birth`, `gender`, active/archived `status`, created/updated timestamps |
| health_profile_members | INT `id`; profile FK + user FK; unique `(health_profile_id,user_id)` | owner/editor/viewer `role`, active/revoked `status`, timestamps; canonical access relationship |
| health_conditions | Signed INT auto-increment `id`; required profile FK | `condition_name` varchar(255), `status` active/resolved/chronic/historical, nullable DATE `diagnosed_on`/`resolved_on`, nullable `doctor_hospital` varchar(255), nullable TEXT `notes`, timestamps |
| health_allergies | INT `id`; required profile FK | `allergen`, `category` medication/food/environmental/other, nullable `reaction`, nullable severity mild/moderate/severe/life-threatening, nullable DATE `identified_on`, `notes`, timestamps |
| health_procedures | INT `id`; required profile FK | `procedure_name`, `procedure_type` surgery/procedure/hospitalization, nullable DATE `performed_on`, nullable `hospital`, `doctor`, `reason`, `notes`, timestamps |
| health_medicines | INT `id`; required profile FK | `brand_name`, `generic_name`, `strength`, `dose`, `frequency`, `route`, current/stopped/historical `status`, nullable `started_on`/`stopped_on`, `prescribed_by`, `notes`, timestamps; existing Step 53B resource |
| health_documents | INT `id`; profile FK and `uploaded_by` user FK | `title`, `document_type`, nullable DATE `document_date`, `hospital`, `doctor`, `notes`, required `original_filename`, `stored_filename`, `storage_path`, `mime_type`, unsigned BIGINT `file_size`, timestamps |

Clinical child rows belong to a profile, not directly to an owning account. Condition/allergy/procedure/medication rows have no `user_id`/`created_by` field. Their profile foreign keys cascade on profile deletion. A condition ID is globally unique **within its table**, not unique across all clinical tables, and the PK is not a composite `(profile,id)` key. No universal `record_type`, `title`, `record_date` or generic record status exists. Condition `status` is not profile active/archived status.

Existing indexes include each child's `health_profile_id`; conditions also index status. Document indexes include profile, type, date and uploader. Member profile/user/status indexes and the unique profile-user pair support authorization. No new index is a demonstrated prerequisite for this small V1.

Related, excluded tables:

- `health_profile_invitations`: id, profile, invited_by, email, role, token_hash, expiry/status/accepted_at/timestamps. This is access metadata and a credential digest, not a health record.
- `health_insurance_policies`: id, profile, created_by, provider_name, policy_number/name/type/status, sum_insured, currency_code, start/expiry dates, renewal_notes/general_notes, timestamps.
- `health_insurance_members`: id, policy_id, optional linked profile, member_name/relationship, member_id_number/health_card_number/DOB, primary-insured flag, notes/timestamps.
- `health_insurance_contacts`: id, policy_id, contact_type, organization_name/contact_person, phone/alternate_phone/email/website/address/notes/timestamps.
- `health_insurance_documents`: id, policy_id, document_id, document_label, created_at. Links existing uploaded documents; it is not an attachment column on conditions/procedures.
- Medication schedule/checkoff and medicine-identity association tables are separate existing features, not generic Health Records or proposed condition fields.

## 4. Existing Authorization Model

Browser: `AuthService::id()` → `HealthProfileService::getProfile()` → `HealthProfileRepository::findAccessible(profileId,userId)`. The repository joins members to profiles and requires both membership and profile status `active`. Owner, editor and viewer all have read access. Owner/editor checks constrain clinical/profile writes; ownership controls sharing/access administration. Creator identity alone does not grant reads.

API: existing `ApiHealthProfileService::detail(principal,profileId)` calls the same `findAccessible()` and exposes the established three-field profile projection. It validates positive signed-INT decimal IDs and returns the same 404 for inaccessible/nonexistent profiles.

Do not substitute `hasAccess()` (does not itself filter profile status), `findActiveById()` (no actor membership), `created_by`, a client user ID, invitation possession or a share token. Do not call `ensureSelfProfile()` on GET: it may create data.

## 5. Record Ownership / Profile Scoping

**Yes: the existing API principal + profile authorization can restrict records to currently accessible profiles**, provided the child query is also bound to that authorized profile. Authentication alone is insufficient.

Conditions already implement the desired child boundary: `findByProfile(profileId)` filters on profile; `findById(conditionId,profileId)` uses **both** IDs in its WHERE clause. `HealthConditionService::getCondition()` authorizes the profile first, then invokes that scoped lookup. For bearer requests, reuse the API profile service rather than the browser service's session-based actor resolution.

Required implementation sequence: admit bearer → authorize requested profile → validate child/paging parameters → read only that profile's conditions → project. A child belonging to a different profile must return the same missing-condition 404 even if the actor separately has access to both profiles.

Allergy/procedure detail repositories currently fetch by child ID alone; their browser services first authorize the requested profile, then explicitly compare the returned child's profile. Document service fetches the document, authorizes its actual profile, and the controller also compares the URL profile. These are existing checked paths, not safe unguarded repository methods to copy into an API. They reinforce the benefit of beginning with the already SQL-scoped Conditions model.

Revoked membership/inactive profile must deny fresh reads. Existing admitted-request race semantics remain; this assessment does not promise atomic revocation across already running requests.

## 6. Sensitive Fields Review

Every clinical field remains sensitive even when recommended for authenticated V1. “Include” never means anonymous/public access.

| Actual field/category | Classification | V1 disposition |
|---|---|---|
| condition id | Resource identity; health-associated identifier | INCLUDE as decimal string; not an access credential |
| condition_name | Recorded clinical/diagnosis label; can contain user-entered text | INCLUDE, necessary to identify the condition; plain text, no interpretation |
| condition status | Recorded clinical lifecycle | INCLUDE exact allowed stored value |
| diagnosed_on, resolved_on | Sensitive clinical dates | INCLUDE nullable date strings; no inferred dates |
| health_profile_id | Internal relationship / authorization key | EXCLUDE from DTO; already in route |
| doctor_hospital, prescribed_by, doctor, hospital | Provider identities, possibly contacts embedded in free text | EXCLUDE |
| notes, procedure reason, allergy reaction | Unstructured clinical/personal content | EXCLUDE from condition V1; no generic description field added |
| created_at, updated_at | Administrative/system metadata | EXCLUDE from V1 |
| allergy allergen/category/severity/identified_on | Sensitive recorded allergy information | DEFER with the separate allergy resource |
| procedure name/type/performed_on | Sensitive procedure information | DEFER with the separate procedure resource |
| medication names/strength/dose/frequency/route/status/dates | Sensitive medication information | EXCLUDE from this new resource; already scoped by Step 53B |
| profile full_name/type/DOB/gender | Identity/demographics | EXCLUDE from condition DTO; existing profile API stays unchanged |
| created_by, uploaded_by, member user_id/id/role/status | Administrative/access identifiers | EXCLUDE |
| documents: title/type/date | Potentially sensitive report/prescription label and date | DEFER with documents, not included as condition fields |
| document hospital/doctor/notes | Provider and free text | EXCLUDE |
| original_filename | May contain patient names or clinical details | EXCLUDE |
| stored_filename, storage_path | Internal storage identifiers / filesystem detail | EXCLUDE |
| mime_type, file_size | Technical file metadata | DEFER with documents; not needed for conditions |
| insurance policy/card/member identifiers, covered amounts/currency/dates/status | Financial/insurance and personal information | EXCLUDE |
| insurance member identity/relationship/DOB and contact organization/person/phone/email/website/address | Personal, provider/contact or administrative data | EXCLUDE |
| insurance labels, renewal/general/member/contact notes | Free text or sensitive document labels | EXCLUDE |
| invitation token_hash/email/roles/expiry and membership metadata | Access information / token digest | EXCLUDE; no joins |
| summary share IDs/tokens/hashes/snapshots/expiry/status/access logs | Separate sharing credentials, clinical copy and access metadata | EXCLUDE; no sharing service call |
| authentication credentials/sessions/governance metadata | Secrets or unrelated internal data | EXCLUDE; no repository join/projection |

There is no dedicated secret/password field in conditions, allergies or procedures. User-entered text can nevertheless contain unintended sensitive content; field whitelisting does not establish that its values are clinically verified or free of personal text.

## 7. Notes and Provider Review

Condition `doctor_hospital` combines provider/organization information in one optional string; there is no structured provider-contact model on that row. Medication `prescribed_by`, procedure `hospital`/`doctor`/`reason`, allergy `reaction`, and each resource's `notes` may contain additional names, contact information or clinical narrative. They have no separate field-level consent/read entitlement model. There is no reliable automatic distinction between an “internal” and “patient-facing” sentence in these text fields.

Exclude all such fields from V1 rather than returning them by default or attempting redaction. `condition_name` is the one necessary user-recorded label; do not rename it “diagnosis,” generate clinical descriptions, strip content to manufacture medical correctness, or render its text as executable HTML.

## 8. Documents and Attachments Review

Documents are separate profile-owned `health_documents` rows. Conditions/allergies/procedures have **no direct document FK or attachment field**. Documents include reports, imaging, prescriptions and a `medical_record` document_type; that enum value does not imply a general health_records entity. Insurance links documents separately through `health_insurance_documents`; `HealthInsuranceService::linkDocument()` checks policy access and that the document belongs to the same profile.

Files are stored beneath configured private storage under `health-documents/{profileId}/` using random stored filenames. `HealthDocumentStorageService` rejects empty/NUL/traversal/out-of-prefix paths; these path checks are storage handling, not actor authorization. No file contents were opened during this assessment.

Browser GET `/account/health-records/{profileId}/documents/{documentId}/file` requires browser auth. `HealthDocumentService::getDocument()` authorizes the actual document's profile; the controller verifies URL-profile equality; `getFilePath()` reuses document authorization and checks file existence. Delivery uses MIME, sanitized original filename, nosniff and private no-cache/no-store headers. There is no separate per-document ACL or existing bearer delivery/signed-URL mechanism in this path.

**Exclude documents, attachments, metadata and URLs from the first Conditions API.** A future bearer file endpoint would need explicit profile/document binding and private transport/header handling; it must not expose a storage path or rely on a session-only browser URL. Signed URLs are not inherently required if a future endpoint rechecks bearer authorization on each delivery, and none should be invented here. No document-delivery prerequisite blocks condition reads.

## 9. Relationship to Medical Summary

The live `MedicalSummaryService` reads conditions, medicines, allergies and procedures. It groups active/chronic conditions versus all others and includes current medicines internally. Step 54B returns only five counts and minimal profile identity; it exposes none of these clinical arrays. Dashboard uses the same counts plus a separately authorized document count.

Conditions API would expose the underlying recorded condition fields only. Across all pages, its four statuses collectively correspond to Medical Summary's `conditions + historical_conditions`; it must not show only active conditions or change summary grouping. Do not call the whole summary assembler to implement a condition collection: use the existing condition repository's profile-scoped data source. This avoids loading unrelated clinical resources.

Timeline reads the same four clinical resources plus documents and derives dated events. It is neither the summary count source nor a generic persisted record identity. Sharing creates expiring snapshots and public access-log writes via `MedicalSummaryShareService`; it must not be used by the API.

## 10. Proposed API Scope

Step 55B: read-only **Medical Conditions list + detail** as the first typed Health Records resource. No general `/health-records` union, new entity, mutation route, medication duplication, allergy/procedure/document/insurance/timeline API, scheduling, saved checks, recommendations or clinical inference.

This is a concrete implementation recommendation, not a request for another discovery/governance phase. Additional category inclusion requires later explicit scope, not new authentication infrastructure.

## 11. Proposed Collection Endpoint

`GET /api/v1/health-profiles/{profileId}/conditions`

Authorize the profile through existing API service first, then fetch only conditions belonging to that profile. Return HTTP 200 `data: []` for no rows, not 404. List rows use the five-field projection below. Allow only `page` and `page_size`; no client actor/status/type/search/sort filters. Body/cookies/custom headers do not select identity. No joins to provider, document, profile-member or sharing data.

Use existing response envelope with collection metadata: `api_version`, `page`, `page_size`, `total`, `has_more`, following the established public V1 paginated-list convention. Existing private API routes continue rejecting their own unsupported queries; do not globally loosen query handling.

## 12. Proposed Detail Endpoint

`GET /api/v1/health-profiles/{profileId}/conditions/{conditionId}`

Useful for selecting/refetching a particular stored condition without downloading all pages. Authorize profile first; validate positive decimal signed-INT child ID; scope SQL by both condition ID and authorized profile ID. Existing `HealthConditionRepository::findById(conditionId,profileId)` already demonstrates that boundary. Use identical five-field projection for detail; detail does not implicitly expand notes/providers. Return generic condition-not-found 404 for absent or different-profile IDs. Meta contains only `api_version: "v1"`.

Do not implement the illustrative generic `/health-records/{recordId}` route: its record ID/type would have no unambiguous source. The browser's apparent record ID is actually a profile ID already handled by Step 52B.

## 13. Proposed API Projection

List `data` is an array of these objects; detail `data` is one identical object:

```json
{
  "id": "123",
  "condition_name": "Example recorded condition",
  "status": "active",
  "diagnosed_on": null,
  "resolved_on": null
}
```

Illustration only; no production values reproduced.

| Proposed field | Exists? | Sensitive? | V1? | Reason / type |
|---|---|---|---|---|
| id | Yes, health_conditions.id | Yes, resource association | INCLUDE | Decimal string; identifies only this resource |
| condition_name | Yes | Yes, clinical label/free text | INCLUDE | Stored name, JSON string; necessary useful content |
| status | Yes | Yes, recorded lifecycle | INCLUDE | Exact active/resolved/chronic/historical string |
| diagnosed_on | Yes, nullable DATE | Yes | INCLUDE | Stored YYYY-MM-DD string or null |
| resolved_on | Yes, nullable DATE | Yes | INCLUDE | Stored YYYY-MM-DD string or null |
| record_type | No generic field | N/A | EXCLUDE | Resource type is expressed by route; no fabricated field |
| title | No generic field | N/A | EXCLUDE | Preserve actual condition_name terminology |
| record_date | No generic field | N/A | EXCLUDE | Do not choose/invent a date from diagnosis/resolution |

Explicitly construct the DTO; do not serialize repository rows. Retain null dates without synthesizing strings, rewriting values or deriving age/status. Validate stored controlled status against the four-value domain; safe internal error for impossible stored values, without repairing production data or relying on non-strict ENUM coercion. Other exclusions are enumerated in sections 6–8 and 16.

## 14. Pagination and Ordering

Current `HealthConditionRepository::findByProfile()` has no LIMIT/pagination or row-count helper. It orders by status CASE rank **active, chronic, historical, resolved, other**, then `diagnosed_on DESC`, then numeric `id DESC`. ID breaks ties within the table, making the order deterministic. Nullable dates follow dated values within the same status under the current MariaDB ordering. Preserve this exact order in the proposed API, rather than substituting the summary assembler's different internal sort.

Observed safe aggregate metadata: 4 condition rows across 2 nonempty profiles; maximum 3 rows in a profile. Other categories: medicines 3/max2, allergies 3/max2, procedures 2/max1, documents 2/max1 (each across 2 nonempty profiles). These are current local counts, not evidence of an inherent growth bound. Schema permits arbitrarily many conditions per profile; pagination is appropriate despite today's small dataset.

Recommend the existing public V1 naming/bounds: `page` default 1/max 10000, `page_size` default 20/max 50. Strict scalar decimal strings without leading zero/sign/fraction; reject invalid/unknown parameters using existing validation conventions. Bind SQL values as integers using existing BaseRepository access. Fetch only the five projected columns and count only within the authorized profile. Return empty data/has_more false beyond the end. No cross-request immutable snapshot or cursor consistency guarantee; concurrent edits may move rows. Existing profile index is sufficient to begin; no theoretical index migration required.

## 15. Authentication and Authorization Reuse

Existing Step 50B bearer parser/admission (`ApiAuthController::admittedBearer(false)` → `ApiAuthGuard` → `ApiAuthService`) supplies the principal and standard current-account eligibility checks. API actor is exclusively `principal.userId`. Reuse `ApiHealthProfileService::detail()` and its canonical repository predicate. There is **no additional authentication prerequisite**: no JWT/API key/new token/browser session/CSRF for GET/CORS/mobile verification/role grant required. Existing 120-family/minute and failed-access 60-peer/minute limiter budgets and fail-closed behavior remain.

Current `public/.htaccess` forwards Authorization only for auth/identity/profile/medication/summary routes. It **does not cover conditions**. A future 55B implementation needs one narrow extension for the two proposed condition paths, preserving all other scopes. This is an ordinary scoped implementation change, not a readiness blocker. No configuration was changed in 55A.

## 16. Privacy Boundary

| Category | Recommendation | Reason |
|---|---|---|
| Condition identity | INCLUDE | Necessary typed resource ID; authorization always required |
| Resource type | INCLUDE via route only | No generic stored record_type exists |
| Condition name | INCLUDE | Small useful recorded health label |
| Diagnosis/resolution dates | INCLUDE | Actual nullable stored facts, without inferred event date |
| Condition status | INCLUDE | Existing domain; no clinical-confidence interpretation |
| Clinical descriptions/content beyond name | EXCLUDE | No dedicated short safe description field; no full summary/event dump |
| Notes and providers | EXCLUDE | Unstructured potentially identifying content, unnecessary for narrow V1 |
| Documents, attachments and delivery URLs | DEFER | Separate authorization/transport resource; not attached to conditions |
| Other typed clinical resources / insurance | DEFER | Separate DTO and binding decisions; medicines already available |
| Internal IDs/timestamps/governance | EXCLUDE | No user-facing need beyond the condition ID |
| Demographics/membership/access/invitations | EXCLUDE | Existing profile boundary; no additional identity/access disclosure |
| Sharing and access logs | EXCLUDE | Separate workflow; never invoke snapshot/public-share reader |
| Credentials/session/token data | EXCLUDE | Authentication stays outside resource projection |

All success/error responses remain private/no-store. No anonymous Health Records route or clinical recommendation is proposed. Clients must treat labels as plain text. Read permission is existing active profile membership, not a claim that the requester is the patient or a clinician.

## 17. Error / Response Conventions

Reuse `PublicApiResponse`: success/data/meta, error/code/message/meta; decimal-string IDs, JSON UTF-8, no-store and nosniff. No PHP/SQL/stack/path leakage. Existing 256 KiB response ceiling remains, with pagination keeping the small projection bounded.

- HTTPS required, without forwarding credential-bearing HTTP requests.
- Missing/malformed/invalid/expired/revoked credentials: existing generic 401 UNAUTHENTICATED and Bearer challenge before resource disclosure.
- Invalid profile/condition IDs or paging fields: safe 422 VALIDATION_ERROR, following current ID/public paging conventions. Unknown query parameters: reject consistently with paging validation; not accepted as identity.
- Inaccessible, archived, revoked-member or missing profile: same existing 404 NOT_FOUND. No distinct 403 confirming another profile exists.
- Authorized profile but missing/wrong-profile condition: same generic condition-not-found 404.
- Unsupported method: 405/Allow GET; HEAD body empty; no OPTIONS/CORS grant.
- Existing limiter: 429/Retry-After, fail-closed 503. Unexpected database/projection errors: safe established 500.

No Health Records-specific exception engine is needed. Browser redirects must not be reused for bearer JSON actions. Existing controller response helper currently assumes unpaginated data; 55B needs a narrow collection response path for service data/meta using existing PublicApiResponse, without changing the envelopes/results of existing routes.

## 18. Database / Schema Assessment

Required schema changes: **None**. Use current conditions/profile/member tables, indexes and FK relationships. Extend existing condition repository methods for bounded minimal API reads/counts if needed; keep browser queries unchanged. No migrations, triggers, schema import or SQL-mode change is required.

Read-only normal inspection used COUNT/GROUP BY aggregate metadata and SHOW CREATE definitions, never medical row samples, credentials or sensitive-row hashing. Relevant baseline counts: profiles 14, members 17, conditions 4, medicines 3, allergies 3, procedures 2, documents 2, shares 13, share-access logs 30, API families 11, refresh history 21, active API families 0. Final comparison repeated the same reads; counts, distributions and eleven normalized SHOW CREATE definition digests are identical. SQL mode remains `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION` for session/global. Only next AUTO_INCREMENT values were excluded from schema-definition digests; no dynamic index CARDINALITY statistics were hashed.

No normal production write, disposable DB, schema change, token lifecycle, browser session or file download was performed for this readiness task.

## 19. Validation Performed

- Read active AGENTS.md and inspected the source/schema/routes/controllers/services/repositories/forms/views listed above, API contracts through Steps 49–54, profile predicates, child binding, storage/delivery and insurance-document linkage.
- PHP lint: **14 inspected PHP files PASS** (Account/API controllers; profile/condition/allergy/procedure/document/storage/timeline/summary/API-profile services; condition/profile/document repositories).
- Existing database-free suites, unchanged: api-auth **103**, api-auth-rate-limit **13**, api-identity **44**, api-health-profiles **20**, api-medications **28**, api-medical-summary **29** plus its reused Dashboard **76**, health-profile-validation **57 plus 4 redirect scenarios**, public-api-v1 **56**, public-api-v1-bootstrap **8**. All passed. Limiter test removed its own disposable private files.
- Unit summary suite also exercises canonical browser summary and existing in-memory sharing behavior. Profile validation exercises existing service/controller/view and authorization scenarios.
- No dedicated standalone Conditions/Procedures/Allergies CRUD or generic Health Records test suite was found in the current tests inventory. Existing profile/Dashboard/summary tests cover parts of the module; they are not a substitute for new 55B endpoint tests.
- No new or modified tests, no HTTP/browser audit, no production bearer family, no isolated database needed. Proposed routes/DTO/pagination are recommendations, not executed API behavior.
- Baseline/final file-hash inventory verifies only this new report was added; existing application, tests, docs, structure.sql, .env and dependency files remain unchanged. No Git commands used.

## 20. Risks / Limitations

“Health Records” is broader than one entity; a generic merged API would need additional identity/type/date semantics and duplicate existing APIs. The typed recommendation avoids that expansion. Clinical labels/dates remain highly sensitive and are user-recorded, not clinically verified. Low current counts do not justify an unbounded production API.

Browser services resolve session actors and must not be called unchanged as bearer authorization. Some child repositories are intentionally unscoped and rely on service checks; beginning with SQL-scoped conditions reduces that adaptation. Existing non-strict SQL mode means the API should explicitly reject an impossible stored status rather than coerce it or mutate data. Existing write validation is outside this read scope.

This assessment did not request real user health data, browse authenticated pages, inspect document bytes or run a new live endpoint. Source/aggregate/unit evidence establishes readiness, not completed HTTP acceptance. Aggregate counts do not establish byte-for-byte row identity; no normal mutating operations were performed. No actual prerequisite blocks the proposed narrow implementation.

## 21. Step 55B Recommendation

Implement **only**:

1. GET `/api/v1/health-profiles/{profileId}/conditions`.
2. GET `/api/v1/health-profiles/{profileId}/conditions/{conditionId}`.

Both use existing bearer principal/profile authorization; five-field condition DTO; list default20/max50/page max10000; exact existing condition repository ordering. No writes/schema/new auth; no notes/provider/document/other-resource exposure. No changes to summary counts, Dashboard, sharing, existing medicines/profile/identity/public APIs or browser auth/Health Records UI.

Likely 55B files (planning only; not changed here):

- Existing `app/Controllers/ApiAuthController.php`: thin list/detail actions with existing admission/error response path and collection metadata support.
- New `app/Services/ApiHealthConditionService.php`: API actor/profile authorization, paging/ID validation and explicit projection.
- Existing `app/Repositories/HealthConditionRepository.php`: narrow API-only explicit-column paginated/count/scoped-detail reads using BaseRepository; preserve browser methods.
- Existing `routes/web.php`, `public/.htaccess`: exactly two GET routes and minimal matching forwarding extension.
- Existing `docs/authenticated-api-v1.md`; new implementation report and focused Conditions API unit/isolated HTTP tests. Existing constructor test setup may need dependency wiring only.

Expected 55B tests: missing/malformed/invalid/expired/revoked bearer; owner/editor/viewer success; other-user/archived/revoked/missing profile parity; child mismatch between even two accessible profiles; profile authorization before source/count reads; exact five fields and null/string types; all four stored statuses; fail-safe invalid stored values; zero/populated lists; default/max/invalid/repeated/unknown paging parameters, boundary/beyond-last pages, deterministic status/date/ID/null ordering; no raw notes/providers/documents/metadata; no GET/session/share/log/token mutations; one limiter admission; existing envelope/cache/method/HTTPS rules. Use guarded disposable synthetic fixtures for any writes, then remove them.

Run existing identity/profile/medicine/summary/auth/public regressions and relevant browser-service/unit checks without altering expectations. Validate success over isolated verified HTTPS and normal Apache credential-free denial if available; real production credentials/families require separate authorization. Any practical authenticated browser navigation check must use genuine login and preserve original records; it is not authorization to seed/edit health data. Step 55B must verify its narrow forwarding extension rather than broaden all `/api/v1` paths.

## 22. Final Readiness Decision

**READY WITH NARROW SCOPE.** Proceed directly to a separately authorized Conditions list/detail implementation as the first Health Records API resource. Generic record unions, notes/providers/documents and other category expansion are excluded, not blockers. No new readiness/governance phase is recommended.

Created only `docs/task-55a-health-records-api-readiness.md`. No application/test/config/auth changes, normal production data/schema writes, sensitive production-row hashing or Git operations. **Step 55B NOT IMPLEMENTED.**
