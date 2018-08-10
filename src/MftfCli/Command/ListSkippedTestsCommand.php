<?php
/**
 * Created by PhpStorm.
 * User: imeron
 * Date: 6/25/18
 * Time: 2:40 PM
 */

namespace MftfCli\Command;

use Magento\FunctionalTestingFramework\Test\Objects\TestObject;
use MftfCli\Util\MftfPathLoader;
use MftfCli\Util\ModuleNameExtractor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\FunctionalTestingFramework\Test\Handlers\TestObjectHandler;

class ListSkippedTestsCommand extends Command
{
    protected function configure()
    {
        $this->setName('skipped');
        $this->setDescription('Command which outputs a list of skipped tests names and filepaths');
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
        // by default we should update the filesystem
        if (!$skipUpdate) {
            $command = $this->getApplication()->get('setup');
            $args = [
                '--proj-dir' => $rootProjectDir
            ];

            $command->run(new ArrayInput($args), $output);
        }

        // get the path to the Magento project and load the framework classes
        $relativeMagent2cePath = rtrim($rootProjectDir, '/') . DIRECTORY_SEPARATOR . 'magento2ce';
        $magento2CePath = realpath($relativeMagent2cePath);
        $mftfPathLoader = new MftfPathLoader($magento2CePath);
        $mftfPathLoader->loadMftfPath();

        // render the table and format the skipped output
        $table = new Table($output);
        $table = $this->formatSkippedTests($table);
        $table->setHeaders(['module', 'test', 'ticket', 'filepath']);
        $table->render();
    }

    private function formatSkippedTests(Table $table)
    {
        ob_start();
        $toh = TestObjectHandler::getInstance();
        $allTests = $toh->getAllObjects();
        ksort($allTests);
        $moduleNameExtractor = new ModuleNameExtractor();
        /** @var TestObject $testObject */
        foreach ($allTests as $testObject) {
            if ($testObject->isSkipped()) {
                 $filePath = $testObject->getFilename();
                 $ticket = $testObject->getAnnotations()['skip'][0] ?? null;
                $table->addRow([$moduleNameExtractor->extractModuleName($filePath), $testObject->getName(), $ticket,  $filePath]);
            }
        }

        ob_end_clean();
        return $table;
    }
}
