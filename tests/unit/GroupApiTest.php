<?php

declare(strict_types=1);

namespace Clubhuesli\Tests;

use Clubhuesli\Http\ApiKernel;
use Clubhuesli\Http\Request;
use Clubhuesli\Http\Response;
use Clubhuesli\Http\Session;
use Clubhuesli\Model\GroupHierarchy;
use Clubhuesli\Tests\Fake\InMemoryGroupDao;
use Clubhuesli\Tests\Fake\InMemoryMembershipDao;
use Clubhuesli\Tests\Fake\InMemoryPersonDao;
use Clubhuesli\Tests\Fake\InMemorySeasonDao;
use Clubhuesli\Tests\Fake\InMemoryUserDao;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Tests seasons, groups, the group hierarchy and the assignment of persons to
 * groups through the JSON API, against data access objects in memory.
 */
final class GroupApiTest extends TestCase
{
    /** @var ApiKernel The kernel under test. */
    private ApiKernel $kernel;

    /** @var int Id of the current season. */
    private int $currentSeason;

    /** @var int Id of the previous season. */
    private int $previousSeason;

    /** @var int Id of the next season. */
    private int $nextSeason;

    /**
     * Builds a kernel with a logged-in admin and three consecutive seasons.
     */
    protected function setUp(): void
    {
        $userDao = new InMemoryUserDao();
        $session = new Session($userDao);
        $session->login($userDao->getUser());

        $this->kernel = new ApiKernel(
            new InMemoryPersonDao(),
            $userDao,
            $session,
            new InMemorySeasonDao(),
            new InMemoryGroupDao(),
            new InMemoryMembershipDao()
        );

        $this->previousSeason = $this->createSeason('Vorjahr', '-465 days', '-101 days');
        $this->currentSeason  = $this->createSeason('Aktuell', '-100 days', '+200 days');
        $this->nextSeason     = $this->createSeason('Nächste', '+201 days', '+565 days');
    }

    // ----------------------------------------------------------------- seasons

    /**
     * Seasons with an end before the start are refused.
     */
    public function testSeasonEndBeforeStartIsRefused(): void
    {
        $response = $this->send('POST', '/seasons', [
            'name' => 'Falsch', 'startDate' => '2040-07-01', 'endDate' => '2040-06-30',
        ]);

        self::assertSame(422, $response->getStatus());
        self::assertSame('Enddatum darf nicht vor dem Startdatum liegen', $response->getData()['error']);
    }

    /**
     * Seasons must not overlap.
     */
    public function testOverlappingSeasonIsRefused(): void
    {
        $response = $this->send('POST', '/seasons', [
            'name'      => 'Ueberlappung',
            'startDate' => (new DateTimeImmutable('+100 days'))->format('Y-m-d'),
            'endDate'   => (new DateTimeImmutable('+150 days'))->format('Y-m-d'),
        ]);

        self::assertSame(422, $response->getStatus());
        self::assertSame('Die Saison überschneidet sich mit der Saison Aktuell', $response->getData()['error']);
    }

    /**
     * Season names are unique.
     */
    public function testDuplicateSeasonNameIsRefused(): void
    {
        $response = $this->send('POST', '/seasons', [
            'name' => 'Aktuell', 'startDate' => '2050-07-01', 'endDate' => '2051-06-30',
        ]);

        self::assertSame(422, $response->getStatus());
        self::assertSame('Diese Saison existiert bereits', $response->getData()['error']);
    }

    /**
     * The current season is marked in the list; the list is sorted latest first.
     */
    public function testSeasonListMarksCurrentSeason(): void
    {
        $seasons = $this->send('GET', '/seasons')->getData()['seasons'];

        self::assertSame(['Nächste', 'Aktuell', 'Vorjahr'], array_column($seasons, 'name'));
        self::assertSame([false, true, false], array_column($seasons, 'isCurrent'));
    }

    /**
     * A season with memberships cannot be deleted; an unused one can.
     */
    public function testSeasonInUseCannotBeDeleted(): void
    {
        $group  = $this->createGroup('Funktionäre', 'FUNCTIONARY');
        $person = $this->createNaturalPerson('Anna', 'Muster');
        $this->assign($group, $person, $this->currentSeason);

        $refused = $this->send('DELETE', "/seasons/{$this->currentSeason}");
        self::assertSame(409, $refused->getStatus());
        self::assertSame('Die Saison wird noch verwendet', $refused->getData()['error']);

        self::assertSame(204, $this->send('DELETE', "/seasons/{$this->nextSeason}")->getStatus());
    }

    // ------------------------------------------------------------------ groups

    /**
     * A new group is active; the name must be unique.
     */
    public function testCreateGroupAndRefuseDuplicateName(): void
    {
        $response = $this->send('POST', '/groups', ['name' => 'NLB', 'groupType' => 'TEAM']);
        self::assertSame(201, $response->getStatus());
        self::assertSame('ACTIVE', $response->getData()['group']['status']);

        $duplicate = $this->send('POST', '/groups', ['name' => 'NLB', 'groupType' => 'TEAM']);
        self::assertSame(422, $duplicate->getStatus());
        self::assertSame('Diese Gruppe existiert bereits', $duplicate->getData()['error']);
    }

