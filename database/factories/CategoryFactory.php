<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->randomElement([
            'Living Room', 'Bedroom', 'Dining Room', 'Office', 'Outdoor',
            'Kids Room', 'Bathroom', 'Kitchen', 'Entryway', 'Storage',
        ]) . ' ' . $this->faker->unique()->numberBetween(1, 9999);

        return [
            'name'        => $name,
            'slug'        => Str::slug($name),
            'description' => $this->faker->sentence(),
            'parent_id'   => null,
        ];
    }
}
