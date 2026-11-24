<?php

declare(strict_types=1);

namespace Clubhuesli\Model;

use Clubhuesli\Validation\ValidationException;
use Clubhuesli\Validation\Validator;
use DateTimeImmutable;

/**
 * A natural person: player, coach, parent, helper, official.
 *
 * The birth date is optional here; it is mandatory for team members (checked in
 * {@see TeamMember}) and for helpers (checked when they are assigned to a duty,
 * which is not part of sprint 1).
 */
class NaturalPerson extends AbstractPerson
{
    /** Age at which a person is of full age in Switzerland. */
    public const AGE_OF_MAJORITY = 18;

    /** @var string|null First name. */
    private ?string $firstName = null;

    /** @var string|null Last name. */
    private ?string $lastName = null;

    /** @var DateTimeImmutable|null Birth date. */
    private ?DateTimeImmutable $birthDate = null;

    /**
     * {@inheritDoc}
     */
    public function getPersonType(): PersonType
    {
        return PersonType::NATURAL;
    }

    /**
     * {@inheritDoc}
     */
    public function getDisplayName(): string
    {
        return trim(($this->getFirstName() ?? '') . ' ' . ($this->getLastName() ?? ''));
    }

    /**
     * Natural persons need an e-mail address; minor team members with a guardian
     * are the exception (see {@see TeamMember::isEmailRequired()}).
     *
     * {@inheritDoc}
     */
    public function isEmailRequired(): bool
    {
        return true;
    }

    /**
     * Returns the first name.
     *
     * @return string|null The first name, or null when not set yet.
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * Sets the first name.
     *
     * @param string|null $firstName The first name.
     *
     * @throws ValidationException When the name is missing, too short or too long.
     */
    public function setFirstName(?string $firstName): void
    {
        $this->firstName = Validator::requireText(
            $firstName,
            'firstName',
            2,
            50,
            'Bitte Vornamen erfassen (2 – 50 Zeichen)'
        );
    }

    /**
     * Returns the last name.
     *
     * @return string|null The last name, or null when not set yet.
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * Sets the last name.
     *
     * @param string|null $lastName The last name.
     *
     * @throws ValidationException When the name is missing, too short or too long.
     */
    public function setLastName(?string $lastName): void
    {
        $this->lastName = Validator::requireText(
            $lastName,
            'lastName',
            2,
            50,
            'Bitte Nachnamen erfassen (2 – 50 Zeichen)'
        );
    }

    /**
     * Returns the birth date.
     *
     * @return DateTimeImmutable|null The birth date, or null when unknown.
     */
    public function getBirthDate(): ?DateTimeImmutable
    {
        return $this->birthDate;
    }

    /**
     * Sets the birth date. It must not lie in the future.
     *
     * @param DateTimeImmutable|string|null $birthDate Birth date or a `YYYY-MM-DD` string.
     *
     * @throws ValidationException When the date is invalid or in the future.
     */
    public function setBirthDate(DateTimeImmutable|string|null $birthDate): void
    {
        if (is_string($birthDate)) {
            $normalised = Validator::optionalDate($birthDate, 'birthDate');
            $birthDate  = $normalised === null ? null : new DateTimeImmutable($normalised);
        }
        if ($birthDate !== null) {
            $birthDate = $birthDate->setTime(0, 0);
            if ($birthDate > new DateTimeImmutable('today')) {
                throw new ValidationException('birthDate', 'Geburtsdatum darf nicht in der Zukunft liegen');
            }
        }
        $this->birthDate = $birthDate;
    }

    /**
     * Returns the age in full years.
     *
     * @param DateTimeImmutable|null $reference Reference date; defaults to today.
     *
     * @return int|null The age, or null when the birth date is unknown.
     */
    public function getAge(?DateTimeImmutable $reference = null): ?int
    {
        $birthDate = $this->getBirthDate();
        if ($birthDate === null) {
            return null;
        }
        $reference = ($reference ?? new DateTimeImmutable('today'))->setTime(0, 0);

        return (int) $birthDate->diff($reference)->y;
    }

    /**
     * Tells whether the person is a minor. A person whose birth date is unknown
     * does not count as a minor.
     *
     * @param DateTimeImmutable|null $reference Reference date; defaults to today.
     *
     * @return bool True when the person is younger than 18 years.
     */
    public function isMinor(?DateTimeImmutable $reference = null): bool
    {
        $age = $this->getAge($reference);

        return $age !== null && $age < self::AGE_OF_MAJORITY;
    }

    /**
     * Creates a natural person from an API payload.
     *
     * @param array<string, mixed> $data Payload with camelCase keys.
     *
     * @return static The created person.
     *
     * @throws ValidationException When a value is invalid.
     */
    public static function fromArray(array $data): static
    {
        $person = new static();
        $person->fillFromArray($data);

        return $person;
    }

    /**
     * Copies every attribute of a natural person from an API payload.
     *
     * @param array<string, mixed> $data Payload with camelCase keys.
     *
     * @throws ValidationException When a value is invalid.
     */
    public function fillFromArray(array $data): void
    {
        $this->fillCommonFromArray($data);
        $this->setFirstName(self::readString($data, 'firstName'));
        $this->setLastName(self::readString($data, 'lastName'));
        $this->setBirthDate(self::readString($data, 'birthDate'));
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'firstName' => $this->getFirstName(),
            'lastName'  => $this->getLastName(),
            'birthDate' => $this->getBirthDate()?->format('Y-m-d'),
            'isMinor'   => $this->isMinor(),
            'teamMember' => false,
        ]);
    }
}
