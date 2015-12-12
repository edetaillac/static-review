<?php

namespace StaticReview\Command;

use StaticReview\VersionControl\GitVersionControl;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class HookInstallCommand extends Command
{
    const PHPUNIT_DEFAULT_CONF_FILENAME = 'phpunit.xml.dist';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('install');

        $this->setDescription('Install precommit in a git repo');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Pre-commit install');

        $git = new GitVersionControl();
        $projectBase = $git->getProjectBase();

        $phpunit = $io->confirm('Enable PhpUnit ?', true);

        $source = realpath($projectBase);
        $hookDir = $source.'/.git/hooks';
        $defaultPhpUnitConfFile = $source.'/'.self::PHPUNIT_DEFAULT_CONF_FILENAME;

        $precommitCommand = sprintf('precommit check%s', $phpunit ? ' --phpunit true' : '');

        if ($phpunit) {
            $phpunitPath = $io->ask('Specify Phpunit bin path [example: vendor/bin/phpunit] ? : ', 'phpunit');
            $phpunitConfFile = $io->ask('Specify Phpunit config file path ? : ', $defaultPhpUnitConfFile);

            if ($phpunitPath != '') {
                if (strpos($phpunitPath, '/') !== false) {
                    $phpunitPath = $source.'/'.$phpunitPath;
                    if (!is_file($phpunitPath)) {
                        $io->error(sprintf('No phpunit bin found "%s"', $phpunitPath));
                        exit(1);
                    }
                }
            }

            if (!is_file($phpunitConfFile)) {
                $io->error(sprintf('No phpunit conf file found "%s"', $phpunitConfFile));
                exit(1);
            }

            $precommitCommand .= ($phpunitPath != 'phpunit') ? ' --phpunit-bin-path '.$phpunitPath : '';
            $precommitCommand .= ($phpunitConfFile != $defaultPhpUnitConfFile) ? ' --phpunit-conf '.$phpunitConfFile : '';
        }

        if (!is_dir($hookDir)) {
            $io->error(sprintf('The git hook directory does not exist (%s)', $hookDir));
            exit(1);
        }

        $target = $hookDir.'/pre-commit';
        $fs = new Filesystem();
        if (!is_file($target)) {
            $fileContent = sprintf("#!/bin/sh\n%s", $precommitCommand);
            $fs->dumpFile($target, $fileContent);
            chmod($target, 0755);
            $io->success('pre-commit file correctly updated');
        } else {
            $io->note(sprintf('A pre-commit file is already exist. Please add "%s" at the end !', $precommitCommand));
        }

        exit(0);
    }
}
