-- Migration fuer bestehende Datenbanken: «Trainer» -> «Coach», «Mitglied» -> «Spielerin»
-- Einmalig ausfuehren (MySQL 8). Neue Installationen brauchen das nicht (schema.sql ist bereits angepasst).
SET NAMES utf8mb4;

-- Benutzerrolle trainer -> coach
ALTER TABLE user_role MODIFY role ENUM('admin','office','trainer','coach','communication','member') NOT NULL;
UPDATE user_role SET role='coach' WHERE role='trainer';
ALTER TABLE user_role MODIFY role ENUM('admin','office','coach','communication','member') NOT NULL;

-- Gruppenrollen
ALTER TABLE role RENAME COLUMN is_trainer TO is_coach;
UPDATE role SET name='Coach' WHERE name='Trainer';
UPDATE role SET name='Spielerin' WHERE name='Mitglied';
