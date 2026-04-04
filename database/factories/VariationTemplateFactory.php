<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\VariationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VariationTemplate>
 */
class VariationTemplateFactory extends Factory
{
    protected $model = VariationTemplate::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->words(2, true),
        ];
    }
}
