<?php

declare(strict_types=1);

namespace App\Demo;

use App\Demo\Model\SavedResult;
use Symfony\Component\Console\Output\OutputInterface;

final class PipelineComparison
{
    public function printRunSummary(OutputInterface $output, string $label, array $run): void
    {
        $output->writeln('');
        $output->writeln(sprintf('<info>%s</info>', $label));
        $output->writeln(sprintf('Completed tasks: %d', count($run['results'])));
        $output->writeln(sprintf('Total time: %.2f s', $run['totalSeconds']));
        $output->writeln(sprintf('Sequential expected time: %.2f s', $run['sequentialExpectedSeconds']));
        $output->writeln(sprintf('Observed overlap: %s', $run['observedOverlap'] ? 'yes' : 'no'));
        $output->writeln('Result files:');

        foreach ($this->sortedResults($run['results']) as $result) {
            $output->writeln(sprintf('- %s', $this->relativePath($result->path)));
        }
    }

    public function printComparison(OutputInterface $output, array $syncRun, array $flowRun, string $driverLabel): void
    {
        $output->writeln('');
        $output->writeln('<info>Comparison</info>');
        $output->writeln(sprintf('Synchronous pipeline: %.2f s', $syncRun['totalSeconds']));
        $output->writeln(sprintf('Darkwood Flow:        %.2f s', $flowRun['totalSeconds']));
        $output->writeln(sprintf('Observed overlap:     %s', $flowRun['observedOverlap'] ? 'yes' : 'no'));

        if (!$flowRun['observedOverlap']) {
            $output->writeln('');
            $output->writeln('The selected Flow driver scheduled jobs cooperatively, but the chosen operation remained blocking.');
        }

        $output->writeln('');
        $output->writeln('Execution model:');
        $output->writeln('- Synchronous: closure onion, one task at a time');
        $output->writeln(sprintf('- Flow: independent instruction packets driven by %s', $driverLabel));
    }

    /**
     * @param list<SavedResult> $results
     *
     * @return list<SavedResult>
     */
    private function sortedResults(array $results): array
    {
        usort($results, static fn (SavedResult $a, SavedResult $b): int => $a->id <=> $b->id);

        return $results;
    }

    private function relativePath(string $path): string
    {
        $projectDir = dirname(__DIR__, 2) . '/';

        if (str_starts_with($path, $projectDir)) {
            return substr($path, strlen($projectDir));
        }

        return $path;
    }
}
