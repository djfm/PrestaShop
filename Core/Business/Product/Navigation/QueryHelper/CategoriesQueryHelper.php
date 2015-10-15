<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryHelper;

use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryContext;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter\CategoryFilter;

class CategoriesQueryHelper
{
    public function getQueryPartsForFiltersUpdate(QueryContext $context, $topLevelCategoryId)
    {
        $queryParts = [];

        $queryParts['select']   = 'other_categories.id_category';
        $queryParts['from']     = ' INNER JOIN prefix_category category ON category.id_category = ' . (int)$topLevelCategoryId . '
                                    INNER JOIN prefix_category_product other_categories
                                        ON other_categories.id_product = product.id_product
                                    INNER JOIN prefix_category_shop category_shop
                                      ON category_shop.id_category = other_categories.id_category
                                        AND category_shop.id_shop = ' . (int)$context->getShopId() . '
                                    INNER JOIN prefix_category other_category
                                        ON other_category.id_category = other_categories.id_category
                                            AND other_category.nleft  >= category.nleft
                                            AND other_category.nright <= category.nright
                                            AND (other_category.level_depth - category.level_depth <= 1)'
                                ;
        $queryParts['groupBy']  = 'other_categories.id_category';
        $queryParts['orderBy']  = 'other_category.level_depth ASC, other_category.nleft ASC';

        return $queryParts;
    }

    public function getJoinsSQLForQuery(QueryContext $context, $facetIndex)
    {
        return "INNER JOIN prefix_category_product category_product{$facetIndex}
                    ON category_product{$facetIndex}.id_product = product.id_product
                INNER JOIN prefix_category_shop category_shop{$facetIndex}
                    ON category_shop{$facetIndex}.id_category = category_product{$facetIndex}.id_category
                    AND category_shop{$facetIndex}.id_shop = " . (int)$context->getShopId()
        ;
    }

    public function getConditionSQLForQuery(CategoryFilter $filter, $facetIndex)
    {
        return "category_product{$facetIndex}.id_category = " . (int)$filter->getCategoryId();
    }
}
