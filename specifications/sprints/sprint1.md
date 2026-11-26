# Sprint 1
## Dauer
20.11.2026 - 20.12.2026

## User Stories
* Natürliche_Personen_verwalten.md (ohne die unten zurückgestellten Szenarien; inkl. Szenarien 8, 8a, 8b: Austritt aus dem Verein)
* Adressen_verwalten.md
* Juristische_Personen_verwalten.md (wird von Adressen_verwalten, Szenario 3, vorausgesetzt)
* Minimaler Login für die Rollen Admin und Geschäftsstelle (Stub; keine eigene Story vorhanden, die volle Authentifizierung mit OAuth/Passkey folgt später)
* Saisons_verwalten
* Mitglied_einem_Team_zuordnen
* Teammitglieder_anzeigen
* Teammitglieder_verwalten
* Teamzugehörigkeit_korrigieren
* Austritt_aus_Team_erfassen
* Gruppen_verwalten (Epic)
  * Gruppe_erfassen_und_bearbeiten
  * Gruppe_deaktivieren_und_reaktivieren
  * Ober_und_Untergruppen_zuordnen
  * Gruppenhierarchie_anzeigen
* Gruppenzuordnungen_verwalten (Epic)
  * Person_einer_Gruppe_zuordnen
  * Austritt_aus_Gruppe_erfassen
  * Gruppenzuordnung_loeschen
  * Gruppenmitglieder_anzeigen

## Datenmodell
Siehe [sprint1_Modell.md](sprint1_Modell.md) (UML-Klassendiagramm und ERM).

## Zurückgestellt (späterer Sprint)
Diese Szenarien aus «Natürliche Personen verwalten» hängen von Einsätzen ab (nicht Teil von Sprint 1):

* Szenario 3a: Helfer ohne Geburtsdatum (benötigt Einsätze, `models/helpers.md`)
* Szenario 3b: Mindestalter für Helfer (benötigt Einsatzarten, `models/helpers.md`)

Einschränkungen in Sprint 1:
* Szenario 9 (Person löschen) entfernt die Person, ihre Beziehungen (Erziehungsberechtigte), die Adresse und ihre Gruppenmitgliedschaften; Teilnahmen und Helfereinsätze kommen mit den späteren Stories dazu.
* «Anzahl Helfereinsätze pro Saison» ist nicht Teil von Sprint 1 und wird später zusammen mit dem Helfer-Modell behandelt.

## Offene Punkte
* Berechtigungskonzept (AHV-Nummer nur für Admin und Geschäftsstelle sichtbar) als eigene Story planen.
