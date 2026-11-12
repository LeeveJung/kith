<?php

namespace App\Models;

use App\Models\Concerns\IsBreederCustomizable;
use Database\Factories\ColorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Color of a species. Global when breeder_id is null, otherwise a custom
 * color created by that breeder. "is_dark" marks colors that need light text.
 */
#[Fillable(['species_id', 'breeder_id', 'name', 'hex', 'is_dark'])]
class Color extends Model
{
    /** @use HasFactory<ColorFactory> */
    use HasFactory, IsBreederCustomizable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_dark' => 'boolean',
        ];
    }

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
