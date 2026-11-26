# language: de
Funktionalität: Gruppen verwalten
  Als Geschäftsführer möchte ich Gruppen erfassen, in Ober- und Untergruppen
  gliedern und deaktivieren.

  Grundlage:
    Angenommen ich bin mit der Rolle Admin angemeldet
    Und die aktuelle Saison "Aktuell" ist erfasst

  Szenario: Gruppe anlegen und Doppelte ablehnen
    Angenommen die Gruppe "NLB" der Art TEAM ist erfasst
    Dann ist die Gruppe "NLB" aktiv
    Wenn ich die Gruppe "NLB" löschen will
    Dann wird die Anfrage abgewiesen

  Szenario: Obergruppe zuordnen
    Angenommen die Gruppe "Funktionärin" der Art FUNCTIONARY ist erfasst
    Und die Gruppe "Geschäftsführerin" der Art FUNCTIONARY ist erfasst
    Wenn ich die Gruppe "Funktionärin" als Obergruppe von "Geschäftsführerin" zuordne
    Dann wird gespeichert

  Szenario: Zyklus verhindern
    Angenommen die Gruppe "Funktionärin" der Art FUNCTIONARY ist erfasst
    Und die Gruppe "Geschäftsführerin" der Art FUNCTIONARY ist erfasst
    Und die Gruppe "Geschäftsführerin" ist Untergruppe von "Funktionärin"
    Wenn ich die Gruppe "Geschäftsführerin" als Obergruppe von "Funktionärin" zuordne
    Dann wird nicht gespeichert und ich sehe die Meldung "Die Zuordnung würde einen Zyklus in der Hierarchie erzeugen"

  Szenario: Deaktivieren mit aktiver Mitgliedschaft
    Angenommen die Gruppe "Funktionäre" der Art FUNCTIONARY ist erfasst
    Und die Person ist erfasst:
      | personType | NATURAL        |
      | firstName  | Peter          |
      | lastName   | Muster         |
      | email      | peter@muster.ch |
    Und "Peter Muster" ist in der Saison "Aktuell" der Gruppe "Funktionäre" zugeordnet
    Wenn ich die Gruppe "Funktionäre" auf inaktiv setze
    Dann wird nicht gespeichert und ich sehe die Meldung "Die Gruppe hat noch aktive Mitgliedschaften"
    Und ist die Gruppe "Funktionäre" aktiv

  Szenario: Deaktivieren mit aktiver Untergruppe
    Angenommen die Gruppe "Funktionärin" der Art FUNCTIONARY ist erfasst
    Und die Gruppe "Webmaster" der Art FUNCTIONARY ist erfasst
    Und die Gruppe "Webmaster" ist Untergruppe von "Funktionärin"
    Wenn ich die Gruppe "Funktionärin" auf inaktiv setze
    Dann wird nicht gespeichert und ich sehe die Meldung "Die Gruppe hat noch aktive Untergruppen"

  Szenario: Unbenutzte Gruppe deaktivieren
    Angenommen die Gruppe "Alt" der Art OTHER ist erfasst
    Wenn ich die Gruppe "Alt" auf inaktiv setze
    Dann wird gespeichert
    Und ist die Gruppe "Alt" inaktiv
