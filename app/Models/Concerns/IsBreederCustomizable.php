<?php

namespace App\Models\Concerns;

use App\Models\Breeder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reference data (breeds, colors) that is either global (breeder_id = null,
 * maintained by platform admins) or a custom entry owned by one breeder.
 *
 * @property int|null $breeder_id
 */
trait IsBreederCustomizable
{
    /**
     * @return BelongsTo<Breeder, $this>
     */
    public function breeder(): BelongsTo
    {
        return $this->belongsTo(Breeder::class);
    }

    public function isGlobal(): bool
    {
        return $this->breeder_id === null;
    }

    /**
     * Global entries plus the custom entries of the given breeder.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function availableTo(Builder $query, ?Breeder $breeder): void
    {
        $query->where(function (Builder $query) use ($breeder): void {
            $query->whereNull('breeder_id');

            if ($breeder !== null) {
                $query->orWhere('breeder_id', $breeder->getKey());
            }
        });
    }
}
