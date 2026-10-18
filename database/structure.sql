/*
SQLyog Ultimate v9.20 
MySQL - 5.5.5-10.4.27-MariaDB : Database - medical1
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*Table structure for table `body_parts` */

DROP TABLE IF EXISTS `body_parts`;

CREATE TABLE `body_parts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name_en` varchar(100) NOT NULL,
  `name_hi` varchar(100) NOT NULL,
  `gender` enum('male','female','both') DEFAULT 'both',
  `slug` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `causes` */

DROP TABLE IF EXISTS `causes`;

CREATE TABLE `causes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cause_en` varchar(255) NOT NULL,
  `cause_hi` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `disease_causes` */

DROP TABLE IF EXISTS `disease_causes`;

CREATE TABLE `disease_causes` (
  `disease_id` int(11) NOT NULL,
  `cause_id` int(11) NOT NULL,
  PRIMARY KEY (`disease_id`,`cause_id`),
  KEY `idx_disease_causes_cause` (`cause_id`),
  CONSTRAINT `fk_disease_causes_cause` FOREIGN KEY (`cause_id`) REFERENCES `causes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_disease_causes_disease` FOREIGN KEY (`disease_id`) REFERENCES `diseases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `disease_content` */

DROP TABLE IF EXISTS `disease_content`;

CREATE TABLE `disease_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `disease_id` int(11) NOT NULL,
  `overview_en` longtext DEFAULT NULL,
  `overview_hi` longtext DEFAULT NULL,
  `causes_en` longtext DEFAULT NULL,
  `causes_hi` longtext DEFAULT NULL,
  `risk_factors_en` longtext DEFAULT NULL,
  `risk_factors_hi` longtext DEFAULT NULL,
  `diagnosis_en` longtext DEFAULT NULL,
  `diagnosis_hi` longtext DEFAULT NULL,
  `prevention_en` longtext DEFAULT NULL,
  `prevention_hi` longtext DEFAULT NULL,
  `urgency_note_en` text DEFAULT NULL,
  `urgency_note_hi` text DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_disease_content` (`disease_id`),
  CONSTRAINT `disease_content_ibfk_1` FOREIGN KEY (`disease_id`) REFERENCES `diseases` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=414 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `disease_diets` */

DROP TABLE IF EXISTS `disease_diets`;

CREATE TABLE `disease_diets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `disease_id` int(11) NOT NULL,
  `diet_type` enum('recommended','avoid') NOT NULL,
  `item_en` varchar(255) NOT NULL,
  `item_hi` varchar(255) DEFAULT NULL,
  `notes_en` text DEFAULT NULL,
  `notes_hi` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_diet_disease` (`disease_id`),
  CONSTRAINT `disease_diets_ibfk_1` FOREIGN KEY (`disease_id`) REFERENCES `diseases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2249 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `disease_faqs` */

DROP TABLE IF EXISTS `disease_faqs`;

CREATE TABLE `disease_faqs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `disease_id` int(11) NOT NULL,
  `question_en` varchar(500) DEFAULT NULL,
  `answer_en` text DEFAULT NULL,
  `question_hi` varchar(500) DEFAULT NULL,
  `answer_hi` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_faq_disease` (`disease_id`),
  CONSTRAINT `disease_faqs_ibfk_1` FOREIGN KEY (`disease_id`) REFERENCES `diseases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `disease_preventions` */

DROP TABLE IF EXISTS `disease_preventions`;

CREATE TABLE `disease_preventions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `disease_id` int(11) NOT NULL,
  `prevention_en` text DEFAULT NULL,
  `prevention_hi` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `disease_id` (`disease_id`),
  CONSTRAINT `disease_preventions_ibfk_1` FOREIGN KEY (`disease_id`) REFERENCES `diseases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `disease_seo` */

DROP TABLE IF EXISTS `disease_seo`;

CREATE TABLE `disease_seo` (
  `disease_id` int(11) NOT NULL,
  `meta_title_en` varchar(255) DEFAULT NULL,
  `meta_title_hi` varchar(255) DEFAULT NULL,
  `meta_description_en` text DEFAULT NULL,
  `meta_description_hi` text DEFAULT NULL,
  `keywords_en` text DEFAULT NULL,
  `keywords_hi` text DEFAULT NULL,
  PRIMARY KEY (`disease_id`),
  CONSTRAINT `disease_seo_ibfk_1` FOREIGN KEY (`disease_id`) REFERENCES `diseases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `disease_symptoms` */

DROP TABLE IF EXISTS `disease_symptoms`;

CREATE TABLE `disease_symptoms` (
  `disease_id` int(11) NOT NULL,
  `symptom_id` int(11) NOT NULL,
  PRIMARY KEY (`disease_id`,`symptom_id`),
  KEY `idx_symptom_id` (`symptom_id`),
  CONSTRAINT `disease_symptoms_ibfk_1` FOREIGN KEY (`disease_id`) REFERENCES `diseases` (`id`),
  CONSTRAINT `disease_symptoms_ibfk_2` FOREIGN KEY (`symptom_id`) REFERENCES `symptoms` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `diseases` */

DROP TABLE IF EXISTS `diseases`;

CREATE TABLE `diseases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `body_part_id` int(11) NOT NULL,
  `disease_en` varchar(255) NOT NULL,
  `disease_hi` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `gender` enum('male','female','both') DEFAULT 'both',
  `severity_level` enum('mild','moderate','urgent','emergency') DEFAULT 'moderate',
  `urgency_level` enum('routine','soon','urgent','emergency') DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `body_part_id` (`body_part_id`),
  CONSTRAINT `diseases_ibfk_1` FOREIGN KEY (`body_part_id`) REFERENCES `body_parts` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=401 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `featured_diseases` */

DROP TABLE IF EXISTS `featured_diseases`;

CREATE TABLE `featured_diseases` (
  `disease_id` int(11) NOT NULL,
  `priority_order` int(11) DEFAULT 0,
  PRIMARY KEY (`disease_id`),
  CONSTRAINT `featured_diseases_ibfk_1` FOREIGN KEY (`disease_id`) REFERENCES `diseases` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `health_allergies` */

DROP TABLE IF EXISTS `health_allergies`;

CREATE TABLE `health_allergies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `health_profile_id` int(11) NOT NULL,
  `allergen` varchar(255) NOT NULL,
  `category` enum('medication','food','environmental','other') NOT NULL DEFAULT 'other',
  `reaction` varchar(500) DEFAULT NULL,
  `severity` enum('mild','moderate','severe','life-threatening') DEFAULT NULL,
  `identified_on` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_health_allergy_profile` (`health_profile_id`),
  KEY `idx_health_allergy_category` (`category`),
  KEY `idx_health_allergy_severity` (`severity`),
  CONSTRAINT `fk_health_allergy_profile` FOREIGN KEY (`health_profile_id`) REFERENCES `health_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `health_conditions` */

DROP TABLE IF EXISTS `health_conditions`;

CREATE TABLE `health_conditions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `health_profile_id` int(11) NOT NULL,
  `condition_name` varchar(255) NOT NULL,
  `status` enum('active','resolved','chronic','historical') NOT NULL DEFAULT 'active',
  `diagnosed_on` date DEFAULT NULL,
  `resolved_on` date DEFAULT NULL,
  `doctor_hospital` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_health_condition_profile` (`health_profile_id`),
  KEY `idx_health_condition_status` (`status`),
  CONSTRAINT `fk_health_condition_profile` FOREIGN KEY (`health_profile_id`) REFERENCES `health_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `health_documents` */

DROP TABLE IF EXISTS `health_documents`;

CREATE TABLE `health_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `health_profile_id` int(11) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `document_type` enum('lab_report','imaging','prescription','discharge_summary','medical_record','surgery_procedure','consultation','other') NOT NULL DEFAULT 'other',
  `document_date` date DEFAULT NULL,
  `hospital` varchar(255) DEFAULT NULL,
  `doctor` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `original_filename` varchar(255) NOT NULL,
  `stored_filename` varchar(255) NOT NULL,
  `storage_path` varchar(500) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` bigint(20) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_health_document_profile` (`health_profile_id`),
  KEY `idx_health_document_type` (`document_type`),
  KEY `idx_health_document_date` (`document_date`),
  KEY `idx_health_document_uploaded_by` (`uploaded_by`),
  CONSTRAINT `fk_health_document_profile` FOREIGN KEY (`health_profile_id`) REFERENCES `health_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_health_document_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `health_insurance_contacts` */

DROP TABLE IF EXISTS `health_insurance_contacts`;

CREATE TABLE `health_insurance_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `policy_id` int(11) NOT NULL,
  `contact_type` enum('insurer','tpa','claims','other') NOT NULL DEFAULT 'insurer',
  `organization_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `alternate_phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(500) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_insurance_contact_policy` (`policy_id`),
  KEY `idx_insurance_contact_type` (`contact_type`),
  CONSTRAINT `fk_insurance_contact_policy` FOREIGN KEY (`policy_id`) REFERENCES `health_insurance_policies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `health_insurance_documents` */

DROP TABLE IF EXISTS `health_insurance_documents`;

CREATE TABLE `health_insurance_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `policy_id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `document_label` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_insurance_policy_document` (`policy_id`,`document_id`),
  KEY `idx_insurance_document_policy` (`policy_id`),
  KEY `idx_insurance_document_document` (`document_id`),
  CONSTRAINT `fk_insurance_document_document` FOREIGN KEY (`document_id`) REFERENCES `health_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_insurance_document_policy` FOREIGN KEY (`policy_id`) REFERENCES `health_insurance_policies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `health_insurance_members` */

DROP TABLE IF EXISTS `health_insurance_members`;

CREATE TABLE `health_insurance_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `policy_id` int(11) NOT NULL,
  `health_profile_id` int(11) DEFAULT NULL,
  `member_name` varchar(255) NOT NULL,
  `relationship` enum('self','spouse','child','parent','sibling','other') NOT NULL DEFAULT 'other',
  `member_id_number` varchar(150) DEFAULT NULL,
  `health_card_number` varchar(150) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `is_primary_insured` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_insurance_member_policy` (`policy_id`),
  KEY `idx_insurance_member_profile` (`health_profile_id`),
  CONSTRAINT `fk_insurance_member_policy` FOREIGN KEY (`policy_id`) REFERENCES `health_insurance_policies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_insurance_member_profile` FOREIGN KEY (`health_profile_id`) REFERENCES `health_profiles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `health_insurance_policies` */

DROP TABLE IF EXISTS `health_insurance_policies`;

CREATE TABLE `health_insurance_policies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `health_profile_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `provider_name` varchar(255) NOT NULL,
  `policy_number` varchar(150) DEFAULT NULL,
  `policy_name` varchar(255) DEFAULT NULL,
  `policy_type` enum('individual','family_floater','employer','government','supplementary','other') NOT NULL DEFAULT 'individual',
  `status` enum('active','expired','cancelled','pending','unknown') NOT NULL DEFAULT 'unknown',
  `sum_insured` decimal(15,2) DEFAULT NULL,
  `currency_code` char(3) NOT NULL DEFAULT 'INR',
  `start_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `renewal_notes` text DEFAULT NULL,
  `general_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_insurance_policy_profile` (`health_profile_id`),
  KEY `idx_insurance_policy_status` (`status`),
  KEY `idx_insurance_policy_expiry` (`expiry_date`),
  KEY `idx_insurance_policy_created_by` (`created_by`),
  CONSTRAINT `fk_insurance_policy_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_insurance_policy_profile` FOREIGN KEY (`health_profile_id`) REFERENCES `health_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `health_medicines` */

DROP TABLE IF EXISTS `health_medicines`;

CREATE TABLE `health_medicines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `health_profile_id` int(11) NOT NULL,
  `brand_name` varchar(255) NOT NULL,
  `generic_name` varchar(500) DEFAULT NULL,
  `strength` varchar(255) DEFAULT NULL,
  `dose` varchar(255) DEFAULT NULL,
  `frequency` varchar(255) DEFAULT NULL,
  `route` varchar(100) DEFAULT NULL,
  `status` enum('current','stopped','historical') NOT NULL DEFAULT 'current',
  `started_on` date DEFAULT NULL,
  `stopped_on` date DEFAULT NULL,
  `prescribed_by` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_health_medicine_profile` (`health_profile_id`),
  KEY `idx_health_medicine_status` (`status`),
  CONSTRAINT `fk_health_medicine_profile` FOREIGN KEY (`health_profile_id`) REFERENCES `health_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `health_procedures` */

DROP TABLE IF EXISTS `health_procedures`;

CREATE TABLE `health_procedures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `health_profile_id` int(11) NOT NULL,
  `procedure_name` varchar(255) NOT NULL,
  `procedure_type` enum('surgery','procedure','hospitalization') NOT NULL DEFAULT 'procedure',
  `performed_on` date DEFAULT NULL,
  `hospital` varchar(255) DEFAULT NULL,
  `doctor` varchar(255) DEFAULT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_health_procedure_profile` (`health_profile_id`),
  KEY `idx_health_procedure_type` (`procedure_type`),
  KEY `idx_health_procedure_date` (`performed_on`),
  CONSTRAINT `fk_health_procedure_profile` FOREIGN KEY (`health_profile_id`) REFERENCES `health_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `health_profile_invitations` */

DROP TABLE IF EXISTS `health_profile_invitations`;

CREATE TABLE `health_profile_invitations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `health_profile_id` int(11) NOT NULL,
  `invited_by` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` enum('editor','viewer') NOT NULL DEFAULT 'viewer',
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `status` enum('pending','accepted','revoked') NOT NULL DEFAULT 'pending',
  `accepted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_health_profile_invitation_token` (`token_hash`),
  KEY `idx_health_profile_invitation_profile` (`health_profile_id`),
  KEY `idx_health_profile_invitation_email` (`email`),
  KEY `idx_health_profile_invitation_status_expiry` (`status`,`expires_at`),
  KEY `idx_health_profile_invitation_invited_by` (`invited_by`),
  CONSTRAINT `fk_health_profile_invitation_invited_by` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_health_profile_invitation_profile` FOREIGN KEY (`health_profile_id`) REFERENCES `health_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `health_profile_members` */

DROP TABLE IF EXISTS `health_profile_members`;

CREATE TABLE `health_profile_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `health_profile_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('owner','editor','viewer') NOT NULL DEFAULT 'viewer',
  `status` enum('active','revoked') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_health_profile_member` (`health_profile_id`,`user_id`),
  KEY `idx_health_profile_member_profile` (`health_profile_id`),
  KEY `idx_health_profile_member_user` (`user_id`),
  KEY `idx_health_profile_member_status` (`status`),
  CONSTRAINT `fk_health_profile_member_profile` FOREIGN KEY (`health_profile_id`) REFERENCES `health_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_health_profile_member_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `health_profiles` */

DROP TABLE IF EXISTS `health_profiles`;

CREATE TABLE `health_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profile_type` enum('self','family','dependent') NOT NULL DEFAULT 'family',
  `full_name` varchar(150) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other','unspecified') NOT NULL DEFAULT 'unspecified',
  `created_by` int(11) NOT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_health_profile_created_by` (`created_by`),
  KEY `idx_health_profile_status` (`status`),
  CONSTRAINT `fk_health_profile_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `kobi_combination_items` */

DROP TABLE IF EXISTS `kobi_combination_items`;

CREATE TABLE `kobi_combination_items` (
  `combination_item_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `combination_id` bigint(20) unsigned NOT NULL,
  `substance_id` bigint(20) unsigned DEFAULT NULL,
  `form_id` bigint(20) unsigned DEFAULT NULL,
  `component_name` varchar(255) NOT NULL,
  `component_name_normalized` varchar(255) NOT NULL,
  `sequence_no` smallint(5) unsigned DEFAULT NULL,
  `strength_text` varchar(100) DEFAULT NULL,
  `review_status` varchar(30) NOT NULL DEFAULT 'PENDING',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`combination_item_id`),
  KEY `idx_combination_items_combination` (`combination_id`),
  KEY `idx_combination_items_substance` (`substance_id`),
  KEY `idx_combination_items_form` (`form_id`),
  CONSTRAINT `fk_combination_item_combination` FOREIGN KEY (`combination_id`) REFERENCES `kobi_combinations` (`combination_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_combination_item_form` FOREIGN KEY (`form_id`) REFERENCES `kobi_substance_forms` (`form_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_combination_item_substance` FOREIGN KEY (`substance_id`) REFERENCES `kobi_substances` (`substance_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6672 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Table structure for table `kobi_combinations` */

DROP TABLE IF EXISTS `kobi_combinations`;

CREATE TABLE `kobi_combinations` (
  `combination_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `combination_name` varchar(500) NOT NULL,
  `combination_name_normalized` varchar(500) NOT NULL,
  `source_name` varchar(100) DEFAULT NULL,
  `source_record_key` varchar(100) DEFAULT NULL,
  `review_status` varchar(30) NOT NULL DEFAULT 'PENDING',
  `is_searchable` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`combination_id`),
  UNIQUE KEY `uq_combination_name_normalized` (`combination_name_normalized`),
  KEY `idx_combination_search` (`is_active`,`is_searchable`,`combination_name_normalized`)
) ENGINE=InnoDB AUTO_INCREMENT=2328 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Table structure for table `kobi_form_components` */

DROP TABLE IF EXISTS `kobi_form_components`;

CREATE TABLE `kobi_form_components` (
  `form_component_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint(20) unsigned NOT NULL,
  `component_type` varchar(30) NOT NULL,
  `component_name` varchar(255) NOT NULL,
  `component_name_normalized` varchar(255) NOT NULL,
  `component_substance_id` bigint(20) unsigned DEFAULT NULL,
  `stoichiometry` decimal(12,6) DEFAULT NULL,
  `verification_status` varchar(30) NOT NULL DEFAULT 'PENDING',
  `evidence_reference` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`form_component_id`),
  KEY `idx_component_form` (`form_id`),
  KEY `idx_component_substance` (`component_substance_id`),
  KEY `idx_component_name` (`component_name_normalized`),
  CONSTRAINT `fk_component_form` FOREIGN KEY (`form_id`) REFERENCES `kobi_substance_forms` (`form_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_component_substance` FOREIGN KEY (`component_substance_id`) REFERENCES `kobi_substances` (`substance_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Table structure for table `kobi_identity_reviews` */

DROP TABLE IF EXISTS `kobi_identity_reviews`;

CREATE TABLE `kobi_identity_reviews` (
  `review_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source_name` varchar(255) NOT NULL,
  `source_alias_id` bigint(20) unsigned DEFAULT NULL,
  `candidate_substance_id` bigint(20) unsigned DEFAULT NULL,
  `candidate_form_id` bigint(20) unsigned DEFAULT NULL,
  `decision` varchar(30) NOT NULL DEFAULT 'PENDING',
  `review_reason` text DEFAULT NULL,
  `evidence` text DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`review_id`),
  KEY `idx_review_source` (`source_name`),
  KEY `idx_review_alias` (`source_alias_id`),
  KEY `idx_review_substance` (`candidate_substance_id`),
  KEY `idx_review_form` (`candidate_form_id`),
  KEY `idx_review_decision` (`decision`),
  CONSTRAINT `fk_review_alias` FOREIGN KEY (`source_alias_id`) REFERENCES `kobi_substance_aliases` (`alias_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_review_form` FOREIGN KEY (`candidate_form_id`) REFERENCES `kobi_substance_forms` (`form_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_review_substance` FOREIGN KEY (`candidate_substance_id`) REFERENCES `kobi_substances` (`substance_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Table structure for table `kobi_substance_aliases` */

DROP TABLE IF EXISTS `kobi_substance_aliases`;

CREATE TABLE `kobi_substance_aliases` (
  `alias_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `substance_id` bigint(20) unsigned DEFAULT NULL,
  `alias_name` varchar(255) NOT NULL,
  `alias_name_normalized` varchar(255) NOT NULL,
  `alias_type` varchar(40) NOT NULL DEFAULT 'SOURCE_NAME',
  `source_name` varchar(100) DEFAULT NULL,
  `source_record_key` varchar(100) DEFAULT NULL,
  `source_reference` text DEFAULT NULL,
  `review_status` varchar(30) NOT NULL DEFAULT 'PENDING',
  `is_searchable` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`alias_id`),
  KEY `idx_alias_search` (`is_active`,`is_searchable`,`alias_name_normalized`),
  KEY `idx_alias_substance` (`substance_id`),
  KEY `idx_alias_source` (`source_name`),
  CONSTRAINT `fk_alias_substance` FOREIGN KEY (`substance_id`) REFERENCES `kobi_substances` (`substance_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2225 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Table structure for table `kobi_substance_forms` */

DROP TABLE IF EXISTS `kobi_substance_forms`;

CREATE TABLE `kobi_substance_forms` (
  `form_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `substance_id` bigint(20) unsigned NOT NULL,
  `form_name` varchar(255) NOT NULL,
  `form_name_normalized` varchar(255) NOT NULL,
  `form_type` varchar(40) NOT NULL DEFAULT 'UNKNOWN',
  `form_status` varchar(30) NOT NULL DEFAULT 'CANDIDATE',
  `canonical_form_name` varchar(255) DEFAULT NULL,
  `verification_source` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`form_id`),
  UNIQUE KEY `uq_substance_form` (`substance_id`,`form_name_normalized`),
  KEY `idx_form_search` (`is_active`,`form_name_normalized`),
  KEY `idx_form_substance` (`substance_id`),
  CONSTRAINT `fk_form_substance` FOREIGN KEY (`substance_id`) REFERENCES `kobi_substances` (`substance_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Table structure for table `kobi_substances` */

DROP TABLE IF EXISTS `kobi_substances`;

CREATE TABLE `kobi_substances` (
  `substance_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `canonical_name` varchar(255) NOT NULL,
  `canonical_name_normalized` varchar(255) NOT NULL,
  `naming_standard` varchar(50) DEFAULT 'WHO_INN',
  `identity_type` varchar(40) NOT NULL DEFAULT 'UNKNOWN',
  `identity_status` varchar(30) NOT NULL DEFAULT 'CANDIDATE',
  `is_searchable` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `verification_source` text DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`substance_id`),
  UNIQUE KEY `uq_substance_canonical_normalized` (`canonical_name_normalized`),
  KEY `idx_substance_search` (`is_active`,`is_searchable`,`canonical_name_normalized`),
  KEY `idx_substance_status` (`identity_status`)
) ENGINE=InnoDB AUTO_INCREMENT=2225 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Table structure for table `kobi_who_inn_evidence` */

DROP TABLE IF EXISTS `kobi_who_inn_evidence`;

CREATE TABLE `kobi_who_inn_evidence` (
  `evidence_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `substance_id` bigint(20) unsigned DEFAULT NULL,
  `inn_name` varchar(255) NOT NULL,
  `inn_name_normalized` varchar(255) NOT NULL,
  `inn_status` varchar(20) NOT NULL,
  `list_number` varchar(30) DEFAULT NULL,
  `publication_date` date DEFAULT NULL,
  `publication_url` text DEFAULT NULL,
  `pdf_url` text DEFAULT NULL,
  `page_reference` varchar(20) DEFAULT NULL,
  `source_sha256` char(64) DEFAULT NULL,
  `verification_status` varchar(30) NOT NULL DEFAULT 'PENDING',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`evidence_id`),
  KEY `idx_who_name` (`inn_name_normalized`),
  KEY `idx_who_substance` (`substance_id`),
  KEY `idx_who_status` (`inn_status`),
  CONSTRAINT `fk_who_substance` FOREIGN KEY (`substance_id`) REFERENCES `kobi_substances` (`substance_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Table structure for table `medical_summary_share_access_logs` */

DROP TABLE IF EXISTS `medical_summary_share_access_logs`;

CREATE TABLE `medical_summary_share_access_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `share_id` int(11) NOT NULL,
  `accessed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_medical_summary_share_access_share` (`share_id`),
  KEY `idx_medical_summary_share_access_date` (`accessed_at`),
  CONSTRAINT `fk_medical_summary_share_access_share` FOREIGN KEY (`share_id`) REFERENCES `medical_summary_shares` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `medical_summary_shares` */

DROP TABLE IF EXISTS `medical_summary_shares`;

CREATE TABLE `medical_summary_shares` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `health_profile_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `snapshot` mediumtext NOT NULL,
  `snapshot_schema_version` varchar(20) NOT NULL DEFAULT '1.0',
  `snapshot_created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `status` enum('active','revoked') NOT NULL DEFAULT 'active',
  `revoked_at` datetime DEFAULT NULL,
  `revoked_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_medical_summary_share_token` (`token_hash`),
  KEY `idx_medical_summary_share_profile` (`health_profile_id`),
  KEY `idx_medical_summary_share_creator` (`created_by`),
  KEY `idx_medical_summary_share_status_expiry` (`status`,`expires_at`),
  KEY `fk_medical_summary_share_revoked_by` (`revoked_by`),
  CONSTRAINT `fk_medical_summary_share_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_medical_summary_share_profile` FOREIGN KEY (`health_profile_id`) REFERENCES `health_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_medical_summary_share_revoked_by` FOREIGN KEY (`revoked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `symptoms` */

DROP TABLE IF EXISTS `symptoms`;

CREATE TABLE `symptoms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `symptom_en` varchar(255) NOT NULL,
  `symptom_hi` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `search_count` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_symptom_en` (`symptom_en`),
  KEY `idx_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=347 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `treatment_protocol_items` */

DROP TABLE IF EXISTS `treatment_protocol_items`;

CREATE TABLE `treatment_protocol_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `protocol_id` int(11) NOT NULL,
  `item_type` enum('medicine','herb','exercise','acupoint','diet','therapy','lifestyle') DEFAULT NULL,
  `title_en` varchar(255) DEFAULT NULL,
  `title_hi` varchar(255) DEFAULT NULL,
  `instructions_en` text DEFAULT NULL,
  `instructions_hi` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_included` tinyint unsigned NOT NULL DEFAULT 0,
  `evidence_classification` enum('evidence_supported','insufficient_evidence','evidence_does_not_support','not_assessed') NOT NULL DEFAULT 'not_assessed',
  `content_purpose` enum('clinical_management','traditional_practice','supportive_care','wellness_self_care','mixed') DEFAULT NULL,
  `limitations_en` text DEFAULT NULL,
  `limitations_hi` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_protocol_items` (`protocol_id`),
  KEY `idx_treatment_item_display` (`protocol_id`,`is_included`,`sort_order`,`id`),
  CONSTRAINT `chk_treatment_item_included` CHECK (`is_included` IN (0,1)),
  CONSTRAINT `treatment_protocol_items_ibfk_1` FOREIGN KEY (`protocol_id`) REFERENCES `treatment_protocols` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3401 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `treatment_protocols` */

DROP TABLE IF EXISTS `treatment_protocols`;

CREATE TABLE `treatment_protocols` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `disease_id` int(11) NOT NULL,
  `treatment_system_id` int(11) NOT NULL,
  `title_en` varchar(255) DEFAULT NULL,
  `title_hi` varchar(255) DEFAULT NULL,
  `overview_en` text DEFAULT NULL,
  `overview_hi` text DEFAULT NULL,
  `review_state` enum('draft','pending_review','changes_required','approved') NOT NULL DEFAULT 'draft',
  `publication_status` enum('unpublished','published','withdrawn') NOT NULL DEFAULT 'unpublished',
  `content_revision` int unsigned NOT NULL DEFAULT 1,
  `approved_review_id` bigint unsigned DEFAULT NULL,
  `risk_eligibility` enum('eligible','not_eligible','not_assessed') NOT NULL DEFAULT 'not_assessed',
  `published_locales` set('en','hi') NOT NULL DEFAULT '',
  `evidence_classification` enum('evidence_supported','insufficient_evidence','evidence_does_not_support','not_assessed') NOT NULL DEFAULT 'not_assessed',
  `content_purpose` enum('clinical_management','traditional_practice','supportive_care','wellness_self_care','mixed') DEFAULT NULL,
  `evidence_summary_en` text DEFAULT NULL,
  `evidence_summary_hi` text DEFAULT NULL,
  `applicability_en` text DEFAULT NULL,
  `applicability_hi` text DEFAULT NULL,
  `limitations_en` text DEFAULT NULL,
  `limitations_hi` text DEFAULT NULL,
  `updated_at` datetime(6) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_protocol_disease` (`disease_id`),
  KEY `idx_protocol_system` (`treatment_system_id`),
  KEY `idx_treatment_public_candidates` (`disease_id`,`publication_status`,`review_state`,`treatment_system_id`,`id`),
  KEY `idx_treatment_updated_by` (`updated_by`),
  KEY `idx_treatment_approved_revision` (`id`,`content_revision`,`approved_review_id`),
  CONSTRAINT `chk_treatment_revision` CHECK (`content_revision` >= 1),
  CONSTRAINT `chk_treatment_risk_value` CHECK (`risk_eligibility` IS NOT NULL AND `risk_eligibility` IN ('eligible','not_eligible','not_assessed')),
  CONSTRAINT `chk_treatment_approval_pointer` CHECK ((`review_state` = 'approved') = (`approved_review_id` IS NOT NULL)),
  CONSTRAINT `chk_treatment_publication` CHECK ((`publication_status` = 'published' AND `review_state` = 'approved' AND `published_locales` <> '') OR (`publication_status` <> 'published' AND `published_locales` = '')),
  CONSTRAINT `fk_treatment_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `treatment_protocols_ibfk_1` FOREIGN KEY (`disease_id`) REFERENCES `diseases` (`id`),
  CONSTRAINT `treatment_protocols_ibfk_2` FOREIGN KEY (`treatment_system_id`) REFERENCES `treatment_systems` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2291 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `treatment_systems` */

DROP TABLE IF EXISTS `treatment_systems`;

CREATE TABLE `treatment_systems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name_en` varchar(100) NOT NULL,
  `name_hi` varchar(100) DEFAULT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/* Treatment governance foundation. Schema definition only; never import this
   destructive full dump into an existing database to perform an upgrade. */

DROP TABLE IF EXISTS `treatment_sources`;
CREATE TABLE `treatment_sources` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source_type` enum('guideline','systematic_review','research_study','official_monograph','professional_guidance','traditional_text','other') NOT NULL,
  `title` varchar(500) NOT NULL,
  `author_or_organization` varchar(255) DEFAULT NULL,
  `publication` varchar(255) DEFAULT NULL,
  `citation_text` text NOT NULL,
  `url` varchar(2048) DEFAULT NULL,
  `identifier` varchar(255) DEFAULT NULL,
  `publication_date` date DEFAULT NULL,
  `edition_or_version` varchar(100) DEFAULT NULL,
  `accessed_on` date DEFAULT NULL,
  `status` enum('unverified','verified','withdrawn') NOT NULL DEFAULT 'unverified',
  `created_by` int(11) NOT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime(6) DEFAULT NULL,
  `withdrawn_by` int(11) DEFAULT NULL,
  `withdrawn_at` datetime(6) DEFAULT NULL,
  `withdrawal_reason` text DEFAULT NULL,
  `replaces_source_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_treatment_source_status` (`status`,`id`),
  KEY `idx_treatment_source_creator` (`created_by`),
  KEY `idx_treatment_source_verifier` (`verified_by`),
  KEY `idx_treatment_source_withdrawer` (`withdrawn_by`),
  KEY `idx_treatment_source_replaces` (`replaces_source_id`),
  CONSTRAINT `fk_treatment_source_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_treatment_source_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_treatment_source_withdrawer` FOREIGN KEY (`withdrawn_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_treatment_source_replaces` FOREIGN KEY (`replaces_source_id`) REFERENCES `treatment_sources` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_treatment_source_identity` CHECK (CHAR_LENGTH(TRIM(`title`)) > 0 AND CHAR_LENGTH(TRIM(`citation_text`)) > 0),
  CONSTRAINT `chk_treatment_source_lifecycle` CHECK (
    (`status` = 'unverified' AND `verified_by` IS NULL AND `verified_at` IS NULL AND `withdrawn_by` IS NULL AND `withdrawn_at` IS NULL AND `withdrawal_reason` IS NULL) OR
    (`status` = 'verified' AND `verified_by` IS NOT NULL AND `verified_at` IS NOT NULL AND `withdrawn_by` IS NULL AND `withdrawn_at` IS NULL AND `withdrawal_reason` IS NULL) OR
    (`status` = 'withdrawn' AND `withdrawn_by` IS NOT NULL AND `withdrawn_at` IS NOT NULL AND `withdrawal_reason` IS NOT NULL AND CHAR_LENGTH(TRIM(`withdrawal_reason`)) > 0 AND ((`verified_by` IS NULL AND `verified_at` IS NULL) OR (`verified_by` IS NOT NULL AND `verified_at` IS NOT NULL)))
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$
CREATE TRIGGER `trg_treatment_source_no_self_insert`
AFTER INSERT ON `treatment_sources` FOR EACH ROW
BEGIN
  IF NEW.replaces_source_id = NEW.id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A treatment source cannot replace itself.';
  END IF;
END$$
CREATE TRIGGER `trg_treatment_source_no_self_update`
AFTER UPDATE ON `treatment_sources` FOR EACH ROW
BEGIN
  IF NEW.replaces_source_id = NEW.id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A treatment source cannot replace itself.';
  END IF;
END$$
DELIMITER ;

DROP TABLE IF EXISTS `treatment_protocol_sources`;
CREATE TABLE `treatment_protocol_sources` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `protocol_id` int(11) NOT NULL,
  `source_id` bigint unsigned NOT NULL,
  `claim_scope` text NOT NULL,
  `locator` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_protocol_source` (`protocol_id`,`source_id`),
  KEY `idx_treatment_source_protocol` (`source_id`,`protocol_id`),
  CONSTRAINT `fk_treatment_protocol_source_protocol` FOREIGN KEY (`protocol_id`) REFERENCES `treatment_protocols` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_treatment_protocol_source_source` FOREIGN KEY (`source_id`) REFERENCES `treatment_sources` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_treatment_protocol_claim` CHECK (CHAR_LENGTH(TRIM(`claim_scope`)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `treatment_item_sources`;
CREATE TABLE `treatment_item_sources` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `source_id` bigint unsigned NOT NULL,
  `claim_scope` text NOT NULL,
  `locator` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_item_source` (`item_id`,`source_id`),
  KEY `idx_treatment_source_item` (`source_id`,`item_id`),
  CONSTRAINT `fk_treatment_item_source_item` FOREIGN KEY (`item_id`) REFERENCES `treatment_protocol_items` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_treatment_item_source_source` FOREIGN KEY (`source_id`) REFERENCES `treatment_sources` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_treatment_item_claim` CHECK (CHAR_LENGTH(TRIM(`claim_scope`)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `treatment_protocol_events`;
CREATE TABLE `treatment_protocol_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `protocol_id` int(11) NOT NULL,
  `content_revision` int unsigned NOT NULL,
  `event_type` enum('legacy_initialized','content_changed','submitted','approved','changes_required','review_reset','published','withdrawn','publication_locales_changed','source_invalidated','risk_assessed') NOT NULL,
  `actor_id` int(11) DEFAULT NULL,
  `occurred_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `locales` set('en','hi') NOT NULL DEFAULT '',
  `notes` text DEFAULT NULL,
  `review_due_at` datetime(6) DEFAULT NULL,
  `independence_confirmed` tinyint unsigned NOT NULL DEFAULT 0,
  `content_snapshot` longtext DEFAULT NULL,
  `content_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_treatment_event_revision` (`protocol_id`,`content_revision`,`id`),
  KEY `idx_treatment_event_history` (`protocol_id`,`id`),
  KEY `idx_treatment_event_action` (`protocol_id`,`event_type`,`content_revision`,`id`),
  KEY `idx_treatment_event_actor` (`actor_id`,`event_type`),
  CONSTRAINT `fk_treatment_event_protocol` FOREIGN KEY (`protocol_id`) REFERENCES `treatment_protocols` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_treatment_event_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_treatment_event_revision` CHECK (`content_revision` >= 1),
  CONSTRAINT `chk_treatment_event_type` CHECK (`event_type` IS NOT NULL AND `event_type` IN ('legacy_initialized','content_changed','submitted','approved','changes_required','review_reset','published','withdrawn','publication_locales_changed','source_invalidated','risk_assessed')),
  CONSTRAINT `chk_treatment_event_actor` CHECK (`event_type` = 'legacy_initialized' OR `actor_id` IS NOT NULL),
  CONSTRAINT `chk_treatment_event_independence` CHECK (`independence_confirmed` IN (0,1)),
  CONSTRAINT `chk_treatment_event_json` CHECK (`content_snapshot` IS NULL OR JSON_VALID(`content_snapshot`)),
  CONSTRAINT `chk_treatment_event_snapshot` CHECK (`event_type` NOT IN ('submitted','approved') OR (`locales` <> '' AND `content_snapshot` IS NOT NULL AND `content_hash` IS NOT NULL)),
  CONSTRAINT `chk_treatment_risk_event` CHECK (`event_type` <> 'risk_assessed' OR (`independence_confirmed` = 1 AND `content_snapshot` IS NOT NULL AND `content_hash` IS NOT NULL AND `notes` IS NOT NULL AND CHAR_LENGTH(TRIM(`notes`)) > 0)),
  CONSTRAINT `chk_treatment_risk_payload` CHECK (`event_type` <> 'risk_assessed' OR COALESCE(
    JSON_TYPE(JSON_EXTRACT(`content_snapshot`, '$.protocol.risk_eligibility')) = 'STRING'
    AND BINARY JSON_UNQUOTE(JSON_EXTRACT(`content_snapshot`, '$.protocol.risk_eligibility')) IN ('eligible','not_eligible','not_assessed')
    AND JSON_TYPE(JSON_EXTRACT(`content_snapshot`, '$.protocol.id')) = 'INTEGER'
    AND JSON_EXTRACT(`content_snapshot`, '$.protocol.id') = `protocol_id`
    AND JSON_TYPE(JSON_EXTRACT(`content_snapshot`, '$.protocol.content_revision')) = 'INTEGER'
    AND JSON_EXTRACT(`content_snapshot`, '$.protocol.content_revision') = `content_revision`, 0)),
  CONSTRAINT `chk_treatment_event_hash` CHECK (`content_hash` IS NULL OR `content_hash` REGEXP '^[0-9a-f]{64}$'),
  CONSTRAINT `chk_treatment_event_reason` CHECK (`event_type` NOT IN ('approved','changes_required','withdrawn','review_reset','source_invalidated') OR (`notes` IS NOT NULL AND CHAR_LENGTH(TRIM(`notes`)) > 0)),
  CONSTRAINT `chk_treatment_event_approval` CHECK (`event_type` <> 'approved' OR (`actor_id` IS NOT NULL AND `independence_confirmed` = 1 AND `review_due_at` IS NOT NULL AND `review_due_at` > `occurred_at`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `treatment_protocols` ADD CONSTRAINT `fk_treatment_approved_revision`
FOREIGN KEY (`id`,`content_revision`,`approved_review_id`) REFERENCES `treatment_protocol_events` (`protocol_id`,`content_revision`,`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

DROP TABLE IF EXISTS `treatment_editorial_permissions`;
CREATE TABLE `treatment_editorial_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `treatment_system_id` int(11) NOT NULL,
  `can_edit` tinyint unsigned NOT NULL DEFAULT 0,
  `can_review` tinyint unsigned NOT NULL DEFAULT 0,
  `can_publish` tinyint unsigned NOT NULL DEFAULT 0,
  `qualification_scope` text DEFAULT NULL,
  `authorized_by` int(11) NOT NULL,
  `granted_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `revoked_by` int(11) DEFAULT NULL,
  `revoked_at` datetime(6) DEFAULT NULL,
  `revocation_reason` text DEFAULT NULL,
  `active_slot` tinyint unsigned GENERATED ALWAYS AS (CASE WHEN `revoked_at` IS NULL THEN 1 ELSE NULL END) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_treatment_active_grant` (`user_id`,`treatment_system_id`,`active_slot`),
  KEY `idx_treatment_grant_system` (`treatment_system_id`,`user_id`),
  KEY `idx_treatment_grant_authorizer` (`authorized_by`),
  KEY `idx_treatment_grant_revoker` (`revoked_by`),
  CONSTRAINT `fk_treatment_grant_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_treatment_grant_system` FOREIGN KEY (`treatment_system_id`) REFERENCES `treatment_systems` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_treatment_grant_authorizer` FOREIGN KEY (`authorized_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_treatment_grant_revoker` FOREIGN KEY (`revoked_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_treatment_grant_capabilities` CHECK (`can_edit` IN (0,1) AND `can_review` IN (0,1) AND `can_publish` IN (0,1) AND (`can_edit` = 1 OR `can_review` = 1 OR `can_publish` = 1)),
  CONSTRAINT `chk_treatment_grant_qualification` CHECK (`can_review` = 0 OR (`qualification_scope` IS NOT NULL AND CHAR_LENGTH(TRIM(`qualification_scope`)) > 0)),
  CONSTRAINT `chk_treatment_grant_revocation` CHECK ((`revoked_by` IS NULL AND `revoked_at` IS NULL AND `revocation_reason` IS NULL) OR (`revoked_by` IS NOT NULL AND `revoked_at` IS NOT NULL AND `revocation_reason` IS NOT NULL AND CHAR_LENGTH(TRIM(`revocation_reason`)) > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Table structure for table `user_auth_identities` */

DROP TABLE IF EXISTS `user_auth_identities`;

CREATE TABLE `user_auth_identities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `provider` varchar(30) NOT NULL,
  `provider_user_id` varchar(255) NOT NULL,
  `provider_email` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_auth_identity_provider_user` (`provider`,`provider_user_id`),
  KEY `idx_auth_identity_user` (`user_id`),
  KEY `idx_auth_identity_provider_email` (`provider`,`provider_email`),
  CONSTRAINT `fk_auth_identity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `user_auth_tokens` */

DROP TABLE IF EXISTS `user_auth_tokens`;

CREATE TABLE `user_auth_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `type` enum('email_verification','password_reset') NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_auth_token_hash` (`token_hash`),
  KEY `idx_user_auth_tokens_user` (`user_id`),
  KEY `idx_user_auth_tokens_lookup` (`type`,`expires_at`),
  CONSTRAINT `fk_user_auth_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `user_saved_diseases` */

DROP TABLE IF EXISTS `user_saved_diseases`;

CREATE TABLE `user_saved_diseases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `disease_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_disease` (`user_id`,`disease_id`),
  KEY `idx_saved_disease_user` (`user_id`),
  KEY `idx_saved_disease_disease` (`disease_id`),
  CONSTRAINT `user_saved_diseases_disease_fk` FOREIGN KEY (`disease_id`) REFERENCES `diseases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_saved_diseases_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `user_treatment_preferences` */

DROP TABLE IF EXISTS `user_treatment_preferences`;

CREATE TABLE `user_treatment_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `treatment_system_id` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_treatment_system` (`user_id`,`treatment_system_id`),
  UNIQUE KEY `uq_user_treatment_order` (`user_id`,`sort_order`),
  KEY `idx_user_treatment_user` (`user_id`),
  KEY `idx_user_treatment_system` (`treatment_system_id`),
  CONSTRAINT `fk_user_treatment_system` FOREIGN KEY (`treatment_system_id`) REFERENCES `treatment_systems` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_treatment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `users` */

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `password_set_at` datetime DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `email_verified_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `mobile_number` varchar(16) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_email` (`email`),
  KEY `idx_user_status` (`status`),
  CONSTRAINT `chk_user_mobile_number` CHECK (`mobile_number` IS NULL OR `mobile_number` REGEXP '^[+][1-9][0-9]{1,14}$')
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Medicine Phase 3.4 additive foundation; aggregate and lifecycle rules require services. */

/*Table structure for table `kobi_medicine_chemical_refs` */

DROP TABLE IF EXISTS `kobi_medicine_chemical_refs`;

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

/*Table structure for table `kobi_medicine_identities` */

DROP TABLE IF EXISTS `kobi_medicine_identities`;

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

/*Table structure for table `kobi_medicine_ingredients` */

DROP TABLE IF EXISTS `kobi_medicine_ingredients`;

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

/*Table structure for table `kobi_medicine_presentations` */

DROP TABLE IF EXISTS `kobi_medicine_presentations`;

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

/*Table structure for table `kobi_medicine_strengths` */

DROP TABLE IF EXISTS `kobi_medicine_strengths`;

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

/*Table structure for table `health_medicine_identity_links` */

DROP TABLE IF EXISTS `health_medicine_identity_links`;

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

/*Table structure for table `health_medicine_selected_ingredients` */

DROP TABLE IF EXISTS `health_medicine_selected_ingredients`;

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


/*Table structure for table `kobi_substance_search` */

DROP TABLE IF EXISTS `kobi_substance_search`;

/*!50001 DROP VIEW IF EXISTS `kobi_substance_search` */;
/*!50001 DROP TABLE IF EXISTS `kobi_substance_search` */;

/*!50001 CREATE TABLE  `kobi_substance_search`(
 `entity_id` bigint(20) unsigned ,
 `alias_id` decimal(20,0) ,
 `display_name` varchar(255) ,
 `search_name` varchar(255) ,
 `match_type` varchar(9) ,
 `review_status` varchar(30) 
)*/;

/*View structure for view kobi_substance_search */

/*!50001 DROP TABLE IF EXISTS `kobi_substance_search` */;
/*!50001 DROP VIEW IF EXISTS `kobi_substance_search` */;

/*!50001 CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `kobi_substance_search` AS select `s`.`substance_id` AS `entity_id`,NULL AS `alias_id`,`s`.`canonical_name` AS `display_name`,`s`.`canonical_name_normalized` AS `search_name`,'CANONICAL' AS `match_type`,`s`.`identity_status` AS `review_status` from `kobi_substances` `s` where `s`.`is_active` = 1 and `s`.`is_searchable` = 1 union all select `a`.`substance_id` AS `entity_id`,`a`.`alias_id` AS `alias_id`,`a`.`alias_name` AS `display_name`,`a`.`alias_name_normalized` AS `search_name`,'ALIAS' AS `match_type`,`a`.`review_status` AS `review_status` from `kobi_substance_aliases` `a` where `a`.`is_active` = 1 and `a`.`is_searchable` = 1 and `a`.`substance_id` is not null */;

/*Medicine Phase 3.8: authority, immutable decisions and explicit registry assessment. No seeds. */
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
 `expires_at` datetime(6) DEFAULT NULL,
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
 CONSTRAINT `chk_medicine_grant_active` CHECK (is_active IN (0,1) AND ((expires_at IS NOT NULL AND expires_at > granted_at) OR (expires_at IS NULL AND BINARY capability=BINARY 'administrator' AND BINARY scope=BINARY 'catalog' AND user_id=granted_by AND qualification_reference IS NULL AND qualification_assessed_by IS NULL AND qualification_scope IS NULL))),
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
CREATE TRIGGER `medicine_events_no_delete` BEFORE DELETE ON `medicine_governance_events` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine history is immutable';
END$$
DELIMITER ;

/*Medicine Phase 3.10: durable registry activity history; existing registry definitions untouched. */
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
CREATE TRIGGER `medicine_form_activity_history` AFTER UPDATE ON `kobi_substance_forms` FOR EACH ROW
BEGIN
 IF NOT (OLD.is_active <=> NEW.is_active) THEN
  INSERT INTO medicine_registry_lifecycle_events(form_id,old_active,new_active,source,occurred_at)
  VALUES(NEW.form_id,OLD.is_active,NEW.is_active,'registry_trigger',UTC_TIMESTAMP(6));
 END IF;
END$$
CREATE TRIGGER `medicine_lifecycle_no_update` BEFORE UPDATE ON `medicine_registry_lifecycle_events` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine lifecycle history is immutable';
END$$
CREATE TRIGGER `medicine_lifecycle_no_delete` BEFORE DELETE ON `medicine_registry_lifecycle_events` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine lifecycle history is immutable';
END$$
DELIMITER ;

/*Medicine Phase 3.12: explicit internal pilot enrollment; no registry or private data changes. */
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
CREATE TRIGGER `medicine_pilot_no_delete` BEFORE DELETE ON `medicine_pilot_records` FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Medicine pilot enrollment is immutable';
END$$
DELIMITER ;

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

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
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


/* Step 44B: user-owned historical symptom checks. */
CREATE TABLE `user_saved_symptom_checks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `check_key` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `checked_at` datetime NOT NULL,
  `saved_at` datetime NOT NULL,
  `selected_symptom_count` smallint unsigned NOT NULL,
  `displayed_result_count` tinyint unsigned NOT NULL,
  `snapshot_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_saved_check_user_key` (`user_id`,`check_key`),
  KEY `idx_saved_check_user_time` (`user_id`,`checked_at`,`id`),
  CONSTRAINT `fk_saved_check_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_saved_check_counts` CHECK (`selected_symptom_count` BETWEEN 1 AND 50 AND `displayed_result_count` BETWEEN 0 AND 10),
  CONSTRAINT `chk_saved_check_json` CHECK (JSON_VALID(`snapshot_json`) AND OCTET_LENGTH(`snapshot_json`) <= 262144)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


/* Step 45B: Personal Medication daily schedules and manual checkoffs. */
CREATE TABLE health_medicine_schedule_slots (
    id int(11) NOT NULL AUTO_INCREMENT,
    health_medicine_id int(11) NOT NULL,
    local_time char(5) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    timezone varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    starts_on date NOT NULL,
    ends_on date DEFAULT NULL,
    is_active tinyint(1) NOT NULL DEFAULT 1,
    created_by int(11) DEFAULT NULL,
    created_at datetime NOT NULL,
    updated_at datetime NOT NULL,
    PRIMARY KEY (id),
    KEY idx_medicine_schedule_active (health_medicine_id, is_active),
    KEY idx_medicine_schedule_creator (created_by),
    CONSTRAINT fk_medicine_schedule_record FOREIGN KEY (health_medicine_id)
        REFERENCES health_medicines (id) ON DELETE CASCADE,
    CONSTRAINT fk_medicine_schedule_creator FOREIGN KEY (created_by)
        REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT chk_medicine_schedule_time CHECK
        (local_time REGEXP '^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$'),
    CONSTRAINT chk_medicine_schedule_timezone CHECK (CHAR_LENGTH(timezone) > 0),
    CONSTRAINT chk_medicine_schedule_active CHECK (is_active IN (0, 1)),
    CONSTRAINT chk_medicine_schedule_dates CHECK
        (ends_on IS NULL OR ends_on >= starts_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE health_medicine_schedule_checkoffs (
    id int(11) NOT NULL AUTO_INCREMENT,
    schedule_id int(11) NOT NULL,
    scheduled_on date NOT NULL,
    state varchar(7) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    medicine_label varchar(255) NOT NULL,
    recorded_by int(11) DEFAULT NULL,
    recorded_at datetime NOT NULL,
    created_at datetime NOT NULL,
    updated_at datetime NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_medicine_checkoff_occurrence (schedule_id, scheduled_on),
    KEY idx_medicine_checkoff_actor (recorded_by),
    CONSTRAINT fk_medicine_checkoff_schedule FOREIGN KEY (schedule_id)
        REFERENCES health_medicine_schedule_slots (id) ON DELETE CASCADE,
    CONSTRAINT fk_medicine_checkoff_actor FOREIGN KEY (recorded_by)
        REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT chk_medicine_checkoff_state CHECK
        (BINARY state IN (BINARY 'taken', BINARY 'skipped'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- Step 50B: two additive tables and one API-only revocation trigger.
-- No SQL-mode directives. Apply only after an authorized verified recovery backup.
CREATE TABLE user_api_sessions (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    public_id varchar(33) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    user_id int(11) NOT NULL,
    device_label varchar(81) DEFAULT NULL,
    created_at datetime NOT NULL,
    last_refreshed_at datetime NOT NULL,
    absolute_expires_at datetime NOT NULL,
    revoked_at datetime DEFAULT NULL,
    revocation_reason varchar(32) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
    credential_stamp varbinary(33) NOT NULL,
    access_hash varbinary(33) NOT NULL,
    access_expires_at datetime NOT NULL,
    refresh_generation int unsigned NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_api_session_public (public_id),
    UNIQUE KEY uq_api_session_access (access_hash),
    KEY idx_api_session_user_state (user_id,revoked_at,absolute_expires_at),
    KEY idx_api_session_expiry (absolute_expires_at),
    KEY idx_api_session_revoked (revoked_at),
    CONSTRAINT fk_api_session_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_api_session_public CHECK (OCTET_LENGTH(public_id)=32 AND public_id REGEXP '^[0-9a-f]{32}$'),
    CONSTRAINT chk_api_session_label CHECK (device_label IS NULL OR CHAR_LENGTH(device_label)<=80),
    CONSTRAINT chk_api_session_hashes CHECK (OCTET_LENGTH(credential_stamp)=32 AND OCTET_LENGTH(access_hash)=32),
    CONSTRAINT chk_api_session_generation CHECK (refresh_generation>=1),
    CONSTRAINT chk_api_session_times CHECK (created_at>'2000-01-01' AND absolute_expires_at>created_at AND last_refreshed_at>=created_at AND last_refreshed_at<absolute_expires_at AND access_expires_at>last_refreshed_at AND access_expires_at<=absolute_expires_at),
    CONSTRAINT chk_api_session_revocation CHECK ((revoked_at IS NULL AND revocation_reason IS NULL) OR (revoked_at IS NOT NULL AND revoked_at>=created_at AND revocation_reason IS NOT NULL AND BINARY revocation_reason IN (BINARY 'logout',BINARY 'logout_all',BINARY 'refresh_reuse',BINARY 'device_limit',BINARY 'account_change')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_api_refresh_tokens (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    api_session_id bigint unsigned NOT NULL,
    generation int unsigned NOT NULL,
    token_hash varbinary(33) NOT NULL,
    issued_at datetime NOT NULL,
    expires_at datetime NOT NULL,
    consumed_at datetime DEFAULT NULL,
    active_slot tinyint GENERATED ALWAYS AS (CASE WHEN consumed_at IS NULL THEN 1 ELSE NULL END) PERSISTENT,
    PRIMARY KEY (id),
    UNIQUE KEY uq_api_refresh_hash (token_hash),
    UNIQUE KEY uq_api_refresh_generation (api_session_id,generation),
    UNIQUE KEY uq_api_refresh_active (api_session_id,active_slot),
    KEY idx_api_refresh_expiry (expires_at),
    CONSTRAINT fk_api_refresh_session FOREIGN KEY (api_session_id) REFERENCES user_api_sessions(id) ON DELETE CASCADE,
    CONSTRAINT chk_api_refresh_hash CHECK (OCTET_LENGTH(token_hash)=32),
    CONSTRAINT chk_api_refresh_generation CHECK (generation>=1),
    CONSTRAINT chk_api_refresh_times CHECK (issued_at>'2000-01-01' AND expires_at>issued_at AND (consumed_at IS NULL OR consumed_at>=issued_at))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$
CREATE TRIGGER trg_users_api_revoke AFTER UPDATE ON users FOR EACH ROW
BEGIN
    IF NOT (BINARY OLD.password_hash <=> BINARY NEW.password_hash)
       OR (BINARY OLD.status = BINARY 'active' AND BINARY NEW.status <> BINARY 'active')
       OR (OLD.email_verified_at IS NOT NULL AND NEW.email_verified_at IS NULL) THEN
        UPDATE user_api_sessions SET revoked_at=UTC_TIMESTAMP(),revocation_reason='account_change'
        WHERE user_id=NEW.id AND revoked_at IS NULL;
    END IF;
END$$
DELIMITER ;
