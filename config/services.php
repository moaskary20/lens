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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    ],

    'fcm' => [
        // Preferred: Firebase service account JSON for HTTP v1.
        'credentials' => env('FCM_CREDENTIALS', storage_path('app/firebase/service-account.json')),
        // Legacy fallback (deprecated Cloud Messaging API).
        'server_key' => env('FCM_SERVER_KEY'),
    ],

    'google' => [
        // Web OAuth client ID from Firebase / Google Cloud (aud check for ID tokens).
        'client_id' => env('GOOGLE_CLIENT_ID', '165894637736-lil7v98s08ndt4munuj7885iv8nmsos1.apps.googleusercontent.com'),
        // Same Maps key used by the mobile app (Places + Maps JS). Prefer env override in production.
        'maps_key' => env('GOOGLE_MAPS_API_KEY', 'AIzaSyDijsR3eTz9oM39y8V0fejOh5LW-IAfPRY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
