<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Filter;

use PrestaShop\PrestaShop\Core\Business\Product\ProductQueryInterface;

abstract class AbstractProductFilter implements ProductQueryInterface
{
    private $enabled;

    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    public function getFilters()
    {
        return [$this];
    }

    public function getOperator()
    {
        return null;
    }

    abstract public function toJSON();
}
