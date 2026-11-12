<?php

namespace App\Models;

use Database\Factories\SpeciesFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Species (cat, dog, horse ...). Global reference table, not tenant-specific.
 * Breeds and colors belong to a species.
 */
#[Fillable(['name', 'slug'])]
class Species extends Model
{
    /** @use HasFactory<SpeciesFactory> */
    use HasFactory;

    /**
     * @return HasMany<Breed, $this>
     */
    public function breeds(): HasMany
    {
        return $this->hasMany(Breed::class);
    }

    /**
     * @return HasMany<Color, $this>
     */
    public function colors(): HasMany
    {
        return $this->hasMany(Color::class);
    }

    /**
     * @return HasMany<Animal, $this>
     */
    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }
}
