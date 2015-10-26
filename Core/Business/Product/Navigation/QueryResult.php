<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

class QueryResult
{
    /**
     * The products found.
     *
     * @var array
     */
    private $products = [];

    /**
     * Next potential query.
     *
     * @var ProductQuery
     */
    private $updatedFilters;

    /**
     * Available sort options.
     *
     * @var array of SortOption
     */
    private $sortOptions = [];

    /**
     * @var PaginationResult
     */
    private $paginationResult;

    /**
     * The URL fragment for SEO friendly URLs.
     *
     * @var string
     */
    private $urlFragment;

    public function __construct()
    {
        $this->paginationResult = new PaginationResult();
    }

    public function setProducts(array $products)
    {
        $this->products = $products;

        return $this;
    }

    public function getProducts()
    {
        return $this->products;
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

    public function setSortOptions(array $sortOptions)
    {
        $this->sortOptions = [];
        array_map([$this, 'addSortOption'], $sortOptions);

        return $this;
    }

    public function getSortOptions()
    {
        return $this->sortOptions;
    }

    public function addSortOption(SortOption $option)
    {
        $this->sortOptions[] = $option;

        return $this;
    }

    public function setPaginationResult(PaginationResult $paginationResult)
    {
        $this->paginationResult = $paginationResult;

        return $this;
    }

    public function getPaginationResult()
    {
        return $this->paginationResult;
    }

    public function setURLFragment($urlFragment)
    {
        $this->urlFragment = $urlFragment;

        return $this;
    }

    public function getURLFragment()
    {
        return $this->urlFragment;
    }
}
