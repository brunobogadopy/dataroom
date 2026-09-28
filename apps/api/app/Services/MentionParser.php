<?php

namespace App\Services;

use App\Models\Workspace;
use Illuminate\Support\Collection;

class MentionParser
{
    public function users(string $body, Workspace $workspace): Collection
    {
        preg_match_all('/@([A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,})/u',$body,$matches);
        $emails=collect($matches[1]??[])->map(fn($e)=>mb_strtolower($e))->unique()->values();

        if($emails->isEmpty())return collect();

        return $workspace->members()->whereIn('users.email',$emails)->get();
    }
}
