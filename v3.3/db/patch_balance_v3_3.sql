-- Economy / upgrades storage
CREATE TABLE IF NOT EXISTS user_upgrades (
  user_id INT NOT NULL,
  code VARCHAR(32) NOT NULL,
  lvl INT NOT NULL DEFAULT 0,
  PRIMARY KEY (user_id, code),
  CONSTRAINT fk_user_upg_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- (опционально) если нет контактов и страниц
CREATE TABLE IF NOT EXISTS pages (
  `key` VARCHAR(64) PRIMARY KEY,
  content MEDIUMTEXT NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- (опционально) таблица ачивок (если у вас другая — игнорируйте)
-- CREATE TABLE IF NOT EXISTS user_achievements (
--   user_id INT NOT NULL,
--   code VARCHAR(32) NOT NULL,
--   got_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--   PRIMARY KEY(user_id, code),
--   FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