    /**
     * Name and group type are mandatory.
     */
    public function testGroupNeedsGroupType(): void
    {
        $response = $this->send('POST', '/groups', ['name' => 'Ohne Art']);

        self::assertSame(422, $response->getStatus());
        self::assertSame('groupType', $response->getData()['errors'][0]['field']);
    }

    /**
     * Name and group type can be changed later.
     */
    public function testGroupNameAndTypeCanBeChanged(): void
    {
        $id = $this->createGroup('Helfer', 'OTHER');

        $response = $this->send('PUT', "/groups/{$id}", ['name' => 'Helfer Heim', 'groupType' => 'FUNCTIONARY']);

        self::assertSame(200, $response->getStatus());
        self::assertSame('FUNCTIONARY', $response->getData()['group']['groupType']);
        self::assertSame('Helfer Heim', $response->getData()['group']['name']);
    }

    /**
     * There is no route to delete a group.
     */
    public function testGroupsCannotBeDeleted(): void
    {
        $id = $this->createGroup('Test', 'OTHER');

        self::assertSame(405, $this->send('DELETE', "/groups/{$id}")->getStatus());
    }

    /**
     * An unused group is deactivated and can be activated again; while it is
     * inactive nobody can be assigned.
     */
    public function testDeactivateAndReactivateGroup(): void
    {
        $group  = $this->createGroup('U12', 'TEAM');
        $person = $this->createTeamMember('Lea', 'Meier');

        $off = $this->send('PUT', "/groups/{$group}", ['name' => 'U12', 'groupType' => 'TEAM', 'status' => 'INACTIVE']);
        self::assertSame(200, $off->getStatus());
        self::assertSame('INACTIVE', $off->getData()['group']['status']);

        $assign = $this->send('POST', '/memberships', [
            'groupId' => $group, 'personId' => $person, 'seasonId' => $this->currentSeason, 'role' => 'PLAYER',
        ]);
        self::assertSame(422, $assign->getStatus());
        self::assertSame('Die Gruppe ist inaktiv', $assign->getData()['error']);

        $on = $this->send('PUT', "/groups/{$group}", ['name' => 'U12', 'groupType' => 'TEAM', 'status' => 'ACTIVE']);
        self::assertSame('ACTIVE', $on->getData()['group']['status']);
    }

    /**
     * A group with an active membership cannot be deactivated; after the exit
     * date has been reached it can.
     */
    public function testDeactivateNeedsNoActiveMemberships(): void
    {
        $group  = $this->createGroup('Funktionäre', 'FUNCTIONARY');
        $person = $this->createNaturalPerson('Anna', 'Muster');
        $id     = $this->assign($group, $person, $this->currentSeason);

        $refused = $this->deactivate($group, 'Funktionäre', 'FUNCTIONARY');
        self::assertSame(422, $refused->getStatus());
        self::assertSame('Die Gruppe hat noch aktive Mitgliedschaften', $refused->getData()['error']);

        $yesterday = (new DateTimeImmutable('yesterday'))->format('Y-m-d');
        self::assertSame(200, $this->send('PUT', "/memberships/{$id}", ['exitDate' => $yesterday])->getStatus());

        self::assertSame(200, $this->deactivate($group, 'Funktionäre', 'FUNCTIONARY')->getStatus());
    }

    /**
     * Memberships of a future season are active, too.
     */
    public function testMembershipInFutureSeasonBlocksDeactivation(): void
    {
        $group  = $this->createGroup('Funktionäre', 'FUNCTIONARY');
        $person = $this->createNaturalPerson('Anna', 'Muster');
        $this->assign($group, $person, $this->nextSeason);

        self::assertSame(422, $this->deactivate($group, 'Funktionäre', 'FUNCTIONARY')->getStatus());
    }

    /**
     * Memberships of past seasons do not block the deactivation.
     */
    public function testPastMembershipsDoNotBlockDeactivation(): void
    {
        $group  = $this->createGroup('Funktionäre', 'FUNCTIONARY');
        $person = $this->createNaturalPerson('Anna', 'Muster');
        $this->assign($group, $person, $this->previousSeason);

        self::assertSame(200, $this->deactivate($group, 'Funktionäre', 'FUNCTIONARY')->getStatus());
    }

    /**
     * A group with an active child group cannot be deactivated.
     */
    public function testDeactivateNeedsInactiveChildren(): void
    {
        $parent = $this->createGroup('Funktionäre', 'FUNCTIONARY');
        $child  = $this->createGroup('Webmaster', 'FUNCTIONARY');
        $this->link($parent, $child);

        $refused = $this->deactivate($parent, 'Funktionäre', 'FUNCTIONARY');
        self::assertSame(422, $refused->getStatus());
        self::assertSame('Die Gruppe hat noch aktive Untergruppen', $refused->getData()['error']);

        self::assertSame(200, $this->deactivate($child, 'Webmaster', 'FUNCTIONARY')->getStatus());
        self::assertSame(200, $this->deactivate($parent, 'Funktionäre', 'FUNCTIONARY')->getStatus());
    }

