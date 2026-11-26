---
name: 📘 Person einer Gruppe zuordnen
about: Eine natürliche oder juristische Person für eine Saison einer Gruppe zuordnen
title: "[Story] Person einer Gruppe zuordnen"
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** Geschäftsführer
**möchte ich** eine natürliche oder juristische Person für eine Saison einer Gruppe zuordnen,
**damit** sie in dieser Saison zur Gruppe gehört und bei Auswertungen und E-Mails an die Gruppe berücksichtigt wird.

## 🧭 Kontext & Hintergrund

- Epic: Gruppenzuordnungen verwalten (Grundregeln siehe dort)
- In Gruppen der Art «Team» können nur natürliche Personen Mitglied sein (Teammitglieder). In allen anderen Gruppen sind natürliche und juristische Personen erlaubt.
- Für Gruppen der Art «Team» gilt zusätzlich die Story «Mitglied einem Team zuordnen» (Rolle, Shirtnummer, nur aktive Teammitglieder).
- Grundlage: `specifications/models/groups.md` (Mitgliedschaft: Gruppe, Person, Saison, Funktion)

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Natürliche Person zuordnen
    - **Gegeben** ich bin mit der Rolle Admin oder Geschäftsstelle angemeldet, die Gruppe «Funktionäre» ist aktiv und Anna Muster ist eine natürliche Person
    - **Wenn** ich Anna Muster der Gruppe «Funktionäre» für die Saison 2026/27 zuordne
    - **Dann** ist sie in der Mitgliederliste der Gruppe für die Saison 2026/27 aufgeführt
- [ ] **Szenario 2:** Juristische Person zuordnen
    - **Gegeben** die Gruppe «Sponsoren» ist aktiv und «Muster AG» ist eine juristische Person
    - **Wenn** ich «Muster AG» der Gruppe «Sponsoren» für die Saison 2026/27 zuordne
    - **Dann** ist sie in der Mitgliederliste aufgeführt (mit Name und Kontaktperson)
- [ ] **Szenario 2b:** Juristische Person nicht in Team
    - **Gegeben** die Gruppe «U18» hat die Gruppenart «Team» und «Muster AG» ist eine juristische Person
    - **Wenn** ich «Muster AG» dem Team zuordnen will
    - **Dann** wird sie nicht zur Auswahl angeboten bzw. abgelehnt mit der Meldung «Nur natürliche Personen können Mitglied eines Teams sein»
- [ ] **Szenario 2c:** Jede Person in Nicht-Team-Gruppen
    - **Gegeben** die Gruppe «Helfer» hat die Gruppenart «andere»
    - **Wenn** ich eine beliebige natürliche oder juristische Person zuordne
    - **Dann** wird die Zuordnung gespeichert; es gibt keine weitere Einschränkung
- [ ] **Szenario 3:** Saison ist Pflicht
    - **Gegeben** ich erfasse eine Zuordnung
    - **Wenn** ich keine Saison wähle
    - **Dann** wird nicht gespeichert und das Feld «Saison» wird markiert; als Vorgabe ist die aktuelle Saison gewählt
- [ ] **Szenario 4:** Mehrere Saisons
    - **Gegeben** Anna Muster ist 2025/26 Mitglied der Gruppe «Funktionäre»
    - **Wenn** ich sie zusätzlich für 2026/27 zuordne
    - **Dann** bestehen zwei getrennte Zuordnungen; beide erscheinen in ihrer Gruppenhistorie
- [ ] **Szenario 5:** Künftige Saison
    - **Gegeben** die Saison 2027/28 existiert
    - **Wenn** ich eine Person für 2027/28 zuordne
    - **Dann** wird die Zuordnung gespeichert und gilt erst ab Saisonbeginn als aktuell
- [ ] **Szenario 6:** Mehrere Gruppen
    - **Gegeben** Anna Muster ist in der Saison 2026/27 in der Gruppe «Funktionäre»
    - **Wenn** ich sie für dieselbe Saison zusätzlich der Gruppe «Helfer» zuordne
    - **Dann** gehört sie beiden Gruppen an; es gibt weiterhin nur einen Personen-Datensatz
