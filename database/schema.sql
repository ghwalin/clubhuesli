-- Clubadmin schema, sprint 1 (MySQL 8, utf8mb4)
-- Scope: persons (natural / team member / legal), addresses, guardianships
--        plus a minimal user table for the login stub (roles admin and office).
-- Inheritance is mapped table-per-type: the common table `person` holds the
-- attributes of AbstractPerson, the specialised tables share its primary key.
-- Run this with an administrative connection, after database/bootstrap.sql:
--
--   mysql -u root -p clubhuesli < database/schema.sql
--
-- The application's database user has only SELECT/INSERT/UPDATE/DELETE, so it
-- cannot create these tables itself.
SET NAMES utf8mb4;
USE clubhuesli;
-- ---------------------------------------------------------------------------
-- AbstractPerson
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS person
(
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    person_type      ENUM ('NATURAL','LEGAL')                                      NOT NULL,
    member_type      ENUM ('ACTIVE','PASSIVE','PARENT','HONORARY',
        'SPONSOR','PATRON','PARTNER','MUNICIPALITY')                               NOT NULL,
    email            VARCHAR(190) NULL COMMENT 'not unique: families share an address',
    phone            VARCHAR(40) NULL,
    street           VARCHAR(150) NULL,
    house_number     VARCHAR(20) NULL,
    address_addition VARCHAR(100) NULL,
    zip              VARCHAR(10) NULL,
    city             VARCHAR(100) NULL,
    country          CHAR(2)                                   NOT NULL DEFAULT 'CH',
    note             TEXT NULL,
    created_at       TIMESTAMP                                 NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP                                 NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_person_email (email),
    INDEX idx_person_type (person_type)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------------
-- NaturalPerson (1:0..1 with person)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS natural_person
(
    person_id  INT UNSIGNED PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name  VARCHAR(50) NOT NULL,
    birth_date DATE NULL COMMENT 'mandatory for team members and helpers',
    INDEX idx_natural_person_name (last_name, first_name),
    CONSTRAINT fk_natural_person_person FOREIGN KEY (person_id) REFERENCES person (id) ON DELETE CASCADE
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------------
-- TeamMember (1:0..1 with natural_person)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS team_member
(
    person_id     INT UNSIGNED PRIMARY KEY,
    ahv_number    VARCHAR(16) NULL COMMENT 'format 756.XXXX.XXXX.XX',
    js_number     VARCHAR(30) NULL,
    photo_consent TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'minors only, default no',
    CONSTRAINT fk_team_member_natural_person FOREIGN KEY (person_id) REFERENCES natural_person (person_id) ON DELETE CASCADE
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------------
-- LegalPerson (1:0..1 with person)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS legal_person
(
    person_id          INT UNSIGNED PRIMARY KEY,
    name               VARCHAR(200) NOT NULL,
    contact_first_name VARCHAR(50)  NOT NULL,
    contact_last_name  VARCHAR(50)  NOT NULL,
    support            TEXT NULL,
    INDEX idx_legal_person_name (name),
    CONSTRAINT fk_legal_person_person FOREIGN KEY (person_id) REFERENCES person (id) ON DELETE CASCADE
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------------
-- Guardianship: n:n between a minor team member (child) and natural persons
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS guardianship
(
    child_id    INT UNSIGNED NOT NULL,
    guardian_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (child_id, guardian_id),
    CONSTRAINT fk_guardianship_child FOREIGN KEY (child_id) REFERENCES team_member (person_id) ON DELETE CASCADE,
    CONSTRAINT fk_guardianship_guardian FOREIGN KEY (guardian_id) REFERENCES natural_person (person_id) ON DELETE CASCADE,
    CONSTRAINT chk_guardianship_not_self CHECK (child_id <> guardian_id)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------------
-- Login stub (sprint 1): e-mail + password, roles admin and office.
-- Replaced later by OAuth2 / passkey authentication with JWT.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS app_user
(
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    person_id     INT UNSIGNED NULL COMMENT 'optional link to the person behind the account',
    email         VARCHAR(190)                 NOT NULL UNIQUE COMMENT 'login e-mail, unique',
    password_hash VARCHAR(255)                 NOT NULL,
    role          ENUM ('admin','office')      NOT NULL,
    active        TINYINT(1)                   NOT NULL DEFAULT 1,
    created_at    TIMESTAMP                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_app_user_person FOREIGN KEY (person_id) REFERENCES person (id) ON DELETE SET NULL
) ENGINE = InnoDB;
