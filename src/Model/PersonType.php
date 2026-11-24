<?php

declare(strict_types=1);

namespace Clubhuesli\Model;

/**
 * Discriminator of the person hierarchy: a person is either a natural person
 * (optionally a team member) or a legal person.
 */
enum PersonType: string
{
    case NATURAL = 'NATURAL';
    case LEGAL   = 'LEGAL';

    /**
     * Returns the German label shown in the user interface.
     *
     * @return string The label.
     */
    public function label(): string
    {
        return match ($this) {
            self::NATURAL => 'Natürliche Person',
            self::LEGAL   => 'Juristische Person',
        };
    }
}
