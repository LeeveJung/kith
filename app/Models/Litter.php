<?php

namespace App\Models;

use App\Enums\DatePrecision;
use Database\Factories\LitterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A litter (or mating): mother and father, optionally with a litter letter
 * (A, B, C ...) that is unique per breeder and species. Species without
 * litter letters (e.g. horses) leave it empty. The birth date may be unknown
 * or only known to the month or year.
 */
#[Fillable(['breeder_id', 'species_id', 'letter', 'mother_id', 'father_id', 'born_on', 'born_on_precision', 'notes'])]
class Litter extends Model
{
    /** @use HasFactory<LitterFactory> */
    use HasFactory;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
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
     * @return BelongsTo<Animal, $this>
     */
    public function mother(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'mother_id');
    }

    /**
     * @return BelongsTo<Animal, $this>
     */
    public function father(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'father_id');
    }

    /**
     * @return HasMany<Animal, $this>
     */
    public function offspring(): HasMany
    {
        return $this->hasMany(Animal::class);
    }
}
