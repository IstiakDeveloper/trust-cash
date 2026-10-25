<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Tests\TestCase;

class CentralShopAdminLoginTest extends TestCase
{
    public function test_shop_login_stays_on_the_main_domain(): void
    {
        $tenant = Tenant::find('demo');
        if (!$tenant || $tenant->status !== 'active') {
            $this->markTestSkipped('Demo tenant is not seeded.');
        }

        $response = $this->post('/login', [
            'email' => 'demo',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $response->assertSessionHas('active_tenant_id', 'demo');
        $this->assertStringNotContainsString('demo.localhost', $response->headers->get('Location'));

        $this->get('/admin/dashboard')->assertOk();
    }

    public function test_tenant_host_serves_the_shop_and_sends_admin_home(): void
    {
        $tenant = Tenant::find('demo');
        if (!$tenant || $tenant->status !== 'active') {
            $this->markTestSkipped('Demo tenant is not seeded.');
        }

        $this->get('http://demo.localhost/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Public/Home'));

        $this->get('http://demo.localhost/login')
            ->assertRedirect(rtrim((string) config('app.url'), '/').'/login');

        $this->get('http://demo.localhost/admin/dashboard')
            ->assertRedirect(rtrim((string) config('app.url'), '/').'/login');
    }

    public function test_super_admin_opens_and_leaves_a_shop_on_the_main_domain(): void
    {
        $admin = User::query()->where('email', 'admin@trustcash.com')->first();
        $tenant = Tenant::find('demo');
        if (!$admin || !$tenant || $tenant->status !== 'active') {
            $this->markTestSkipped('Super admin or demo tenant is missing.');
        }

        $this->actingAs($admin)
            ->get(route('super-admin.tenants.impersonate', $tenant))
            ->assertRedirect('/admin/dashboard');

        $this->get('/admin/dashboard')->assertOk();
        $this->assertAuthenticated();
        $this->assertNotSame($admin->id, auth()->id());

        $this->post('/admin/leave-shop')
            ->assertRedirect(route('super-admin.tenants.index'));

        $this->get(route('super-admin.tenants.index'))->assertOk();
        $this->assertAuthenticatedAs($admin);
    }
}
