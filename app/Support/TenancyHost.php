<?php

namespace App\Support;

use Illuminate\Http\Request;

class TenancyHost
{
    /**
     * Normalize APP_URL / CENTRAL_DOMAIN / stored domain values to a hostname.
     * Accepts full URLs, missing colons (http//host), and leftover tenant FQDNs.
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

        $value = preg_replace('/\s+/', '', $value) ?? $value;
        $value = preg_replace('#^(https?)[:/\\\\]+#i', '$1://', $value) ?? $value;

        if (preg_match('#https?://([^/]+)#i', $value, $matches)) {
            return self::normalizeHost($matches[1]);
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $value)) {
            $host = parse_url($value, PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                return self::normalizeHost($host);
            }
        }

        $withoutPath = preg_split('~[/?#]~', $value)[0] ?? $value;
        $host = self::normalizeHost($withoutPath);
        if ($host && !in_array($host, ['http', 'https'], true)) {
            return $host;
        }

        if (preg_match_all('/[a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)+/i', $value, $found)) {
            $candidates = $found[0];
            usort($candidates, fn ($a, $b) => strlen($b) <=> strlen($a));
            foreach ($candidates as $candidate) {
                $normalized = self::normalizeHost($candidate);
                if ($normalized && !in_array($normalized, ['http', 'https'], true)) {
                    return $normalized;
                }
            }
        }

        return null;
    }

    /**
     * Central hostname used to build shop URLs (never includes http/https).
     */
    public static function central(?Request $request = null): string
    {
        $request = $request ?: (function_exists('request') ? request() : null);

        $configured = self::from((string) config('tenancy.base_domain'))
            ?: self::from((string) config('app.url'));

        if ($configured && !self::isLocalHost($configured)) {
            return self::withNipIo($configured);
        }

        $host = $request?->getHost();
        if ($host && !self::isLocalHost($host)) {
            if ($configured && str_ends_with(strtolower($host), '.'.$configured)) {
                return self::withNipIo($configured);
            }

            return self::withNipIo($host);
        }

        return 'localhost';
    }

    public static function shopHost(string $subdomain, ?string $baseDomain = null): string
    {
        $sub = strtolower(trim($subdomain));
        $base = self::from($baseDomain) ?: self::central();

        if ($base === '' || $sub === '') {
            return $sub !== '' ? $sub : $base;
        }

        if (str_starts_with($base, $sub.'.')) {
            return $base;
        }

        return $sub.'.'.$base;
    }

    public static function shopUrl(string $subdomain, ?string $baseDomain = null, ?Request $request = null): string
    {
        $request = $request ?: (function_exists('request') ? request() : null);
        $scheme = 'http';
        if (str_starts_with((string) config('app.url'), 'https://')) {
            $scheme = 'https';
        } elseif ($request?->isSecure()) {
            $scheme = 'https';
        } elseif ($request) {
            $scheme = $request->getScheme() ?: 'http';
        }

        return $scheme.'://'.self::shopHost($subdomain, $baseDomain ?: self::central($request)).self::portSuffix($request);
    }

    public static function isMalformedShopDomain(?string $domain): bool
    {
        if (!$domain) {
            return true;
        }

        return str_contains($domain, '://')
            || str_contains($domain, '//')
            || str_contains($domain, '/')
            || preg_match('/(^|\.)https?\./i', $domain) === 1;
    }

    private static function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));
        $host = trim($host, '.[]');
        $host = preg_replace('#^(https?)\.#', '', $host) ?? $host;

        if (str_contains($host, ':') && !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $host = explode(':', $host)[0];
        }

        return $host;
    }

    public static function portSuffix(?Request $request = null): string
    {
        $request = $request ?: (function_exists('request') ? request() : null);
        if (!$request) {
            $configuredPort = parse_url((string) config('app.url'), PHP_URL_PORT);

            return ($configuredPort && !in_array((int) $configuredPort, [80, 443], true)) ? ":{$configuredPort}" : '';
        }

        $hostHeader = $request->header('Host', '');
        $headerPort = str_contains($hostHeader, ':') ? explode(':', $hostHeader)[1] : null;
        $configuredPort = parse_url((string) config('app.url'), PHP_URL_PORT);
        $forwardedPort = $request->header('X-Forwarded-Port');
        $port = $headerPort ?: ($forwardedPort ?: ($configuredPort ?: $request->getPort()));

        return ($port && !in_array((int) $port, [80, 443], true)) ? ":{$port}" : '';
    }

    private static function isLocalHost(string $host): bool
    {
        return in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
    }

    private static function withNipIo(string $host): string
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $host.'.nip.io';
        }

        return $host;
    }
}
