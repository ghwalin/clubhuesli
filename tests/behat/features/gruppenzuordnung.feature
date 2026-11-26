# language: de
Funktionalität: Gruppenzuordnungen verwalten
  Als Geschäftsführer möchte ich natürliche und juristische Personen pro Saison
  Gruppen zuordnen.

  Grundlage:
    Angenommen ich bin mit der Rolle Geschäftsstelle angemeldet
    Und die aktuelle Saison "Aktuell" ist erfasst
    Und die Person ist erfasst:
      | personType | NATURAL          |
      | teamMember | true             |
      | firstName  | Anna             |
      | lastName   | Muster           |
      | birthDate  | vor 22 Jahren    |
      | email      | anna@muster.ch   |
    Und die Person ist erfasst:
      | personType       | LEGAL          |
      | name             | Jensen AG      |
      | contactFirstName | Beat           |
      | contactLastName  | Keller         |
      | email            | info@jensen.ch |

  Szenario: Natürliche Person einer Gruppe zuordnen
    Angenommen die Gruppe "Funktionäre" der Art FUNCTIONARY ist erfasst
    Wenn ich "Anna Muster" der Gruppe "Funktionäre" für die Saison "Aktuell" zuordne
    Dann wird gespeichert
    Und ist "Anna Muster" in der Saison "Aktuell" Mitglied der Gruppe "Funktionäre"

  Szenario: Juristische Person einer Gruppe zuordnen
    Angenommen die Gruppe "Sponsoren" der Art OTHER ist erfasst
    Wenn ich "Jensen AG" der Gruppe "Sponsoren" für die Saison "Aktuell" zuordne
    Dann wird gespeichert
    Und ist "Jensen AG" in der Saison "Aktuell" Mitglied der Gruppe "Sponsoren"

  Szenario: Juristische Person nicht in einem Team
    Angenommen die Gruppe "U18" der Art TEAM ist erfasst
    Wenn ich "Jensen AG" der Gruppe "U18" für die Saison "Aktuell" zuordne als PLAYER
    Dann wird nicht gespeichert und ich sehe die Meldung "Nur natürliche Personen können Mitglied eines Teams sein"

  Szenario: Keine doppelte Zuordnung
    Angenommen die Gruppe "Funktionäre" der Art FUNCTIONARY ist erfasst
    Und "Anna Muster" ist in der Saison "Aktuell" der Gruppe "Funktionäre" zugeordnet
    Wenn ich "Anna Muster" der Gruppe "Funktionäre" für die Saison "Aktuell" zuordne
    Dann wird nicht gespeichert und ich sehe die Meldung "Anna Muster ist in dieser Saison bereits dieser Gruppe zugeordnet"

  Szenario: Keine Zuordnung zu einer inaktiven Gruppe
    Angenommen die Gruppe "Alt" der Art OTHER ist erfasst
    Und ich die Gruppe "Alt" auf inaktiv setze
    Wenn ich "Anna Muster" der Gruppe "Alt" für die Saison "Aktuell" zuordne
    Dann wird nicht gespeichert und ich sehe die Meldung "Die Gruppe ist inaktiv"

  Szenario: Mitglied der Untergruppe gehört zur Obergruppe
    Angenommen die Gruppe "Funktionärin" der Art FUNCTIONARY ist erfasst
    Und die Gruppe "Geschäftsführerin" der Art FUNCTIONARY ist erfasst
    Und die Gruppe "Geschäftsführerin" ist Untergruppe von "Funktionärin"
    Wenn ich "Anna Muster" der Gruppe "Geschäftsführerin" für die Saison "Aktuell" zuordne
    Dann ist "Anna Muster" in der Saison "Aktuell" Mitglied der Gruppe "Funktionärin"

  Szenario: Austrittsdatum ausserhalb der Saison
    Angenommen die Gruppe "Funktionäre" der Art FUNCTIONARY ist erfasst
    Und "Anna Muster" ist in der Saison "Aktuell" der Gruppe "Funktionäre" zugeordnet
    Wenn ich bei "Anna Muster" in der Gruppe "Funktionäre" und der Saison "Aktuell" das Austrittsdatum 1999-01-01 erfasse
    Dann wird nicht gespeichert und ich sehe die Meldung "Das Austrittsdatum muss innerhalb der Saison liegen"

  Szenario: Zuordnung löschen entfernt auch die Zugehörigkeit zur Obergruppe
    Angenommen die Gruppe "Funktionärin" der Art FUNCTIONARY ist erfasst
    Und die Gruppe "Geschäftsführerin" der Art FUNCTIONARY ist erfasst
    Und die Gruppe "Geschäftsführerin" ist Untergruppe von "Funktionärin"
    Und "Anna Muster" ist in der Saison "Aktuell" der Gruppe "Geschäftsführerin" zugeordnet
    Wenn ich die Zuordnung von "Anna Muster" zur Gruppe "Geschäftsführerin" in der Saison "Aktuell" lösche und die Warnung bestätige
    Dann wird gespeichert
    Und ist "Anna Muster" in der Saison "Aktuell" nicht Mitglied der Gruppe "Funktionärin"
