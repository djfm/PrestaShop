<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryHelper;

use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryContext;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter\AttributeFilter;

class AttributesQueryHelper
{
    public function getQueryPartsForFiltersUpdate(QueryContext $context)
    {
        $queryParts = [];

        $queryParts['select']   = 'attribute.id_attribute_group, attribute.id_attribute';
        $queryParts['groupBy']  = 'attribute.id_attribute_group, attribute.id_attribute';
        $queryParts['orderBy']  = 'attribute.id_attribute_group, attribute.id_attribute';
        $queryParts['from']     = ' ' . $this->getJoinsSQLForQuery($context, '');

        return $queryParts;
    }

    // TODO: Mulstishop not supported at this point.
    public function getJoinsSQLForQuery(QueryContext $context, $facetIndex)
    {
        $i = $facetIndex;
        return "INNER JOIN prefix_product_attribute product_attribute{$i}
                    ON product_attribute{$i}.id_product = product.id_product
                INNER JOIN prefix_product_attribute_combination product_attribute_combination{$i}
                    ON product_attribute_combination{$i}.id_product_attribute = product_attribute{$i}.id_product_attribute
                INNER JOIN prefix_attribute attribute{$i}
                    ON attribute{$i}.id_attribute = product_attribute_combination{$i}.id_attribute
                INNER JOIN prefix_attribute_group attribute_group{$i}
                    ON attribute_group{$i}.id_attribute_group = attribute{$i}.id_attribute_group"
        ;
    }

    public function getConditionSQLForQuery(AttributeFilter $filter, $facetIndex)
    {
        return "attribute{$facetIndex}.id_attribute_group = " . (int)$filter->getAttributeGroupId()
             . " AND attribute{$facetIndex}.id_attribute = " . (int)$filter->getAttributeId()
        ;
    }
}
