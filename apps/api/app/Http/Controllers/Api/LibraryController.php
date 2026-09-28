<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Node;
use App\Models\Workspace;
use App\Services\NodeAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LibraryController extends Controller
{
    public function favorites(Request $request, Workspace $workspace, NodeAccess $access)
    {
        abort_unless($access->isMember($request->user(),$workspace),403);
        $ids=DB::table('favorites')->where('user_id',$request->user()->id)->pluck('node_id');
        return Node::query()->where('workspace_id',$workspace->id)->whereIn('id',$ids)->with(['file.currentVersion'])->get()->filter(fn($n)=>$access->canView($request->user(),$n))->values();
    }

    public function toggleFavorite(Request $request, Node $node, NodeAccess $access)
    {
        abort_unless($access->canView($request->user(),$node),403);
        $q=DB::table('favorites')->where('user_id',$request->user()->id)->where('node_id',$node->id);
        $exists=$q->exists();
        if($exists)$q->delete();
        else DB::table('favorites')->insert(['user_id'=>$request->user()->id,'node_id'=>$node->id,'created_at'=>now(),'updated_at'=>now()]);
        return ['favorite'=>!$exists];
    }

    public function recent(Request $request, Workspace $workspace, NodeAccess $access)
    {
        abort_unless($access->isMember($request->user(),$workspace),403);
        $rows=DB::table('recent_nodes')->where('user_id',$request->user()->id)->orderByDesc('viewed_at')->limit(50)->get()->keyBy('node_id');
        return Node::query()->where('workspace_id',$workspace->id)->whereIn('id',$rows->keys())->with(['file.currentVersion'])->get()->filter(fn($n)=>$access->canView($request->user(),$n))->sortByDesc(fn($n)=>$rows[$n->id]->viewed_at)->values();
    }

    public function trash(Request $request, Workspace $workspace, NodeAccess $access)
    {
        abort_unless($access->isMember($request->user(),$workspace),403);
        return Node::onlyTrashed()->where('workspace_id',$workspace->id)->orderByDesc('deleted_at')->get()->filter(fn($n)=>in_array($access->role($request->user(),$workspace),['owner','admin'],true)||$n->created_by===$request->user()->id)->values();
    }

    public function activity(Request $request, Workspace $workspace, NodeAccess $access)
    {
        abort_unless($access->isMember($request->user(),$workspace),403);
        $items=Activity::where('workspace_id',$workspace->id)->with(['actor:id,name,email','node:id,workspace_id,parent_id,name,type,visibility'])->latest('created_at')->limit(100)->get();

        if(!in_array($access->role($request->user(),$workspace),['owner','admin'],true)){
            $items=$items->filter(fn($item)=>$item->node&&$access->canView($request->user(),$item->node))->values();
        }

        return $items;
    }
}
