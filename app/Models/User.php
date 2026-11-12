<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'email', 'password', 'role', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Breeders (tenants) the user is a member of.
     *
     * @return BelongsToMany<Breeder, $this>
     */
    public function breeders(): BelongsToMany
    {
        return $this->belongsToMany(Breeder::class)->withTimestamps();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    /**
     * Everyone may use the breeder panel; the platform panel is for super admins only.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'platform' ? $this->isSuperAdmin() : true;
    }

    /**
     * @return Collection<int, Breeder>
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->breeders;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->breeders()->whereKey($tenant)->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }
}
