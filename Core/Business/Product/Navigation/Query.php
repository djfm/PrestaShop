<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

class Query
{
    private $facets = [];
    private $pagination;

    public function addFacet(Facet $facet)
    {
        $this->facets[] = $facet;
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
        return null;
    }

    public function withoutFacet($identifier)
    {
        $query = new Query;
        foreach ($this->getFacets() as $facet) {
            $add = false;
            if (is_string($identifier)) {
                $add = $facet->getIdentifier() !== $identifier;
            } else {
                $add = !$identifier($facet->getIdentifier());
            }

            if ($add) {
                $query->addFacet(clone $facet);
            }
        }
        return $query;
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
}
