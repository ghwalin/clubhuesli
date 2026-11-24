<?php

declare(strict_types=1);

namespace Clubhuesli\Model;

use Clubhuesli\Validation\ValidationException;
use Clubhuesli\Validation\Validator;

/**
 * Common base of every person: membership kind, contact data, optional postal
 * address and a free-text note.
 *
 * All attributes are private and are read and written through getters and
 * setters, including inside this class. Single values are validated in the
 * setters; rules spanning several attributes are checked in {@see validate()},
 * which the controllers call before persisting.
 */
abstract class AbstractPerson
{
    /** @var int|null Primary key; null until the person is stored. */
    private ?int $id = null;

    /** @var MemberType|null Kind of membership. */
    private ?MemberType $memberType = null;

    /** @var string|null Contact e-mail address; not unique. */
    private ?string $email = null;

    /** @var string|null Phone number. */
    private ?string $phone = null;

    /** @var string|null Street of the postal address. */
    private ?string $street = null;

    /** @var string|null House number. */
    private ?string $houseNumber = null;

    /** @var string|null Address addition such as a P.O. box or c/o line. */
    private ?string $addressAddition = null;

    /** @var string|null Postal code. */
    private ?string $zip = null;

    /** @var string|null City. */
    private ?string $city = null;

    /** @var string ISO 3166-1 alpha-2 country code; defaults to `CH`. */
    private string $country = 'CH';

    /** @var string|null Free-text note. */
    private ?string $note = null;

    /**
     * Returns the discriminator of this person.
     *
     * @return PersonType The person type.
     */
    abstract public function getPersonType(): PersonType;

    /**
     * Returns the name shown in lists and e-mails.
     *
     * @return string The display name.
     */
    abstract public function getDisplayName(): string;

    /**
     * Tells whether an e-mail address must be present for this person.
     *
     * @return bool True when the e-mail address is mandatory.
     */
    abstract public function isEmailRequired(): bool;

    /**
     * Returns the primary key.
     *
     * @return int|null The id, or null when not stored yet.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Sets the primary key.
     *
     * @param int|null $id The id.
     *
     * @throws ValidationException When the id is not positive.
     */
    public function setId(?int $id): void
    {
        if ($id !== null && $id <= 0) {
            throw new ValidationException('id', 'Ungültige Personen-Id');
        }
        $this->id = $id;
    }

    /**
     * Returns the kind of membership.
     *
     * @return MemberType|null The member type, or null when not set yet.
     */
    public function getMemberType(): ?MemberType
    {
        return $this->memberType;
    }

    /**
     * Sets the kind of membership.
     *
     * @param MemberType|string|null $memberType Member type or its name.
     *
     * @throws ValidationException When the value is unknown or does not fit the person type.
     */
    public function setMemberType(MemberType|string|null $memberType): void
    {
        if ($memberType === null || $memberType === '') {
            throw new ValidationException('memberType', 'Bitte Mitgliederart wählen');
        }
        if (is_string($memberType)) {
            $parsed = MemberType::tryFrom(strtoupper(trim($memberType)));
            if ($parsed === null) {
                throw new ValidationException('memberType', 'Bitte Mitgliederart wählen');
            }
            $memberType = $parsed;
        }
        if (!$memberType->appliesTo($this->getPersonType())) {
            throw new ValidationException(
                'memberType',
                sprintf('Mitgliederart «%s» ist für diese Personenart nicht erlaubt', $memberType->label())
            );
        }
        $this->memberType = $memberType;
    }

    /**
     * Returns the contact e-mail address.
     *
     * @return string|null The address, or null when none is known.
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Sets the contact e-mail address. Addresses are not unique: families often
     * share one.
     *
     * @param string|null $email The address.
     *
     * @throws ValidationException When the format is invalid.
     */
    public function setEmail(?string $email): void
    {
        $this->email = Validator::optionalEmail($email);
    }

