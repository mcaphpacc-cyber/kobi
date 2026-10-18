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
