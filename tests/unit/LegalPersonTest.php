<?php

declare(strict_types=1);

namespace Clubhuesli\Tests;

use Clubhuesli\Model\LegalPerson;
use Clubhuesli\Model\MemberType;
use Clubhuesli\Model\PersonType;
use Clubhuesli\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Tests the legal person: «Juristische Personen verwalten».
 */
final class LegalPersonTest extends TestCase
{
    /**
     * Scenario 1: a legal person with name, member type, e-mail and contact
     * person is valid.
     */
    public function testLegalPersonWithContactIsValid(): void
    {
        $person = $this->legalPerson();

        self::assertSame([], $person->validate());
        self::assertSame('Jensen AG', $person->getDisplayName());
        self::assertSame('Anna Muster', $person->getContactName());
        self::assertSame(PersonType::LEGAL, $person->getPersonType());
        self::assertSame(MemberType::SPONSOR, $person->getMemberType());
    }

    /**
     * Scenario 2: without the name of the contact person nothing is stored.
     */
    public function testMissingContactFirstNameIsRejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Bitte Vor- und Nachname der Kontaktperson erfassen');

        $this->legalPerson(['contactFirstName' => null]);
    }

    /**
     * Scenario 2: the last name of the contact person is mandatory as well.
     */
    public function testMissingContactLastNameIsRejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Bitte Vor- und Nachname der Kontaktperson erfassen');

        $this->legalPerson(['contactLastName' => '']);
    }

    /**
     * Scenario 3: the contact person is replaced.
     */
    public function testContactPersonCanBeChanged(): void
    {
        $person = $this->legalPerson();
        $person->setContactFirstName('Beat');
        $person->setContactLastName('Keller');

        self::assertSame('Beat Keller', $person->getContactName());
    }

    /**
     * Scenario 8: support and note are kept.
     */
    public function testSupportAndNoteAreStored(): void
    {
        $person = $this->legalPerson([
            'support' => 'Trikotsponsoring 2026–2028',
            'note'    => 'Kontakt über die Geschäftsleitung',
        ]);

        self::assertSame('Trikotsponsoring 2026–2028', $person->getSupport());
        self::assertSame('Kontakt über die Geschäftsleitung', $person->getNote());
    }

    /**
     * The name of the organisation is mandatory.
     */
    public function testMissingNameIsRejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Bitte Namen erfassen (2 – 200 Zeichen)');

        $this->legalPerson(['name' => null]);
    }

    /**
     * A member type reserved for natural persons is rejected.
     */
    public function testMemberTypeOfNaturalPersonsIsRejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->legalPerson(['memberType' => 'ACTIVE']);
    }

    /**
     * Scenario 3 of «Adressen verwalten»: the postal address is stored; the
     * e-mail address is mandatory for legal persons.
     */
    public function testAddressIsStoredAndEmailIsMandatory(): void
    {
        $person = $this->legalPerson([
            'street'      => 'Dorfstrasse',
            'houseNumber' => '12',
            'zip'         => '8000',
            'city'        => 'Zürich',
        ]);

        self::assertTrue($person->hasCompleteAddress());
        self::assertSame([], $person->validate());

        $person->setEmail(null);

        self::assertSame([['field' => 'email', 'message' => 'Bitte E-Mail-Adresse erfassen']], $person->validate());
    }

    /**
     * Creates a legal person with sensible defaults.
     *
     * @param array<string, mixed> $overrides Attributes to overwrite.
     *
     * @return LegalPerson The legal person.
     */
    private function legalPerson(array $overrides = []): LegalPerson
    {
        return LegalPerson::fromArray(array_merge([
            'memberType'       => 'SPONSOR',
            'name'             => 'Jensen AG',
            'contactFirstName' => 'Anna',
            'contactLastName'  => 'Muster',
            'email'            => 'info@jensen.ch',
        ], $overrides));
    }
}
