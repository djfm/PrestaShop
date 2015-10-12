<?php

namespace PrestaShop\PrestaShop\Tests\Integration\Core\Business\Product\Navigation;

use PrestaShop\PrestaShop\Tests\TestCase\IntegrationTestCase;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryContext;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Query;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Facet;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\PaginationQuery;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter\CategoryFilter;
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
        $this->lister = Adapter_ServiceLocator::get(
            'PrestaShop\PrestaShop\Core\Business\Product\Navigation\ProductLister'
        );
        $this->context = new QueryContext;
        $this->context
            ->setShopId($psContext->shop->id)
            ->setLanguageId($psContext->language->id)
        ;
        $this->pagination = new PaginationQuery;
        $this->pagination->setPage(1)->setResultsPerPage(2);
    }

    public function test_Products_Are_Found_By_Category()
    {
        $query      = new Query;
        $facet      = new Facet;

        $facet->addFilter(new CategoryFilter(4, true));
        $query->addFacet($facet);

        $result = $this->lister->listProducts($this->context, $query, $this->pagination);
        $this->assertInstanceOf(
            'PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryResult',
            $result
        );
        $this->assertCount(2, $result->getProducts());
    }

    public function test_Products_Are_Found_By_Category_And_Filters_Are_Updated()
    {
        $query      = new Query;
        $facet      = new Facet;

        $facet->addFilter(new CategoryFilter(3, true)); // "Women"
        $query->addFacet($facet);

        $result = $this->lister->listProducts($this->context, $query, $this->pagination);

        $expectedUpdatedFilters = new Query;

        $parentCategoryFacet = new Facet;
        $parentCategoryFacet
            ->setName('Category')
            ->addFilter(new CategoryFilter(3, true))    // "Women"   , enabled
        ;

        $childrenCategoriesFacet = new Facet;
        $childrenCategoriesFacet
            ->setName('Category')
            ->addFilter(new CategoryFilter(4, false))   // "Tops"    , disabled
            ->addFilter(new CategoryFilter(8, false))   // "Dresses" , disabled
        ;

        $expectedUpdatedFilters
            ->addFacet($parentCategoryFacet)
            ->addFacet($childrenCategoriesFacet)
        ;

        $this->assertEquals($expectedUpdatedFilters, $result->getUpdatedFilters());
    }
}
