<?php

declare(strict_types=1);

namespace App\Command;

use App\Demo\PipelineComparison;
use App\Demo\SyncPipelineRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'demo:pipeline:sync',
    description: 'Run the synchronous Laravel-style pipeline demo',
)]
final class SyncPipelineCommand extends Command
{
    public function __construct(
        private SyncPipelineRunner $runner,
        private PipelineComparison $comparison,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $run = $this->runner->run();
        $this->comparison->printRunSummary($output, 'Synchronous pipeline', $run);

        return Command::SUCCESS;
    }
}
