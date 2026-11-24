<?php

declare(strict_types=1);

namespace Clubhuesli\Model;

/**
 * User roles available in the login stub of sprint 1.
 *
 * The remaining roles of the permission concept (coach, communication, member)
 * arrive with the stories that need them.
 */
enum Role: string
{
    case ADMIN  = 'admin';
    case OFFICE = 'office';

    /**
     * Returns the German label shown in the user interface.
     *
     * @return string The label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN  => 'Admin',
            self::OFFICE => 'Geschäftsstelle',
        };
    }

    /**
     * Tells whether this role may read and write person master data.
     *
     * @return bool True when the role manages persons.
     */
    public function mayManagePersons(): bool
    {
        return true;
    }

    /**
     * Tells whether this role may see the social security number, which is
     * particularly sensitive and never shown to coaches.
     *
     * @return bool True when the role may see the AHV number.
     */
    public function maySeeAhvNumber(): bool
    {
        return $this === self::ADMIN || $this === self::OFFICE;
    }
}
