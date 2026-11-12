<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Where an animal in the pedigree comes from.
 *
 * Bred: born in the own business (belongs to a litter).
 * Foundation: founding animal, starting point of the breeding line.
 * External: partner animal from another breeder.
 */
enum AnimalOrigin: string implements HasLabel
{
    case Bred = 'bred';
    case Foundation = 'foundation';
    case External = 'external';

    public function getLabel(): string
    {
        return match ($this) {
            self::Bred => __('Own breeding'),
            self::Foundation => __('Foundation animal'),
            self::External => __('External animal'),
        };
    }
}
