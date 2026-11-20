<?php

namespace App\Services;

class CsvSanitizer
{
    /**
     *Karakter awal yang memicu eksekusi formula pada spreadsheet (CSV Formula Injection).
     *
     * @var list<string>
     */
    private const FORMULA_TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * Netralisasi nilai string agar aman saat dibuka di Excel / LibreOffice / Google Sheets.
     */
    public static function sanitize(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $stringValue = (string) $value;

        if ($stringValue === '') {
            return '';
        }

        $trimmed = ltrim($stringValue, " \t\n\r\0\x0B");

        foreach (self::FORMULA_TRIGGERS as $trigger) {
            if (str_starts_with($stringValue, $trigger) || str_starts_with($trimmed, $trigger)) {
                return "'".$stringValue;
            }
        }

        return $stringValue;
    }

    /**
     * Sanitasi seluruh elemen dalam satu baris CSV.
     *
     * @param  list<mixed>  $row
     * @return list<string>
     */
    public static function sanitizeRow(array $row): array
    {
        return array_map(fn (mixed $cell): string => self::sanitize($cell), $row);
    }
}
