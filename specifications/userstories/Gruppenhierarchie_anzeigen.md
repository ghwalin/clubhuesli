---
name: 📘 Gruppenhierarchie anzeigen
about: Gruppen und ihre Ober-/Untergruppen als Liste und Baum anzeigen
title: "[Story] Gruppenhierarchie anzeigen"
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** Geschäftsführer
**möchte ich** alle Gruppen mit ihren Ober- und Untergruppen als Liste und Baum sehen und filtern,
**damit** ich die Struktur des Vereins überblicke und Fehler in der Zuordnung schnell erkenne.

## 🧭 Kontext & Hintergrund

- Epic: Gruppen verwalten
- Verwandte Stories: Ober- und Untergruppen zuordnen, Gruppe erfassen und bearbeiten

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Gruppenliste
    - **Gegeben** es existieren mehrere Gruppen
    - **Wenn** ich die Gruppenübersicht öffne
    - **Dann** sehe ich pro Gruppe Bezeichnung, Gruppenart, Status sowie die direkten Ober- und Untergruppen
- [ ] **Szenario 2:** Baumansicht
    - **Gegeben** «Funktionärin» hat die Untergruppen «Geschäftsführerin» und «Webmaster»
    - **Wenn** ich die Baumansicht wähle
    - **Dann** werden die Untergruppen unter «Funktionärin» eingerückt dargestellt; Gruppen mit mehreren Obergruppen erscheinen unter jeder davon
- [ ] **Szenario 3:** Filter
    - **Gegeben** es gibt Gruppen verschiedener Arten und Status
    - **Wenn** ich nach Gruppenart «Team» und Status «aktiv» filtere
    - **Dann** sehe ich nur die passenden Gruppen
- [ ] **Szenario 4:** Gruppen ohne Beziehung
    - **Gegeben** die Gruppe «Förderverein» hat weder Ober- noch Untergruppen
    - **Wenn** ich die Baumansicht öffne
    - **Dann** erscheint sie als Wurzelgruppe

## 🚫 Nicht im Umfang (Out of Scope)

- Anzeige der Mitglieder (siehe «Teammitglieder anzeigen»)
- Drag-and-drop zum Umhängen von Gruppen
