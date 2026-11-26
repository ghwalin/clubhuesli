<?php

declare(strict_types=1);

namespace Clubhuesli\Tests;

use Clubhuesli\Model\NaturalPerson;
use Clubhuesli\Model\TeamMember;
use Clubhuesli\Validation\ValidationException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Tests the team member: «Natürliche Personen verwalten», scenarios 1, 2, 4, 5,
 * 6, 11 and 12, and scenario 2 of «Adressen verwalten».
 */
final class TeamMemberTest extends TestCase
{
    /**
     * Scenario 1: a team member with first name, last name, member type
     * «Aktiv» and birth date is valid.
     */
    public function testTeamMemberWithBirthDateIsValid(): void
    {
        $member = $this->member();

        self::assertSame([], $member->validate());
        self::assertSame('Anna Muster', $member->getDisplayName());
        self::assertTrue($member->toArray()['teamMember']);
    }

    /**
     * Scenario 2: a team member without a birth date is not stored.
     */
    public function testTeamMemberWithoutBirthDateIsRejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Geburtsdatum ist für Teammitglieder erforderlich');

        $this->member(['birthDate' => null]);
    }

    /**
     * Scenario 4: an AHV number with a wrong check digit is not stored.
     */
    public function testInvalidAhvNumberIsRejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Bitte gültige AHV-Nummer eingeben (756.XXXX.XXXX.XX)');

        $this->member(['ahvNumber' => '756.1234.5678.96']);
    }

    /**
     * A J+S number longer than 30 characters is rejected.
     */
    public function testTooLongJsNumberIsRejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->member(['jsNumber' => str_repeat('1', 31)]);
    }

    /**
     * Scenario 12: the photo consent may be given for minors.
     */
    public function testPhotoConsentIsAllowedForMinors(): void
    {
        $member = $this->member(['birthDate' => $this->yearsAgo(14), 'photoConsent' => true]);

        self::assertTrue($member->hasPhotoConsent());
        self::assertSame([], $member->validate());
    }

    /**
     * Scenario 12: the photo consent is not offered for members of full age.
     */
    public function testPhotoConsentIsRejectedForAdults(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Die Einwilligung Foto wird nur bei minderjährigen Teammitgliedern erfasst');

        $this->member(['birthDate' => $this->yearsAgo(25), 'photoConsent' => true]);
    }

    /**
     * The photo consent defaults to «no».
     */
    public function testPhotoConsentDefaultsToNo(): void
    {
        self::assertFalse($this->member(['birthDate' => $this->yearsAgo(14)])->hasPhotoConsent());
    }

    /**
     * Scenario 5: a guardian is linked to a minor team member.
     */
    public function testGuardianCanBeLinkedToMinor(): void
    {
        $member = $this->member(['birthDate' => $this->yearsAgo(14)]);
        $member->setId(1);
        $member->addGuardian($this->guardian(2));

        self::assertCount(1, $member->getGuardians());
        self::assertSame([], $member->validate());
        self::assertSame('Peter Muster', $member->toArray()['guardians'][0]['displayName']);
    }

    /**
     * Scenario 6: a team member of full age gets no guardians.
     */
    public function testGuardianOfAdultIsRejected(): void
    {
        $member = $this->member(['birthDate' => $this->yearsAgo(20)]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Erziehungsberechtigte können nur bei minderjährigen Teammitgliedern erfasst werden');

        $member->addGuardian($this->guardian(2));
    }

    /**
     * A person cannot hold custody of itself.
     */
    public function testGuardianMustNotBeThePersonItself(): void
    {
        $member = $this->member(['birthDate' => $this->yearsAgo(14)]);
        $member->setId(7);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Eine Person kann nicht ihr eigener Erziehungsberechtigter sein');

        $member->addGuardian($this->guardian(7));
    }

    /**
     * Scenario 2 of «Adressen verwalten»: a minor team member with a guardian
     * needs no e-mail address of its own.
     */
    public function testMinorWithGuardianNeedsNoEmail(): void
    {
        $member = $this->member(['birthDate' => $this->yearsAgo(14), 'email' => null]);
        $member->setId(1);

        self::assertTrue($member->isEmailRequired());
        self::assertNotSame([], $member->validate());

        $member->addGuardian($this->guardian(2));

        self::assertFalse($member->isEmailRequired());
        self::assertSame([], $member->validate());
    }

    /**
     * Scenario 11: a player who also coaches stays one record; the role is
     * decided per group membership, so the model carries no role at all.
     */
    public function testTeamMemberHasNoRoleAttribute(): void
    {
        self::assertArrayNotHasKey('role', $this->member()->toArray());
    }

    /**
     * Creates a team member with sensible defaults.
     *
     * @param array<string, mixed> $overrides Attributes to overwrite.
     *
     * @return TeamMember The team member.
     */
    private function member(array $overrides = []): TeamMember
    {
        return TeamMember::fromArray(array_merge([
            'firstName'  => 'Anna',
            'lastName'   => 'Muster',
            'birthDate'  => $this->yearsAgo(22),
            'email'      => 'anna@example.ch',
        ], $overrides));
    }

    /**
     * Creates a stored natural person usable as a guardian.
     *
     * @param int $id Id of the person.
     *
     * @return NaturalPerson The guardian.
     */
    private function guardian(int $id): NaturalPerson
    {
        $guardian = NaturalPerson::fromArray([
            'firstName'  => 'Peter',
            'lastName'   => 'Muster',
            'email'      => 'familie.muster@example.ch',
        ]);
        $guardian->setId($id);

        return $guardian;
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
