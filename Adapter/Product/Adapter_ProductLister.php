<?php

use PrestaShop\PrestaShop\Core\Business\Product\Navigation\ProductListerInterface;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryContext;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Query;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Facet;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\PaginationQuery;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter\AbstractProductFilter;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter\CategoryFilter;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter\AttributeFilter;
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
        } else if ($filter instanceof AttributeFilter) {
            return "attribute{$facetIndex}.id_attribute_group = " . (int)$filter->getAttributeGroupId()
                 . " AND attribute{$facetIndex}.id_attribute = " . (int)$filter->getAttributeId()
            ;
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

    private function buildAttributesJoins($facetIndex)
    {
        // TODO: Mulstishop not supported at this point.
        $i = $facetIndex;
        return " INNER JOIN prefix_product_attribute product_attribute{$i}
                        ON product_attribute{$i}.id_product = product.id_product
                   INNER JOIN prefix_product_attribute_combination product_attribute_combination{$i}
                        ON product_attribute_combination{$i}.id_product_attribute = product_attribute{$i}.id_product_attribute
                   INNER JOIN prefix_attribute attribute{$i}
                        ON attribute{$i}.id_attribute = product_attribute_combination{$i}.id_attribute
                   INNER JOIN prefix_attribute_group attribute_group{$i}
                        ON attribute_group{$i}.id_attribute_group = attribute{$i}.id_attribute_group"
        ;
    }

    private function buildQueryFrom(QueryContext $context, Query $query)
    {
        $sql = 'prefix_product product';

        $sql .= ' INNER JOIN prefix_product_lang product_lang ON product_lang.id_product = product.id_product AND product_lang.id_lang = ' . (int)$context->getLanguageId() . ' AND product_lang.id_shop = ' . (int)$context->getShopId();

        foreach ($query->getFacets() as $i => $facet) {
            foreach ($this->getFacetDataDomains($facet) as $domain) {
                if ('categories' === $domain) {
                    $sql .= " INNER JOIN prefix_category_product category_product{$i}
                                ON category_product{$i}.id_product = product.id_product
                              INNER JOIN prefix_category_shop category_shop{$i}
                                ON category_shop{$i}.id_category = category_product{$i}.id_category
                                    AND category_shop{$i}.id_shop = " . (int)$context->getShopId();
                } else if ('attributes' === $domain){
                    $sql .= $this->buildAttributesJoins($i);
                } else {
                    throw new Exception(sprintf('Unknown product data domain `%s`.', $domain));
                }
            }
        }

        return $sql;
    }

    private function buildQueryParts(
        QueryContext $context,
        Query $query
    ) {
        return [
            'select'    => '',
            'from'      => $this->buildQueryFrom($context, $query),
            'where'     => $this->buildQueryWhere($context, $query),
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
        Query $query
    ) {
        $updatedFilters = new Query;

        $this->addCategoryFacets(
            $updatedFilters,
            $context,
            $query
        );

        $this->addAttributeGroupFacets(
            $updatedFilters,
            $context,
            $query
        );

        return $updatedFilters;
    }

    private function addCategoryFacets(
        Query $updatedFilters,
        QueryContext $context,
        Query $initialFilters
    ) {
        $queryParts = $this->buildQueryParts(
            $context,
            $initialFilters->withoutFacet('childrenCategories')
        );

        $topLevelCategoryId = $this->getTopLevelCategoryId($initialFilters);

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
                $this->mergeFacets(
                    $childrenCategoriesFacet,
                    $initialFilters->getFacetByIdentifier('childrenCategories')
                )
            )
        ;

        return $this;
    }

    private function addAttributeGroupFacets(
        Query $updatedFilters,
        QueryContext $context,
        Query $initialFilters
    ) {
        $queryParts = $this->buildQueryParts(
            $context,
            $initialFilters->withoutFacet(function ($identifier) {
                return preg_match('/^attributeGroup\d+$/', $identifier);
            })
        );

        $queryParts['select']   = 'attribute.id_attribute_group, attribute.id_attribute';
        $queryParts['groupBy']  = 'attribute.id_attribute_group, attribute.id_attribute';
        $queryParts['orderBy']  = 'attribute.id_attribute_group, attribute.id_attribute';
        $queryParts['from']    .= $this->buildAttributesJoins('');

        $sql = $this->assembleQueryParts($queryParts);

        $attributes = Db::getInstance()->executeS($sql);

        $groups = [];

        foreach ($attributes as $row) {
            $groups[(int)$row['id_attribute_group']][] = (int)$row['id_attribute'];
        }

        foreach ($groups as $groupId => $attributes) {
            $groupName = Db::getInstance()->getValue(
                $this->addDbPrefix(
                    "SELECT name FROM prefix_attribute_group_lang WHERE id_attribute_group = $groupId AND id_lang = " . (int)$context->getLanguageId()
                )
            );
            $facet = new Facet;
            $facetIdentifier = 'attributeGroup' . $groupId;
            $facet
                ->setIdentifier($facetIdentifier)
                ->setName($groupName)
            ;
            foreach ($attributes as $attributeId) {
                $filter = new AttributeFilter($groupId, $attributeId);
                $facet->addFilter($filter);
            }
            $updatedFilters->addFacet(
                $this->mergeFacets(
                    $facet,
                    $initialFilters->getFacetByIdentifier($facetIdentifier)
                )
            );
        }
    }

    private function mergeFacets(Facet $target, Facet $initial = null)
    {
        if (null === $initial) {
            return $target;
        }

        foreach ($initial->getFilters() as $initialFilter) {
            $targetFilter = $target->getFilterByIdentifier($initialFilter->getIdentifier());
            if ($targetFilter) {
                $targetFilter->setEnabled($initialFilter->isEnabled());
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

    public function listProducts(QueryContext $context, Query $query)
    {
        $queryParts = $this->buildQueryParts($context, $query);
        $queryParts['select'] = 'DISTINCT product.*, product_lang.*';
        $sql = $this->assembleQueryParts($queryParts);

        $products = $this->addMissingProductInformation($context, Db::getInstance()->executeS($sql));

        $result = new QueryResult;
        $result->setProducts($products);

        $result->setUpdatedFilters($this->buildUpdatedFilters(
            $context, $query
        ));

        return $result;
    }
}
