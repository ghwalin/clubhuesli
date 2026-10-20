# Clubadmin

PHP 8 (PDO) JSON-API + Vanilla-JS-Frontend + MySQL 8. Anforderungen: `Clubadmin.md`.

## Installation
1. DB + User anlegen:
   ```sql
   CREATE DATABASE clubhuesli CHARACTER SET utf8mb4;
   CREATE USER 'clubhuesli'@'localhost' IDENTIFIED BY '...';
   GRANT ALL ON clubhuesli.* TO 'clubhuesli'@'localhost';
   ```
2. `config/config.local.php` anlegen (überschreibt `config/config.php`):
   ```php
   <?php return ['db' => ['pass' => '...'], 'mail_from' => 'club@example.org'];
   ```
3. Schema laden und ersten Admin anlegen: `php bin/install.php admin@example.org 'Passwort'`
4. Webroot auf `public/` setzen (bzw. `http://localhost/clubhuesli/public/`). `config/`, `src/`, `database/` nicht öffentlich ausliefern.

## Rollen
admin (alles) · office/Geschäftsstelle (Personen, Gruppen, Zuordnung, Helfer) · trainer (Termine + An-/Abmeldungen seiner Teams; Trainer = Gruppenrolle mit `is_trainer`) · communication (E-Mail an Gruppen) · member (eigene/Kinder-Anmeldungen, Helfer eintragen).
Benutzer werden über eine Person verknüpft; Team-/Kinder-Bezug ergibt sich aus Gruppenzuordnung und Eltern-Kind-Beziehung.
