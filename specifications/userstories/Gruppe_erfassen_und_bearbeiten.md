---
name: 📘 Gruppe erfassen und bearbeiten
about: Neue Gruppen mit Bezeichnung, Gruppenart und Status anlegen und ändern
title: "[Story] Gruppe erfassen und bearbeiten"
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** Geschäftsführer
**möchte ich** Gruppen mit Bezeichnung, Gruppenart und Status erfassen und bearbeiten,
**damit** ich Teams, Funktionärsgruppen und weitere Gruppen des Vereins abbilden kann.

## 🧭 Kontext & Hintergrund

- Epic: Gruppen verwalten (Grundregeln siehe dort)
- Grundlage: `specifications/models/groups.md`
- Verwandte Stories: Ober- und Untergruppen zuordnen, Gruppe deaktivieren und reaktivieren

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Gruppe anlegen
    - **Gegeben** ich bin mit der Rolle Admin oder Geschäftsstelle angemeldet
    - **Wenn** ich die Gruppe «NLB» mit Gruppenart «Team» erfasse und speichere
    - **Dann** ist die Gruppe mit Status «aktiv» gespeichert und in der Gruppenliste sichtbar
- [ ] **Szenario 2:** Gruppe bearbeiten
    - **Gegeben** die Gruppe «NLB» existiert
    - **Wenn** ich die Bezeichnung auf «NLB Frauen» ändere und speichere
    - **Dann** wird die neue Bezeichnung überall angezeigt, Mitgliedschaften und Hierarchie bleiben unverändert
- [ ] **Szenario 2b:** Gruppenart ändern
    - **Gegeben** die Gruppe «Helfer» hat die Gruppenart «andere» und es existieren Mitgliedschaften
    - **Wenn** ich die Gruppenart auf «Funktionäre» ändere und speichere
    - **Dann** ist die neue Gruppenart gespeichert, Mitgliedschaften und Hierarchie bleiben unverändert
- [ ] **Szenario 3:** Doppelte Bezeichnung
    - **Gegeben** die Gruppe «NLB» existiert
    - **Wenn** ich eine weitere Gruppe «NLB» erfasse
    - **Dann** wird nicht gespeichert und ich sehe die Meldung «Diese Gruppe existiert bereits»
- [ ] **Szenario 4:** Pflichtfelder
    - **Gegeben** ich erfasse eine Gruppe
    - **Wenn** Bezeichnung oder Gruppenart fehlt
    - **Dann** wird nicht gespeichert und das fehlende Feld wird markiert
- [ ] **Szenario 5:** Fehlende Berechtigung
    - **Gegeben** ich bin als Coach oder Spielerin angemeldet
    - **Wenn** ich Gruppen erfassen oder bearbeiten will
    - **Dann** ist die Funktion nicht verfügbar bzw. wird mit «Keine Berechtigung» abgelehnt

## 🗂️ Datenanforderungen / Attribute

| Attribut    | Typ  | Pflicht | Validierung / Regeln                        | Beispiel |
|-------------|------|---------|---------------------------------------------|----------|
| Bezeichnung | Text | ✅      | eindeutig, max. 100 Zeichen                 | NLB      |
| Gruppenart  | Enum | ✅      | Team / Funktionäre / andere; nachträglich änderbar | Team |
| Status      | Enum | ✅      | aktiv / inaktiv; Standard bei Neuanlage: aktiv | aktiv |
