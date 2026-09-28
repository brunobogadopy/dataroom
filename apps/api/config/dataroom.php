<?php

return [
    'max_upload_mb' => (int) env('DATAROOM_MAX_UPLOAD_MB', 100),
    'storage' => [
        'zone' => env('BUNNY_STORAGE_ZONE'),
        'access_key' => env('BUNNY_STORAGE_ACCESS_KEY'),
        'hostname' => env('BUNNY_STORAGE_HOSTNAME', 'storage.bunnycdn.com'),
    ],
    'search' => [
        'host' => rtrim((string) env('MEILISEARCH_HOST', 'http://meilisearch:7700'), '/'),
        'key' => env('MEILISEARCH_KEY'),
        'index' => env('MEILISEARCH_INDEX', 'dataroom_nodes'),
    ],
];
