<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Category;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('===============================================');
        $this->command->info('SETUP REMINDER:');
        $this->command->info('1. Run `php artisan storage:link` if you haven\'t already.');
        $this->command->info('2. Create `storage/app/public/placeholders` directory.');
        $this->command->info('3. Place a `default_furniture.jpg` inside that folder.');
        $this->command->info('4. Update your .env APP_URL to your local IPv4 address (e.g., http://192.168.1.x:8000).');
        $this->command->info('===============================================');
        // ── Admin ──────────────────────────────────────────────
        $admin = User::factory()->admin()->create([
            'name'  => 'Admin User',
            'email' => 'admin@furniture.com',
            'password' => Hash::make('password'),
        ]);

        // ── Tags ───────────────────────────────────────────────
        $tagNames = [
            'Modern', 'Scandinavian', 'Industrial', 'Minimalist', 'Rustic',
            'Contemporary', 'Bohemian', 'Mid-Century', 'Traditional', 'Eco-Friendly',
            'Luxury', 'Compact', 'Outdoor', 'Velvet', 'Wooden',
        ];
        $tags = collect($tagNames)->map(fn($name) => Tag::create([
            'name' => $name,
            'slug' => Str::slug($name),
        ]));

        // ── Categories ─────────────────────────────────────────
        $categoryData = [
            ['Living Room', ['Sofas & Couches', 'Coffee Tables', 'TV Stands']],
            ['Bedroom',     ['Beds & Frames', 'Wardrobes', 'Nightstands']],
            ['Dining Room', ['Dining Tables', 'Dining Chairs']],
            ['Office',      ['Desks', 'Office Chairs']],
            ['Outdoor',     ['Garden Furniture', 'Patio Sets']],
        ];

        $allCategories = collect();
        foreach ($categoryData as [$parentName, $children]) {
            $parent = Category::create([
                'name'      => $parentName,
                'slug'      => Str::slug($parentName),
                'parent_id' => null,
            ]);
            $allCategories->push($parent);
            foreach ($children as $childName) {
                $child = Category::create([
                    'name'      => $childName,
                    'slug'      => Str::slug($childName),
                    'parent_id' => $parent->id,
                ]);
                $allCategories->push($child);
            }
        }
        $leafCategories = $allCategories->whereNotNull('parent_id');

        // ── Vendors + Addresses ────────────────────────────────
        $vendorUsers = User::factory()->vendor()->count(3)->create();
        $vendors     = collect();
        foreach ($vendorUsers as $vUser) {
            $address  = Address::create([
                'user_id'      => $vUser->id,
                'address_line' => fake()->streetAddress(),
                'city'         => fake()->city(),
                'country'      => fake()->country(),
                'postal_code'  => fake()->postcode(),
                'is_default'   => true,
            ]);
            $vendor = Vendor::create([
                'user_id'     => $vUser->id,
                'address_id'  => $address->id,
                'name'        => fake()->company() . ' Furniture',
                'description' => fake()->paragraph(),
                'is_active'   => true,
            ]);
            $vendors->push($vendor);
        }

        // ── Products ───────────────────────────────────────────
        $products = collect();
        for ($i = 0; $i < 30; $i++) {
            $name    = fake()->randomElement([
                'Walnut Lounge Chair', 'Calacatta Marble Console', 'Matte Black Arch Lamp', 
                'Bouclé Cloud Sofa', 'Travertine Coffee Table', 'Brushed Brass Pendant', 
                'Oak Wood Dining Chair', 'Minimalist Glass Side Table', 'Cashmere Armchair', 
                'Ash Wood Credenza', 'Ceramic Accent Stool', 'Ribbed Glass Cabinet',
                'Linen Platform Bed', 'Smoked Glass Floor Lamp', 'Fluted Oak Console',
            ]) . ' ' . fake()->unique()->numberBetween(1, 9999);
            $product = Product::create([
                'vendor_id'   => $vendors->random()->id,
                'category_id' => $leafCategories->random()->id,
                'name'        => $name,
                'slug'        => Str::slug($name) . '-' . ($i + 1),
                'description' => fake()->paragraph(3),
                'price'       => fake()->randomFloat(2, 50, 5000),
                'stock'       => fake()->numberBetween(1, 100),
                'is_active'   => true,
                'weight'      => fake()->randomFloat(2, 1, 100),
                'dimensions'  => '120x60x80',
                'material'    => fake()->randomElement(['Oak', 'Leather', 'Fabric', 'Metal', 'Glass', 'MDF', 'Walnut']),
            ]);

            // Attach random tags (2-4)
            $product->tags()->sync($tags->random(rand(2, 4))->pluck('id'));

            // Attach a media placeholder
            $media = Media::create([
                'name'      => $product->name . ' Image',
                'url'       => 'placeholders/default_furniture.jpg',
                'disk'      => 'public',
                'type'      => 'image',
                'mime_type' => 'image/jpeg',
            ]);
            $product->media()->attach($media->id, ['is_primary' => true, 'sort_order' => 0]);
            $products->push($product);
        }

        // ── Customer Users + Addresses ─────────────────────────
        $customers = User::factory()->count(10)->create();
        foreach ($customers as $customer) {
            $address = Address::create([
                'user_id'      => $customer->id,
                'address_line' => fake()->streetAddress(),
                'city'         => fake()->city(),
                'country'      => fake()->country(),
                'postal_code'  => fake()->postcode(),
                'is_default'   => true,
            ]);
            $customer->update(['default_address_id' => $address->id]);

            // ── Orders ─────────────────────────────────────────
            $orderCount = rand(1, 3);
            for ($o = 0; $o < $orderCount; $o++) {
                $orderProducts = $products->random(rand(1, 4));
                $total         = 0;
                $order         = Order::create([
                    'user_id'     => $customer->id,
                    'total_price' => 0,
                    'status'      => fake()->randomElement(['pending', 'paid', 'shipped', 'delivered']),
                    'notes'       => null,
                ]);
                $order->addresses()->attach($address->id);

                foreach ($orderProducts as $product) {
                    $qty      = rand(1, 3);
                    $subtotal = $product->price * $qty;
                    OrderItem::create([
                        'order_id'   => $order->id,
                        'product_id' => $product->id,
                        'quantity'   => $qty,
                        'unit_price' => $product->price,
                        'subtotal'   => $subtotal,
                    ]);
                    $total += $subtotal;
                }
                $order->update(['total_price' => $total]);
            }
        }
    }
}
