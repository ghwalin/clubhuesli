# People: Classes and Attributes
## Allgemeine Person (abstract)
Wird im Datenmodell als Entität abgebildet mit Beziehungen zu den spezialisierten Tabellen.
- Personenart (natürliche Person oder juristische Person). Eine «Mitgliederart» gibt es nicht mehr: ob jemand Aktivmitglied, Passivmitglied, Freimitglied, Sponsor, … ist, ergibt sich aus den **Gruppen** (siehe `groups.md`).
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
Implementiert allgemeine Person (Spielerin, Coach, Erziehungsberechtigte, Helfer, Funktionärin, ...; die Zuordnung dazu erfolgt über Gruppen)
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
Nur Teammitglieder können Mitglied eines Teams (Gruppenart Team) sein; juristische Personen nie. In allen anderen Gruppen sind natürliche und juristische Personen erlaubt (siehe `groups.md`).
- AHV-Nummer
- J+S-Nummer
- Beziehung zu Erziehungsberechtigten (optional, nur minderjährige)
- Einwilligung Foto (nur minderjährige)

## Offene Fragen
- Anzahl Helfereinsätze pro Saison: wird später behandelt (zusammen mit dem Modell «Helpers»)
