<?php

namespace Tests\Unit;

use App\Support\TenancyHost;
use PHPUnit\Framework\TestCase;

class TenancyHostTest extends TestCase
{
    public function test_extracts_host_from_full_https_url(): void
    {
        $this->assertSame('trustcash.mitsoft.bd', TenancyHost::from('https://trustcash.mitsoft.bd/'));
    }

    public function test_extracts_host_from_http_url_without_path(): void
    {
        $this->assertSame('trustcash.mitsoft.bd', TenancyHost::from('http://trustcash.mitsoft.bd'));
    }

    public function test_fixes_missing_colon_in_scheme(): void
    {
        $this->assertSame('trustcash.mitsoft.bd', TenancyHost::from('http//trustcash.mitsoft.bd'));
    }

    public function test_keeps_bare_hostname(): void
    {
        $this->assertSame('trustcash.mitsoft.bd', TenancyHost::from('trustcash.mitsoft.bd'));
    }

    public function test_strips_port_from_hostname(): void
    {
        $this->assertSame('trustcash.mitsoft.bd', TenancyHost::from('trustcash.mitsoft.bd:443'));
    }

    public function test_builds_shop_host_without_scheme(): void
    {
        $this->assertSame(
            'testshop.trustcash.mitsoft.bd',
            TenancyHost::shopHost('testshop', 'http://trustcash.mitsoft.bd')
        );
        $this->assertSame(
            'testshop.trustcash.mitsoft.bd',
            TenancyHost::shopHost('testshop', 'http//trustcash.mitsoft.bd')
        );
    }

    public function test_detects_malformed_shop_domains(): void
    {
        $this->assertTrue(TenancyHost::isMalformedShopDomain('testshop.http://trustcash.mitsoft.bd'));
        $this->assertTrue(TenancyHost::isMalformedShopDomain('testshop.http//trustcash.mitsoft.bd'));
        $this->assertFalse(TenancyHost::isMalformedShopDomain('testshop.trustcash.mitsoft.bd'));
    }

    public function test_returns_null_for_empty_values(): void
    {
        $this->assertNull(TenancyHost::from(null));
        $this->assertNull(TenancyHost::from(''));
        $this->assertNull(TenancyHost::from('   '));
    }
}
