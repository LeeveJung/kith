<?php

use App\Models\Breeder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->breeder = Breeder::factory()->create();
    $this->user = User::factory()->create();
    $this->user->breeders()->attach($this->breeder);
});

it('shows the interface in English by default', function () {
    expect(config('app.locale'))->toBe('en');

    $this->actingAs($this->user)
        ->get("/app/{$this->breeder->slug}/animals")
        ->assertSuccessful()
        ->assertSee('Animals')
        ->assertDontSee('Tiere');
});

it('stores the chosen language on the user and in the session', function () {
    $this->actingAs($this->user)
        ->from("/app/{$this->breeder->slug}/animals")
        ->get('/locale/de')
        ->assertRedirect("/app/{$this->breeder->slug}/animals");

    expect($this->user->fresh()->locale)->toBe('de')
        ->and(session('locale'))->toBe('de');
});

it('shows the interface in German after switching', function () {
    $this->actingAs($this->user)->get('/locale/de');

    $this->get("/app/{$this->breeder->slug}/animals")
        ->assertSuccessful()
        ->assertSee('Tiere')
        ->assertDontSee('>Animals<', false);
});

it('remembers the language in the session for guests', function () {
    $this->get('/locale/de')->assertRedirect('/');

    expect(session('locale'))->toBe('de');
});

it('rejects unsupported languages', function () {
    $this->get('/locale/xx')->assertNotFound();
});

it('offers a language switch in the user menu', function () {
    $this->actingAs($this->user)
        ->get("/app/{$this->breeder->slug}/animals")
        ->assertSee(route('locale.switch', 'de'), false)
        ->assertDontSee(route('locale.switch', 'en'), false);
});
