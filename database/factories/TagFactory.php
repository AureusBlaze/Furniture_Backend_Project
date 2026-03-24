<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TagFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'Modern', 'Scandinavian', 'Industrial', 'Minimalist', 'Rustic',
            'Contemporary', 'Bohemian', 'Mid-Century', 'Traditional', 'Farmhouse',
            'Luxury', 'Budget-Friendly', 'Eco-Friendly', 'Compact', 'Outdoor',
        ]) . ' ' . $this->faker->unique()->numberBetween(1, 9999);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
