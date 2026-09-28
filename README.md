# Dataroom v0.5

Company knowledge + file storage built around one core idea: **folders, documents and files live in the same tree**.

## v0.5: history, recovery and daily workflow

### Document history
Every content save stores the previous document body as an immutable revision. Users with edit access can open version history and restore an older revision without losing the current state.

### File versions
Existing files can receive new versions without changing their node identity or permissions. Every version keeps its own object key, checksum, MIME type, size and extraction status.

### Library
Each workspace now exposes:
- **Favorites** — personal starred nodes
- **Recent** — recently opened folders, documents and files
- **Trash** — recoverable soft-deleted nodes
- **Activity** — workspace actions, permission-filtered for non-admin users

### Trash
Editable content can be moved to trash. Restoring requires the parent folder to exist. Permanent deletion is limited to owners/admins, refuses non-empty folders, and removes stored file objects from bunny.net Storage.

### Read-only UX
The API now returns per-node edit capability so viewers and view-only grants do not see editing/version-upload controls that would fail server authorization.

## CI
Pull requests to `main` now run:
- Composer validation + install
- PHP syntax checks
- Laravel route boot check
- npm install
- TypeScript validation
- Next.js production build

## Upgrade

```bash
php artisan migrate --force
```

No additional infrastructure service is required for v0.5.
