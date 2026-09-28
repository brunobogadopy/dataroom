<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NodeAccess;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request, NodeAccess $access)
    {
        return Notification::where('user_id',$request->user()->id)
            ->with('node:id,workspace_id,parent_id,name,type,visibility')
            ->latest('created_at')
            ->limit(100)
            ->get()
            ->filter(function($notification)use($request,$access){
                return !$notification->node || $access->canView($request->user(),$notification->node);
            })
            ->values();
    }

    public function unreadCount(Request $request, NodeAccess $access)
    {
        $count=Notification::where('user_id',$request->user()->id)
            ->whereNull('read_at')
            ->with('node:id,workspace_id,parent_id,name,type,visibility')
            ->get()
            ->filter(fn($n)=>!$n->node || $access->canView($request->user(),$n->node))
            ->count();

        return ['count'=>$count];
    }

    public function markRead(Request $request, Notification $notification)
    {
        abort_unless($notification->user_id===$request->user()->id,403);
        if(!$notification->read_at)$notification->update(['read_at'=>now()]);
        return $notification;
    }

    public function markAllRead(Request $request)
    {
        Notification::where('user_id',$request->user()->id)->whereNull('read_at')->update(['read_at'=>now()]);
        return response()->noContent();
    }
}
