<?php

return [
    'enabled' => (bool) env('SMARTSIS_SPP_ENABLED', false),
    'base_url' => env('SMARTSIS_SPP_BASE_URL'),
    'token' => env('SMARTSIS_SPP_TOKEN'),
    'timeout' => (int) env('SMARTSIS_SPP_TIMEOUT', 10),
    'use_cache_fallback' => (bool) env('SMARTSIS_SPP_USE_CACHE_FALLBACK', true),
];