    /**
     * The group list can be filtered by type and status.
     */
    public function testGroupListFilters(): void
    {
        $this->createGroup('NLB', 'TEAM');
        $old = $this->createGroup('U10', 'TEAM');
        $this->createGroup('Vorstand', 'FUNCTIONARY');
        $this->deactivate($old, 'U10', 'TEAM');

        $response = $this->kernel->handle(Request::create('GET', '/groups', [], ['groupType' => 'TEAM', 'status' => 'ACTIVE']));

        self::assertSame(['NLB'], array_column($response->getData()['groups'], 'name'));
    }

    // --------------------------------------------------------------- hierarchy

    /**
     * A link shows up at both groups.
     */
    public function testLinkParentAndChild(): void
    {
        $parent = $this->createGroup('Funktionärin', 'FUNCTIONARY');
        $child  = $this->createGroup('Geschäftsführerin', 'FUNCTIONARY');

        $response = $this->send('POST', "/groups/{$child}/parents", ['parentId' => $parent]);

        self::assertSame(200, $response->getStatus());
        self::assertSame([$parent], $response->getData()['group']['parentIds']);
        $parentGroup = $this->send('GET', "/groups/{$parent}")->getData()['group'];
        self::assertSame([$child], $parentGroup['childIds']);
    }

    /**
     * A group can have several parents.
     */
    public function testMultipleParents(): void
    {
        $a     = $this->createGroup('Funktionärin', 'FUNCTIONARY');
        $b     = $this->createGroup('Vorstand', 'FUNCTIONARY');
        $child = $this->createGroup('Material', 'FUNCTIONARY');
        $this->link($a, $child);
        $this->link($b, $child);

        self::assertEqualsCanonicalizing([$a, $b], $this->send('GET', "/groups/{$child}")->getData()['group']['parentIds']);
    }

    /**
     * Cycles and self-references are refused.
     */
    public function testCycleAndSelfLinkAreRefused(): void
    {
        $a = $this->createGroup('Gr A', 'OTHER');
        $b = $this->createGroup('Gr B', 'OTHER');
        $c = $this->createGroup('Gr C', 'OTHER');
        $this->link($a, $b);
        $this->link($b, $c);

        $cycle = $this->send('POST', "/groups/{$c}/children", ['childId' => $a]);
        self::assertSame(422, $cycle->getStatus());
        self::assertSame('Die Zuordnung würde einen Zyklus in der Hierarchie erzeugen', $cycle->getData()['error']);

        self::assertSame(422, $this->send('POST', "/groups/{$a}/children", ['childId' => $a])->getStatus());
    }

    /**
     * The same link cannot be created twice; a diamond is fine.
     */
    public function testDuplicateLinkIsRefusedButDiamondIsAllowed(): void
    {
        $a = $this->createGroup('Gr A', 'OTHER');
        $b = $this->createGroup('Gr B', 'OTHER');
        $c = $this->createGroup('Gr C', 'OTHER');
        $d = $this->createGroup('Gr D', 'OTHER');
        $this->link($a, $b);
        $this->link($a, $c);
        $this->link($b, $d);

        $duplicate = $this->send('POST', "/groups/{$a}/children", ['childId' => $b]);
        self::assertSame(422, $duplicate->getStatus());
        self::assertSame('Die Zuordnung existiert bereits', $duplicate->getData()['error']);

        self::assertSame(200, $this->send('POST', "/groups/{$c}/children", ['childId' => $d])->getStatus());
    }

    /**
     * Inactive groups cannot be linked anew.
     */
    public function testInactiveGroupCannotBeLinked(): void
    {
        $a = $this->createGroup('Gr A', 'OTHER');
        $b = $this->createGroup('Gr B', 'OTHER');
        $this->deactivate($b, 'Gr B', 'OTHER');

        self::assertSame(422, $this->send('POST', "/groups/{$a}/children", ['childId' => $b])->getStatus());
    }

    /**
     * Removing a link keeps both groups.
     */
    public function testRemoveLink(): void
    {
        $a = $this->createGroup('Gr A', 'OTHER');
        $b = $this->createGroup('Gr B', 'OTHER');
        $this->link($a, $b);

        $response = $this->send('DELETE', "/groups/{$a}/children/{$b}");

        self::assertSame(200, $response->getStatus());
        self::assertSame([], $response->getData()['group']['childIds']);
        self::assertSame(200, $this->send('GET', "/groups/{$b}")->getStatus());
        self::assertSame(404, $this->send('DELETE', "/groups/{$a}/children/{$b}")->getStatus());
    }

