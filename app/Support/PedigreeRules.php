<?php

namespace App\Support;

use App\Enums\Sex;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Server-side validation rules that keep pedigree data consistent: every
 * referenced record must belong to the given breeder (or be global
 * reference data) and match the species. Form option lists are filtered
 * the same way, but request data can always be tampered with.
 */
final class PedigreeRules
{
    /**
     * A breed of the species that is global or owned by the breeder.
     */
    public static function breed(?int $breederId, mixed $speciesId): Exists
    {
        return self::referenceData('breeds', $breederId, $speciesId);
    }

    /**
     * A color of the species that is global or owned by the breeder.
     */
    public static function color(?int $breederId, mixed $speciesId): Exists
    {
        return self::referenceData('colors', $breederId, $speciesId);
    }

    /**
     * A litter of the breeder with the given species.
     */
    public static function litter(?int $breederId, mixed $speciesId): Exists
    {
        return Rule::exists('litters', 'id')
            ->where('breeder_id', $breederId)
            ->where('species_id', $speciesId);
    }

    /**
     * An animal of the breeder with the given species and sex, usable as mother or father.
     */
    public static function parent(?int $breederId, mixed $speciesId, Sex $sex): Exists
    {
        return Rule::exists('animals', 'id')
            ->where('breeder_id', $breederId)
            ->where('species_id', $speciesId)
            ->where('sex', $sex->value);
    }

    private static function referenceData(string $table, ?int $breederId, mixed $speciesId): Exists
    {
        return Rule::exists($table, 'id')->where(
            fn (Builder $query) => $query
                ->where('species_id', $speciesId)
                ->where(fn (Builder $query) => $query->whereNull('breeder_id')->orWhere('breeder_id', $breederId)),
        );
    }
}
