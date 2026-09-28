<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Models\NodePermission;
use App\Models\Workspace;
use App\Services\NodeAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NodePermissionController extends Controller
{
    public function show(Request $request, Workspace $workspace, Node $node, NodeAccess $access)
    {
        abort_unless($node->workspace_id === $workspace->id, 404);
        abort_unless($access->canManageWorkspace($request->user(), $workspace), 403);

        return [
            'node_id' => $node->id,
            'visibility' => $node->visibility,
            'permissions' => $node->permissions()
                ->with('user:id,name,email')
                ->get()
                ->map(fn ($permission) => [
                    'id' => $permission->id,
                    'user_id' => $permission->user_id,
                    'permission' => $permission->permission,
                    'user' => $permission->user,
                ]),
        ];
    }

    public function update(Request $request, Workspace $workspace, Node $node, NodeAccess $access)
    {
        abort_unless($node->workspace_id === $workspace->id, 404);
        abort_unless($access->canManageWorkspace($request->user(), $workspace), 403);

        $data = $request->validate([
            'visibility' => ['required', Rule::in(['workspace', 'restricted'])],
            'grants' => ['array'],
            'grants.*.user_id' => ['required', 'uuid'],
            'grants.*.permission' => ['required', Rule::in(['view', 'edit'])],
        ]);

        $node->update(['visibility' => $data['visibility']]);

        NodePermission::where('node_id', $node->id)->delete();

        if ($data['visibility'] === 'restricted') {
            foreach ($data['grants'] ?? [] as $grant) {
                abort_unless($workspace->members()->whereKey($grant['user_id'])->exists(), 422, 'Permission user must belong to the workspace.');

                NodePermission::create([
                    'workspace_id' => $workspace->id,
                    'node_id' => $node->id,
                    'user_id' => $grant['user_id'],
                    'permission' => $grant['permission'],
                ]);
            }
        }

        return $this->show($request, $workspace, $node->fresh(), $access);
    }
}
