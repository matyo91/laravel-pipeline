<?php

declare(strict_types=1);

namespace App\Demo\Flow;

use App\Demo\Model\FetchedContent;
use App\Demo\Model\HashedContent;
use App\Demo\Output\DemoLogger;
use Flow\JobInterface;

/**
 * @implements JobInterface<FetchedContent, HashedContent>
 */
final class HashJob implements JobInterface
{
    public function __construct(private DemoLogger $logger) {}

    public function __invoke($data): HashedContent
    {
        $this->logger->log('flow', $data->id, 'hash', 'start');

        $startedAt = microtime(true);
        $hash = hash('sha256', $data->content);

        $result = new HashedContent(
            $data->id,
            $data->url,
            $data->content,
            $hash,
            $startedAt,
            microtime(true),
        );

        $this->logger->log('flow', $data->id, 'hash', 'end');

        return $result;
    }
}
