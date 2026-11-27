---
name: Trainings erstellen
about: Trainings pro Team erstellen
title: "[Story] Training_erstellen"
labels: ["user-story", "needs-refinement"]
assignees: []
---
## 📖 User Story

**Als** Coach
**möchte ich** die Trainings für mein Team erstellen und verwalten,
**damit** die Spieler/innen wissen, wann und wo trainiert wird, und ich die Teilnahme planen kann.

## 🧭 Kontext & Hintergrund

Coaches organisieren Trainings heute über verschiedene Kanäle (Chat, E-Mail, mündlich). Dadurch fehlt ein zentraler Überblick, und Spieler/innen verpassen Änderungen oder Absagen. Mit dieser Story bekommt jedes Team einen eigenen Trainingskalender, den der Coach selbst pflegt.

- Epic: Trainingsplanung
- Verwandte Stories:
  - [Story] Training_anmelden (Spieler/innen melden sich an oder ab)
  - [Story] Anwesenheit_erfassen
  - [Story] Trainingsbenachrichtigungen

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Training erstellen
    - **Gegeben** ich bin als Coach angemeldet und einem Team zugewiesen,
    - **Wenn** ich im Team auf «Training erstellen» klicke und die Pflichtfelder ausfülle,
    - **Dann** wird das Training gespeichert und erscheint im Trainingskalender des Teams.

- [ ] **Szenario 2:** Pflichtfelder fehlen
    - **Gegeben** ich erstelle ein neues Training,
    - **Wenn** ich speichere, ohne alle Pflichtfelder (Datum, Start, Ende, Ort) auszufüllen,
    - **Dann** wird das Training nicht gespeichert und die fehlenden Felder werden markiert.

- [ ] **Szenario 3:** Ungültige Zeitangaben
    - **Gegeben** ich erstelle ein neues Training,
    - **Wenn** die Endzeit vor der Startzeit liegt oder das Datum in der Vergangenheit liegt,
    - **Dann** erscheint eine verständliche Fehlermeldung und das Training wird nicht gespeichert.

- [ ] **Szenario 4:** Nur eigene Teams
    - **Gegeben** ich bin als Coach angemeldet,
    - **Wenn** ich ein Team öffne, dem ich nicht als Coach zugewiesen bin,
    - **Dann** wird mir die Funktion «Training erstellen» nicht angezeigt.

- [ ] **Szenario 5:** Wiederkehrendes Training
    - **Gegeben** ich erstelle ein neues Training,
    - **Wenn** ich «wöchentlich wiederholen» wähle und ein Enddatum setze,
    - **Dann** werden alle Termine bis zum Enddatum im Kalender erstellt.

- [ ] **Szenario 6:** Training bearbeiten
    - **Gegeben** ein Training meines Teams existiert,
    - **Wenn** ich Datum, Zeit oder Ort ändere und speichere,
    - **Dann** wird das Training aktualisiert und die Teammitglieder werden über die Änderung informiert.

- [ ] **Szenario 7:** Training absagen
    - **Gegeben** ein Training meines Teams existiert,
    - **Wenn** ich das Training absage,
    - **Dann** wird es im Kalender als «abgesagt» angezeigt und die Teammitglieder werden benachrichtigt.

## 🗂️ Datenanforderungen / Attribute

| Attribut | Typ | Pflicht | Beschreibung |
|---|---|---|---|
| `id` | UUID | ja | Eindeutige ID (vom System vergeben) |
| `teamId` | UUID | ja | Referenz auf das Team |
| `titel` | String (max. 100) | nein | z. B. «Techniktraining» |
| `datum` | Date | ja | Darf nicht in der Vergangenheit liegen |
| `startzeit` | Time | ja | |
| `endzeit` | Time | ja | Muss nach der Startzeit liegen |
| `ort` | String (max. 150) | ja | z. B. «Sportplatz Nord» |
| `beschreibung` | Text | nein | Trainingsinhalt oder Hinweise |
| `maxTeilnehmende` | Integer | nein | Leer heisst unbegrenzt |
| `anmeldeschluss` | DateTime | nein | Muss vor dem Trainingsbeginn liegen |
| `wiederholung` | Enum (`keine`, `woechentlich`) | ja | Default: `keine` |
| `wiederholungBis` | Date | bedingt | Pflicht, wenn `wiederholung` ≠ `keine` |
| `serieId` | UUID | nein | Verknüpft die Termine einer Serie |
| `status` | Enum (`geplant`, `abgesagt`) | ja | Default: `geplant` |
| `erstelltVon` | UUID | ja | Coach, der das Training erstellt hat |
| `erstelltAm` / `geaendertAm` | DateTime | ja | Vom System gesetzt |

## 🛠️ Technische Hinweise

- **Berechtigung:** Prüfung im Backend, nicht nur im UI. Nur User mit der Rolle `Coach` für die jeweilige `teamId` dürfen Trainings erstellen, ändern oder absagen.
- **API (Vorschlag):**
  - `POST /teams/{teamId}/trainings`: erstellen
  - `GET /teams/{teamId}/trainings?von=&bis=`: Kalender laden
  - `PATCH /trainings/{id}`: bearbeiten
  - `POST /trainings/{id}/absagen`: absagen
- **Validierung:** Im Frontend und im Backend mit den gleichen Regeln.
- **Wiederkehrende Trainings:** Jeden Termin als eigenen Datensatz mit gemeinsamer `serieId` speichern, damit sich einzelne Termine ändern lassen.
- **Zeitzone:** Zeiten in UTC speichern und in `Europe/Zurich` anzeigen. Sommer- und Winterzeit bei Serien beachten.
- **Benachrichtigungen:** Asynchron versenden (Queue), damit das Speichern nicht blockiert.
- **Absagen:** Soft-Status (`abgesagt`) statt Löschen, damit die Historie erhalten bleibt.

## ❓ Offene Fragen

- [ ] Brauchen wir neben «wöchentlich» weitere Wiederholungen (z. B. zweiwöchentlich)?
- [ ] Bei einer Serie: Soll eine Änderung nur diesen Termin oder alle folgenden betreffen (oder soll der Coach wählen)?
- [ ] Über welche Kanäle wird benachrichtigt: Push, E-Mail oder beides?
- [ ] Soll der Ort ein Freitext sein oder aus einer Liste von Sportanlagen gewählt werden?
- [ ] Sollen sich Trainings verschiedener Teams am selben Ort überschneiden dürfen, oder braucht es eine Warnung?
