<?php

declare(strict_types=1);

namespace App\Demo\Pipeline;

use Closure;

final class SynchronousPipeline
{
    private mixed $passable = null;

    /** @var list<callable|object> */
    private array $pipes = [];

    public function send(mixed $passable): self
    {
        $this->passable = $passable;

        return $this;
    }

    /**
     * @param callable|object|list<callable|object> $pipes
     */
    public function through(callable|object|array $pipes): self
    {
        $this->pipes = is_array($pipes) ? $pipes : [$pipes];

        return $this;
    }

    public function then(Closure $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $destination,
        );

        return $pipeline($this->passable);
    }

    public function thenReturn(): mixed
    {
        return $this->then(static fn (mixed $passable): mixed => $passable);
    }

    private function carry(): Closure
    {
        return function (Closure $stack, callable|object $pipe): Closure {
            return function (mixed $passable) use ($stack, $pipe): mixed {
                if (is_callable($pipe)) {
                    return $pipe($passable, $stack);
                }

                return $pipe($passable, $stack);
            };
        };
    }
}
