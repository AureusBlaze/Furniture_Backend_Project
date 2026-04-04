<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->randomElement([
            'Walnut Lounge Chair', 'Calacatta Marble Console', 'Matte Black Arch Lamp', 
            'Bouclé Cloud Sofa', 'Travertine Coffee Table', 'Brushed Brass Pendant', 
            'Oak Wood Dining Chair', 'Minimalist Glass Side Table', 'Cashmere Armchair', 
            'Ash Wood Credenza', 'Ceramic Accent Stool', 'Ribbed Glass Cabinet',
            'Linen Platform Bed', 'Smoked Glass Floor Lamp', 'Fluted Oak Console',
        ]) . ' ' . $this->faker->unique()->numberBetween(1, 9999);

        return [
            'vendor_id'   => Vendor::factory(),
            'category_id' => Category::factory(),
            'name'        => $name,
            'slug'        => Str::slug($name),
            'description' => $this->faker->paragraph(3),
            'price'       => $this->faker->randomFloat(2, 50, 5000),
            'stock'       => $this->faker->numberBetween(0, 200),
            'is_active'   => $this->faker->boolean(90),
            'weight'      => $this->faker->randomFloat(2, 1, 150),
            'dimensions'  => $this->faker->numberBetween(50, 250) . 'x' . $this->faker->numberBetween(30, 150) . 'x' . $this->faker->numberBetween(30, 200),
            'material'    => $this->faker->randomElement(['Oak Wood', 'Walnut', 'Pine', 'MDF', 'Leather', 'Fabric', 'Metal', 'Glass', 'Rattan', 'Velvet']),
        ];
    }
}
