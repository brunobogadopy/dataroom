<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\IndexNodeEmbeddings;
use App\Models\Document;
use App\Models\DocumentRevision;
use App\Models\Node;
use App\Models\Workspace;
use App\Services\ActivityLogger;
use App\Services\NodeAccess;
use App\Services\RecentTracker;
use App\Services\SearchIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function store(Request $request, Workspace $workspace, SearchIndex $index, NodeAccess $access, ActivityLogger $activity)
    {
        $data=$request->validate(['name'=>['required','string','max:255'],'parent_id'=>['nullable','uuid'],'content_json'=>['nullable','array'],'plain_text'=>['nullable','string']]);
        $parent=!empty($data['parent_id'])?$workspace->nodes()->whereKey($data['parent_id'])->where('type','folder')->firstOrFail():null;
        abort_unless($access->canCreateIn($request->user(),$workspace,$parent),403);
        $created=DB::transaction(function()use($request,$workspace,$data,$parent){
            $node=$workspace->nodes()->create(['parent_id'=>$parent?->id,'type'=>'document','name'=>$data['name'],'slug'=>Str::slug($data['name']).'-'.Str::lower(Str::random(6)),'created_by'=>$request->user()->id]);
            Document::create(['node_id'=>$node->id,'content_json'=>$data['content_json']??['type'=>'doc','content'=>[]],'plain_text'=>$data['plain_text']??'','status'=>'draft','owner_user_id'=>$request->user()->id,'version'=>1]);
            return $node->load('document');
        });
        $index->indexNode($created);
        IndexNodeEmbeddings::dispatch($created->id);
        $activity->log($workspace,$request->user(),'document.created',$created);
        return response()->json($created,201);
    }

    public function show(Request $request, Node $node, NodeAccess $access, RecentTracker $recent)
    {
        abort_unless($node->type==='document',404);abort_unless($access->canView($request->user(),$node),403);
        $recent->touch($request->user(),$node);$node->load('document');$node->setAttribute('can_edit',$access->canEdit($request->user(),$node));return $node;
    }

    public function update(Request $request, Node $node, SearchIndex $index, NodeAccess $access, ActivityLogger $activity)
    {
        abort_unless($node->type==='document',404);abort_unless($access->canEdit($request->user(),$node),403);
        $data=$request->validate(['name'=>['sometimes','string','max:255'],'content_json'=>['sometimes','array'],'plain_text'=>['sometimes','string']]);
        $contentChanged=array_key_exists('content_json',$data)||array_key_exists('plain_text',$data);$titleChanged=isset($data['name'])&&$data['name']!==$node->name;

        DB::transaction(function()use($request,$node,$data,$contentChanged){
            if(isset($data['name']))$node->update(['name'=>$data['name']]);
            $doc=$node->document;
            if($contentChanged){
                DocumentRevision::create(['node_id'=>$node->id,'version'=>$doc->version,'content_json'=>$doc->content_json,'plain_text'=>$doc->plain_text,'created_by'=>$request->user()->id,'created_at'=>now()]);
                $doc->fill(array_intersect_key($data,array_flip(['content_json','plain_text'])));$doc->version++;$doc->save();
            }
        });

        $fresh=$node->fresh()->load('document');$fresh->setAttribute('can_edit',true);$index->indexNode($fresh);
        if($contentChanged)IndexNodeEmbeddings::dispatch($fresh->id);
        if($contentChanged)$activity->log($node->workspace,$request->user(),'document.edited',$fresh,['version'=>$fresh->document->version]);
        elseif($titleChanged)$activity->log($node->workspace,$request->user(),'node.renamed',$fresh);
        return $fresh;
    }
}
