<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Livewire\Livewire;

/**
 * Livewire update requests already pass the "web" middleware group. Running the
 * cookie and session middleware a second time as persistent middleware makes
 * EncryptCookies decrypt the already decrypted session cookie, which drops the
 * session and logs the user out on the first Livewire interaction.
 */
it('does not re-run cookie and session middleware on Livewire update requests', function (string $middleware) {
    expect(Livewire::getPersistentMiddleware())->not->toContain($middleware);
})->with([
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    PreventRequestForgery::class,
]);

it('applies the user language on Livewire update requests through the web group', function () {
    expect(app('router')->getMiddlewareGroups()['web'])->toContain(SetLocale::class);
});
