---
name: 📘 Natürliche Personen verwalten
about: Mitglieder und Erziehungsberechtigte als natürliche Personen anlegen und verwalten
title: "[Story] Natürliche Personen verwalten"
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** Geschäftsführer
**möchte ich** Teammitglieder (Spielerinnen und Coaches), Passiv- und Ehrenmitglieder sowie Erziehungsberechtigte als natürliche Personen erfassen und verwalten,
**damit** wir sie den passenden Gruppen zuordnen und sie kontaktieren können.

## 🧭 Kontext & Hintergrund

- Epic: Stammdaten verwalten
- Verwandte Stories: Adressen verwalten, Juristische Personen verwalten, Mitglied einem Team zuordnen
- Grundlage: `specifications/models/people.md`. Die natürliche Person implementiert die Allgemeine Person (Mitgliederart, E-Mail, Telefon, Adresse (optional), Notiz). Natürliche Personen sind z. B. Spielerinnen, Coaches, Elternteile, Helfer und Funktionäre.
- Spielerinnen und Coaches sind **Teammitglieder** (Spezialisierung der natürlichen Person) mit AHV-Nummer, J+S-Nummer und Einwilligung Foto (nur Minderjährige). Die «Anzahl Helfereinsätze pro Saison» wird später behandelt (siehe `specifications/models/helpers.md`).
  - Ist eine Person Spielerin **und** Coach, gibt es nur **einen** Datensatz; die Rolle (Spielerin/Coach) wird pro Gruppenmitgliedschaft festgelegt (siehe «Mitglied einem Team zuordnen»).
  - Die Shirtnummer gehört zur Teammitgliedschaft, nicht zur Person.
- Eintritts- und Austrittsdatum gibt es bei der Person nicht mehr. Die Zugehörigkeit zum Verein ergibt sich aus den Gruppenmitgliedschaften pro Saison (siehe `specifications/models/groups.md`). Ein Austritt während der Saison wird an der Gruppenmitgliedschaft mit dem Attribut «Ausgetreten» erfasst.
- Das **Geburtsdatum** ist ein Attribut der natürlichen Person. Es ist Pflicht für Teammitglieder und für Helfer (Prüfung des Mindestalters einer Einsatzart, siehe `specifications/models/helpers.md`), für alle anderen Personen optional.
- **Austritt aus dem Verein:** Alle Gruppenmitgliedschaften der Person enden auf Ende der aktuellen Saison.
- Minderjährig sein können nur Teammitglieder und einzelne Helfer (z. B. Geschwister, die mithelfen). Erziehungsberechtigte werden nur bei **minderjährigen Teammitgliedern** erfasst (n:n – ein Kind kann mehrere Erziehungsberechtigte haben, ein Elternteil mehrere Kinder). Bei minderjährigen Helfern wird das Geburtsdatum erfasst, aber keine Erziehungsberechtigten.
- Die Einwilligung Foto wird nur bei minderjährigen Teammitgliedern erfasst; bei Helfern ist sie nicht relevant.

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Teammitglied anlegen
    - **Gegeben** ich bin mit der Rolle Admin oder Geschäftsstelle angemeldet
    - **Wenn** ich eine Person mit Vorname, Nachname, Mitgliederart «Aktiv» und Geburtsdatum als Teammitglied erfasse und speichere
    - **Dann** ist die Person als Teammitglied gespeichert, erscheint in der Personenliste und kann einem Team als Spielerin oder Coach zugeordnet werden
- [ ] **Szenario 2:** Teammitglied ohne Geburtsdatum
    - **Gegeben** ich erfasse ein Teammitglied
    - **Wenn** ich ohne Geburtsdatum speichere
    - **Dann** wird nicht gespeichert und ich sehe die Meldung «Geburtsdatum ist für Teammitglieder erforderlich»
- [ ] **Szenario 3:** Passivmitglied, Ehrenmitglied oder Elternteil anlegen
    - **Gegeben** ich erfasse eine Person mit Mitgliederart «Passiv», «Ehrenmitglied» oder «Elternteil»
    - **Wenn** ich ohne Geburtsdatum, AHV- und J+S-Nummer speichere
    - **Dann** wird die Person gespeichert; das Geburtsdatum ist optional, die Felder AHV-Nummer, J+S-Nummer und Einwilligung Foto werden bei diesen Personen nicht angeboten
- [ ] **Szenario 3a:** Helfer ohne Geburtsdatum
    - **Gegeben** Tom Muster (Bruder einer Spielerin) ist ohne Geburtsdatum erfasst
    - **Wenn** ich ihn als Helfer einem Einsatz zuteilen will
    - **Dann** wird die Zuteilung mit der Meldung «Für Helfer ist ein Geburtsdatum erforderlich» abgelehnt
