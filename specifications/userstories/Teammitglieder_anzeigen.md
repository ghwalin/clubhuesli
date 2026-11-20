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
    - **Dann** sehe ich zuerst die Coaches, danach die Spielerinnen, je alphabetisch nach Nachname, mit Funktion im Team, Shirtnummer (nur Spielerinnen) und Eintrittsdatum
- [ ] **Szenario 2:** Ehemalige anzeigen
    - **Gegeben** im Team «U18» gibt es ausgetretene Spielerinnen
    - **Wenn** ich «Ehemalige anzeigen» wähle
    - **Dann** sehe ich auch die ehemaligen Mitglieder mit Ein- und Austrittsdatum
- [ ] **Szenario 3:** Künftige Mitglieder
    - **Gegeben** eine Spielerin hat ein Eintrittsdatum in der Zukunft
    - **Wenn** ich die Teamliste öffne
    - **Dann** wird sie separat als «ab TT.MM.JJJJ» angezeigt und zählt noch nicht zu den aktuellen Mitgliedern
- [ ] **Szenario 4:** Teams einer Person
    - **Gegeben** Lea Meier ist als Spielerin im Team «U18» und mit einem separaten Datensatz als Coach im Team «U12» erfasst
    - **Wenn** ich die Personendetails des jeweiligen Datensatzes öffne
    - **Dann** sehe ich die zugehörigen Teams (beim Spielerinnen-Datensatz «U18», beim Coach-Datensatz «U12») mit Rolle, Funktion im Team, Ein- und Austrittsdatum, inkl. früherer Teamzugehörigkeiten
- [ ] **Szenario 5:** Coach sieht nur eigene Teams
    - **Gegeben** ich bin als Coach des Teams «U12» angemeldet
    - **Wenn** ich die Teamlisten aufrufe
    - **Dann** sehe ich nur die Teamliste «U12»
- [ ] **Szenario 6:** Mitglieder einer Obergruppe
    - **Gegeben** die Gruppe «Spielerin» hat die Untergruppen «NLB» und «U18»
    - **Wenn** ich die Mitglieder der Gruppe «Spielerin» öffne
    - **Dann** sehe ich alle aktuellen Spielerinnen der Teams «NLB» und «U18», jede Person nur einmal, aber keine Coaches

## 🔒 Nicht-funktionale Anforderungen

- Coaches sehen in der Teamliste nur Name, Rolle, Funktion im Team, Ein-/Austrittsdatum und Kontaktdaten – keine AHV-Nummer.

## 🚫 Nicht im Umfang (Out of Scope)

- Export der Teamliste (z. B. PDF, Excel)
