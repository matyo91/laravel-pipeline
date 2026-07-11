<?php

declare(strict_types=1);

namespace App\Demo\Flow;

use App\Demo\Fixture\DemoTaskProvider;
use App\Demo\Model\FetchedContent;
use App\Demo\Model\FetchRequest;
use App\Demo\Output\DemoLogger;
use Flow\DriverInterface;
use Flow\JobInterface;

/**
 * @implements JobInterface<FetchRequest, FetchedContent>
 */
final class FetchJob implements JobInterface
{
    public function __construct(
        private DriverInterface $driver,
        private DemoLogger $logger,
    ) {}

    public function __invoke($data): FetchedContent
    {
        $this->logger->log('flow', $data->id, 'fetch', 'start');

        $startedAt = microtime(true);
        $this->driver->delay($data->delaySeconds);

        $result = new FetchedContent(
            $data->id,
            $data->url,
            DemoTaskProvider::contentForTask($data->id),
            $startedAt,
            microtime(true),
        );

        $this->logger->log('flow', $data->id, 'fetch', 'end');

        return $result;
    }
}
