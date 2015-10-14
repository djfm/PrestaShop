<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

use PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter\AbstractProductFilter;

class Facet
{
    private $filters = [];
    private $name;
    private $identifier;

    public function addFilter(AbstractProductFilter $filter)
    {
        $this->filters[$filter->getIdentifier()] = $filter;
        return $this;
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function getFilterByIdentifier($identifier)
    {
        if (array_key_exists($identifier, $this->filters)) {
            return $this->filters[$identifier];
        }
        return null;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }
}
