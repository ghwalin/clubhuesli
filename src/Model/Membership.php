<?php

declare(strict_types=1);

namespace Clubhuesli\Model;

use Clubhuesli\Validation\ValidationException;
use Clubhuesli\Validation\Validator;
use DateTimeImmutable;

/**
 * The assignment of a person to a group for exactly one season.
 *
 * Membership of the parent groups is derived from the child groups and not
 * stored (see {@see GroupHierarchy}).
 */
class Membership
{
    /** Highest accepted shirt number. */
    public const MAX_SHIRT_NUMBER = 999;

    /** @var int|null Id, null as long as the membership is not stored. */
    private ?int $id = null;

    /** @var int|null Id of the group. */
    private ?int $groupId = null;

    /** @var int|null Id of the person. */
    private ?int $personId = null;

    /** @var int|null Id of the season. */
    private ?int $seasonId = null;

    /** @var MembershipRole Role; outside of teams always {@see MembershipRole::MEMBER}. */
    private MembershipRole $role = MembershipRole::MEMBER;

    /** @var string|null Free-text function, e.g. head coach. */
    private ?string $function = null;

    /** @var int|null Shirt number; players in teams only. */
    private ?int $shirtNumber = null;

    /** @var DateTimeImmutable|null Day of the exit during the season. */
    private ?DateTimeImmutable $exitDate = null;

    /**
     * Returns the id.
     *
     * @return int|null The id, or null when not stored yet.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Sets the id.
     *
     * @param int|null $id The id.
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * Returns the group id.
     *
     * @return int|null The id of the group.
     */
    public function getGroupId(): ?int
    {
        return $this->groupId;
    }

    /**
     * Sets the group id.
     *
     * @param int|null $groupId The id of the group.
     */
    public function setGroupId(?int $groupId): void
    {
        $this->groupId = $groupId;
    }

    /**
     * Returns the person id.
     *
     * @return int|null The id of the person.
     */
    public function getPersonId(): ?int
    {
        return $this->personId;
    }

    /**
     * Sets the person id.
     *
     * @param int|null $personId The id of the person.
     */
    public function setPersonId(?int $personId): void
    {
        $this->personId = $personId;
    }

    /**
     * Returns the season id.
     *
     * @return int|null The id of the season.
     */
    public function getSeasonId(): ?int
    {
        return $this->seasonId;
    }

    /**
     * Sets the season id.
     *
     * @param int|null $seasonId The id of the season.
     */
    public function setSeasonId(?int $seasonId): void
    {
        $this->seasonId = $seasonId;
    }

    /**
     * Returns the role.
     *
     * @return MembershipRole The role.
     */
    public function getRole(): MembershipRole
    {
        return $this->role;
    }

    /**
     * Sets the role.
     *
     * @param MembershipRole|string|null $role Role or its value; null means member.
     *
     * @throws ValidationException When the value is unknown.
     */
    public function setRole(MembershipRole|string|null $role): void
    {
        if ($role === null || (is_string($role) && trim($role) === '')) {
            $role = MembershipRole::MEMBER;
        }
        if (is_string($role)) {
            $parsed = MembershipRole::tryFrom(strtoupper(trim($role)));
            if ($parsed === null) {
                throw new ValidationException('role', 'Unbekannte Rolle');
            }
            $role = $parsed;
        }
        $this->role = $role;
    }

    /**
     * Returns the function.
     *
     * @return string|null The function, or null when none is set.
     */
    public function getFunction(): ?string
    {
        return $this->function;
    }

    /**
     * Sets the function.
     *
     * @param string|null $function Free text, at most 100 characters.
     *
     * @throws ValidationException When the text is too long.
     */
    public function setFunction(?string $function): void
    {
        $this->function = Validator::optionalText($function, 'function', 1, 100, 'Die Funktion darf höchstens 100 Zeichen lang sein');
    }

    /**
     * Returns the shirt number.
     *
     * @return int|null The shirt number, or null when none is set.
     */
    public function getShirtNumber(): ?int
    {
        return $this->shirtNumber;
    }

    /**
     * Sets the shirt number. Uniqueness within a team is deliberately not checked.
     *
     * @param int|string|null $shirtNumber Whole number from 0 to 999, or null.
     *
     * @throws ValidationException When the value is not a whole number in range.
     */
    public function setShirtNumber(int|string|null $shirtNumber): void
    {
        if (is_string($shirtNumber)) {
            $shirtNumber = trim($shirtNumber) === '' ? null : $shirtNumber;
        }
        if ($shirtNumber === null) {
            $this->shirtNumber = null;

            return;
        }
        if (is_string($shirtNumber) && preg_match('/^\d{1,3}$/', $shirtNumber) !== 1) {
            throw new ValidationException('shirtNumber', 'Die Shirtnummer muss eine ganze Zahl von 0 bis 999 sein');
        }
        $number = (int) $shirtNumber;
        if ($number < 0 || $number > self::MAX_SHIRT_NUMBER) {
            throw new ValidationException('shirtNumber', 'Die Shirtnummer muss eine ganze Zahl von 0 bis 999 sein');
        }
        $this->shirtNumber = $number;
    }

    /**
     * Returns the exit date.
     *
     * @return DateTimeImmutable|null The exit date, or null when the person has not left.
     */
    public function getExitDate(): ?DateTimeImmutable
    {
        return $this->exitDate;
    }

