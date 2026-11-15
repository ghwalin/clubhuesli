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
- [ ] **Szenario 3:** Mitglied in mehreren Teams
    - **Gegeben** Lea Meier ist bereits Spielerin im Team «U18»
    - **Wenn** ich sie zusätzlich als Coach dem Team «U12» zuordne
    - **Dann** ist sie in beiden Teams mit der jeweiligen Rolle aufgeführt
- [ ] **Szenario 4:** Nur Aktivmitglieder
    - **Gegeben** Peter Muster hat die Mitgliederart «Passiv» oder «Elternteil»
    - **Wenn** ich ihn einem Team zuordnen will
    - **Dann** wird er in der Auswahl nicht angeboten bzw. die Zuordnung wird mit der Meldung «Nur Aktivmitglieder können einem Team zugeordnet werden» abgelehnt
- [ ] **Szenario 5:** Keine inaktiven Personen
    - **Gegeben** ein Aktivmitglied ist deaktiviert
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

## 🗂️ Datenanforderungen / Attribute

| Attribut       | Typ          | Pflicht | Validierung / Regeln                                          | Beispiel     |
|----------------|--------------|---------|---------------------------------------------------------------|--------------|
| Team           | Verknüpfung  | ✅      | nur Gruppen der Art «Team»                                     | U18          |
| Person         | Verknüpfung  | ✅      | Mitgliederart «Aktiv», Status aktiv                           | Anna Muster  |
| Rolle          | Enum         | ✅      | Spielerin / Coach                                             | Spielerin    |
| Funktion im Team | Text       | ❌      | Freitext, max. 100 Zeichen                                    | Headcoach, Torhüterin |
| Eintrittsdatum | Datum        | ✅      | Standard: heute; Zukunft erlaubt                              | 01.08.2026   |
| Austrittsdatum | Datum        | ❌      | ≥ Eintrittsdatum; keine Überschneidung (siehe Epic, Regel 4) | 30.06.2027   |

## 🛠️ Technische Hinweise

- Abbildung in `group_member` (`group_id`, `person_id`, `role_id`, `start_date`, `end_date`).
- Für die Funktion im Team braucht `group_member` ein neues Feld, z. B. `team_function VARCHAR(100) NULL`.
- Die Rollen heissen in der Tabelle `role` «Spielerin» und «Coach» (`is_coach = 1`).
- Die Prüfungen «nur Aktivmitglieder» und «keine Überschneidung» gehören ins Backend (Setter bzw. DAO), nicht nur ins Frontend.

## ❓ Offene Fragen

- Kann eine Person im selben Team gleichzeitig Spielerin **und** Coach sein (spielende Coach)? Nach Regel 4 wäre das erlaubt, da es verschiedene Rollen sind.
- Soll eine Coach-Zuordnung automatisch die Benutzerrolle «Coach» (Berechtigung für Trainings und An-/Abmeldungen) vergeben?
