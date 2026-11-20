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
- Ein regulärer Abgang wird über «Austritt aus einem Team erfassen» abgebildet (auch während der Saison, die Mitgliedschaft bleibt erhalten). Löschen ist nur für Fehlerfassungen gedacht.

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Rolle ändern
    - **Gegeben** Lea Meier wurde im Team «U12» für die Saison 2026/27 irrtümlich als Spielerin zugeordnet, gemeint war Coach
    - **Wenn** ich die Rolle auf «Coach» ändere und speichere
    - **Dann** ist sie in der Teamliste «U12» als Coach aufgeführt und eine allfällige Shirtnummer wird entfernt
- [ ] **Szenario 2:** Funktion im Team ändern
    - **Gegeben** Anna Muster ist Spielerin im Team «U18» mit der Funktion «Torhüterin»
    - **Wenn** ich die Funktion auf «Torhüterin, Captain» ändere und speichere
    - **Dann** wird in der Teamliste die neue Funktion angezeigt
- [ ] **Szenario 3:** Saison korrigieren
    - **Gegeben** eine Teamzugehörigkeit wurde irrtümlich für die Saison 2025/26 statt 2026/27 erfasst
    - **Wenn** ich die Saison auf 2026/27 ändere
    - **Dann** wird die neue Saison gespeichert, sofern keine doppelte Zuordnung entsteht (siehe Epic, Regel 5)
- [ ] **Szenario 4:** Korrektur mit doppelter Zuordnung
    - **Gegeben** Anna Muster ist im Team «U18» in den Saisons 2025/26 und 2026/27 als Spielerin erfasst
    - **Wenn** ich die Saison der Mitgliedschaft 2025/26 auf 2026/27 ändere
    - **Dann** wird nicht gespeichert und ich sehe die Meldung «Anna Muster ist in dieser Saison bereits Spielerin in diesem Team»
- [ ] **Szenario 5:** Shirtnummer ändern
    - **Gegeben** Anna Muster trägt im Team «U18» in der Saison 2026/27 die Shirtnummer 10
    - **Wenn** ich die Shirtnummer auf 7 ändere
    - **Dann** wird die neue Nummer gespeichert und in der Teamliste angezeigt
- [ ] **Szenario 6:** Austritt rückgängig machen
    - **Gegeben** bei Anna Muster wurde im Team «U18» irrtümlich «Ausgetreten» mit dem Datum 31.12.2026 erfasst
    - **Wenn** ich das Datum entferne und speichere
    - **Dann** ist sie wieder aktuelles Teammitglied der Saison 2026/27
- [ ] **Szenario 7:** Teamzugehörigkeit löschen
    - **Gegeben** Anna Muster wurde irrtümlich dem Team «U12» zugeordnet
    - **Wenn** ich diese Zugehörigkeit lösche und die Sicherheitsabfrage bestätige
    - **Dann** ist die Zugehörigkeit entfernt und erscheint weder in der Teamliste noch in ihrer Teamhistorie

## 🚫 Nicht im Umfang (Out of Scope)

- Änderungsprotokoll (wer hat wann was geändert)

## ❓ Offene Fragen

- Was passiert beim Löschen mit bereits erfassten Teilnahmen (Offen/Zusage/Absage) der Person an Anlässen dieses Teams (siehe `specifications/models/teamplan.md`)?
