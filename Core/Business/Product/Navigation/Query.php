<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

class Query
{
    private $facets = [];

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
            if ($facet->getIdentifier() !== $identifier) {
                $query->addFacet(clone $facet);
            }
        }
        return $query;
    }
}
