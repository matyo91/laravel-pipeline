<?php

declare(strict_types=1);

namespace App\Command;

use App\Demo\FlowPipelineRunner;
use App\Demo\PipelineComparison;
use App\Demo\SyncPipelineRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'demo:pipeline:compare',
    description: 'Compare synchronous and Darkwood Flow pipeline execution',
)]
final class ComparePipelineCommand extends Command
{
    public function __construct(
        private SyncPipelineRunner $syncRunner,
        private FlowPipelineRunner $flowRunner,
        private PipelineComparison $comparison,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Running synchronous pipeline...</info>');
        $syncRun = $this->syncRunner->run();

        $output->writeln('');
        $output->writeln('<info>Running Darkwood Flow pipeline...</info>');
        $flowRun = $this->flowRunner->run();

        $this->comparison->printComparison(
            $output,
            $syncRun,
            $flowRun,
            $this->flowRunner->getDriverLabel(),
        );

        return Command::SUCCESS;
    }
}
