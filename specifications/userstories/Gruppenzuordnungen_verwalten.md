---
name: 📗 Gruppenzuordnungen verwalten
about: Epic für die Zuordnung natürlicher und juristischer Personen zu Gruppen pro Saison
title: "[Epic] Gruppenzuordnungen verwalten"
labels: ["epic"]
assignees: []
---

## 📖 EPIC: Gruppenzuordnungen verwalten

**Als** Geschäftsführer
**möchte ich** natürliche und juristische Personen (z. B. Funktionäre, Sponsoren, Gemeinden) pro Saison Gruppen zuordnen,
**damit** jederzeit klar ist, wer in welcher Saison zu welcher Gruppe gehört und E-Mails und Auswertungen die richtigen Personen erreichen.

## 🧭 Kontext & Hintergrund

- Grundlage: `specifications/models/groups.md` (Mitgliedschaft), `specifications/models/people.md`
- Abhängig von den Epics «Stammdaten verwalten» (Personen) und «Gruppen verwalten» (Gruppen, Hierarchie) sowie von der Story «Saisons verwalten»
- Berechtigt: Rollen Admin und Geschäftsstelle
- Das Epic «Teammitglieder verwalten» regelt die **Spezialfälle für Gruppen der Art «Team»** (nur aktive Teammitglieder, Rolle Spielerin/Coach, Shirtnummer, Austritt). Die Regeln hier gelten für **alle** Gruppenarten; bei Teams gelten zusätzlich die Regeln des Team-Epics.

## 📏 Grundregeln (gelten für alle Sub-Stories)

1. Eine Zuordnung gilt **immer für genau eine Saison** (Person + Gruppe + Saison). Für mehrere Saisons sind mehrere Zuordnungen nötig.
2. Zugeordnet werden können **natürliche und juristische Personen**. Ausnahme: **Mitglied eines Teams** (Gruppenart «Team») können nur **natürliche Personen** sein. Eine Person ist mit einem Datensatz erfasst und kann in derselben Saison mehreren Gruppen angehören.
3. Zugeordnet werden kann nur zu Gruppen mit Status **aktiv**. Ist die Gruppe inaktiv, können keine neuen Personen zugeordnet werden.
4. Pro Person, Gruppe und Saison gibt es **höchstens eine** Zuordnung (bei Teams zusätzlich pro Rolle, siehe Team-Epic).
5. Für **jede Gruppenart** kann optional ein **Austrittsdatum** («Ausgetreten») innerhalb der Saison erfasst werden; die Zuordnung bleibt dabei erhalten.
6. Optional kann eine **Funktion** (Freitext, z. B. «Kassier», «Hauptsponsor») erfasst werden.
7. Mit der Zuordnung zu einer Untergruppe gehört die Person in derselben Saison **transitiv** auch zu allen Obergruppen. Diese abgeleitete Zugehörigkeit wird nicht separat gespeichert.
8. **Zuordnungen können gelöscht werden**, vor dem Löschen erscheint die Warnung, dass dies nur zur Korrektur eines Fehlers gedacht ist. Ein regulärer Abgang wird über das Austrittsdatum bzw. die Nicht-Zuordnung für die Folgesaison abgebildet.

## 🧩 Zugehörige User Stories

- [ ] Person einer Gruppe zuordnen
- [ ] Austritt aus einer Gruppe erfassen
- [ ] Gruppenzuordnung löschen
- [ ] Gruppenmitglieder anzeigen

## ✅ Epic ist abgeschlossen, wenn

- [ ] alle zugehörigen User Stories erledigt sind
- [ ] die Grundregeln 1–8 in allen Stories eingehalten werden
- [ ] nur die Rollen Admin und Geschäftsstelle Zuordnungen ändern können
- [ ] natürliche und juristische Personen Gruppen zugeordnet werden können (Teams: nur natürliche Personen)
- [ ] der Austritt in allen Gruppenarten erfasst werden kann

## 🚫 Nicht im Umfang (Out of Scope)

- Teamspezifische Regeln (Rolle Spielerin/Coach, Shirtnummer): siehe Epic «Teammitglieder verwalten»
- Gruppen anlegen und Hierarchie pflegen (Epic «Gruppen verwalten»)
- Änderungsprotokoll (wer hat wann was geändert)
- Automatisches Übernehmen von Zuordnungen in die Folgesaison
