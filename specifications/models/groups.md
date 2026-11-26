# Groups: Classes and Attributes
Die Personen (natürliche und juristische) werden in Gruppen organisiert. Die Gruppen können hierarchisch strukturiert sein (z.B. Geschäftsführerin ist Untergruppe von Funktionärin).
Die Zugehörigkeit zu einer Gruppe erfolgt pro Saison (z.B. Spielerin in Gruppe NLB für Saison 2023/24). Die Saison ist in `season.md` beschrieben.

## Gruppe
### Attribute
- Bezeichnung (eindeutig)
- Gruppenart (Team, Funktionäre, andere; nachträglich änderbar)
- Status (aktiv/inaktiv)
- Obergruppen
- Untergruppen

### Regeln
- Gruppen werden **nicht gelöscht**, nur deaktiviert und bei Bedarf wieder aktiviert.
- Deaktivieren ist nur möglich, wenn **keine aktiven Mitgliedschaften** mehr vorhanden sind und **alle Untergruppen inaktiv** sind.
- Aktive Mitgliedschaft: Saison ist die aktuelle oder eine künftige und «Ausgetreten» ist leer oder noch nicht erreicht.
- Einer inaktiven Gruppe können keine neuen Personen zugeordnet und keine neuen Hierarchiebeziehungen erstellt werden.

## Mitgliedschaft
Zuordnung einer Person zu einer Gruppe für genau eine Saison.
### Attribute
- GruppenID
- PersonID (natürliche oder juristische Person)
- SaisonID
- Rolle (nur bei Gruppenart Team: Spielerin oder Coach; bei anderen Gruppen nicht relevant)
- Funktion (Freitext)
- Shirtnummer (optional, nur Rolle Spielerin in einem Team; Eindeutigkeit wird nicht geprüft)
- Ausgetreten (optional, Datum des Austritts innerhalb der Saison; die Mitgliedschaft bleibt erhalten; für alle Gruppenarten)

### Regeln
- Pro Person, Gruppe und Saison höchstens eine Mitgliedschaft (bei Teams zusätzlich pro Rolle).
- **Team:** Mitglied können nur natürliche Personen sein, und zwar nur Teammitglieder (siehe `people.md`).
- **Andere Gruppenarten:** Beide Personenarten (natürliche und juristische Personen) sind erlaubt.
- Mitgliedschaften können gelöscht werden (nur zur Korrektur von Fehlern, mit Warnung). Ein regulärer Abgang wird über «Ausgetreten» bzw. Nicht-Zuordnung in der Folgesaison abgebildet.

## Standardgruppen von Chilis
Die Gruppen von Chilis (`chilis/groups.md`, Skript `database/seed_groups.sql`):
- **Teams:** NLB, 1. Liga KF, U21B, U17B, Jun B, Jun C, Jun D, Starters
- **Gruppen** (eingerückt = Untergruppe):
  - Aktivmitglied
    - Vorstand
    - Funktionärin
      - Spielsekretärin
    - Spielerin
    - Coach
    - Ehrenmitglied
    - Schiedsrichter
  - Passivmitglied
    - Freimitglied
    - 200er Club
  - Alumni
  - Partner
    - Sponsoren
    - Persönliche Sponsoren
    - Gemeinden

Dank der transitiven Zugehörigkeit gehört z. B. ein Mitglied der Gruppe «Spielsekretärin» auch zu «Funktionärin» und «Aktivmitglied».

## Beziehungen
- Gruppen können Gruppen enthalten (z.B. Materialverantwortlicher und Webmaster sind Funktionäre)
  - n:n-Beziehung (Mehrfachzuordnung, auch «Raute», ist erlaubt)
  - zyklenfrei: eine Gruppe kann weder sich selbst noch einer ihrer direkten oder indirekten Untergruppen als Obergruppe zugeordnet werden
- Personen werden Gruppen pro Saison zugeordnet (n:n-Beziehung über die Mitgliedschaft)
- Mitglieder einer Untergruppe gehören in derselben Saison automatisch auch zu allen Obergruppen (transitiv; z. B. Mitglieder der Gruppe «Geschäftsführerin» gehören zur Gruppe «Funktionärin»). Diese Zugehörigkeit wird abgeleitet, nicht gespeichert.
  - Das Austrittsdatum einer Untergruppen-Mitgliedschaft gilt auch für die Obergruppen, sofern die Person nicht über eine andere Untergruppe Mitglied bleibt.
  - Wird eine Mitgliedschaft gelöscht, entfällt auch die abgeleitete Zugehörigkeit (ausser sie besteht über eine andere Untergruppe weiter).
- Tritt eine Person aus dem Verein aus, enden alle ihre Gruppenmitgliedschaften auf Ende der aktuellen Saison

## Offene Fragen
- Was passiert beim Löschen einer Mitgliedschaft mit abhängigen Daten (z.B. Teilnahmen, siehe `teamplan.md`)?
- Braucht es einen Austrittsgrund?
