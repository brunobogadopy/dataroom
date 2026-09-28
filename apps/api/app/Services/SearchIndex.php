<?php

namespace App\Services;

use App\Models\Node;
use Illuminate\Support\Facades\Http;
use Throwable;

class SearchIndex
{
    private static bool $configured = false;
    private function endpoint(string $path): string
    {
        return config('dataroom.search.host').'/'.ltrim($path, '/');
    }

    private function request()
    {
        $request = Http::acceptJson()->timeout(5);
        if ($key = config('dataroom.search.key')) $request = $request->withToken($key);
        return $request;
    }

    private function configure(): void
    {
        if (self::$configured) return;
        $base = 'indexes/'.config('dataroom.search.index').'/settings';
        $a = $this->request()->put($this->endpoint($base.'/filterable-attributes'), ['workspace_id', 'parent_id', 'type']);
        $b = $this->request()->put($this->endpoint($base.'/searchable-attributes'), ['name', 'content']);
        self::$configured = $a->successful() && $b->successful();
    }

    public function indexNode(Node $node): void
    {
        try {
            $node->loadMissing(['document', 'file.currentVersion']);
            $text = match ($node->type) {
                'document' => $node->document?->plain_text ?? '',
                'file' => $node->file?->currentVersion?->extracted_text ?? '',
                default => '',
            };
            $payload = [[
                'id' => $node->id,
                'workspace_id' => $node->workspace_id,
                'parent_id' => $node->parent_id,
                'type' => $node->type,
                'name' => $node->name,
                'content' => $text,
            ]];
            $this->request()->post($this->endpoint('indexes/'.config('dataroom.search.index').'/documents'), $payload)->throw();
            $this->configure();
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function deleteNode(string $nodeId): void
    {
        try {
            $this->request()->delete($this->endpoint('indexes/'.config('dataroom.search.index').'/documents/'.rawurlencode($nodeId)))->throw();
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function search(string $workspaceId, string $query): ?array
    {
        try {
            $this->configure();
            $response = $this->request()->post($this->endpoint('indexes/'.config('dataroom.search.index').'/search'), [
                'q' => $query,
                'filter' => 'workspace_id = "'.$workspaceId.'"',
                'limit' => 50,
                'attributesToHighlight' => ['name', 'content'],
                'highlightPreTag' => '<mark>',
                'highlightPostTag' => '</mark>',
                'attributesToCrop' => ['content'],
                'cropLength' => 35,
            ]);
            if (!$response->successful()) return null;
            return $response->json('hits', []);
        } catch (Throwable) {
            return null;
        }
    }
}
