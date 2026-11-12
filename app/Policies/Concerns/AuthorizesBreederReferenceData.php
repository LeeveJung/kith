<?php

namespace App\Policies\Concerns;

use App\Models\Breed;
use App\Models\Color;
use App\Models\User;
use Filament\Facades\Filament;

/**
 * Shared rules for breeds and colors: everyone can see global entries and
 * the custom entries of their current breeder, but only a breeder's own
 * custom entries can be changed by that breeder. Super admins manage
 * everything in the platform panel.
 */
trait AuthorizesBreederReferenceData
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Breed|Color $record): bool
    {
        return $record->isGlobal() || $this->canManage($user, $record);
    }

    public function create(User $user): bool
    {
        return $this->isPlatformPanel() ? $user->isSuperAdmin() : Filament::getTenant() !== null;
    }

    public function update(User $user, Breed|Color $record): bool
    {
        return $this->canManage($user, $record);
    }

    public function delete(User $user, Breed|Color $record): bool
    {
        return $this->canManage($user, $record);
    }

    public function deleteAny(User $user): bool
    {
        return true;
    }

    private function canManage(User $user, Breed|Color $record): bool
    {
        if ($this->isPlatformPanel()) {
            return $user->isSuperAdmin();
        }

        $tenant = Filament::getTenant();

        return $tenant !== null
            && $record->breeder_id === $tenant->getKey()
            && $user->canAccessTenant($tenant);
    }

    private function isPlatformPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'platform';
    }
}
