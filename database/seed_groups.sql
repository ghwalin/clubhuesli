-- Standard groups and teams of Chilis (see chilis/groups.md). Optional, repeatable:
--   mysql -u clubhuesli -p clubhuesli < database/seed_groups.sql
-- Existing groups (by name) are kept; the hierarchy is only extended.
-- The Admidio import (bin/import_admidio.php) creates the same structure.
SET NAMES utf8mb4;
USE clubhuesli;
-- Teams
INSERT IGNORE INTO club_group (name, group_type) VALUES
    ('NLB', 'TEAM'), ('1. Liga KF', 'TEAM'), ('U21B', 'TEAM'), ('U17B', 'TEAM'),
    ('Jun B', 'TEAM'), ('Jun C', 'TEAM'), ('Jun D', 'TEAM'), ('Starters', 'TEAM');

-- Groups
INSERT IGNORE INTO club_group (name, group_type) VALUES
    ('Aktivmitglied', 'OTHER'),
    ('Vorstand', 'FUNCTIONARY'),
    ('Funktionärin', 'FUNCTIONARY'),
    ('Spielsekretärin', 'FUNCTIONARY'),
    ('Spielerin', 'OTHER'),
    ('Coach', 'OTHER'),
    ('Ehrenmitglied', 'OTHER'),
    ('Schiedsrichter', 'OTHER'),
    ('Passivmitglied', 'OTHER'),
    ('Freimitglied', 'OTHER'),
    ('200er Club', 'OTHER'),
    ('Alumni', 'OTHER'),
    ('Partner', 'OTHER'),
    ('Sponsoren', 'OTHER'),
    ('Persönliche Sponsoren', 'OTHER'),
    ('Gemeinden', 'OTHER');

-- Hierarchy (parent, child)
INSERT IGNORE INTO group_hierarchy (parent_id, child_id)
SELECT p.id, c.id
FROM (
    SELECT 'Aktivmitglied' AS parent, 'Vorstand' AS child
    UNION ALL SELECT 'Aktivmitglied', 'Funktionärin'
    UNION ALL SELECT 'Funktionärin', 'Spielsekretärin'
    UNION ALL SELECT 'Aktivmitglied', 'Spielerin'
    UNION ALL SELECT 'Aktivmitglied', 'Coach'
    UNION ALL SELECT 'Aktivmitglied', 'Ehrenmitglied'
    UNION ALL SELECT 'Aktivmitglied', 'Schiedsrichter'
    UNION ALL SELECT 'Passivmitglied', 'Freimitglied'
    UNION ALL SELECT 'Passivmitglied', '200er Club'
    UNION ALL SELECT 'Partner', 'Sponsoren'
    UNION ALL SELECT 'Partner', 'Persönliche Sponsoren'
    UNION ALL SELECT 'Partner', 'Gemeinden'
) AS links
JOIN club_group p ON p.name = links.parent
JOIN club_group c ON c.name = links.child;
