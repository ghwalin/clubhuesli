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
- Grundlage: `specifications/models/people.md`. Die natürliche Person implementiert die Allgemeine Person (Mitgliederart, E-Mail, Telefon, Adresse, Eintritt/Austritt, Notiz).
- Aktivmitglieder sind **Aktive Spielerinnen** und/oder **Coaches** (Spezialisierungen der natürlichen Person).
  - Aktive Spielerin: Geburtsdatum, AHV-Nummer, J+S-Nummer, Einwilligung Foto (nur Minderjährige), Anzahl Helfereinsätze pro Saison.
  - Coach: AHV-Nummer, J+S-Nummer.
  - Spielerin und Coach sind getrennte Klassen. Ist eine Person beides, wird sie als **zwei Datensätze** erfasst (mit je eigenen Gruppenzugehörigkeiten).
  - Die Shirtnummer gehört zur Teamzugehörigkeit der Spielerin, nicht zur Person (siehe «Mitglied einem Team zuordnen»).
- Eintritt und Austritt aus dem Verein sind je ein Datum und können mehrfach vorkommen (Austritt und späterer Wiedereintritt). Ein separates Statusfeld gibt es nicht: «aktiv» heisst, dass ein Zeitraum aktuell läuft (Eintritt erreicht, Austritt leer oder noch nicht erreicht).
- Minderjährige werden mit ihren Erziehungsberechtigten verknüpft (n:n – ein Kind kann mehrere Erziehungsberechtigte haben, ein Elternteil mehrere Kinder).

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Aktivmitglied anlegen
    - **Gegeben** ich bin mit der Rolle Admin oder Geschäftsstelle angemeldet
    - **Wenn** ich eine Person mit Vorname, Nachname, Mitgliederart «Aktiv», Geburtsdatum, Adresse und Eintrittsdatum erfasse und speichere
    - **Dann** ist die Person als Aktive Spielerin gespeichert, gilt ab dem Eintrittsdatum als Vereinsmitglied und erscheint in der Personenliste
- [ ] **Szenario 2:** Aktive Spielerin ohne Geburtsdatum
    - **Gegeben** ich erfasse eine Aktive Spielerin
    - **Wenn** ich ohne Geburtsdatum speichere
    - **Dann** wird nicht gespeichert und ich sehe die Meldung «Geburtsdatum ist für Aktive Spielerinnen erforderlich»
- [ ] **Szenario 3:** Passivmitglied, Ehrenmitglied oder Elternteil anlegen
    - **Gegeben** ich erfasse eine Person mit Mitgliederart «Passiv», «Ehrenmitglied» oder «Elternteil»
    - **Wenn** ich ohne Geburtsdatum, AHV- und J+S-Nummer speichere
    - **Dann** wird die Person gespeichert; die Felder Geburtsdatum, AHV-Nummer, J+S-Nummer, Einwilligung Foto und Helfereinsätze werden bei diesen Mitgliederarten nicht angeboten
- [ ] **Szenario 4:** Ungültige AHV-Nummer
    - **Gegeben** ich erfasse eine Aktive Spielerin oder einen Coach
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
- [ ] **Szenario 7:** Austritt aus dem Verein erfassen
    - **Gegeben** eine Person ist seit dem 01.08.2024 Vereinsmitglied (ohne Austrittsdatum)
    - **Wenn** ich als Austrittsdatum den 30.06.2026 erfasse (z. B. bei Austritt oder Tod)
    - **Dann** gilt sie ab dem 01.07.2026 als inaktiv, erscheint ab dann nicht mehr als Empfängerin beim E-Mail-Versand, und die Daten sowie die Mitgliedschaftshistorie bleiben erhalten
- [ ] **Szenario 8:** Person löschen
    - **Gegeben** eine Person ist gespeichert
    - **Wenn** ich sie lösche und die Sicherheitsabfrage bestätige
    - **Dann** werden die Person sowie ihre Beziehungen, Gruppenzugehörigkeiten, Teilnahmen und Helfereinsätze entfernt
- [ ] **Szenario 9:** Wiedereintritt
    - **Gegeben** eine Person war vom 01.08.2020 bis 30.06.2023 Vereinsmitglied
    - **Wenn** ich ein neues Eintrittsdatum 01.08.2025 erfasse
    - **Dann** werden beide Zeiträume gespeichert, und die Person gilt ab dem 01.08.2025 wieder als aktiv
- [ ] **Szenario 10:** Austrittsdatum vor Eintrittsdatum
    - **Gegeben** ein Mitgliedschaftszeitraum mit Eintrittsdatum 01.08.2026
    - **Wenn** ich als Austrittsdatum den 31.07.2026 erfasse
    - **Dann** wird nicht gespeichert und ich sehe die Meldung «Austrittsdatum darf nicht vor dem Eintrittsdatum liegen»
- [ ] **Szenario 11:** Notiz erfassen
    - **Gegeben** ich bearbeite eine Person
    - **Wenn** ich im Notizfeld einen Text eingebe und speichere
    - **Dann** wird die Notiz gespeichert und bei der Person angezeigt
- [ ] **Szenario 12:** Coach anlegen
    - **Gegeben** ich erfasse eine Person mit Mitgliederart «Aktiv» als Coach
    - **Wenn** ich Vorname, Nachname, Adresse, Eintrittsdatum sowie AHV- und J+S-Nummer eingebe und speichere
    - **Dann** ist die Person als Coach gespeichert; Geburtsdatum, Einwilligung Foto und Helfereinsätze werden nicht angeboten
