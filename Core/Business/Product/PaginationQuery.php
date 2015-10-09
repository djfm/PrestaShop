<?php

namespace PrestaShop\PrestaShop\Core\Business\Product;

class PaginationQuery
{
    /**
     * The number of the requested page.
     * @var int
     */
    private $page;

    /**
     * The number of results per page to return.
     * @var int
     */
    private $resultsPerPage;

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
