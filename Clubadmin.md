
# Stammdaten

## Personalien

### Natürliche Person
- Vorname
- Nachname
- E-Mail
- Geburtsdatum
- Adresse (Strasse, Nr, PLZ, Ort)
- AHV-Nummer (nur Spielerinnen)
- J+S-Nummer (Spielerinnen, Trainer)
- Beziehung Eltern <-> Kind
- Mitgliederart (Aktiv, Passiv, Elternteil, …)
- Einwilligung Foto


### Juristische Person (Sponsoren, Gemeinden)
- Name
- Adresse
- Kontaktperson
- E-Mail


## Gruppen
### Attribute
- Bezeichnung
- Art der Gruppe (Team, Funktionär, ...)
### Eigenschaften
 - Gruppen können Gruppen enthalten (z.B. Materialverantwortlicher, Funktionär)
   - n:n-Beziehung
 - Zuordnung der Personen zu Gruppe   (n:n-Beziehung) 
 - Start / Ende (mehrfach für gleiche Gruppe möglich)
 - Person hat Rolle (z.B. Trainer, Mitglied, Förderkader)

# Teilnahme (SpielerPlus)

- Mehrere Teams (aus Gruppen gebildet)
- Pro Team mehrere Trainings und Spiele
  - Ort/Zeit
  - Bemerkungen
  - Frist für An/Abmeldung
- Pro Teilnehmer und Termin An-/Abmeldung mit Kommentar
- Mobile App für Teilnehmer


# Helfereinsatz

- Viele Events, teilweise mit mehreren Schichten
- Pro Event mehrere Einsätze
- Jedes Mitglied mit Anzahl Einsätze


# Berechtigungen / Benutzerrollen
## Admin
- Alle Personendaten bearbeiten
- Gruppen erstellen, bearbeiten
- Alle Daten bearbeiten
## Trainer
- Spiele und Trainings für sein Team erfassen
- An/Abmeldungen für seine Spielerinnen erfassen
## Geschäftsstelle
- Alle Personendaten bearbeiten
- Gruppen erstellen, bearbeiten
- Personen zu Gruppen zuordnen

## Kommunikation
- E-Mail an Gruppen versenden

