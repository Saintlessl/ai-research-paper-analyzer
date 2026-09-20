<?php

namespace App\Services;

use App\Contracts\PaperProcessor;
use App\Models\Paper;
use RuntimeException;

class PendingPaperProcessor implements PaperProcessor
{
    public function process(Paper $paper, string $requestId): void
    {
        throw new RuntimeException('Paper processing service is not configured.');
    }
}