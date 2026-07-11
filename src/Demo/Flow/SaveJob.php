<?php

declare(strict_types=1);

namespace App\Demo\Flow;

use App\Demo\Model\HashedContent;
use App\Demo\Model\SavedResult;
use App\Demo\Output\DemoLogger;
use Flow\JobInterface;

/**
 * @implements JobInterface<HashedContent, SavedResult>
 */
final class SaveJob implements JobInterface
{
    public function __construct(
        private DemoLogger $logger,
        private string $outputDir,
    ) {}

    public function __invoke($data): SavedResult
    {
        $this->logger->log('flow', $data->id, 'save', 'start');

        $startedAt = microtime(true);
        $path = $this->outputDir . '/task-' . $data->id . '.json';

        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0777, true);
        }

        $result = new SavedResult(
            $data->id,
            $data->url,
            $data->hash,
            $path,
            $startedAt,
            microtime(true),
        );

        file_put_contents($path, json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        $this->logger->log('flow', $data->id, 'save', 'end');

        return $result;
    }
}
