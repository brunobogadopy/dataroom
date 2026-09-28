<?php

namespace App\Services;

use App\Models\Node;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecentTracker
{
    public function touch(User $user, Node $node): void
    {
        DB::table('recent_nodes')->upsert([
            ['user_id' => $user->id, 'node_id' => $node->id, 'viewed_at' => now()],
        ], ['user_id', 'node_id'], ['viewed_at']);
    }
}
