<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BunnyStorage
{
    private function credentials(): array
    {
        $zone = config('dataroom.storage.zone');
        $key = config('dataroom.storage.access_key');
        $host = config('dataroom.storage.hostname');

        if (!$zone || !$key || !$host) {
            throw new RuntimeException('Bunny Storage is not configured.');
        }

        return [$zone, $key, $host];
    }

    private function url(string $objectKey): string
    {
        [$zone, , $host] = $this->credentials();
        $encoded = implode('/', array_map('rawurlencode', explode('/', ltrim($objectKey, '/'))));
        return sprintf('https://%s/%s/%s', $host, rawurlencode($zone), $encoded);
    }

    public function upload(UploadedFile $file, string $objectKey): void
    {
        [, $key] = $this->credentials();
        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) throw new RuntimeException('Unable to read upload.');

        try {
            $response = Http::withHeaders([
                'AccessKey' => $key,
                'Content-Type' => $file->getMimeType() ?: 'application/octet-stream',
            ])->timeout(300)->send('PUT', $this->url($objectKey), ['body' => $handle]);
        } finally {
            fclose($handle);
        }

        if (!$response->successful()) {
            throw new RuntimeException('Bunny Storage upload failed: HTTP '.$response->status());
        }
    }

    public function downloadTo(string $objectKey, string $destination): void
    {
        [, $key] = $this->credentials();
        $response = Http::withHeaders(['AccessKey' => $key])
            ->timeout(300)
            ->withOptions(['sink' => $destination])
            ->get($this->url($objectKey));

        if (!$response->successful()) {
            @unlink($destination);
            throw new RuntimeException('Bunny Storage download failed: HTTP '.$response->status());
        }
    }

    public function delete(string $objectKey): void
    {
        [, $key] = $this->credentials();
        $response = Http::withHeaders(['AccessKey' => $key])->timeout(60)->delete($this->url($objectKey));
        if (!$response->successful() && $response->status() !== 404) {
            throw new RuntimeException('Bunny Storage delete failed: HTTP '.$response->status());
        }
    }
}
