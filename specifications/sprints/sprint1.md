# Sprint 1
## Dauer
20.11.2026 - 20.12.2026

## User Stories
* Natürliche_Personen_verwalten.md (ohne die unten zurückgestellten Szenarien)
* Adressen_verwalten.md
* Juristische_Personen_verwalten.md (wird von Adressen_verwalten, Szenario 3, vorausgesetzt)
* Minimaler Login für die Rollen Admin und Geschäftsstelle (Stub; keine eigene Story vorhanden, die volle Authentifizierung mit OAuth/Passkey folgt später)

## Datenmodell
Siehe [sprint1_Modell.md](sprint1_Modell.md) (UML-Klassendiagramm und ERM).

## Zurückgestellt (späterer Sprint)
Diese Szenarien aus «Natürliche Personen verwalten» hängen von Gruppen, Saisons, Mitgliedschaften oder Einsätzen ab, die nicht Teil von Sprint 1 sind:

* Szenario 3a: Helfer ohne Geburtsdatum (benötigt Einsätze, `models/helpers.md`)
* Szenario 3b: Mindestalter für Helfer (benötigt Einsatzarten, `models/helpers.md`)
* Szenario 8: Austritt aus dem Verein (benötigt Mitgliedschaften und Saisons)
* Szenario 8a: Austritt aus dem Verein mit bereits erfasstem Teamaustritt
* Szenario 8b: Austritt aus dem Verein mit Mitgliedschaften in künftigen Saisons

Einschränkungen in Sprint 1:
* Szenario 9 (Person löschen) entfernt nur die Person, ihre Beziehungen (Erziehungsberechtigte) und die Adresse. Mitgliedschaften und Helfereinsätze kommen mit den späteren Stories dazu.
* «Anzahl Helfereinsätze pro Saison» ist nicht Teil von Sprint 1 und wird später zusammen mit dem Helfer-Modell behandelt.

## Offene Punkte
* Berechtigungskonzept (AHV-Nummer nur für Admin und Geschäftsstelle sichtbar) als eigene Story planen.
