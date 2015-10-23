<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation\FacetQueryHelper;

use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Facet;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryAssembler;

class SuppliersQueryHelper extends AbstractFacetQueryHelper
{
    public function getJoinsSQLForQuery($facetIndex, Facet $facet)
    {
        $i = $facetIndex;
        return "INNER JOIN prefix_product_supplier product_supplier{$i}
                    ON product_supplier{$i}.id_product = product.id_product
                INNER JOIN prefix_supplier supplier{$i}
                    ON supplier{$i}.id_supplier = product_supplier{$i}.id_supplier";
        ;
    }

    public function getFilterConditionSQLForQuery($facetIndex, Filter $filter)
    {
        $id_supplier = (int)$filter->getCondition()['id_supplier'];
        return "supplier{$facetIndex}.id_supplier = $id_supplier";
    }

    public function getUpdatedFacet(array $queryParts, Facet $initialFacet)
    {
        $queryParts['select']   = 'supplier.id_supplier, supplier.name, count(DISTINCT product.id_product) as magnitude';
        $queryParts['groupBy']  = 'supplier.id_supplier';
        $queryParts['orderBy']  = 'supplier.name';
        $queryParts['from']    .= ' ' . $this->getJoinsSQLForQuery('', $initialFacet);

        $sql = (new QueryAssembler)->assemble($queryParts);

        $rows = $this->db->select($sql);

        $facet = clone $initialFacet;

        $facet->clearFilters();

        foreach ($rows as $row) {
            $filter = new Filter;
            $filter
                ->setCondition(['id_supplier' => (int)$row['id_supplier']])
                ->setMagnitude((int)$row['magnitude'])
                ->setLabel($row['name'])
                ->setEnabled(false)
            ;
            $facet->addFilter($filter);
        }

        return $facet;
    }

    public function buildFilterFromLabel($label)
    {
        $id_supplier = (int)$this->db->getValue('
            SELECT id_supplier FROM prefix_supplier
            WHERE name = "' . $this->db->escape($label) . '"'
        );

        $filter = new Filter;
        $filter
            ->setCondition(['id_supplier' => $id_supplier])
            ->setLabel($label)
        ;
        return $filter;
    }
}
