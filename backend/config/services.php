<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Facebook Graph API Configuration
    |--------------------------------------------------------------------------
    */
    'facebook' => [
        'client_id'     => env('FACEBOOK_APP_ID'),
        'client_secret' => env('FACEBOOK_APP_SECRET'),
        'redirect'      => env('FACEBOOK_REDIRECT_URI'),
        'graph_version' => env('FACEBOOK_GRAPH_VERSION', 'v20.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Instagram (Uses Facebook App Credentials)
    |--------------------------------------------------------------------------
    */
    'instagram' => [
        'client_id'     => env('INSTAGRAM_APP_ID', env('FACEBOOK_APP_ID')),
        'client_secret' => env('INSTAGRAM_APP_SECRET', env('FACEBOOK_APP_SECRET')),
        'redirect'      => env('INSTAGRAM_REDIRECT_URI'),
    ],
];
