# Clubadmin

PHP 8 (PDO) JSON-API + Vanilla-JS-Frontend + MySQL 8. Anforderungen: `specifications/Clubadmin.md`.

Umfang von Sprint 1 (siehe `specifications/sprints/sprint1.md`): natürliche Personen und
Teammitglieder, Adressen, juristische Personen sowie ein minimaler Login für die Rollen
Admin und Geschäftsstelle.

## Voraussetzungen
* PHP 8.3 mit den Erweiterungen `mbstring` und `pdo_mysql`
* MySQL 8
* Composer (nur für die Tests; die Applikation selbst läuft auch ohne, siehe `src/autoload.php`)

## Installation
1. DB + User anlegen (Passwort in `database/bootstrap.sql` vorher anpassen).
   Das Skript verwirft eine bestehende Datenbank `clubhuesli` samt Daten:
   ```
   mysql -u root -p < database/bootstrap.sql
   ```
2. `config/config.php` aus `config/config.example.php` kopieren und anpassen. Alternativ
   `config/config.local.php` anlegen, die `config/config.php` überschreibt:
   ```php
   <?php return ['db' => ['pass' => '...'], 'debug' => true];
   ```
   Beide Dateien sind von git ausgeschlossen.
3. Schema laden und ersten Admin anlegen:
   ```
   php bin/install.php admin@example.org 'Passwort1234'
   ```
   Eine weitere Rolle: `php bin/install.php office@example.org 'Passwort1234' office`
4. Webroot auf `public/` setzen (bzw. `http://localhost/clubhuesli/public/`). `config/`, `src/`,
   `database/`, `bin/` und `tests/` nicht öffentlich ausliefern. Für Apache liegt eine
   `public/.htaccess` bereit (Rewrite auf `api/index.php` bzw. `index.html`).

Entwicklungsserver ohne Apache:
```
php -S localhost:8000 -t public bin/dev_router.php
```

## Projektstruktur
```
bin/                 install.php (Schema + erstes Konto), dev_router.php
config/              Konfiguration (nicht im Repository)
database/            Datenbank-Skripte (MySQL 8)
  bootstrap.sql      Datenbank und DB-Benutzer (als root ausführen)
  schema.sql         Datenbankschema (Tabellen)
  seed_demo.sql      Demodaten (optional)
public/              alles, was der Client erreicht
  index.html         Single-Page-Application
  api/index.php      Front-Controller der JSON-API
  css/, js/, views/  Stylesheet, ES-Module, HTML-Teilansichten
src/                 Backend (PSR-4: Clubhuesli\)
  Config.php         Konfiguration
  Controller/        PersonController, AuthController
  Dao/               Database, PersonDao, UserDao
  Http/              ApiKernel, Router, Request, Response, Session, HttpException
  Model/             AbstractPerson, NaturalPerson, TeamMember, LegalPerson, User, Enums
  Validation/        Validator, ValidationException
tests/unit/          PHPUnit (Modelle, Validierung, JSON-API)
tests/behat/         Behat: Akzeptanzkriterien der User Stories
```

Architektur: MVC mit DAO. Alle Attribute der Modelle sind `private`, der Zugriff läuft auch
innerhalb der Klasse über Getter und Setter, und die Validierung einzelner Werte erfolgt im
Setter. Regeln über mehrere Attribute (vollständige Adresse, Pflicht-E-Mail) prüft
`validate()` bzw. `assertValid()` vor dem Speichern.

Vererbung in der Datenbank als *Table-per-Type*: `person` enthält die gemeinsamen
Attribute, `natural_person`, `team_member` und `legal_person` teilen sich den
Primärschlüssel (`ON DELETE CASCADE`).

## API
Alle Endpunkte liegen unter `/api` und antworten mit JSON. Zugriff nur für angemeldete
Benutzer mit der Rolle Admin oder Geschäftsstelle.

| Methode | Pfad                         | Beschreibung                                    |
|---------|------------------------------|-------------------------------------------------|
| POST    | `/api/auth/login`            | Anmelden (`email`, `password`)                  |
| POST    | `/api/auth/logout`           | Abmelden                                        |
| GET     | `/api/auth/me`               | Angemeldetes Konto                              |
| GET     | `/api/meta`                  | Personen- und Mitgliederarten für die Formulare |
| GET     | `/api/persons`               | Personenliste (`search`, `personType`)          |
| POST    | `/api/persons`               | Person erfassen                                 |
| GET     | `/api/persons/{id}`          | Person inkl. Erziehungsberechtigte und Kinder   |
| PUT     | `/api/persons/{id}`          | Person ändern                                   |
| DELETE  | `/api/persons/{id}`          | Person löschen                                  |
| PUT     | `/api/persons/{id}/guardians`| Erziehungsberechtigte setzen (`guardianIds`)    |

Fehler kommen als `{"error": "…"}`, Validierungsfehler zusätzlich mit
`{"errors": [{"field": "birthDate", "message": "…"}]}` und Status 422.

## Tests
```
composer install
composer test     # PHPUnit: Modelle, Validierung und JSON-API (ohne Datenbank)
composer behat    # Behat: Akzeptanzkriterien der Sprint-1-User-Stories
```
Beide Suiten laufen ohne Datenbank: die API-Tests und die Behat-Szenarien verwenden die
In-Memory-DAOs aus `tests/unit/Fake/`.

## Rollen
admin (alles) · office/Geschäftsstelle (Personen, Gruppen, Zuordnung, Helfer) · coach (Termine + An-/Abmeldungen seiner Teams; Coach = Gruppenrolle mit `is_coach`) · communication (E-Mail an Gruppen) · member (eigene/Kinder-Anmeldungen, Helfer eintragen).
Benutzer werden über eine Person verknüpft; Team-/Kinder-Bezug ergibt sich aus Gruppenzuordnung und Eltern-Kind-Beziehung.

In Sprint 1 sind nur admin und office implementiert (`src/Model/Role.php`). Der Login ist ein
Stub mit E-Mail/Passwort und PHP-Session; OAuth 2.0 (Microsoft Identity Platform), Passkey
und JWT folgen in einem späteren Sprint.
