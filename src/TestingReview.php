<?php

namespace StaticReview;

use StaticReview\Collection\ReviewCollection;
use StaticReview\Reporter\ReporterInterface;

class TestingReview
{
    /**
     * A ReviewCollection.
     */
    protected $reviews;
    protected $reporter;

    /**
     * Constructor.
     *
     * @param ReporterInterface $reporter
     */
    public function __construct(ReporterInterface $reporter)
    {
        $this->reviews = new ReviewCollection();
        $this->setReporter($reporter);
    }

    /**
     * Gets the ReporterInterface instance.
     *
     * @return ReporterInterface
     */
    public function getReporter()
    {
        return $this->reporter;
    }

    /**
     * Sets the ReporterInterface instance.
     *
     * @param ReporterInterface $reporter
     *
     * @return StaticReview
     */
    public function setReporter(ReporterInterface $reporter)
    {
        $this->reporter = $reporter;

        return $this;
    }

    /**
     * @return ReviewCollection
     */
    public function getReviews()
    {
        return $this->reviews;
    }

    /**
     * @param $review
     *
     * @return $this
     */
    public function addReview($review)
    {
        $this->reviews->append($review);

        return $this;
    }

    /**
     * @param ReviewCollection $reviews
     *
     * @return $this
     */
    public function addReviews(ReviewCollection $reviews)
    {
        foreach ($reviews as $review) {
            $this->reviews->append($review);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function review()
    {
        if (count($this->getReviews()) > 0) {
            $this->getReporter()->displayMsg(' <fg=cyan>Tests in progress...</>');
        }

        foreach ($this->getReviews() as $review) {
            $review->review($this->getReporter());
        }

        return $this;
    }
}
