<?php

declare(strict_types=1);

namespace Clubhuesli\Tests;

use Clubhuesli\Http\ApiKernel;
use Clubhuesli\Http\Request;
use Clubhuesli\Http\Session;
use Clubhuesli\Tests\Fake\InMemoryPersonDao;
use Clubhuesli\Tests\Fake\InMemoryUserDao;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Tests the JSON API end to end – router, controllers, models and validation –
 * against data access objects that keep their data in memory.
 */
final class PersonApiTest extends TestCase
{
    /** @var ApiKernel The kernel under test. */
    private ApiKernel $kernel;

    /** @var InMemoryPersonDao Data access for persons. */
    private InMemoryPersonDao $personDao;

    /**
     * Builds a kernel with a logged-in admin.
     */
    protected function setUp(): void
    {
        $this->personDao = new InMemoryPersonDao();
        $userDao         = new InMemoryUserDao();
        $session         = new Session($userDao);
        $session->login($userDao->getUser());

        $this->kernel = new ApiKernel($this->personDao, $userDao, $session);
    }

    /**
     * Without a login the person list is refused.
     */
    public function testPersonListNeedsLogin(): void
    {
        $userDao = new InMemoryUserDao();
        $kernel  = new ApiKernel(new InMemoryPersonDao(), $userDao, new Session($userDao));

        $response = $kernel->handle(Request::create('GET', '/persons'));

        self::assertSame(401, $response->getStatus());
    }

    /**
     * A wrong password is refused with the same message as an unknown account.
     */
    public function testLoginWithWrongPasswordIsRefused(): void
    {
        $userDao = new InMemoryUserDao();
        $kernel  = new ApiKernel(new InMemoryPersonDao(), $userDao, new Session($userDao));

        $response = $kernel->handle(Request::create('POST', '/auth/login', [
            'email'    => 'admin@example.org',
            'password' => 'falsch1234',
        ]));

        self::assertSame(401, $response->getStatus());
        self::assertSame('E-Mail-Adresse oder Passwort ist falsch', $response->getData()['error']);
    }

    /**
     * The correct credentials log the account in.
     */
    public function testLoginSucceeds(): void
    {
        $userDao = new InMemoryUserDao();
        $kernel  = new ApiKernel(new InMemoryPersonDao(), $userDao, new Session($userDao));

        $response = $kernel->handle(Request::create('POST', '/auth/login', [
            'email'    => 'admin@example.org',
            'password' => 'geheim1234',
        ]));

        self::assertSame(200, $response->getStatus());
        self::assertSame('admin', $response->getData()['user']['role']);
    }

    /**
     * Scenario 1 of «Natürliche Personen verwalten»: a team member is created
     * and appears in the person list.
     */
    public function testCreateTeamMemberAndFindItInTheList(): void
    {
        $response = $this->post('/persons', $this->teamMemberPayload());

        self::assertSame(201, $response->getStatus());
        $person = $response->getData()['person'];
        self::assertSame('Anna Muster', $person['displayName']);
        self::assertTrue($person['teamMember']);
        self::assertSame('756.1234.5678.97', $person['ahvNumber']);

        $list = $this->kernel->handle(Request::create('GET', '/persons'));

        self::assertSame(200, $list->getStatus());
        self::assertCount(1, $list->getData()['persons']);
    }

    /**
     * Scenario 2: a team member without a birth date is refused with the
     * message from the story.
     */
    public function testCreateTeamMemberWithoutBirthDateFails(): void
    {
        $payload = $this->teamMemberPayload();
        unset($payload['birthDate']);

        $response = $this->post('/persons', $payload);

        self::assertSame(422, $response->getStatus());
        self::assertSame('Geburtsdatum ist für Teammitglieder erforderlich', $response->getData()['error']);
        self::assertSame('birthDate', $response->getData()['errors'][0]['field']);
    }

    /**
     * Scenario 3: a passive member is created without birth date, AHV and J+S
     * number.
     */
    public function testCreatePassiveMemberWithoutBirthDate(): void
    {
        $response = $this->post('/persons', [
            'personType' => 'NATURAL',
            'memberType' => 'PASSIVE',
            'firstName'  => 'Peter',
            'lastName'   => 'Muster',
            'email'      => 'peter@example.ch',
        ]);

        self::assertSame(201, $response->getStatus());
        $person = $response->getData()['person'];
        self::assertFalse($person['teamMember']);
        self::assertNull($person['birthDate']);
        self::assertArrayNotHasKey('ahvNumber', $person);
    }

    /**
     * Scenario 4: an AHV number with a wrong check digit is refused.
     */
    public function testCreateTeamMemberWithInvalidAhvNumberFails(): void
    {
        $response = $this->post('/persons', $this->teamMemberPayload(['ahvNumber' => '756.1234.5678.96']));

        self::assertSame(422, $response->getStatus());
        self::assertSame(
            'Bitte gültige AHV-Nummer eingeben (756.XXXX.XXXX.XX)',
            $response->getData()['error']
        );
    }

