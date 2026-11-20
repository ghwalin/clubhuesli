---
name: 📘 Austritt aus einem Team erfassen
about: Den Austritt einer Spielerin oder eines Coaches aus einem Team auf Ende Saison oder während der Saison erfassen
title: "[Story] Austritt aus einem Team erfassen"
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** Geschäftsführer
**möchte ich** erfassen, dass eine Spielerin oder ein Coach ein Team verlässt,
**damit** die Person ab dann nicht mehr zum Team gehört, ihre Teamhistorie aber erhalten bleibt.

## 🧭 Kontext & Hintergrund

- Epic: Teammitglieder verwalten (Grundregeln siehe dort)
- Die Teamzugehörigkeit wird pro Saison erfasst.
  - **Austritt auf Ende Saison:** Die Person wird für die nächste Saison nicht mehr dem Team zugeordnet.
  - **Austritt während der Saison:** Bei der Mitgliedschaft der laufenden Saison wird das Datum im Attribut «Ausgetreten» erfasst. Die Mitgliedschaft bleibt erhalten, damit ersichtlich ist, dass die Person bis zu diesem Datum im Team war.
- Mit dem Austritt aus dem Team endet ab demselben Datum auch die abgeleitete Zugehörigkeit zu den Obergruppen des Teams (siehe Epic, Regel 8).
- Ein Austritt aus einem Team ist **kein** Austritt aus dem Verein; die Person bleibt in ihren anderen Teams und Gruppen.

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Austritt auf Ende Saison
    - **Gegeben** Anna Muster ist in der Saison 2025/26 Spielerin im Team «U18»
    - **Wenn** ich sie für die Saison 2026/27 nicht mehr dem Team «U18» zuordne
    - **Dann** ist sie ab der Saison 2026/27 kein aktuelles Teammitglied mehr und erscheint unter «Ehemalige»; die Mitgliedschaft der Saison 2025/26 bleibt erhalten
- [ ] **Szenario 2:** Austritt während der laufenden Saison
    - **Gegeben** Anna Muster ist in der aktuellen Saison 2026/27 (01.07.2026–30.06.2027) Spielerin im Team «U18»
    - **Wenn** ich bei ihrer Mitgliedschaft «Ausgetreten» mit dem Datum 31.12.2026 erfasse
    - **Dann** ist sie bis und mit 31.12.2026 aktuelles Teammitglied; danach erscheint sie unter «Ehemalige» mit dem Vermerk «ausgetreten am 31.12.2026», und die Mitgliedschaft der Saison 2026/27 bleibt erhalten
- [ ] **Szenario 3:** Austrittsdatum ausserhalb der Saison
    - **Gegeben** eine Mitgliedschaft in der Saison 2026/27 (01.07.2026–30.06.2027)
    - **Wenn** ich als «Ausgetreten» ein Datum vor dem 01.07.2026 oder nach dem 30.06.2027 erfasse
    - **Dann** wird nicht gespeichert und ich sehe die Meldung «Das Austrittsdatum muss innerhalb der Saison liegen»
- [ ] **Szenario 4:** Geplanter Austritt
    - **Gegeben** eine Spielerin verlässt das Team Ende Januar
    - **Wenn** ich im Dezember «Ausgetreten» mit dem Datum 31.01.2027 erfasse
    - **Dann** bleibt sie bis und mit 31.01.2027 aktuelles Teammitglied
- [ ] **Szenario 5:** Andere Teams bleiben unverändert
    - **Gegeben** Lea Meier ist in der Saison 2026/27 Spielerin im Team «U18» und Coach im Team «U12»
    - **Wenn** ich ihren Austritt aus dem Team «U18» erfasse
    - **Dann** bleibt sie Coach im Team «U12»
- [ ] **Szenario 6:** Ehemalige erhalten keine Team-E-Mails
    - **Gegeben** Anna Muster ist aus dem Team «U18» ausgetreten (Austrittsdatum erreicht oder in der aktuellen Saison nicht mehr zugeordnet)
    - **Wenn** eine E-Mail an das Team «U18» versendet wird
    - **Dann** erhält Anna Muster diese E-Mail nicht

## 🗂️ Datenanforderungen / Attribute

| Attribut    | Typ   | Pflicht | Validierung / Regeln                                         | Beispiel   |
|-------------|-------|---------|--------------------------------------------------------------|------------|
| Ausgetreten | Datum | ❌      | innerhalb von Start- und Enddatum der Saison; Vergangenheit und Zukunft erlaubt | 31.12.2026 |

## ❓ Offene Fragen

- Was passiert, wenn die Mitgliederart einer Person von «Aktiv» auf z. B. «Passiv» geändert wird, während sie in der aktuellen Saison noch in einem Team ist: verhindern, warnen oder Mitgliedschaft automatisch entfernen?
- Braucht es einen Austrittsgrund (z. B. Teamwechsel, Pause, Verletzung)?
