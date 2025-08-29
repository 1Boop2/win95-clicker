-- Schema (run once to create)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stats (
  user_id INT PRIMARY KEY,
  total_clicks BIGINT NOT NULL DEFAULT 0,
  balance BIGINT NOT NULL DEFAULT 0,
  best_cps DECIMAL(10,2) NOT NULL DEFAULT 0,
  last_update_ts DOUBLE NOT NULL DEFAULT 0,
  auto_carry DECIMAL(20,6) NOT NULL DEFAULT 0,
  CONSTRAINT fk_stats_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS upgrades (
  code VARCHAR(32) PRIMARY KEY,
  name VARCHAR(64) NOT NULL,
  description TEXT,
  type ENUM('manual','auto') NOT NULL,
  base_cost INT NOT NULL,
  cost_growth DECIMAL(8,4) NOT NULL,
  base_effect DECIMAL(12,6) NOT NULL,
  effect_growth DECIMAL(8,4) NOT NULL DEFAULT 1.0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_upgrades (
  user_id INT NOT NULL,
  upgrade_code VARCHAR(32) NOT NULL,
  level INT NOT NULL DEFAULT 0,
  PRIMARY KEY (user_id, upgrade_code),
  CONSTRAINT fk_uu_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_uu_up FOREIGN KEY (upgrade_code) REFERENCES upgrades(code) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS clicks_log (
  user_id INT NOT NULL,
  bucket_ms BIGINT NOT NULL,
  count INT NOT NULL DEFAULT 0,
  PRIMARY KEY (user_id, bucket_ms),
  INDEX idx_user_edge (user_id, bucket_ms),
  CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User achievements (owned)
CREATE TABLE IF NOT EXISTS user_achievements (
  user_id INT NOT NULL,
  code VARCHAR(32) NOT NULL,
  unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, code),
  CONSTRAINT fk_ua_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dynamic achievement definitions
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
