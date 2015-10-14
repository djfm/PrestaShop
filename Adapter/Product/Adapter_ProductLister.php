<?php

use PrestaShop\PrestaShop\Core\Business\Product\Navigation\ProductListerInterface;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryContext;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Query;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Facet;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\PaginationQuery;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter\AbstractProductFilter;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter\CategoryFilter;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryResult;

class Adapter_ProductLister implements ProductListerInterface
{
    private function addDbPrefix($sql)
    {
        return str_replace('prefix_', _DB_PREFIX_, $sql);
    }

    private function getFacetDataDomains(Facet $facet)
    {
        return array_unique(array_map(function (AbstractProductFilter $filter) {
            return $filter->getDataDomain();
        }, $facet->getFilters()));
    }

    private function getQueryDataDomains(Query $query)
    {
        return array_unique(
            call_user_func_array(
                'array_merge',
                array_map(
                    [$this, 'getFacetDataDomains'],
                    $query->getFacets()
                )
            )
        );
    }

    private function filterToSQL(AbstractProductFilter $filter, $facetIndex)
    {
        if ($filter instanceof CategoryFilter) {
            return "category_product{$facetIndex}.id_category = " . (int)$filter->getCategoryId();
        } else {
            throw new Exception(
                sprintf(
                    'Cannot build SQL for filter with class `%s`.',
                    get_class($filter)
                )
            );
        }
    }

    private function buildQueryWhere(QueryContext $context, Query $query)
    {
        $cumulativeConditions = [];
        foreach ($query->getFacets() as $i => $facet) {
            $cumulativeConditions[] = '(' . implode(' OR ', array_map(function (AbstractProductFilter $filter) use ($i) {
                return $this->filterToSQL($filter, $i);
            }, $facet->getFilters())) . ')';
        }
        return implode(' AND ', $cumulativeConditions);
    }

    private function buildQueryFrom(QueryContext $context, Query $query, PaginationQuery $pagination = null)
    {
        $sql = 'prefix_product product';

        $sql .= ' INNER JOIN prefix_product_lang product_lang ON product_lang.id_product = product.id_product AND product_lang.id_lang = ' . (int)$context->getLanguageId() . ' AND product_lang.id_shop = ' . (int)$context->getShopId();

        foreach ($query->getFacets() as $i => $facet) {
            foreach ($this->getFacetDataDomains($facet) as $domain) {
                switch ($domain) {
                    case 'categories':
                        $sql .= " INNER JOIN prefix_category_product category_product{$i}
                                    ON category_product{$i}.id_product = product.id_product
                                  INNER JOIN prefix_category_shop category_shop{$i}
                                    ON category_shop{$i}.id_category = category_product{$i}.id_category
                                        AND category_shop{$i}.id_shop = " . (int)$context->getShopId();
                }
            }
        }

        return $sql;
    }

    private function buildQueryParts(
        QueryContext $context,
        Query $query,
        PaginationQuery $pagination = null
    ) {
        return [
            'select'    => '',
            'from'      => $this->buildQueryFrom($context, $query, $pagination),
            'where'     => $this->buildQueryWhere($context, $query, $pagination),
            'groupBy'   => '',
            'orderBy'   => ''
        ];
    }

    private function assembleQueryParts(array $queryParts)
    {
        $sql = "SELECT {$queryParts['select']} FROM {$queryParts['from']}";
        if ($queryParts['where']) {
            $sql .= " WHERE {$queryParts['where']}";
        }
        if ($queryParts['groupBy']) {
            $sql .= " GROUP BY {$queryParts['groupBy']}";
        }
        if ($queryParts['orderBy']) {
            $sql .= " ORDER BY {$queryParts['orderBy']}";
        }
        return $this->addDbPrefix($sql);
    }

    private function getTopLevelCategoryId(Query $query)
    {
        $minDepth   = null;
        $categoryId = null;

        foreach ($query->getFacets() as $facet) {
            foreach ($facet->getFilters() as $filter) {
                if ($filter instanceof CategoryFilter) {
                    $id = (int)$filter->getCategoryId();
                    $depth = (int)Db::getInstance()->getValue(
                        $this->addDbPrefix("SELECT level_depth FROM prefix_category WHERE id_category = $id")
                    );
                    if ($minDepth === null || $depth < $minDepth) {
                        $minDepth = $depth;
                        $categoryId = $id;
                    }
                }
            }
        }

        if (null === $categoryId) {
            return (int)Configuration::get('PS_ROOT_CATEGORY');
        }

        return $categoryId;
    }

