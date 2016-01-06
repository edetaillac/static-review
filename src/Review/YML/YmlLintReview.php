<?php

namespace StaticReview\Review\YML;

use StaticReview\Review\ReviewableInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use StaticReview\Reporter\ReporterInterface;
use StaticReview\Review\AbstractReview;

class YmlLintReview extends AbstractReview
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
        return parent::canReview($file) && $file->getExtension() === 'yml';
    }

    /**
     * @param ReporterInterface        $reporter
     * @param ReviewableInterface|null $file
     */
    public function review(ReporterInterface $reporter, ReviewableInterface $file = null)
    {
        // delete PHP code in yaml files to avoid ParseException
        $ymlData = preg_replace('|(<\?php.*\?>)|i', '', file_get_contents($file->getFullPath()));
        // delete Namespace class on scalar value to avoid ParseException with escape caracter
        $ymlData = preg_replace('|(:\s*\".*\")|i', ': ""', $ymlData);
        try {
            print_r($ymlData);
            Yaml::parse($ymlData, false, true);
        } catch (ParseException $e) {
            preg_match('/at line ([0-9]+)/i', $e->getMessage(), $matches);
            $line = isset($matches[1]) ? $matches[1] : null;
            $reporter->error('Unable to parse the YAML file', $this, $file, $line);
        }
    }
}
