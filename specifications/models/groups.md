# Groups: Classes and Attributes
Die Personen werden in Gruppen organisiert. Die Gruppen können hierarchisch strukturiert sein (z.B. Geschäftsführerin ist Untergruppe von Funktionärin). 
Die Zugehörigkeit zu einer Gruppe erfolgt pro Saison (z.B. Spielerin in Gruppe NLB für Saison 2023/24).
## Saison
- Saison (z.B. 2023/24)
- Startdatum
- Enddatum
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
- SaisonID
- Rolle
- Funktion (Freitext)
- Shirtnummer (optional, nur Spielerin in einem Team; Eindeutigkeit wird nicht geprüft)
- Ausgetreten (optional, Datum des Austritts während der Saison; die Mitgliedschaft bleibt erhalten)

## Beziehungen
- Gruppen können Gruppen enthalten (z.B. Materialverantwortlicher und Webmaster sind Funktionäre)
  - n:n-Beziehung
- Personen werden Gruppen pro Saison zugeordnet (n:n-Beziehung)
- Mitglieder einer Untergruppe gehören in derselben Saison automatisch auch zu allen Obergruppen (transitiv; z. B. Mitglieder der Gruppe «Geschäftsführerin» gehören zur Gruppe «Funktionärin»)
- Tritt eine Person aus dem Verein aus, enden alle ihre Gruppenmitgliedschaften auf Ende der aktuellen Saison

## Offene Fragen
- ...