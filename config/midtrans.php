<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Midtrans Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for Midtrans Payment Gateway.
    | Server Key and Client Key can be obtained from your Midtrans Dashboard.
    |
    */

    'server_key' => env('MIDTRANS_SERVER_KEY', ''),
    'client_key' => env('MIDTRANS_CLIENT_KEY', ''),
    'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
    'is_sanitized' => (bool) env('MIDTRANS_IS_SANITIZED', true),
    'is_3ds' => (bool) env('MIDTRANS_IS_3DS', true),
];
