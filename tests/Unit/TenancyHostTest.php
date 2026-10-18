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

    public function test_keeps_bare_hostname(): void
    {
        $this->assertSame('trustcash.mitsoft.bd', TenancyHost::from('trustcash.mitsoft.bd'));
    }

    public function test_strips_port_from_hostname(): void
    {
        $this->assertSame('trustcash.mitsoft.bd', TenancyHost::from('trustcash.mitsoft.bd:443'));
    }

    public function test_returns_null_for_empty_values(): void
    {
        $this->assertNull(TenancyHost::from(null));
        $this->assertNull(TenancyHost::from(''));
        $this->assertNull(TenancyHost::from('   '));
    }
}
