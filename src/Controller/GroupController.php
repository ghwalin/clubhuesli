<?php

declare(strict_types=1);

namespace Clubhuesli\Controller;

use Clubhuesli\Dao\GroupDao;
use Clubhuesli\Dao\MembershipDao;
use Clubhuesli\Dao\SeasonDao;
use Clubhuesli\Http\HttpException;
use Clubhuesli\Http\Request;
use Clubhuesli\Http\Response;
use Clubhuesli\Http\Session;
use Clubhuesli\Model\Group;
use Clubhuesli\Model\GroupStatus;
use Clubhuesli\Model\GroupType;
use Clubhuesli\Model\MembershipRole;
use Clubhuesli\Model\Role;
use Clubhuesli\Service\MembershipReader;
use Clubhuesli\Validation\ValidationException;
use DateTimeImmutable;

/**
 * REST endpoints for groups, their hierarchy and their members. Groups cannot
 * be deleted, only deactivated. Only the roles admin and office may use them.
 */
final class GroupController
{
    /**
     * @param GroupDao         $groupDao      Data access for groups.
     * @param SeasonDao        $seasonDao     Data access for seasons.
     * @param MembershipDao    $membershipDao Data access for memberships.
     * @param MembershipReader $reader        Read service for members.
     * @param Session          $session       Session of the current request.
     */
    public function __construct(
        private readonly GroupDao $groupDao,
        private readonly SeasonDao $seasonDao,
        private readonly MembershipDao $membershipDao,
        private readonly MembershipReader $reader,
        private readonly Session $session,
    ) {
    }

    /**
     * Returns the values the forms offer: `GET /api/groups/meta`.
     *
     * @return Response Group types, statuses and roles.
     */
    public function meta(): Response
    {
        $this->requireManager();

        $list = static fn (array $cases): array => array_map(
            static fn ($case): array => ['value' => $case->value, 'label' => $case->label()],
            $cases
        );

        return Response::ok([
            'groupTypes'      => $list(GroupType::cases()),
            'groupStatuses'   => $list(GroupStatus::cases()),
            'membershipRoles' => $list(MembershipRole::cases()),
        ]);
    }

    /**
     * Lists the groups with their direct parents and children: `GET /api/groups`.
     *
     * @param Request $request Request with the optional filters `groupType` and `status`.
     *
     * @return Response The groups.
     */
    public function index(Request $request): Response
    {
        $this->requireManager();
        $type   = GroupType::tryFrom(strtoupper((string) $request->getQueryParam('groupType', '')));
        $status = GroupStatus::tryFrom(strtoupper((string) $request->getQueryParam('status', '')));

        $hierarchy = $this->reader->hierarchy();
        $groups    = [];
        foreach ($this->groupDao->findAll() as $group) {
            if (($type !== null && $group->getGroupType() !== $type)
                || ($status !== null && $group->getStatus() !== $status)) {
                continue;
            }
            $groups[] = $this->serialise($group, $hierarchy->parentsOf((int) $group->getId()), $hierarchy->childrenOf((int) $group->getId()));
        }

        return Response::ok(['groups' => $groups]);
    }

    /**
     * Returns a group with its direct parents and children: `GET /api/groups/{id}`.
     *
     * @param Request               $request    The request.
     * @param array<string, string> $parameters Route parameters with the key `id`.
     *
     * @return Response The group.
     *
     * @throws HttpException When the group does not exist.
     */
    public function show(Request $request, array $parameters): Response
    {
        $this->requireManager();

        return Response::ok(['group' => $this->serialiseWithLinks($this->load((int) $parameters['id']))]);
    }

    /**
     * Creates a group: `POST /api/groups`.
     *
     * @param Request $request Request with `name` and `groupType`.
     *
     * @return Response The created group.
     *
     * @throws ValidationException When a rule is violated.
     */
    public function store(Request $request): Response
    {
        $this->requireManager();
        $group = Group::fromArray(array_diff_key($request->getBody(), ['status' => true]));
        $group->assertValid();
        $this->assertNameFree($group);
        $this->groupDao->save($group);

        return Response::created(['group' => $this->serialiseWithLinks($group)]);
    }

