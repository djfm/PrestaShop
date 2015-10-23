<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation\FacetQueryHelper;

use PrestaShop\PrestaShop\Core\Foundation\Database\AutoPrefixingDatabase;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryContext;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Facet;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter;

abstract class AbstractFacetQueryHelper
{
    protected $db;
    protected $context;

    public function __construct(
        AutoPrefixingDatabase $db,
        QueryContext $context
    ) {
        $this->db = $db;
        $this->context = $context;
    }

    abstract public function getJoinsSQLForQuery($facetIndex, Facet $facet);
    abstract public function getFilterConditionSQLForQuery($facetIndex, Filter $filter);
    abstract public function getUpdatedFacet(array $queryParts, Facet $facet);
    abstract public function buildFilterFromLabel($label);
}
