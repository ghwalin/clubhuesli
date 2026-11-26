# language: de
Funktionalität: Minimaler Login für Admin und Geschäftsstelle
  Als Geschäftsführer möchte ich mich anmelden, damit nur berechtigte Personen
  die Stammdaten sehen und bearbeiten. Die vollständige Authentifizierung mit
  OAuth 2.0 und Passkey folgt in einem späteren Sprint.

  Szenario: Anmeldung mit korrekten Angaben
    Angenommen die Testumgebung ist aufgesetzt
    Wenn ich mich mit "admin@example.org" und "geheim1234" anmelde
    Dann bin ich mit der Rolle Admin angemeldet

  Szenario: Anmeldung mit falschem Passwort
    Angenommen die Testumgebung ist aufgesetzt
    Wenn ich mich mit "admin@example.org" und "falsch1234" anmelde
    Dann sehe ich die Meldung "E-Mail-Adresse oder Passwort ist falsch"

  Szenario: Anmeldung mit unbekannter E-Mail-Adresse
    Angenommen die Testumgebung ist aufgesetzt
    Wenn ich mich mit "unbekannt@example.org" und "geheim1234" anmelde
    Dann sehe ich die Meldung "E-Mail-Adresse oder Passwort ist falsch"

  Szenario: Ohne Anmeldung ist die Personenliste gesperrt
    Angenommen ich bin nicht angemeldet
    Wenn ich die Personenliste abrufe
    Dann wird die Anfrage abgewiesen

  Szenario: Ohne Anmeldung kann keine Person erfasst werden
    Angenommen ich bin nicht angemeldet
    Wenn ich die Person mit folgenden Daten erfasse:
      | personType | NATURAL         |
      | firstName  | Peter           |
      | lastName   | Muster          |
      | email      | peter@muster.ch |
    Dann wird die Anfrage abgewiesen
