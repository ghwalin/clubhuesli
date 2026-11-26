---
name: 📘 Austritt aus einer Gruppe erfassen
about: Das Austrittsdatum einer Person in einer beliebigen Gruppe erfassen
title: "[Story] Austritt aus einer Gruppe erfassen"
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** Geschäftsführer
**möchte ich** bei einer Gruppenzuordnung ein Austrittsdatum erfassen,
**damit** die Person ab diesem Datum nicht mehr als Mitglied der Gruppe gilt, ihre Historie aber erhalten bleibt.

## 🧭 Kontext & Hintergrund

- Epic: Gruppenzuordnungen verwalten (Grundregel 5)
- Gilt für **jede Gruppenart** (Team, Funktionäre, andere) und für natürliche und juristische Personen. Für Teams gilt ergänzend «Austritt aus einem Team erfassen».
- Die Zuordnung bleibt erhalten; das Austrittsdatum liegt innerhalb der Saison. Ein Austritt auf Ende Saison erfolgt durch Nicht-Zuordnung in der Folgesaison.
- Mit dem Austritt endet ab demselben Datum auch die abgeleitete Zugehörigkeit zu den Obergruppen (sofern die Person nicht über eine andere Untergruppe weiter Mitglied ist).

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Austritt erfassen
    - **Gegeben** Anna Muster ist in der Saison 2026/27 (01.07.2026–30.06.2027) Mitglied der Gruppe «Funktionäre»
    - **Wenn** ich bei der Zuordnung «Ausgetreten» mit dem Datum 31.12.2026 erfasse
    - **Dann** ist sie bis und mit 31.12.2026 Mitglied, danach unter «Ehemalige» mit «ausgetreten am 31.12.2026»; die Zuordnung bleibt erhalten
- [ ] **Szenario 2:** Juristische Person
    - **Gegeben** «Muster AG» ist Mitglied der Gruppe «Sponsoren» in 2026/27
    - **Wenn** ich ein Austrittsdatum erfasse
    - **Dann** gilt dasselbe wie bei natürlichen Personen
- [ ] **Szenario 3:** Datum ausserhalb der Saison
    - **Gegeben** eine Zuordnung in der Saison 2026/27
    - **Wenn** ich ein Datum vor dem 01.07.2026 oder nach dem 30.06.2027 erfasse
    - **Dann** wird nicht gespeichert (Meldung «Das Austrittsdatum muss innerhalb der Saison liegen»)
- [ ] **Szenario 4:** Geplanter Austritt
    - **Gegeben** ein Austritt ist erst in der Zukunft
    - **Wenn** ich das künftige Datum erfasse
    - **Dann** bleibt die Person bis und mit diesem Datum Mitglied
- [ ] **Szenario 5:** Austritt rückgängig machen
    - **Gegeben** ein Austrittsdatum wurde irrtümlich erfasst
    - **Wenn** ich es entferne und speichere
    - **Dann** ist die Person wieder Mitglied für die ganze Saison
- [ ] **Szenario 6:** Obergruppen
    - **Gegeben** Anna Muster ist nur über «Geschäftsführerin» in «Funktionärin»
    - **Wenn** sie am 31.12.2026 aus «Geschäftsführerin» austritt
    - **Dann** gilt der Austritt ab diesem Datum auch für «Funktionärin»
- [ ] **Szenario 7:** Andere Gruppen bleiben unverändert
    - **Gegeben** Anna Muster ist in 2026/27 in «Funktionäre» und «Helfer»
    - **Wenn** ich ihren Austritt aus «Funktionäre» erfasse
    - **Dann** bleibt sie Mitglied von «Helfer»
- [ ] **Szenario 8:** Keine E-Mails an Ausgetretene
    - **Gegeben** Anna Muster ist aus «Funktionäre» ausgetreten (Datum erreicht)
    - **Wenn** eine E-Mail an die Gruppe versendet wird
    - **Dann** erhält sie diese nicht
- [ ] **Szenario 9:** Austritt bei inaktiver Gruppe
    - **Gegeben** eine Gruppe soll deaktiviert werden und hat noch aktive Mitgliedschaften
    - **Wenn** ich bei allen das Austrittsdatum erfasse
    - **Dann** zählen sie nicht mehr als aktive Mitgliedschaften und die Gruppe kann deaktiviert werden

## 🗂️ Datenanforderungen / Attribute

| Attribut    | Typ   | Pflicht | Validierung / Regeln                                         | Beispiel   |
|-------------|-------|---------|--------------------------------------------------------------|------------|
| Ausgetreten | Datum | ❌      | innerhalb von Start- und Enddatum der Saison; Vergangenheit und Zukunft erlaubt | 31.12.2026 |
