---
name: 📘 Juristische Personen verwalten
about: Juristische Personen (Sponsoren, Gönner, Partner, Gemeinden) inkl. Kontaktpersonen anlegen und verwalten
title: "[Story] Juristische Personen verwalten"
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** Geschäftsführer
**möchte ich** Sponsoren, Gönner, Partner und Gemeinden als juristische Personen mit ihren Kontaktpersonen erfassen und verwalten,
**damit** wir sie den passenden Gruppen zuordnen und gezielt die zuständige Ansprechperson kontaktieren können.

## 🧭 Kontext & Hintergrund

- Epic: Stammdaten verwalten
- Verwandte Stories: Adressen verwalten, Natürliche Personen verwalten
- Eine juristische Person erbt **nicht** von der natürlichen Person (siehe `Clubadmin.md`).
- Pro juristische Person werden **1–n Kontaktpersonen** erfasst. Jede Kontaktperson kann eine **Aufgabe** als Freitext haben (z. B. «Finanzen», «Sublimation»), damit klar ist, wer wofür zuständig ist.
- **Entscheid:** Kontaktpersonen sind eigenständige Datensätze und werden **nicht** mit natürlichen Personen verknüpft. Ist ein Mitglied gleichzeitig Kontaktperson, wird es doppelt erfasst. Diese seltene Redundanz wird bewusst in Kauf genommen.

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Juristische Person mit Kontaktperson anlegen
    - **Gegeben** ich bin mit der Rolle Admin oder Geschäftsstelle angemeldet
    - **Wenn** ich eine juristische Person mit Name, Kategorie, Adresse und mindestens einer Kontaktperson erfasse und speichere
    - **Dann** ist die juristische Person gespeichert und erscheint mit ihrer Kategorie in der Personenliste
- [ ] **Szenario 2:** Ohne Kontaktperson nicht speicherbar
    - **Gegeben** ich erfasse eine neue juristische Person
    - **Wenn** ich speichere, ohne eine Kontaktperson erfasst zu haben
    - **Dann** wird nicht gespeichert und ich sehe die Meldung «Bitte mindestens eine Kontaktperson erfassen»
- [ ] **Szenario 3:** Weitere Kontaktperson mit Aufgabe hinzufügen
    - **Gegeben** eine juristische Person mit einer Kontaktperson (Aufgabe «Finanzen») ist gespeichert
    - **Wenn** ich eine weitere Kontaktperson mit Vorname, Nachname, E-Mail und der Aufgabe «Sublimation» hinzufüge und speichere
    - **Dann** sind beide Kontaktpersonen mit ihrer jeweiligen Aufgabe bei der juristischen Person sichtbar
- [ ] **Szenario 4:** Kontaktperson ohne Aufgabe
    - **Gegeben** ich erfasse eine Kontaktperson
    - **Wenn** ich das Feld «Aufgabe» leer lasse und speichere
    - **Dann** wird die Kontaktperson trotzdem gespeichert
- [ ] **Szenario 5:** Kontaktperson bearbeiten
    - **Gegeben** eine Kontaktperson hat die Aufgabe «Finanzen»
    - **Wenn** ich die Aufgabe auf «Finanzen, Sponsoring» ändere und speichere
    - **Dann** wird bei der Kontaktperson die neue Aufgabe angezeigt
- [ ] **Szenario 6:** Kontaktperson entfernen
    - **Gegeben** eine juristische Person hat zwei Kontaktpersonen
    - **Wenn** ich eine davon entferne
    - **Dann** ist nur noch die andere Kontaktperson erfasst
- [ ] **Szenario 7:** Letzte Kontaktperson kann nicht entfernt werden
    - **Gegeben** eine juristische Person hat genau eine Kontaktperson
    - **Wenn** ich diese entfernen will
    - **Dann** wird das verhindert und ich sehe die Meldung «Mindestens eine Kontaktperson ist erforderlich»
- [ ] **Szenario 8:** Juristische Person bearbeiten
    - **Gegeben** eine juristische Person ist gespeichert
    - **Wenn** ich z. B. Name, Kategorie oder Adresse ändere und speichere
    - **Dann** werden die Änderungen gespeichert und überall angezeigt
