<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter;

use PrestaShop\PrestaShop\Core\Business\Product\ProductQueryInterface;

abstract class AbstractProductFilter
{
    private $enabled = false;
    private $label;

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

    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    public function getLabel()
    {
        return $this->label;
    }

    abstract public function serializeCriterium();
    abstract public function unserializeCriterium($string);
    abstract public function getQueryHelper();
}