    /**
     * Scenario 5: the guardian is linked and the relation is visible at both
     * persons.
     */
    public function testLinkGuardianToMinor(): void
    {
        $guardianId = $this->post('/persons', [
            'personType' => 'NATURAL',
            'memberType' => 'PARENT',
            'firstName'  => 'Peter',
            'lastName'   => 'Muster',
            'email'      => 'familie.muster@example.ch',
        ])->getData()['person']['id'];

        $childId = $this->post('/persons', $this->teamMemberPayload([
            'birthDate' => $this->yearsAgo(14),
            'email'     => null,
            'guardianIds' => [$guardianId],
        ]))->getData()['person']['id'];

        $child = $this->kernel->handle(Request::create('GET', "/persons/{$childId}"))->getData()['person'];
        self::assertCount(1, $child['guardians']);
        self::assertSame('Peter Muster', $child['guardians'][0]['displayName']);

        $guardian = $this->kernel->handle(Request::create('GET', "/persons/{$guardianId}"))->getData()['person'];
        self::assertSame([['id' => $childId, 'displayName' => 'Anna Muster']], $guardian['children']);
    }

    /**
     * Scenario 6: a team member of full age gets no guardians.
     */
    public function testGuardianForAdultIsRefused(): void
    {
        $guardianId = $this->post('/persons', [
            'personType' => 'NATURAL',
            'memberType' => 'PARENT',
            'firstName'  => 'Peter',
            'lastName'   => 'Muster',
            'email'      => 'peter@example.ch',
        ])->getData()['person']['id'];

        $response = $this->post('/persons', $this->teamMemberPayload(['guardianIds' => [$guardianId]]));

        self::assertSame(422, $response->getStatus());
        self::assertStringContainsString('minderjährigen Teammitgliedern', $response->getData()['error']);
    }

    /**
     * Scenario 7: changes to a person are stored.
     */
    public function testUpdatePerson(): void
    {
        $id = $this->post('/persons', $this->teamMemberPayload())->getData()['person']['id'];

        $response = $this->kernel->handle(Request::create('PUT', "/persons/{$id}", $this->teamMemberPayload([
            'lastName'   => 'Keller',
            'memberType' => 'HONORARY',
            'note'       => 'Name nach Heirat geändert',
        ])));

        self::assertSame(200, $response->getStatus());
        $person = $response->getData()['person'];
        self::assertSame('Anna Keller', $person['displayName']);
        self::assertSame('HONORARY', $person['memberType']);
        self::assertSame('Name nach Heirat geändert', $person['note']);
    }

    /**
     * The person type of a stored person cannot be changed.
     */
    public function testPersonTypeCannotChange(): void
    {
        $id = $this->post('/persons', $this->teamMemberPayload())->getData()['person']['id'];

        $response = $this->kernel->handle(Request::create('PUT', "/persons/{$id}", [
            'personType'       => 'LEGAL',
            'memberType'       => 'SPONSOR',
            'name'             => 'Jensen AG',
            'contactFirstName' => 'Anna',
            'contactLastName'  => 'Muster',
            'email'            => 'info@jensen.ch',
        ]));

        self::assertSame(400, $response->getStatus());
    }

    /**
     * Scenario 9: the person, its relations and its address are removed.
     */
    public function testDeletePerson(): void
    {
        $id = $this->post('/persons', $this->teamMemberPayload())->getData()['person']['id'];

        $response = $this->kernel->handle(Request::create('DELETE', "/persons/{$id}"));

        self::assertSame(204, $response->getStatus());
        self::assertSame(404, $this->kernel->handle(Request::create('GET', "/persons/{$id}"))->getStatus());
    }

    /**
     * Scenario 12: the photo consent is refused for a team member of full age.
     */
    public function testPhotoConsentForAdultIsRefused(): void
    {
        $response = $this->post('/persons', $this->teamMemberPayload(['photoConsent' => true]));

        self::assertSame(422, $response->getStatus());
        self::assertStringContainsString('Einwilligung Foto', $response->getData()['error']);
    }

    /**
     * Scenario 1 of «Juristische Personen verwalten»: a legal person with a
     * contact person is created.
     */
    public function testCreateLegalPerson(): void
    {
        $response = $this->post('/persons', [
            'personType'       => 'LEGAL',
            'memberType'       => 'SPONSOR',
            'name'             => 'Jensen AG',
            'contactFirstName' => 'Anna',
            'contactLastName'  => 'Muster',
            'email'            => 'info@jensen.ch',
            'street'           => 'Dorfstrasse',
            'houseNumber'      => '12',
            'zip'              => '8000',
            'city'             => 'Zürich',
            'support'          => 'Trikotsponsoring 2026–2028',
        ]);

        self::assertSame(201, $response->getStatus());
        $person = $response->getData()['person'];
        self::assertSame('Jensen AG', $person['displayName']);
        self::assertSame('Anna Muster', $person['contactName']);
        self::assertSame('Sponsor', $person['memberTypeLabel']);
        self::assertSame('Trikotsponsoring 2026–2028', $person['support']);
    }

