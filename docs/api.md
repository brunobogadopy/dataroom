# API v1

Base path: `/api/v1`

## Workspaces
- `POST /workspaces`
- `GET /workspaces/{workspace}`
- `GET /workspaces/{workspace}/members`
- `POST /workspaces/{workspace}/invitations`

## Nodes
- `GET /workspaces/{workspace}/nodes?parent_id=`
- `POST /workspaces/{workspace}/folders`
- `PATCH /nodes/{node}`
- `DELETE /nodes/{node}`
- `POST /nodes/{node}/restore`

## Documents
- `POST /workspaces/{workspace}/documents`
- `GET /documents/{node}`
- `PUT /documents/{node}`
- `GET /documents/{node}/revisions`
- `POST /documents/{node}/revisions/{revision}/restore`

## Files
- `POST /workspaces/{workspace}/uploads/presign`
- `POST /workspaces/{workspace}/files/complete`
- `GET /files/{node}/download`
- `GET /files/{node}/versions`

## Search
- `GET /workspaces/{workspace}/search?q=`

Search response shape:
```json
{
  "query": "contract",
  "results": [
    {"node_id":"...","type":"document","name":"Contract Policy","snippet":"..."},
    {"node_id":"...","type":"file","name":"contract.pdf","snippet":"..."}
  ]
}
```
