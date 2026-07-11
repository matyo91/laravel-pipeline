<?php

declare(strict_types=1);

namespace App\Command;

use App\Demo\FlowPipelineRunner;
use App\Demo\PipelineComparison;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'demo:pipeline:flow',
    description: 'Run the Darkwood Flow pipeline demo',
)]
final class FlowPipelineCommand extends Command
{
    public function __construct(
        private FlowPipelineRunner $runner,
        private PipelineComparison $comparison,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $run = $this->runner->run();
        $this->comparison->printRunSummary($output, 'Darkwood Flow pipeline', $run);

        return Command::SUCCESS;
    }
}
