<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

interface ProductListerInterface
{
    /**
     * Well, it lists products.
     *
     * @param  ProductQueryContext $context Holds general query settings, such as shop, language and customer group
     * @param  ProductQuery    $query
     * @return ProductQueryResult
     */
    public function listProducts(
        QueryContext $context,
        Query $query
    );

    /**
     * For ProductLister that generate SEO friendly URLs,
     * deserialize an URL fragment and get a query from it.
     * If ProductLister cannot or won't unserialize it should return null,
     * not throw an exception.
     *
     */
    public function getQueryFromURLFragment(
        QueryContext $context,
        Query $defaultQuery,
        $urlFragment
    );
}
