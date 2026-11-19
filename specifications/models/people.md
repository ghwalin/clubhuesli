# People: Classes and Attributes
## Allgemeine Person (abstract)
- Mitgliederart (Aktiv, Passiv, Elternteil, Sponsor, …)
- E-Mail
- Telefonnummer
- Adresse (Strasse, Nr, Adresszusatz, PLZ, Ort, Land)
- Eintritt/Austrittdatum, mehrfach vorhanden
- Notizfeld

## Natürliche Person
Implementiert Allgemeine Person
- Vorname
- Nachname
- Beziehung zu Erziehungsberechtigten (optional)
- Beziehung zu Kindern (optional)

## Juristische Person (Sponsoren, Gemeinden)
Implementiert Allgemeine Person
- Name
- Kontaktperson (Vorname, Nachname)
- Unterstützung (Textarea)

## Aktive Spielerin
Erbt von Natürliche Person
- Geburtsdatum
- AHV-Nummer
- J+S-Nummer
- Einwilligung Foto (nur minderjährige)
- Anzahl Helfereinsätze pro Saison

## Coach
Erbt von Natürliche Person
(Spielerin und Coach sind getrennte Klassen. Ist eine Person beides, existieren zwei Datensätze.)
- AHV-Nummer
- J+S-Nummer