    /**
     * The hierarchy class finds ancestors and descendants once, also in a diamond.
     */
    public function testGroupHierarchyWalksDiamond(): void
    {
        $hierarchy = new GroupHierarchy([[1, 2], [1, 3], [2, 4], [3, 4]]);

        self::assertEqualsCanonicalizing([2, 3, 4], $hierarchy->descendantsOf(1));
        self::assertEqualsCanonicalizing([1, 2, 3], $hierarchy->ancestorsOf(4));
        self::assertTrue($hierarchy->wouldCreateCycle(4, 1));
        self::assertFalse($hierarchy->wouldCreateCycle(1, 4));
    }

    // ------------------------------------------------------------- memberships

    /**
     * A natural person and a legal person can be assigned to a group; the
     * default season is the current one.
     */
    public function testAssignNaturalAndLegalPerson(): void
    {
        $group = $this->createGroup('Sponsoren', 'OTHER');
        $anna  = $this->createNaturalPerson('Anna', 'Muster');
        $ag    = $this->createLegalPerson('Muster AG');

        self::assertSame(201, $this->send('POST', '/memberships', ['groupId' => $group, 'personId' => $anna])->getStatus());
        self::assertSame(201, $this->send('POST', '/memberships', ['groupId' => $group, 'personId' => $ag])->getStatus());

        $members = $this->members($group)['members'];
        self::assertCount(2, $members);
        self::assertSame('MEMBER', $members[0]['role']);
    }

    /**
     * Every member type is allowed outside of teams.
     */
    public function testAnyMemberTypeIsAllowedOutsideTeams(): void
    {
        $group = $this->createGroup('Helfer', 'OTHER');
        $peter = $this->createNaturalPerson('Peter', 'Muster', 'PARENT');

        self::assertSame(201, $this->send('POST', '/memberships', ['groupId' => $group, 'personId' => $peter])->getStatus());
    }

    /**
     * Legal persons and non-active persons are refused for teams.
     */
    public function testTeamAcceptsOnlyActiveTeamMembers(): void
    {
        $team  = $this->createGroup('U18', 'TEAM');
        $legal = $this->createLegalPerson('Muster AG');
        $peter = $this->createNaturalPerson('Peter', 'Muster', 'PASSIVE');

        $refusedLegal = $this->send('POST', '/memberships', ['groupId' => $team, 'personId' => $legal, 'role' => 'PLAYER']);
        self::assertSame(422, $refusedLegal->getStatus());
        self::assertSame('Nur natürliche Personen können Mitglied eines Teams sein', $refusedLegal->getData()['error']);

        $refusedPassive = $this->send('POST', '/memberships', ['groupId' => $team, 'personId' => $peter, 'role' => 'PLAYER']);
        self::assertSame('Nur aktive Teammitglieder können einem Team zugeordnet werden', $refusedPassive->getData()['error']);
    }

    /**
     * A team membership needs a role.
     */
    public function testTeamMembershipNeedsRole(): void
    {
        $team = $this->createGroup('U18', 'TEAM');
        $anna = $this->createTeamMember('Anna', 'Muster');

        $response = $this->send('POST', '/memberships', ['groupId' => $team, 'personId' => $anna]);

        self::assertSame(422, $response->getStatus());
        self::assertSame('role', $response->getData()['errors'][0]['field']);
    }

    /**
     * A person is only assigned once per group, season and role; another season
     * and another role are fine.
     */
    public function testDuplicateMembership(): void
    {
        $group = $this->createGroup('Funktionäre', 'FUNCTIONARY');
        $anna  = $this->createNaturalPerson('Anna', 'Muster');
        $this->assign($group, $anna, $this->currentSeason);

        $duplicate = $this->send('POST', '/memberships', [
            'groupId' => $group, 'personId' => $anna, 'seasonId' => $this->currentSeason,
        ]);
        self::assertSame(422, $duplicate->getStatus());
        self::assertSame('Anna Muster ist in dieser Saison bereits dieser Gruppe zugeordnet', $duplicate->getData()['error']);

        self::assertSame(201, $this->send('POST', '/memberships', [
            'groupId' => $group, 'personId' => $anna, 'seasonId' => $this->previousSeason,
        ])->getStatus());
    }

    /**
     * In a team the same person can be player and coach, but not twice a player.
     */
    public function testTeamPlayerAndCoachAndDuplicatePlayer(): void
    {
        $team = $this->createGroup('U18', 'TEAM');
        $lea  = $this->createTeamMember('Lea', 'Meier');
        $this->assign($team, $lea, $this->currentSeason, ['role' => 'PLAYER']);

        self::assertSame(201, $this->send('POST', '/memberships', [
            'groupId' => $team, 'personId' => $lea, 'role' => 'COACH',
        ])->getStatus());

        $duplicate = $this->send('POST', '/memberships', ['groupId' => $team, 'personId' => $lea, 'role' => 'PLAYER']);
        self::assertSame('Lea Meier ist in dieser Saison bereits Spielerin in diesem Team', $duplicate->getData()['error']);
    }

