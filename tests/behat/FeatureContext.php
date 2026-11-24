<?php

declare(strict_types=1);

namespace Clubhuesli\Behat;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Clubhuesli\Http\ApiKernel;
use Clubhuesli\Http\Request;
use Clubhuesli\Http\Response;
use Clubhuesli\Http\Session;
use Clubhuesli\Model\Role;
use Clubhuesli\Tests\Fake\InMemoryPersonDao;
use Clubhuesli\Tests\Fake\InMemoryUserDao;
use DateTimeImmutable;
use RuntimeException;

/**
 * Steps of the acceptance criteria of sprint 1.
 *
 * The scenarios run against the JSON API with data access objects that keep
 * their data in memory, so no database is needed.
 */
final class FeatureContext implements Context
{
    /** @var InMemoryPersonDao Data access for persons. */
    private InMemoryPersonDao $personDao;

    /** @var ApiKernel The API under test. */
    private ApiKernel $kernel;

    /** @var Response|null Response of the last request. */
    private ?Response $response = null;

    /**
     * Starts every scenario with an empty data set and nobody logged in.
     */
    public function __construct()
    {
        $this->theTestEnvironmentIsSetUp();
    }

    /**
     * Resets the API and the data.
     */
    #[Given('the test environment is set up')]
    #[Given('/^die Testumgebung ist aufgesetzt$/')]
    public function theTestEnvironmentIsSetUp(): void
    {
        $this->personDao = new InMemoryPersonDao();
        $userDao         = new InMemoryUserDao();
        $this->kernel    = new ApiKernel($this->personDao, $userDao, new Session($userDao));
        $this->response  = null;
    }

    /**
     * Logs an account of the given role in.
     *
     * @param string $role German label of the role.
     */
    #[Given('/^ich bin mit der Rolle (Admin|Geschäftsstelle) angemeldet$/')]
    public function iAmLoggedInAs(string $role): void
    {
        $userDao = new InMemoryUserDao(
            'user@example.org',
            'geheim1234',
            $role === 'Admin' ? Role::ADMIN : Role::OFFICE
        );
        $session = new Session($userDao);
        $session->login($userDao->getUser());

        $this->kernel = new ApiKernel($this->personDao, $userDao, $session);
    }

    /**
     * Makes sure nobody is logged in.
     */
    #[Given('/^ich bin nicht angemeldet$/')]
    public function iAmNotLoggedIn(): void
    {
        $userDao      = new InMemoryUserDao();
        $this->kernel = new ApiKernel($this->personDao, $userDao, new Session($userDao));
    }

    /**
     * Logs in through the API.
     *
     * @param string $email    Login e-mail address.
     * @param string $password Password.
     */
    #[When('/^ich mich mit "([^"]*)" und "([^"]*)" anmelde$/')]
    public function iLogIn(string $email, string $password): void
    {
        $this->response = $this->kernel->handle(
            Request::create('POST', '/auth/login', ['email' => $email, 'password' => $password])
        );
    }

    /**
     * Checks that the login succeeded and reports the role.
     *
     * @param string $role German label of the expected role.
     */
    #[Then('/^bin ich mit der Rolle (Admin|Geschäftsstelle) angemeldet$/')]
    public function iAmLoggedInWithRole(string $role): void
    {
        if ($this->response()->getStatus() !== 200) {
            throw new RuntimeException('Die Anmeldung ist fehlgeschlagen');
        }
        $actual = (string) ($this->responseData()['user']['roleLabel'] ?? '');
        if ($actual !== $role) {
            throw new RuntimeException("Erwartete Rolle «{$role}», erhalten «{$actual}»");
        }
    }

    /**
     * Stores a person that the scenario needs up front.
     *
     * @param TableNode $table Attributes of the person.
     */
    #[Given('/^die Person ist erfasst:$/')]
    public function thePersonIsStored(TableNode $table): void
    {
        $this->iCreateThePerson($table);
        $this->thePersonIsSaved();
    }

    /**
     * Creates a person through the API.
     *
     * @param TableNode $table Attributes of the person.
     */
    #[When('/^ich die Person mit folgenden Daten erfasse:$/')]
    public function iCreateThePerson(TableNode $table): void
    {
        $this->response = $this->kernel->handle(Request::create('POST', '/persons', $this->payload($table)));
    }

    /**
     * Updates a person through the API.
     *
     * @param string    $name  Display name of the person.
     * @param TableNode $table Attributes to send.
     */
    #[When('/^ich bei "([^"]*)" folgende Daten speichere:$/')]
    public function iUpdateThePerson(string $name, TableNode $table): void
    {
        $this->response = $this->kernel->handle(
            Request::create('PUT', '/persons/' . $this->idOf($name), $this->payload($table))
        );
    }

    /**
     * Links a guardian to a minor team member.
     *
     * @param string $guardian Display name of the guardian.
     * @param string $child    Display name of the team member.
     */
    #[When('/^ich "([^"]*)" als erziehungsberechtigt mit "([^"]*)" verknüpfe$/')]
    public function iLinkTheGuardian(string $guardian, string $child): void
    {
        $this->response = $this->kernel->handle(Request::create(
            'PUT',
            '/persons/' . $this->idOf($child) . '/guardians',
            ['guardianIds' => [$this->idOf($guardian)]]
        ));
    }

