---
name: 📘 Gruppenzuordnung löschen
about: Fehlerhafte Zuordnung einer Person zu einer Gruppe nach Warnung löschen
title: "[Story] Gruppenzuordnung löschen"
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** Geschäftsführer
**möchte ich** eine fehlerhafte Gruppenzuordnung löschen,
**damit** die Mitgliederlisten und die Historie wieder stimmen.

## 🧭 Kontext & Hintergrund

- Epic: Gruppenzuordnungen verwalten (Grundregel 7)
- Löschen ist **nur zur Korrektur von Fehlern** gedacht. Ein regulärer Abgang wird über das Austrittsdatum oder durch Nicht-Zuordnung in der Folgesaison abgebildet, damit die Historie erhalten bleibt.
- Für Teams: siehe auch «Teamzugehörigkeit korrigieren oder löschen» (gleiche Warnung).

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Löschen mit Warnung
    - **Gegeben** Anna Muster wurde irrtümlich der Gruppe «Funktionäre» für 2026/27 zugeordnet
    - **Wenn** ich «Zuordnung löschen» wähle
    - **Dann** erscheint die Warnung «Zuordnungen sollten nur gelöscht werden, wenn sie fehlerhaft erfasst wurden. Bei einem regulären Abgang bitte stattdessen den Austritt erfassen. Wirklich löschen?» mit den Optionen «Abbrechen» und «Löschen»
- [ ] **Szenario 2:** Löschen bestätigen
    - **Gegeben** die Warnung wird angezeigt
    - **Wenn** ich «Löschen» bestätige
    - **Dann** ist die Zuordnung entfernt und erscheint weder in der Mitgliederliste der Gruppe noch in der Gruppenhistorie der Person; die Personendaten bleiben erhalten
- [ ] **Szenario 3:** Löschen abbrechen
    - **Gegeben** die Warnung wird angezeigt
    - **Wenn** ich «Abbrechen» wähle
    - **Dann** bleibt die Zuordnung unverändert
- [ ] **Szenario 4:** Auswirkung auf Obergruppen
    - **Gegeben** Anna Muster gehört nur über die Untergruppe «Geschäftsführerin» zu «Funktionärin»
    - **Wenn** ich die Zuordnung zu «Geschäftsführerin» lösche
    - **Dann** gehört sie in dieser Saison auch nicht mehr zu «Funktionärin»
- [ ] **Szenario 5:** Obergruppe bleibt bei weiterer Zuordnung
    - **Gegeben** Anna Muster gehört über «Geschäftsführerin» und «Webmaster» zu «Funktionärin»
    - **Wenn** ich die Zuordnung zu «Geschäftsführerin» lösche
    - **Dann** gehört sie weiterhin zu «Funktionärin» (über «Webmaster»)
- [ ] **Szenario 6:** Vergangene Saison und inaktive Gruppe
    - **Gegeben** eine Zuordnung betrifft eine frühere Saison oder eine inaktive Gruppe
    - **Wenn** ich sie lösche und bestätige
    - **Dann** wird sie ebenfalls entfernt
- [ ] **Szenario 7:** Fehlende Berechtigung
    - **Gegeben** ich bin nicht Admin oder Geschäftsstelle
    - **Wenn** ich eine Zuordnung löschen will
    - **Dann** ist die Funktion nicht verfügbar bzw. wird abgelehnt

## 🚫 Nicht im Umfang (Out of Scope)

- Löschen von Personen oder Gruppen
- Änderungsprotokoll / Wiederherstellen gelöschter Zuordnungen

## ❓ Offene Fragen

- Was passiert beim Löschen mit abhängigen Daten (z. B. Teilnahmen an Anlässen des Teams, siehe `specifications/models/teamplan.md`)?
