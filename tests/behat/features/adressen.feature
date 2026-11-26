# language: de
Funktionalität: Adressen verwalten
  Als Geschäftsführer möchte ich die Post- und E-Mail-Adressen von Mitgliedern,
  Sponsoren und Partnern verwalten.

  Grundlage:
    Angenommen ich bin mit der Rolle Geschäftsstelle angemeldet

  Szenario: Szenario 1 – Adresse einer natürlichen Person erfassen
    Wenn ich die Person mit folgenden Daten erfasse:
      | personType  | NATURAL         |
      | firstName   | Peter           |
      | lastName    | Muster          |
      | street      | Dorfstrasse     |
      | houseNumber | 12a             |
      | zip         | 8000            |
      | city        | Zürich          |
      | email       | peter@muster.ch |
    Dann wird die Person gespeichert
    Und ist das Feld "city" auf "Zürich" gesetzt
    Und ist das Feld "country" auf "CH" gesetzt

  Szenario: Szenario 2 – Minderjähriges Teammitglied ohne eigene E-Mail
    Angenommen die Person ist erfasst:
      | personType | NATURAL                   |
      | firstName  | Peter                     |
      | lastName   | Muster                    |
      | email      | familie.muster@example.ch |
    Wenn ich die Person mit folgenden Daten erfasse:
      | personType | NATURAL       |
      | teamMember | true          |
      | firstName  | Anna          |
      | lastName   | Muster        |
      | birthDate  | vor 14 Jahren |
      | guardians  | Peter Muster  |
    Dann wird die Person gespeichert
    Und ist das Feld "email" auf "" gesetzt
    Und ist bei "Anna Muster" die erziehungsberechtigte Person "Peter Muster" sichtbar

  Szenario: Szenario 3 – Adresse einer juristischen Person erfassen
    Wenn ich die Person mit folgenden Daten erfasse:
      | personType       | LEGAL          |
      | name             | Jensen AG      |
      | contactFirstName | Anna           |
      | contactLastName  | Muster         |
      | email            | info@jensen.ch |
      | street           | Dorfstrasse    |
      | houseNumber      | 12             |
      | zip              | 8000           |
      | city             | Zürich         |
    Dann wird die Person gespeichert
    Und ist das Feld "street" auf "Dorfstrasse" gesetzt
    Und ist das Feld "contactName" auf "Anna Muster" gesetzt

  Szenario: Szenario 4 – Ungültige E-Mail-Adresse
    Wenn ich die Person mit folgenden Daten erfasse:
      | personType | NATURAL |
      | firstName  | Peter   |
      | lastName   | Muster  |
      | email      | anna@   |
    Dann wird nicht gespeichert und ich sehe die Meldung "Bitte gültige E-Mail-Adresse eingeben"

  Szenario: Szenario 5 – Gemeinsame E-Mail-Adresse
    Angenommen die Person ist erfasst:
      | personType | NATURAL                   |
      | firstName  | Peter                     |
      | lastName   | Muster                    |
      | email      | familie.muster@example.ch |
    Wenn ich die Person mit folgenden Daten erfasse:
      | personType | NATURAL                   |
      | firstName  | Petra                     |
      | lastName   | Muster                    |
      | email      | familie.muster@example.ch |
    Dann wird die Person gespeichert
    Und erscheint "Petra Muster" in der Personenliste
    Und erscheint "Peter Muster" in der Personenliste

  Szenario: Szenario 6 – Ungültige Schweizer PLZ
    Wenn ich die Person mit folgenden Daten erfasse:
      | personType | NATURAL         |
      | firstName  | Peter           |
      | lastName   | Muster          |
      | email      | peter@muster.ch |
      | country    | CH              |
      | street     | Dorfstrasse     |
      | zip        | 800             |
      | city       | Zürich          |
    Dann wird nicht gespeichert und ich sehe die Meldung "Bitte gültige PLZ eingeben"

  Szenario: Szenario 7 – Person ohne Postadresse
    Wenn ich die Person mit folgenden Daten erfasse:
      | personType | NATURAL         |
      | firstName  | Peter           |
      | lastName   | Muster          |
      | email      | peter@muster.ch |
    Dann wird die Person gespeichert
    Und ist das Feld "street" auf "" gesetzt

  Szenario: Szenario 8 – Unvollständige Postadresse
    Wenn ich die Person mit folgenden Daten erfasse:
      | personType | NATURAL         |
      | firstName  | Peter           |
      | lastName   | Muster          |
      | email      | peter@muster.ch |
      | street     | Dorfstrasse     |
    Dann wird nicht gespeichert und ich sehe die Meldung "Bitte Adresse vollständig erfassen (Strasse, PLZ, Ort)"
