<?php

namespace Database\Factories;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'code' => 'AST-'.fake()->unique()->numerify('####'),
            'category' => fake()->randomElement(['Komputer', 'Jaringan', 'Alat Ukur', 'Multimedia']),
            'status' => fake()->randomElement(['tersedia', 'dipinjam', 'rusak', 'perawatan']),
            'price' => fake()->numberBetween(1000000, 50000000),
            'location' => 'Lab '.fake()->randomDigitNotNull(),
        ];
    }
}