- [ ] **Szenario 3b:** Mindestalter für Helfer
    - **Gegeben** die Einsatzart «Spielsekretär» verlangt ein Mindestalter von 16 Jahren und Tom Muster ist laut Geburtsdatum 14 Jahre alt
    - **Wenn** ich ihn diesem Einsatz zuteilen will
    - **Dann** wird die Zuteilung mit der Meldung «Mindestalter für diesen Einsatz: 16 Jahre» abgelehnt
- [ ] **Szenario 4:** Ungültige AHV-Nummer
    - **Gegeben** ich erfasse ein Teammitglied
    - **Wenn** ich eine AHV-Nummer mit falschem Format oder falscher Prüfziffer eingebe
    - **Dann** wird nicht gespeichert und ich sehe die Meldung «Bitte gültige AHV-Nummer eingeben (756.XXXX.XXXX.XX)»
- [ ] **Szenario 5:** Minderjährige mit Erziehungsberechtigten verknüpfen
    - **Gegeben** eine minderjährige Spielerin und ein Elternteil sind erfasst
    - **Wenn** ich das Elternteil als erziehungsberechtigt mit der Spielerin verknüpfe
    - **Dann** ist die Beziehung bei beiden Personen sichtbar
- [ ] **Szenario 6:** Erziehungsberechtigte nur bei Minderjährigen
    - **Gegeben** ein Teammitglied ist laut Geburtsdatum volljährig
    - **Wenn** ich es bearbeite
    - **Dann** kann ich keine Erziehungsberechtigten verknüpfen; dasselbe gilt für Personen, die keine Teammitglieder sind (z. B. Helfer)
- [ ] **Szenario 7:** Person bearbeiten
    - **Gegeben** eine Person ist gespeichert
    - **Wenn** ich z. B. Name, Mitgliederart oder Foto-Einwilligung ändere und speichere
    - **Dann** werden die Änderungen gespeichert und überall angezeigt
- [ ] **Szenario 8:** Austritt aus dem Verein
    - **Gegeben** Anna Muster ist in der aktuellen Saison 2026/27 (bis 30.06.2027) Mitglied der Gruppen «U18» und «Passivmitglieder»
    - **Wenn** ich «Austritt aus dem Verein» wähle und die Sicherheitsabfrage bestätige
    - **Dann** wird bei allen ihren Mitgliedschaften der aktuellen Saison «Ausgetreten» auf das Saisonende 30.06.2027 gesetzt; sie bleibt bis dahin in ihren Gruppen, gilt ab dem 01.07.2027 als inaktiv und erscheint ab dann nicht mehr als Empfängerin beim E-Mail-Versand; ihre Daten und Mitgliedschaften bleiben erhalten
- [ ] **Szenario 8a:** Austritt aus dem Verein mit bereits erfasstem Teamaustritt
    - **Gegeben** bei Anna Muster ist im Team «U18» bereits «Ausgetreten» mit dem Datum 31.12.2026 erfasst
    - **Wenn** ich «Austritt aus dem Verein» wähle
    - **Dann** bleibt das frühere Datum 31.12.2026 bei dieser Mitgliedschaft erhalten; nur die übrigen Mitgliedschaften enden auf Saisonende
- [ ] **Szenario 8b:** Austritt aus dem Verein mit Mitgliedschaften in künftigen Saisons
    - **Gegeben** Anna Muster ist bereits für die Saison 2027/28 dem Team «U18» zugeordnet
    - **Wenn** ich «Austritt aus dem Verein» wähle
    - **Dann** werden die Mitgliedschaften in künftigen Saisons entfernt
- [ ] **Szenario 9:** Person löschen
    - **Gegeben** eine Person ist gespeichert
    - **Wenn** ich sie lösche und die Sicherheitsabfrage bestätige
    - **Dann** werden die Person sowie ihre Beziehungen, Gruppenmitgliedschaften, Teilnahmen und Helfereinsätze entfernt
- [ ] **Szenario 10:** Notiz erfassen
    - **Gegeben** ich bearbeite eine Person
    - **Wenn** ich im Notizfeld einen Text eingebe und speichere
    - **Dann** wird die Notiz gespeichert und bei der Person angezeigt
- [ ] **Szenario 11:** Spielerin und Coach in einem Datensatz
    - **Gegeben** Lea Meier ist als Teammitglied erfasst
    - **Wenn** sie im Team «U18» als Spielerin und im Team «U12» als Coach zugeordnet wird
    - **Dann** gibt es für Lea Meier weiterhin nur einen Personen-Datensatz; Adress- und Kontaktänderungen gelten für beide Rollen
