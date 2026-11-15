<?php

namespace Database\Factories;

use App\Models\HomeText;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomeText>
 */
class HomeTextFactory extends Factory
{
    protected $model = HomeText::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->slug(2),
            'value' => fake()->sentence(),
        ];
    }
}
