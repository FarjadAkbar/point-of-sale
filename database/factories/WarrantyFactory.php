<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\Warranty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warranty>
 */
class WarrantyFactory extends Factory
{
    protected $model = Warranty::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->words(2, true).' warranty',
            'description' => fake()->optional()->sentence(),
            'duration_value' => fake()->numberBetween(1, 36),
            'duration_unit' => fake()->randomElement(['day', 'week', 'month', 'year']),
        ];
    }
}
