<?php

namespace Database\Factories;

use App\Models\HomeTestimony;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomeTestimony>
 */
class HomeTestimonyFactory extends Factory
{
    protected $model = HomeTestimony::class;

    public function definition(): array
    {
        return [
            'image' => 'patient-'.fake()->slug().'.webp',
            'name' => fake()->name(),
            'title' => 'Pasien Rawat Inap',
            'text' => fake()->paragraph(),
        ];
    }
}
