<?php

namespace PrestaShop\PrestaShop\Tests\Integration\Core\Business\Product;

use PrestaShop\PrestaShop\Tests\TestCase\IntegrationTestCase;
use PrestaShop\PrestaShop\Core\Business\Product\ProductLister;
use PrestaShop\PrestaShop\Core\Business\Product\ProductQuery;
use PrestaShop\PrestaShop\Core\Business\Product\PaginationQuery;
use PrestaShop\PrestaShop\Core\Business\Product\ProductQueryContext;
use PrestaShop\PrestaShop\Core\Business\Product\Filter\CategoryFilter;
use Adapter_ServiceLocator;

class ProductListerTest extends IntegrationTestCase
{
    private $lister;
    private $context;
    private $pagination;

    public function setup()
    {
        parent::setup();
        $this->lister = Adapter_ServiceLocator::get('PrestaShop\PrestaShop\Core\Business\Product\ProductLister');
        $this->context = new ProductQueryContext;
        $this->context->setShopId(1);
        $this->pagination = new PaginationQuery;
        $this->pagination->setPage(1)->setResultsPerPage(2);
    }

    public function test_Products_Are_Found_By_Category()
    {
        $query = new ProductQuery('and');
        $categoryFilter = (new CategoryFilter)->setCategoryId(3); // "Women"
        $query->addFilter($categoryFilter);
        $result = $this->lister->listProducts($this->context, $query, $this->pagination);
    }
}
