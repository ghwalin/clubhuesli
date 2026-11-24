<?php

declare(strict_types=1);

namespace Clubhuesli\Dao;

use Clubhuesli\Model\User;
use PDO;

/**
 * Database access for the accounts of the login stub.
 */
class UserDao
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
     * Returns the connection and opens the shared one on first use, so that
     * requests which need no database (e.g. an unauthenticated one) do not
     * trigger a connection.
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
     * Returns the account with the given login e-mail address.
     *
     * @param string $email The login e-mail address.
     *
     * @return User|null The account, or null when unknown.
     */
    public function findByEmail(string $email): ?User
    {
        $statement = $this->connection()->prepare(
            'SELECT id, person_id, email, password_hash, role, active FROM app_user WHERE email = :email'
        );
        $statement->execute(['email' => mb_strtolower(trim($email))]);
        $row = $statement->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    /**
     * Returns the account with the given id.
     *
     * @param int $id Id of the account.
     *
     * @return User|null The account, or null when unknown.
     */
    public function find(int $id): ?User
    {
        $statement = $this->connection()->prepare(
            'SELECT id, person_id, email, password_hash, role, active FROM app_user WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    /**
     * Inserts or updates an account.
     *
     * @param User $user The account to store.
     *
     * @return int The id of the stored account.
     */
    public function save(User $user): int
    {
        $id = $user->getId();
        if ($id === null) {
            $statement = $this->connection()->prepare(
                'INSERT INTO app_user (person_id, email, password_hash, role, active)'
                . ' VALUES (:personId, :email, :passwordHash, :role, :active)'
            );
            $statement->execute([
                'personId'     => $user->getPersonId(),
                'email'        => $user->getEmail(),
                'passwordHash' => $user->getPasswordHash(),
                'role'         => $user->getRole()?->value,
                'active'       => $user->isActive() ? 1 : 0,
            ]);
            $id = (int) $this->connection()->lastInsertId();
            $user->setId($id);

            return $id;
        }

        $statement = $this->connection()->prepare(
            'UPDATE app_user SET person_id = :personId, email = :email, password_hash = :passwordHash,'
            . ' role = :role, active = :active WHERE id = :id'
        );
        $statement->execute([
            'personId'     => $user->getPersonId(),
            'email'        => $user->getEmail(),
            'passwordHash' => $user->getPasswordHash(),
            'role'         => $user->getRole()?->value,
            'active'       => $user->isActive() ? 1 : 0,
            'id'           => $id,
        ]);

        return $id;
    }

    /**
     * Turns a database row into an account.
     *
     * @param array<string, mixed> $row Row of `app_user`.
     *
     * @return User The hydrated account.
     */
    protected function hydrate(array $row): User
    {
        $user = new User();
        $user->setId((int) $row['id']);
        $user->setPersonId($row['person_id'] === null ? null : (int) $row['person_id']);
        $user->setEmail((string) $row['email']);
        $user->setPasswordHash((string) $row['password_hash']);
        $user->setRole((string) $row['role']);
        $user->setActive((int) $row['active'] === 1);

        return $user;
    }
}
