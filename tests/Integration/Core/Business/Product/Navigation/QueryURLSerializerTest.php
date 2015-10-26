<?php

namespace PrestaShop\PrestaShop\Tests\Integration\Core\Business\Product\Navigation;

use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryURLSerializer;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Query;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\DatabaseQuery;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Facet;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\ProductLister;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryContext;
use PrestaShop\PrestaShop\Tests\TestCase\IntegrationTestCase;
use Adapter_ServiceLocator;
use Context;
use Phake;

class QueryURLSerializerTest extends IntegrationTestCase
{
    private $serializer;
    private $productLister;
    private $context;
    private $db;

    public function setup()
    {
        parent::setup();

        $this->serializer = Adapter_ServiceLocator::get(
            'PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryURLSerializer'
        );

        $this->db = Adapter_ServiceLocator::get(
            'PrestaShop\PrestaShop\Core\Foundation\Database\AutoPrefixingDatabase'
        );

        $this->productLister = Adapter_ServiceLocator::get(
            'PrestaShop\PrestaShop\Core\Business\Product\Navigation\ProductLister'
        );

        $psContext = Context::getContext();

        $this->context = new QueryContext;
        $this->context
            ->setShopId($psContext->shop->id)
            ->setLanguageId($psContext->language->id)
        ;
    }

    private function getURLFragmentFromQuery(Query $query)
    {
        return $this->serializer->getURLFragmentFromQuery($query);
    }

    private function getQueryFromURLFragment($fragment, Query $defaultQuery)
    {
        $query = $this->productLister->getQueryTemplate($this->context, $defaultQuery);
        return $this->serializer->getQueryFromURLFragment($this->context, $query, $fragment);
    }

    private function getFiltersIdentifiers(Query $query)
    {
        $identifiers = [];
        foreach ($query->getFacets() as $facet) {
            foreach ($facet->getFilters() as $filter) {
                if ($filter->isEnabled()) {
                    $identifiers[] = $filter->getIdentifier();
                }
            }
        }
        return $identifiers;
    }

    private function doTest($expectedFragment, Query $query)
    {
        $this->assertEquals(
            $expectedFragment,
            $this->getURLFragmentFromQuery($query),
            'Did not serialize properly.'
        );

        $unserializedQuery = $this->getQueryFromURLFragment(
            $expectedFragment,
            new Query
        );

        $this->assertEquals(
            $this->getFiltersIdentifiers($query),
            $this->getFiltersIdentifiers($unserializedQuery),
            'Did not unserialize properly.'
        );
    }

    public function test_category_facet_with_one_filter_is_serialized()
    {
        $query = new Query;
        $facet = new Facet;
        $facet->setLabel('Category');
        $facet->addFilter((new Filter)
            ->setLabel('Women')
            ->setEnabled()
            ->setCondition(['id_category' => 3])
        );
        $query->addFacet($facet);

        $this->doTest('Category-Women', $query);
    }

    public function test_category_facet_with_two_filters_is_serialized()
    {
        $query = new Query;
        $facet = new Facet;
        $facet->setLabel('Category');
        $facet->addFilter((new Filter)
            ->setLabel('Women')
            ->setEnabled()
            ->setCondition(['id_category' => 3])
        );
        $facet->addFilter((new Filter)
            ->setLabel('Tops')
            ->setEnabled()
            ->setCondition(['id_category' => 4])
        );
        $query->addFacet($facet);

        $this->doTest('Category-Women-Tops', $query);
    }

    public function test_disabled_filters_are_not_included_in_url()
    {
        $query = new Query;
        $facet = new Facet;
        $facet->setLabel('Category');
        $facet->addFilter((new Filter)
            ->setLabel('Women')
            ->setEnabled()
            ->setCondition(['id_category' => 3])
        );
        $facet->addFilter((new Filter)
            ->setLabel('Tops')
            ->setEnabled(false)
            ->setCondition(['id_category' => 4])
        );
        $query->addFacet($facet);

        $this->doTest('Category-Women', $query);
    }

    public function test_two_facets_are_serialized()
    {
        $query = (new Query)
            ->addFacet((new Facet)
                ->setLabel('Category')
                ->addFilter((new Filter)
                    ->setLabel('Women')
                    ->setEnabled()
                    ->setCondition(['id_category' => 3])
                )
                ->addFilter((new Filter)
                    ->setLabel('Tops')
                    ->setEnabled(true)
                    ->setCondition(['id_category' => 4])
                )
            )
            ->addFacet((new Facet)
                ->setLabel('Color')
                ->addFilter((new Filter)
                    ->setCondition(['id_attribute' => 11])
                    ->setEnabled(true)
                    ->setLabel('Black')
                )
            )
        ;

        $this->doTest('Category-Women-Tops/Color-Black', $query);
    }

    public function test_two_ambiguous_facets_are_serialized()
    {
        $query = (new DatabaseQuery($this->db, $this->context))
            ->addFacet((new Facet)
                ->setLabel('Category')
                ->setIdentifier('categories')
                ->addFilter((new Filter)
                    ->setCondition(['id_category' => 3])
                    ->setLabel('Women')
                    ->setEnabled()
                )
            )
            ->addFacet((new Facet)
                ->setLabel('Category')
                ->setIdentifier('categories')
                ->addFilter((new Filter)
                    ->setCondition(['id_category' => 4])
                    ->setLabel('Tops')
                    ->setEnabled()
                )
            )
        ;

        $this->productLister = Phake::partialMock(
            'PrestaShop\PrestaShop\Core\Business\Product\Navigation\ProductLister',
            $this->db
        );

        Phake::when($this->productLister)->getQueryTemplate(Phake::anyParameters())->thenReturn($query);

        $this->doTest('Category-Women/Category---Tops', $query);
    }
}
