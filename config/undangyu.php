<?php

return [

    /*
    |--------------------------------------------------------------------------
    | First super admin
    |--------------------------------------------------------------------------
    |
    | Read by AdminUserSeeder. Credentials live in the environment, never in the
    | repository — with `email` or `password` blank the seeder skips instead of
    | creating an account with a guessable password.
    |
    | Read through config() rather than env() so `config:cache` doesn't blank
    | them out in production.
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Super Admin'),
        'email' => env('ADMIN_EMAIL'),
        'phone' => env('ADMIN_PHONE', '+628000000000'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
