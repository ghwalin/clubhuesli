# People: Classes and Attributes
## Allgemeine Person (abstract)
Wird im Datenmodell als Entität abgebildet mit Beziehungen zu den spezialisierten Tabellen.
- Mitgliederart (Aktiv, Passiv, Elternteil, Sponsor, …)
- E-Mail
- Telefonnummer
- Adresse (Strasse, Nr, Adresszusatz, PLZ, Ort, Land)
- Eintritt/Austrittdatum, mehrfach vorhanden (redundant)
- Notizfeld

## Natürliche Person
Implementiert allgemeine Person
- Vorname
- Nachname
- Beziehung zu Erziehungsberechtigten (optional)
- Beziehung zu Kindern (optional)

## Juristische Person (Sponsoren, Gemeinden)
Implementiert allgemeine Person
- Name
- Kontaktperson (Vorname, Nachname)
- Unterstützung (Textarea)

## Aktive Spielerin
Erbt von natürliche Person
- Geburtsdatum
- AHV-Nummer
- Einwilligung Foto (nur minderjährige)  pro Saison / Team?
- Anzahl Helfereinsätze pro Saison

## Coach
Erbt von natürliche Person
Spielerin und Coach sind getrennte Klassen. 
Im Datenmodell existieren beide als Natürliche Person mit Beziehung(en) zu Spielerin bzw. Coach. (Spezialisierung)
- AHV-Nummer
- J+S-Nummer