---
name: 📗 Teammitglieder verwalten
about: Epic für die Zuordnung von Spielerinnen und Coaches zu Teams pro Saison
title: "[Epic] Teammitglieder verwalten"
labels: ["epic"]
assignees: []
---

## 📖 EPIC: Teammitglieder verwalten

**Als** Geschäftsführer
**möchte ich** festhalten, welche Spielerinnen und Coaches in welcher Saison zu welchem Team gehören,
**damit** jederzeit klar ist, wer aktuell und früher in einem Team war, und Trainings, Spiele und E-Mails die richtigen Personen erreichen.

## 🧭 Kontext & Hintergrund

- Grundlage: `specifications/models/groups.md` (Teams sind Gruppen der Art «Team»; Mitgliedschaft mit Saison, Rolle, Funktion, optionaler Shirtnummer und optionalem Austrittsdatum)
- Abhängig von Epic: Stammdaten verwalten (natürliche Personen bzw. Teammitglieder mit Mitgliederart)
- Berechtigt: Rollen Admin und Geschäftsstelle

## 📏 Grundregeln (gelten für alle Sub-Stories)

1. Nur **Teammitglieder** (`specifications/models/people.md`) mit Mitgliederart **«Aktiv»** können einem Team zugeordnet werden.
2. Die Teamzugehörigkeit wird **pro Saison** erfasst (z. B. Spielerin im Team «NLB» in der Saison 2026/27). Eine Saison hat eine Bezeichnung sowie ein Start- und Enddatum.
3. Jede Teamzugehörigkeit hat eine **Saison**, eine **Rolle** (Spielerin oder Coach), optional eine **Funktion im Team** als Freitext (z. B. Headcoach, Assistenzcoach, Torhüterin, Captain) und – nur bei Spielerinnen – optional eine **Shirtnummer**.
   Verlässt die Person das Team während der Saison, wird das Datum im Attribut **«Ausgetreten»** erfasst; die Mitgliedschaft bleibt erhalten.
4. Eine Person ist mit **einem** Datensatz erfasst und kann in derselben Saison in **mehreren Teams** sein, auch mit unterschiedlichen Rollen (z. B. Spielerin im U18 und Coach im U12).
5. Pro Person, Team, Saison und Rolle gibt es **höchstens eine** Zugehörigkeit.
6. Eine Teamzugehörigkeit ist **aktuell**, wenn ihre Saison die aktuelle Saison ist (heutiges Datum zwischen Start- und Enddatum der Saison) und «Ausgetreten» leer oder noch nicht erreicht ist.
7. Zugeordnet werden kann nur zu Gruppen der Art **«Team»** mit Status **aktiv**.
8. Teammitglieder gehören in derselben Saison automatisch auch zu allen **Obergruppen** ihres Teams (transitiv, unabhängig von der Rolle). Ist die Person aus dem Team ausgetreten, gilt das Austrittsdatum auch für die Obergruppen.

## 🧩 Zugehörige User Stories

- [ ] Saisons verwalten
- [ ] Mitglied einem Team zuordnen
- [ ] Austritt aus einem Team erfassen
- [ ] Teamzugehörigkeit korrigieren oder löschen
- [ ] Teammitglieder anzeigen

## ✅ Epic ist abgeschlossen, wenn

- [ ] alle zugehörigen User Stories erledigt sind
- [ ] die Grundregeln 1–8 in allen Stories eingehalten werden
- [ ] nur die Rollen Admin und Geschäftsstelle Teamzugehörigkeiten ändern können

## 🚫 Nicht im Umfang (Out of Scope)

- Teams anlegen, umbenennen oder löschen (Epic «Gruppen verwalten»)
- Trainings, Spiele und An-/Abmeldungen (Epic «Teilnahme»)
- Weitere Rollen wie Förderkader, Betreuerin oder Materialverantwortliche