    /**
     * Returns the phone number.
     *
     * @return string|null The number, or null when none is known.
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * Sets the phone number.
     *
     * @param string|null $phone The number.
     *
     * @throws ValidationException When the number contains unexpected characters.
     */
    public function setPhone(?string $phone): void
    {
        $this->phone = Validator::optionalPhone($phone);
    }

    /**
     * Returns the street.
     *
     * @return string|null The street, or null when none is known.
     */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * Sets the street.
     *
     * @param string|null $street The street.
     *
     * @throws ValidationException When the street is too short or too long.
     */
    public function setStreet(?string $street): void
    {
        $this->street = Validator::optionalText($street, 'street', 2, 150, 'Bitte gültige Strasse eingeben (2 – 150 Zeichen)');
    }

    /**
     * Returns the house number.
     *
     * @return string|null The house number, or null when none is known.
     */
    public function getHouseNumber(): ?string
    {
        return $this->houseNumber;
    }

    /**
     * Sets the house number.
     *
     * @param string|null $houseNumber The house number.
     *
     * @throws ValidationException When the house number is too long.
     */
    public function setHouseNumber(?string $houseNumber): void
    {
        $this->houseNumber = Validator::optionalText($houseNumber, 'houseNumber', 1, 20, 'Hausnummer: maximal 20 Zeichen');
    }

    /**
     * Returns the address addition.
     *
     * @return string|null The addition, or null when none is known.
     */
    public function getAddressAddition(): ?string
    {
        return $this->addressAddition;
    }

    /**
     * Sets the address addition (P.O. box, c/o line).
     *
     * @param string|null $addressAddition The addition.
     *
     * @throws ValidationException When the addition is too long.
     */
    public function setAddressAddition(?string $addressAddition): void
    {
        $this->addressAddition = Validator::optionalText(
            $addressAddition,
            'addressAddition',
            1,
            100,
            'Adresszusatz: maximal 100 Zeichen'
        );
    }

    /**
     * Returns the postal code.
     *
     * @return string|null The postal code, or null when none is known.
     */
    public function getZip(): ?string
    {
        return $this->zip;
    }

    /**
     * Sets the postal code. Swiss postal codes consist of exactly four digits.
     *
     * @param string|null $zip The postal code.
     *
     * @throws ValidationException When the postal code does not fit the country.
     */
    public function setZip(?string $zip): void
    {
        $this->zip = Validator::optionalZip($zip, $this->getCountry());
    }

    /**
     * Returns the city.
     *
     * @return string|null The city, or null when none is known.
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * Sets the city.
     *
     * @param string|null $city The city.
     *
     * @throws ValidationException When the city is too short or too long.
     */
    public function setCity(?string $city): void
    {
        $this->city = Validator::optionalText($city, 'city', 2, 100, 'Bitte gültigen Ort eingeben (2 – 100 Zeichen)');
    }

    /**
     * Returns the ISO country code of the postal address.
     *
     * @return string The country code.
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * Sets the ISO country code and re-checks the postal code against it.
     *
     * @param string|null $country The country code; null falls back to `CH`.
     *
     * @throws ValidationException When the code or the current postal code is invalid.
     */
    public function setCountry(?string $country): void
    {
        $this->country = Validator::countryCode($country);
        $this->setZip($this->getZip());
    }

    /**
     * Returns the free-text note.
     *
     * @return string|null The note, or null when none was entered.
     */
    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * Sets the free-text note.
     *
     * @param string|null $note The note.
     */
    public function setNote(?string $note): void
    {
        $this->note = Validator::trimToNull($note);
    }

    /**
     * Tells whether at least one field of the postal address is filled in.
     *
     * @return bool True when a postal address was started.
     */
    public function hasAddress(): bool
    {
        return $this->getStreet() !== null
            || $this->getHouseNumber() !== null
            || $this->getAddressAddition() !== null
            || $this->getZip() !== null
            || $this->getCity() !== null;
    }

