<?php

declare(strict_types=1);

namespace App\Demo\Model;

final readonly class HashedContent
{
    public function __construct(
        public int $id,
        public string $url,
        public string $content,
        public string $hash,
        public float $startedAt,
        public float $finishedAt,
    ) {}
}
