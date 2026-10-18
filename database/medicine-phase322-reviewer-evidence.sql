/* Phase 3.22: private append-only qualification and provisional evidence appraisal.
   Deploy separately after controlled backup/recovery validation. No seeds or SQL mode changes. */
CREATE TABLE `medicine_reviewer_qualifications` (
 `id` bigint unsigned NOT NULL AUTO_INCREMENT,
 `user_id` int(11) NOT NULL,
 `revision` int unsigned NOT NULL,
 `state` varchar(16) COLLATE utf8mb4_bin NOT NULL,
 `snapshot_json` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
 `snapshot_hash` binary(32) NOT NULL,
 `actor_id` int(11) NOT NULL,
 `occurred_at` datetime(6) NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `uq_medicine_qualification_revision` (`user_id`,`revision`),
 UNIQUE KEY `uq_medicine_qualification_subject` (`id`,`user_id`),
 CONSTRAINT `fk_medicine_qualification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_qualification_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `chk_medicine_qualification_state` CHECK (BINARY state IN (BINARY 'pending',BINARY 'verified',BINARY 'rejected',BINARY 'expired',BINARY 'suspended')),
 CONSTRAINT `chk_medicine_qualification_shape` CHECK (revision>=1 AND JSON_VALID(snapshot_json) AND OCTET_LENGTH(snapshot_json)<=8192 AND snapshot_hash=UNHEX(SHA2(snapshot_json,256))),
 CONSTRAINT `chk_medicine_qualification_binding` CHECK (IFNULL(
  JSON_UNQUOTE(JSON_EXTRACT(snapshot_json,'$.user_id'))=CAST(user_id AS CHAR)
  AND BINARY JSON_UNQUOTE(JSON_EXTRACT(snapshot_json,'$.credential.status'))=BINARY state
  AND BINARY JSON_UNQUOTE(JSON_EXTRACT(snapshot_json,'$.credential.profession')) IN (BINARY 'doctor',BINARY 'pharmacist')
  AND CHAR_LENGTH(TRIM(JSON_UNQUOTE(JSON_EXTRACT(snapshot_json,'$.credential.registration')))) BETWEEN 1 AND 512
  AND CHAR_LENGTH(TRIM(JSON_UNQUOTE(JSON_EXTRACT(snapshot_json,'$.credential.jurisdiction')))) BETWEEN 1 AND 512
  AND CHAR_LENGTH(TRIM(JSON_UNQUOTE(JSON_EXTRACT(snapshot_json,'$.credential.authority')))) BETWEEN 1 AND 512
  AND BINARY JSON_UNQUOTE(JSON_EXTRACT(snapshot_json,'$.scope')) IN (BINARY 'chemical',BINARY 'composition',BINARY 'presentation',BINARY 'catalog')
  AND (BINARY state<>BINARY 'verified' OR (actor_id<>user_id AND JSON_UNQUOTE(JSON_EXTRACT(snapshot_json,'$.credential.verifier'))=CAST(actor_id AS CHAR)
   AND JSON_TYPE(JSON_EXTRACT(snapshot_json,'$.verification'))='OBJECT')),0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `medicine_evidence_appraisals` (
 `id` bigint unsigned NOT NULL AUTO_INCREMENT,
 `substance_id` bigint unsigned NOT NULL,
 `provisional_revision` int unsigned NOT NULL,
 `decision_revision` int unsigned NOT NULL,
 `state` varchar(16) COLLATE utf8mb4_bin NOT NULL,
 `reviewer_id` int(11) NOT NULL,
 `qualification_event_id` bigint unsigned NOT NULL,
 `authority_grant_id` bigint unsigned NOT NULL,
 `snapshot_json` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
 `snapshot_hash` binary(32) NOT NULL,
 `occurred_at` datetime(6) NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `uq_medicine_appraisal_revision` (`substance_id`,`provisional_revision`,`decision_revision`),
 KEY `idx_medicine_appraisal_qualification` (`qualification_event_id`,`reviewer_id`),
 CONSTRAINT `fk_medicine_appraisal_target` FOREIGN KEY (`substance_id`,`provisional_revision`) REFERENCES `medicine_provisional_identity_revisions` (`substance_id`,`revision`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_appraisal_qualification` FOREIGN KEY (`qualification_event_id`,`reviewer_id`) REFERENCES `medicine_reviewer_qualifications` (`id`,`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_appraisal_grant` FOREIGN KEY (`authority_grant_id`) REFERENCES `medicine_authority_grants` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `chk_medicine_appraisal_state` CHECK (BINARY state IN (BINARY 'pending',BINARY 'accepted',BINARY 'rejected',BINARY 'withdrawn')),
 CONSTRAINT `chk_medicine_appraisal_shape` CHECK (decision_revision>=1 AND JSON_VALID(snapshot_json) AND OCTET_LENGTH(snapshot_json)<=65535 AND snapshot_hash=UNHEX(SHA2(snapshot_json,256))),
 CONSTRAINT `chk_medicine_appraisal_binding` CHECK (IFNULL(
  BINARY JSON_UNQUOTE(JSON_EXTRACT(snapshot_json,'$.target'))=BINARY CONCAT('provisional:S:',substance_id)
  AND JSON_UNQUOTE(JSON_EXTRACT(snapshot_json,'$.provisional_revision'))=CAST(provisional_revision AS CHAR)
  AND JSON_UNQUOTE(JSON_EXTRACT(snapshot_json,'$.reviewer_id'))=CAST(reviewer_id AS CHAR)
  AND JSON_UNQUOTE(JSON_EXTRACT(snapshot_json,'$.reviewer_qualification.qualification_event_id'))=CAST(qualification_event_id AS CHAR)
  AND BINARY JSON_UNQUOTE(JSON_EXTRACT(snapshot_json,'$.state'))=BINARY state
  AND BINARY JSON_UNQUOTE(JSON_EXTRACT(snapshot_json,'$.purpose'))=BINARY 'identity'
  AND JSON_UNQUOTE(JSON_EXTRACT(snapshot_json,'$.canonical_approval'))='',0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DELIMITER $$
CREATE TRIGGER `medicine_qualification_no_update` BEFORE UPDATE ON `medicine_reviewer_qualifications` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine qualification history is immutable';
END$$
CREATE TRIGGER `medicine_qualification_no_delete` BEFORE DELETE ON `medicine_reviewer_qualifications` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine qualification history is immutable';
END$$
CREATE TRIGGER `medicine_appraisal_no_update` BEFORE UPDATE ON `medicine_evidence_appraisals` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine appraisal history is immutable';
END$$
CREATE TRIGGER `medicine_appraisal_no_delete` BEFORE DELETE ON `medicine_evidence_appraisals` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine appraisal history is immutable';
END$$
DELIMITER ;
