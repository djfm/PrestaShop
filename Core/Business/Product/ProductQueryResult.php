<?php

namespace PrestaShop\PrestaShop\Core\Business\Product;

class ProductQueryResult
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
    private $totalPages;

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

    public function setTotalPages($totalPages)
    {
        $this->totalPages = $totalPages;
        return $this;
    }

    public function getTotalPages()
    {
        return $this->totalPages;
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

    public function setUpdatedFilters(ProductQuery $updatedFilters)
    {
        $this->updatedFilters = $updatedFilters;
        return $this;
    }

    public function getUpdatedFilters()
    {
        return $this->updatedFilters;
    }
}
