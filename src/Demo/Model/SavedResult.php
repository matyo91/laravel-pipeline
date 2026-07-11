<?php

declare(strict_types=1);

namespace App\Demo\Model;

final readonly class SavedResult
{
    public function __construct(
        public int $id,
        public string $url,
        public string $hash,
        public string $path,
        public float $startedAt,
        public float $finishedAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'hash' => $this->hash,
            'path' => $this->path,
            'startedAt' => $this->startedAt,
            'finishedAt' => $this->finishedAt,
        ];
    }
}