    /**
     * Updates name, group type and status of a group: `PUT /api/groups/{id}`.
     *
     * Deactivating needs that no active memberships exist and all child groups
     * are inactive.
     *
     * @param Request               $request    Request with `name`, `groupType` and `status`.
     * @param array<string, string> $parameters Route parameters with the key `id`.
     *
     * @return Response The updated group.
     *
     * @throws HttpException       When the group does not exist.
     * @throws ValidationException When a rule is violated.
     */
    public function update(Request $request, array $parameters): Response
    {
        $this->requireManager();
        $existing = $this->load((int) $parameters['id']);

        $body = $request->getBody();
        if (!array_key_exists('status', $body)) {
            $body['status'] = $existing->getStatus()->value;
        }
        $group = Group::fromArray($body);
        $group->setId($existing->getId());
        $group->assertValid();
        $this->assertNameFree($group);

        if ($existing->isActive() && !$group->isActive()) {
            $this->assertCanBeDeactivated($existing);
        }
        $this->groupDao->save($group);

        return Response::ok(['group' => $this->serialiseWithLinks($group)]);
    }

    /**
     * Makes a group the parent of another one: `POST /api/groups/{id}/children`.
     *
     * @param Request               $request    Request with `childId`.
     * @param array<string, string> $parameters Route parameters with the key `id` (the parent).
     *
     * @return Response The parent group with its links.
     *
     * @throws HttpException       When a group does not exist.
     * @throws ValidationException When the link is not allowed.
     */
    public function addChild(Request $request, array $parameters): Response
    {
        $this->requireManager();
        $parent = $this->load((int) $parameters['id']);
        $child  = $this->load((int) ($request->getBody()['childId'] ?? 0));
        $this->link($parent, $child);

        return Response::ok(['group' => $this->serialiseWithLinks($parent)]);
    }

    /**
     * Makes a group a child of another one: `POST /api/groups/{id}/parents`.
     *
     * @param Request               $request    Request with `parentId`.
     * @param array<string, string> $parameters Route parameters with the key `id` (the child).
     *
     * @return Response The child group with its links.
     *
     * @throws HttpException       When a group does not exist.
     * @throws ValidationException When the link is not allowed.
     */
    public function addParent(Request $request, array $parameters): Response
    {
        $this->requireManager();
        $child  = $this->load((int) $parameters['id']);
        $parent = $this->load((int) ($request->getBody()['parentId'] ?? 0));
        $this->link($parent, $child);

        return Response::ok(['group' => $this->serialiseWithLinks($child)]);
    }

    /**
     * Removes a link: `DELETE /api/groups/{id}/children/{childId}`.
     *
     * The groups and their memberships stay; members of the child group no
     * longer belong to the parent group through it.
     *
     * @param Request               $request    The request.
     * @param array<string, string> $parameters Route parameters `id` (parent) and `childId`.
     *
     * @return Response The parent group with its links.
     *
     * @throws HttpException When a group does not exist or the link does not exist.
     */
    public function removeChild(Request $request, array $parameters): Response
    {
        $this->requireManager();
        $parent = $this->load((int) $parameters['id']);
        $child  = $this->load((int) $parameters['childId']);
        if (!$this->groupDao->removeLink((int) $parent->getId(), (int) $child->getId())) {
            throw HttpException::notFound('Die Zuordnung existiert nicht');
        }

        return Response::ok(['group' => $this->serialiseWithLinks($parent)]);
    }

    /**
     * Lists the members of a group in a season: `GET /api/groups/{id}/members`.
     *
     * @param Request               $request    Request with the optional `seasonId` (default: the
     *                                          current season) and `includeFormer`.
     * @param array<string, string> $parameters Route parameters with the key `id`.
     *
     * @return Response The group, the season and the members.
     *
     * @throws HttpException When the group or the season does not exist.
     */
    public function members(Request $request, array $parameters): Response
    {
        $this->requireManager();
        $group = $this->load((int) $parameters['id']);

        $seasonParam = $request->getQueryParam('seasonId');
        $season      = $seasonParam === null || $seasonParam === ''
            ? $this->reader->currentSeason()
            : $this->seasonDao->find((int) $seasonParam);
        if ($season === null) {
            if ($seasonParam === null || $seasonParam === '') {
                return Response::ok([
                    'group' => $group->toArray(), 'season' => null, 'members' => [], 'previousSeasons' => [],
                ]);
            }
            throw HttpException::notFound('Saison nicht gefunden');
        }

        $includeFormer = filter_var($request->getQueryParam('includeFormer', '0'), FILTER_VALIDATE_BOOLEAN);
        $result        = $this->reader->membersOf($group, $season, $includeFormer);

        return Response::ok([
            'group'           => $group->toArray(),
            'season'          => $season->toArray(),
            'members'         => $result['members'],
            'previousSeasons' => $result['previousSeasons'],
        ]);
    }

