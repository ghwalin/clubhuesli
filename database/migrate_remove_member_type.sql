-- Migration for existing databases: the member type («Mitgliederart») is gone.
-- A person is either natural or legal (person_type); everything else (active member,
-- sponsor, passive member, ...) is expressed by group memberships.
-- Run once (MySQL 8). New installations do not need it (schema.sql is already up to date).
SET NAMES utf8mb4;
USE clubhuesli;

ALTER TABLE person DROP COLUMN member_type;
