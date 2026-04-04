<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->words(2, true),
            'code' => null,
            'description' => fake()->optional()->sentence(),
            'is_sub_taxonomy' => false,
            'parent_id' => null,
        ];
    }
}
