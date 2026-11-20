---
name: 📘 Saisons verwalten
about: Saisons mit Start- und Enddatum erfassen, auf die sich Gruppenmitgliedschaften beziehen
title: "[Story] Saisons verwalten"
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** Geschäftsführer
**möchte ich** Saisons mit Bezeichnung, Start- und Enddatum erfassen,
**damit** ich Personen pro Saison den Teams und Gruppen zuordnen kann.

## 🧭 Kontext & Hintergrund

- Epic: Teammitglieder verwalten (Grundregeln siehe dort)
- Grundlage: `specifications/models/groups.md` (Saison: Bezeichnung, Startdatum, Enddatum)
- Die Saison mit heutigem Datum zwischen Start- und Enddatum ist die **aktuelle Saison**.

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Saison anlegen
    - **Gegeben** ich bin mit der Rolle Admin oder Geschäftsstelle angemeldet
    - **Wenn** ich die Saison «2026/27» mit Startdatum 01.07.2026 und Enddatum 30.06.2027 erfasse und speichere
    - **Dann** ist die Saison gespeichert und kann bei Team- und Gruppenzuordnungen ausgewählt werden
- [ ] **Szenario 2:** Enddatum vor Startdatum
    - **Gegeben** ich erfasse eine Saison
    - **Wenn** das Enddatum vor dem Startdatum liegt
    - **Dann** wird nicht gespeichert und ich sehe die Meldung «Enddatum darf nicht vor dem Startdatum liegen»
- [ ] **Szenario 3:** Überschneidende Saisons
    - **Gegeben** die Saison «2026/27» dauert vom 01.07.2026 bis 30.06.2027
    - **Wenn** ich eine Saison mit Startdatum 01.06.2027 erfasse
    - **Dann** wird nicht gespeichert und ich sehe die Meldung «Die Saison überschneidet sich mit der Saison 2026/27»
- [ ] **Szenario 4:** Doppelte Bezeichnung
    - **Gegeben** die Saison «2026/27» existiert
    - **Wenn** ich eine weitere Saison «2026/27» erfasse
    - **Dann** wird nicht gespeichert und ich sehe die Meldung «Diese Saison existiert bereits»
- [ ] **Szenario 5:** Saison mit Mitgliedschaften löschen
    - **Gegeben** der Saison «2025/26» sind Gruppenmitgliedschaften zugeordnet
    - **Wenn** ich die Saison löschen will
    - **Dann** wird das Löschen mit der Meldung «Die Saison wird noch verwendet» abgelehnt

## 🗂️ Datenanforderungen / Attribute

| Attribut    | Typ   | Pflicht | Validierung / Regeln                         | Beispiel   |
|-------------|-------|---------|----------------------------------------------|------------|
| Bezeichnung | Text  | ✅      | eindeutig; z. B. Format JJJJ/JJ              | 2026/27    |
| Startdatum  | Datum | ✅      | keine Überschneidung mit anderen Saisons     | 01.07.2026 |
| Enddatum    | Datum | ✅      | ≥ Startdatum                                 | 30.06.2027 |

## ❓ Offene Fragen

- Sollen Saisons automatisch angelegt werden (z. B. jeweils 01.07.–30.06.)?
- Braucht es eine Funktion «Mitgliedschaften aus Vorsaison übernehmen» beim Anlegen einer neuen Saison?
