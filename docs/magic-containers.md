# Magic Containers deployment — Dataroom v0.2

Use one **Single Region** Magic Containers application initially because PostgreSQL is stateful. Containers within the same application can use the internal networking model provided by Magic Containers; set hostnames according to the values shown in your deployed app.

## web
Image: `ghcr.io/<owner>/<repo>-web:latest`  
Port: `3000`

Build variable:
```env
NEXT_PUBLIC_API_URL=https://api.example.com/api/v1
```

## api
Image: `ghcr.io/<owner>/<repo>-api:latest`  
Port: `8080`

```env
APP_NAME=Dataroom
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.example.com
APP_KEY=<generated Laravel key>
CORS_ALLOWED_ORIGINS=https://app.example.com

DB_CONNECTION=pgsql
DB_HOST=<postgres host>
DB_PORT=5432
DB_DATABASE=dataroom
DB_USERNAME=<secret>
DB_PASSWORD=<secret>

REDIS_HOST=<redis host>
REDIS_PORT=6379
CACHE_STORE=redis
QUEUE_CONNECTION=redis

MEILISEARCH_HOST=http://<meilisearch-host>:7700
MEILISEARCH_KEY=<strong master key>
MEILISEARCH_INDEX=dataroom_nodes

BUNNY_STORAGE_ZONE=<storage zone name>
BUNNY_STORAGE_ACCESS_KEY=<storage zone password/access key>
BUNNY_STORAGE_HOSTNAME=storage.bunnycdn.com
DATAROOM_MAX_UPLOAD_MB=100
```

Keep `BUNNY_STORAGE_ACCESS_KEY` only in the API/worker environment. Never expose it as a `NEXT_PUBLIC_*` value.

Run migrations on release:
```bash
php artisan migrate --force
```

## worker
Same API image and environment. Command:
```bash
php artisan queue:work --sleep=1 --tries=3 --timeout=300
```

PDF/DOCX extraction happens here, not in the HTTP request.

## postgres
Attach a persistent volume. Use backups before real customer onboarding.

## redis
Used for queues/cache. v0.2 assumes a single Redis instance.

## meilisearch
Attach a persistent volume and use a strong `MEILI_MASTER_KEY`. If Meilisearch is temporarily unavailable, the API falls back to permission-scoped PostgreSQL search.

## bunny.net Storage
Create one private Storage Zone for Dataroom objects. The API stores objects beneath:

```text
workspaces/{workspace_uuid}/files/{node_uuid}/{version_uuid}.{ext}
```

The database is the authorization source of truth. Do not expose the raw Storage Zone URL or key to end users.

## Health check
API: `/up`


## AI / semantic search (v0.7)

Dataroom v0.7 requires PostgreSQL with the pgvector extension. Use a PostgreSQL image/build that includes pgvector.

Server-side environment variables:

```env
AI_API_KEY=<provider key>
AI_EMBEDDINGS_URL=<full embeddings endpoint>
AI_RESPONSES_URL=<full Responses-compatible generation endpoint>
AI_EMBEDDING_MODEL=<embedding model>
AI_GENERATION_MODEL=<generation model>
AI_EMBEDDING_DIMENSIONS=1536
AI_CHUNK_CHARS=2400
AI_CHUNK_OVERLAP_CHARS=300
AI_MAX_SOURCES=8
```

Never expose these as `NEXT_PUBLIC_*` values. After enabling AI for an existing workspace, run:

```bash
php artisan dataroom:reindex-ai
```

The worker generates embeddings asynchronously. Retrieval is filtered through Dataroom node permissions before any source text is sent to the generation provider.
