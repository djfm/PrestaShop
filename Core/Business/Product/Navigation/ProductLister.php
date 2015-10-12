<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

use Adapter_ServiceLocator;

class ProductLister implements ProductListerInterface
{
    public function listProducts(QueryContext $context, Query $query, PaginationQuery $pagination)
    {
        return Adapter_ServiceLocator::get('Adapter_ProductLister')->listProducts($context, $query, $pagination);
    }
}
