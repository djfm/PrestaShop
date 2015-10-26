<?php

namespace PrestaShop\PrestaShop\Tests\Integration\Core\Business\Product\Navigation;

use PrestaShop\PrestaShop\Tests\TestCase\IntegrationTestCase;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryContext;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Query;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Facet;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\SortOption;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\PaginationQuery;
use Adapter_ServiceLocator;
use Context;

class ProductListerTest extends IntegrationTestCase
{
    private $lister;
    private $context;
    private $db;

    public function setup()
    {
        parent::setup();

        $this->db = Adapter_ServiceLocator::get(
            'PrestaShop\PrestaShop\Core\Foundation\Database\AutoPrefixingDatabase'
        );
        $psContext = Context::getContext();
        $this->lister = Adapter_ServiceLocator::get(
            'PrestaShop\PrestaShop\Core\Business\Product\Navigation\ProductLister'
        );
        $this->context = new QueryContext;
        $this->context
            ->setShopId($psContext->shop->id)
            ->setLanguageId($psContext->language->id)
        ;
    }

    public function test_products_are_found_by_category()
    {
        $query      = new Query;
        $facet      = new Facet;
        $filter     = new Filter;
        $filter
            ->setCondition(['id_category' => 4])
            ->setEnabled()
        ;
        $facet->addFilter($filter);
        $facet->setIdentifier('categories');
        $query->addFacet($facet);

        $this->assertCount(1, $facet->getFilters());

        $result = $this->lister->listProducts($this->context, $query);
        $this->assertInstanceOf(
            'PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryResult',
            $result
        );
        $this->assertCount(2, $result->getProducts());
    }

    public function test_products_are_found_by_category_and_filters_are_updated()
    {
        $query      = new Query;
        $facet      = new Facet;
        $facet
            ->setIdentifier('categories')
            ->setCondition(['id_category' => 3])
        ;
        $query->addFacet($facet);

        $result = $this->lister->listProducts($this->context, $query);

        $categories = $result->getUpdatedFilters()->getFacetByIdentifier('categories');

        $labels = array_map(function (Filter $filter) {
            return $filter->getLabel();
        }, $categories->getFilters());

        $this->assertEquals(['Tops', 'Dresses'], array_values($labels));
    }

    public function test_available_facets_include_attribute_facets()
    {
        $facets = $this
            ->lister
            ->getQueryTemplate($this->context, new Query)
            ->getFacets()
        ;

        $expected = ['Category', 'Size', 'Size', 'Color'];

        $actual = array_map(function (Facet $facet) {
            return $facet->getLabel();
        }, $facets);

        $this->assertEquals($expected, array_slice($actual, 0, count($expected)));
    }

    public function test_products_are_sorted_by_name()
    {
        $query      = new Query;
        $facet      = new Facet;
        $filter     = new Filter;
        $filter
            ->setCondition(['id_category' => 4])
            ->setEnabled()
        ;
        $facet->addFilter($filter);
        $facet->setIdentifier('categories');
        $query->addFacet($facet);

        $nameAsc    = new SortOption('product_lang.name', 'ASC' , 'Product A to Z');
        $nameDesc   = new SortOption('product_lang.name', 'DESC', 'Product Z to A');

        $query->setSortOption($nameAsc);
        $products = $this
            ->lister
            ->listProducts($this->context, $query)
            ->getProducts()
        ;
        $this->assertTrue($products[0]['name'] < $products[1]['name']);

        $query->setSortOption($nameDesc);
        $products = $this
            ->lister
            ->listProducts($this->context, $query)
            ->getProducts()
        ;
        $this->assertTrue($products[0]['name'] > $products[1]['name']);
    }

    public function test_pagination_limits_number_of_results()
    {
        $query      = new Query;
        $facet      = new Facet;
        $filter     = new Filter;
        $filter
            ->setCondition(['id_category' => 3])
            ->setEnabled()
        ;
        $facet
            ->addFilter($filter);
        $facet->setIdentifier('categories');
        $query->addFacet($facet);

        $result = $this->lister->listProducts($this->context, $query);
        $this->assertCount(7, $result->getProducts());

        $pagination = new PaginationQuery;
        $pagination->setPage(1)->setResultsPerPage(2);
        $query->setPagination($pagination);

        $result = $this->lister->listProducts($this->context, $query);

        $this->assertCount(2, $result->getProducts());
    }

    public function test_pagination_gets_total_number_of_results()
    {
        $query      = new Query;
        $facet      = new Facet;
        $filter     = new Filter;
        $filter->setCondition(['id_category' => 3]);
        $facet->addFilter($filter);
        $facet->setIdentifier('categories');
        $query->addFacet($facet);

        $pagination = new PaginationQuery;
        $pagination->setPage(1)->setResultsPerPage(2);
        $query->setPagination($pagination);

        $result = $this->lister->listProducts($this->context, $query);

        $this->assertEquals(2, $result->getPaginationResult()->getResultsCount());
        $this->assertEquals(1, $result->getPaginationResult()->getPage());
        $this->assertEquals(7, $result->getPaginationResult()->getTotalResultsCount());
        $this->assertEquals(4, $result->getPaginationResult()->getPagesCount());
    }

    public function test_pagination_takes_requested_page_into_account()
    {
        $query      = new Query;
        $facet      = new Facet;
        $filter     = new Filter;
        $filter->setCondition(['id_category' => 3]);
        $facet->addFilter($filter);
        $facet->setIdentifier('categories');
        $query->addFacet($facet);

        $pagination = new PaginationQuery;

        $pagination->setPage(1)->setResultsPerPage(2);
        $query->setPagination($pagination);
        $firstTwoProducts = $this
            ->lister
            ->listProducts($this->context, $query)
            ->getProducts()
        ;

        $pagination->setPage(2)->setResultsPerPage(2);
        $query->setPagination($pagination);
        $nextTwoProducts = $this
            ->lister
            ->listProducts($this->context, $query)
            ->getProducts()
        ;

        $this->assertNotSame($firstTwoProducts, $nextTwoProducts);
    }

    public function test_products_are_found_by_category_and_color()
    {
        $query      = new Query;

        $facet      = new Facet;
        $filter     = new Filter;
        $filter
            ->setCondition(['id_category' => 4])
            ->setEnabled()
        ;
        $facet
            ->addFilter($filter)
            ->setIdentifier('categories')
        ;
        $query->addFacet($facet);

        $facet = new Facet;
        $facet
            ->setCondition(['id_attribute_group' => 3])
            ->setIdentifier('attributeGroup3')
        ;
        $filter = new Filter;
        $filter
            ->setCondition(['id_attribute' => 11])
            ->setEnabled()
        ;
        $facet->addFilter($filter);
        $query->addFacet($facet);

        $result = $this->lister->listProducts($this->context, $query);
        $this->assertInstanceOf(
            'PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryResult',
            $result
        );

        $this->assertCount(1, $result->getProducts());
    }
}