    /**
     * Deletes a person.
     *
     * @param string $name Display name of the person.
     */
    #[When('/^ich "([^"]*)" lösche und die Sicherheitsabfrage bestätige$/')]
    public function iDeleteThePerson(string $name): void
    {
        $this->response = $this->kernel->handle(Request::create('DELETE', '/persons/' . $this->idOf($name)));
    }

    /**
     * Loads the person list.
     */
    #[When('/^ich die Personenliste abrufe$/')]
    public function iLoadThePersonList(): void
    {
        $this->response = $this->kernel->handle(Request::create('GET', '/persons'));
    }

    /**
     * Searches the person list.
     *
     * @param string $search Search text.
     */
    #[When('/^ich die Personenliste nach "([^"]*)" durchsuche$/')]
    public function iSearchThePersonList(string $search): void
    {
        $this->response = $this->kernel->handle(Request::create('GET', '/persons', [], ['search' => $search]));
    }

    /**
     * Opens a single person.
     *
     * @param string $name Display name of the person.
     */
    #[When('/^ich "([^"]*)" öffne$/')]
    public function iOpenThePerson(string $name): void
    {
        $this->response = $this->kernel->handle(Request::create('GET', '/persons/' . $this->idOf($name)));
    }

    /**
     * Checks that the last request succeeded.
     */
    #[Then('/^wird die Person gespeichert$/')]
    public function thePersonIsSaved(): void
    {
        $status = $this->response()->getStatus();
        if ($status !== 200 && $status !== 201) {
            throw new RuntimeException(sprintf(
                'Erwartet war ein Erfolg, erhalten: %d – %s',
                $status,
                (string) ($this->responseData()['error'] ?? '')
            ));
        }
    }

    /**
     * Checks that the last request failed with the given message.
     *
     * @param string $message Expected message.
     */
    #[Then('/^wird nicht gespeichert und ich sehe die Meldung "([^"]*)"$/')]
    public function nothingIsSavedAndISeeTheMessage(string $message): void
    {
        $status = $this->response()->getStatus();
        if ($status < 400) {
            throw new RuntimeException("Erwartet war eine Ablehnung, erhalten: {$status}");
        }
        $actual = (string) ($this->responseData()['error'] ?? '');
        if ($actual !== $message) {
            throw new RuntimeException("Erwartete Meldung «{$message}», erhalten «{$actual}»");
        }
    }

    /**
     * Checks the message of the last response.
     *
     * @param string $message Expected message.
     */
    #[Then('/^sehe ich die Meldung "([^"]*)"$/')]
    public function iSeeTheMessage(string $message): void
    {
        $actual = (string) ($this->responseData()['error'] ?? '');
        if ($actual !== $message) {
            throw new RuntimeException("Erwartete Meldung «{$message}», erhalten «{$actual}»");
        }
    }

    /**
     * Checks that the last request was refused.
     */
    #[Then('/^wird die Anfrage abgewiesen$/')]
    public function theRequestIsRefused(): void
    {
        if ($this->response()->getStatus() < 400) {
            throw new RuntimeException('Erwartet war eine Ablehnung');
        }
    }

    /**
     * Checks that a person appears in the person list.
     *
     * @param string $name Display name of the person.
     */
    #[Then('/^erscheint "([^"]*)" in der Personenliste$/')]
    public function thePersonAppearsInTheList(string $name): void
    {
        if (!in_array($name, $this->displayNamesOfTheList(), true)) {
            throw new RuntimeException("«{$name}» fehlt in der Personenliste");
        }
    }

    /**
     * Checks that a person does not appear in the person list.
     *
     * @param string $name Display name of the person.
     */
    #[Then('/^erscheint "([^"]*)" nicht in der Personenliste$/')]
    public function thePersonDoesNotAppearInTheList(string $name): void
    {
        if (in_array($name, $this->displayNamesOfTheList(), true)) {
            throw new RuntimeException("«{$name}» erscheint noch in der Personenliste");
        }
    }

    /**
     * Loads the person list without touching the response of the step before,
     * so that the steps checking the list can be combined with those checking
     * single attributes.
     *
     * @return array<int, string> The display names in the list.
     */
    private function displayNamesOfTheList(): array
    {
        $previous       = $this->response;
        $this->iLoadThePersonList();
        $names          = array_column($this->responseData()['persons'] ?? [], 'displayName');
        $this->response = $previous;

        return $names;
    }

    /**
     * Checks the number of persons in the list.
     *
     * @param int $count Expected number.
     */
    #[Then('/^enthält die Personenliste (\d+) (?:Person|Personen)$/')]
    public function theListContainsPersons(int $count): void
    {
        $actual = count($this->responseData()['persons']);
        if ($actual !== $count) {
            throw new RuntimeException("Erwartet waren {$count} Personen, erhalten {$actual}");
        }
    }

