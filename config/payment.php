<?php

declare(strict_types=1);

use App\Services\Payment\Drivers\FakeGateway;
use App\Services\Payment\Drivers\MidtransGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Active driver
    |--------------------------------------------------------------------------
    |
    | PaymentServiceProvider resolves App\Services\Payment\Contracts\PaymentGateway
    | from this key. Tests set it to "fake" so nothing reaches the network.
    |
    */

    'driver' => env('PAYMENT_DRIVER', 'midtrans'),

    /*
    |--------------------------------------------------------------------------
    | Driver map
    |--------------------------------------------------------------------------
    |
    | Adding a gateway is a class implementing the contract plus a line here.
    |
    */

    'drivers' => [
        'midtrans' => MidtransGateway::class,
        'fake' => FakeGateway::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Production switch
    |--------------------------------------------------------------------------
    |
    | Deliberately separate from APP_ENV: a staging box points at the sandbox
    | while running with APP_ENV=production, and pointing the wrong way costs
    | real money.
    |
    */

    'is_production' => (bool) env('PAYMENT_IS_PRODUCTION', false),

    /*
    |--------------------------------------------------------------------------
    | Midtrans
    |--------------------------------------------------------------------------
    |
    | Keys live in env only. The server key doubles as the webhook signature
    | secret, so it never leaves the server side.
    |
    */

    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'merchant_id' => env('MIDTRANS_MERCHANT_ID'),

        // One switch decides sandbox or live, above — a second env flag here
        // would be a way to point the keys and the URLs at different worlds.
        'snap_url' => env('PAYMENT_IS_PRODUCTION', false)
            ? 'https://app.midtrans.com/snap/v1'
            : 'https://app.sandbox.midtrans.com/snap/v1',

        'api_url' => env('PAYMENT_IS_PRODUCTION', false)
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2',

        // Seconds to wait on the Snap API before giving up. Checkout is
        // synchronous for the client, so this stays short.
        'timeout' => (int) env('MIDTRANS_TIMEOUT', 15),

        // Which methods Snap offers. Empty means "everything the account has".
        'enabled_payments' => array_values(array_filter(
            explode(',', (string) env('MIDTRANS_ENABLED_PAYMENTS', '')),
        )),
    ],

];
