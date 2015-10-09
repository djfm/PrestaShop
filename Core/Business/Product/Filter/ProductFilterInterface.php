<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Filter;

interface ProductFilterInterface
{
    public function getFilters();
    public function getOperator();
    public function isEnabled();
}