    /**
     * Tells whether the postal address is complete (street, postal code, city).
     *
     * @return bool True when the person can be reached by mail.
     */
    public function hasCompleteAddress(): bool
    {
        return $this->getStreet() !== null && $this->getZip() !== null && $this->getCity() !== null;
    }

    /**
     * Copies the attributes of the general person from an API payload.
     *
     * @param array<string, mixed> $data Payload with camelCase keys.
     *
     * @throws ValidationException When a value is invalid.
     */
    protected function fillCommonFromArray(array $data): void
    {
        $this->setMemberType(self::readString($data, 'memberType'));
        // The country is set before the postal code so that the Swiss rule applies.
        $this->setCountry(self::readString($data, 'country'));
        $this->setEmail(self::readString($data, 'email'));
        $this->setPhone(self::readString($data, 'phone'));
        $this->setStreet(self::readString($data, 'street'));
        $this->setHouseNumber(self::readString($data, 'houseNumber'));
        $this->setAddressAddition(self::readString($data, 'addressAddition'));
        $this->setZip(self::readString($data, 'zip'));
        $this->setCity(self::readString($data, 'city'));
        $this->setNote(self::readString($data, 'note'));
    }

    /**
     * Checks the rules that span several attributes.
     *
     * @return array<int, array{field: string, message: string}> The errors found; empty when valid.
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->getMemberType() === null) {
            $errors[] = ['field' => 'memberType', 'message' => 'Bitte Mitgliederart wählen'];
        }
        if ($this->hasAddress() && !$this->hasCompleteAddress()) {
            $errors[] = [
                'field'   => 'street',
                'message' => 'Bitte Adresse vollständig erfassen (Strasse, PLZ, Ort)',
            ];
        }
        if ($this->isEmailRequired() && $this->getEmail() === null) {
            $errors[] = ['field' => 'email', 'message' => 'Bitte E-Mail-Adresse erfassen'];
        }

        return $errors;
    }

    /**
     * Validates the person and throws when it is not consistent.
     *
     * @throws ValidationException When at least one rule is violated.
     */
    public function assertValid(): void
    {
        $errors = $this->validate();
        if ($errors !== []) {
            throw ValidationException::fromErrors($errors);
        }
    }

    /**
     * Returns the attributes of the general person for the JSON API.
     *
     * @return array<string, mixed> The attributes.
     */
    public function toArray(): array
    {
        return [
            'id'              => $this->getId(),
            'personType'      => $this->getPersonType()->value,
            'memberType'      => $this->getMemberType()?->value,
            'memberTypeLabel' => $this->getMemberType()?->label(),
            'displayName'     => $this->getDisplayName(),
            'email'           => $this->getEmail(),
            'phone'           => $this->getPhone(),
            'street'          => $this->getStreet(),
            'houseNumber'     => $this->getHouseNumber(),
            'addressAddition' => $this->getAddressAddition(),
            'zip'             => $this->getZip(),
            'city'            => $this->getCity(),
            'country'         => $this->getCountry(),
            'note'            => $this->getNote(),
        ];
    }

    /**
     * Reads a value from a payload and casts it to a string.
     *
     * @param array<string, mixed> $data Payload.
     * @param string               $key  Key to read.
     *
     * @return string|null The value as string, or null when absent or empty.
     */
    protected static function readString(array $data, string $key): ?string
    {
        if (!array_key_exists($key, $data) || $data[$key] === null) {
            return null;
        }
        $value = $data[$key];
        if (is_bool($value) || is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return is_string($value) ? $value : null;
    }

    /**
     * Reads a boolean value from a payload.
     *
     * @param array<string, mixed> $data Payload.
     * @param string               $key  Key to read.
     *
     * @return bool True when the value is truthy in the sense of the API.
     */
    protected static function readBool(array $data, string $key): bool
    {
        $value = $data[$key] ?? false;

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true;
    }
}
