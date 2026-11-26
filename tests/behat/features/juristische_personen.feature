# language: de
Funktionalität: Juristische Personen verwalten
  Als Geschäftsführer möchte ich Sponsoren, Gönner, Partner und Gemeinden als
  juristische Personen mit ihrer Kontaktperson erfassen und verwalten.

  Grundlage:
    Angenommen ich bin mit der Rolle Geschäftsstelle angemeldet

  Szenario: Szenario 1 – Juristische Person mit Kontaktperson anlegen
    Wenn ich die Person mit folgenden Daten erfasse:
      | personType       | LEGAL          |
      | name             | Jensen AG      |
      | contactFirstName | Anna           |
      | contactLastName  | Muster         |
      | email            | info@jensen.ch |
    Dann wird die Person gespeichert
    Und erscheint "Jensen AG" in der Personenliste
    Und ist das Feld "personTypeLabel" auf "Juristische Person" gesetzt

  Szenario: Szenario 2 – Ohne Kontaktperson nicht speicherbar
    Wenn ich die Person mit folgenden Daten erfasse:
      | personType | LEGAL          |
      | name       | Jensen AG      |
      | email      | info@jensen.ch |
    Dann wird nicht gespeichert und ich sehe die Meldung "Bitte Vor- und Nachname der Kontaktperson erfassen"

  Szenario: Szenario 3 – Kontaktperson ändern
    Angenommen die Person ist erfasst:
      | personType       | LEGAL          |
      | name             | Jensen AG      |
      | contactFirstName | Anna           |
      | contactLastName  | Muster         |
      | email            | info@jensen.ch |
    Wenn ich bei "Jensen AG" folgende Daten speichere:
      | personType       | LEGAL          |
      | name             | Jensen AG      |
      | contactFirstName | Beat           |
      | contactLastName  | Keller         |
      | email            | info@jensen.ch |
    Dann wird die Person gespeichert
    Und ist das Feld "contactName" auf "Beat Keller" gesetzt

  Szenario: Szenario 4 – Juristische Person bearbeiten
    Angenommen die Person ist erfasst:
      | personType       | LEGAL          |
      | name             | Jensen AG      |
      | contactFirstName | Anna           |
      | contactLastName  | Muster         |
      | email            | info@jensen.ch |
    Wenn ich bei "Jensen AG" folgende Daten speichere:
      | personType       | LEGAL          |
      | name             | Jensen Sport AG |
      | contactFirstName | Anna           |
      | contactLastName  | Muster         |
      | email            | info@jensen.ch |
      | street           | Dorfstrasse    |
      | zip              | 8000           |
      | city             | Zürich         |
    Dann wird die Person gespeichert
    Und erscheint "Jensen Sport AG" in der Personenliste

  Szenario: Szenario 7 – Juristische Person löschen
    Angenommen die Person ist erfasst:
      | personType       | LEGAL          |
      | name             | Jensen AG      |
      | contactFirstName | Anna           |
      | contactLastName  | Muster         |
      | email            | info@jensen.ch |
    Wenn ich "Jensen AG" lösche und die Sicherheitsabfrage bestätige
    Dann erscheint "Jensen AG" nicht in der Personenliste

  Szenario: Szenario 8 – Unterstützung und Notiz erfassen
    Wenn ich die Person mit folgenden Daten erfasse:
      | personType       | LEGAL                      |
      | name             | Jensen AG                  |
      | contactFirstName | Anna                       |
      | contactLastName  | Muster                     |
      | email            | info@jensen.ch             |
      | support          | Trikotsponsoring 2026–2028 |
      | note             | Kontakt über die GL        |
    Dann wird die Person gespeichert
    Und ist das Feld "support" auf "Trikotsponsoring 2026–2028" gesetzt
    Und ist das Feld "note" auf "Kontakt über die GL" gesetzt

