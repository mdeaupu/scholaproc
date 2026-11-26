<?php

return [
    'base_url' => env('WAHA_BASE_URL', 'http://localhost:3000'),
    'session' => env('WAHA_SESSION', 'default'),
    'engine' => env('WAHA_ENGINE', 'NOWEB'),
    'api_key' => env('WAHA_API_KEY', ''),
    'admin_cv_phone' => env('ADMIN_CV_PHONE', ''),
    'admin_cv_email' => env('ADMIN_CV_EMAIL', ''),
    'rate_limit' => env('WAHA_RATE_LIMIT', 30),
    'sync' => env('WAHA_SYNC', true),

    // Konfigurasi webhook untuk mencatat status delivery pesan keluar ke tabel notifikasi.
    'webhook_url' => env('WAHA_WEBHOOK_URL', null),
    'webhook_token' => env('WAHA_WEBHOOK_TOKEN', null),
    'webhook_events' => env('WAHA_WEBHOOK_EVENTS', 'message,message.ack,session.status'),
];
