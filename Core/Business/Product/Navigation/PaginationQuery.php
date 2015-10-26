<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

class PaginationQuery
{
    /**
     * The number of the requested page.
     *
     * @var int
     */
    private $page = 1;

    /**
     * The number of results per page to return.
     *
     * @var int
     */
    private $resultsPerPage = 12;

    public function setPage($page)
    {
        $this->page = $page;

        return $this;
    }

    public function getPage()
    {
        return $this->page;
    }

    public function setResultsPerPage($resultsPerPage)
    {
        $this->resultsPerPage = $resultsPerPage;

        return $this;
    }

    public function getResultsPerPage()
    {
        return $this->resultsPerPage;
    }
}
