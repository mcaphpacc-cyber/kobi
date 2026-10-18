/* Phase 3.21A: only the self-established owner administrator can be non-expiring.
   Service additionally requires configured owner identity and immutable bootstrap decision.
   Delegated/content/operator grants still require a future expiry. No seeds or content DDL. */
ALTER TABLE `medicine_authority_grants`
 MODIFY COLUMN `expires_at` datetime(6) DEFAULT NULL,
 DROP CONSTRAINT `chk_medicine_grant_active`,
 ADD CONSTRAINT `chk_medicine_grant_active` CHECK (is_active IN (0,1) AND ((expires_at IS NOT NULL AND expires_at > granted_at) OR (expires_at IS NULL AND BINARY capability=BINARY 'administrator' AND BINARY scope=BINARY 'catalog' AND user_id=granted_by AND qualification_reference IS NULL AND qualification_assessed_by IS NULL AND qualification_scope IS NULL)));
