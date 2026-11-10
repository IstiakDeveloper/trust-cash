<?php

namespace Tests\Feature;

use App\Http\Middleware\InitializeTenancyFromSession;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Exercise the authenticated routes on an isolated tenant-equivalent schema.
        $this->withoutMiddleware(InitializeTenancyFromSession::class);
    }

    private function login(): void
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
    }

    public function test_inline_creation_returns_records_and_creates_product_without_stock(): void
    {
        $this->login();
        $category = $this->postJson('/admin/categories', ['name' => 'চাল', 'status' => true])
            ->assertCreated()->assertJsonPath('data.name', 'চাল')->json('data');
        $brand = $this->postJson('/admin/brands', ['name' => 'দেশি', 'status' => true])
            ->assertCreated()->json('data');
        $this->postJson('/admin/brands', ['name' => 'অন্য ব্র্যান্ড', 'status' => true])->assertCreated();
        $unit = $this->postJson('/admin/units', ['name' => 'Kilogram', 'short_name' => 'kg', 'status' => true])
            ->assertCreated()->json('data');
        $this->getJson('/admin/products/create')->assertOk()
            ->assertJsonFragment(['id' => $category['id'], 'name' => 'চাল']);
        $payload = [
            'name' => 'চাল ২৫ কেজি', 'sku' => 'QUICK-001', 'type' => 'product',
            'category_id' => $category['id'], 'brand_id' => $brand['id'], 'unit_id' => $unit['id'],
            'selling_price' => 1500, 'alert_quantity' => 0, 'primary_image_index' => 0, 'status' => true,
        ];
        $record = $this->postJson('/admin/products', $payload)->assertCreated()
            ->assertJsonPath('data.unit_id', $unit['id'])->json('data');
        $this->assertTrue(Product::findOrFail($record['id'])->isPhysical());
        $this->assertDatabaseCount('product_stocks', 0);
        $this->assertDatabaseCount('ledger_lines', 0);
        $this->postJson('/admin/products', $payload)->assertUnprocessable()->assertJsonValidationErrors('sku');
    }

    public function test_validation_errors_remain_json_and_do_not_create_duplicates(): void
    {
        $this->login();
        $this->postJson('/admin/categories', ['name' => 'Food', 'status' => true])->assertCreated();
        $this->postJson('/admin/categories', ['name' => 'Food', 'status' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->postJson('/admin/brands', ['name' => 'Acme', 'status' => true])->assertCreated();
        $this->postJson('/admin/brands', ['name' => 'Acme', 'status' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->postJson('/admin/units', ['name' => 'Box', 'status' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('short_name');
        $this->postJson('/admin/products', ['name' => 'Incomplete'])
            ->assertUnprocessable()->assertJsonValidationErrors(['category_id', 'unit_id', 'sku']);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_unauthenticated_inline_creation_is_rejected(): void
    {
        $this->postJson('/admin/categories', ['name' => 'Unauthorized'])->assertUnauthorized();
        $this->postJson('/admin/products', ['name' => 'Unauthorized'])->assertUnauthorized();
    }
}
