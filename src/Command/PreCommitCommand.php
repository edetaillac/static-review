<?php

namespace StaticReview\Command;

use StaticReview\Review\Cmd\PhpUnitReview;
use StaticReview\Review\Composer\ComposerLintReview;
use StaticReview\Review\JS\EsLintReview;
use StaticReview\Review\JSON\JsonLintReview;
use StaticReview\Review\PHP\PhpLintReview;
use StaticReview\Review\Composer\ComposerLockReview;
use StaticReview\Review\PHP\PhpStopWordsReview;
use StaticReview\Review\PHP\PhpCPDReview;
use StaticReview\Review\PHP\PhpMDReview;
use StaticReview\Review\PHP\PhpCodeSnifferReview;
use StaticReview\Review\SCSS\SassConvertFixerReview;
use StaticReview\Review\SCSS\ScssLintReview;
use StaticReview\Review\YML\YmlLintReview;
use StaticReview\Review\XML\XmlLintReview;
use StaticReview\Review\JS\JsStopWordsReview;
use StaticReview\Review\GIT\GitConflictReview;
use StaticReview\StaticReview;
use StaticReview\TestingReview;
use StaticReview\VersionControl\GitVersionControl;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use StaticReview\Reporter\Reporter;
use StaticReview\Issue\Issue;
use Symfony\Component\Console\Style\SymfonyStyle;

class PreCommitCommand extends Command
{
    const AUTO_ADD_GIT = true;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
          ->setName('check')->setDescription('Scan and check all files added to commit')
          ->addOption('phpunit', null, InputOption::VALUE_OPTIONAL, 'Phpunit feature state')
          ->addOption('phpunit-bin-path', null, InputOption::VALUE_OPTIONAL, 'Phpunit bin path')
          ->addOption('phpunit-conf', null, InputOption::VALUE_OPTIONAL, 'Phpunit conf path');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $git = new GitVersionControl();
        $stagedFiles = $git->getStagedFiles();
        $projectBase = $git->getProjectBase();
        $reporter = new Reporter($output, count($stagedFiles));

        $review = new StaticReview($reporter);
        $review->addReview(new ComposerLockReview())
          ->addReview(new ComposerLintReview())
          ->addReview(new PhpLintReview())
          ->addReview(new PhpStopWordsReview())
          ->addReview(new JsStopWordsReview())
          ->addReview(new EsLintReview(self::AUTO_ADD_GIT))
          ->addReview(new YmlLintReview())
          ->addReview(new JsonLintReview())
          ->addReview(new XmlLintReview())
          ->addReview(new GitConflictReview());

        // --------------------------------------------------------
        // Front Dev profile
        // --------------------------------------------------------
        /*$review->addReview(new ScssLintReview())
          ->addReview(new SassConvertFixerReview(self::AUTO_ADD_GIT));*/

        // --------------------------------------------------------
        // Dev PHP profile
        // --------------------------------------------------------
        $phpCodeSniffer = new PhpCodeSnifferReview();
        $phpCodeSniffer->setOption('standard', 'Pear');
        $phpCodeSniffer->setOption('sniffs', 'PEAR.Commenting.FunctionComment');

        $review->addReview(new PhpCPDReview())
          ->addReview(new PhpMDReview())
          ->addReview($phpCodeSniffer);
        // --------------------------------------------------------

        $review->files($stagedFiles);

        $reporter->displayReport();

        $testingReporter = new Reporter($output, 0);
        // --------------------------------------------------------
        // Dev PHP profile
        // --------------------------------------------------------
        if (!$reporter->hasIssueLevel(Issue::LEVEL_ERROR) && count($stagedFiles) > 0) {
            $testingReview = new TestingReview($testingReporter);
            if ($input->getOption('phpunit')) {
                $testingReview->addReview(new PhpUnitReview($input->getOption('phpunit-bin-path'), $input->getOption('phpunit-conf'), $projectBase));
            }
            $testingReview->review();

            $testingReporter->displayReport();
        }
        // --------------------------------------------------------

        if ($reporter->hasIssueLevel(Issue::LEVEL_ERROR) || $testingReporter->hasIssueLevel(Issue::LEVEL_ERROR)) {
            $io->error('✘ Please fix the errors above or use --no-verify.');
            exit(1);
        } elseif ($reporter->hasIssueLevel(Issue::LEVEL_WARNING) || $testingReporter->hasIssueLevel(Issue::LEVEL_WARNING)) {
            $io->note('Try to fix warnings !');
        } else {
            $io->success('✔ Looking good.');
        }
        exit(0);
    }
}
