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
- Adresse, E-Mail und Telefon sind Attribute der Allgemeinen Person (`specifications/models/people.md`) und gelten für natürliche und juristische Personen. Kontaktpersonen von juristischen Personen haben nur E-Mail und Telefon (siehe «Juristische Personen verwalten»).
- Die Postadresse ist **optional**. Wird sie erfasst, müssen Strasse, PLZ und Ort vollständig sein.
- Familien nutzen oft eine gemeinsame E-Mail-Adresse – die E-Mail ist deshalb **nicht** eindeutig.

## ✅ Akzeptanzkriterien

- [ ] **Szenario 1:** Adresse einer natürlichen Person erfassen
    - **Gegeben** ich erfasse oder bearbeite eine natürliche Person (ausser einem minderjährigen Teammitglied)
    - **Wenn** ich Strasse, PLZ, Ort und E-Mail eingebe und speichere
    - **Dann** sind die Adressen gespeichert und die Person erhält E-Mails, die an ihre Gruppen gesendet werden
- [ ] **Szenario 2:** Minderjähriges Teammitglied ohne eigene E-Mail
    - **Gegeben** ein Teammitglied ist laut Geburtsdatum minderjährig und ist mit mindestens einem Erziehungsberechtigten verknüpft
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
- [ ] **Szenario 7:** Person ohne Postadresse
    - **Gegeben** ich erfasse eine Person, deren Postadresse nicht bekannt ist
    - **Wenn** ich alle Adressfelder leer lasse und speichere
    - **Dann** wird die Person gespeichert; sie wird beim Postversand nicht berücksichtigt
- [ ] **Szenario 8:** Unvollständige Postadresse
    - **Gegeben** ich erfasse eine Postadresse
    - **Wenn** ich nur die Strasse eingebe und PLZ oder Ort leer lasse
    - **Dann** wird nicht gespeichert und ich sehe die Meldung «Bitte Adresse vollständig erfassen (Strasse, PLZ, Ort)»

## 🗂️ Datenanforderungen / Attribute

| Attribut      | Typ    | Pflicht | Validierung / Regeln                                               | Beispiel                  |
|---------------|--------|---------|--------------------------------------------------------------------|---------------------------|
| Strasse       | Text   | bedingt | 2 – 150 Zeichen; Pflicht, sobald ein Adressfeld erfasst ist                                                | Dorfstrasse               |
| Hausnummer    | Text   | ❌      | max. 20 Zeichen                                                    | 12a                       |
| Adresszusatz  | Text   | ❌      | max. 100 Zeichen (z. B. Postfach, c/o)                              | Postfach 123              |
| PLZ           | Text   | bedingt | 4 – 10 Zeichen; bei Land Schweiz genau 4 Ziffern; Pflicht, sobald ein Adressfeld erfasst ist | 8000                      |
| Ort           | Text   | bedingt | 2 – 100 Zeichen; Pflicht, sobald ein Adressfeld erfasst ist        | Zürich                    |
| E-Mail        | E-Mail | bedingt | Pflicht bei natürlichen Personen, ausser minderjährigen Teammitgliedern mit Erziehungsberechtigtem; gültiges Format; nicht eindeutig | anna@example.ch |
| Land          | Enum   | bedingt | ISO-Ländercode, Standard: CH                                       | CH                        |
| Telefon       | Text   | ❌      | internationales Format                                             | +41 79 876 54 32          |

## 🛠️ Technische Hinweise

- Strasse, Nr., Adresszusatz, PLZ, Ort, Land, E-Mail und Telefon sind Attribute von `AbstractPerson`; Validierung in den Settern.
- Die E-Mail für das Login bleibt eindeutig – das ist unabhängig von der Kontakt-E-Mail der Person.

## ❓ Offene Fragen

- Sollen bei Minderjährigen alle verknüpften Erziehungsberechtigten die E-Mails erhalten oder nur einer?
- Braucht es mehrere Telefonnummern (z. B. Privat, Mobile, Geschäft)?
