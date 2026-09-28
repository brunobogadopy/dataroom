<?php

namespace App\Jobs;

use App\Models\FileVersion;
use App\Services\BunnyStorage;
use App\Services\SearchIndex;
use App\Services\TextExtractor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ExtractFileText implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;
    public int $tries = 3;

    public function __construct(public string $fileVersionId) {}

    public function handle(BunnyStorage $storage, TextExtractor $extractor, SearchIndex $index): void
    {
        $version = FileVersion::with('node')->findOrFail($this->fileVersionId);
        $version->update(['extraction_status' => 'processing', 'extraction_error' => null]);
        $tmp = tempnam(sys_get_temp_dir(), 'dataroom-file-');
        if (!$tmp) throw new \RuntimeException('Unable to create extraction temp file.');

        try {
            $storage->downloadTo($version->object_key, $tmp);
            $text = $extractor->extract($tmp, $version->mime_type, $version->node->name);
            $version->update([
                'extracted_text' => $text,
                'extraction_status' => $text === null ? 'unsupported' : 'ready',
            ]);
            $index->indexNode($version->node->fresh());
        } catch (Throwable $e) {
            $version->update(['extraction_status' => 'failed', 'extraction_error' => mb_substr($e->getMessage(), 0, 2000)]);
            throw $e;
        } finally {
            @unlink($tmp);
        }
    }
}
