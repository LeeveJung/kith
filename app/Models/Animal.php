<?php

namespace App\Models;

use App\Enums\AnimalOrigin;
use App\Enums\DatePrecision;
use App\Enums\Sex;
use Database\Factories\AnimalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * An animal in the pedigree. Its parents are derived from its litter;
 * foundation and external animals have no litter and therefore no parents.
 * "call_name" is an alternative name, e.g. used in the buyer's family.
 */
#[Fillable([
    'breeder_id', 'species_id', 'breed_id', 'color_id', 'litter_id', 'name', 'call_name',
    'sex', 'origin', 'born_on', 'born_on_precision', 'notes',
])]
class Animal extends Model
{
    /** @use HasFactory<AnimalFactory> */
    use HasFactory;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'sex' => Sex::class,
            'origin' => AnimalOrigin::class,
            'born_on' => 'date',
            'born_on_precision' => DatePrecision::class,
        ];
    }

    /**
     * @return BelongsTo<Breeder, $this>
     */
    public function breeder(): BelongsTo
    {
        return $this->belongsTo(Breeder::class);
    }

    /**
     * @return BelongsTo<Species, $this>
     */
    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    /**
     * @return BelongsTo<Breed, $this>
     */
    public function breed(): BelongsTo
    {
        return $this->belongsTo(Breed::class);
    }

    /**
     * @return BelongsTo<Color, $this>
     */
    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    /**
     * The litter the animal was born in.
     *
     * @return BelongsTo<Litter, $this>
     */
    public function litter(): BelongsTo
    {
        return $this->belongsTo(Litter::class);
    }

    /**
     * @return HasMany<AnimalPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(AnimalPhoto::class)->orderBy('position');
    }

    /**
     * @return HasOneThrough<Animal, Litter, $this>
     */
    public function mother(): HasOneThrough
    {
        return $this->hasOneThrough(self::class, Litter::class, 'id', 'id', 'litter_id', 'mother_id');
    }

    /**
     * @return HasOneThrough<Animal, Litter, $this>
     */
    public function father(): HasOneThrough
    {
        return $this->hasOneThrough(self::class, Litter::class, 'id', 'id', 'litter_id', 'father_id');
    }

    /**
     * Litters in which the animal is the mother.
     *
     * @return HasMany<Litter, $this>
     */
    public function littersAsMother(): HasMany
    {
        return $this->hasMany(Litter::class, 'mother_id');
    }

    /**
     * Litters in which the animal is the father (stud dog, stud cat, stallion ...).
     *
     * @return HasMany<Litter, $this>
     */
    public function littersAsFather(): HasMany
    {
        return $this->hasMany(Litter::class, 'father_id');
    }
}
