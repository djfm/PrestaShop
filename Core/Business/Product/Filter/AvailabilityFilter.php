<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Filter;

class AvailabilityFilter extends AbstractProductFilter
{
    /**
     * @var int
     */
    private $available;

    public function setAvailable($available = true)
    {
        $this->available = $available;
        return $this;
    }

    public function isAvailable()
    {
        return $this->$available;
    }
}
