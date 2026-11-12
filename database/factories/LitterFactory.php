<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\Breeder;
use App\Models\Litter;
use App\Models\Species;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Litter>
 */
class LitterFactory extends Factory
{
    /**
     * Mother and father are created for the same breeder and species as the litter.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'breeder_id' => Breeder::factory(),
            'species_id' => Species::factory(),
            'letter' => fake()->unique()->lexify('?'),
            'mother_id' => fn (array $attributes) => Animal::factory()->female()->create([
                'breeder_id' => $attributes['breeder_id'],
                'species_id' => $attributes['species_id'],
            ])->id,
            'father_id' => fn (array $attributes) => Animal::factory()->male()->create([
                'breeder_id' => $attributes['breeder_id'],
                'species_id' => $attributes['species_id'],
            ])->id,
            'born_on' => null,
            'born_on_precision' => null,
        ];
    }
}
