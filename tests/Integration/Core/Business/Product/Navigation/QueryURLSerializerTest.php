<?php

namespace PrestaShop\PrestaShop\Tests\Integration\Core\Business\Product\Navigation;

use PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryURLSerializer;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter\CategoryFilter;
use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter\AttributeFilter;
use PrestaShop\PrestaShop\Tests\TestCase\IntegrationTestCase;
use Adapter_ServiceLocator;

class QueryURLSerializerTest extends IntegrationTestCase
{
    private $serializer;

    public function setup()
    {
        parent::setup();
        $this->serializer = Adapter_ServiceLocator::get(
            'PrestaShop\PrestaShop\Core\Business\Product\Navigation\QueryURLSerializer'
        );
    }

    public function testNothing()
    {
        $this->markTestIncomplete();
    }
}
