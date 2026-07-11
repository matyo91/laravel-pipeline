<?php

declare(strict_types=1);

namespace App\Demo;

use App\Demo\Fixture\DemoTaskProvider;
use App\Demo\Model\SavedResult;
use App\Demo\Output\DemoLogger;
use App\Demo\Output\DemoOutputCleaner;
use App\Demo\Pipeline\FetchPipe;
use App\Demo\Pipeline\HashPipe;
use App\Demo\Pipeline\SavePipe;
use App\Demo\Pipeline\SynchronousPipeline;

final class SyncPipelineRunner
{
    public function __construct(
        private DemoTaskProvider $taskProvider,
        private DemoLogger $logger,
        private DemoOutputCleaner $outputCleaner,
        private FetchPipe $fetchPipe,
        private HashPipe $hashPipe,
        private SavePipe $savePipe,
    ) {}

    /**
     * @return array{
     *     results: list<SavedResult>,
     *     totalSeconds: float,
     *     sequentialExpectedSeconds: float,
     *     observedOverlap: bool
     * }
     */
    public function run(): array
    {
        $this->logger->reset();
        $this->outputCleaner->clear();

        $pipeline = new SynchronousPipeline();
        $results = [];
        $startedAt = microtime(true);

        foreach ($this->taskProvider->getTasks() as $task) {
            $result = $pipeline
                ->send($task)
                ->through([$this->fetchPipe, $this->hashPipe, $this->savePipe])
                ->thenReturn();

            if ($result instanceof SavedResult) {
                $results[] = $result;
            }
        }

        $totalSeconds = microtime(true) - $startedAt;
        $sequentialExpected = $this->taskProvider->getSequentialExpectedSeconds();

        return [
            'results' => $results,
            'totalSeconds' => $totalSeconds,
            'sequentialExpectedSeconds' => $sequentialExpected,
            'observedOverlap' => $this->logger->hasInterleavedExecution(),
        ];
    }
}
