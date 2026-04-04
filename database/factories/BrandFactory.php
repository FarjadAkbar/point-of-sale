<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->company().' brand',
            'description' => fake()->optional()->sentence(),
            'user_for_repair' => false,
        ];
    }
}
