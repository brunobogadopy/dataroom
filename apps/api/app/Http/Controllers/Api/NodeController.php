<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Models\Workspace;
use App\Services\SearchIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NodeController extends Controller
{
    private function assertMember(Request $request, Workspace $workspace): void
    {
        abort_unless($workspace->members()->whereKey($request->user()->id)->exists(), 403);
    }

    public function index(Request $request, Workspace $workspace)
    {
        $this->assertMember($request, $workspace);
        $parentId = $request->query('parent_id');

        if ($parentId) {
            abort_unless($workspace->nodes()->whereKey($parentId)->where('type', 'folder')->exists(), 404);
        }

        return $workspace->nodes()
            ->where('parent_id', $parentId)
            ->with(['file.currentVersion'])
            ->orderByRaw("case type when 'folder' then 0 when 'document' then 1 else 2 end")
            ->orderBy('name')
            ->get();
    }

    public function breadcrumbs(Request $request, Workspace $workspace, Node $node)
    {
        $this->assertMember($request, $workspace);
        abort_unless($node->workspace_id === $workspace->id && $node->type === 'folder', 404);

        $crumbs = collect();
        $current = $node;

        while ($current) {
            $crumbs->prepend([
                'id' => $current->id,
                'name' => $current->name,
            ]);
            $current = $current->parent;
        }

        return $crumbs->values();
    }

    public function storeFolder(Request $request, Workspace $workspace)
    {
        $this->assertMember($request, $workspace);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'parent_id' => ['nullable', 'uuid']]);

        if (!empty($data['parent_id'])) {
            abort_unless($workspace->nodes()->whereKey($data['parent_id'])->where('type', 'folder')->exists(), 422);
        }

        $node = $workspace->nodes()->create([
            'parent_id' => $data['parent_id'] ?? null,
            'type' => 'folder',
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(6)),
            'created_by' => $request->user()->id,
        ]);

        return response()->json($node, 201);
    }

    public function destroy(Request $request, Node $node, SearchIndex $index)
    {
        abort_unless($node->workspace->members()->whereKey($request->user()->id)->exists(), 403);
        $node->delete();
        $index->deleteNode($node->id);
        return response()->noContent();
    }
}
