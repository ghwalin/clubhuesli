<?php

declare(strict_types=1);

namespace Clubhuesli\Tests;

use Clubhuesli\Model\Role;
use Clubhuesli\Model\User;
use Clubhuesli\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Tests the account of the login stub (roles admin and office).
 */
final class UserTest extends TestCase
{
    /**
     * The password is hashed and can be verified.
     */
    public function testPasswordIsHashedAndVerified(): void
    {
        $user = new User();
        $user->setEmail('Admin@Example.ORG');
        $user->setPassword('geheim1234');
        $user->setRole('admin');

        self::assertSame('admin@example.org', $user->getEmail());
        self::assertSame(Role::ADMIN, $user->getRole());
        self::assertNotSame('geheim1234', $user->getPasswordHash());
        self::assertTrue($user->verifyPassword('geheim1234'));
        self::assertFalse($user->verifyPassword('falsch1234'));
    }

    /**
     * A password shorter than eight characters is rejected.
     */
    public function testShortPasswordIsRejected(): void
    {
        $this->expectException(ValidationException::class);

        (new User())->setPassword('kurz');
    }

    /**
     * Only the two roles of sprint 1 are accepted.
     */
    public function testUnknownRoleIsRejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Bitte gültige Rolle wählen (admin oder office)');

        (new User())->setRole('coach');
    }

    /**
     * Both roles of sprint 1 may manage persons and see the AHV number.
     */
    public function testBothRolesManagePersons(): void
    {
        foreach (Role::cases() as $role) {
            self::assertTrue($role->mayManagePersons());
            self::assertTrue($role->maySeeAhvNumber());
        }
    }

    /**
     * The payload for the API never contains the password hash.
     */
    public function testPayloadHidesPasswordHash(): void
    {
        $user = new User();
        $user->setEmail('office@example.org');
        $user->setPassword('geheim1234');
        $user->setRole('office');

        self::assertArrayNotHasKey('passwordHash', $user->toArray());
        self::assertSame('Geschäftsstelle', $user->toArray()['roleLabel']);
    }
}
