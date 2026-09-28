<?php

namespace App\Services;

use App\Models\Node;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class SemanticSearch
{
    public function __construct(private AiClient $ai,private NodeAccess $access) {}

    public function search(User $user,Workspace $workspace,string $query,int $limit=8): array
    {
        $embedding=$this->ai->embed([$query])[0];
        $vector=Vector::literal($embedding);
        $candidateLimit=max($limit*8,40);

        $rows=DB::select(
            'SELECT sc.id, sc.node_id, sc.chunk_index, sc.content,
                    1 - (sc.embedding <=> ?::vector) AS score
             FROM semantic_chunks sc
             WHERE sc.workspace_id = ?
             ORDER BY sc.embedding <=> ?::vector
             LIMIT ?',
            [$vector,$workspace->id,$vector,$candidateLimit]
        );

        $nodeIds=collect($rows)->pluck('node_id')->unique()->values();
        $nodes=Node::whereIn('id',$nodeIds)->get()->keyBy('id');

        $results=[];
        foreach($rows as $row){
            $node=$nodes->get($row->node_id);
            if(!$node||!$this->access->canView($user,$node))continue;

            $results[]=[
                'chunk_id'=>$row->id,
                'node_id'=>$node->id,
                'type'=>$node->type,
                'name'=>$node->name,
                'parent_id'=>$node->parent_id,
                'chunk_index'=>(int)$row->chunk_index,
                'content'=>$row->content,
                'score'=>(float)$row->score,
            ];

            if(count($results)>=$limit)break;
        }

        return $results;
    }
}
