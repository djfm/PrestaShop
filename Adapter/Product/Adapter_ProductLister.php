<?php

use PrestaShop\PrestaShop\Core\Business\Product\ProductListerInterface;
use PrestaShop\PrestaShop\Core\Business\Product\ProductQueryContext;
use PrestaShop\PrestaShop\Core\Business\Product\ProductQuery;
use PrestaShop\PrestaShop\Core\Business\Product\PaginationQuery;
use PrestaShop\PrestaShop\Core\Business\Product\Filter\CategoryFilter;
use PrestaShop\PrestaShop\Core\Business\Product\ProductQueryResult;

class Adapter_ProductLister implements ProductListerInterface
{
    private function addDbPrefix($sql)
    {
        return str_replace('prefix_', _DB_PREFIX_, $sql);
    }

    private function getRelatedEntities(ProductQuery $query)
    {
        $entities = [];

        $aggregate = function (array $newEntities) use (&$entities) {
            $entities = array_unique(array_merge($entities, $newEntities));
        };

        foreach ($query->getFilters() as $filter) {

            if ($filter instanceof ProductQuery) {
                $aggregate($this->getRelatedEntities($filter));
            } else if ($filter instanceof CategoryFilter) {
                $aggregate(['category']);
            } else {
                throw new Exception(sprintf("Don't know which tables are needed for '%s'.", get_class($filter)));
            }
        }

        return $entities;
    }

    private function buildQueryWhere(ProductQueryContext $context, ProductQuery $query)
    {
        foreach ($query->getFilters() as $filter) {

            if (!$filter->isEnabled()) {
                continue;
            }

            if ($filter instanceof ProductQuery) {
                return '(' . implode(
                    strtoupper($filter->getOperator()),
                    array_map([$this, 'buildQueryWhere'], $filter->getFilters())
                ) . ')';
            } else if ($filter instanceof CategoryFilter) {
                return 'category_product.id_category = ' . (int)$filter->getCategoryId();
            } else {
                throw new Exception(sprintf("Don't know how to build where clause for '%s'.", get_class($filter)));
            }
        }
    }

    private function buildQueryFrom(ProductQueryContext $context, ProductQuery $query, PaginationQuery $pagination)
    {
        $relatedEntities = $this->getRelatedEntities($query);

        $sql = 'prefix_product product';

        $sql .= ' INNER JOIN prefix_product_lang product_lang ON product_lang.id_product = product.id_product AND product_lang.id_lang = ' . (int)$context->getLanguageId() . ' AND product_lang.id_shop = ' . (int)$context->getShopId();

        foreach ($relatedEntities as $entity) {
            switch ($entity) {
                case 'category':
                    $sql .= ' INNER JOIN prefix_category_product category_product ON category_product.id_product = product.id_product INNER JOIN prefix_category_shop category_shop ON category_shop.id_category = category_product.id_category AND category_shop.id_shop = ' . (int)$context->getShopId();
            }
        }

        return $sql;
    }

    private function buildQueryParts(ProductQueryContext $context, ProductQuery $query, PaginationQuery $pagination)
    {
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

    private function buildUpdatedFilters(ProductQueryContext $context, ProductQuery $query, PaginationQuery $pagination)
    {
        foreach ($this->getRelatedEntities($query) as $entity) {
            if ($entity === 'category') {

                $queryParts = $this->buildQueryParts($context, $query, $pagination);

                $queryParts['select']   = 'other_categories.id_category';
                $queryParts['from']    .= ' INNER JOIN prefix_category category ON category.id_category = category_product.id_category INNER JOIN prefix_category_product other_categories ON other_categories.id_product = product.id_product INNER JOIN prefix_category other_category ON other_category.id_category = other_categories.id_category AND other_category.nleft > category.nleft AND other_category.nright < category.nright AND (other_category.level_depth - category.level_depth <= 1)';
                $queryParts['groupBy']  = 'other_categories.id_category';

                $sql = $this->assembleQueryParts($queryParts);

                $categoryIds = Db::getInstance()->executeS($sql);

                $filters = new ProductQuery('or');

                foreach ($categoryIds as $row) {
                    $categoryId = $row['id_category'];
                    $filters->addFilter(
                        (new CategoryFilter)
                            ->setCategoryId($categoryId)
                            ->setEnabled(false)
                    );
                }

                $query->addFilter($filters);
            }
        }
        return $query;
    }

    private function addMissingProductInformation(ProductQueryContext $context, array $products)
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

    public function listProducts(ProductQueryContext $context, ProductQuery $query, PaginationQuery $pagination)
    {
        $queryParts = $this->buildQueryParts($context, $query, $pagination);
        $queryParts['select'] = 'product.*, product_lang.*';
        $sql = $this->assembleQueryParts($queryParts);

        $products = $this->addMissingProductInformation($context, Db::getInstance()->executeS($sql));

        $result = new ProductQueryResult;
        $result->setProducts($products);

        $result->setUpdatedFilters($this->buildUpdatedFilters(
            $context, $query, $pagination
        ));

        return $result;
    }
}
