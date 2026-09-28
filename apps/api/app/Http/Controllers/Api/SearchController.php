<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Models\Workspace;
use App\Services\SearchIndex;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request, Workspace $workspace, SearchIndex $search)
    {
        abort_unless($workspace->members()->whereKey($request->user()->id)->exists(), 403);
        $q = trim((string) $request->query('q', ''));
        if ($q === '') return ['query' => '', 'results' => []];

        $hits = $search->search($workspace->id, $q);
        if ($hits !== null) {
            return ['query' => $q, 'engine' => 'meilisearch', 'results' => collect($hits)->map(fn ($hit) => [
                'node_id' => $hit['id'],
                'type' => $hit['type'],
                'name' => $hit['name'],
                'snippet' => data_get($hit, '_formatted.content') ?: mb_substr((string) ($hit['content'] ?? ''), 0, 240),
            ])->values()];
        }

        $needle = mb_strtolower($q);
        $results = Node::query()->where('workspace_id', $workspace->id)
            ->where(function ($query) use ($needle) {
                $query->whereRaw('lower(name) like ?', ['%'.$needle.'%'])
                    ->orWhereHas('document', fn ($doc) => $doc->whereRaw('lower(plain_text) like ?', ['%'.$needle.'%']))
                    ->orWhereHas('file.currentVersion', fn ($version) => $version->whereRaw('lower(extracted_text) like ?', ['%'.$needle.'%']));
            })
            ->with(['document:node_id,plain_text', 'file.currentVersion'])
            ->limit(50)->get()->map(fn (Node $node) => [
                'node_id' => $node->id,
                'type' => $node->type,
                'name' => $node->name,
                'snippet' => match ($node->type) {
                    'document' => mb_substr($node->document?->plain_text ?? '', 0, 220),
                    'file' => mb_substr($node->file?->currentVersion?->extracted_text ?? '', 0, 220),
                    default => null,
                },
            ]);

        return ['query' => $q, 'engine' => 'postgres', 'results' => $results];
    }
}
