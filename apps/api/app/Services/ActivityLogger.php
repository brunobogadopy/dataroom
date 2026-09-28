<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Node;
use App\Models\User;
use App\Models\Workspace;

class ActivityLogger
{
    public function log(Workspace $workspace, ?User $actor, string $action, ?Node $node = null, array $metadata = []): void
    {
        Activity::create([
            'workspace_id' => $workspace->id,
            'actor_user_id' => $actor?->id,
            'node_id' => $node?->id,
            'action' => $action,
            'metadata' => $metadata ?: null,
            'created_at' => now(),
        ]);
    }
}
