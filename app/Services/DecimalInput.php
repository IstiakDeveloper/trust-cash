<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class DecimalInput
{
    public static function normalize($value, int $scale): string
    {
        if (! is_numeric($value) || ! is_finite((float) $value)) {
            throw ValidationException::withMessages(['amount' => 'Use a finite decimal number.']);
        }
        $text = (string) $value;
        if (str_contains(strtolower($text), 'e')) {
            $text = sprintf('%.*F', $scale, (float) $value);
        }
        if (! preg_match('/^-?\d+(?:\.\d+)?$/D', $text) || bccomp($text, bcadd($text, '0', $scale), max($scale, 12)) !== 0) {
            throw ValidationException::withMessages(['amount' => "Use no more than {$scale} decimal places."]);
        }

        return bcadd($text, '0', $scale);
    }
}
