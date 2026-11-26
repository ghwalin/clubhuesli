-- Demo data for sprint 1 (optional): mysql -u clubhuesli -p clubhuesli < database/seed_demo.sql
-- Creates a minor player with her guardian, an adult coach, a passive member
-- and a sponsor, plus seasons and memberships. Load database/seed_groups.sql first.
-- The birth dates and seasons are relative to the import date.
SET NAMES utf8mb4;
USE clubhuesli;

START TRANSACTION;

-- Guardian (parent)
INSERT INTO person (person_type, email, phone, street, house_number, zip, city, country)
VALUES ('NATURAL', 'familie.muster@example.ch', '+41 79 876 54 32', 'Dorfstrasse', '12a', '8000', 'Zürich', 'CH');
SET @guardian = LAST_INSERT_ID();
INSERT INTO natural_person (person_id, first_name, last_name, birth_date)
VALUES (@guardian, 'Peter', 'Muster', '1982-03-14');

-- Minor player without her own e-mail address: group e-mails go to the guardian
INSERT INTO person (person_type, street, house_number, zip, city, country, note)
VALUES ('NATURAL', 'Dorfstrasse', '12a', '8000', 'Zürich', 'CH', 'Allergie: Nüsse');
SET @player = LAST_INSERT_ID();
INSERT INTO natural_person (person_id, first_name, last_name, birth_date)
VALUES (@player, 'Anna', 'Muster', DATE_FORMAT(CURDATE() - INTERVAL 14 YEAR, '%Y-%m-%d'));
INSERT INTO team_member (person_id, ahv_number, js_number, photo_consent)
VALUES (@player, '756.1234.5678.97', NULL, 1);
INSERT INTO guardianship (child_id, guardian_id) VALUES (@player, @guardian);

-- Team member of full age, assigned as a coach in one team and as a player in another
INSERT INTO person (person_type, email, phone, street, house_number, zip, city, country)
VALUES ('NATURAL', 'lea.meier@example.ch', '+41 79 111 22 33', 'Seestrasse', '7', '8800', 'Thalwil', 'CH');
SET @coach = LAST_INSERT_ID();
INSERT INTO natural_person (person_id, first_name, last_name, birth_date)
VALUES (@coach, 'Lea', 'Meier', DATE_FORMAT(CURDATE() - INTERVAL 27 YEAR, '%Y-%m-%d'));
INSERT INTO team_member (person_id, ahv_number, js_number, photo_consent)
VALUES (@coach, NULL, '1234567', 0);

-- Passive member without a birth date
INSERT INTO person (person_type, email, zip, city, country)
VALUES ('NATURAL', 'rita.brunner@example.ch', '8004', 'Zürich', 'CH');
SET @passive = LAST_INSERT_ID();
INSERT INTO natural_person (person_id, first_name, last_name, birth_date)
VALUES (@passive, 'Rita', 'Brunner', NULL);

-- Sponsor with a contact person
INSERT INTO person (person_type, email, phone, street, house_number, zip, city, country)
VALUES ('LEGAL', 'info@jensen.example', '+41 44 123 45 67', 'Industriestrasse', '4', '8902', 'Urdorf', 'CH');
SET @sponsor = LAST_INSERT_ID();
INSERT INTO legal_person (person_id, name, contact_first_name, contact_last_name, support)
VALUES (@sponsor, 'Jensen AG', 'Beat', 'Keller', 'Trikotsponsoring 2026–2028');

-- Seasons: the one containing today (1 July - 30 June) and the following one
SET @season_start = MAKEDATE(YEAR(CURDATE()) - IF(MONTH(CURDATE()) < 7, 1, 0), 1) + INTERVAL 6 MONTH;
INSERT INTO season (name, start_date, end_date)
VALUES (CONCAT(YEAR(@season_start), '/', RIGHT(YEAR(@season_start) + 1, 2)),
        @season_start, @season_start + INTERVAL 1 YEAR - INTERVAL 1 DAY);
SET @season = LAST_INSERT_ID();
INSERT INTO season (name, start_date, end_date)
VALUES (CONCAT(YEAR(@season_start) + 1, '/', RIGHT(YEAR(@season_start) + 2, 2)),
        @season_start + INTERVAL 1 YEAR, @season_start + INTERVAL 2 YEAR - INTERVAL 1 DAY);

-- Groups: the standard groups of Chilis (database/seed_groups.sql) must be loaded first.
INSERT IGNORE INTO club_group (name, group_type) VALUES ('U18', 'TEAM'), ('U12', 'TEAM');
SET @u18        = (SELECT id FROM club_group WHERE name = 'U18');
SET @u12        = (SELECT id FROM club_group WHERE name = 'U12');
SET @players    = (SELECT id FROM club_group WHERE name = 'Spielerin');
SET @coaches    = (SELECT id FROM club_group WHERE name = 'Coach');
SET @passives   = (SELECT id FROM club_group WHERE name = 'Passivmitglied');
SET @sponsors   = (SELECT id FROM club_group WHERE name = 'Sponsoren');

-- Memberships in the current season (members of a sub-group belong to the parent groups automatically)
INSERT INTO membership (group_id, person_id, season_id, role, `function`, shirt_number)
VALUES (@u18, @player, @season, 'PLAYER', 'Torhüterin', 1),
       (@u18, @coach, @season, 'PLAYER', NULL, 10),
       (@u12, @coach, @season, 'COACH', 'Headcoach', NULL);
INSERT INTO membership (group_id, person_id, season_id, role)
VALUES (@players, @player, @season, 'MEMBER'),
       (@players, @coach, @season, 'MEMBER'),
       (@coaches, @coach, @season, 'MEMBER'),
       (@passives, @passive, @season, 'MEMBER'),
       (@sponsors, @sponsor, @season, 'MEMBER');
UPDATE membership SET `function` = 'Trikotsponsor' WHERE group_id = @sponsors AND person_id = @sponsor;

COMMIT;
