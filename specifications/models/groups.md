# Groups: Classes and Attributes
## Gruppe
### Attribute
- Bezeichnung
- Gruppenart (Team, Funktionäre, andere)
- Status (aktiv/inaktiv)
- Obergruppen
- Untergruppen

## Mitgliedschaft
### Attribute
- GruppenID
- PersonID
- Rolle
- Funktion (Freitext)
- Shirtnummer (nur Spielerin in einem Team; pro Team eindeutig)

## Beziehungen
- Gruppen können Gruppen enthalten (z.B. Materialverantwortlicher und Webmaster sind Funktionäre)
  - n:n-Beziehung
- Mitglieder einer Untergruppe gehören automatisch auch zu allen Obergruppen (z.B. Mitglieder des Teams NLB gehören zur Gruppe Spielerin)
  - Ausnahme: Coaches gehören nicht zur Obergruppe «Spielerin»
- Spielerin und Coach sind getrennte Personen-Datensätze und haben getrennte Gruppenzugehörigkeiten
- Personen werden Gruppen zugeordnet (n:n-Beziehung)
  - Start- / End-Datum der Gruppenzugehörigkeit (mehrfach pro Person/Gruppe)

## Offene Fragen
- Zugehörigkeit zur Gruppe pro Saison (statt Start-/Enddatum)?