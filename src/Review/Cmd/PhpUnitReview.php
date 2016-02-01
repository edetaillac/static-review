<?php

namespace StaticReview\Review\Cmd;

use StaticReview\Reporter\ReporterInterface;
use StaticReview\Review\AbstractReview;
use StaticReview\Review\ReviewableInterface;
use Symfony\Component\Process\Process;

class PhpUnitReview extends AbstractReview
{
    protected $phpUnitConfigPath;
    protected $projectBase;

    const LEVEL_SYMFONY_DEPRECATION_WEAK = 'weak';
    const LEVEL_SYMFONY_DEPRECATION_STRICT = 'strict';

    /**
     * Constructor.
     *
     * @param $phpUnitBinPath
     * @param $phpUnitConfigPath
     * @param $projectBase
     */
    public function __construct($phpUnitBinPath, $phpUnitConfigPath, $projectBase)
    {
        $this->phpUnitBinPath = $phpUnitBinPath ? $phpUnitBinPath : 'phpunit';
        $this->phpUnitConfigPath = $phpUnitConfigPath;
        $this->projectBase = $projectBase;
    }

    /**
     * Git conflict review.
     *
     * @param ReporterInterface $reporter
     */
    public function review(ReporterInterface $reporter, ReviewableInterface $file = null)
    {
        if ($this->phpUnitConfigPath && !(is_file($this->phpUnitConfigPath) || is_file($this->projectBase.'/'.$this->phpUnitConfigPath))) {
            $reporter->error('Phpunit config path is not correct.', $this, $file);

            return;
        }

        $cmd = sprintf('export SYMFONY_DEPRECATIONS_HELPER=%s;', self::LEVEL_SYMFONY_DEPRECATION_WEAK);
        $cmd .= sprintf('%s --exclude_group slow --stop-on-failure%s', $this->phpUnitBinPath, $this->phpUnitConfigPath ? ' -c '.$this->phpUnitConfigPath : '');
        $process = $this->getProcess($cmd, $this->projectBase, null, null, 960);

        echo "\n";
        $process->run(
          function ($type, $buffer) {
              if (Process::ERR !== $type) {
                  if (in_array($buffer, array('.', 'F', 'E', 'R', 'S', 'I')) || preg_match('/\( [0-9]+\%\)/', $buffer)) {
                      echo $buffer;
                  }
              }
          }
        );
        echo "\n";

        if (preg_match('|Usage: phpunit|i', $process->getOutput())) {
            $reporter->error('You must specify Phpunit config path [--phpunit-conf PATH].', $this, $file);
        } elseif (!$process->isSuccessful()) {
            $reporter->error('Fix the Unit Tests !!!', $this, $file);
        }
    }
}
