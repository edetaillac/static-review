<?php

namespace StaticReview\Command;

use StaticReview\Collection\FileCollection;
use StaticReview\File\File;
use StaticReview\Review\Composer\ComposerLintReview;
use StaticReview\Review\JS\EsLintReview;
use StaticReview\Review\JSON\JsonLintReview;
use StaticReview\Review\PHP\PhpLintReview;
use StaticReview\Review\PHP\PhpCsFixerReview;
use StaticReview\Review\Composer\ComposerLockReview;
use StaticReview\Review\PHP\PhpCodeSnifferReview;
use StaticReview\Review\PHP\PhpStopWordsReview;
use StaticReview\Review\PHP\PhpCPDReview;
use StaticReview\Review\PHP\PhpMDReview;
use StaticReview\Review\SCSS\SassConvertFixerReview;
use StaticReview\Review\SCSS\ScssLintReview;
use StaticReview\Review\YML\YmlLintReview;
use StaticReview\Review\XML\XmlLintReview;
use StaticReview\Review\JS\JsStopWordsReview;
use StaticReview\Review\GIT\GitConflictReview;
use StaticReview\Review\GIT\NoCommitTagReview;
use StaticReview\StaticReview;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use StaticReview\Reporter\Reporter;
use StaticReview\Issue\Issue;
use Symfony\Component\Console\Style\SymfonyStyle;

class CheckFileCommand extends Command
{
    const AUTO_ADD_GIT = false;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
          ->setName('checkFile')->setDescription('Scan specific file')
          ->addArgument('file', InputArgument::REQUIRED, 'Filename to check ?');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $fileInput = trim($input->getArgument('file'));

        if (!$realPath = realpath($fileInput)) {
            $io->error(sprintf('File %s is not exist.', $fileInput));
            exit(1);
        }

        $pathInfoFile = pathinfo($realPath);
        $file = new File('', $realPath, $pathInfoFile['dirname']);
        $fileCollection = new FileCollection();
        $fileCollection = $fileCollection->append($file);

        $reporter = new Reporter($output, 1);

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
          ->addReview(new GitConflictReview())
          ->addReview(new NoCommitTagReview());

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
          ->addReview(new PhpCsFixerReview(self::AUTO_ADD_GIT))
          ->addReview(new PhpMDReview())
          ->addReview($phpCodeSniffer);
        // --------------------------------------------------------

        // Review the staged files.
        $review->files($fileCollection);

        $reporter->displayReport();

        if ($reporter->hasIssueLevel(Issue::LEVEL_ERROR)) {
            $io->error('✘ Please fix the errors above or use --no-verify.');
            exit(1);
        } elseif ($reporter->hasIssueLevel(Issue::LEVEL_WARNING)) {
            $io->note('Try to fix warnings !');
        } else {
            $io->success('✔ Looking good.');
        }

        exit(0);
    }
}
