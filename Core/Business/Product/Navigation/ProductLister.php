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

    private function buildQueryWhere(
        QueryContext $context,
        DatabaseQuery $query
    ) {
        $cumulativeConditions = [];
        foreach ($query->getFacets() as $i => $facet) {
            $alternativeConditions = [];
            foreach ($facet->getFilters() as $filter) {
                if (!$filter->isEnabled()) {
                    continue;
                }

                $conditionSQL = $facet->getFilterSQL($i, $filter);
                if ($conditionSQL) {
                    $alternativeConditions[] = $conditionSQL;
                }
            }
            if (!empty($alternativeConditions)) {
                $cumulativeConditions[] = '('.implode(' OR ', $alternativeConditions).')';
            }
        }

        if (!empty($cumulativeConditions)) {
            return implode(' AND ', $cumulativeConditions);
        } else {
            return '';
        }
    }

    private function buildQueryFrom(
        QueryContext $context,
        DatabaseQuery $query
    ) {
        $sql = 'prefix_product product
                    INNER JOIN prefix_product_lang product_lang
                        ON product_lang.id_product = product.id_product
                            AND product_lang.id_lang = '.(int) $context->getLanguageId()
                        .' AND product_lang.id_shop = '.(int) $context->getShopId()
        ;

        foreach ($query->getFacets() as $i => $facet) {
            if (empty($facet->getFilters())) {
                continue;
            }
            $sql .= ' '.$facet->getJoinsSQL($i);
        }

        return $sql;
    }

    private function buildQueryParts(
        QueryContext $context,
        DatabaseQuery $query
    ) {
        return [
            'select' => '',
            'from' => $this->buildQueryFrom($context, $query),
            'where' => $this->buildQueryWhere($context, $query),
            'groupBy' => '',
            'orderBy' => '',
        ];
    }

    private function assembleQueryParts(array $queryParts)
    {
        return (new SQLQuery($queryParts))->getSQLString();
    }

    public function getQueryTemplate(
        QueryContext $context,
        Query $initialQuery
    ) {
        return (new QueryTemplateProvider($this->db))->getQueryTemplate(
            $context,
            $initialQuery
        );
    }

    private function buildUpdatedFilters(
        QueryContext $context,
        DatabaseQuery $query
    ) {
        $updatedFilters = new Query();

        foreach ($query->getFacets() as $facet) {
            $queryParts = $this->buildQueryParts(
                $context,
                $query->withoutFacet($facet->getIdentifier())
            );

            $sql = (new SQLQuery(
                $facet->getQueryPartsForFacetUpdate($queryParts)
            ))->getSQLString();

            $updatedFacet = clone $facet;
            $updatedFacet->clearFilters();

            foreach ($this->db->select($sql) as $row) {
                $filter = new Filter();
                $filter
                    ->setLabel($row['label'])
                    ->setMagnitude((int) $row['magnitude'])
                    ->setEnabled(false)
                ;
                unset($row['label']);
                unset($row['magnnitude']);

                $filter->setCondition($row);

                $updatedFacet->addFilter($filter);
            }

            $updatedFacet->merge($facet);
            $updatedFilters->addFacet($updatedFacet);
        }

        return $updatedFilters->sortFacets();
    }

    private function setSortOptions(QueryResult $result)
    {
        $result->setSortOptions([
            new SortOption('product_lang.name', 'ASC',  'Product A to Z'),
            new SortOption('product_lang.name', 'DESC', 'Product Z to A'),
        ]);
    }

    private function setURLFragment(QueryContext $context, QueryResult $result)
    {
        $serializer = new QueryURLSerializer($this->db, $this);
        $fragment = $serializer->getURLFragmentFromQuery(
            $result->getUpdatedFilters()
        );
        $result->setURLFragment($fragment);

        return $this;
    }

    public function listProducts(QueryContext $context, Query $initialQuery)
    {
        $query = $this->getQueryTemplate($context, $initialQuery);

        $queryParts = $this->buildQueryParts($context, $query);
        $queryParts['select'] = 'DISTINCT product.*, product_lang.*';
        $rpp = (int) $query->getPagination()->getResultsPerPage();
        $p = (int) $query->getPagination()->getPage();
        $queryParts['limit'] = $rpp;
        $queryParts['offset'] = $rpp * ($p - 1);

        if (($sortOption = $query->getSortOption())) {
            $queryParts['orderBy'] = $sortOption->getFieldName().' '.$sortOption->getSortOrder();
        }

        $sql = $this->assembleQueryParts($queryParts);
        $products = $this->db->select($sql);

        $result = new QueryResult();
        $result->setProducts($products);

        $pagination = new PaginationResult();
        $result->setPaginationResult($pagination);

        $pagination->setPage($p);

        unset($queryParts['limit']);
        $queryParts['select'] = 'COUNT(DISTINCT product.id_product)';
        $totalResultsCount = (int) $this->db->getValue($this->assembleQueryParts($queryParts));

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

    public function getQueryFromURLFragment(
        QueryContext $context,
        Query $initialQuery,
        $urlFragment
    ) {
        $serializer = new QueryURLSerializer($this->db);

        return $serializer->getQueryFromURLFragment(
            $context,
            $this->getQueryTemplate($context, $initialQuery),
            $urlFragment
        );
    }
}
