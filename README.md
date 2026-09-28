# Dataroom v0.1

Company knowledge + file storage, built around one core idea: **folders, documents and files live in the same tree**.

## Stack
- Laravel 13 / PHP 8.4 API
- Next.js 16 web app
- PostgreSQL
- Redis queues/cache
- Meilisearch reserved for indexed search
- S3-compatible object storage (MinIO locally; bunny.net Storage in production)

## Current v0.1 flow
1. Register / login with API token auth.
2. Create a workspace.
3. Create folders.
4. Create documents.
5. Browse a unified node tree.
6. Search document names and plain text.

File upload endpoints are intentionally the next milestone because production uploads should go directly from the browser to object storage rather than proxy through PHP.

## Local boot

```bash
# API dependencies
cd apps/api
composer install
cp .env.example .env
php artisan key:generate
cd ../..

# Web dependencies
cd apps/web
npm install
cd ../..

# Infrastructure + apps
docker compose up --build -d

docker compose exec api php artisan migrate
```

Open http://localhost:3000.

## GitHub Container Registry
`.github/workflows/images.yml` publishes:
- `ghcr.io/<owner>/<repo>-api:latest`
- `ghcr.io/<owner>/<repo>-web:latest`

Also configure repository variable `NEXT_PUBLIC_API_URL` before the web image build.

## Magic Containers layout
Recommended first deployment: one single-region app with containers for:
- `web` → port 3000
- `api` → port 8080
- `worker` → same API image, command `php artisan queue:work --sleep=1 --tries=3 --timeout=90`
- PostgreSQL
- Redis
- Meilisearch later

Customer file bytes belong in bunny.net Storage, not on container disk.

## Production environment variables
See `docs/magic-containers.md`.
