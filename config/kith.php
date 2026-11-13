<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Donations
    |--------------------------------------------------------------------------
    |
    | kith is free for everyone. This link appears wherever we ask for a
    | voluntary donation (landing page, footers of the public pages).
    |
    */

    'donation_url' => env('KITH_DONATION_URL', 'https://ko-fi.com/lebenmitcmt'),

    /*
    |--------------------------------------------------------------------------
    | Operator (imprint and privacy policy)
    |--------------------------------------------------------------------------
    |
    | The person responsible for this site according to § 5 DDG and the
    | controller according to Art. 4 No. 7 GDPR. Kept in the environment so
    | no private address ends up in the repository.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Matomo
    |--------------------------------------------------------------------------
    |
    | Self-hosted visitor statistics. The tracking code is only rendered when
    | both values are set, and it only runs after consent in the cookie
    | settings (resources/js/consent.js).
    |
    */

    'matomo' => [
        'url' => env('MATOMO_URL'),
        'site_id' => env('MATOMO_SITE_ID'),
    ],

    'operator' => [
        'name' => env('KITH_OPERATOR_NAME'),
        'street' => env('KITH_OPERATOR_STREET'),
        'city' => env('KITH_OPERATOR_CITY'),
        'country' => env('KITH_OPERATOR_COUNTRY', 'Deutschland'),
        'email' => env('KITH_OPERATOR_EMAIL'),
        'phone' => env('KITH_OPERATOR_PHONE'),
    ],

];
