# Seasons: Classes and Attributes
Die Zugehörigkeit zu einer Gruppe erfolgt pro Saison (z.B. Spielerin in Gruppe NLB für Saison 2023/24).
## Saison
### Attribute
- Bezeichnung (z.B. 2023/24; eindeutig)
- Startdatum
- Enddatum (≥ Startdatum)

### Regeln
- Saisons überschneiden sich nicht.
- Die **aktuelle Saison** ist die Saison, in der das heutige Datum zwischen Start- und Enddatum liegt.
- Künftige Saisons können angelegt und mit Mitgliedschaften belegt werden.
- Eine Saison kann nur gelöscht werden, wenn ihr keine Mitgliedschaften zugeordnet sind.

### Beziehungen
- Eine Saison hat 0..n Mitgliedschaften (siehe `groups.md`).

## Offene Fragen
- Sollen Saisons automatisch angelegt werden (z.B. jeweils 01.07.–30.06.)?
- Braucht es eine Funktion «Mitgliedschaften aus Vorsaison übernehmen»?
