---
name: 📘 Teammitglieder anzeigen
about: Aktuelle und ehemalige Mitglieder eines Teams sowie die Teams einer Person anzeigen
title: "[Story] Teammitglieder anzeigen"
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** Geschäftsführer oder Coach
**möchte ich** die Mitglieder eines Teams pro Saison sowie die Teams einer Person sehen,
**damit** ich weiss, wer zu welchem Team gehört bzw. gehört hat.

## 🧭 Kontext & Hintergrund

- Epic: Teammitglieder verwalten (Grundregeln siehe dort)
- Coaches dürfen nur die Teamlisten ihrer eigenen Teams sehen.

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Aktuelle Teamliste
    - **Gegeben** das Team «U18» hat in der aktuellen Saison 2 Coaches und 14 Spielerinnen
    - **Wenn** ich die Teamliste «U18» öffne
    - **Dann** sehe ich die Mitglieder der aktuellen Saison: zuerst die Coaches, danach die Spielerinnen, je alphabetisch nach Nachname, mit Funktion im Team und Shirtnummer (nur Spielerinnen)
- [ ] **Szenario 2:** Andere Saison anzeigen
    - **Gegeben** das Team «U18» hat Mitglieder in den Saisons 2025/26 und 2026/27
    - **Wenn** ich in der Teamliste die Saison 2025/26 auswähle
    - **Dann** sehe ich die Mitglieder der Saison 2025/26
- [ ] **Szenario 3:** Ehemalige anzeigen
    - **Gegeben** im Team «U18» gibt es Spielerinnen, die in früheren Saisons Mitglied waren oder während der aktuellen Saison ausgetreten sind
    - **Wenn** ich «Ehemalige anzeigen» wähle
    - **Dann** sehe ich auch die ehemaligen Mitglieder mit den Saisons, in denen sie im Team waren, und gegebenenfalls dem Austrittsdatum
- [ ] **Szenario 4:** Teams einer Person
    - **Gegeben** Lea Meier ist in der Saison 2026/27 Spielerin im Team «U18» und Coach im Team «U12»
    - **Wenn** ich ihre Personendetails öffne
    - **Dann** sehe ich beide Teams mit Saison, Rolle, Funktion im Team und gegebenenfalls Austrittsdatum, inkl. Teamzugehörigkeiten früherer Saisons
- [ ] **Szenario 5:** Coach sieht nur eigene Teams
    - **Gegeben** ich bin als Coach des Teams «U12» angemeldet
    - **Wenn** ich die Teamlisten aufrufe
    - **Dann** sehe ich nur die Teamliste «U12»
- [ ] **Szenario 6:** Mitglieder einer Obergruppe
    - **Gegeben** die Gruppe «Juniorinnen» hat die Untergruppen «U12» und «U18»
    - **Wenn** ich die Mitglieder der Gruppe «Juniorinnen» für die aktuelle Saison öffne
    - **Dann** sehe ich alle aktuellen Mitglieder (Spielerinnen und Coaches) der Teams «U12» und «U18», jede Person nur einmal

## 🔒 Nicht-funktionale Anforderungen

- Coaches sehen in der Teamliste nur Name, Rolle, Funktion im Team, Shirtnummer, Saison, Austrittsdatum und Kontaktdaten – keine AHV-Nummer.

## 🚫 Nicht im Umfang (Out of Scope)

- Export der Teamliste (z. B. PDF, Excel)
