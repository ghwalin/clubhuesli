# People: Classes and Attributes
## Allgemeine Person (abstract)
Wird im Datenmodell als Entität abgebildet mit Beziehungen zu den spezialisierten Tabellen.
- Mitgliederart (Aktiv, Passiv, Elternteil, Sponsor, …)
- E-Mail
- Telefonnummer
- Adresse (optional)
  - Strasse, Nr
  - Adresszusatz
  - PLZ
  - Ort
  - Land
- Notizfeld

## Natürliche Person
Implementiert allgemeine Person (Spielerin, Coach, Elternteil, Helfer, Funktionär, ...)
- Vorname
- Nachname
- Geburtsdatum (Pflicht für Teammitglieder und Helfer, sonst optional)
- Beziehung zu Kindern (optional)

## Juristische Person (Sponsoren, Gemeinden)
Implementiert allgemeine Person
- Name
- Kontaktperson (Vorname, Nachname)
- Unterstützung (Textarea)

## Teammitglieder (bisher: Aktive Spielerin / Coach)
Erbt von natürliche Person
- AHV-Nummer
- J+S-Nummer
- Beziehung zu Erziehungsberechtigten (optional, nur minderjährige)
- Einwilligung Foto (nur minderjährige)
- Anzahl Helfereinsätze pro Saison
