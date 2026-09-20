<?php

namespace App\Services;

use App\Contracts\PaperProcessor;
use App\Models\Paper;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PendingPaperProcessor implements PaperProcessor
{
    public function __construct(
        private readonly AnalysisHttpClient $client,
        private readonly AnalysisPersister $persister,
    ) {}

    public function process(Paper $paper, string $requestId): void
    {
        $disk = Storage::disk($paper->storage_disk ?: config('papers.storage_disk'));
        $stream = $disk->readStream($paper->file_path);
        if (! is_resource($stream)) {
            throw new RuntimeException('Private paper file could not be read.');
        }

        $analysis = $this->client->analyze($paper, $requestId, $stream);
        $this->persister->persist($paper, $analysis);
    }
}