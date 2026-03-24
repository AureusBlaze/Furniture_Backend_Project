<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VendorFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->company() . ' Furniture';
        return [
            'user_id'     => User::factory()->state(['role' => 'vendor']),
            'name'        => $name,
            'description' => $this->faker->paragraph(2),
            'logo_url'    => null,
            'is_active'   => true,
        ];
    }
}
