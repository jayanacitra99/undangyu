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

    /*
    |--------------------------------------------------------------------------
    | Link preview image
    |--------------------------------------------------------------------------
    |
    | GenerateOgImageJob draws the couple's names onto a 1200×630 card. GD's
    | built-in font ignores size(), so a real TTF is what makes the names
    | legible at the size WhatsApp shows the card. See resources/fonts/README.md
    | for where this one came from and how to replace it.
    |
    */

    'og_image' => [
        'font' => env('OG_IMAGE_FONT', resource_path('fonts/DejaVuSerif-Bold.ttf')),
    ],

    'admin' => [
        'name' => env('ADMIN_NAME', 'Super Admin'),
        'email' => env('ADMIN_EMAIL'),
        'phone' => env('ADMIN_PHONE', '+628000000000'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
