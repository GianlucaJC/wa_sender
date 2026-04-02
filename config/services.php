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



    'meta_whatsapp' => [
        'system_user_token' => env('META_SYSTEM_USER_TOKEN'), // Token del System User del Portfolio
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'), // ID numero di telefono di default (se necessario)
        'api_version' => 'v20.0', // Aggiornato a una versione più recente e stabile
        'client_id' => env('META_WHATSAPP_CLIENT_ID'), // ID dell'App Facebook (per l'SDK JS)
        'config_id' => env('META_WHATSAPP_CONFIG_ID'), // ID della configurazione "Embedded Signup"
        'app_secret' => env('META_APP_SECRET'), 
        'webhook_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN'), 
    ],
    'management_app' => [
        'home_url' => env('MANAGEMENT_HOME_URL', '#'),
    ],
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

];
