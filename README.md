# Clubadmin

PHP 8 (PDO) JSON-API + Vanilla-JS-Frontend + MySQL 8. Anforderungen: `specifications/Clubadmin.md`.

Umfang von Sprint 1 (siehe `specifications/sprints/sprint1.md`): natürliche Personen und
Teammitglieder, Adressen, juristische Personen, Saisons, Gruppen mit Ober-/Untergruppen,
Gruppenzuordnungen pro Saison (inkl. Austritt) sowie ein minimaler Login für die Rollen
Admin und Geschäftsstelle.

## Voraussetzungen
* PHP 8.3 mit den Erweiterungen `mbstring` und `pdo_mysql`
* MySQL 8
* Composer (nur für die Tests; die Applikation selbst läuft auch ohne, siehe `src/autoload.php`)

## Installation
1. `database/bootstrap.sql` aus `database/bootstrap.example.sql` kopieren und in der Kopie
   das Passwort des DB-Benutzers setzen (`IDENTIFIED BY 'change-me'`, zweimal). Die Kopie ist
   von git ausgeschlossen, das Beispiel enthält keine echten Passwörter. Danach die Kopie
   ausführen; das Skript verwirft eine bestehende Datenbank `clubhuesli` samt Daten:
   ```
   cp database/bootstrap.example.sql database/bootstrap.sql
   mysql -u root -p < database/bootstrap.sql
   ```
2. `config/config.php` aus `config/config.example.php` kopieren und anpassen. Alternativ
   `config/config.local.php` anlegen, die `config/config.php` überschreibt:
   ```php
   <?php return ['db' => ['pass' => '...'], 'debug' => true];
   ```
   Beide Dateien sind von git ausgeschlossen.
3. Schema laden. Das gehört wie Schritt 1 dem Server-Administrator: der DB-Benutzer der
   Applikation hat keine `CREATE TABLE`-Rechte, `bin/install.php` legt deshalb keine Tabellen
   an.
   ```
   mysql -u root -p clubhuesli < database/schema.sql
   ```
   Das Skript ist wiederholbar (`CREATE TABLE IF NOT EXISTS`): eine bestehende Datenbank von
   früher bekommt so die neuen Tabellen `season`, `club_group`, `group_hierarchy` und
   `membership`.
   Optional die Demodaten: `mysql -u root -p clubhuesli < database/seed_demo.sql`
4. Ersten Admin anlegen (braucht `config/config.php` und das geladene Schema):
   ```
   php bin/install.php admin@example.org 'Passwort1234'
   ```
   Eine weitere Rolle: `php bin/install.php office@example.org 'Passwort1234' office`
5. Webroot auf `public/` setzen (bzw. `http://localhost/clubhuesli/public/`). `config/`, `src/`,
   `database/`, `bin/` und `tests/` nicht öffentlich ausliefern. Für Apache liegt eine
   `public/.htaccess` bereit (Rewrite auf `api/index.php` bzw. `index.html`).

Entwicklungsserver ohne Apache:
```
php -S localhost:8000 -t public bin/dev_router.php
```