    /**
     * Adds the link between two groups after checking the rules.
     *
     * @param Group $parent The future parent group.
     * @param Group $child  The future child group.
     *
     * @throws ValidationException When the link is not allowed.
     */
    private function link(Group $parent, Group $child): void
    {
        $parentId  = (int) $parent->getId();
        $childId   = (int) $child->getId();
        $hierarchy = $this->reader->hierarchy();

        if ($parentId === $childId) {
            throw new ValidationException('childId', 'Eine Gruppe kann nicht sich selbst zugeordnet werden');
        }
        if ($hierarchy->hasLink($parentId, $childId)) {
            throw new ValidationException('childId', 'Die Zuordnung existiert bereits');
        }
        if (!$parent->isActive() || !$child->isActive()) {
            throw new ValidationException('childId', 'Inaktive Gruppen können nicht neu zugeordnet werden');
        }
        if ($hierarchy->wouldCreateCycle($parentId, $childId)) {
            throw new ValidationException('childId', 'Die Zuordnung würde einen Zyklus in der Hierarchie erzeugen');
        }
        $this->groupDao->addLink($parentId, $childId);
    }

    /**
     * Checks that a group can be deactivated: no active memberships and only
     * inactive child groups.
     *
     * @param Group $group The group to deactivate.
     *
     * @throws ValidationException When the group is still in use.
     */
    private function assertCanBeDeactivated(Group $group): void
    {
        $groupId = (int) $group->getId();
        $today   = new DateTimeImmutable('today');

        $seasons = [];
        foreach ($this->seasonDao->findAll() as $season) {
            $seasons[$season->getId()] = $season;
        }
        foreach ($this->membershipDao->findByGroups([$groupId]) as $membership) {
            $season = $seasons[$membership->getSeasonId()] ?? null;
            if ($season !== null && $membership->isActive($season, $today)) {
                throw new ValidationException('status', 'Die Gruppe hat noch aktive Mitgliedschaften');
            }
        }

        foreach ($this->reader->hierarchy()->childrenOf($groupId) as $childId) {
            $child = $this->groupDao->find($childId);
            if ($child !== null && $child->isActive()) {
                throw new ValidationException('status', 'Die Gruppe hat noch aktive Untergruppen');
            }
        }
    }

    /**
     * Checks that no other group uses the name.
     *
     * @param Group $group The group to check.
     *
     * @throws ValidationException When the name is taken.
     */
    private function assertNameFree(Group $group): void
    {
        $same = $this->groupDao->findByName((string) $group->getName());
        if ($same !== null && $same->getId() !== $group->getId()) {
            throw new ValidationException('name', 'Diese Gruppe existiert bereits');
        }
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
    private function load(int $id): Group
    {
        $group = $this->groupDao->find($id);
        if ($group === null) {
            throw HttpException::notFound('Gruppe nicht gefunden');
        }

        return $group;
    }

    /**
     * Serialises a group together with its direct links, read from the database.
     *
     * @param Group $group The group.
     *
     * @return array<string, mixed> The payload.
     */
    private function serialiseWithLinks(Group $group): array
    {
        $hierarchy = $this->reader->hierarchy();
        $id        = (int) $group->getId();

        return $this->serialise($group, $hierarchy->parentsOf($id), $hierarchy->childrenOf($id));
    }

    /**
     * Serialises a group with its direct parents and children.
     *
     * @param Group           $group     The group.
     * @param array<int, int> $parentIds Ids of the parents.
     * @param array<int, int> $childIds  Ids of the children.
     *
     * @return array<string, mixed> The payload.
     */
    private function serialise(Group $group, array $parentIds, array $childIds): array
    {
        return $group->toArray() + [
            'parentIds' => array_values($parentIds),
            'childIds'  => array_values($childIds),
        ];
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
