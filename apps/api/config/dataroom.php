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

    'ai' => [
        'api_key' => env('AI_API_KEY'),
        'embeddings_url' => env('AI_EMBEDDINGS_URL'),
        'responses_url' => env('AI_RESPONSES_URL'),
        'embedding_model' => env('AI_EMBEDDING_MODEL'),
        'generation_model' => env('AI_GENERATION_MODEL'),
        'embedding_dimensions' => (int) env('AI_EMBEDDING_DIMENSIONS', 1536),
        'chunk_chars' => (int) env('AI_CHUNK_CHARS', 2400),
        'chunk_overlap_chars' => (int) env('AI_CHUNK_OVERLAP_CHARS', 300),
        'max_sources' => (int) env('AI_MAX_SOURCES', 8),
    ],
];
