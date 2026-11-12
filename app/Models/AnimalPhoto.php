<?php

namespace App\Models;

use Database\Factories\AnimalPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Photo of an animal. The lowest position is the main photo, further photos follow in ascending order.
 */
#[Fillable(['animal_id', 'path', 'position', 'caption'])]
class AnimalPhoto extends Model
{
    /** @use HasFactory<AnimalPhotoFactory> */
    use HasFactory;

    /**
     * Maximum number of photos per animal.
     */
    public const MAX_PER_ANIMAL = 5;

    /**
     * Storage directory for the photos of a breeder. Separated per tenant by id,
     * so neither breeder nor animal names appear in paths or URLs.
     */
    public static function directoryFor(Breeder $breeder): string
    {
        return 'animal-photos/'.$breeder->getKey();
    }

    /**
     * @return BelongsTo<Animal, $this>
     */
    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
