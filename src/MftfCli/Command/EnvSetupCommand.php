<?php
/**
 * Created by PhpStorm.
 * User: imeron
 * Date: 6/22/18
 * Time: 11:03 AM
 */

namespace MftfCli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class EnvSetupCommand extends Command
{
    const GIT_REPOS = [
        'magento2ce' => "git@github.com:magento/magento2ce.git",
        'magento2ee' => "git@github.com:magento/magento2ee.git",
        'magento2b2b' => "git@github.com:magento/magento2b2b.git",
        'magento2-page-builder' => "git@github.com:magento/magento2-page-builder.git"
    ];

    const GIT_BRANCHES = [
        'magento2ce' => "2.3-develop",
        'magento2ee' => "2.3-develop",
        'magento2b2b' => "1.1-develop",
        'magento2-page-builder' => "develop"
    ];

    protected function configure()
    {
        $this->setName('setup');
        $this->setDescription('Command which uses the current git user creds to clone necessary repos and update their status');
        $this->addOption(
            'proj-dir',
            'p',
            InputOption::VALUE_OPTIONAL,
            'parent directory where all projects are expected to exist',
            getcwd()
        );
        $this->addOption('reset', 'r', InputOption::VALUE_NONE, 'forces all projects to reset');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projectDir = rtrim($input->getOption('proj-dir'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $resetProject = $input->getOption('reset');
        $extendedProj = ['magento2ee', 'magento2b2b', 'magento2-page-builder'];

        // see if magento2ce/ee/b2b/pagebuilder are available
        $fileSystem = new Filesystem();
        $newSetup = $this->cloneRepos($projectDir, $fileSystem, $output);

        if (!$newSetup && $resetProject) {
            $this->unlinkProjects($projectDir, $output);
        }

        $this->updateAllProjects($projectDir, $output);
        $this->installDependencies($projectDir, $output);

        if ($newSetup || $resetProject) {
            $this->symlinkProjects($projectDir, $extendedProj, $output);
        }
    }

    /**
     * @param $projectDir
     * @param $fileSystem
     * @param OutputInterface $output
     * @return bool
     */
    private function cloneRepos($projectDir, $fileSystem, $output)
    {
        $missingRepos = [];

        foreach (array_keys(self::GIT_REPOS) as $repoName) {
            $repoGitDir = $projectDir . "{$repoName}/.git";
            if ($output->isDebug()) {
                $output->writeln('validating dir exists: ' . $repoGitDir);
            }

            if (!$fileSystem->exists($repoGitDir)) {
                $missingRepos[] = $repoName;
            }
        }
        if (!empty($missingRepos)) {
            $output->writeln('cloning missing repos, this process will take some time...');
        }

        // clone missing repos
        foreach ($missingRepos as $missingRepo) {
            $output->writeln('cloning missing repo: ' . $missingRepo . '...');
            $cloneProject = new Process("git clone " . self::GIT_REPOS[$missingRepo]);
            $cloneProject->setTimeout(0);
            $cloneProject->setIdleTimeout(300);
            $cloneProject->setWorkingDirectory($projectDir);
            $cloneProject->run(function ($type, $buffer) use ($output) {
                if ($output->isDebug()) {
                    $output->write($buffer);
                }
            });
        }

        return count($missingRepos) === count(self::GIT_REPOS);
    }

    private function unlinkProjects($projectDir, $output)
    {
        $output->writeln("unlinking projects just in case...");
        // unlink projects
        $unlinkProjectsProcess = new Process('php magento2ee/dev/tools/build-ee.php --command unlink');
        $unlinkProjectsProcess->setWorkingDirectory($projectDir);
        $unlinkProjectsProcess->setTimeout(0);
        $unlinkProjectsProcess->setIdleTimeout(300);
        $unlinkProjectsProcess->run(function ($type, $buffer) use ($output) {
            if ($output->isDebug()) {
                $output->write($buffer);
            }
        });
    }

    private function updateAllProjects($projectDir, $output)
    {
        $output->writeln("updating all projects to the latest commit...");

        // update the projects
        foreach (array_keys(self::GIT_REPOS) as $repoName) {
            $updateGitProject = new Process(
                'git fetch && git checkout origin/' . self::GIT_BRANCHES[$repoName]);
            $updateGitProject->setWorkingDirectory($projectDir . $repoName);
            $updateGitProject->setTimeout(0);
            $updateGitProject->setIdleTimeout(300);
            $updateGitProject->run(function ($type, $buffer) use ($output) {
                if ($output->isDebug()) {
                    $output->write($buffer);
                }
            });
        }
    }

    private function installDependencies($projectDir, $output)
    {
        $installDir = $projectDir . 'magento2ce';

        // where is the mftf dependency?
        $rootComposerJson = json_decode(
            file_get_contents($projectDir . 'magento2ce/composer.json'),
            true
        );

        if (!array_key_exists('magento/magento2-functional-testing-framework', $rootComposerJson['require-dev'])) {
             $installDir = $installDir . '/dev/tests/acceptance';
        }

        $output->writeln('installing project dependencies...');
        $installDependencies = new Process('composer install');
        $installDependencies->setWorkingDirectory($installDir);
        $installDependencies->setTimeout(0);
        $installDependencies->setIdleTimeout(300);
        $installDependencies->run(function ($type, $buffer) use ($output) {
            if ($output->isDebug()) {
                $output->write($buffer);
            }
        });
    }

    private function symlinkProjects($projectDir, $extendedProj, $output)
    {
        $output->writeln('linking projects together...');
        foreach ($extendedProj as $proj) {
            $symlinkCmd = new Process('php magento2ee/dev/tools/build-ee.php --ee-source ' . $proj);
            $symlinkCmd->setWorkingDirectory($projectDir);
            $symlinkCmd->setTimeout(0);
            $symlinkCmd->setIdleTimeout(300);
            $symlinkCmd->run(function ($type, $buffer) use ($output) {
                if ($output->isDebug()) {
                    $output->write($buffer);
                }
            });
        }
    }
}