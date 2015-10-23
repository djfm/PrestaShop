<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

class Facet
{
    private $filters = [];
    private $filtersByIdentifier = [];
    private $label;
    private $identifier;
    private $condition;
    private $hidden;

    public function addFilter(Filter $filter)
    {
        $this->filters[] = $filter;
        $this->filtersByIdentifier[$filter->getIdentifier()] = $filter;
        return $this;
    }

    public function clearFilters()
    {
        $this->filters = [];
        $this->filtersByIdentifier = [];
        return $this;
    }

    public function getFilters()
    {
        return $this->filters;
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

    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function hide($hidden = true)
    {
        $this->hidden = $hidden;
        return $this;
    }
    public function isHidden()
    {
        return $this->hidden;
    }

    public function setCondition($condition)
    {
        $this->condition = $condition;
        return $this;
    }

    public function getCondition()
    {
        return $this->condition;
    }

    public function merge(Facet $facet = null)
    {
        if (null !== $facet) {
            foreach ($facet->getFilters() as $initialFilter) {
                $targetFilter = $this->getFilterByIdentifier($initialFilter->getIdentifier());
                if ($targetFilter) {
                    $targetFilter->setEnabled($initialFilter->isEnabled());
                }
            }
        }
        return $this;
    }

    public function getFilterByIdentifier($identifier)
    {
        if (array_key_exists($identifier, $this->filtersByIdentifier)) {
            return $this->filtersByIdentifier[$identifier];
        }
        return null;
    }
}