- [ ] **Szenario 9:** Juristische Person deaktivieren
    - **Gegeben** eine aktive juristische Person ist gespeichert
    - **Wenn** ich sie deaktiviere
    - **Dann** wird sie als inaktiv markiert, sie und ihre Kontaktpersonen erscheinen nicht mehr als Empfänger beim E-Mail-Versand, und die Daten bleiben erhalten (Reaktivieren möglich)
- [ ] **Szenario 10:** Juristische Person löschen
    - **Gegeben** eine juristische Person ist gespeichert
    - **Wenn** ich sie lösche und die Sicherheitsabfrage bestätige
    - **Dann** werden die juristische Person, alle ihre Kontaktpersonen und ihre Gruppenzuordnungen entfernt

## 🗂️ Datenanforderungen / Attribute

### Juristische Person

| Attribut          | Typ                       | Pflicht | Validierung / Regeln                    | Beispiel        |
|-------------------|---------------------------|---------|-----------------------------------------|-----------------|
| Name              | Text                      | ✅      | 2 – 200 Zeichen                         | Jensen AG       |
| Kategorie         | Enum                      | ✅      | Sponsor / Gönner / Partner / Gemeinde   | Sponsor         |
| Adresse           | siehe «Adressen verwalten» | ✅      | Strasse, Nr., PLZ, Ort                  | Dorfstrasse 12, 8000 Zürich |
| Allgemeine E-Mail | E-Mail                    | ❌      | gültiges Format                         | info@jensen.ch  |
| Status            | Enum                      | ✅      | aktiv / inaktiv, Standard: aktiv        | aktiv           |
| Kontaktpersonen   | Liste                     | ✅      | mindestens 1 (siehe unten)              | –               |

### Kontaktperson (1–n pro juristische Person)

| Attribut  | Typ    | Pflicht | Validierung / Regeln                   | Beispiel               |
|-----------|--------|---------|----------------------------------------|------------------------|
| Vorname   | Text   | ✅      | 2 – 50 Zeichen                         | Anna                   |
| Nachname  | Text   | ✅      | 2 – 50 Zeichen                         | Muster                 |
| E-Mail    | E-Mail | ✅      | gültiges Format                        | anna.muster@jensen.ch  |
| Telefon   | Text   | ❌      | internationales Format                 | +41 79 876 54 32       |
| Aufgabe   | Text   | ❌      | Freitext, max. 100 Zeichen             | Finanzen, Sublimation  |

## 🛠️ Technische Hinweise

- Das aktuelle Schema hat in `person` nur das Feld `contact_person VARCHAR(200)`. Das reicht für 1–n Kontaktpersonen nicht.
  Vorschlag: neue Tabelle `legal_contact` (`id`, `person_id` → `person.id` mit `kind = 'legal'`, `first_name`, `last_name`, `email`, `phone`, `task VARCHAR(100)`, `ON DELETE CASCADE`) und `person.contact_person` entfernen.
- Bei juristischen Personen bleiben `first_name`, `last_name`, `birthdate`, `ahv_number` usw. leer.
- Für den Status (aktiv/inaktiv) fehlt im Schema noch ein Feld (z. B. `active TINYINT(1)`).

## ❓ Offene Fragen

- Kann eine juristische Person mehrere Kategorien haben (z. B. Sponsor **und** Partner)?
- Braucht es einen Hauptkontakt (z. B. für «z. Hd.» in der Postadresse)?
- An wen gehen E-Mails beim Gruppenversand: an die allgemeine E-Mail, an alle Kontaktpersonen oder nur an den Hauptkontakt?
- Soll später nach Aufgabe gefiltert werden (z. B. alle Kontaktpersonen «Finanzen»)? Dann wäre eine Auswahlliste statt Freitext sinnvoll.

## 🔒 Nicht-funktionale Anforderungen

- Nur die Rollen Admin und Geschäftsstelle dürfen juristische Personen und Kontaktpersonen bearbeiten.
- Kontaktpersonen sind Personendaten (revDSG): Sie müssen auf Wunsch vollständig gelöscht werden können.

## 🚫 Nicht im Umfang (Out of Scope)

- Verwaltung von Sponsoring-Verträgen, Beträgen und Rechnungen
