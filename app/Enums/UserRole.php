<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Platform-wide role of a user. Access to individual breeders is
 * controlled separately through the breeder_user membership.
 */
enum UserRole: string implements HasLabel
{
    case User = 'user';
    case SuperAdmin = 'super_admin';

    public function getLabel(): string
    {
        return match ($this) {
            self::User => __('User'),
            self::SuperAdmin => __('Super admin'),
        };
    }
}
