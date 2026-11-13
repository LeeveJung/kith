<?php

it('renders the imprint with the operator details from the configuration', function () {
    config([
        'kith.operator.name' => 'Mira Example',
        'kith.operator.street' => 'Lindenweg 3',
        'kith.operator.city' => '12345 Musterstadt',
        'kith.operator.email' => 'hello@example.test',
        'kith.operator.phone' => null,
    ]);

    $response = $this->get(route('imprint'));

    $response->assertOk()
        ->assertSeeInOrder(['Mira Example', 'Lindenweg 3', '12345 Musterstadt'])
        ->assertSee('mailto:hello@example.test', false)
        ->assertDontSee('The operator details have not been configured yet.')
        ->assertDontSee('Phone');
});

it('shows a notice on the imprint while no operator is configured', function () {
    config(['kith.operator.name' => null]);

    $response = $this->get(route('imprint'));

    $response->assertOk()->assertSee('The operator details have not been configured yet.');
});

it('shows the privacy policy in the chosen language', function () {
    $this->get(route('privacy'))
        ->assertOk()
        ->assertSee('Controller')
        ->assertDontSee('Verantwortlicher');

    $this->get(route('locale.switch', 'de'));

    $this->get(route('privacy'))
        ->assertOk()
        ->assertSee('Verantwortlicher')
        ->assertSee('Datenschutz');
});

it('links the legal pages, cookie settings and donations from the landing page', function () {
    config(['kith.donation_url' => 'https://ko-fi.com/example']);

    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee(route('imprint'), false)
        ->assertSee(route('privacy'), false)
        ->assertSee('data-consent-settings', false)
        ->assertSee('href="https://ko-fi.com/example"', false)
        ->assertSee('All of kith. Completely free.');
});

it('renders the Matomo tracking code as a script that waits for consent', function () {
    config(['kith.matomo.url' => 'https://stats.example.test', 'kith.matomo.site_id' => '7']);

    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('<script type="text/plain" data-type="text/javascript" data-name="matomo">', false)
        ->assertSee("'https:\/\/stats.example.test\/'", false)
        ->assertSee("['setSiteId', '7']", false);
});

it('does not render the Matomo tracking code while it is not configured', function () {
    config(['kith.matomo.url' => null, 'kith.matomo.site_id' => null]);

    $response = $this->get(route('home'));

    $response->assertOk()->assertDontSee('data-name="matomo"', false);
});

it('describes Matomo in the privacy policy only while it is configured', function () {
    config(['kith.matomo.url' => 'https://stats.example.test/', 'kith.matomo.site_id' => '7']);

    $this->get(route('privacy'))
        ->assertOk()
        ->assertSee('Visitor statistics with Matomo')
        ->assertSee('stats.example.test');

    config(['kith.matomo.url' => null]);

    $this->get(route('privacy'))
        ->assertOk()
        ->assertDontSee('Visitor statistics with Matomo')
        ->assertSee('We only use technically necessary cookies.');
});