- [ ] **Szenario 7:** Keine doppelte Zuordnung
    - **Gegeben** Anna Muster ist in der Saison 2026/27 der Gruppe «Funktionäre» zugeordnet
    - **Wenn** ich sie nochmals derselben Gruppe für dieselbe Saison zuordne
    - **Dann** wird abgelehnt mit der Meldung «Anna Muster ist in dieser Saison bereits dieser Gruppe zugeordnet»
- [ ] **Szenario 8:** Inaktive Gruppe
    - **Gegeben** die Gruppe «Alt» hat den Status «inaktiv»
    - **Wenn** ich eine Person dieser Gruppe zuordnen will
    - **Dann** wird die Gruppe nicht zur Auswahl angeboten bzw. mit der Meldung «Die Gruppe ist inaktiv» abgelehnt
- [ ] **Szenario 9:** Funktion erfassen
    - **Gegeben** ich ordne Anna Muster der Gruppe «Funktionäre» zu
    - **Wenn** ich als Funktion «Kassierin» eingebe
    - **Dann** wird in der Mitgliederliste «Kassierin» angezeigt; ohne Eingabe wird die Zuordnung trotzdem gespeichert
- [ ] **Szenario 10:** Zugehörigkeit zu Obergruppen
    - **Gegeben** «Geschäftsführerin» ist Untergruppe von «Funktionärin»
    - **Wenn** ich Anna Muster für 2026/27 «Geschäftsführerin» zuordne
    - **Dann** gehört sie in dieser Saison auch zu «Funktionärin», ohne dass ich sie dort separat zuordne
- [ ] **Szenario 11:** Direkte Zuordnung zur Obergruppe
    - **Gegeben** Anna Muster gehört in 2026/27 bereits transitiv zu «Funktionärin»
    - **Wenn** ich sie zusätzlich direkt «Funktionärin» zuordne
    - **Dann** wird die Zuordnung gespeichert; in der Mitgliederliste von «Funktionärin» erscheint sie nur einmal
- [ ] **Szenario 12:** Fehlende Berechtigung
    - **Gegeben** ich bin als Coach oder Spielerin angemeldet
    - **Wenn** ich Zuordnungen zu Gruppen erfassen will (ausser den Zuordnungen meines Teams gemäss Team-Epic)
    - **Dann** ist die Funktion nicht verfügbar bzw. wird mit «Keine Berechtigung» abgelehnt

## 🗂️ Datenanforderungen / Attribute

| Attribut | Typ         | Pflicht | Validierung / Regeln                                   | Beispiel         |
|----------|-------------|---------|--------------------------------------------------------|------------------|
| Gruppe   | Verknüpfung | ✅      | Status aktiv                                           | Funktionäre      |
| Person   | Verknüpfung | ✅      | natürliche oder juristische Person; bei Teams nur Teammitglieder | Anna Muster |
| Saison   | Verknüpfung | ✅      | Standard: aktuelle Saison; künftige Saisons erlaubt    | 2026/27          |
| Funktion | Text        | ❌      | Freitext, max. 100 Zeichen                             | Kassierin        |
| Ausgetreten | Datum    | ❌      | beim Zuordnen leer; siehe «Austritt aus einer Gruppe erfassen» | –        |

## 🛠️ Technische Hinweise

- Klasse `Membership` (`groupId`, `personId`, `seasonId`, `function`, …); eindeutig pro (`groupId`, `personId`, `seasonId`, bei Teams zusätzlich `role`).
- `personId` verweist auf die allgemeine Person und damit auf natürliche und juristische Personen.
- Zugehörigkeit zu Obergruppen wird abgeleitet (transitiv), nicht gespeichert.
- Validierung in Setter/DAO bzw. Backend, nicht nur im Frontend.
