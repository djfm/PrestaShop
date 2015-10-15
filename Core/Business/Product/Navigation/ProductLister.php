<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

use Core_Business_ConfigurationInterface;
use Core_Foundation_Database_DatabaseInterface;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\ProductListerInterface;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryContext;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Query;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Facet;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\PaginationQuery;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter\AbstractProductFilter;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter\CategoryFilter;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryHelper\CategoriesQueryHelper;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter\AttributeFilter;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryHelper\AttributesQueryHelper;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryResult;

class ProductLister implements ProductListerInterface
{
    private $configuration;
    private $db;

    public function __construct(
        Core_Business_ConfigurationInterface $configuration,
        Core_Foundation_Database_DatabaseInterface $db
    ) {
        $this->configuration    = $configuration;
        $this->db               = $db;
    }

    private function addDbPrefix($sql)
    {
        return str_replace('prefix_', $this->configuration->get('_DB_PREFIX_'), $sql);
    }

    private function select($sql)
    {
        return $this->db->select($this->addDbPrefix($sql));
    }

    private function getDbValue($sql)
    {
        $rows = $this->select($sql);
        return current(current($rows));
    }

    private function setCategoryFilterLabel(QueryContext $context, CategoryFilter $filter)
    {
        $filter->setLabel(
            $this->getDbValue(
                'SELECT name FROM prefix_category_lang WHERE id_category = '
                . (int)$filter->getCategoryId()
                . ' AND id_lang = ' . (int)$context->getLanguageId()
                . ' AND id_shop = ' . (int)$context->getShopId())
        );
        return $filter;
    }

    private function setAttributeFilterLabel(QueryContext $context, AttributeFilter $filter)
    {
        $filter->setLabel(
            $this->getDbValue(
                'SELECT name FROM prefix_attribute_lang WHERE id_attribute = '
                . (int)$filter->getAttributeId()
                . ' AND id_lang = ' . (int)$context->getLanguageId()
            )
        );
        return $filter;
    }

    private function buildQueryWhere(QueryContext $context, Query $query)
    {
        $cumulativeConditions = [];
        foreach ($query->getFacets() as $i => $facet) {
            $cumulativeConditions[] = '(' . implode(
                ' OR ',
                array_map(function (AbstractProductFilter $filter) use ($i) {
                    return $filter->getQueryHelper()->getConditionSQLForQuery($filter, $i);
                }, $facet->getFilters())
            ) . ')';
        }
        return implode(' AND ', $cumulativeConditions);
    }

    private function buildQueryFrom(QueryContext $context, Query $query)
    {
        $sql = 'prefix_product product
                    INNER JOIN prefix_product_lang product_lang
                        ON product_lang.id_product = product.id_product
                            AND product_lang.id_lang = ' . (int)$context->getLanguageId()
                        . ' AND product_lang.id_shop = ' . (int)$context->getShopId()
        ;

        foreach ($query->getFacets() as $i => $facet) {
            foreach ($facet->getQueryHelpers() as $helper) {
                $sql .= ' ' . $helper->getJoinsSQLForQuery($context, $i);
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
        if (!empty($queryParts['where'])) {
            $sql .= " WHERE {$queryParts['where']}";
        }
        if (!empty($queryParts['groupBy'])) {
            $sql .= " GROUP BY {$queryParts['groupBy']}";
        }
        if (!empty($queryParts['orderBy'])) {
            $sql .= " ORDER BY {$queryParts['orderBy']}";
        }
        if (!empty($queryParts['limit'])) {
            $sql .= " LIMIT {$queryParts['limit']}";
            if (!empty($queryParts['offset'])) {
                $sql .= " OFFSET {$queryParts['offset']}";
            }
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
                    $depth = (int)$this->db->select(
                        $this->addDbPrefix("SELECT level_depth FROM prefix_category WHERE id_category = $id")
                    )[0]['level_depth'];
                    if ($minDepth === null || $depth < $minDepth) {
                        $minDepth = $depth;
                        $categoryId = $id;
                    }
                }
            }
        }

        if (null === $categoryId) {
            return (int)$this->configuration->get('PS_ROOT_CATEGORY');
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

        $newParts = (new CategoriesQueryHelper)->getQueryPartsForFiltersUpdate(
            $context,
            $this->getTopLevelCategoryId($initialFilters)
        );
        $newParts['from'] = $queryParts['from'] . ' ' . $newParts['from'];

        $sql = $this->assembleQueryParts($newParts);

        $categoryIds = $this->db->select($sql);

        $parentCategoryFacet = new Facet;
        $parentCategoryFacet
            ->setName('Category')
            ->setIdentifier('parentCategory')
            ->addFilter(
                $this->setCategoryFilterLabel(
                    $context,
                    new CategoryFilter(
                        (int)array_shift($categoryIds)['id_category'],
                        true
                    )
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
                $this->setCategoryFilterLabel(
                    $context,
                    new CategoryFilter($categoryId, false)
                )
            );
        }

        $updatedFilters
            ->addFacet($parentCategoryFacet)
            ->addFacet(
                $childrenCategoriesFacet->merge(
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

        $queryHelper = new AttributesQueryHelper;

        $newParts = $queryHelper->getQueryPartsForFiltersUpdate($context);
        $newParts['from'] = $queryParts['from'] . ' ' . $newParts['from'];

        $sql = $this->assembleQueryParts($newParts);

        $attributes = $this->db->select($sql);

        $groups = [];

        foreach ($attributes as $row) {
            $groups[(int)$row['id_attribute_group']][] = (int)$row['id_attribute'];
        }

        foreach ($groups as $groupId => $attributes) {
            $groupName = $this->getDbValue(
                "SELECT name FROM prefix_attribute_group_lang WHERE id_attribute_group = $groupId AND id_lang = " . (int)$context->getLanguageId()
            );
            $facet = new Facet;
            $facetIdentifier = 'attributeGroup' . $groupId;
            $facet
                ->setIdentifier($facetIdentifier)
                ->setName($groupName)
            ;
            foreach ($attributes as $attributeId) {
                $filter = new AttributeFilter($groupId, $attributeId);
                $facet->addFilter($this->setAttributeFilterLabel($context, $filter));
            }
            $updatedFilters->addFacet(
                $facet->merge(
                    $initialFilters->getFacetByIdentifier($facetIdentifier)
                )
            );
        }
    }

    public function listProducts(QueryContext $context, Query $query)
    {
        $queryParts = $this->buildQueryParts($context, $query);
        $queryParts['select'] = 'DISTINCT product.*, product_lang.*';
        $rpp = (int)$query->getPagination()->getResultsPerPage();
        $p   = (int)$query->getPagination()->getPage();
        $queryParts['limit']  = $rpp;
        $queryParts['offset'] = $rpp * ($p - 1);

        $sql = $this->assembleQueryParts($queryParts);

        $products = $this->db->select($sql);

        $result = new QueryResult;
        $result->setProducts($products);
        $result->setPage($p);

        unset($queryParts['limit']);
        $queryParts['select'] = 'COUNT(DISTINCT product.id_product)';
        $totalResultsCount = (int)$this->getDbValue($this->assembleQueryParts($queryParts));

        $result->setTotalResultsCount($totalResultsCount);
        $result->setPagesCount(ceil($totalResultsCount / $rpp));

        $result->setUpdatedFilters($this->buildUpdatedFilters(
            $context, $query
        ));

        return $result;
    }
}
