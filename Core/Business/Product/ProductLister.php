<?php

namespace PrestaShop\PrestaShop\Core\Business\Product;

use Adapter_ServiceLocator;

class ProductLister implements ProductListerInterface
{
    public function listProducts(ProductQueryContext $context, ProductQuery $query, PaginationQuery $pagination)
    {
        return Adapter_ServiceLocator::get('Adapter_ProductLister')->listProducts($context, $query, $pagination);
    }
}
