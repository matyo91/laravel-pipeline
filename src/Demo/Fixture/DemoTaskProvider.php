<?php

declare(strict_types=1);

namespace App\Demo\Fixture;

use App\Demo\Model\FetchRequest;

class DemoTaskProvider
{
    /**
     * @return list<FetchRequest>
     */
    public function getTasks(): array
    {
        return [
            new FetchRequest(1, 'fixture://task-1', 0.8),
            new FetchRequest(2, 'fixture://task-2', 0.3),
            new FetchRequest(3, 'fixture://task-3', 0.6),
            new FetchRequest(4, 'fixture://task-4', 0.2),
            new FetchRequest(5, 'fixture://task-5', 0.5),
        ];
    }

    public function getSequentialExpectedSeconds(): float
    {
        return array_sum(array_map(
            static fn (FetchRequest $task): float => $task->delaySeconds,
            $this->getTasks(),
        ));
    }

    public static function contentForTask(int $id): string
    {
        return sprintf('content-for-task-%d', $id);
    }
}
