<?php

declare(strict_types=1);

namespace Clubhuesli\Import;

use Generator;
use RuntimeException;

/**
 * Reads the rows of single-line `INSERT INTO` statements from a MySQL dump.
 *
 * The reader streams the file, so even large dumps need little memory. Only the
 * tables asked for are parsed; all other lines are skipped without being
 * interpreted.
 */
final class SqlDumpReader
{
    /**
     * @param string $path Path of the dump file.
     */
    public function __construct(private readonly string $path)
    {
    }

    /**
     * Yields the rows of the given tables.
     *
     * @param array<int, string> $tables Names of the tables to read.
     *
     * @return Generator<int, array{table: string, row: array<string, string|int|float|null>}>
     *
     * @throws RuntimeException When the file cannot be read or a statement is malformed.
     */
    public function rows(array $tables): Generator
    {
        $handle = fopen($this->path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Die Datei «{$this->path}» kann nicht gelesen werden");
        }
        $wanted = array_flip($tables);

        try {
            $lineNumber = 0;
            while (($line = fgets($handle)) !== false) {
                $lineNumber++;
                if (!str_starts_with($line, 'INSERT INTO `')) {
                    continue;
                }
                $end   = strpos($line, '`', 13);
                $table = $end === false ? '' : substr($line, 13, $end - 13);
                if (!isset($wanted[$table])) {
                    continue;
                }
                yield ['table' => $table, 'row' => $this->parse($line, $lineNumber)];
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Collects the rows of the given tables, grouped by table.
     *
     * @param array<int, string> $tables Names of the tables to read.
     *
     * @return array<string, array<int, array<string, string|int|float|null>>> The rows per table.
     */
    public function collect(array $tables): array
    {
        $result = array_fill_keys($tables, []);
        foreach ($this->rows($tables) as $entry) {
            $result[$entry['table']][] = $entry['row'];
        }

        return $result;
    }

    /**
     * Parses one `INSERT INTO t (cols) VALUES (values);` statement.
     *
     * @param string $line       The statement.
     * @param int    $lineNumber Line number for error messages.
     *
     * @return array<string, string|int|float|null> The row, keyed by column.
     *
     * @throws RuntimeException When the statement is malformed.
     */
    private function parse(string $line, int $lineNumber): array
    {
        if (preg_match('/^INSERT INTO `[^`]+` \(([^)]*)\) VALUES \(/', $line, $matches) !== 1) {
            throw new RuntimeException("Zeile {$lineNumber}: unbekanntes INSERT-Format");
        }
        $columns = array_map(static fn (string $column): string => trim($column, ' `'), explode(',', $matches[1]));
        $values  = self::parseValues($line, strlen($matches[0]), $lineNumber);

        if (count($columns) !== count($values)) {
            throw new RuntimeException("Zeile {$lineNumber}: Anzahl Spalten und Werte stimmt nicht überein");
        }

        return array_combine($columns, $values);
    }

    /**
     * Parses the value list of a statement.
     *
     * @param string $line       The statement.
     * @param int    $offset     Position after the opening parenthesis.
     * @param int    $lineNumber Line number for error messages.
     *
     * @return array<int, string|int|float|null> The values.
     *
     * @throws RuntimeException When the list is malformed.
     */
    private static function parseValues(string $line, int $offset, int $lineNumber): array
    {
        $values = [];
        $length = strlen($line);
        $i      = $offset;

        while ($i < $length) {
            while ($i < $length && ($line[$i] === ' ' || $line[$i] === ',')) {
                $i++;
            }
            if ($i >= $length || $line[$i] === ')') {
                return $values;
            }
            if ($line[$i] === "'") {
                [$values[], $i] = self::readString($line, $i + 1, $lineNumber);
                continue;
            }
            $start = $i;
            while ($i < $length && $line[$i] !== ',' && $line[$i] !== ')') {
                $i++;
            }
            $token = trim(substr($line, $start, $i - $start));
            if (strcasecmp($token, 'NULL') === 0) {
                $values[] = null;
            } elseif (is_numeric($token)) {
                $values[] = str_contains($token, '.') ? (float) $token : (int) $token;
            } else {
                $values[] = $token;
            }
        }

        throw new RuntimeException("Zeile {$lineNumber}: die Wertliste ist nicht abgeschlossen");
    }

    /**
     * Reads a quoted string with MySQL escapes.
     *
     * @param string $line       The statement.
     * @param int    $start      Position after the opening quote.
     * @param int    $lineNumber Line number for error messages.
     *
     * @return array{0: string, 1: int} The string and the position after the closing quote.
     *
     * @throws RuntimeException When the string is not closed.
     */
    private static function readString(string $line, int $start, int $lineNumber): array
    {
        static $escapes = ['n' => "\n", 'r' => "\r", 't' => "\t", '0' => "\0", 'Z' => "\x1a", 'b' => "\x08"];

        $length = strlen($line);
        $result = '';
        $i      = $start;
        while ($i < $length) {
            $char = $line[$i];
            if ($char === '\\' && $i + 1 < $length) {
                $next    = $line[$i + 1];
                $result .= $escapes[$next] ?? $next;
                $i      += 2;
                continue;
            }
            if ($char === "'") {
                if ($i + 1 < $length && $line[$i + 1] === "'") {
                    $result .= "'";
                    $i      += 2;
                    continue;
                }

                return [$result, $i + 1];
            }
            $result .= $char;
            $i++;
        }

        throw new RuntimeException("Zeile {$lineNumber}: eine Zeichenkette ist nicht abgeschlossen");
    }
}
