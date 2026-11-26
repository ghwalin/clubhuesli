---
name: 📗 Gruppen verwalten
about: Epic für die Verwaltung von Gruppen und ihrer Hierarchie (Ober-/Untergruppen)
title: "[Epic] Gruppen verwalten"
labels: ["epic"]
assignees: []
---

## 📖 EPIC: Gruppen verwalten

**Als** Geschäftsführer
**möchte ich** Gruppen (Teams, Funktionäre, andere) erfassen und hierarchisch in Ober- und Untergruppen gliedern,
**damit** Personen sinnvoll organisiert sind und Rechte, Auswertungen und E-Mails an ganze Gruppenbäume gerichtet werden können.

## 🧭 Kontext & Hintergrund

- Grundlage: `specifications/models/groups.md` (Gruppe: Bezeichnung, Gruppenart, Status, Obergruppen, Untergruppen)
- Berechtigt: Rollen Admin und Geschäftsstelle (Gruppen erstellen, bearbeiten)
- Die Zuordnung von Personen zu Gruppen ist Thema des Epics «Teammitglieder verwalten».

## 📏 Grundregeln (gelten für alle Sub-Stories)

1. Eine Gruppe hat eine **Bezeichnung** (eindeutig), eine **Gruppenart** (Team, Funktionäre, andere) und einen **Status** (aktiv/inaktiv).
2. Gruppen können andere Gruppen enthalten. Die Beziehung ist **n:n**: eine Gruppe kann mehrere Obergruppen und mehrere Untergruppen haben.
3. Die Hierarchie ist **zyklenfrei**: eine Gruppe kann weder sich selbst noch einer ihrer (direkten oder indirekten) Untergruppen als Obergruppe zugeordnet werden.
4. Mitglieder einer Untergruppe gehören in derselben Saison **transitiv** auch zu allen Obergruppen (z. B. «Geschäftsführerin» → «Funktionärin»).
5. Nur Gruppen mit Status **aktiv** können neu verknüpft oder mit Personen belegt werden.

## 🧩 Zugehörige User Stories

- [ ] Gruppe erfassen und bearbeiten
- [ ] Gruppe deaktivieren und reaktivieren
- [ ] Ober- und Untergruppen zuordnen
- [ ] Gruppenhierarchie anzeigen

## ✅ Epic ist abgeschlossen, wenn

- [ ] alle zugehörigen User Stories erledigt sind
- [ ] die Grundregeln 1–5 in allen Stories eingehalten werden
- [ ] nur die Rollen Admin und Geschäftsstelle Gruppen ändern können

## 🚫 Nicht im Umfang (Out of Scope)

- Gruppen löschen (vorerst nicht vorgesehen)
- Personen zu Gruppen zuordnen (Epic «Teammitglieder verwalten»)
- Saisons verwalten (eigene Story)
- Berechtigungen pro Gruppe
