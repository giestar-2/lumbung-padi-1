<?php

namespace App;

use Illuminate\Validation\ValidationException;

final class Decimal
{
    public static function normalize(mixed $value, int $scale = 2, string $field = 'amount'): string
    {
        $value = str_replace(',', '.', trim((string) $value));
        if (! preg_match('/^\d+(?:\.\d{1,'.$scale.'})?$/D', $value) || strlen(explode('.', $value)[0]) > 12) {
            throw ValidationException::withMessages([$field => 'Gunakan angka tanpa pemisah ribuan, maksimal '.$scale.' angka desimal.']);
        }

        return bcadd($value, '0', $scale);
    }

    public static function money(string $value): string
    {
        return bcadd($value, bccomp($value, '0', 8) < 0 ? '-0.005' : '0.005', 2);
    }

    public static function today(): string
    {
        return now('Asia/Jakarta')->toDateString();
    }

    public static function display(mixed $value, int $scale = 2): string
    {
        $formatted = number_format((float) str_replace(',', '.', (string) $value), $scale, ',', '.');

        return $scale > 0 ? rtrim(rtrim($formatted, '0'), ',') : $formatted;
    }

    public static function input(mixed $value): string
    {
        $value = (string) $value;

        return str_contains($value, '.') ? rtrim(rtrim($value, '0'), '.') : $value;
    }
}
