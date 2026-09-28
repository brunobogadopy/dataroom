<?php

namespace App\Jobs;

use App\Models\Node;
use App\Services\AiClient;
use App\Services\TextChunker;
use App\Services\Vector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IndexNodeEmbeddings implements ShouldQueue
{
    use Queueable;

    public int $timeout=300;
    public int $tries=3;

    public function __construct(public string $nodeId) {}

    public function handle(AiClient $ai,TextChunker $chunker): void
    {
        if(!$ai->configured())return;

        $node=Node::with(['document','file.currentVersion'])->find($this->nodeId);
        if(!$node)return;

        $text=match($node->type){
            'document'=>$node->document?->plain_text??'',
            'file'=>$node->file?->currentVersion?->extracted_text??'',
            default=>'',
        };

        $chunks=$chunker->chunks($text);

        if(!$chunks){
            DB::table('semantic_chunks')->where('node_id',$node->id)->delete();
            return;
        }

        $embeddings=$ai->embed($chunks);
        $model=(string)config('dataroom.ai.embedding_model');

        DB::transaction(function()use($node,$chunks,$embeddings,$model){
            DB::table('semantic_chunks')->where('node_id',$node->id)->delete();

            foreach($chunks as $i=>$content){
                DB::insert(
                    'INSERT INTO semantic_chunks (id,workspace_id,node_id,chunk_index,content,content_hash,embedding,embedding_model,created_at,updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?::vector, ?, now(), now())',
                    [
                        (string)Str::uuid(),
                        $node->workspace_id,
                        $node->id,
                        $i,
                        $content,
                        hash('sha256',$content),
                        Vector::literal($embeddings[$i]),
                        $model,
                    ]
                );
            }
        });
    }
}
