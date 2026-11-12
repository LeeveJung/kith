<?php

namespace Database\Factories;

use App\Models\Breeder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Breeder>
 */
class BreederFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->lastName().' Zucht';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
