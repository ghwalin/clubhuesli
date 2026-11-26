---
name: 📘 Mitglied einem Team zuordnen
about: Ein Teammitglied für eine Saison als Spielerin oder Coach einem Team zuordnen
title: "[Story] Mitglied einem Team zuordnen"
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** Geschäftsführer
**möchte ich** ein Teammitglied mit Saison, Rolle und Funktion im Team einem Team zuordnen,
**damit** es in dieser Saison zum Team gehört und für Trainings, Spiele und Team-E-Mails berücksichtigt wird.

## 🧭 Kontext & Hintergrund

- Epic: Teammitglieder verwalten (Grundregeln siehe dort)
- Spielerinnen und Coaches sind **Teammitglieder** mit **einem** Personen-Datensatz. Die Rolle (Spielerin/Coach) wird pro Mitgliedschaft festgelegt.
- Grundlage: `specifications/models/groups.md` (Mitgliedschaft: Gruppe, Person, Saison, Rolle, Funktion, Shirtnummer, Ausgetreten)
- Verwandte Stories: Saisons verwalten, Austritt aus einem Team erfassen, Natürliche Personen verwalten

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Spielerin einem Team zuordnen
    - **Gegeben** ich bin mit der Rolle Admin oder Geschäftsstelle angemeldet und Anna Muster ist ein Teammitglied mit Mitgliederart «Aktiv»
    - **Wenn** ich sie dem Team «U18» für die Saison 2026/27 mit Rolle «Spielerin» zuordne
    - **Dann** ist sie in der Teamliste «U18» der Saison 2026/27 als Spielerin aufgeführt
- [ ] **Szenario 2:** Coach einem Team zuordnen
    - **Gegeben** Lea Meier ist ein Teammitglied mit Mitgliederart «Aktiv»
    - **Wenn** ich sie dem Team «U12» für die Saison 2026/27 mit Rolle «Coach» zuordne
    - **Dann** ist sie in der Teamliste «U12» der Saison 2026/27 als Coach aufgeführt
- [ ] **Szenario 3:** Person als Spielerin und als Coach
    - **Gegeben** Lea Meier ist in der Saison 2026/27 Spielerin im Team «U18»
    - **Wenn** ich sie für dieselbe Saison zusätzlich als Coach dem Team «U12» zuordne
    - **Dann** ist sie in «U18» als Spielerin und in «U12» als Coach aufgeführt; es gibt weiterhin nur einen Personen-Datensatz
- [ ] **Szenario 4:** Nur Teammitglieder mit Mitgliederart «Aktiv»
    - **Gegeben** Peter Muster hat die Mitgliederart «Passiv» oder «Elternteil» bzw. ist kein Teammitglied
    - **Wenn** ich ihn einem Team zuordnen will
    - **Dann** wird er in der Auswahl nicht angeboten bzw. die Zuordnung wird mit der Meldung «Nur aktive Teammitglieder können einem Team zugeordnet werden» abgelehnt
- [ ] **Szenario 4b:** Juristische Person
    - **Gegeben** «Muster AG» ist eine juristische Person
    - **Wenn** ich sie einem Team zuordnen will
    - **Dann** wird sie nicht angeboten bzw. abgelehnt mit der Meldung «Nur natürliche Personen können Mitglied eines Teams sein»
- [ ] **Szenario 5:** Keine doppelte Zuordnung
    - **Gegeben** Anna Muster ist in der Saison 2026/27 Spielerin im Team «U18»
    - **Wenn** ich sie nochmals als Spielerin dem Team «U18» für die Saison 2026/27 zuordne
    - **Dann** wird die Zuordnung mit der Meldung «Anna Muster ist in dieser Saison bereits Spielerin in diesem Team» abgelehnt
- [ ] **Szenario 6:** Mehrere Saisons
    - **Gegeben** Anna Muster war in der Saison 2025/26 Spielerin im Team «U18»
    - **Wenn** ich sie für die Saison 2026/27 erneut als Spielerin dem Team «U18» zuordne
    - **Dann** werden beide Saisons gespeichert und in ihrer Teamhistorie angezeigt
- [ ] **Szenario 7:** Zuordnung für eine künftige Saison
    - **Gegeben** ich plane das Kader für die nächste Saison 2027/28
    - **Wenn** ich eine Spielerin für die Saison 2027/28 zuordne
    - **Dann** wird die Zuordnung gespeichert, sie erscheint aber erst ab Beginn der Saison als aktuelles Teammitglied
- [ ] **Szenario 8:** Funktion im Team erfassen
    - **Gegeben** ich ordne Lea Meier als Coach dem Team «U12» zu
    - **Wenn** ich als Funktion im Team «Headcoach» eingebe und speichere
    - **Dann** wird sie in der Teamliste «U12» als «Coach – Headcoach» angezeigt
- [ ] **Szenario 9:** Ohne Funktion im Team
    - **Gegeben** ich ordne eine Spielerin einem Team zu
    - **Wenn** ich das Feld «Funktion im Team» leer lasse und speichere
    - **Dann** wird die Zuordnung trotzdem gespeichert und sie erscheint nur als «Spielerin»
