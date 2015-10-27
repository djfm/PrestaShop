<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation\DatabaseFacet;

use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter;

class SupplierFacet extends AbstractDatabaseFacet
{
    public function getJoinsSQL($facetIndex)
    {
        $i = $facetIndex;

        return "INNER JOIN prefix_product_supplier product_supplier{$i}
                    ON product_supplier{$i}.id_product = product.id_product
                INNER JOIN prefix_supplier supplier{$i}
                    ON supplier{$i}.id_supplier = product_supplier{$i}.id_supplier";
    }

    public function getFilterSQL($facetIndex, Filter $filter)
    {
        $id_supplier = (int) $filter->getCondition()['id_supplier'];

        return "supplier{$facetIndex}.id_supplier = $id_supplier";
    }

    public function getQueryPartsForFacetUpdate(array $queryParts)
    {
        $queryParts['select'] = 'supplier.id_supplier, supplier.name as label, count(DISTINCT product.id_product) as magnitude';
        $queryParts['groupBy'] = 'supplier.id_supplier';
        $queryParts['orderBy'] = 'supplier.name';
        $queryParts['from']    .= ' '.$this->getJoinsSQL('');

        // Don't include supplier filter if it is part of the facet
        // as a whole.
        $id_supplier = isset($this->getCondition()['id_supplier']) ?
            (int) $this->getCondition()['id_supplier'] :
            null
        ;
        if ($id_supplier) {
            $queryParts['from']   .= ' AND supplier.id_supplier != '.$id_supplier;
        }

        return $queryParts;
    }

    public function buildFilterFromLabel($label)
    {
        $id_supplier = (int) $this->db->getValue('
            SELECT id_supplier FROM prefix_supplier
            WHERE name = "'.$this->db->escape($label).'"'
        );

        $filter = new Filter();
        $filter
            ->setCondition(['id_supplier' => $id_supplier])
            ->setLabel($label)
        ;

        return $filter;
    }
}