    /**
     * Checks an attribute of the person of the last response.
     *
     * @param string $field Name of the attribute.
     * @param string $value Expected value.
     */
    #[Then('/^ist das Feld "([^"]*)" auf "([^"]*)" gesetzt$/')]
    public function theFieldHasTheValue(string $field, string $value): void
    {
        $person = $this->responseData()['person'] ?? [];
        $actual = $person[$field] ?? null;
        $actual = is_bool($actual) ? ($actual ? 'true' : 'false') : (string) $actual;
        if ($actual !== $value) {
            throw new RuntimeException("Feld «{$field}»: erwartet «{$value}», erhalten «{$actual}»");
        }
    }

    /**
     * Checks that the person of the last response has no such attribute.
     *
     * @param string $field Name of the attribute.
     */
    #[Then('/^wird das Feld "([^"]*)" nicht angeboten$/')]
    public function theFieldIsNotOffered(string $field): void
    {
        $person = $this->responseData()['person'] ?? [];
        if (array_key_exists($field, $person)) {
            throw new RuntimeException("Feld «{$field}» wird unerwartet angeboten");
        }
    }

    /**
     * Checks that a guardian is linked to a team member.
     *
     * @param string $child    Display name of the team member.
     * @param string $guardian Display name of the guardian.
     */
    #[Then('/^ist bei "([^"]*)" die erziehungsberechtigte Person "([^"]*)" sichtbar$/')]
    public function theGuardianIsVisibleAtTheChild(string $child, string $guardian): void
    {
        $names = $this->namesOfThePerson($child, 'guardians');
        if (!in_array($guardian, $names, true)) {
            throw new RuntimeException("«{$guardian}» ist bei «{$child}» nicht als erziehungsberechtigt sichtbar");
        }
    }

    /**
     * Checks that a child is linked to a guardian.
     *
     * @param string $guardian Display name of the guardian.
     * @param string $child    Display name of the child.
     */
    #[Then('/^ist bei "([^"]*)" das Kind "([^"]*)" sichtbar$/')]
    public function theChildIsVisibleAtTheGuardian(string $guardian, string $child): void
    {
        $names = $this->namesOfThePerson($guardian, 'children');
        if (!in_array($child, $names, true)) {
            throw new RuntimeException("«{$child}» ist bei «{$guardian}» nicht als Kind sichtbar");
        }
    }

    /**
     * Loads a person and returns the display names of one of its relations,
     * keeping the response of the step before.
     *
     * @param string $name     Display name of the person.
     * @param string $relation Key of the relation, `guardians` or `children`.
     *
     * @return array<int, string> The display names.
     */
    private function namesOfThePerson(string $name, string $relation): array
    {
        $previous       = $this->response;
        $this->iOpenThePerson($name);
        $names          = array_column($this->responseData()['person'][$relation] ?? [], 'displayName');
        $this->response = $previous;

        return $names;
    }

    /**
     * Returns the response of the last request.
     *
     * @return Response The response.
     */
    private function response(): Response
    {
        if ($this->response === null) {
            throw new RuntimeException('Es wurde noch keine Anfrage gesendet');
        }

        return $this->response;
    }

    /**
     * Returns the payload of the last response.
     *
     * @return array<string, mixed> The payload.
     */
    private function responseData(): array
    {
        $data = $this->response()->getData();

        return is_array($data) ? $data : [];
    }

    /**
     * Turns a step table into the payload of the API.
     *
     * Values are read as text; `true` and `false` become booleans, `vor N
     * Jahren` becomes the date N years ago and a comma-separated list of names
     * under `guardians` becomes the ids of those persons.
     *
     * @param TableNode $table Table of the step.
     *
     * @return array<string, mixed> The payload.
     */
    private function payload(TableNode $table): array
    {
        $payload = [];
        foreach ($table->getRowsHash() as $field => $value) {
            if ($field === 'guardians') {
                $payload['guardianIds'] = array_map(
                    fn (string $name): int => $this->idOf(trim($name)),
                    array_filter(explode(',', $value), static fn (string $name): bool => trim($name) !== '')
                );
                continue;
            }
            $payload[$field] = $this->value($value);
        }

        return $payload;
    }

    /**
     * Converts a single table value.
     *
     * @param string $value Raw value.
     *
     * @return mixed The converted value.
     */
    private function value(string $value): mixed
    {
        $value = trim($value);

        if ($value === 'true' || $value === 'false') {
            return $value === 'true';
        }
        if ($value === '' || $value === '-') {
            return null;
        }
        if (preg_match('/^vor (\d+) Jahren$/', $value, $matches) === 1) {
            return (new DateTimeImmutable("-{$matches[1]} years"))->format('Y-m-d');
        }

        return $value;
    }

    /**
     * Returns the id of a stored person.
     *
     * @param string $name Display name of the person.
     *
     * @return int The id.
     */
    private function idOf(string $name): int
    {
        foreach ($this->personDao->findAll() as $person) {
            if ($person->getDisplayName() === $name) {
                return (int) $person->getId();
            }
        }

        throw new RuntimeException("Person «{$name}» ist nicht erfasst");
    }
}
