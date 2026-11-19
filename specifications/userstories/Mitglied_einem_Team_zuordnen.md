---
name: 📘 Mitglied einem Team zuordnen
about: Ein Aktivmitglied als Spielerin oder Coach einem Team zuordnen
title: "[Story] Mitglied einem Team zuordnen"
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** Geschäftsführer
**möchte ich** ein Aktivmitglied mit Rolle, Funktion im Team und Eintrittsdatum einem Team zuordnen,
**damit** es ab diesem Datum zum Team gehört und für Trainings, Spiele und Team-E-Mails berücksichtigt wird.

## 🧭 Kontext & Hintergrund

- Epic: Teammitglieder verwalten (Grundregeln siehe dort)
- Spielerin (`ActivePlayer`) und Coach (`Coach`) sind getrennte Personen-Datensätze. Ist jemand beides, gibt es **zwei separate Gruppenzugehörigkeiten**, je eine pro Datensatz.
- Grundlage: `specifications/models/groups.md` (Mitgliedschaft: Gruppe, Person, Rolle, Funktion; Start-/Enddatum mehrfach pro Person und Gruppe)
- Verwandte Stories: Austritt aus einem Team erfassen, Natürliche Personen verwalten

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Spielerin einem Team zuordnen
    - **Gegeben** ich bin mit der Rolle Admin oder Geschäftsstelle angemeldet und Anna Muster ist ein aktives Aktivmitglied
    - **Wenn** ich sie dem Team «U18» mit Rolle «Spielerin» und Eintrittsdatum 01.08.2026 zuordne
    - **Dann** ist sie ab dem 01.08.2026 in der Teamliste «U18» als Spielerin aufgeführt
- [ ] **Szenario 2:** Coach einem Team zuordnen
    - **Gegeben** Lea Meier ist ein aktives Aktivmitglied
    - **Wenn** ich sie dem Team «U12» mit Rolle «Coach» und einem Eintrittsdatum zuordne
    - **Dann** ist sie in der Teamliste «U12» als Coach aufgeführt
- [ ] **Szenario 3:** Person als Spielerin und als Coach
    - **Gegeben** Lea Meier ist als Spielerin (Datensatz «Aktive Spielerin») bereits im Team «U18» und zusätzlich als Coach (separater Datensatz) erfasst
    - **Wenn** ich den Coach-Datensatz dem Team «U12» zuordne
    - **Dann** ist sie in «U18» als Spielerin und in «U12» als Coach aufgeführt; die beiden Zugehörigkeiten sind unabhängig voneinander
- [ ] **Szenario 4:** Nur Aktivmitglieder
    - **Gegeben** Peter Muster hat die Mitgliederart «Passiv» oder «Elternteil»
    - **Wenn** ich ihn einem Team zuordnen will
    - **Dann** wird er in der Auswahl nicht angeboten bzw. die Zuordnung wird mit der Meldung «Nur Aktivmitglieder können einem Team zugeordnet werden» abgelehnt
- [ ] **Szenario 5:** Keine ausgetretenen Personen
    - **Gegeben** ein Aktivmitglied ist aus dem Verein ausgetreten (Austrittsdatum erreicht)
    - **Wenn** ich es einem Team zuordnen will
    - **Dann** wird die Zuordnung abgelehnt
- [ ] **Szenario 6:** Keine doppelte Zuordnung
    - **Gegeben** Anna Muster ist seit dem 01.08.2026 Spielerin im Team «U18» (ohne Austrittsdatum)
    - **Wenn** ich sie nochmals als Spielerin dem Team «U18» mit Eintrittsdatum 01.01.2027 zuordne
    - **Dann** wird die Zuordnung mit der Meldung «Anna Muster ist in diesem Zeitraum bereits Spielerin in diesem Team» abgelehnt
- [ ] **Szenario 7:** Wiedereintritt
    - **Gegeben** Anna Muster war vom 01.08.2024 bis 30.06.2025 Spielerin im Team «U18»
    - **Wenn** ich sie mit Eintrittsdatum 01.08.2026 erneut als Spielerin zuordne
    - **Dann** werden beide Zeiträume gespeichert und in ihrer Teamhistorie angezeigt
- [ ] **Szenario 8:** Eintritt in der Zukunft
    - **Gegeben** ich plane das Kader für die nächste Saison
    - **Wenn** ich eine Spielerin mit einem Eintrittsdatum in der Zukunft zuordne
    - **Dann** wird die Zuordnung gespeichert, sie erscheint aber erst ab diesem Datum als aktuelles Teammitglied
- [ ] **Szenario 9:** Funktion im Team erfassen
    - **Gegeben** ich ordne Lea Meier als Coach dem Team «U12» zu
    - **Wenn** ich als Funktion im Team «Headcoach» eingebe und speichere
    - **Dann** wird sie in der Teamliste «U12» als «Coach – Headcoach» angezeigt
- [ ] **Szenario 10:** Ohne Funktion im Team
    - **Gegeben** ich ordne eine Spielerin einem Team zu
    - **Wenn** ich das Feld «Funktion im Team» leer lasse und speichere
    - **Dann** wird die Zuordnung trotzdem gespeichert und sie erscheint nur als «Spielerin»
- [ ] **Szenario 11:** Inaktives Team
    - **Gegeben** das Team «U10» hat den Status «inaktiv»
    - **Wenn** ich eine Person diesem Team zuordnen will
    - **Dann** wird das Team nicht zur Auswahl angeboten bzw. die Zuordnung wird mit der Meldung «Das Team ist inaktiv» abgelehnt
