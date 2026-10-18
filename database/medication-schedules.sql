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

