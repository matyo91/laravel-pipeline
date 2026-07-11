<?php

declare(strict_types=1);

namespace App\Demo\Pipeline;

use App\Demo\Model\HashedContent;
use App\Demo\Model\SavedResult;
use App\Demo\Output\DemoLogger;

final class SavePipe
{
    public function __construct(
        private DemoLogger $logger,
        private string $outputDir,
    ) {}

    public function __invoke(HashedContent $content, callable $next): mixed
    {
        $this->logger->log('sync', $content->id, 'save', 'start');

        $startedAt = microtime(true);
        $path = $this->outputDir . '/task-' . $content->id . '.json';

        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0777, true);
        }

        $result = new SavedResult(
            $content->id,
            $content->url,
            $content->hash,
            $path,
            $startedAt,
            microtime(true),
        );

        file_put_contents($path, json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        $this->logger->log('sync', $content->id, 'save', 'end');

        return $next($result);
    }
}
