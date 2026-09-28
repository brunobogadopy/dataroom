<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        $dimensions=(int)config('dataroom.ai.embedding_dimensions',1536);

        DB::statement("
            CREATE TABLE semantic_chunks (
                id uuid PRIMARY KEY,
                workspace_id uuid NOT NULL REFERENCES workspaces(id) ON DELETE CASCADE,
                node_id uuid NOT NULL REFERENCES nodes(id) ON DELETE CASCADE,
                chunk_index integer NOT NULL,
                content text NOT NULL,
                content_hash varchar(64) NOT NULL,
                embedding vector({$dimensions}) NOT NULL,
                embedding_model varchar(120) NOT NULL,
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                UNIQUE (node_id, chunk_index)
            )
        ");

        DB::statement('CREATE INDEX semantic_chunks_workspace_idx ON semantic_chunks(workspace_id)');
        DB::statement('CREATE INDEX semantic_chunks_node_idx ON semantic_chunks(node_id)');
        DB::statement('CREATE INDEX semantic_chunks_embedding_idx ON semantic_chunks USING hnsw (embedding vector_cosine_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('semantic_chunks');
    }
};
