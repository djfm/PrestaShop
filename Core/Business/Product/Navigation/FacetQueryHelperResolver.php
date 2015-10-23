<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

use PrestaShop\PrestaShop\Core\Foundation\Database\AutoPrefixingDatabase;
use Exception;

class FacetQueryHelperResolver
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

    public function getFacetQueryHelper(Facet $facet)
    {
        if ($facet->getIdentifier() === 'categories') {
            return new FacetQueryHelper\CategoriesQueryHelper(
                $this->db,
                $this->context
            );
        } else if (preg_match('/^attributeGroup\d+$/', $facet->getIdentifier())) {
            return new FacetQueryHelper\AttributeGroupQueryHelper(
                $this->db,
                $this->context
            );
        } else if (preg_match('/^feature\d+$/', $facet->getIdentifier())) {
            return new FacetQueryHelper\FeatureQueryHelper(
                $this->db,
                $this->context
            );
        } else if ($facet->getIdentifier() === 'suppliers') {
            return new FacetQueryHelper\SuppliersQueryHelper(
                $this->db,
                $this->context
            );
        }

        throw new Exception(sprintf(
            'Could not find QueryHelper for facet identified by `%s`.',
            $facet->getIdentifier()
        ));
    }
}
