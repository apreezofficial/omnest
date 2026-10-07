CREATE TABLE children (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(60) NOT NULL,
    birth_date DATE NULL,
    age_tier VARCHAR(16) NOT NULL,
    avatar VARCHAR(32) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_children_user (user_id),
    CONSTRAINT fk_children_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A paired child phone. token_hash is the SHA-256 of the per-device token (never the parent's login).
CREATE TABLE devices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    child_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    name VARCHAR(80) NOT NULL,
    model VARCHAR(80) NULL,
    os_version VARCHAR(20) NULL,
    app_version VARCHAR(20) NULL,
    fcm_token VARCHAR(255) NULL,
    fcm_token_updated_at DATETIME NULL,
    last_seen_at DATETIME NULL,
    paired_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    UNIQUE KEY uq_devices_token_hash (token_hash),
    KEY idx_devices_child (child_id),
    CONSTRAINT fk_devices_child FOREIGN KEY (child_id) REFERENCES children (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Short-lived 6-digit pairing codes. code_hash is an HMAC of the code with APP_KEY.
CREATE TABLE pairing_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    child_id BIGINT UNSIGNED NOT NULL,
    code_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    device_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    KEY idx_pairing_codes_hash (code_hash),
    KEY idx_pairing_codes_child (child_id),
    CONSTRAINT fk_pairing_codes_child FOREIGN KEY (child_id) REFERENCES children (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
