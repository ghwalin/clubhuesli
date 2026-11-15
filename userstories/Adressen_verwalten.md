---
name: 📘 Adressen verwalten
about: Post-, E-Mail-Adressen und Telefonnummern von Personen verwalten
title: "[Story] Adressen verwalten"
labels: ["user-story", "needs-refinement"]
assignees: []
---

## 📖 User Story

**Als** Geschäftsführer
**möchte ich** die Post- und E-Mail-Adressen von Mitgliedern, Sponsoren und Partnern verwalten,
**damit** wir sie per Post und E-Mail kontaktieren können.

## 🧭 Kontext & Hintergrund

- Epic: Stammdaten verwalten
- Verwandte Stories: Natürliche Personen verwalten, Juristische Personen verwalten
- Die Adresse ist Teil jeder natürlichen und juristischen Person. Kontaktpersonen von juristischen Personen haben nur E-Mail und Telefon (siehe «Juristische Personen verwalten»).
- Familien nutzen oft eine gemeinsame E-Mail-Adresse – die E-Mail ist deshalb **nicht** eindeutig.

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Adresse einer volljährigen Person erfassen
    - **Gegeben** ich erfasse oder bearbeite eine volljährige natürliche Person
    - **Wenn** ich Strasse, PLZ, Ort und E-Mail eingebe und speichere
    - **Dann** sind die Adressen gespeichert und die Person erhält E-Mails, die an ihre Gruppen gesendet werden
- [ ] **Szenario 2:** Minderjährige Person ohne eigene E-Mail
    - **Gegeben** ein minderjähriges Mitglied ist mit mindestens einem Erziehungsberechtigten verknüpft
    - **Wenn** ich es ohne eigene E-Mail-Adresse speichere
    - **Dann** wird gespeichert, und E-Mails an seine Gruppen gehen an die Erziehungsberechtigten
- [ ] **Szenario 3:** Adresse einer juristischen Person erfassen
    - **Gegeben** ich erfasse oder bearbeite eine juristische Person
    - **Wenn** ich Strasse, PLZ und Ort eingebe und speichere
    - **Dann** ist die Postadresse gespeichert; die E-Mail-Kommunikation läuft über die Kontaktpersonen
- [ ] **Szenario 4:** Ungültige E-Mail-Adresse
    - **Gegeben** ich erfasse eine Adresse
    - **Wenn** ich eine E-Mail-Adresse ohne gültiges Format (z. B. «anna@») eingebe
    - **Dann** wird nicht gespeichert und ich sehe die Meldung «Bitte gültige E-Mail-Adresse eingeben»
- [ ] **Szenario 5:** Gemeinsame E-Mail-Adresse
    - **Gegeben** eine Person mit der E-Mail «familie.muster@example.ch» ist gespeichert
    - **Wenn** ich eine zweite Person mit derselben E-Mail-Adresse speichere
    - **Dann** wird auch die zweite Person gespeichert
- [ ] **Szenario 6:** Ungültige Schweizer PLZ
    - **Gegeben** als Land ist «Schweiz» gewählt
    - **Wenn** ich eine PLZ eingebe, die nicht aus genau 4 Ziffern besteht
    - **Dann** wird nicht gespeichert und ich sehe die Meldung «Bitte gültige PLZ eingeben»

## 🗂️ Datenanforderungen / Attribute

| Attribut      | Typ    | Pflicht | Validierung / Regeln                                               | Beispiel                  |
|---------------|--------|---------|--------------------------------------------------------------------|---------------------------|
| Strasse       | Text   | ✅      | 2 – 150 Zeichen                                                    | Dorfstrasse               |
| Hausnummer    | Text   | ❌      | max. 20 Zeichen                                                    | 12a                       |
| Adresszusatz  | Text   | ❌      | max. 100 Zeichen (z. B. Postfach, c/o)                              | Postfach 123              |
| PLZ           | Text   | ✅      | 4 – 10 Zeichen; bei Land Schweiz genau 4 Ziffern                   | 8000                      |
| Ort           | Text   | ✅      | 2 – 100 Zeichen                                                    | Zürich                    |
| Land          | Enum   | ✅      | ISO-Ländercode, Standard: CH                                       | CH                        |
| E-Mail        | E-Mail | bedingt | Pflicht bei volljährigen natürlichen Personen; gültiges Format; nicht eindeutig | anna@example.ch |
| Telefon       | Text   | ❌      | internationales Format                                             | +41 79 876 54 32          |

## 🛠️ Technische Hinweise

- `street`, `street_no`, `zip`, `city` und `email` sind im Schema vorhanden. Es fehlen `address_line2` (Adresszusatz), `country` und `phone`.
- Die E-Mail für das Login (`app_user.email`) bleibt eindeutig – das ist unabhängig von der Kontakt-E-Mail der Person.

## ❓ Offene Fragen

- Sollen bei Minderjährigen alle verknüpften Erziehungsberechtigten die E-Mails erhalten oder nur einer?
- Braucht es mehrere Telefonnummern (z. B. Privat, Mobile, Geschäft)?
