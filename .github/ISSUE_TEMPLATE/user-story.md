---
name: 📘 User Story
about: Beschreibe eine neue Anforderung aus Sicht der Benutzerin oder des Benutzers
title: "[Story] "
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** <Rolle / Persona>
**möchte ich** <Ziel / Funktion>,
**damit** <Nutzen / Mehrwert>.

## 🧭 Kontext & Hintergrund

<!-- Warum ist diese Story wichtig? Welches Problem wird gelöst? Links zu Epics, Diskussionen, Designs usw. -->

- Epic: #
- Verwandte Issues: #

## ✅ Akzeptanzkriterien

<!-- Möglichst konkret und testbar formulieren, z. B. im Given/When/Then-Format. -->

- [ ] **Szenario 1:** <Titel>
    - **Gegeben** <Ausgangssituation>
    - **Wenn** <Aktion>
    - **Dann** <erwartetes Ergebnis>
- [ ] **Szenario 2:** <Titel>
    - **Gegeben** …
    - **Wenn** …
    - **Dann** …

## 🗂️ Datenanforderungen / Attribute

| Attribut      | Typ    | Pflicht | Validierung / Regeln            | Beispiel             |
|---------------|--------|---------|---------------------------------|----------------------|
| Vorname       | Text   | ✅      | max. 50 Zeichen                 | Anna                 |
| Nachname      | Text   | ✅      | max. 50 Zeichen                 | Muster               |
| E-Mail        | E-Mail | ✅      | gültiges Format, eindeutig      | anna@example.ch      |
| Geburtsdatum  | Datum  | ❌      | nicht in der Zukunft, ≥ 18 J.   | 1990-05-12           |
| Kundentyp     | Enum   | ✅      | Privat / Geschäft               | Privat               |

## 🎨 Design / Mockups

<!-- Screenshots, Figma-Links, Skizzen -->

## 🛠️ Technische Hinweise

<!-- Betroffene Komponenten, APIs, Datenmodelle, Abhängigkeiten, Risiken -->

## 🚫 Nicht im Umfang (Out of Scope)

<!-- Was diese Story bewusst NICHT abdeckt -->

-

## 📏 Schätzung & Priorität

| Feld         | Wert                         |
|--------------|------------------------------|
| Story Points | <!-- 1, 2, 3, 5, 8, 13 -->   |
| Priorität    | <!-- Hoch / Mittel / Tief --> |
| Sprint       |                              |

## ☑️ Definition of Ready

- [ ] Story ist verständlich und vom Team besprochen
- [ ] Akzeptanzkriterien sind definiert und testbar
- [ ] Abhängigkeiten sind geklärt
- [ ] Story ist geschätzt
- [ ] Story passt in einen Sprint

## 🏁 Definition of Done

- [ ] Code implementiert und reviewed
- [ ] Alle Akzeptanzkriterien erfüllt
- [ ] Unit- und Integrationstests geschrieben und grün
- [ ] Dokumentation aktualisiert
- [ ] Vom Product Owner abgenommen
