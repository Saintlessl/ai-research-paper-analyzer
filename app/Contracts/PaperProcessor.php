<?php

namespace App\Contracts;

use App\Models\Paper;

interface PaperProcessor
{
    public function process(Paper $paper, string $requestId): void;
}