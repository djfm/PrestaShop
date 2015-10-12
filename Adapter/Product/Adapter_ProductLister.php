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

    private function getDataDomains(Query $query)
    {
        $domains = [];
        foreach ($query->getFacets() as $facet) {
            foreach ($facet->getFilters() as $filter) {
                $domains[$filter->getDataDomain()] = true;
            }
        }
        return array_keys($domains);
    }

    private function filterToSQL(AbstractProductFilter $filter)
    {
        if ($filter instanceof CategoryFilter) {
            return 'category_product.id_category = ' . (int)$filter->getCategoryId();
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
        return implode(' AND ', array_map(function (Facet $facet) {
            return '(' . implode(' OR ', array_map([$this, 'filterToSQL'], $facet->getFilters())) . ')';
        }, $query->getFacets()));
    }

    private function buildQueryFrom(QueryContext $context, Query $query, PaginationQuery $pagination)
    {
        $sql = 'prefix_product product';

        $sql .= ' INNER JOIN prefix_product_lang product_lang ON product_lang.id_product = product.id_product AND product_lang.id_lang = ' . (int)$context->getLanguageId() . ' AND product_lang.id_shop = ' . (int)$context->getShopId();

        foreach ($this->getDataDomains($query) as $domain) {
            switch ($domain) {
                case 'categories':
                    $sql .= ' INNER JOIN prefix_category_product category_product ON category_product.id_product = product.id_product INNER JOIN prefix_category_shop category_shop ON category_shop.id_category = category_product.id_category AND category_shop.id_shop = ' . (int)$context->getShopId();
            }
        }

        return $sql;
    }

    private function buildQueryParts(
        QueryContext $context,
        Query $query,
        PaginationQuery $pagination
    ) {
        return [
            'select'    => '',
            'from'      => $this->buildQueryFrom($context, $query, $pagination),
            'where'     => $this->buildQueryWhere($context, $query, $pagination),
            'groupBy'   => '',
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
        return $this->addDbPrefix($sql);
    }

    private function isCategoryFilterEnabled(Query $query, $categoryId)
    {
        foreach ($query->getFacets() as $facet) {
            foreach ($facet->getFilters() as $filter) {
                if ($filter instanceof CategoryFilter && $filter->getCategoryId() === $categoryId) {
                    return true;
                }
            }
        }

        return false;
    }

    private function buildUpdatedFilters(
        QueryContext $context,
        Query $query,
        PaginationQuery $pagination
    ) {
        $updatedFilters = new Query;
        foreach ($this->getDataDomains($query) as $domain) {
            if ($domain === 'categories') {

                $queryParts = $this->buildQueryParts($context, $query, $pagination);

                $queryParts['select']   = 'other_categories.id_category';
                $queryParts['from']    .= ' INNER JOIN prefix_category category
                                                ON category.id_category = category_product.id_category
                                            INNER JOIN prefix_category_product other_categories
                                                ON other_categories.id_product = product.id_product
                                            INNER JOIN prefix_category other_category
                                                ON other_category.id_category = other_categories.id_category
                                                    AND other_category.nleft  >= category.nleft
                                                    AND other_category.nright <= category.nright
                                                    AND (other_category.level_depth - category.level_depth <= 1)'
                                        ;
                $queryParts['groupBy']  = 'other_categories.id_category';

                $sql = $this->assembleQueryParts($queryParts);

                $categoryIds = Db::getInstance()->executeS($sql);

                $facet = new Facet;

                foreach ($categoryIds as $row) {
                    $categoryId = (int)$row['id_category'];


                    $facet->setName('Category');
                    $facet->addFilter(
                        new CategoryFilter(
                            $categoryId,
                            $this->isCategoryFilterEnabled($query, $categoryId)
                        )
                    );
                }

                $updatedFilters->addFacet($facet);
            }
        }
        return $updatedFilters;
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
