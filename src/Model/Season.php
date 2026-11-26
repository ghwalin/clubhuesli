<?php

declare(strict_types=1);

namespace Clubhuesli\Model;

use Clubhuesli\Validation\ValidationException;
use Clubhuesli\Validation\Validator;
use DateTimeImmutable;

/**
 * A season with a name and a start and end date. Group memberships always
 * refer to exactly one season.
 */
class Season
{
    /** @var int|null Id, null as long as the season is not stored. */
    private ?int $id = null;

    /** @var string|null Name, e.g. 2026/27. */
    private ?string $name = null;

    /** @var DateTimeImmutable|null First day of the season. */
    private ?DateTimeImmutable $startDate = null;

    /** @var DateTimeImmutable|null Last day of the season. */
    private ?DateTimeImmutable $endDate = null;

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
     * Returns the name.
     *
     * @return string|null The name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Sets the name.
     *
     * @param string|null $name The name, 3 to 20 characters.
     *
     * @throws ValidationException When the name is missing, too short or too long.
     */
    public function setName(?string $name): void
    {
        $this->name = Validator::requireText($name, 'name', 3, 20, 'Bitte Bezeichnung erfassen (z. B. 2026/27)');
    }

    /**
     * Returns the first day.
     *
     * @return DateTimeImmutable|null The start date.
     */
    public function getStartDate(): ?DateTimeImmutable
    {
        return $this->startDate;
    }

    /**
     * Sets the first day.
     *
     * @param DateTimeImmutable|string|null $startDate Date or a `YYYY-MM-DD` string.
     *
     * @throws ValidationException When the date is missing or invalid.
     */
    public function setStartDate(DateTimeImmutable|string|null $startDate): void
    {
        $this->startDate = self::requireDate($startDate, 'startDate', 'Bitte Startdatum erfassen');
    }

    /**
     * Returns the last day.
     *
     * @return DateTimeImmutable|null The end date.
     */
    public function getEndDate(): ?DateTimeImmutable
    {
        return $this->endDate;
    }

    /**
     * Sets the last day.
     *
     * @param DateTimeImmutable|string|null $endDate Date or a `YYYY-MM-DD` string.
     *
     * @throws ValidationException When the date is missing or invalid.
     */
    public function setEndDate(DateTimeImmutable|string|null $endDate): void
    {
        $this->endDate = self::requireDate($endDate, 'endDate', 'Bitte Enddatum erfassen');
    }

    /**
     * Tells whether a day lies within the season (both limits included).
     *
     * @param DateTimeImmutable $date The day to check.
     *
     * @return bool True when the day belongs to the season.
     */
    public function contains(DateTimeImmutable $date): bool
    {
        $day = $date->setTime(0, 0);

        return $this->getStartDate() !== null && $this->getEndDate() !== null
            && $day >= $this->getStartDate() && $day <= $this->getEndDate();
    }

    /**
     * Tells whether this is the current season on the given day.
     *
     * @param DateTimeImmutable|null $today The reference day; defaults to today.
     *
     * @return bool True when the day lies within the season.
     */
    public function isCurrent(?DateTimeImmutable $today = null): bool
    {
        return $this->contains($today ?? new DateTimeImmutable('today'));
    }

    /**
     * Tells whether the season has not ended yet (it is current or in the future).
     *
     * @param DateTimeImmutable|null $today The reference day; defaults to today.
     *
     * @return bool True when the end date has not passed.
     */
    public function isCurrentOrFuture(?DateTimeImmutable $today = null): bool
    {
        $day = ($today ?? new DateTimeImmutable('today'))->setTime(0, 0);

        return $this->getEndDate() !== null && $this->getEndDate() >= $day;
    }

    /**
     * Tells whether the date range overlaps the one of another season.
     *
     * @param Season $other The other season.
     *
     * @return bool True when at least one day belongs to both seasons.
     */
    public function overlaps(Season $other): bool
    {
        return $this->getStartDate() <= $other->getEndDate() && $other->getStartDate() <= $this->getEndDate();
    }

    /**
     * Checks the rules that span several attributes.
     *
     * @return array<int, array{field: string, message: string}> The errors found; empty when valid.
     */
    public function validate(): array
    {
        $errors = [];
        if ($this->getName() === null) {
            $errors[] = ['field' => 'name', 'message' => 'Bitte Bezeichnung erfassen (z. B. 2026/27)'];
        }
        if ($this->getStartDate() === null) {
            $errors[] = ['field' => 'startDate', 'message' => 'Bitte Startdatum erfassen'];
        }
        if ($this->getEndDate() === null) {
            $errors[] = ['field' => 'endDate', 'message' => 'Bitte Enddatum erfassen'];
        }
        if ($this->getStartDate() !== null && $this->getEndDate() !== null
            && $this->getEndDate() < $this->getStartDate()) {
            $errors[] = ['field' => 'endDate', 'message' => 'Enddatum darf nicht vor dem Startdatum liegen'];
        }

        return $errors;
    }

    /**
     * Throws when a rule is violated.
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
     * Builds a season from an API payload.
     *
     * @param array<string, mixed> $data Payload with `name`, `startDate` and `endDate`.
     *
     * @return static The season.
     *
     * @throws ValidationException When a value is invalid.
     */
    public static function fromArray(array $data): static
    {
        $season = new static();
        $season->setName(is_string($data['name'] ?? null) ? $data['name'] : null);
        $season->setStartDate(is_string($data['startDate'] ?? null) ? $data['startDate'] : null);
        $season->setEndDate(is_string($data['endDate'] ?? null) ? $data['endDate'] : null);

        return $season;
    }

    /**
     * Returns the attributes for the JSON API.
     *
     * @return array<string, mixed> The attributes.
     */
    public function toArray(): array
    {
        return [
            'id'        => $this->getId(),
            'name'      => $this->getName(),
            'startDate' => $this->getStartDate()?->format('Y-m-d'),
            'endDate'   => $this->getEndDate()?->format('Y-m-d'),
            'isCurrent' => $this->isCurrent(),
        ];
    }

    /**
     * Converts and checks a date.
     *
     * @param DateTimeImmutable|string|null $value   Date or a `YYYY-MM-DD` string.
     * @param string                        $field   Name of the field for error messages.
     * @param string                        $message Message when the date is missing.
     *
     * @return DateTimeImmutable The date at midnight.
     *
     * @throws ValidationException When the date is missing or invalid.
     */
    private static function requireDate(DateTimeImmutable|string|null $value, string $field, string $message): DateTimeImmutable
    {
        if (is_string($value)) {
            $value = Validator::optionalDate($value, $field);
        }
        if ($value === null) {
            throw new ValidationException($field, $message);
        }

        return is_string($value) ? new DateTimeImmutable($value) : $value->setTime(0, 0);
    }
}