- [ ] **Szenario 12:** Nur Gruppen der Art «Team»
    - **Gegeben** die Gruppe «Materialverantwortliche» hat die Gruppenart «Funktionäre»
    - **Wenn** ich eine Teamzuordnung erfasse
    - **Dann** wird diese Gruppe nicht zur Auswahl angeboten
- [ ] **Szenario 13:** Mitglied gehört automatisch zu Obergruppen
    - **Gegeben** das Team «NLB» ist Untergruppe der Gruppe «Spielerin»
    - **Wenn** ich Anna Muster dem Team «NLB» zuordne
    - **Dann** gehört sie für denselben Zeitraum auch zur Gruppe «Spielerin», ohne dass ich sie dort separat zuordnen muss
- [ ] **Szenario 14:** Coaches gehören nicht zur Obergruppe «Spielerin»
    - **Gegeben** das Team «NLB» ist Untergruppe der Gruppe «Spielerin»
    - **Wenn** ich Lea Meier (Coach-Datensatz) dem Team «NLB» als Coach zuordne
    - **Dann** ist sie im Team «NLB» Coach, gehört aber nicht zur Gruppe «Spielerin»
- [ ] **Szenario 15:** Rolle passt zur Personenart
    - **Gegeben** Lea Meier ist nur als Coach erfasst (kein Datensatz «Aktive Spielerin»)
    - **Wenn** ich sie einem Team mit der Rolle «Spielerin» zuordnen will
    - **Dann** wird die Zuordnung mit der Meldung «Nur Aktive Spielerinnen können als Spielerin zugeordnet werden» abgelehnt (analog für Coach)
- [ ] **Szenario 16:** Shirtnummer erfassen
    - **Gegeben** ich ordne Anna Muster als Spielerin dem Team «U18» zu
    - **Wenn** ich die Shirtnummer 10 eingebe und speichere
    - **Dann** wird sie in der Teamliste «U18» mit der Shirtnummer 10 angezeigt
- [ ] **Szenario 17:** Shirtnummer im Team bereits vergeben
    - **Gegeben** im Team «U18» trägt Anna Muster die Shirtnummer 10
    - **Wenn** ich Lea Meier im selben Team (überschneidender Zeitraum) die Shirtnummer 10 gebe
    - **Dann** wird nicht gespeichert und ich sehe die Meldung «Shirtnummer 10 ist im Team bereits vergeben»; in einem anderen Team ist dieselbe Nummer erlaubt

## 🗂️ Datenanforderungen / Attribute

| Attribut       | Typ          | Pflicht | Validierung / Regeln                                          | Beispiel     |
|----------------|--------------|---------|---------------------------------------------------------------|--------------|
| Team           | Verknüpfung  | ✅      | nur Gruppen der Art «Team» mit Status aktiv                    | U18          |
| Person         | Verknüpfung  | ✅      | Aktive Spielerin (Rolle Spielerin) bzw. Coach (Rolle Coach); Mitgliederart «Aktiv», aktuell Vereinsmitglied | Anna Muster  |
| Rolle          | Enum         | ✅      | Spielerin / Coach; muss zur Personenart passen                | Spielerin    |
| Shirtnummer    | Zahl         | ❌      | nur Rolle Spielerin; ganze Zahl ≥ 0; im Team eindeutig (bei überschneidenden Zeiträumen) | 10 |
| Funktion im Team | Text       | ❌      | Freitext, max. 100 Zeichen                                    | Headcoach, Torhüterin |
| Eintrittsdatum | Datum        | ✅      | Standard: heute; Zukunft erlaubt                              | 01.08.2026   |
| Austrittsdatum | Datum        | ❌      | ≥ Eintrittsdatum; keine Überschneidung (siehe Epic, Regel 4) | 30.06.2027   |

## 🛠️ Technische Hinweise

- Abbildung als Klasse `Membership` (`groupId`, `personId`, `role`, `function`, `startDate`, `endDate`); dieselbe Person/Gruppe kann mehrere Mitgliedschaften (Zeiträume) haben.
- Die Zugehörigkeit zu Obergruppen wird aus den Untergruppen abgeleitet (transitiv), nicht als eigene Mitgliedschaft gespeichert; Coach-Mitgliedschaften werden nicht in die Obergruppe «Spielerin» übernommen.
- Die Shirtnummer ist ein Attribut der Mitgliedschaft (nur Rolle Spielerin); die Eindeutigkeit wird im DAO/Backend geprüft.
- Die Funktion im Team entspricht dem Modellattribut «Funktion (Freitext)».
- Die Prüfungen «nur Aktivmitglieder», «nur aktive Teams» und «keine Überschneidung» gehören in die Setter bzw. ins DAO/Backend, nicht nur ins Frontend.

## ❓ Offene Fragen

- Kann eine Person im selben Team gleichzeitig Spielerin **und** Coach sein (spielende Coach)? Mit getrennten Datensätzen ist das möglich.
- Modell-Frage: Soll die Zugehörigkeit pro Saison statt mit Start-/Enddatum erfasst werden?
- Soll eine Coach-Zuordnung automatisch die Benutzerrolle «Coach» (Berechtigung für Trainings und An-/Abmeldungen) vergeben?
