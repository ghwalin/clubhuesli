# Grundlagen
## Allgemein
Vollständige PHPDoc in English
## Backend
* PHP-Webapplikation
* RESTful
* MVC-Architektur mit DAO für Datenbankzugriffe
* Alle Attribute sind private, getter/setter erzeugen
* Zugriff auch innerhalb der Klasse über getter/setter
* Validierung der Werte erfolgt im Setter
## Frontend
* HTML, Javascript, CSS
## Authentifikation
* JWT
* Token 2 Minuten gültig, Refresh-Token
* Token im LocalStorage gespeichert
# Stammdaten

## Personalien
### Natürliche Person
- Vorname
- Nachname
- E-Mail
- Adresse (Strasse, Nr, PLZ, Ort)
### Juristische Person (Sponsoren, Gemeinden)
Erbt **nicht** von Natürliche Person
- Name
- Adresse
- Kontaktperson (Liste von Personen)
- E-Mail

  
### Aktive Spielerin
Erbt von Natürliche Person
- Geburtsdatum
- AHV-Nummer (nur Spielerinnen)
- J+S-Nummer (Spielerinnen, Trainer)
- Beziehung zu Erziehungsberechtigten (optional)
- Mitgliederart (Aktiv, Passiv, Elternteil, …)
- Einwilligung Foto

### Erziehungsberechtigter
Erbt von Person
- Beziehung zu Kind(ern)


## Gruppen
### Attribute
- Bezeichnung
- Art der Gruppe (Team, Funktionär, ...)
### Eigenschaften
 - Gruppen können Gruppen enthalten (z.B. Materialverantwortlicher und Webmaster sind Funktionäre)
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

