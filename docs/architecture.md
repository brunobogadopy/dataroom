# Architecture

## Shape
Start as a modular monolith.

```text
Browser
  |
  v
Next.js web app
  |
  v
Laravel API ----------------------+
  |             |                 |
  v             v                 v
PostgreSQL    Redis             Meilisearch
                                ^
                                |
                         indexing workers
                                ^
                                |
Browser ---- presigned upload --> MinIO/S3
```

## Principles
1. `folder`, `document`, and `file` are nodes in one tree.
2. Every tenant-owned row carries `workspace_id`.
3. File bytes never proxy through the app server in normal uploads.
4. Search results are permission filtered.
5. Documents keep immutable revisions.
6. Files keep immutable versions.
7. Soft delete first; destructive cleanup happens asynchronously.
8. Audit-friendly events are emitted for security-relevant actions.