    private function buildUpdatedFilters(
        QueryContext $context,
        Query $query,
        PaginationQuery $pagination = null
    ) {
        $updatedFilters = new Query;

        $this->addCategoryFacets(
            $updatedFilters,
            $context,
            $query
        );

        return $updatedFilters;
    }

    private function addCategoryFacets(
        Query $updatedFilters,
        QueryContext $context,
        Query $query
    ) {
        $queryParts = $this->buildQueryParts(
            $context,
            $query->withoutFacet('childrenCategories')
        );

        $topLevelCategoryId = $this->getTopLevelCategoryId($query);

        $queryParts['select']   = 'other_categories.id_category';
        $queryParts['from']    .= ' INNER JOIN prefix_category category ON category.id_category = ' . (int)$topLevelCategoryId . '
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

        $sql = $this->assembleQueryParts($queryParts);

        $categoryIds = Db::getInstance()->executeS($sql);

        $parentCategoryFacet = new Facet;
        $parentCategoryFacet
            ->setName('Category')
            ->setIdentifier('parentCategory')
            ->addFilter(
                new CategoryFilter(
                    (int)array_shift($categoryIds)['id_category'],
                    true
                )
            )
        ;

        $childrenCategoriesFacet = new Facet;
        $childrenCategoriesFacet
            ->setName('Category')
            ->setIdentifier('childrenCategories')
        ;

        foreach ($categoryIds as $row) {
            $categoryId = (int)$row['id_category'];
            $childrenCategoriesFacet->addFilter(
                new CategoryFilter(
                    $categoryId,
                    false
                )
            );
        }

        $updatedFilters
            ->addFacet($parentCategoryFacet)
            ->addFacet(
                $this->mergeCategoryFacets(
                    $childrenCategoriesFacet,
                    $query->getFacetByIdentifier('childrenCategories')
                )
            )
        ;

        return $this;
    }

    private function mergeCategoryFacets(Facet $target, Facet $initial = null)
    {
        if (null === $initial) {
            return $target;
        }

        foreach ($initial->getFilters() as $initialFilter) {
            $found = false;
            foreach ($target->getFilters() as $targetFilter) {
                if ($targetFilter->getCategoryId() === $initialFilter->getCategoryId()) {
                    $found = true;
                    $targetFilter->setEnabled($initialFilter->isEnabled());
                    break;
                }
            }
            if (!$found) {
                $target->addFilter($initialFilter);
            }
        }
        return $target;
    }

    private function addMissingProductInformation(QueryContext $context, array $products)
    {
        $nb_days_new_product = Configuration::get('PS_NB_DAYS_NEW_PRODUCT');
        if (!Validate::isUnsignedInt($nb_days_new_product)) {
            $nb_days_new_product = 20;
        }

        return array_map(function (array $product) use ($context, $nb_days_new_product){
            if (empty($product['id_product_attribute'])) {
                $product['id_product_attribute'] = 0;
            }

            if (!array_key_exists('new', $product)) {
                $productAge = round((time() - strtotime($product['date_add'])) / 24 / 3600);
                $product['new'] = $productAge < $nb_days_new_product;
            }

            return Product::getProductProperties(
                $context->getLanguageId(),
                $product
            );
        }, $products);
    }

    public function listProducts(QueryContext $context, Query $query, PaginationQuery $pagination)
    {
        $queryParts = $this->buildQueryParts($context, $query, $pagination);
        $queryParts['select'] = 'product.*, product_lang.*';
        $sql = $this->assembleQueryParts($queryParts);

        $products = $this->addMissingProductInformation($context, Db::getInstance()->executeS($sql));

        $result = new QueryResult;
        $result->setProducts($products);

        $result->setUpdatedFilters($this->buildUpdatedFilters(
            $context, $query, $pagination
        ));

        return $result;
    }
}
