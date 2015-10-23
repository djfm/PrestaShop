<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation\FacetQueryHelper;

use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Facet;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryAssembler;

class AttributeGroupQueryHelper extends AbstractFacetQueryHelper
{
    public function getJoinsSQLForQuery($facetIndex, Facet $facet)
    {
        $id_attribute_group = (int)$facet->getCondition()['id_attribute_group'];
        $i = $facetIndex;
        return "INNER JOIN prefix_product_attribute product_attribute{$i}
                    ON product_attribute{$i}.id_product = product.id_product
                INNER JOIN prefix_product_attribute_combination product_attribute_combination{$i}
                    ON product_attribute_combination{$i}.id_product_attribute = product_attribute{$i}.id_product_attribute
                INNER JOIN prefix_attribute attribute{$i}
                    ON attribute{$i}.id_attribute = product_attribute_combination{$i}.id_attribute
                INNER JOIN prefix_attribute_group attribute_group{$i}
                    ON attribute_group{$i}.id_attribute_group = attribute{$i}.id_attribute_group
                    AND attribute_group{$i}.id_attribute_group = $id_attribute_group";
        ;
    }

    public function getFilterConditionSQLForQuery($facetIndex, Filter $filter)
    {
        $id_attribute = (int)$filter->getCondition()['id_attribute'];
        return "attribute{$facetIndex}.id_attribute = $id_attribute";
    }

    public function getUpdatedFacet(array $queryParts, Facet $initialFacet)
    {
        $queryParts['select']   = 'attribute.id_attribute, attribute_lang.name, count(DISTINCT product.id_product) as magnitude';
        $queryParts['groupBy']  = 'attribute.id_attribute';
        $queryParts['orderBy']  = 'attribute.position';
        $queryParts['from']    .= ' ' . $this->getJoinsSQLForQuery('', $initialFacet) .
                                  ' ' . 'INNER JOIN prefix_attribute_lang attribute_lang
                                            ON attribute_lang.id_attribute = attribute.id_attribute
                                            AND attribute_lang.id_lang = ' . (int)$this->context->getLanguageId()
        ;

        $sql = (new QueryAssembler)->assemble($queryParts);

        $rows = $this->db->select($sql);

        $facet = clone $initialFacet;

        $facet->clearFilters();

        foreach ($rows as $row) {
            $filter = new Filter;
            $filter
                ->setCondition(['id_attribute' => (int)$row['id_attribute']])
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
        $id_attribute = (int)$this->db->getValue('
            SELECT id_attribute FROM prefix_attribute_lang
            WHERE id_lang = ' . (int)$this->context->getLanguageId() . '
                AND name = "' . $this->db->escape($label) . '"'
        );

        $filter = new Filter;
        $filter
            ->setCondition(['id_attribute' => $id_attribute])
            ->setLabel($label)
        ;
        return $filter;
    }
}
