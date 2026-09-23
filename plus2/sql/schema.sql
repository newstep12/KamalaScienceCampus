-- Shree Kamala Secondary School — +2 Science portal schema
--
-- This is a separate database from the Kamala Science Campus portal's. The two
-- portals share no tables, no accounts and no sign-in: a student of one cannot
-- sign in to the other, and nothing done here can touch the campus's records.
--
-- Charset is utf8mb4 throughout so Devanagari stores and sorts correctly.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name       VARCHAR(120)  NOT NULL,
  full_name_ne    VARCHAR(120)  NULL,
  email           VARCHAR(190)  NOT NULL,
  password_hash   VARCHAR(255)  NOT NULL,
  role            ENUM('student','teacher','admin') NOT NULL DEFAULT 'student',
  status          ENUM('pending','active','rejected','suspended') NOT NULL DEFAULT 'pending',
  -- Students only. 11 or 12; the school office moves a class 11 student up to
  -- class 12 from Admin → People at the start of the next session.
  class_level     TINYINT UNSIGNED NULL,
  section         VARCHAR(10)   NULL,            -- A, B … where the school runs more than one
  roll_no         VARCHAR(20)   NULL,            -- the school's own roll number
  -- The +2 Science group: 'biology' or 'computer' (Computer Science). Chosen
  -- by the student at registration, changeable from their portfolio.
  study_group     VARCHAR(20)   NULL,
  -- The student's permanent number, given in order as the office approves
  -- them: the n in KSSD-XI-n. It never changes, so a student moved up from
  -- class 11 to class 12 keeps it and only the class in front changes.
  student_no      INT UNSIGNED  NULL,
  neb_reg_no      VARCHAR(40)   NULL,            -- NEB registration number, once issued
  guardian_name   VARCHAR(120)  NULL,            -- father's, mother's or guardian's name
  guardian_phone  VARCHAR(30)   NULL,            -- the number to ring about this student
  phone           VARCHAR(30)   NULL,
  address         VARCHAR(190)  NULL,
  date_of_birth   DATE          NULL,
  blood_group     VARCHAR(8)    NULL,            -- A+, O-, and the rest; printed on the card's back
  -- Staff cards only: the two government numbers.
  national_id     VARCHAR(30)   NULL,
  pan_no          VARCHAR(20)   NULL,
  designation     VARCHAR(80)   NULL,            -- staff title printed on the ID card
  bio             TEXT          NULL,
  avatar_path     VARCHAR(255)  NULL,
  -- The photograph as it was uploaded, scaled down and printed on nothing,
  -- kept so the crop can be moved afterwards.
  avatar_source_path VARCHAR(255) NULL,
  -- Where the frame sits on it: 0 keeps the top of the photograph, 100 the
  -- bottom. NULL means the crop's own default.
  avatar_focus    TINYINT UNSIGNED NULL,
  -- The holder's own signature, printed on the back of their card.
  signature_path  VARCHAR(255)  NULL,
  rejection_note  VARCHAR(255)  NULL,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  approved_at     DATETIME      NULL,
  approved_by     INT UNSIGNED  NULL,
  last_login_at   DATETIME      NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_student_no (student_no),
  KEY idx_users_status_role (status, role),
  KEY idx_users_class (class_level, section)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Throttles brute-force login attempts. Rows older than a day are pruned on write.
CREATE TABLE IF NOT EXISTS login_attempts (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email        VARCHAR(190) NOT NULL,
  ip_address   VARCHAR(45)  NOT NULL,
  succeeded    TINYINT(1)   NOT NULL DEFAULT 0,
  attempted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_attempts (email, ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit trail for approvals and other admin actions.
CREATE TABLE IF NOT EXISTS activity_log (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  actor_id   INT UNSIGNED NULL,
  action     VARCHAR(60)  NOT NULL,
  subject    VARCHAR(190) NULL,
  detail     VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_log_time (created_at),
  CONSTRAINT fk_log_actor FOREIGN KEY (actor_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  k VARCHAR(64)  NOT NULL,
  v TEXT         NULL,
  PRIMARY KEY (k)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The signature library: the Principal's signature, held so that putting it
-- on a card is something an administrator does deliberately.
--
-- release_scope:
--   locked   — held, applied to nothing. Every signature starts here.
--   admin    — prints on a card an administrator prints; a student printing
--              their own card gets a blank line for the office to sign.
--   everyone — prints on every card, including one a student prints.
CREATE TABLE IF NOT EXISTS signatures (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  label         VARCHAR(120)  NOT NULL,          -- "Black ink", "Blue ink"
  owner_name    VARCHAR(120)  NOT NULL,
  owner_name_ne VARCHAR(120)  NULL,
  owner_title   VARCHAR(80)   NOT NULL DEFAULT 'principal',
  file_path     VARCHAR(255)  NOT NULL,
  release_scope ENUM('locked','admin','everyone') NOT NULL DEFAULT 'locked',
  note          VARCHAR(255)  NULL,
  uploaded_by   INT UNSIGNED  NULL,
  created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  released_at   DATETIME      NULL,
  released_by   INT UNSIGNED  NULL,
  PRIMARY KEY (id),
  KEY idx_sig_scope (release_scope),
  CONSTRAINT fk_sig_uploader FOREIGN KEY (uploaded_by) REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT fk_sig_releaser FOREIGN KEY (released_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
