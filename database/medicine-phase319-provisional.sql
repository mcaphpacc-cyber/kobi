/*Medicine Phase 3.19: preparation-only revisions; never canonical catalog subjects. */
CREATE TABLE `medicine_provisional_identity_revisions` (
 `substance_id` bigint unsigned NOT NULL,
 `revision` int unsigned NOT NULL,
 `state` varchar(16) COLLATE utf8mb4_bin NOT NULL,
 `snapshot_json` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
 `snapshot_hash` binary(32) NOT NULL,
 `evidence_json` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
 `evidence_hash` binary(32) NOT NULL,
 `actor_id` int(11) NOT NULL,
 `authority_grant_id` bigint unsigned NOT NULL,
 `rationale` text CHARACTER SET utf8mb4 NOT NULL,
 `occurred_at` datetime(6) NOT NULL,
 PRIMARY KEY (`substance_id`,`revision`),
 KEY `idx_medicine_provisional_actor` (`actor_id`,`occurred_at`),
 KEY `idx_medicine_provisional_grant` (`authority_grant_id`),
 CONSTRAINT `fk_medicine_provisional_substance` FOREIGN KEY (`substance_id`) REFERENCES `kobi_substances` (`substance_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_provisional_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_provisional_grant` FOREIGN KEY (`authority_grant_id`) REFERENCES `medicine_authority_grants` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `chk_medicine_provisional_scope` CHECK (substance_id IN (15,54,319,481,2064)),
 CONSTRAINT `chk_medicine_provisional_state` CHECK (BINARY state IN (BINARY 'draft',BINARY 'withdrawn',BINARY 'invalidated')),
 CONSTRAINT `chk_medicine_provisional_shape` CHECK (revision>=1 AND CHAR_LENGTH(TRIM(rationale))>0 AND JSON_VALID(snapshot_json) AND JSON_VALID(evidence_json) AND OCTET_LENGTH(snapshot_json)<=262144 AND OCTET_LENGTH(evidence_json)<=65535),
 CONSTRAINT `chk_medicine_provisional_hashes` CHECK (snapshot_hash=UNHEX(SHA2(snapshot_json,256)) AND evidence_hash=UNHEX(SHA2(evidence_json,256)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DELIMITER $$
CREATE TRIGGER `provisional_identity_before_insert` BEFORE INSERT ON `medicine_provisional_identity_revisions` FOR EACH ROW
BEGIN
 DECLARE locked_substance bigint unsigned;
 DECLARE prior_revision int unsigned DEFAULT 0;
 DECLARE prior_state varchar(16);
 DECLARE valid_grant int DEFAULT 0;
 DECLARE source_index int DEFAULT 0;
 DECLARE source_path varchar(16);
 DECLARE facts_path varchar(32);
 /* Serialize even first insertion on the parent registry row. */
 SELECT substance_id INTO locked_substance FROM kobi_substances WHERE substance_id=NEW.substance_id FOR UPDATE;
 SELECT COALESCE(MAX(revision),0) INTO prior_revision FROM medicine_provisional_identity_revisions WHERE substance_id=NEW.substance_id;
 IF NEW.revision<>prior_revision+1 THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine provisional revision is stale';
 END IF;
 IF prior_revision>0 THEN
  SELECT state INTO prior_state FROM medicine_provisional_identity_revisions WHERE substance_id=NEW.substance_id AND revision=prior_revision;
  IF BINARY prior_state<>BINARY 'draft' THEN
   SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine provisional history cannot be restored';
  END IF;
 ELSEIF BINARY NEW.state<>BINARY 'draft' THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine provisional history must begin as draft';
 END IF;
 SELECT COUNT(*) INTO valid_grant FROM medicine_authority_grants g JOIN users u ON u.id=g.user_id
 WHERE g.id=NEW.authority_grant_id AND g.user_id=NEW.actor_id AND u.status='active'
 AND g.is_active=1 AND g.revoked_at IS NULL AND g.revoked_by IS NULL AND g.revocation_reason IS NULL
 AND g.granted_at<=UTC_TIMESTAMP(6) AND g.expires_at>UTC_TIMESTAMP(6)
 AND BINARY g.scope IN (BINARY 'composition',BINARY 'catalog')
 AND ((BINARY NEW.state=BINARY 'draft' AND BINARY g.capability IN (BINARY 'contributor',BINARY 'editor'))
 OR (BINARY NEW.state IN (BINARY 'withdrawn',BINARY 'invalidated') AND BINARY g.capability=BINARY 'steward'));
 IF valid_grant<>1 THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine provisional authority is unavailable';
 END IF;
 IF BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot_json,'$.domain')),'')<>'provisional_identity'
 OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot_json,'$.target')),'')<>CONCAT('provisional:S:',NEW.substance_id)
 OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot_json,'$.registry.id')),'')<>CAST(NEW.substance_id AS CHAR)
 OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot_json,'$.package_sha256')),'')<>'3fc38ca47fa9ace4bd8a9963854e120425909565b2a91e2c8c4ac763da454086'
 OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot_json,'$.evidence_status')),'')<>'NOT_APPRAISED'
 OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot_json,'$.review_status')),'')<>'NOT_ASSIGNED'
 OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot_json,'$.approval_status')),'')<>'NONE'
 OR COALESCE(JSON_TYPE(JSON_EXTRACT(NEW.snapshot_json,'$.reviewer_id')),'')<>'NULL'
 OR COALESCE(JSON_TYPE(JSON_EXTRACT(NEW.snapshot_json,'$.canonical_identity_id')),'')<>'NULL'
 OR COALESCE(JSON_TYPE(JSON_EXTRACT(NEW.snapshot_json,'$.chemical_ref_id')),'')<>'NULL'
 OR COALESCE(JSON_TYPE(JSON_EXTRACT(NEW.snapshot_json,'$.proposal.chemical_form')),'')<>'NULL'
 OR COALESCE(JSON_TYPE(JSON_EXTRACT(NEW.snapshot_json,'$.proposal.stereochemistry')),'')<>'NULL'
 OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot_json,'$.proposal.mapping_status')),'')<>'PENDING_REVIEW'
 OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot_json,'$.proposal.chemical_form_status')),'')<>'PENDING_REVIEW'
 OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot_json,'$.proposal.stereochemistry_status')),'')<>'PENDING_REVIEW'
 OR COALESCE(JSON_TYPE(NEW.evidence_json),'')<>'ARRAY'
 OR COALESCE(JSON_LENGTH(NEW.evidence_json),0)<>2 THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine provisional facts must remain unapproved';
 END IF;
 WHILE source_index<2 DO
  SET source_path=CONCAT('$[',source_index,']');
  SET facts_path=CONCAT('$.sources[',source_index,']');
  IF BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.evidence_json,CONCAT(source_path,'.appraisal_status'))),'')<>'NOT_APPRAISED'
  OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.evidence_json,CONCAT(source_path,'.scope'))),'')<>'provisional_identity'
  OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.evidence_json,CONCAT(source_path,'.target'))),'')<>CONCAT('provisional:S:',NEW.substance_id)
  OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.evidence_json,CONCAT(source_path,'.revision'))),'')<>CAST(NEW.revision AS CHAR)
  OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.evidence_json,CONCAT(source_path,'.snapshot_sha256'))),'')<>LOWER(HEX(NEW.snapshot_hash))
  OR BINARY COALESCE(JSON_COMPACT(JSON_EXTRACT(NEW.evidence_json,CONCAT(source_path,'.source'))),'')<>BINARY COALESCE(JSON_COMPACT(JSON_EXTRACT(NEW.snapshot_json,CONCAT(facts_path,'.proposed_evidence'))),'MISSING')
  OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.evidence_json,CONCAT(source_path,'.proposed_tier'))),'')<>IF(source_index=0,'1','4')
  OR BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.evidence_json,CONCAT(source_path,'.retrieved_at'))),'')<>BINARY COALESCE(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot_json,CONCAT(facts_path,'.accessed_at_utc'))),'MISSING') THEN
   SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine provisional evidence binding is invalid';
  END IF;
  SET source_index=source_index+1;
 END WHILE;
END$$
CREATE TRIGGER `provisional_identity_no_update` BEFORE UPDATE ON `medicine_provisional_identity_revisions` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine provisional history is immutable';
END$$
CREATE TRIGGER `provisional_identity_no_delete` BEFORE DELETE ON `medicine_provisional_identity_revisions` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine provisional history is immutable';
END$$
DELIMITER ;
/*End Medicine Phase 3.19. */
