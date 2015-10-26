<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation\DatabaseFacet;

use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryContext;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Facet;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter;
use PrestaShop\PrestaShop\Core\Foundation\Database\AutoPrefixingDatabase;

abstract class AbstractDatabaseFacet extends Facet
{
    protected $db;
    protected $context;

    public function __construct(
        AutoPrefixingDatabase $db,
        QueryContext $context,
        Facet $facet
    ) {
        $this->db = $db;
        $this->context = $context;

        $this->copyConstructor($facet);
    }

    private function copyConstructor(Facet $facet)
    {
        $this
            ->setLabel($facet->getLabel())
            ->setIdentifier($facet->getIdentifier())
            ->hide($facet->isHidden())
            ->setCondition($facet->getCondition())
            ->setPosition($facet->getPosition())
        ;

        foreach ($facet->getFilters() as $filter) {
            $this->addFilter($filter);
        }

        return $this;
    }

    abstract public function getJoinsSQL($facetIndex);
    abstract public function getFilterSQL($facetIndex, Filter $filter);
    abstract public function getQueryPartsForFacetUpdate(array $queryParts);
    abstract public function buildFilterFromLabel($label);
}
