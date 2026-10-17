-- Clubadmin schema (MySQL 8, utf8mb4)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS member_type (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS person (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kind ENUM('natural','legal') NOT NULL DEFAULT 'natural',
  first_name VARCHAR(100) NULL,
  last_name VARCHAR(100) NULL,
  org_name VARCHAR(200) NULL,
  contact_person VARCHAR(200) NULL,
  email VARCHAR(190) NULL,
  birthdate DATE NULL,
  street VARCHAR(150) NULL,
  street_no VARCHAR(20) NULL,
  zip VARCHAR(10) NULL,
  city VARCHAR(100) NULL,
  ahv_number VARCHAR(16) NULL,
  js_number VARCHAR(30) NULL,
  member_type_id INT UNSIGNED NULL,
  photo_consent TINYINT(1) NOT NULL DEFAULT 0,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (last_name, first_name),
  INDEX (org_name),
  CONSTRAINT fk_person_mt FOREIGN KEY (member_type_id) REFERENCES member_type(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS person_relation (  -- Eltern <-> Kind
  parent_id INT UNSIGNED NOT NULL,
  child_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (parent_id, child_id),
  FOREIGN KEY (parent_id) REFERENCES person(id) ON DELETE CASCADE,
  FOREIGN KEY (child_id) REFERENCES person(id) ON DELETE CASCADE,
  CHECK (parent_id <> child_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS app_user (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  person_id INT UNSIGNED NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (person_id) REFERENCES person(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_role (
  user_id INT UNSIGNED NOT NULL,
  role ENUM('admin','office','trainer','communication','member') NOT NULL,
  PRIMARY KEY (user_id, role),
  FOREIGN KEY (user_id) REFERENCES app_user(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS grp (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  kind ENUM('team','functionary','other') NOT NULL DEFAULT 'other'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS group_group (  -- Gruppe enthält Gruppe (n:n)
  parent_id INT UNSIGNED NOT NULL,
  child_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (parent_id, child_id),
  FOREIGN KEY (parent_id) REFERENCES grp(id) ON DELETE CASCADE,
  FOREIGN KEY (child_id) REFERENCES grp(id) ON DELETE CASCADE,
  CHECK (parent_id <> child_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS role (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL UNIQUE,
  is_trainer TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS group_member (  -- mehrfach pro Person/Gruppe, mit Start/Ende
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  group_id INT UNSIGNED NOT NULL,
  person_id INT UNSIGNED NOT NULL,
  role_id INT UNSIGNED NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NULL,
  INDEX (group_id), INDEX (person_id),
  FOREIGN KEY (group_id) REFERENCES grp(id) ON DELETE CASCADE,
  FOREIGN KEY (person_id) REFERENCES person(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES role(id),
  CHECK (end_date IS NULL OR end_date >= start_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS event (  -- Training / Spiel eines Teams
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  group_id INT UNSIGNED NOT NULL,
  kind ENUM('training','game') NOT NULL DEFAULT 'training',
  title VARCHAR(200) NOT NULL,
  location VARCHAR(200) NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NULL,
  notes TEXT NULL,
  deadline DATETIME NULL,
  INDEX (group_id, starts_at),
  FOREIGN KEY (group_id) REFERENCES grp(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS attendance (
  event_id INT UNSIGNED NOT NULL,
  person_id INT UNSIGNED NOT NULL,
  status ENUM('yes','no') NOT NULL,
  comment VARCHAR(500) NULL,
  updated_by INT UNSIGNED NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (event_id, person_id),
  FOREIGN KEY (event_id) REFERENCES event(id) ON DELETE CASCADE,
  FOREIGN KEY (person_id) REFERENCES person(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS helper_event (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  event_date DATE NOT NULL,
  location VARCHAR(200) NULL,
  notes TEXT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS helper_shift (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  helper_event_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,         -- z.B. "Kasse 1. Schicht"
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  slots INT UNSIGNED NOT NULL DEFAULT 1,
  FOREIGN KEY (helper_event_id) REFERENCES helper_event(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS helper_assignment (
  shift_id INT UNSIGNED NOT NULL,
  person_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (shift_id, person_id),
  FOREIGN KEY (shift_id) REFERENCES helper_shift(id) ON DELETE CASCADE,
  FOREIGN KEY (person_id) REFERENCES person(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS mail_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sent_by INT UNSIGNED NULL,
  group_id INT UNSIGNED NULL,
  subject VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  recipients INT UNSIGNED NOT NULL,
  sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO member_type (name) VALUES ('Aktiv'),('Passiv'),('Elternteil'),('Ehrenmitglied');
INSERT IGNORE INTO role (name, is_trainer) VALUES ('Mitglied',0),('Trainer',1),('Förderkader',0),('Materialverantwortlicher',0);
