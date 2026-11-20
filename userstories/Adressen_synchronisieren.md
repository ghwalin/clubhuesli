---
name: 📘 Adressen mit Buchhaltung synchronisieren
about: Neue/geänderte Adressen werden in das Buchhaltungsprogramm übernommen
title: "[Story] Adressen synchronisieren"
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** Finanzverantwortliche
**möchte ich** neue/geänderte Adressen automatisch in die Buchhaltung übernehmen,
**damit** ich immer die aktuellsten Kontaktdaten habe.

## 🧭 Kontext & Hintergrund

- Epic: Stammdaten verwalten
- Verwandte Stories: Adressen verwalten, Natürliche Personen verwalten, Juristische Personen verwalten
- Die Stammdaten werden ausschliesslich in Clubhüsli gepflegt (**führendes System**). Die Buchhaltung erhält die Adressen nur als Kopie; Änderungen in der Buchhaltung werden **nicht** zurückgespielt (Einweg-Synchronisation).
- Abgeglichen werden Postadressen von Personen, die Rechnungen erhalten können: Aktiv-, Passiv- und Ehrenmitglieder, Erziehungsberechtigte sowie juristische Personen (Sponsoren, Gönner, Partner, Gemeinden). Bei juristischen Personen kann die Kontaktperson mit der Aufgabe «Finanzen» als Rechnungskontakt dienen.
- Bei Minderjährigen ist üblicherweise der Erziehungsberechtigte der Rechnungsempfänger.
- Das Buchhaltungsprogramm ist noch nicht festgelegt (siehe offene Fragen).

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Neue Person wird übernommen
    - **Gegeben** eine neue Person (Mitglied oder juristische Person) wurde mit vollständiger Adresse erfasst
    - **Wenn** die Synchronisation läuft
    - **Dann** wird in der Buchhaltung ein neuer Adressdatensatz mit Name, Strasse, PLZ, Ort, Land und E-Mail angelegt, und die Person erhält die Buchhaltungs-Referenz
- [ ] **Szenario 2:** Geänderte Adresse wird aktualisiert
    - **Gegeben** eine Person ist bereits mit der Buchhaltung verknüpft
    - **Wenn** ich ihre Adresse (z. B. Strasse, PLZ oder Ort) ändere und die Synchronisation läuft
    - **Dann** wird der bestehende Datensatz in der Buchhaltung aktualisiert und **kein** Duplikat erzeugt
- [ ] **Szenario 3:** Unveränderte Daten werden nicht erneut übertragen
    - **Gegeben** seit der letzten Synchronisation hat sich bei einer Person nichts geändert
    - **Wenn** die Synchronisation läuft
    - **Dann** wird diese Person übersprungen
- [ ] **Szenario 4:** Namensänderung wird übernommen
    - **Gegeben** eine verknüpfte Person ändert ihren Nachnamen (z. B. nach Heirat)
    - **Wenn** die Synchronisation läuft
    - **Dann** wird der Name in der Buchhaltung aktualisiert, die bisherigen Buchungen bleiben dem Datensatz zugeordnet
- [ ] **Szenario 5:** Person wird deaktiviert
    - **Gegeben** eine verknüpfte Person wird in Clubhüsli deaktiviert (z. B. bei Austritt)
    - **Wenn** die Synchronisation läuft
    - **Dann** wird der Datensatz in der Buchhaltung als inaktiv markiert, aber **nicht** gelöscht (offene Posten und Buchungshistorie bleiben erhalten)
- [ ] **Szenario 6:** Person wird gelöscht
    - **Gegeben** eine verknüpfte Person wird in Clubhüsli gelöscht
    - **Wenn** die Synchronisation läuft
    - **Dann** wird der Buchhaltungs-Datensatz nicht automatisch gelöscht, sondern ich sehe einen Hinweis, dass er manuell geprüft werden muss
- [ ] **Szenario 7:** Unvollständige Adresse
    - **Gegeben** einer Person fehlen Pflichtangaben für die Buchhaltung (z. B. Strasse, PLZ oder Ort)
    - **Wenn** die Synchronisation läuft
    - **Dann** wird diese Person nicht übertragen und erscheint im Synchronisationsprotokoll mit dem Grund «Adresse unvollständig»; die übrigen Personen werden trotzdem synchronisiert
- [ ] **Szenario 8:** Buchhaltung nicht erreichbar
    - **Gegeben** die Buchhaltung ist nicht erreichbar oder lehnt die Anmeldung ab
    - **Wenn** die Synchronisation läuft
    - **Dann** wird sie abgebrochen, es gehen keine Änderungen verloren, und ich sehe die Meldung «Synchronisation fehlgeschlagen» mit der Fehlerursache; beim nächsten Lauf werden die offenen Änderungen nachgeholt
- [ ] **Szenario 9:** Synchronisation manuell auslösen
    - **Gegeben** ich bin mit der Rolle Admin oder Finanzen angemeldet
    - **Wenn** ich «Jetzt synchronisieren» wähle
    - **Dann** werden alle seit der letzten Synchronisation neuen oder geänderten Adressen übertragen, und ich sehe eine Zusammenfassung (neu, aktualisiert, übersprungen, fehlerhaft)
- [ ] **Szenario 10:** Synchronisationsprotokoll einsehen
    - **Gegeben** es wurde mindestens eine Synchronisation durchgeführt
    - **Wenn** ich das Protokoll öffne
    - **Dann** sehe ich pro Lauf Zeitpunkt, Auslöser (automatisch/manuell), Anzahl Datensätze und je Fehler die betroffene Person samt Ursache
