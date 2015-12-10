<?php

namespace StaticReview\Review\PHP;

use StaticReview\File\FileInterface;
use StaticReview\Reporter\ReporterInterface;
use StaticReview\Review\AbstractReview;

class ComposerReview extends AbstractReview
{
    private $composerJsonDetected = false;
    private $composerLockDetected = false;

    /**
     * Checks Composer json and lock files.
     */
    public function review(ReporterInterface $reporter, FileInterface $file = null)
    {
        if ($file->getFileName() == 'composer.json') {
            $this->composerJsonDetected = true;
        }

        if ($file->getFileName() == 'composer.lock') {
            $this->composerLockDetected = true;
        }

        // Check if we are on the Last File
        if ((($reporter->getCurrent() + 1) == $reporter->getTotal()) && $this->composerJsonDetected && !$this->composerLockDetected) {
            $reporter->warning('You must commit composer.lock with composer.json', $this);
        }
    }
}
