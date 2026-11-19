---
name: 📗 Teammitglieder verwalten
about: Epic für die Zuordnung von Spielerinnen und Coaches zu Teams
title: "[Epic] Teammitglieder verwalten"
labels: ["epic"]
assignees: []
---

## 📖 EPIC: Teammitglieder verwalten

**Als** Geschäftsführer
**möchte ich** festhalten, welche Spielerinnen und Coaches in welchem Zeitraum zu welchem Team gehören,
**damit** jederzeit klar ist, wer aktuell und früher in einem Team war, und Trainings, Spiele und E-Mails die richtigen Personen erreichen.

## 🧭 Kontext & Hintergrund

- Grundlage: `specifications/models/groups.md` (Teams sind Gruppen der Art «Team»; Mitgliedschaft mit Rolle, Funktion und Zeitraum)
- Abhängig von Epic: Stammdaten verwalten (natürliche Personen mit Mitgliederart)
- Berechtigt: Rollen Admin und Geschäftsstelle

## 📏 Grundregeln (gelten für alle Sub-Stories)

1. Nur Personen mit Mitgliederart **«Aktiv»** und aktueller Vereinsmitgliedschaft (siehe «Natürliche Personen verwalten») können einem Team zugeordnet werden.
2. Eine Person kann gleichzeitig in **mehreren Teams** sein (z. B. Spielerin im U18 und Coach im U12). Spielerin und Coach sind getrennte Personen-Datensätze mit je eigenen Gruppenzugehörigkeiten.
3. Jede Teamzugehörigkeit hat eine **Rolle** (Spielerin oder Coach), ein **Eintrittsdatum** und optional ein **Austrittsdatum** sowie eine **Funktion im Team** als Freitext (z. B. Headcoach, Assistenzcoach, Torhüterin, Captain).
4. Eine Person kann **mehrmals** im selben Team sein (z. B. Austritt und späterer Wiedereintritt). Die Zeiträume dürfen sich für dieselbe Person, dasselbe Team und dieselbe Rolle **nicht überschneiden**.
5. Eine Teamzugehörigkeit ist **aktuell**, wenn das Eintrittsdatum erreicht und das Austrittsdatum leer oder noch nicht erreicht ist.
6. Zugeordnet werden kann nur zu Gruppen der Art **«Team»** mit Status **aktiv**.
7. Teammitglieder gehören automatisch auch zu allen **Obergruppen** ihres Teams (z. B. Team «NLB» → Gruppe «Spielerin»). Ausnahme: Coaches gehören nicht zur Obergruppe «Spielerin».

## 🧩 Zugehörige User Stories

- [ ] Mitglied einem Team zuordnen
- [ ] Austritt aus einem Team erfassen
- [ ] Teamzugehörigkeit korrigieren oder löschen
- [ ] Teammitglieder anzeigen

## ✅ Epic ist abgeschlossen, wenn

- [ ] alle zugehörigen User Stories erledigt sind
- [ ] die Grundregeln 1–7 in allen Stories eingehalten werden
- [ ] nur die Rollen Admin und Geschäftsstelle Teamzugehörigkeiten ändern können

## 🚫 Nicht im Umfang (Out of Scope)

- Teams anlegen, umbenennen oder löschen (Epic «Gruppen verwalten»)
- Trainings, Spiele und An-/Abmeldungen (Epic «Teilnahme»)
- Weitere Rollen wie Förderkader, Betreuerin oder Materialverantwortliche