- [ ] **Szenario 11:** Zuordnung bestehender Buchhaltungsadressen
    - **Gegeben** in der Buchhaltung existiert bereits ein Adressdatensatz zu einer Person, die in Clubhüsli noch nicht verknüpft ist
    - **Wenn** die erste Synchronisation läuft
    - **Dann** wird die Person nicht blind neu angelegt, sondern ich erhalte einen Zuordnungsvorschlag (gleicher Name und PLZ) zur Bestätigung

## 🗂️ Datenanforderungen / Attribute

### Übertragene Felder

| Attribut (Clubhüsli)   | Typ    | Pflicht für Sync | Regeln                                                          | Beispiel            |
|------------------------|--------|------------------|-----------------------------------------------------------------|---------------------|
| Name                   | Text   | ✅               | natürliche Person: Vorname + Nachname; juristische Person: Name | Anna Muster         |
| Strasse + Hausnummer   | Text   | ✅               | wie in «Adressen verwalten»                                     | Dorfstrasse 12a     |
| Adresszusatz           | Text   | ❌               | z. B. Postfach, c/o                                             | Postfach 123        |
| PLZ                    | Text   | ✅               | wie in «Adressen verwalten»                                     | 8000                |
| Ort                    | Text   | ✅               | –                                                               | Zürich              |
| Land                   | Enum   | ✅               | ISO-Ländercode                                                  | CH                  |
| E-Mail                 | E-Mail | ❌               | gültiges Format                                                 | anna@example.ch     |
| Status                 | Enum   | ✅               | aktiv / inaktiv                                                 | aktiv               |

### Synchronisationsdaten (pro Person)

| Attribut                | Typ       | Pflicht | Regeln                                                         | Beispiel              |
|-------------------------|-----------|---------|----------------------------------------------------------------|-----------------------|
| Buchhaltungs-ID         | Text      | ❌      | wird nach erster Übertragung gesetzt; eindeutig                | 10045                 |
| Zuletzt synchronisiert  | Zeitpunkt | ❌      | wird bei erfolgreicher Übertragung gesetzt                     | 2026-11-16 06:00      |
| Prüfsumme der Adresse   | Text      | ❌      | zur Erkennung von Änderungen                                   | a3f9…                 |

### Synchronisationsprotokoll

| Attribut   | Typ       | Pflicht | Regeln                                              | Beispiel                    |
|------------|-----------|---------|-----------------------------------------------------|-----------------------------|
| Zeitpunkt  | Zeitpunkt | ✅      | –                                                   | 2026-11-16 06:00            |
| Auslöser   | Enum      | ✅      | automatisch / manuell                               | automatisch                 |
| Ergebnis   | Enum      | ✅      | erfolgreich / teilweise / fehlgeschlagen            | teilweise                   |
| Details    | Text      | ❌      | Anzahl neu / aktualisiert / übersprungen / Fehler   | 3 neu, 5 aktualisiert, 1 Fehler |

## 🛠️ Technische Hinweise

- Im Schema fehlen Felder für die Verknüpfung, z. B. `person.accounting_id` und `person.synced_at` (bzw. eine eigene Tabelle `accounting_sync` mit `person_id`, `accounting_id`, `synced_at`, `hash`) sowie eine Tabelle `sync_log`.
- Änderungserkennung über `updated_at` bzw. Prüfsumme der Adressfelder; so werden nur tatsächlich geänderte Datensätze übertragen.
- Die Synchronisation sollte **idempotent** sein: Ein wiederholter Lauf darf keine Duplikate erzeugen.
- Zugangsdaten bzw. API-Schlüssel der Buchhaltung gehören in die Konfiguration, nicht in die Datenbank oder ins Repository.
- Fehler einzelner Datensätze brechen den Gesamtlauf nicht ab; nur ein Verbindungs-/Authentifizierungsfehler bricht ihn ab.

## ❓ Offene Fragen

- Welches Buchhaltungsprogramm wird verwendet (z. B. bexio, Abacus, Banana, Run my Accounts) und gibt es eine API oder nur Import per CSV?
- Soll automatisch (z. B. nachts) oder nur manuell synchronisiert werden – oder beides?
- Wer ist bei Minderjährigen Rechnungsempfänger: der Erziehungsberechtigte oder die Person selbst? Sollen Kinder überhaupt in die Buchhaltung übertragen werden?
- Welche Personengruppen sollen übertragen werden (alle oder nur Personen, die Rechnungen erhalten, z. B. Mitgliederbeiträge)?
- Soll bei juristischen Personen die Kontaktperson «Finanzen» als «z. Hd.» mitübertragen werden?
- Wie soll mit Konflikten umgegangen werden, wenn Adressen in der Buchhaltung manuell geändert wurden (überschreiben oder melden)?

## 🔒 Nicht-funktionale Anforderungen

- Nur die Rollen Admin und Finanzen dürfen die Synchronisation auslösen und das Protokoll einsehen.
- Übertragen werden nur die für die Buchhaltung nötigen Felder; AHV-Nummer, Geburtsdatum, Foto-Einwilligung und J+S-Nummer werden **nicht** übermittelt (Datensparsamkeit, revDSG).
- Die Übertragung erfolgt verschlüsselt (HTTPS).
- Ein Lauf mit bis zu 1000 Personen soll in unter 5 Minuten abgeschlossen sein.

## 🚫 Nicht im Umfang (Out of Scope)

- Rückübertragung von Adressänderungen aus der Buchhaltung nach Clubhüsli
- Erstellen von Rechnungen, Buchungen oder Zahlungsabgleich
- Übertragung von Telefonnummern und weiteren Kontaktpersonen
