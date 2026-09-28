<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Services\NodeAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function index(Request $request, Workspace $workspace, NodeAccess $access)
    {
        abort_unless($access->isMember($request->user(), $workspace), 403);

        return $workspace->members()
            ->select('users.id', 'users.name', 'users.email')
            ->orderBy('users.name')
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->pivot->role,
            ]);
    }

    public function update(Request $request, Workspace $workspace, string $userId, NodeAccess $access)
    {
        abort_unless($access->canManageWorkspace($request->user(), $workspace), 403);
        abort_if($workspace->owner_user_id === $userId, 422, 'The workspace owner role cannot be changed.');

        $data = $request->validate([
            'role' => ['required', Rule::in(['admin', 'member', 'viewer'])],
        ]);

        abort_unless($workspace->members()->whereKey($userId)->exists(), 404);
        $workspace->members()->updateExistingPivot($userId, ['role' => $data['role']]);

        return response()->json(['user_id' => $userId, 'role' => $data['role']]);
    }

    public function destroy(Request $request, Workspace $workspace, string $userId, NodeAccess $access)
    {
        abort_unless($access->canManageWorkspace($request->user(), $workspace), 403);
        abort_if($workspace->owner_user_id === $userId, 422, 'The workspace owner cannot be removed.');

        $workspace->members()->detach($userId);

        return response()->noContent();
    }
}
