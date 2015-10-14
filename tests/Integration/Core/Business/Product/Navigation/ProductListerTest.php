<?php

namespace PrestaShop\PrestaShop\Tests\Integration\Core\Business\Product\Navigation;

use PrestaShop\PrestaShop\Tests\TestCase\IntegrationTestCase;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryContext;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Query;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Facet;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\PaginationQuery;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter\CategoryFilter;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter\AttributeFilter;
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

        $parentCategoryFacet = new Facet;
        $parentCategoryFacet
            ->setName('Category')
            ->setIdentifier('parentCategory')
            ->addFilter(new CategoryFilter(3, true))    // "Women"   , enabled
        ;

        $childrenCategoriesFacet = new Facet;
        $childrenCategoriesFacet
            ->setName('Category')
            ->setIdentifier('childrenCategories')
            ->addFilter(new CategoryFilter(4, false))   // "Tops"    , disabled
            ->addFilter(new CategoryFilter(8, false))   // "Dresses" , disabled
        ;

        $this->assertEquals(
            $parentCategoryFacet,
            $result->getUpdatedFilters()->getFacetByIdentifier('parentCategory')
        );

        $this->assertEquals(
            $childrenCategoriesFacet,
            $result->getUpdatedFilters()->getFacetByIdentifier('childrenCategories')
        );
    }

    public function test_Products_Are_Found_By_Category_And_Color()
    {
        $query          = new Query;

        $categoryFacet  = new Facet;
        $categoryFacet->addFilter(new CategoryFilter(4, true));

        $colorFacet     = new Facet;
        $colorFacet->addFilter(new AttributeFilter(3, 11, true));

        $query
            ->addFacet($categoryFacet)
            ->addFacet($colorFacet)
        ;

        $result = $this->lister->listProducts($this->context, $query);
        $this->assertInstanceOf(
            'PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryResult',
            $result
        );
        $this->assertCount(1, $result->getProducts());
    }
}
