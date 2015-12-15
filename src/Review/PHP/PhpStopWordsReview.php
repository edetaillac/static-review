<?php

namespace StaticReview\Review\PHP;

use StaticReview\Reporter\ReporterInterface;
use StaticReview\Review\AbstractReview;
use StaticReview\Review\ReviewableInterface;

class PhpStopWordsReview extends AbstractReview
{
    /**
     * Determins if a given file should be reviewed.
     *
     * @param ReviewableInterface $file
     *
     * @return bool
     */
    public function canReview(ReviewableInterface $file = null)
    {
        return parent::canReview($file) && $file->getExtension() === 'php';
    }

    /**
     * Checks PHP StopWords.
     */
    public function review(ReporterInterface $reporter, ReviewableInterface $file = null)
    {
        $stopWordsPhp = array(
            'var_dump()' => '[^a-zA-Z]var_dump(',
            'die()'      => '[^a-zA-Z]die(',
        );

        // Check StopWords
        foreach ($stopWordsPhp as $key => $word) {
            $cmd = sprintf('grep --ignore-case --quiet \'%s\' %s', $word, $file->getFullPath());
            $process = $this->getProcess($cmd);
            $process->run();

            if ($process->isSuccessful()) {
                $reporter->error(sprintf('expr "%s" detected', $key), $this, $file);
            }
        }
    }
}
