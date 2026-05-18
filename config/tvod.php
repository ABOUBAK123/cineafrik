<?php

return [
    'max_simultaneous_streams' => (int) env('MAX_SIMULTANEOUS_STREAMS', 2),
    'max_offline_downloads' => (int) env('MAX_OFFLINE_DOWNLOADS', 3),
    'offline_expiry_hours' => (int) env('OFFLINE_EXPIRY_HOURS', 48),
    'offline_download_expiry_days' => (int) env('OFFLINE_DOWNLOAD_EXPIRY_DAYS', 7),
    'drm_secret_key' => env('DRM_SECRET_KEY'),
    'drm_key_rotation_hours' => (int) env('DRM_KEY_ROTATION_HOURS', 24),

    'payment_providers' => [
        'cinetpay' => [
            'api_key' => env('CINETPAY_API_KEY'),
            'site_id' => env('CINETPAY_SITE_ID'),
            'base_url' => 'https://api-checkout.cinetpay.com/v2',
        ],
        'fedapay' => [
            'secret_key' => env('FEDAPAY_SECRET_KEY'),
            'public_key' => env('FEDAPAY_PUBLIC_KEY'),
            'base_url' => 'https://api.fedapay.com/v1',
        ],
        'wave' => [
            'api_key' => env('WAVE_API_KEY'),
            'base_url' => 'https://api.wave.com/v1',
        ],
        'orange_money' => [
            'client_id' => env('ORANGE_MONEY_CLIENT_ID'),
            'client_secret' => env('ORANGE_MONEY_CLIENT_SECRET'),
        ],
        'mtn_momo' => [
            'api_key' => env('MTN_MOMO_API_KEY'),
            'subscription_key' => env('MTN_MOMO_SUBSCRIPTION_KEY'),
        ],
    ],

    'sms' => [
        'provider' => env('SMS_PROVIDER', 'africas_talking'),
        'africas_talking' => [
            'username' => env('AFRICAS_TALKING_USERNAME'),
            'api_key' => env('AFRICAS_TALKING_API_KEY'),
        ],
    ],

    // Origines autorisées (domaine web app + app mobile en prod)
    'allowed_origins' => array_filter(explode(',', env('ALLOWED_ORIGINS', 'http://localhost:8001'))),

    // Hôte CDN pour validation des segments relayés
    'cdn_host' => env('AWS_CLOUDFRONT_URL', ''),

    'countries' => [
        'CI' => ['name' => 'Côte d\'Ivoire', 'currency' => 'XOF', 'dial_code' => '+225'],
        'SN' => ['name' => 'Sénégal', 'currency' => 'XOF', 'dial_code' => '+221'],
        'NG' => ['name' => 'Nigeria', 'currency' => 'NGN', 'dial_code' => '+234'],
        'GH' => ['name' => 'Ghana', 'currency' => 'GHS', 'dial_code' => '+233'],
        'BF' => ['name' => 'Burkina Faso', 'currency' => 'XOF', 'dial_code' => '+226'],
    ],
];
