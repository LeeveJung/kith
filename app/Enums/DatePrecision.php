<?php

namespace App\Enums;

use Carbon\CarbonInterface;
use Filament\Support\Contracts\HasLabel;

/**
 * How precisely a date is known. Birth dates in a pedigree are often only
 * known as "April 2023", or not at all.
 */
enum DatePrecision: string implements HasLabel
{
    case Day = 'day';
    case Month = 'month';
    case Year = 'year';

    /**
     * Formats the date according to its precision ("April 2023"); "Date unknown" without a date.
     */
    public static function describe(?CarbonInterface $date, ?self $precision): string
    {
        if ($date === null) {
            return __('Date unknown');
        }

        return match ($precision) {
            self::Month => $date->translatedFormat('F Y'),
            self::Year => $date->format('Y'),
            default => $date->translatedFormat('Y-m-d'),
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Day => __('Day'),
            self::Month => __('Month'),
            self::Year => __('Year'),
        };
    }
}
