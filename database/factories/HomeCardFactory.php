<?php

namespace Database\Factories;

use App\Models\HomeCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomeCard>
 */
class HomeCardFactory extends Factory
{
    protected $model = HomeCard::class;

    public function definition(): array
    {
        return [
            'section' => fake()->randomElement(['about', 'mission', 'core_values', 'service']),
            'icon' => 'fa-heartbeat',
            'title' => fake()->sentence(3),
            'text' => fake()->paragraph(),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
