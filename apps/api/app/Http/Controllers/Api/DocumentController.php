<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Node;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function store(Request $request, Workspace $workspace)
    {
        abort_unless($workspace->members()->whereKey($request->user()->id)->exists(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'parent_id' => ['nullable', 'uuid'],
            'content_json' => ['nullable', 'array'], 'plain_text' => ['nullable', 'string'],
        ]);
        return DB::transaction(function () use ($request, $workspace, $data) {
            $node = $workspace->nodes()->create([
                'parent_id' => $data['parent_id'] ?? null, 'type' => 'document', 'name' => $data['name'],
                'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(6)), 'created_by' => $request->user()->id,
            ]);
            $doc = Document::create([
                'node_id' => $node->id, 'content_json' => $data['content_json'] ?? ['type' => 'doc', 'content' => []],
                'plain_text' => $data['plain_text'] ?? '', 'status' => 'draft', 'owner_user_id' => $request->user()->id, 'version' => 1,
            ]);
            return response()->json($node->load('document'), 201);
        });
    }

    public function show(Request $request, Node $node)
    {
        abort_unless($node->type === 'document', 404);
        abort_unless($node->workspace->members()->whereKey($request->user()->id)->exists(), 403);
        return $node->load('document');
    }

    public function update(Request $request, Node $node)
    {
        abort_unless($node->type === 'document', 404);
        abort_unless($node->workspace->members()->whereKey($request->user()->id)->exists(), 403);
        $data = $request->validate(['name' => ['sometimes', 'string', 'max:255'], 'content_json' => ['sometimes', 'array'], 'plain_text' => ['sometimes', 'string']]);
        if (isset($data['name'])) $node->update(['name' => $data['name']]);
        $doc = $node->document;
        $doc->fill(array_intersect_key($data, array_flip(['content_json', 'plain_text'])));
        if (array_key_exists('content_json', $data) || array_key_exists('plain_text', $data)) $doc->version++;
        $doc->save();
        return $node->fresh()->load('document');
    }
}
