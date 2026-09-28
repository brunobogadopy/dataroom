# Dataroom v0.2

Company knowledge + file storage built around one core idea: **folders, documents and files live in the same tree**.

## Stack
- Laravel 13 / PHP 8.4 API
- Next.js 16 web app
- PostgreSQL
- Redis queues/cache
- Meilisearch with safe PostgreSQL search fallback
- bunny.net Storage for customer file bytes

## v0.2 flow
1. Register / login.
2. Create a workspace.
3. Create folders and documents.
4. Drag/drop or select a file.
5. Laravel validates membership, quota and file size, then uploads the object to bunny.net Storage using a server-side Storage Zone key.
6. PostgreSQL stores the file node + immutable file version metadata.
7. A Redis worker downloads the object temporarily, extracts text from PDF/DOCX/text formats and indexes the node.
8. Search returns documents and uploaded files from the same endpoint.
9. Authenticated downloads are proxied by the API for now; a later release can replace this with short-lived delivery URLs.

## Important security decision
The bunny.net Storage Zone `AccessKey` is **never sent to the browser**. v0.2 intentionally proxies upload through the authenticated API because exposing a write-capable zone key client-side would let users bypass Dataroom authorization and quotas.

## Local boot

```bash
cd apps/api
composer install
cp .env.example .env
php artisan key:generate
cd ../..

cd apps/web
npm install
cd ../..

docker compose up --build -d
docker compose exec api php artisan migrate
```

Set `BUNNY_STORAGE_ZONE` and `BUNNY_STORAGE_ACCESS_KEY` in the API environment before testing uploads.

Open http://localhost:3000.

## File extraction
Currently indexed:
- PDF via `pdftotext` (included in the API Docker image)
- DOCX via Zip/XML extraction
- TXT / Markdown / CSV / JSON / XML and other `text/*`

Other files are stored normally but marked `unsupported` for full-text extraction.

## GitHub Container Registry
`.github/workflows/images.yml` publishes:
- `ghcr.io/<owner>/<repo>-api:latest`
- `ghcr.io/<owner>/<repo>-web:latest`

Configure repository variable `NEXT_PUBLIC_API_URL` before the web image build.

## Magic Containers
Recommended first deployment: a **single-region** app with:
- `web` → port 3000
- `api` → port 8080
- `worker` → same API image with queue worker command
- PostgreSQL + persistent volume
- Redis
- Meilisearch + persistent volume

Customer file bytes live in bunny.net Storage, not container volumes.

See `docs/magic-containers.md`.
