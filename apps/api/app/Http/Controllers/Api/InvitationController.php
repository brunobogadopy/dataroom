<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Services\NodeAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InvitationController extends Controller
{
    public function index(Request $request, Workspace $workspace, NodeAccess $access)
    {
        abort_unless($access->canManageWorkspace($request->user(),$workspace),403);
        return $workspace->invitations()->whereNull('accepted_at')->where('expires_at','>',now())->latest()->get(['id','email','role','expires_at','created_at']);
    }

    public function store(Request $request, Workspace $workspace, NodeAccess $access)
    {
        abort_unless($access->canManageWorkspace($request->user(),$workspace),403);
        $data=$request->validate(['email'=>['required','email','max:255'],'role'=>['required',Rule::in(['admin','member','viewer'])]]);
        $email=mb_strtolower($data['email']);
        abort_if($workspace->members()->whereRaw('lower(users.email) = ?',[$email])->exists(),422,'This user is already a workspace member.');

        $plainToken=Str::random(64);
        WorkspaceInvitation::where('workspace_id',$workspace->id)->whereRaw('lower(email) = ?',[$email])->whereNull('accepted_at')->delete();
        $invite=WorkspaceInvitation::create(['workspace_id'=>$workspace->id,'email'=>$email,'role'=>$data['role'],'token_hash'=>hash('sha256',$plainToken),'invited_by'=>$request->user()->id,'expires_at'=>now()->addDays(7)]);

        return response()->json(['id'=>$invite->id,'email'=>$invite->email,'role'=>$invite->role,'expires_at'=>$invite->expires_at,'token'=>$plainToken],201);
    }

    public function accept(Request $request, string $token)
    {
        $invite=WorkspaceInvitation::where('token_hash',hash('sha256',$token))->whereNull('accepted_at')->where('expires_at','>',now())->firstOrFail();
        abort_unless(mb_strtolower($request->user()->email)===mb_strtolower($invite->email),403,'This invitation belongs to a different email address.');
        $invite->workspace->members()->syncWithoutDetaching([$request->user()->id=>['role'=>$invite->role]]);
        $invite->update(['accepted_at'=>now()]);
        return response()->json(['workspace'=>$invite->workspace,'role'=>$invite->role]);
    }

    public function destroy(Request $request, Workspace $workspace, WorkspaceInvitation $invitation, NodeAccess $access)
    {
        abort_unless($access->canManageWorkspace($request->user(),$workspace),403);
        abort_unless($invitation->workspace_id===$workspace->id,404);
        $invitation->delete();return response()->noContent();
    }
}
