# language: de
Funktionalität: Natürliche Personen verwalten
  Als Geschäftsführer möchte ich Teammitglieder, Passiv- und Ehrenmitglieder
  sowie Erziehungsberechtigte als natürliche Personen erfassen und verwalten.

  Grundlage:
    Angenommen ich bin mit der Rolle Geschäftsstelle angemeldet

  Szenario: Szenario 1 – Teammitglied anlegen
    Wenn ich die Person mit folgenden Daten erfasse:
      | personType | NATURAL        |
      | teamMember | true           |
      | firstName  | Anna           |
      | lastName   | Muster         |
      | birthDate  | vor 22 Jahren  |
      | email      | anna@muster.ch |
    Dann wird die Person gespeichert
    Und erscheint "Anna Muster" in der Personenliste
    Und ist das Feld "teamMember" auf "true" gesetzt

  Szenario: Szenario 2 – Teammitglied ohne Geburtsdatum
    Wenn ich die Person mit folgenden Daten erfasse:
      | personType | NATURAL        |
      | teamMember | true           |
      | firstName  | Anna           |
      | lastName   | Muster         |
      | email      | anna@muster.ch |
    Dann wird nicht gespeichert und ich sehe die Meldung "Geburtsdatum ist für Teammitglieder erforderlich"

  Szenario: Szenario 3 – Natürliche Person ohne Geburtsdatum, AHV- und J+S-Nummer anlegen
    Wenn ich die Person mit folgenden Daten erfasse:
      | personType | NATURAL         |
      | firstName  | Peter           |
      | lastName   | Muster          |
      | email      | peter@muster.ch |
    Dann wird die Person gespeichert
    Und ist das Feld "teamMember" auf "false" gesetzt
    Und wird das Feld "ahvNumber" nicht angeboten
    Und wird das Feld "photoConsent" nicht angeboten

  Szenario: Szenario 4 – Ungültige AHV-Nummer
    Wenn ich die Person mit folgenden Daten erfasse:
      | personType | NATURAL          |
      | teamMember | true             |
      | firstName  | Anna             |
      | lastName   | Muster           |
      | birthDate  | vor 22 Jahren    |
      | email      | anna@muster.ch   |
      | ahvNumber  | 756.1234.5678.96 |
    Dann wird nicht gespeichert und ich sehe die Meldung "Bitte gültige AHV-Nummer eingeben (756.XXXX.XXXX.XX)"

  Szenario: Szenario 5 – Minderjährige mit Erziehungsberechtigten verknüpfen
    Angenommen die Person ist erfasst:
      | personType | NATURAL                   |
      | firstName  | Peter                     |
      | lastName   | Muster                    |
      | email      | familie.muster@example.ch |
    Und die Person ist erfasst:
      | personType | NATURAL       |
      | teamMember | true          |
      | firstName  | Anna          |
      | lastName   | Muster        |
      | birthDate  | vor 14 Jahren |
      | email      | anna@muster.ch |
    Wenn ich "Peter Muster" als erziehungsberechtigt mit "Anna Muster" verknüpfe
    Dann wird die Person gespeichert
    Und ist bei "Anna Muster" die erziehungsberechtigte Person "Peter Muster" sichtbar
    Und ist bei "Peter Muster" das Kind "Anna Muster" sichtbar

  Szenario: Szenario 6 – Erziehungsberechtigte nur bei Minderjährigen
    Angenommen die Person ist erfasst:
      | personType | NATURAL         |
      | firstName  | Peter           |
      | lastName   | Muster          |
      | email      | peter@muster.ch |
    Und die Person ist erfasst:
      | personType | NATURAL        |
      | teamMember | true           |
      | firstName  | Lea            |
      | lastName   | Meier          |
      | birthDate  | vor 25 Jahren  |
      | email      | lea@meier.ch   |
    Wenn ich "Peter Muster" als erziehungsberechtigt mit "Lea Meier" verknüpfe
    Dann wird nicht gespeichert und ich sehe die Meldung "Erziehungsberechtigte können nur bei minderjährigen Teammitgliedern erfasst werden"

  Szenario: Szenario 7 – Person bearbeiten
    Angenommen die Person ist erfasst:
      | personType | NATURAL        |
      | teamMember | true           |
      | firstName  | Anna           |
      | lastName   | Muster         |
      | birthDate  | vor 22 Jahren  |
      | email      | anna@muster.ch |
    Wenn ich bei "Anna Muster" folgende Daten speichere:
      | personType | NATURAL        |
      | teamMember | true           |
      | firstName  | Anna           |
      | lastName   | Keller         |
      | birthDate  | vor 22 Jahren  |
      | email      | anna@keller.ch |
    Dann wird die Person gespeichert
    Und ist das Feld "displayName" auf "Anna Keller" gesetzt
    Und erscheint "Anna Keller" in der Personenliste

  Szenario: Szenario 9 – Person löschen
    Angenommen die Person ist erfasst:
      | personType | NATURAL        |
      | teamMember | true           |
      | firstName  | Anna           |
      | lastName   | Muster         |
      | birthDate  | vor 22 Jahren  |
      | email      | anna@muster.ch |
    Wenn ich "Anna Muster" lösche und die Sicherheitsabfrage bestätige
    Dann erscheint "Anna Muster" nicht in der Personenliste

  Szenario: Szenario 10 – Notiz erfassen
    Wenn ich die Person mit folgenden Daten erfasse:
      | personType | NATURAL         |
      | firstName  | Peter           |
      | lastName   | Muster          |
      | email      | peter@muster.ch |
      | note       | Allergie: Nüsse |
    Dann wird die Person gespeichert
    Und ist das Feld "note" auf "Allergie: Nüsse" gesetzt

  Szenario: Szenario 12 – Einwilligung Foto bei minderjährigem Teammitglied
    Wenn ich die Person mit folgenden Daten erfasse:
      | personType   | NATURAL       |
      | teamMember   | true          |
      | firstName    | Anna          |
      | lastName     | Muster        |
      | birthDate    | vor 14 Jahren |
      | email        | anna@muster.ch |
      | photoConsent | true          |
    Dann wird die Person gespeichert
    Und ist das Feld "photoConsent" auf "true" gesetzt
    Und ist das Feld "isMinor" auf "true" gesetzt

  Szenario: Szenario 12 – Einwilligung Foto nicht bei volljährigen Teammitgliedern
    Wenn ich die Person mit folgenden Daten erfasse:
      | personType   | NATURAL        |
      | teamMember   | true           |
      | firstName    | Anna           |
      | lastName     | Muster         |
      | birthDate    | vor 22 Jahren  |
      | email        | anna@muster.ch |
      | photoConsent | true           |
    Dann wird nicht gespeichert und ich sehe die Meldung "Die Einwilligung Foto wird nur bei minderjährigen Teammitgliedern erfasst"
