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

    /*
    |--------------------------------------------------------------------------
    | Optional Static Access Token
    |--------------------------------------------------------------------------
    |
    | Keep this null for the normal daily login flow. The application now
    | prefers the stored session file for the live access token.
    |
    | If you ever want a local manual fallback, you can paste a token here
    | directly instead of storing it in .env.
    |
    */
    'access_token' => null,

    'token_file_path' => env('KITE_TOKEN_FILE_PATH', 'storage/app/kite_session.json'),
    'redirect_url' => env('KITE_REDIRECT_URL'),

];
