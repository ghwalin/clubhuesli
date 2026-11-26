<?php

declare(strict_types=1);

namespace Clubhuesli\Model;

use Clubhuesli\Validation\ValidationException;
use Clubhuesli\Validation\Validator;

/**
 * A group of persons: a team, a group of officials or any other group.
 *
 * The hierarchy (parent and child groups) is stored separately and handled by
 * {@see GroupHierarchy}.
 */
class Group
{
    /** @var int|null Id, null as long as the group is not stored. */
    private ?int $id = null;

    /** @var string|null Unique name. */
    private ?string $name = null;

    /** @var GroupType|null Kind of group. */
    private ?GroupType $groupType = null;

    /** @var GroupStatus Status; new groups are active. */
    private GroupStatus $status = GroupStatus::ACTIVE;

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
     * @param string|null $name The name, 2 to 100 characters.
     *
     * @throws ValidationException When the name is missing, too short or too long.
     */
    public function setName(?string $name): void
    {
        $this->name = Validator::requireText($name, 'name', 2, 100, 'Bitte Bezeichnung erfassen (2 – 100 Zeichen)');
    }

    /**
     * Returns the group type.
     *
     * @return GroupType|null The group type.
     */
    public function getGroupType(): ?GroupType
    {
        return $this->groupType;
    }

    /**
     * Sets the group type.
     *
     * @param GroupType|string|null $groupType Group type or its value.
     *
     * @throws ValidationException When the value is missing or unknown.
     */
    public function setGroupType(GroupType|string|null $groupType): void
    {
        if (is_string($groupType)) {
            $groupType = GroupType::tryFrom(strtoupper(trim($groupType)));
        }
        if ($groupType === null) {
            throw new ValidationException('groupType', 'Bitte Gruppenart wählen');
        }
        $this->groupType = $groupType;
    }

    /**
     * Returns the status.
     *
     * @return GroupStatus The status.
     */
    public function getStatus(): GroupStatus
    {
        return $this->status;
    }

    /**
     * Sets the status.
     *
     * @param GroupStatus|string|null $status Status or its value; null means active.
     *
     * @throws ValidationException When the value is unknown.
     */
    public function setStatus(GroupStatus|string|null $status): void
    {
        if ($status === null) {
            $status = GroupStatus::ACTIVE;
        }
        if (is_string($status)) {
            $parsed = GroupStatus::tryFrom(strtoupper(trim($status)));
            if ($parsed === null) {
                throw new ValidationException('status', 'Unbekannter Status');
            }
            $status = $parsed;
        }
        $this->status = $status;
    }

    /**
     * Tells whether the group is active.
     *
     * @return bool True when the status is active.
     */
    public function isActive(): bool
    {
        return $this->getStatus() === GroupStatus::ACTIVE;
    }

    /**
     * Tells whether the group is a team.
     *
     * @return bool True when the group type is team.
     */
    public function isTeam(): bool
    {
        return $this->getGroupType() === GroupType::TEAM;
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
            $errors[] = ['field' => 'name', 'message' => 'Bitte Bezeichnung erfassen (2 – 100 Zeichen)'];
        }
        if ($this->getGroupType() === null) {
            $errors[] = ['field' => 'groupType', 'message' => 'Bitte Gruppenart wählen'];
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
     * Fills the group from an API payload.
     *
     * @param array<string, mixed> $data Payload with `name`, `groupType` and optionally `status`.
     *
     * @return static The group.
     *
     * @throws ValidationException When a value is invalid.
     */
    public static function fromArray(array $data): static
    {
        $group = new static();
        $group->setName(is_string($data['name'] ?? null) ? $data['name'] : null);
        $group->setGroupType(is_string($data['groupType'] ?? null) ? $data['groupType'] : null);
        $group->setStatus(is_string($data['status'] ?? null) ? $data['status'] : null);

        return $group;
    }

    /**
     * Returns the attributes for the JSON API.
     *
     * @return array<string, mixed> The attributes.
     */
    public function toArray(): array
    {
        return [
            'id'             => $this->getId(),
            'name'           => $this->getName(),
            'groupType'      => $this->getGroupType()?->value,
            'groupTypeLabel' => $this->getGroupType()?->label(),
            'status'         => $this->getStatus()->value,
            'statusLabel'    => $this->getStatus()->label(),
        ];
    }
}
