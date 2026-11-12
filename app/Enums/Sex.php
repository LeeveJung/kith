<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Sex: string implements HasLabel
{
    case Female = 'female';
    case Male = 'male';
    case Unknown = 'unknown';

    public function getLabel(): string
    {
        return match ($this) {
            self::Female => __('Female'),
            self::Male => __('Male'),
            self::Unknown => __('Unknown'),
        };
    }
}
