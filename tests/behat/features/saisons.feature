# language: de
Funktionalität: Saisons verwalten
  Als Geschäftsführer möchte ich Saisons mit Start- und Enddatum erfassen,
  damit ich Personen pro Saison Gruppen zuordnen kann.

  Grundlage:
    Angenommen ich bin mit der Rolle Geschäftsstelle angemeldet

  Szenario: Szenario 1 – Saison anlegen
    Wenn ich die Saison "2026/27" von 2026-07-01 bis 2027-06-30 erfasse
    Dann wird gespeichert

  Szenario: Szenario 2 – Enddatum vor Startdatum
    Wenn ich die Saison "2026/27" von 2027-06-30 bis 2026-07-01 erfasse
    Dann wird nicht gespeichert und ich sehe die Meldung "Enddatum darf nicht vor dem Startdatum liegen"

  Szenario: Szenario 3 – Überschneidende Saisons
    Angenommen die Saison "2026/27" von 2026-07-01 bis 2027-06-30 ist erfasst
    Wenn ich die Saison "2027/28" von 2027-06-01 bis 2028-06-30 erfasse
    Dann wird nicht gespeichert und ich sehe die Meldung "Die Saison überschneidet sich mit der Saison 2026/27"

  Szenario: Szenario 4 – Doppelte Bezeichnung
    Angenommen die Saison "2026/27" von 2026-07-01 bis 2027-06-30 ist erfasst
    Wenn ich die Saison "2026/27" von 2030-07-01 bis 2031-06-30 erfasse
    Dann wird nicht gespeichert und ich sehe die Meldung "Diese Saison existiert bereits"
