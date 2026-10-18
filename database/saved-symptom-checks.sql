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
