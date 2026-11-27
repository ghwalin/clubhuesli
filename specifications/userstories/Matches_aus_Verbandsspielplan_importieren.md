---
name: Matches aus Verbandsspielplan importieren
about: Matches automatisch aus dem Spielplan des Verbandes erstellen und aktualisieren
title: "[Story] Match_import_Verband"
labels: ["user-story", "needs-refinement"]
assignees: []
---
## 📖 User Story

**Als** Coach
**möchte ich**, dass die Matches meines Teams automatisch aus dem Spielplan des Verbandes erstellt und aktualisiert werden,
**damit** ich sie nicht von Hand erfassen muss und die Spieler/innen immer die aktuellen Daten zu Datum, Zeit, Ort und Gegner sehen.

## 🧭 Kontext & Hintergrund

Der Verband führt den offiziellen Spielplan, und Verschiebungen passieren dort. Heute muss der Coach jede Änderung von Hand nachtragen. Das kostet Zeit und führt zu falschen Angaben. Mit dieser Story wird der Verbandsspielplan zur einzigen Quelle für offizielle Matches (Meisterschaft, Cup). Der Coach ergänzt nur noch teaminterne Infos wie Treffpunkt oder Hinweise.

- Epic: Spielbetrieb
- Verwandte Stories:
  - [Story] Match_erstellen (manuelle Erfassung, z. B. für Freundschaftsspiele)
  - [Story] Team_mit_Verband_verknuepfen
  - [Story] Aufgebot_erstellen
  - [Story] Matchbenachrichtigungen

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Team mit Verbandsteam verknüpfen
    - **Gegeben** ich bin als Coach eines Teams angemeldet,
    - **Wenn** ich in den Teameinstellungen das passende Verbandsteam (Verbands-Team-ID) auswähle und speichere,
    - **Dann** ist das Team verknüpft und der erste Import startet automatisch.

- [ ] **Szenario 2:** Neue Matches werden automatisch erstellt
    - **Gegeben** mein Team ist mit einem Verbandsteam verknüpft,
    - **Wenn** der Verbandsspielplan ein Match enthält, das bei uns noch nicht existiert,
    - **Dann** wird das Match mit Gegner, Datum, Anspielzeit, Ort, Heim/Auswärts im Teamkalender erstellt.

- [ ] **Szenario 3:** Änderungen werden übernommen
    - **Gegeben** ein importiertes Match existiert bereits,
    - **Wenn** der Verband Datum, Anspielzeit oder Ort ändert,
    - **Dann** wird das Match bei der nächsten Synchronisation aktualisiert,
    - **Und** die Teammitglieder werden über die Änderung informiert.

- [ ] **Szenario 4:** Verschobene oder abgesagte Matches
    - **Gegeben** ein importiertes Match existiert,
    - **Wenn** der Verband das Match als verschoben oder abgesagt markiert oder es aus dem Spielplan entfernt,
    - **Dann** erhält das Match den Status «verschoben» bzw. «abgesagt» (es wird nicht gelöscht),
    - **Und** die Teammitglieder werden benachrichtigt.

- [ ] **Szenario 5:** Keine Duplikate
    - **Gegeben** ein Match wurde bereits importiert,
    - **Wenn** die Synchronisation erneut läuft,
    - **Dann** wird kein zweites Match erstellt (Abgleich über die Spielnummer des Verbandes).

- [ ] **Szenario 6:** Verbandsdaten sind schreibgeschützt
    - **Gegeben** ich öffne ein importiertes Match,
    - **Wenn** ich es bearbeiten möchte,
    - **Dann** kann ich die Verbandsfelder (Gegner, Datum, Anspielzeit, Ort, Heim/Auswärts, Matchart) nicht ändern,
    - **Und** ich kann die teaminternen Felder (Treffpunkt, Treffpunktzeit, Beschreibung) bearbeiten,
    - **Und** diese bleiben bei späteren Synchronisationen erhalten.

- [ ] **Szenario 7:** Kennzeichnung im Kalender
    - **Gegeben** im Teamkalender sind importierte und manuell erstellte Matches,
    - **Wenn** ich den Kalender öffne,
    - **Dann** sind importierte Matches als «Verband» gekennzeichnet und zeigen den Zeitpunkt der letzten Synchronisation.

- [ ] **Szenario 8:** Manuelle Synchronisation
    - **Gegeben** mein Team ist mit einem Verbandsteam verknüpft,
    - **Wenn** ich auf «Jetzt synchronisieren» klicke,
    - **Dann** wird der Spielplan sofort abgeglichen und ich sehe eine Zusammenfassung (z. B. «2 neu, 1 geändert, 0 abgesagt»).

