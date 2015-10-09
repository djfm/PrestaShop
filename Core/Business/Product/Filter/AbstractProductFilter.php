<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Filter;

abstract class AbstractProductFilter implements ProductFilterInterface
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
}