    /**
     * Sets the exit date.
     *
     * @param DateTimeImmutable|string|null $exitDate Date or a `YYYY-MM-DD` string; null removes it.
     *
     * @throws ValidationException When the date is invalid.
     */
    public function setExitDate(DateTimeImmutable|string|null $exitDate): void
    {
        if (is_string($exitDate)) {
            $normalised = Validator::optionalDate($exitDate, 'exitDate');
            $exitDate   = $normalised === null ? null : new DateTimeImmutable($normalised);
        }
        $this->exitDate = $exitDate?->setTime(0, 0);
    }

    /**
     * Tells whether the person is still a member on a day: the exit date is
     * empty or not passed yet.
     *
     * @param DateTimeImmutable|null $today The reference day; defaults to today.
     *
     * @return bool True when the person has not left before the day.
     */
    public function hasNotLeftOn(?DateTimeImmutable $today = null): bool
    {
        $day = ($today ?? new DateTimeImmutable('today'))->setTime(0, 0);

        return $this->getExitDate() === null || $this->getExitDate() >= $day;
    }

    /**
     * Tells whether the membership counts as active: its season is current or
     * in the future and the exit date is empty or not reached yet.
     *
     * @param Season                 $season The season of the membership.
     * @param DateTimeImmutable|null $today  The reference day; defaults to today.
     *
     * @return bool True when the membership is active.
     */
    public function isActive(Season $season, ?DateTimeImmutable $today = null): bool
    {
        return $season->isCurrentOrFuture($today) && $this->hasNotLeftOn($today);
    }

    /**
     * Tells whether the membership belongs to the current members of its group:
     * the season is the current one and the exit date is not reached.
     *
     * @param Season                 $season The season of the membership.
     * @param DateTimeImmutable|null $today  The reference day; defaults to today.
     *
     * @return bool True when the person is a current member.
     */
    public function isCurrent(Season $season, ?DateTimeImmutable $today = null): bool
    {
        return $season->isCurrent($today) && $this->hasNotLeftOn($today);
    }

    /**
     * Checks the membership against group, season and person.
     *
     * The role is normalised on the way: outside of teams it becomes
     * {@see MembershipRole::MEMBER}.
     *
     * @param Group          $group       The group.
     * @param Season         $season      The season.
     * @param AbstractPerson $person      The person.
     * @param bool           $checkPerson Whether to check that the person may join a team; only
     *                                    done when a membership is created, so that later
     *                                    corrections (e.g. an exit) stay possible.
     *
     * @return array<int, array{field: string, message: string}> The errors found; empty when valid.
     */
    public function validateAgainst(
        Group $group,
        Season $season,
        AbstractPerson $person,
        bool $checkPerson = true
    ): array {
        $errors = [];

        if ($group->isTeam()) {
            if ($checkPerson && !$person instanceof NaturalPerson) {
                return [['field' => 'personId', 'message' => 'Nur natürliche Personen können Mitglied eines Teams sein']];
            }
            if ($checkPerson && (!$person instanceof TeamMember || $person->getMemberType() !== MemberType::ACTIVE)) {
                return [['field' => 'personId', 'message' => 'Nur aktive Teammitglieder können einem Team zugeordnet werden']];
            }
            if ($this->getRole() === MembershipRole::MEMBER) {
                $errors[] = ['field' => 'role', 'message' => 'Bitte Rolle wählen (Spielerin oder Coach)'];
            }
        } else {
            $this->role = MembershipRole::MEMBER;
        }

        if ($this->getShirtNumber() !== null && $this->getRole() !== MembershipRole::PLAYER) {
            $errors[] = ['field' => 'shirtNumber', 'message' => 'Eine Shirtnummer gibt es nur bei Spielerinnen'];
        }

        $exitDate = $this->getExitDate();
        if ($exitDate !== null && !$season->contains($exitDate)) {
            $errors[] = ['field' => 'exitDate', 'message' => 'Das Austrittsdatum muss innerhalb der Saison liegen'];
        }

        return $errors;
    }

    /**
     * Fills the editable attributes from an API payload.
     *
     * @param array<string, mixed> $data Payload with `role`, `function`, `shirtNumber` and `exitDate`.
     *
     * @throws ValidationException When a value is invalid.
     */
    public function fillFromArray(array $data): void
    {
        $this->setRole(is_string($data['role'] ?? null) ? $data['role'] : null);
        $this->setFunction(is_string($data['function'] ?? null) ? $data['function'] : null);
        $shirt = $data['shirtNumber'] ?? null;
        $this->setShirtNumber(is_int($shirt) || is_string($shirt) ? $shirt : null);
        $this->setExitDate(is_string($data['exitDate'] ?? null) ? $data['exitDate'] : null);
    }

    /**
     * Returns the attributes for the JSON API.
     *
     * @return array<string, mixed> The attributes.
     */
    public function toArray(): array
    {
        return [
            'id'          => $this->getId(),
            'groupId'     => $this->getGroupId(),
            'personId'    => $this->getPersonId(),
            'seasonId'    => $this->getSeasonId(),
            'role'        => $this->getRole()->value,
            'roleLabel'   => $this->getRole()->label(),
            'function'    => $this->getFunction(),
            'shirtNumber' => $this->getShirtNumber(),
            'exitDate'    => $this->getExitDate()?->format('Y-m-d'),
        ];
    }
}
