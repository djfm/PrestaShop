<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation\DatabaseFacet;

use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter;

class AttributeGroupFacet extends AbstractDatabaseFacet
{
    public function getJoinsSQL($facetIndex)
    {
        $id_attribute_group = (int) $this->getCondition()['id_attribute_group'];
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
    }

    public function getFilterSQL($facetIndex, Filter $filter)
    {
        $id_attribute = (int) $filter->getCondition()['id_attribute'];

        return "attribute{$facetIndex}.id_attribute = $id_attribute";
    }

    public function getQueryPartsForFacetUpdate(array $queryParts)
    {
        $queryParts['select'] = 'attribute.id_attribute, attribute_lang.name as label, count(DISTINCT product.id_product) as magnitude';
        $queryParts['groupBy'] = 'attribute.id_attribute';
        $queryParts['orderBy'] = 'attribute.position';
        $queryParts['from']    .= ' '.$this->getJoinsSQL('')
                                .' '.'INNER JOIN prefix_attribute_lang attribute_lang
                                            ON attribute_lang.id_attribute = attribute.id_attribute
                                            AND attribute_lang.id_lang = '.(int) $this->context->getLanguageId()
        ;

        return $queryParts;
    }

    public function buildFilterFromLabel($label)
    {
        $id_attribute = (int) $this->db->getValue('
            SELECT id_attribute FROM prefix_attribute_lang
            WHERE id_lang = '.(int) $this->context->getLanguageId().'
                AND name = "'.$this->db->escape($label).'"'
        );

        $filter = new Filter();
        $filter
            ->setCondition(['id_attribute' => $id_attribute])
            ->setLabel($label)
        ;

        return $filter;
    }
}
