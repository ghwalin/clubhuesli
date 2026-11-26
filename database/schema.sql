-- Clubadmin schema, sprint 1 (MySQL 8, utf8mb4)
-- Scope: persons (natural / team member / legal), addresses, guardianships,
--        seasons, groups with their hierarchy and group memberships
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

-- ---------------------------------------------------------------------------
-- Season: a membership always refers to exactly one season.
-- Names are unique; non-overlapping date ranges are checked by the application.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS season
(
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(20) NOT NULL UNIQUE COMMENT 'e.g. 2026/27',
    start_date DATE        NOT NULL,
    end_date   DATE        NOT NULL,
    created_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_season_dates CHECK (end_date >= start_date)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------------
-- Group (`group` is a reserved word in SQL). Groups are never deleted, only
-- set to INACTIVE.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS club_group
(
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)                            NOT NULL UNIQUE,
    group_type ENUM ('TEAM','FUNCTIONARY','OTHER')     NOT NULL,
    status     ENUM ('ACTIVE','INACTIVE')              NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP                               NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP                               NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------------
-- Group hierarchy: n:n between parent and child groups. The application
-- guarantees that the hierarchy has no cycles.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS group_hierarchy
(
    parent_id INT UNSIGNED NOT NULL,
    child_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (parent_id, child_id),
    CONSTRAINT fk_group_hierarchy_parent FOREIGN KEY (parent_id) REFERENCES club_group (id) ON DELETE RESTRICT,
    CONSTRAINT fk_group_hierarchy_child FOREIGN KEY (child_id) REFERENCES club_group (id) ON DELETE RESTRICT,
    CONSTRAINT chk_group_hierarchy_not_self CHECK (parent_id <> child_id)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------------
-- Membership: person (natural or legal) in a group for one season.
-- `role` is PLAYER or COACH in teams and MEMBER in all other groups (never
-- NULL, so that the unique key also applies outside of teams).
-- Deleting a person removes its memberships; seasons with memberships cannot
-- be deleted.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS membership
(
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id     INT UNSIGNED                         NOT NULL,
    person_id    INT UNSIGNED                         NOT NULL,
    season_id    INT UNSIGNED                         NOT NULL,
    role         ENUM ('PLAYER','COACH','MEMBER')     NOT NULL DEFAULT 'MEMBER',
    `function`   VARCHAR(100) NULL COMMENT 'free text, e.g. head coach',
    shirt_number SMALLINT UNSIGNED NULL COMMENT 'players in teams only; uniqueness is not checked',
    exit_date    DATE NULL COMMENT 'exit during the season; the membership is kept',
    created_at   TIMESTAMP                            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP                            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_membership (group_id, person_id, season_id, role),
    INDEX idx_membership_person (person_id),
    INDEX idx_membership_season (season_id),
    CONSTRAINT fk_membership_group FOREIGN KEY (group_id) REFERENCES club_group (id) ON DELETE RESTRICT,
    CONSTRAINT fk_membership_person FOREIGN KEY (person_id) REFERENCES person (id) ON DELETE CASCADE,
    CONSTRAINT fk_membership_season FOREIGN KEY (season_id) REFERENCES season (id) ON DELETE RESTRICT
) ENGINE = InnoDB;
