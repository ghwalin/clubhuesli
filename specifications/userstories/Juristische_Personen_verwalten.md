---
name: 📘 Juristische Personen verwalten
about: Juristische Personen (Sponsoren, Gönner, Partner, Gemeinden) inkl. Kontaktperson anlegen und verwalten
title: "[Story] Juristische Personen verwalten"
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** Geschäftsführer
**möchte ich** Sponsoren, Gönner, Partner und Gemeinden als juristische Personen mit ihrer Kontaktperson erfassen und verwalten,
**damit** wir sie den passenden Gruppen zuordnen und gezielt die Ansprechperson kontaktieren können.

## 🧭 Kontext & Hintergrund

- Epic: Stammdaten verwalten
- Verwandte Stories: Adressen verwalten, Natürliche Personen verwalten
- Grundlage: `specifications/models/people.md`. Eine juristische Person erbt **nicht** von der natürlichen Person, implementiert aber wie diese die Allgemeine Person (Mitgliederart, E-Mail, Telefon, Adresse, Eintritt/Austritt, Notiz).
- Pro juristische Person wird genau eine Kontaktperson mit Vor- und Nachname erfasst. E-Mail und Telefon sind die der juristischen Person.
- **Entscheid:** Die Kontaktperson ist kein eigener Datensatz und wird **nicht** mit natürlichen Personen verknüpft. Ist ein Mitglied gleichzeitig Kontaktperson, wird es doppelt erfasst. Diese seltene Redundanz wird bewusst in Kauf genommen.

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Juristische Person mit Kontaktperson anlegen
    - **Gegeben** ich bin mit der Rolle Admin oder Geschäftsstelle angemeldet
    - **Wenn** ich eine juristische Person mit Name, Mitgliederart, Adresse, E-Mail, Eintrittsdatum sowie Vor- und Nachname der Kontaktperson erfasse und speichere
    - **Dann** ist die juristische Person gespeichert und erscheint mit ihrer Mitgliederart in der Personenliste
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
    - **Wenn** ich z. B. Name, Mitgliederart oder Adresse ändere und speichere
    - **Dann** werden die Änderungen gespeichert und überall angezeigt
- [ ] **Szenario 5:** Austritt erfassen
    - **Gegeben** eine juristische Person hat ein Eintrittsdatum und kein Austrittsdatum
    - **Wenn** ich ein Austrittsdatum erfasse (z. B. Ende der Unterstützung)
    - **Dann** gilt sie ab diesem Datum als inaktiv, sie erscheint nicht mehr als Empfänger beim E-Mail-Versand, und die Daten bleiben erhalten (neuer Eintritt möglich, Zeiträume werden mehrfach gespeichert)
- [ ] **Szenario 6:** Juristische Person löschen
    - **Gegeben** eine juristische Person ist gespeichert
    - **Wenn** ich sie lösche und die Sicherheitsabfrage bestätige
    - **Dann** werden die juristische Person, ihre Kontaktperson und ihre Gruppenzuordnungen entfernt
- [ ] **Szenario 7:** Unterstützung und Notiz erfassen
    - **Gegeben** ich bearbeite eine juristische Person
    - **Wenn** ich im Feld «Unterstützung» beschreibe, wie sie den Club unterstützt (z. B. «Trikotsponsoring 2026–2028»), ein Notizfeld ausfülle und speichere
    - **Dann** werden Unterstützung und Notiz bei der juristischen Person angezeigt

## 🗂️ Datenanforderungen / Attribute

### Juristische Person

| Attribut          | Typ                       | Pflicht | Validierung / Regeln                    | Beispiel        |
|-------------------|---------------------------|---------|-----------------------------------------|-----------------|
| Name              | Text                      | ✅      | 2 – 200 Zeichen                         | Jensen AG       |
| Mitgliederart     | Enum                      | ✅      | Sponsor / Gönner / Partner / Gemeinde (erweiterbar) | Sponsor |
| Adresse           | siehe «Adressen verwalten» | ✅      | Strasse, Nr., PLZ, Ort                  | Dorfstrasse 12, 8000 Zürich |
| E-Mail            | E-Mail                    | ✅      | gültiges Format                         | info@jensen.ch  |
| Telefon           | Text                      | ❌      | internationales Format                  | +41 44 123 45 67 |
| Unterstützung     | Text (Textarea)           | ❌      | Freitext                                | Trikotsponsoring 2026–2028 |
| Eintrittsdatum    | Datum (mehrfach)          | ✅      | mindestens ein Zeitraum; keine Überschneidung | 01.08.2024 |
| Austrittsdatum    | Datum (mehrfach)          | ❌      | ≥ Eintrittsdatum desselben Zeitraums    | 30.06.2027      |
| Notiz             | Text                      | ❌      | Freitext                                | –               |
| Kontaktperson Vorname | Text                  | ✅      | 2 – 50 Zeichen                          | Anna            |
| Kontaktperson Nachname | Text                 | ✅      | 2 – 50 Zeichen                          | Muster          |

**Abgeleitet:** Status aktiv/inaktiv ergibt sich aus den Mitgliedschaftszeiträumen (Eintritt erreicht, Austritt leer oder noch nicht erreicht).

## 🛠️ Technische Hinweise

- Klasse `LegalPerson` implementiert `AbstractPerson` (siehe `specifications/models/people.md`); Vor- und Nachname, Geburtsdatum, AHV-Nummer usw. gibt es dort nicht.
- Die Kontaktperson besteht nur aus Vor- und Nachname und wird als Attribute von `LegalPerson` geführt (keine eigene Tabelle/Klasse).

## ❓ Offene Fragen

- Kann eine juristische Person mehrere Mitgliederarten haben (z. B. Sponsor **und** Partner)?
- Braucht es mehrere Kontaktpersonen oder einen Hauptkontakt (z. B. für «z. Hd.» in der Postadresse)? Aktuell nicht vorgesehen.
- An wen gehen E-Mails beim Gruppenversand: an die E-Mail der juristischen Person (z. Hd. der Kontaktperson)?

## 🔒 Nicht-funktionale Anforderungen

- Nur die Rollen Admin und Geschäftsstelle dürfen juristische Personen und ihre Kontaktperson bearbeiten.
- Die Kontaktperson besteht aus Personendaten (revDSG): Sie muss auf Wunsch vollständig gelöscht werden können.

## 🚫 Nicht im Umfang (Out of Scope)

- Verwaltung von Sponsoring-Verträgen, Beträgen und Rechnungen
