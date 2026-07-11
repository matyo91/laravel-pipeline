<?php

declare(strict_types=1);

namespace App\Tests\Demo\Pipeline;

use App\Demo\Pipeline\SynchronousPipeline;
use PHPUnit\Framework\TestCase;

final class SynchronousPipelineTest extends TestCase
{
    public function testPipesExecuteInOrder(): void
    {
        $order = [];

        $pipeline = new SynchronousPipeline();
        $result = $pipeline
            ->send('input')
            ->through([
                function (string $passable, callable $next) use (&$order): string {
                    $order[] = 'fetch';

                    return $next($passable . '-fetched');
                },
                function (string $passable, callable $next) use (&$order): string {
                    $order[] = 'hash';

                    return $next($passable . '-hashed');
                },
                function (string $passable, callable $next) use (&$order): string {
                    $order[] = 'save';

                    return $next($passable . '-saved');
                },
            ])
            ->thenReturn();

        self::assertSame(['fetch', 'hash', 'save'], $order);
        self::assertSame('input-fetched-hashed-saved', $result);
    }

    public function testShortCircuitStopsChain(): void
    {
        $order = [];

        $pipeline = new SynchronousPipeline();
        $result = $pipeline
            ->send('input')
            ->through([
                function (string $passable, callable $next) use (&$order): string {
                    $order[] = 'fetch';

                    return 'short-circuit';
                },
                function (string $passable, callable $next) use (&$order): string {
                    $order[] = 'hash';

                    return $next($passable);
                },
            ])
            ->then(function (string $passable) use (&$order): string {
                $order[] = 'destination';

                return $passable;
            });

        self::assertSame(['fetch'], $order);
        self::assertSame('short-circuit', $result);
    }
}