- [ ] **Szenario 12:** Einwilligung Foto nur bei minderjährigen Teammitgliedern
    - **Gegeben** ein Teammitglied ist laut Geburtsdatum minderjährig
    - **Wenn** ich sie bearbeite
    - **Dann** kann ich die Einwilligung Foto (Ja/Nein, Standard: Nein) erfassen; bei volljährigen Teammitgliedern und bei Personen, die keine Teammitglieder sind (z. B. Helfer), wird das Feld nicht angeboten

## 🗂️ Datenanforderungen / Attribute

### Natürliche Person (inkl. Allgemeine Person)

| Attribut              | Typ                        | Pflicht | Validierung / Regeln                                             | Beispiel          |
|-----------------------|----------------------------|---------|------------------------------------------------------------------|-------------------|
| Vorname               | Text                       | ✅      | 2 – 50 Zeichen                                                   | Anna              |
| Nachname              | Text                       | ✅      | 2 – 50 Zeichen                                                   | Muster            |
| Mitgliederart         | Enum                       | ✅      | Aktiv / Passiv / Elternteil / Ehrenmitglied (erweiterbar)        | Aktiv             |
| Geburtsdatum          | Datum                      | bedingt | Pflicht für Teammitglieder und Helfer; nicht in der Zukunft      | 12.05.2002        |
| Kinder                | Verknüpfung (Gegenrichtung) | ❌     | ergibt sich aus «Erziehungsberechtigte» der Teammitglieder       | Anna Muster       |
| Adresse, E-Mail, Telefon | siehe «Adressen verwalten» | –     | Adresse optional                                                 | –                 |
| Notiz                 | Text                       | ❌      | Freitext                                                         | Allergie: Nüsse   |

### Zusätzlich bei Teammitgliedern (Spielerinnen und Coaches)

| Attribut                      | Typ     | Pflicht | Validierung / Regeln                                              | Beispiel          |
|-------------------------------|---------|---------|-------------------------------------------------------------------|-------------------|
| AHV-Nummer                    | Text    | ❌      | Format 756.XXXX.XXXX.XX, Prüfziffer (EAN-13)                      | 756.1234.5678.97  |
| J+S-Nummer                    | Text    | ❌      | max. 30 Zeichen                                                   | 1234567           |
| Einwilligung Foto             | Ja/Nein | bedingt | nur bei Minderjährigen; Standard: Nein                            | Ja                |
| Erziehungsberechtigte         | Verknüpfung (n:n) | ❌ | nur bei Minderjährigen; nur mit natürlichen Personen; nicht mit sich selbst | Peter Muster |

**Abgeleitet:** Minderjährig ergibt sich aus dem Geburtsdatum. Die Person gilt als aktives Vereinsmitglied, wenn sie in der aktuellen Saison Mitglied mindestens einer Gruppe ist und dort «Ausgetreten» leer oder noch nicht erreicht ist.

## 🛠️ Technische Hinweise

- Klassen gemäss `specifications/models/people.md`: `AbstractPerson` (abstrakt), `NaturalPerson` und `TeamMember` (erbt von `NaturalPerson`). Die bisherigen Klassen `ActivePlayer` und `Coach` entfallen.
- Das Geburtsdatum ist ein Attribut von `NaturalPerson`; die Pflicht für Teammitglieder wird in `TeamMember` geprüft, die Pflicht für Helfer beim Zuteilen zu einem Einsatz.
- Die Einwilligung Foto ist ein Attribut von `TeamMember`.
- Die Beziehung Erziehungsberechtigte ↔ Kinder ist eine n:n-Beziehung zwischen `TeamMember` (Kind) und `NaturalPerson` (erziehungsberechtigte Person).
- Validierung (Pflichtfelder je Personenart, AHV-Prüfziffer, Minderjährigkeit) erfolgt in den Settern.

## ❓ Offene Fragen

- Ist bei minderjährigen Teammitgliedern mindestens ein Erziehungsberechtigter Pflicht?
- Kann eine Person mehrere Mitgliederarten gleichzeitig haben (z. B. Passivmitglied **und** Elternteil)?

## 🔒 Nicht-funktionale Anforderungen

- Die AHV-Nummer ist besonders sensibel: Nur Admin und Geschäftsstelle dürfen sie sehen; sie erscheint nicht in Listen oder Exporten für Coaches.
- Fotos von minderjährigen Teammitgliedern dürfen nur mit «Einwilligung Foto = Ja» veröffentlicht werden.
