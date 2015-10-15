<?php

namespace PrestaShop\PrestaShop\Tests\Unit\Core\Business\Product\Navigation;

use PHPUnit_Framework_Testcase;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Query;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Facet;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryContext;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryPresenter;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter\AbstractProductFilter;

class FakeProductFilter extends AbstractProductFilter
{
    private $criterium;

    public function __construct($criterium)
    {
        $this->criterium = $criterium;
        $this->setLabel('FakeProductFilter');
    }

    public function serializeCriterium()
    {
        return $this->criterium;
    }

    public function unserializeCriterium($criterium)
    {
        $this->criterium = $criterium;
        return $this;
    }

    public function getQueryHelper()
    {
        return null;
    }
}

class QueryPresenterTest extends PHPUnit_Framework_Testcase
{
    private $presenter;

    public function setup()
    {
        $this->presenter = new QueryPresenter;
    }

    public function test_query_is_serialized_in_a_template_friendly_fashion()
    {
        $query = new Query;
        $facet1 = (new Facet)->setName('First Facet')->setIdentifier('first');
        $facet2 = (new Facet)->setName('Second Facet')->setIdentifier('second');

        $facet1
            ->addFilter(new FakeProductFilter('fake 1'))
            ->addFilter(new FakeProductFilter('fake 2'))
        ;

        $facet2
            ->addFilter((new FakeProductFilter('fake 3'))->setEnabled())
        ;

        $query
            ->addFacet($facet1)
            ->addFacet($facet2)
        ;

        $this->assertEquals([
            [
                'name' => 'First Facet',
                'identifier' => 'first',
                'filters' => [
                    [
                        'label'     => 'FakeProductFilter',
                        'inputName' => 'query[0][]',
                        'inputValue' => '{"filterType":"FakeProductFilter","criterium":"fake 1","enabled":false}',
                        'enabled'   => false
                    ],
                    [
                        'label'     => 'FakeProductFilter',
                        'inputName' => 'query[0][]',
                        'inputValue' => '{"filterType":"FakeProductFilter","criterium":"fake 2","enabled":false}',
                        'enabled'   => false
                    ]
                ]
            ],
            [
                'name' => 'Second Facet',
                'identifier' => 'second',
                'filters' => [
                    [
                        'label'     => 'FakeProductFilter',
                        'inputName' => 'query[1][]',
                        'inputValue' => '{"filterType":"FakeProductFilter","criterium":"fake 3","enabled":true}',
                        'enabled'   => true
                    ],
                ]
            ]
        ], $this->presenter->present($query));
    }
}
