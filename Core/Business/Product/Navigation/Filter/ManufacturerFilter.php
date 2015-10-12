<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation\Filter;

class ManufacturerFilter extends AbstractProductFilter
{
    /**
     * @var int
     */
    private $manufacturerId;

    public function setManufacturerId($manufacturerId)
    {
        $this->manufacturerId = $manufacturerId;
        return $this;
    }

    public function getManufacturerId()
    {
        return $this->manufacturerId;
    }
}
