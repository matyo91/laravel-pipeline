<?php

declare(strict_types=1);

namespace App\Demo\Output;

final class DemoLogger
{
    /** @var list<array{mode: string, taskId: int, step: string, event: string, time: float}> */
    private array $entries = [];

    public function __construct(private bool $silent = false) {}

    public function log(string $mode, int $taskId, string $step, string $event): void
    {
        $line = sprintf('[%s][task #%d][%s] %s', strtoupper($mode), $taskId, $step, $event);
        $this->entries[] = [
            'mode' => strtolower($mode),
            'taskId' => $taskId,
            'step' => $step,
            'event' => $event,
            'time' => microtime(true),
        ];

        if (!$this->silent) {
            echo $line . PHP_EOL;
        }
    }

    /**
     * @return list<array{mode: string, taskId: int, step: string, event: string, time: float}>
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    public function reset(): void
    {
        $this->entries = [];
    }

    public function hasInterleavedExecution(): bool
    {
        $open = [];

        foreach ($this->entries as $entry) {
            $key = $entry['step'];
            $taskId = $entry['taskId'];

            if ($entry['event'] === 'start') {
                foreach ($open[$key] ?? [] as $openTaskId) {
                    if ($openTaskId !== $taskId) {
                        return true;
                    }
                }

                $open[$key][] = $taskId;
            }

            if ($entry['event'] === 'end') {
                $open[$key] = array_values(array_filter(
                    $open[$key] ?? [],
                    static fn (int $id): bool => $id !== $taskId,
                ));
            }
        }

        return false;
    }
}
