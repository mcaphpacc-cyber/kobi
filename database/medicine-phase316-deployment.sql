CREATE TABLE `kobi_medicine_chemical_refs` (
  `chemical_ref_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `substance_id` bigint(20) unsigned DEFAULT NULL,
  `form_id` bigint(20) unsigned DEFAULT NULL,
  `identity_status` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT 'candidate',
  `metadata_revision` int(10) unsigned NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` datetime(6) NOT NULL,
  `updated_at` datetime(6) NOT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime(6) DEFAULT NULL,
  `verification_reference` text DEFAULT NULL,
  `retired_by` int(11) DEFAULT NULL,
  `retired_at` datetime(6) DEFAULT NULL,
  `retirement_reason` text DEFAULT NULL,
  PRIMARY KEY (`chemical_ref_id`),
  UNIQUE KEY `uq_medicine_ref_substance` (`substance_id`),
  UNIQUE KEY `uq_medicine_ref_form` (`form_id`),
  CONSTRAINT `fk_medicine_ref_substance` FOREIGN KEY (`substance_id`) REFERENCES `kobi_substances` (`substance_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_medicine_ref_form` FOREIGN KEY (`form_id`) REFERENCES `kobi_substance_forms` (`form_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_medicine_ref_target` CHECK ((`substance_id` IS NOT NULL AND `form_id` IS NULL) OR (`substance_id` IS NULL AND `form_id` IS NOT NULL)),
  KEY `idx_medicine_ref_status` (`identity_status`,`chemical_ref_id`),
  KEY `idx_medicine_ref_created_by` (`created_by`),
  KEY `idx_medicine_ref_verified_by` (`verified_by`),
  KEY `idx_medicine_ref_retired_by` (`retired_by`),
  CONSTRAINT `fk_medicine_ref_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_medicine_ref_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_medicine_ref_retired_by` FOREIGN KEY (`retired_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_medicine_ref_status` CHECK (BINARY `identity_status` IN (BINARY 'candidate',BINARY 'verified',BINARY 'retired')),
  CONSTRAINT `chk_medicine_ref_revision` CHECK (`metadata_revision` >= 1),
  CONSTRAINT `chk_medicine_ref_verification` CHECK ((`verified_by` IS NULL AND `verified_at` IS NULL AND `verification_reference` IS NULL) OR (`verified_by` IS NOT NULL AND `verified_at` IS NOT NULL AND `verification_reference` IS NOT NULL AND CHAR_LENGTH(TRIM(`verification_reference`)) > 0)),
  CONSTRAINT `chk_medicine_ref_verified` CHECK (BINARY `identity_status` <> BINARY 'verified' OR `verified_by` IS NOT NULL),
  CONSTRAINT `chk_medicine_ref_retirement` CHECK ((`retired_by` IS NULL AND `retired_at` IS NULL AND `retirement_reason` IS NULL) OR (`retired_by` IS NOT NULL AND `retired_at` IS NOT NULL AND `retirement_reason` IS NOT NULL AND CHAR_LENGTH(TRIM(`retirement_reason`)) > 0)),
  CONSTRAINT `chk_medicine_ref_retired` CHECK (BINARY `identity_status` <> BINARY 'retired' OR `retired_by` IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `kobi_medicine_identities` (
  `medicine_identity_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key_version` smallint(5) unsigned NOT NULL DEFAULT 1,
  `composition_hash` binary(32) NOT NULL,
  `composition_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `display_name` varchar(500) DEFAULT NULL,
  `identity_status` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT 'candidate',
  `metadata_revision` int(10) unsigned NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` datetime(6) NOT NULL,
  `updated_at` datetime(6) NOT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime(6) DEFAULT NULL,
  `verification_reference` text DEFAULT NULL,
  `retired_by` int(11) DEFAULT NULL,
  `retired_at` datetime(6) DEFAULT NULL,
  `retirement_reason` text DEFAULT NULL,
  PRIMARY KEY (`medicine_identity_id`),
  UNIQUE KEY `uq_medicine_composition_hash` (`composition_hash`),
  CONSTRAINT `chk_medicine_composition_version` CHECK (`key_version` = 1),
  CONSTRAINT `chk_medicine_composition_key` CHECK (JSON_VALID(`composition_key`) AND OCTET_LENGTH(`composition_key`) <= 8192),
  KEY `idx_medicine_identity_status` (`identity_status`,`medicine_identity_id`),
  KEY `idx_medicine_identity_created_by` (`created_by`),
  KEY `idx_medicine_identity_verified_by` (`verified_by`),
  KEY `idx_medicine_identity_retired_by` (`retired_by`),
  CONSTRAINT `fk_medicine_identity_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_medicine_identity_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_medicine_identity_retired_by` FOREIGN KEY (`retired_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_medicine_identity_status` CHECK (BINARY `identity_status` IN (BINARY 'candidate',BINARY 'verified',BINARY 'retired')),
  CONSTRAINT `chk_medicine_identity_revision` CHECK (`metadata_revision` >= 1),
  CONSTRAINT `chk_medicine_identity_verification` CHECK ((`verified_by` IS NULL AND `verified_at` IS NULL AND `verification_reference` IS NULL) OR (`verified_by` IS NOT NULL AND `verified_at` IS NOT NULL AND `verification_reference` IS NOT NULL AND CHAR_LENGTH(TRIM(`verification_reference`)) > 0)),
  CONSTRAINT `chk_medicine_identity_verified` CHECK (BINARY `identity_status` <> BINARY 'verified' OR `verified_by` IS NOT NULL),
  CONSTRAINT `chk_medicine_identity_retirement` CHECK ((`retired_by` IS NULL AND `retired_at` IS NULL AND `retirement_reason` IS NULL) OR (`retired_by` IS NOT NULL AND `retired_at` IS NOT NULL AND `retirement_reason` IS NOT NULL AND CHAR_LENGTH(TRIM(`retirement_reason`)) > 0)),
  CONSTRAINT `chk_medicine_identity_retired` CHECK (BINARY `identity_status` <> BINARY 'retired' OR `retired_by` IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `kobi_medicine_ingredients` (
  `medicine_ingredient_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `medicine_identity_id` bigint(20) unsigned NOT NULL,
  `chemical_ref_id` bigint(20) unsigned NOT NULL,
  `display_order` smallint(5) unsigned NOT NULL,
  `created_at` datetime(6) NOT NULL,
  PRIMARY KEY (`medicine_ingredient_id`),
  UNIQUE KEY `uq_medicine_ingredient_ref` (`medicine_identity_id`,`chemical_ref_id`),
  UNIQUE KEY `uq_medicine_ingredient_order` (`medicine_identity_id`,`display_order`),
  UNIQUE KEY `uq_medicine_ingredient_scope` (`medicine_identity_id`,`medicine_ingredient_id`),
  KEY `idx_medicine_ingredient_ref` (`chemical_ref_id`,`medicine_identity_id`),
  CONSTRAINT `fk_medicine_ingredient_identity` FOREIGN KEY (`medicine_identity_id`) REFERENCES `kobi_medicine_identities` (`medicine_identity_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_medicine_ingredient_ref` FOREIGN KEY (`chemical_ref_id`) REFERENCES `kobi_medicine_chemical_refs` (`chemical_ref_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_medicine_ingredient_order` CHECK (`display_order` BETWEEN 1 AND 16)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `kobi_medicine_presentations` (
  `medicine_presentation_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `medicine_identity_id` bigint(20) unsigned NOT NULL,
  `key_version` smallint(5) unsigned NOT NULL DEFAULT 1,
  `presentation_hash` binary(32) NOT NULL,
  `presentation_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `dosage_form_code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `route_code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `release_code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `display_name` varchar(500) DEFAULT NULL,
  `identity_status` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT 'candidate',
  `metadata_revision` int(10) unsigned NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` datetime(6) NOT NULL,
  `updated_at` datetime(6) NOT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime(6) DEFAULT NULL,
  `verification_reference` text DEFAULT NULL,
  `retired_by` int(11) DEFAULT NULL,
  `retired_at` datetime(6) DEFAULT NULL,
  `retirement_reason` text DEFAULT NULL,
  PRIMARY KEY (`medicine_presentation_id`),
  UNIQUE KEY `uq_medicine_presentation_hash` (`presentation_hash`),
  UNIQUE KEY `uq_medicine_presentation_scope` (`medicine_identity_id`,`medicine_presentation_id`),
  KEY `idx_medicine_presentation_identity` (`medicine_identity_id`,`identity_status`,`medicine_presentation_id`),
  CONSTRAINT `fk_medicine_presentation_identity` FOREIGN KEY (`medicine_identity_id`) REFERENCES `kobi_medicine_identities` (`medicine_identity_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_medicine_presentation_version` CHECK (`key_version` = 1),
  CONSTRAINT `chk_medicine_presentation_key` CHECK (JSON_VALID(`presentation_key`) AND OCTET_LENGTH(`presentation_key`) <= 16384),
  CONSTRAINT `chk_medicine_presentation_form` CHECK (BINARY `dosage_form_code` IN (BINARY 'tablet',BINARY 'capsule',BINARY 'solution',BINARY 'suspension',BINARY 'cream',BINARY 'ointment')),
  CONSTRAINT `chk_medicine_presentation_route` CHECK (BINARY `route_code` IN (BINARY 'oral',BINARY 'cutaneous')),
  CONSTRAINT `chk_medicine_presentation_release` CHECK (BINARY `release_code` IN (BINARY 'immediate',BINARY 'delayed',BINARY 'extended',BINARY 'not_applicable')),
  CONSTRAINT `chk_medicine_presentation_form_route` CHECK ((BINARY `dosage_form_code` IN (BINARY 'tablet',BINARY 'capsule',BINARY 'solution',BINARY 'suspension') AND BINARY `route_code` = BINARY 'oral') OR (BINARY `dosage_form_code` IN (BINARY 'cream',BINARY 'ointment') AND BINARY `route_code` = BINARY 'cutaneous')),
  CONSTRAINT `chk_medicine_presentation_form_release` CHECK ((BINARY `dosage_form_code` IN (BINARY 'tablet',BINARY 'capsule') AND BINARY `release_code` IN (BINARY 'immediate',BINARY 'delayed',BINARY 'extended')) OR (BINARY `dosage_form_code` IN (BINARY 'solution',BINARY 'suspension',BINARY 'cream',BINARY 'ointment') AND BINARY `release_code` = BINARY 'not_applicable')),
  KEY `idx_medicine_presentation_status` (`identity_status`,`medicine_presentation_id`),
  KEY `idx_medicine_presentation_created_by` (`created_by`),
  KEY `idx_medicine_presentation_verified_by` (`verified_by`),
  KEY `idx_medicine_presentation_retired_by` (`retired_by`),
  CONSTRAINT `fk_medicine_presentation_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_medicine_presentation_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_medicine_presentation_retired_by` FOREIGN KEY (`retired_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_medicine_presentation_status` CHECK (BINARY `identity_status` IN (BINARY 'candidate',BINARY 'verified',BINARY 'retired')),
  CONSTRAINT `chk_medicine_presentation_revision` CHECK (`metadata_revision` >= 1),
  CONSTRAINT `chk_medicine_presentation_verification` CHECK ((`verified_by` IS NULL AND `verified_at` IS NULL AND `verification_reference` IS NULL) OR (`verified_by` IS NOT NULL AND `verified_at` IS NOT NULL AND `verification_reference` IS NOT NULL AND CHAR_LENGTH(TRIM(`verification_reference`)) > 0)),
  CONSTRAINT `chk_medicine_presentation_verified` CHECK (BINARY `identity_status` <> BINARY 'verified' OR `verified_by` IS NOT NULL),
  CONSTRAINT `chk_medicine_presentation_retirement` CHECK ((`retired_by` IS NULL AND `retired_at` IS NULL AND `retirement_reason` IS NULL) OR (`retired_by` IS NOT NULL AND `retired_at` IS NOT NULL AND `retirement_reason` IS NOT NULL AND CHAR_LENGTH(TRIM(`retirement_reason`)) > 0)),
  CONSTRAINT `chk_medicine_presentation_retired` CHECK (BINARY `identity_status` <> BINARY 'retired' OR `retired_by` IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `kobi_medicine_strengths` (
  `medicine_presentation_id` bigint(20) unsigned NOT NULL,
  `medicine_identity_id` bigint(20) unsigned NOT NULL,
  `medicine_ingredient_id` bigint(20) unsigned NOT NULL,
  `ratio_numerator` decimal(38,0) NOT NULL,
  `ratio_denominator` decimal(38,0) NOT NULL,
  `numerator_unit_code` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `denominator_unit_code` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `original_strength_text` varchar(512) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL,
  PRIMARY KEY (`medicine_presentation_id`,`medicine_ingredient_id`),
  KEY `idx_medicine_strength_presentation` (`medicine_identity_id`,`medicine_presentation_id`),
  KEY `idx_medicine_strength_ingredient` (`medicine_identity_id`,`medicine_ingredient_id`),
  CONSTRAINT `fk_medicine_strength_presentation` FOREIGN KEY (`medicine_identity_id`,`medicine_presentation_id`) REFERENCES `kobi_medicine_presentations` (`medicine_identity_id`,`medicine_presentation_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_medicine_strength_ingredient` FOREIGN KEY (`medicine_identity_id`,`medicine_ingredient_id`) REFERENCES `kobi_medicine_ingredients` (`medicine_identity_id`,`medicine_ingredient_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_medicine_strength_ratio` CHECK (`ratio_numerator` > 0 AND `ratio_denominator` > 0),
  CONSTRAINT `chk_medicine_strength_numerator_unit` CHECK (BINARY `numerator_unit_code` IN (BINARY 'g')),
  CONSTRAINT `chk_medicine_strength_denominator_unit` CHECK (BINARY `denominator_unit_code` IN (BINARY 'unit',BINARY 'mL',BINARY 'g'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `health_medicine_identity_links` (
  `health_medicine_id` int(11) NOT NULL,
  `association_mode` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `medicine_identity_id` bigint(20) unsigned DEFAULT NULL,
  `medicine_presentation_id` bigint(20) unsigned DEFAULT NULL,
  `link_revision` int(10) unsigned NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `created_at` datetime(6) NOT NULL,
  `updated_at` datetime(6) NOT NULL,
  PRIMARY KEY (`health_medicine_id`),
  UNIQUE KEY `uq_health_medicine_link_mode` (`health_medicine_id`,`association_mode`),
  KEY `idx_health_medicine_link_medicine_identity_id` (`medicine_identity_id`),
  KEY `idx_health_medicine_link_medicine_presentation_id` (`medicine_presentation_id`),
  KEY `idx_health_medicine_link_created_by` (`created_by`),
  KEY `idx_health_medicine_link_updated_by` (`updated_by`),
  CONSTRAINT `fk_health_medicine_link_record` FOREIGN KEY (`health_medicine_id`) REFERENCES `health_medicines` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_health_medicine_link_identity` FOREIGN KEY (`medicine_identity_id`) REFERENCES `kobi_medicine_identities` (`medicine_identity_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_health_medicine_link_presentation` FOREIGN KEY (`medicine_presentation_id`) REFERENCES `kobi_medicine_presentations` (`medicine_presentation_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_health_medicine_link_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_health_medicine_link_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_health_medicine_link_revision` CHECK (`link_revision` >= 1),
  CONSTRAINT `chk_health_medicine_link_mode` CHECK (BINARY `association_mode` IN (BINARY 'composition',BINARY 'presentation',BINARY 'ingredients')),
  CONSTRAINT `chk_health_medicine_link_shape` CHECK ((BINARY `association_mode` = BINARY 'composition' AND `medicine_identity_id` IS NOT NULL AND `medicine_presentation_id` IS NULL) OR (BINARY `association_mode` = BINARY 'presentation' AND `medicine_identity_id` IS NULL AND `medicine_presentation_id` IS NOT NULL) OR (BINARY `association_mode` = BINARY 'ingredients' AND `medicine_identity_id` IS NULL AND `medicine_presentation_id` IS NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `health_medicine_selected_ingredients` (
  `health_medicine_id` int(11) NOT NULL,
  `chemical_ref_id` bigint(20) unsigned NOT NULL,
  `association_mode` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `display_order` smallint(5) unsigned NOT NULL,
  `observed_name` varchar(500) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL,
  PRIMARY KEY (`health_medicine_id`,`chemical_ref_id`),
  UNIQUE KEY `uq_health_medicine_selection_order` (`health_medicine_id`,`display_order`),
  KEY `idx_health_medicine_selection_mode` (`health_medicine_id`,`association_mode`),
  KEY `idx_health_medicine_selection_ref` (`chemical_ref_id`),
  CONSTRAINT `fk_health_medicine_selection_link` FOREIGN KEY (`health_medicine_id`,`association_mode`) REFERENCES `health_medicine_identity_links` (`health_medicine_id`,`association_mode`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_health_medicine_selection_ref` FOREIGN KEY (`chemical_ref_id`) REFERENCES `kobi_medicine_chemical_refs` (`chemical_ref_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_health_medicine_selection_mode` CHECK (BINARY `association_mode` = BINARY 'ingredients'),
  CONSTRAINT `chk_health_medicine_selection_order` CHECK (`display_order` BETWEEN 1 AND 16)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `medicine_authority_grants` (
 `id` bigint unsigned NOT NULL AUTO_INCREMENT,
 `user_id` int(11) NOT NULL,
 `capability` varchar(16) COLLATE utf8mb4_bin NOT NULL,
 `scope` varchar(16) COLLATE utf8mb4_bin NOT NULL,
 `is_active` tinyint unsigned NOT NULL DEFAULT 1,
 `qualification_reference` text DEFAULT NULL,
 `qualification_assessed_by` int(11) DEFAULT NULL,
 `qualification_scope` varchar(16) COLLATE utf8mb4_bin DEFAULT NULL,
 `granted_by` int(11) NOT NULL,
 `granted_at` datetime(6) NOT NULL,
 `expires_at` datetime(6) NOT NULL,
 `revoked_by` int(11) DEFAULT NULL,
 `revoked_at` datetime(6) DEFAULT NULL,
 `revocation_reason` text DEFAULT NULL,
 `active_slot` tinyint GENERATED ALWAYS AS (CASE WHEN revoked_at IS NULL THEN 1 ELSE NULL END) STORED,
 PRIMARY KEY (`id`),
 UNIQUE KEY `uq_medicine_active_grant` (`user_id`,`capability`,`scope`,`active_slot`),
 KEY `idx_medicine_grant_actor` (`user_id`,`is_active`,`expires_at`),
 CONSTRAINT `fk_medicine_grant_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_grant_authorizer` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_grant_qualification` FOREIGN KEY (`qualification_assessed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_grant_revoker` FOREIGN KEY (`revoked_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `chk_medicine_grant_codes` CHECK (BINARY capability IN (BINARY 'contributor',BINARY 'editor',BINARY 'reviewer',BINARY 'publisher',BINARY 'steward',BINARY 'administrator',BINARY 'registry',BINARY 'deployment') AND BINARY scope IN (BINARY 'chemical',BINARY 'composition',BINARY 'presentation',BINARY 'catalog')),
 CONSTRAINT `chk_medicine_grant_active` CHECK (is_active IN (0,1) AND expires_at > granted_at),
 CONSTRAINT `chk_medicine_grant_qualification_tuple` CHECK ((qualification_reference IS NULL AND qualification_assessed_by IS NULL AND qualification_scope IS NULL) OR (qualification_reference IS NOT NULL AND CHAR_LENGTH(TRIM(qualification_reference))>0 AND qualification_assessed_by IS NOT NULL AND qualification_scope IS NOT NULL AND BINARY qualification_scope IN (BINARY 'chemical',BINARY 'composition',BINARY 'presentation',BINARY 'catalog'))),
 CONSTRAINT `chk_medicine_grant_reviewer` CHECK (BINARY capability <> BINARY 'reviewer' OR qualification_reference IS NOT NULL),
 CONSTRAINT `chk_medicine_grant_revocation` CHECK ((revoked_by IS NULL AND revoked_at IS NULL AND revocation_reason IS NULL) OR (revoked_by IS NOT NULL AND revoked_at IS NOT NULL AND revocation_reason IS NOT NULL AND CHAR_LENGTH(TRIM(revocation_reason))>0 AND is_active=0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `medicine_governance_events` (
 `id` bigint unsigned NOT NULL AUTO_INCREMENT,
 `grant_subject_id` bigint unsigned DEFAULT NULL,
 `chemical_ref_id` bigint unsigned DEFAULT NULL,
 `medicine_identity_id` bigint unsigned DEFAULT NULL,
 `medicine_presentation_id` bigint unsigned DEFAULT NULL,
 `actor_id` int(11) NOT NULL,
 `authority_grant_id` bigint unsigned DEFAULT NULL,
 `action` varchar(16) COLLATE utf8mb4_bin NOT NULL,
 `revision` int unsigned NOT NULL,
 `previous_state` varchar(16) COLLATE utf8mb4_bin DEFAULT NULL,
 `new_state` varchar(16) COLLATE utf8mb4_bin NOT NULL,
 `rationale` text NOT NULL,
 `evidence_json` mediumtext COLLATE utf8mb4_bin NOT NULL,
 `snapshot_json` mediumtext COLLATE utf8mb4_bin NOT NULL,
 `snapshot_hash` binary(32) NOT NULL,
 `independent` tinyint unsigned NOT NULL DEFAULT 0,
 `occurred_at` datetime(6) NOT NULL,
 PRIMARY KEY (`id`),
 KEY `idx_medicine_event_grant` (`grant_subject_id`,`id`),
 KEY `idx_medicine_event_ref` (`chemical_ref_id`,`id`),
 KEY `idx_medicine_event_identity` (`medicine_identity_id`,`id`),
 KEY `idx_medicine_event_presentation` (`medicine_presentation_id`,`id`),
 CONSTRAINT `fk_medicine_event_grant` FOREIGN KEY (`grant_subject_id`) REFERENCES `medicine_authority_grants` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_event_authority` FOREIGN KEY (`authority_grant_id`) REFERENCES `medicine_authority_grants` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_event_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_event_ref` FOREIGN KEY (`chemical_ref_id`) REFERENCES `kobi_medicine_chemical_refs` (`chemical_ref_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_event_identity` FOREIGN KEY (`medicine_identity_id`) REFERENCES `kobi_medicine_identities` (`medicine_identity_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_event_presentation` FOREIGN KEY (`medicine_presentation_id`) REFERENCES `kobi_medicine_presentations` (`medicine_presentation_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `chk_medicine_event_subject` CHECK ((grant_subject_id IS NOT NULL)+(chemical_ref_id IS NOT NULL)+(medicine_identity_id IS NOT NULL)+(medicine_presentation_id IS NOT NULL)=1),
 CONSTRAINT `chk_medicine_event_action` CHECK (BINARY action IN (BINARY 'grant',BINARY 'revoke',BINARY 'assess',BINARY 'edit',BINARY 'verify',BINARY 'invalidate',BINARY 'retire',BINARY 'create',BINARY 'submit',BINARY 'reject',BINARY 'pilot_enroll',BINARY 'pilot_disable')),
 CONSTRAINT `chk_medicine_event_state` CHECK (BINARY new_state IN (BINARY 'active',BINARY 'revoked',BINARY 'candidate',BINARY 'unresolved',BINARY 'verified',BINARY 'invalidated',BINARY 'withdrawn',BINARY 'superseded',BINARY 'retired') AND (previous_state IS NULL OR BINARY previous_state IN (BINARY 'active',BINARY 'revoked',BINARY 'candidate',BINARY 'unresolved',BINARY 'verified',BINARY 'invalidated',BINARY 'withdrawn',BINARY 'superseded',BINARY 'retired'))),
 CONSTRAINT `chk_medicine_event_shape` CHECK (revision>=1 AND independent IN (0,1) AND CHAR_LENGTH(TRIM(rationale))>0 AND JSON_VALID(evidence_json) AND JSON_VALID(snapshot_json) AND OCTET_LENGTH(evidence_json)<=65535 AND OCTET_LENGTH(snapshot_json)<=262144),
 CONSTRAINT `chk_medicine_event_review` CHECK (BINARY action <> BINARY 'verify' OR (independent=1 AND authority_grant_id IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `medicine_registry_assessments` (
 `chemical_ref_id` bigint unsigned NOT NULL,
 `candidate_identity_id` bigint unsigned DEFAULT NULL,
 `revision` int unsigned NOT NULL,
 `assessment_state` varchar(16) COLLATE utf8mb4_bin NOT NULL,
 `registry_hash` binary(32) NOT NULL,
 `registry_snapshot` mediumtext COLLATE utf8mb4_bin NOT NULL,
 `decision_event_id` bigint unsigned NOT NULL,
 `updated_by` int(11) NOT NULL,
 `updated_at` datetime(6) NOT NULL,
 PRIMARY KEY (`chemical_ref_id`),
 KEY `idx_medicine_assessment_state` (`assessment_state`,`chemical_ref_id`),
 CONSTRAINT `fk_medicine_assessment_ref` FOREIGN KEY (`chemical_ref_id`) REFERENCES `kobi_medicine_chemical_refs` (`chemical_ref_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_assessment_identity` FOREIGN KEY (`candidate_identity_id`) REFERENCES `kobi_medicine_identities` (`medicine_identity_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_assessment_event` FOREIGN KEY (`decision_event_id`) REFERENCES `medicine_governance_events` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_assessment_actor` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `chk_medicine_assessment_state` CHECK (BINARY assessment_state IN (BINARY 'candidate',BINARY 'unresolved',BINARY 'verified',BINARY 'invalidated',BINARY 'withdrawn',BINARY 'superseded',BINARY 'retired')),
 CONSTRAINT `chk_medicine_assessment_shape` CHECK (revision>=1 AND JSON_VALID(registry_snapshot) AND OCTET_LENGTH(registry_snapshot)<=262144)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$
CREATE TRIGGER `medicine_events_no_update` BEFORE UPDATE ON `medicine_governance_events` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine history is immutable';
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `medicine_events_no_delete` BEFORE DELETE ON `medicine_governance_events` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine history is immutable';
END$$
DELIMITER ;

CREATE TABLE `medicine_registry_lifecycle_events` (
 `id` bigint unsigned NOT NULL AUTO_INCREMENT,
 `substance_id` bigint unsigned DEFAULT NULL,
 `form_id` bigint unsigned DEFAULT NULL,
 `old_active` smallint NOT NULL,
 `new_active` smallint NOT NULL,
 `source` varchar(32) COLLATE utf8mb4_bin NOT NULL DEFAULT 'registry_trigger',
 `occurred_at` datetime(6) NOT NULL,
 PRIMARY KEY (`id`),
 KEY `idx_medicine_lifecycle_substance` (`substance_id`,`id`),
 KEY `idx_medicine_lifecycle_form` (`form_id`,`id`),
 CONSTRAINT `fk_medicine_lifecycle_substance` FOREIGN KEY (`substance_id`) REFERENCES `kobi_substances` (`substance_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_lifecycle_form` FOREIGN KEY (`form_id`) REFERENCES `kobi_substance_forms` (`form_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `chk_medicine_lifecycle_target` CHECK ((substance_id IS NOT NULL)+(form_id IS NOT NULL)=1),
 CONSTRAINT `chk_medicine_lifecycle_change` CHECK (old_active<>new_active AND old_active BETWEEN -128 AND 127 AND new_active BETWEEN -128 AND 127),
 CONSTRAINT `chk_medicine_lifecycle_source` CHECK (BINARY source=BINARY 'registry_trigger')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$
CREATE TRIGGER `medicine_substance_activity_history` AFTER UPDATE ON `kobi_substances` FOR EACH ROW
BEGIN
 IF NOT (OLD.is_active <=> NEW.is_active) THEN
  INSERT INTO medicine_registry_lifecycle_events(substance_id,old_active,new_active,source,occurred_at)
  VALUES(NEW.substance_id,OLD.is_active,NEW.is_active,'registry_trigger',UTC_TIMESTAMP(6));
 END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `medicine_form_activity_history` AFTER UPDATE ON `kobi_substance_forms` FOR EACH ROW
BEGIN
 IF NOT (OLD.is_active <=> NEW.is_active) THEN
  INSERT INTO medicine_registry_lifecycle_events(form_id,old_active,new_active,source,occurred_at)
  VALUES(NEW.form_id,OLD.is_active,NEW.is_active,'registry_trigger',UTC_TIMESTAMP(6));
 END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `medicine_lifecycle_no_update` BEFORE UPDATE ON `medicine_registry_lifecycle_events` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine lifecycle history is immutable';
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `medicine_lifecycle_no_delete` BEFORE DELETE ON `medicine_registry_lifecycle_events` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine lifecycle history is immutable';
END$$
DELIMITER ;

CREATE TABLE `medicine_pilot_records` (
 `id` bigint unsigned NOT NULL AUTO_INCREMENT,
 `pilot_code` varchar(64) COLLATE utf8mb4_bin NOT NULL,
 `scope` varchar(16) COLLATE utf8mb4_bin NOT NULL,
 `chemical_ref_id` bigint unsigned DEFAULT NULL,
 `medicine_identity_id` bigint unsigned DEFAULT NULL,
 `medicine_presentation_id` bigint unsigned DEFAULT NULL,
 `creation_event_id` bigint unsigned NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `uq_medicine_pilot_ref` (`chemical_ref_id`),
 UNIQUE KEY `uq_medicine_pilot_identity` (`medicine_identity_id`),
 UNIQUE KEY `uq_medicine_pilot_presentation` (`medicine_presentation_id`),
 UNIQUE KEY `uq_medicine_pilot_event` (`creation_event_id`),
 KEY `idx_medicine_pilot_scope` (`pilot_code`,`scope`,`id`),
 CONSTRAINT `fk_medicine_pilot_ref` FOREIGN KEY (`chemical_ref_id`) REFERENCES `kobi_medicine_chemical_refs` (`chemical_ref_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_pilot_identity` FOREIGN KEY (`medicine_identity_id`) REFERENCES `kobi_medicine_identities` (`medicine_identity_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_pilot_presentation` FOREIGN KEY (`medicine_presentation_id`) REFERENCES `kobi_medicine_presentations` (`medicine_presentation_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `fk_medicine_pilot_event` FOREIGN KEY (`creation_event_id`) REFERENCES `medicine_governance_events` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `chk_medicine_pilot_code` CHECK (BINARY pilot_code REGEXP '^[a-z0-9_-]{1,64}$'),
 CONSTRAINT `chk_medicine_pilot_target` CHECK (
  (BINARY scope=BINARY 'chemical' AND chemical_ref_id IS NOT NULL AND medicine_identity_id IS NULL AND medicine_presentation_id IS NULL) OR
  (BINARY scope=BINARY 'composition' AND chemical_ref_id IS NULL AND medicine_identity_id IS NOT NULL AND medicine_presentation_id IS NULL) OR
  (BINARY scope=BINARY 'presentation' AND chemical_ref_id IS NULL AND medicine_identity_id IS NULL AND medicine_presentation_id IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$
CREATE TRIGGER `medicine_pilot_no_update` BEFORE UPDATE ON `medicine_pilot_records` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine pilot enrollment is immutable';
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `medicine_pilot_no_delete` BEFORE DELETE ON `medicine_pilot_records` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine pilot enrollment is immutable';
END$$
DELIMITER ;
