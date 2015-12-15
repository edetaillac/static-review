<?php

namespace StaticReview\Review\PHP;

use StaticReview\Reporter\ReporterInterface;
use StaticReview\Review\AbstractReview;
use StaticReview\Review\ReviewableInterface;

class ComposerReview extends AbstractReview
{
    private $composerJsonDetected = false;
    private $composerLockDetected = false;

        /**
         * Determins if a given file should be reviewed.
         *
         * @param ReviewableInterface $file
         *
         * @return bool
         */
        public function canReview(ReviewableInterface $file = null)
        {
            return parent::canReview($file) && ($file->getExtension() === 'json' || $file->getExtension() === 'lock');
        }

    /**
     * Checks Composer json and lock files.
     */
    public function review(ReporterInterface $reporter, ReviewableInterface $file = null)
    {
        if ($file->getFileName() == 'composer.json') {
            $this->composerJsonDetected = true;
        }

        if ($file->getFileName() == 'composer.lock') {
            $this->composerLockDetected = true;
        }

        // Check if we are on the Last File
        if ((($reporter->getCurrent() - 1) == $reporter->getTotal()) && $this->composerJsonDetected && !$this->composerLockDetected) {
            $reporter->warning('You must commit composer.lock with composer.json', $this, $file);
        }
    }
}
