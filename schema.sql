-- =============================================================
--  MPIN — Interactive Community Mapping & Polling Platform
--  Database schema  ·  MySQL 5.7+ / MariaDB 10.3+  ·  utf8mb4
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- users
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  username      VARCHAR(30)      NOT NULL,
  email         VARCHAR(190)     NOT NULL,
  password_hash VARCHAR(255)     NOT NULL,
  display_name  VARCHAR(60)      NOT NULL,
  bio           VARCHAR(300)     NOT NULL DEFAULT '',
  avatar_color  VARCHAR(7)       NOT NULL DEFAULT '',
  role          ENUM('user','admin') NOT NULL DEFAULT 'user',
  locale        VARCHAR(5)       NOT NULL DEFAULT 'en',
  is_active     TINYINT(1)       NOT NULL DEFAULT 1,
  remember_hash CHAR(64)         DEFAULT NULL,
  last_login    DATETIME         DEFAULT NULL,
  created_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- categories
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug       VARCHAR(40)  NOT NULL,
  name       VARCHAR(60)  NOT NULL,
  icon       VARCHAR(40)  NOT NULL DEFAULT 'tag',
  sort_order INT          NOT NULL DEFAULT 0,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- polls
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS polls (
  id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id             INT UNSIGNED    NOT NULL,
  category_id         INT UNSIGNED    NOT NULL,
  slug                VARCHAR(120)    NOT NULL,
  title               VARCHAR(150)    NOT NULL,
  description         TEXT            NULL,
  cover               VARCHAR(255)    NULL,
  visibility          ENUM('public','private') NOT NULL DEFAULT 'public',
  status              ENUM('draft','open','closed') NOT NULL DEFAULT 'open',
  allow_anonymous     TINYINT(1)      NOT NULL DEFAULT 1,
  allow_multiple      TINYINT(1)      NOT NULL DEFAULT 0,
  start_at            DATETIME        NULL,
  end_at              DATETIME        NULL,
  is_featured         TINYINT(1)      NOT NULL DEFAULT 0,
  is_verified         TINYINT(1)      NOT NULL DEFAULT 0,
  is_demo             TINYINT(1)      NOT NULL DEFAULT 0,
  response_count      INT UNSIGNED    NOT NULL DEFAULT 0,
  created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_polls_slug (slug),
  KEY idx_polls_user (user_id),
  KEY idx_polls_category (category_id),
  KEY idx_polls_status (visibility, status, created_at),
  KEY idx_polls_responses (response_count),
  FULLTEXT KEY ft_polls (title, description),
  CONSTRAINT fk_polls_user      FOREIGN KEY (user_id)     REFERENCES users (id)      ON DELETE CASCADE,
  CONSTRAINT fk_polls_category  FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- poll_questions
--    type: yesno | single | multi | text | number | rating
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS poll_questions (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  poll_id       BIGINT UNSIGNED NOT NULL,
  position      SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  type          ENUM('yesno','single','multi','text','number','rating') NOT NULL,
  question_text VARCHAR(300)    NOT NULL,
  is_required   TINYINT(1)      NOT NULL DEFAULT 1,
  config        VARCHAR(500)    NULL,
  PRIMARY KEY (id),
  KEY idx_questions_poll (poll_id, position),
  CONSTRAINT fk_questions_poll FOREIGN KEY (poll_id) REFERENCES polls (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- poll_options  (choices for single/multi questions)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS poll_options (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  question_id BIGINT UNSIGNED NOT NULL,
  position    SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  option_text VARCHAR(150)    NOT NULL,
  PRIMARY KEY (id),
  KEY idx_options_question (question_id, position),
  CONSTRAINT fk_options_question FOREIGN KEY (question_id) REFERENCES poll_questions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- responses
--   user_id NULL  -> anonymous/guest participation
--   guest_hash    -> cookie fingerprint for de-duplication
--   fp_hash       -> salted browser-fingerprint hash (layer 4 anti-duplicate)
--   ip_hash       -> salted hash, moderation only (never exposed)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS responses (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  poll_id    BIGINT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED    NULL,
  guest_hash CHAR(64)        NULL,
  fp_hash    CHAR(64)        NULL,
  ip_hash    CHAR(64)        NOT NULL,
  country    VARCHAR(80)     NULL,
  created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_responses_poll (poll_id, created_at),
  KEY idx_responses_user (user_id),
  KEY idx_responses_guest (poll_id, guest_hash),
  CONSTRAINT fk_responses_poll FOREIGN KEY (poll_id) REFERENCES polls (id) ON DELETE CASCADE,
  CONSTRAINT fk_responses_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- response_answers
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS response_answers (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  response_id   BIGINT UNSIGNED NOT NULL,
  question_id   BIGINT UNSIGNED NOT NULL,
  option_id     BIGINT UNSIGNED NULL,
  answer_text   VARCHAR(500)    NULL,
  answer_number DECIMAL(14,4)   NULL,
  answer_rating TINYINT UNSIGNED NULL,
  PRIMARY KEY (id),
  KEY idx_answers_response (response_id),
  KEY idx_answers_question (question_id),
  KEY idx_answers_option (option_id),
  CONSTRAINT fk_answers_response FOREIGN KEY (response_id)  REFERENCES responses (id)  ON DELETE CASCADE,
  CONSTRAINT fk_answers_question FOREIGN KEY (question_id)  REFERENCES poll_questions (id) ON DELETE CASCADE,
  CONSTRAINT fk_answers_option   FOREIGN KEY (option_id)    REFERENCES poll_options (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- locations
--   Only PRIVACY-GRID coordinates are ever stored (never exact).
--   lat/lng are already snapped + jittered server-side on insert.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS locations (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  response_id BIGINT UNSIGNED NOT NULL,
  poll_id     BIGINT UNSIGNED NOT NULL,
  lat         DECIMAL(9,6)    NOT NULL,
  lng         DECIMAL(9,6)    NOT NULL,
  country     VARCHAR(80)     NULL,
  region      VARCHAR(120)    NULL,
  city        VARCHAR(120)    NULL,
  created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_locations_poll (poll_id, lat, lng),
  KEY idx_locations_response (response_id),
  CONSTRAINT fk_locations_response FOREIGN KEY (response_id) REFERENCES responses (id) ON DELETE CASCADE,
  CONSTRAINT fk_locations_poll     FOREIGN KEY (poll_id)     REFERENCES polls (id)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- favorites
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS favorites (
  user_id    INT UNSIGNED    NOT NULL,
  poll_id    BIGINT UNSIGNED NOT NULL,
  created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, poll_id),
  CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_fav_poll FOREIGN KEY (poll_id) REFERENCES polls (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- notifications
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED    NOT NULL,
  type       VARCHAR(30)     NOT NULL,
  title      VARCHAR(150)    NOT NULL,
  body       VARCHAR(300)    NOT NULL DEFAULT '',
  link       VARCHAR(255)    NOT NULL DEFAULT '',
  is_read    TINYINT(1)      NOT NULL DEFAULT 0,
  created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notifications_user (user_id, is_read, created_at),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- reports
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reports (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  poll_id          BIGINT UNSIGNED NOT NULL,
  reporter_user_id INT UNSIGNED    NULL,
  reason           ENUM('spam','abuse','fake','privacy','other') NOT NULL,
  details          VARCHAR(500)    NOT NULL DEFAULT '',
  status           ENUM('open','resolved','dismissed') NOT NULL DEFAULT 'open',
  created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_reports_status (status, created_at),
  CONSTRAINT fk_reports_poll     FOREIGN KEY (poll_id)          REFERENCES polls (id) ON DELETE CASCADE,
  CONSTRAINT fk_reports_reporter FOREIGN KEY (reporter_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- rate_limits  (sliding fixed-window counters)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rate_limits (
  action       VARCHAR(40)      NOT NULL,
  ip_hash      CHAR(64)         NOT NULL,
  window_start INT UNSIGNED     NOT NULL,
  hits         INT UNSIGNED     NOT NULL DEFAULT 0,
  PRIMARY KEY (action, ip_hash, window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- audit_logs  (every moderation / management action)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_logs (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED    NULL,
  actor       VARCHAR(30)     NOT NULL DEFAULT 'system',
  action      VARCHAR(60)     NOT NULL,
  target_type VARCHAR(20)     NOT NULL,
  target_id   VARCHAR(30)     NOT NULL DEFAULT '',
  details     VARCHAR(300)    NOT NULL DEFAULT '',
  created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_created (created_at),
  KEY idx_audit_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- settings  (key/value site configuration)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
  setting_key   VARCHAR(60)  NOT NULL,
  setting_value TEXT         NULL,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
