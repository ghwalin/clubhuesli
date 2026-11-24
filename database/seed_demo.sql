-- Demo data for sprint 1 (optional): mysql -u clubhuesli -p clubhuesli < database/seed_demo.sql
-- Creates a minor player with her guardian, an adult coach, a passive member
-- and a sponsor. The birth dates are relative to the import date.
SET NAMES utf8mb4;

START TRANSACTION;

-- Guardian (parent)
INSERT INTO person (person_type, member_type, email, phone, street, house_number, zip, city, country)
VALUES ('NATURAL', 'PARENT', 'familie.muster@example.ch', '+41 79 876 54 32', 'Dorfstrasse', '12a', '8000', 'Zürich', 'CH');
SET @guardian = LAST_INSERT_ID();
INSERT INTO natural_person (person_id, first_name, last_name, birth_date)
VALUES (@guardian, 'Peter', 'Muster', '1982-03-14');

-- Minor player without her own e-mail address: group e-mails go to the guardian
INSERT INTO person (person_type, member_type, street, house_number, zip, city, country, note)
VALUES ('NATURAL', 'ACTIVE', 'Dorfstrasse', '12a', '8000', 'Zürich', 'CH', 'Allergie: Nüsse');
SET @player = LAST_INSERT_ID();
INSERT INTO natural_person (person_id, first_name, last_name, birth_date)
VALUES (@player, 'Anna', 'Muster', DATE_FORMAT(CURDATE() - INTERVAL 14 YEAR, '%Y-%m-%d'));
INSERT INTO team_member (person_id, ahv_number, js_number, photo_consent)
VALUES (@player, '756.1234.5678.97', NULL, 1);
INSERT INTO guardianship (child_id, guardian_id) VALUES (@player, @guardian);

-- Team member of full age, assigned as a coach in one team and as a player in another
INSERT INTO person (person_type, member_type, email, phone, street, house_number, zip, city, country)
VALUES ('NATURAL', 'ACTIVE', 'lea.meier@example.ch', '+41 79 111 22 33', 'Seestrasse', '7', '8800', 'Thalwil', 'CH');
SET @coach = LAST_INSERT_ID();
INSERT INTO natural_person (person_id, first_name, last_name, birth_date)
VALUES (@coach, 'Lea', 'Meier', DATE_FORMAT(CURDATE() - INTERVAL 27 YEAR, '%Y-%m-%d'));
INSERT INTO team_member (person_id, ahv_number, js_number, photo_consent)
VALUES (@coach, NULL, '1234567', 0);

-- Passive member without a birth date
INSERT INTO person (person_type, member_type, email, zip, city, country)
VALUES ('NATURAL', 'PASSIVE', 'rita.brunner@example.ch', '8004', 'Zürich', 'CH');
SET @passive = LAST_INSERT_ID();
INSERT INTO natural_person (person_id, first_name, last_name, birth_date)
VALUES (@passive, 'Rita', 'Brunner', NULL);

-- Sponsor with a contact person
INSERT INTO person (person_type, member_type, email, phone, street, house_number, zip, city, country)
VALUES ('LEGAL', 'SPONSOR', 'info@jensen.example', '+41 44 123 45 67', 'Industriestrasse', '4', '8902', 'Urdorf', 'CH');
SET @sponsor = LAST_INSERT_ID();
INSERT INTO legal_person (person_id, name, contact_first_name, contact_last_name, support)
VALUES (@sponsor, 'Jensen AG', 'Beat', 'Keller', 'Trikotsponsoring 2026–2028');

COMMIT;
