<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Models\Workspace;
use App\Services\NodeAccess;
use App\Services\SearchIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NodeController extends Controller
{
    public function index(Request $request, Workspace $workspace, NodeAccess $access)
    {
        abort_unless($access->isMember($request->user(), $workspace), 403);
        $parentId=$request->query('parent_id'); $parent=null;
        if($parentId){$parent=$workspace->nodes()->whereKey($parentId)->where('type','folder')->firstOrFail();abort_unless($access->canView($request->user(),$parent),403);}
        return $workspace->nodes()->where('parent_id',$parentId)->with(['file.currentVersion'])->orderByRaw("case type when 'folder' then 0 when 'document' then 1 else 2 end")->orderBy('name')->get()->filter(fn($n)=>$access->canView($request->user(),$n))->values();
    }

    public function breadcrumbs(Request $request, Workspace $workspace, Node $node, NodeAccess $access)
    {
        abort_unless($node->workspace_id===$workspace->id && $node->type==='folder',404);
        abort_unless($access->canView($request->user(),$node),403);
        $crumbs=collect();$current=$node;
        while($current){$crumbs->prepend(['id'=>$current->id,'name'=>$current->name]);$current=$current->parent;}
        return $crumbs->values();
    }

    public function storeFolder(Request $request, Workspace $workspace, NodeAccess $access)
    {
        $data=$request->validate(['name'=>['required','string','max:255'],'parent_id'=>['nullable','uuid']]);
        $parent=!empty($data['parent_id'])?$workspace->nodes()->whereKey($data['parent_id'])->where('type','folder')->firstOrFail():null;
        abort_unless($access->canCreateIn($request->user(),$workspace,$parent),403);
        $node=$workspace->nodes()->create(['parent_id'=>$parent?->id,'type'=>'folder','name'=>$data['name'],'slug'=>Str::slug($data['name']).'-'.Str::lower(Str::random(6)),'created_by'=>$request->user()->id]);
        return response()->json($node,201);
    }

    public function destroy(Request $request, Node $node, SearchIndex $index, NodeAccess $access)
    {
        abort_unless($access->canEdit($request->user(),$node),403);
        $node->delete();$index->deleteNode($node->id);return response()->noContent();
    }
}
