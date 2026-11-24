<?php

declare(strict_types=1);

namespace Clubhuesli\Http;

use Clubhuesli\Dao\UserDao;
use Clubhuesli\Model\Role;
use Clubhuesli\Model\User;

/**
 * Session handling of the login stub.
 *
 * Sprint 1 keeps the logged-in account in a PHP session cookie (SameSite=Lax).
 * The full authentication described in `Clubadmin.md` – OAuth 2.0 with the
 * Microsoft Identity Platform, passkeys and JWT with refresh tokens – replaces
 * this class in a later sprint.
 */
final class Session
{
    /** Key under which the account id is kept in the session. */
    private const USER_KEY = 'user_id';

    /** @var User|null Account of the current request, loaded on demand. */
    private ?User $currentUser = null;

    /**
     * @param UserDao $userDao Data access for accounts.
     */
    public function __construct(private readonly UserDao $userDao)
    {
    }

    /**
     * Starts the session if it is not running yet.
     */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        if (PHP_SAPI === 'cli') {
            return;
        }
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => ($_SERVER['HTTPS'] ?? '') !== '',
        ]);
        session_start();
    }

    /**
     * Remembers the account as logged in.
     *
     * @param User $user The account.
     */
    public function login(User $user): void
    {
        $this->start();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION[self::USER_KEY] = $user->getId();
        }
        $this->currentUser = $user;
    }

    /**
     * Ends the session.
     */
    public function logout(): void
    {
        $this->start();
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }
        $this->currentUser = null;
    }

    /**
     * Returns the logged-in account.
     *
     * @return User|null The account, or null when nobody is logged in.
     */
    public function getCurrentUser(): ?User
    {
        if ($this->currentUser !== null) {
            return $this->currentUser;
        }
        $this->start();
        $id = $_SESSION[self::USER_KEY] ?? null;
        if (!is_int($id) && !is_string($id)) {
            return null;
        }
        $user = $this->userDao->find((int) $id);
        if ($user === null || !$user->isActive()) {
            return null;
        }
        $this->currentUser = $user;

        return $user;
    }

    /**
     * Returns the logged-in account or fails.
     *
     * @return User The account.
     *
     * @throws HttpException When nobody is logged in.
     */
    public function requireUser(): User
    {
        $user = $this->getCurrentUser();
        if ($user === null) {
            throw HttpException::unauthorized();
        }

        return $user;
    }

    /**
     * Returns the logged-in account and checks its role.
     *
     * @param array<int, Role> $roles Roles that are allowed.
     *
     * @return User The account.
     *
     * @throws HttpException When nobody is logged in or the role does not match.
     */
    public function requireRole(array $roles): User
    {
        $user = $this->requireUser();
        if (!in_array($user->getRole(), $roles, true)) {
            throw HttpException::forbidden();
        }

        return $user;
    }
}
