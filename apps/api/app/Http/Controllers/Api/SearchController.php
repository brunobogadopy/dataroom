<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Models\Workspace;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request, Workspace $workspace)
    {
        abort_unless($workspace->members()->whereKey($request->user()->id)->exists(), 403);
        $q = trim((string) $request->query('q', ''));
        if ($q === '') return ['query' => '', 'results' => []];
        $needle = mb_strtolower($q);
        $results = Node::query()->where('workspace_id', $workspace->id)
            ->where(function ($query) use ($needle) {
                $query->whereRaw('lower(name) like ?', ['%'.$needle.'%'])
                    ->orWhereHas('document', fn ($doc) => $doc->whereRaw('lower(plain_text) like ?', ['%'.$needle.'%']));
            })
            ->with('document:node_id,plain_text')
            ->limit(50)->get()->map(fn (Node $node) => [
                'node_id' => $node->id, 'type' => $node->type, 'name' => $node->name,
                'snippet' => $node->document ? mb_substr($node->document->plain_text ?? '', 0, 220) : null,
            ]);
        return ['query' => $q, 'results' => $results];
    }
}
