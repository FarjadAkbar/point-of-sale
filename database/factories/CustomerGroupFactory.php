<?php

namespace Database\Factories;

use App\Models\CustomerGroup;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerGroup>
 */
class CustomerGroupFactory extends Factory
{
    protected $model = CustomerGroup::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->words(2, true),
            'price_calculation_type' => 'percentage',
            'amount' => null,
            'selling_price_group_id' => null,
        ];
    }
}
