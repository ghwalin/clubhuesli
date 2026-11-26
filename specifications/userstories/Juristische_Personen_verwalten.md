---
name: 📘 Juristische Personen verwalten
about: Juristische Personen (z. B. Sponsoren, Gemeinden) inkl. Kontaktperson anlegen und verwalten
title: "[Story] Juristische Personen verwalten"
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** Geschäftsführer
**möchte ich** Sponsoren, Partner und Gemeinden als juristische Personen mit ihrer Kontaktperson erfassen und verwalten,
**damit** wir sie den passenden Gruppen zuordnen und gezielt die Ansprechperson kontaktieren können.

## 🧭 Kontext & Hintergrund

- Epic: Stammdaten verwalten
- Verwandte Stories: Adressen verwalten, Natürliche Personen verwalten
- Grundlage: `specifications/models/people.md`. Eine juristische Person erbt **nicht** von der natürlichen Person, implementiert aber wie diese die Allgemeine Person (Personenart, E-Mail, Telefon, Adresse (optional), Notiz).
- Eintritts- und Austrittsdatum gibt es bei der Person nicht mehr. Die Zugehörigkeit zum Verein ergibt sich aus den Gruppenmitgliedschaften pro Saison (z. B. Gruppe «Sponsoren» in der Saison 2026/27, siehe `specifications/models/groups.md`). Ein Austritt während der Saison wird an der Gruppenmitgliedschaft mit dem Attribut «Ausgetreten» erfasst.
- Pro juristische Person wird genau eine Kontaktperson mit Vor- und Nachname erfasst. E-Mail und Telefon sind die der juristischen Person.
- **Entscheid:** Die Kontaktperson ist kein eigener Datensatz und wird **nicht** mit natürlichen Personen verknüpft. Ist ein Mitglied gleichzeitig Kontaktperson, wird es doppelt erfasst. Diese seltene Redundanz wird bewusst in Kauf genommen.

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Juristische Person mit Kontaktperson anlegen
    - **Gegeben** ich bin mit der Rolle Admin oder Geschäftsstelle angemeldet
    - **Wenn** ich eine juristische Person mit Name, E-Mail sowie Vor- und Nachname der Kontaktperson erfasse und speichere
    - **Dann** ist die juristische Person gespeichert und erscheint mit der Personenart «juristische Person» in der Personenliste
- [ ] **Szenario 2:** Ohne Kontaktperson nicht speicherbar
    - **Gegeben** ich erfasse eine neue juristische Person
    - **Wenn** ich speichere, ohne Vor- oder Nachname der Kontaktperson erfasst zu haben
    - **Dann** wird nicht gespeichert und ich sehe die Meldung «Bitte Vor- und Nachname der Kontaktperson erfassen»
- [ ] **Szenario 3:** Kontaktperson ändern
    - **Gegeben** die Kontaktperson einer juristischen Person ist «Anna Muster»
    - **Wenn** ich sie auf «Beat Keller» ändere und speichere
    - **Dann** wird bei der juristischen Person «Beat Keller» als Kontaktperson angezeigt
- [ ] **Szenario 4:** Juristische Person bearbeiten
    - **Gegeben** eine juristische Person ist gespeichert
    - **Wenn** ich z. B. Name oder Adresse ändere und speichere
    - **Dann** werden die Änderungen gespeichert und überall angezeigt
- [ ] **Szenario 5:** Unterstützung endet
    - **Gegeben** die Jensen AG ist in der Saison 2025/26 Mitglied der Gruppe «Sponsoren»
    - **Wenn** sie für die Saison 2026/27 keiner Gruppe mehr zugeordnet wird
    - **Dann** erhält sie ab der Saison 2026/27 keine E-Mails an diese Gruppe mehr, und ihre Daten und früheren Gruppenmitgliedschaften bleiben erhalten
- [ ] **Szenario 6:** Unterstützung endet während der Saison
    - **Gegeben** die Jensen AG ist in der aktuellen Saison 2026/27 Mitglied der Gruppe «Sponsoren»
    - **Wenn** ich bei dieser Mitgliedschaft «Ausgetreten» mit dem Datum 31.12.2026 erfasse
    - **Dann** erhält sie ab dem 01.01.2027 keine E-Mails an diese Gruppe mehr, und die Mitgliedschaft der Saison 2026/27 bleibt erhalten
