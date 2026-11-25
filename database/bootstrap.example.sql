-- Clubadmin bootstrap, sprint 1 (MySQL 8, utf8mb4)
-- Template that creates the database and the application user. Copy it to
-- database/bootstrap.sql (git-ignored), set the password of the application
-- user in that copy and run the copy. Needs an administrative connection and
-- must run BEFORE database/schema.sql:
--
--   cp database/bootstrap.example.sql database/bootstrap.sql
--   mysql -u root -p < database/bootstrap.sql
--   mysql -u root -p clubhuesli < database/schema.sql
--
-- Warning: this drops the existing database and user, destroying all data.
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- Database: drop and recreate from scratch (destroys all existing data!)
-- ---------------------------------------------------------------------------
DROP DATABASE IF EXISTS clubhuesli;
CREATE DATABASE clubhuesli
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Application user: recreate and grant the privileges the app needs.
-- Replace 'change-me' below (both statements) with the real password and keep
-- it in sync with config/config.php ('db' => 'pass'). Do not commit the copy.
-- ---------------------------------------------------------------------------
DROP USER IF EXISTS 'clubhuesli'@'localhost';
DROP USER IF EXISTS 'clubhuesli'@'127.0.0.1';

CREATE USER 'clubhuesli'@'localhost' IDENTIFIED BY 'change-me';
CREATE USER 'clubhuesli'@'127.0.0.1' IDENTIFIED BY 'change-me';

GRANT SELECT, INSERT, UPDATE, DELETE ON clubhuesli.* TO 'clubhuesli'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON clubhuesli.* TO 'clubhuesli'@'127.0.0.1';

FLUSH PRIVILEGES;
