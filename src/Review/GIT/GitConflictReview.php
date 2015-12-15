<?php

namespace StaticReview\Review\GIT;

use StaticReview\Reporter\ReporterInterface;
use StaticReview\Review\AbstractReview;
use StaticReview\Review\ReviewableInterface;

class GitConflictReview extends AbstractReview
{
    /**
     * Review any text based file.
     *
     * @link http://stackoverflow.com/a/632786
     *
     * @param ReviewableInterface $file
     *
     * @return bool
     */
    public function canReview(ReviewableInterface $file)
    {
        // check to see if the mime-type starts with 'text'
        return parent::canReview($file) && substr($file->getMimeType(), 0, 4) === 'text';
    }

    /**
     * Git conflict review.
     *
     * @param ReporterInterface   $reporter
     * @param ReviewableInterface $file
     */
    public function review(ReporterInterface $reporter, ReviewableInterface $file = null)
    {
        $gitConflictMarkers = array('>>>>>>', '<<<<<<');

        // Check Git Conflict Markers
        foreach ($gitConflictMarkers as $word) {
            $cmd = sprintf('grep --fixed-strings --ignore-case --quiet "%s" %s', $word, $file->getFullPath());

            $process = $this->getProcess($cmd);
            $process->run();

            if ($process->isSuccessful()) {
                $reporter->error(sprintf('Git Conflict marker "%s" detected', $word), $this, $file);
            }
        }
    }
}
