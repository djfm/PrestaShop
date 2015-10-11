<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Filter;

interface ProductQueryInterface
{
    public function getFilters();
    public function getOperator();
    public function isEnabled();
}
