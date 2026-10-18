<?php

namespace App\Support;

class TenancyHost
{
    /**
     * Normalize APP_URL / CENTRAL_DOMAIN values to a hostname.
     * Accepts "trustcash.mitsoft.bd", "https://trustcash.mitsoft.bd/", or an IP.
     */
    public static function from(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $value)) {
            $host = parse_url($value, PHP_URL_HOST);

            return $host ? strtolower($host) : null;
        }

        $value = explode('/', $value)[0];
        $value = trim($value);

        if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $value;
        }

        if (str_contains($value, ':') && !str_starts_with($value, '[')) {
            $value = explode(':', $value)[0];
        }

        return $value !== '' ? strtolower($value) : null;
    }
}
