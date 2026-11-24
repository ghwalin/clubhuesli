<?php

declare(strict_types=1);

namespace Clubhuesli\Model;

/**
 * Kind of membership a person holds.
 *
 * The list is extensible; the cases marked as legal-only are used for sponsors,
 * patrons, partners and municipalities (see «Juristische Personen verwalten»).
 */
enum MemberType: string
{
    case ACTIVE       = 'ACTIVE';
    case PASSIVE      = 'PASSIVE';
    case PARENT       = 'PARENT';
    case HONORARY     = 'HONORARY';
    case SPONSOR      = 'SPONSOR';
    case PATRON       = 'PATRON';
    case PARTNER      = 'PARTNER';
    case MUNICIPALITY = 'MUNICIPALITY';

    /**
     * Returns the German label shown in the user interface.
     *
     * @return string The label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE       => 'Aktiv',
            self::PASSIVE      => 'Passiv',
            self::PARENT       => 'Elternteil',
            self::HONORARY     => 'Ehrenmitglied',
            self::SPONSOR      => 'Sponsor',
            self::PATRON       => 'Gönner',
            self::PARTNER      => 'Partner',
            self::MUNICIPALITY => 'Gemeinde',
        };
    }

    /**
     * Tells whether this member type may be used for the given person type.
     *
     * @param PersonType $personType Person type to check.
     *
     * @return bool True when the combination is allowed.
     */
    public function appliesTo(PersonType $personType): bool
    {
        return match ($this) {
            self::ACTIVE, self::PASSIVE, self::PARENT, self::HONORARY => $personType === PersonType::NATURAL,
            self::SPONSOR, self::PATRON, self::PARTNER, self::MUNICIPALITY => $personType === PersonType::LEGAL,
        };
    }

    /**
     * Returns all member types available for the given person type.
     *
     * @param PersonType $personType Person type to filter by.
     *
     * @return array<int, self> The matching member types.
     */
    public static function forPersonType(PersonType $personType): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $case): bool => $case->appliesTo($personType)
        ));
    }
}
