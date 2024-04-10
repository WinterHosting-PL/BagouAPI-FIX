<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],
    ],
    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'client_link_id' => env('GITHUB_LINK_CLIENT_ID'),
        'client_link_secret' => env('GITHUB_LINK_CLIENT_SECRET'),
    ],
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET')
    ],
    'patreon' => [
        'client_id' => env('PATREON_KEY'),
        'client_secret' => env('PATREON_SECRET')
    ],
    'discord' => [
        'token' => env('DISCORD_TOKEN'),
        'server' => env('DISCORD_SERVER'),
        'client' => env('DISCORD_CLIENT'),
        'client_id' => env('DISCORD_CLIENT'),
        'client_secret' => env('DISCORD_CLIENT_SECRET'),
        'redirect' => env('DISCORD_OAUTH_REDIRECTION_URL'),
        'accountredirect' => env('DISCORD_ACCOUNT_REDIRECTION_URL'),
        'botkey' => env('BAGOUOX_API_KEY')
    ],
    'infomaniak' => [
        'api' => env('INFOMANIAK_KEY'),
        'secret' => env("INFOMANIAK_SECRET"),
        'key' => env('INFOMANIAK_V3Key')
    ],
    'encryption' => [
        'PBKDF' => [
            'shared_key' => env('PBKDF_SHARED_KEY'),
            'iteration' => env('PBKDF_ITERATION')
        ],
    ],
];
