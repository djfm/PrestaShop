<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

use PrestaShop\PrestaShop\Core\Foundation\Database\AutoPrefixingDatabase;

class ProductLister implements ProductListerInterface
{
    private $db;

    public function __construct(
        AutoPrefixingDatabase $db
    ) {
        $this->db = $db;
    }

    private function buildQueryWhere(QueryContext $context, Query $query)
    {
        $fqhr = new FacetQueryHelperResolver($this->db, $context);

        $cumulativeConditions = [];
        foreach ($query->getFacets() as $i => $facet) {
            $alternativeConditions = [];
            $queryHelper = $fqhr->getFacetQueryHelper($facet);
            foreach ($facet->getFilters() as $filter) {

                if (!$filter->isEnabled()) {
                    continue;
                }

                $conditionSQL = $queryHelper
                    ->getFilterConditionSQLForQuery($i, $filter)
                ;
                if ($conditionSQL) {
                    $alternativeConditions[] = $conditionSQL;
                }
            }
            if (!empty($alternativeConditions)) {
                $cumulativeConditions[] = '(' . implode(' OR ', $alternativeConditions) . ')';
            }
        }

        if (!empty($cumulativeConditions)) {
            return implode(' AND ', $cumulativeConditions);
        } else {
            return '';
        }
    }

