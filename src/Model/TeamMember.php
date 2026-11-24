<?php

declare(strict_types=1);

namespace Clubhuesli\Model;

use Clubhuesli\Validation\ValidationException;
use Clubhuesli\Validation\Validator;
use DateTimeImmutable;

/**
 * A team member: player or coach. Whether somebody acts as a player or as a
 * coach is decided per group membership, so a person who does both still has a
 * single record.
 *
 * Compared with a plain natural person a team member has a mandatory birth
 * date, a social security (AHV) and a J+S number, and – while a minor – a photo
 * consent and links to the persons holding custody.
 */
class TeamMember extends NaturalPerson
{
    /** @var string|null Swiss social security number, format `756.XXXX.XXXX.XX`. */
    private ?string $ahvNumber = null;

    /** @var string|null J+S number. */
    private ?string $jsNumber = null;

    /** @var bool Consent to publish photos; only for minors, default no. */
    private bool $photoConsent = false;

    /** @var array<int, NaturalPerson> Persons holding custody, keyed by their id. */
    private array $guardians = [];

    /**
     * A minor team member linked to at least one guardian needs no e-mail
     * address of its own: group e-mails go to the guardians.
     *
     * {@inheritDoc}
     */
    public function isEmailRequired(): bool
    {
        return !($this->isMinor() && $this->getGuardians() !== []);
    }

    /**
     * Sets the birth date, which is mandatory for team members.
     *
     * @param DateTimeImmutable|string|null $birthDate Birth date or a `YYYY-MM-DD` string.
     *
     * @throws ValidationException When the date is missing, invalid or in the future.
     */
    public function setBirthDate(DateTimeImmutable|string|null $birthDate): void
    {
        if ($birthDate === null || $birthDate === '') {
            throw new ValidationException('birthDate', 'Geburtsdatum ist für Teammitglieder erforderlich');
        }
        parent::setBirthDate($birthDate);
    }

    /**
     * Returns the social security number.
     *
     * @return string|null The AHV number, or null when none is known.
     */
    public function getAhvNumber(): ?string
    {
        return $this->ahvNumber;
    }

    /**
     * Sets the social security number.
     *
     * @param string|null $ahvNumber The AHV number in the format `756.XXXX.XXXX.XX`.
     *
     * @throws ValidationException When format or check digit is wrong.
     */
    public function setAhvNumber(?string $ahvNumber): void
    {
        $this->ahvNumber = Validator::optionalAhvNumber($ahvNumber);
    }

    /**
     * Returns the J+S number.
     *
     * @return string|null The J+S number, or null when none is known.
     */
    public function getJsNumber(): ?string
    {
        return $this->jsNumber;
    }

    /**
     * Sets the J+S number.
     *
     * @param string|null $jsNumber The J+S number.
     *
     * @throws ValidationException When the number is longer than 30 characters.
     */
    public function setJsNumber(?string $jsNumber): void
    {
        $this->jsNumber = Validator::optionalText($jsNumber, 'jsNumber', 1, 30, 'J+S-Nummer: maximal 30 Zeichen');
    }

    /**
     * Tells whether photos of this team member may be published.
     *
     * @return bool True when consent was given.
     */
    public function hasPhotoConsent(): bool
    {
        return $this->photoConsent;
    }

    /**
     * Sets the photo consent. It may only be given for minors, so the birth
     * date has to be set first.
     *
     * @param bool $photoConsent True when consent was given.
     *
     * @throws ValidationException When consent is given for a team member of full age.
     */
    public function setPhotoConsent(bool $photoConsent): void
    {
        if ($photoConsent && !$this->isMinor()) {
            throw new ValidationException(
                'photoConsent',
                'Die Einwilligung Foto wird nur bei minderjährigen Teammitgliedern erfasst'
            );
        }
        $this->photoConsent = $photoConsent;
    }

    /**
     * Returns the persons holding custody.
     *
     * @return array<int, NaturalPerson> The guardians, keyed by their id.
     */
    public function getGuardians(): array
    {
        return $this->guardians;
    }

    /**
     * Replaces the persons holding custody.
     *
     * @param array<int, NaturalPerson> $guardians The guardians.
     *
     * @throws ValidationException When a guardian is not allowed.
     */
    public function setGuardians(array $guardians): void
    {
        $this->guardians = [];
        foreach ($guardians as $guardian) {
            $this->addGuardian($guardian);
        }
    }

    /**
     * Links a person holding custody. Guardians are only recorded for minor
     * team members and never point to the team member itself.
     *
     * @param NaturalPerson $guardian The guardian.
     *
     * @throws ValidationException When the team member is of full age, the guardian has no id or is the member itself.
     */
    public function addGuardian(NaturalPerson $guardian): void
    {
        if (!$this->isMinor()) {
            throw new ValidationException(
                'guardians',
                'Erziehungsberechtigte können nur bei minderjährigen Teammitgliedern erfasst werden'
            );
        }
        $guardianId = $guardian->getId();
        if ($guardianId === null) {
            throw new ValidationException('guardians', 'Erziehungsberechtigte müssen bereits erfasst sein');
        }
        if ($guardianId === $this->getId()) {
            throw new ValidationException('guardians', 'Eine Person kann nicht ihr eigener Erziehungsberechtigter sein');
        }
        $this->guardians[$guardianId] = $guardian;
    }

    /**
     * Removes the link to a person holding custody.
     *
     * @param int $guardianId Id of the guardian.
     */
    public function removeGuardian(int $guardianId): void
    {
        unset($this->guardians[$guardianId]);
    }

    /**
     * {@inheritDoc}
     */
    public function fillFromArray(array $data): void
    {
        // The birth date is set by the parent first: photo consent and
        // guardians depend on whether the team member is a minor.
        parent::fillFromArray($data);
        $this->setAhvNumber(self::readString($data, 'ahvNumber'));
        $this->setJsNumber(self::readString($data, 'jsNumber'));
        $this->setPhotoConsent(self::readBool($data, 'photoConsent'));
    }

    /**
     * {@inheritDoc}
     */
    public function validate(): array
    {
        $errors = parent::validate();

        if ($this->getBirthDate() === null) {
            $errors[] = ['field' => 'birthDate', 'message' => 'Geburtsdatum ist für Teammitglieder erforderlich'];
        }
        if (!$this->isMinor() && $this->getGuardians() !== []) {
            $errors[] = [
                'field'   => 'guardians',
                'message' => 'Erziehungsberechtigte können nur bei minderjährigen Teammitgliedern erfasst werden',
            ];
        }
        if (!$this->isMinor() && $this->hasPhotoConsent()) {
            $errors[] = [
                'field'   => 'photoConsent',
                'message' => 'Die Einwilligung Foto wird nur bei minderjährigen Teammitgliedern erfasst',
            ];
        }

        return $errors;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $guardians = [];
        foreach ($this->getGuardians() as $guardian) {
            $guardians[] = [
                'id'          => $guardian->getId(),
                'displayName' => $guardian->getDisplayName(),
                'email'       => $guardian->getEmail(),
            ];
        }

        return array_merge(parent::toArray(), [
            'teamMember'   => true,
            'ahvNumber'    => $this->getAhvNumber(),
            'jsNumber'     => $this->getJsNumber(),
            'photoConsent' => $this->hasPhotoConsent(),
            'guardians'    => $guardians,
        ]);
    }
}
