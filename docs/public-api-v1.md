# KOBI Public API V1 Contract

Implemented 14 October 2026. Base path: `{KOBI public base URL}/api/v1`. Local development base: `http://localhost/kobi/public/api/v1`.

Public knowledge only. No authentication, API key, CSRF token or browser session. Cookies/Authorization do not change results. Native clients can consume normal HTTPS JSON without browser CORS permission; no cross-origin browser permission is granted. Production HTTPS/hosting deployment is separate from local implementation.

## Routes

| GET route | Parameters | Result |
| --- | --- | --- |
| `/diseases` | page default1,max10000; page_size default20,max50 | DiseaseSummary array, ordered English name then numeric ID |
| `/diseases/search` | required q | Up to10 DiseaseSummary objects |
| `/diseases/{slug}` | public stored slug; no query parameters | DiseaseDetail object |
| `/substances/search` | required q | Up to10 SubstanceSummary objects |

These are the public read-only V1 routes. Step 50B separately implements the four authentication POST routes documented in [authenticated-api-v1.md](authenticated-api-v1.md), with normal deployment still pending. The literal disease search route precedes slug matching. No Substance detail, Treatments, symptom matching, private profiles, medications, saved checks, documents or sharing API is exposed.

## Input and search

Parameters must be scalar valid UTF-8 strings. Unknown parameters rejected. Positive integer page/size syntax is digits without leading zero/sign/fraction; values must be within bounds. Search q trims/collapses Unicode whitespace, retains punctuation, requires2–100 Unicode code points and caps raw input4096 bytes. LIKE `%`, `_` and `=` are literal search characters, not wildcards. No fuzzy/AI/medical relevance ranking.

Exact name → prefix → contains, then name under existing case-insensitive database collation, then numeric ID. Disease names search English/Hindi; content narratives are English only. Duplicate IDs/names are never merged. Searches fetch11 and display10 with has_more; no search pagination. List pages beyond the final page return[] and has_more=false. No cross-request immutable paging snapshot promised.

Stored disease slugs currently use ASCII letters/numbers/hyphens. Lookup validates1–255 bytes and decodes URL escapes once, disallowing slash/control/NUL and invalid shape. Use returned slug/public_url, not a guessed ID route.

## Envelopes and headers

Success:

```json
{"success":true,"data":[],"meta":{"api_version":"v1"}}
```

Error example:

```json
{"success":false,"error":{"code":"VALIDATION_ERROR","message":"Check the request parameters.","fields":{"q":"Enter 2–100 characters."}},"meta":{"api_version":"v1"}}
```

Success has data/meta, no error. Error has error/meta, no data. fields appears only for named validation errors. No raw exception/debug details. Empty lists/searches are200/[]; missing detail404. List meta: api_version,page,page_size,total,has_more. Search meta: api_version,query,limit=10,has_more. Detail meta: api_version only.

All requests reaching the application V1 handler return `Content-Type: application/json; charset=UTF-8`, `Cache-Control: no-store`, `X-Content-Type-Options: nosniff`, without Set-Cookie/CORS grants. Accept does not negotiate other formats; even text/html receives JSON. GET body/Content-Type is not parsed. No ETag/Last-Modified/application cache.

| Status / code | Meaning |
| --- | --- |
|200 | Successful read, including empty result |
|400 BAD_REQUEST | Non-scalar or invalid UTF-8 parameter/slug |
|422 VALIDATION_ERROR | Missing/short/long q, invalid page/size/slug, unsupported parameter |
|404 NOT_FOUND | Missing public resource or unknown V1 route |
|405 METHOD_NOT_ALLOWED | Known route requires GET; Allow:GET; HEAD has no body |
|500 INTERNAL_ERROR | Unexpected read/encoding/response-size failure, safely handled |

No401/403/token flows or429 limiter currently implemented. Host-level failures can precede PHP: local Apache rejects encoded NUL/slash paths with its HTML404. That transport response is outside the application's JSON contract; server settings were not altered to intercept it.

## Stable projections

DiseaseSummary exact fields:

- id: decimal **string**, preserving entity identity.
- slug,name_en,public_url: strings.
- name_hi,body_system: string|null.
- gender: male|female|both|null, descriptive registry field, not personalized suitability.

DiseaseDetail adds:

- locale: en; content_format: plain_text.
- sections: fixed overview,causes,risk_factors,diagnosis,prevention keys, each string|null.
- causes_items: array of English cause-name strings from existing public relationship, maximum100; unexpected excess fails500 rather than truncating content.
- urgency_note: string|null, existing English note.
- references: fixed five section keys, each an array of objects containing organization,title,url strings and date,date_type strings|null.

Source dates retain their stated meaning; no invented KOBI clinical review date. Empty reference arrays mean no confirmed public section mapping. No automatic whole-page source claim. This is a bounded subset of the existing Disease page, omitting Treatments, symptoms, diet/FAQ/related rankings and severity scores. Blank narratives→null; text/quotes/Unicode are preserved, not HTML-encoded or rendered widgets. Clients must render text safely, not inject it as HTML.

SubstanceSummary exact fields:

- id: decimal string, including unsigned-bigint identities.
- canonical_name: string.
- registry_url: current `/medicines?q={encoded canonical name}` web lookup URL, not a rich detail page.
- information_type: legacy_registry_name.

Only active/searchable canonical registry rows. No aliases, forms, combinations, medical guidance, evidence, reviewer/verification/approval or governance fields. No private-data enrichment for authenticated users.

Whole JSON responses have a256KiB guard; excess/encoding errors fail safely without medical text truncation. IDs are strings deliberately; mobile/JavaScript clients must not coerce them to floating-point numbers.

## Compatibility and deferred scope

V1 field names/types/error semantics/order are stable; optional additive fields may appear and clients should ignore unknown fields. Mutable content/names/visibility may change. Breaking contract changes require a new version. Existing unversioned AJAX endpoints and `/search` remain untouched; notably `/api/substances/search` retains prefix matching and its original bare-array keys.

No token/private health/mobile authentication, Treatment/rich Medicine/Symptom Checker/medication/document/sharing API, ABHA/ABDM, AI/fuzzy search, new search infrastructure or cache server. Application-level rate limiting and OpenAPI tooling are not included; hosting limits should be assessed before unrestricted Internet rollout. No package installation or schema changes.
# Authentication foundation

Step 50B adds four separate POST authentication routes. Their contract and deployment status are documented in [authenticated-api-v1.md](authenticated-api-v1.md). The four public read-only routes in this document remain unauthenticated, session-independent and unchanged; they do not expose account or health resources.

