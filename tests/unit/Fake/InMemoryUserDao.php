<?php

declare(strict_types=1);

namespace Clubhuesli\Tests\Fake;

use Clubhuesli\Dao\UserDao;
use Clubhuesli\Model\Role;
use Clubhuesli\Model\User;

/**
 * Account DAO keeping a single account in memory, used by the API tests.
 */
final class InMemoryUserDao extends UserDao
{
    /** @var User The only account of this DAO. */
    private User $user;

    /**
     * @param string $email    Login e-mail address.
     * @param string $password Clear-text password.
     * @param Role   $role     Role of the account.
     */
    public function __construct(
        string $email = 'admin@example.org',
        string $password = 'geheim1234',
        Role $role = Role::ADMIN,
    ) {
        parent::__construct();

        $this->user = new User();
        $this->user->setId(1);
        $this->user->setEmail($email);
        $this->user->setPassword($password);
        $this->user->setRole($role);
    }

    /**
     * Returns the account of this DAO.
     *
     * @return User The account.
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * {@inheritDoc}
     */
    public function findByEmail(string $email): ?User
    {
        return mb_strtolower(trim($email)) === $this->user->getEmail() ? $this->user : null;
    }

    /**
     * {@inheritDoc}
     */
    public function find(int $id): ?User
    {
        return $id === $this->user->getId() ? $this->user : null;
    }

    /**
     * {@inheritDoc}
     */
    public function save(User $user): int
    {
        $this->user = $user;

        return (int) $user->getId();
    }
}
