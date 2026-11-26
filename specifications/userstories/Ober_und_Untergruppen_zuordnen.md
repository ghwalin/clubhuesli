---
name: 📘 Ober- und Untergruppen zuordnen
about: Gruppen hierarchisch verknüpfen (n:n, zyklenfrei)
title: "[Story] Ober- und Untergruppen zuordnen"
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** Geschäftsführer
**möchte ich** einer Gruppe Ober- und Untergruppen zuordnen und diese Zuordnung wieder aufheben,
**damit** die Vereinsstruktur abgebildet ist (z. B. «Geschäftsführerin» ist Untergruppe von «Funktionärin») und Mitglieder automatisch zu den Obergruppen gehören.

## 🧭 Kontext & Hintergrund

- Epic: Gruppen verwalten (Grundregeln 2–5)
- Mehrfachvererbung (Raute) ist erlaubt, solange kein Zyklus entsteht.
- Grundlage: `specifications/models/groups.md` (Beziehungen: Gruppen können Gruppen enthalten, n:n; Mitgliedschaft transitiv)
- Verwandte Stories: Gruppenhierarchie anzeigen, Mitglied einem Team zuordnen

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Obergruppe zuordnen
    - **Gegeben** die aktiven Gruppen «Geschäftsführerin» und «Funktionärin» existieren
    - **Wenn** ich «Funktionärin» als Obergruppe von «Geschäftsführerin» wähle und speichere
    - **Dann** erscheint «Geschäftsführerin» als Untergruppe von «Funktionärin» (und umgekehrt als Obergruppe)
- [ ] **Szenario 2:** Untergruppe zuordnen
    - **Gegeben** die Gruppe «Funktionärin» existiert
    - **Wenn** ich «Webmaster» als Untergruppe hinzufüge
    - **Dann** ist «Webmaster» Untergruppe von «Funktionärin»
- [ ] **Szenario 3:** Mehrere Obergruppen
    - **Gegeben** «Materialverantwortlicher» ist Untergruppe von «Funktionärin»
    - **Wenn** ich zusätzlich «Vorstand» als Obergruppe zuordne
    - **Dann** hat die Gruppe beide Obergruppen
- [ ] **Szenario 3b:** Raute
    - **Gegeben** «B» und «C» sind Untergruppen von «A»
    - **Wenn** ich «D» als Untergruppe sowohl von «B» als auch von «C» zuordne
    - **Dann** werden beide Zuordnungen gespeichert; Mitglieder von «D» gehören (einmalig, ohne Duplikate) zu «B», «C» und «A»
- [ ] **Szenario 4:** Zuordnung aufheben
    - **Gegeben** «Webmaster» ist Untergruppe von «Funktionärin»
    - **Wenn** ich die Zuordnung entferne
    - **Dann** sind beide Gruppen unverändert vorhanden, aber nicht mehr verknüpft; Mitglieder von «Webmaster» gehören nicht mehr zu «Funktionärin»
- [ ] **Szenario 5:** Zyklus verhindern
    - **Gegeben** «Geschäftsführerin» ist Untergruppe von «Funktionärin»
    - **Wenn** ich «Geschäftsführerin» als Obergruppe von «Funktionärin» zuordnen will
    - **Dann** wird abgelehnt mit der Meldung «Die Zuordnung würde einen Zyklus in der Hierarchie erzeugen»
- [ ] **Szenario 6:** Selbstzuordnung verhindern
    - **Gegeben** ich bearbeite die Gruppe «Funktionärin»
    - **Wenn** ich sie sich selbst als Ober- oder Untergruppe zuordnen will
    - **Dann** wird die Gruppe in der Auswahl nicht angeboten bzw. abgelehnt
- [ ] **Szenario 7:** Doppelte Zuordnung
    - **Gegeben** «Webmaster» ist bereits Untergruppe von «Funktionärin»
    - **Wenn** ich dieselbe Zuordnung nochmals erfasse
    - **Dann** wird sie nicht doppelt gespeichert (Meldung «Die Zuordnung existiert bereits»)
- [ ] **Szenario 8:** Inaktive Gruppe
    - **Gegeben** die Gruppe «Alt» ist inaktiv
    - **Wenn** ich sie neu mit einer anderen Gruppe verknüpfen will
    - **Dann** wird sie in der Auswahl nicht angeboten
- [ ] **Szenario 9:** Transitive Mitgliedschaft
    - **Gegeben** Anna ist in der Saison 2026/27 Mitglied von «Geschäftsführerin», diese ist Untergruppe von «Funktionärin»
    - **Wenn** ich die Mitglieder von «Funktionärin» für 2026/27 anzeige
    - **Dann** ist Anna aufgeführt

## 🗂️ Datenanforderungen / Attribute

| Attribut    | Typ    | Pflicht | Validierung / Regeln                                      | Beispiel         |
|-------------|--------|---------|-----------------------------------------------------------|------------------|
| Obergruppe  | Gruppe | ❌      | aktiv, nicht sich selbst/Untergruppe (zyklenfrei), n:n    | Funktionärin     |
| Untergruppe | Gruppe | ❌      | aktiv, nicht sich selbst/Obergruppe (zyklenfrei), n:n     | Geschäftsführerin|

## 🛠️ Technische Hinweise

- Verknüpfungstabelle `group_group` (parent_id, child_id) mit eindeutigem Schlüssel
- Zyklenprüfung: Obergruppe darf nicht in der transitiven Untergruppen-Menge der Gruppe liegen

## 🚫 Nicht im Umfang (Out of Scope)

- Personen einer Gruppe zuordnen
- Grafische Darstellung der Hierarchie (siehe «Gruppenhierarchie anzeigen»)
