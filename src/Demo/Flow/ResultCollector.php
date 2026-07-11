<?php

declare(strict_types=1);

namespace App\Demo\Flow;

use App\Demo\Model\SavedResult;

final class ResultCollector
{
    /** @var list<SavedResult> */
    private array $results = [];

    public function add(SavedResult $result): void
    {
        $this->results[] = $result;
    }

    /**
     * @return list<SavedResult>
     */
    public function all(): array
    {
        return $this->results;
    }

    public function count(): int
    {
        return count($this->results);
    }

    public function reset(): void
    {
        $this->results = [];
    }
}
