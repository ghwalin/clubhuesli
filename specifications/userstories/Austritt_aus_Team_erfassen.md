---
name: 📘 Austritt aus einem Team erfassen
about: Das Austrittsdatum einer Spielerin oder eines Coaches in einem Team erfassen
title: "[Story] Austritt aus einem Team erfassen"
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** Geschäftsführer
**möchte ich** für eine Spielerin oder einen Coach ein Austrittsdatum aus einem Team erfassen,
**damit** die Person ab diesem Datum nicht mehr zum Team gehört, ihre Teamhistorie aber erhalten bleibt.

## 🧭 Kontext & Hintergrund

- Epic: Teammitglieder verwalten (Grundregeln siehe dort)
- Ein Austritt aus einem Team ist **kein** Austritt aus dem Verein; die Person bleibt Mitglied und in ihren anderen Teams.

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Austritt erfassen
    - **Gegeben** Anna Muster ist seit dem 01.08.2025 Spielerin im Team «U18» ohne Austrittsdatum
    - **Wenn** ich als Austrittsdatum den 30.06.2026 erfasse
    - **Dann** ist sie bis und mit 30.06.2026 aktuelles Teammitglied, danach erscheint sie unter «Ehemalige»
- [ ] **Szenario 2:** Andere Teams bleiben unverändert
    - **Gegeben** Lea Meier ist Spielerin im Team «U18» und Coach im Team «U12»
    - **Wenn** ich ihren Austritt aus dem Team «U18» erfasse
    - **Dann** bleibt sie Coach im Team «U12»
- [ ] **Szenario 3:** Austrittsdatum vor Eintrittsdatum
    - **Gegeben** eine Teamzugehörigkeit mit Eintrittsdatum 01.08.2026
    - **Wenn** ich als Austrittsdatum den 31.07.2026 erfasse
    - **Dann** wird nicht gespeichert und ich sehe die Meldung «Austrittsdatum darf nicht vor dem Eintrittsdatum liegen»
- [ ] **Szenario 4:** Geplanter Austritt
    - **Gegeben** eine Spielerin wechselt Ende Saison das Team
    - **Wenn** ich ein Austrittsdatum in der Zukunft erfasse
    - **Dann** bleibt sie bis zu diesem Datum aktuelles Teammitglied
- [ ] **Szenario 5:** Ehemalige erhalten keine Team-E-Mails
    - **Gegeben** Anna Muster ist aus dem Team «U18» ausgetreten (Austrittsdatum erreicht)
    - **Wenn** eine E-Mail an das Team «U18» versendet wird
    - **Dann** erhält Anna Muster diese E-Mail nicht

## 🗂️ Datenanforderungen / Attribute

| Attribut       | Typ   | Pflicht | Validierung / Regeln                               | Beispiel   |
|----------------|-------|---------|----------------------------------------------------|------------|
| Austrittsdatum | Datum | ✅      | ≥ Eintrittsdatum; Vergangenheit und Zukunft erlaubt | 30.06.2026 |

## ❓ Offene Fragen

- Soll beim Erfassen eines Vereins-Austrittsdatums (siehe «Natürliche Personen verwalten») automatisch bei allen laufenden Teamzugehörigkeiten das Austrittsdatum gesetzt werden?
- Was passiert, wenn die Mitgliederart einer Person von «Aktiv» auf z. B. «Passiv» geändert wird, während sie noch in einem Team ist: verhindern, warnen oder Austritt automatisch setzen?
- Modell-Frage: Soll die Zugehörigkeit pro Saison statt mit Austrittsdatum erfasst werden?
- Braucht es einen Austrittsgrund (z. B. Teamwechsel, Pause, Verletzung)?
