<?php

namespace PrestaShop\PrestaShop\Core\Business\Product;

interface ProductListerInterface
{
    /**
     * Well, it lists products.
     * @param  ProductQueryContext $context Holds general query settings, such as shop, language and customer group
     * @param  ProductQuery    $query
     * @param  PaginationQuery $pagination
     * @return ProductQueryResult or `null`, where:
     *                        `null` is returned when the class
     *                        does not know how to answer the query.
     *                        Returning null is very different from returning
     *                        a ProductQueryResult with no products, it means
     *                        something else should be asked for the result.
     */
    public function listProducts(ProductQueryContext $context, ProductQuery $query, PaginationQuery $pagination);
}
