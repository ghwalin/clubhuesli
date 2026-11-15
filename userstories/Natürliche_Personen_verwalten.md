---
name: 📘 Natürliche Personen verwalten
about: Mitglieder und Erziehungsberechtigte als natürliche Personen anlegen und verwalten
title: "[Story] Natürliche Personen verwalten"
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** Geschäftsführer
**möchte ich** Aktiv-, Passiv- und Ehrenmitglieder sowie Erziehungsberechtigte als natürliche Personen erfassen und verwalten,
**damit** wir sie den passenden Gruppen zuordnen und sie kontaktieren können.

## 🧭 Kontext & Hintergrund

- Epic: Stammdaten verwalten
- Verwandte Stories: Adressen verwalten, Juristische Personen verwalten
- Spielerinnen (Aktivmitglieder) brauchen zusätzliche Angaben (Geburtsdatum, AHV- und J+S-Nummer, Foto-Einwilligung), siehe `Clubadmin.md`.
- Minderjährige werden mit ihren Erziehungsberechtigten verknüpft (n:n – ein Kind kann mehrere Erziehungsberechtigte haben, ein Elternteil mehrere Kinder).

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Aktivmitglied anlegen
    - **Gegeben** ich bin mit der Rolle Admin oder Geschäftsstelle angemeldet
    - **Wenn** ich eine Person mit Vorname, Nachname, Mitgliederart «Aktiv», Geburtsdatum und Adresse erfasse und speichere
    - **Dann** ist die Person gespeichert und erscheint in der Personenliste
- [ ] **Szenario 2:** Aktivmitglied ohne Geburtsdatum
    - **Gegeben** ich erfasse eine Person mit Mitgliederart «Aktiv»
    - **Wenn** ich ohne Geburtsdatum speichere
    - **Dann** wird nicht gespeichert und ich sehe die Meldung «Geburtsdatum ist für Aktivmitglieder erforderlich»
- [ ] **Szenario 3:** Passivmitglied, Ehrenmitglied oder Elternteil anlegen
    - **Gegeben** ich erfasse eine Person mit Mitgliederart «Passiv», «Ehrenmitglied» oder «Elternteil»
    - **Wenn** ich ohne Geburtsdatum, AHV- und J+S-Nummer speichere
    - **Dann** wird die Person gespeichert
- [ ] **Szenario 4:** Ungültige AHV-Nummer
    - **Gegeben** ich erfasse ein Aktivmitglied
    - **Wenn** ich eine AHV-Nummer mit falschem Format oder falscher Prüfziffer eingebe
    - **Dann** wird nicht gespeichert und ich sehe die Meldung «Bitte gültige AHV-Nummer eingeben (756.XXXX.XXXX.XX)»
- [ ] **Szenario 5:** Minderjährige mit Erziehungsberechtigten verknüpfen
    - **Gegeben** eine minderjährige Spielerin und ein Elternteil sind erfasst
    - **Wenn** ich das Elternteil als erziehungsberechtigt mit der Spielerin verknüpfe
    - **Dann** ist die Beziehung bei beiden Personen sichtbar
- [ ] **Szenario 6:** Person bearbeiten
    - **Gegeben** eine Person ist gespeichert
    - **Wenn** ich z. B. Name, Mitgliederart oder Foto-Einwilligung ändere und speichere
    - **Dann** werden die Änderungen gespeichert und überall angezeigt
- [ ] **Szenario 7:** Person deaktivieren
    - **Gegeben** eine aktive Person ist gespeichert
    - **Wenn** ich sie deaktiviere (z. B. bei Austritt oder Tod)
    - **Dann** wird sie als inaktiv markiert, erscheint nicht mehr als Empfängerin beim E-Mail-Versand, und die Daten bleiben erhalten (Reaktivieren möglich)
- [ ] **Szenario 8:** Person löschen
    - **Gegeben** eine Person ist gespeichert
    - **Wenn** ich sie lösche und die Sicherheitsabfrage bestätige
    - **Dann** werden die Person sowie ihre Beziehungen, Gruppenzuordnungen und An-/Abmeldungen entfernt

## 🗂️ Datenanforderungen / Attribute

| Attribut              | Typ                        | Pflicht | Validierung / Regeln                                             | Beispiel          |
|-----------------------|----------------------------|---------|------------------------------------------------------------------|-------------------|
| Vorname               | Text                       | ✅      | 2 – 50 Zeichen                                                   | Anna              |
| Nachname              | Text                       | ✅      | 2 – 50 Zeichen                                                   | Muster            |
| Mitgliederart         | Enum                       | ✅      | Aktiv / Passiv / Elternteil / Ehrenmitglied                      | Aktiv             |
| Geburtsdatum          | Datum                      | bedingt | Pflicht bei Aktivmitgliedern, nicht in der Zukunft               | 12.05.2002        |
| AHV-Nummer            | Text                       | ❌      | nur Aktivmitglieder; Format 756.XXXX.XXXX.XX, Prüfziffer (EAN-13) | 756.1234.5678.97  |
| J+S-Nummer            | Text                       | ❌      | nur Spielerinnen und Coaches; max. 30 Zeichen                    | 1234567           |
| Einwilligung Foto     | Ja/Nein                    | ✅      | Standard: Nein                                                   | Ja                |
| Erziehungsberechtigte | Verknüpfung (n:n)          | ❌      | nur mit natürlichen Personen; nicht mit sich selbst              | Peter Muster      |
| Adresse, E-Mail, Telefon | siehe «Adressen verwalten» | ✅    | –                                                                | –                 |
| Status                | Enum                       | ✅      | aktiv / inaktiv, Standard: aktiv                                 | aktiv             |

## 🛠️ Technische Hinweise

- Das Schema hat mit `member_type` bereits die passenden Mitgliederarten; der Status aktiv/inaktiv fehlt noch (z. B. `person.active`).
- Die Beziehung zu Erziehungsberechtigten ist in `person_relation` abgebildet.

## ❓ Offene Fragen

- Ist bei Minderjährigen mindestens ein Erziehungsberechtigter Pflicht?
- Was passiert beim Deaktivieren mit laufenden Gruppenzuordnungen – sollen sie automatisch ein Enddatum erhalten?
- Kann eine Person mehrere Mitgliederarten gleichzeitig haben (z. B. Passivmitglied **und** Elternteil)?

## 🔒 Nicht-funktionale Anforderungen

- Die AHV-Nummer ist besonders sensibel: Nur Admin und Geschäftsstelle dürfen sie sehen; sie erscheint nicht in Listen oder Exporten für Coaches.
- Fotos dürfen nur von Personen mit «Einwilligung Foto = Ja» veröffentlicht werden.
