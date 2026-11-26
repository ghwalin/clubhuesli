<?php

declare(strict_types=1);

namespace Clubhuesli\Controller;

use Clubhuesli\Dao\PersonDao;
use Clubhuesli\Http\HttpException;
use Clubhuesli\Http\Request;
use Clubhuesli\Http\Response;
use Clubhuesli\Http\Session;
use Clubhuesli\Model\AbstractPerson;
use Clubhuesli\Model\LegalPerson;
use Clubhuesli\Model\MemberType;
use Clubhuesli\Model\NaturalPerson;
use Clubhuesli\Model\PersonType;
use Clubhuesli\Model\Role;
use Clubhuesli\Model\TeamMember;
use Clubhuesli\Model\User;

/**
 * REST endpoints for the person master data: natural persons, team members and
 * legal persons including their postal and e-mail addresses.
 *
 * Only the roles admin and office may read and write person master data.
 */
final class PersonController
{
    /**
     * @param PersonDao $personDao Data access for persons.
     * @param Session   $session   Session of the current request.
     */
    public function __construct(
        private readonly PersonDao $personDao,
        private readonly Session $session,
    ) {
    }

    /**
     * Returns the person list: `GET /api/persons`.
     *
     * @param Request $request Request with the optional query parameters `search` and `personType`.
     *
     * @return Response The persons found.
     *
     * @throws HttpException When the user is not allowed to read person data.
     */
    public function index(Request $request): Response
    {
        $user       = $this->requireManager();
        $personType = $this->parsePersonType($request->getQueryParam('personType'));

        $persons = [];
        foreach ($this->personDao->findAll($request->getQueryParam('search'), $personType) as $person) {
            $persons[] = $this->serialise($person, $user);
        }

        return Response::ok(['persons' => $persons]);
    }

    /**
     * Returns a single person including guardians and children:
     * `GET /api/persons/{id}`.
     *
     * @param Request              $request    The request.
     * @param array<string, string> $parameters Route parameters with the key `id`.
     *
     * @return Response The person.
     *
     * @throws HttpException When the person does not exist.
     */
    public function show(Request $request, array $parameters): Response
    {
        $user   = $this->requireManager();
        $person = $this->loadPerson((int) $parameters['id']);

        return Response::ok(['person' => $this->serialise($person, $user, true)]);
    }

    /**
     * Creates a person: `POST /api/persons`.
     *
     * @param Request $request Request with the attributes of the person.
     *
     * @return Response The created person.
     *
     * @throws HttpException When the payload is invalid.
     */
    public function store(Request $request): Response
    {
        $user   = $this->requireManager();
        $body   = $request->getBody();
        $person = $this->createPerson($body);
        $this->applyGuardians($person, $body);

        $person->assertValid();
        $this->personDao->save($person);

        $id = $person->getId();

        return Response::created([
            'person' => $this->serialise($id === null ? $person : $this->loadPerson($id), $user, true),
        ]);
    }

    /**
     * Updates a person: `PUT /api/persons/{id}`.
     *
     * @param Request              $request    Request with the attributes of the person.
     * @param array<string, string> $parameters Route parameters with the key `id`.
     *
     * @return Response The updated person.
     *
     * @throws HttpException When the person does not exist or the payload is invalid.
     */
    public function update(Request $request, array $parameters): Response
    {
        $user     = $this->requireManager();
        $id       = (int) $parameters['id'];
        $existing = $this->loadPerson($id);

        $body   = $request->getBody();
        $person = $this->createPerson($body, $existing->getPersonType());
        $person->setId($id);
        $this->applyGuardians($person, $body);

        $person->assertValid();
        $this->personDao->save($person);

        return Response::ok(['person' => $this->serialise($this->loadPerson($id), $user, true)]);
    }

    /**
     * Deletes a person together with its guardianships, its address and its
     * group memberships (`ON DELETE CASCADE`): `DELETE /api/persons/{id}`.
     *
     * Attendances and helper duties follow with the stories that introduce them.
     *
     * @param Request              $request    The request.
     * @param array<string, string> $parameters Route parameters with the key `id`.
     *
     * @return Response An empty response.
     *
     * @throws HttpException When the person does not exist.
     */
    public function destroy(Request $request, array $parameters): Response
    {
        $this->requireManager();
        $id = (int) $parameters['id'];
        $this->loadPerson($id);

        $this->personDao->delete($id);

        return Response::noContent();
    }

    /**
     * Replaces the guardians of a minor team member:
     * `PUT /api/persons/{id}/guardians`.
     *
     * @param Request              $request    Request with the array `guardianIds`.
     * @param array<string, string> $parameters Route parameters with the key `id`.
     *
     * @return Response The updated team member.
     *
     * @throws HttpException When the person is not a minor team member or a guardian is invalid.
     */
    public function updateGuardians(Request $request, array $parameters): Response
    {
        $user   = $this->requireManager();
        $id     = (int) $parameters['id'];
        $person = $this->loadPerson($id);

        if (!$person instanceof TeamMember) {
            throw HttpException::badRequest('Erziehungsberechtigte gibt es nur bei Teammitgliedern');
        }

        $this->applyGuardians($person, $request->getBody());
        $person->assertValid();
        $this->personDao->save($person);

        return Response::ok(['person' => $this->serialise($this->loadPerson($id), $user, true)]);
    }

