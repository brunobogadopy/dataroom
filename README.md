# Dataroom v0.3

Company knowledge + file storage built around one core idea: **folders, documents and files live in the same tree**.

## Stack
- Laravel 13 / PHP 8.4 API
- Next.js 16 web app
- Tiptap 3 rich-text editor
- PostgreSQL
- Redis queues/cache
- Meilisearch with PostgreSQL fallback
- bunny.net Storage for customer file bytes

## v0.3 product flow
1. Register / login and enter a workspace.
2. Browse the unified content tree.
3. Open folders and navigate through breadcrumb paths.
4. Create a document inside the current folder.
5. Write rich text with headings, bold/italic, lists, quotes and undo/redo.
6. Changes autosave back to Laravel and are re-indexed for search.
7. Upload files into the current folder.
8. Open supported files in an authenticated preview view.
9. Search and jump directly into a folder, document or file.

## File previews
Inline preview currently supports:
- PDF
- images
- text MIME types

DOCX and other unsupported browser formats still show metadata, extracted text when available, and a Download action.

## Security
The bunny.net Storage Zone `AccessKey` remains server-side only. Browser previews and downloads go through authenticated API endpoints in v0.3.

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

Set `BUNNY_STORAGE_ZONE` and `BUNNY_STORAGE_ACCESS_KEY` before testing file upload/preview.

Open http://localhost:3000.

## GitHub Container Registry
`.github/workflows/images.yml` publishes:
- `ghcr.io/<owner>/<repo>-api:latest`
- `ghcr.io/<owner>/<repo>-web:latest`

Configure repository variable `NEXT_PUBLIC_API_URL` before the web image build.

## Magic Containers
Recommended first deployment remains a single-region application with:
- `web` → port 3000
- `api` → port 8080
- `worker` → same API image with queue worker command
- PostgreSQL + persistent volume
- Redis
- Meilisearch + persistent volume

Customer file bytes live in bunny.net Storage, not container volumes.
