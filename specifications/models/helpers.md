# Helpers: Classes and Attributes

## Anlass

### Attribute
- Bezeichnung
- Datum
- Anspielzeit
- Eventart (Beziehung)
- Status (aktiv, inaktiv)

## Einsatz
### Attribute
- Einsatzart (Beziehung)
- Helfer (Beziehung zu natürliche Person; Geburtsdatum erforderlich für die Prüfung des Mindestalters)
- Gutschrift (Beziehung zu Teammitglied)
- Bemerkung

## Einsatzart
### Attribute
- Bezeichnung
- Startoffset (gegenüber Anspielzeit)
- Dauer (Stunden, Dezimalzahl)
- Beschreibung (URL)
- Voraussetzung
  - Gruppe (z.B. Spielsekretär)
  - Mindestalter

## Anlassart
### Attribute
- Bezeichnung
- Einsätze
  - Einsatzart
  - Anzahl

## Offene Fragen
- Anlässe mit mehreren Schichten (Tagesrunde, Trophy, ...)
- Abgleich mit Anzahl Pflichteinsätze der Teammitglieder