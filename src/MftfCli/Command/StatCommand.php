<?php
/**
 * Created by PhpStorm.
 * User: imeron
 * Date: 6/20/18
 * Time: 4:32 PM
 */

namespace MftfCli\Command;

use MftfCli\Util\Calculator\AggregateStatCalculator;
use MftfCli\Util\Calculator\ModuleStatCalculator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StatCommand extends Command
{
    protected function configure()
    {
        $this->setName('stat');
        $this->setDescription('Command which prints general statistics around mftf test metadata');
        $this->addOption(
            'detail',
            'd',
            InputOption::VALUE_NONE,
            'provides a report for each module available to the magento project'
        );

        $this->addOption(
            'proj-dir',
            'p',
            InputOption::VALUE_OPTIONAL,
            'provide a path to the magento2ce project you would like stats on',
            getcwd()
        );

        $this->addOption(
            'skip-update',
            'k',
            InputOption::VALUE_NONE,
            'skip updating the filesystem before stat calculation'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $skipUpdate = $input->getOption('skip-update');
        $rootProjectDir = $input->getOption('proj-dir');
        $ceRootArg = $rootProjectDir . '/magento2ce';

        // by default we should update the filesystem
        if (!$skipUpdate) {
            $command = $this->getApplication()->get('setup');
            $args = [
              '--proj-dir' => $rootProjectDir
            ];

            $command->run(new ArrayInput($args), $output);
        }

        $start = microtime(true);
        $magento2cePath = realpath($ceRootArg);

        $statCalculator = new AggregateStatCalculator($magento2cePath);
        if ($input->getOption('detail')) {
            $statCalculator = new ModuleStatCalculator($magento2cePath);
        }
        $output->writeln('calculating statistics...');
        $result = $statCalculator->aggregateStats();
        $table = new Table($output);
        $table->setHeaders($statCalculator->getTableHeaders());
        $table->setRows($result);
        $table->render();
        $output->writeln('caculations done in : ' . (microtime(true) - $start) . ' seconds');
    }
}