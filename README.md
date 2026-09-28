# Atlas Starter

A company knowledge + file storage platform starter architecture.

## Product thesis
Articles and files live in the same hierarchical tree. Users should not have to decide whether knowledge belongs in a wiki or a drive.

## Local services
- PostgreSQL
- Redis
- Meilisearch
- MinIO (S3-compatible object storage)
- Mailpit

## Core domain
- Workspace
- Membership
- Group
- Node (`folder`, `document`, `file`)
- Document revision
- File version
- Permission
- Favorite
- Activity

## First milestone
1. Register/sign in
2. Create workspace
3. Create folder
4. Create article
5. Upload a PDF/file
6. Search once and receive article + file matches

See `docs/architecture.md`, `docs/database.md`, `docs/permissions.md`, and `docs/api.md`.
