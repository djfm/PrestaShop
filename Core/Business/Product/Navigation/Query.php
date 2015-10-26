<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

class Query
{
    private $facets = [];
    private $pagination;
    private $sortOption;

    public function __construct()
    {
        $this->pagination = new PaginationQuery();
    }

    public function withoutFacet($identifier)
    {
        $query = clone $this;
        $query->clearFacets();
        foreach ($this->getFacets() as $facet) {
            if ($facet->getIdentifier() !== $identifier) {
                $query->addFacet(clone $facet);
            }
        }

        return $query;
    }

    public function addFacet(Facet $facet)
    {
        $this->facets[] = $facet;

        return $this;
    }

    public function clearFacets()
    {
        $this->facets = [];

        return $this;
    }

    public function getFacets()
    {
        return $this->facets;
    }

    public function getFacetByIdentifier($identifier)
    {
        foreach ($this->getFacets() as $facet) {
            if ($facet->getIdentifier() === $identifier) {
                return $facet;
            }
        }

        return;
    }

    public function setPagination(PaginationQuery $pagination)
    {
        $this->pagination = $pagination;

        return $this;
    }

    public function getPagination()
    {
        return $this->pagination;
    }

    public function setSortOption(SortOption $sortOption = null)
    {
        $this->sortOption = $sortOption;

        return $this;
    }

    public function getSortOption()
    {
        return $this->sortOption;
    }

    public function sortFacets()
    {
        usort($this->facets, function (Facet $a, Facet $b) {
            return $a->getPosition() - $b->getPosition();
        });

        return $this;
    }
}
