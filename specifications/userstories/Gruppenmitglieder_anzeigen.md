---
name: 📘 Gruppenmitglieder anzeigen
about: Mitglieder einer Gruppe pro Saison und die Gruppen einer Person anzeigen
title: "[Story] Gruppenmitglieder anzeigen"
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** Geschäftsführer
**möchte ich** die Mitglieder einer Gruppe pro Saison sowie die Gruppen einer Person sehen,
**damit** ich prüfen kann, wer zu welcher Gruppe gehört bzw. gehört hat.

## 🧭 Kontext & Hintergrund

- Epic: Gruppenzuordnungen verwalten
- Für Gruppen der Art «Team» siehe «Teammitglieder anzeigen» (Sortierung nach Rolle, Shirtnummer).

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Mitglieder der aktuellen Saison
    - **Gegeben** die Gruppe «Funktionäre» hat in der aktuellen Saison Mitglieder
    - **Wenn** ich die Gruppe öffne
    - **Dann** sehe ich die Mitglieder der aktuellen Saison (natürliche Personen mit Vor- und Nachname, juristische Personen mit Name) mit Funktion, alphabetisch sortiert
- [ ] **Szenario 2:** Andere Saison
    - **Gegeben** die Gruppe hat Mitglieder in 2025/26 und 2026/27
    - **Wenn** ich die Saison 2025/26 wähle
    - **Dann** sehe ich die Mitglieder dieser Saison
- [ ] **Szenario 3:** Direkte und abgeleitete Mitglieder
    - **Gegeben** «Geschäftsführerin» ist Untergruppe von «Funktionärin»
    - **Wenn** ich die Mitglieder von «Funktionärin» anzeige
    - **Dann** sehe ich auch die Mitglieder der Untergruppen, jede Person nur einmal, mit Kennzeichnung der Herkunft («über Geschäftsführerin»)
- [ ] **Szenario 4:** Gruppen einer Person
    - **Gegeben** Anna Muster ist in 2026/27 in «Funktionäre» und «Helfer»
    - **Wenn** ich ihre Personendetails öffne
    - **Dann** sehe ich alle Gruppen nach Saison gegliedert, inklusive früherer Saisons, mit direkter und abgeleiteter Zugehörigkeit
- [ ] **Szenario 5:** Filter nach Personenart
    - **Gegeben** die Gruppe «Sponsoren» enthält natürliche und juristische Personen
    - **Wenn** ich nach «juristische Personen» filtere
    - **Dann** sehe ich nur diese

## 🚫 Nicht im Umfang (Out of Scope)

- Export und E-Mail-Versand an Gruppen
