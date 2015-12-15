<?php

namespace StaticReview\Review\JS;

use StaticReview\Reporter\ReporterInterface;
use StaticReview\Review\AbstractReview;
use StaticReview\Review\ReviewableInterface;

class JsStopWordsReview extends AbstractReview
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
        return parent::canReview($file) && $file->getExtension() === 'js';
    }

    /**
     * Reviewing method.
     *
     * @param ReporterInterface   $reporter
     * @param ReviewableInterface $file
     */
    public function review(ReporterInterface $reporter, ReviewableInterface $file = null)
    {
        // JavaScript debug code that would break IE.
        $stopWordsJs = array(
          'console.debug()' => 'console.debug',
          'console.log()'   => 'console.log',
          'alert()'         => '[^a-zA-Z]alert(',
        );

        // Check StopWords
        foreach ($stopWordsJs as $key => $word) {
            $cmd = sprintf('grep --ignore-case --quiet \'%s\' %s', $word, $file->getFullPath());
            $process = $this->getProcess($cmd);
            $process->run();

            if ($process->isSuccessful()) {
                $reporter->error(sprintf('expr "%s" detected', $key), $this, $file);
            }
        }
    }
}
