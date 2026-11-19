# Grundlagen
## Allgemein
Vollständige PHPDoc in English
Bezeichner und Kommentare in English

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
* Passkey (primär), E-Mail/Telefon und Passwort (backup)
* JWT
* Token 2 Minuten gültig, Refresh-Token
* Refresh-Token im LocalStorage gespeichert, 30 Tage

# Berechtigungen / Benutzerrollen

## Admin
- Alle Personendaten bearbeiten
- Gruppen erstellen, bearbeiten
- Alle Daten bearbeiten

## Coach
- Spiele und Trainings für sein Team erfassen
- An/Abmeldungen für seine Spielerinnen erfassen

## Geschäftsstelle
- Alle Personendaten bearbeiten
- Gruppen erstellen, bearbeiten
- Personen zu Gruppen zuordnen

## Kommunikation
- E-Mail an Gruppen versenden

