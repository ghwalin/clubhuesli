---
name: 📘 Teamzugehörigkeit korrigieren oder löschen
about: Falsch erfasste Teamzugehörigkeiten korrigieren oder entfernen
title: "[Story] Teamzugehörigkeit korrigieren oder löschen"
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** Geschäftsführer
**möchte ich** falsch erfasste Teamzugehörigkeiten korrigieren oder löschen,
**damit** die Teamlisten und die Teamhistorie stimmen.

## 🧭 Kontext & Hintergrund

- Epic: Teammitglieder verwalten (Grundregeln siehe dort)
- Ein regulärer Abgang wird über «Austritt aus einem Team erfassen» abgebildet. Löschen ist nur für Fehlerfassungen gedacht.

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Rolle ist nicht änderbar
    - **Gegeben** Lea Meier wurde im Team «U12» irrtümlich dem Spielerinnen-Datensatz als Spielerin zugeordnet, gemeint war ihr Coach-Datensatz
    - **Wenn** ich die Zugehörigkeit bearbeite
    - **Dann** kann ich die Rolle nicht ändern (sie ergibt sich aus der Personenart); ich lösche die Zugehörigkeit und ordne den Coach-Datensatz neu zu
- [ ] **Szenario 2:** Funktion im Team ändern
    - **Gegeben** Anna Muster ist Spielerin im Team «U18» mit der Funktion «Torhüterin»
    - **Wenn** ich die Funktion auf «Torhüterin, Captain» ändere und speichere
    - **Dann** wird in der Teamliste die neue Funktion angezeigt
- [ ] **Szenario 3:** Eintrittsdatum korrigieren
    - **Gegeben** eine Teamzugehörigkeit mit Eintrittsdatum 01.09.2026
    - **Wenn** ich das Eintrittsdatum auf 01.08.2026 ändere
    - **Dann** wird das neue Datum gespeichert, sofern keine Überschneidung entsteht (siehe Epic, Regel 4)
- [ ] **Szenario 4:** Korrektur mit Überschneidung
    - **Gegeben** Anna Muster hat im Team «U18» zwei Zeiträume als Spielerin (2024–2025 und ab 01.08.2026)
    - **Wenn** ich das Austrittsdatum des ersten Zeitraums auf den 31.12.2026 ändere
    - **Dann** wird nicht gespeichert und ich sehe die Meldung «Die Zeiträume dürfen sich nicht überschneiden»
- [ ] **Szenario 5:** Teamzugehörigkeit löschen
    - **Gegeben** Anna Muster wurde irrtümlich dem Team «U12» zugeordnet
    - **Wenn** ich diese Zugehörigkeit lösche und die Sicherheitsabfrage bestätige
    - **Dann** ist die Zugehörigkeit entfernt und erscheint weder in der Teamliste noch in ihrer Teamhistorie

## 🚫 Nicht im Umfang (Out of Scope)

- Änderungsprotokoll (wer hat wann was geändert)

## ❓ Offene Fragen

- Was passiert beim Löschen mit bereits erfassten Teilnahmen (Offen/Zusage/Absage) der Person an Anlässen dieses Teams (siehe `specifications/models/teamplan.md`)?
