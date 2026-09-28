<?php

if (!function_exists('to_bangla_number')) {
    /**
     * Converts English numbers/digits into Bengali digits
     */
    function to_bangla_number($number): string
    {
        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        return str_replace($en, $bn, (string) $number);
    }
}

if (!function_exists('format_amount')) {
    /**
     * Formats an amount according to user rule:
     * - If no fractional part (e.g. 5000.00), removes .00 and returns e.g. "5,000".
     * - If fractional part exists (e.g. 5000.05), keeps 2 decimals e.g. "5,000.05".
     * - If $isBn is true, converts to Bengali digits (e.g. "৫,০০০" or "৫,০০০.০৫").
     *
     * @param mixed $amount
     * @param bool $useComma
     * @param bool $isBn
     * @return string
     */
    function format_amount($amount, bool $useComma = true, bool $isBn = false): string
    {
        if ($amount === null || $amount === '') {
            return $isBn ? '০' : '0';
        }

        $clean = str_replace(',', '', (string) $amount);
        $num = (float) $clean;
        $hasDecimal = abs($num - round($num)) >= 0.00001;

        if ($hasDecimal) {
            $formatted = $useComma ? number_format($num, 2) : number_format($num, 2, '.', '');
        } else {
            $formatted = $useComma ? number_format($num, 0) : number_format($num, 0, '.', '');
        }

        return $isBn ? to_bangla_number($formatted) : $formatted;
    }
}

if (!function_exists('to_bangla_date')) {
    /**
     * Converts a date string or timestamp into Bengali formatted date
     */
    function to_bangla_date($date, string $format = 'd/m/Y'): string
    {
        if (!$date) return '';
        $dt = \Carbon\Carbon::parse($date);
        $months = [
            'January' => 'জানুয়ারি',
            'February' => 'ফেব্রুয়ারি',
            'March' => 'মার্চ',
            'April' => 'এপ্রিল',
            'May' => 'মে',
            'June' => 'জুন',
            'July' => 'জুলাই',
            'August' => 'আগস্ট',
            'September' => 'সেপ্টেম্বর',
            'October' => 'অক্টোবর',
            'November' => 'নভেম্বর',
            'December' => 'ডিসেম্বর',
            'Jan' => 'জানু',
            'Feb' => 'ফেব্রু',
            'Mar' => 'মার্চ',
            'Apr' => 'এপ্রিল',
            'May' => 'মে',
            'Jun' => 'জুন',
            'Jul' => 'জুলাই',
            'Aug' => 'আগস্ট',
            'Sep' => 'সেপ্টে',
            'Oct' => 'অক্টো',
            'Nov' => 'নভে',
            'Dec' => 'ডিসে',
        ];

        $formatted = $dt->format($format);
        $formatted = str_replace(array_keys($months), array_values($months), $formatted);
        return to_bangla_number($formatted);
    }
}

if (!function_exists('to_bangla_month')) {
    /**
     * Returns Bengali name of month
     */
    function to_bangla_month($month): string
    {
        $map = [
            1 => 'জানুয়ারি',
            2 => 'ফেব্রুয়ারি',
            3 => 'মার্চ',
            4 => 'এপ্রিল',
            5 => 'মে',
            6 => 'জুন',
            7 => 'জুলাই',
            8 => 'আগস্ট',
            9 => 'সেপ্টেম্বর',
            10 => 'অক্টোবর',
            11 => 'নভেম্বর',
            12 => 'ডিসেম্বর',
            'January' => 'জানুয়ারি',
            'February' => 'ফেব্রুয়ারি',
            'March' => 'মার্চ',
            'April' => 'এপ্রিল',
            'May' => 'মে',
            'June' => 'জুন',
            'July' => 'জুলাই',
            'August' => 'আগস্ট',
            'September' => 'সেপ্টেম্বর',
            'October' => 'অক্টোবর',
            'November' => 'নভেম্বর',
            'December' => 'ডিসেম্বর',
        ];

        return $map[$month] ?? (string) $month;
    }
}
