<?php

namespace Database\Factories;

use App\Models\SellingPriceGroup;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SellingPriceGroup>
 */
class SellingPriceGroupFactory extends Factory
{
    protected $model = SellingPriceGroup::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
