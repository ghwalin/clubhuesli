<?php

declare(strict_types=1);

namespace Clubhuesli\Model;

/**
 * Role of a member in a group. Players and coaches exist in teams only; in all
 * other groups the role is {@see self::MEMBER}.
 */
enum MembershipRole: string
{
    case PLAYER = 'PLAYER';
    case COACH  = 'COACH';
    case MEMBER = 'MEMBER';

    /**
     * Returns the German label shown in the user interface.
     *
     * @return string The label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PLAYER => 'Spielerin',
            self::COACH  => 'Coach',
            self::MEMBER => 'Mitglied',
        };
    }
}