    private function buildQueryFrom(QueryContext $context, Query $query)
    {
        $sql = 'prefix_product product
                    INNER JOIN prefix_product_lang product_lang
                        ON product_lang.id_product = product.id_product
                            AND product_lang.id_lang = ' . (int)$context->getLanguageId()
                        . ' AND product_lang.id_shop = ' . (int)$context->getShopId()
        ;

        $fqhr = new FacetQueryHelperResolver($this->db, $context);

        foreach ($query->getFacets() as $i => $facet) {
            if (empty($facet->getFilters())) {
                continue;
            }
            $queryHelper = $fqhr->getFacetQueryHelper($facet);
            $sql .= ' ' . $queryHelper->getJoinsSQLForQuery($i, $facet);
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
        return (new QueryAssembler)->assemble($queryParts);
    }

    public function getAvailableFacets(QueryContext $context, Query $defaultQuery)
    {
        $facets = [];

        /**
         * Categories
         */

        $categoryFacet = $defaultQuery->getFacetByIdentifier('categories');

        if (!$categoryFacet) {
            $categoryFacet = new Facet($this->db, $context);
            $categoryFacet
                ->setLabel('Category')
                ->setIdentifier('categories')
            ;
        }

        $facets[] = $categoryFacet;

        /**
         * Attributes
         */

        $sql = 'SELECT agl.id_attribute_group, agl.public_name FROM prefix_attribute_group_lang agl
                INNER JOIN prefix_attribute_group ag
                    ON ag.id_attribute_group = agl.id_attribute_group
                    WHERE agl.id_lang = ' . (int)$context->getLanguageId() . '
                ORDER BY ag.position';

        foreach ($this->db->select($sql) as $group) {
            $facet = new Facet($this->db, $context);
            $facet
                ->setLabel($group['public_name'])
                ->setIdentifier('attributeGroup' . (int)$group['id_attribute_group'])
                ->setCondition([
                    'id_attribute_group' => (int)$group['id_attribute_group']
                ])
            ;
            $facets[] = $facet;
        }

        /**
         * Features
         */

        $sql = 'SELECT fl.id_feature, fl.name FROM prefix_feature_lang fl
                    INNER JOIN prefix_feature_shop fs ON fs.id_feature = fl.id_feature
                    INNER JOIN prefix_feature f ON f.id_feature = fs.id_feature
                WHERE fl.id_lang = ' . (int)$context->getLanguageId() . '
                AND fs.id_shop = ' . (int)$context->getShopId()
        ;

        foreach ($this->db->select($sql) as $feature) {
            $facet = new Facet($this->db, $context);
            $facet
                ->setLabel($feature['name'])
                ->setIdentifier('feature' . (int)$feature['id_feature'])
                ->setCondition([
                    'id_feature' => (int)$feature['id_feature']
                ])
            ;
            $facets[] = $facet;
        }

        return $facets;
    }

    private function buildUpdatedFilters(
        QueryContext $context,
        Query $query
    ) {
        $fqhr = new FacetQueryHelperResolver($this->db, $context);

        $updatedFilters = new Query;

        foreach ($query->getFacets() as $facet) {
            $queryHelper = $fqhr->getFacetQueryHelper($facet);

            $queryParts = $this->buildQueryParts(
                $context,
                $query->withoutFacet($facet->getIdentifier())
            );

            $updatedFacet = $queryHelper->getUpdatedFacet($queryParts, $facet);
            $updatedFacet->merge($facet);

            $updatedFilters->addFacet($updatedFacet);
        }

        $queryParts = $this->buildQueryParts($context, $query);

        foreach ($this->getAvailableFacets($context, $query) as $facet) {
            $queryHelper = $fqhr->getFacetQueryHelper($facet);
            if (null === $query->getFacetByIdentifier($facet->getIdentifier())) {
                $updatedFilters->addFacet(
                    $queryHelper->getUpdatedFacet($queryParts, $facet)
                );
            }
        }

        return $updatedFilters;
    }

    private function setSortOptions(QueryResult $result)
    {
        $result->setSortOptions([
            new SortOption('product_lang.name', 'ASC'   , 'Product A to Z'),
            new SortOption('product_lang.name', 'DESC'  , 'Product Z to A')
        ]);
    }

    private function setURLFragment(QueryContext $context, QueryResult $result)
    {
        $serializer = new QueryURLSerializer($this->db, $this);
        $fragment   = $serializer->getURLFragmentFromQuery(
            $result->getUpdatedFilters()
        );
        $result->setURLFragment($fragment);
        return $this;
    }

    public function listProducts(QueryContext $context, Query $query)
    {
        $queryParts = $this->buildQueryParts($context, $query);
        $queryParts['select'] = 'DISTINCT product.*, product_lang.*';
        $rpp = (int)$query->getPagination()->getResultsPerPage();
        $p   = (int)$query->getPagination()->getPage();
        $queryParts['limit']  = $rpp;
        $queryParts['offset'] = $rpp * ($p - 1);

        if (($sortOption = $query->getSortOption())) {
            $queryParts['orderBy'] = $sortOption->getFieldName() . ' ' . $sortOption->getSortOrder();
        }

        $sql = $this->assembleQueryParts($queryParts);
        $products = $this->db->select($sql);

        $result = new QueryResult;
        $result->setProducts($products);

        $pagination = new PaginationResult;
        $result->setPaginationResult($pagination);

        $pagination->setPage($p);

        unset($queryParts['limit']);
        $queryParts['select'] = 'COUNT(DISTINCT product.id_product)';
        $totalResultsCount = (int)$this->db->getValue($this->assembleQueryParts($queryParts));

        $pagination->setTotalResultsCount($totalResultsCount);
        $pagination->setPagesCount(ceil($totalResultsCount / $rpp));
        $pagination->setResultsCount(count($result->getProducts()));

        $result->setUpdatedFilters($this->buildUpdatedFilters(
            $context, $query
        ));

        $this->setSortOptions($result);

        $this->setURLFragment($context, $result);

        return $result;
    }

    public function getQueryFromURLFragment(QueryContext $context, Query $defaultQuery, $urlFragment)
    {
        $serializer = new QueryURLSerializer($this->db, $this);
        return $serializer->getQueryFromURLFragment(
            $context,
            $defaultQuery,
            $urlFragment
        );
    }

    public function buildFilterFromLabel(QueryContext $context, Facet $facet, $label)
    {
        $fqhr = new FacetQueryHelperResolver($this->db, $context);
        $queryHelper = $fqhr->getFacetQueryHelper($facet);
        return $queryHelper->buildFilterFromLabel($label);
    }
}
