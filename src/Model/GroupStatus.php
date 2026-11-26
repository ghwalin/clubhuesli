<?php

declare(strict_types=1);

namespace Clubhuesli\Model;

/**
 * Status of a group. Inactive groups accept no new members and no new links.
 */
enum GroupStatus: string
{
    case ACTIVE   = 'ACTIVE';
    case INACTIVE = 'INACTIVE';

    /**
     * Returns the German label shown in the user interface.
     *
     * @return string The label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE   => 'aktiv',
            self::INACTIVE => 'inaktiv',
        };
    }
}