- [ ] **Szenario 13:** Einwilligung Foto nur bei Minderjährigen
    - **Gegeben** eine Aktive Spielerin ist laut Geburtsdatum minderjährig
    - **Wenn** ich sie bearbeite
    - **Dann** kann ich die Einwilligung Foto (Ja/Nein, Standard: Nein) erfassen; bei volljährigen Spielerinnen wird das Feld nicht angeboten

## 🗂️ Datenanforderungen / Attribute

### Natürliche Person (inkl. Allgemeine Person)

| Attribut              | Typ                        | Pflicht | Validierung / Regeln                                             | Beispiel          |
|-----------------------|----------------------------|---------|------------------------------------------------------------------|-------------------|
| Vorname               | Text                       | ✅      | 2 – 50 Zeichen                                                   | Anna              |
| Nachname              | Text                       | ✅      | 2 – 50 Zeichen                                                   | Muster            |
| Mitgliederart         | Enum                       | ✅      | Aktiv / Passiv / Elternteil / Ehrenmitglied (erweiterbar)        | Aktiv             |
| Eintrittsdatum        | Datum (mehrfach)           | ✅      | mindestens ein Zeitraum; Zeiträume überschneiden sich nicht      | 01.08.2024        |
| Austrittsdatum        | Datum (mehrfach)           | ❌      | ≥ Eintrittsdatum desselben Zeitraums                             | 30.06.2026        |
| Erziehungsberechtigte | Verknüpfung (n:n, optional) | ❌     | nur mit natürlichen Personen; nicht mit sich selbst              | Peter Muster      |
| Kinder                | Verknüpfung (Gegenrichtung) | ❌     | ergibt sich aus «Erziehungsberechtigte»                          | Anna Muster       |
| Adresse, E-Mail, Telefon | siehe «Adressen verwalten» | ✅    | –                                                                | –                 |
| Notiz                 | Text                       | ❌      | Freitext                                                         | Allergie: Nüsse   |

### Zusätzlich bei Aktiven Spielerinnen

| Attribut                      | Typ     | Pflicht | Validierung / Regeln                                              | Beispiel          |
|-------------------------------|---------|---------|-------------------------------------------------------------------|-------------------|
| Geburtsdatum                  | Datum   | ✅      | nicht in der Zukunft                                              | 12.05.2002        |
| AHV-Nummer                    | Text    | ❌      | Format 756.XXXX.XXXX.XX, Prüfziffer (EAN-13)                      | 756.1234.5678.97  |
| J+S-Nummer                    | Text    | ❌      | max. 30 Zeichen                                                   | 1234567           |
| Einwilligung Foto             | Ja/Nein | bedingt | nur bei Minderjährigen; Standard: Nein                            | Ja                |
| Anzahl Helfereinsätze pro Saison | Zahl | ❌      | ganze Zahl ≥ 0 (Pflichteinsätze, siehe Modell «Helpers»)          | 2                 |

### Zusätzlich bei Coaches (Mitgliederart «Aktiv»)

| Attribut   | Typ  | Pflicht | Validierung / Regeln                             | Beispiel          |
|------------|------|---------|--------------------------------------------------|-------------------|
| AHV-Nummer | Text | ❌      | Format 756.XXXX.XXXX.XX, Prüfziffer (EAN-13)     | 756.1234.5678.97  |
| J+S-Nummer | Text | ❌      | max. 30 Zeichen                                  | 1234567           |

**Abgeleitet:** Status aktiv/inaktiv ergibt sich aus den Mitgliedschaftszeiträumen (Eintritt erreicht, Austritt leer oder noch nicht erreicht).

## 🛠️ Technische Hinweise

- Klassen gemäss `specifications/models/people.md`: `AbstractPerson` (abstrakt), `NaturalPerson`, `ActivePlayer` und `Coach` (beide erben von `NaturalPerson`).
- Eintritt/Austritt sind eine eigene Liste von Zeiträumen pro Person (1:n).
- Die Beziehung Erziehungsberechtigte ↔ Kinder ist eine n:n-Beziehung zwischen natürlichen Personen.
- Validierung (Pflichtfelder je Mitgliederart, AHV-Prüfziffer, Datumsreihenfolge) erfolgt in den Settern.

## ❓ Offene Fragen

- Ist bei Minderjährigen mindestens ein Erziehungsberechtigter Pflicht? (Das Alter ist nur bei Aktiven Spielerinnen über das Geburtsdatum bekannt.)
- Soll beim Erfassen eines Vereins-Austrittsdatums automatisch bei allen laufenden Gruppenzugehörigkeiten ein Enddatum gesetzt werden?
- Kann eine Person mehrere Mitgliederarten gleichzeitig haben (z. B. Passivmitglied **und** Elternteil)?
- Wie werden die zwei Datensätze einer Person, die Spielerin und Coach ist, erkannt bzw. verknüpft (gleiche Adresse/E-Mail, Duplikatwarnung, Änderungen von Adresse in beiden Datensätzen)?

## 🔒 Nicht-funktionale Anforderungen

- Die AHV-Nummer ist besonders sensibel: Nur Admin und Geschäftsstelle dürfen sie sehen; sie erscheint nicht in Listen oder Exporten für Coaches.
- Fotos von Minderjährigen dürfen nur mit «Einwilligung Foto = Ja» veröffentlicht werden.