    /**
     * Returns the values the forms offer: `GET /api/meta`.
     *
     * @return Response Person types, member types and roles.
     */
    public function meta(): Response
    {
        $this->requireManager();

        $memberTypes = [];
        foreach (MemberType::cases() as $case) {
            $memberTypes[] = [
                'value'   => $case->value,
                'label'   => $case->label(),
                'natural' => $case->appliesTo(PersonType::NATURAL),
                'legal'   => $case->appliesTo(PersonType::LEGAL),
            ];
        }

        $personTypes = [];
        foreach (PersonType::cases() as $case) {
            $personTypes[] = ['value' => $case->value, 'label' => $case->label()];
        }

        return Response::ok([
            'personTypes'    => $personTypes,
            'memberTypes'    => $memberTypes,
            'ageOfMajority'  => NaturalPerson::AGE_OF_MAJORITY,
        ]);
    }

    /**
     * Checks that somebody with the role admin or office is logged in.
     *
     * @return User The logged-in account.
     *
     * @throws HttpException When nobody is logged in or the role does not match.
     */
    private function requireManager(): User
    {
        return $this->session->requireRole([Role::ADMIN, Role::OFFICE]);
    }

    /**
     * Loads a person or fails.
     *
     * @param int $id Id of the person.
     *
     * @return AbstractPerson The person.
     *
     * @throws HttpException When the person does not exist.
     */
    private function loadPerson(int $id): AbstractPerson
    {
        $person = $this->personDao->find($id);
        if ($person === null) {
            throw HttpException::notFound('Person nicht gefunden');
        }

        return $person;
    }

    /**
     * Builds the matching model object from a payload.
     *
     * @param array<string, mixed> $body     Payload of the request.
     * @param PersonType|null      $expected Person type of an existing record, if any.
     *
     * @return AbstractPerson The filled person.
     *
     * @throws HttpException When the person type is unknown or would change.
     */
    private function createPerson(array $body, ?PersonType $expected = null): AbstractPerson
    {
        $personType = $this->parsePersonType(
            is_string($body['personType'] ?? null) ? $body['personType'] : null
        ) ?? $expected;

        if ($personType === null) {
            throw HttpException::badRequest('Bitte Personenart angeben (NATURAL oder LEGAL)');
        }
        if ($expected !== null && $personType !== $expected) {
            throw HttpException::badRequest('Die Personenart einer gespeicherten Person kann nicht geändert werden');
        }

        if ($personType === PersonType::LEGAL) {
            return LegalPerson::fromArray($body);
        }

        return filter_var($body['teamMember'] ?? false, FILTER_VALIDATE_BOOLEAN)
            ? TeamMember::fromArray($body)
            : NaturalPerson::fromArray($body);
    }

    /**
     * Links the guardians named in the payload to a team member.
     *
     * The key `guardianIds` is optional: when it is absent the existing links
     * are kept.
     *
     * @param AbstractPerson       $person The person to link.
     * @param array<string, mixed> $body   Payload of the request.
     *
     * @throws HttpException When a guardian is unknown or not a natural person.
     */
    private function applyGuardians(AbstractPerson $person, array $body): void
    {
        if (!array_key_exists('guardianIds', $body)) {
            if ($person instanceof TeamMember && $person->getId() !== null && $person->isMinor()) {
                $person->setGuardians($this->personDao->findGuardiansOf($person->getId()));
            }

            return;
        }
        if (!$person instanceof TeamMember) {
            throw HttpException::badRequest('Erziehungsberechtigte gibt es nur bei Teammitgliedern');
        }
        if (!is_array($body['guardianIds'])) {
            throw HttpException::badRequest('«guardianIds» muss eine Liste von Personen-Ids sein');
        }

        $guardians = [];
        foreach ($body['guardianIds'] as $guardianId) {
            $guardian = $this->personDao->find((int) $guardianId);
            if (!$guardian instanceof NaturalPerson) {
                throw HttpException::badRequest(
                    'Erziehungsberechtigte müssen erfasste natürliche Personen sein'
                );
            }
            $guardians[] = $guardian;
        }
        $person->setGuardians($guardians);
    }

    /**
     * Reads a person type from a string.
     *
     * @param string|null $value Raw value.
     *
     * @return PersonType|null The person type, or null when the value is empty.
     *
     * @throws HttpException When the value is not a known person type.
     */
    private function parsePersonType(?string $value): ?PersonType
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $personType = PersonType::tryFrom(strtoupper(trim($value)));
        if ($personType === null) {
            throw HttpException::badRequest('Unbekannte Personenart');
        }

        return $personType;
    }

    /**
     * Turns a person into the payload of the API.
     *
     * The social security number is particularly sensitive and is removed for
     * roles that may not see it.
     *
     * @param AbstractPerson $person     The person to serialise.
     * @param User           $user       The account asking for the data.
     * @param bool           $withDetail Whether to add the children of the person.
     *
     * @return array<string, mixed> The payload.
     */
    private function serialise(AbstractPerson $person, User $user, bool $withDetail = false): array
    {
        $data = $person->toArray();

        if (array_key_exists('ahvNumber', $data) && $user->getRole()?->maySeeAhvNumber() !== true) {
            unset($data['ahvNumber']);
        }
        if ($withDetail && $person instanceof NaturalPerson && $person->getId() !== null) {
            $data['children'] = $this->personDao->findChildrenOf($person->getId());
        }

        return $data;
    }
}
