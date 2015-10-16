<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

class PaginationResult
{
    /**
     * The total number of pages for this query.
     * @var int
     */
    private $pagesCount;

    /**
     * The number of results actually retruned
     * @var int
     */
    private $resultsCount;

    /**
     * The total number of results
     * @var int
     */
    private $totalResultsCount;

    /**
     * The index of the returned page.
     * @var int
     */
    private $page;

    public function setPagesCount($pagesCount)
    {
        $this->pagesCount = $pagesCount;
        return $this;
    }

    public function getPagesCount()
    {
        return $this->pagesCount;
    }

    public function setTotalResultsCount($totalResultsCount)
    {
        $this->totalResultsCount = $totalResultsCount;
        return $this;
    }

    public function getTotalResultsCount()
    {
        return $this->totalResultsCount;
    }

    public function setResultsCount($resultsCount)
    {
        $this->resultsCount = $resultsCount;
        return $this;
    }

    public function getResultsCount()
    {
        return $this->resultsCount;
    }

    public function setPage($page)
    {
        $this->page = $page;
        return $this;
    }

    public function getPage()
    {
        return $this->page;
    }
}