- [ ] **Szenario 10:** Inaktives Team
    - **Gegeben** das Team «U10» hat den Status «inaktiv»
    - **Wenn** ich eine Person diesem Team zuordnen will
    - **Dann** wird das Team nicht zur Auswahl angeboten bzw. die Zuordnung wird mit der Meldung «Das Team ist inaktiv» abgelehnt
- [ ] **Szenario 11:** Nur Gruppen der Art «Team»
    - **Gegeben** die Gruppe «Materialverantwortliche» hat die Gruppenart «Funktionäre»
    - **Wenn** ich eine Teamzuordnung erfasse
    - **Dann** wird diese Gruppe nicht zur Auswahl angeboten
- [ ] **Szenario 12:** Shirtnummer erfassen
    - **Gegeben** ich ordne Anna Muster als Spielerin dem Team «U18» für die Saison 2026/27 zu
    - **Wenn** ich die Shirtnummer 10 eingebe und speichere
    - **Dann** wird sie in der Teamliste «U18» mit der Shirtnummer 10 angezeigt
- [ ] **Szenario 13:** Ohne Shirtnummer
    - **Gegeben** ich ordne eine Spielerin einem Team zu
    - **Wenn** ich keine Shirtnummer eingebe und speichere
    - **Dann** wird die Zuordnung gespeichert und in der Teamliste ohne Shirtnummer angezeigt
- [ ] **Szenario 14:** Gleiche Shirtnummer im Team
    - **Gegeben** im Team «U18» trägt Anna Muster in der Saison 2026/27 die Shirtnummer 10
    - **Wenn** ich Lea Meier im selben Team und in derselben Saison ebenfalls die Shirtnummer 10 gebe
    - **Dann** wird die Zuordnung gespeichert; die Eindeutigkeit der Shirtnummer wird nicht geprüft
- [ ] **Szenario 15:** Shirtnummer nur bei Spielerinnen
    - **Gegeben** ich ordne Lea Meier als Coach einem Team zu
    - **Wenn** ich die Zuordnung erfasse
    - **Dann** wird das Feld Shirtnummer nicht angeboten
- [ ] **Szenario 16:** Mitglied gehört automatisch zu Obergruppen
    - **Gegeben** das Team «U18» ist Untergruppe der Gruppe «Juniorinnen»
    - **Wenn** ich Anna Muster für die Saison 2026/27 dem Team «U18» zuordne
    - **Dann** gehört sie in der Saison 2026/27 auch zur Gruppe «Juniorinnen», ohne dass ich sie dort separat zuordnen muss; dasselbe gilt für Coaches

## 🗂️ Datenanforderungen / Attribute

| Attribut         | Typ          | Pflicht | Validierung / Regeln                                          | Beispiel     |
|------------------|--------------|---------|---------------------------------------------------------------|--------------|
| Team             | Verknüpfung  | ✅      | nur Gruppen der Art «Team» mit Status aktiv                    | U18          |
| Person           | Verknüpfung  | ✅      | Teammitglied mit Mitgliederart «Aktiv»                        | Anna Muster  |
| Saison           | Verknüpfung  | ✅      | Standard: aktuelle Saison; künftige Saisons erlaubt           | 2026/27      |
| Rolle            | Enum         | ✅      | Spielerin / Coach                                             | Spielerin    |
| Funktion im Team | Text         | ❌      | Freitext, max. 100 Zeichen                                    | Headcoach, Torhüterin |
| Shirtnummer      | Zahl         | ❌      | nur Rolle Spielerin; ganze Zahl ≥ 0; keine Prüfung auf Eindeutigkeit | 10    |
| Ausgetreten      | Datum        | ❌      | beim Zuordnen leer; siehe «Austritt aus einem Team erfassen»  | –            |

## 🛠️ Technische Hinweise

- Abbildung als Klasse `Membership` (`groupId`, `personId`, `seasonId`, `role`, `function`, `shirtNumber`, `exitDate`); eindeutig pro (`groupId`, `personId`, `seasonId`, `role`).
- Die Zugehörigkeit zu Obergruppen wird aus den Untergruppen abgeleitet (transitiv), nicht als eigene Mitgliedschaft gespeichert.
- Die Shirtnummer ist ein Attribut der Mitgliedschaft (nur Rolle Spielerin); ihre Eindeutigkeit wird weder im Code noch in der Datenbank geprüft.
- Die Funktion im Team entspricht dem Modellattribut «Funktion (Freitext)».
- Die Prüfungen «nur aktive Teammitglieder», «nur aktive Teams» und «keine doppelte Zuordnung» gehören in die Setter bzw. ins DAO/Backend, nicht nur ins Frontend.

## ❓ Offene Fragen

- Kann eine Person im selben Team und in derselben Saison gleichzeitig Spielerin **und** Coach sein (spielende Coach)? Gemäss Regel 5 wäre das erlaubt.
- Soll eine Coach-Zuordnung automatisch die Benutzerrolle «Coach» (Berechtigung für Trainings und An-/Abmeldungen) vergeben?
