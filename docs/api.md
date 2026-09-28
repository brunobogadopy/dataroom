# API v1

All workspace routes below require `Authorization: Bearer <token>`.

## Files

### Upload
`POST /api/v1/workspaces/{workspaceSlug}/files`

`multipart/form-data`:
- `file` required
- `parent_id` optional folder UUID

The endpoint checks workspace membership and quota, uploads bytes to bunny.net Storage and returns a unified `file` node. Text extraction is asynchronous.

### File metadata
`GET /api/v1/files/{nodeId}`

Returns the file node, current version, MIME type, size and extraction status.

### Download
`GET /api/v1/files/{nodeId}/download`

Authenticated API-proxied download.

## Search
`GET /api/v1/workspaces/{workspaceSlug}/search?q=vacation`

Searches folder/file names plus document text and extracted file text. Uses Meilisearch when its workspace filter is configured and healthy; otherwise safely falls back to PostgreSQL.
