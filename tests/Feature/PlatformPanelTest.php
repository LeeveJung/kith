<?php

use App\Enums\UserRole;
use App\Filament\Platform\Resources\Breeds\Pages\ManageBreeds;
use App\Filament\Platform\Resources\Users\Pages\ManageUsers;
use App\Models\Breed;
use App\Models\Breeder;
use App\Models\Species;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lets only super admins into the platform panel', function () {
    $this->get('/platform')->assertRedirect();

    $this->actingAs(User::factory()->create())->get('/platform')->assertForbidden();

    $this->actingAs(User::factory()->superAdmin()->create())->get('/platform')->assertSuccessful();
});

it('lets every user into the breeder panel regardless of role', function () {
    $user = User::factory()->create();
    $breeder = Breeder::factory()->create();
    $user->breeders()->attach($breeder);

    $this->actingAs($user)->get("/app/{$breeder->slug}/animals")->assertSuccessful();
});

it('lets super admins promote a custom breed to a global one', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    Filament::setCurrentPanel('platform');
    Filament::bootCurrentPanel();

    $breed = Breed::factory()->for(Species::factory()->create())->create(['breeder_id' => Breeder::factory()->create()->id]);

    Livewire::test(ManageBreeds::class)
        ->assertCanSeeTableRecords([$breed])
        ->callAction(TestAction::make('edit')->table($breed), data: ['breeder_id' => null])
        ->assertHasNoFormErrors();

    expect($breed->fresh()->isGlobal())->toBeTrue();
});

it('lets super admins create users with a role and breeder membership', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    Filament::setCurrentPanel('platform');
    Filament::bootCurrentPanel();

    $breeder = Breeder::factory()->create();

    Livewire::test(ManageUsers::class)
        ->callAction('create', data: [
            'name' => 'Jane Breeder',
            'email' => 'jane@example.com',
            'password' => 'secret-password',
            'role' => UserRole::User,
            'locale' => 'de',
            'breeders' => [$breeder->id],
        ])
        ->assertHasNoFormErrors();

    $user = User::firstWhere('email', 'jane@example.com');

    expect($user->role)->toBe(UserRole::User)
        ->and($user->locale)->toBe('de')
        ->and($user->breeders->pluck('id')->all())->toBe([$breeder->id])
        ->and(Hash::check('secret-password', $user->password))->toBeTrue();
});

it('treats only the super admin role as platform admin', function () {
    expect(User::factory()->create()->isSuperAdmin())->toBeFalse()
        ->and(User::factory()->superAdmin()->create()->isSuperAdmin())->toBeTrue();
});

it('renders every page of the platform panel', function (string $path) {
    $this->actingAs(User::factory()->superAdmin()->create())
        ->get("/platform/{$path}")
        ->assertSuccessful();
})->with(['species', 'breeds', 'colors', 'breeders', 'users']);
