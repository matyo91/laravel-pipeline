<?php

declare(strict_types=1);

namespace App\Demo\Pipeline;

use App\Demo\Fixture\DemoTaskProvider;
use App\Demo\Model\FetchedContent;
use App\Demo\Model\FetchRequest;
use App\Demo\Output\DemoLogger;

final class FetchPipe
{
    public function __construct(private DemoLogger $logger) {}

    public function __invoke(FetchRequest $request, callable $next): mixed
    {
        $this->logger->log('sync', $request->id, 'fetch', 'start');

        $startedAt = microtime(true);
        usleep((int) ($request->delaySeconds * 1_000_000));

        $result = new FetchedContent(
            $request->id,
            $request->url,
            DemoTaskProvider::contentForTask($request->id),
            $startedAt,
            microtime(true),
        );

        $this->logger->log('sync', $request->id, 'fetch', 'end');

        return $next($result);
    }
}
