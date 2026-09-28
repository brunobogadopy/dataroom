# API v1

All workspace routes below require `Authorization: Bearer <token>`.

## Files

### Upload
`POST /api/v1/workspaces/{workspaceSlug}/files`

`multipart/form-data`:
- `file` required
- `parent_id` optional folder UUID

### File metadata
`GET /api/v1/files/{nodeId}`

### Download
`GET /api/v1/files/{nodeId}/download`

## Search

### Lexical search
`GET /api/v1/workspaces/{workspaceSlug}/search?q=vacation`

Uses Meilisearch with a PostgreSQL fallback and permission filters.

### Semantic search
`GET /api/v1/workspaces/{workspaceSlug}/semantic-search?q=enterprise+refunds`

Returns permission-filtered semantic chunks ranked by cosine similarity from pgvector.

### Ask Dataroom
`POST /api/v1/workspaces/{workspaceSlug}/ask`

JSON:
```json
{"question":"What does our refund policy say about enterprise customers?"}
```

Returns:
- `answer` — grounded generated answer with inline `[S1]` citations
- `sources` — node IDs, names, snippets and semantic scores for each cited context candidate

Only chunks from nodes the requesting user can currently view are sent to the generation provider.
