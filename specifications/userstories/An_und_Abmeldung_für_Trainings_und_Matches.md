---
name: An- und Abmeldung für Trainings und Matches
about: Spieler/innen melden sich für Trainings und Matches an oder ab
title: "[Story] Teilnahme_an_abmelden"
labels: ["user-story", "needs-refinement"]
assignees: []
---
## 📖 User Story

**Als** Spieler/in
**möchte ich** mich für Trainings und Matches meines Teams an- oder abmelden,
**damit** der Coach weiss, wer dabei ist, und Trainings sowie Aufgebote besser planen kann.

## 🧭 Kontext & Hintergrund

Heute melden sich Spieler/innen über Chat, Telefon oder gar nicht ab. Der Coach weiss oft erst auf dem Platz, wer kommt. Mit dieser Story gibt jede/r Spieler/in die Teilnahme direkt beim Termin an. Der Coach sieht so jederzeit den aktuellen Stand.

- Epic: Trainingsplanung / Spielbetrieb
- Verwandte Stories:
  - [Story] Training_erstellen
  - [Story] Match_import_Verband
  - [Story] Match_erstellen
  - [Story] Teilnahmeuebersicht_Coach
  - [Story] Erinnerungen_Teilnahme

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Termine sehen
    - **Gegeben** ich bin als Spieler/in angemeldet und Mitglied eines Teams,
    - **Wenn** ich den Teamkalender öffne,
    - **Dann** sehe ich alle kommenden Trainings und Matches mit meinem Teilnahmestatus («offen», «angemeldet», «abgemeldet»).

- [ ] **Szenario 2:** Anmelden
    - **Gegeben** ein Termin liegt in der Zukunft und der Anmeldeschluss ist noch nicht erreicht,
    - **Wenn** ich auf «Dabei» klicke,
    - **Dann** ist mein Status «angemeldet» und der Coach sieht das sofort.

- [ ] **Szenario 3:** Abmelden mit Grund
    - **Gegeben** ein Termin liegt in der Zukunft und der Anmeldeschluss ist noch nicht erreicht,
    - **Wenn** ich auf «Nicht dabei» klicke,
    - **Dann** muss ich einen Grund wählen (Krank, Verletzt, Schule/Arbeit, Ferien, Anderes),
    - **Und** kann optional einen Kommentar erfassen,
    - **Und** mein Status ist danach «abgemeldet».

- [ ] **Szenario 4:** Status ändern
    - **Gegeben** ich habe mich bereits an- oder abgemeldet,
    - **Wenn** ich vor dem Anmeldeschluss meine Antwort ändere,
    - **Dann** wird der neue Status gespeichert und der Coach sieht die Änderung.

- [ ] **Szenario 5:** Anmeldeschluss erreicht
    - **Gegeben** der Anmeldeschluss eines Termins ist vorbei,
    - **Wenn** ich den Termin öffne,
    - **Dann** kann ich mich nicht mehr anmelden,
    - **Und** eine Abmeldung ist weiterhin möglich, wird aber als «kurzfristige Abmeldung» markiert und dem Coach gemeldet.

- [ ] **Szenario 6:** Matches nur mit Aufgebot
    - **Gegeben** für ein Match wurde ein Aufgebot erstellt,
    - **Wenn** ich aufgeboten bin,
    - **Dann** kann ich das Aufgebot bestätigen oder mit Grund absagen,
    - **Und** wenn ich nicht aufgeboten bin, sehe ich das Match nur zur Information.

- [ ] **Szenario 7:** Abgesagte oder vergangene Termine
    - **Gegeben** ein Termin ist abgesagt oder liegt in der Vergangenheit,
    - **Wenn** ich ihn öffne,
    - **Dann** sind die Buttons für An- und Abmeldung deaktiviert.

- [ ] **Szenario 8:** Verschobener Termin
    - **Gegeben** ich habe mich für einen Termin angemeldet,
    - **Wenn** der Termin auf ein anderes Datum oder eine andere Zeit verschoben wird,
    - **Dann** wird mein Status auf «offen» zurückgesetzt,
    - **Und** ich werde benachrichtigt und gebeten, mich neu zu melden.

