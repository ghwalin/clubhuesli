<?php

declare(strict_types=1);

namespace Clubhuesli\Tests;

use Clubhuesli\Model\MemberType;
use Clubhuesli\Model\NaturalPerson;
use Clubhuesli\Validation\ValidationException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Tests the natural person and the attributes of the general person:
 * «Natürliche Personen verwalten» and «Adressen verwalten».
 */
final class NaturalPersonTest extends TestCase
{
    /**
     * Scenario 3: a passive member, honorary member or parent is stored without
     * a birth date.
     */
    public function testPassiveMemberNeedsNoBirthDate(): void
    {
        $person = NaturalPerson::fromArray([
            'memberType' => 'PASSIVE',
            'firstName'  => 'Peter',
            'lastName'   => 'Muster',
            'email'      => 'peter@example.ch',
        ]);

        self::assertSame([], $person->validate());
        self::assertNull($person->getBirthDate());
        self::assertSame(MemberType::PASSIVE, $person->getMemberType());
    }

    /**
     * Scenario 10: the note is kept.
     */
    public function testNoteIsStored(): void
    {
        $person = $this->person(['note' => 'Allergie: Nüsse']);

        self::assertSame('Allergie: Nüsse', $person->getNote());
    }

    /**
     * A first name shorter than two characters is rejected.
     */
    public function testShortFirstNameIsRejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->person(['firstName' => 'A']);
    }

    /**
     * A birth date in the future is rejected.
     */
    public function testBirthDateInTheFutureIsRejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Geburtsdatum darf nicht in der Zukunft liegen');

        $this->person(['birthDate' => (new DateTimeImmutable('+1 day'))->format('Y-m-d')]);
    }

    /**
     * «Minderjährig» is derived from the birth date.
     */
    public function testMinorIsDerivedFromBirthDate(): void
    {
        $child = $this->person(['birthDate' => (new DateTimeImmutable('-12 years'))->format('Y-m-d')]);
        $adult = $this->person(['birthDate' => (new DateTimeImmutable('-24 years'))->format('Y-m-d')]);

        self::assertTrue($child->isMinor());
        self::assertSame(12, $child->getAge());
        self::assertFalse($adult->isMinor());
    }

    /**
     * A person whose birth date is unknown does not count as a minor.
     */
    public function testPersonWithoutBirthDateIsNotMinor(): void
    {
        self::assertFalse($this->person()->isMinor());
        self::assertNull($this->person()->getAge());
    }

    /**
     * Scenario 1 of «Adressen verwalten»: a complete address is stored.
     */
    public function testCompleteAddressIsAccepted(): void
    {
        $person = $this->person([
            'street' => 'Dorfstrasse',
            'houseNumber' => '12a',
            'zip' => '8000',
            'city' => 'Zürich',
        ]);

        self::assertSame([], $person->validate());
        self::assertTrue($person->hasCompleteAddress());
        self::assertSame('CH', $person->getCountry());
    }

    /**
     * Scenario 7 of «Adressen verwalten»: a person without a postal address is
     * stored.
     */
    public function testPersonWithoutAddressIsAccepted(): void
    {
        $person = $this->person();

        self::assertSame([], $person->validate());
        self::assertFalse($person->hasAddress());
    }

    /**
     * Scenario 8 of «Adressen verwalten»: an address with a street only is
     * rejected.
     */
    public function testIncompleteAddressIsRejected(): void
    {
        $errors = $this->person(['street' => 'Dorfstrasse'])->validate();

        self::assertSame(
            [['field' => 'street', 'message' => 'Bitte Adresse vollständig erfassen (Strasse, PLZ, Ort)']],
            $errors
        );
    }

    /**
     * Scenario 6 of «Adressen verwalten»: a Swiss postal code with three digits
     * is rejected.
     */
    public function testSwissZipWithThreeDigitsIsRejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Bitte gültige PLZ eingeben');

        $this->person(['street' => 'Dorfstrasse', 'zip' => '800', 'city' => 'Zürich']);
    }

    /**
     * Scenario 5 of «Adressen verwalten»: two persons may share an address.
     */
    public function testEmailIsNotUnique(): void
    {
        $first  = $this->person(['email' => 'familie.muster@example.ch']);
        $second = $this->person(['firstName' => 'Beat', 'email' => 'familie.muster@example.ch']);

        self::assertSame($first->getEmail(), $second->getEmail());
        self::assertSame([], $first->validate());
        self::assertSame([], $second->validate());
    }

    /**
     * A natural person without an e-mail address is reported: only minor team
     * members with a guardian are exempt.
     */
    public function testEmailIsMandatoryForNaturalPersons(): void
    {
        $errors = $this->person(['email' => null])->validate();

        self::assertSame([['field' => 'email', 'message' => 'Bitte E-Mail-Adresse erfassen']], $errors);
    }

    /**
     * A member type reserved for legal persons is rejected.
     */
    public function testMemberTypeOfLegalPersonsIsRejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->person(['memberType' => 'SPONSOR']);
    }

    /**
     * Creates a natural person with sensible defaults.
     *
     * @param array<string, mixed> $overrides Attributes to overwrite.
     *
     * @return NaturalPerson The person.
     */
    private function person(array $overrides = []): NaturalPerson
    {
        return NaturalPerson::fromArray(array_merge([
            'memberType' => 'PARENT',
            'firstName'  => 'Peter',
            'lastName'   => 'Muster',
            'email'      => 'peter@example.ch',
        ], $overrides));
    }
}
