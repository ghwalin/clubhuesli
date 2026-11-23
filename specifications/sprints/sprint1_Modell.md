# Sprint 1: Klassendiagramm und ERM

Grundlage: `models/people.md`, User Stories «Natürliche Personen verwalten», «Adressen verwalten» sowie «Juristische Personen verwalten».
Gruppen, Saisons und Mitgliedschaften sind **nicht** Teil von Sprint 1 (siehe `models/groups.md`).

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

## Hinweise zum ERM

Vererbung als *Table-per-Type*: gemeinsame Tabelle `person`, die spezialisierten Tabellen teilen sich den Primärschlüssel (1:0..1).

Constraints:
* `GUARDIANSHIP.child_id <> guardian_id`
* `ON DELETE CASCADE` auf allen `person_id`- und `GUARDIANSHIP`-Fremdschlüsseln (Szenario «Person löschen»)
