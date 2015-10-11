<?php

namespace PrestaShop\PrestaShop\Tests\Integration\Adapter;

use PrestaShop\PrestaShop\Tests\TestCase\IntegrationTestCase;
use PrestaShop\PrestaShop\Core\Business\Product\ProductQuery;
use PrestaShop\PrestaShop\Core\Business\Product\ProductQueryContext;
use PrestaShop\PrestaShop\Core\Business\Product\Filter\CategoryFilter;
use Adapter_ProductFilterPresenter;
use Context;

class Adapter_ProductFilterPresenter_Test extends IntegrationTestCase
{
    private $presenter;
    private $context;

    public function setup()
    {
        parent::setup();
        $this->presenter = new Adapter_ProductFilterPresenter;

        $psContext = Context::getContext();
        parent::setup();
        $this->context = new ProductQueryContext;
        $this->context
            ->setShopId($psContext->shop->id)
            ->setLanguageId($psContext->language->id)
        ;
    }


    public function test_category_filters_are_grouped()
    {
        $query = (new ProductQuery('and'))
            ->addFilter((new CategoryFilter)->setCategoryId(3)->setEnabled(true))           // "Women"
            ->addFilter((new ProductQuery('or'))
                ->addFilter((new CategoryFilter)->setCategoryId(4)->setEnabled(false))      // "Tops"
                ->addFilter((new CategoryFilter)->setCategoryId(8)->setEnabled(false))      // "Dresses"
            )
        ;

        $this->assertEquals([
            'type'     => null,
            'operator' => 'and',
            'facets'   => [
                [
                    'type'      => 'CategoryFilter',
                    'operator'  => 'or',
                    'choices'    => [
                        [
                            'name' => 'query[/and/CategoryFilter][]',
                            'value' => '{"categoryId":3,"className":"CategoryFilter"}',
                            'enabled' => true,
                            'label' => 'Women'
                        ]
                    ]
                ],
                [
                    'type'     => null,
                    'operator' => 'or',
                    'facets'   => [
                        [
                            'type'      => 'CategoryFilter',
                            'operator'  => 'or',
                            'choices'   => [
                                [
                                    'name' => 'query[/and/or/CategoryFilter][]',
                                    'value' => '{"categoryId":4,"className":"CategoryFilter"}',
                                    'enabled' => false,
                                    'label' => 'Tops'
                                ],
                                [
                                    'name' => 'query[/and/or/CategoryFilter][]',
                                    'value' => '{"categoryId":8,"className":"CategoryFilter"}',
                                    'enabled' => false,
                                    'label' => 'Dresses'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ], $this->presenter->present($this->context, $query));
    }
}
