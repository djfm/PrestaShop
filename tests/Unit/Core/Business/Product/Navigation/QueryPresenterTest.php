<?php

namespace PrestaShop\PrestaShop\Tests\Unit\Core\Business\Product\Navigation;

use PHPUnit_Framework_Testcase;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Query;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Facet;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryContext;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryPresenter;
use Phake;

class QueryPresenterTest extends PHPUnit_Framework_Testcase
{
    private $presenter;

    private $db;
    private $context;

    public function setup()
    {
        $this->db = Phake::mock(
            'PrestaShop\PrestaShop\Core\Foundation\Database\AutoPrefixingDatabase'
        );
        $this->context = new QueryContext;
        $this->presenter = new QueryPresenter;
    }

    private function newFacet($identifier, $condition = null)
    {
        $facet = new Facet;
        $facet->setIdentifier($identifier);
        $facet->setCondition($condition);
        return $facet;
    }

    private function newFilter($label, $magnitude = 0)
    {
        return (new Filter)->setLabel($label)->setCondition([$label])->setMagnitude($magnitude);
    }

    public function test_query_is_serialized_in_a_template_friendly_fashion()
    {
        $query = new Query;
        $facet1 = $this->newFacet('first')->setLabel('First Facet');
        $facet2 = $this->newFacet('second', ['test'])->setLabel('Second Facet');

        $facet1
            ->addFilter($this->newFilter('fake 1', 1))
            ->addFilter($this->newFilter('fake 2', 2))
        ;

        $facet2
            ->addFilter($this->newFilter('fake 3', 3)->setEnabled())
            ->addFilter($this->newFilter('fake 4', 4)->setEnabled())
        ;

        $query
            ->addFacet($facet1)
            ->addFacet($facet2)
        ;

        $this->assertEquals([
            [
                'label' => 'First Facet',
                'identifier' => 'first',
                'condition' => 'null',
                'hidden'    => false,
                'filters' => [
                    [
                        'label'     => 'fake 1',
                        'magnitude' => 1,
                        'inputName' => 'query[first][fake 1]',
                        'inputValue' => '["fake 1"]',
                        'enabled'   => false
                    ],
                    [
                        'label'     => 'fake 2',
                        'magnitude' => 2,
                        'inputName' => 'query[first][fake 2]',
                        'inputValue' => '["fake 2"]',
                        'enabled'   => false
                    ]
                ]
            ],
            [
                'label' => 'Second Facet',
                'identifier' => 'second',
                'condition' => '["test"]',
                'hidden'    => false,
                'filters' => [
                    [
                        'label'     => 'fake 3',
                        'magnitude' => 3,
                        'inputName' => 'query[second][fake 3]',
                        'inputValue' => '["fake 3"]',
                        'enabled'   => true
                    ],
                    [
                        'label'     => 'fake 4',
                        'magnitude' => 4,
                        'inputName' => 'query[second][fake 4]',
                        'inputValue' => '["fake 4"]',
                        'enabled'   => true
                    ],
                ]
            ]
        ], $this->presenter->present($query));
    }

    public function test_facets_with_zero_filters_are_hidden()
    {
        $query = new Query;
        $facet = $this->newFacet('first')->setLabel('First Facet');
        $query->addFacet($facet);

        $this->assertEquals([
            [
                'label' => 'First Facet',
                'condition' => 'null',
                'identifier' => 'first',
                'hidden'     => true,
                'filters' => []
            ]
        ], $this->presenter->present($query));
    }

    public function test_hidden_facet_is_hidden()
    {
        $query = new Query;
        $facet = $this->newFacet('first')->setLabel('First Facet');
        $facet->addFilter($this->newFilter('fake 1', 42));
        $facet->hide();
        $query->addFacet($facet);

        $this->assertEquals([
            [
                'label' => 'First Facet',
                'condition' => 'null',
                'identifier' => 'first',
                'hidden'     => true,
                'filters' => [
                    [
                        'label'     => 'fake 1',
                        'magnitude' => 42,
                        'inputName' => 'query[first][fake 1]',
                        'inputValue' => '["fake 1"]',
                        'enabled'   => false
                    ]
                ]
            ]
        ], $this->presenter->present($query));
    }
}
