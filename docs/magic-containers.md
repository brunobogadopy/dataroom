# Magic Containers deployment notes

## Containers

### web
Image: `ghcr.io/<owner>/<repo>-web:latest`
Port: `3000`
Public hostname: e.g. `app.example.com`

Build-time variable:
`NEXT_PUBLIC_API_URL=https://api.example.com/api/v1`

### api
Image: `ghcr.io/<owner>/<repo>-api:latest`
Port: `8080`
Public hostname: e.g. `api.example.com`

Required environment:
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

FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=<storage key>
AWS_SECRET_ACCESS_KEY=<storage secret>
AWS_DEFAULT_REGION=<region>
AWS_BUCKET=<storage zone/bucket mapping>
AWS_ENDPOINT=<S3-compatible endpoint>
AWS_USE_PATH_STYLE_ENDPOINT=false
```

Run database migrations once for each release that contains schema changes:
`php artisan migrate --force`

### worker
Use the same API image and environment, but override the command:
`php artisan queue:work --sleep=1 --tries=3 --timeout=90`

### postgres
Use a persistent volume. Start single-region while the application is stateful.

### redis
Used for cache and queues. Persistence is optional for the first MVP if jobs can be recreated, but production queue semantics should be reviewed before launch.

## Health check
API health endpoint: `/up`

## Files
Do not store customer uploads on the API container filesystem. The upcoming upload flow will issue direct-to-storage upload instructions and only persist metadata in PostgreSQL.
