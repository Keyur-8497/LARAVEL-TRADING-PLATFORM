<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Zerodha Kite API Configuration
    |--------------------------------------------------------------------------
    |
    | These values are used to authenticate with the Zerodha Kite API.
    | Using config() instead of env() ensures the app works when
    | config is cached in production.
    |
    */

    'api_key' => env('KITE_API_KEY'),
    'api_secret' => env('KITE_API_SECRET'),
    'access_token' => env('KITE_ACCESS_TOKEN'),

];
