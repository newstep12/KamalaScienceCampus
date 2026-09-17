-- Kamala Science Campus — portal schema
-- Charset is utf8mb4 throughout so Devanagari stores and sorts correctly.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name       VARCHAR(120)  NOT NULL,
  full_name_ne    VARCHAR(120)  NULL,
  email           VARCHAR(190)  NOT NULL,
  password_hash   VARCHAR(255)  NOT NULL,
  role            ENUM('student','lecturer','admin') NOT NULL DEFAULT 'student',
  status          ENUM('pending','active','rejected','suspended') NOT NULL DEFAULT 'pending',
  year_level      TINYINT UNSIGNED NULL,          -- 1..4, students only
  symbol_no       VARCHAR(40)   NULL,             -- TU symbol / registration number
  phone           VARCHAR(30)   NULL,
  address         VARCHAR(190)  NULL,
  date_of_birth   DATE          NULL,
  designation     VARCHAR(80)   NULL,            -- staff title printed on the ID card
  bio             TEXT          NULL,
  avatar_path     VARCHAR(255)  NULL,
  rejection_note  VARCHAR(255)  NULL,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  approved_at     DATETIME      NULL,
  approved_by     INT UNSIGNED  NULL,
  last_login_at   DATETIME      NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_status_role (status, role),
  KEY idx_users_year (year_level),
  -- Registration checks a TU symbol number is not already taken. A plain
  -- index, not a unique one: rows predating the check may already collide,
  -- and MySQL refuses a unique index on a column that does.
  KEY idx_users_symbol (symbol_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS courses (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code            VARCHAR(30)   NOT NULL,
  title_en        VARCHAR(190)  NOT NULL,
  title_ne        VARCHAR(190)  NULL,
  description_en  TEXT          NULL,
  description_ne  TEXT          NULL,
  year_level      TINYINT UNSIGNED NOT NULL,      -- 1..4
  credit_hours    DECIMAL(4,1)  NULL,
  lecturer_id     INT UNSIGNED  NULL,
  is_active       TINYINT(1)    NOT NULL DEFAULT 1,
  created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_courses_code (code),
  KEY idx_courses_year (year_level, is_active),
  KEY idx_courses_lecturer (lecturer_id),
  CONSTRAINT fk_courses_lecturer FOREIGN KEY (lecturer_id)
    REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enrolments (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  course_id   INT UNSIGNED NOT NULL,
  enrolled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_enrolment (user_id, course_id),
  KEY idx_enrolment_course (course_id),
  CONSTRAINT fk_enrol_user   FOREIGN KEY (user_id)   REFERENCES users (id)   ON DELETE CASCADE,
  CONSTRAINT fk_enrol_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Learning materials: an uploaded file, an external link, or a written note.
CREATE TABLE IF NOT EXISTS materials (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_id   INT UNSIGNED NOT NULL,
  kind        ENUM('file','link','note') NOT NULL DEFAULT 'file',
  title       VARCHAR(190) NOT NULL,
  description TEXT NULL,
  body        MEDIUMTEXT NULL,        -- kind = note
  file_path   VARCHAR(255) NULL,      -- kind = file
  file_name   VARCHAR(190) NULL,
  file_size   INT UNSIGNED NULL,
  link_url    VARCHAR(500) NULL,      -- kind = link
  uploaded_by INT UNSIGNED NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_materials_course (course_id, created_at),
  CONSTRAINT fk_materials_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE,
  CONSTRAINT fk_materials_user   FOREIGN KEY (uploaded_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A student's own private notes against a course. Never visible to anyone else.
CREATE TABLE IF NOT EXISTS student_notes (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  course_id  INT UNSIGNED NOT NULL,
  title      VARCHAR(190) NOT NULL,
  body       MEDIUMTEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notes_user_course (user_id, course_id, updated_at),
  CONSTRAINT fk_notes_user   FOREIGN KEY (user_id)   REFERENCES users (id)   ON DELETE CASCADE,
  CONSTRAINT fk_notes_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notices. year_level NULL = shown to every year.
CREATE TABLE IF NOT EXISTS notices (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category     ENUM('tu','exam','campus','ugc','scholarship') NOT NULL DEFAULT 'tu',
  title_en     VARCHAR(190) NOT NULL,
  title_ne     VARCHAR(190) NULL,
  body_en      MEDIUMTEXT NULL,
  body_ne      MEDIUMTEXT NULL,
  -- Set when the Nepali beside it was produced by portal/inc/translate.php
  -- rather than typed by a person. It keeps an admin's own wording from being
  -- overwritten, and lets the page say plainly that a translation is machine-made.
  title_ne_auto TINYINT(1) NOT NULL DEFAULT 0,
  body_ne_auto  TINYINT(1) NOT NULL DEFAULT 0,
  source_url   VARCHAR(500) NULL,
  file_path    VARCHAR(255) NULL,
  file_name    VARCHAR(190) NULL,
  year_level   TINYINT UNSIGNED NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  is_pinned    TINYINT(1) NOT NULL DEFAULT 0,
  published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by   INT UNSIGNED NULL,
  PRIMARY KEY (id),
  KEY idx_notices_feed (is_published, published_at),
  KEY idx_notices_year (year_level),
  CONSTRAINT fk_notices_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
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

CREATE TABLE IF NOT EXISTS assessments (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_id       INT UNSIGNED NOT NULL,
  title           VARCHAR(190) NOT NULL,
  kind            ENUM('internal','assignment','practical','terminal','other') NOT NULL DEFAULT 'internal',
  max_marks       DECIMAL(6,2) NOT NULL DEFAULT 100.00,
  weight_percent  DECIMAL(5,2) NULL,
  assessed_on     DATE NULL,
  is_published    TINYINT(1) NOT NULL DEFAULT 0,
  created_by      INT UNSIGNED NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_assess_course (course_id, assessed_on),
  CONSTRAINT fk_assess_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE,
  CONSTRAINT fk_assess_user   FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marks (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  assessment_id  INT UNSIGNED NOT NULL,
  user_id        INT UNSIGNED NOT NULL,
  marks_obtained DECIMAL(6,2) NULL,
  is_absent      TINYINT(1) NOT NULL DEFAULT 0,
  remarks        VARCHAR(255) NULL,
  recorded_by    INT UNSIGNED NULL,
  recorded_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mark (assessment_id, user_id),
  KEY idx_mark_user (user_id),
  CONSTRAINT fk_mark_assess FOREIGN KEY (assessment_id) REFERENCES assessments (id) ON DELETE CASCADE,
  CONSTRAINT fk_mark_user   FOREIGN KEY (user_id)       REFERENCES users (id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendance_sessions (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_id    INT UNSIGNED NOT NULL,
  held_on      DATE NOT NULL,
  topic        VARCHAR(190) NULL,
  created_by   INT UNSIGNED NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_session (course_id, held_on),
  CONSTRAINT fk_sess_course FOREIGN KEY (course_id)  REFERENCES courses (id) ON DELETE CASCADE,
  CONSTRAINT fk_sess_user   FOREIGN KEY (created_by) REFERENCES users (id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendance (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  session_id  INT UNSIGNED NOT NULL,
  user_id     INT UNSIGNED NOT NULL,
  status      ENUM('present','absent','late','excused') NOT NULL DEFAULT 'present',
  PRIMARY KEY (id),
  UNIQUE KEY uq_attendance (session_id, user_id),
  KEY idx_att_user (user_id),
  CONSTRAINT fk_att_session FOREIGN KEY (session_id) REFERENCES attendance_sessions (id) ON DELETE CASCADE,
  CONSTRAINT fk_att_user    FOREIGN KEY (user_id)    REFERENCES users (id)                ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  k VARCHAR(64)  NOT NULL,
  v TEXT         NULL,
  PRIMARY KEY (k)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The signature library. Scans of the signatures the campus issues documents
-- under — the Campus Chief's among them — held in one place so that applying
-- one is an act an administrator performs deliberately rather than a file
-- anybody who can reach the portal may copy.
--
-- release_scope is the whole point of the table:
--   locked   — held, applied to nothing. Every signature starts here.
--   admin    — an administrator may apply it. It prints on a card an
--              administrator prints and on a document they issue; a student
--              printing their own card still gets a blank signature line.
--   everyone — it prints on every card, including one a student prints.
--
-- Which signature is applied where is a pair of settings (signature_id_card,
-- signature_document), so a signature can be uploaded and kept back until the
-- office decides to use it.
CREATE TABLE IF NOT EXISTS signatures (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  label         VARCHAR(120)  NOT NULL,          -- "Black ink", "Blue ink"
  owner_name    VARCHAR(120)  NOT NULL,
  owner_name_ne VARCHAR(120)  NULL,
  owner_title   VARCHAR(80)   NOT NULL DEFAULT 'campus_chief',
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

-- Official documents the campus office issues over a signature: bonafide and
-- character certificates and the rest. The row is the office register — who
-- it was issued to, by whom, under which signature — so that every use of a
-- signature outside an identity card has a record behind it.
CREATE TABLE IF NOT EXISTS documents (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ref_no       VARCHAR(40)  NOT NULL,
  kind         ENUM('bonafide','character','enrolment','recommendation','custom') NOT NULL DEFAULT 'bonafide',
  subject_id   INT UNSIGNED NULL,               -- the account it is about, when there is one
  subject_name VARCHAR(120) NOT NULL,
  title        VARCHAR(190) NULL,               -- kind = custom
  body         MEDIUMTEXT   NULL,               -- kind = custom
  purpose      VARCHAR(190) NULL,
  issued_on    DATE         NOT NULL,
  signature_id INT UNSIGNED NULL,
  issued_by    INT UNSIGNED NULL,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_doc_ref (ref_no),
  KEY idx_doc_issued (issued_on),
  KEY idx_doc_subject (subject_id),
  CONSTRAINT fk_doc_subject FOREIGN KEY (subject_id)   REFERENCES users (id)      ON DELETE SET NULL,
  CONSTRAINT fk_doc_sig     FOREIGN KEY (signature_id) REFERENCES signatures (id) ON DELETE SET NULL,
  CONSTRAINT fk_doc_issuer  FOREIGN KEY (issued_by)    REFERENCES users (id)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
