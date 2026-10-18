# Phase 2C-2 isolated live-test checklist

These tests have **not been run**. They require separate authorization for DDL/data writes in a disposable MariaDB **10.4.27** environment. Do not use the current KOBI database. Do not change server/global/project SQL mode.

## Isolated setup

- Confirm `SELECT VERSION(), @@SESSION.sql_mode, @@check_constraint_checks;`. Test with the observed mode `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION` and constraint checking enabled. Configure only the disposable test connection if needed; preserve the server's settings.
- Load the authoritative schema into an empty, disposable database using a separately reviewed setup procedure. `database/structure.sql` is a full dump containing DROP statements; it is not an upgrade script for an existing database.
- Use synthetic users, two systems A/B, one disease, and protocols/items/references. Explicitly configure a test permission administrator and grant editor, qualified reviewer and source-withdrawal capabilities. Do not use real medical content or personal health information.
- Run the application against that isolated database with publication mode `hold`. Never point this test deployment at the existing KOBI database.

## Database integrity and source versions

1. Inspect `SHOW CREATE TRIGGER trg_treatment_source_no_self_insert` and `SHOW CREATE TRIGGER trg_treatment_source_no_self_update`. Confirm the exact Phase 2C-1 definitions.
2. Insert a source with an explicit synthetic `id` equal to `replaces_source_id`, otherwise valid bibliographic fields and a valid creator. Expect SQLSTATE `45000`; confirm the row does not exist afterward. Repeat with an automatically assigned ID and `replaces_source_id` set to the next generated value in this isolated fixture.
3. Insert a normal source, then update its `replaces_source_id` to its own `id`. Expect SQLSTATE `45000`; confirm the original predecessor remains unchanged.
4. Attempt source/relationship/event/grant writes referencing nonexistent users, sources, items, protocols and systems. Expect FK rejection. Attempt to delete referenced fixtures; expect RESTRICT behavior.
5. Attempt revision zero; inconsistent approved-review pointers; published rows without approval/locales; locales on nonpublished rows; invalid inclusion/capability flags; lifecycle actor/date mismatches; empty claim scope; duplicate direct references; unqualified review grants; incomplete revocations; submitted/approved events without snapshots; invalid JSON/hash; and approval without rationale, attestation or future expiry. Expect the relevant CHECK/UNIQUE rejection.
6. Try assigning an approval event from another protocol or revision. Expect the composite approval FK to reject it. Separately confirm service rejection of a same-revision event of the wrong type.
7. Create source versions A, B replacing A, C replacing B through the service/UI. Confirm A/B are unchanged, all predecessors are retained, and existing protocol/item references still point to their original versions. Test missing predecessor and cyclic fixtures through service validation; expect rejection and rollback. Triggers enforce direct self-reference only; services enforce complete chains.
8. Verify `unverified → verified`, `unverified → withdrawn`, and `verified → withdrawn` with permitted actors. Test restoration attempts; expect rejection. Confirm provenance verification does not change treatment evidence classifications.

## Transaction and concurrent editing

9. Save protocol plus multiple item changes together. Confirm one revision increment, authenticated `updated_by`, UTC `updated_at`, one content-change event, and cleared prior approval. Save an unchanged form; confirm no revision/event change.
10. Inject a failure at event insertion or item update in the isolated test connection. Confirm protocol, items, revision, approval and event history all roll back. Remove the fault before continuing.
11. Open the same protocol in browser sessions A/B at revision 1. Save B to revision 2. Save A's revision 1 form. Expect a safe concurrency response, unchanged B content, no extra increment/event, and instructions to reload.
12. Concurrently attach the same source from two sessions. Confirm one relationship; the other request must reject as stale/duplicate rather than overwrite its claim.
13. Race source withdrawal against attachment/submission. Confirm source-first locking, rejected stale preparation, preserved references, one invalidation per affected protocol, and no continued published state.
14. Where practical, induce a deadlock using isolated sessions. Confirm bounded retry revalidates permissions/state/revision and rolls back before retry; persistent failures return a safe message.

## Authorization, HTTP and public safety

15. Test every editorial GET/POST while logged out: redirect to login and no content/write. Test missing/invalid CSRF on every POST: rejection and no data changes. GET must not invoke mutation commands.
16. Grant editor access only to system A. Confirm the list/detail/save/submit/reference actions cannot expose or change system B. Manipulate URL IDs, posted system/user/actor IDs, item IDs and relationship targets; expect safe rejection. Revoke the grant, then repeat from the already-open form.
17. Test reviewer-only source access and verification. Confirm reviewer-only users cannot edit protocols or create sources. Confirm normal editors cannot verify sources. Source bibliography is a shared catalog; the contextual system does not assign ownership to a source.
18. Withdraw a shared source spanning A/B with only A withdrawal authority. Confirm complete rollback. Grant the explicit required capabilities for both systems and repeat: confirm every linked protocol is invalidated once, published ones become withdrawn, approval/locales clear, and relationships/history remain.
19. Submit complete saved English/Hindi content with explicit verified protocol and item references. Confirm pending review plus canonical snapshot/hash, unchanged revision, no approval/publication. Repeat with missing governed content or unverified/withdrawn references; expect rejection.
20. Seed a published fixture through a separately authorized test procedure. Confirm the editor is read-only and forged save/reference POSTs fail without silently withdrawing it. No approval/publication/permission administration UI exists in this phase.
21. Save draft content, then visit public disease routes. Confirm treatments remain held and no governance notes/snapshot/internal fields are rendered. Confirm there is no public preview or editorial navigation link.
22. Render all editor/source pages with HTML metacharacters, quotes and Unicode. Confirm escaped treatment text, citations, claim scope and locator. Reject `javascript:`, `data:` and `ftp:` URLs; permit valid http/https. Verify unsafe stored URLs are displayed as text, never executable links.
23. Check keyboard access, labels, responsive Bootstrap layouts, page filters and pagination. Confirm no inline scripts/event handlers or CSP changes. Test a large item form exceeding PHP input limits: the missing trailing completion marker must reject the save without partial writes.

Record server version/mode, test database identity, checks performed, HTTP responses and resulting row/event states. Stop before Phase 2C-3.
