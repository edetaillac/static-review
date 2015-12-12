<?php

namespace StaticReview\Reporter;

use StaticReview\Collection\IssueCollection;
use StaticReview\File\FileInterface;
use StaticReview\Issue\Issue;
use StaticReview\Review\ReviewInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class Reporter implements ReporterInterface
{
    protected $issues;
    protected $progress;
    protected $total;
    protected $current;
    protected $output;

    /**
     * Initializes a new instance of the Reporter class.
     *
     * @param  $output
     * @param  $total
     *
     * @return Reporter
     */
    public function __construct(OutputInterface $output, $total)
    {
        $this->issues = new IssueCollection();
        $this->output = $output;

        if ($total > 1) {
            $this->output->writeln('');
            ProgressBar::setFormatDefinition('minimal', ' <fg=cyan>Reviewing file %current% of %max%.</>');
            $this->progress = new ProgressBar($output, $total);
            $this->progress->setFormat('minimal');
            $this->progress->start();
        }

        $this->total = $total;
        $this->current = 1;
    }

    /**
     * Advance ProgressBar.
     */
    public function progress()
    {
        if (isset($this->progress)) {
            $this->progress->advance();
        }
        ++$this->current;
    }

    /**
     * @return int
     */
    public function getCurrent()
    {
        return $this->current;
    }

    /**
     * @return mixed
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Reports an Issue raised by a Review.
     *
     * @param int             $level
     * @param string          $message
     * @param ReviewInterface $review
     * @param FileInterface   $file
     * @param int             $line
     *
     * @return Reporter
     */
    public function report($level, $message, ReviewInterface $review, FileInterface $file = null, $line = null)
    {
        $issue = new Issue($level, $message, $review, $file, $line);
        $this->issues->append($issue);

        return $this;
    }

    /**
     * Reports an Info Issue raised by a Review.
     *
     * @param string          $message
     * @param ReviewInterface $review
     * @param FileInterface   $file
     * @param int             $line
     *
     * @return Reporter
     */
    public function info($message, ReviewInterface $review, FileInterface $file = null, $line = null)
    {
        $this->report(Issue::LEVEL_INFO, $message, $review, $file, $line);

        return $this;
    }

    /**
     * Reports an Warning Issue raised by a Review.
     *
     * @param string          $message
     * @param ReviewInterface $review
     * @param FileInterface   $file
     * @param int             $line
     *
     * @return Reporter
     */
    public function warning($message, ReviewInterface $review, FileInterface $file = null, $line = null)
    {
        $this->report(Issue::LEVEL_WARNING, $message, $review, $file, $line);

        return $this;
    }

    /**
     * Reports an Error Issue raised by a Review.
     *
     * @param string          $message
     * @param ReviewInterface $review
     * @param FileInterface   $file
     * @param int             $line
     *
     * @return Reporter
     */
    public function error($message, ReviewInterface $review, FileInterface $file = null, $line = null)
    {
        $this->report(Issue::LEVEL_ERROR, $message, $review, $file, $line);

        return $this;
    }

    /**
     * Checks if the reporter has revieved any Issues.
     *
     * @return IssueCollection
     */
    public function hasIssues()
    {
        return count($this->issues) > 0;
    }

    /**
     * @param Issue $a
     * @param Issue $b
     *
     * @return int
     */
    protected static function cmpIssues(Issue $a, Issue $b)
    {
        if ($a->getReviewName().$a->getLine() == $b->getReviewName().$b->getLine()) {
            return 0;
        }

        if ($a->getReviewName() == $b->getReviewName()) {
            return $a->getLine() > $b->getLine();
        }

        return strcmp($a->getReviewName(), $b->getReviewName());
    }

    /**
     * Gets the reporters IssueCollection.
     *
     * @return IssueCollection
     */
    public function getIssues($ordered = false, $filterLevel = false)
    {
        $issues = $this->issues;
        if ($filterLevel !== false) {
            $issues = $this->filterIssues($filterLevel);
        }

        if ($ordered) {
            $arrayIssues = $issues->toArray();
            usort($arrayIssues, array('StaticReview\Reporter\Reporter', 'cmpIssues'));
            $issues = new IssueCollection($arrayIssues);
        }

        return $issues;
    }

    /**
     * @param $filterLevel
     *
     * @return IssueCollection
     */
    public function filterIssues($filterLevel)
    {
        $issues = array();
        foreach ($this->getIssues() as $issue) {
            if ($issue->getLevel() == $filterLevel) {
                $issues[] = $issue;
            }
        }

        return new IssueCollection($issues);
    }

    /**
     * Check if IssueLevel is reached in IssueCollection.
     *
     * @return bool
     */
    public function hasIssueLevel($level)
    {
        foreach ($this->getIssues() as $issue) {
            if ($issue->matches($level)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Display report.
     */
    public function displayReport()
    {
        if (isset($this->progress)) {
            $this->output->writeln('');
        }
        $this->displayIssues($this->getIssues(true, Issue::LEVEL_INFO));
        $this->displayIssues($this->getIssues(true, Issue::LEVEL_WARNING));
        $this->displayIssues($this->getIssues(true, Issue::LEVEL_ERROR));
    }

    /**
     * @param IssueCollection $issues
     */
    public function displayIssues($issues)
    {
        $lastReviewName = '';

        foreach ($issues as $issue) {
            if ($lastReviewName == '' || $lastReviewName != $issue->getReviewName()) {
                $this->output->writeln('');
                $this->output->writeln(sprintf(' <fg=%s>%s :</>', $issue->getColour(), $issue->getReviewName()));
                $lastReviewName = $issue->getReviewName();
            }
            $this->output->writeln(sprintf('<fg=%s>%s</>', $issue->getColour(), $issue));
        }
    }

    /**
     * Display simple message.
     *
     * @param $message
     */
    public function displayMsg($message)
    {
        $this->output->writeln('');
        $this->output->writeln($message);
    }
}
