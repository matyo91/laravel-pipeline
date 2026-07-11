<?php

declare(strict_types=1);

namespace App\Demo\Output;

final class DemoOutputCleaner
{
    public function __construct(private string $outputDir) {}

    public function clear(): void
    {
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0777, true);
        }

        foreach (glob($this->outputDir . '/*.json') ?: [] as $file) {
            unlink($file);
        }
    }
}