## Projektstruktur
```
bin/                 install.php (erstes Konto), import_admidio.php (Datenimport), dev_router.php
config/              Konfiguration (nicht im Repository)
database/            Datenbank-Skripte (MySQL 8)
  bootstrap.example.sql      Vorlage für Datenbank und DB-Benutzer (Kopie als root ausführen)
  schema.sql         Datenbankschema (Tabellen, als root ausführen)
  seed_demo.sql      Demodaten (optional)
public/              alles, was der Client erreicht
  index.html         Single-Page-Application
  api/index.php      Front-Controller der JSON-API
  css/, js/, views/  Stylesheet, ES-Module (api.js, app.js, groups.js), HTML-Teilansichten
src/                 Backend (PSR-4: Clubhuesli\)
  Config.php         Konfiguration
  Controller/        PersonController, SeasonController, GroupController,
                     MembershipController, AuthController
  Dao/               Database, PersonDao, UserDao, SeasonDao, GroupDao, MembershipDao
  Http/              ApiKernel, Router, Request, Response, Session, HttpException
  Model/             AbstractPerson, NaturalPerson, TeamMember, LegalPerson, User,
                     Season, Group, GroupHierarchy, Membership, Enums
  Import/            SqlDumpReader, AdmidioMapper, AdmidioImporter (Import aus Admidio)
  Service/           MembershipReader (Mitglieder inkl. Untergruppen, Gruppen einer Person)
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
| GET     | `/api/meta`                  | Personenarten für die Formulare |
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

Gruppen und Zuordnungen: `club_group` (die Tabelle heisst so, weil `group` in SQL reserviert
ist), `group_hierarchy` (n:n, Zyklenfreiheit prüft die Applikation), `membership` (Person,
Gruppe, Saison, Rolle, Funktion, Shirtnummer, Austrittsdatum). Die Zugehörigkeit zu Obergruppen
wird aus den Untergruppen abgeleitet und nicht gespeichert. Gruppen werden nie gelöscht, nur
deaktiviert.

## Import der Mitglieder aus Admidio

`bin/import_admidio.php` liest ein SQL-Backup der alten Admidio-Datenbank direkt ein (keine
zweite Datenbank nötig) und legt die **aktuellen** Mitglieder in Clubadmin an. Voraussetzung:
Schema geladen (siehe Installation) und `config/config.php` vorhanden.

```
# 1. Nur anzeigen, was importiert würde (braucht keine Datenbank)
php bin/import_admidio.php <dump.sql> --plan-only
# 2. Probelauf mit der Datenbank: alles in einer Transaktion, am Ende Rollback
php bin/import_admidio.php <dump.sql> --dry-run
# 3. Import
php bin/import_admidio.php <dump.sql>
```

Optionen: `--date=JJJJ-MM-TT` (Stichtag für «aktuell», Standard heute), `--org=1` (Id der
Admidio-Organisation). Exit-Code 3 heisst: der Import lief, einzelne Personen mussten aber
übersprungen werden (siehe Ausgabe). Der Import ist wiederholbar: Saison, Gruppen, Personen
(Name + Geburtsdatum bzw. E-Mail) und Zuordnungen werden erkannt und nicht doppelt angelegt.

Regeln (`src/Import/AdmidioMapper.php`):

* **Saison:** die Saison, die den Stichtag enthält (1.7.–30.6., z. B. 2026/27); sie wird angelegt.
* **Gruppen:** Der Import legt die Standardgruppen von Chilis samt Hierarchie an
  (`chilis/groups.md`, `src/Import/ChilisGroups.php`, `database/seed_groups.sql`): 8 Teams und
  die Gruppen Aktivmitglied (mit Vorstand, Funktionärin > Spielsekretärin, Spielerin, Coach,
  Ehrenmitglied, Schiedsrichter), Passivmitglied (Freimitglied, 200er Club), Alumni und Partner
  (Sponsoren, Persönliche Sponsoren, Gemeinden). Eine Mitgliederart gibt es nicht mehr, es zählen
  nur Personenart (natürlich/juristisch) und Gruppen.
* **Rollen → Gruppen** (`ROLE_GROUPS` im Mapper): z. B. «Funktionäre» → Funktionärin,
  «Spielsekretär:innen» → Spielsekretärin, «Trainer:innen» → Coach, «Ehrenmitglieder» →
  Ehrenmitglied, «Gönner» → 200er Club, «Sponsor» → Sponsoren, «Gemeindepräsidenten» → Gemeinden,
  «B-/C-Juniorinnen» → Jun B/Jun C, «U17B-Juniorinnen» → U17B. Das Häkchen «beitragsbefreit» ergibt
  Freimitglied, die Schiedsrichter-Felder ergeben Schiedsrichter. Rollen ohne Gruppe werden mit
  der Anzahl aktiver Mitgliedschaften gemeldet. Systemrollen (Kategorie «COMMON», z. B.
  Administrator) sind keine Gruppen.
* **Zuordnungen:** Übernommen werden Mitgliedschaften, deren Ende noch nicht erreicht ist. Ein
  Enddatum innerhalb der Saison wird zum Austrittsdatum. In Teams ist der Leiter Coach, sonst
  Spielerin (mit Trikot-Nr.); Spielerinnen gehören zusätzlich zur Gruppe «Spielerin», Coaches
  zur Gruppe «Coach». Die Funktion von Vorstand/Funktionärin entsteht aus den angekreuzten Feldern.
* **Personen:** Mitglieder von «Sponsor», «Persönliche Sponsoren» und «Gemeindepräsidenten» sind
  **immer** juristische Personen (Name = Firma bzw. Sponsorenname, sonst der Name der Person;
  Kontaktperson = das Admidio-Profil; keine Teamzuordnung); auch ein «Gönner» mit Firma wird
  juristische Person. Alle anderen sind natürliche Personen; wer in einem Team oder in
  «Aktivmitglied» ist und ein Geburtsdatum hat, wird Teammitglied. Nicht gültige oder
  archivierte Konten werden übersprungen.
* **Eltern:** Bei Minderjährigen wird mit «Vorname Nachname» und E-Mail der Eltern ein
  Erziehungsberechtigter angelegt (Geschwister teilen ihn). Sonst landen die Angaben in der
  Notiz, und die E-Mail der Eltern dient als Kontaktadresse.
* **Ungültige Werte:** Optionale Werte, die die Validierung nicht bestehen (z. B. falsche
  AHV-Nummer), werden verworfen, in der Notiz festgehalten und gemeldet. Personen ohne
  E-Mail-Adresse (Pflichtfeld) werden nicht importiert, sondern in der Ausgabe aufgelistet.
* Nicht übernommen werden Passwörter, Konten, Mitteilungen, Termine und das Änderungsprotokoll.

## Migration einer bestehenden Datenbank

Die Mitgliederart (`person.member_type`) ist entfallen. Bestehende Datenbanken einmal anpassen:
`mysql -u root -p clubhuesli < database/migrate_remove_member_type.sql`. Die Standardgruppen
(optional): `mysql -u root -p clubhuesli < database/seed_groups.sql`.
