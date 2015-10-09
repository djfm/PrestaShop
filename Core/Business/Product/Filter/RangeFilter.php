<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Filter;

abstract class RangeFilter extends AbstractProductFilter
{
    private $fromInclusive;
    private $toExclusive;

    public function setFromInclusive($fromInclusive)
    {
        $this->fromInclusive = $fromInclusive;
        return $this;
    }

    public function getFromInclusive()
    {
        return $this->fromInclusive;
    }

    public function setToExclusive($toExclusive)
    {
        $this->toExclusive = $toExclusive;
        return $this;
    }

    public function getToExclusive()
    {
        return $this->toExclusive;
    }
}
