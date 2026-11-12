<?php

namespace Database\Factories;

use App\Enums\AnimalOrigin;
use App\Enums\Sex;
use App\Models\Animal;
use App\Models\Breeder;
use App\Models\Litter;
use App\Models\Species;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Animal>
 */
class AnimalFactory extends Factory
{
    /**
     * Defaults to a foundation animal without litter and parents.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'breeder_id' => Breeder::factory(),
            'species_id' => Species::factory(),
            'breed_id' => null,
            'color_id' => null,
            'litter_id' => null,
            'name' => fake()->unique()->firstName(),
            'call_name' => null,
            'sex' => fake()->randomElement([Sex::Female, Sex::Male]),
            'origin' => AnimalOrigin::Foundation,
            'born_on' => null,
            'born_on_precision' => null,
        ];
    }

    public function female(): static
    {
        return $this->state(fn (array $attributes) => ['sex' => Sex::Female]);
    }

    public function male(): static
    {
        return $this->state(fn (array $attributes) => ['sex' => Sex::Male]);
    }

    /**
     * Animal from another breeder.
     */
    public function external(): static
    {
        return $this->state(fn (array $attributes) => ['origin' => AnimalOrigin::External]);
    }

    /**
     * Offspring of the given litter; takes over breeder, species and birth date from the litter.
     */
    public function inLitter(Litter $litter): static
    {
        return $this->state(fn (array $attributes) => [
            'breeder_id' => $litter->breeder_id,
            'species_id' => $litter->species_id,
            'litter_id' => $litter->id,
            'origin' => AnimalOrigin::Bred,
            'born_on' => $litter->born_on,
            'born_on_precision' => $litter->born_on_precision,
        ]);
    }
}
