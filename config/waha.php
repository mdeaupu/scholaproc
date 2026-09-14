<?php

return [
    'base_url' => env('WAHA_BASE_URL', 'http://localhost:3000'),
    'session' => env('WAHA_SESSION', 'default'),
    'api_key' => env('WAHA_API_KEY', ''),
    'admin_cv_phone' => env('ADMIN_CV_PHONE', ''),
    'admin_cv_email' => env('ADMIN_CV_EMAIL', ''),
    'rate_limit' => env('WAHA_RATE_LIMIT', 30),
];
