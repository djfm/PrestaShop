<?php

namespace PrestaShop\PrestaShop\Tests\Integration\Core\Business\Product;

use PrestaShop\PrestaShop\Tests\TestCase\IntegrationTestCase;
use PrestaShop\PrestaShop\Core\Business\Product\ProductLister;
use PrestaShop\PrestaShop\Core\Business\Product\ProductQuery;
use PrestaShop\PrestaShop\Core\Business\Product\PaginationQuery;
use PrestaShop\PrestaShop\Core\Business\Product\ProductQueryContext;
use PrestaShop\PrestaShop\Core\Business\Product\ProductQueryResult;
use PrestaShop\PrestaShop\Core\Business\Product\Filter\CategoryFilter;
use Adapter_ServiceLocator;
use Context;

class ProductListerTest extends IntegrationTestCase
{
    private $lister;
    private $context;
    private $pagination;

    public function setup()
    {
        $psContext = Context::getContext();

        parent::setup();
        $this->lister = Adapter_ServiceLocator::get('PrestaShop\PrestaShop\Core\Business\Product\ProductLister');
        $this->context = new ProductQueryContext;
        $this->context
            ->setShopId($psContext->shop->id)
            ->setLanguageId($psContext->language->id)
        ;
        $this->pagination = new PaginationQuery;
        $this->pagination->setPage(1)->setResultsPerPage(2);
    }

    public function test_Products_Are_Found_By_Category()
    {
        $query = new ProductQuery('and');
        $categoryFilter = (new CategoryFilter)->setCategoryId(3)->setEnabled(true);     // "Women"
        $query->addFilter($categoryFilter);
        $result = $this->lister->listProducts($this->context, $query, $this->pagination);
        $this->assertInstanceOf('PrestaShop\PrestaShop\Core\Business\Product\ProductQueryResult', $result);
        $this->assertCount(7, $result->getProducts());
    }

    public function test_Products_Are_Found_By_Category_And_Filters_Are_Updated()
    {
        $query = new ProductQuery('and');
        $categoryFilter = (new CategoryFilter)->setCategoryId(3)->setEnabled(true);     // "Women"
        $query->addFilter($categoryFilter);
        $result = $this->lister->listProducts($this->context, $query, $this->pagination);

        $expectedUpdatedFilters = (new ProductQuery('and'))
            ->addFilter((new CategoryFilter)->setCategoryId(3)->setEnabled(true))       // "Women"
        ;

        $newFilters = (new ProductQuery('or'))
            ->addFilter((new CategoryFilter)->setCategoryId(4)->setEnabled(false))      // "Tops"
            ->addFilter((new CategoryFilter)->setCategoryId(8)->setEnabled(false))      // "Dresses"
        ;

        $expectedUpdatedFilters->addFilter($newFilters);

        $this->assertEquals($expectedUpdatedFilters, $result->getUpdatedFilters());
    }
}
