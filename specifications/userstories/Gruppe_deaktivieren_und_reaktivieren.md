---
name: 📘 Gruppe deaktivieren und reaktivieren
about: Nicht mehr benötigte Gruppen inaktiv setzen und bei Bedarf wieder aktivieren
title: "[Story] Gruppe deaktivieren und reaktivieren"
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** Geschäftsführer
**möchte ich** nicht mehr benötigte Gruppen deaktivieren und bei Bedarf wieder aktivieren,
**damit** die Gruppenliste aktuell bleibt, ohne dass historische Mitgliedschaften verloren gehen.

## 🧭 Kontext & Hintergrund

- Epic: Gruppen verwalten (Grundregeln siehe dort)
- **Gruppen können vorerst nicht gelöscht werden**, nur deaktiviert.
- **Aktive Mitgliedschaft:** Saison ist die aktuelle oder eine künftige und «Ausgetreten» ist leer oder noch nicht erreicht.
- Mitgliedschaften früherer Saisons müssen nachvollziehbar bleiben.

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Gruppe deaktivieren
    - **Gegeben** die Gruppe «U12» ist aktiv, hat keine aktiven Mitgliedschaften mehr und alle ihre Untergruppen sind inaktiv (oder sie hat keine)
    - **Wenn** ich sie auf «inaktiv» setze
    - **Dann** bleiben alle bisherigen Mitgliedschaften und Hierarchiebeziehungen erhalten
- [ ] **Szenario 2:** Deaktivieren mit aktiven Mitgliedschaften
    - **Gegeben** die Gruppe «U12» hat aktive Mitgliedschaften
    - **Wenn** ich sie deaktivieren will
    - **Dann** wird mit der Meldung «Die Gruppe hat noch aktive Mitgliedschaften» abgelehnt
- [ ] **Szenario 3:** Deaktivieren mit aktiven Untergruppen
    - **Gegeben** die Gruppe «Funktionäre» hat mindestens eine aktive Untergruppe
    - **Wenn** ich sie deaktivieren will
    - **Dann** wird mit der Meldung «Die Gruppe hat noch aktive Untergruppen» abgelehnt
- [ ] **Szenario 4:** Keine neuen Personen in inaktiver Gruppe
    - **Gegeben** die Gruppe «U12» ist inaktiv
    - **Wenn** ich ihr eine Person zuordnen will
    - **Dann** wird die Gruppe nicht zur Auswahl angeboten bzw. mit der Meldung «Die Gruppe ist inaktiv» abgelehnt
- [ ] **Szenario 5:** Gruppe reaktivieren
    - **Gegeben** die Gruppe «U12» ist inaktiv
    - **Wenn** ich sie auf «aktiv» setze
    - **Dann** können ihr wieder Personen zugeordnet und neue Verknüpfungen erstellt werden
- [ ] **Szenario 6:** Kein Löschen
    - **Gegeben** ich bin als Admin oder Geschäftsstelle angemeldet
    - **Wenn** ich die Gruppenverwaltung öffne
    - **Dann** gibt es keine Funktion zum Löschen einer Gruppe

## 🚫 Nicht im Umfang (Out of Scope)

- Gruppen löschen
- Automatisches Deaktivieren von Untergruppen beim Deaktivieren einer Obergruppe (Untergruppen müssen zuerst einzeln deaktiviert werden)