- [ ] **Szenario 9:** Fehler beim Import
    - **Gegeben** die Verbandsschnittstelle ist nicht erreichbar oder liefert ungültige Daten,
    - **Wenn** die Synchronisation läuft,
    - **Dann** bleiben die bestehenden Matches unverändert,
    - **Und** der Fehler wird protokolliert,
    - **Und** der Coach sieht einen Hinweis, wenn die letzte erfolgreiche Synchronisation länger als 48 Stunden zurückliegt.

## 🗂️ Datenanforderungen / Attribute

**Erweiterung Team**

| Attribut | Typ | Pflicht | Beschreibung |
|---|---|---|---|
| `verbandTeamId` | String | nein | ID des Teams beim Verband |
| `letzteSynchronisation` | DateTime | nein | Zeitpunkt der letzten erfolgreichen Synchronisation |
| `syncStatus` | Enum (`ok`, `fehler`, `inaktiv`) | ja | Default: `inaktiv` |

**Match**

| Attribut | Typ | Quelle | Beschreibung |
|---|---|---|---|
| `id` | UUID | System | Eindeutige ID |
| `teamId` | UUID | System | Referenz auf das eigene Team |
| `quelle` | Enum (`verband`, `manuell`) | System | Herkunft des Matches |
| `spielnummer` | String | Verband | Eindeutiger Schlüssel für den Abgleich (bei `verband` Pflicht) |
| `gegner` | String | Verband | Name des gegnerischen Teams |
| `matchart` | Enum (`meisterschaft`, `cup`, `freundschaftsspiel`, `turnier`) | Verband | |
| `heimAuswaerts` | Enum (`heim`, `auswaerts`) | Verband | |
| `datum` | Date | Verband | |
| `anspielzeit` | Time | Verband | |
| `ort` | String | Verband | Spielort / Adresse |
| `liga` / `gruppe` | String | Verband | z. B. «3. Liga, Gruppe 2» |
| `status` | Enum (`geplant`, `verschoben`, `abgesagt`, `gespielt`) | Verband | Default: `geplant` |
| `treffpunktZeit` | Time | Coach | Muss vor der Anspielzeit liegen |
| `treffpunktOrt` | String | Coach | z. B. «Parkplatz Clubhaus» |
| `beschreibung` | Text | Coach | Hinweise wie Trikotfarbe, Fahrdienst |
| `zuletztSynchronisiert` | DateTime | System | Letzter Abgleich dieses Matches |
| `erstelltAm` / `geaendertAm` | DateTime | System | |

## 🛠️ Technische Hinweise

- **Datenquelle:** Klären, welche Schnittstelle der Verband anbietet (REST-API, iCal-Feed, CSV-Export). Davon hängen Authentifizierung, Datenformat und mögliche Rate-Limits ab.
- **Synchronisation:** Geplanter Job (Vorschlag: täglich nachts, zusätzlich alle 2 bis 4 Stunden an Spieltagen), dazu manuell über `POST /teams/{teamId}/sync`.
- **Idempotenz:** Upsert über `(teamId, spielnummer)` mit einem Unique-Index, damit keine Duplikate entstehen.
- **Feldhoheit:** Verbandsfelder werden bei jeder Synchronisation überschrieben, Coach-Felder nie. Das Backend lehnt Änderungen an Verbandsfeldern über die API ab.
- **Änderungserkennung:** Nur bei echten Änderungen (Vergleich alt/neu) speichern und benachrichtigen, damit es keine Benachrichtigungsflut gibt.
- **Entfernte Matches:** Ein Match, das nicht mehr im Spielplan steht, auf `abgesagt` setzen statt löschen, damit Aufgebote und Historie erhalten bleiben.
- **Fehlerbehandlung:** Schlägt der Abruf fehl, nichts ändern (Transaktion pro Team). Retry mit Backoff, Logging und Monitoring/Alert bei wiederholten Fehlern.
- **Zeitzone:** Verbandszeiten als `Europe/Zurich` interpretieren und in UTC speichern.
- **Manuelle Matches:** Freundschaftsspiele und Turniere ohne Verbandseintrag bleiben über die Story «Match_erstellen» manuell erfassbar (`quelle = manuell`).

## ❓ Offene Fragen

- [ ] Um welchen Verband bzw. welche Sportart geht es, und welche Schnittstelle stellt er zur Verfügung?
- [ ] Braucht es für den Zugriff einen Vereins-Login oder API-Key, und wer verwaltet diesen?
- [ ] Wird die Verknüpfung pro Team vom Coach gemacht oder zentral von einem Vereins-Admin für alle Teams?
- [ ] Wie oft muss synchronisiert werden, und reicht täglich?
- [ ] Sollen Resultate ebenfalls aus dem Verbandsspielplan übernommen werden?
- [ ] Wie gehen wir mit einem Match um, das der Verband löscht, für das aber schon ein Aufgebot besteht?
- [ ] Soll der Coach eine Benachrichtigung erhalten, wenn neue Matches importiert wurden?
- [ ] Wie wird der Ort übernommen: als Freitext oder abgeglichen mit einer Liste von Sportanlagen?
