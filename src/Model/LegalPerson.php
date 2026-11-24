<?php

declare(strict_types=1);

namespace Clubhuesli\Model;

use Clubhuesli\Validation\ValidationException;
use Clubhuesli\Validation\Validator;

/**
 * A legal person: sponsor, patron, partner or municipality.
 *
 * A legal person does not inherit from the natural person but shares the
 * attributes of the general person. Its contact person consists of a first and
 * a last name only and is deliberately not a record of its own, so e-mail and
 * phone are those of the legal person.
 */
class LegalPerson extends AbstractPerson
{
    /** @var string|null Name of the organisation. */
    private ?string $name = null;

    /** @var string|null First name of the contact person. */
    private ?string $contactFirstName = null;

    /** @var string|null Last name of the contact person. */
    private ?string $contactLastName = null;

    /** @var string|null Description of how the organisation supports the club. */
    private ?string $support = null;

    /**
     * {@inheritDoc}
     */
    public function getPersonType(): PersonType
    {
        return PersonType::LEGAL;
    }

    /**
     * {@inheritDoc}
     */
    public function getDisplayName(): string
    {
        return $this->getName() ?? '';
    }

    /**
     * {@inheritDoc}
     */
    public function isEmailRequired(): bool
    {
        return true;
    }

    /**
     * Returns the name of the organisation.
     *
     * @return string|null The name, or null when not set yet.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Sets the name of the organisation.
     *
     * @param string|null $name The name.
     *
     * @throws ValidationException When the name is missing, too short or too long.
     */
    public function setName(?string $name): void
    {
        $this->name = Validator::requireText($name, 'name', 2, 200, 'Bitte Namen erfassen (2 – 200 Zeichen)');
    }

    /**
     * Returns the first name of the contact person.
     *
     * @return string|null The first name, or null when not set yet.
     */
    public function getContactFirstName(): ?string
    {
        return $this->contactFirstName;
    }

    /**
     * Sets the first name of the contact person.
     *
     * @param string|null $contactFirstName The first name.
     *
     * @throws ValidationException When the name is missing, too short or too long.
     */
    public function setContactFirstName(?string $contactFirstName): void
    {
        $this->contactFirstName = Validator::requireText(
            $contactFirstName,
            'contactFirstName',
            2,
            50,
            'Bitte Vor- und Nachname der Kontaktperson erfassen'
        );
    }

    /**
     * Returns the last name of the contact person.
     *
     * @return string|null The last name, or null when not set yet.
     */
    public function getContactLastName(): ?string
    {
        return $this->contactLastName;
    }

    /**
     * Sets the last name of the contact person.
     *
     * @param string|null $contactLastName The last name.
     *
     * @throws ValidationException When the name is missing, too short or too long.
     */
    public function setContactLastName(?string $contactLastName): void
    {
        $this->contactLastName = Validator::requireText(
            $contactLastName,
            'contactLastName',
            2,
            50,
            'Bitte Vor- und Nachname der Kontaktperson erfassen'
        );
    }

    /**
     * Returns the name of the contact person.
     *
     * @return string The full name of the contact person.
     */
    public function getContactName(): string
    {
        return trim(($this->getContactFirstName() ?? '') . ' ' . ($this->getContactLastName() ?? ''));
    }

    /**
     * Returns the description of the support.
     *
     * @return string|null The description, or null when none was entered.
     */
    public function getSupport(): ?string
    {
        return $this->support;
    }

    /**
     * Sets the description of how the organisation supports the club.
     *
     * @param string|null $support Free text, e.g. «Trikotsponsoring 2026–2028».
     */
    public function setSupport(?string $support): void
    {
        $this->support = Validator::trimToNull($support);
    }

    /**
     * Creates a legal person from an API payload.
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
     * Copies every attribute of a legal person from an API payload.
     *
     * @param array<string, mixed> $data Payload with camelCase keys.
     *
     * @throws ValidationException When a value is invalid.
     */
    public function fillFromArray(array $data): void
    {
        $this->fillCommonFromArray($data);
        $this->setName(self::readString($data, 'name'));
        $this->setContactFirstName(self::readString($data, 'contactFirstName'));
        $this->setContactLastName(self::readString($data, 'contactLastName'));
        $this->setSupport(self::readString($data, 'support'));
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'name'             => $this->getName(),
            'contactFirstName' => $this->getContactFirstName(),
            'contactLastName'  => $this->getContactLastName(),
            'contactName'      => $this->getContactName(),
            'support'          => $this->getSupport(),
        ]);
    }
}
