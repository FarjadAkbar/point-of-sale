<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->word().'s',
            'short_name' => fake()->lexify('??'),
            'allow_decimal' => false,
            'is_multiple_of_base' => false,
            'base_unit_id' => null,
            'base_unit_multiplier' => null,
        ];
    }
}