- [ ] **Szenario 9:** Mehrere Termine auf einmal abmelden
    - **Gegeben** ich bin z. B. in den Ferien,
    - **Wenn** ich einen Zeitraum wähle und mich mit Grund abmelde,
    - **Dann** werde ich für alle Trainings und Matches in diesem Zeitraum abgemeldet.

## 🗂️ Datenanforderungen / Attribute

**Teilnahme**

| Attribut | Typ | Pflicht | Beschreibung |
|---|---|---|---|
| `id` | UUID | ja | Eindeutige ID |
| `terminTyp` | Enum (`training`, `match`) | ja | Art des Termins |
| `terminId` | UUID | ja | Referenz auf Training oder Match |
| `spielerId` | UUID | ja | Referenz auf den/die Spieler/in |
| `status` | Enum (`offen`, `angemeldet`, `abgemeldet`, `warteliste`) | ja | Default: `offen` |
| `abmeldegrund` | Enum (`krank`, `verletzt`, `schule_arbeit`, `ferien`, `anderes`) | bedingt | Pflicht, wenn `status = abgemeldet` |
| `kommentar` | String (max. 250) | nein | Freitext für den Coach |
| `kurzfristig` | Boolean | ja | `true`, wenn nach dem Anmeldeschluss abgemeldet |
| `geantwortetAm` | DateTime | nein | Zeitpunkt der letzten Antwort |
| `geaendertVon` | UUID | ja | Spieler/in selbst, Elternteil oder Coach |

**Abhängigkeiten aus anderen Stories**
- Training: `anmeldeschluss`, `maxTeilnehmende`, `status`
- Match: `anmeldeschluss` (neu), `status`, Aufgebot (Story «Aufgebot_erstellen»)

## 🛠️ Technische Hinweise

- **Berechtigung:** Spieler/innen dürfen nur ihre eigene Teilnahme ändern, und nur für Termine ihrer Teams. Prüfung im Backend.
- **API (Vorschlag):**
  - `GET /me/termine?von=&bis=`: eigene Termine inkl. Status
  - `PUT /termine/{terminTyp}/{terminId}/teilnahme`: an- oder abmelden (Body: `status`, `abmeldegrund`, `kommentar`)
  - `POST /me/abwesenheiten`: Abmeldung für einen Zeitraum
- **Teilnahmeeinträge:** Beim Erstellen oder Importieren eines Termins für alle Teammitglieder einen Eintrag mit `status = offen` anlegen, oder den Status «offen» aus einem fehlenden Eintrag ableiten. Das Team muss sich für eine Variante entscheiden.
- **Verschiebungen:** Ändert sich das Datum oder die Zeit eines Termins (auch durch den Verbandsimport), alle Teilnahmen auf `offen` zurücksetzen und benachrichtigen.
- **Zeitraum-Abmeldung:** Gilt auch für Termine, die erst später erstellt oder importiert werden. Dafür eine Abwesenheit speichern und bei neuen Terminen anwenden.
- **Mobile first:** An- und Abmelden muss mit einem Klick aus der Benachrichtigung oder der Kalenderansicht gehen.
- **Datenschutz:** Abmeldegründe wie «krank» oder «verletzt» sind sensible Daten. Nur Coaches des Teams dürfen sie sehen, andere Spieler/innen sehen nur «nicht dabei».

## ❓ Offene Fragen

- [ ] Wie lange vor dem Termin ist der Standard-Anmeldeschluss (z. B. 24 Stunden)?
- [ ] Dürfen Eltern ihre Kinder an- und abmelden (Juniorenteams)? Braucht es dafür eine Rolle «Elternteil»?
- [ ] Sollen andere Spieler/innen sehen, wer angemeldet ist, oder nur die Anzahl?
- [ ] Soll eine Abmeldung nach Anmeldeschluss noch möglich sein oder nur über den Coach?
- [ ] Wann und wie oft sollen Spieler/innen ohne Antwort erinnert werden (eigene Story)?
- [ ] Gibt es Matches ohne Aufgebot, bei denen sich alle Spieler/innen frei anmelden können?
