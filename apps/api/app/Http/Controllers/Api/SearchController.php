<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Models\Workspace;
use App\Services\NodeAccess;
use App\Services\SearchIndex;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request, Workspace $workspace, SearchIndex $search, NodeAccess $access)
    {
        abort_unless($access->isMember($request->user(),$workspace),403);
        $q=trim((string)$request->query('q',''));if($q==='')return ['query'=>'','results'=>[]];
        $hits=$search->search($workspace->id,$q);
        if($hits!==null){
            $ids=collect($hits)->pluck('id')->all();$nodes=Node::whereIn('id',$ids)->get()->keyBy('id');
            $results=collect($hits)->filter(fn($h)=>isset($nodes[$h['id']])&&$access->canView($request->user(),$nodes[$h['id']]))->map(fn($h)=>['node_id'=>$h['id'],'type'=>$h['type'],'name'=>$h['name'],'snippet'=>data_get($h,'_formatted.content')?:mb_substr((string)($h['content']??''),0,240)])->values();
            return ['query'=>$q,'engine'=>'meilisearch','results'=>$results];
        }
        $needle=mb_strtolower($q);
        $nodes=Node::query()->where('workspace_id',$workspace->id)->where(function($query)use($needle){$query->whereRaw('lower(name) like ?',['%'.$needle.'%'])->orWhereHas('document',fn($d)=>$d->whereRaw('lower(plain_text) like ?',['%'.$needle.'%']))->orWhereHas('file.currentVersion',fn($v)=>$v->whereRaw('lower(extracted_text) like ?',['%'.$needle.'%']));})->with(['document:node_id,plain_text','file.currentVersion'])->limit(100)->get()->filter(fn($n)=>$access->canView($request->user(),$n))->take(50);
        return ['query'=>$q,'engine'=>'postgres','results'=>$nodes->map(fn(Node $n)=>['node_id'=>$n->id,'type'=>$n->type,'name'=>$n->name,'snippet'=>$n->type==='document'?mb_substr($n->document?->plain_text??'',0,220):($n->type==='file'?mb_substr($n->file?->currentVersion?->extracted_text??'',0,220):null)])->values()];
    }
}
