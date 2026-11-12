<?php

namespace Database\Factories;

use App\Models\Color;
use App\Models\Species;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Color>
 */
class ColorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'species_id' => Species::factory(),
            'name' => fake()->unique()->colorName(),
            'hex' => fake()->hexColor(),
            'is_dark' => false,
        ];
    }

    /**
     * Color that needs light text on top.
     */
    public function dark(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_dark' => true,
        ]);
    }
}
