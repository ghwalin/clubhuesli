<?php

declare(strict_types=1);

namespace Clubhuesli\Model;

/**
 * Kind of a group. Teams have special rules for their members.
 */
enum GroupType: string
{
    case TEAM       = 'TEAM';
    case FUNCTIONARY = 'FUNCTIONARY';
    case OTHER      = 'OTHER';

    /**
     * Returns the German label shown in the user interface.
     *
     * @return string The label.
     */
    public function label(): string
    {
        return match ($this) {
            self::TEAM        => 'Team',
            self::FUNCTIONARY => 'Funktionäre',
            self::OTHER       => 'Andere',
        };
    }
}
