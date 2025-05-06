<?php

return [
    'allowed_origins' => [
        'https://admin.komplekt.az',
        'http://localhost',
        'https://open-az.vercel.app',
    ],
    'timestamp_tolerance' => 60, // saniyələr - 1 dəqiqə ərzində etibarlı
    'signing_secret' => env('API_SIGNING_SECRET', 'your-secret-key-here'),
];
