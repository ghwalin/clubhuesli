# Sprint 1: Klassendiagramm und ERM

Grundlage: `models/people.md`, `models/groups.md`, `models/season.md`, User Stories zu Personen, Adressen, Saisons, Gruppen und Gruppenzuordnungen.

## Diagramme

Die Diagramme liegen als Mermaid-Dateien (einzige Quelle) in `specifications/diagrams/`:

* UML-Klassendiagramm: [sprint1_klassendiagramm.mmd](../diagrams/sprint1_klassendiagramm.mmd)
* ERM: [sprint1_erm.mmd](../diagrams/sprint1_erm.mmd)

## Hinweise zum Klassendiagramm

Alle Attribute sind `private` (Zugriff über Getter/Setter, Validierung im Setter).

**Validierungen (Setter):**
* Geburtsdatum Pflicht für `TeamMember`, nicht in der Zukunft
* AHV-Nummer `756.XXXX.XXXX.XX` mit EAN-13-Prüfziffer
* Einwilligung Foto nur bei Minderjährigen (Standard: Nein)
* Adresse nur vollständig (Strasse, PLZ, Ort); bei Land CH PLZ = 4 Ziffern
* E-Mail gültig, nicht eindeutig; Pflicht bei natürlichen Personen, ausser minderjährigen Teammitgliedern mit Erziehungsberechtigtem
* Erziehungsberechtigte nur bei minderjährigen Teammitgliedern, nicht mit sich selbst
* Saison: Bezeichnung eindeutig, Enddatum ≥ Startdatum, keine Überschneidung mit anderen Saisons
* Gruppe: Bezeichnung eindeutig; Hierarchie zyklenfrei, keine Selbstzuordnung; Deaktivieren nur ohne aktive Mitgliedschaften und mit ausschliesslich inaktiven Untergruppen; kein Löschen
* Mitgliedschaft: nur aktive Gruppen; pro Person/Gruppe/Saison (bei Teams zusätzlich Rolle) höchstens eine; Team nur mit Teammitgliedern (natürliche Personen; Rolle `PLAYER`/`COACH`), sonst Rolle `MEMBER`; Shirtnummer nur bei `PLAYER`; Austrittsdatum innerhalb der Saison
* Zugehörigkeit zu Obergruppen wird abgeleitet (transitiv) und nicht gespeichert

## Hinweise zum ERM

Vererbung als *Table-per-Type*: gemeinsame Tabelle `person`, die spezialisierten Tabellen teilen sich den Primärschlüssel (1:0..1).

Constraints:
* `GUARDIANSHIP.child_id <> guardian_id`
* `ON DELETE CASCADE` auf allen `person_id`- und `GUARDIANSHIP`-Fremdschlüsseln, auch bei `MEMBERSHIP.person_id` (Szenario «Person löschen»)
* `SEASON.name` und `CLUB_GROUP.name` eindeutig
* `GROUP_HIERARCHY.parent_id <> child_id`; Zyklenfreiheit wird in der Anwendung geprüft
* `MEMBERSHIP`: eindeutig über (`group_id`, `person_id`, `season_id`, `role`); `role` ist bei Nicht-Team-Gruppen `MEMBER` (kein NULL, damit die Eindeutigkeit greift)
* `MEMBERSHIP.season_id` und `group_id` mit `ON DELETE RESTRICT` (Saison löschen nur ohne Mitgliedschaften; Gruppen werden nicht gelöscht)
* Tabelle heisst `club_group`, da `group` in SQL reserviert ist