    /**
     * Shirt numbers exist for players only, and are not checked for uniqueness.
     */
    public function testShirtNumber(): void
    {
        $team = $this->createGroup('U18', 'TEAM');
        $anna = $this->createTeamMember('Anna', 'Muster');
        $lea  = $this->createTeamMember('Lea', 'Meier');
        $this->assign($team, $anna, $this->currentSeason, ['role' => 'PLAYER', 'shirtNumber' => 10]);
        $this->assign($team, $lea, $this->currentSeason, ['role' => 'PLAYER', 'shirtNumber' => 10]);

        $coach = $this->send('POST', '/memberships', [
            'groupId' => $team, 'personId' => $this->createTeamMember('Eva', 'Frei'), 'role' => 'COACH', 'shirtNumber' => 5,
        ]);
        self::assertSame(422, $coach->getStatus());
        self::assertSame('shirtNumber', $coach->getData()['errors'][0]['field']);
    }

    /**
     * Changing a player to a coach drops the shirt number.
     */
    public function testRoleChangeRemovesShirtNumber(): void
    {
        $team = $this->createGroup('U12', 'TEAM');
        $lea  = $this->createTeamMember('Lea', 'Meier');
        $id   = $this->assign($team, $lea, $this->currentSeason, ['role' => 'PLAYER', 'shirtNumber' => 7]);

        $response = $this->send('PUT', "/memberships/{$id}", ['role' => 'COACH']);

        self::assertSame(200, $response->getStatus());
        self::assertSame('COACH', $response->getData()['membership']['role']);
        self::assertNull($response->getData()['membership']['shirtNumber']);
    }

    /**
     * A correction that creates a duplicate is refused.
     */
    public function testSeasonCorrectionCannotCreateDuplicate(): void
    {
        $team = $this->createGroup('U18', 'TEAM');
        $anna = $this->createTeamMember('Anna', 'Muster');
        $this->assign($team, $anna, $this->currentSeason, ['role' => 'PLAYER']);
        $old = $this->assign($team, $anna, $this->previousSeason, ['role' => 'PLAYER']);

        $response = $this->send('PUT', "/memberships/{$old}", ['seasonId' => $this->currentSeason]);

        self::assertSame(422, $response->getStatus());
    }

    /**
     * The exit date must lie within the season, in any kind of group.
     */
    public function testExitDateMustBeWithinSeason(): void
    {
        $group = $this->createGroup('Funktionäre', 'FUNCTIONARY');
        $anna  = $this->createNaturalPerson('Anna', 'Muster');
        $id    = $this->assign($group, $anna, $this->currentSeason);

        $outside = $this->send('PUT', "/memberships/{$id}", ['exitDate' => (new DateTimeImmutable('+400 days'))->format('Y-m-d')]);
        self::assertSame(422, $outside->getStatus());
        self::assertSame('Das Austrittsdatum muss innerhalb der Saison liegen', $outside->getData()['error']);

        $inside = $this->send('PUT', "/memberships/{$id}", ['exitDate' => (new DateTimeImmutable('+10 days'))->format('Y-m-d')]);
        self::assertSame(200, $inside->getStatus());

        $removed = $this->send('PUT', "/memberships/{$id}", ['exitDate' => null]);
        self::assertNull($removed->getData()['membership']['exitDate']);
    }

    /**
     * A legal person can leave a group as well.
     */
    public function testLegalPersonCanHaveExitDate(): void
    {
        $group = $this->createGroup('Sponsoren', 'OTHER');
        $ag    = $this->createLegalPerson('Muster AG');
        $id    = $this->assign($group, $ag, $this->currentSeason);

        $response = $this->send('PUT', "/memberships/{$id}", ['exitDate' => (new DateTimeImmutable('+5 days'))->format('Y-m-d')]);

        self::assertSame(200, $response->getStatus());
    }

    /**
     * Members of a child group also belong to the parent group, once.
     */
    public function testMembersOfChildGroupBelongToParent(): void
    {
        $parent = $this->createGroup('Funktionärin', 'FUNCTIONARY');
        $ceo    = $this->createGroup('Geschäftsführerin', 'FUNCTIONARY');
        $web    = $this->createGroup('Webmaster', 'FUNCTIONARY');
        $this->link($parent, $ceo);
        $this->link($parent, $web);
        $anna = $this->createNaturalPerson('Anna', 'Muster');
        $this->assign($ceo, $anna, $this->currentSeason);
        $this->assign($web, $anna, $this->currentSeason);

        $members = $this->members($parent)['members'];

        self::assertCount(1, $members);
        self::assertTrue($members[0]['derived']);
        self::assertEqualsCanonicalizing(['Geschäftsführerin', 'Webmaster'], $members[0]['via']);

        $this->assign($parent, $anna, $this->currentSeason);
        $direct = $this->members($parent)['members'];
        self::assertCount(1, $direct);
        self::assertFalse($direct[0]['derived']);
    }

