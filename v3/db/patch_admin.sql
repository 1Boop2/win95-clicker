-- Run this if вы уже установили v1/v2 и хотите обновить БД под админку/динамические ачивки
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) NOT NULL DEFAULT 0;
CREATE TABLE IF NOT EXISTS ach_defs (
  code VARCHAR(32) PRIMARY KEY,
  name VARCHAR(64) NOT NULL,
  description TEXT,
  icon VARCHAR(16) DEFAULT '🏅',
  type ENUM('stat','admin') NOT NULL DEFAULT 'stat',
  field VARCHAR(32) NULL,
  gte DECIMAL(14,4) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
