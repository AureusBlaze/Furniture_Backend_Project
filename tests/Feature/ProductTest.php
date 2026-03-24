<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_can_be_listed()
    {
        Vendor::factory()->create();
        Category::factory()->create();
        Product::factory()->count(3)->create(['is_active' => true]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    public function test_authenticated_vendor_can_create_product()
    {
        $user = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/products', [
            'name' => 'New Sofa',
            'description' => 'A nice sofa',
            'price' => 299.99,
            'stock' => 10,
            'category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.name', 'New Sofa');
    }
}
