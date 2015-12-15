<?php

namespace StaticReview\Review\XML;

use StaticReview\Reporter\ReporterInterface;
use StaticReview\Review\AbstractReview;
use StaticReview\Review\ReviewableInterface;

class XmlLintReview extends AbstractReview
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
        return parent::canReview($file) && $file->getExtension() === 'xml';
    }

    /**
     * {@inheritdoc}
     */
    public function review(ReporterInterface $reporter, ReviewableInterface $file = null)
    {
        $cmd = sprintf('xmllint --noout %s', $file->getFullPath());
        $process = $this->getProcess($cmd);
        $process->run();
        if (!$process->isSuccessful()) {
            $output = $process->getErrorOutput();
            preg_match('/:([0-9]+):/i', $output, $matches);
            $line = isset($matches[1]) ? $matches[1] : null;
            $reporter->error('Unable to parse the XML file', $this, $file, $line);
        }
    }
}
