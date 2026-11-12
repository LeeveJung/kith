<?php

it('shows the landing page in English by default', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('Every litter.')
        ->assertSee('images/kith-logo.webp', false)
        ->assertSee(route('filament.app.auth.login'), false)
        ->assertSee(route('locale.switch', 'de'), false);
});

it('shows the landing page in German after switching the language', function () {
    $this->get('/locale/de')->assertRedirect();

    $this->get('/')
        ->assertSuccessful()
        ->assertSee('Jeder Wurf.')
        ->assertSee('lang="de"', false)
        ->assertDontSee('Every litter.');
});
