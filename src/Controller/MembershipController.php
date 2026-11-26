<?php

declare(strict_types=1);

namespace Clubhuesli\Controller;

use Clubhuesli\Dao\GroupDao;
use Clubhuesli\Dao\MembershipDao;
use Clubhuesli\Dao\PersonDao;
use Clubhuesli\Dao\SeasonDao;
use Clubhuesli\Http\HttpException;
use Clubhuesli\Http\Request;
use Clubhuesli\Http\Response;
use Clubhuesli\Http\Session;
use Clubhuesli\Model\Group;
use Clubhuesli\Model\Membership;
use Clubhuesli\Model\MembershipRole;
use Clubhuesli\Model\Role;
use Clubhuesli\Model\Season;
use Clubhuesli\Service\MembershipReader;
use Clubhuesli\Validation\ValidationException;
use DateTimeImmutable;

/**
 * REST endpoints for the assignment of persons (natural and legal) to groups.
 * A membership always refers to exactly one season. Only the roles admin and
 * office may use them.
 */
final class MembershipController
{
    /**
     * @param GroupDao         $groupDao      Data access for groups.
     * @param SeasonDao        $seasonDao     Data access for seasons.
     * @param MembershipDao    $membershipDao Data access for memberships.
     * @param PersonDao        $personDao     Data access for persons.
     * @param MembershipReader $reader        Read service for memberships.
     * @param Session          $session       Session of the current request.
     */
    public function __construct(
        private readonly GroupDao $groupDao,
        private readonly SeasonDao $seasonDao,
        private readonly MembershipDao $membershipDao,
        private readonly PersonDao $personDao,
        private readonly MembershipReader $reader,
        private readonly Session $session,
    ) {
    }

    /**
     * Assigns a person to a group for a season: `POST /api/memberships`.
     *
     * @param Request $request Request with `groupId`, `personId`, `seasonId` (default: the current
     *                         season), and optionally `role`, `function`, `shirtNumber`, `exitDate`.
     *
     * @return Response The created membership.
     *
     * @throws HttpException       When group, person or season do not exist.
     * @throws ValidationException When a rule is violated.
     */
    public function store(Request $request): Response
    {
        $this->requireManager();
        $body = $request->getBody();

        $group  = $this->loadGroup((int) ($body['groupId'] ?? 0));
        $person = $this->personDao->find((int) ($body['personId'] ?? 0));
        if ($person === null) {
            throw HttpException::notFound('Person nicht gefunden');
        }
        $season = $this->resolveSeason($body['seasonId'] ?? null);

        if (!$group->isActive()) {
            throw new ValidationException('groupId', 'Die Gruppe ist inaktiv');
        }

        $membership = new Membership();
        $membership->setGroupId($group->getId());
        $membership->setPersonId($person->getId());
        $membership->setSeasonId($season->getId());
        $membership->fillFromArray($body);

        $errors = $membership->validateAgainst($group, $season, $person);
        if ($errors !== []) {
            throw ValidationException::fromErrors($errors);
        }
        $this->assertNoDuplicate($membership, $group, $person->getDisplayName());
        $this->membershipDao->save($membership);

        return Response::created(['membership' => $membership->toArray()]);
    }

    /**
     * Corrects a membership: `PUT /api/memberships/{id}`.
     *
     * Role, function, shirt number, exit date and season can change; group and
     * person stay. A shirt number is dropped when the role changes from player
     * to coach.
     *
     * @param Request               $request    Request with the new values.
     * @param array<string, string> $parameters Route parameters with the key `id`.
     *
     * @return Response The updated membership.
     *
     * @throws HttpException       When the membership does not exist.
     * @throws ValidationException When a rule is violated.
     */
    public function update(Request $request, array $parameters): Response
    {
        $this->requireManager();
        $existing = $this->loadMembership((int) $parameters['id']);
        $group    = $this->loadGroup((int) $existing->getGroupId());
        $person   = $this->personDao->find((int) $existing->getPersonId());
        if ($person === null) {
            throw HttpException::notFound('Person nicht gefunden');
        }

        $body   = $request->getBody();
        $season = array_key_exists('seasonId', $body)
            ? $this->resolveSeason($body['seasonId'])
            : $this->loadSeason((int) $existing->getSeasonId());

        $membership = new Membership();
        $membership->setId($existing->getId());
        $membership->setGroupId($existing->getGroupId());
        $membership->setPersonId($existing->getPersonId());
        $membership->setSeasonId($season->getId());
        $membership->fillFromArray($body + [
            'role'        => $existing->getRole()->value,
            'function'    => $existing->getFunction(),
            'shirtNumber' => $existing->getShirtNumber(),
            'exitDate'    => $existing->getExitDate()?->format('Y-m-d'),
        ]);
        if ($existing->getRole() === MembershipRole::PLAYER
            && $group->isTeam()
            && $membership->getRole() !== MembershipRole::PLAYER) {
            $membership->setShirtNumber(null);
        }

        $errors = $membership->validateAgainst($group, $season, $person, false);
        if ($errors !== []) {
            throw ValidationException::fromErrors($errors);
        }
        $this->assertNoDuplicate($membership, $group, $person->getDisplayName());
        $this->membershipDao->save($membership);

        return Response::ok(['membership' => $membership->toArray()]);
    }

