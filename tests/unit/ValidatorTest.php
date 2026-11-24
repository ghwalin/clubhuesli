<?php

declare(strict_types=1);

namespace Clubhuesli\Tests;

use Clubhuesli\Validation\ValidationException;
use Clubhuesli\Validation\Validator;
use PHPUnit\Framework\TestCase;

/**
 * Tests the reusable value checks.
 */
final class ValidatorTest extends TestCase
{
    /**
     * An AHV number in the documented format with a correct check digit passes.
     */
    public function testValidAhvNumberIsAccepted(): void
    {
        self::assertSame('756.1234.5678.97', Validator::optionalAhvNumber('756.1234.5678.97'));
    }

    /**
     * A wrong check digit is rejected with the message from the story.
     */
    public function testAhvNumberWithWrongCheckDigitIsRejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Bitte gültige AHV-Nummer eingeben (756.XXXX.XXXX.XX)');

        Validator::optionalAhvNumber('756.1234.5678.96');
    }

    /**
     * A number that does not start with 756 or has the wrong shape is rejected.
     */
    public function testAhvNumberWithWrongFormatIsRejected(): void
    {
        $this->expectException(ValidationException::class);

        Validator::optionalAhvNumber('123.4567.8901.12');
    }

    /**
     * An empty AHV number stays empty: the number is optional.
     */
    public function testEmptyAhvNumberIsAllowed(): void
    {
        self::assertNull(Validator::optionalAhvNumber('   '));
    }

    /**
     * The EAN-13 check digit is verified over the first twelve digits.
     */
    public function testEan13CheckDigit(): void
    {
        self::assertTrue(Validator::hasValidEan13CheckDigit('7561234567897'));
        self::assertFalse(Validator::hasValidEan13CheckDigit('7561234567890'));
        self::assertFalse(Validator::hasValidEan13CheckDigit('75612345678'));
    }

    /**
     * Swiss postal codes consist of exactly four digits.
     */
    public function testSwissZipNeedsFourDigits(): void
    {
        self::assertSame('8000', Validator::optionalZip('8000', 'CH'));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Bitte gültige PLZ eingeben');

        Validator::optionalZip('800', 'CH');
    }

    /**
     * Postal codes of other countries may be longer and contain letters.
     */
    public function testForeignZipMayContainLetters(): void
    {
        self::assertSame('D-79540', Validator::optionalZip('D-79540', 'DE'));
    }

    /**
     * An address without a valid format is rejected.
     */
    public function testInvalidEmailIsRejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Bitte gültige E-Mail-Adresse eingeben');

        Validator::optionalEmail('anna@');
    }

    /**
     * A date that does not exist is rejected.
     */
    public function testImpossibleDateIsRejected(): void
    {
        $this->expectException(ValidationException::class);

        Validator::optionalDate('2026-02-30', 'birthDate');
    }
}
