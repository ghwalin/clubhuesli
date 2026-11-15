---
name: 📘 Teammitglieder anzeigen
about: Aktuelle und ehemalige Mitglieder eines Teams sowie die Teams einer Person anzeigen
title: "[Story] Teammitglieder anzeigen"
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** Geschäftsführer oder Coach
**möchte ich** die aktuellen und ehemaligen Mitglieder eines Teams sowie die Teams einer Person sehen,
**damit** ich weiss, wer zu welchem Team gehört bzw. gehört hat.

## 🧭 Kontext & Hintergrund

- Epic: Teammitglieder verwalten (Grundregeln siehe dort)
- Coaches dürfen nur die Teamlisten ihrer eigenen Teams sehen.

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Aktuelle Teamliste
    - **Gegeben** das Team «U18» hat 2 Coaches und 14 aktuelle Spielerinnen
    - **Wenn** ich die Teamliste «U18» öffne
    - **Dann** sehe ich zuerst die Coaches, danach die Spielerinnen, je alphabetisch nach Nachname, mit Funktion im Team und Eintrittsdatum
- [ ] **Szenario 2:** Ehemalige anzeigen
    - **Gegeben** im Team «U18» gibt es ausgetretene Spielerinnen
    - **Wenn** ich «Ehemalige anzeigen» wähle
    - **Dann** sehe ich auch die ehemaligen Mitglieder mit Ein- und Austrittsdatum
- [ ] **Szenario 3:** Künftige Mitglieder
    - **Gegeben** eine Spielerin hat ein Eintrittsdatum in der Zukunft
    - **Wenn** ich die Teamliste öffne
    - **Dann** wird sie separat als «ab TT.MM.JJJJ» angezeigt und zählt noch nicht zu den aktuellen Mitgliedern
- [ ] **Szenario 4:** Teams einer Person
    - **Gegeben** Lea Meier ist Spielerin im Team «U18» und Coach im Team «U12»
    - **Wenn** ich ihre Personendetails öffne
    - **Dann** sehe ich beide Teams mit Rolle, Funktion im Team, Ein- und Austrittsdatum, inkl. früherer Teamzugehörigkeiten
- [ ] **Szenario 5:** Coach sieht nur eigene Teams
    - **Gegeben** ich bin als Coach des Teams «U12» angemeldet
    - **Wenn** ich die Teamlisten aufrufe
    - **Dann** sehe ich nur die Teamliste «U12»

## 🔒 Nicht-funktionale Anforderungen

- Coaches sehen in der Teamliste nur Name, Rolle, Funktion im Team, Ein-/Austrittsdatum und Kontaktdaten – keine AHV-Nummer.

## 🚫 Nicht im Umfang (Out of Scope)

- Export der Teamliste (z. B. PDF, Excel)
