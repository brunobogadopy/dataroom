<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Node;
use App\Models\Notification;
use App\Services\ActivityLogger;
use App\Services\MentionParser;
use App\Services\NodeAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    public function index(Request $request, Node $node, NodeAccess $access)
    {
        abort_unless($access->canView($request->user(),$node),403);

        return Comment::where('node_id',$node->id)
            ->with(['user:id,name,email','mentions:id,name,email'])
            ->oldest('created_at')
            ->get();
    }

    public function store(Request $request, Node $node, NodeAccess $access, MentionParser $mentions, ActivityLogger $activity)
    {
        abort_unless($access->canView($request->user(),$node),403);
        $data=$request->validate(['body'=>['required','string','max:10000']]);

        $comment=DB::transaction(function()use($request,$node,$data,$mentions,$access){
            $comment=Comment::create([
                'workspace_id'=>$node->workspace_id,
                'node_id'=>$node->id,
                'user_id'=>$request->user()->id,
                'body'=>$data['body'],
            ]);

            $mentioned=$mentions->users($data['body'],$node->workspace)
                ->filter(fn($user)=>$user->id!==$request->user()->id && $access->canView($user,$node))
                ->values();

            if($mentioned->isNotEmpty()){
                $comment->mentions()->sync($mentioned->pluck('id'));
                foreach($mentioned as $user){
                    Notification::create([
                        'user_id'=>$user->id,
                        'workspace_id'=>$node->workspace_id,
                        'node_id'=>$node->id,
                        'type'=>'mention',
                        'data'=>[
                            'comment_id'=>$comment->id,
                            'actor_name'=>$request->user()->name,
                            'node_name'=>$node->name,
                            'node_type'=>$node->type,
                            'workspace_slug'=>$node->workspace->slug,
                            'body'=>mb_substr($data['body'],0,280),
                        ],
                        'created_at'=>now(),
                    ]);
                }
            }

            return $comment;
        });

        $activity->log($node->workspace,$request->user(),'comment.created',$node,['comment_id'=>$comment->id]);

        return response()->json($comment->load(['user:id,name,email','mentions:id,name,email']),201);
    }

    public function destroy(Request $request, Comment $comment, NodeAccess $access, ActivityLogger $activity)
    {
        $node=$comment->node;
        abort_unless($access->canView($request->user(),$node),403);
        $role=$access->role($request->user(),$node->workspace);
        abort_unless($comment->user_id===$request->user()->id || in_array($role,['owner','admin'],true),403);

        $id=$comment->id;
        $comment->delete();
        $activity->log($node->workspace,$request->user(),'comment.deleted',$node,['comment_id'=>$id]);

        return response()->noContent();
    }
}
