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
}
