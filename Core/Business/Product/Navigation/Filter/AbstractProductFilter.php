<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter;

use PrestaShop\PrestaShop\Core\Business\Product\ProductQueryInterface;
use Serializable;

abstract class AbstractProductFilter
{
    private $enabled = false;

    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    public function getFilterType()
    {
        $tmp = explode('\\', get_called_class());
        return end($tmp);
    }

    public function getIdentifier()
    {
        return $this->getFilterType() . $this->serializeCriterium();
    }

    abstract public function getDataDomain();
    abstract public function serializeCriterium();
    abstract public function unserializeCriterium($string);
}
