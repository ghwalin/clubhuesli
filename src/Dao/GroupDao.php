<?php

declare(strict_types=1);

namespace Clubhuesli\Dao;

use Clubhuesli\Model\Group;
use PDO;

/**
 * Database access for groups and their hierarchy. Groups cannot be deleted.
 */
class GroupDao
{
    /** @var PDO|null The connection used by this DAO; resolved on first use. */
    private ?PDO $connection;

    /**
     * @param PDO|null $connection Connection to use; defaults to the shared one.
     */
    public function __construct(?PDO $connection = null)
    {
        $this->connection = $connection;
    }

    /**
     * Returns the connection and opens the shared one on first use.
     *
     * @return PDO The connection.
     */
    private function connection(): PDO
    {
        if ($this->connection === null) {
            $this->connection = Database::connection();
        }

        return $this->connection;
    }

    /**
     * Returns all groups sorted by name.
     *
     * @return array<int, Group> The groups.
     */
    public function findAll(): array
    {
        $rows = $this->connection()->query('SELECT * FROM club_group ORDER BY name')->fetchAll();

        return array_map(fn (array $row): Group => $this->hydrate($row), $rows);
    }

    /**
     * Finds a group by id.
     *
     * @param int $id Id of the group.
     *
     * @return Group|null The group, or null when it does not exist.
     */
    public function find(int $id): ?Group
    {
        $statement = $this->connection()->prepare('SELECT * FROM club_group WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    /**
     * Finds a group by its unique name.
     *
     * @param string $name Name of the group.
     *
     * @return Group|null The group, or null when it does not exist.
     */
    public function findByName(string $name): ?Group
    {
        $statement = $this->connection()->prepare('SELECT * FROM club_group WHERE name = :name');
        $statement->execute(['name' => $name]);
        $row = $statement->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    /**
     * Inserts or updates a group.
     *
     * @param Group $group The group; receives its id when inserted.
     *
     * @return int The id of the group.
     */
    public function save(Group $group): int
    {
        $values = [
            'name'   => $group->getName(),
            'type'   => $group->getGroupType()?->value,
            'status' => $group->getStatus()->value,
        ];
        if ($group->getId() === null) {
            $this->connection()
                ->prepare('INSERT INTO club_group (name, group_type, status) VALUES (:name, :type, :status)')
                ->execute($values);
            $group->setId((int) $this->connection()->lastInsertId());
        } else {
            $this->connection()
                ->prepare('UPDATE club_group SET name = :name, group_type = :type, status = :status WHERE id = :id')
                ->execute($values + ['id' => $group->getId()]);
        }

        return (int) $group->getId();
    }

    /**
     * Returns all parent/child links.
     *
     * @return array<int, array{0: int, 1: int}> Pairs of parent id and child id.
     */
    public function findLinks(): array
    {
        $rows = $this->connection()->query('SELECT parent_id, child_id FROM group_hierarchy')->fetchAll();

        return array_map(
            static fn (array $row): array => [(int) $row['parent_id'], (int) $row['child_id']],
            $rows
        );
    }

    /**
     * Links a child group to a parent group.
     *
     * @param int $parentId Id of the parent group.
     * @param int $childId  Id of the child group.
     */
    public function addLink(int $parentId, int $childId): void
    {
        $this->connection()
            ->prepare('INSERT INTO group_hierarchy (parent_id, child_id) VALUES (:parent, :child)')
            ->execute(['parent' => $parentId, 'child' => $childId]);
    }

    /**
     * Removes the link between a parent and a child group.
     *
     * @param int $parentId Id of the parent group.
     * @param int $childId  Id of the child group.
     *
     * @return bool True when a link was removed.
     */
    public function removeLink(int $parentId, int $childId): bool
    {
        $statement = $this->connection()
            ->prepare('DELETE FROM group_hierarchy WHERE parent_id = :parent AND child_id = :child');
        $statement->execute(['parent' => $parentId, 'child' => $childId]);

        return $statement->rowCount() > 0;
    }

    /**
     * Builds a group from a database row.
     *
     * @param array<string, mixed> $row The row.
     *
     * @return Group The group.
     */
    protected function hydrate(array $row): Group
    {
        $group = new Group();
        $group->setId((int) $row['id']);
        $group->setName((string) $row['name']);
        $group->setGroupType((string) $row['group_type']);
        $group->setStatus((string) $row['status']);

        return $group;
    }
}
