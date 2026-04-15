<?php

return [
    'enabled' => (bool) env('SMARTSIS_SPP_ENABLED', false),
    'base_url' => env('SMARTSIS_SPP_BASE_URL'),
    'token' => env('SMARTSIS_SPP_TOKEN'),
    'timeout' => (int) env('SMARTSIS_SPP_TIMEOUT', 10),
    'sync_timeout' => (int) env('SMARTSIS_SPP_SYNC_TIMEOUT', 60),
    'retry_times' => (int) env('SMARTSIS_SPP_RETRY_TIMES', 2),
    'retry_sleep_ms' => (int) env('SMARTSIS_SPP_RETRY_SLEEP_MS', 1000),
    'use_cache_fallback' => (bool) env('SMARTSIS_SPP_USE_CACHE_FALLBACK', true),
    'master_default_nominal' => (int) env('SMARTSIS_SPP_DEFAULT_NOMINAL', 400000),
    'jurusan_map' => [
        [
            'kode' => 'RPL',
            'nama' => 'Rekayasa Perangkat Lunak',
            'aliases' => ['RPL', 'REKAYASAPERANGKATLUNAK'],
            'kode_akun' => '4.01.01.00',
        ],
        [
            'kode' => 'TSM',
            'nama' => 'Teknik Sepeda Motor',
            'aliases' => ['TSM', 'TEKNIKSEPEDAMOTOR'],
            'kode_akun' => '4.01.02.00',
        ],
        [
            'kode' => 'PH',
            'nama' => 'Perhotelan',
            'aliases' => ['PH', 'PERHOTELAN'],
            'kode_akun' => '4.01.03.00',
        ],
    ],
];