    /**
     * The derived membership follows the child memberships over a diamond.
     */
    public function testDiamondGivesMembershipOnlyOnce(): void
    {
        $a = $this->createGroup('Gr A', 'OTHER');
        $b = $this->createGroup('Gr B', 'OTHER');
        $c = $this->createGroup('Gr C', 'OTHER');
        $d = $this->createGroup('Gr D', 'OTHER');
        $this->link($a, $b);
        $this->link($a, $c);
        $this->link($b, $d);
        $this->link($c, $d);
        $this->assign($d, $this->createNaturalPerson('Anna', 'Muster'), $this->currentSeason);

        self::assertCount(1, $this->members($a)['members']);
        self::assertCount(1, $this->members($b)['members']);
    }

    /**
     * The exit from a child group applies to the parent group unless another
     * child group keeps the person.
     */
    public function testExitFromChildAppliesToParent(): void
    {
        $parent = $this->createGroup('Funktionärin', 'FUNCTIONARY');
        $ceo    = $this->createGroup('Geschäftsführerin', 'FUNCTIONARY');
        $web    = $this->createGroup('Webmaster', 'FUNCTIONARY');
        $this->link($parent, $ceo);
        $this->link($parent, $web);
        $anna    = $this->createNaturalPerson('Anna', 'Muster');
        $ceoId   = $this->assign($ceo, $anna, $this->currentSeason);
        $webId   = $this->assign($web, $anna, $this->currentSeason);
        $yesterday = (new DateTimeImmutable('yesterday'))->format('Y-m-d');

        $this->send('PUT', "/memberships/{$ceoId}", ['exitDate' => $yesterday]);
        self::assertCount(1, $this->members($parent)['members'], 'still a member via Webmaster');

        $this->send('PUT', "/memberships/{$webId}", ['exitDate' => $yesterday]);
        self::assertCount(0, $this->members($parent)['members']);

        $former = $this->kernel->handle(Request::create('GET', "/groups/{$parent}/members", [], ['includeFormer' => '1']));
        self::assertTrue($former->getData()['members'][0]['former']);
    }

    /**
     * Teams list coaches first, then players, each sorted by name. Members who
     * left are hidden unless requested; earlier seasons are listed separately.
     */
    public function testTeamListOrderAndFormerMembers(): void
    {
        $team  = $this->createGroup('U18', 'TEAM');
        $zoe   = $this->createTeamMember('Zoe', 'Albrecht');
        $anna  = $this->createTeamMember('Anna', 'Muster');
        $lea   = $this->createTeamMember('Lea', 'Meier');
        $old   = $this->createTeamMember('Olga', 'Alt');
        $this->assign($team, $anna, $this->currentSeason, ['role' => 'PLAYER']);
        $this->assign($team, $zoe, $this->currentSeason, ['role' => 'PLAYER']);
        $this->assign($team, $lea, $this->currentSeason, ['role' => 'COACH', 'function' => 'Headcoach']);
        $this->assign($team, $old, $this->previousSeason, ['role' => 'PLAYER']);

        $members = $this->members($team)['members'];
        self::assertSame(['Lea Meier', 'Zoe Albrecht', 'Anna Muster'], array_column($members, 'displayName'));
        self::assertSame('Headcoach', $members[0]['function']);

        $annaId = $this->findMembershipId($team, $anna);
        $this->send('PUT', "/memberships/{$annaId}", ['exitDate' => (new DateTimeImmutable('yesterday'))->format('Y-m-d')]);

        self::assertCount(2, $this->members($team)['members']);

        $all = $this->kernel->handle(Request::create('GET', "/groups/{$team}/members", [], ['includeFormer' => '1']))->getData();
        self::assertCount(3, $all['members']);
        self::assertSame(['Olga Alt'], array_column($all['previousSeasons'], 'displayName'));

        $previous = $this->kernel->handle(Request::create('GET', "/groups/{$team}/members", [], ['seasonId' => (string) $this->previousSeason]))->getData();
        self::assertSame(['Olga Alt'], array_column($previous['members'], 'displayName'));
    }

    /**
     * A future season is stored but the member does not appear in the current list.
     */
    public function testFutureSeasonMembershipIsNotCurrent(): void
    {
        $team = $this->createGroup('U18', 'TEAM');
        $anna = $this->createTeamMember('Anna', 'Muster');
        $this->assign($team, $anna, $this->nextSeason, ['role' => 'PLAYER']);

        self::assertCount(0, $this->members($team)['members']);
        self::assertCount(1, $this->members($team, $this->nextSeason)['members']);
    }

    /**
     * Deleting a membership removes the derived membership too, unless another
     * path keeps it.
     */
    public function testDeleteMembership(): void
    {
        $parent = $this->createGroup('Funktionärin', 'FUNCTIONARY');
        $ceo    = $this->createGroup('Geschäftsführerin', 'FUNCTIONARY');
        $this->link($parent, $ceo);
        $anna = $this->createNaturalPerson('Anna', 'Muster');
        $id   = $this->assign($ceo, $anna, $this->currentSeason);

        self::assertCount(1, $this->members($parent)['members']);
        self::assertSame(204, $this->send('DELETE', "/memberships/{$id}")->getStatus());
        self::assertCount(0, $this->members($parent)['members']);
        self::assertSame(404, $this->send('DELETE', "/memberships/{$id}")->getStatus());
    }

