<?php

namespace App\Models;

use Database\Factories\BreederFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tenant: a breeding business (cattery, kennel, stud farm ...) with its own pedigree.
 */
#[Fillable(['name', 'slug'])]
class Breeder extends Model
{
    /** @use HasFactory<BreederFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * @return HasMany<Animal, $this>
     */
    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }

    /**
     * @return HasMany<Litter, $this>
     */
    public function litters(): HasMany
    {
        return $this->hasMany(Litter::class);
    }

    /**
     * Species in which this breeder has at least one litter, by name.
     *
     * @return array<int, string> species id => name
     */
    public function speciesWithLitters(): array
    {
        return Species::query()
            ->whereIn('id', $this->litters()->select('species_id'))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Custom breeds created by this breeder (global breeds are not included).
     *
     * @return HasMany<Breed, $this>
     */
    public function breeds(): HasMany
    {
        return $this->hasMany(Breed::class);
    }

    /**
     * Custom colors created by this breeder (global colors are not included).
     *
     * @return HasMany<Color, $this>
     */
    public function colors(): HasMany
    {
        return $this->hasMany(Color::class);
    }
}
