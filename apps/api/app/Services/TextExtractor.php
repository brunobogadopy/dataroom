<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

class TextExtractor
{
    public function extract(string $path, string $mimeType, string $name): ?string
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if ($mimeType === 'application/pdf' || $extension === 'pdf') {
            return $this->pdf($path);
        }

        if ($mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' || $extension === 'docx') {
            return $this->docx($path);
        }

        if (str_starts_with($mimeType, 'text/') || in_array($extension, ['txt', 'md', 'csv', 'json', 'xml'], true)) {
            return mb_substr((string) file_get_contents($path), 0, 5_000_000);
        }

        return null;
    }

    private function pdf(string $path): string
    {
        $output = tempnam(sys_get_temp_dir(), 'dataroom-pdf-');
        if (!$output) throw new RuntimeException('Unable to create PDF extraction temp file.');
        $command = 'pdftotext -layout '.escapeshellarg($path).' '.escapeshellarg($output).' 2>&1';
        exec($command, $lines, $code);
        if ($code !== 0) {
            @unlink($output);
            throw new RuntimeException('pdftotext failed: '.implode("\n", $lines));
        }
        $text = (string) file_get_contents($output);
        @unlink($output);
        return mb_substr($text, 0, 5_000_000);
    }

    private function docx(string $path): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('Unable to open DOCX.');
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false) return '';
        $xml = str_replace(['</w:p>', '</w:tr>', '<w:tab/>'], ["\n", "\n", "\t"], $xml);
        return mb_substr(html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8'), 0, 5_000_000);
    }
}
