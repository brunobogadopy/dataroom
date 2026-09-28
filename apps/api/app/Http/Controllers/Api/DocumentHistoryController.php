<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentRevision;
use App\Models\Node;
use App\Services\ActivityLogger;
use App\Services\NodeAccess;
use App\Services\SearchIndex;
use Illuminate\Http\Request;

class DocumentHistoryController extends Controller
{
    public function index(Request $request, Node $node, NodeAccess $access)
    {
        abort_unless($node->type === 'document' && $access->canView($request->user(), $node), 403);

        return DocumentRevision::where('node_id', $node->id)
            ->with('creator:id,name,email')
            ->orderByDesc('version')
            ->get(['id','node_id','version','plain_text','created_by','created_at']);
    }

    public function restore(Request $request, Node $node, DocumentRevision $revision, NodeAccess $access, SearchIndex $index, ActivityLogger $activity)
    {
        abort_unless($node->type === 'document' && $access->canEdit($request->user(), $node), 403);
        abort_unless($revision->node_id === $node->id, 404);

        $doc = $node->document;
        DocumentRevision::create([
            'node_id' => $node->id,
            'version' => $doc->version,
            'content_json' => $doc->content_json,
            'plain_text' => $doc->plain_text,
            'created_by' => $request->user()->id,
            'created_at' => now(),
        ]);

        $doc->update([
            'content_json' => $revision->content_json,
            'plain_text' => $revision->plain_text,
            'version' => $doc->version + 1,
        ]);

        $fresh = $node->fresh()->load('document');
        $index->indexNode($fresh);
        $activity->log($node->workspace, $request->user(), 'document.version_restored', $node, ['from_version' => $revision->version]);

        return $fresh;
    }
}