- [ ] **Szenario 6a:** Austritt aus dem Verein
    - **Gegeben** die Jensen AG ist in der aktuellen Saison 2026/27 Mitglied der Gruppe «Sponsoren»
    - **Wenn** ich «Austritt aus dem Verein» wähle und die Sicherheitsabfrage bestätige
    - **Dann** enden alle ihre Gruppenmitgliedschaften auf Ende der aktuellen Saison (gleiche Regeln wie bei natürlichen Personen, siehe «Natürliche Personen verwalten», Szenarien 8–8b)
- [ ] **Szenario 7:** Juristische Person löschen
    - **Gegeben** eine juristische Person ist gespeichert
    - **Wenn** ich sie lösche und die Sicherheitsabfrage bestätige
    - **Dann** werden die juristische Person, ihre Kontaktperson und ihre Gruppenzuordnungen entfernt
- [ ] **Szenario 8:** Unterstützung und Notiz erfassen
    - **Gegeben** ich bearbeite eine juristische Person
    - **Wenn** ich im Feld «Unterstützung» beschreibe, wie sie den Club unterstützt (z. B. «Trikotsponsoring 2026–2028»), ein Notizfeld ausfülle und speichere
    - **Dann** werden Unterstützung und Notiz bei der juristischen Person angezeigt

## 🗂️ Datenanforderungen / Attribute

### Juristische Person

| Attribut          | Typ                       | Pflicht | Validierung / Regeln                    | Beispiel        |
|-------------------|---------------------------|---------|-----------------------------------------|-----------------|
| Name              | Text                      | ✅      | 2 – 200 Zeichen                         | Jensen AG       |
| Personenart       | Auswahl                   | ✅      | juristische Person (fest)                           | juristische Person |
| Adresse           | siehe «Adressen verwalten» | ❌      | optional; wenn erfasst: Strasse, PLZ, Ort | Dorfstrasse 12, 8000 Zürich |
| E-Mail            | E-Mail                    | ✅      | gültiges Format                         | info@jensen.ch  |
| Telefon           | Text                      | ❌      | internationales Format                  | +41 44 123 45 67 |
| Unterstützung     | Text (Textarea)           | ❌      | Freitext                                | Trikotsponsoring 2026–2028 |
| Notiz             | Text                      | ❌      | Freitext                                | –               |
| Kontaktperson Vorname | Text                  | ✅      | 2 – 50 Zeichen                          | Anna            |
| Kontaktperson Nachname | Text                 | ✅      | 2 – 50 Zeichen                          | Muster          |

**Abgeleitet:** Die juristische Person gilt als aktiv, wenn sie in der aktuellen Saison Mitglied mindestens einer Gruppe ist und dort «Ausgetreten» leer oder noch nicht erreicht ist.

## 🛠️ Technische Hinweise

- Klasse `LegalPerson` implementiert `AbstractPerson` (siehe `specifications/models/people.md`); Vor- und Nachname, Geburtsdatum, AHV-Nummer usw. gibt es dort nicht.
- Die Kontaktperson besteht nur aus Vor- und Nachname und wird als Attribute von `LegalPerson` geführt (keine eigene Tabelle/Klasse).

## ❓ Offene Fragen

- Braucht es mehrere Kontaktpersonen oder einen Hauptkontakt (z. B. für «z. Hd.» in der Postadresse)? Aktuell nicht vorgesehen.
- An wen gehen E-Mails beim Gruppenversand: an die E-Mail der juristischen Person (z. Hd. der Kontaktperson)?

## 🔒 Nicht-funktionale Anforderungen

- Nur die Rollen Admin und Geschäftsstelle dürfen juristische Personen und ihre Kontaktperson bearbeiten.
- Die Kontaktperson besteht aus Personendaten (revDSG): Sie muss auf Wunsch vollständig gelöscht werden können.

## 🚫 Nicht im Umfang (Out of Scope)

- Verwaltung von Sponsoring-Verträgen, Beträgen und Rechnungen