    /**
     * Scenario 2 of «Juristische Personen verwalten»: without a contact person
     * nothing is stored.
     */
    public function testLegalPersonWithoutContactFails(): void
    {
        $response = $this->post('/persons', [
            'personType' => 'LEGAL',
            'memberType' => 'SPONSOR',
            'name'       => 'Jensen AG',
            'email'      => 'info@jensen.ch',
        ]);

        self::assertSame(422, $response->getStatus());
        self::assertSame('Bitte Vor- und Nachname der Kontaktperson erfassen', $response->getData()['error']);
    }

    /**
     * Scenario 8 of «Adressen verwalten»: an incomplete postal address is
     * refused.
     */
    public function testIncompleteAddressIsRefused(): void
    {
        $response = $this->post('/persons', $this->teamMemberPayload(['street' => 'Dorfstrasse']));

        self::assertSame(422, $response->getStatus());
        self::assertSame(
            'Bitte Adresse vollständig erfassen (Strasse, PLZ, Ort)',
            $response->getData()['error']
        );
    }

    /**
     * Scenario 5 of «Adressen verwalten»: a family may share one address.
     */
    public function testTwoPersonsMayShareAnEmailAddress(): void
    {
        $first = $this->post('/persons', $this->teamMemberPayload(['email' => 'familie.muster@example.ch']));
        $second = $this->post('/persons', $this->teamMemberPayload([
            'firstName' => 'Lea',
            'email'     => 'familie.muster@example.ch',
        ]));

        self::assertSame(201, $first->getStatus());
        self::assertSame(201, $second->getStatus());
    }

    /**
     * The person list can be searched and filtered by person type.
     */
    public function testListIsSearchableAndFilterable(): void
    {
        $this->post('/persons', $this->teamMemberPayload());
        $this->post('/persons', [
            'personType'       => 'LEGAL',
            'memberType'       => 'SPONSOR',
            'name'             => 'Jensen AG',
            'contactFirstName' => 'Anna',
            'contactLastName'  => 'Muster',
            'email'            => 'info@jensen.ch',
        ]);

        $all = $this->kernel->handle(Request::create('GET', '/persons'));
        self::assertCount(2, $all->getData()['persons']);

        $legal = $this->kernel->handle(Request::create('GET', '/persons', [], ['personType' => 'LEGAL']));
        self::assertCount(1, $legal->getData()['persons']);

        $search = $this->kernel->handle(Request::create('GET', '/persons', [], ['search' => 'jensen']));
        self::assertCount(1, $search->getData()['persons']);
    }

    /**
     * The metadata names the member types of both person types.
     */
    public function testMetaListsMemberTypes(): void
    {
        $response = $this->kernel->handle(Request::create('GET', '/meta'));

        self::assertSame(200, $response->getStatus());
        $natural = array_filter($response->getData()['memberTypes'], static fn (array $t): bool => $t['natural']);
        $legal   = array_filter($response->getData()['memberTypes'], static fn (array $t): bool => $t['legal']);
        self::assertCount(4, $natural);
        self::assertCount(4, $legal);
        self::assertSame(18, $response->getData()['ageOfMajority']);
    }

    /**
     * An unknown endpoint and a wrong method are reported.
     */
    public function testUnknownEndpointAndMethod(): void
    {
        self::assertSame(404, $this->kernel->handle(Request::create('GET', '/unknown'))->getStatus());
        self::assertSame(405, $this->kernel->handle(Request::create('PATCH', '/persons'))->getStatus());
    }

    /**
     * Sends a POST request to the kernel.
     *
     * @param string               $path Path below the API root.
     * @param array<string, mixed> $body Request body.
     *
     * @return \Clubhuesli\Http\Response The response.
     */
    private function post(string $path, array $body): \Clubhuesli\Http\Response
    {
        return $this->kernel->handle(Request::create('POST', $path, $body));
    }

    /**
     * Returns the payload of a team member with sensible defaults.
     *
     * @param array<string, mixed> $overrides Attributes to overwrite.
     *
     * @return array<string, mixed> The payload.
     */
    private function teamMemberPayload(array $overrides = []): array
    {
        return array_merge([
            'personType' => 'NATURAL',
            'teamMember' => true,
            'memberType' => 'ACTIVE',
            'firstName'  => 'Anna',
            'lastName'   => 'Muster',
            'birthDate'  => $this->yearsAgo(22),
            'email'      => 'anna@example.ch',
            'ahvNumber'  => '756.1234.5678.97',
        ], $overrides);
    }

    /**
     * Returns the date the given number of years ago.
     *
     * @param int $years Number of years.
     *
     * @return string The date in `YYYY-MM-DD` notation.
     */
    private function yearsAgo(int $years): string
    {
        return (new DateTimeImmutable("-{$years} years"))->format('Y-m-d');
    }
}