    /**
     * Deleting works for past seasons and inactive groups as well.
     */
    public function testDeleteMembershipOfPastSeasonAndInactiveGroup(): void
    {
        $group = $this->createGroup('Alt', 'OTHER');
        $anna  = $this->createNaturalPerson('Anna', 'Muster');
        $id    = $this->assign($group, $anna, $this->previousSeason);
        $this->deactivate($group, 'Alt', 'OTHER');

        self::assertSame(204, $this->send('DELETE', "/memberships/{$id}")->getStatus());
    }

    /**
     * The groups of a person list direct and derived memberships per season.
     */
    public function testGroupsOfPerson(): void
    {
        $parent = $this->createGroup('Funktionärin', 'FUNCTIONARY');
        $ceo    = $this->createGroup('Geschäftsführerin', 'FUNCTIONARY');
        $helper = $this->createGroup('Helfer', 'OTHER');
        $this->link($parent, $ceo);
        $anna = $this->createNaturalPerson('Anna', 'Muster');
        $this->assign($ceo, $anna, $this->currentSeason);
        $this->assign($helper, $anna, $this->currentSeason);
        $this->assign($helper, $anna, $this->previousSeason);

        $data = $this->send('GET', "/persons/{$anna}/memberships")->getData();

        self::assertCount(3, $data['memberships']);
        self::assertSame('Aktuell', $data['memberships'][0]['seasonName']);
        self::assertCount(1, $data['derived']);
        self::assertSame('Funktionärin', $data['derived'][0]['groupName']);
        self::assertSame(['Geschäftsführerin'], $data['derived'][0]['via']);
    }

    /**
     * Only admin and office may manage groups.
     */
    public function testGroupsNeedLogin(): void
    {
        $userDao = new InMemoryUserDao();
        $kernel  = new ApiKernel(new InMemoryPersonDao(), $userDao, new Session($userDao));

        self::assertSame(401, $kernel->handle(Request::create('GET', '/groups'))->getStatus());
        self::assertSame(401, $kernel->handle(Request::create('POST', '/memberships', []))->getStatus());
    }

    // --------------------------------------------------------- leaving the club

    /**
     * Leaving the club ends all memberships of the current season with the
     * season, keeps an earlier exit date and removes memberships of future
     * seasons. Past seasons are untouched.
     */
    public function testLeaveClub(): void
    {
        $team    = $this->createGroup('U18', 'TEAM');
        $passive = $this->createGroup('Passivmitglieder', 'OTHER');
        $anna    = $this->createTeamMember('Anna', 'Muster');
        $this->assign($team, $anna, $this->currentSeason, ['role' => 'PLAYER']);
        $this->assign($passive, $anna, $this->currentSeason);
        $this->assign($team, $anna, $this->nextSeason, ['role' => 'PLAYER']);
        $this->assign($team, $anna, $this->previousSeason, ['role' => 'PLAYER']);

        $earlier = (new DateTimeImmutable('+10 days'))->format('Y-m-d');
        $this->send('PUT', '/memberships/' . $this->findMembershipId($passive, $anna), ['exitDate' => $earlier]);

        $response = $this->send('POST', "/persons/{$anna}/leave-club");

        self::assertSame(200, $response->getStatus());
        $seasonEnd = (new DateTimeImmutable('+200 days'))->format('Y-m-d');
        $bySeason  = [];
        foreach ($response->getData()['memberships'] as $membership) {
            $bySeason[$membership['seasonName'] . ':' . $membership['groupName']] = $membership['exitDate'];
        }
        self::assertSame($seasonEnd, $bySeason['Aktuell:U18']);
        self::assertSame($earlier, $bySeason['Aktuell:Passivmitglieder']);
        self::assertArrayNotHasKey('Nächste:U18', $bySeason);
        self::assertNull($bySeason['Vorjahr:U18']);
    }

    // ------------------------------------------------------------------ helpers

    /**
     * Sends a request to the kernel.
     *
     * @param string               $method HTTP method.
     * @param string               $path   Path below the API root.
     * @param array<string, mixed> $body   Request body.
     *
     * @return Response The response.
     */
    private function send(string $method, string $path, array $body = []): Response
    {
        return $this->kernel->handle(Request::create($method, $path, $body));
    }

    /**
     * Creates a season relative to today and returns its id.
     *
     * @param string $name  Name of the season.
     * @param string $start Start, relative to today.
     * @param string $end   End, relative to today.
     *
     * @return int The id.
     */
    private function createSeason(string $name, string $start, string $end): int
    {
        $response = $this->send('POST', '/seasons', [
            'name'      => $name,
            'startDate' => (new DateTimeImmutable($start))->format('Y-m-d'),
            'endDate'   => (new DateTimeImmutable($end))->format('Y-m-d'),
        ]);
        self::assertSame(201, $response->getStatus(), (string) ($response->getData()['error'] ?? ''));

        return $response->getData()['season']['id'];
    }