    /**
     * Deletes a membership: `DELETE /api/memberships/{id}`.
     *
     * Meant for corrections only; a regular exit is recorded with the exit
     * date. The user interface warns before it calls this endpoint.
     *
     * @param Request               $request    The request.
     * @param array<string, string> $parameters Route parameters with the key `id`.
     *
     * @return Response An empty response.
     *
     * @throws HttpException When the membership does not exist.
     */
    public function destroy(Request $request, array $parameters): Response
    {
        $this->requireManager();
        $membership = $this->loadMembership((int) $parameters['id']);
        $this->membershipDao->delete((int) $membership->getId());

        return Response::noContent();
    }

    /**
     * Lists the groups of a person, direct and derived: `GET /api/persons/{id}/memberships`.
     *
     * @param Request               $request    The request.
     * @param array<string, string> $parameters Route parameters with the key `id`.
     *
     * @return Response The memberships and the derived memberships.
     *
     * @throws HttpException When the person does not exist.
     */
    public function ofPerson(Request $request, array $parameters): Response
    {
        $this->requireManager();
        $personId = (int) $parameters['id'];
        if ($this->personDao->find($personId) === null) {
            throw HttpException::notFound('Person nicht gefunden');
        }

        return Response::ok($this->reader->groupsOf($personId));
    }

    /**
     * Records the exit of a person from the club: `POST /api/persons/{id}/leave-club`.
     *
     * In the current season every membership without an earlier exit date ends
     * with the season; memberships in future seasons are removed.
     *
     * @param Request               $request    The request.
     * @param array<string, string> $parameters Route parameters with the key `id`.
     *
     * @return Response The memberships after the exit.
     *
     * @throws HttpException When the person does not exist or there is no current season.
     */
    public function leaveClub(Request $request, array $parameters): Response
    {
        $this->requireManager();
        $personId = (int) $parameters['id'];
        if ($this->personDao->find($personId) === null) {
            throw HttpException::notFound('Person nicht gefunden');
        }
        $today   = new DateTimeImmutable('today');
        $current = $this->reader->currentSeason($today);
        if ($current === null) {
            throw new HttpException(409, 'Es gibt keine aktuelle Saison');
        }

        $seasons = [];
        foreach ($this->seasonDao->findAll() as $season) {
            $seasons[$season->getId()] = $season;
        }
        foreach ($this->membershipDao->findByPerson($personId) as $membership) {
            $season = $seasons[$membership->getSeasonId()] ?? null;
            if ($season === null) {
                continue;
            }
            if ($season->getStartDate() > $today) {
                $this->membershipDao->delete((int) $membership->getId());
                continue;
            }
            if ($season->getId() !== $current->getId()) {
                continue;
            }
            $exit = $membership->getExitDate();
            if ($exit === null || $exit > $current->getEndDate()) {
                $membership->setExitDate($current->getEndDate());
                $this->membershipDao->save($membership);
            }
        }

        return Response::ok($this->reader->groupsOf($personId));
    }

    /**
     * Refuses a second membership with the same group, person, season and role.
     *
     * @param Membership $membership Membership to check.
     * @param Group      $group      The group.
     * @param string     $personName Name of the person for the message.
     *
     * @throws ValidationException When the membership exists already.
     */
    private function assertNoDuplicate(Membership $membership, Group $group, string $personName): void
    {
        if ($this->membershipDao->findDuplicate($membership) === null) {
            return;
        }
        $message = $group->isTeam()
            ? sprintf('%s ist in dieser Saison bereits %s in diesem Team', $personName, $membership->getRole()->label())
            : sprintf('%s ist in dieser Saison bereits dieser Gruppe zugeordnet', $personName);

        throw new ValidationException('personId', $message);
    }

    /**
     * Reads the season of a payload; without a value the current season is used.
     *
     * @param mixed $seasonId Id from the payload, or null.
     *
     * @return Season The season.
     *
     * @throws ValidationException When no season is given and there is no current one.
     * @throws HttpException       When the season does not exist.
     */
    private function resolveSeason(mixed $seasonId): Season
    {
        if ($seasonId === null || $seasonId === '') {
            $season = $this->reader->currentSeason();
            if ($season === null) {
                throw new ValidationException('seasonId', 'Bitte Saison wählen');
            }

            return $season;
        }

        return $this->loadSeason((int) $seasonId);
    }

    /**
     * Loads a season or fails.
     *
     * @param int $id Id of the season.
     *
     * @return Season The season.
     *
     * @throws HttpException When the season does not exist.
     */
    private function loadSeason(int $id): Season
    {
        $season = $this->seasonDao->find($id);
        if ($season === null) {
            throw HttpException::notFound('Saison nicht gefunden');
        }

        return $season;
    }

    /**
     * Loads a group or fails.
     *
     * @param int $id Id of the group.
     *
     * @return Group The group.
     *
     * @throws HttpException When the group does not exist.
     */
    private function loadGroup(int $id): Group
    {
        $group = $this->groupDao->find($id);
        if ($group === null) {
            throw HttpException::notFound('Gruppe nicht gefunden');
        }

        return $group;
    }

    /**
     * Loads a membership or fails.
     *
     * @param int $id Id of the membership.
     *
     * @return Membership The membership.
     *
     * @throws HttpException When the membership does not exist.
     */
    private function loadMembership(int $id): Membership
    {
        $membership = $this->membershipDao->find($id);
        if ($membership === null) {
            throw HttpException::notFound('Zuordnung nicht gefunden');
        }

        return $membership;
    }

    /**
     * Checks that somebody with the role admin or office is logged in.
     *
     * @throws HttpException When nobody is logged in or the role does not match.
     */
    private function requireManager(): void
    {
        $this->session->requireRole([Role::ADMIN, Role::OFFICE]);
    }
}
