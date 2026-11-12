<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\AnimalPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnimalPhoto>
 */
class AnimalPhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'animal_id' => Animal::factory(),
            'path' => 'animal-photos/'.fake()->uuid().'.png',
            'position' => 0,
            'caption' => null,
        ];
    }
}
