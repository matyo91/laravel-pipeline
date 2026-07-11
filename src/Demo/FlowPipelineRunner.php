<?php

declare(strict_types=1);

namespace App\Demo;

use App\Demo\Fixture\DemoTaskProvider;
use App\Demo\Flow\FetchJob;
use App\Demo\Flow\HashJob;
use App\Demo\Flow\ResultCollector;
use App\Demo\Flow\SaveJob;
use App\Demo\Model\SavedResult;
use App\Demo\Output\DemoLogger;
use App\Demo\Output\DemoOutputCleaner;
use Flow\Driver\AmpDriver;
use Flow\DriverInterface;
use Flow\Flow\Flow;
use Flow\Ip;

final class FlowPipelineRunner
{
    public function __construct(
        private DemoTaskProvider $taskProvider,
        private DemoLogger $logger,
        private DemoOutputCleaner $outputCleaner,
        private FetchJob $fetchJob,
        private HashJob $hashJob,
        private SaveJob $saveJob,
        private DriverInterface $driver,
    ) {}

    /**
     * @return array{
     *     results: list<SavedResult>,
     *     totalSeconds: float,
     *     sequentialExpectedSeconds: float,
     *     observedOverlap: bool,
     *     driverName: string
     * }
     */
    public function run(): array
    {
        $this->logger->reset();
        $this->outputCleaner->clear();

        $collector = new ResultCollector();
        $startedAt = microtime(true);

        $flow = new Flow($this->fetchJob, driver: $this->driver);
        $flow->fn($this->hashJob)
            ->fn($this->saveJob)
            ->fn(function (SavedResult $result) use ($collector): SavedResult {
                $collector->add($result);

                return $result;
            });

        foreach ($this->taskProvider->getTasks() as $task) {
            $flow(new Ip($task));
        }

        $flow->await();

        $totalSeconds = microtime(true) - $startedAt;
        $sequentialExpected = $this->taskProvider->getSequentialExpectedSeconds();
        $logOverlap = $this->logger->hasInterleavedExecution();
        $timingOverlap = $totalSeconds < ($sequentialExpected * 0.8);

        return [
            'results' => $collector->all(),
            'totalSeconds' => $totalSeconds,
            'sequentialExpectedSeconds' => $sequentialExpected,
            'observedOverlap' => $logOverlap || $timingOverlap,
            'driverName' => $this->driver::class,
        ];
    }

    public function getDriverLabel(): string
    {
        if ($this->driver instanceof AmpDriver) {
            return 'AmpDriver (Revolt event loop)';
        }

        return $this->driver::class;
    }
}
