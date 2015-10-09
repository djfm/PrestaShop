<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Filter;

class PriceFilter extends RangeFilter
{
    /**
     * @var float
     */
    private $price;

    public function setPrice($price)
    {
        $this->price = $price;
        return $this;
    }

    public function getPrice()
    {
        return $this->price;
    }
}
