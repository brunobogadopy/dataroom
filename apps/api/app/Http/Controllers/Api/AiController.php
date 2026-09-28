<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Services\AiClient;
use App\Services\NodeAccess;
use App\Services\SemanticSearch;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function semanticSearch(Request $request,Workspace $workspace,NodeAccess $access,SemanticSearch $semantic,AiClient $ai)
    {
        abort_unless($access->isMember($request->user(),$workspace),403);
        abort_unless($ai->configured(),503,'AI is not configured.');

        $data=$request->validate(['q'=>['required','string','max:2000']]);
        $results=$semantic->search($request->user(),$workspace,$data['q'],12);

        return ['query'=>$data['q'],'results'=>$results];
    }

    public function ask(Request $request,Workspace $workspace,NodeAccess $access,SemanticSearch $semantic,AiClient $ai)
    {
        abort_unless($access->isMember($request->user(),$workspace),403);
        abort_unless($ai->configured(),503,'AI is not configured.');

        $data=$request->validate(['question'=>['required','string','max:4000']]);
        $sources=$semantic->search(
            $request->user(),
            $workspace,
            $data['question'],
            max(1,(int)config('dataroom.ai.max_sources',8))
        );

        if(!$sources){
            return [
                'question'=>$data['question'],
                'answer'=>'I could not find any accessible Dataroom sources relevant to that question.',
                'sources'=>[],
            ];
        }

        $answer=$ai->answer($data['question'],$sources);

        return [
            'question'=>$data['question'],
            'answer'=>$answer,
            'sources'=>collect($sources)->values()->map(fn($source,$index)=>[
                'label'=>'S'.($index+1),
                'node_id'=>$source['node_id'],
                'type'=>$source['type'],
                'name'=>$source['name'],
                'parent_id'=>$source['parent_id'],
                'snippet'=>mb_substr($source['content'],0,500),
                'score'=>$source['score'],
            ]),
        ];
    }
}
