-- Child's local timezone, reported by the phone; used to work out "today" for that child.
ALTER TABLE devices ADD COLUMN timezone VARCHAR(64) NULL AFTER app_version;
ALTER TABLE devices ADD COLUMN usage_synced_at DATETIME NULL AFTER last_seen_at;

-- Foreground seconds per app per local day. The phone sends absolute day totals,
-- so re-sending the same day is safe (idempotent).
CREATE TABLE usage_app_daily (
    device_id BIGINT UNSIGNED NOT NULL,
    usage_date DATE NOT NULL,
    package VARCHAR(191) NOT NULL,
    seconds INT UNSIGNED NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (device_id, usage_date, package),
    KEY idx_usage_app_daily_date (usage_date),
    CONSTRAINT fk_usage_app_daily_device FOREIGN KEY (device_id) REFERENCES devices (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-device day total, kept in step with usage_app_daily on every ingest.
CREATE TABLE usage_daily (
    device_id BIGINT UNSIGNED NOT NULL,
    usage_date DATE NOT NULL,
    total_seconds INT UNSIGNED NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (device_id, usage_date),
    KEY idx_usage_daily_date (usage_date),
    CONSTRAINT fk_usage_daily_device FOREIGN KEY (device_id) REFERENCES devices (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Launchable apps on the phone. removed_at is set when an app disappears from the list.
CREATE TABLE installed_apps (
    device_id BIGINT UNSIGNED NOT NULL,
    package VARCHAR(191) NOT NULL,
    label VARCHAR(120) NOT NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    first_seen_at DATETIME NOT NULL,
    removed_at DATETIME NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (device_id, package),
    CONSTRAINT fk_installed_apps_device FOREIGN KEY (device_id) REFERENCES devices (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
