<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation\DatabaseFacet;

use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter;

class CategoryFacet extends AbstractDatabaseFacet
{
    public function getRootIdCategory()
    {
        $condition = $this->getCondition();

        if (isset($condition['id_category'])) {
            return (int) $condition['id_category'];
        } else {
            return;
        }
    }

    public function getJoinsSQL($facetIndex)
    {
        return "INNER JOIN prefix_category_product category_product{$facetIndex}
                    ON category_product{$facetIndex}.id_product = product.id_product
                INNER JOIN prefix_category_shop category_shop{$facetIndex}
                    ON category_shop{$facetIndex}.id_category = category_product{$facetIndex}.id_category
                    AND category_shop{$facetIndex}.id_shop = ".(int) $this->context->getShopId()
        ;
    }

    public function getFilterSQL($facetIndex, Filter $filter)
    {
        return "category_product{$facetIndex}.id_category = ".(int) $filter->getCondition()['id_category'];
    }

    public function getQueryPartsForFacetUpdate(array $queryParts)
    {
        $queryParts['select'] = 'category.id_category, category_lang.name as label, count(distinct product.id_product) as magnitude';
        $queryParts['from'] .= ' '.$this->getJoinsSQL('')
                            .' '.'INNER JOIN prefix_category category
                                            ON category_product.id_category = category.id_category
                                      INNER JOIN prefix_category_lang category_lang ON
                                            category_lang.id_category = category.id_category
                                            AND category_lang.id_lang = '.(int) $this->context->getLanguageId().'
                                            AND category_lang.id_shop = '.(int) $this->context->getShopId()
        ;

        if (($id = $this->getRootIdCategory())) {
            $queryParts['from'] .= " INNER JOIN prefix_category root_category
                                        ON root_category.id_category = $id
                                            AND category.level_depth <= root_category.level_depth + 1
                                            AND category.nleft > root_category.nleft
                                            AND category.nright < root_category.nright"
            ;
        }

        $queryParts['groupBy'] = 'category.id_category';
        $queryParts['orderBy'] = 'category.level_depth ASC, category.nleft ASC';

        return $queryParts;
    }

    public function buildFilterFromLabel($label)
    {
        $filter = new Filter();
        $id_category = (int) $this->db->getValue(
            'SELECT id_category FROM prefix_category_lang WHERE
                name = "'.$this->db->escape($label).'"
                AND id_lang = '.(int) $this->context->getLanguageId().'
                AND id_shop = '.(int) $this->context->getShopId()
        );

        $filter->setCondition(['id_category' => $id_category]);

        return $filter;
    }
}
