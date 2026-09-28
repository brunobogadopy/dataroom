# Dataroom v0.7

Dataroom combines company knowledge, documents and file storage in one permission-aware hierarchy.

## v0.7: semantic search + Ask Dataroom

### Semantic indexing
Documents and extracted file text are split into overlapping chunks and embedded asynchronously by the queue worker.

Embeddings are stored in PostgreSQL using **pgvector**.

Whenever:
- a document is created or edited,
- a document version is restored,
- a file finishes text extraction,
- or a new file version finishes extraction,

Dataroom schedules semantic reindexing for that node.

### Ask Dataroom
Users can ask natural-language questions across the workspace.

The request flow is:

1. Embed the user's question.
2. Retrieve the closest chunks with pgvector cosine similarity.
3. Load the corresponding nodes.
4. Apply Dataroom permissions to every candidate.
5. Send only authorized source chunks to the generation provider.
6. Return a grounded answer with inline source labels such as `[S1]`.
7. Return clickable source cards alongside the answer.

If no permitted source is relevant, Dataroom returns a no-source answer instead of inventing information.

### Prompt-injection boundary
Retrieved document/file text is wrapped as untrusted source data. The generation prompt explicitly forbids following instructions, role changes or commands contained inside source documents.

### Provider configuration
AI is server-side and provider-configurable through full endpoint URLs:

```env
AI_API_KEY=
AI_EMBEDDINGS_URL=
AI_RESPONSES_URL=
AI_EMBEDDING_MODEL=
AI_GENERATION_MODEL=
AI_EMBEDDING_DIMENSIONS=1536
AI_CHUNK_CHARS=2400
AI_CHUNK_OVERLAP_CHARS=300
AI_MAX_SOURCES=8
```

The generation endpoint is expected to accept an OpenAI Responses-style request containing `model`, `instructions`, `input` and `max_output_tokens`.

Never expose AI credentials through `NEXT_PUBLIC_*` variables.

### pgvector
Local Docker now uses:

```text
pgvector/pgvector:pg17
```

Production PostgreSQL must also have the `vector` extension available.

### Upgrade

```bash
php artisan migrate --force
php artisan dataroom:reindex-ai
```

The second command queues existing documents/files for embedding. New or edited content is indexed automatically.

## Existing foundation
- unified folders/documents/files
- rich-text documents
- bunny.net Storage
- file extraction and previews
- lexical search
- workspace roles and restricted nodes
- document/file versioning
- favorites, recent, trash and activity
- comments, mentions and notifications
- CI on pull requests
