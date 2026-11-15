<?php

namespace Database\Factories;

use App\Models\HomeDoctor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomeDoctor>
 */
class HomeDoctorFactory extends Factory
{
    protected $model = HomeDoctor::class;

    public function definition(): array
    {
        return [
            'image' => 'doctor-'.fake()->slug().'.webp',
            'name' => fake()->name(),
            'title' => 'Spesialis Bedah',
            'text' => fake()->paragraph(),
            'twitter' => null,
            'fb' => null,
            'linkedin' => null,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
