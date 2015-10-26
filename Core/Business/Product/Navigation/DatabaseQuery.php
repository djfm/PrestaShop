<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

use PrestaShop\PrestaShop\Core\Foundation\Database\AutoPrefixingDatabase;
use Exception;

class DatabaseQuery extends Query
{
    private $db;
    private $context;

    public function __construct(
        AutoPrefixingDatabase $db,
        QueryContext $context
    ) {
        $this->db = $db;
        $this->context = $context;
        parent::__construct();
    }

    public function __call($method, $args)
    {
        $res = call_user_func_array([$this->query, $method], $args);
        if ($res === $this->query) {
            return $this;
        } else {
            return $res;
        }
    }

    public function addFacet(Facet $facet)
    {
        if ($facet instanceof DatabaseFacet\AbstractDatabaseFacet) {
            parent::addFacet($facet);
        } else {
            parent::addFacet($this->decorateFacet($facet));
        }

        return $this;
    }

    private function decorateFacet(Facet $facet)
    {
        if ($facet->getIdentifier() === 'categories') {
            return new DatabaseFacet\CategoryFacet(
                $this->db,
                $this->context,
                $facet
            );
        } elseif (preg_match('/^attributeGroup\d+$/', $facet->getIdentifier())) {
            return new DatabaseFacet\AttributeGroupFacet(
                $this->db,
                $this->context,
                $facet
            );
        } elseif (preg_match('/^feature\d+$/', $facet->getIdentifier())) {
            return new DatabaseFacet\FeatureFacet(
                $this->db,
                $this->context,
                $facet
            );
        } elseif ($facet->getIdentifier() === 'suppliers') {
            return new DatabaseFacet\SupplierFacet(
                $this->db,
                $this->context,
                $facet
            );
        }

        throw new Exception(sprintf(
            'Could not decorate facet identified by `%s` for database query.',
            $facet->getIdentifier()
        ));
    }
}
