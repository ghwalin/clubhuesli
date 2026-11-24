<?php

declare(strict_types=1);

namespace Clubhuesli\Validation;

/**
 * Reusable value checks shared by the setters of the person models.
 *
 * Every method either returns the normalised value or throws a
 * {@see ValidationException} carrying the message shown to the user.
 */
final class Validator
{
    /** Pattern of a Swiss social security number (AHV). */
    public const AHV_PATTERN = '/^756\.\d{4}\.\d{4}\.\d{2}$/';

    /**
     * Trims a string and turns an empty string into null.
     *
     * @param string|null $value Raw value.
     *
     * @return string|null The trimmed value or null when empty.
     */
    public static function trimToNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * Checks the length of a mandatory text.
     *
     * @param string|null $value   Raw value.
     * @param string      $field   Field name used in the error.
     * @param int         $min     Minimum number of characters.
     * @param int         $max     Maximum number of characters.
     * @param string      $message Message shown to the user.
     *
     * @return string The trimmed value.
     *
     * @throws ValidationException When the value is missing or too short/long.
     */
    public static function requireText(?string $value, string $field, int $min, int $max, string $message): string
    {
        $value = self::trimToNull($value);
        if ($value === null) {
            throw new ValidationException($field, $message);
        }
        $length = mb_strlen($value);
        if ($length < $min || $length > $max) {
            throw new ValidationException($field, $message);
        }

        return $value;
    }

    /**
     * Checks the length of an optional text.
     *
     * @param string|null $value   Raw value.
     * @param string      $field   Field name used in the error.
     * @param int         $min     Minimum number of characters when present.
     * @param int         $max     Maximum number of characters when present.
     * @param string      $message Message shown to the user.
     *
     * @return string|null The trimmed value or null when empty.
     *
     * @throws ValidationException When the value is present but too short/long.
     */
    public static function optionalText(?string $value, string $field, int $min, int $max, string $message): ?string
    {
        $value = self::trimToNull($value);
        if ($value === null) {
            return null;
        }

        return self::requireText($value, $field, $min, $max, $message);
    }

    /**
     * Checks an e-mail address. E-mail addresses are not unique: families may
     * share one.
     *
     * @param string|null $value Raw value.
     * @param string      $field Field name used in the error.
     *
     * @return string|null The trimmed address or null when empty.
     *
     * @throws ValidationException When the address has no valid format.
     */
    public static function optionalEmail(?string $value, string $field = 'email'): ?string
    {
        $value = self::trimToNull($value);
        if ($value === null) {
            return null;
        }
        if (mb_strlen($value) > 190 || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new ValidationException($field, 'Bitte gültige E-Mail-Adresse eingeben');
        }

        return $value;
    }

    /**
     * Checks a phone number in international or national notation.
     *
     * @param string|null $value Raw value.
     * @param string      $field Field name used in the error.
     *
     * @return string|null The trimmed number or null when empty.
     *
     * @throws ValidationException When the number contains unexpected characters.
     */
    public static function optionalPhone(?string $value, string $field = 'phone'): ?string
    {
        $value = self::trimToNull($value);
        if ($value === null) {
            return null;
        }
        if (preg_match('/^\+?[0-9 ()\/.-]{6,40}$/', $value) !== 1) {
            throw new ValidationException($field, 'Bitte gültige Telefonnummer eingeben');
        }

        return $value;
    }

    /**
     * Checks an ISO 3166-1 alpha-2 country code.
     *
     * @param string|null $value Raw value.
     * @param string      $field Field name used in the error.
     *
     * @return string The upper-case country code; defaults to `CH`.
     *
     * @throws ValidationException When the code is not two letters.
     */
    public static function countryCode(?string $value, string $field = 'country'): string
    {
        $value = self::trimToNull($value);
        if ($value === null) {
            return 'CH';
        }
        $value = mb_strtoupper($value);
        if (preg_match('/^[A-Z]{2}$/', $value) !== 1) {
            throw new ValidationException($field, 'Bitte gültigen Ländercode eingeben (z. B. CH)');
        }

        return $value;
    }

    /**
     * Checks a postal code. Swiss postal codes consist of exactly four digits.
     *
     * @param string|null $value   Raw value.
     * @param string      $country ISO country code of the address.
     * @param string      $field   Field name used in the error.
     *
     * @return string|null The trimmed postal code or null when empty.
     *
     * @throws ValidationException When the postal code is invalid.
     */
    public static function optionalZip(?string $value, string $country, string $field = 'zip'): ?string
    {
        $value = self::trimToNull($value);
        if ($value === null) {
            return null;
        }
        $valid = $country === 'CH'
            ? preg_match('/^\d{4}$/', $value) === 1
            : preg_match('/^[A-Za-z0-9 -]{4,10}$/', $value) === 1;
        if (!$valid) {
            throw new ValidationException($field, 'Bitte gültige PLZ eingeben');
        }

        return $value;
    }

    /**
     * Checks a Swiss social security number: format `756.XXXX.XXXX.XX` with a
     * valid EAN-13 check digit.
     *
     * @param string|null $value Raw value.
     * @param string      $field Field name used in the error.
     *
     * @return string|null The trimmed number or null when empty.
     *
     * @throws ValidationException When format or check digit is wrong.
     */
    public static function optionalAhvNumber(?string $value, string $field = 'ahvNumber'): ?string
    {
        $value = self::trimToNull($value);
        if ($value === null) {
            return null;
        }
        $message = 'Bitte gültige AHV-Nummer eingeben (756.XXXX.XXXX.XX)';
        if (preg_match(self::AHV_PATTERN, $value) !== 1) {
            throw new ValidationException($field, $message);
        }
        if (!self::hasValidEan13CheckDigit(str_replace('.', '', $value))) {
            throw new ValidationException($field, $message);
        }

        return $value;
    }

    /**
     * Verifies the EAN-13 check digit of a 13-digit number.
     *
     * The first twelve digits are weighted 1 and 3 alternately; the check digit
     * completes the weighted sum to the next multiple of ten.
     *
     * @param string $digits Exactly 13 digits.
     *
     * @return bool True when the last digit is the correct check digit.
     */
    public static function hasValidEan13CheckDigit(string $digits): bool
    {
        if (preg_match('/^\d{13}$/', $digits) !== 1) {
            return false;
        }
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $digits[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        $checkDigit = (10 - $sum % 10) % 10;

        return $checkDigit === (int) $digits[12];
    }

    /**
     * Checks a date in `YYYY-MM-DD` notation.
     *
     * @param string|null $value Raw value.
     * @param string      $field Field name used in the error.
     *
     * @return string|null The date or null when empty.
     *
     * @throws ValidationException When the date does not exist.
     */
    public static function optionalDate(?string $value, string $field): ?string
    {
        $value = self::trimToNull($value);
        if ($value === null) {
            return null;
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches) !== 1
            || !checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            throw new ValidationException($field, 'Bitte gültiges Datum eingeben (JJJJ-MM-TT)');
        }

        return $value;
    }
}