    /**
     * Creates a group and returns its id.
     *
     * @param string $name Name of the group.
     * @param string $type Group type.
     *
     * @return int The id.
     */
    private function createGroup(string $name, string $type): int
    {
        $response = $this->send('POST', '/groups', ['name' => $name, 'groupType' => $type]);
        self::assertSame(201, $response->getStatus());

        return $response->getData()['group']['id'];
    }

    /**
     * Sets a group inactive.
     *
     * @param int    $id   Id of the group.
     * @param string $name Name of the group.
     * @param string $type Group type.
     *
     * @return Response The response.
     */
    private function deactivate(int $id, string $name, string $type): Response
    {
        return $this->send('PUT', "/groups/{$id}", ['name' => $name, 'groupType' => $type, 'status' => 'INACTIVE']);
    }

    /**
     * Makes a group the parent of another one.
     *
     * @param int $parent Id of the parent group.
     * @param int $child  Id of the child group.
     */
    private function link(int $parent, int $child): void
    {
        self::assertSame(200, $this->send('POST', "/groups/{$parent}/children", ['childId' => $child])->getStatus());
    }

    /**
     * Creates a natural person (not a team member) and returns its id.
     *
     * @param string $first      First name.
     * @param string $last       Last name.
     * @param string $memberType Member type.
     *
     * @return int The id.
     */
    private function createNaturalPerson(string $first, string $last, string $memberType = 'ACTIVE'): int
    {
        $response = $this->send('POST', '/persons', [
            'personType' => 'NATURAL', 'memberType' => $memberType, 'firstName' => $first,
            'lastName' => $last, 'email' => strtolower($first) . '@example.ch',
        ]);
        self::assertSame(201, $response->getStatus());

        return $response->getData()['person']['id'];
    }

    /**
     * Creates an active team member and returns its id.
     *
     * @param string $first First name.
     * @param string $last  Last name.
     *
     * @return int The id.
     */
    private function createTeamMember(string $first, string $last): int
    {
        $response = $this->send('POST', '/persons', [
            'personType' => 'NATURAL', 'teamMember' => true, 'memberType' => 'ACTIVE', 'firstName' => $first,
            'lastName' => $last, 'birthDate' => (new DateTimeImmutable('-22 years'))->format('Y-m-d'),
            'email' => strtolower($first) . '@example.ch',
        ]);
        self::assertSame(201, $response->getStatus(), (string) ($response->getData()['error'] ?? ''));

        return $response->getData()['person']['id'];
    }

    /**
     * Creates a legal person and returns its id.
     *
     * @param string $name Name of the company.
     *
     * @return int The id.
     */
    private function createLegalPerson(string $name): int
    {
        $response = $this->send('POST', '/persons', [
            'personType' => 'LEGAL', 'memberType' => 'SPONSOR', 'name' => $name,
            'contactFirstName' => 'Beat', 'contactLastName' => 'Keller', 'email' => 'info@example.ch',
        ]);
        self::assertSame(201, $response->getStatus(), (string) ($response->getData()['error'] ?? ''));

        return $response->getData()['person']['id'];
    }

    /**
     * Assigns a person to a group and returns the id of the membership.
     *
     * @param int                  $group  Id of the group.
     * @param int                  $person Id of the person.
     * @param int                  $season Id of the season.
     * @param array<string, mixed> $extra  Further attributes.
     *
     * @return int The id of the membership.
     */
    private function assign(int $group, int $person, int $season, array $extra = []): int
    {
        $response = $this->send('POST', '/memberships', $extra + [
            'groupId' => $group, 'personId' => $person, 'seasonId' => $season,
        ]);
        self::assertSame(201, $response->getStatus(), (string) ($response->getData()['error'] ?? ''));

        return $response->getData()['membership']['id'];
    }

    /**
     * Returns the id of the membership of a person in a group in the current season.
     *
     * @param int $group  Id of the group.
     * @param int $person Id of the person.
     *
     * @return int The id of the membership.
     */
    private function findMembershipId(int $group, int $person): int
    {
        foreach ($this->members($group, $this->currentSeason, true)['members'] as $member) {
            if ($member['personId'] === $person && $member['membershipId'] !== null) {
                return $member['membershipId'];
            }
        }
        self::fail('membership not found');
    }

    /**
     * Returns the member list of a group.
     *
     * @param int      $group         Id of the group.
     * @param int|null $season        Id of the season; default: the current one.
     * @param bool     $includeFormer Whether to include members who have left.
     *
     * @return array<string, mixed> The payload.
     */
    private function members(int $group, ?int $season = null, bool $includeFormer = false): array
    {
        $query = [];
        if ($season !== null) {
            $query['seasonId'] = (string) $season;
        }
        if ($includeFormer) {
            $query['includeFormer'] = '1';
        }

        return $this->kernel->handle(Request::create('GET', "/groups/{$group}/members", [], $query))->getData();
    }
}
