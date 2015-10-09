<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Filter;

class WeightFilter extends RangeFilter
{
    /**
     * @var float
     */
    private $weight;

    public function setWeight($weight)
    {
        $this->weight = $weight;
        return $this;
    }

    public function getWeight()
    {
        return $this->weight;
    }
}
