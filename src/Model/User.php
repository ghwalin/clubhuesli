<?php

declare(strict_types=1);

namespace Clubhuesli\Model;

use Clubhuesli\Validation\ValidationException;
use Clubhuesli\Validation\Validator;

/**
 * An account of the login stub: login e-mail, password hash and one role.
 *
 * The login e-mail is unique – unlike the contact e-mail of a person. Full
 * authentication (OAuth 2.0 / passkey with JWT) replaces this class later.
 */
final class User
{
    /** Minimum length of a password. */
    public const MIN_PASSWORD_LENGTH = 8;

    /** @var int|null Primary key; null until the account is stored. */
    private ?int $id = null;

    /** @var int|null Id of the person behind the account. */
    private ?int $personId = null;

    /** @var string|null Unique login e-mail address. */
    private ?string $email = null;

    /** @var string|null Hash of the password. */
    private ?string $passwordHash = null;

    /** @var Role|null Role of the account. */
    private ?Role $role = null;

    /** @var bool Whether the account may log in. */
    private bool $active = true;

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
            throw new ValidationException('id', 'Ungültige Benutzer-Id');
        }
        $this->id = $id;
    }

    /**
     * Returns the id of the person behind the account.
     *
     * @return int|null The person id, or null when the account is not linked.
     */
    public function getPersonId(): ?int
    {
        return $this->personId;
    }

    /**
     * Sets the id of the person behind the account.
     *
     * @param int|null $personId The person id.
     *
     * @throws ValidationException When the id is not positive.
     */
    public function setPersonId(?int $personId): void
    {
        if ($personId !== null && $personId <= 0) {
            throw new ValidationException('personId', 'Ungültige Personen-Id');
        }
        $this->personId = $personId;
    }

    /**
     * Returns the login e-mail address.
     *
     * @return string|null The address, or null when not set yet.
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Sets the login e-mail address.
     *
     * @param string|null $email The address.
     *
     * @throws ValidationException When the address is missing or invalid.
     */
    public function setEmail(?string $email): void
    {
        $email = Validator::optionalEmail($email);
        if ($email === null) {
            throw new ValidationException('email', 'Bitte E-Mail-Adresse eingeben');
        }
        $this->email = mb_strtolower($email);
    }

    /**
     * Returns the password hash.
     *
     * @return string|null The hash, or null when not set yet.
     */
    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    /**
     * Sets the password hash as read from the database.
     *
     * @param string|null $passwordHash The hash.
     *
     * @throws ValidationException When the hash is empty.
     */
    public function setPasswordHash(?string $passwordHash): void
    {
        $passwordHash = Validator::trimToNull($passwordHash);
        if ($passwordHash === null) {
            throw new ValidationException('passwordHash', 'Passwort fehlt');
        }
        $this->passwordHash = $passwordHash;
    }

    /**
     * Hashes and stores a clear-text password.
     *
     * @param string $password The clear-text password.
     *
     * @throws ValidationException When the password is too short.
     */
    public function setPassword(string $password): void
    {
        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new ValidationException(
                'password',
                sprintf('Passwort: mindestens %d Zeichen', self::MIN_PASSWORD_LENGTH)
            );
        }
        $this->setPasswordHash(password_hash($password, PASSWORD_DEFAULT));
    }

    /**
     * Checks a clear-text password against the stored hash.
     *
     * @param string $password The clear-text password.
     *
     * @return bool True when the password matches.
     */
    public function verifyPassword(string $password): bool
    {
        $hash = $this->getPasswordHash();

        return $hash !== null && password_verify($password, $hash);
    }

    /**
     * Returns the role of the account.
     *
     * @return Role|null The role, or null when not set yet.
     */
    public function getRole(): ?Role
    {
        return $this->role;
    }

    /**
     * Sets the role of the account.
     *
     * @param Role|string|null $role The role or its name.
     *
     * @throws ValidationException When the role is unknown.
     */
    public function setRole(Role|string|null $role): void
    {
        if (is_string($role)) {
            $role = Role::tryFrom(strtolower(trim($role)));
        }
        if ($role === null) {
            throw new ValidationException('role', 'Bitte gültige Rolle wählen (admin oder office)');
        }
        $this->role = $role;
    }

    /**
     * Tells whether the account may log in.
     *
     * @return bool True when the account is active.
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Activates or deactivates the account.
     *
     * @param bool $active True to allow logging in.
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * Returns the account for the JSON API; the password hash is never exposed.
     *
     * @return array<string, mixed> The attributes.
     */
    public function toArray(): array
    {
        return [
            'id'        => $this->getId(),
            'personId'  => $this->getPersonId(),
            'email'     => $this->getEmail(),
            'role'      => $this->getRole()?->value,
            'roleLabel' => $this->getRole()?->label(),
            'active'    => $this->isActive(),
        ];
    }
}
