<?php

declare(strict_types=1);

namespace App\Demo\Pipeline;

use App\Demo\Model\FetchedContent;
use App\Demo\Model\HashedContent;
use App\Demo\Output\DemoLogger;

final class HashPipe
{
    public function __construct(private DemoLogger $logger) {}

    public function __invoke(FetchedContent $content, callable $next): mixed
    {
        $this->logger->log('sync', $content->id, 'hash', 'start');

        $startedAt = microtime(true);
        $hash = hash('sha256', $content->content);

        $result = new HashedContent(
            $content->id,
            $content->url,
            $content->content,
            $hash,
            $startedAt,
            microtime(true),
        );

        $this->logger->log('sync', $content->id, 'hash', 'end');

        return $next($result);
    }
}
