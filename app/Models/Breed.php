<?php

namespace App\Models;

use App\Models\Concerns\IsBreederCustomizable;
use Database\Factories\BreedFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Breed of a species (e.g. Maine Coon for cats). Global when breeder_id is
 * null, otherwise a custom breed created by that breeder.
 */
#[Fillable(['species_id', 'breeder_id', 'name'])]
class Breed extends Model
{
    /** @use HasFactory<BreedFactory> */
    use HasFactory, IsBreederCustomizable;

    /**
     * @return BelongsTo<Species, $this>
     */
    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    /**
     * @return HasMany<Animal, $this>
     */
    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }
}
