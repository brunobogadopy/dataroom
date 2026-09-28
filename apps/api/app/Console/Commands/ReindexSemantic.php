<?php

namespace App\Console\Commands;

use App\Jobs\IndexNodeEmbeddings;
use App\Models\Node;
use Illuminate\Console\Command;

class ReindexSemantic extends Command
{
    protected $signature='dataroom:reindex-ai {--sync : Run indexing immediately instead of queueing jobs}';
    protected $description='Rebuild semantic chunks for all searchable documents and files.';

    public function handle(): int
    {
        $count=0;

        Node::query()
            ->whereIn('type',['document','file'])
            ->orderBy('id')
            ->chunkById(100,function($nodes)use(&$count){
                foreach($nodes as $node){
                    if($this->option('sync'))IndexNodeEmbeddings::dispatchSync($node->id);
                    else IndexNodeEmbeddings::dispatch($node->id);
                    $count++;
                }
            });

        $this->info("Scheduled {$count} nodes for semantic indexing.");
        return self::SUCCESS;
    }
}
