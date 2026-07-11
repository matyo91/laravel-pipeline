<?php

declare(strict_types=1);

namespace App\Tests\Demo;

use App\Demo\Fixture\DemoTaskProvider;
use App\Demo\Flow\FetchJob;
use App\Demo\Flow\HashJob;
use App\Demo\Flow\ResultCollector;
use App\Demo\Flow\SaveJob;
use App\Demo\FlowPipelineRunner;
use App\Demo\Model\FetchRequest;
use App\Demo\Model\SavedResult;
use App\Demo\Output\DemoLogger;
use App\Demo\Output\DemoOutputCleaner;
use App\Demo\Pipeline\FetchPipe;
use App\Demo\Pipeline\HashPipe;
use App\Demo\Pipeline\SavePipe;
use App\Demo\SyncPipelineRunner;
use Flow\Driver\FiberDriver;
use PHPUnit\Framework\TestCase;

final class PipelineComparisonTest extends TestCase
{
    private string $outputDir;

    protected function setUp(): void
    {
        $this->outputDir = sys_get_temp_dir() . '/laravel-pipeline-demo-' . uniqid('', true);
        mkdir($this->outputDir, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->outputDir . '/*.json') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->outputDir)) {
            rmdir($this->outputDir);
        }
    }

    public function testBothProduceSameHashesAndFiles(): void
    {
        $taskProvider = $this->createZeroDelayTaskProvider();
        $logger = new DemoLogger(silent: true);
        $cleaner = new DemoOutputCleaner($this->outputDir);

        $syncRunner = new SyncPipelineRunner(
            $taskProvider,
            $logger,
            $cleaner,
            new FetchPipe($logger),
            new HashPipe($logger),
            new SavePipe($logger, $this->outputDir),
        );

        $driver = new FiberDriver();
        $flowRunner = new FlowPipelineRunner(
            $taskProvider,
            $logger,
            $cleaner,
            new FetchJob($driver, $logger),
            new HashJob($logger),
            new SaveJob($logger, $this->outputDir),
            $driver,
        );

        $syncRun = $syncRunner->run();
        $flowRun = $flowRunner->run();

        self::assertCount(5, $syncRun['results']);
        self::assertCount(5, $flowRun['results']);

        $syncById = $this->indexById($syncRun['results']);
        $flowById = $this->indexById($flowRun['results']);

        foreach ($syncById as $id => $syncResult) {
            $flowResult = $flowById[$id];
            self::assertSame($syncResult->hash, $flowResult->hash);
            self::assertSame(
                file_get_contents($syncResult->path),
                file_get_contents($flowResult->path),
            );
        }
    }

    public function testResultCollectorDoesNotLoseTasks(): void
    {
        $collector = new ResultCollector();
        $taskProvider = $this->createZeroDelayTaskProvider();
        $logger = new DemoLogger(silent: true);
        $cleaner = new DemoOutputCleaner($this->outputDir);
        $driver = new FiberDriver();

        $runner = new FlowPipelineRunner(
            $taskProvider,
            $logger,
            $cleaner,
            new FetchJob($driver, $logger),
            new HashJob($logger),
            new SaveJob($logger, $this->outputDir),
            $driver,
        );

        $run = $runner->run();

        self::assertSame(5, count($run['results']));
        self::assertSame(5, count(array_unique(array_map(
            static fn (SavedResult $result): int => $result->id,
            $run['results'],
        ))));
    }

    private function createZeroDelayTaskProvider(): DemoTaskProvider
    {
        return new class extends DemoTaskProvider {
            public function getTasks(): array
            {
                return [
                    new FetchRequest(1, 'fixture://task-1', 0.0),
                    new FetchRequest(2, 'fixture://task-2', 0.0),
                    new FetchRequest(3, 'fixture://task-3', 0.0),
                    new FetchRequest(4, 'fixture://task-4', 0.0),
                    new FetchRequest(5, 'fixture://task-5', 0.0),
                ];
            }
        };
    }

    /**
     * @param list<SavedResult> $results
     *
     * @return array<int, SavedResult>
     */
    private function indexById(array $results): array
    {
        $indexed = [];
        foreach ($results as $result) {
            $indexed[$result->id] = $result;
        }

        return $indexed;
    }
}
