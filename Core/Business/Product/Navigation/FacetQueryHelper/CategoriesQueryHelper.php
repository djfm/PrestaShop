<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation\FacetQueryHelper;

use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Facet;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryAssembler;

class CategoriesQueryHelper extends AbstractFacetQueryHelper
{
    public function getRootIdCategory(Facet $facet)
    {
        $condition = $facet->getCondition();

        if (isset($condition['id_category'])) {
            return (int)$condition['id_category'];
        } else {
            return null;
        }
    }

    public function getJoinsSQLForQuery($facetIndex, Facet $facet)
    {
        return "INNER JOIN prefix_category_product category_product{$facetIndex}
                    ON category_product{$facetIndex}.id_product = product.id_product
                INNER JOIN prefix_category_shop category_shop{$facetIndex}
                    ON category_shop{$facetIndex}.id_category = category_product{$facetIndex}.id_category
                    AND category_shop{$facetIndex}.id_shop = " . (int)$this->context->getShopId()
        ;
    }

    public function getFilterConditionSQLForQuery($facetIndex, Filter $filter)
    {
        return "category_product{$facetIndex}.id_category = " . (int)$filter->getCondition()['id_category'];
    }

    public function getUpdatedFacet(array $queryParts, Facet $initialFacet)
    {
        $queryParts['select'] = 'category.id_category, category_lang.name, category.level_depth, category.nleft, count(distinct product.id_product) as magnitude';
        $queryParts['from'] .= ' ' . $this->getJoinsSQLForQuery('', $initialFacet)
                            .  ' ' . 'INNER JOIN prefix_category category
                                            ON category_product.id_category = category.id_category
                                      INNER JOIN prefix_category_lang category_lang ON
                                            category_lang.id_category = category.id_category
                                            AND category_lang.id_lang = ' . (int)$this->context->getLanguageId() . '
                                            AND category_lang.id_shop = ' . (int)$this->context->getShopId() . '
                            ';

        if (($id = $this->getRootIdCategory($initialFacet))) {
            $queryParts['from'] .= " INNER JOIN prefix_category root_category
                                        ON root_category.id_category = $id
                                            AND category.level_depth <= root_category.level_depth + 1
                                            AND category.nleft > root_category.nleft
                                            AND category.nright < root_category.nright"
            ;
        }

        $queryParts['groupBy'] = 'category.id_category';
        $queryParts['orderBy'] = 'category.level_depth ASC, category.nleft ASC';

        $sql = (new QueryAssembler)->assemble($queryParts);

        $rows = $this->db->select($sql);

        $facet = clone $initialFacet;
        $facet->clearFilters();
        
        foreach ($rows as $row) {
            $filter = new Filter;
            $filter
                ->setLabel($row['name'])
                ->setCondition(['id_category' => (int)$row['id_category']])
                ->setMagnitude($row['magnitude'])
                ->setEnabled(false)
            ;

            $facet->addFilter($filter);
        }

        return $facet;
    }

    public function buildFilterFromLabel($label)
    {
        $filter = new Filter;
        $id_category = (int)$this->db->getValue(
            'SELECT id_category FROM prefix_category_lang WHERE
                name = "' . $this->db->escape($label) . '"
                AND id_lang = ' . (int)$this->context->getLanguageId() . '
                AND id_shop = ' . (int)$this->context->getShopId()
        );

        $filter->setCondition(['id_category' => $id_category]);

        return $filter;
    }
}
