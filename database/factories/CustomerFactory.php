<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'party_role' => 'customer',
            'entity_type' => 'individual',
            'customer_code' => null,
            'customer_group_id' => null,
            'business_name' => null,
            'prefix' => null,
            'first_name' => fake()->firstName(),
            'middle_name' => null,
            'last_name' => fake()->lastName(),
            'mobile' => fake()->numerify('##########'),
            'alternate_number' => null,
            'landline' => null,
            'email' => fake()->unique()->safeEmail(),
            'dob' => null,
            'tax_number' => null,
            'opening_balance' => 0,
            'credit_limit' => null,
            'pay_term_number' => null,
            'pay_term_type' => null,
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => null,
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => fake()->country(),
            'zip_code' => fake()->postcode(),
            'land_mark' => null,
            'street_name' => null,
            'building_number' => null,
            'additional_number' => null,
            'shipping_address' => null,
        ];
    }

    public function business(): static
    {
        return $this->state(fn () => [
            'entity_type' => 'business',
            'business_name' => fake()->company(),
            'first_name' => null,
            'last_name' => null,
        ]);
    }
}
