<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Services\ActivityLogger;
use App\Services\BunnyStorage;
use App\Services\NodeAccess;
use App\Services\SearchIndex;
use Illuminate\Http\Request;
use Throwable;

class TrashController extends Controller
{
    public function restore(Request $request, string $nodeId, NodeAccess $access, SearchIndex $index, ActivityLogger $activity)
    {
        $node=Node::onlyTrashed()->findOrFail($nodeId);
        abort_unless(in_array($access->role($request->user(),$node->workspace),['owner','admin'],true)||$node->created_by===$request->user()->id,403);

        if($node->parent_id){
            abort_unless(Node::withTrashed()->whereKey($node->parent_id)->whereNull('deleted_at')->exists(),422,'Restore the parent folder first.');
        }

        $node->restore();$fresh=$node->fresh();
        $index->indexNode($fresh);
        $activity->log($fresh->workspace,$request->user(),'node.restored',$fresh);
        return $fresh;
    }

    public function forceDelete(Request $request, string $nodeId, NodeAccess $access, ActivityLogger $activity, BunnyStorage $storage)
    {
        $node=Node::onlyTrashed()->findOrFail($nodeId);
        abort_unless(in_array($access->role($request->user(),$node->workspace),['owner','admin'],true),403);
        abort_if(Node::withTrashed()->where('parent_id',$node->id)->exists(),422,'Delete or move child items first.');

        $workspace=$node->workspace;$name=$node->name;
        if($node->type==='file'){
            $node->load('file.versions');
            foreach($node->file?->versions??[] as $version){
                try{$storage->delete($version->object_key);}catch(Throwable $e){report($e);}
            }
        }

        $node->forceDelete();
        $activity->log($workspace,$request->user(),'node.deleted_permanently',null,['name'=>$name,'node_id'=>$nodeId]);
        return response()->noContent();
    }
}
