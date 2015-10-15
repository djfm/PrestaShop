<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

class QueryResult
{
    /**
     * The products found.
     * @var array
     */
    private $products;

    /**
     * The total number of pages for this query.
     * @var int
     */
    private $pagesCount;

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

    /**
     * Next potential query.
     * @var ProductQuery
     */
    private $updatedFilters;

    public function setProducts(array $products)
    {
        $this->products = $products;
        return $this;
    }

    public function getProducts()
    {
        return $this->products;
    }

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

    public function getResultsCount()
    {
        return count($this->getProducts());
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

    public function setUpdatedFilters(Query $updatedFilters)
    {
        $this->updatedFilters = $updatedFilters;
        return $this;
    }

    public function getUpdatedFilters()
    {
        return $this->updatedFilters;
    }
}
